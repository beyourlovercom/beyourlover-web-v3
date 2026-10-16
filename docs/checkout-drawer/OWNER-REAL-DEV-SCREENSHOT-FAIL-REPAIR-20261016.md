# Checkout Drawer Option B — Owner real Dev screenshots: UAT FAIL
2026-10-16 · Draft PR #120 · Dev release head 413062ba0cfb4b4196a07c40e8e7db9dc745dcc2

## Evidence and privacy
The Owner supplied genuine Shipping and Payment screenshots from a Dev Woo cart containing products. Do not copy these screenshots to the public GitHub repository: they contain identifying and session information. This note records sanitized findings.

## P0 discrepancies against locked Shipping and Payment Owner PASS visuals
1. Shipping Phone appears AFTER ZIP Code; move ahead of ZIP, retaining the native delivery-only privacy note.
2. Shipping Country follows the name, and optional Apt and Company disclosures appear above required fields. Lock Country → First + Last → Street → optional Apt → City + State → Phone → ZIP → optional Organization. Post-ZIP Tax ID still needs an independent Woo server validation/country rules contract.
3. WooPayments, PayPal and other express wallets render at the beginning of Shipping, although payment decisions belong on Payment. The Dev hook read-only audit found exactly three callbacks on woocommerce_checkout_before_customer_details: WCPay express priority 1, PaymentPlugins WooCommerce PPCP PaymentButtonController priority 10, and wc_get_pay_buttons priority 30. The repair keeps their real server-rendered hooks, but relocates output to the Payment section ONLY on Dev Option B; plain /checkout/ fallback remains unchanged.
4. Payment shows the real Woo no-available-payment-methods message despite express wallet buttons. This is NOT evidence that card gateways have been enabled. Do not fake a method or enable PAY NOW; inspect activation, currency, address eligibility and Woo gateway state separately with Owner-approved read-only access.
5. Native optional order_comments / Additional information appears as a distracting textarea on Payment. Reuse the genuine Woo field in a collapsed Shipping disclosure, opening automatically if populated; never rename or clone it.
6. Payment left summary has an unnecessary Payment not configured row and editable coupon prompt. Keep Cart/Edit, Shipping/Billing details and Woo-computed order totals; coupon editing remains available in Cart/Shipping.

## Repair implementation status
Source changes in the same independent Draft branch: server-side Woo field priorities, defensively scoped six-column CSS grid orders, express-payment hook moved for Dev Drawer only, native optional delivery note moved under Shipping, compact native Woo unavailable-method notice, and sanitized left Payment summary. Source-only and synthetic DOM/browser regression tests must PASS before committing.

## Release and UAT gates
The prior exact-head approval covered PR120 head 413062ba0cfb4b4196a07c40e8e7db9dc745dcc2 ONLY. No new Dev, WWW, GitHub main merge or production transaction changes are authorized for this repair HEAD. New exact-head Dev-only approval plus file hashes, off-webroot backup and rollback required. After any authorized Canary, verify M320/M390/iPhone14/PC1440 real cart screenshots against Owner originals. Woo express buttons must initialize when Shipping progresses to Payment; abort if not. No real order, payment, account, email, DB writes or READY restarts. Country Notices, Tax ID, auto-account/Mail OS, true no-navigation Cart handoff, Commerce #87 E2E and WWW T6 remain HOLD.
