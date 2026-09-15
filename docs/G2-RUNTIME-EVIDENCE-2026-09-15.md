# G2 Runtime Evidence — 2026-09-15

Status: **LOCAL RUNTIME GATE PASS**. This evidence does not authorize Production deploy or data writes.

## Canonical code

- Repository: `beyourlovercom/beyourlover-web-v3`
- Exact code SHA: `fe82fc420023830c0f63d5ffe1b5d80eed79bb7a`
- Commit: `Merge PR #1 — G2 runtime foundation hardening`
- Canonical checkout: `/Users/a/AI/007/beyourlover-web-v3`
- `HEAD == origin/main` at runtime verification; working tree was clean before this evidence branch.
- Local V3 theme and BYL Platform normalized tree hashes matched the canonical SHA exactly after removal of uncommitted runtime-only preview drift.

## Local runtime

- Local site: `localnewBYL`
- Local site ID: `eKyWS9TTP`
- Host: `https://localnew.beyourlover.com`
- Web root: `D:\ai\localnewbyl\app\public`
- PHP: `8.2.29`
- MySQL: `8.4.0`
- Local app runtime: `10.1.2+7004`
- Database restored from the validated native production-derived dump; native import exited `0`.
- Restored database: `266` base tables; key WordPress/Woo tables were readable after restore.

## Isolation controls

- `WP_ENVIRONMENT_TYPE=local`
- `DISABLE_WP_CRON=true`
- outbound external HTTP blocked by local safety policy
- real Woo payment gateways disabled
- Woo webhook delivery disabled
- outbound WordPress mail short-circuited
- `blog_public=0`
- response header: `X-Robots-Tag: noindex, nofollow, noarchive, nosnippet`

## V3 activation and migration

- Theme active: `beyourlover-v3`
- Plugin active: `byl-platform`
- Legacy `yby-core` / Andy Core was deactivated on localnew after it attempted an unversioned schema mutation during bootstrap.
- `wp byl migrate` dry-run listed only `V3_DB_001`.
- `--apply` succeeded and verification passed.
- Post-verify reported `No pending BYL V3 migrations.`
- Ledger contains `V3_DB_001` with apply timestamp `2026-09-15T01:50:32+00:00`.

## Clean smoke

After deactivating `yby-core` and restoring exact canonical theme code, all tested routes returned HTTP `200`:

- `/`
- `/shop/`
- `/cart/`
- `/my-account/`
- `/wp-json/`
- `/robots.txt`
- `/beyourlover-abe/`

Post-smoke localnew PHP and Nginx error logs were empty. The legacy `localdev.beyourlover.com` regression smoke also returned HTTP `200` for `/`, `/shop/`, `/cart/`, `/my-account/`, `/wp-json/`, and `/robots.txt`.

## Known carry-forward

Local WP-CLI emits a non-fatal startup warning because the Local PHP runtime references `php_imagick.dll` that is not present. It did not create web-runtime errors in the clean smoke and does not block G2 foundation, but image-processing validation must resolve or explicitly accept it before media-heavy release gates.

## Decision

The local Runtime Gate requirements in `G2-RUNTIME-GATE.md` are satisfied. Production remains untouched. G2 may proceed to repository policy closure and Owner UAT; later gates must still resolve the 47 media exceptions and all G1 carry-forward controls.
