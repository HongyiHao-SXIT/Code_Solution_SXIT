import os
from typing import Any, Dict

try:
    import yaml
except ImportError:
    yaml = None


class Config:
    SECRET_KEY = os.getenv('SECRET_KEY', 'change-this-to-a-random-secret')

    BASE_DIR = os.path.dirname(os.path.abspath(__file__))

    DATABASE_URL = os.getenv('DATABASE_URL')

    MYSQL_HOST = os.getenv('MYSQL_HOST')
    MYSQL_PORT = int(os.getenv('MYSQL_PORT', '3306'))
    MYSQL_USER = os.getenv('MYSQL_USER', 'root')
    MYSQL_PASSWORD = os.getenv('MYSQL_PASSWORD', '123456')
    MYSQL_DB = os.getenv('MYSQL_DB')

    if DATABASE_URL:
        SQLALCHEMY_DATABASE_URI = DATABASE_URL
    elif MYSQL_HOST and MYSQL_DB:
        SQLALCHEMY_DATABASE_URI = (
            f'mysql+pymysql://{MYSQL_USER}:{MYSQL_PASSWORD}@{MYSQL_HOST}:{MYSQL_PORT}/{MYSQL_DB}'
            '?charset=utf8mb4'
        )
    else:
        SQLALCHEMY_DATABASE_URI = f'sqlite:///{os.path.join(BASE_DIR, "trashdet.db")}'

    SQLALCHEMY_TRACK_MODIFICATIONS = False
    SQLALCHEMY_ENGINE_OPTIONS = {
        'pool_pre_ping': True,
        'pool_recycle': 280,
    }

    STATIC_DIR = os.path.join(BASE_DIR, 'static')
    UPLOAD_DIR = os.path.join(STATIC_DIR, 'uploads')
    RESULT_DIR = os.path.join(STATIC_DIR, 'results')
    LOG_DIR = os.path.join(BASE_DIR, 'logs')

    YOLO_MODEL_PATH = os.getenv(
        'YOLO_MODEL_PATH',
        os.path.join(
            BASE_DIR, 'model', 'runs', 'train',
            'train-200epoch-v11n.pt-bs16', 'weights', 'best.pt',
        ),
    )
    YOLO_CONF_THRESHOLD = float(os.getenv('YOLO_CONF_THRESHOLD', '0.25'))
    DETECT_LEFT_HALF_ONLY = os.getenv('DETECT_LEFT_HALF_ONLY', 'false').lower() in ('1', 'true', 'yes')
    CAMERA_TIMEOUT_MS = int(os.getenv('CAMERA_TIMEOUT_MS', '3000'))
    SEED = int(os.getenv('SEED', '42'))

    CAPTCHA_ENABLED = os.getenv('CAPTCHA_ENABLED', 'true').lower() in ('1', 'true', 'yes')
    CAPTCHA_LENGTH = int(os.getenv('CAPTCHA_LENGTH', '4'))
    CAPTCHA_EXPIRE_SECONDS = int(os.getenv('CAPTCHA_EXPIRE_SECONDS', '180'))
    CAPTCHA_MAX_FAILURES = int(os.getenv('CAPTCHA_MAX_FAILURES', '3'))
    CAPTCHA_COOLDOWN_SECONDS = int(os.getenv('CAPTCHA_COOLDOWN_SECONDS', '5'))

    # 图片上传限制 10 MB；视频限制 200 MB
    IMAGE_MAX_CONTENT_LENGTH = 10 * 1024 * 1024
    VIDEO_MAX_CONTENT_LENGTH = 200 * 1024 * 1024
    TRAIN_MAX_CONTENT_LENGTH = int(os.getenv('TRAIN_MAX_CONTENT_LENGTH', str(1024 * 1024 * 1024)))
    MAX_CONTENT_LENGTH = max(VIDEO_MAX_CONTENT_LENGTH, TRAIN_MAX_CONTENT_LENGTH)

    ALLOWED_EXTENSIONS = {'png', 'jpg', 'jpeg', 'gif', 'bmp', 'webp'}


BASE_DIR = Config.BASE_DIR


def _parse_yaml_value(raw: str):
    """将 YAML 标量转为 Python 原生类型。

    数字解析策略：先检查小数点或科学计数法标记，优先 int 后 fallback 到 float，
    避免 '1e5' 被 int() 抛出异常后返回原始字符串。
    """
    text = raw.strip()
    if not text:
        return ''

    if len(text) >= 2 and text[0] == text[-1] and text[0] in ('"', "'"):
        return text[1:-1]

    lowered = text.lower()
    if lowered in ('true', 'yes', 'on'):
        return True
    if lowered in ('false', 'no', 'off'):
        return False
    if lowered in ('null', 'none', '~'):
        return None

    try:
        if '.' in text or 'e' in lowered:
            return float(text)
        return int(text)
    except (TypeError, ValueError):
        return text


def _parse_simple_yaml(config_path: str) -> Dict[str, Any]:
    """PyYAML 不可用时的降级解析器。"""
    data: Dict[str, Any] = {}
    current_section: str | None = None

    with open(config_path, 'r', encoding='utf-8') as fh:
        for line in fh:
            stripped = line.split('#', 1)[0].rstrip()
            if not stripped:
                continue

            indent = len(line) - len(line.lstrip())
            if indent == 0 and stripped.endswith(':'):
                current_section = stripped[:-1].strip()
                if current_section:
                    data[current_section] = {}
            elif indent > 0 and current_section and ':' in stripped:
                key, _, val = stripped.partition(':')
                key = key.strip()
                if key:
                    data[current_section][key] = _parse_yaml_value(val)

    return data


def load_yaml_runtime_overrides(base_dir: str) -> Dict[str, Any]:
    config_path = os.getenv(
        'BUSINESS_CONFIG_PATH',
        os.path.join(base_dir, 'runtime_config.yaml'),
    )
    if not os.path.exists(config_path):
        return {}

    try:
        if yaml is not None:
            with open(config_path, 'r', encoding='utf-8') as fh:
                data = yaml.safe_load(fh)
        else:
            data = _parse_simple_yaml(config_path)
    except (OSError, ValueError):
        return {}

    if not isinstance(data, dict):
        return {}

    app_sec = data.get('app')
    detect_sec = data.get('detect')
    database_sec = data.get('database')

    app_sec = app_sec if isinstance(app_sec, dict) else {}
    detect_sec = detect_sec if isinstance(detect_sec, dict) else {}
    database_sec = database_sec if isinstance(database_sec, dict) else {}

    overrides: Dict[str, Any] = {}

    # --- app section ---
    for key in ('SECRET_KEY',):
        if key in app_sec:
            overrides[key] = app_sec[key]

    for key in ('MAX_CONTENT_LENGTH', 'TRAIN_MAX_CONTENT_LENGTH'):
        if key in app_sec:
            try:
                overrides[key] = int(app_sec[key])
            except (TypeError, ValueError):
                pass

    # --- detect section ---
    for key in ('YOLO_CONF_THRESHOLD',):
        if key in detect_sec:
            overrides[key] = detect_sec[key]

    if 'LEFT_HALF_ONLY' in detect_sec:
        val = detect_sec['LEFT_HALF_ONLY']
        overrides['DETECT_LEFT_HALF_ONLY'] = (
            val if isinstance(val, bool)
            else str(val).strip().lower() in ('1', 'true', 'yes', 'on')
        )

    if 'CAMERA_TIMEOUT_MS' in detect_sec:
        try:
            overrides['CAMERA_TIMEOUT_MS'] = int(detect_sec['CAMERA_TIMEOUT_MS'])
        except (TypeError, ValueError):
            pass

    # --- database section ---
    db_url = database_sec.get('DATABASE_URL') or database_sec.get('database_url')
    if db_url:
        overrides['SQLALCHEMY_DATABASE_URI'] = str(db_url).strip()
        return overrides

    host = database_sec.get('MYSQL_HOST') or database_sec.get('host')
    name = database_sec.get('MYSQL_DB') or database_sec.get('name') or database_sec.get('database')
    if host and name:
        port = int(database_sec.get('MYSQL_PORT') or database_sec.get('port') or 3306)
        user = str(database_sec.get('MYSQL_USER') or database_sec.get('user') or 'root')
        password = str(database_sec.get('MYSQL_PASSWORD') or database_sec.get('password') or '123456')
        overrides['SQLALCHEMY_DATABASE_URI'] = (
            f'mysql+pymysql://{user}:{password}@{host}:{port}/{name}?charset=utf8mb4'
        )

    return overrides