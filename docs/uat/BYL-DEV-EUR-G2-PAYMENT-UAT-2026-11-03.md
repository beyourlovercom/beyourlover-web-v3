# Dev EUR local payments — scoped G2 UAT proposal (2026-11-03)

Status: **Design/Draft only — no MU safety source changes, no Production changes, no payment/grant actions.**

## 1. Runtime findings (official BYL DC)

- `dev.beyourlover.com` WooPayments primary card gateway: enabled=yes, account connected=true, `needs_setup()=false`, `is_available()=true` within admin, card capability=active.
- `WC()->payment_gateways()->get_available_payment_gateways()` returns `[]` in admin; the source is priority-99 `woocommerce_available_payment_gateways` closure from `wp-content/mu-plugins/byl-dev-safety.php`.
- Existing main code in `dev/mu-plugins/byl-dev-safety.php` lines 355–361 deliberately gives ordinary visitors no payment methods; only a valid `byl_dev_p13_checkout_gate()` gets **one** chosen gateway.
- Actual existing admin portal `/wp-admin/tools.php?page=byl-p13-sandbox` displayed **Gate status: Closed**, not approved or tester-activated. Do **not** activate it before the new payment/currency UAT is ready.
- Existing G2 permit: exact Dev origin + HTTPS, admin approval; one unprivileged synthetic customer `byl-p13@example.invalid`; a host-only HttpOnly Secure/SameSite Strict browser cookie; max 30-minute permit, max 11 synthetic orders, one gateway, WooPayments Test Mode required.
- Existing Store API **Checkout** intentionally returns HTTP 403. Never bypass or expand to Store API. Classic Woo checkout only.
- Current Dev YayCurrency Pro `isCheckoutDifferentCurrency=0` (**OFF**), fallback USD, FX EUR=0.897. Do not flip this owner-frozen option without separate Dev-only approval.
- Lab V0.1.1 product/cart at EUR: `126.40 USD` → `113.38 EUR`. During 3-step Checkout, captured Store API `currency_code=EUR`, total `12640` minor units, while checkout displayed `USD 126.40 (~EUR 113.38)`. This is a **must-stop amount/currency mismatch**. No order, Payment Intent, charge, or refund created.
- Browser UAT on US Mac Mini verified opt-in request header propagation across approximately 490 Dev requests per country, including AJAX and Store API. Guest Payment stage showed no gateways as designed by G2.

## 2. Hard gates, in required order

**G0 — Baseline:** Reuse the already-installed opt-in WooPayments × YayCurrency V0.1.1 lab and its PR #291. Do not enable it for general traffic. Do not modify WWW.

**G1 — Currency correctness:** In Dev-only isolated test context, resolve conflicts between YayCurrency checkout authorization/fallback, WooPayments Multi-Currency and the lab; validate USD and EUR product, cart, shipping, coupon, tax, Woo classic checkout and Store API cart return coherent currency and identical cent amounts. Any `EUR 12640` against EUR-priced `11338` is FAIL. A temporary YayCurrency checkout setting change requires explicit Owner pass and rollback proof; it remains OFF by default.

**G2 — Scoped EU gateway proposal:** Amend the existing MU safety gate only on a *separate reviewed Dev-only branch* (not this design PR). Keep all controls. The current card `woocommerce_payments` path remains unchanged. A separate explicit **default OFF** feature gate may allow exactly one UAT method in a permit at a time:
- Belgium + EUR → `woocommerce_payments_bancontact`
- Austria + EUR → `woocommerce_payments_eps`
- Netherlands + EUR → `woocommerce_payments_ideal`

Never reveal these to guests, anonymous browsers, administrators acting as buyers, other countries, other currencies, or any customer lacking the existing approved G2 synthetic identity and bound cookie. No blanket `return $gateways`, no `remove_all_filters`, no disabling `byl-dev-safety.php`; preserve order cap, 30-minute expiry, server-side preflight, cookie hashing, admin approval and Store API 403. Existing WooPayments gateway methods must be enabled in TEST mode and account capability active. One EU country/rail per permit.

**G3 — Security preflight:** Independent review of exact GitHub source HEAD, Dev MU runtime SHA, backup and one-command rollback. Negative tests prove regular buyers see 0 gateways; mismatched country/currency, missing/wrong/expired permit, missing cookie, non-synthetic customer, disabled test gateway, and Store API Checkout cannot create orders. No automatic grant issuance.

**G4 — Owner-admin activation:** After G1–G3 pass, Owner explicitly approves the one-use / 30-minute Dev window in the existing **BYL Dev Sandbox UAT** page, and the real synthetic customer activates it through the **same US Mac test browser** on My Account. Neither browser credential nor cookie plaintext is logged, copied, or shared.

**G5 — Test payments and refunds:** Only after the browser displays the single approved EUR rail, compare Woo order `currency`, order `total`, Payment Intent `currency` and integer `amount` before capture. For a successful test transaction, verify the same currency/amount on charge, Woo order readback and refund. Refund cannot be tested until a previously validated test payment exists. Check no live transport, mail, aftership/ERP sync, or real customer order leakage. Immediately revoke grant and rollback Dev test settings afterward.

## 3. Current gate matrix

| Gate | Status |
| --- | --- |
| US Mac isolated browser / tagged AJAX | PASS |
| Opt-in lab price/cart/flat-rate smoke | PASS in previously tested sample |
| G2 deliberate zero gateways / reason | CONFIRMED |
| Existing G2 Admin portal | CLOSED |
| Dev WooPayments TEST-connected/card capability | PASS (admin context; not paid-flow proof) |
| YayCurrency checkout permission / transaction EUR consistency | BLOCKED (OFF + mismatch) |
| BE/AT/NL local methods under G2 allowlist | NOT IMPLEMENTED |
| Test Payment Intent / refund | NOT RUN |
| Production | UNCHANGED |

## 4. Forbidden shortcuts

No use of production Stripe account or live keys; no synthetic `order_id` claims; no cross-session cookie copy; no disabling Dev MU safety; no Store API Checkout 403 bypass; no generic Woo gateway enablement for guests; no Production deployment or main merge before Owner PASS. The isolated read-only diagnostic plugin may be deactivated after evidence capture; it is not needed in Production.

Relevant official docs: https://woocommerce.com/document/woopayments/payment-methods/local-payment-methods/ and https://docs.yaycommerce.com/yaycurrency/configurations/checkout-options .
