from datetime import datetime, timedelta
import time

from api.stats_data_helpers import query_hotspot_source_rows
from ml.algorithm import build_hotspot_forecast


def build_hotspot_payload(lookback_days, top_k, attach_hotspot_regions, logger=None):
    total_start = time.perf_counter()

    query_start = time.perf_counter()
    cutoff_time = datetime.now() - timedelta(days=lookback_days)
    hotspot_rows = query_hotspot_source_rows(cutoff_time)

    # If window has no data, degrade to all historical rows.
    actual_lookback = lookback_days
    if not hotspot_rows:
        if logger is not None:
            logger.info('热点预测：%d 天窗口内无数据，降级使用全量历史数据', lookback_days)
        hotspot_rows = query_hotspot_source_rows(cutoff_time=None)
        if hotspot_rows:
            earliest = min(row.created_at for row in hotspot_rows)
            actual_lookback = max(lookback_days, (datetime.now() - earliest).days + 1)

    query_ms = (time.perf_counter() - query_start) * 1000.0

    payload = build_hotspot_forecast(records=hotspot_rows, lookback_days=actual_lookback, top_k=top_k)
    geocode_start = time.perf_counter()
    attach_hotspot_regions(payload)
    geocode_ms = (time.perf_counter() - geocode_start) * 1000.0

    payload['perf'] = {
        'cache_hit': False,
        'query_ms': round(query_ms, 2),
        'geocode_ms': round(geocode_ms, 2),
        'total_ms': round((time.perf_counter() - total_start) * 1000.0, 2),
    }
    payload['ok'] = True
    return payload
