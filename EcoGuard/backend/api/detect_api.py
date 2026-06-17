import os
import threading
import uuid

from flask import Blueprint, current_app, jsonify, request

from api.auth_helpers import get_session_user, is_admin_user
from api.response_helpers import json_ok
from api.detect_helpers import (
    _HAS_CV2,
    _normalize_ingest_detection,
    complete_task,
    create_detect_task,
    cv2,
    ensure_storage_dirs,
    fail_task,
    get_detection_dependency_report,
    get_detection_runtime_error,
    is_allowed_image,
    is_allowed_video,
    json_error,
    load_detector,
    logger,
    parse_geo_from_form,
    parse_int,
    parse_optional_float,
    read_uploaded_file,
    resolve_location,
    save_detect_items,
    save_uploaded_file,
)
from api.detect_request_helpers import (
    build_image_result_paths,
    ensure_image_size_with_cleanup,
    ensure_video_size_with_cleanup,
    parse_image_request,
    parse_ingest_payload,
    parse_video_request,
)
from database.db import db
from database.models import DetectTask, Robot


detect_bp = Blueprint('detect_bp', __name__)


# ---------------------------------------------------------------------------
# 内部辅助
# ---------------------------------------------------------------------------

def _resolve_task_owner_user_id(device_id=None):
    user = get_session_user()
    if user:
        return user.id
    if device_id:
        robot = Robot.query.filter_by(device_id=device_id).first()
        if robot:
            return robot.owner_user_id
    return None


def _can_access_task(user, task):
    if not user:
        return False
    if is_admin_user(user):
        return True
    return getattr(task, 'user_id', None) == getattr(user, 'id', None)


def _serialize_detect_items(task):
    """统一序列化检测项列表。"""
    return [
        {
            'label': item.label,
            'confidence': f'{item.confidence * 100:.2f}%',
            'bbox': [item.x1, item.y1, item.x2, item.y2],
            'frame_index': item.frame_index,
            'snapshot_path': item.snapshot_path,
        }
        for item in task.items
    ]


# ---------------------------------------------------------------------------
# 路由
# ---------------------------------------------------------------------------

@detect_bp.route('/detect/dependencies', methods=['GET'])
def detect_dependencies():
    report = get_detection_dependency_report()
    return jsonify({'ok': True, **report})


@detect_bp.route('/detect/ingest', methods=['POST'])
def ingest_detection_result():
    payload = request.get_json(silent=True) or {}
    try:
        ctx = parse_ingest_payload(
            payload, _normalize_ingest_detection,
            parse_optional_float, resolve_location, parse_int,
        )
    except ValueError as exc:
        return jsonify({'ok': False, 'message': str(exc)}), 400

    if not ctx['normalized_detections']:
        return json_ok({
            'task_id': None, 'inserted_items': 0,
            'skipped_items': ctx['skipped_count'],
            'recorded': False, 'task': None,
        })

    task = create_detect_task(
        source_rel_path=ctx['source_rel_path'],
        result_rel_path=ctx['result_rel_path'],
        source_type=ctx['source_type'],
        lat=ctx['latitude'], lng=ctx['longitude'],
        location=ctx['location'], device_id=ctx['device_id'],
        user_id=_resolve_task_owner_user_id(ctx['device_id']),
    )
    try:
        save_detect_items(
            task, ctx['normalized_detections'],
            snapshot_rel_path=ctx['result_rel_path'],
            frame_index=ctx['frame_index'],
        )
        complete_task(task)
    except Exception as exc:
        fail_task(task, exc)
        logger.exception('Ingest persist failed task_id=%s', task.id)
        return json_error(exc, 500)

    return json_ok({
        'task_id': task.id,
        'inserted_items': len(ctx['normalized_detections']),
        'skipped_items': ctx['skipped_count'],
        'recorded': True,
        'task': {'id': task.id},
    })


@detect_bp.route('/detect', methods=['POST'])
def detect_image():
    if err := get_detection_runtime_error(require_video=False):
        return json_error(err, 503)

    ensure_storage_dirs()
    uploaded = read_uploaded_file(['image', 'file'])

    try:
        img_ctx = parse_image_request(
            request.form, uploaded, parse_geo_from_form, is_allowed_image,
        )
    except ValueError as exc:
        return jsonify({'ok': False, 'message': str(exc)}), 400

    location = resolve_location(img_ctx['latitude'], img_ctx['longitude'])
    fname, abs_path, rel_path = save_uploaded_file(uploaded)

    max_size = current_app.config.get('IMAGE_MAX_CONTENT_LENGTH', 10 * 1024 * 1024)
    if not ensure_image_size_with_cleanup(abs_path, max_size):
        return jsonify({
            'ok': False,
            'message': f'图片超过 {max_size // (1024 * 1024)} MB 限制',
        }), 413

    result_abs, result_rel = build_image_result_paths(
        current_app.config['RESULT_DIR'], fname,
    )
    task = None

    try:
        detector = load_detector()
        if not detector:
            raise RuntimeError('YOLO 模型未就绪')

        conf = current_app.config.get('YOLO_CONF_THRESHOLD', 0.25)
        detections = detector.analyze_uploaded_waste_image(
            abs_path, save_result=True,
            result_path=result_abs, conf_thres=conf,
        )

        if not detections:
            return json_ok({
                'result': [], 'annotated_image_path': result_rel,
                'recorded': False, 'task': None,
            })

        task = create_detect_task(
            source_rel_path=rel_path, result_rel_path=result_rel,
            source_type=img_ctx['source_type'],
            lat=img_ctx['latitude'], lng=img_ctx['longitude'],
            location=location, device_id=img_ctx['device_id'],
            user_id=_resolve_task_owner_user_id(img_ctx['device_id']),
        )
        items = save_detect_items(
            task, detections, snapshot_rel_path=result_rel, frame_index=0,
        )
        complete_task(task)
        return json_ok({
            'result': items, 'annotated_image_path': result_rel,
            'recorded': True, 'task': {'id': task.id},
        })

    except Exception as exc:
        if task:
            fail_task(task, exc)
            logger.exception('Image detection failed task_id=%s', task.id)
        else:
            logger.exception('Image detection failed (no task)')
        return json_error(exc, 500)


# ---------------------------------------------------------------------------
# 视频检测（后台线程）
# ---------------------------------------------------------------------------

def _process_video_background(app, task_id, source_abs, conf_thres,
                              frame_step, max_frames, result_dir):
    with app.app_context():
        task = db.session.get(DetectTask, task_id)
        if not task:
            logger.error('Video background task not found id=%s', task_id)
            return

        cap = None
        try:
            detector = load_detector()
            if not detector:
                raise RuntimeError('YOLO 模型未就绪')

            cap = cv2.VideoCapture(source_abs)
            if not cap.isOpened():
                raise RuntimeError('无法打开视频文件')

            frame_idx = 0
            processed = 0
            hit_frames = 0
            while True:
                ok, frame = cap.read()
                if not ok:
                    break
                frame_idx += 1
                if frame_idx % frame_step != 0:
                    continue
                processed += 1

                dets, annotated = detector.analyze_realtime_camera_stream(
                    frame, conf_thres=conf_thres,
                )
                if not dets:
                    if processed >= max_frames:
                        break
                    continue

                hit_frames += 1
                snap_name = f'{task.id}_frame_{frame_idx}_{uuid.uuid4().hex[:8]}.jpg'
                snap_abs = os.path.join(result_dir, snap_name)
                snap_rel = f'static/results/{snap_name}'
                if not cv2.imwrite(snap_abs, annotated):
                    raise IOError(f'快照保存失败: {snap_abs}')

                if not task.result_path:
                    task.result_path = snap_rel
                save_detect_items(
                    task, dets, snapshot_rel_path=snap_rel,
                    frame_index=frame_idx,
                )

                if processed >= max_frames:
                    break

            complete_task(task)
            logger.info(
                'Video done task_id=%s processed=%s hits=%s',
                task_id, processed, hit_frames,
            )

        except Exception as exc:
            fail_task(task, exc)
            logger.exception('Video failed task_id=%s', task_id)
        finally:
            if cap is not None:
                cap.release()


@detect_bp.route('/detect/video', methods=['POST'])
def detect_video():
    if err := get_detection_runtime_error(require_video=True):
        return json_error(err, 503)
    if not _HAS_CV2:
        return json_error('OpenCV 未安装，不支持视频检测', 503)

    ensure_storage_dirs()
    uploaded = read_uploaded_file(['video', 'file'])

    try:
        vid_ctx = parse_video_request(
            request.form, uploaded, parse_geo_from_form,
            parse_int, is_allowed_video,
        )
    except ValueError as exc:
        return jsonify({'ok': False, 'message': str(exc)}), 400

    _, abs_path, rel_path = save_uploaded_file(uploaded)

    max_size = current_app.config.get('VIDEO_MAX_CONTENT_LENGTH', 200 * 1024 * 1024)
    if not ensure_video_size_with_cleanup(abs_path, max_size):
        return jsonify({
            'ok': False,
            'message': f'视频超过 {max_size // (1024 * 1024)} MB 限制',
        }), 413

    location = resolve_location(vid_ctx['latitude'], vid_ctx['longitude'])
    conf = current_app.config.get('YOLO_CONF_THRESHOLD', 0.25)
    result_dir = current_app.config['RESULT_DIR']

    task = create_detect_task(
        source_rel_path=rel_path, result_rel_path=None, source_type='video',
        lat=vid_ctx['latitude'], lng=vid_ctx['longitude'],
        location=location, device_id=vid_ctx['device_id'],
        user_id=_resolve_task_owner_user_id(vid_ctx['device_id']),
    )

    app_ref = current_app._get_current_object()
    threading.Thread(
        target=_process_video_background,
        args=(app_ref, task.id, abs_path, conf,
              vid_ctx['frame_step'], vid_ctx['max_frames'], result_dir),
        daemon=True,
    ).start()

    return json_ok({
        'task_id': task.id, 'status': 'PROCESSING',
        'status_url': f'/api/detect/video/status/{task.id}',
        'task': {'id': task.id, 'source_type': task.source_type},
    })


@detect_bp.route('/detect/video/status/<int:task_id>', methods=['GET'])
def detect_video_status(task_id):
    task = db.session.get(DetectTask, task_id)
    if not task:
        return json_error('任务不存在', 404)

    user = get_session_user()
    if not user:
        return json_error('请先登录', 401)
    if not _can_access_task(user, task):
        return json_error('无权限访问该任务', 403)

    payload = {
        'task_id': task.id, 'status': task.status,
        'source_type': task.source_type,
    }
    if task.status == 'DONE':
        payload['annotated_image_path'] = task.result_path
        payload['result'] = _serialize_detect_items(task)
    elif task.status == 'FAILED':
        payload['error'] = task.error_msg

    return json_ok(payload)