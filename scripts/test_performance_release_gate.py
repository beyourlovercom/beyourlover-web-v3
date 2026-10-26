#!/usr/bin/env python3
"""Offline regression for performance budget evaluator and governance wiring.

Fixtures are deliberately synthetic UNIT TESTS, never real Dev PageSpeed evidence.
"""
import copy
import json
import tempfile
import unittest
from pathlib import Path

from performance_gate import evaluate, normalized_url, parse_report

ROOT = Path(__file__).resolve().parents[1]
URL = "https://dev.beyourlover.com/"


def fixture(score=0.95, lcp=1800, fcp=1000, tbt=100, cls=0.01):
    return {
        "lighthouseVersion": "12.0.0",
        "finalUrl": URL,
        "configSettings": {"formFactor": "mobile"},
        "categories": {"performance": {"score": score}},
        "audits": {
            "largest-contentful-paint": {"numericValue": lcp},
            "first-contentful-paint": {"numericValue": fcp},
            "total-blocking-time": {"numericValue": tbt},
            "cumulative-layout-shift": {"numericValue": cls},
        },
    }


class PerformanceGateContract(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory()
        self.addCleanup(self.temp.cleanup)
        self.paths = [Path(self.temp.name) / f"run-{i}.json" for i in range(3)]

    def write(self, reports):
        for path, report in zip(self.paths, reports):
            path.write_text(json.dumps(report), encoding="utf-8")
        return self.paths

    def test_passing_mobile_budget(self):
        result = evaluate(self.write([fixture(), fixture(score=0.92), fixture(score=0.96)]), URL)
        self.assertEqual(result["status"], "PERF PASS")
        self.assertEqual(result["median"]["performance_score"], 95)

    def test_lcp_failure_even_when_performance_is_high(self):
        result = evaluate(self.write([fixture(lcp=3200)] * 3), URL)
        self.assertEqual(result["status"], "PERF FAIL")
        self.assertIn("lcp_ms", str(result["failures"]))

    def test_tbt_failure_even_when_performance_is_high(self):
        result = evaluate(self.write([fixture(tbt=350)] * 3), URL)
        self.assertEqual(result["status"], "PERF FAIL")

    def test_individual_run_floor(self):
        result = evaluate(self.write([fixture(score=0.80), fixture(score=0.99), fixture(score=0.99)]), URL)
        self.assertEqual(result["status"], "PERF FAIL")
        self.assertIn("run 1", str(result["failures"]))

    def test_reject_missing_run(self):
        with self.assertRaisesRegex(ValueError, "Exactly three"):
            evaluate(self.write([fixture()] * 3)[:2], URL)

    def test_reject_duplicate_path(self):
        self.write([fixture()] * 3)
        with self.assertRaisesRegex(ValueError, "distinct"):
            evaluate([self.paths[0]] * 3, URL)

    def test_reject_desktop_result(self):
        doc = fixture()
        doc["configSettings"]["formFactor"] = "desktop"
        self.write([doc] * 3)
        with self.assertRaisesRegex(ValueError, "mobile"):
            evaluate(self.paths, URL)

    def test_reject_wrong_url_or_host(self):
        with self.assertRaisesRegex(ValueError, "Only HTTPS"):
            normalized_url("https://www.beyourlover.com/")
        doc = fixture()
        doc["finalUrl"] = "https://dev.beyourlover.com/checkout/"
        self.write([doc] * 3)
        with self.assertRaisesRegex(ValueError, "does not match"):
            evaluate(self.paths, URL)

    def test_reject_lighthouse_runtime_error(self):
        doc = fixture()
        doc["runtimeError"] = {"code": "PAGE_HUNG", "message": "test fixture"}
        self.write([doc] * 3)
        with self.assertRaisesRegex(ValueError, "runtimeError"):
            evaluate(self.paths, URL)

    def test_reject_absent_metric(self):
        doc = fixture()
        del doc["audits"]["largest-contentful-paint"]
        self.write([doc] * 3)
        with self.assertRaises(KeyError):
            evaluate(self.paths, URL)


class GovernanceReferences(unittest.TestCase):
    def test_release_gate_linked_across_authorities(self):
        standard = (ROOT / "docs/agent/PERFORMANCE-RELEASE-GATE-V1.md").read_text(encoding="utf-8")
        for path in ("AGENTS.md", "docs/agent/RELEASE.md", "docs/agent/TESTING.md"):
            self.assertIn("PERFORMANCE-RELEASE-GATE-V1.md", (ROOT / path).read_text(encoding="utf-8"), path)
        self.assertIn("Performance >= 90/100", standard)
        self.assertIn("LCP <= 2,500 ms", standard)
        self.assertIn("Owner exception", standard)
        self.assertIn("Desktop-only", standard)
        self.assertIn("PERF BLOCKED", standard)
        self.assertIn("does **not** run Lighthouse", standard)


if __name__ == "__main__":
    unittest.main()
