import unittest
from datetime import datetime, timedelta

from flask import Flask

from api.robot_api import robot_bp
from database.db import db
from database.models import Robot, User


class RobotApiTestCase(unittest.TestCase):
    def setUp(self):
        self.app = Flask(__name__)
        self.app.config.update({
            'TESTING': True,
            'SECRET_KEY': 'test-secret',
            'SQLALCHEMY_DATABASE_URI': 'sqlite:///:memory:',
            'SQLALCHEMY_TRACK_MODIFICATIONS': False,
        })
        db.init_app(self.app)
        self.app.register_blueprint(robot_bp, url_prefix='/api/robot')

        with self.app.app_context():
            db.create_all()

            owner = User(username='owner_user')
            owner.set_password('password123')
            owner.set_security_code('1111')
            other_user = User(username='other_user')
            other_user.set_password('password123')
            other_user.set_security_code('2222')
            admin_user = User(username='admin_user', role='admin')
            admin_user.set_password('password123')
            admin_user.set_security_code('3333')
            db.session.add_all([owner, other_user, admin_user])
            db.session.flush()

            robot = Robot(
                device_id='R001',
                name='Robot-1',
                status='ONLINE',
                owner_user_id=owner.id,
                last_heartbeat=datetime.now(),
                next_command='STOP',
            )
            db.session.add(robot)
            db.session.commit()
            self.robot_id = robot.id
            self.owner_user_id = owner.id
            self.other_user_id = other_user.id
            self.admin_user_id = admin_user.id

        self.client = self.app.test_client()

    def _login_as(self, user_id):
        with self.client.session_transaction() as session_ctx:
            session_ctx['user_id'] = user_id

    def tearDown(self):
        with self.app.app_context():
            db.session.remove()
            db.drop_all()
            db.engine.dispose()

    def test_register_and_duplicate(self):
        self._login_as(self.owner_user_id)
        resp = self.client.post('/api/robot/register', json={'device_id': 'R002', 'name': 'Robot-2'})
        self.assertEqual(resp.status_code, 200)
        self.assertTrue(resp.get_json().get('ok'))

        with self.app.app_context():
            created = Robot.query.filter_by(device_id='R002').first()
            self.assertIsNotNone(created)
            assert created is not None
            self.assertEqual(created.owner_user_id, self.owner_user_id)

        dup = self.client.post('/api/robot/register', json={'device_id': 'R002', 'name': 'Robot-2'})
        self.assertEqual(dup.status_code, 409)
        self.assertFalse(dup.get_json().get('ok'))

    def test_heartbeat_returns_next_command_and_sets_ip(self):
        resp = self.client.post(
            '/api/robot/heartbeat',
            json={'device_id': 'R001', 'battery': 88},
            headers={'X-Forwarded-For': '10.1.1.5, 192.168.1.10'}
        )
        self.assertEqual(resp.status_code, 200)
        data = resp.get_json()
        self.assertTrue(data.get('ok'))
        self.assertEqual(data.get('command'), 'STOP')

        with self.app.app_context():
            robot = db.session.get(Robot, self.robot_id)
            assert robot is not None
            self.assertEqual(robot.next_command, None)
            self.assertEqual(robot.ip_address, '10.1.1.5')
            self.assertEqual(robot.battery, 88)

    def test_control_alias_command(self):
        self._login_as(self.owner_user_id)
        resp = self.client.post('/api/robot/control', json={'id': self.robot_id, 'command': 'backward'})
        self.assertEqual(resp.status_code, 200)
        data = resp.get_json()
        self.assertEqual(data.get('command'), 'BACK')

        with self.app.app_context():
            robot = db.session.get(Robot, self.robot_id)
            assert robot is not None
            self.assertEqual(robot.next_command, 'BACK')

    def test_navigate_invalid_coordinate(self):
        self._login_as(self.owner_user_id)
        resp = self.client.post('/api/robot/navigate', json={'id': self.robot_id, 'lat': 99.0, 'lng': 120.0})
        self.assertEqual(resp.status_code, 400)
        self.assertFalse(resp.get_json().get('ok'))

    def test_list_marks_robot_offline_when_timeout(self):
        self._login_as(self.owner_user_id)
        with self.app.app_context():
            robot = db.session.get(Robot, self.robot_id)
            assert robot is not None
            robot.last_heartbeat = datetime.now() - timedelta(seconds=100)
            robot.status = 'ONLINE'
            db.session.commit()

        resp = self.client.get('/api/robot/list')
        self.assertEqual(resp.status_code, 200)
        payload = resp.get_json()
        self.assertTrue(payload.get('ok'))
        robots = payload.get('robots') or []
        self.assertEqual(robots[0].get('status'), 'OFFLINE')

    def test_non_owner_cannot_control_robot(self):
        self._login_as(self.other_user_id)
        resp = self.client.post('/api/robot/control', json={'id': self.robot_id, 'command': 'STOP'})
        self.assertEqual(resp.status_code, 403)

    def test_non_owner_cannot_list_owner_robots(self):
        self._login_as(self.other_user_id)
        resp = self.client.get('/api/robot/list')
        self.assertEqual(resp.status_code, 200)
        robots = resp.get_json().get('robots') or []
        self.assertEqual(len(robots), 0)

    def test_admin_can_list_all_robots(self):
        self._login_as(self.admin_user_id)
        resp = self.client.get('/api/robot/list')
        self.assertEqual(resp.status_code, 200)
        robots = resp.get_json().get('robots') or []
        self.assertEqual(len(robots), 1)


if __name__ == '__main__':
    unittest.main()
