# Release path: Dev integration → full-site WWW cutover

## Critical architectural decision
**Dev is the new website.** Old `www.beyourlover.com` remains customer-facing and unmodified during V3 development. Do not install each new Dev feature into legacy WWW.

## Pre-cutover exit gate
- All critical e-commerce flows pass with real Dev evidence: LP/PDP/Wishlist/Cart/Apple Checkout/Payment/Thank You/Account/Tracking/AfterShip/Emails/recovery.
- Security, identity/affiliate, plugin parity, performance, mobile responsiveness and SEO are UAT accepted.
- Payment/provider cards/wallets are coherent; real transaction Canary is separately planned/approved.
- Confirm affiliate FX migration/dual-calculation parity and retirement plan for legacy multi-currency capability as defined by the applicable project contract.
- Inventory, fulfillment, ERP payment status and order export/sync parity are reconciled.
- Version every plugin/theme and deploy from reproducible Git commits. Record hashes, dependency versions, DB schemas and rollback instructions.

## Native-First Dual UAT release prerequisite
- Before **page closure**, record approved design baseline, **Functional LIVE DEV PASS**, post-polish functional regressions and **Owner Visual PASS** as distinct evidence under [Native-First Development & Dual UAT V1](NATIVE-FIRST-DUAL-UAT-V1.md).
- For an interactive Checkout control, current [54-case Issue #125](https://github.com/beyourlovercom/beyourlover-web-v3/issues/125) governs required native clicks, field/rate/tax/amount state, errors and mobile/desktop; a mocked or static result cannot satisfy an untested live behavior.
- Payment/order steps blocked by missing sandbox or authorization remain **BLOCKED**, not PASS. No live payment or order creation merely to pass a screenshot test; use separately authorized payment/G5 Canary and end-to-end gates.
- A CSS or Drawer UI release requires retesting affected native controls (not only page screenshots), including Woo SelectWoo Country/State and provider-owned popups when relevant. This policy does **not** grant any Dev/WWW deployment or merge approval.

## WWW cutover gate (Owner explicit approval required)
1. Identify actual WWW hosting and deployment access; do not infer it from the Dev SSH alias.
2. Back up current WWW files, database, DNS/Cloudflare/configuration, secrets references and restore point; verify restore procedure.
3. Freeze / account for live-order changes and map final delta synchronization for orders, users, content, coupons and inventory. Avoid dropping new live customers/orders.
4. Validate staging domain-to-WWW URLs, Woo payment webhook/callbacks, email provider, HTTPS, cookies, cache/CDN/R2, analytics and ERP integrations.
5. Preserve SEO: 301 redirects, canonical, robots, hreflang/sitemaps as applicable, crawl gates and Search Console.
6. Schedule an observed cutover, smoke test genuine checkout/notifications and gateway callbacks, monitor errors/metrics, retain rollback window.
7. Roll back based on stated triggers and reconcile orders/events safely; never quietly revert irreversible business events.

## Disallowed shortcuts
PR merged ≠ installed on Dev ≠ Owner UAT PASS ≠ WWW permission. Do not flip DNS, change WWW plugins or perform live payment/email operations based only on a general approval to continue development.
