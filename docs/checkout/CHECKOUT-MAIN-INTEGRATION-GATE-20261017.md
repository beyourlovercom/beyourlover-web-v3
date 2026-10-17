# Checkout Main Integration — selective Owner-accepted native flow

Date: 2026-10-17
Status: **main-based integration Draft PR; no GitHub main merge and no Dev/WWW deployment by this branch**
Branch: `integration/checkout-standard-owner-uat-main-20261017`
Main at branch creation: `47ee78943bd7e10e7bf3b1a64fea4d83e4125791`
Source accepted visual stack: PR #141 HEAD `35d817bcc59b9a9c855ff9142078fd61cbf8065f` (based on PR #140 and upstream canonical native Checkout work).

## Why this integration branch exists
Prior PR #140 targets a **Newsletter feature branch**, not main; PR #141 targets the PR #140 feature branch. Merging either alone would not put accepted Checkout on main. Retargeting the full feature chain blindly could roll back **five commits of the main Cart empty-state module** and inadvertently mix unrelated Cart changes.

This main integration is a **selective, reviewable candidate**:
- Started directly at canonical main SHA `47ee789...` (which includes main Cart empty-state PR #133).
- Imported only Checkout-specific source, source checks and evidence from the latest Owner-accepted source stack. No old Checkout Drawer runtime/PR restored.
- Carried over the Checkout component's `theme-os.json` file entries **only**, preserving main's entire `cart` module and all other existing module metadata and ownership.
- Preserved byte-identical copies of **all five** main Cart empty-state files: `scripts/test_cart_empty_page_parity_dev.js`, `theme/beyourlover-v3/assets/css/cart-empty-parity.css`, `theme/beyourlover-v3/inc/cart-drawer.php`, `theme/beyourlover-v3/inc/notification-cart-empty.php`, `theme/beyourlover-v3/inc/notification-cart.php`.
- Source provenance of 29 Checkout-specific files, plus selective Theme OS manifest, is the real PR #141 branch exact HEAD above. See the actual PR file diff before approving any merge.

## Function acceptance carried forward (no repeat claims)
- Standalone WooCommerce `/checkout/` with **Cart → Shipping → Payment** progress and genuine Woo checkout field, price, tax, shipping and payment gateway ownership. Cart Drawer remains; Checkout Drawer retired.
- Owner-confirmed Shipping mobile visual PASS in PR #141 with real 390px and 320px screenshot evidence. Express price/ETA and sample total are **NOT hard-coded** to match the image; Woo real shipping rates remain authoritative.
- Country-first shipping field ordering, scoped SelectWoo theme, native Country/Province selection, Mexico RFC Tax ID, native free-shipping card tied to Woo eligibility.
- Genuine independent billing `byl_invoice_*` and optional account address saving. Guest cannot save account address; logged-in users must explicitly opt in; no unexpected Woo customer-account auto-save. Order shipping/billing separated in native Woo CRUD. Saved shipping address read back from persisted `WC_Customer($id)`, **not transient session customer store**.
- Newsletter Checkout JS fail-closed while Dev's active/newsletter-outbound flags are OFF. This branch does not enable actual mailing, subscriptions, checkout payments, orders or customer updates.

## Integration tests completed locally before creating Draft PR
- 31 discrete source/functional commands PASS; test log at US Mac mini ephemeral `/tmp/byl-checkout-main-integration-tests-20261017.log`.
- Includes Theme OS schema/unit tests, native route script ordering, real address Woo stubs, MX RFC, newsletter OFF/source/events, 7-case G5 3DS/duplicate-submit lock, all targeted Checkout visual/country/field ordering contracts, native Checkout secure shell/handoff, and Cart V2/Drawer progress/quantity/Store API mocks, PHP syntax, JS syntax.
- One newly discovered stale source-only test expected 2 `alignShippingAddressFields()` call sites, while previously approved pre-AJAX first-paint fix legitimately introduced 2 more observer/load call sites. The integration branch updates **only this test's assertion** to check all 4 call sites and observer cleanup. No Checkout runtime change introduced for this test.
- **Hosted Foundation CI and full main-based integration still required**. Do not mark Github CI PASS until the actual new integration PR run concludes SUCCESS. The previous #140 and #141 CI SUCCESS applies to their old stacked bases only.

## Owner merge/WWW gates
1. Read-only inspect integration PR file list: no Cart sources changed/deleted, no Checkout Drawer reintroduction, no implicit Newsletter or Woo configuration changes; verify exact main base SHA and exact integration head.
2. Hosted main-based Foundation CI SUCCESS, plus reviewed changes and privacy/security gateways.
3. **Require separate explicit Owner authorization for exact-head GitHub main merge**. This Draft PR creation is not that approval.
4. **Require separate Dev/WWW release authorization**. Existing Dev CSS/function remains already accepted and deployed through the previous branches; this integration branch does not redeploy or enable business writes.
5. Payment gateway availability, country selector occasional ~2s lag Issue #132, and carrier ETA are separate factual UAT gates. Do not treat as resolved through source integration.
