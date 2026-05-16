from flask import Blueprint, jsonify, session

from api.robot_helpers import (
    COMMAND_ALIASES,
    CONTROL_COMMANDS,
    HEARTBEAT_TIMEOUT,
    _build_robot_item,
    _commit_or_error,
    _find_robot_by_device_or_error,
    _find_robot_by_id_or_error,
    _get_robot_by_device_id,
    _get_robot_or_none,
    _json_error,
    _json_from_request,
    _json_ok,
    _normalize_command,
    _parse_navigation_payload,
    apply_robot_fields,
    resolve_robot_status,
    take_next_command,
)
from api.robot_route_services import (
    apply_navigation_target,
    apply_robot_update_payload,
    build_robot_list_snapshot,
    validate_register_fields,
)
from database.db import db
from database.models import Robot, User


robot_bp = Blueprint('robot_bp', __name__)


def _get_session_user():
    user_id = session.get('user_id')
    if not user_id:
        return None
    return db.session.get(User, user_id)


def _is_admin_user(user):
    return bool(user and getattr(user, 'role', '') == 'admin')


def _require_ui_user():
    user = _get_session_user()
    if not user:
        return None, _json_error('请先登录', 401)
    return user, None


def _can_access_robot(user, robot):
    if _is_admin_user(user):
        return True
    return getattr(robot, 'owner_user_id', None) == getattr(user, 'id', None)


@robot_bp.route('/heartbeat', methods=['POST'])
def sync_heartbeat():
    payload, payload_error = _json_from_request()
    if payload_error is not None:
        return payload_error
    assert payload is not None

    robot, error_body, error_code = _find_robot_by_device_or_error(payload)
    if not robot:
        if error_body is not None and error_code is not None:
            return error_body, error_code
        return _json_error('设备未注册', 403)

    apply_robot_fields(robot, payload, default_status='ONLINE')
    command = take_next_command(robot)
    commit_error = _commit_or_error('同步心跳')
    if commit_error:
        return commit_error

    return _json_ok({
        'command': command,
        'target': {
            'lat': robot.target_lat,
            'lng': robot.target_lng,
        },
    })


@robot_bp.route('/status_update', methods=['POST'])
def sync_status():
    payload, payload_error = _json_from_request()
    if payload_error is not None:
        return payload_error
    assert payload is not None

    robot, error_body, error_code = _find_robot_by_device_or_error(payload)
    if not robot:
        if error_body is not None and error_code is not None:
            return error_body, error_code
        return _json_error('设备未注册', 403)

    apply_robot_fields(robot, payload)
    command = take_next_command(robot)
    commit_error = _commit_or_error('同步状态')
    if commit_error:
        return commit_error

    return _json_ok({
        'command': command,
        'target': {'lat': robot.target_lat, 'lng': robot.target_lng},
    })


@robot_bp.route('/register', methods=['POST'])
def create_robot():
    current_user, auth_error = _require_ui_user()
    if auth_error:
        return auth_error
    assert current_user is not None

    payload, payload_error = _json_from_request()
    if payload_error is not None:
        return payload_error
    assert payload is not None

    try:
        device_id, name = validate_register_fields(payload)
    except ValueError as error:
        return _json_error(str(error), 400)

    if _get_robot_by_device_id(device_id):
        return jsonify({'ok': False, 'msg': '该设备 ID 已存在'}), 409

    robot = Robot(device_id=device_id, name=name, status='OFFLINE', owner_user_id=current_user.id)
    db.session.add(robot)
    commit_error = _commit_or_error('创建设备')
    if commit_error:
        return commit_error
    return jsonify({'ok': True})


@robot_bp.route('/delete/<int:robot_id>', methods=['POST'])
def remove_robot(robot_id):
    current_user, auth_error = _require_ui_user()
    if auth_error:
        return auth_error

    robot = _get_robot_or_none(robot_id)
    if not robot:
        return _json_error('未找到设备', 404)
    if not _can_access_robot(current_user, robot):
        return _json_error('无权限访问该机器人', 403)

    db.session.delete(robot)
    commit_error = _commit_or_error('删除设备')
    if commit_error:
        return commit_error
    return _json_ok()


@robot_bp.route('/navigate', methods=['POST'])
def send_navigation():
    current_user, auth_error = _require_ui_user()
    if auth_error:
        return auth_error

    payload, payload_error = _json_from_request()
    if payload_error is not None:
        return payload_error
    assert payload is not None

    robot_id, latitude, longitude, parse_error = _parse_navigation_payload(payload)
    if parse_error:
        return parse_error

    robot = _get_robot_or_none(robot_id)
    if not robot:
        return _json_error('机器人不存在', 404)
    if not _can_access_robot(current_user, robot):
        return _json_error('无权限访问该机器人', 403)

    apply_navigation_target(robot, latitude, longitude)
    commit_error = _commit_or_error('下发导航')
    if commit_error:
        return commit_error
    return _json_ok({'msg': '目标已锁定'})


@robot_bp.route('/commands', methods=['GET'])
def list_control_commands():
    commands = sorted(CONTROL_COMMANDS)
    return _json_ok({'commands': commands})


@robot_bp.route('/control', methods=['POST'])
def send_control():
    current_user, auth_error = _require_ui_user()
    if auth_error:
        return auth_error

    payload, payload_error = _json_from_request()
    if payload_error is not None:
        return payload_error
    assert payload is not None

    robot, _, _ = _find_robot_by_id_or_error(payload)
    if not robot:
        return _json_error('机器人不存在', 404)
    if not _can_access_robot(current_user, robot):
        return _json_error('无权限访问该机器人', 403)

    command = _normalize_command(payload.get('command'))
    if not command:
        return _json_error('控制指令不能为空', 400)
    if command not in CONTROL_COMMANDS:
        return _json_error('不支持的控制指令', 400)

    robot.next_command = command
    commit_error = _commit_or_error('下发控制')
    if commit_error:
        return commit_error
    return _json_ok({'command': command})


@robot_bp.route('/list', methods=['GET'])
def fetch_robots():
    current_user, auth_error = _require_ui_user()
    if auth_error:
        return auth_error
    assert current_user is not None

    if _is_admin_user(current_user):
        robots = Robot.query.all()
    else:
        robots = Robot.query.filter_by(owner_user_id=current_user.id).all()

    robot_items, pending_updates = build_robot_list_snapshot(
        robots=robots,
        resolve_status=resolve_robot_status,
        timeout=HEARTBEAT_TIMEOUT,
        build_item=_build_robot_item,
    )

    if pending_updates:
        commit_error = _commit_or_error('更新离线状态')
        if commit_error:
            return commit_error

    return jsonify({'ok': True, 'robots': robot_items})


@robot_bp.route('/update', methods=['POST'])
def save_robot_changes():
    current_user, auth_error = _require_ui_user()
    if auth_error:
        return auth_error

    payload, payload_error = _json_from_request()
    if payload_error is not None:
        return payload_error
    assert payload is not None

    robot_id = payload.get('id')
    robot = _get_robot_or_none(robot_id)
    if not robot:
        return jsonify({'ok': False, 'msg': '机器人不存在'}), 404
    if not _can_access_robot(current_user, robot):
        return _json_error('无权限访问该机器人', 403)

    try:
        apply_robot_update_payload(
            robot=robot,
            payload=payload,
            normalize_command=_normalize_command,
            control_commands=CONTROL_COMMANDS,
        )
    except ValueError as error:
        return _json_error(str(error), 400)

    commit_error = _commit_or_error('更新设备')
    if commit_error:
        return commit_error
    return jsonify({'ok': True})
