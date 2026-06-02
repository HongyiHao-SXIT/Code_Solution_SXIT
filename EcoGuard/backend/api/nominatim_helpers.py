import threading
import time


_nominatim_lock = threading.Lock()
_nominatim_last_call_ts = 0.0
_NOMINATIM_MIN_INTERVAL = 1.1


def rate_limit_nominatim():
    """Ensure Nominatim calls stay within the public usage limit."""
    global _nominatim_last_call_ts
    with _nominatim_lock:
        now = time.time()
        wait_time = _NOMINATIM_MIN_INTERVAL - (now - _nominatim_last_call_ts)
        if wait_time > 0:
            time.sleep(wait_time)
        _nominatim_last_call_ts = time.time()