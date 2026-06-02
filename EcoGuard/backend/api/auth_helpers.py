from flask import session

from database.db import db
from database.models import User


def get_session_user():
    user_id = session.get('user_id')
    if not user_id:
        return None
    return db.session.get(User, user_id)


def is_admin_user(user):
    return bool(user and getattr(user, 'role', '') == 'admin')