# Architecture and module ownership — BYL Website V3

## Objective
Build a coherent new B2C site on Dev, not a sequence of patches on legacy WWW. Existing approved designer/theme work is the baseline; optimize structure, performance and behavior, not start redesigns without approval.

| Component | Owns | Does not own |
| --- | --- | --- |
| BYL V3 Theme | Site layouts, tokens, responsive behavior, page rendering, progressive UI | Payment truth, coupon/shipping authority |
| Andy Core | Generic WordPress modules, foundational APIs, common utilities | B2C-only Woo business logic |
| Andy Commerce | Shipping/promotion policy, Woo export, commerce-specific config and checkout policy interfaces | Entire site design, Woo payment processing itself |
| WooCommerce + gateways | Cart, order lifecycle, address validation, tax/shipping calculations, gateway sessions and payment callbacks | BYL-specific presentation |
| BYL ERP | Fulfillment/payment authority, inventory/logistics/business accounting as defined by ERP contracts | Reimplementation in Website code |

## Stable interaction contracts
- Funnel: Landing Page → Product/PDP → Wishlist/Cart → Checkout → Payment → Thank You → My Account/Order Detail → Tracking → AfterShip → Email and recovery.
- Checkout customer journey is **Cart → Shipping → Payment** across the approved Cart Drawer / Checkout Drawer, with native Woo checkout on its secure URL. Delivery choices belong inside Shipping and review/totals and the final place-order action belong within Payment: **no separate Delivery or Review customer step**. On mobile, use one sticky primary CTA and editable prior-step summaries; any strict no-navigation handoff is a separately tested compatibility gate.
- Avoid duplicate processing hooks when legacy plugins coexist. Do not remove legacy plugins until replacement parity is demonstrated in Dev and Owner-approved.
- Store API, classical Woo checkout, block checkout and provider extensions have different lifecycles; inspect which engine is active before changing hooks.
- Runtime and code truth must both be checked. A merged PR or loaded class does not establish working UX or valid payment.
- A website PR must not silently modify ERP payment/shipment authority or migrate canonical business records.

## Native-first implementation and two independent UAT gates
- Root [AGENTS.md](../../AGENTS.md) and [Native-First & Dual UAT V1](NATIVE-FIRST-DUAL-UAT-V1.md) require every in-scope interactive control to map to its actual WordPress/Woo/Andy authority and preserve native events, keyboard/touch, validation, persistence and fallback before visual-detail closure.
- Complete the working control-level functional gate first, then polish against the Owner-approved design and regress affected native functions. A page closes only after **Functional PASS + Owner Visual PASS**. Green CI, isolated mocks, runtime PHP or a static screenshot never establish both.
- Current Checkout issue [#125](https://github.com/beyourlovercom/beyourlover-web-v3/issues/125) provides a 54-case native contract; any missing Woo country/state selection or gateway availability is **FAIL/BLOCKED**, not a visual exception.

## Scope by workstream
Cart and Checkout can be independently developed/UATed while sharing Andy Commerce policy contracts. Payment Gateway Integration and Post-purchase Experience are separate gates; neither is declared done by finishing Checkout layout.

## BYL Theme OS V1

Owner-approved standalone BYL Theme version and upgrade lifecycle without Shoptimizer, CommerceKit or an additional Child Theme. The module ownership, SemVer, CI and rollout rules are in [THEME-OS-V1.md](THEME-OS-V1.md). Phase 1 keeps Theme bootstrap and all Woo Cart/Checkout hooks unchanged.
