# G5 — Abnormal WooPayments Sandbox / Dev UAT Contract

**Source of truth:** GitHub `beyourlovercom/beyourlover-web-v3`, based on `main` after PR #40 (G3 Owner UAT PASS). This document defines **readiness and evidence**, not authorization for payments.

## Fixed scope / stop rules

- **Dev only:** `https://dev.beyourlover.com`, environment `staging`, WooPayments **Test Mode**. G2 normal WP admin 30-minute approve + synthetic Woo customer browser activation; **closed by default**.
- Exactly one WooPayments primary Card rail. Stripe Card stays hidden hot backup; **PayPal Sandbox excluded**. Do not install new WordPress plugins, change gateway routing, disable G2, or touch WWW/ERP Production.
- Synthetic identity `byl-p13@example.invalid`, no real customer details. The Dev MU caps synthetic customer orders at **6 lifetime**. Before any real test, independently count existing synthetic orders; never assume remaining capacity.
- No unauthorised Place Order, payment, payment-intent confirmation, 3DS challenge, webhook redelivery, duplicate POST, Order status mutation or sandbox test order. Real provider UAT requires **separate, explicit Owner scope** and review of external effects.
- Synthetic Woo native `order.*` webhook and `pre_wp_mail` safety do **not** prove the AfterShip provider's independently configured notification flows are isolated. Owner chose to defer AfterShip changes to the Dev→WWW migration; **do not modify AfterShip in G5**.
- Do not inspect/forge WordPress cookies, G2 cookie hash, payment keys, Provider secrets or use privileged browser session injection. Do not restart connected devices / Chrome.
- On any Live charge, unexpected customer message, ERP import, fulfillment, missing gate, full cap, unknown payment status or third-party effect: **HOLD and revoke** through the normal admin page.

## Layered G5 matrix

| Case | Offline deterministic evidence (no payment) | Real WooPayments Test Mode Owner UAT (separately gated) | Current state |
| --- | --- | --- | --- |
| G5-01 Issuer decline | Woo `checkout_error` re-enables only the explicit retry path; failure must not imply paid | Official generic decline or insufficient funds, exact UI message and Woo order status plus Test Mode transaction/error code | OFFLINE pending; PROVIDER HOLD |
| G5-02 3DS success | Long `requires_action` window never resets UI submission lock on timer | Real challenge/redirect, approved Test Mode intent and exactly one paid order | OFFLINE pending; PROVIDER HOLD |
| G5-03 3DS failure/cancel/timeout | Pending or cancelled authentication must not be inferred paid; no timer-driven auto retry | User cancels/declines challenge, returns to checkout; order and intent state, no fulfillment | CONTRACT only; PROVIDER HOLD |
| G5-04 Retry after failure | Retry allowed **only** after explicit Woo `checkout_error`; direct repeated click blocked | One intentional retry, distinguish payment attempt from order ID, verify no additional payment on same success | OFFLINE pending; PROVIDER HOLD |
| G5-05 Double click / concurrent | Double-click + 100 re-click/fragment events call `nativeButton.click` only once per in-flight cycle | True concurrent network submissions require separate permission, per-attempt ID and provider-level idempotency | OFFLINE pending; PROVIDER HOLD |
| G5-06 Duplicate, replayed or out-of-order webhook | P7 Woo `woocommerce_webhook_should_deliver` mock blocks 15 repeated/out-of-order synthetic `order.*` events, on top of existing guard cases; does **not** prove provider PaymentIntent event deduplication | Provider webhook/event and order IDs, duplicate/ordering acceptance policy, ERP/finance/fulfillment side effects; no redelivery without approval | WOO NATIVE OFFLINE PASS; PROVIDER HOLD |
| G5-07 Failed-payment effects | G2/Store API deny, fail-closed gateway, Dev email and synthetic Woo webhook guards (local mock only) | Failed order cannot become Paid/Completed/shippable or send AfterShip/customer mail; audit scope and system isolation | EXISTING partial; E2E HOLD |
| G5-08 Teardown / rollback | Invalid/no permit blocks guest, source rollback path, `G2_CLOSED` after UAT | Admin Revoke, subsequent anonymous 0 gateways, read-only runtime hash/CI and test logs | EXISTING partial; LIVE UAT PENDING |

**Dev AfterShip audit (read-only):** The installed active `aftership-woocommerce-tracking` plugin includes a direct `wp_remote_post` to `https://api.automizely.com/aftership/public/graphql` for `WG_INS_queryConfig` (shipping protection configuration). This request alone does not demonstrate notification delivery or order propagation, but does show the plugin can reach the external provider by a path other than the native Woo order-webhook filter. The provider-flow customer-notification isolation is not yet independently proved; **never infer it from no native Woo webhook delivery**. Do not reconfigure the provider just to green-light G5.

**Do not mark** G5-01, G5-02, G5-03 or any provider-related G5 row PASS based only on test-card documentation, mock tests, screenshots, successful G4 order #39923 or source CI. Report `UNVERIFIED` where required order/intent or downstream evidence is absent.

## G5 duplicate UI submission mitigation

Before G5 development, the Checkout Review action set `submitting=true` and **blindly cleared it after 6000 ms**. During a prolonged 3DS flow this could allow a second UI-triggered `nativeButton.click()`. The proposed focused change removes the timeout; it keeps the BYL CTA disabled with `aria-busy=true` until Woo emits `checkout_error` (then customer may initiate one explicit retry), or navigation after successful payment. It leaves native Woo validation, nonce, gateway and callback authoritative. **This is client-side defense-in-depth, NOT server-side payment idempotency or a guarantee against custom POST/replay/network races.**

Run `node scripts/test_g5_submission_lock.js` offline; this extracts the **actual** Checkout event handler into a disposable fake DOM using Node `vm`. It never sends HTTP or clicks a browser/payment button. The actual native click is replaced by a counter.

## Official sandbox scenario references (read before real Owner UAT)

- WooPayments official testing: https://woocommerce.com/document/woopayments/testing-and-troubleshooting/testing/
- Stripe official test values / 3D Secure behavior for underlying test payment processing: https://docs.stripe.com/testing
- Generic issuer decline: `4000 0000 0000 0002`. Insufficient funds: `4000 0000 0000 9995`.
- WooPayments 3DS test examples: Always authenticate `4000 0027 6000 3184`, authenticate unless setup `4000 0025 0000 3155`, 3DS then insufficient funds `4000 0082 6000 3178`.
- Values are **test-only**, never send real card details or paste payment credentials in GitHub/chat. Confirm current provider documentation and account behavior just before an authorised UAT. These numbers in this document are **not permission to place orders**.

## Evidence form and gates

Each scenario: `G5-XX | mode=OFFLINE/PROVIDER | exact git head | Dev deployment SHA | time (timezone) | gate=CLOSED/ACTIVATED | synthetic order count before/after | order ID if any | Woo state before/after | WCPay test flag | sanitized provider intent/event status | customer messages / native Woo webhook / AfterShip / ERP effects | screenshot or log location | PASS/FAIL/HOLD/UNVERIFIED | rollback / G2 revoke evidence`.

**Release gate sequence:**

1. GitHub Draft PR → focused tests / Foundation CI → *Owner approval to merge and/or Dev-only deploy separately*.
2. Read-only Dev runtime: WooPayments Test Mode, G2 CLOSED, sole Card rail, installed plugins, synthetic lifetime order count, isolated mail/native Woo webhook filters; **do not overclaim AfterShip or ERP Production isolation**.
3. Real provider G5 only if the Owner approves a concrete maximum number of additional synthetic orders, selected test cases, and independently reviewed external-effect isolation. G2 admin approves only *after* that authorization; test customer activates by normal My Account. Never fake the user/nonce.
4. Owner manually completes real UI Test Mode scenarios (390px and 1440px) without sharing passwords/cookies/secrets; capture payment status and browser screenshots. Use real provider status, not mock screenshots.
5. Immediately revoke via WP Admin; independently verify permit file absent and guest 0 gateways. Final G5 Owner PASS only for cases whose required provider/downstream evidence exists.
6. G5 cannot authorize WWW Production deployment, DB migration, Mail/AfterShip reconfiguration or ERP mutating actions.

## Owner UAT boundaries to resolve

- G5 *offline* guard can progress with G2 CLOSED.
- G5 *provider* UAT remains **HOLD** because third-party outbound notification risk is not proven isolated and the synthetic order budget is not yet read. G4 successful payment is historical baseline and must not be rerun.
- **No payment-provider idempotency key behavior has been validated**; frontend lock cannot replace server-side accounting/event deduplication.
