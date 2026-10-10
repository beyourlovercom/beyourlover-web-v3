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
- Checkout is a single URL with Apple-style task stages: Shipping Address → Delivery Method → Payment → Review/Place Order; a single sticky next-step action on phones; completion summaries remain editable.
- Avoid duplicate processing hooks when legacy plugins coexist. Do not remove legacy plugins until replacement parity is demonstrated in Dev and Owner-approved.
- Store API, classical Woo checkout, block checkout and provider extensions have different lifecycles; inspect which engine is active before changing hooks.
- Runtime and code truth must both be checked. A merged PR or loaded class does not establish working UX or valid payment.
- A website PR must not silently modify ERP payment/shipment authority or migrate canonical business records.

## Scope by workstream
Cart and Checkout can be independently developed/UATed while sharing Andy Commerce policy contracts. Payment Gateway Integration and Post-purchase Experience are separate gates; neither is declared done by finishing Checkout layout.

## BYL Theme OS V1

Owner-approved standalone BYL Theme version and upgrade lifecycle without Shoptimizer, CommerceKit or an additional Child Theme. The module ownership, SemVer, CI and rollout rules are in [THEME-OS-V1.md](THEME-OS-V1.md). Phase 1 keeps Theme bootstrap and all Woo Cart/Checkout hooks unchanged.
