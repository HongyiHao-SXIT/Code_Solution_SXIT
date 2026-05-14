import unittest
from datetime import datetime, timedelta

from flask import Flask

from api.robot_api import robot_bp
from database.db import db
from database.models import Robot


class RobotApiTestCase(unittest.TestCase):
    def setUp(self):
        self.app = Flask(__name__)
        self.app.config.update({
            'TESTING': True,
            'SQLALCHEMY_DATABASE_URI': 'sqlite:///:memory:',
            'SQLALCHEMY_TRACK_MODIFICATIONS': False,
        })
        db.init_app(self.app)
        self.app.register_blueprint(robot_bp, url_prefix='/api/robot')

        with self.app.app_context():
            db.create_all()
            robot = Robot(
                device_id='R001',
                name='Robot-1',
                status='ONLINE',
                last_heartbeat=datetime.now(),
                next_command='STOP',
            )
            db.session.add(robot)
            db.session.commit()
            self.robot_id = robot.id

        self.client = self.app.test_client()

    def tearDown(self):
        with self.app.app_context():
            db.session.remove()
            db.drop_all()
            db.engine.dispose()

    def test_register_and_duplicate(self):
        resp = self.client.post('/api/robot/register', json={'device_id': 'R002', 'name': 'Robot-2'})
        self.assertEqual(resp.status_code, 200)
        self.assertTrue(resp.get_json().get('ok'))

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
        resp = self.client.post('/api/robot/control', json={'id': self.robot_id, 'command': 'backward'})
        self.assertEqual(resp.status_code, 200)
        data = resp.get_json()
        self.assertEqual(data.get('command'), 'BACK')

        with self.app.app_context():
            robot = db.session.get(Robot, self.robot_id)
            assert robot is not None
            self.assertEqual(robot.next_command, 'BACK')

    def test_navigate_invalid_coordinate(self):
        resp = self.client.post('/api/robot/navigate', json={'id': self.robot_id, 'lat': 99.0, 'lng': 120.0})
        self.assertEqual(resp.status_code, 400)
        self.assertFalse(resp.get_json().get('ok'))

    def test_list_marks_robot_offline_when_timeout(self):
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


if __name__ == '__main__':
    unittest.main()
