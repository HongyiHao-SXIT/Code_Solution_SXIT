def build_pagination_payload(pagination):
    return {
        'page': pagination.page,
        'pages': pagination.pages,
        'total': pagination.total,
        'has_prev': pagination.has_prev,
        'has_next': pagination.has_next,
        'prev_num': pagination.prev_num,
        'next_num': pagination.next_num,
    }


def build_tasks_payload(pagination, can_delete, serialize_task):
    return {
        'ok': True,
        'tasks': [serialize_task(task) for task in pagination.items],
        'pagination': build_pagination_payload(pagination),
        'can_delete': bool(can_delete),
    }


def build_items_payload(pagination, can_delete, serialize_item_row):
    return {
        'ok': True,
        'items': [serialize_item_row(item) for item in pagination.items],
        'pagination': build_pagination_payload(pagination),
        'can_delete': bool(can_delete),
    }


def build_task_detail_payload(task, can_delete, serialize_task, serialize_item):
    return {
        'ok': True,
        'task': serialize_task(task),
        'items': [serialize_item(item) for item in task.items],
        'can_delete': bool(can_delete),
    }


def ensure_admin_user_or_forbidden(current_user, is_admin_user):
    return bool(is_admin_user(current_user))
