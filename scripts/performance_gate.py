#!/usr/bin/env python3
"""Fail-closed, offline evaluator of three REAL mobile Lighthouse JSON reports.

This program does not navigate Dev, deploy code, or manufacture Lighthouse data.
It only evaluates previously captured reports for the same requested Dev URL.
Exit 0 = budget PASS for those reports; exit 1/2 = FAIL/BLOCKED/invalid input.
"""
import argparse
import json
import statistics
import sys
from pathlib import Path
from urllib.parse import urlsplit, urlunsplit

BUDGETS = {
    "performance_score": (90.0, "min"),
    "lcp_ms": (2500.0, "max"),
    "fcp_ms": (1800.0, "max"),
    "tbt_ms": (200.0, "max"),
    "cls": (0.10, "max"),
}
AUDITS = {
    "lcp_ms": "largest-contentful-paint",
    "fcp_ms": "first-contentful-paint",
    "tbt_ms": "total-blocking-time",
    "cls": "cumulative-layout-shift",
}


def normalized_url(raw):
    parsed = urlsplit(raw)
    if parsed.scheme != "https" or parsed.hostname != "dev.beyourlover.com":
        raise ValueError("Only HTTPS dev.beyourlover.com URLs are eligible for this release gate")
    if parsed.username or parsed.password:
        raise ValueError("Credential-bearing URLs are not accepted")
    return urlunsplit(("https", parsed.netloc.lower(), parsed.path.rstrip("/") or "/", parsed.query, ""))


def numeric(value, label):
    if isinstance(value, bool) or not isinstance(value, (float, int)):
        raise ValueError(f"{label}: missing or non-numeric Lighthouse value")
    number = float(value)
    if number != number or number in (float("inf"), -float("inf")) or number < 0:
        raise ValueError(f"{label}: invalid Lighthouse metric")
    return number


def parse_report(path, expected_url):
    doc = json.loads(Path(path).read_text(encoding="utf-8"))
    if not isinstance(doc, dict):
        raise ValueError(f"{path}: report is not a JSON object")
    if doc.get("runtimeError"):
        raise ValueError(f"{path}: Lighthouse runtimeError; status is BLOCKED")
    if normalized_url(doc.get("finalUrl", "")) != expected_url:
        raise ValueError(f"{path}: finalUrl does not match tested Dev URL")
    if doc.get("configSettings", {}).get("formFactor") != "mobile":
        raise ValueError(f"{path}: report must be mobile formFactor")
    if not doc.get("lighthouseVersion"):
        raise ValueError(f"{path}: Lighthouse version missing")
    perf = numeric(doc["categories"]["performance"]["score"], "performance.score")
    if perf > 1:
        raise ValueError(f"{path}: Lighthouse category score outside 0..1")
    row = {"performance_score": round(100 * perf, 3)}
    for key, audit_key in AUDITS.items():
        row[key] = numeric(doc["audits"][audit_key]["numericValue"], audit_key)
    return row


def evaluate(paths, url):
    if len(paths) != 3 or len(set(map(str, paths))) != 3:
        raise ValueError("Exactly three distinct Lighthouse JSON report paths are mandatory")
    expected = normalized_url(url)
    rows = [parse_report(path, expected) for path in paths]
    medians = {name: statistics.median(row[name] for row in rows) for name in BUDGETS}
    failures = []
    for name, (threshold, direction) in BUDGETS.items():
        actual = medians[name]
        if (direction == "min" and actual < threshold) or (direction == "max" and actual > threshold):
            failures.append(f"{name}: median {actual:g} exceeds budget {direction} {threshold:g}")
    for index, row in enumerate(rows, 1):
        if row["performance_score"] < 85.0:
            failures.append(f"run {index}: individual Performance {row['performance_score']:g} < 85")
    return {"status": "PERF FAIL" if failures else "PERF PASS", "url": expected,
            "runs": rows, "median": medians, "failures": failures}


def main(argv=None):
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--url", required=True, help="Exact publicly visible HTTPS Dev URL")
    parser.add_argument("reports", nargs="+", help="Exactly three JSON files from real Lighthouse runs")
    args = parser.parse_args(argv)
    try:
        result = evaluate(args.reports, args.url)
    except (ValueError, OSError, KeyError, TypeError, json.JSONDecodeError) as exc:
        print(json.dumps({"status": "PERF BLOCKED", "reason": str(exc)}, ensure_ascii=False))
        return 2
    print(json.dumps(result, indent=2, ensure_ascii=False))
    return 0 if result["status"] == "PERF PASS" else 1


if __name__ == "__main__":
    sys.exit(main())
