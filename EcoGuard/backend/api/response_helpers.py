from flask import jsonify, request


def json_ok(payload=None, status_code=200):
    body = {'ok': True}
    if payload:
        body.update(payload)
    return jsonify(body), status_code


def json_success(payload=None):
    body = {'ok': True, 'status': 'success'}
    if payload:
        body.update(payload)
    return jsonify(body)


def json_error(message, status_code=400, message_key='msg'):
    return jsonify({'ok': False, message_key: message}), status_code


def json_from_request(error_message='请求体必须是 JSON 对象', status_code=400, error_key='msg'):
    payload = request.get_json(silent=True)
    if isinstance(payload, dict):
        return payload, None
    return None, json_error(error_message, status_code, error_key)