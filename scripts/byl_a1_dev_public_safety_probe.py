#!/usr/bin/env python3
"""Read-only, credential-free BYL Dev public isolation regression.

Fixed origin and endpoints only. Never sends authenticated requests,
payment/order mutations, forms, PII, cookies or request bodies.
Public HTTP behavior is NOT proof of private payment/mail/ERP/WMS safety.
"""
import json
import sys
import urllib.error
import urllib.request

ORIGIN = "https://dev.beyourlover.com"
TIMEOUT = 8
MAX_RESPONSE_BYTES = 4096


class NoRedirects(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, request, fp, code, msg, headers, newurl):
        return None


def read_public(route, method):
    if not isinstance(route, str) or not route.startswith("/") or route.startswith("//"):
        raise ValueError("NON_ALLOWLISTED_ROUTE")
    if method not in ("GET", "HEAD"):
        raise ValueError("UNSAFE_METHOD")
    # Constructed solely from code-defined constant routes (not user input).
    request = urllib.request.Request(
        ORIGIN + route, method=method,
        headers={"Accept": "application/json, text/plain;q=0.8", "User-Agent": "BYL-A1-ReadOnlyProbe/1.0"}
    )
    opener = urllib.request.build_opener(NoRedirects)
    try:
        with opener.open(request, timeout=TIMEOUT) as response:
            body = response.read(MAX_RESPONSE_BYTES) if method == "GET" else b""
            return response.status, dict(response.headers), body
    except urllib.error.HTTPError as err:
        body = err.read(MAX_RESPONSE_BYTES) if method == "GET" else b""
        return err.code, dict(err.headers), body


def _header(headers, name):
    for k, v in headers.items():
        if k.casefold() == name.casefold():
            return str(v).casefold()
    return ""


def _code(body):
    try:
        data = json.loads(body.decode("utf-8"))
        return data.get("code") if isinstance(data, dict) else None
    except (ValueError, UnicodeError):
        return None


# Explicit fixed routes; no arbitrary device command, WP login, API key or mutation.
CASES = (
    ("home_noindex", "/", "HEAD", "NOINDEX"),
    ("robots_disallow", "/robots.txt", "GET", "ROBOTS"),
    ("orders_rest_quarantine", "/wp-json/wc/v3/orders", "GET", "QUARANTINE"),
    ("customers_rest_quarantine", "/wp-json/wc/v3/customers", "GET", "QUARANTINE"),
    ("aftership_rewrite_deny", "/aftership-api/v1/orders", "HEAD", "DENY"),
    ("aftership_query_deny", "/?aftership-api-route=/orders", "HEAD", "DENY"),
    ("store_checkout_v1_deny", "/wp-json/wc/store/v1/checkout", "GET", "CHECKOUT"),
    ("store_checkout_legacy_deny", "/wp-json/wc/store/checkout", "GET", "CHECKOUT"),
    ("public_products_available", "/wp-json/wc/store/v1/products?per_page=1", "HEAD", "PUBLIC"),
)


def verify_case(kind, status, headers, body):
    if kind == "NOINDEX":
        h = _header(headers, "x-robots-tag")
        return status == 200 and all(t in h for t in ("noindex", "nofollow", "noarchive"))
    if kind == "ROBOTS":
        return status == 200 and b"Disallow: /" in body and b"User-agent: *" in body
    if kind == "QUARANTINE":
        return status == 403 and _code(body) == "byl_dev_g5_provider_inbound_quarantine"
    if kind == "CHECKOUT":
        return status == 403 and _code(body) == "byl_dev_sandbox_checkout_closed"
    if kind == "DENY":
        return status == 403  # route denied; not proof of provider-side isolation
    if kind == "PUBLIC":
        return status == 200
    raise ValueError("INVALID_CASE_KIND")


def audit(fetch=read_public):
    results = []
    for name, route, method, kind in CASES:
        try:
            status, headers, body = fetch(route, method)
            passed = verify_case(kind, status, headers, body)
            results.append({"check": name, "external_evidence": "PASS" if passed else "FAIL",
                            "http_status": status if isinstance(status, int) else None})
        except Exception:
            # Redact exception: might include private network/proxy paths.
            results.append({"check": name, "external_evidence": "UNVERIFIED", "http_status": None})
    return {
        "schema": "byl-a1-dev-public-safety-v1",
        "scope": "UNAUTHENTICATED_FIXED_DEV_PUBLIC_ENDPOINTS_ONLY",
        "results": results,
        "public_checks_all_pass": all(r["external_evidence"] == "PASS" for r in results),
        "not_tested": [
            "DEV_MU_PLUGIN_INSTALLED_SHA",
            "GATEWAY_TEST_MODE_AND_NO_LIVE_PAYMENTS",
            "WP_MAIL_AND_EXTERNAL_TRANSPORT",
            "AFTERSHIP_EGRESS_AND_PROVIDER_SIDE_PULLS",
            "ERP_WMS_SHIPOUT_SM_CRM_EGRESS",
            "WEBHOOKS_CRON_ACTION_SCHEDULER",
            "DEV_BACKUP_RESTORE_PROVENANCE",
            "WWW_DEV_FULL_WPCLI_SKU_VARIATIONS_MEDIA_DIFF",
        ],
        "a1_overall": "HOLD",
        "a2_import_authorized": False,
    }


def main():
    report = audit()
    print(json.dumps(report, ensure_ascii=False, sort_keys=True))
    return 0 if report["public_checks_all_pass"] else 2


if __name__ == "__main__":
    sys.exit(main())
