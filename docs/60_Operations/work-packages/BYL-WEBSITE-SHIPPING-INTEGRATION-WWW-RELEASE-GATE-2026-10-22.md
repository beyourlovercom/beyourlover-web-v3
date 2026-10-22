# BYL Website — Shipping Integration / WWW Release Gate V1
**Date:** 2026-10-22 · **Owner:** Checkout B / Shipping + Cart V2
**Status:** Integration source candidate + owner Dev visual PASS; **WWW GO/NO-GO = HOLD**.
**Scope:** GitHub source review and production runbook only. No GitHub main merge, WordPress WWW modification, WWW SSH, real orders, payment, emails, or device restarts.

## 0. Do not misinterpret approved authorization
Owner explicitly approved:
- Global marketing sentence: **Free shipping from $69 (special items excluded)**. The whole sentence links to `/free-shipping/`; Cart, Cart Drawer and Checkout do not stack progress celebrations or geography tables.
- Country-specific Woo shipping rules (not a worldwide $69 rule). The full explanations belong to `/free-shipping/`.
- Exactly **SAKURA**, **BDSM BONDAGE KIT – GREEN**, **BDSM RED BONDAGE KIT** ship for **USD $30 per special shipment/package** anywhere, exempt from ordinary free shipping. Multiple qualifying special SKUs in one package are **not $30/each**.
- **PILLOW** remains **USD $30 per pillow unit** (separate from class-1939 products).
- **Owner PASS** on Dev visual UI, and genuine Dev Woo Cart rate + zone lookups; this is **not** permission to deploy WWW.
- **WWW SSH must never be requested**. A future authorized WWW release must use Owner-approved native WordPress admin / permitted release UI only.

## 1. Verified source graph
| Component | Draft PR | Status |
| --- | --- | --- |
| PILLOW $30/unit shipping package adapter | #150 | Dev-only and separate from #152/#153/#154; previously omitted from that linear stack |
| After-coupon $69 correction | #152 | Dev-only, guarded |
| Restworld ordinary $20/<$69, ordinary free/≥$69; global special $30 | #153 | Dev-only, stacked on #152; depends on #150 |
| One sentence and native five-zone policy | #154 | Dev Owner visual PASS, stacked on #153 |
| Full pre-main dependency assembly and this release gate | independent integration branch / Draft PR | Source-only candidate, do not deploy as-is to WWW |

The **integration candidate** adds exactly the missing PR #150 PHP adapter, bootstrap require, Theme OS manifest entry and PHP Checkout shipping marker, together with its standalone 70-assertion regression. It **does not reintroduce** PR #150's obsolete multi-line PILLOW Checkout JS marketing copy: the Owner-approved PR #154 one-line text supersedes that part of PR #150. This resolves the conflict without touching the verified Woo rate logic.

## 2. Current known Dev state (NOT asserted production WWW state)
| Woo shipping zone | Dev configuration | Special handling |
| --- | --- | --- |
| Zone 0 Rest of World (GB, FR & unmatched countries) | flat_rate #3 base $20; free_shipping #11 min $69 after coupon | $30 separate special package |
| Zone 1 Hong Kong | free_shipping #2 without minimum | dedicated $30 special method (ordinary remains free) |
| Zone 3 selected international | flat_rate #5 base $30; free_shipping #6 min $169 after coupon | $30 separate special package |
| Zone 4 Alaska, Hawaii, etc. | flat_rate #7 base $20; free_shipping #8 min $129 after coupon | $30 separate special package |
| Zone 5 USA and some Asia | flat_rate #10 base $10; free_shipping #9 min $69 after coupon | $30 separate special package |

Actual Woo zone matching—not broad country assumptions—determines each destination. Rate **instance IDs are database-specific**; **NEVER copy Dev Free #11 to WWW or infer that WWW IDs/costs match**.

Read-only earlier WWW *method-list-only* audit showed Zone 0 Free #1 and Flat #3 both enabled, Zone 1 Free #2, Zone 3 Flat #5 / Free #6, Zone 4 Flat #7 / Free #8, Zone 5 Free #9 / Flat #10. These are **method identities only**, not verified production rate amounts or coupon basis. A prior Owner-reported WWW Restworld Flat base **$0.00** once caused a real UK free-shipping issue; treat its *current* setting as requiring fresh native-admin verification, not a permanent database fact.

## 3. Production blockers — NO GO before resolution
1. **Dev-only code cannot charge on WWW:** All 3 shipping adapters from #150/#152/#153 contain strict `dev.beyourlover.com` / staging guards. A blind merge/build/deploy preserves an OFF or absent Woo shipping correction on WWW. Prepare a **separate production-safe, initially OFF, site-scoped release adapter**, independently review and test, then obtain a separate exact-head and WWW enable approval. Do not loosen Dev safety guards in place. Production code must use actual matched Woo rate instance IDs and instance settings; no blind #11.
2. **No standalone Woo setting solves all exceptions:** Native Free Shipping can override shipping-class surcharges; changing Zone0 base from $0 to $20 while leaving class1939 +$30 could cause **$50** (not approved $30). Hong Kong has only Free Shipping: paid exception needs a safe, verified specialized rate. The global three-product exclusion and PILLOW per-item fee require reviewed, explicitly enabled shipping logic, not a universal 'free shipping >$69' setting.
3. **Native WWW settings unknown in detail:** Owner must review `WooCommerce → Settings → Shipping → Shipping zones`, each target zone's specific method settings and shipping-class definitions. Capture **Woo native screenshots/config values** through normal admin only, not WWW SSH. Confirm for each Free Shipping method `requires`, `min_amount`, coupon discount handling / `ignore_discounts`; Flat Rate `cost`, `type`, `no_class_cost`, class costs `class_cost_932`, `class_cost_1874`, `class_cost_1939`; current class assignments. Verify global BYL/Andy Commerce promo settings and BYL49 protection; do not assume Dev matches WWW.
4. **Checkout conflicts/independent owners:** Current open Draft PRs #142 (native Checkout integration; *not mergeable* at last read), #137 and #141 overlap `assets/js/checkout.js`, `assets/css/checkout.css`, `inc/checkout.php`. Cart Canary #148 overlaps Theme OS and Foundation CI. Their Owner UAT must be preserved; perform exact-head manual integration and per-owner review, not blanket merge/cherry-pick.
5. **Native checkout functional gate / FX:** Dev Store API Checkout returns the intentional `403 byl_dev_sandbox_checkout_closed`; do not bypass or claim a full order/Payment PASS. Dev returns USD for GBP/EUR queries and lacks active YayCurrency, so multi-currency settlement and rendered totals remain unverified for WWW. Use a separate safe Owner-approved production read-only cart checkout-render check without submitting a real order.
6. **Dev test coupon housekeeping:** Four short-lived fixtures (IDs 39969–39972) expired; deletion was security-blocked, therefore **not confirmed deleted**. They are not part of the WWW release and must not be copied.

## 4. Owner-native WWW configuration review (read-only; no edits until explicit separate authorization)
1. Open `https://www.beyourlover.com/wp-admin/admin.php?page=wc-settings&tab=shipping` in Owner's existing logged-in Chrome session.
2. View **Shipping zones**, starting Rest of World fallback then zone 1/3/4/5. Screenshot each methods list and the **specific Edit** page of Free Shipping and Flat Rate. Record enabled, requirement mode, amount, after/before coupon setting, Flat base and per-class costs. Do not click Save.
3. Open `WooCommerce → Settings → Shipping → Classes`. Confirm the 3 special products and PILLOW classification and any other products sharing their classes. Confirm shipping destination priorities and country/state mappings; no assumptions from zone title.
4. Review existing BYL/Andy shipping-promotion controls / BYL49; confirm discount-after, coupon rules, and whether any old $49 logic still unlocks shipping in Zone0.
5. Record each **pre-change** production method setting in a small release ledger and label all unverified cells `UNKNOWN`. This ledger is the only valid basis for a manual production change and rollback.

## 5. Proposed WWW sequence AFTER future separate Owner authorization
**Gate A — production-safe code:** Independently reviewed Woo-native source release, Dev-disabled guards left intact, separate WWW feature flag default OFF; full isolated PHP and Woo rate regressions for GBP/EUR UI, tax, mixed carts, PILLOW ×2, 3 special SKUs, UK/FR/HK/CA/AK/HI/MK, BYL49 and after-coupon $68.99/$69/$70. Exact-head CI PASS. No main merge before the explicitly requested permission.
**Gate B — native settings:** Owner via approved WordPress admin interface adjusts exact matching WWW methods/conditions, not a guessed instance. For Restworld ordinary goal, Flat **$20** below after-coupon eligible **$69**; native Woo Free Shipping requires **minimum order amount 69** with discount taken into account, if compatible with currently enabled promotions and class exceptions. Other region tiers unchanged unless separately verified/approved. Do not blindly change base/class surcharges; production adapter must prevent extra $30 stacking/free override.
**Gate C — release UAT:** Revalidate WWW visible Cart/Drawer/Checkout one-line linked copy and policy page, 390px mobile and desktop, first-party destination lookup, real available shipping rates and selected amounts in read-only/non-order session. Confirm no duplicate free shipping promises; no empty/invalid address loopholes, stale selected rate or coupon-state drift. No real submit/payment.
**Gate D — rollback + closure:** Keep original theme/UI artifact and original Woo settings export/screenshots. If fee/Checkout regression occurs, **disable the new shipping feature first**, restore prechange theme and each modified native shipping instance value from the ledger; confirm cart renders and charge recomputation; then record incident. Do not restart READY devices. Owner explicitly signs final GO/NO-GO and separate PR exact-head main merge + WWW publish permissions.

## 6. Exact Dev evidence and scope
- Owner PASS on 2026-10-22 for Cart/Drawer/Checkout one-line linked sentence and policy page; PR #154 HEAD `88b4dc1d97a4ea158a85b8c03a2dfa1b199ede67`, Foundation CI #39709914407 SUCCESS.
- Dev nine-file staged UI rollback backup: `/home/u247343654/.byl-dev-shipping-copy-v1.7xko0ZW9`.
- Dev actual shipping guest-cart tests: 6/6 geographic country policy lookup, 4/4 post-copy shipping smoke, extended global exceptional shipping across all 5 zones, multiple specials one package $30, PILLOW 2× = $60; earlier transient DB HTTP500 on one FR request recovered with read-only retry; no server restart.
- Dev native Checkout 403 intentional safety; no GBP/EUR actual conversion validation. Do not upgrade these to PASS.
- This document does not assert any production-level edits, availability or account-specific fees.

**Next action:** Present the missing production native-admin instance settings (read-only) and independent production-safe adapter review. Until both are verified, **WWW = HOLD** and **main merge = HOLD**.
