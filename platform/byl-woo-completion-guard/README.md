# BYL Woo Completion Guard — P0

This is a **standalone WooCommerce plugin** under the BYL web repository. Install only on **Dev** first. The Owner installs WordPress plugins on **WWW manually** after acceptance.

## Scope
- Removes the "Mark completed" bulk option for legacy orders and HPOS screens; hides the quick-complete icon.
- Blocks forged non-admin bulk completion submissions.
- Rejects attempts to set a physical order to completed/completed-delivered **without** AfterShip carrier and a plausible tracking number, whether via Woo admin, API, or normal code paths using WC_Order::save().
- Ordinary WordPress non-administrator UI cannot mark orders completed directly, even with tracking.
- Existing completed orders are untouched; saving other properties without a status transition is unaffected.
- Only virtual-product orders are exempt from shipment-proof requirements.
- Audit rejection entries go to WooCommerce Logs > byl-woo-completion-guard; no PII/tracking values logged.

## Important boundaries
This plugin does **not** prove that every split shipment is shipped: the ERP's aggregate fulfillment logic owns that check. Historical completion evidence must be handled in P1. It doesn't correct old orders or send emails.

## Dev UAT
1. HPOS and legacy list actions: no bulk Complete or quick Complete shortcut.
2. Non-admin direct status change to Completed with no tracking is refused.
3. Admin API attempt without tracking is refused; processing remains unchanged.
4. Shipped order with AfterShip tracking can be completed by the authorized ERP API.
5. Existing completed order can be edited without re-triggering status checks.
6. Virtual-only order and refunded/cancelled changes remain unaffected.
7. Rollback: deactivate plugin. No schema changes or data backfill.
