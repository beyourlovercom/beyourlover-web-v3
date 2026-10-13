# M5 Cart Drawer — Unconfirmed $10 shipping fallback bug · Dev-only fix · 2026-10-12

## Owner evidence / observed behavior

Owner-provided screenshot: Dev Cart Drawer `?byl_cart_drawer_preview=1`; TARA S2 tara ×2 = $158, Skull Man ×1 = $96; item subtotal **$254**. Banner says `Shipping options depend on your delivery address` yet shipping line showed **$10** and order total **$264** without an entered customer shipping address.

## Confirmed root cause (Dev read-only)

- Woo option `woocommerce_default_customer_address=base`; Store API anonymous empty cart returns shipping address country `US`, state `CA`, city and postcode blank; `has_calculated_shipping=false` before a real destination.
- Woo zone 5 `1. Zone 1 - $10/ $69 for Free` has enabled `flat_rate` **$10** and `free_shipping` min **$69**. Its fee matches the screenshot, but without a confirmed shipping destination it cannot be advertised as definitive.
- Previous Cart V2 JS copied `cart.totals.total_shipping` directly into the Shipping line and `cart.totals.total_price` into both Order total/footer even while its banner warned that address was not known.
- Existing Woo native zone thresholds and the M4 Dev Canary policy were **NOT** changed by this fix.

## Correct intended behavior

| Destination/Woo state | Cart shipping | Order total | M5 progress |
| --- | --- | --- | --- |
| Anonymous, only Woo base country/state, no postcode/city/street | Calculated at checkout | Calculated at checkout | hidden |
| Authenticated Woo customer with a saved usable shipping address and **one** Woo-confirmed selected shipping rate | Actual Woo selected shipping amount, including $0 when genuinely free | Actual Woo calculated total | eligible to show based on server authority |
| Guest enters a usable delivery address through checkout/session | As for confirmed saved address | As for confirmed saved address | as for confirmed address |
| Valid destination but Woo shipping calculation incomplete, zero/multiple rate selections or packages | Calculated at checkout | Calculated at checkout | hidden |

Login is **not** a requirement for free shipping. Standard Woo customer/session data can supply an earlier saved shipping address; this JS reads the returned Store API `shipping_address` and does not query personal profile data directly. Owner should confirm this end-to-end using an actual logged-in Dev account.

## Implementation

- Only `theme/beyourlover-v3/assets/js/cart-drawer.js` was changed on Dev: `hasConfirmedDestination()` requires a valid country and either a postcode, or both city and street. A default shop-base `US/CA` alone is insufficient. `confirmedShipping()` additionally requires Woo `has_calculated_shipping===true` and one package/selected actual Woo rate.
- If unconfirmed, render subtotal and discount normally, `Shipping: Calculated at checkout`, `Order total: Calculated at checkout` and nonnumeric footer instead of stale $10/$264. Suppress M5 progress and any free-shipping claim until the same authority contract passes.
- No Woo native checkout/cart backend filter changes, no database option writes, no customer address changes, no fake cart submissions/orders/payments, no WWW.

## Verification and exact Dev rollback

- Tests: `scripts/test_cart_drawer_shipping_address_authority.js` **7/7 PASS** (screenshot replica $254 subtotal + Woo $10 fallback, saved profile address, free rate, missing Woo calculations); `scripts/test_cart_drawer_m5_progress.js` **13/13 PASS**; original `scripts/test_cart_drawer_storeapi_mock.js` PASS; JS syntax PASS.
- Isolated actual Chrome browser with **mocked GET cart fixture only**, no remote cart POST: M390 `width=390 / scrollWidth=390` and PC1440 `width=1440 / scrollWidth=1440` both rendered **Shipping and Order total = Calculated at checkout** and preserved $254 subtotal; no erroneous $10 or $264, no promotions. See `scripts/manual_cart_no_address_shipping_visual_probe.js`.
- Dev-only single JS SHA256 **before** `13fd664addb31605e92d7df24964071468ceef2d22ae5bd95517fbce7fa8c6d1`; **after** `3ee54247d4c191672098dad52d3e58a80cf69fb6a622e1f0186b43c7f82a68d3`. Off-webroot rollback: `/home/u247343654/domains/dev.beyourlover.com/.m5-shipping-fallback-rollback-20261012/cart-drawer-before-address-fix.js`.
- Postdeploy Dev GET homepage 200, Cart Drawer 200, Checkout GET 200; Drawer script src uses versioned filemtime to bust asset cache.
- GitHub [Draft PR #80](https://github.com/beyourlovercom/beyourlover-web-v3/pull/80): source-only integrated with Theme T4/Cart V2 & N6 bridge, **not merged**; exact-head GitHub Foundation CI still requires verification before merge. Do not deploy WWW.
- Native `/cart/` page and real logged-in account restoration were not modified or independently UATed by this scoped Drawer JS fix.

## Owner follow-up

Hard-refresh the Dev Drawer `https://dev.beyourlover.com/?byl_cart_drawer_preview=1` with the same existing items. Verify shipping and order total no longer claim $10/$264 without destination. Separately sign in with an account having a saved address to check normal Woo auto-prefill, then confirm Woo's selected rate and resulting shipping total. Do not place a real order.
