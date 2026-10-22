# WWW shipping production candidate — isolated R4 design review

**Status: SOURCE / TEST ONLY. NOT DEPLOYABLE YET. NO WWW GO.**

## Intent
Owner-approved country-specific shipping: qualifying regular merchandise after coupon; Zone0 ordinary $20 under $69, free from $69; Zones5/4/3 use their own minimums and rates; HK ordinary free with no minimum. Three approved special SKUs #9145/#9153/#32251 cost $30 **per exceptional shipping package**, including Hong Kong. PILLOW parent #38047 costs $30 **per unit**. Cart/Checkout marketing copy remains only “Free shipping from $69 (special items excluded)” linked to /free-shipping/.

## Source candidate
`scripts/release-candidates/www-shipping-production-candidate-v1.php` on separate Draft branch, based on integration #155. This file is **not included by functions.php**, **not referenced by Theme OS**, and has **no top-level hook registration**. It therefore does not affect Dev or WWW. The test script `scripts/test_www_shipping_production_candidate_off_v1.php` runs without Woo/WordPress/network: **95 mock assertions PASS** covering default OFF, zone settings digest, five-zone classification, special $30, two pillows $60, mixed split, after-coupon $68.99 vs $69, BYL49 preservation, missing methods, unexpected Free methods, USD/EUR runtime guard, site/env guard.

## Distinct gates
1. **Site gate:** exact `www.beyourlover.com`, WordPress production environment, explicit separate release constant plus WP option. These do not exist in the source candidate automatically.
2. **Method contract:** each approved zone's specific real enabled Woo method instance IDs, settings digests and threshold/flat cost, assembled from a fresh native-admin read-only inspection. No speculative Woo instance ID is valid.
3. **Currency gate:** prototype supports USD only. Live YayCurrency behavior cannot be presumed from base store currency. GBP/EUR and tax inclusion require independent verification; until then **NO GO**.
4. **Shipping tax and exclusions:** tax must recalculate against real Woo rates; empty/unknown destination and class collision must refuse unsupported rates. Test on actual Woo runtime in isolated Dev with a production-mode mock, without disabling checkout safety. BYL49 remains protected.
5. **Integration owners:** Draft Checkout #142, #137, #141 and Cart Canary #148 overlap shared runtime files. Production adapter must be integrated with their exact reviewed heads and verified for only one shipping-package splitter; never deploy a second splitter beside Dev-only ones.
6. **Release control:** a future separately authorized release must stage an initially OFF module and an independent Owner-approved boot. This branch does **not** authorize either stage, activation, settings changes, GitHub main merges, Stable publication or WWW deployment.

## Required WWW facts before planning a write
Via existing Owner WordPress admin browser only (never WWW SSH), inspect actual zone IDs/method instances, Free Shipping requires/min_amount/ignore_discounts, Flat Rate cost/class_cost/no_class_cost/type, shipping class assignments, priority/countries/states, BYL49/Andy Commerce policies, currency switch, and pre-change snapshots. A previous methods list showed Zone0 Free #1/Flat #3, but not settings cost/threshold, so **do not use that list as a source for a production activation contract**.

## Operational NO-GO
- A CI PASS proves mock source behavior, not production fee correctness or native checkout validation.
- A production HTTP GET without Owner Chrome session cannot prove admin settings or authorization. Do not infer values from Dev.
- Dev Checkout remains deliberately 403 for Store API Checkout. Do not bypass to claim payment success.
- WWW must remain completely unchanged until Owner explicitly authorizes a separate reviewed release with exact-head, actual native settings, final rollback steps and quality gates.

## Safe rollback proposal for future authorized release
First disable the new WWW-only feature flag and confirm its actual state in native administration. Restore only shipping methods explicitly changed according to the Owner's pre-change ledger and original theme artifact. Verify normal and exceptional selected Woo rates without order creation. Never restart a READY machine.
