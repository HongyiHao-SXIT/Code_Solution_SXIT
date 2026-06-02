from datetime import date, datetime, timedelta
import math
import os

from ml.hotspot_common import (
    aggregate_grid_cells,
    build_empty_hotspot_payload,
    build_history,
    build_hotspot_reason,
    build_hotspot_recommendations,
    build_score_context,
    created_at_to_datetime,
    is_valid_coordinate,
    normalize_risk_score,
    record_value,
    safe_float,
    safe_int,
    to_event_day,
    weighted_moving_average,
    weekday_average,
)

HotspotSearchEngine = None


DEFAULT_FORECAST_DAYS = 1
DEFAULT_GRID_SIZE = 0.001
DEFAULT_LOOKBACK_DAYS = 30
DEFAULT_TOP_K = 6
DEFAULT_MIN_CONFIDENCE = 0.15
DEFAULT_MIN_CONFIDENCE_DETECTIONS = 3


def _load_hotspot_search_engine():
    global HotspotSearchEngine
    if HotspotSearchEngine is not None:
        return HotspotSearchEngine

    enabled = os.getenv('ECOGUARD_ENABLE_HOTSPOT_SERVICE', '0').strip().lower() in {'1', 'true', 'yes', 'on'}
    if not enabled:
        return None

    try:
        from services.hotspot_search_engine import HotspotSearchEngine as engine_cls
    except Exception:
        return None

    HotspotSearchEngine = engine_cls
    return HotspotSearchEngine


# ---------------------------------------------------------------------------
# Utility helpers (must be defined before any callers)
# ---------------------------------------------------------------------------


def _grid_floor(value, grid_size):
    return math.floor(float(value) / grid_size) * grid_size


def _smoothed_horizon_factor(forecast_days):
    horizon = max(1.0, float(forecast_days))
    # 使用对数平滑预测跨度，避免 forecast_days 放大过快。
    return 1.0 + (math.log1p(horizon) * 0.35)


def _build_empty_forecast_payload(grid_size, lookback_days, forecast_days):
    return build_empty_hotspot_payload(
        grid_size=grid_size,
        lookback_days=lookback_days,
        forecast_days=forecast_days,
        recommendation_message='历史垃圾点位不足，暂时无法生成热点预测。',
    )


def _build_recommendations(hotspots):
    return build_hotspot_recommendations(
        hotspots=hotspots,
        empty_message='当前还没有足够的历史数据来生成巡检建议。',
        first_line_template='优先巡检热点 1，风险分 {item[risk_score]}，预测未来 24 小时约有 {item[predicted_count]} 个目标。',
        second_line_template='热点 1 的主导垃圾类型为 {labels}，可针对性准备对应抓取与分类策略。',
        third_line_template='前 3 个热点区域合计预测目标数约为 {total_predicted}，适合作为机器人优先巡检路线。',
    )


def _passes_confidence_filter(item, lookback_days):
    confidence = float(item.get('confidence', 0.0) or 0.0)
    total_detections = int(item.get('total_detections', 0) or 0)
    if confidence >= DEFAULT_MIN_CONFIDENCE:
        return True
    if total_detections >= DEFAULT_MIN_CONFIDENCE_DETECTIONS:
        return True
    # 对于窗口较小的场景，允许略低置信度通过，避免全部被过滤。
    return confidence >= (1.0 / float(max(lookback_days, 1)))


# ---------------------------------------------------------------------------
# Aggregation & scoring (callers of the helpers above)
# ---------------------------------------------------------------------------


def _build_service_events(records, lookback_days, as_of_day):
    cutoff_day = as_of_day - timedelta(days=lookback_days - 1)
    events = []
    tasks_used = 0
    detections_used = 0

    for index, record in enumerate(records):
        latitude = record_value(record, 'latitude')
        longitude = record_value(record, 'longitude')
        created_at_raw = record_value(record, 'created_at')
        created_at = created_at_to_datetime(created_at_raw)
        if created_at is None:
            continue

        event_day = created_at.date()
        if event_day < cutoff_day:
            continue
        if not is_valid_coordinate(latitude, longitude):
            continue

        latitude_value = safe_float(latitude)
        longitude_value = safe_float(longitude)
        if latitude_value is None or longitude_value is None:
            continue

        detection_count = max(safe_int(record_value(record, 'detection_count'), 0), 0)
        task_count = max(safe_int(record_value(record, 'task_count'), 1), 0)
        if detection_count <= 0:
            continue

        label = str(record_value(record, 'label') or 'unknown').strip() or 'unknown'
        volume = float(detection_count) + float(task_count) * 0.5
        events.append({
            'id': index + 1,
            'longitude': longitude_value,
            'latitude': latitude_value,
            'timestamp': created_at.isoformat(),
            'waste_type': label,
            'volume': max(0.1, volume),
        })
        tasks_used += task_count
        detections_used += detection_count

    return events, tasks_used, detections_used


def _aggregate_grid_cells(records, lookback_days, grid_size, as_of_day):
    return aggregate_grid_cells(
        records=records,
        lookback_days=lookback_days,
        grid_size=grid_size,
        as_of_day=as_of_day,
        include_weighted_centroid=True,
    )


def _score_grid_cell(cell, as_of_day, lookback_days, forecast_days):
    context = build_score_context(cell, as_of_day, lookback_days)
    counts = context['counts']
    recent_7_avg = context['recent_7_avg']
    overall_avg = context['overall_avg']
    wma_3 = weighted_moving_average(counts, 3)
    wma_7 = weighted_moving_average(counts, 7)
    wma_14 = weighted_moving_average(counts, 14)
    today_count = context['today_count']
    weekday_baseline = context['weekday_baseline']
    active_days = context['active_days']
    confidence = context['confidence']
    recency_factor = context['recency_factor']
    momentum = context['momentum']
    horizon_factor = _smoothed_horizon_factor(forecast_days)

    centroid_weight = float(cell.get('weight_total', 0.0) or 0.0)
    if centroid_weight > 0:
        centroid_lat = round(cell['sum_lat'] / centroid_weight, 6)
        centroid_lng = round(cell['sum_lng'] / centroid_weight, 6)
    elif cell.get('count', 0) > 0:
        centroid_lat = round(cell['sum_lat'] / float(cell['count']), 6)
        centroid_lng = round(cell['sum_lng'] / float(cell['count']), 6)
    else:
        centroid_lat = cell['center_lat']
        centroid_lng = cell['center_lng']

    predicted_count = max(
        0.0,
        (
            wma_3 * 0.48 +
            wma_7 * 0.30 +
            wma_14 * 0.22 +
            max(momentum, -wma_3) * 0.20 +
            weekday_baseline * 0.12 +
            today_count * 0.10 * recency_factor
        ) * horizon_factor * (0.65 + 0.35 * confidence)
    )
    raw_score = (
        predicted_count * (0.70 + 0.30 * confidence)
        + (today_count * 0.25)
        + (recent_7_avg * 0.20)
        + recency_factor
    )

    return {
        'grid_id': cell['grid_id'],
        'center_lat': centroid_lat,
        'center_lng': centroid_lng,
        'predicted_count': predicted_count,
        'raw_score': raw_score,
        'today_count': today_count,
        'recent_7_avg': recent_7_avg,
        'overall_avg': overall_avg,
        'active_days': active_days,
        'confidence': confidence,
        'task_count': cell['task_count'],
        'total_detections': cell['total_detections'],
        'dominant_labels': [label for label, _ in cell['label_counter'].most_common(3)],
        'last_seen_at': cell['last_seen_at'],
        'history': build_history(cell['daily_counts'], as_of_day),
    }


# ---------------------------------------------------------------------------
# Forecast builders
# ---------------------------------------------------------------------------


def _build_hotspot_forecast_via_service(records, lookback_days, top_k, grid_size, forecast_days):
    engine_cls = _load_hotspot_search_engine()
    if engine_cls is None:
        return None

    as_of_day = datetime.now().date()
    events, tasks_used, detections_used = _build_service_events(
        records=records,
        lookback_days=lookback_days,
        as_of_day=as_of_day,
    )

    if not events:
        return _build_empty_forecast_payload(
            grid_size=grid_size,
            lookback_days=lookback_days,
            forecast_days=forecast_days,
        )

    try:
        engine = engine_cls()
        engine.fit(events)
        hotspots = engine.build_hotspots(top_k=top_k)
    except Exception:
        return None

    return {
        'generated_at': datetime.now().isoformat(),
        'grid_size': grid_size,
        'lookback_days': lookback_days,
        'forecast_days': forecast_days,
        'summary': {
            'cells_analyzed': len(hotspots),
            'tasks_used': tasks_used,
            'detections_used': detections_used,
        },
        'chart_data': {
            'labels': [f"热点 {item.get('rank', index + 1)}" for index, item in enumerate(hotspots)],
            'values': [item.get('predicted_count', 0) for item in hotspots],
        },
        'hotspots': hotspots,
        'recommendations': _build_recommendations(hotspots),
    }


def _build_hotspot_forecast_legacy(records, lookback_days, grid_size, top_k, forecast_days):
    as_of_day = datetime.now().date()
    cells = _aggregate_grid_cells(
        records=records,
        lookback_days=lookback_days,
        grid_size=grid_size,
        as_of_day=as_of_day
    )
    if not cells:
        return _build_empty_forecast_payload(
            grid_size=grid_size,
            lookback_days=lookback_days,
            forecast_days=forecast_days,
        )

    scored_cells = [
        _score_grid_cell(cell=cell, as_of_day=as_of_day, lookback_days=lookback_days, forecast_days=forecast_days)
        for cell in cells.values()
    ]
    scored_cells.sort(key=lambda item: (item['predicted_count'], item['recent_7_avg'], item['total_detections']), reverse=True)

    confidence_filtered = [
        item for item in scored_cells
        if _passes_confidence_filter(item, lookback_days)
    ]
    candidate_cells = confidence_filtered if confidence_filtered else scored_cells

    top_cells = candidate_cells[:top_k]
    max_raw_score = max((item['raw_score'] for item in top_cells), default=0.0)
    hotspots = []
    for index, item in enumerate(top_cells, start=1):
        risk_score = normalize_risk_score(item['raw_score'], max_raw_score)
        hotspots.append({
            'rank': index,
            'grid_id': item['grid_id'],
            'center_lat': item['center_lat'],
            'center_lng': item['center_lng'],
            'predicted_count': round(item['predicted_count'], 2),
            'risk_score': risk_score,
            'recent_count': item['today_count'],
            'active_days': item['active_days'],
            'confidence': round(item['confidence'], 3),
            'task_count': item['task_count'],
            'dominant_labels': item['dominant_labels'],
            'last_seen_at': item['last_seen_at'].isoformat() if item['last_seen_at'] else None,
            'history': item['history'],
            'reason': build_hotspot_reason(
                item,
                sustained_prefix='该网格在历史窗口内持续有检测记录，建议优先巡检 ',
                include_confidence=True,
            ),
        })

    return {
        'generated_at': datetime.now().isoformat(),
        'grid_size': grid_size,
        'lookback_days': lookback_days,
        'forecast_days': forecast_days,
        'summary': {
            'cells_analyzed': len(cells),
            'tasks_used': sum(cell['task_count'] for cell in cells.values()),
            'detections_used': sum(cell['total_detections'] for cell in cells.values()),
        },
        'chart_data': {
            'labels': [f"热点 {item['rank']}" for item in hotspots],
            'values': [item['predicted_count'] for item in hotspots],
        },
        'hotspots': hotspots,
        'recommendations': _build_recommendations(hotspots),
    }


def build_hotspot_forecast(records, lookback_days=DEFAULT_LOOKBACK_DAYS, grid_size=DEFAULT_GRID_SIZE,
                           top_k=DEFAULT_TOP_K, forecast_days=DEFAULT_FORECAST_DAYS):
    normalized_lookback = max(7, int(lookback_days or DEFAULT_LOOKBACK_DAYS))
    normalized_top_k = max(1, int(top_k or DEFAULT_TOP_K))
    normalized_horizon = max(1, int(forecast_days or DEFAULT_FORECAST_DAYS))
    normalized_grid_size = max(0.001, float(grid_size or DEFAULT_GRID_SIZE))
    payload = _build_hotspot_forecast_legacy(
        records=records,
        lookback_days=normalized_lookback,
        grid_size=normalized_grid_size,
        top_k=normalized_top_k,
        forecast_days=normalized_horizon,
    )
    if payload.get('hotspots'):
        return payload

    # Keep service path only as a fallback for sparse/edge data.
    service_payload = _build_hotspot_forecast_via_service(
        records=records,
        lookback_days=normalized_lookback,
        top_k=normalized_top_k,
        grid_size=normalized_grid_size,
        forecast_days=normalized_horizon,
    )
    if service_payload is not None:
        return service_payload

    return payload

