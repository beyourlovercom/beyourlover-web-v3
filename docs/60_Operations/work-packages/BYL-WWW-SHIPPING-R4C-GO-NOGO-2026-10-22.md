# BYL Shipping R4-C — release evidence and production Go/No-Go (2026-10-22)

**STATUS: NO-GO / HOLD.** This file is evidence-only. It is NOT an implementation, a release authorization, or a new visual-UAT requirement. No main merge, plugin install/upgrade, Dev/WWW deploy, WWW SSH, real order/payment/mail changes, or READY-device restart.

## 1. Frozen owner contract (do not regress)

- Customer-facing line (link the *entire sentence* to `/free-shipping/`): **Free shipping from $69 (special items excluded)**.
- Ordinary merchandise: destination-specific Woo zone; eligible **discounted** ordinary line totals excluding shipping/tax. Do not present a global $69 promise or invented fees.
- Three exceptions: #32251/#9145/#9153, **USD $30 per combined special package** (not per unit), regardless of free shipping.
- PILLOW #38047 (variation #38165): **USD $30 per unit**, distinct from the special parcel fee.
- No exemption based solely on literal `BYL49`. A valid native Woo free-shipping coupon must use `is_valid() && get_free_shipping()`, and never waive mandatory paid special/PILLOW packages.
- Source of actual rates, tax and selected methods is WooCommerce. The foreign-currency charge must be the selected-currency equivalent of the USD-base fee, not 30 units of every currency.

Existing R1–R3 Dev source and Owner visual PASS remain accepted; no duplicate visual review.

## 2. Fresh PR / CI evidence

| Pull request | Exact head, refreshed Oct 22 | GitHub Foundation CI | Meaning |
|---|---|---|---|
| [#142](https://github.com/beyourlovercom/beyourlover-web-v3/pull/142) | `816885048316d85adc5c620c71f2dcc784b5f277` | SUCCESS #39015343726 | Owner Checkout PASS, Draft, **non-mergeable**, do not overwrite |
| [#156](https://github.com/beyourlovercom/beyourlover-web-v3/pull/156) | `b738fa56d15fa2404ab0449c3aaaeb2a6bf5d336` | SUCCESS #39736746137 | OFF-by-default, **unwired** source only; mergeable to #155 branch, not production clearance |
| [#157](https://github.com/beyourlovercom/beyourlover-web-v3/pull/157) | `aee3c58b881d5bb28ea0d0ccac091563642fde59` | SUCCESS #39736935702 | Draft ghost-free Checkout visual guard; mergeable to #156 branch, not main |

Dependency chain: #150 + #152 → #153 → #154 → #155 → #156 → #157 → this audit only. Review latest exact-head again before any independent main authorization.

### Source evidence already obtained (reuse, do NOT conflate with live UAT)

- #156: **167 mock/no-network PHP assertions** and **180 five-zone × five-currency acceptance fixture rows**, with shipping candidate permanently absent from runtime bootstrap; Foundation CI passed.
- Official published YayCurrency **Lite** 3.3.5 inspection: its `woocommerce_package_rates` converter at priority **10**, minimum converter at **20**. Proposed BYL `byl_www_ship_rates` would run at **100200** if separately wired: assigning raw `30.0` after FX conversion is **unsafe for non-USD**. Pro code/path must be independently checked.
- Candidate requires FX-reviewed sentinel, ≥3.3.5 vendor prerequisite and **verified effective USD**; does not provide a safe general five-currency shipping bridge. Enabling under current multi-currency production settings is prohibited.
- Woo native discount/coupon and zone-specific fail-closed/source tests have passed, not a live five-currency Woo/Checkout rate/tax/cache verification.
- Prior #142 exact-head source-only three-file overlay (JS approved single-line prefix, PHP WWW special/PILLOW ghost-free guard, CSS small mobile chevron alignment) passed isolated Checkout tests. It was **not** committed to #142, deployed, or proven mergeable; preserve #142's owners and Cart V2. Compare #148 and Theme OS manifests before future source integration.
- Dev Checkout's `403 byl_dev_sandbox_checkout_closed` is an intentional security boundary. WP-CLI read-only native Woo probe previously produced generic critical-error, so full runtime probe is **UNVERIFIED**.
- Authorized remote test availability on Oct 22: Desktop Commander reports monthly usage quota exhausted. Devices remain paired; **do not reconnect, restart, bypass or infer runtime PASS**.

## 3. Production native-settings ledger (read-only audited; never apply Dev IDs)

| Woo zone | Free instance / rule | Ordinary flat instance / USD | Special/PILLOW rule |
|---|---|---|---|
| 0 Rest of World | #1; `either` coupon OR discounted $69 | #3 / $20 | special $30 per package; PILLOW $30/unit |
| 1 HK | #2; `no` minimum | none | paid exception still mandatory |
| 3 high-cost | #6; $169 | #5 / $30, class calculation type `order` | paid exception independent |
| 4 AK/HI | #8; $129 | #7 / $20 | paid exception independent |
| 5 US/selected Asia | #9; $69 | #10 / $10 | paid exception independent |

The legacy `shipping_class=1939` surcharge + flat base is **NOT** the owner-approved final special fee. The production adapter must isolate #32251/#9145/#9153 per combined package, and #38047 per unit. Zone 0's actual `either` is deliberately preserved.

## 4. R4-C gate evaluation

| Work package | Result | Remaining condition |
|---|---|---|
| R4-C1 fresh HEAD/CI/stack audit | **PASS** source metadata checked | Re-read HEAD if branches move |
| R4-C2 trusted Pro ≥3.3.5, entitlement, Dev-only installation | **BLOCKED / NO-GO** | Vendor-origin authenticated package and separate Dev-only authorization; prior third-party ZIP must never be installed |
| R4-C3 FX hook ordering, taxes, Order-type, cache, coupons | **SOURCE REVIEW PASS; RUNTIME UNVERIFIED** | Legitimate Pro plugin path, selected-currency rate/fee/tax and invalidation tests in isolated Dev |
| R4-C4 Checkout #142 min-diff source reconciliation | **ISOLATED OVERLAY PASS; INTEGRATION HOLD** | Checkout owner branch conflict resolution and full exact-head hosted CI; never wholesale replace Checkout |
| R4-C5 production rollout plan, rollback | **NO-GO recorded** | All dynamic gates first; independent exact-head main and WWW release approval required |

## 5. Mandatory dynamic acceptance (no real paid orders)

Test actual **USD, EUR, GBP, AUD, CAD** selected checkout currencies for each of Woo zones **0/1/3/4/5**, including Zone 3 `order`, with representative destinations GB, FR, HK, US-CA, US-AK, HI and selected high-cost area. Real-world YayCurrency Pro hook order and rate/fee conversion must agree with displayed Woo shipping, taxes and total after currency-specific rounding.

For every applicable zone, cover ordinary merchandise just under/at the local discounted threshold ($68.99/$69 or $128.99/$129 or $168.99/$169), ordinary + one/two exceptions, two distinct special SKUs sharing one package, 1×/2× PILLOW and mixed special+PILLOW, valid native free-shipping coupon vs ordinary coupon / literal BYL49, and empty/changed country/address. Recalculate Woo package hash on enablement/config/currency/destination/coupon change and verify shipping-rate/tax totals are not stale. Never fabricate exchange rates, fallback to undocumented FX behaviour, submit real orders/payments or bypass Checkout 403.

## 6. Release + rollback sequence (requires new independent owner approvals)

1. Authenticate official vendor package provenance and license entitlement; authorize **Dev-only** plugin test in an isolated environment. Retain prior Dev plugin-version snapshot and restoration method.
2. Close five-currency actual Woo-rate/tax/Checkout gates, including Zone3 `order`, Woo cache/selection, Andy Commerce BYL49 exact behavior, and Checkout #142 integration. Document every unsatisfied check as UNVERIFIED/NO-GO.
3. Re-read all exact HEADs and CI; separately request an **exact-head GitHub main merge authorization**. **No merge is implicitly approved by this audit.**
4. Prepare WWW **WordPress admin** read-only snapshot of every enabled instance, zone order/location, current plugin/theme build, settings digests, currency behavior and source backups. **Never use WWW SSH**.
5. Request separate explicit **WWW release authorization** before any production admin save, activation, plugin upgrade, theme deploy or shipping method change; then perform safeguarded fee/tax/Checkout UAT and final Owner mobile review only if user-visible changes require it.
6. Roll back only the production changes actually made: first turn OFF newly armed shipping (if independently authorized), verify Woo real rates, then restore reviewed instance/config/build snapshots through approved release controls; never overwrite WWW from Dev snapshots.

**Stop at final Owner gate. R1–R3 Owner PASS remains valid. Current decision: HOLD.**
