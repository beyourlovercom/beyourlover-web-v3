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

## 2026-11-01 — AUTHORITATIVE LIVE DEV A0 v2 + A1 sanitized service-owner receipts (WWW remains untouched)

### Access provenance and zero-write controls

The previously authorized **formal BYL DC → Windows → existing
`byl-dev-hostinger` SSH alias** became usable using the installed Git for
Windows OpenSSH client. The server account/read-only WP-CLI target were
independently verified against WordPress `home` =
`https://dev.beyourlover.com` and `wp_get_environment_type()=staging`.
The Windows default `ssh` executable was absent; Git's already installed
SSH client was available. A different China Mac alias logged into a
different hosting account and was **not** used to inspect WordPress.
No credentials, private keys, PHP configuration, WWW SSH or WWW DB were
inspected; no machine/service/browser was restarted.

The exporter from **pinned PR #168 source commit
`20dac7f345e015584b0b451273be150e2a2da3ad`** was first
remote-PHP linted, then streamed from immutable GitHub source through
the authorized **Dev-only** WP-CLI `eval-file -`, without writing an
executable script to the server. Output was validated before being saved
as a private UTF-8 JSON report in the existing Windows user's local
app-data area. **Never upload the private report to public GitHub,
Notion, or this chat.**

- Report schema and identity: **`byl-catalog-a0-v2`**,
  `https://dev.beyourlover.com`, stored/edit-context SKU provenance.
- Captured UTC: **2026-11-01T09:28:32Z**.
- Local private Dev snapshot SHA-256:
  **`7a6b337505bedbefe5678b891befa92870c1c128b442c174e92fb22013256da0`**.
- **321 actual WP-CLI rows = 253 parent products + 68 variations.**
  Parent raw post statuses: **235 publish, 15 private,
  2 pending, 1 draft**; all **68 variation raw statuses publish**.
  These counts reconcile with independent WP-CLI read-only queries.
- Every variation's **stored/edit SKU is blank (68/68)**; the prior
  public Store API's parent-SKU display must NEVER become a native
  variation identity. **6 parents have blank own SKUs**.
- Native v2 variation parent IDs: **0 orphan records**.
  Read-only local identity preflight: **35 wildcard/unkeyed variation
  identities**, **0 duplicate constructible native identity-key groups**.
  **6 parents have no primary image** (a review cue, not proof of loss).
- Source side public API earlier counted **235 public parents**, which
  now exactly matches the **235 published WP product parents**.
  Other 18 parents are private/pending/draft; do not publish them
  automatically as part of migration.

**Dev A0 full v2 snapshot: ACQUIRED and validated. WWW A0 snapshot:
NOT ACQUIRED.** No full two-site diff or WWW→Dev product delta exists yet.

### A1 live Dev-only, sanitized WP-CLI receipt

The previously authored `scripts/byl_a1_dev_runtime_readonly.php`
was executed with the same safe pinned-source, authorized Dev WP-CLI
channel, and its sanitized receipt stored privately on Windows.
Receipt SHA-256:
**`539f7e1162890de80d8a3d400708bfd4883294cf1981abecef6523ab78123598`**.

Current live Dev evidence:

| Check | Observed Dev-only runtime | Meaning |
| --- | --- | --- |
| WordPress / Woo / environment | WP **7.1.4**, Woo **11.2.1**, `staging` | Dev identity PASS |
| Indexing | `blog_public=0`, public noindex/robots PASS | SEO isolation sample PASS |
| WooPayments | **enabled**, **test_mode=yes** | current TEST mode hint, no transaction |
| Stripe | **disabled**, **testmode=yes** | current TEST mode hint, no transaction |
| Other gateways | PayPal PPCP environment indicates SANDBOX, enablement UNKNOWN; independently installed payment plugins may exist | Full gateway inventory NOT VERIFIED |
| Dev Safety MU loaded | exists; runtime mail/HTTP/Checkout hooks present | Hook presence only |
| Dev Safety installed SHA-256 | **`f0979a924a83a320bfa8256ee919d133d44f2f00598757c6720d2489d004ce3b`** | **Does not match** previously pinned GitHub main source SHA `7b8e3bc570b259766083aed75d58d03c3ca676c5704ef69a6d07434e33826500`; DO NOT overwrite or claim current source parity |
| AfterShip | active, **1.18.5** | Newer than historical G5 baseline; inbound sampled public denials still PASS |
| WP-Cron | `DISABLE_WP_CRON=true`, **120 event records / 116 timestamps** | External Cron / Action Scheduler may still execute; no isolation PASS |
| Protected backup directory | exists, contains **2 candidate .sql.gz** archives dated **2026-10-10** (~5.5 MB / ~7 MB) | Old candidate metadata ONLY; site identity, freshness, files, R2 and restore are **NOT VERIFIED** |
| ERP/ShipOut/SM/AfterShip outbound/pulls, SMTP, CRM | NO provider-side/config corroboration | HOLD |

A bounded comparison against the examined GitHub main history did
not find the currently installed Dev Safety fingerprint. This does NOT
prove tampering; it may be a separately approved deploy. A service
owner must establish release provenance and verify effective mail,
provider egress, webhooks and credential boundaries before A1 PASS.
Do not try a real email/order/payment/warehouse event to shortcut
these controls.

### Explicit next gate

- **WWW access boundary is unchanged.** The Owner previously declined
  WWW SSH; do not request or probe it. Acquire a current authoritative
  WWW product/variation/status/media export through an **already
  approved WordPress admin / site-owner export route**. Native Woo CSV
  is NOT identical to the v2 JSON schema; use a reviewed converter
  before any two-site automated comparison. No WWW plugin install or
  write without separate explicit Owner approval.
- Reconcile the current Dev Safety MU hash to an approved release,
  and independently attest effective mail, ERP/WMS/ShipOut/SM/AfterShip
  egress/polling, external scheduler and a RECOVERABLE fresh Dev
  DB/files/media backup. Historical G5 sandbox payment tests are
  reusable; do not repeat transactions unnecessarily.
- Preserve 6 blank-SKU parents, 35 wildcard variations and 6 no-image
  parents as explicit exception-review cohorts, not automatic edits.
- Only after A0 **BOTH SITES** + A1 full PASS + reviewed preimage
  backup + Owner-approved SKU/field allowlist: propose one tiny,
  reversible **Dev-only** A2 Canary. This work created **zero Dev/WWW
  product, payment, email, order or warehouse writes**.

**State: `A0 DEV PASS / WWW HOLD`; `A1 PARTIAL / HOLD`;
`A2 PREVIEW-ONLY / IMPORT HOLD`.**

## 2026-11-01 — live Dev Safety source provenance **RESOLVED** (read-only)

This corrects the earlier bounded main-branch history lookup that
could not identify the installed Dev Safety fingerprint. A targeted
read-only Git comparison across related unmerged PRs found an **exact
file SHA-256 match**:

| Source | Safety MU SHA-256 | Status |
| --- | --- | --- |
| Current installed Dev | `f0979a924a83a320bfa8256ee919d133d44f2f00598757c6720d2489d004ce3b` | Authenticated Dev SSH file hash |
| [PR #166](https://github.com/beyourlovercom/beyourlover-web-v3/pull/166) HEAD `95a48e63a8a820e755064e6f7357f4a6880ea017` | **same exact SHA-256** | Draft, unmerged, Dev-only R13 Stripe TEST scope |
| [PR #164](https://github.com/beyourlovercom/beyourlover-web-v3/pull/164) HEAD `6125102f1b2f5366a29bf674430cb48b30c261a0` | `9824d5618ff7983013f2a480aed6f5fe122c00d9c58bf3dd10d11c7d266d7265` | Draft, earlier Marketing Dev-only gate |
| Prior pinned GitHub main | `7b8e3bc570b259766083aed75d58d03c3ca676c5704ef69a6d07434e33826500` | Different version |

**Technical source identity established.** This is *not* an approval to
merge PR #166, a proof of formal Dev installation approval, or proof
that all provider-side guards are currently safe. PR #166's **2026-10-28
historical comments** reported restoration to the earlier file
`9824d...` after an R13 experiment, so today's installed `f097...`
requires a change-history/authorization reconciliation for any release
closure. No source file was read from or written to WWW; Dev MU source
has **NOT** been replaced or restarted.

Further legitimate Dev-only readback:
- `andy_commerce_marketing_dev_uat_enabled` = `no`.
- `andy_commerce_marketing_p1_sandbox_enabled` = `no`.
- The previously documented R13 rollback script exists off-webroot,
  **not executed or functionally restore-tested**.
- Both prior backup `.sql.gz` candidate archives from 2026-10-10
  pass read-only `gzip -t` compression-integrity checks. This
  establishes valid gzip structure only; it does NOT prove the SQL
  content, target site identity, freshness, attached media/files, R2
  export, or verified restore. **A current recoverable Dev backup
  remains a blocking A1 prerequisite.**
- R13 source gate requires an authenticated eligible administrator,
  short-lived Canary, real eligible TEST rail and very narrow UAT
  fixture constraints; ordinary unauthenticated Checkout denial is
  confirmed by earlier public A1 9/9 probe. Do not activate or
  retry R13 payment to test A1.

**Revised gate:** Dev Safety **SOURCE PROVENANCE IDENTIFIED** (not
production merge/release attestation), marketing fixture gates OFF,
historical DB gzip integrity PASS; **A1 OVERALL HOLD** for provider-side
outbound and polling isolation, ALL relevant gateway enablement and
external queue/cron behavior, current full Dev backup/files/R2
recoverability and unresolved release-approval provenance.
**A0 WWW full authoritative catalog still absent; A2 import HOLD.**

## 2026-11-01 — Dev no-primary-image records reconciled with R13 fixtures

A targeted read-only WordPress admin WP-CLI check on the authorized Dev
account confirmed that four public/no-image parents
**40026, 40027, 40028, 40029** each have the
`_aicc_marketing_uat_fixture=yes` marker and status `publish`.
These are **Dev-only UAT fixtures** linked to the existing Marketing /
R13 test contract (see PR #164 / PR #166). Exclude these IDs from
ordinary catalog media-missing repair/migration candidate lists;
do not auto-publish to WWW or copy them as commercial products.

Using the existing protected full Dev v2 snapshot (no new export):
- Remaining no-primary-image parents after excluding those four:
  **Dev ID 26737**, `publish`, own SKU blank; and **Dev ID 34246**,
  `private`, own SKU nonblank.
- ID 26737 needs identity/manual review; no automatic title match,
  placeholder SKU, or silent media replacement.
- ID 34246 is private and has no primary image; preserve its draft/
  private workflow and do not auto-publish/alter it.
- These two are **review candidates**, not proof of WWW image loss.

A2 source/target diff still needs a current authorized WWW full
snapshot. A2 remains `HOLD`; no Dev or WWW product/image data was
written. This note is classification only.

## 2026-11-01 evening — real Dev Action Scheduler and backup gate (read-only plus failed cleanup)

**Source:** formal BYL DC Windows → existing authorized `byl-dev-hostinger`
→ verified `https://dev.beyourlover.com`, WP-CLI. No WordPress
scheduler run, checkout, mail or provider request was triggered.

### Action Scheduler: **EXTERNAL-JOBS HOLD**

- Action Scheduler **4.1.0**, `ActionScheduler_DBStore` and
  `ActionScheduler_QueueRunner`.
- Real DB status: **1,522 complete, 14 failed, 24 pending**.
  Latest completion observed 2026-11-01 **09:18:34 UTC**.
  `DISABLE_WP_CRON=true` does not by itself disable an Action Scheduler
  runner or independent external queue.
- All **24 pending** scheduled action hooks were enumerated without
  arguments, recipient details or payloads. Hooks requiring risk review
  include:
  `affwp_retry_action_required_payouts`,
  `affwp_monthly_email_summaries`,
  `affwp_payout_batch_watchdog`,
  `wcpay_store_setup_sync`,
  `woocommerce_tracker_send_event_wrapper`,
  plus core Woo session/order/stock-scheduled and
  `action_scheduler_run_recurring_actions_schedule_hook` hooks.
- All **14 failed** records show
  `hostinger-reach/jobs/cleanup_carts/start`.
- Recent **completed** hooks include
  `wc_stripe_deferred_webhook`, `wcpay_store_setup_sync`,
  AffiliateWP scheduled work, and Woo jobs. A status of
  `complete` is **NOT** evidence of external webhook delivery,
  customer payment, outbound email, or payout.
- The account-level crontab's `wp-cron` keyword count previously
  returned zero; that does not prove other Hostinger, HTTP or provider
  schedulers absent. **Do not disable these hooks globally or drain
  any queue without separate Owner/provider review.**

### Current Dev database backup: **NOT CREATED; recovery HOLD**

- Preflight: Dev `wp-content` ~335 MB, hosting filesystem
  approximately 3.0 TB free (shared filesystem capacity is not an
  individual Hostinger quota guarantee); DB reported ~98.96 MB.
- A standard WP-CLI Dev-only, off-webroot, restrictive-permissions
  database export was attempted with intended `mysqldump`-compatible
  flags. It produced **no SQL data** and returned nonzero
  (`255` on one direct attempt). No usable diagnostic text was
  available. The 20-byte empty gzip container and zero-length logs
  from the new temporary directory were verified and **removed**;
  the directory was removed. No original backup was touched.
- This is **not** a backup PASS. Do not repackage the rejected
  database export through direct database credentials, alternate
  remote tools or a second device to circumvent a security/hosting
  gate.
- Previously found two Oct 10 `.sql.gz` candidate archives did pass
  gzip integrity. Still unknown: Dev site identity and SQL completeness,
  attachment files, offloaded R2 media, backup freshness, restore
  procedure and isolated restore verification. Existing Hostinger
  panel backup/restore under proper Owner control is the next
  supported path, not an improvised DB export bypass.

**A1 remains HOLD** for current recoverable Dev backup, effective
outbound mail/provider/ERP/WMS/AfterShip/affiliate behavior and
external schedule provenance. The presence of payout/email/telemetry
hooks does not prove those side effects occurred; it rules out a
blanket assertion that Dev background business integrations are
disabled. **A2 data import remains unapproved and zero.**

## 2026-11-02 — P1 WWW product export preflight (source acquisition HOLD)

**Request:** execute P1 (current, authorized WWW product/variation
inventory and provisional Dev comparison). Source is WWW WordPress admin;
no WWW SSH, production plugin changes, API token/cookie extraction, or
unapproved alternative egress.

### Verified access / historical candidate discovery

- The native ChatGPT browser connection could not list the user's
  browser tabs without its originating extension context. The
  authorized China Mac's existing Chrome session contained **no WWW
  WordPress admin tab** (no Chrome restart or new tab opened).
  Windows/007Windows Downloads had no identifiable current product
  export. No logged-in WWW admin session was obtained.
- China Mac Downloads had **historical**, unsuitable files: some
  `wc-product-export-24-8-2026-...` exports from 2026-08-24
  with **116 rows and selectively reduced columns** (or 0 rows);
  `wc-products-report-export-17923975957923.csv` from 2026-10-19
  with **103 rows and a nonnative analytics header**. These are
  NOT current comprehensive WWW inventory, and are deliberately
  excluded from P1 parity claims.
- Dev authoritative `byl-catalog-a0-v2` 321-record, private Windows
  snapshot remains available at the previously attested SHA. No WWW
  inventory at the same scope/time was collected in this P1 attempt.

### WooCommerce official native CSV scope and limits

The current WooCommerce built-in exporter uses the **edit context**
for most raw product fields, including `sku`, and can export variable
products and variations. Its native export query includes publish,
private, draft, future and pending statuses; it does **not** itself
certify trash/attachment database completeness, R2 offload metadata,
native image attachment ID equality or all plugin product custom
fields. Product status `Published` encodings are 1=publish,
0=private, -1=draft, 2=pending. Verify the actual WWW WordPress
export version if these assumptions are material.

A native CSV is **NOT** a JSON `byl-catalog-a0-v2` export and must
never be falsely relabeled/repackaged as v2. A complete authoritative
source v2 or reviewed read-only field-specific adjunct would still be
needed for final media/R2 and raw-status parity.

### Prepared/offline-tested P1 safe comparison

New `scripts/byl_p1_www_woo_csv_audit.py` reads an **unaltered
native WWW Products CSV**, validates IDs, required columns and native
statuses, and compares **only unique parent SKU + Woo type**
provisionally against the already authorized Dev v2 snapshot.
It checks unkeyed/duplicate parent SKUs, variation missing parent
references, null attributes and missing images, and excludes the
four proven Dev R13/Marketing fixtures 40026–40029 from ordinary
commercial product comparisons.

It intentionally **does not** claim native variation identity parity,
taxonomy/term ID parity, media attachment/R2 parity, complete WWW
coverage, or allow any Dev product writes. Its report unconditionally
contains `a0_complete_both_sites=false`,
`a2_import_authorized=false`, `gate=HOLD`. Detailed SKU/ID
results are for private local storage only; stdout reports aggregate
counts. New P1 synthetic tests **7/7 PASS**; previously verified
A0–A2 tests **39/39 PASS** on US Mac mini. Foundation CI workflow
now runs both sets, with zero WP or external API calls.

### Exact one-time authorized native WWW export gate

In an **already authenticated WWW WordPress administrator session**,
navigate to **Products → All Products → Export**, choose **all columns,
all products, all categories, all product types** and keep custom
metadata unchecked (to avoid exporting private plugin secrets).
Export native CSV with parent and variation rows included, preserving
file bytes as downloaded (do not open/resave in Excel).
Do not install a plugin or edit a product. An operator must verify
the resulting header contains ID, Type, SKU, Published, Parent,
Categories and Images and record Woo admin count by status / variant
scope. Do not send credentials, API keys or cookies.

If downloaded onto the already authorized China Mac, use its private
Downloads folder for local-only CSV review. Once the file exists,
execute the source-only audit against the existing protected Dev v2
JSON, preserving all detailed reports privately; do not push them to
GitHub/Notion. A later WWW full WP-CLI-style v2 or reviewed alternative
must still reconcile custom media/R2/native statuses before A0 COMPLETE.

**P1 current status: CSV audit tool PASS, actual current WWW export
NOT ACQUIRED; A0 WWW HOLD, A1 safety/backup HOLD, A2 import HOLD.**

## 2026-11-02 — P1 LIVE WWW native export and real Dev v2 provisional SKU reconciliation

The Owner downloaded **two identical** current `wc-product-export-2-11-2026-*.csv`
files into the authorized China Mac Downloads. Input SHA-256 was
`39aa0efde3ed5ffe7151c71292234e052f2933016c9f014fc5f10156ee66664b`.
File `wc-product-export-2-11-2026-1793587861247.csv` was selected
for analysis. **Raw source and detailed report stayed on Owner devices.**

### Native source integrity, with explicit limits

- The file starts with two whitespace-only CSV records then an
  **embedded UTF-8 BOM at the real Woo header**; header has **150
  columns**. Rows arrive in 50-row groups of **135 / 143 / 147 / 149
  / 150 columns**, consistent with late-discovered plugin metadata
  columns. P1 parser recognizes this real layout, fills only missing
  *trailing* fields for conservative SKU/status/parent checks, and
  does NOT certify incomplete custom metadata. **Do not re-save or
  edit this CSV in Excel**.
- **315 unique WWW native IDs = 247 parent products (225 simple, 22
  variable) plus 68 variations**, no duplicate parent SKU groups.
  Parent statuses: **228 publish / 15 private / 2 draft / 2 pending**.
- Dev existing independently authorized 2026-11-01 private
  `byl-catalog-a0-v2` snapshot: **321 native rows = 253 parents +
  68 variations**. To run P1 entirely on the Owner's machines,
  Windows supplied only a minimal in-memory Dev SKU/type/status
  projection; **not** the full private Dev JSON. All detailed output
  remained on China Mac in
  `~/.byl-private/p1-www-dev-20261102/www-dev-p1-provisional-diff.json`,
  permission 0600 (directory 0700), SHA-256
  `0e5fdb83c0d7c20cb0205e5df42e381528f0de55ce9e9d4630f911c31bc6b562`.
  Dev full v2 source SHA-256:
  `7a6b337505bedbefe5678b891befa92870c1c128b442c174e92fb22013256da0`.

### REAL P1 conservative comparison

| Check | Verified result |
| --- | --- |
| Parent native SKU + type provisional unique matches | **238** |
| Matched parent publication status differences | **0** |
| Parent duplicate non-empty SKU groups | **0** on WWW, **0** previously on Dev |
| Native SKU-only result before same-ID drift classification | **3 WWW-only**, **5 Dev-only** |
| Identical WP ID but conflicting native SKU, **quarantine** | **2**: ID 35550 (WWW `VP757A`, Dev `VP757B`); ID 36134 (WWW `VP757A & MF414A`, Dev `VP757B & MF414A`) |
| After keeping possible SKU drifts separate | **1 WWW-only** draft `BX033B-1` (ID 41810); **3 Dev-only** published `S137B-purple` (32905), `SG02` (32940), `VX737A` (34470) |
| Parent blank own SKUs | **6 on each**, with matching ID/status sets: 9128, 9132, 17085, 17286, 24875, 26737. This is an audit clue, **not** authorization to treat WP IDs as cross-site stable identities |
| Own stored variation SKUs | **3 on WWW**: `pillow-blue` (38164), `pillow-green` (38165), `pillow-grey` (38166), parent `pillow`. **0/68 on Dev**. These are a protected variation-SKU drift exception cohort |
| Variation attributes | **35** with null/wildcard required values in WWW; **35** on the previously reviewed Dev v2/public snapshots. **0 unresolved WWW variation parent references** |
| Native parent CSV missing images | **2**: 26737 (publish, blank SKU), 34246 (private, `RJ238A`). These are the **same two** non-fixture Dev no-primary-image parent IDs. Four further Dev no-image records 40026–40029 are independently confirmed Dev UAT fixtures, not ordinary catalog products |
| Protected Dev fixture exclusion | Four proven Dev R13/Marketing fixtures 40026–40029 removed from ordinary SKU candidate comparison; **249 commercial/non-fixture Dev parents vs 247 WWW parents** |

**Interpretation:** The original apparent `3 WWW-only / 5 Dev-only`
mismatch should not trigger six/eight product creations/deletions:
**two pairs have matching WP ID but conflicting SKU**, and must be
reconciled as potential SKU edits by an Owner or native authoritative
product history. Current source WWW is commercially authoritative
but this report does not claim blanket safe overwrites.

### Real P1 quality gates / remaining limits

The raw WWW file exposes many `Meta:` fields. Do **not** publish it
or a derivative containing private plugin metadata. This P1
analysis compares only unique native parent SKU and type, and checks
status/variant-parent/declared attribute evidence. It does NOT prove
that the native CSV covers every WP `trash` status, every media
attachment/thumbnail/R2 key, custom product meta, or secure identities
of SKU-less variations. These source gaps cannot be replaced by
relabeling the CSV as the v2 WP-CLI exporter output.

Current P1 implementation and tests at PR #168:
`scripts/byl_p1_www_woo_csv_audit.py` and corresponding unit
suite; **39 prior A0–A2 + 12 P1 = 51 isolated tests PASS** on formal
DC. Report remains `gate=HOLD`, `a0_complete_both_sites=false`,
`a2_import_authorized=false`. WWW was **only read from Owner's CSV
on the Mac**; no WWW SSH/login or Dev/WWW data writes.

**P1 outcome: Current WWW CSV ACQUIRED; provisional parent SKU delta
PASS; full A0 media/R2/variation identity scope still PARTIAL.
A1 recoverable backup and external system isolation still HOLD;
A2 import still HOLD.** Exact next Owner decision is reconcile 35550/
36134 native SKU direction and the WWW Pillow variation SKUs,
then use a proper current Dev restore point before proposing any
A2 product Canary.

## 2026-11-02 — Owner decision: WWW is the canonical SKU authority

The Owner explicitly decided: **when product/variation SKU anomalies
appear between production WWW and Dev, preserve the currently approved
WWW native stored SKU as the source of truth**. Do not let a Dev test
snapshot silently overwrite WWW. This decision resolves the direction
of candidate corrections; it **does not** authorize an A2 import,
reclassify unrelated products, or establish cross-site identity by
equal WordPress ID alone.

| Candidate | Verified WWW authority | Dev observed | Planned Dev-only direction (NOT applied) |
| --- | --- | --- | --- |
| Parent ID 35550 | `VP757A` | `VP757B` | After independent same-product identity check, preserve WWW `VP757A` |
| Parent ID 36134 | `VP757A & MF414A` | `VP757B & MF414A` | After identity check, preserve WWW `VP757A & MF414A` |
| WWW PILLOW variation IDs 38164–38166 | `pillow-blue`, `pillow-green`, `pillow-grey` | Dev variations have blank stored own SKUs | Map variations by verified parent + actual option/attribute values and restore WWW independent SKU values on corresponding Dev variations; **do not assume IDs match** |

The same decision also applies to future verified WWW-vs-Dev SKU
conflicts. It does **not** imply auto-creating WWW-only draft
`BX033B-1` on Dev, removing Dev-only published SKUs
`S137B-purple`, `SG02`, `VX737A`, filling blank SKUs
from guessed names, or altering existing WWW inventory.

The P1 offline report now emits `owner_decision.sku_authority=WWW`
and fields for **proposed** Dev SKU values, explicitly marked
`application_allowed=false`. Existing
`a2_import_authorized=false` / `gate=HOLD` remain.

**Next dependency before any Dev write:** identity mapping,
current **recoverable** Dev DB/files/media/R2 backup, complete A1
external integration/queue isolation, precise field+SKU target
allowlist, and a separate explicit Owner approval for the Dev-only
Canary. WWW remains read-only throughout this branch.

## 2026-11-02 — five-record WWW-canonical SKU-only Canary plan READ-ONLY PASS

Based on the Owner decision that WWW production's native SKU wins,
we completed a deterministic, source-fingerprinted offline preimage
check using the **real WWW WooCommerce CSV** and **actual protected
Dev v2 WP-CLI snapshot**. No Woo/WordPress writes.

- Read-only program: `scripts/byl_a2_www_sku_canary_plan.py`.
  The WWW native CSV is fixed at SHA-256
  `39aa0efde3ed5ffe7151c71292234e052f2933016c9f014fc5f10156ee66664b`;
  the independently archived Dev v2 JSON is fixed at
  `7a6b337505bedbefe5678b891befa92870c1c128b442c174e92fb22013256da0`.
  Both source hashes verified on their respective Owner machines.
  A restricted Dev SKU/type/status/price/attribute projection was
  transferred in-memory, not the full sensitive source JSON.
- Scope is **EXACTLY five existing Dev records, SKU field only**.
  Two simple products (IDs 35550 and 36134) passed price, status, type
  and exact-before-value checks. Three Pillow variations (38164,
  38165, 38166) additionally passed parent ID=38047, parent
  stored SKU `pillow`, **blue / green / grey** option, type,
  price 59, and blank Dev stored SKU.
- Planned target values: 35550 `VP757A`, 36134
  `VP757A & MF414A`, 38164 `pillow-blue`, 38165
  `pillow-green`, 38166 `pillow-grey`.
- Plan enforces exact source row-count/status/price/parent/color
  preconditions, no target-SKU collisions, and refuses stale source,
  schema, before-values, variation ambiguity, or broadening the
  allowlist. Neither media, price, title, visibility, stock,
  parent identity nor any unrelated variation is in scope.
- **Real-data plan: 5/5 planned preimages PASS**, immutable
  SHA-256 `686824bab5050d519f6cb8535c9dcd830c15fd24c8354bc8c8413481ee33f387`;
  private file on China Mac at
  `~/.byl-private/p1-www-dev-20261102/sku-only-dev-canary-plan.json`
  (0600).
- Isolated regression: 53 A0/A2 tests + 13 P1 tests = **66/66
  PASS**, including 11 new five-record preimage cases.

**Implementation correction encountered and tested:** The actual
Dev v2 JSON represents absent attributes in **228 simple products**
as `[]`, not `{}`. The older validator rejected this real,
legitimate Woo/PHP empty-array encoding. It now accepts `[]`
**only for non-variation products**, treats it as equivalent to
an empty object for a field-diff, and rejects an array for a
variation (whose identity must be explicit). This fix was unit
tested and used in the successful real-data plan.

**Current authorization remains unchanged:** `gate=HOLD`,
`a2_import_authorized=false`, every planned action
`apply_allowed=false`. Before actual Dev SKU modification,
validate a fresh live readback (all five before SKUs and parent/
color/unique SKU invariants), current **complete recoverable Dev
backup/restore proof**, and full A1 outbound mail, ERP/WMS,
AffiliateWP/AfterShip, payment, queue/Cron isolation. Then obtain
a separate exact-five `_sku` **Dev-only Owner write approval**.
No WWW changes, no product import or live SKU changes now.

## 2026-11-02 — live Dev exact-five SKU preimage/uniqueness readback PASS (no write)

Authorized formal BYL DC Windows → existing Hostinger Dev WP-CLI
performed a fresh, **read-only** direct `get_post_meta(id,'_sku',true)`
check and WooCommerce `wc_get_product_id_by_sku(target)` lookup for
**only the five preapproved candidate IDs**:

| Dev ID | Current own stored `_sku` | Proposed authoritative WWW SKU | Target SKU already used on Dev? |
| --- | --- | --- | --- |
| 35550 | `VP757B` | `VP757A` | No (lookup ID 0) |
| 36134 | `VP757B & MF414A` | `VP757A & MF414A` | No (lookup ID 0) |
| 38164 | empty | `pillow-blue` | No (lookup ID 0) |
| 38165 | empty | `pillow-green` | No (lookup ID 0) |
| 38166 | empty | `pillow-grey` | No (lookup ID 0) |

**Result: fresh live SKU preimage and target uniqueness checks 5/5 PASS.**
Combined with the immutable WWW CSV + Dev v2 attribute/parent/
price/status comparison, this is a reliable **read-only proposed
five-field SKU patch specification**. It is not yet proof of runtime
A1 outbound isolation nor a backup/restore guarantee.

Current PR #168 contains the local-source-only validator, strict
fail-closed tests and separate private signed-plan receipt; no Dev
source installation, SKU mutation, variation rebuild, product
creation, payment, mail, order or WMS action occurred. Continue to
require **independently verified current complete Dev recovery point,
A1 live outbound/inbound and scheduled job isolation, explicit
five-field Owner Dev-only Canary authorization, fresh readback
immediately before any future write, and post-write/rollback receipts**.

**Decision remains: WWW SKU authoritative; A2 writes HOLD.**

## 2026-11-02 — A1 LIVE isolation recheck and Hostinger-managed backup handoff

**Scope:** Source-only and authorized Dev read-only inspection via
formal BYL DC Windows → already authenticated `byl-dev-hostinger`,
plus fixed unauthenticated GET/HEAD public endpoint probe from formal
DC US Mac mini. NO checkout attempt, Woo order/customer write, mail,
provider webhook dispatch, Action Scheduler execution, manual DB export,
host restore, plugin toggle, or www access. Existing Owner 5-SKU plan
remains separately frozen and unapplied.

### PASS today, precise scope only

- **Fixed public Dev A1 9/9**: noindex/robots 200, native Woo
  Orders and Customers REST 403 with expected
  `byl_dev_g5_provider_inbound_quarantine`, AfterShip route/query
  403, Store API checkout V1/legacy 403, public products endpoint 200.
  All are credential-free GET/HEAD; this does not prove provider-side
  or authenticated-client isolation beyond identified guards.
- **Authenticated WP-CLI:** `home_url=https://dev.beyourlover.com`,
  `wp_get_environment_type=staging`,
  `DISABLE_WP_CRON=true`. Runtime hook presence checks passed for
  `pre_wp_mail`, `pre_http_request`, `rest_pre_dispatch`,
  `woocommerce_webhook_should_deliver`, and
  `woocommerce_available_payment_gateways`.
- **Deployed Dev Safety MU** SHA-256 remained exactly
  `f0979a924a83a320bfa8256ee919d133d44f2f00598757c6720d2489d004ce3b`,
  matching Draft PR #166 (NOT GitHub main).
  Marketing two option gates still `no`; both the P13 sandbox
  permit and newsletter one-mail permit files were absent.
  The exact installed source has a default-deny `pre_wp_mail`
  guard and a domain-bound `pre_http_request` AfterShip/
  Automizely filter. These are positive **WP mail path** and
  **specific provider-host path** controls, **not** proof of
  blocking custom SMTP, non-WP HTTP clients, inbound credentials
  in external systems, or independent ERP/WMS actions.
- Dev Woo `shop_webhook` post list with any status produced
  **zero rows**. This checks the native WP post representation
  only; Woo webhook settings, different storage/provider queues,
  and external pulling services remain independently scoped.
- Active Dev plugins include **AfterShip WooCommerce Tracking,
  AffiliateWP, WooPayments, Stripe gateway, and Advanced Media
  Offloader**. Active plugin presence is **not** evidence of a
  successful external delivery or transaction.

### Action Scheduler: CLEAR CURRENT BLOCKER

- Newest live Action Scheduler 4.1.0 / DBStore status:
  **1,528 complete, 14 failed, 24 pending**, up from 1,522
  complete in the previous readback. Latest complete record timestamp
  **2026-11-01 17:15:28 UTC**. `DISABLE_WP_CRON=true` does NOT
  make this queue inert.
- Recent complete hook names include `wcpay_store_setup_sync`,
  `woocommerce_cancel_unpaid_orders`,
  `affwp_payout_batch_watchdog`, `woocommerce_tracker_send_event_wrapper`
  and unrelated maintenance tasks. The status `complete` is NOT
  evidence of an external payment, cancellation, payout, email, or
  webhook successfully occurring. The queue can still run.
- **Do not clear, drain, edit, or globally disable scheduler.**
  Before SKU writing, the service owner must verify that Dev's
  *product update* hooks cannot dispatch ERP/WMS/ShipOut/SM sync,
  AffiliateWP payouts, mail, AfterShip or unrelated real-site
  actions; verify independent provider-side jobs do not pull Dev.
  The existing Woo Orders/Customers REST 403 is useful but
  insufficient to certify all external integrations.

### Current complete recoverable backup: BLOCKED, exactly scoped next action

- The earlier failed WP-CLI database export was not retried or
  bypassed. Its empty disposable files had been removed. Old
  Oct-10 DB gzip archives are not a contemporary full restore point.
- Hostinger's current official help instructions:
  https://www.hostinger.com/support/2298928-how-to-create-backups-at-hostinger/
  and
  https://www.hostinger.com/support/5981435-how-to-download-backups-at-hostinger/.
  In the hosting **owner's authenticated hPanel**, select **Websites
  → the DEV site account → Dashboard → Backups → Create Backup**
  (manual backup availability depends on plan). Use the **Dev
  site/server account**, NOT WWW, then record the latest backup
  timestamp and whether its files+database components were created.
  Do **not** press Restore on the live Dev or WWW.
- To verify a usable rollback independently, download (via approved
  hPanel) a current Dev site/files `.tar.gz` and DB `.sql.gz`
  into a protected owner-controlled location, check file sizes,
  SHA-256, archive integrity and the proper WordPress site identity.
  Test restore **only in a separate disposable sandbox**; never
  overwrite active Dev or production orders. The hPanel backup might
  cover all hosting-plan files/DB rather than only one domain;
  double-check `dev.beyourlover.com` identity and specific database.
- Dev has active **Advanced Media Offloader**; Hostinger hosting
  backup alone does **not** establish that independent Cloudflare
  R2 object bytes are backed up. Preserve DB attachment mappings,
  and separately attest R2 object/key availability / versioning
  or an approved object-storage recovery plan before full
  media/restore PASS. Do not enumerate cloud credentials.
- No authorized hPanel session/tool is currently available; **a
  host-owner hPanel backup confirmation is the one immediate
  external action**. No Hostinger backup or restore occurred here.

**Updated A1 gate:** public boundary/Dev Safety mail and specified
provider HTTP controls **PASS within their exact scope**;
**Action Scheduler/ERP+provider independent pull/mail transport
and current full Hostinger+R2 backup recovery HOLD**. The Owner's
five exact WWW-canonical Dev SKU preimages remain 5/5 PASS
from the previous scoped live read. **A2 actual SKU update HOLD,
no WWW/Dev business data mutations, PR #168 stays Draft.**
