# WWW shipping production candidate — isolated R4 design review

**Status: SOURCE / TEST ONLY. NOT DEPLOYABLE YET. NO WWW GO.**

## Intent
Owner-approved country-specific shipping: qualifying regular merchandise after coupon; Zone0 ordinary $20 under $69, free from $69; Zones5/4/3 use their own minimums and rates; HK ordinary free with no minimum. Three approved special SKUs #9145/#9153/#32251 cost $30 **per exceptional shipping package**, including Hong Kong. PILLOW parent #38047 costs $30 **per unit**. Cart/Checkout marketing copy remains only “Free shipping from $69 (special items excluded)” linked to /free-shipping/.

## Source candidate
`scripts/release-candidates/www-shipping-production-candidate-v1.php` on separate Draft branch, based on integration #155. This file is **not included by functions.php**, **not referenced by Theme OS**, and has **no top-level hook registration**. It therefore does not affect Dev or WWW. The test script `scripts/test_www_shipping_production_candidate_off_v1.php` runs without Woo/WordPress/network: **136 mock assertions PASS** covering default OFF, zone settings digest, five-zone classification, special $30, two pillows $60, mixed split, after-coupon $68.99 vs $69, BYL49 preservation, missing methods, unexpected Free methods, USD/EUR runtime guard, site/env guard.

## R3 native WWW settings now verified (READ ONLY; 2026-10-22)

See `BYL-WWW-SHIPPING-WPADMIN-READONLY-FACTS-2026-10-22.md` in this folder, captured from the Owner's already logged-in China Mac Chrome via read-only WordPress admin forms. **No WWW SSH and NO Save.**

Actual enabled Woo instance IDs now verified:
- Rest of World Zone0 `free_shipping:1` requires **`either` (minimum OR valid free-shipping coupon)**, minimum **$69 after coupons**, `flat_rate:3` base **$20**, special class1939 addend **$30**, type `class`.
- Hong Kong Zone1 `free_shipping:2` no minimum, no Flat.
- Zone3 `free_shipping:6` min **$169 after coupons**, Flat#5 base30, special class addend20, type `order`.
- Zone4 `free_shipping:8` min **$129 after coupons**, Flat#7 base20, special addend10, type `class`.
- Zone5 `free_shipping:9` min **$69 after coupons**, Flat#10 base10, special addend20, type `class`.

Shipping class1939 lists **exactly four parent products in WWW**: PILLOW #38047, SAKURA #32251, BDSM RED #9153, GREEN #9145; the current code explicitly verifies that complete class membership rather than trusting only product IDs. Woo global shipping settings: `woocommerce_shipping_hide_rates_when_free=no`, `woocommerce_shipping_debug_mode=no` (shipping session caching active).

YayCurrency WWW is active for **USD/EUR/GBP/AUD/CAD**, multi-currency checkout ON, auto-country conversion ON (UK GBP, AU AUD, CA CAD), automatic FX rates ON. Candidate's old “Woo base currency === USD” test was insufficient; it now requires an **independently reviewed effective-checkout-currency resolver**, refuses non-USD or unreviewed FX, and remains unconnected. Therefore **multi-currency WWW launch is still NO-GO**; source-only USD mock is not user-visible GBP/EUR/AUD/CAD pricing proof.

Native Woo coupon search for `BYL49` returned **No coupons found**, but this does not rule out an Andy Commerce shipping-promotion or other special rule. **Resolved at source/mock level (R4-B, not WWW UAT):** The candidate now explicitly accepts the actual WWW Zone0 `either` requirement, checks the discounted USD $69 threshold for regular items, and preserves **only a valid Woo native free-shipping coupon** for under-threshold regular items. Literal `BYL49` without verified native free-shipping entitlement never bypasses the rate. It does not waive three specials or PILLOW even when regular items are free. This retains Woo settings unmodified. **Remaining risk:** independent Andy Commerce BYL49/promotion effects at WWW still require end-to-end compatibility validation before activation; do not infer their state from a Woo coupon search.

Woo shipping-rate quotes are cached by package hash. The candidate now injects a SHA256 marker of shipping methods/options/zone locations and checkout currency into an armed package to invalidate stale session Free Shipping after changes. Offline tests confirm marker difference but **real Woo 11.x shipping-session UAT is still necessary**. Background source reference: [Woo WC_Shipping implementation](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/includes/class-wc-shipping.php).

An independent source review identified two additional guards now added: class1939 product list must stay exactly the verified four IDs, and active Flat Rate tax status must stay taxable. Unit tests include a positive tax array propagation case; **actual production tax rate calculation remains unverified**.

## Distinct gates
1. **Site gate:** exact `www.beyourlover.com`, WordPress production environment, explicit separate release constant plus WP option. These do not exist in the source candidate automatically.
2. **Method contract:** each approved zone's specific real enabled Woo method instance IDs, settings digests and threshold/flat cost, assembled from a fresh native-admin read-only inspection. No speculative Woo instance ID is valid.
3. **Currency gate:** prototype supports USD checkout currency only, with an OFF-by-default bridge contract. Actual YayCurrency permits EUR/GBP/AUD/CAD checkout. Its vendor changelog documents shipping-class conversion fixes (3.3.3) and a shipping **calculation type `order`** bug fix (3.3.4); WWW Zone3 currently has Flat type `order`. Verify installed WWW plugin version, exact exchange-rate conversion, shipping tax and rate rendering on all five currencies in an isolated compatible test before turning on. Official changelog: https://wordpress.org/plugins/yaycurrency/ . **NO GO** before the separate FX tests and Owner approval.
4. **Shipping tax and exclusions:** tax must recalculate against real Woo rates; empty/unknown destination and class collision must refuse unsupported rates. Test on actual Woo runtime in isolated Dev with a production-mode mock, without disabling checkout safety. Source/mock test confirms only Woo-validated free coupons preserve Zone0 regular-item waiver, never special/pillow.
5. **Checkout owner overlap (P1):** PR #142 currently has mergeable=false and modifies the exact checkout.js, checkout.css, inc/checkout.php and Theme OS files used here. In current `inc/checkout.php`, `woocommerce_review_order_after_shipping` adds a display-only *locked Free Shipping* row for packages without eligible real free rates. The Dev-only PILLOW check does not recognize future WWW `byl_www_ship_kind` markers, so **special/PILLOW WWW packages risk misleading locked-free cards**. Future separately-authorized Checkout integration must skip the ghost-card helper for WWW packages of kind `special` or `pillow`, while leaving actual Woo rates/totals untouched. Reconcile with the Owner-PASS single-line copy before any merge.
6. **Integration owners:** Draft Checkout #142, #137, #141 and Cart Canary #148 overlap shared runtime files. Production adapter must be integrated with their exact reviewed heads and verified for only one shipping-package splitter; never deploy a second splitter beside Dev-only ones.
7. **Release control:** a future separately authorized release must stage an initially OFF module and an independent Owner-approved boot. This branch does **not** authorize either stage, activation, settings changes, GitHub main merges, Stable publication or WWW deployment.

## Required WWW facts before planning a write
Via existing Owner WordPress admin browser only (never WWW SSH), inspect actual zone IDs/method instances, Free Shipping requires/min_amount/ignore_discounts, Flat Rate cost/class_cost/no_class_cost/type, shipping class assignments, priority/countries/states, BYL49/Andy Commerce policies, currency switch, and pre-change snapshots. A previous methods list showed Zone0 Free #1/Flat #3, but not settings cost/threshold, so **do not use that list as a source for a production activation contract**.

## Operational NO-GO
- A CI PASS proves mock source behavior, not production fee correctness or native checkout validation.
- A production HTTP GET without Owner Chrome session cannot prove admin settings or authorization. Do not infer values from Dev.
- Dev Checkout remains deliberately 403 for Store API Checkout. Do not bypass to claim payment success.
- WWW must remain completely unchanged until Owner explicitly authorizes a separate reviewed release with exact-head, actual native settings, final rollback steps and quality gates.

## Safe rollback proposal for future authorized release
First disable the new WWW-only feature flag and confirm its actual state in native administration. Restore only shipping methods explicitly changed according to the Owner's pre-change ledger and original theme artifact. Verify normal and exceptional selected Woo rates without order creation. Never restart a READY machine.
