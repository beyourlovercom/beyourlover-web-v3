"""Synthetic-only A1 fixed Dev public safety gate regression tests."""
import json
import unittest
from byl_a1_dev_public_safety_probe import CASES, ORIGIN, audit, verify_case, read_public


def stub_response(route, method):
    if route == "/":
        return 200, {"X-Robots-Tag": "noindex, nofollow, noarchive"}, b""
    if route == "/robots.txt":
        return 200, {}, b"User-agent: *\nDisallow: /\n"
    if route in ("/wp-json/wc/v3/orders", "/wp-json/wc/v3/customers"):
        return 403, {}, json.dumps({"code": "byl_dev_g5_provider_inbound_quarantine"}).encode()
    if route in ("/wp-json/wc/store/v1/checkout", "/wp-json/wc/store/checkout"):
        return 403, {}, json.dumps({"code": "byl_dev_sandbox_checkout_closed"}).encode()
    if route in ("/aftership-api/v1/orders", "/?aftership-api-route=/orders"):
        return 403, {}, b""
    if route == "/wp-json/wc/store/v1/products?per_page=1":
        return 200, {}, b""
    raise AssertionError("unexpected route")


class DevSafetyExternalProbeTests(unittest.TestCase):
    def test_fixed_origin_and_safe_methods_only(self):
        self.assertEqual(ORIGIN, "https://dev.beyourlover.com")
        self.assertEqual(len(CASES), 9)
        self.assertTrue(all(route.startswith("/") and not route.startswith("//") for _, route, _, _ in CASES))
        self.assertTrue(all(method in ("GET", "HEAD") for _, _, method, _ in CASES))

    def test_all_public_checks_pass_still_cannot_authorize_import(self):
        report = audit(stub_response)
        self.assertTrue(report["public_checks_all_pass"])
        self.assertEqual(report["a1_overall"], "HOLD")
        self.assertIs(report["a2_import_authorized"], False)
        self.assertEqual(len(report["results"]), 9)
        self.assertGreaterEqual(len(report["not_tested"]), 7)

    def test_403_without_expected_provider_quarantine_code_is_not_pass(self):
        def bad_fetch(route, method):
            status, headers, body = stub_response(route, method)
            if route == "/wp-json/wc/v3/orders":
                return 403, headers, b'{"code":"rest_forbidden"}'
            return status, headers, body
        report = audit(bad_fetch)
        self.assertFalse(report["public_checks_all_pass"])
        self.assertEqual(report["results"][2]["external_evidence"], "FAIL")

    def test_checkout_200_is_fail_even_if_no_real_order_created(self):
        def bad_fetch(route, method):
            if route == "/wp-json/wc/store/v1/checkout":
                return 200, {}, b'{}'
            return stub_response(route, method)
        report = audit(bad_fetch)
        self.assertFalse(report["public_checks_all_pass"])
        self.assertEqual(report["results"][6]["external_evidence"], "FAIL")

    def test_missing_robots_or_noindex_fails_closed(self):
        self.assertFalse(verify_case("ROBOTS", 200, {}, b"User-agent: *\nAllow: /"))
        self.assertFalse(verify_case("NOINDEX", 200, {"X-Robots-Tag": "nofollow"}, b""))
        self.assertFalse(verify_case("NOINDEX", 301, {"X-Robots-Tag": "noindex,nofollow,noarchive"}, b""))

    def test_request_errors_are_redacted_and_never_pass(self):
        def unavailable(route, method):
            raise RuntimeError("sensitive-internal-error path=/protected/config")
        report = audit(unavailable)
        self.assertFalse(report["public_checks_all_pass"])
        self.assertTrue(all(x["external_evidence"] == "UNVERIFIED" for x in report["results"]))
        self.assertNotIn("protected", json.dumps(report))
        self.assertFalse(report["a2_import_authorized"])

    def test_external_403_is_not_claimed_as_full_integration_block(self):
        report = audit(stub_response)
        self.assertTrue(report["public_checks_all_pass"])
        self.assertIn("AFTERSHIP_EGRESS_AND_PROVIDER_SIDE_PULLS", report["not_tested"])
        self.assertIn("ERP_WMS_SHIPOUT_SM_CRM_EGRESS", report["not_tested"])

    def test_arbitrary_route_and_methods_refused_by_transport(self):
        for route in ["https://www.beyourlover.com/", "//www.example.com", ""]:
            with self.assertRaises(ValueError):
                read_public(route, "GET")
        with self.assertRaises(ValueError):
            read_public("/", "POST")


if __name__ == "__main__":
    unittest.main()
