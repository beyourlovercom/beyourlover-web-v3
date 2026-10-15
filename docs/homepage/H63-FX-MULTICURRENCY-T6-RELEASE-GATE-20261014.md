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
