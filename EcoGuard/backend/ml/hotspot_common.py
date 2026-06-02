from collections import Counter, defaultdict
from datetime import date, datetime, timedelta
import math


def record_value(record, key, default=None):
    if isinstance(record, dict):
        return record.get(key, default)
    return getattr(record, key, default)


def to_event_day(value):
    if isinstance(value, datetime):
        return value.date()
    if isinstance(value, date):
        return value
    return None


def safe_int(value, default=0):
    try:
        return int(value)
    except (TypeError, ValueError):
        return default


def safe_float(value, default=None):
    try:
        return float(value)
    except (TypeError, ValueError):
        return default


def grid_floor(value, grid_size):
    return math.floor(float(value) / float(grid_size)) * float(grid_size)


def is_valid_coordinate(latitude, longitude):
    if latitude is None or longitude is None:
        return False
    return -90.0 <= float(latitude) <= 90.0 and -180.0 <= float(longitude) <= 180.0


def created_at_to_datetime(value):
    if isinstance(value, datetime):
        return value
    if isinstance(value, date):
        return datetime.combine(value, datetime.min.time())
    return None


def average(values):
    if not values:
        return 0.0
    return float(sum(values)) / float(len(values))


def weekday_average(daily_counts, as_of_day):
    values = [count for day, count in daily_counts.items() if day.weekday() == as_of_day.weekday()]
    return average(values)


def weighted_moving_average(values, window):
    if not values:
        return 0.0
    size = max(1, min(int(window), len(values)))
    recent = list(values[-size:])
    weights = list(range(1, size + 1))
    weighted_sum = sum(value * weight for value, weight in zip(recent, weights))
    return float(weighted_sum) / float(sum(weights))


def build_history(daily_counts, as_of_day, days=7):
    labels = []
    values = []
    for offset in range(days - 1, -1, -1):
        day = as_of_day - timedelta(days=offset)
        labels.append(day.strftime('%m-%d'))
        values.append(int(daily_counts.get(day, 0)))
    return {
        'labels': labels,
        'values': values,
    }


def normalize_risk_score(raw_score, max_raw_score):
    if raw_score <= 0 or max_raw_score <= 0:
        return 0
    return min(100, max(30, int(round((raw_score / max_raw_score) * 100))))


def build_empty_hotspot_payload(grid_size, lookback_days, forecast_days, recommendation_message):
    return {
        'generated_at': datetime.now().isoformat(),
        'grid_size': grid_size,
        'lookback_days': lookback_days,
        'forecast_days': forecast_days,
        'summary': {
            'cells_analyzed': 0,
            'tasks_used': 0,
            'detections_used': 0,
        },
        'chart_data': {
            'labels': [],
            'values': [],
        },
        'hotspots': [],
        'recommendations': [recommendation_message],
    }


def format_hotspot_labels(item):
    labels = item.get('dominant_labels') or []
    return '、'.join(labels) if labels else '混合垃圾'


def build_hotspot_reason(
    item,
    sustained_prefix,
    include_confidence=False,
    confidence_template='置信度 {confidence}%',
):
    labels = format_hotspot_labels(item)
    confidence_suffix = ''
    if include_confidence:
    confidence_value = round(float(item.get('confidence', 0.0) or 0.0) * 100)
    confidence_suffix = f'（{confidence_template.format(confidence=confidence_value)}）'

    if item['today_count'] > 0:
        return f"近 24 小时仍有 {item['today_count']} 个垃圾目标，{labels}出现更集中{confidence_suffix}。"
    if item['recent_7_avg'] >= item['overall_avg']:
        return f"近 7 天活跃度高于长期均值，{labels}在该区域复现概率较高{confidence_suffix}。"
    return f"{sustained_prefix}{labels}{confidence_suffix}。"


def build_hotspot_recommendations(
    hotspots,
    empty_message,
    first_line_template,
    second_line_template,
    third_line_template,
):
    if not hotspots:
        return [empty_message]

    recommendations = []
    top_hotspot = hotspots[0]
    recommendations.append(first_line_template.format(item=top_hotspot))
    recommendations.append(second_line_template.format(labels=format_hotspot_labels(top_hotspot), item=top_hotspot))
    if len(hotspots) > 1:
        total_predicted = round(sum(item['predicted_count'] for item in hotspots[:3]), 2)
        recommendations.append(third_line_template.format(total_predicted=total_predicted))
    return recommendations


def aggregate_grid_cells(records, lookback_days, grid_size, as_of_day, include_weighted_centroid=False):
    cutoff_day = as_of_day - timedelta(days=lookback_days - 1)
    cells = {}

    for record in records:
        latitude = record_value(record, 'latitude')
        longitude = record_value(record, 'longitude')
        created_at = record_value(record, 'created_at')
        if not is_valid_coordinate(latitude, longitude) or created_at is None:
            continue

        event_day = to_event_day(created_at)
        if event_day is None or event_day < cutoff_day:
            continue

        latitude_value = safe_float(latitude)
        longitude_value = safe_float(longitude)
        if latitude_value is None or longitude_value is None:
            continue

        label = str(record_value(record, 'label') or '').strip()
        detection_count = max(safe_int(record_value(record, 'detection_count'), 0), 0)
        task_count = max(safe_int(record_value(record, 'task_count'), 1), 0)
        if detection_count <= 0:
            continue

        labels = [label] * detection_count if label else []
        grid_lat = grid_floor(latitude_value, grid_size)
        grid_lng = grid_floor(longitude_value, grid_size)
        grid_id = f'{grid_lat:.4f},{grid_lng:.4f}'
        center_lat = round(grid_lat + (float(grid_size) / 2.0), 6)
        center_lng = round(grid_lng + (float(grid_size) / 2.0), 6)
        cell = cells.setdefault(
            grid_id,
            {
                'grid_id': grid_id,
                'center_lat': center_lat,
                'center_lng': center_lng,
                'daily_counts': defaultdict(int),
                'label_counter': Counter(),
                'task_count': 0,
                'total_detections': 0,
                'last_seen_at': None,
            },
        )
        if include_weighted_centroid:
            cell.setdefault('sum_lat', 0.0)
            cell.setdefault('sum_lng', 0.0)
            cell.setdefault('count', 0)
            cell.setdefault('weight_total', 0.0)

        cell['daily_counts'][event_day] += detection_count
        cell['label_counter'].update(labels)
        cell['task_count'] += task_count
        cell['total_detections'] += detection_count

        if include_weighted_centroid:
            weight = max(float(detection_count), 1.0)
            cell['sum_lat'] += latitude_value * weight
            cell['sum_lng'] += longitude_value * weight
            cell['count'] += 1
            cell['weight_total'] += weight

        if cell['last_seen_at'] is None or created_at > cell['last_seen_at']:
            cell['last_seen_at'] = created_at

    return cells


def build_score_context(cell, as_of_day, lookback_days):
    window_days = [as_of_day - timedelta(days=offset) for offset in range(lookback_days - 1, -1, -1)]
    counts = [int(cell['daily_counts'].get(day, 0)) for day in window_days]
    recent_3_avg = average(counts[-3:])
    recent_7_avg = average(counts[-7:])
    overall_avg = average(counts)
    today_count = counts[-1] if counts else 0
    previous_3_avg = average(counts[-6:-3]) if len(counts) >= 6 else 0.0
    weekday_baseline = weekday_average(cell['daily_counts'], as_of_day)
    active_days = sum(1 for value in counts if value > 0)
    recency_days = (as_of_day - cell['last_seen_at'].date()).days if cell['last_seen_at'] else lookback_days
    recency_factor = 1.0 / (1.0 + max(recency_days, 0))
    momentum = recent_3_avg - previous_3_avg
    confidence = float(active_days) / float(max(lookback_days, 1))

    return {
        'counts': counts,
        'recent_3_avg': recent_3_avg,
        'recent_7_avg': recent_7_avg,
        'overall_avg': overall_avg,
        'today_count': today_count,
        'previous_3_avg': previous_3_avg,
        'weekday_baseline': weekday_baseline,
        'active_days': active_days,
        'recency_days': recency_days,
        'recency_factor': recency_factor,
        'momentum': momentum,
        'confidence': confidence,
    }