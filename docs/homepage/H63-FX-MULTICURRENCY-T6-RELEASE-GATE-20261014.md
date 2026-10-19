# H6.3-FX — Currency / YayCurrency → V3 controlled release gate

**Status:** Read-only source and live WWW/Dev audit; specification-only Draft. Not a currency migration, not a payment or Production authorization. Owner decision pending. Parent #113, Homepage #76, Theme OS #66, Integration #87.

## Source-derived observations (2026-10-14)

- Live WWW mobile homepage HTTP 200 continues to load **YayCurrency Pro 3.2.1** assets (`yaycurrency-pro/src/styles.css`, `callback.min.js`, `script.min.js`, third-party and cache integrations). Actual Woo product HTML uses `.yay-currency-cache-product-id` and `.woocommerce-Price-currencySymbol` in examples of discounted prices. A product-displayed USD symbol is **not** proof that all enabled currencies or current switcher variants have been discovered.
- Live Dev homepage is standalone V3; its imported Footer renders a **static $ USD** label. It is **not** a working currency selector. Dev product Woo pricing and checkout cannot be assumed parity on the basis of homepage labels.
- Per `docs/agent/THEME-OS-V1.md`, Theme owns presentation, Woo/gateways own stock, tax, native cart and monetary totals; Andy Commerce owns campaign/shipping policies. The Theme must not calculate its own checkout/order total.
- Existing project baseline temporarily retains YayCurrency on WWW; do **not** uninstall it as part of this Homepage workstream.

## Missing read-only evidence before any code
1. Exact enabled currency and country matrix from authorized WWW settings (not infer from visible USD).
2. Native Woo base currency, YayCurrency exchange-rate provider, price rounding, coupon/free-shipping comparison currency, geo-detection, cache/session/cart behavior.
3. Checkout gateway authorization & settlement currency by WooPayments/Stripe/PayPal as configured, 3DS/returns/Order Pay rules; never place actual orders during this audit.
4. AffiliateWP commission, reporting and payout currency: avoid double conversion, precisely one authoritative source.
5. Subscription and product visibility with change in country, user account and persisted currency state.
6. WordPress/Theme V3 dependencies and actual Dev payment G5 revalidation.

## Decision alternatives (Owner/Commerce control)
**FX-A (recommended temporary release candidate):** Keep the proven WWW multi-currency implementation during old WWW operation and cut over only when replacement V3 money/affiliate integration is Owner-approved. Avoid portraying static Footer $ USD as equivalent.

**FX-B (eventual standalone V3 path):** Explicitly choose one Woo-owned base/order currency and validated gateway currency strategy, exchange rate/rounding source and customer display switcher. A standalone Theme switcher only reflects authoritative Woo totals; it does not invent its own FX/order total. Separate legal/affiliate accounting and migration UAT required.

## Readiness tests (all synthetic or read-only until independently authorized)
| Matrix | Gate |
| --- | --- |
| Product + sale/discount | Same authoritative native Woo sale amount, formatting, and settled currency |
| Cart + BYL49 and other thresholds | Discount-after-subtotal, shipping/tax boundaries and country rules preserved |
| Checkout + 3DS + gateway | No mismatch in pay amount, currency or order status under G2/G5 |
| AffiliateWP | No dual conversion, reproducible commission ledger, predictable payout |
| Refund/order pay/Thank You | Original order currency retained, exchange differences auditable |
| Cache / logged-in / guest | No cross-customer leaking selected currency or prices |
| Mobile M390/M14 + PC1440 | Accessible switcher only when authoritative service genuinely available |

**Fail closed:** no upgrade/deactivation of YayCurrency, no Woo/Stripe/WooPayments settings changes, no checkout order POSTs, no external mail, ERP changes, Dev/WWW deployment, DB write or PR merge. Formal Cart Final PASS + Checkout Final PASS + current G5 evidence still required for #87. No standalone Owner FX migration approval exists yet.


## FX0–FX4 execution contract — 2026-10-15 read-only checkpoint

**Verified baseline:** GitHub `main=1438768d919e6a848ae71c9a7ebfa3ceeafc80ea` (identical to prior handoff). FX PR remains documentation-only Draft; WWW is still legacy Shoptimizer+YayCurrency, Dev is standalone Theme V3. No monetary checkout parity has been established.

**Public WWW evidence only:** native switcher `select.yay-currency-switcher[name=currency]` exists; observed option IDs `25356,24406,24407,24408,24409` and page resource/DOM code strings `USD,EUR,GBP,AUD,CAD`. These are two separate observations, **NOT a verified one-to-one ID↔currency mapping, rate source, active country mapping, gateway currency list, order currency or affiliate reporting currency**. Default selector ID observed `25356`; currency code for that ID is unverified. Do not hardcode it.

### FX0 evidence collection (all read-only, owner-visible and dated)

| WP / vendor surface | Exact data required | Status |
| --- | --- | --- |
| WooCommerce > Settings > General | Base currency, price decimals/separators, shipping/tax defaults | Not collected from authenticated WWW settings |
| YayCurrency Pro > currencies/options | Per-option ID/code, enabled flags, order, assigned country, FX rate/provider/last refresh, rounding and fee, auto-detect policy | 5 public IDs found; backend mappings not collected |
| Currency switcher + session/cache | Guest/member persistence, geolocation/default, CDN cache keys, no mixed-currency fragments | Only public DOM existence verified; interaction not exercised |
| Woo / YayCurrency cart and checkout settings | Which exchange calculation owns cart, tax, shipping, BYL49 promotion threshold, coupon rounding | Unverified |
| WooPayments / Stripe / PayPal settings | Enabled method and supported transaction currencies; actual authorization vs settlement currency (no live payment tests) | Unverified |
| AffiliateWP and accounting | Commission event currency, rate/rounding source, payout currency and reporting ledger | Unverified |
| Order Pay / refunds / G5 | Original order currency, refund amount authority, retries/3DS, provider callback invariants | Contract only; no new test |

**Safety:** Read-only evidence must not be collected via WP options REST writes, checkout POST, cache or session mutation, plugin reconfiguration, or real order/affiliate/payment mutations. Authorized WP Admin settings inspection is needed; do not use guesses or infer values from frontend labels.

### FX1 invariant contract — one monetary authority per lifecycle

1. Product/catalog display: selected currency and price formatting are output of the authorized Woo/YayCurrency integration, not Theme-side arithmetic. A selector may render only when the source can supply verified amounts.
2. Cart: Woo cart session and Woo totals are authoritative; Andy Commerce policies evaluate the **post-discount, pre-shipping, pre-tax merchandise subtotal**, after verified currency normalization. BYL49 retains its locked policy and must never receive an unverified double-converted threshold.
3. Checkout: Woo checkout validation/rate/tax totals and the gateway-accepted payment currency must agree with one defined conversion and rounding path. A Theme display never converts a submitted amount independently.
4. Order creation / captures: authoritative persisted Woo order currency/line totals; record explicit original/converted/settled values and rates where applicable. Do not retroactively rewrite existing orders.
5. AffiliateWP: commission amount and currency derive from the same approved Woo order money snapshot, once; record any commission-to-payout conversion separately. Never apply YayCurrency again to a converted Woo amount.
6. Refund / Order Pay / Thank You / email: preserve original order currency and Woo authoritative monetary fields. Gate cross-currency refund handling with explicit finance and payment-provider acceptance.
7. Guest/account/cache: currency context cannot leak between shoppers or shared-page caches; browser UI reflects the actual session currency after switching, not stale HTML.
8. Fail closed: if no trusted currency service is available in V3, do **not** render a fake interactive `$ USD` switcher; do not enable currency-dependent checkout until approved money parity.

### FX2 synthetic test fixtures, **no real orders**

- USD/EUR/GBP/AUD/CAD are **candidate test codes based on the visible DOM, not a confirmed production configuration**.
- For every verified configured currency: a discounted and full-price product, one coupon, BYL49 boundary just below/at/above threshold, shipping Zone/Tax affected totals, native Woo gateway availability, refund/order-pay echo, and affiliate commission rounding.
- Run anonymous/member and simulated geo-selection with isolated fixtures; compare Woo authoritative totals vs themed labels. Ensure all exchange rates/rounding fixtures are explicitly **synthetic** until sourced from settings.
- Capture exact commit, settings snapshot (no secrets), fixture ID, expected/actual amount + currency, Woo and gateway authority, PASS/FAIL and 0 live writes.

### FX3 release decision

- **FX-A = default risk-limiting recommendation, not an Owner-approved final cutover:** do not remove YayCurrency from current WWW; V3 T6 remains HOLD until V3 can preserve monetary and affiliate parity in a validated, independent integration. If parity is absent, do not switch WWW theme just to meet visual schedule.
- **FX-B = migration project:** requires separately approved integration implementation, rate/rounding authority, Woo/Gateway/AffiliateWP validation, consumer-facing behavior, rollback and UAT. No new Theme rate engine.
- **Neither FX-A nor FX-B means that a static V3 USD label is acceptable for a live multi-currency checkout.**

### FX4 explicit exit conditions

Recorded authenticated WWW settings + financial/currency authority; read-only/synthetic matrix PASS; Cart Owner Final PASS; Checkout three-step Owner Final PASS; G5 evidence fresh for actual runtime; independently approved Dev-only scope, exact build and rollback; Owner FX decision; finally separate T6 WWW release permission. Until all are present: `FX=IN_PROGRESS`, `PR #116=DRAFT`, `#87=HOLD`, `T6=HOLD`.

**Current CI honesty:** hosted Foundation CI on old PR #116 head `fe492c6d...` concluded **failure** (run #38690501800). Failure is not green, and previously authorized independent CI exemptions for other PRs do not transfer. Rerun exact new head and seek explicit PR-specific independent CI exception only if needed. This is a document update only; no WWW/Dev, configuration, transaction, device or main change.
