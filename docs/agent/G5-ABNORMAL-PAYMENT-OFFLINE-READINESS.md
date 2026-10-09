# G5 — Abnormal Payment, G3 minimum prerequisite, offline readiness (2026-10-09)

**Scope:** BYL Website V3 / WooPayments Test Mode / Dev-only read-only preflight. **No payment, order, gateway configuration, G2 permit, DB, Dev runtime, AfterShip, WWW or ERP Production changes.**

## Authority and current boundary
- Baseline GitHub `main`: `2b56d3a1004257784077523b6c3f93728c458ae6` (PR #36/#37/#38/#39 merged). PR #28 remains a Draft and is not a completion certificate.
- Parallel G3 Draft PR #40 (head `4ca97bff76402d956bd8dd8e0d673ffd469065ea`) owns the Checkout JS/CSS/consent layout. Do **not** parallel-edit its files or infer it is deployed. Owner-approved Dev-only deploy was reported blocked by self-hosted Remote Operator policy.
- G4 historic successful Test Mode order `#39923` is a retained baseline, not a new G5 transaction, and does not prove G3, G5, incoming provider-webhook replay or real external isolation.
- Last evidenced Dev G2 was `ABSENT_CLOSED`. **Current live Dev grant state is UNVERIFIED** if privileged read-only checks are denied; never assert it was rechecked. Read-only admin checks may be done through a normal authorized browser; do not click Approve or Activate.
- WooPayments alone is the primary Card rail, Stripe hidden fallback, PayPal Sandbox excluded. Apple/Google Pay only eligible Cart/Checkout; no Product-page wallet.

## Source audit (non-transactional)
Run `python scripts/test_g5_offline_readiness.py`. This checks 16 concrete source safeguards: Dev/staging and WooPayments scope, default guest-zero methods, synthetic identity and six-order cap, 30-minute permit source configuration, pre-save synthetic marker, Store API Checkout 403, Woo **outbound** synthetic order webhook block, WP mail short circuit, native Woo Checkout submit authority and recovery from `checkout_error`. Existing dynamic mock tests `p13-g2-offline-security-smoke.php`, `p5-store-api-checkout-deny-smoke.php`, and `p7-sandbox-webhook-isolation-smoke.php` continue to run in Foundation CI.

**Do not interpret source assertions or PHP mocks as actual payment execution.** They do not provide idempotency for WooPayments inbound webhooks, Woo/ERP sync, AfterShip provider APIs, external emails or payment authorizations.

## G3 minimum prerequisite (separate Owner UAT)
1. From normal Dev tester/administrator browser profiles, verify a single WooPayments Card choice and no competing ordinary Card rails, and identify the historical `Card + 3 / Card + 5` labels (not assumed duplicates).
2. After separate approved Dev-only PR #40 deployment, test 390px and 1440px Review UI; native Terms + Privacy links, unchecked required consent, optional review-invitation consent, legal-text order, `updated_checkout`, no overflow/JS errors. **Do not click Place Order**.
3. Treat the Terms page displaying `Copyrights` and a legacy localdev support address as an outstanding **content/binding defect**; requires Owner legal/content review. PR #40 UI alone cannot close it.
4. Confirm Dev G2 permit is actually CLOSED; if administrator sees an active window, use normal **Revoke authorization immediately**. Do not create a new 30-minute permit for the current offline work.

## G5 case contract, evidence, and ownership

| Case | Offline layer (permitted now) | Authorized provider/browser layer (HOLD) |
| --- | --- | --- |
| G5-01 issuer decline | Verify server gate and WC-native submit boundary; failure must not mean Paid/fulfillable | Official WooPayments decline test, UI message, Woo/transaction flags and effects |
| G5-02 3DS success | Confirm Woo owns payment callback; no fake JS success | Official test-mode challenge, return, Woo paid proof and callback sequence |
| G5-03 3DS failure/cancel/timeout | Capture required negative transition rules | Authorized challenge failure, back/refresh/timeout, never Paid accidentally |
| G5-04 retry | Detect `checkout_error` busy-state reset; no new authorized attempt inferred | Same order vs new attempt, provider reference, recovery button and notices |
| G5-05 concurrent/double submit | **HOLD**: Checkout JS fixed `setTimeout(..., 6000)` unlocks `submitting` even if payment/3DS is still pending. Browser-only mutex != server idempotency; avoid concurrent edit to #40's JS. | Need approved remediation + offline fake-clock tests and only later authorized concurrency; show one order/capture |
| G5-06 inbound webhook duplicate/out-of-order | Existing P7 **outbound** filter mock has coverage, but no matching **inbound** provider replay/financial idempotency proof in Website repo | Sanitized signed-event replay and order/ERP reconciliation under independent authorization |
| G5-07 downstream side effects | Verify Dev WP mail and outbound native Woo synthetic-order filter code; ERP #435 merge != runtime deployment | Provider/AfterShip/email/ERP read-only event evidence; HOLD on external-notification risk |
| G5-08 teardown/recovery | Source G2 expiry and revoke mechanisms; no Dev writes | Live grant readback CLOSED, ordinary guest zero gateways, order-cap ledger, rollback evidence |

## Security and evidence gates
- **Zero new Sandbox transactions are authorized.** WooPayments test orders still create actual Woo order records; G2 activation and Place Order require separate Owner permission.
- Independent provider influence remains **HOLD** because AfterShip Dev notification flows are intentionally deferred until Dev→WWW cutover. Never amend those flows in this work package.
- Do not infer unused capacity under the **six-order cap** from a historic order list; current count must be read only before any proposed grant.
- Minimum future per-case evidence: GitHub head/CI, Dev MU SHA, staging/Test Mode, grant timeline, isolated user/guest states, sanitized order and provider attempt IDs, Woo order transition, matching duplicate event identifiers, exactly-once Woo/ERP/payments outcome, outbound notification/fulfillment effects, 390px/1440px screenshots, revocation and rollback.
- Official scenario authority: https://woocommerce.com/document/woopayments/testing-and-troubleshooting/testing/ . Consult the live upstream matrix for cards/3DS before an independently approved transaction, not invented test results.

## GO / HOLD
- **GO:** GitHub-only isolated regression and test-contract PR; read-only code and CI investigation; Dev nontransactional G3 browser UAT if already normally authenticated/authorized.
- **HOLD:** G3 final Owner PASS, #40 Dev deployment evidence, new 30-minute G2 approval, all new Sandbox orders, G5 issuer/3DS/parallel/provider-replay live tests, AfterShip, WWW and ERP Production.
- **Next independent gate:** Owner-approved Dev browser / authorized Operator read-only verification of G2 CLOSED and PR #40 Dev artifact hashes; then G3 390px/1440px no-order UAT and Terms content review. Any G5 transaction requires a **separate** documented external-impact decision and explicit Owner permission.
