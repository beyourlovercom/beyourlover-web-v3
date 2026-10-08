# Commerce workflow contract — Cart, Checkout, Payment

## Cart and Checkout
- Cart and Checkout are separate UI/PR/UAT workstreams; shared behavior belongs in Andy Commerce policy, not duplicated theme JavaScript.
- Apple-style progressive Checkout on one URL: **Shipping Address → Delivery Method → Payment → Review**.
- Completed stages show compact summaries and Edit; desktop uses bounded two-column layout; mobile shows one persistent bottom primary CTA respecting safe areas.
- Shipping-only: no pickup option. Shipping phone required for carrier use; avoid a duplicated billing-phone field. Validate correctly on both client and Woo server.
- One promo code per order. The UI and backend must agree; prevent misleading multiple-code stacking or stale totals.
- Woo shipping zones and actual methods determine displayed options and totals. Andy Commerce supplies promotion/free-shipping policy; do not hardcode thresholds into theme.
- Apple Pay/Google Pay only on Cart/Checkout when eligible, never automatically on Product pages.

## Payments
- Normal Checkout presents only one ordinary Card option; providers are transaction rails, not competing duplicate UI.
- Preserve reviewed payment authority / stale-pending / failure-recovery contracts. Do not delete Stripe based only on WooPayments cost; observe reliability and failover evidence.
- No payment gateway present means **Place Order must not appear successful**. Never fake card forms, wallet availability, settlement, webhook or order-confirmation success.
- Real payment Canary, webhook routing, gateway secret installation and live transactions need an explicit guarded test plan and applicable Owner approval.

## After purchase
Thank You, Order Detail, Tracking/AfterShip, transactional email and failed-payment recovery are separate end-to-end acceptance gates. Reuse existing Woo/ERP capabilities; respect ERP's fulfillment/payment authority.

## Acceptance
Demonstrate guest and logged-in flows, representative countries/zone thresholds, discounts, address editing, mobile/desktop, empty/failed/retry states, wallet eligibility and order-status correctness before marking full commerce closure.
