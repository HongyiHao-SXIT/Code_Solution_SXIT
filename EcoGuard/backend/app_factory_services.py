import logging
import os
import random
from logging.handlers import RotatingFileHandler

from flask import jsonify, request
from werkzeug.exceptions import HTTPException


def seed_random_state(seed=42, np_module=None, torch_module=None):
    random.seed(seed)
    if np_module is not None:
        np_module.random.seed(seed)
    if torch_module is not None:
        torch_module.manual_seed(seed)
        if torch_module.cuda.is_available():
            torch_module.cuda.manual_seed(seed)
            torch_module.cuda.manual_seed_all(seed)


def ensure_bootstrap_admin_impl(app, db_obj, user_model, os_module=os, logger=None):
    if app.config.get('TESTING'):
        return

    logger_obj = logger or logging.getLogger(__name__)
    try:
        has_user = db_obj.session.query(user_model.id).first() is not None
        if has_user:
            return

        username = os_module.getenv('ECOGUARD_ADMIN_USERNAME', 'admin').strip() or 'admin'
        password = os_module.getenv('ECOGUARD_ADMIN_PASSWORD', 'admin123456').strip() or 'admin123456'
        security_code = os_module.getenv('ECOGUARD_ADMIN_SECURITY_CODE', '0000').strip() or '0000'

        admin_user = user_model(username=username, role='admin')
        admin_user.set_password(password)
        admin_user.set_security_code(security_code)
        db_obj.session.add(admin_user)
        db_obj.session.commit()

        logger_obj.warning(
            'No users detected. Bootstrap admin account created. '
            'Please change credentials immediately. username=%s',
            username,
        )
    except Exception:
        db_obj.session.rollback()
        logger_obj.exception('Failed to create bootstrap admin account.')


def mask_database_uri(database_uri):
    if not database_uri:
        return 'unknown'
    if '://' not in database_uri or '@' not in database_uri:
        return database_uri

    scheme, remainder = database_uri.split('://', 1)
    credentials, suffix = remainder.split('@', 1)
    if ':' not in credentials:
        return f'{scheme}://***@{suffix}'

    username, _ = credentials.split(':', 1)
    return f'{scheme}://{username}:***@{suffix}'


def configure_root_logging(app, rotating_file_handler_cls=RotatingFileHandler):
    logging.basicConfig(level=logging.INFO, format='%(asctime)s %(levelname)s %(name)s: %(message)s')
    os.makedirs(app.config.get('LOG_DIR', 'logs'), exist_ok=True)
    log_path = os.path.join(app.config.get('LOG_DIR', 'logs'), 'service.log')
    root_logger = logging.getLogger()
    has_file_handler = any(
        isinstance(handler, rotating_file_handler_cls) and getattr(handler, 'baseFilename', '') == os.path.abspath(log_path)
        for handler in root_logger.handlers
    )
    if has_file_handler:
        return

    file_handler = rotating_file_handler_cls(
        log_path,
        maxBytes=10 * 1024 * 1024,
        backupCount=3,
        encoding='utf-8'
    )
    file_handler.setFormatter(logging.Formatter('%(asctime)s %(levelname)s %(name)s: %(message)s'))
    root_logger.addHandler(file_handler)


def register_blueprints(app, blueprint_specs):
    for blueprint, url_prefix in blueprint_specs:
        if url_prefix:
            app.register_blueprint(blueprint, url_prefix=url_prefix)
        else:
            app.register_blueprint(blueprint)


def register_error_handler(app):
    @app.errorhandler(Exception)
    def render_error(error):
        if request.path.startswith('/api'):
            if isinstance(error, HTTPException):
                return jsonify({
                    'ok': False,
                    'code': error.code,
                    'name': error.name,
                    'message': error.description,
                }), error.code

            logging.exception('An error occurred during an API request.')
            return jsonify({
                'ok': False,
                'code': 500,
                'name': 'Internal Server Error',
                'message': str(error) if app.debug else 'An unexpected error occurred.'
            }), 500

        if isinstance(error, HTTPException):
            return error

        logging.exception('An error occurred during a web request.')
        return 'Internal Server Error', 500


def register_health_route(app):
    @app.route('/health')
    def show_health():
        return jsonify({'status': 'ok', 'version': '2.0.0'})
