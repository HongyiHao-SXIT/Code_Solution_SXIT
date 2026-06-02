import math


NAV_ROUTE_KEYS = (
    'waypoints',
    'points',
    'path',
    'planned_path',
    'targets',
    'route',
)

NAV_RESPONSE_ROUTE_KEYS = NAV_ROUTE_KEYS + (
    'target_list',
    'patrol_path',
    'navigation_points',
)


CONTROL_COMMANDS = {
    'FORWARD', 'BACK', 'LEFT', 'RIGHT', 'STOP',
    'PICK_TRASH', 'RESET', 'PAUSE', 'RESUME',
    'SLOW_FORWARD', 'FAST_FORWARD', 'SPIN_LEFT', 'SPIN_RIGHT',
    'HOLD_POSITION', 'CANCEL_NAVIGATION', 'RETURN_HOME', 'DOCK',
}


COMMON_CMD_ALIASES = {
    'f': 'FORWARD',
    'forward': 'FORWARD',
    'b': 'BACK',
    'back': 'BACK',
    'backward': 'BACK',
    'biede': 'BACK',
    'bide': 'BACK',
    'l': 'LEFT',
    'left': 'LEFT',
    'r': 'RIGHT',
    'right': 'RIGHT',
    's': 'STOP',
    'stop': 'STOP',
    'sf': 'SLOW_FORWARD',
    'slow_forward': 'SLOW_FORWARD',
    'ff': 'FAST_FORWARD',
    'fast_forward': 'FAST_FORWARD',
    'sl': 'SPIN_LEFT',
    'spin_left': 'SPIN_LEFT',
    'sr': 'SPIN_RIGHT',
    'spin_right': 'SPIN_RIGHT',
    'pick': 'PICK_TRASH',
    'pick_trash': 'PICK_TRASH',
    'hold': 'HOLD_POSITION',
    'hold_position': 'HOLD_POSITION',
    'home': 'RETURN_HOME',
    'return_home': 'RETURN_HOME',
    'dock': 'DOCK',
    'pause': 'PAUSE',
    'resume': 'RESUME',
    'reset': 'RESET',
    'cancelnav': 'CANCEL_NAVIGATION',
    'cancel_navigation': 'CANCEL_NAVIGATION',
}


def normalize_server_base(raw, default=''):
    server = (raw or '').strip()
    if not server:
        return default
    if '://' not in server:
        server = f'http://{server}'
    return server.rstrip('/')


def clamp_lat(value):
    return max(-90.0, min(90.0, float(value)))


def clamp_lng(value):
    return max(-180.0, min(180.0, float(value)))


def heading_to_delta(heading_deg, step):
    rad = math.radians(float(heading_deg))
    step_value = float(step)
    dlat = math.cos(rad) * step_value
    dlng = math.sin(rad) * step_value
    return dlat, dlng


def normalize_command(raw, aliases=None):
    cleaned = (raw or '').strip()
    if not cleaned:
        return ''
    alias_table = aliases or COMMON_CMD_ALIASES
    return alias_table.get(cleaned.lower(), cleaned.upper())


def resolve_control_command(raw, aliases=None, commands=None):
    command = normalize_command(raw, aliases=aliases)
    if not command:
        return None
    command_set = commands or CONTROL_COMMANDS
    return command if command in command_set else None


def normalize_nav_point(raw_point):
    if isinstance(raw_point, dict):
        lat_raw = raw_point.get('lat')
        lng_raw = raw_point.get('lng')
    elif isinstance(raw_point, (list, tuple)) and len(raw_point) >= 2:
        lat_raw = raw_point[0]
        lng_raw = raw_point[1]
    else:
        return None

    if lat_raw is None or lng_raw is None:
        return None

    try:
        lat = float(lat_raw)
        lng = float(lng_raw)
    except (TypeError, ValueError):
        return None

    if not math.isfinite(lat) or not math.isfinite(lng):
        return None
    if not (-90.0 <= lat <= 90.0 and -180.0 <= lng <= 180.0):
        return None
    return (lat, lng)


def append_nav_points(raw_points, output):
    if raw_points is None:
        return

    point = normalize_nav_point(raw_points)
    if point is not None:
        output.append(point)
        return

    if isinstance(raw_points, (list, tuple)):
        for item in raw_points:
            nested_point = normalize_nav_point(item)
            if nested_point is not None:
                output.append(nested_point)
                continue
            if isinstance(item, dict):
                for key in NAV_ROUTE_KEYS:
                    append_nav_points(item.get(key), output)
        return

    if isinstance(raw_points, dict):
        for key in NAV_ROUTE_KEYS:
            append_nav_points(raw_points.get(key), output)


def extract_nav_route(target, response_body):
    route_points = []

    if isinstance(target, dict):
        for key in NAV_ROUTE_KEYS:
            append_nav_points(target.get(key), route_points)
    elif isinstance(target, (list, tuple)):
        append_nav_points(target, route_points)

    if isinstance(response_body, dict):
        for key in NAV_RESPONSE_ROUTE_KEYS:
            append_nav_points(response_body.get(key), route_points)

    normalized = []
    for point in route_points:
        if not normalized:
            normalized.append(point)
            continue
        prev = normalized[-1]
        if math.hypot(point[0] - prev[0], point[1] - prev[1]) > 1e-12:
            normalized.append(point)

    return normalized


def same_nav_point(p1, p2, tolerance=1e-9):
    return math.hypot(p1[0] - p2[0], p1[1] - p2[1]) <= tolerance


def find_route_point_index(route, point, tolerance):
    for index, candidate in enumerate(route):
        if same_nav_point(candidate, point, tolerance=tolerance):
            return index
    return -1