# BYL Marketing OS / BOGO & Promotions V1 — Andy Commerce integration contract
Date: 2026-10-12
Status: **DESIGN / OWNER POLICY DIRECTION**, NOT runtime authorization. No discount, free gift, or free-shipping configuration is changed by this document.

## 0. Evidence and existing owners
- Observed live Dev WordPress plugin inventory (read-only): `andy-commerce 1.0.1` active, `yby-core 1.9.0` active, `woocommerce 11.2.1` active; first-party `byl-dev-safety` MU gate exists. No Woo BOGO engine/plugin was visible in the Andy Commerce PHP file inventory.
- Live Andy Commerce file inventory includes `admin/class-andy-commerce-admin.php`, `includes/class-andy-commerce-shipping-promotion-policy.php`, and `includes/class-andy-commerce-shipping-promotion-runtime.php`. The latter registers Woo shipping availability and coupon-individual-use hooks. The BYL shipping option uses **`pre_discount_merchandise_subtotal`**; the admin view says this site is in **read-only migration mode** for legacy BYL49 configuration pending separate authorization.
- GitHub `main` of `beyourlovercom/beyourlover-web-v3` contains legacy/transition contracts `platform/byl-platform/includes/class-byl-shipping-promotion-policy.php`, `...runtime.php`, `docs/C3-CHECKOUT-SHIPPING-PROMOTION-IMPLEMENTATION-CONTRACT.md`, and `docs/agent/CART-FREE-SHIPPING-NATIVE-PARITY-2026-10-09.md`, also using **pre-discount** subtotal. *These are current legacy contracts, not justification to silently replace them.*
- GitHub `docs/agent/COMMERCE.md` already assigns shipping/promotion business authority to **Andy Commerce** and UI to Theme; Woo owns cart/tax/discount/shipping/order truth. Cart Drawer Draft PR #63 uses Woo Store API totals and has Marketing Slots OFF / Free Gift OFF.
- Andy Commerce source of truth must be established in an approved repository/monorepo path before committing runtime/admin implementation. Do not create an unrelated new WordPress plugin, activate unknown modules, patch the live plugin by SSH, or put discount calculation in the Cart Theme. This separate contract branch is NOT a commerce release.

## 1. Owner policy — common promotion threshold money basis
**Default eligibility basis:** `post_discount_merchandise_subtotal_excluding_tax_and_shipping`.
Definition in Woo cart context: `SUM(eligible cart line_total)` (Woo `line_total` after applied discounts, excluding line tax), in **integer currency minor units** after deterministic Woo rounding. Do not derive from order grand total. Excludes shipping, all taxes, fees, paid gift cards/store credit where not merchandise discounts, and any auto-inserted $0 gift from its own qualifying spend.
- Example threshold $100: merchandise $120 minus a $20 applicable coupon → eligibility $100 (qualifies); minus $30 → eligibility $90 (remaining $10).
- Coupon + auto BOGO/bundle spend discounts must affect the qualifying merchandise basis before threshold evaluation; no double discounts, discount loops, or taking a “free” line into its own threshold.
- Every rule states local campaign currency, Woo/FX conversion policy and zone; eligibility unknown if a customer destination/currency is not resolved, not fabricated.
- At Checkout recompute from the **same server-side policy and Woo normalized cart**; quantities, promotions, coupon, shipping zone, product exclusions or out-of-stock changes require recalculation. No UI-only eligibility.

## 2. P0 LEGACY POLICY INCOMPATIBILITY — block rollout
Existing legacy BYL49/Shipping policy `ELIGIBILITY_BASIS = pre_discount_merchandise_subtotal`; some configurations respect tax-inclusive display and do not subtract coupon discounts. The Owner-approved new default above is **incompatible** with this contract.
**Do not silently alter current BYL49 or Woo free-shipping methods.** New progress bars must remain OFF unless the actual shipping policy and presentation both share one versioned, active eligibility evaluator.
Proposed migration gate:
1. Read-only determine effective runtime owner on Dev (Andy Commerce or remaining legacy hook; never dual hook owners), persisted option, existing Woo shipping-zone thresholds and customer tax-display mode.
2. Introduce a versioned `eligibility_basis_v2` on the approved **Andy Commerce** code path; explicit per-site opt-in / migration plan; do not rewrite old options in place without backed-up rollback and Owner approval.
3. Run **isolated side-by-side evaluator** for representative addresses/zones, pre/post coupon, inclusive/exclusive tax, currencies, free shipping method vs selected rate. Report divergences; do NOT show optimistic 'unlocked' UI before Woo confirms the available and selected rate.
4. Decide whether existing BYL49 code/one-coupon regime remains or is migrated. Legacy behavior stays until separate authorization for settings write, migration, Dev Canary and eventually WWW.
5. D4 Cart progress is a read-only representation of the **effective active policy**, not an independent $49/$69 threshold hardcode.

## 3. Admin IA: WordPress Marketing → BOGO & Promotions (Andy Commerce-owned)
One administration surface, **not** a second promotion engine:
- Overview / campaigns list: Status (Draft, Scheduled, Paused, Ended), category, window/time zone, currency, zone, priority, use limits and audit metadata.
- **BOGO**: Buy X get Y (e.g. 1+1, buy two get cheapest 50%, free item selectable only if separate Gift policy permits). Qualifying SKUs/categories, target SKUs/categories, quantity caps, cheapest-item tie breaker, discount allocation, min spend, exclusions.
- **Bundles**: fixed set price / % off selected bundle, required component quantities, out-of-stock policy, variable SKU selection and bundle split-return implications.
- **Spend & Save**: `spend X save Y`, percent/fixed cart or line amount, tiers, per-zone/currency and coupon compatibility.
- **Free Gift**: eligibility + inventory + fulfillment + gift variant + removal/returns + tax/stock treatment; real giveaway remains OFF until specifically approved.
- **Free Shipping**: reference/edit through existing Andy Commerce Shipping policy only; Woo zone/method authority and old BYL49 migration gate apply.
- Preview / Sandbox: synthetic non-order cart evaluator showing input line prices/coupons and expected awards without cart/order/payment writes.
- Permissions: `manage_woocommerce` for editing; separated explicit Owner Publish permission/approval for activation and any WWW configuration. CSRF nonces, capability checks, sanitized schema, audit trail and deterministic rollback required.
- Save/edit campaign must NOT auto-publish. Campaign runtime defaults OFF on site-level and per-campaign gates; admin capability alone does not activate WWW campaigns.

## 4. One engine & conflict/stacking policy
- **Andy Commerce = only server-side promotion rules owner**, WooCommerce = cart, coupons, price/tax/shipping/order and authoritative calculation; Theme = display only; Andy Core = shared generic infrastructure; ERP Marketing = optional campaign distribution/analytics, **never** a second on-site cart discount calculator.
- Existing Andy Commerce shipping adapter hooks include `woocommerce_shipping_free_shipping_is_available`, `woocommerce_coupon_get_individual_use`, `woocommerce_apply_with_individual_use_coupon`, and `woocommerce_cart_loaded_from_session`; the historical one-promo-code restriction MUST be regression tested.
- **Default: one manually entered promo code**. Automatic campaigns do **not** consume that code slot. They are **non-stackable by default** with another auto offer or coupon unless an explicit compatibility matrix authorizes the combination. Priority and 'best permissible benefit' selection must be deterministic and auditable. A 'one code' rule does not justify disabling all automatic promotions.
- One qualifying source line cannot be discounted twice; gifts never count themselves toward eligibility; shipping/gift/BOGO eligibility cannot form recursive loops.
- Existing third-party discount/gift/bundle plugins require runtime hook inventory and explicit allowlisting; no duplicates, no automatic coexistence assumption.
- Order/ERP/analytics should receive one promotion ID/version and stable discount accounting (no invented order lines) only after future separately authorized transactions/UAT.

## 5. Frontend progress / marketing presentation
- Free-shipping and gift progress UI share one compact reusable component with values from a **read-only, namespaced Woo Store API extension** emitted by the **active** promotion engine: `active`, `eligible`, `basis_minor`, `threshold_minor`, `remaining_minor`, `progress_fraction`, `currency`, `zone`, `state`, and `source_policy_version`. Always verify actual Woo chosen shipping rate before displaying “applied.”
- Two visible bars max in Cart; within natural scroll content, not pinned over footer CTA. Campaign/bundle messages use Cart Marketing Slots V1 only when explicitly active. No banner/ad or reward promise today; slots remain **OFF** and height zero.
- Gift bars: **OFF until Gift SKU/variant, threshold, inventory, region, coupon stacking, consent/selection, loss-of-eligibility removal and returns/refunds are approved**.
- Message feedback uses the independent Notification UX V1; Cart does not register competing toast handlers.
- `/cart/`, Cart Drawer and Checkout must have matching status/amounts in a single Woo session. UI never calls Checkout POST as part of this integration.

## 6. Delivery roadmap and gates
- **M0** Existing Andy Commerce v1.0.1 vs legacy BYL Platform vs Woo: exact hook / option / DB table / policy-readonly audit; source repo/owner confirmed. No writes.
- **M1** Admin UX + campaign schema / typed rules / version migration contract (BOGO, Bundles, Spend & Save, Gift, Shipping). Owner design UAT, no campaign enablement.
- **M2** Pure/offline promotion calculation evaluator with post-discount ex-tax merchandise basis. Tests for 0/negative/rounding, zones, currencies, free/variable items, coupons, quantity/coupon changes, competing offers, gift loops, refunds.
- **M3** Disabled Andy Commerce admin skeleton + save draft/sandbox in *isolated Dev*, security and audit UAT; separate DB migration gate, if necessary.
- **M4** Shipping basis reconciliation/migration Dev-only gated side-by-side Canary. No live policy switch without explicit Owner approval.
- **M5** Read-only Store API progress adapter for Cart and Checkout. No double policy, no marketing ads or gift runtime.
- **M6** Bounded promotional Canary with synthetic users/cart only after exact source SHA + CI + backup + isolation and separate authorization. No payments/orders/WWW.
- **M7** Owner mobile/desktop UAT across promotions and `/cart/`, Drawer and Checkout, then GitHub merge and WWW gates separately.
- **M8** Production separate code deploy, config activation and rollback approval. Campaign default OFF until Owner publishes.

## 7. Current authorization
Owner approved the **default rule basis** and creation of a Marketing BOGO management module in design/roadmap. This is **not** authorization to overwrite existing BYL49, activate real BOGO/Gift/Free Shipping promotions, create/modify orders, touch payment rails, deploy WWW, install plugins, or change DB. Stop at the appropriate code/config/Owner gate.
