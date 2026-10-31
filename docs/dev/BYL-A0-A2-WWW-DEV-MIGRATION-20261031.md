# BYL V3 A0–A2 — WWW → Dev Catalog Baseline (2026-10-31)

## Evidence and boundary
- WWW is production WooCommerce/transactional data authority; Dev is the isolated UAT target; GitHub main is code authority.
- Cart V2 Owner PASS and Checkout V3 merged source do not prove current runtime. Commerce E2E #87 stays HOLD.
- Live A0/A1 two-site DB/runtime evidence is **not verified**: BYL DC device metadata READY but remote operation returned OPERATOR_TOOL_DENIED; secondary DC read-only is quota-blocked.
- This draft supplies only read-only WP-CLI catalog export, offline JSON diff, tests and a migration contract. No import or deployment.

## A0 — read-only inventory and dry-run
- Obtain exact HTTP host/TLS, PHP/WP/Woo/version, environment, theme and MU/plugin runtime hashes; non-sensitive totals of products, variations, categories, attributes, attachments, pages/posts; sanitized shipping/currency rules (never secrets).
- Audit WWW/Dev R2 img.beyourlover.com mapping, media IDs, categories and original SKU/variation identity.
- Through independently authorized secure WP-CLI, run scripts/byl_a0_a2_catalog_export.php as a trusted temporary file outside each webroot, producing one protected JSON snapshot per site. Example: wp eval-file /trusted/path/byl_a0_a2_catalog_export.php --quiet > /protected/report/www-catalog.json. Repeat with Dev.
- On a local machine, compare snapshots with: python3 scripts/byl_a0_a2_catalog_diff.py --www /protected/report/www-catalog.json --dev /protected/report/dev-catalog.json --out /protected/report/diff.json.
- Compare generated timestamps if WWW product edits happen during export. No customer/real-order data, credentials or snapshot files are to be checked into GitHub.
- The result is always HOLD or DRY_RUN_ONLY, never authorization to import. WordPress post IDs / attachment IDs are not portable.

## A1 — fail-closed Dev gates: ALL REQUIRED
- [ ] Live Dev has staging environment, expected home_url HTTPS and access protection.
- [ ] Dev SEO noindex; no Dev URLs in canonical or public sitemap.
- [ ] Running MU/plugin exact hashes and effective gates verified on Dev. Source-only existence is insufficient.
- [ ] Gateways TEST; checkout and order submission denied except timeboxed owner-approved Canary.
- [ ] Woo/background mail blocked; no leftover exception allowing arbitrary real send.
- [ ] AfterShip/Automizely outbound and order/customer inbound blocked. Independently check ERP/WMS/Shipout/SM/CRM, external callbacks and Cron; existing MU gate is not proof of complete provider isolation.
- [ ] No WWW payment/affiliate secrets, subscriber data, customers or live orders copied to Dev.
- [ ] Dev DB/files backup SHA + independently verified restore path, outside webroot.
- [ ] A0 fresh source and destination report, SKU collisions and media exceptions reviewed.

## A2 — proposed incremental batch process, CURRENTLY HOLD
1. Use unique SKU; for SKU-less variation, only unique parent SKU plus attribute values. Quarantine duplicates, missing/ambiguous keys, missing media.
2. Verify allowlist of product/variation/category/media fields, preserving separate Woo IDs. Preserve ERP stock ownership; never blindly copy inventory counts.
3. Review A0 preview; obtain backup and scoped owner approval before creating any Dev batch.
4. Small Canary on Dev → readback and exact-item journal → verified rollback → batch and idempotent rerun.
5. No WWW writes, no real customer/order data, no credentials, no payment/fulfillment notifications.
6. A2 closure only with actual Dev SKU/media discrepancy readback and safe recovery evidence.

## Source test commands
- python3 -m unittest discover -s scripts -p 'test_byl_a0_a2_catalog_diff.py'
- php -l scripts/byl_a0_a2_catalog_export.php

## References
[Notion new Roadmap](https://app.notion.com/p/4095f5efdf94819eb0c4c53ffada5a1c) · [Commerce #87](https://github.com/beyourlovercom/beyourlover-web-v3/issues/87) · [Theme OS #66](https://github.com/beyourlovercom/beyourlover-web-v3/issues/66).

## 2026-10-31 — live public A0/A1 evidence and A2 quarantine (US Mac mini, authorized BYL DC)

**Evidence scope:** unauthenticated, read-only HTTPS against Dev public routes. The
US Mac mini had no privileged WP-CLI, WordPress admin, database or Hostinger
session for this run. WWW public endpoints from the same machine returned
HTTP 445; that result is a network/access restriction **not** evidence of
catalog difference, lost orders or a WWW outage. Do not seek an alternative
route to bypass denied access.

### A0 — Dev public catalog, not full WooCommerce inventory

- `/wp-json/wc/store/v1/products?per_page=100&page=N` returned exactly
  three JSON array pages comprising **235 public product parents**.
- Type counts: **215 simple**, **20 variable**. Those 20 visible parents
  reference **68 variations**; the 68 are references, not independently
  exported Woo variation records or a total DB variation count.
- Public product SKUs: **2 blank**, **0 duplicate non-empty SKU groups**.
  SKU-less Dev simple product IDs: **26737**, **24875** — quarantine from
  automatic SKU matching. Public IDs are Dev-only, not portable to WWW.
- Product records lacking images: **5** (Dev IDs
  **40029, 40028, 40027, 40026, 26737**); this is a human/media mapping
  review list, not proof of a source data loss.
- The 235 parents expose **1,295 image references**, **1,293 unique image
  URLs**, with media host `img.beyourlover.com`. Evenly distributed
  **8 unique image HEAD samples** returned 8/8 HTTP 200. This is **not**
  an exhaustive image existence or R2 mapping audit.
- Other Dev public REST `X-WP-Total` counts: pages **78**, posts **281**,
  WP post categories **11**, public Woo product categories **85**.
  Do not reinterpret these as complete private/draft database counts.

### A1 — externally confirmed controls versus still-open controls

| Control | Evidence | State |
| --- | --- | --- |
| Public noindex | Dev homepage `X-Robots-Tag: noindex, nofollow, noarchive`; `robots.txt` contains `Disallow: /` | EXTERNAL PASS |
| Woo Orders REST | unauthenticated GET `/wp-json/wc/v3/orders` returns `byl_dev_g5_provider_inbound_quarantine`; HEAD 403 | EXTERNAL PASS |
| Woo Customers REST | unauthenticated GET `/wp-json/wc/v3/customers` returns same quarantine code; HEAD 403 | EXTERNAL PASS |
| AfterShip inbound | HEAD `/aftership-api/v1/orders` and `/?aftership-api-route=/orders` 403 | EXTERNAL PASS on sampled routes |
| Woo Store Checkout | GET `/wp-json/wc/store/v1/checkout` and `/wp-json/wc/store/checkout` both return `byl_dev_sandbox_checkout_closed` | EXTERNAL PASS on sampled routes |
| Woo Store product REST | GET + HEAD public products 200, preserves read-only catalog accessibility | EXTERNAL PASS |
| Installed Dev Safety source SHA / full hooks | GitHub source inspected, live installed bytes not attested | NOT VERIFIED |
| Payment TEST and real gateway disable | no authorized admin/runtime readback | NOT VERIFIED |
| WP mail/transactional/marketing outbound | public HTTP cannot establish SMTP deny | NOT VERIFIED |
| AfterShip outbound and ERP/Shipout/SM/CRM external consumers | no provider-side receipt or effective egress config | NOT VERIFIED |
| Cron, background queues, webhooks, affiliate tasks | no authorized runtime/job audit | NOT VERIFIED |
| Dev database/files backup and recoverability | no independent backup receipt/restore evidence | NOT VERIFIED |

**A1 overall: HOLD.** Public REST 403 is strong evidence of an active
quarantine route, **not** proof that all external providers are isolated.

### A2 — concrete Dev-only preflight, zero imports

1. Preserve all existing Dev Woo IDs, local edits, V3 Cart/Checkout/Theme
   assets and options. WWW remains source authority for intended catalog
   updates; Dev is not an authoritative source for product data.
2. Quarantine the two SKU-less Dev simple records by Dev ID above. Require
   manual SKU/identity reconciliation; never auto-match by title or WP ID.
3. Flag five no-image Dev parent records for R2/attachment comparison,
   without automatically deleting or replacing images.
4. Reserve 20 variable parents / 68 referenced variations for **complete
   per-variation attribute/SKU** WP-CLI export before applying updates.
   Public Store API references cannot prove variation identity.
5. Once an **authorized** complete WWW + Dev snapshot exists, run the
   existing `byl_a0_a2_catalog_diff.py` and review duplicate, missing,
   changed, unkeyed, broken-media and mapping sets. The source comparator
   always sets `a2_import_authorized=false`.
6. Require A1 complete PASS, explicit Dev backup/rollback evidence and Owner
   approval of exact SKU allowlist before any Dev write. Then allow a
   small scoped reversible Canary, verify it, and only then authorize batch.
7. **No API keys, real customers/orders, payment secrets or production
   callbacks to Dev. No writes to WWW.**

**Current closure:** A0 PARTIAL, A1 PARTIAL/HOLD, A2 DRY-RUN PLAN ONLY/HOLD.
No claim of WWW–Dev SKU parity, full R2 media parity, staging payment safety
or successful data migration.
