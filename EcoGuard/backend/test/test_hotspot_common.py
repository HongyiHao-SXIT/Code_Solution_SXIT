import unittest
from datetime import datetime, timedelta

from ml.hotspot_common import build_score_context


class HotspotCommonTestCase(unittest.TestCase):
    def test_build_score_context_metrics(self):
        as_of_day = datetime(2026, 6, 2).date()
        daily_counts = {
            as_of_day - timedelta(days=6): 1,
            as_of_day - timedelta(days=5): 0,
            as_of_day - timedelta(days=4): 2,
            as_of_day - timedelta(days=3): 0,
            as_of_day - timedelta(days=2): 3,
            as_of_day - timedelta(days=1): 0,
            as_of_day: 4,
        }
        cell = {
            'daily_counts': daily_counts,
            'last_seen_at': datetime(2026, 6, 2, 11, 30, 0),
        }

        context = build_score_context(cell, as_of_day=as_of_day, lookback_days=7)

        self.assertEqual(context['counts'], [1, 0, 2, 0, 3, 0, 4])
        self.assertAlmostEqual(context['recent_3_avg'], (3 + 0 + 4) / 3)
        self.assertAlmostEqual(context['recent_7_avg'], sum(context['counts']) / 7)
        self.assertEqual(context['today_count'], 4)
        self.assertEqual(context['active_days'], 4)
        self.assertAlmostEqual(context['confidence'], 4 / 7)
        self.assertEqual(context['recency_days'], 0)
        self.assertAlmostEqual(context['recency_factor'], 1.0)


if __name__ == '__main__':
    unittest.main()
