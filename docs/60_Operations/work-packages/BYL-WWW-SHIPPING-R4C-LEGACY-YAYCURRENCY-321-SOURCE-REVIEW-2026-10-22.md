# R4-C: YayCurrency Pro 3.2.1 — source-only legacy compatibility candidate

**2026-10-22: UNWIRED, OFF by default, NO DEV/WWW DEPLOY, NO main merge authorization.** Owner confirmed **no subscription renewal and no new Pro ZIP**. The purpose is to investigate adaptation to the *existing* WWW licensed Pro 3.2.1, not to silently lower a security requirement.

## Frozen production shipping requirements

Keep exact approved single-line Cart/Checkout notice: `Free shipping from $69 (special items excluded)` with whole text linking to `/free-shipping/`. Woo zones 0/1/3/4/5 preserve real native conditions (Zone0 `either`, Zone1 free/no-minimum, other thresholds $169/$129/$69); discounted *ordinary-only* subtotal determines ordinary threshold. Product #32251/#9145/#9153 **USD 30 per special parcel**; PILLOW #38047 **USD 30 per item**. Full fee may not be waived by ordinary or free coupons. BYL49 text alone is not an authorizing Woo free-shipping coupon. Keep real Woo rates and shipping tax.

## New isolated checks

- Exact Pro header `3.2.1` **and** active plugin, Woo base `woocommerce_currency=USD`, named `BYL_WWW_SHIPPING_V1_LEGACY_321_REVIEWED` sentinel, pre-existing production Owner flags, and explicit verified selected-currency detector are required. No auto switch, no WP settings writes.
- Requires YayCurrency 3.1.0+ documented `yay_currency_convert_price` and `yay_currency_revert_price` hooks *to actually be registered*, plus numeric/finite/positive values. Tests conversion of base USD 30 and round-trip back to USD with a fail-closed bound.
- Proposed Woo `woocommerce_package_rates` post-converter callback at priority 100200 converts newly constructed mandatory **base-USD fees exactly once** into the effective currency; never charges numeric 30 in EUR/GBP/AUD/CAD by relabeling.
- For an ordinary non-free destination (especially Zone3 `order`), compares the already-converted native Woo flat rate against a separately converted expected USD base freight. An incompatible vendor native amount yields *no shipping options* rather than charging a known-wrong amount.
- Incorporates effective currency and converter output into the package cache fingerprint, so currency/rate drift prompts Woo recalculation.
- All runtime hooks still reside only in `scripts/release-candidates/www-shipping-production-candidate-v1.php`; **there is no theme bootstrap**. `byl_www_ship_boot()` requires a *future separately authorized runtime loader* and is not executed by this branch.

## Isolated regression (US Mac mini, user-owned OAuth DC)

`php -l` candidate + harness: **PASS**. `php scripts/test_www_shipping_production_candidate_off_v1.php`: **199 offline assertions PASS**, increased from 167. `python3 scripts/test_www_shipping_fx_acceptance_matrix_v1.py`: **180 fixture rows PASS**. Cases cover synthetic USD/EUR/GBP/AUD/CAD ratios, mixed normal+special+2x pillow, special $30 vs pillow $60, legacy Zone3 `order` native mismatch fail-closed, invalid/throwing vendor converter, incoherent reverse conversion, unknown currency, wrong base, wrong Pro version, absence of registered vendor hooks, FX cache invalidation, and inertness on Dev. All ratios are **illustrative**; none are current quotes. No real order, checkout, payment or WWW shell.

## Remaining P0 gates — DO NOT ENABLE

1. True authenticated Pro **3.2.1** runtime: confirm documented hook signatures, active vendor priorities, selected-currency detector and round-trip conversion; **no untrusted packages or renewed subscription**.
2. Five checkout currencies × actual Woo five zones, true Woo rounding/shipping-tax, selected method and cart coupon, Zone3 `order` and shipping class reconciliation. Confirm cart and checkout actual amounts without live checkout submission.
3. The current published CVE and version-specific vendor shipping bug concerns do not disappear because a backwards-compatible adapter exists. Require separate security review of the **current installed Pro** and mitigation/no-go decision; do not imply legacy plugin is patched.
4. Owner Checkout #142 still Draft/non-mergeable; use minimal reconciler only. Cart Canary #148 manifest overlaps; preserve both.
5. Dev and WWW unchanged. Any real Dev Pro install/transfer requires separate permission. Any main merge requires fresh exact-head authorization; any WWW change/production enablement and final Owner UAT require separate approvals.

**Source-only compatibility hypothesis PASS; real legacy five-currency Woo/Checkout UAT UNVERIFIED; release HOLD.**

References: official [YayCurrency hooks](https://docs.yaycommerce.com/yaycurrency/configurations/developer-zone/available-yaycurrency-hooks); [WordPress.org YayCurrency release history](https://wordpress.org/plugins/yaycurrency/); parent release gate [PR #158](https://github.com/beyourlovercom/beyourlover-web-v3/pull/158).
