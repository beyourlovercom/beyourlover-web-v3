# G2 Runtime Gate

## Canonical local runtime

`localnew.beyourlover.com` is the development/UAT hostname. The runtime must be production-derived but fail-closed for outbound side effects.

## Required checks before G2 PASS

1. Runtime is based on a fresh Production-derived safe clone.
2. V3 code comes only from the exact Git SHA in `beyourlovercom/beyourlover-web-v3`.
3. V3 theme and BYL Platform plugin load without PHP fatal errors.
4. `wp byl migrate` dry-run lists `V3_DB_001`; apply succeeds only on localnew.
5. Indexing is blocked.
6. Payment capture, live email and ERP writes are disabled outside explicit UAT.
7. Smoke routes return without 5xx.
8. Git working tree is clean after installation mapping.
9. Exact runtime path, PHP/MySQL versions and local route are recorded.

## CI infrastructure fallback

If GitHub Actions creates a job with zero runner steps/logs, local verification may be recorded as an infrastructure exception, but this never authorizes Production deploy. Hosted CI must be rechecked before later release gates.
