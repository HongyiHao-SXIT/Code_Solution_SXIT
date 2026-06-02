def parse_optional_float(value):
    if value in (None, ''):
        return None
    try:
        return float(value)
    except (TypeError, ValueError):
        return None


def parse_int(value, default_value, minimum=None, maximum=None):
    try:
        parsed_value = int(value)
    except (TypeError, ValueError):
        parsed_value = default_value

    if minimum is not None:
        parsed_value = max(minimum, parsed_value)
    if maximum is not None:
        parsed_value = min(maximum, parsed_value)
    return parsed_value


def parse_bool(value, default=False, default_value=None):
    if default_value is not None:
        default = default_value
    if isinstance(value, bool):
        return value
    if value is None:
        return default
    if isinstance(value, str):
        normalized = value.strip().lower()
        if normalized in {'1', 'true', 'yes', 'on'}:
            return True
        if normalized in {'0', 'false', 'no', 'off'}:
            return False
        return default
    if isinstance(value, (int, float)):
        return value != 0
    return default