import time
import logging
from threading import Lock

from flask import Blueprint, jsonify, request
from sqlalchemy.exc import SQLAlchemyError

from api.auth_helpers import get_session_user as _get_session_user, is_admin_user as _is_admin_user
from api.geo_utils import reverse_geocode_detail
from api.stats_hotspot_service import build_hotspot_payload
from api.stats_summary_service import build_summary_payload
from database.db import db

stats_bp = Blueprint('stats_bp', __name__)
logger = logging.getLogger(__name__)
SUMMARY_CACHE_TTL_SECONDS = 2.0
_summary_cache = {}
_summary_cache_lock = Lock()
HOTSPOT_CACHE_TTL_SECONDS = 45.0
HOTSPOT_CACHE_MAX_ENTRIES = 64
_hotspot_cache = {}
_hotspot_cache_lock = Lock()

_EMPTY_REGION = {
    'province': '', 'city': '', 'district': '',
    'town': '', 'road': '', 'display_name': '',
}


def _copy_payload_for_cache(payload):
    return dict(payload) if isinstance(payload, dict) else payload


def _require_session_user():
    current_user = _get_session_user()
    if not current_user:
        return None, (jsonify({'ok': False, 'error': '请先登录'}), 401)
    return current_user, None


def _stats_scope_key(current_user):
    if _is_admin_user(current_user):
        return 'admin:all'
    return f'user:{getattr(current_user, "id", "anonymous")}'


def _apply_region_to_hotspot(hotspot, region):
    for field in ('province', 'city', 'district', 'town', 'road', 'display_name'):
        hotspot[field] = region.get(field, '')


def _bounded_int_arg(name, default_value, min_value, max_value):
    raw_value = request.args.get(name, default_value)
    try:
        parsed_value = int(raw_value)
    except (TypeError, ValueError):
        parsed_value = default_value
    return max(min_value, min(max_value, parsed_value))


def _hotspot_cache_key(lookback_days, top_k):
    return f'{int(lookback_days)}:{int(top_k)}'


def _summary_cache_get(cache_key, current_ts):
    with _summary_cache_lock:
        cached = _summary_cache.get(cache_key)
    if not cached:
        return None
    if (current_ts - cached.get('ts', 0.0)) >= SUMMARY_CACHE_TTL_SECONDS:
        with _summary_cache_lock:
            _summary_cache.pop(cache_key, None)
        return None
    return _copy_payload_for_cache(cached.get('data'))


def _summary_cache_set(cache_key, payload, current_ts):
    with _summary_cache_lock:
        _summary_cache[cache_key] = {
            'ts': current_ts,
            'data': _copy_payload_for_cache(payload),
        }


def _prune_cache_by_ts(cache, max_entries):
    overflow = len(cache) - max_entries
    if overflow <= 0:
        return
    oldest_keys = sorted(cache.items(), key=lambda item: item[1].get('ts', 0.0))[:overflow]
    for key, _ in oldest_keys:
        cache.pop(key, None)


def _get_hotspot_cache(cache_key):
    with _hotspot_cache_lock:
        cached = _hotspot_cache.get(cache_key)
    if not cached:
        return None
    if (time.time() - cached.get('ts', 0.0)) > HOTSPOT_CACHE_TTL_SECONDS:
        with _hotspot_cache_lock:
            _hotspot_cache.pop(cache_key, None)
        return None
    return _copy_payload_for_cache(cached.get('payload'))


def _set_hotspot_cache(cache_key, payload):
    with _hotspot_cache_lock:
        _hotspot_cache[cache_key] = {
            'ts': time.time(),
            'payload': _copy_payload_for_cache(payload),
        }
        _prune_cache_by_ts(_hotspot_cache, HOTSPOT_CACHE_MAX_ENTRIES)


def _resolve_hotspot_region(lat, lng):
    return reverse_geocode_detail(lat, lng)


def _attach_hotspot_regions(payload):
    hotspots = payload.get('hotspots') if isinstance(payload, dict) else None
    if not isinstance(hotspots, list):
        return

    valid_targets = []
    for index, hotspot in enumerate(hotspots):
        if not isinstance(hotspot, dict):
            continue
        raw_lat = hotspot.get('center_lat')
        raw_lng = hotspot.get('center_lng')
        if raw_lat is None or raw_lng is None:
            continue
        try:
            lat = float(raw_lat)
            lng = float(raw_lng)
        except (TypeError, ValueError):
            continue
        key = (round(lat, 6), round(lng, 6))
        valid_targets.append((index, key, lat, lng))

    if not valid_targets:
        return

    coord_to_region = {}
    for _, key, lat, lng in valid_targets:
        if key not in coord_to_region:
            coord_to_region[key] = _resolve_hotspot_region(lat, lng)

    for index, key, _, _ in valid_targets:
        hotspot = hotspots[index]
        region = coord_to_region.get(key, _EMPTY_REGION)
        _apply_region_to_hotspot(hotspot, region)


@stats_bp.route('/summary')
def load_summary():
    current_user, auth_error = _require_session_user()
    if auth_error:
        return auth_error

    try:
        current_ts = time.time()
        cache_key = _stats_scope_key(current_user)
        cached_payload = _summary_cache_get(cache_key, current_ts)
        if cached_payload is not None:
            return jsonify(cached_payload)

        payload = build_summary_payload(
            load_task_items_option=None,
            current_user=current_user,
            is_admin_checker=_is_admin_user,
        )
        _summary_cache_set(cache_key, payload, current_ts)
        return jsonify(payload)
    except SQLAlchemyError:
        db.session.rollback()
        logger.exception('统计接口数据库查询失败')
        return jsonify({'ok': False, 'error': '数据库查询失败，请稍后重试'}), 500
    except Exception:
        logger.exception('统计接口异常')
        return jsonify({'ok': False, 'error': '服务器内部错误，请稍后重试'}), 500


@stats_bp.route('/hotspots')
def load_hotspots():
    current_user, auth_error = _require_session_user()
    if auth_error:
        return auth_error

    lookback_days = _bounded_int_arg('lookback_days', 90, 7, 365)
    top_k = _bounded_int_arg('top_k', 6, 3, 10)

    total_start = time.perf_counter()
    cache_scope = _stats_scope_key(current_user)
    cache_key = f'{cache_scope}:{_hotspot_cache_key(lookback_days, top_k)}'
    cached_payload = _get_hotspot_cache(cache_key)
    if cached_payload is not None:
        cached_payload['ok'] = True
        cached_payload['perf'] = {
            'cache_hit': True,
            'query_ms': 0.0,
            'geocode_ms': 0.0,
            'total_ms': round((time.perf_counter() - total_start) * 1000.0, 2),
        }
        return jsonify(cached_payload)

    try:
        payload = build_hotspot_payload(
            lookback_days=lookback_days,
            top_k=top_k,
            current_user=current_user,
            is_admin_checker=_is_admin_user,
            attach_hotspot_regions=_attach_hotspot_regions,
            logger=logger,
        )
        if isinstance(payload.get('perf'), dict):
            payload['perf']['total_ms'] = round((time.perf_counter() - total_start) * 1000.0, 2)
        _set_hotspot_cache(cache_key, payload)
        return jsonify(payload)
    except SQLAlchemyError:
        db.session.rollback()
        logger.exception('热点预测接口数据库查询失败')
        return jsonify({'ok': False, 'error': '热点预测查询失败，请稍后重试'}), 500
    except Exception:
        logger.exception('热点预测接口异常')
        return jsonify({'ok': False, 'error': '热点预测服务异常，请稍后重试'}), 500