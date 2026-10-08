# Checkout Address Autocomplete V1 — P0 Provider Contract

Status: DESIGN ONLY / NO RUNTIME ENABLEMENT
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
