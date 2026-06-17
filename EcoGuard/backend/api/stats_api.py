import time
import logging
from threading import Lock

from flask import Blueprint, jsonify, request
from sqlalchemy.exc import SQLAlchemyError

from api.auth_helpers import get_session_user, is_admin_user
from api.stats_hotspot_service import build_hotspot_payload
from api.stats_summary_service import build_summary_payload
from database.db import db

stats_bp = Blueprint('stats_bp', __name__)
logger = logging.getLogger(__name__)

# 缓存 TTL：摘要 15s（原 2s 高频查询压力过大），热点 45s
SUMMARY_CACHE_TTL_S = 15.0
HOTSPOT_CACHE_TTL_S = 45.0
HOTSPOT_CACHE_MAX_ENTRIES = 64

_summary_cache: dict = {}
_summary_cache_lock = Lock()
_hotspot_cache: dict = {}
_hotspot_cache_lock = Lock()

_EMPTY_REGION = {
    'province': '', 'city': '', 'district': '',
    'town': '', 'road': '', 'display_name': '',
}


# ---------------------------------------------------------------------------
# 缓存辅助
# ---------------------------------------------------------------------------

def _stats_scope_key(current_user):
    if is_admin_user(current_user):
        return 'admin:all'
    return f'user:{getattr(current_user, "id", "anonymous")}'


def _hotspot_cache_key(lookback_days, top_k):
    return f'{int(lookback_days)}:{int(top_k)}'


def _copy_payload(payload):
    return dict(payload) if isinstance(payload, dict) else payload


def _summary_cache_get(cache_key, now):
    with _summary_cache_lock:
        entry = _summary_cache.get(cache_key)
    if not entry:
        return None
    if (now - entry.get('ts', 0.0)) >= SUMMARY_CACHE_TTL_S:
        with _summary_cache_lock:
            _summary_cache.pop(cache_key, None)
        return None
    return _copy_payload(entry.get('data'))


def _summary_cache_set(cache_key, payload, now):
    with _summary_cache_lock:
        _summary_cache[cache_key] = {'ts': now, 'data': _copy_payload(payload)}


def _hotspot_cache_get(cache_key):
    with _hotspot_cache_lock:
        entry = _hotspot_cache.get(cache_key)
    if not entry:
        return None
    if (time.time() - entry.get('ts', 0.0)) > HOTSPOT_CACHE_TTL_S:
        with _hotspot_cache_lock:
            _hotspot_cache.pop(cache_key, None)
        return None
    return _copy_payload(entry.get('payload'))


def _hotspot_cache_set(cache_key, payload):
    with _hotspot_cache_lock:
        _hotspot_cache[cache_key] = {'ts': time.time(), 'payload': _copy_payload(payload)}
        # 淘汰最旧条目
        overflow = len(_hotspot_cache) - HOTSPOT_CACHE_MAX_ENTRIES
        if overflow > 0:
            oldest = sorted(_hotspot_cache.items(), key=lambda kv: kv[1].get('ts', 0.0))[:overflow]
            for key, _ in oldest:
                _hotspot_cache.pop(key, None)


def _attach_hotspot_regions(payload):
    from api.geo_utils import reverse_geocode_detail

    hotspots = payload.get('hotspots') if isinstance(payload, dict) else None
    if not isinstance(hotspots, list):
        return

    valid_targets = []
    for idx, hs in enumerate(hotspots):
        if not isinstance(hs, dict):
            continue
        raw_lat = hs.get('center_lat')
        raw_lng = hs.get('center_lng')
        if raw_lat is None or raw_lng is None:
            continue
        try:
            lat, lng = float(raw_lat), float(raw_lng)
        except (TypeError, ValueError):
            continue
        valid_targets.append((idx, (round(lat, 6), round(lng, 6)), lat, lng))

    if not valid_targets:
        return

    coord_to_region = {}
    for _, key, lat, lng in valid_targets:
        if key not in coord_to_region:
            coord_to_region[key] = reverse_geocode_detail(lat, lng)

    for idx, key, _, _ in valid_targets:
        region = coord_to_region.get(key, _EMPTY_REGION)
        hs = hotspots[idx]
        for field in ('province', 'city', 'district', 'town', 'road', 'display_name'):
            hs[field] = region.get(field, '')


def _bounded_int_arg(name, default, lo, hi):
    try:
        val = int(request.args.get(name, default))
    except (TypeError, ValueError):
        val = default
    return max(lo, min(hi, val))


# ---------------------------------------------------------------------------
# 路由
# ---------------------------------------------------------------------------

@stats_bp.route('/summary')
def load_summary():
    user = get_session_user()
    if not user:
        return jsonify({'ok': False, 'error': '请先登录'}), 401

    try:
        now = time.time()
        cache_key = _stats_scope_key(user)
        cached = _summary_cache_get(cache_key, now)
        if cached is not None:
            return jsonify(cached)

        payload = build_summary_payload(
            load_task_items_option=None,
            current_user=user,
            is_admin_checker=is_admin_user,
        )
        _summary_cache_set(cache_key, payload, now)
        return jsonify(payload)
    except SQLAlchemyError:
        db.session.rollback()
        logger.exception('统计摘要查询失败')
        return jsonify({'ok': False, 'error': '数据库查询失败，请稍后重试'}), 500
    except Exception:
        logger.exception('统计摘要异常')
        return jsonify({'ok': False, 'error': '服务器内部错误，请稍后重试'}), 500


@stats_bp.route('/hotspots')
def load_hotspots():
    user = get_session_user()
    if not user:
        return jsonify({'ok': False, 'error': '请先登录'}), 401

    lookback = _bounded_int_arg('lookback_days', 90, 7, 365)
    top_k = _bounded_int_arg('top_k', 6, 3, 10)

    total_start = time.perf_counter()
    cache_key = f'{_stats_scope_key(user)}:{_hotspot_cache_key(lookback, top_k)}'
    cached = _hotspot_cache_get(cache_key)
    if cached is not None:
        cached['ok'] = True
        cached['perf'] = {
            'cache_hit': True, 'query_ms': 0.0, 'geocode_ms': 0.0,
            'total_ms': round((time.perf_counter() - total_start) * 1000, 2),
        }
        return jsonify(cached)

    try:
        payload = build_hotspot_payload(
            lookback_days=lookback, top_k=top_k,
            current_user=user, is_admin_checker=is_admin_user,
            attach_hotspot_regions=_attach_hotspot_regions,
            logger=logger,
        )
        perf = payload.get('perf')
        if isinstance(perf, dict):
            perf['total_ms'] = round((time.perf_counter() - total_start) * 1000, 2)
        _hotspot_cache_set(cache_key, payload)
        return jsonify(payload)
    except SQLAlchemyError:
        db.session.rollback()
        logger.exception('热点预测查询失败')
        return jsonify({'ok': False, 'error': '热点预测查询失败，请稍后重试'}), 500
    except Exception:
        logger.exception('热点预测异常')
        return jsonify({'ok': False, 'error': '热点预测服务异常，请稍后重试'}), 500