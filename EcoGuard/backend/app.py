import logging
import os

from flask import Flask

from api.detect_api import detect_bp
from api.detect_helpers import log_detection_dependency_report
from api.robot_api import robot_bp
from api.stats_api import stats_bp
from api.train_api import train_bp
from app_factory_services import (
    configure_root_logging,
    ensure_bootstrap_admin_impl,
    ensure_ownership_schema_impl,
    mask_database_uri,
    register_blueprints,
    register_error_handler,
    register_health_route,
    seed_random_state,
)
from config import BASE_DIR, Config, load_yaml_runtime_overrides
from database.db import db
from database.models import User
from web.pages import web_bp


def create_app():
    app = Flask(__name__)
    app.config.from_object(Config)
    app.config.update(load_yaml_runtime_overrides(app.config.get('BASE_DIR', BASE_DIR)))

    train_max = app.config.get('TRAIN_MAX_CONTENT_LENGTH', 0)
    global_max = app.config.get('MAX_CONTENT_LENGTH', 0)
    if isinstance(train_max, int) and train_max > 0:
        if not isinstance(global_max, int) or global_max < train_max:
            app.config['MAX_CONTENT_LENGTH'] = train_max

    seed_random_state(seed=app.config.get('SEED', 42))

    configure_root_logging(app)
    log_detection_dependency_report(logging.getLogger(__name__))

    secret = app.config.get('SECRET_KEY', '')
    if secret in ('', 'change-this-to-a-random-secret', 'dev-key'):
        logging.warning(
            'SECRET_KEY is insecure. Set the SECRET_KEY env var before deploying.'
        )

    logging.info(
        'Database URI: %s',
        mask_database_uri(app.config.get('SQLALCHEMY_DATABASE_URI', '')),
    )

    db.init_app(app)
    with app.app_context():
        os.makedirs(
            app.config.get('STATIC_DIR', os.path.join(app.root_path, 'static')),
            exist_ok=True,
        )
        os.makedirs(
            app.config.get('UPLOAD_DIR', os.path.join(app.root_path, 'static', 'uploads')),
            exist_ok=True,
        )
        os.makedirs(
            app.config.get('RESULT_DIR', os.path.join(app.root_path, 'static', 'results')),
            exist_ok=True,
        )
        db.create_all()
        ensure_ownership_schema_impl(app=app, db_obj=db, logger=logging.getLogger(__name__))
        ensure_bootstrap_admin_impl(
            app=app,
            db_obj=db,
            user_model=User,
            os_module=os,
            logger=logging.getLogger(__name__),
        )

    register_blueprints(app, [
        (detect_bp, '/api'),
        (web_bp, None),
        (stats_bp, '/api/stats'),
        (robot_bp, '/api/robot'),
        (train_bp, '/api/train'),
    ])
    register_error_handler(app)
    register_health_route(app)

    return app


if __name__ == '__main__':
    application = create_app()
    debug = os.getenv('FLASK_DEBUG', '').lower() in ('true', '1', 't')
    application.run(host='0.0.0.0', port=5000, debug=debug)