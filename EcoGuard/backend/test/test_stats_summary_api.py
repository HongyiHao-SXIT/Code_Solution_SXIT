import unittest
from datetime import datetime, timedelta

from flask import Flask

import api.stats_api as stats_api
from api.stats_api import stats_bp
from database.db import db
from database.models import DetectItem, DetectTask, Robot


class StatsSummaryApiTestCase(unittest.TestCase):
    def setUp(self):
        self.app = Flask(__name__)
        self.app.config.update({
            'TESTING': True,
            'SQLALCHEMY_DATABASE_URI': 'sqlite:///:memory:',
            'SQLALCHEMY_TRACK_MODIFICATIONS': False,
        })
        db.init_app(self.app)
        self.app.register_blueprint(stats_bp, url_prefix='/api/stats')

        with self.app.app_context():
            db.create_all()
            now = datetime.now()

            task_1 = DetectTask(
                source_type='image',
                source_path='a.jpg',
                result_path='ra.jpg',
                status='DONE',
                latitude=30.1,
                longitude=110.1,
                created_at=now - timedelta(days=1),
            )
            task_2 = DetectTask(
                source_type='image',
                source_path='b.jpg',
                result_path='rb.jpg',
                status='DONE',
                latitude=31.2,
                longitude=111.2,
                created_at=now,
            )
            db.session.add_all([task_1, task_2])
            db.session.flush()

            db.session.add_all([
                DetectItem(task_id=task_1.id, label='Plastic', confidence=0.9, x1=1, y1=1, x2=2, y2=2, area=1),
                DetectItem(task_id=task_2.id, label='Metal', confidence=0.8, x1=1, y1=1, x2=2, y2=2, area=1),
            ])

            robot = Robot(device_id='RB_SUM', name='RB_SUM', status='ONLINE', last_heartbeat=now)
            db.session.add(robot)
            db.session.commit()

        self.client = self.app.test_client()
        stats_api._summary_cache['ts'] = 0.0
        stats_api._summary_cache['data'] = None

    def tearDown(self):
        with self.app.app_context():
            db.session.remove()
            db.drop_all()
            db.engine.dispose()
        stats_api._summary_cache['ts'] = 0.0
        stats_api._summary_cache['data'] = None

    def test_summary_returns_expected_sections(self):
        resp = self.client.get('/api/stats/summary')
        self.assertEqual(resp.status_code, 200)
        payload = resp.get_json()

        self.assertTrue(payload.get('ok'))
        self.assertIn('locations', payload)
        self.assertIn('pie_data', payload)
        self.assertIn('line_data', payload)
        self.assertIn('robot_list', payload)
        self.assertGreaterEqual(len(payload.get('locations') or []), 2)
        self.assertGreaterEqual(len(payload.get('pie_data') or []), 1)

    def test_summary_cache_hit(self):
        first = self.client.get('/api/stats/summary')
        self.assertEqual(first.status_code, 200)

        second = self.client.get('/api/stats/summary')
        self.assertEqual(second.status_code, 200)

        self.assertTrue(stats_api._summary_cache.get('data') is not None)


if __name__ == '__main__':
    unittest.main()
