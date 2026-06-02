from datetime import datetime, timedelta
import math

from api.stats_data_helpers import query_hotspot_source_rows
from ml.hotspot_common import (
    aggregate_grid_cells,
    build_empty_hotspot_payload,
    build_history,
    build_hotspot_reason,
    build_hotspot_recommendations,
    build_score_context,
    normalize_risk_score,
)


DEFAULT_GRID_SIZE = 0.01


def _build_empty_patrol_payload(lookback_days):
    payload = build_empty_hotspot_payload(
        grid_size=DEFAULT_GRID_SIZE,
        lookback_days=lookback_days,
        forecast_days=1,
        recommendation_message='当前还没有足够的历史数据来生成巡检意见。',
    )
    payload['opinion'] = {'source': 'database_opinion', 'mode': 'database', 'message': '暂无足够数据'}
    return payload


def _query_rows_with_fallback(lookback_days, now, current_user, is_admin_checker, logger=None):
    cutoff_time = now - timedelta(days=lookback_days)
    hotspot_rows = query_hotspot_source_rows(
        cutoff_time,
        current_user=current_user,
        is_admin_checker=is_admin_checker,
    )

    actual_lookback = lookback_days
    if hotspot_rows:
        return hotspot_rows, actual_lookback

    if logger is not None:
        logger.info('巡检意见：%d 天窗口内无数据，降级使用全量历史数据', lookback_days)

    hotspot_rows = query_hotspot_source_rows(
        cutoff_time=None,
        current_user=current_user,
        is_admin_checker=is_admin_checker,
    )
    if not hotspot_rows:
        return hotspot_rows, actual_lookback

    earliest = min(row.created_at for row in hotspot_rows)
    actual_lookback = max(lookback_days, (now - earliest).days + 1)
    return hotspot_rows, actual_lookback


def _build_recommendations(hotspots):
    return build_hotspot_recommendations(
        hotspots=hotspots,
        empty_message='当前还没有足够的历史数据来生成巡检意见。',
        first_line_template='优先巡检热点 1，风险分 {item[risk_score]}，数据库中近 24 小时约有 {item[predicted_count]} 个目标。',
        second_line_template='热点 1 的主导垃圾类型为 {labels}，建议按对应清运与分类策略准备巡检。',
        third_line_template='前 3 个热点区域合计目标数约为 {total_predicted}，可作为机器人优先巡检路线。',
    )


def _aggregate_grid_cells(records, lookback_days, grid_size, as_of_day):
    return aggregate_grid_cells(
        records=records,
        lookback_days=lookback_days,
        grid_size=grid_size,
        as_of_day=as_of_day,
        include_weighted_centroid=False,
    )


def _score_grid_cell(cell, as_of_day, lookback_days, forecast_days):
    context = build_score_context(cell, as_of_day, lookback_days)
    recent_3_avg = context['recent_3_avg']
    recent_7_avg = context['recent_7_avg']
    overall_avg = context['overall_avg']
    today_count = context['today_count']
    weekday_baseline = context['weekday_baseline']
    active_days = context['active_days']
    recency_factor = context['recency_factor']
    momentum = context['momentum']

    predicted_count = max(
        0.0,
        (
            recent_3_avg * 0.38
            + recent_7_avg * 0.28
            + overall_avg * 0.18
            + weekday_baseline * 0.16
            + max(momentum, -recent_3_avg) * 0.25
            + today_count * 0.12 * recency_factor
        ) * math.sqrt(max(forecast_days, 1))
    )
    raw_score = predicted_count + (today_count * 0.35) + (recent_7_avg * 0.25) + (active_days / max(lookback_days, 1)) + recency_factor

    return {
        'grid_id': cell['grid_id'],
        'center_lat': cell['center_lat'],
        'center_lng': cell['center_lng'],
        'predicted_count': predicted_count,
        'raw_score': raw_score,
        'today_count': today_count,
        'recent_7_avg': recent_7_avg,
        'overall_avg': overall_avg,
        'active_days': active_days,
        'task_count': cell['task_count'],
        'total_detections': cell['total_detections'],
        'dominant_labels': [label for label, _ in cell['label_counter'].most_common(3)],
        'last_seen_at': cell['last_seen_at'],
        'history': build_history(cell['daily_counts'], as_of_day),
    }


def build_patrol_opinion_payload(lookback_days, top_k, current_user, is_admin_checker, logger=None):
    now = datetime.now()
    hotspot_rows, actual_lookback = _query_rows_with_fallback(
        lookback_days=lookback_days,
        now=now,
        current_user=current_user,
        is_admin_checker=is_admin_checker,
        logger=logger,
    )

    if not hotspot_rows:
        return _build_empty_patrol_payload(lookback_days)

    as_of_day = now.date()
    cells = _aggregate_grid_cells(hotspot_rows, actual_lookback, DEFAULT_GRID_SIZE, as_of_day)
    if not cells:
        return _build_empty_patrol_payload(actual_lookback)

    scored_cells = [
        _score_grid_cell(cell=cell, as_of_day=as_of_day, lookback_days=actual_lookback, forecast_days=1)
        for cell in cells.values()
    ]
    scored_cells.sort(key=lambda item: (item['predicted_count'], item['recent_7_avg'], item['total_detections']), reverse=True)

    top_cells = scored_cells[:top_k]
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
            'task_count': item['task_count'],
            'dominant_labels': item['dominant_labels'],
            'last_seen_at': item['last_seen_at'].isoformat() if item['last_seen_at'] else None,
            'history': item['history'],
            'reason': build_hotspot_reason(
                item,
                sustained_prefix='该网格在历史数据库中持续有检测记录，建议优先巡检 ',
                include_confidence=False,
            ),
        })

    return {
        'generated_at': now.isoformat(),
        'grid_size': DEFAULT_GRID_SIZE,
        'lookback_days': actual_lookback,
        'forecast_days': 1,
        'summary': {
            'cells_analyzed': len(cells),
            'tasks_used': sum(cell['task_count'] for cell in cells.values()),
            'detections_used': sum(cell['total_detections'] for cell in cells.values()),
        },
        'chart_data': {
            'labels': [f'热点 {item["rank"]}' for item in hotspots],
            'values': [item['predicted_count'] for item in hotspots],
        },
        'hotspots': hotspots,
        'recommendations': _build_recommendations(hotspots),
        'opinion': {
            'source': 'database_opinion',
            'mode': 'database',
            'message': hotspots[0]['reason'] if hotspots else '暂无足够数据',
        },
    }