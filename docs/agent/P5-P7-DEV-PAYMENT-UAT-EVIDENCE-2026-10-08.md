# BYL Website V3 — P5 Sandbox / P6 Gateway Economics / P7 Checkout Integration Gate

**Evidence date:** 2026-10-08 (America/Los_Angeles)
**Scope:** Dev only, `dev.beyourlover.com`. WWW and Live payment activation remain explicitly excluded.
**GitHub is the source of truth.** Documentation and read-only verification only in this PR.

## Owner acceptance carried forward

- **P4 WooPayments native address autocomplete:** Owner UAT PASS, recorded in Draft PR #18. Dev setting `woocommerce_address_autocomplete_enabled=yes`, native provider working in earlier isolated browser tests. No extra paid Google Places key.
- **Checkout Company removal:** Owner UAT PASS, recorded in PR #26 (`868afece503e004e82620653ce888af2184604f4`). That PR has been marked **ready for review** but remains **unmerged**. Exact `inc/checkout.php` patch already on Dev, billing and shipping company fields removed at the Woo checkout field contract layer. Subsequent Owner approval on 2026-10-08 authorized its GitHub main-only merge; PR #26 is now merged.

## P5 — Gateway sandbox transaction UAT

### Reused verified source (PR #25 merged, docs/agent/P1.3-PAYMENT-CHANNEL-MATRIX.md)

- WooPayments configured Card enabled with `test_mode=yes`; WooPayments account service checks reported `has_account_data=true`, `is_stripe_connected=true`, `is_stripe_account_valid=true`. These are **config readiness only**, not test-transaction success.
- Single Card routing policy: WooPayments chosen in Dev; independent Stripe Card **disabled** but retains Test Mode option and potential fallback; WooPayments express locations restricted to Cart+Checkout, none on PDP.
- **PayPal Sandbox excluded by prior Owner decision.** Do not ask to configure/test it during Dev P1.3/P5. PayPal Live remains a separate future P11 gate.
- Dev MU Safety protects ordinary visitors with **zero available payment gateways**; privileged test session requires a scoped short-lived synthetic-customer permit bound to an authenticated browser and appropriate test-mode gateway. Previous attempt to establish this privileged session was blocked by the execution tool's safety control; **do not bypass, relax or reattempt by alternate means**.

### Current P5 conclusion

- Browser and repo contract inspection can proceed, but **sandbox payment order creation remains BLOCKED** by absent permit/supported test-session execution. This document does not weaken the MU guard or claim an order was created.
- **NOT TESTED:** success card, issuer decline, 3DS challenge, duplicate submission, timeout, webhook signature/idempotency, test Woo order + ERP isolation/reconciliation, Stripe sandbox fallback.
- **Before a transaction:** supported authorized test-session workflow OR owner-supervised browser testing; Dev-only synthetic identity, merchant/payment type Test Mode verified, external email and ERP fulfillment isolated, max test order count and expiration enforced. If safety tooling blocks, stop and request a supported route.
- No live payment, WWW changes, production migration, rollback bypass, or secrets exposure.

## P6 — Gateway economics evidence (ERP PR #422 MERGED)

Source: `beyourlovercom/erpbeyourlovercom`, `docs/60_Codex_Operations/work-packages/ERP-GATEWAY-ECONOMICS-V1-001.md`.

- Historical audited paid-order cohort, 2026-04-01 onward as frozen 2026-09-24: WooPayments 861/861, Stripe Element 88/88, PayPal 464/464 distinct paid orders with observed order-level actual fee evidence.
- Observed keys: WooPayments `_wcpay_transaction_fee`; Stripe Element `_cpsw_stripe_fee`; PayPal `_paypal_fee`. Airwallex actual processor fee not available in V1.
- These counts are **historical fee evidence coverage**, not payment success/authorization rates or today's latest number. Avoid representing them as real-time performance.
- Economics analysis rows must be **gateway × currency**; no cross-currency amount sums. USD/EUR/GBP/CAD/AUD historically occurred.
- Owner's 3.5% is a financial/accounting estimate, not a real observed gateway fee. Conflicted/invalid/missing fee metadata => actual fee null, never substituted by estimate.
- Raw processor attempts, processor webhook event store, chargeback/dispute cost and Airwallex fee remain unavailable; cannot rank gateways fairly by total realized economics yet.
- ERP PR #422 is merged in GitHub, but Production migration/deployment and history backfill were **not authorized in that work package**. Current active runtime data freshness is unverified. **P6 code/evidence contract PASS; real-data current economics UAT PENDING.**

## P7 — Dev Checkout non-transaction integration UAT

### Local contract regression on `fix/checkout-remove-company-v1@868afece`

PASS:
- PHP lint on `theme/beyourlover-v3/inc/checkout.php`.
- `scripts/test_checkout_progressive_ux_contract.py`.
- `scripts/test_checkout_secure_shell.js`.
- `scripts/test_checkout_shipping_promotion_docs.py`.
- `scripts/test_cart_promotion_ux_contract.py`.
- `scripts/test_shipping_promotion_contract.py` (ran from project root; first run from wrong working directory failed only to locate a relative test fixture).
- `scripts/test_shipping_promotion_runtime_contract.py`.
- `git diff --check`.

### Real-browser Dev Checkout anonymous smoke

Isolated Chrome browser + ephemeral Dev cart with a published Dev product, **no order submission and no payment**.

At 390px, 500px, 1280px:
- Four step ids: `address`, `delivery`, `payment`, `review`.
- No `billing_company` or `shipping_company`, no Company field visible.
- Billing email, first/last name, country, address, city, state, ZIP remain present; `shipping_phone` present; no separate `billing_phone`.
- Native billing autocomplete integration remains available.
- Native payment host is present but `#payment input[name=payment_method]` is empty for public anonymous Dev sessions, **EXPECTED** because MU safety gate fails closed.
- CusRev review opt-in `cr_customer_consent` is **unchecked** by default; valid Privacy Policies links still present.
- Document width equals viewport (no page-global horizontal overflow), zero browser `pageerror` events.
- The promo-code input is present in DOM but not shown in the 390/500px initial view, visible at 1280px. This may be a collapsed/mobile summary state and was **not** functionally verified; retain mobile promotion functionality as an open regression gate.

### P7 conclusion

- **PASS** for static contract tests and anonymous, non-payment Checkout safety/mobile DOM smoke.
- **PENDING**: authenticated permitted sandbox checkout, Apple/Google Pay on eligible devices, address/shipping/tax totals stability through Delivery/Payment/Review, coupon/mobile summary expand, 3DS/decline/retry/webhook/thank-you, test order state and ERP/AfterShip/email isolation, Owner end-to-end UAT.
- Do not label P7 full integration UAT PASS until P5 safe test payments and post-purchase regression are complete.

## Gate summary

- P4 native address autocomplete Owner UAT **PASS**.
- Company field removal Dev Owner UAT **PASS**; PR #26 **MERGED into main** as `0d55b4d72636d609082c228f495245da44d08409` (merge only, no WWW deploy).
- P5 WooPayments account and gateway config **PASS**, but actual Sandbox transaction UAT **BLOCKED / NOT RUN**.
- P6 ERP economics **implemented and merged**; current transactional metrics and release gate **PENDING**.
- P7 Checkout **non-payment integration smoke PASS**; full payment/post-purchase UAT **PENDING**.
- WWW Production payment and deployments **NOT AUTHORIZED**.


## Follow-up read-only checkpoint — 2026-10-08

- Remote Dev verification: `siteurl=https://dev.beyourlover.com`; WooPayments `test_mode=yes`; native BYL Dev Safety MU plugin present.
- The narrowly scoped private `p13-sandbox-gate.json` file was confirmed **absent** at its canonical path. Thus the normal public session remains blocked. No file was created, no session token handled and no payment attempted. Earlier privileged authenticated session creation had been blocked by execution safety checks; do not bypass these checks.
- The Dev WooCommerce HPOS setting reads `yes`. Old `wp post list --post_type=shop_order` count was zero but **is not valid proof of no HPOS orders**; do not infer order inventory from the legacy posts count. Order verification remains pending an HPOS-aware read-only method.
- GitHub Foundation CI on PR #26 exact head `868afece503e004e82620653ce888af2184604f4`: **success**. PR #26 was **ready for review and mergeable** during that checkpoint; Owner UAT recorded PASS; **subsequently merged** as `0d55b4d72636d609082c228f495245da44d08409`. No merge performed in this checkpoint.
- GitHub Foundation CI on PR #28 `64c9046f98dff0009957f89c15926680c14e76ae`: **success**; Draft remains open.
- ERP PR #423 (Payment Reliability Closure V1) also **merged**, composed from prior read-only Payment Health + Gateway Economics, expressly no Production deploy, write, payment routing change or P1.9 schema migration. It does not update the historical fee evidence to current live runtime data.
- **Next supported gate:** independently authorized, safely provisioned synthetic-customer test session within the established MU limits, plus verified isolation from ERP fulfillment/email, before any transactional UAT. If not available, keep P5 transaction and full P7 marked blocked; work on doc/contract verification only.


## 2026-10-08 — PR #26 main-only merge closure

- Owner expressly approved `approve merge PR #26 — Owner UAT PASS — exact-head=868afece503e004e82620653ce888af2184604f4 — Foundation CI PASS — merge main only — no WWW deploy`.
- Fresh pre-merge GitHub checks: PR #26 open, ready for review, mergeable; exact head unchanged; Foundation CI `success`.
- GitHub `merge_pull_request` returned `merged=true`, merge SHA `0d55b4d72636d609082c228f495245da44d08409`; post-merge PR status `closed / merged=true`.
- **Only GitHub main merge** was executed. No WWW deploy, Live payment, Dev gateway changes, DB migration, Woo test order, ERP business-data write, or altered Sandbox permit.
- **Remaining critical path unchanged:** P5 Dev controlled Sandbox authenticated test session/permit still absent and earlier attempt blocked; P6 current actual fee evidence verification pending; P7 full payment/order/webhook/post-purchase Owner UAT pending; WWW independent gate not approved.
