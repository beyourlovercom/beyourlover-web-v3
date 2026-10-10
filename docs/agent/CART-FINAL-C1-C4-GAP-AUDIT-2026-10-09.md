# BYL V3 Cart C1–C4 — Dev-only Gap Audit & final UAT
Baseline GitHub main: `9e393711dc0d5045b1bc8edd0a03ab5dbe528aab`. Owner approved audit, bounded Cart changes and read-only UAT up to Owner gate. No WWW, orders, payments, Woo/ERP sync, production migration or business-data writes. Do not reopen G2.

## C1 evidence matrix
| Feature | Evidence | Scope / disposition |
|---|---|---|
| Empty Cart | Independent guest Chrome desktop 1440px HTTP 200, empty notice, no fake totals | PASS real read-only |
| Mobile M390 | Initial one HTTP 503 Maintenance; independent isolated retest GET /cart/ 200 twice and no overflow (390px width). Treat 503 as intermittent, not proven fixed | PASS recheck; operational watch |
| Cart / Checkout authority | Woo shortcode `[woocommerce_cart]` / `[woocommerce_checkout]`, Dev Woo page IDs 12/13 | PASS static + live IDs |
| Quantity +/- | Native Woo form submit; original JS decorators only initial DOM, lost after Woo fragment update | C2 FIX |
| Qty min/max/racing | Source originally scheduled twice per click, though last timer cancelled first | C2 FIX + debounce |
| Header cart count | PHP-rendered badge could stay stale after native AJAX update/removal | C2 FIX |
| Remove | Woo native `.product-remove a.remove` retained | code PASS; real interaction untested |
| Coupon | One enhanced entry; native fallback if policy context missing, Store API then Woo re-render | fixture PASS; live mutation untested |
| Inventory/variations | Woo authoritative, JS respects qty min/max/step | fixture PASS; real inventory not mutated |
| Shipping / totals | Native Woo shipping package & selected rates, Andy Commerce policy; $59/$69 zone fixture | fixture PASS, no live cart writes |
| Recommendations | Woo cross-sell/upsell/category simple published in-stock only, excluded cart items | contract PASS; populated real UI pending |
| Empty Checkout | Woo may redirect guests with empty Cart to Cart | expected, not payment defect |
| G2 | Checkout Store API POST must remain 403, no Place Order | deployment gate |

## C2
Retain Woo core quantity and remove form/nonce authority. Debounce rapid
clicks/manual qty edits to one native Woo `requestSubmit(update_cart)`;
no custom REST or totals calculation. Reinstall once on Woo
`updated_wc_div` / `updated_cart_totals` or replaced input nodes.
Never submit orphaned form; refresh header Cart badge from latest native Woo
quantities including zero. No plugin, theme shell or Checkout changes.

## C3
Native Woo shortcode Cart → native Woo shortcode Checkout, no parallel
totals/coupon/shipping calculation. Cart promotion uses actual matched
country/zone, current Woo shipping packages and selected rate. User's
Checkout G3/G5 branch untouched. Empty Checkout redirect is Woo-owned.

## C4 no-business-write verification
Run `node scripts/test_cart_quantity_refresh_c1c4.js`,
`node scripts/test_cart_checkout_handoff_c1c4.js`, the existing
Cart/Shipping/Checkout focused tests and Foundation CI.
Independent isolated headless Chrome: desktop 1440px + M390 real empty
Dev Cart GET; browser-local one-item fixture tests rapid quantity clicks,
native-submit interception, Woo form replacement, header badge refresh,
empty form, no 390px overflow; these are **fixtures, not live cart edits**.
Check Dev Store API Checkout 403 after replacing one JS file, and Owner
visual UAT. Full live nonempty Cart E2E (quantity/remove/coupon) is
prohibited by this approval and cannot be represented as PASS.

## Dev Deployment and merge gates
Main is single source. Backup the exact current `cart-experience.js` and
validate SHA before changing only this JS file. No Dev PHP, Woo settings,
DB, WWW or Production changes. Keep ready remote devices running. PR remains
unmerged until Owner UAT and separate main-only merge approval.
