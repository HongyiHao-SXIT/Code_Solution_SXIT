"""
EcoGuard SimRobot Qt 操作台

功能:
1) 选择本地文件夹作为模拟数据来源
2) 预览图片/文本数据文件
3) 调用后端 /api/detect 触发模型识别
4) 在表格中手动润色识别结果（标签、置信度、bbox）
5) 调用后端 /api/detect/ingest 上传人工修订后的结果
6) 支持批量识别与批量识别后自动上传

运行示例:
    python Sim_robot_qt.py
    python Sim_robot_qt.py --server http://127.0.0.1:5000 --device-id SIM_QT_001

依赖:
    pip install PySide6 requests
    # 或 pip install PyQt6 requests
"""

from __future__ import annotations

import argparse
import os
import sys
from pathlib import Path
from typing import List, Optional

import requests


try:
    from PySide6.QtCore import Qt
    from PySide6.QtGui import QPixmap
    from PySide6.QtWidgets import (
        QApplication,
        QFileDialog,
        QGridLayout,
        QGroupBox,
        QHBoxLayout,
        QHeaderView,
        QLabel,
        QLineEdit,
        QListWidget,
        QListWidgetItem,
        QMainWindow,
        QMessageBox,
        QPushButton,
        QDoubleSpinBox,
        QSplitter,
        QStatusBar,
        QTableWidget,
        QTableWidgetItem,
        QTextEdit,
        QVBoxLayout,
        QWidget,
    )
    QT_BINDING = "PySide6"
except ImportError:
    try:
        from PyQt6.QtCore import Qt
        from PyQt6.QtGui import QPixmap
        from PyQt6.QtWidgets import (
            QApplication,
            QFileDialog,
            QGridLayout,
            QGroupBox,
            QHBoxLayout,
            QHeaderView,
            QLabel,
            QLineEdit,
            QListWidget,
            QListWidgetItem,
            QMainWindow,
            QMessageBox,
            QPushButton,
            QDoubleSpinBox,
            QSplitter,
            QStatusBar,
            QTableWidget,
            QTableWidgetItem,
            QTextEdit,
            QVBoxLayout,
            QWidget,
        )
        QT_BINDING = "PyQt6"
    except ImportError as exc:
        raise SystemExit(
            "未检测到 Qt 绑定。请安装 PySide6 或 PyQt6。\n"
            "示例: pip install PySide6 requests"
        ) from exc


IMAGE_EXTENSIONS = {".png", ".jpg", ".jpeg", ".bmp", ".webp", ".gif"}
TEXT_EXTENSIONS = {".txt", ".json", ".csv", ".yaml", ".yml", ".log"}
TABLE_COLUMNS = ["标签", "置信度(0-1)", "x1", "y1", "x2", "y2"]

QT_HORIZONTAL = getattr(Qt, "Horizontal", Qt.Orientation.Horizontal)
QT_ALIGN_CENTER = getattr(Qt, "AlignCenter", Qt.AlignmentFlag.AlignCenter)
QT_USER_ROLE = getattr(Qt, "UserRole", Qt.ItemDataRole.UserRole)
QT_KEEP_ASPECT = getattr(Qt, "KeepAspectRatio", Qt.AspectRatioMode.KeepAspectRatio)
QT_SMOOTH_TRANSFORM = getattr(
    Qt,
    "SmoothTransformation",
    Qt.TransformationMode.SmoothTransformation,
)
QHEADER_STRETCH = getattr(QHeaderView, "Stretch", QHeaderView.ResizeMode.Stretch)


class SimRobotWorkbench(QMainWindow):  # type: ignore[misc]
    def __init__(self, server: str, device_id: str):
        super().__init__()
        self.setWindowTitle(f"EcoGuard SimRobot Qt 操作台 ({QT_BINDING})")
        self.resize(1560, 940)

        self.current_folder: Optional[Path] = None
        self.current_file: Optional[Path] = None
        self.last_source_path: Optional[str] = None
        self.last_result_path: Optional[str] = None

        self._build_ui(server=server, device_id=device_id)

    # ----------------------------- UI -----------------------------

    def _build_ui(self, server: str, device_id: str):
        central = QWidget()
        self.setCentralWidget(central)
        root_layout = QVBoxLayout(central)
        root_layout.setContentsMargins(10, 10, 10, 10)
        root_layout.setSpacing(10)

        # 顶部配置栏
        config_box = QGroupBox("平台连接与上传参数")
        cfg_grid = QGridLayout(config_box)

        self.server_edit = QLineEdit(server)
        self.server_edit.setPlaceholderText("后端地址，例如 http://127.0.0.1:5000")

        self.device_edit = QLineEdit(device_id)
        self.device_edit.setPlaceholderText("设备 ID")

        self.location_edit = QLineEdit("模拟道路")
        self.location_edit.setPlaceholderText("地点描述（可选）")

        self.lat_spin = QDoubleSpinBox()
        self.lat_spin.setDecimals(6)
        self.lat_spin.setRange(-90.0, 90.0)
        self.lat_spin.setValue(30.500000)

        self.lng_spin = QDoubleSpinBox()
        self.lng_spin.setDecimals(6)
        self.lng_spin.setRange(-180.0, 180.0)
        self.lng_spin.setValue(114.300000)

        cfg_grid.addWidget(QLabel("服务器"), 0, 0)
        cfg_grid.addWidget(self.server_edit, 0, 1)
        cfg_grid.addWidget(QLabel("设备 ID"), 0, 2)
        cfg_grid.addWidget(self.device_edit, 0, 3)

        cfg_grid.addWidget(QLabel("纬度"), 1, 0)
        cfg_grid.addWidget(self.lat_spin, 1, 1)
        cfg_grid.addWidget(QLabel("经度"), 1, 2)
        cfg_grid.addWidget(self.lng_spin, 1, 3)

        cfg_grid.addWidget(QLabel("位置描述"), 2, 0)
        cfg_grid.addWidget(self.location_edit, 2, 1, 1, 3)

        root_layout.addWidget(config_box)

        # 主区域
        splitter = QSplitter(QT_HORIZONTAL)
        root_layout.addWidget(splitter, 1)

        # 左侧：文件夹与文件列表
        left = QWidget()
        left_layout = QVBoxLayout(left)

        folder_row = QHBoxLayout()
        self.folder_edit = QLineEdit()
        self.folder_edit.setReadOnly(True)
        self.folder_edit.setPlaceholderText("请选择本地模拟数据目录")

        self.pick_folder_btn = QPushButton("选择文件夹")
        self.pick_folder_btn.clicked.connect(self.select_folder)

        folder_row.addWidget(self.folder_edit, 1)
        folder_row.addWidget(self.pick_folder_btn)

        left_layout.addLayout(folder_row)

        self.file_list = QListWidget()
        self.file_list.itemSelectionChanged.connect(self.on_file_selected)
        left_layout.addWidget(self.file_list, 1)

        splitter.addWidget(left)

        # 右侧：预览 + 检测结果编辑
        right = QWidget()
        right_layout = QVBoxLayout(right)

        preview_box = QGroupBox("文件预览")
        preview_layout = QVBoxLayout(preview_box)

        self.preview_title = QLabel("尚未选择文件")
        self.preview_title.setStyleSheet("font-weight: 700;")
        preview_layout.addWidget(self.preview_title)

        self.image_preview = QLabel("图片预览区域")
        self.image_preview.setAlignment(QT_ALIGN_CENTER)
        self.image_preview.setMinimumHeight(300)
        self.image_preview.setStyleSheet("border: 1px solid #d9d9d9; background: #f7f7f7;")
        preview_layout.addWidget(self.image_preview)

        self.text_preview = QTextEdit()
        self.text_preview.setReadOnly(True)
        self.text_preview.setPlaceholderText("文本/数据文件预览")
        self.text_preview.setMinimumHeight(160)
        preview_layout.addWidget(self.text_preview)

        right_layout.addWidget(preview_box)

        action_row = QHBoxLayout()
        self.detect_btn = QPushButton("调用平台识别")
        self.detect_btn.clicked.connect(self.detect_current_file)

        self.batch_detect_btn = QPushButton("批量识别")
        self.batch_detect_btn.clicked.connect(self.batch_detect_files)

        self.batch_detect_upload_btn = QPushButton("批量识别并上传")
        self.batch_detect_upload_btn.clicked.connect(self.batch_detect_and_upload)

        self.add_row_btn = QPushButton("新增结果行")
        self.add_row_btn.clicked.connect(self.add_result_row)

        self.del_row_btn = QPushButton("删除选中行")
        self.del_row_btn.clicked.connect(self.delete_selected_rows)

        self.upload_btn = QPushButton("上传润色结果")
        self.upload_btn.clicked.connect(self.upload_results)

        action_row.addWidget(self.detect_btn)
        action_row.addWidget(self.batch_detect_btn)
        action_row.addWidget(self.batch_detect_upload_btn)
        action_row.addWidget(self.add_row_btn)
        action_row.addWidget(self.del_row_btn)
        action_row.addStretch(1)
        action_row.addWidget(self.upload_btn)
        right_layout.addLayout(action_row)

        self.result_table = QTableWidget(0, len(TABLE_COLUMNS))
        self.result_table.setHorizontalHeaderLabels(TABLE_COLUMNS)
        self.result_table.horizontalHeader().setSectionResizeMode(QHEADER_STRETCH)
        self.result_table.verticalHeader().setVisible(False)
        right_layout.addWidget(self.result_table, 1)

        self.log_view = QTextEdit()
        self.log_view.setReadOnly(True)
        self.log_view.setMinimumHeight(150)
        right_layout.addWidget(self.log_view)

        splitter.addWidget(right)
        splitter.setStretchFactor(0, 2)
        splitter.setStretchFactor(1, 5)

        self.setStatusBar(QStatusBar())
        self.statusBar().showMessage("就绪")

        self._log("Qt 操作台已启动")

    # -------------------------- Helpers ---------------------------

    def _api_base(self) -> str:
        return self.server_edit.text().strip().rstrip("/")

    def _log(self, message: str):
        self.log_view.append(message)
        self.statusBar().showMessage(message)

    def _warn(self, text: str):
        QMessageBox.warning(self, "提示", text)

    def _error(self, text: str):
        QMessageBox.critical(self, "错误", text)

    def _is_image(self, path: Path) -> bool:
        return path.suffix.lower() in IMAGE_EXTENSIONS

    def _is_text_data(self, path: Path) -> bool:
        return path.suffix.lower() in TEXT_EXTENSIONS

    def _set_table_value(self, row: int, col: int, value: str):
        item = QTableWidgetItem(value)
        self.result_table.setItem(row, col, item)

    # ----------------------- Folder / Preview ----------------------

    def select_folder(self):
        folder = QFileDialog.getExistingDirectory(self, "选择模拟数据目录")
        if not folder:
            return

        self.current_folder = Path(folder)
        self.folder_edit.setText(folder)
        self.file_list.clear()

        files = [p for p in self.current_folder.iterdir() if p.is_file()]
        files.sort(key=lambda p: p.name.lower())

        for path in files:
            item = QListWidgetItem(path.name)
            item.setData(QT_USER_ROLE, str(path))
            self.file_list.addItem(item)

        self._log(f"已加载目录: {folder}，共 {len(files)} 个文件")

    def on_file_selected(self):
        items = self.file_list.selectedItems()
        if not items:
            return

        file_path = Path(items[0].data(QT_USER_ROLE))
        self.current_file = file_path
        self.preview_title.setText(f"当前文件: {file_path.name}")

        if self._is_image(file_path):
            self._preview_local_image(file_path)
            self.text_preview.setPlainText("")
            return

        # 非图片文件：显示文件内容/元信息
        self.image_preview.setText("该文件不是图片，见下方文本预览")
        self.image_preview.setPixmap(QPixmap())

        if self._is_text_data(file_path):
            try:
                content = file_path.read_text(encoding="utf-8", errors="replace")
                self.text_preview.setPlainText(content[:12000])
            except OSError as error:
                self.text_preview.setPlainText(f"读取失败: {error}")
        else:
            size_kb = file_path.stat().st_size / 1024.0
            self.text_preview.setPlainText(
                f"文件名: {file_path.name}\n"
                f"路径: {file_path}\n"
                f"大小: {size_kb:.2f} KB\n"
                f"类型: {file_path.suffix or '无扩展名'}"
            )

    def _preview_local_image(self, path: Path):
        pix = QPixmap(str(path))
        if pix.isNull():
            self.image_preview.setText("图片加载失败")
            return

        scaled = pix.scaled(
            self.image_preview.width() if self.image_preview.width() > 10 else 760,
            self.image_preview.height() if self.image_preview.height() > 10 else 380,
            QT_KEEP_ASPECT,
            QT_SMOOTH_TRANSFORM,
        )
        self.image_preview.setPixmap(scaled)

    def _preview_remote_annotated(self, rel_path: str):
        url = f"{self._api_base()}/{rel_path.lstrip('/')}"
        try:
            resp = requests.get(url, timeout=12)
            resp.raise_for_status()
            pix = QPixmap()
            if not pix.loadFromData(resp.content):
                self._log("识别后图片预览失败（解码失败）")
                return
            scaled = pix.scaled(
                self.image_preview.width() if self.image_preview.width() > 10 else 760,
                self.image_preview.height() if self.image_preview.height() > 10 else 380,
                QT_KEEP_ASPECT,
                QT_SMOOTH_TRANSFORM,
            )
            self.image_preview.setPixmap(scaled)
            self._log(f"已预览识别后图片: {rel_path}")
        except requests.RequestException as error:
            self._log(f"识别后图片预览失败: {error}")

    # -------------------------- Detect ----------------------------

    def detect_current_file(self):
        if self.current_file is None:
            self._warn("请先选择文件")
            return

        if not self._is_image(self.current_file):
            self._warn("当前文件不是图片，无法调用 /api/detect")
            return

        result = self._detect_image_file(self.current_file, show_preview=True)
        if not result["ok"]:
            self._error(result["message"])
            return

        detections = result["detections"]
        self._fill_result_table(detections)

        self.last_source_path = result["source_path"]
        self.last_result_path = result["result_path"]

        self._log(f"识别完成: {self.current_file.name}，检测到 {len(detections)} 个目标")

    def _detect_image_file(self, image_path: Path, show_preview: bool = False) -> dict:
        base = self._api_base()
        if not base.startswith("http"):
            return {
                "ok": False,
                "message": "服务器地址格式无效",
                "detections": [],
                "source_path": None,
                "result_path": None,
            }

        url = f"{base}/api/detect"
        self._log(f"开始识别: {image_path.name}")

        try:
            with image_path.open("rb") as fp:
                files = {
                    "image": (image_path.name, fp, "application/octet-stream"),
                }
                data = {
                    "device_id": self.device_edit.text().strip(),
                    "latitude": f"{self.lat_spin.value():.6f}",
                    "longitude": f"{self.lng_spin.value():.6f}",
                    "source_type": "image",
                }
                resp = requests.post(url, files=files, data=data, timeout=60)

            payload = resp.json() if resp.content else {}
            if resp.status_code >= 400 or not payload.get("ok", False):
                msg = payload.get("message") or f"识别失败，HTTP {resp.status_code}"
                self._log(f"识别失败: {msg}")
                return {
                    "ok": False,
                    "message": msg,
                    "detections": [],
                    "source_path": None,
                    "result_path": None,
                }

            detections = payload.get("result") or []

            task = payload.get("task") or {}
            source_path = task.get("source_path") or f"simulator://{image_path.name}"
            result_path = payload.get("annotated_image_path")

            if show_preview and result_path:
                self._preview_remote_annotated(result_path)

            return {
                "ok": True,
                "message": "ok",
                "detections": detections,
                "source_path": source_path,
                "result_path": result_path,
            }

        except requests.RequestException as error:
            self._log(f"识别请求失败: {error}")
            return {
                "ok": False,
                "message": f"请求失败: {error}",
                "detections": [],
                "source_path": None,
                "result_path": None,
            }
        except ValueError as error:
            self._log(f"识别响应解析失败: {error}")
            return {
                "ok": False,
                "message": f"响应解析失败: {error}",
                "detections": [],
                "source_path": None,
                "result_path": None,
            }
        except OSError as error:
            self._log(f"文件读取失败: {error}")
            return {
                "ok": False,
                "message": f"文件读取失败: {error}",
                "detections": [],
                "source_path": None,
                "result_path": None,
            }

    def _list_image_files(self) -> List[Path]:
        images: List[Path] = []
        for i in range(self.file_list.count()):
            item = self.file_list.item(i)
            path = Path(item.data(QT_USER_ROLE))
            if self._is_image(path):
                images.append(path)
        return images

    def batch_detect_files(self):
        image_files = self._list_image_files()
        if not image_files:
            self._warn("当前目录没有可识别的图片文件")
            return

        if not self._api_base().startswith("http"):
            self._warn("服务器地址格式无效")
            return

        self._log(f"批量识别开始，共 {len(image_files)} 张图片")
        ok_count = 0
        fail_count = 0
        failed_names: List[str] = []

        for idx, image_path in enumerate(image_files, start=1):
            self.statusBar().showMessage(f"批量识别中 {idx}/{len(image_files)}: {image_path.name}")
            QApplication.processEvents()

            result = self._detect_image_file(image_path, show_preview=False)
            if not result["ok"]:
                fail_count += 1
                failed_names.append(image_path.name)
                continue

            ok_count += 1
            self.current_file = image_path
            self.last_source_path = result["source_path"]
            self.last_result_path = result["result_path"]
            self._fill_result_table(result["detections"])

        if self.last_result_path:
            self._preview_remote_annotated(self.last_result_path)

        self._log(f"批量识别完成: 成功 {ok_count}，失败 {fail_count}")
        if failed_names:
            fail_text = "\n".join(failed_names[:8])
            QMessageBox.warning(
                self,
                "批量识别完成",
                f"成功 {ok_count}，失败 {fail_count}\n失败文件(最多8个):\n{fail_text}",
            )
        else:
            QMessageBox.information(self, "批量识别完成", f"全部完成，共 {ok_count} 张")

    def _to_upload_detections(self, detections: List[dict]) -> List[dict]:
        normalized: List[dict] = []
        for det in detections:
            label = str(det.get("label") or det.get("class_name") or "Unknown")
            conf = self._normalize_confidence(det.get("confidence"))
            bbox = det.get("bbox") or [0, 0, 0, 0]
            if not isinstance(bbox, list) or len(bbox) != 4:
                bbox = [0, 0, 0, 0]
            normalized.append({
                "label": label,
                "confidence": conf,
                "bbox": [
                    int(float(bbox[0])),
                    int(float(bbox[1])),
                    int(float(bbox[2])),
                    int(float(bbox[3])),
                ],
            })
        return normalized

    def _post_ingest_payload(self, payload: dict) -> dict:
        base = self._api_base()
        url = f"{base}/api/detect/ingest"

        try:
            resp = requests.post(url, json=payload, timeout=30)
            body = resp.json() if resp.content else {}
            if resp.status_code >= 400 or not body.get("ok", False):
                msg = body.get("message") or f"上传失败，HTTP {resp.status_code}"
                return {"ok": False, "message": msg, "body": body}
            return {"ok": True, "message": "ok", "body": body}
        except requests.RequestException as error:
            return {"ok": False, "message": f"上传请求失败: {error}", "body": {}}
        except ValueError as error:
            return {"ok": False, "message": f"上传响应解析失败: {error}", "body": {}}

    def batch_detect_and_upload(self):
        image_files = self._list_image_files()
        if not image_files:
            self._warn("当前目录没有可识别的图片文件")
            return

        if not self._api_base().startswith("http"):
            self._warn("服务器地址格式无效")
            return

        self._log(f"批量识别并上传开始，共 {len(image_files)} 张图片")
        detect_ok = 0
        upload_ok = 0
        fail_count = 0

        for idx, image_path in enumerate(image_files, start=1):
            self.statusBar().showMessage(f"批量识别并上传中 {idx}/{len(image_files)}: {image_path.name}")
            QApplication.processEvents()

            detect_result = self._detect_image_file(image_path, show_preview=False)
            if not detect_result["ok"]:
                fail_count += 1
                continue

            detect_ok += 1
            detections = self._to_upload_detections(detect_result["detections"])

            payload = {
                "source_type": "simulator",
                "device_id": self.device_edit.text().strip() or None,
                "latitude": self.lat_spin.value(),
                "longitude": self.lng_spin.value(),
                "location": self.location_edit.text().strip() or "模拟道路",
                "source_path": detect_result["source_path"] or f"simulator://{image_path.name}",
                "result_path": detect_result["result_path"],
                "detections": detections,
                "frame_index": 0,
            }

            upload_result = self._post_ingest_payload(payload)
            if not upload_result["ok"]:
                fail_count += 1
                self._log(f"上传失败({image_path.name}): {upload_result['message']}")
                continue

            upload_ok += 1
            self.current_file = image_path
            self.last_source_path = payload["source_path"]
            self.last_result_path = payload["result_path"]
            self._fill_result_table(detect_result["detections"])

        if self.last_result_path:
            self._preview_remote_annotated(self.last_result_path)

        self._log(
            f"批量识别并上传完成: 识别成功 {detect_ok}/{len(image_files)}，上传成功 {upload_ok}/{len(image_files)}，失败 {fail_count}"
        )
        QMessageBox.information(
            self,
            "批量任务完成",
            (
                f"总数: {len(image_files)}\n"
                f"识别成功: {detect_ok}\n"
                f"上传成功: {upload_ok}\n"
                f"失败: {fail_count}"
            ),
        )

    def _normalize_confidence(self, raw) -> float:
        if raw is None:
            return 0.0
        if isinstance(raw, (int, float)):
            value = float(raw)
            if value > 1.0:
                value = value / 100.0
            return max(0.0, min(1.0, value))

        text = str(raw).strip().replace("%", "")
        if not text:
            return 0.0
        value = float(text)
        if value > 1.0:
            value = value / 100.0
        return max(0.0, min(1.0, value))

    def _fill_result_table(self, detections: List[dict]):
        self.result_table.setRowCount(0)

        for det in detections:
            row = self.result_table.rowCount()
            self.result_table.insertRow(row)

            label = str(det.get("class_name") or det.get("label") or "Unknown")
            conf = self._normalize_confidence(det.get("confidence"))

            bbox = det.get("bbox") or [0, 0, 0, 0]
            if not isinstance(bbox, list) or len(bbox) != 4:
                bbox = [0, 0, 0, 0]

            self._set_table_value(row, 0, label)
            self._set_table_value(row, 1, f"{conf:.4f}")
            self._set_table_value(row, 2, str(int(float(bbox[0]))))
            self._set_table_value(row, 3, str(int(float(bbox[1]))))
            self._set_table_value(row, 4, str(int(float(bbox[2]))))
            self._set_table_value(row, 5, str(int(float(bbox[3]))))

    # ------------------------ Edit / Upload -----------------------

    def add_result_row(self):
        row = self.result_table.rowCount()
        self.result_table.insertRow(row)
        self._set_table_value(row, 0, "Unknown")
        self._set_table_value(row, 1, "0.5000")
        self._set_table_value(row, 2, "0")
        self._set_table_value(row, 3, "0")
        self._set_table_value(row, 4, "10")
        self._set_table_value(row, 5, "10")

    def delete_selected_rows(self):
        selected_rows = sorted({item.row() for item in self.result_table.selectedItems()}, reverse=True)
        for row in selected_rows:
            self.result_table.removeRow(row)

    def _collect_table_detections(self) -> List[dict]:
        detections: List[dict] = []

        for row in range(self.result_table.rowCount()):
            label_item = self.result_table.item(row, 0)
            conf_item = self.result_table.item(row, 1)
            x1_item = self.result_table.item(row, 2)
            y1_item = self.result_table.item(row, 3)
            x2_item = self.result_table.item(row, 4)
            y2_item = self.result_table.item(row, 5)

            label = (label_item.text().strip() if label_item else "")
            if not label:
                continue

            try:
                confidence = self._normalize_confidence(conf_item.text() if conf_item else "0")
                x1 = int(float(x1_item.text() if x1_item else "0"))
                y1 = int(float(y1_item.text() if y1_item else "0"))
                x2 = int(float(x2_item.text() if x2_item else "0"))
                y2 = int(float(y2_item.text() if y2_item else "0"))
            except ValueError:
                raise ValueError(f"第 {row + 1} 行存在非法数字，请检查置信度或坐标")

            detections.append({
                "label": label,
                "confidence": confidence,
                "bbox": [x1, y1, x2, y2],
            })

        return detections

    def upload_results(self):
        base = self._api_base()
        if not base.startswith("http"):
            self._warn("服务器地址格式无效")
            return

        try:
            detections = self._collect_table_detections()
        except ValueError as error:
            self._warn(str(error))
            return

        if not detections:
            self._warn("没有可上传的识别结果")
            return

        source_path = self.last_source_path
        if not source_path:
            if self.current_file:
                source_path = f"simulator://{self.current_file.name}"
            else:
                source_path = f"simulator://manual_{self.device_edit.text().strip() or 'device'}"

        payload = {
            "source_type": "simulator",
            "device_id": self.device_edit.text().strip() or None,
            "latitude": self.lat_spin.value(),
            "longitude": self.lng_spin.value(),
            "location": self.location_edit.text().strip() or "模拟道路",
            "source_path": source_path,
            "result_path": self.last_result_path,
            "detections": detections,
            "frame_index": 0,
        }

        self._log(f"开始上传人工润色结果，共 {len(detections)} 项")
        result = self._post_ingest_payload(payload)
        if not result["ok"]:
            self._error(result["message"])
            self._log(f"上传失败: {result['message']}")
            return

        body = result["body"]
        task_id = body.get("task_id")
        inserted = body.get("inserted_items")
        skipped = body.get("skipped_items")
        self._log(f"上传成功: task_id={task_id}, inserted={inserted}, skipped={skipped}")
        QMessageBox.information(self, "成功", f"上传完成\n任务ID: {task_id}\n入库条目: {inserted}")


def parse_args():
    parser = argparse.ArgumentParser(description="EcoGuard SimRobot Qt 操作台")
    parser.add_argument("--server", default="http://127.0.0.1:5000", help="后端地址")
    parser.add_argument("--device-id", default="SIM_QT_001", help="模拟设备 ID")
    return parser.parse_args()


def main():
    args = parse_args()
    app = QApplication(sys.argv)
    win = SimRobotWorkbench(server=args.server, device_id=args.device_id)
    win.show()
    if hasattr(app, "exec"):
        sys.exit(app.exec())
    sys.exit(app.exec_())


if __name__ == "__main__":
    main()
