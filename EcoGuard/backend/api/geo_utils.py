"""Shared geocoding and coordinate utilities — single source of truth for Nominatim calls."""

import logging
import threading
import time
from typing import Optional

import requests

logger = logging.getLogger(__name__)

# ---------------------------------------------------------------------------
# Nominatim rate limiter (merged from nominatim_helpers.py)
# ---------------------------------------------------------------------------
_nominatim_lock = threading.Lock()
_nominatim_last_call_ts = 0.0
_NOMINATIM_MIN_INTERVAL = 1.1


def _rate_limit_nominatim():
    """Ensure Nominatim calls stay within the public usage limit (≤1 req/s)."""
    global _nominatim_last_call_ts
    with _nominatim_lock:
        now = time.time()
        wait_time = _NOMINATIM_MIN_INTERVAL - (now - _nominatim_last_call_ts)
        if wait_time > 0:
            time.sleep(wait_time)
        _nominatim_last_call_ts = time.time()


# ---------------------------------------------------------------------------
# Lightweight coordinate cache shared across robot + hotspot callers
# ---------------------------------------------------------------------------
_GEO_CACHE: dict[tuple[float, float], str] = {}
_GEO_CACHE_LOCK = threading.Lock()


def _to_float_or_none(value) -> Optional[float]:
    if value is None:
        return None
    try:
        return float(value)
    except (TypeError, ValueError):
        return None


def _to_int_or_none(value) -> Optional[int]:
    if value is None:
        return None
    try:
        return int(value)
    except (TypeError, ValueError):
        return None


def is_valid_lat(lat: Optional[float]) -> bool:
    return lat is not None and -90.0 <= lat <= 90.0


def is_valid_lng(lng: Optional[float]) -> bool:
    return lng is not None and -180.0 <= lng <= 180.0


def is_valid_coordinate(lat: Optional[float], lng: Optional[float]) -> bool:
    return is_valid_lat(lat) and is_valid_lng(lng)


# ---------------------------------------------------------------------------
# Nominatim reverse-geocode (single implementation)
# ---------------------------------------------------------------------------
def reverse_geocode(
    lat: float,
    lng: float,
    zoom: int = 10,
    timeout: int = 5,
    user_agent: str = "EcoGuard/2.0",
) -> str:
    """Return a human-readable address string for (lat, lng).

    Returns ``'未知地点'`` when geocoding fails or coordinates are invalid.
    This is the **only** function in the codebase that calls Nominatim reverse.
    """
    if not is_valid_coordinate(lat, lng):
        return "未知地点"

    # Round to 4 decimals for cache key (≈11 m resolution)
    cache_key = (round(lat, 4), round(lng, 4))
    with _GEO_CACHE_LOCK:
        if cache_key in _GEO_CACHE:
            return _GEO_CACHE[cache_key]

    _rate_limit_nominatim()
    try:
        resp = requests.get(
            "https://nominatim.openstreetmap.org/reverse",
            params={
                "format": "json",
                "lat": lat,
                "lon": lng,
                "zoom": zoom,
                "addressdetails": 1,
                "accept-language": "zh-CN",
            },
            headers={"User-Agent": user_agent},
            timeout=timeout,
        )
        if resp.status_code == 200 and resp.content:
            data = resp.json()
            address = data.get("display_name", "").strip()
            result = address or "未知地点"
        else:
            result = f"坐标: {lat:.5f}, {lng:.5f} (解析失败)"
    except Exception:
        logger.warning("Nominatim reverse geocode failed lat=%s lng=%s", lat, lng)
        result = f"坐标: {lat:.5f}, {lng:.5f} (解析失败)"

    with _GEO_CACHE_LOCK:
        _GEO_CACHE[cache_key] = result

    return result


def reverse_geocode_detail(
    lat: float,
    lng: float,
    timeout: int = 5,
) -> dict[str, str]:
    """Reverse-geocode and return structured address fields.

    Used by the hotspot service to populate display_name, city, district, etc.
    """
    empty = {"province": "", "city": "", "district": "", "town": "", "road": "", "display_name": ""}
    if not is_valid_coordinate(lat, lng):
        return empty

    _rate_limit_nominatim()
    try:
        resp = requests.get(
            "https://nominatim.openstreetmap.org/reverse",
            params={
                "format": "json",
                "lat": lat,
                "lon": lng,
                "zoom": 14,
                "addressdetails": 1,
                "accept-language": "zh-CN",
            },
            headers={"User-Agent": "EcoGuard/2.0 (hotspot-region-resolver)"},
            timeout=timeout,
        )
        if resp.status_code != 200 or not resp.content:
            return empty

        data = resp.json()
        address = data.get("address", {}) if isinstance(data.get("address"), dict) else {}

        def _pick(*keys: str) -> str:
            for k in keys:
                v = address.get(k)
                if v:
                    return str(v).strip()
            return ""

        return {
            "province": _pick("state", "province", "region"),
            "city": _pick("city", "town", "municipality", "county"),
            "district": _pick("county", "city_district", "district", "suburb", "quarter"),
            "town": _pick("town", "village", "hamlet", "suburb", "neighbourhood"),
            "road": _pick("road", "pedestrian", "residential", "path"),
            "display_name": str(data.get("display_name", "")).strip(),
        }
    except Exception:
        logger.warning("Nominatim detail geocode failed lat=%s lng=%s", lat, lng)
        return empty