from flask import g

from .blueprint import web_bp
from .helpers import _get_current_user


@web_bp.before_app_request
def load_logged_in_user():
    g.auth_user = _get_current_user()


@web_bp.app_context_processor
def inject_auth_user():
    return {'auth_user': getattr(g, 'auth_user', None)}
