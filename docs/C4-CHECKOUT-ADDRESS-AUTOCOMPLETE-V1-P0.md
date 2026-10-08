# Checkout Address Autocomplete V1 — P0 Provider Contract

Status: P4 DEV CONFIG ENABLED / AUTOMATED UAT PASS / OWNER MOBILE UAT PENDING — PR STILL DOCS-ONLY
Base: main@d46137b (PR #15 merged)
Environment: Dev-first; WWW/Production explicitly gated.

## Evidence (2026-10-08)
- Dev WooCommerce 11.1.1 includes the native AddressProviderController, WC_Address_Provider and native address-autocomplete JS/CSS.
- Dev option `woocommerce_address_autocomplete_enabled=no`.
- Live `AddressProviderController::get_providers()` returns **0 registered providers**. Flipping the setting alone cannot provide suggestions.
- Active Dev plugin list contains WooCommerce, Andy Commerce, BYL Platform, CusRev and other utilities, but no WooPayments/address suggestion plugin.
- Official implementation contract: https://developer.woocommerce.com/docs/features/address-autocomplete
- Pricing/session documentation: https://developers.google.com/maps/documentation/places/web-service/session-pricing

## V1 implementation boundary
1. Reuse native WooCommerce autocomplete UI and registered provider extension; no second address widget or extra plugin without owner approval.
2. Register one server-side `WC_Address_Provider` using `woocommerce_address_providers` and its matching client-side provider through `wc.addressAutocomplete.registerAddressAutocompleteProvider`.
3. Preferred architecture is BYL-owned integration in existing Andy Commerce (after owner architecture sign-off), not a new plugin. Provider credentials only via Dev secret store; never committed, logged, or embedded into a browser request unless a provider requires a separately restricted public browser key.
4. Provider supplies country-scoped `search(query, country, type)` and `select(id)` data; suggest only after at least 3 characters, with debounce, stale-response rejection, request caps and no suggestions where unsupported.
5. Auto-fill street, city, state/province, postcode, country when valid; leave apartment/suite editable. Woo validation, shipping methods, order totals and payment remain Woo-authoritative.
6. Fail-open for manual address entry: no provider, API error, quota exceeded, slow response or unsupported country must not block checkout.
7. Mobile 390px and 500px, desktop 3-column City / State / ZIP alignment, keyboard & screen reader, privacy disclosure and canonical Burgundy VI.
8. Keep address autocomplete disabled until Dev-only provider credentials and spending controls are explicitly authorized.

## Provider decision gate
- Candidate: Google Places (New), not yet approved or provisioned.
- Required: confirm Cloud project, billing account, restricted key (server/API restriction as applicable), allowed country set, quota/day, monthly budget alerts and anticipated checkout volume.
- Google charges by SKU/session; abandoned sessions and Details termination change pricing. Do not represent as free.
- No calls to paid provider or third-party address sharing before approval.

## Dev acceptance gates
- Confirm one registered server provider, native frontend asset and autocomplete setting only in Dev.
- Type 3+ characters in Street: up to 5 suggestions; select a real address and verify address/city/state/postcode/country.
- Change country; unsupported country always allows manual entry.
- Confirm apartment field stays editable, Select2 State remains one line, no duplicate suggestions after `updated_checkout`.
- Simulate provider outage/timeouts/quota: checkout stays usable and manual address remains available.
- Payment gate from PR #15 remains fail-closed if Woo requires payment but no gateway is available; no order creation and no credential exposure.
- Re-run shipping promotion, coupon, review text and mobile Checkout contract.
- Local tests -> Dev deploy -> Owner UAT PASS -> GitHub merge. WWW requires separate approval.

## Current status
P0 provider audit PASS; provider registration 0; design specification ready. P1 connector engineering BLOCKED on approved provider/credentials/budget, Dev setting remains no. No WWW changes.


## 2026-10-08 — P1/P2/P3 rescan (supersedes earlier Dev snapshot)

This is a read-only Dev and official-policy rescan. The earlier zero-provider / no-payment-plugin observation above was accurate **before** the latest plugin installation; do not use it as current state.

### Dev read-only evidence
- GitHub: PR #15 merged; PR #18 remains a Draft, documentation-only proposal.
- Dev: WooCommerce 11.1.1; WooPayments 11.1.0 active; WooCommerce Stripe Gateway 11.0.1 active; `pymntpl-paypal-woocommerce` 2.0.27 active; Airwallex not installed.
- WooPayments gateway `enabled=yes`, `test_mode=yes`; independent Stripe gateway `enabled=yes`, `testmode=yes`. This is **not** evidence of account approval or successful payments. Test-only configured gateways need browser review for duplicate cards/wallets.
- PayPal plugin registers `ppcp` and related wallet gateways; PayPal account connection, gateway eligibility and sandbox routing are **not verified**. Do not infer these from plugin activation or legacy `woocommerce_paypal_settings` values.
- Store configuration: USD, default location US:CA. This does **not** establish the legal merchant entity country or Klarna approval.
- Native WooCommerce `AddressProviderController::get_providers()` now returns **one** provider: `WC_Payments_Address_Provider`; `woocommerce_address_autocomplete_enabled=no` remains unchanged.
- No payment transaction, checkout order, external paid Places API key, Dev option write, Production change or migration was performed in this rescan.

### Official documentation / provisional decision
- WooPayments Klarna varies by merchant country and account eligibility and does **not** support cross-border BNPL: customer billing country must match merchant account country and the national currency. Source: https://woocommerce.com/document/woopayments/payment-methods/buy-now-pay-later/
- U.S. WooPayments published Klarna base fee (indicative **only if U.S. merchant**): 5.99% + $0.30; $15 dispute fee. Actual BYL merchant location/contract is unverified. Source: https://woocommerce.com/document/woopayments/fees/
- Airwallex supports merchant regions including US, UK, EEA, HK and shopper countries across US/EU/UK; Airwallex Klarna currently documents settlement eight business days after capture, manual-capture deadline 28 days, refund/dispute eligibility up to 180 days. Merchant-specific fees are not verified. Sources: https://www.airwallex.com/docs/payments/payment-methods/global/klarna ; https://www.airwallex.com/docs/payments/online-payments/plugins/woocommerce
- WooPayments address autocomplete requires WooCommerce 10.3+ and WooPayments enabled in checkout. Native WooPayments provider is available; standalone Google Places key/integration should **not** be developed yet. Source: https://woocommerce.com/document/woopayments/settings-guide/
- **Merchant-category eligibility is a blocker, not a footnote:** adult-oriented goods/services may be prohibited or restricted by the processors. The merchant must seek accurate written confirmation for its actual merchandise, legal entity and destination markets before any Live connection. Sources: https://woocommerce.com/document/woopayments/our-policies/prohibited-and-restricted-products/ ; https://stripe.com/legal/restricted-businesses ; https://help.airwallex.com/hc/en-gb/articles/4410623274905-Unsupported-Industries ; https://www.paypal.com/us/cshelp/article/paypal%E5%AF%B9%E6%80%A7%E5%8F%96%E5%90%91%E5%95%86%E5%93%81%E5%92%8C%E6%9C%8D%E5%8A%A1%E7%9A%84%E4%BA%A4%E6%98%93%E5%88%B6%E5%AE%9A%E4%BA%86%E4%BB%80%E4%B9%88-help384?locale.x=zh_US

### Decisions and remaining gates
- **WooPayments KEEP (Dev Test only); Stripe TEMP KEEP (Dev Test only); PayPal KEEP pending Sandbox/connection verification; Airwallex DEFER**. No Live payments or unverified Klarna eligibility.
- **Address Autocomplete P4 blocked on explicit Dev opt-in**, review of address-data handling, then synthetic-address-only browser UAT; option remains `no` until approval. Use existing WooPayments provider first; no new paid Google API or duplicate widget.
- P5: confirm PayPal actual Sandbox gateway routing; inspect whether Stripe and WooPayments appear as duplicate card/wallet options; then test only with synthetic checkout/test orders under separate authorization.
- P6: use actual transaction/fee/settlement evidence, not assumptions, to decide gateway economics and Airwallex necessity.
- P7 Owner UAT; P8 WWW entirely separately approved.


## 2026-10-08 — P4 Dev-only native WooPayments autocomplete automated UAT

**Owner authorization:** use registered `WC_Payments_Address_Provider`, turn on autocomplete in Dev only, use publicly known/synthetic address data, preserve manual fallback, do not provision new Google paid keys, create orders, connect Live payments, deploy WWW or perform Production writes.

**Environment & change**
- `dev.beyourlover.com` only; WooCommerce 11.1.1, WooPayments 11.1.0.
- **Single Dev runtime configuration change:** `wp option update woocommerce_address_autocomplete_enabled yes` (previous value `no`; post-update readback `yes`). No code, plugin, payment gateway or Production changes.
- WooCommerce registered provider: `WC_Payments_Address_Provider` (one server-side instance); client `window.wc.addressAutocomplete` and WooCommerce `address-autocomplete.min.js` / `a8c-address-autocomplete-service.min.js` loaded in the real Checkout. Frontend provider is registered and active.
- Rollback for Dev only (if owner requests or regression emerges): `wp option update woocommerce_address_autocomplete_enabled no`. **Do not execute on WWW.**

**Real-browser automated observations**
- Isolated temporary headless Chrome profile at 390px (not the owner's existing Chrome/profile), with a published **Dev** product added to an ephemeral browser cart to expose Checkout. No order submission, real customer data or payment action.
- Typed publicly known `1600 Amphitheatre Parkway` into displayed `#billing_address_1`. The WooPayments/native WooCommerce UI returned **5 real address suggestions**, first being `1600 Amphitheatre Parkway, Mountain View, CA, USA`. WordPress.com provider search endpoint returned HTTP 200.
- Selected the first `.woocommerce-address-suggestions .suggestions-list > li` candidate. `/woo/address-autocomplete/select` returned HTTP 200, autofilling these exact **Dev browser DOM** values:
  - `billing_address_1 = 1600 Amphitheatre Pkwy`
  - `billing_city = Mountain View`
  - `billing_state = CA`
  - `billing_postcode = 94043`
  - `billing_country = US`
  - `billing_address_2 = \"\"` (remains editable).
- Manual fallback: intercepted and deliberately aborted a subsequent autocomplete search request. Browser field continued to accept the **fictitious** string `123 Imaginary Lane`, with address, city and apartment not disabled or readonly; one intercepted request, expected test-only network failure.
- Triggered WooCommerce `updated_checkout`: native suggestions widget count under the billing address remained **1 → 1**, no duplicate.
- Responsive synthetic viewports 390px, 500px and 1280px: the visible Checkout contained its address fields with document scrollWidth equal to viewport width (no global horizontal overflow).
- Local screenshots captured only on the authorized Windows Dev device at temp paths: `%TEMP%\\byl-p4-uat-390.png`, `%TEMP%\\byl-p4-uat-500.png`, `%TEMP%\\byl-p4-uat-1280.png`. They were not deployed to WWW and are not yet a real-device M14 Owner sign-off.
- One intermittent later repeat did not display suggestions in its short wait, while prior runs definitively returned 5 real suggestions and successful selection. Monitor search reliability in Owner UAT; do not claim a statistical success rate from these spot tests.

**Gate result**
- P4 core functional automated UAT **PASS**: real suggestions, selection/autofill, manual fallback during injected provider failure, no duplicate widget after checkout refresh, and basic responsive overflow check.
- **Not covered / pending:** real iPhone/mobile browser Owner UAT (M14 preferred); keyboard and screen-reader testing; unsupported-country switching; full postal validation/shipping-rate persistence; provider uptime/frequency/limit/charges; performance; checkout delivery/payment/review end-to-end regression. These cannot be called PASS.
- Dev setting remains **yes** to allow Owner UAT. Keep a manual entry fallback. A customer address query is handled by WooPayments' remote autocomplete service; no separate BYL Google API key was added.
- The PR stays **Draft**; no code merge or WWW deploy. P5 gateway sandbox review remains separately gated by merchant-product eligibility and Live authorization.
