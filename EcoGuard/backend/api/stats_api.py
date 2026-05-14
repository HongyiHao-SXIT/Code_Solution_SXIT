import time
import logging
from datetime import datetime, timedelta
from concurrent.futures import ThreadPoolExecutor, as_completed
from threading import Lock
import requests

from flask import Blueprint, jsonify, request
from sqlalchemy.exc import SQLAlchemyError

from api.stats_data_helpers import query_hotspot_source_rows
from api.stats_summary_service import build_summary_payload
from database.db import db
from services.search_engine import HybridSearchEngine

stats_bp = Blueprint('stats_bp', __name__)
logger = logging.getLogger(__name__)
SUMMARY_CACHE_TTL_SECONDS = 2.0
_summary_cache = {
    'ts': 0.0,
    'data': None,
}
_summary_cache_lock = Lock()
HOTSPOT_CACHE_TTL_SECONDS = 45.0
HOTSPOT_CACHE_MAX_ENTRIES = 64
_hotspot_cache = {}
_hotspot_cache_lock = Lock()
HOTSPOT_GEO_CONNECT_TIMEOUT_SECONDS = 2
HOTSPOT_GEO_READ_TIMEOUT_SECONDS = 6
HOTSPOT_GEO_CACHE_TTL_SECONDS = 1800
HOTSPOT_GEO_FAILURE_CACHE_TTL_SECONDS = 120
HOTSPOT_GEO_CACHE_MAX_ENTRIES = 2048
# Nominatim 服务条款要求最多 1 请求/秒，使用单个 worker 确保串行发送
HOTSPOT_GEO_MAX_WORKERS = 1
HOTSPOT_GEO_RETRY_COUNT = 2
HOTSPOT_GEO_RETRY_BACKOFF_SECONDS = 1.25
_hotspot_geo_cache = {}
_hotspot_geo_cache_lock = Lock()
_nominatim_lock = Lock()
_nominatim_last_call_ts = 0.0
_NOMINATIM_MIN_INTERVAL = 1.1


def _build_hotspot_payload_from_local_engine(lookback_days, top_k):
    query_start = time.perf_counter()
    cutoff_time = datetime.now() - timedelta(days=int(lookback_days))
    rows = query_hotspot_source_rows(cutoff_time=cutoff_time)
    query_ms = (time.perf_counter() - query_start) * 1000.0

    events = []
    for index, row in enumerate(rows):
        created_at = row.created_at
        if not created_at:
            continue

        detection_count = getattr(row, 'detection_count', 0) or 0
        task_count = getattr(row, 'task_count', 0) or 0
        volume = float(detection_count) + float(task_count) * 0.5

        events.append(
            {
                'id': index + 1,
                'longitude': row.longitude,
                'latitude': row.latitude,
                'timestamp': created_at.isoformat(),
                'waste_type': row.label or 'unknown',
                'volume': max(0.1, volume),
            }
        )

    engine = HybridSearchEngine()
    engine.fit(events)
    hotspots = engine.build_hotspots(top_k=top_k)

    payload = {
        'ok': True,
        'summary': {
            'cells_analyzed': len(hotspots),
            'tasks_used': len(rows),
            'detections_used': len(events),
        },
        'hotspots': hotspots,
        'recommendations': [
            '已切换为本地混合索引引擎（KDTree + PCA）计算热点。',
            '建议优先派发巡检到 TOP 风险网格并结合实时回传动态调整。',
        ],
        'chart_data': {
            'labels': [f"TOP {item.get('rank', idx + 1)}" for idx, item in enumerate(hotspots)],
            'values': [item.get('predicted_count', 0) for item in hotspots],
        },
        'perf': {
            'cache_hit': False,
            'query_ms': round(query_ms, 2),
            'geocode_ms': 0.0,
            'total_ms': round(query_ms, 2),
            'source': 'local_hybrid_search_engine',
        },
    }

    geocode_start = time.perf_counter()
    _attach_hotspot_regions(payload)
    payload['perf']['geocode_ms'] = round((time.perf_counter() - geocode_start) * 1000.0, 2)
    return payload


def _bounded_int_arg(name, default_value, min_value, max_value):
    raw_value = request.args.get(name, default_value)
    try:
        parsed_value = int(raw_value)
    except (TypeError, ValueError):
        parsed_value = default_value
    return max(min_value, min(max_value, parsed_value))


def _hotspot_cache_key(lookback_days, top_k):
    return f'{int(lookback_days)}:{int(top_k)}'


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
    payload = cached.get('payload')
    return dict(payload) if isinstance(payload, dict) else payload


def _set_hotspot_cache(cache_key, payload):
    with _hotspot_cache_lock:
        _hotspot_cache[cache_key] = {
            'ts': time.time(),
            'payload': dict(payload) if isinstance(payload, dict) else payload,
        }
        _prune_cache_by_ts(_hotspot_cache, HOTSPOT_CACHE_MAX_ENTRIES)


def _pick_address_value(address, keys):
    for key in keys:
        value = address.get(key)
        if value:
            return str(value).strip()
    return ''


def _nominatim_rate_limit():
    """确保 Nominatim 调用间隔不小于 _NOMINATIM_MIN_INTERVAL 秒，遵守使用条款。"""
    global _nominatim_last_call_ts
    with _nominatim_lock:
        now = time.time()
        wait_time = _NOMINATIM_MIN_INTERVAL - (now - _nominatim_last_call_ts)
        if wait_time > 0:
            time.sleep(wait_time)
        _nominatim_last_call_ts = time.time()


def _resolve_hotspot_region(lat, lng):
    if lat is None or lng is None:
        return {
            'province': '',
            'city': '',
            'district': '',
            'town': '',
            'road': '',
            'display_name': '',
        }

    cache_key = (round(float(lat), 4), round(float(lng), 4))
    now_ts = time.time()
    with _hotspot_geo_cache_lock:
        cached = _hotspot_geo_cache.get(cache_key)
    cached_ttl = cached.get('ttl', HOTSPOT_GEO_CACHE_TTL_SECONDS) if cached else HOTSPOT_GEO_CACHE_TTL_SECONDS
    if cached and (now_ts - cached.get('ts', 0.0)) < cached_ttl:
        return cached.get('value', {})

    try:
        geo_response = None
        retriable_statuses = {429, 500, 502, 503, 504}
        for attempt in range(HOTSPOT_GEO_RETRY_COUNT + 1):
            _nominatim_rate_limit()
            try:
                geo_response = requests.get(
                    'https://nominatim.openstreetmap.org/reverse',
                    params={
                        'format': 'json',
                        'lat': lat,
                        'lon': lng,
                        'zoom': 14,
                        'addressdetails': 1,
                        'accept-language': 'zh-CN',
                    },
                    headers={
                        'User-Agent': 'EcoGuard/2.0 (hotspot-region-resolver)'
                    },
                    timeout=(HOTSPOT_GEO_CONNECT_TIMEOUT_SECONDS, HOTSPOT_GEO_READ_TIMEOUT_SECONDS),
                )
                if geo_response.status_code == 200:
                    break
                if geo_response.status_code not in retriable_statuses:
                    break
            except (requests.Timeout, requests.ConnectionError):
                geo_response = None
            if attempt < HOTSPOT_GEO_RETRY_COUNT:
                time.sleep(HOTSPOT_GEO_RETRY_BACKOFF_SECONDS * (attempt + 1))

        if geo_response is None or geo_response.status_code != 200:
            status_code = geo_response.status_code if geo_response is not None else 'timeout'
            raise RuntimeError(f'nominatim status={status_code}')

        geo_payload = geo_response.json() if geo_response.content else {}
        address = geo_payload.get('address') if isinstance(geo_payload.get('address'), dict) else {}
        region_value = {
            'province': _pick_address_value(address, ['state', 'province', 'region']),
            'city': _pick_address_value(address, ['city', 'town', 'municipality', 'county']),
            'district': _pick_address_value(address, ['county', 'city_district', 'district', 'suburb', 'quarter']),
            'town': _pick_address_value(address, ['town', 'village', 'hamlet', 'suburb', 'neighbourhood']),
            'road': _pick_address_value(address, ['road', 'pedestrian', 'residential', 'path']),
            'display_name': str(geo_payload.get('display_name') or '').strip(),
        }
        cache_ttl = HOTSPOT_GEO_CACHE_TTL_SECONDS
    except Exception as error:
        logger.warning('热点位置解析失败 lat=%s lng=%s error=%s', lat, lng, error)
        region_value = {
            'province': '',
            'city': '',
            'district': '',
            'town': '',
            'road': '',
            'display_name': '',
        }
        cache_ttl = HOTSPOT_GEO_FAILURE_CACHE_TTL_SECONDS

    with _hotspot_geo_cache_lock:
        _hotspot_geo_cache[cache_key] = {
            'ts': time.time(),
            'ttl': cache_ttl,
            'value': region_value,
        }
        _prune_cache_by_ts(_hotspot_geo_cache, HOTSPOT_GEO_CACHE_MAX_ENTRIES)
    return region_value


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
    unique_coords = {}
    for _, key, lat, lng in valid_targets:
        unique_coords[key] = (lat, lng)

    if HOTSPOT_GEO_MAX_WORKERS <= 1 or len(unique_coords) == 1:
        for key, (lat, lng) in unique_coords.items():
            try:
                coord_to_region[key] = _resolve_hotspot_region(lat, lng)
            except Exception:
                coord_to_region[key] = {
                    'province': '',
                    'city': '',
                    'district': '',
                    'town': '',
                    'road': '',
                    'display_name': '',
                }
    else:
        with ThreadPoolExecutor(max_workers=HOTSPOT_GEO_MAX_WORKERS) as executor:
            future_map = {
                executor.submit(_resolve_hotspot_region, lat, lng): key
                for key, (lat, lng) in unique_coords.items()
            }
            for future in as_completed(future_map):
                key = future_map[future]
                try:
                    coord_to_region[key] = future.result()
                except Exception:
                    coord_to_region[key] = {
                        'province': '',
                        'city': '',
                        'district': '',
                        'town': '',
                        'road': '',
                        'display_name': '',
                    }

    for index, key, _, _ in valid_targets:
        hotspot = hotspots[index]
        region = coord_to_region.get(key, {})
        hotspot['province'] = region.get('province', '')
        hotspot['city'] = region.get('city', '')
        hotspot['district'] = region.get('district', '')
        hotspot['town'] = region.get('town', '')
        hotspot['road'] = region.get('road', '')
        hotspot['display_name'] = region.get('display_name', '')


@stats_bp.route('/summary')
def load_summary():
    try:
        current_ts = time.time()
        with _summary_cache_lock:
            cached_payload = _summary_cache.get('data')
            cache_ts = _summary_cache.get('ts', 0.0)
        if cached_payload is not None and (current_ts - cache_ts) < SUMMARY_CACHE_TTL_SECONDS:
            return jsonify(cached_payload)

        payload = build_summary_payload(load_task_items_option=None)
        with _summary_cache_lock:
            _summary_cache['data'] = payload
            _summary_cache['ts'] = current_ts
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
    # 默认回溯 90 天，前端可传参，上限 365 天
    lookback_days = _bounded_int_arg('lookback_days', 90, 7, 365)
    top_k = _bounded_int_arg('top_k', 6, 3, 10)

    total_start = time.perf_counter()
    cache_key = _hotspot_cache_key(lookback_days, top_k)
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
        payload = _build_hotspot_payload_from_local_engine(
            lookback_days=lookback_days,
            top_k=top_k,
        )

        # Keep route-level cache timer to preserve existing endpoint behavior.
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
