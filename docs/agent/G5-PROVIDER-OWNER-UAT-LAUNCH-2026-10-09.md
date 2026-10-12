# G5 — Provider Test Mode Owner UAT Launch Gate (2026-10-09)

**Status:** Technical nontransaction pre-UAT complete; **real Provider transactions remain HOLD** until independently isolated. GitHub is the sole source of truth. This document does NOT grant permission to place orders, change payment settings or modify production.

## Evidence already PASS

- G3 PR #40 already merged and Owner UAT PASS. G5 Draft PR #44 Checkout reentrancy fix: latest known exact-head `d2760b630659bc27f7e6a2249a86de4ec69eeb4d`, Foundation CI PASS; 7 offline fake-DOM scenarios, including 3DS pending, double click, retry only after `checkout_error`, Enter-key submission and step navigation. Deployed Dev Checkout JS SHA-256 `e6ee86d09a4e06f54e7cd2a65254f56c7994b58caab353fb2b0f3377c052b13d`.
- G5 Draft PR #48 Dev outbound guard: parent source exact-head before this documentation `a09df792f0cf35eb438159eb0c25c0d597701879`, Foundation CI PASS. Existing Dev Safety MU SHA-256 `6955e86d6ac14107684509ad6f5b240a934b966d01d743f60cf9206d33398015`. Actual WP filter runtime blocks Automizely/AfterShip WP HTTP requests, preserves WooPayments/Stripe and lookalike domains.
- G2 private permit ABSENT/CLOSED; WooPayments enabled and Test Mode=yes. Anonymous Woo REST GET `/wp-json/wc/v3/orders` and `/orders/39923` return HTTP 401. Store API Checkout GET returns 403; Store Cart/REST index return 200.
- Native Woo order webhooks #3 and #4 target `webhooks.automizely.com`; `woocommerce_webhook_should_deliver` returned BLOCKED for the existing marked synthetic order in read-only filter calls (not actual delivery). P7 offline repeat/out-of-order guard also PASS.
- Fresh isolated guest Chrome at 390px/1440px PASS for fail-closed gateway/Terms/Privacy/UI, with no order submission. Historical test customer's current lifetime synthetic order count **1 of max 6**, capacity **at most 5 additional orders** (not permission).

## Key new inbound AfterShip exposure — requires separate risk gate

Dev `aftership-woocommerce-tracking` is **connected=true**. One WooCommerce API key is labeled for AfterShip/Automizely and has **read_write** permissions; the key material, key ID and customer details were not read or exported.

Plugin source registers a custom `aftership-api` route at WordPress `parse_request` priority 0 and exposes authenticated orders endpoints `/v3/orders`, `/v4/orders`, `/v5/orders` with collection and single-order queries. Authenticated external polling is **inbound** and therefore **not stopped** by the existing WordPress `pre_http_request` outbound guard or native Woo webhook suppression. Standard Woo REST order endpoints can also be called by a party that legitimately possesses API credentials.

Anonymous read-only probes to `/aftership-api/v3/orders`, `/v4/orders`, `/v5/orders`, and `/v5/orders/39923` return HTTP 200 with `text/html` body containing only the numeric value `0`. **No anonymous order data was returned in this test.** This is *not* evidence that a credentialed Provider cannot fetch orders. No secrets/cookies were used, and no API writes were performed.

No AfterShip-named WordPress cron entries were found in this scan. This does **not** rule out provider-side jobs or queued notifications outside Dev WordPress.

WooCommerce's official testing documentation cautions that sandbox Woo orders are still real Woo order records and extensions can treat them like regular orders; see https://woocommerce.com/document/managing-orders/testing-orders/ and https://woocommerce.com/document/woopayments/testing-and-troubleshooting/testing/.

## Isolation decision required before first new payment attempt

**Preferred — independent G5 payment-test environment**: Use a dedicated temporary staging identity/site/database with WooPayments Test Mode, synthetic test customer and **no AfterShip/Automizely authorization or live Provider org connection**, no real customer/order copy, no ERP/Woo integration, no external fulfillment or mail transport. Verify all connectors are disconnected/denied and immutable snapshot/rollback is available. Creating this separate environment/DB/identity, cloning any application and connecting payment Test Mode requires a separate scoped Owner approval; never copy live gateway keys or provider credentials as a shortcut.

**Alternative — temporary Dev inbound quarantine**: On existing Dev, explicitly approve a reversible block for authenticated AfterShip custom order API **and** WooCommerce REST order read/write endpoints accessed by external API consumers. Confirm it does not break WooPayments gateway processing, internal Dev testing or any necessary service. Strongly prefer a dedicated staging site because a partial inbound route block does NOT guarantee Provider-side jobs cannot act on already imported orders. Do not revoke/delete the existing read_write key, disable the AfterShip plugin, or reconfigure the Provider under the existing *outbound-only* approval.

**Until isolation is independently evidenced, real G5 decline/3DS/submit/retry tests remain HOLD.** No speculative claim that the Provider will never message, bill, import or fulfill a sandbox order.

## Planned Owner UAT once isolated and additionally authorized

| Scenario | Human action | Required independently captured evidence | Default gate |
|---|---|---|---|
| G5-01 Issuer decline | Use provider-official declined Test Mode card once | Order attempt vs Woo order ID, declined provider intent, no paid/fulfilled state, no mail/AfterShip/ERP effects | HOLD |
| G5-02 3DS success | Complete provider 3DS challenge with official test data | Exactly one successful intent and Woo order, correct Review/Thank You flow at 390px and 1440px | HOLD |
| G5-03 3DS cancel/fail | Intentionally cancel/decline a challenge | Pending/failure correctly communicated; no paid, no fulfillment/notification | HOLD |
| G5-04 Retry | Explicit one retry **after error only** | Separate attempt count, no duplicate captured amount/order processing | HOLD |
| G5-05 Duplicate/long pending | Attempt repeated UI clicks during an authorized 3DS window, **not raw POST replay** | One native submit, pending lock and no duplicate provider charge | HOLD |
| G5-06 Webhook replay | Read-only processor event IDs/status; do not manually redeliver | Provider deduplication evidence; native Woo synthetic webhooks remain blocked | HOLD |
| G5-07 Downstream isolation | Inspect audit for test orders | No AfterShip sync/notification, external mail, ERP, accounting, fulfillment or production effects | HOLD |
| G5-08 Teardown | Click normal Dev WP Admin Revoke | G2 permit file ABSENT, guest gateways 0, Test Mode still active, backups intact | HOLD |

**Suggested initial budget:** up to **3 new synthetic orders** in the first controlled window, even though the last read of the lifetime cap indicates 5 remain; do not assume failed attempts do/do not create orders. Recheck the exact count before each attempt, stop on any unexpected side effect, and never exceed 6 lifetime without a new explicit governance decision. Do not start a test window without a real Owner present.

## Exact run sequence (future, after isolation)

1. Verify GitHub source exact-head and CI PASS; Dev runtime PHP/JS SHA match accepted versions, siteurl/home point only to Dev, environment=staging, WooPayments enabled + Test Mode and G2 permit file absent.
2. Verify Provider isolation objectively: credentials absent or inbound endpoints unable to retrieve synthetic orders even with authenticated external API access; inspect outbound email/native webhook and ERP/fulfillment audit. Do not put API secrets in a test script or chat.
3. Owner explicitly approves the **number of additional synthetic orders**, named G5 cases, and permitted outcomes, including browser/3DS interactions. Approval for the Dev outbound filter by itself is not transaction approval.
4. Owner/Admin uses the normal `Tools → BYL Sandbox UAT` **Approve 30-minute test window** and the synthetic customer clicks native **Activate Sandbox Test** in `/my-account/`. No cookie cloning, nonce spoofing or raw endpoint injection.
5. Owner executes only approved official Test Mode card/3DS actions in a normal Chrome customer session at 390px and 1440px; inspect UI, order status, provider mode/status and order count after **every** attempt.
6. At any email, Provider sync, notification, live-money, fulfillment, ERP write, duplicate order, unexpected status or cap mismatch: **STOP**, revoke via normal Admin GUI, preserve audit and do not repeat automatically.
7. After UAT, Admin **Revoke authorization immediately**; independently reverify G2 CLOSED, guest 0 gateways, and Dev rollback state. Mark PASS only with actual screenshots/processor/side-effect evidence; CI and mocks alone cannot close Provider cases.
8. PR #44/#48 main merges and WWW release require their own exact-head approvals. **Do not deploy WWW, run DB migrations, remove customers/orders or reconfigure AfterShip Provider under this UAT document.**

## Current Owner UAT status

**Code / Dev / anonymous visual / native Woo webhook / WP HTTP outbound: PASS within proven scope. Provider real failed-card/3DS/retry: BLOCKED on connected AfterShip read_write API credentials and unverified Provider remote jobs.**

The next exceptional approval should be an **isolated G5 Provider test environment (recommended)**, not an unqualified permission to click Place Order on the current linked Dev instance.
