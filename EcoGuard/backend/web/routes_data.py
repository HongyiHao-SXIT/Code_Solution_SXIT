import json

from flask import jsonify, request

from .blueprint import web_bp
from .helpers import (
    _get_current_user,
    _is_admin_user,
    _can_access_robot,
    _load_task_with_items,
    _parse_page_number,
    _query_latest_items,
    _query_latest_tasks,
    _serialize_detect_item,
    _serialize_detect_item_row,
    _serialize_robot,
    _serialize_task,
    _delete_task_with_items,
    api_login_required,
)
from database.db import db
from database.models import OpsLog, Robot


_MAX_LOG_ACTION_LEN = 255

# ---- pagination helpers (merged from services_data.py) ----

def _build_pagination_payload(pagination):
    return {
        'page': pagination.page,
        'pages': pagination.pages,
        'total': pagination.total,
        'has_prev': pagination.has_prev,
        'has_next': pagination.has_next,
        'prev_num': pagination.prev_num,
        'next_num': pagination.next_num,
    }


def _build_paginated_collection(pagination, can_delete, key, items):
    return {
        'ok': True,
        key: items,
        'pagination': _build_pagination_payload(pagination),
        'can_delete': bool(can_delete),
    }


def _build_tasks_payload(pagination, can_delete, serialize_task):
    return _build_paginated_collection(
        pagination, can_delete, 'tasks',
        [serialize_task(t) for t in pagination.items])


def _build_items_payload(pagination, can_delete, serialize_item_row):
    return _build_paginated_collection(
        pagination, can_delete, 'items',
        [serialize_item_row(i) for i in pagination.items])


def _build_task_detail_payload(task, can_delete, serialize_task, serialize_item):
    return {
        'ok': True,
        'task': serialize_task(task),
        'items': [serialize_item(item) for item in task.items],
        'can_delete': bool(can_delete),
    }


# ---- misc helpers ----

def _current_user_can_delete_results():
    return _is_admin_user(_get_current_user())


def _request_page_number():
    return _parse_page_number(request.args.get('page'))


def _read_trimmed_payload_text(payload, key, max_len):
    return str(payload.get(key) or '').strip()[:max_len]


def _build_ops_action(message, category, path, source):
    parts = []
    if category:
        parts.append(f'[{category}]')
    if source:
        parts.append(f'({source})')
    parts.append(str(message or ''))
    if path:
        parts.append(f'@{path}')
    return ' '.join(p for p in parts if p).strip()[:_MAX_LOG_ACTION_LEN]


# ---- routes ----

@web_bp.route('/api/web/tasks', methods=['GET'])
@api_login_required
def get_tasks_json():
    current_user = _get_current_user()
    pagination = _query_latest_tasks(_request_page_number(), current_user=current_user)
    return jsonify(_build_tasks_payload(
        pagination, _current_user_can_delete_results(), _serialize_task))


@web_bp.route('/api/web/items', methods=['GET'])
@api_login_required
def get_items_json():
    current_user = _get_current_user()
    pagination = _query_latest_items(_request_page_number(), current_user=current_user)
    return jsonify(_build_items_payload(
        pagination, _current_user_can_delete_results(), _serialize_detect_item_row))


@web_bp.route('/api/web/tasks/<int:task_id>', methods=['GET'])
@api_login_required
def get_task_detail_json(task_id):
    current_user = _get_current_user()
    task = _load_task_with_items(task_id, current_user=current_user)
    return jsonify(_build_task_detail_payload(
        task, _current_user_can_delete_results(), _serialize_task, _serialize_detect_item))


@web_bp.route('/api/web/tasks/<int:task_id>/delete', methods=['POST'])
@api_login_required
def delete_result_json(task_id):
    if not _current_user_can_delete_results():
        return jsonify({'ok': False, 'message': '只有管理员可以删除检测结果'}), 403
    _delete_task_with_items(task_id)
    return jsonify({'ok': True, 'message': f'任务 #{task_id} 已删除'})


@web_bp.route('/api/web/robots/<int:robot_id>', methods=['GET'])
@api_login_required
def get_robot_detail_json(robot_id):
    current_user = _get_current_user()
    robot = db.get_or_404(Robot, robot_id)
    if not _can_access_robot(current_user, robot):
        return jsonify({'ok': False, 'message': '无权限访问该机器人'}), 403
    return jsonify({'ok': True, 'robot': _serialize_robot(robot)})


@web_bp.route('/api/web/client-log', methods=['POST'])
def write_client_log_json():
    payload = request.get_json(silent=True) or {}

    message = _read_trimmed_payload_text(payload, 'message', 512)
    if not message:
        return jsonify({'ok': False, 'message': '日志内容不能为空'}), 400

    category = _read_trimmed_payload_text(payload, 'category', 24)
    path = _read_trimmed_payload_text(payload, 'path', 120)
    source = _read_trimmed_payload_text(payload, 'source', 40)

    meta = payload.get('meta')
    if meta is not None:
        try:
            meta_text = json.dumps(meta, ensure_ascii=False, separators=(',', ':'))
            message = f'{message} meta={meta_text[:160]}'
        except (TypeError, ValueError):
            pass

    current_user = _get_current_user()
    log_item = OpsLog()
    log_item.user_id = getattr(current_user, 'id', None)
    log_item.action = _build_ops_action(message, category, path, source)
    db.session.add(log_item)
    db.session.commit()

    return jsonify({'ok': True, 'id': log_item.id})