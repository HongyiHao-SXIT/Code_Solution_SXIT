from flask import jsonify, request

from .blueprint import web_bp
from .services_data import (
    build_items_payload,
    build_task_detail_payload,
    build_tasks_payload,
    ensure_admin_user_or_forbidden,
)
from .helpers import (
    _get_current_user,
    _is_admin_user,
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
from database.models import Robot


@web_bp.route('/api/web/tasks', methods=['GET'])
@api_login_required
def get_tasks_json():
    page_number = _parse_page_number(request.args.get('page'))
    pagination = _query_latest_tasks(page_number)
    payload = build_tasks_payload(
        pagination=pagination,
        can_delete=_is_admin_user(_get_current_user()),
        serialize_task=_serialize_task,
    )
    return jsonify(payload)


@web_bp.route('/api/web/items', methods=['GET'])
@api_login_required
def get_items_json():
    page_number = _parse_page_number(request.args.get('page'))
    pagination = _query_latest_items(page_number)
    payload = build_items_payload(
        pagination=pagination,
        can_delete=_is_admin_user(_get_current_user()),
        serialize_item_row=_serialize_detect_item_row,
    )
    return jsonify(payload)


@web_bp.route('/api/web/tasks/<int:task_id>', methods=['GET'])
@api_login_required
def get_task_detail_json(task_id):
    task = _load_task_with_items(task_id)
    payload = build_task_detail_payload(
        task=task,
        can_delete=_is_admin_user(_get_current_user()),
        serialize_task=_serialize_task,
        serialize_item=_serialize_detect_item,
    )
    return jsonify(payload)


@web_bp.route('/api/web/tasks/<int:task_id>/delete', methods=['POST'])
@api_login_required
def delete_result_json(task_id):
    current_user = _get_current_user()
    if not ensure_admin_user_or_forbidden(current_user, _is_admin_user):
        return jsonify({'ok': False, 'message': '只有管理员可以删除检测结果'}), 403

    _delete_task_with_items(task_id)
    return jsonify({'ok': True, 'message': f'任务 #{task_id} 已删除'})


@web_bp.route('/api/web/robots/<int:robot_id>', methods=['GET'])
@api_login_required
def get_robot_detail_json(robot_id):
    robot = db.get_or_404(Robot, robot_id)
    return jsonify({
        'ok': True,
        'robot': _serialize_robot(robot),
    })
