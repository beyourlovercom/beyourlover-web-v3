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

## 2026-10-31 — A0 public variations readback / A2 deterministic public-only preflight

The authorized BYL DC US Mac mini performed GET (no credentials, no write)
for every one of the 68 variation IDs referenced by the three pages of Dev's
public Woo Store API. **68/68 HTTP 200**; every fetched record identifies the
expected public parent and reports type `variation`. This is NOT a WP-CLI/DB
export of the true WooCommerce variation inventory.

**Critical projection pitfall:** 68/68 fetched variation records report their
parent's `sku` string; the Store API public SKU projection is not authoritative
for a variation's independently configured Woo SKU. **Never** match / upsert
these variants by that projected `sku`. Use the already-constructed trusted
WP-CLI full A0 export for any eventual import decision, matching each verified
native variation identity to its parent+attributes and quarantine rules.

Public parent `variations[].attributes` inspection:
- 68 variation references, across 20 public variable parents.
- **35 references** carry at least one null/empty/wildcard attribute value,
  among parent IDs **17168, 17169, 17170, 17171, 17172, 17185, 17237, 34861**.
  Quarantine all 35 for native variation SKU/attribute readback. A partially
  wildcard attribute is legal in Woo and is NOT by itself a data integrity bug.
- **4 references under Dev parent 17168**, IDs 17173, 17180, 17181, 17182,
  share one entirely empty public attribute combination. They cannot be
  distinguished by public parent+attribute values. This collision requires
  manual/native reconciliation, not automatic import.
- Remaining **33 references** have unique, fully specified *public*
  parent+attribute combinations; label **candidates only**, not verified
  native Woo SKU matches, not an import allowlist.
- Two public simple parent SKU values are empty: Dev parent IDs 24875,
  26737. Five public parent IDs have no image: 26737, 40026, 40027,
  40028, 40029. Earlier media HEAD 8/8 PASS was only a small sample.
- Neither this data nor HTTP 445 from the WWW path enables a real WWW→Dev
  product/media delta estimate.

New **pure offline, public-snapshot-only, fail-closed** tool:
`scripts/byl_a0_a2_public_dev_preflight.py` consumes the three *previously
captured* public Dev Store API JSON parent arrays and the public-only
`public-variations.json` receipt, emits a local JSON report. It has **no
network call, Woo write client, database connection, import mode, or mutable
authorization flag**. It checks parent/variation references, wildcard and
duplicate attribute keys, mismatched/orphaned refs, blank parent SKUs and
missing parent images. `a2_import_authorized=false` and `gate=HOLD` are
unconditional. It reports 68 fetched variations, 35 wildcard references,
33 public candidate combinations, one wildcard collision group with four
variation IDs, and the two blank SKU parents.

Synthetic validation: existing + new `test_byl_a0_a2*.py` tests
**14/14 PASS** on an isolated US Mac mini checkout; actual Dev public-only
snapshot preflight executed and returned `gate=HOLD`.

**Release STOP preserved:** No WWW or Dev data writes, no real transactions,
no private credentials. Real A0 authoritative two-site WP-CLI snapshot, A1
payment/mail/ERP/AfterShip outbound/cron/backup gate and exact Owner-approved
Dev migration scope remain prerequisites for A2 Canary.

## 2026-11-01 — repeatable A1 public read-only probe + authorized Dev-only runtime receipt (source-only)

### Executed read-only public A1 regression

New `scripts/byl_a1_dev_public_safety_probe.py` uses a **fixed**
`https://dev.beyourlover.com` origin and exactly **9 allowlisted GET/HEAD**
routes; never accepts credentials, arbitrary hosts, POST, cookies, checkout
submissions or write actions. Redirects are disallowed and error details
are sanitized. It can be executed on an allowed independent computer to
recheck public safety **without** WordPress administration access.

The real authorized US Mac mini run returned **9/9 public checks PASS**:
Dev homepage noindex, robots disallow, orders/customers REST protected with
exact dedicated quarantine error, two AfterShip inbound routes 403, two
Store Checkout GET endpoints return `byl_dev_sandbox_checkout_closed`,
and Woo public Store product API remains HTTP 200.

**Scope limit:** Public access denial is *not* proof of SMTP, payment
gateway credentials, ERP/Shipout/SM/WMS provider pulls, HTTP egress, active
Cron or private backup/restore. The checker **always** reports
`a1_overall=HOLD`, `a2_import_authorized=false`, and enumerates missing
private/authorized evidence. `public_checks_all_pass=true` is not A1 PASS.

### New source-only authorized Dev WP-CLI inspector

`scripts/byl_a1_dev_runtime_readonly.php` is a scoped **read-only, sanitized
Dev status collector**. It requires trusted existing WordPress WP-CLI and
strictly enforces `https://dev.beyourlover.com` plus `staging`;
running on WWW, a wrong Dev identity, or outside WP-CLI fails closed.
The script reads *only* version and selected status metadata:
- WordPress/PHP/Woo versions, theme stylesheet, number of active plugins,
  and version/active flags for a short allowlist of named integrations;
- the installed Dev Safety MU plugin's SHA-256 **without its path or bytes**
  and whether known runtime sentinel/hooks are present;
- public indexing option, non-secret WooPayments/Stripe/PayPal test
  config hints, WP-Cron configured event counts and cron constant.

**Neither gateway setting hints nor hook presence prove all real traffic is
isolated.** In particular, the current Dev Safety source's explicit outbound
host deny list covers AfterShip/Automizely; ERP/Shipout/SM/WMS/CRM provider
egress and external server Cron remain independent STOP items.

The inspector intentionally never returns option arrays, API tokens,
webhook URLs, customer/order data or storage paths; no mail/payment/API
provider call, no WordPress/DB write, no remote request and no backup
creation. Its output must stay in private Owner-controlled evidence storage,
**not GitHub**. Run only from an **independently authorized** Dev WP-CLI
service-owner path; do not invoke generic DC shell, a secondary connector
or browser to evade a previously denied privileged access. No authorized
runtime invocation of this new inspector has occurred.

The companion synthetic PHP/WordPress harness verifies host and staging
fail-closed, output contains no synthetic secrets, and the receipt never
asserts A1 PASS. **Full A0–A2 synthetic suite 26/26 PASS; PHP lint PASS**
on US Mac mini. Public A1 probe also executed against real Dev with 9/9 PASS.
This is SOURCE-ONLY for the inspector, **not Dev-installed/runtime PASS**.

### Safe final barrier to A2 import

A2 is still HOLD until **all** are independently obtained: current authorized
WWW+Dev full WP-CLI catalog/variation/media diff; matching installed Dev
Safety fingerprint and runtime provenance; independently attested test
gateway and mail/ERP/fulfillment/provider/cron isolation; verified Dev
backup and recoverable rollback; exact Owner-approved Dev SKU allowlist.
Existing 35 public wildcard variants, 4 indistinguishable public variation
identities and 2 blank parent SKUs remain quarantined.
No WWW/Dev data was changed during this work package.

**Canonical public GitHub source identity for later runtime readback (not a
Dev-installed claim):** `beyourlovercom/beyourlover-web-v3` main HEAD
`c7b0c067f56f26279ab2987e26276f4707c1b16c`;
`dev/mu-plugins/byl-dev-safety.php` Git source SHA-256
`7b8e3bc570b259766083aed75d58d03c3ca676c5704ef69a6d07434e33826500`.
This is an explicitly public-source fingerprint from an isolated Git
checkout, not a remote site's loaded file hash. A legitimate trusted
service-owner Dev WP-CLI receipt can later compare installed SHA-256 and
the actually approved release. A mismatch is an inventory discrepancy to
investigate, not license to overwrite the installed MU plugin.

## 2026-11-01 — actual data migration readiness: source/operator handoff

This is **not a request to re-run G5 synthetic payments**. Existing scoped G5
historical evidence is held at [Issue #31](https://github.com/beyourlovercom/beyourlover-web-v3/issues/31)
and the related Notion G5 closure dated 2026-10-11: declined card,
3DS success/cancel, recovery payment, and sandbox isolation tests were
accepted *at that historical Dev runtime*. Reuse those receipts, then
independently attest current Dev mode/identity and unchanged gates, rather
than creating another Woo order. Historical G5 did **not** prove ERP and
external provider polling were completely isolated.

**Execution path availability:** formal self-hosted BYL DC lists all four
devices READY (registry state, not live signed health), and the authorized
US Mac mini shell contains PHP and Git but **no local `wp` executable**.
It has no verified Hostinger WWW/Dev WP-CLI service-owner session. Earlier
attempts to obtain protected live WWW/Dev runtime through DC were
`OPERATOR_TOOL_DENIED`. Never repackage a restricted private read
as a different connector, generic shell command, or browser operation.
Public WWW access from this Mac previously returned HTTP 445, which is
not evidence of a site outage. No actual private snapshot was collected.

### Exactly one approved two-site read-only evidence acquisition

Only an independently authorized **Hostinger site service-owner** may run
the source-approved, read-only `scripts/byl_a0_a2_catalog_export.php`
inside each site's verified WP-CLI context. Before running, confirm
that the terminal belongs to the **intended** site, and that `home_url()`
matches the script's strict allowlist; do not infer the WWW webroot from
the Dev SSH account or any similarly named directory.

Save the output to a **protected local report store outside the public
webroot**, then compare with `byl_a0_a2_catalog_diff.py` and preserve
both report fingerprints/timestamps. No product/variation or secret
data should be copied into GitHub/Notion or chat. This is a zero-write
inventory run, not permission to install a plugin or modify either site.
Source inventory query includes publish/private/draft/pending statuses;
any archived/trash/future differences must be separately counted or
explicitly excluded, never reported as a complete total without scope.

### Exactly one authorized Dev-only A1 runtime evidence acquisition

Under the same valid Dev service-owner custody (never on WWW), use the
source-only `scripts/byl_a1_dev_runtime_readonly.php` in existing approved
WP-CLI. It checks a strict Dev/staging identity and emits only
non-sensitive version/module fingerprints, test-mode **hints**, active
Hook presence and Cron count. Compare installed Dev Safety fingerprint
to the exact approved release/source rather than overwriting it.
No remote payment, mail or provider request is made.

Independently review from existing authorized provider/ERP configuration:
1. Payment gateway **all active methods** TEST/no real charge; timeboxed
   synthetic permit is CLOSED; no need to perform payment UAT anew.
2. WordPress mail/third-party mail transport DENY verified from effective
   current config without sending an email.
3. ERP production Woo **pull** target is WWW and never Dev. ERP
   `WooCommerceInboundService::importAutomaticIncremental()` may query
   Woo Orders even when Dev webhooks are disabled. Also attest
   AfterShip/provider-side pollers, WMS, ShipOut, SM and CRM egress paths
   (the Dev Safety MU source only has explicit HTTP host DENY for
   AfterShip/Automizely).
4. WP-Cron, external scheduler and Action Scheduler actions cannot
   send real mail, submit warehouse jobs, or create real integrations.
5. An independently verified **Dev** database+files+R2/mapping recovery
   point exists and can be restored by a named owner. Don't assert
   recoverability based only on backup filenames; avoid making a
   duplicate backup if an adequate verified recovery point already
   exists.

### A2 authorization gate (nothing auto-approved)

A0 complete current two-site snapshots, reviewed SKU/variation collisions
(including 35 public wildcard and 4 indistinguishable variants), A1
live/provider-side safety, Dev recovery evidence and an **exact
Owner-approved SKU field-level allowlist** are each mandatory.
Even then, require a separate one-batch Dev-only reversible Canary:
preimage journal → apply → readback → restore exercise → idempotent rerun
before wider incremental sync. Do not copy WWW customers, orders,
credentials, payment/affiliate secrets, or overwrite existing V3 Theme/
Cart/Checkout code or local Dev edits. At present:
`A0=PARTIAL`, `A1=PARTIAL/HOLD`, `A2=NO IMPORT AUTHORITY`.

## 2026-11-01 — P0 A0 exporter bug closed at source level (v2, **not live installed**)

Review of the previous v1 WP-CLI exporter found a migration-identity
defect: WooCommerce `WC_Product_Variation::get_sku('view')` inherits the
parent SKU when a variation has no stored SKU. Calling `get_sku()` with
its default view context caused a SKU-less variation to masquerade as the
parent SKU. Separately, the v1 WP_Query `post_status` list omitted
`inherit`, which could silently exclude records having that native raw
status. These are **source-contract bugs**, not evidence of data loss on
WWW or Dev. WooCommerce upstream source shows the context-dependent SKU
fallback; therefore do not use old v1 snapshots for import planning.

Fix in Draft PR #168:
- `byl_a0_a2_catalog_export.php` now queries `product` and
  `product_variation` including `inherit`, plus publish/private/draft/
  pending/future/trash. `auto-draft` (editor placeholder) is explicitly
  out of scope. It exports raw post status and fails rather than silently
  skipping an unresolved Woo product ID.
- For every row, `sku = get_sku('edit')` and
  `sku_origin='stored'`; parent SKU also uses edit context. Thus an
  independent blank variation SKU **stays blank**, not parent-inherited.
- Output now carries `byl-catalog-a0-v2`, an exact enumerated-scope
  string, SKU provenance contract and self-checking record/variation
  counts. **v1 snapshots are incompatible and deliberately rejected**.
- `byl_a0_a2_catalog_diff.py` validates v2 contract, the site identity,
  record IDs, statuses, provenance and counts before matching. Existing
  wildcard/duplicate/unkeyed quarantine and unconditional
  `a2_import_authorized=false` remain in place.
- Added synthetic WordPress/Woo PHP runtime fixtures verifying that the
  query includes `inherit`, variation `get_sku('edit')` returns an
  independent empty SKU, parent SKU remains available, and a wrong
  host is refused; added regressions rejecting v1 snapshots, inherited
  SKU projections and inconsistent v2 counts.

**Exact-head isolated US Mac mini verification:** PHP lint PASS;
A0/A1/A2 Python synthetic tests **35/35 PASS**, including synthetic PHP
runtime. No Hostinger WWW or Dev read/write; this is **not** a live v2
snapshot or migration result. Do **not** reuse the earlier public Store
API 235/68 collection as a v2 authorized WP-CLI snapshot. Two actual
site-owner v2 exports are still needed once legitimate authorization and
A1 protected runtime/backup evidence are available.

**Policy reminder:** Auditing `trash`/future records does not authorize
restoring, publishing or importing them into Dev. Approval for product
Canary always specifies exact eligible status + SKU + field allowlist,
with a verified backup and rollback.
