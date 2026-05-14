from datetime import datetime


def validate_register_fields(payload):
    device_id = (payload.get('device_id') or '').strip()
    name = (payload.get('name') or '').strip()

    if not device_id or not name:
        raise ValueError('信息不完整')

    if len(device_id) > 50 or len(name) > 100:
        raise ValueError('设备 ID 或名称过长')

    return device_id, name


def apply_navigation_target(robot, latitude, longitude):
    robot.target_lat = latitude
    robot.target_lng = longitude
    robot.next_command = 'NAVIGATE'


def build_robot_list_snapshot(robots, resolve_status, timeout, build_item):
    robot_items = []
    pending_updates = []
    now = datetime.now()

    for robot in robots:
        status, needs_update = resolve_status(robot, now=now, timeout=timeout)
        if needs_update:
            robot.status = 'OFFLINE'
            pending_updates.append(robot)
        robot_items.append(build_item(robot, status))

    return robot_items, pending_updates


def apply_robot_update_payload(robot, payload, normalize_command, control_commands):
    if 'name' in payload:
        new_name = (payload.get('name') or '').strip()
        if len(new_name) > 100:
            raise ValueError('名称过长')
        robot.name = new_name

    if 'status' in payload:
        robot.status = payload.get('status')

    if 'target_lat' in payload and 'target_lng' in payload:
        robot.target_lat = payload.get('target_lat')
        robot.target_lng = payload.get('target_lng')

    if 'next_command' in payload:
        cmd = normalize_command(payload.get('next_command'))
        if cmd and cmd not in control_commands:
            raise ValueError('不支持的控制指令')
        robot.next_command = cmd

    if 'config' in payload:
        robot.config = payload.get('config')
