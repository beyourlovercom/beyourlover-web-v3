# Black Friday 2026 · Promotion UI V1 (R0–R3)

## Latest status — 2026-11-12

- **R0 reference audit:** DONE — Lovense-style announcement, discount badge, cart, sticky patterns; original BYL implementation, no third-party proprietary source copied.
- **R1 Dev baseline:** DONE — active `black-friday-2026` `0.2.3-dev-rc4`, Andy Campaign 0.1.0, WooCommerce 11.2.1, BYL Website V3, native cart and custom Cart Drawer.
- **R2 module:** DONE — standalone display-only class, CSS and JS; all modules initially OFF. Pure PHP calculations, LA date validation, JS syntax and isolated Chrome 1440/390 PASS.
- **R3 Dev deployment:** **DONE, technical browser PASS** on `dev.beyourlover.com`. Runtime plugin now reports `0.2.3-dev-rc4-promo-ui1`. Source installed via exact baseline SHA guard with plugin folder backup outside public_html. Dev promotion UI switches saved ON through **007 Windows** authenticated WordPress backend. REST status `/wp-json/byl-bf2026/v1/promotion-status` returned 200 and `active=true`.
- **R3 real browser:** Desktop 1440 and mobile 390 PASS, no horizontal overflow; `/clock/` product detail price $139 → $96 and badge 30%; product cards 38% and 22% examples; Woo native Add to cart created a Dev-only cart session; existing BYL Cart Drawer line displayed 30%; fallback `/cart/` classic Woo cart displayed 30%; no order/payment tests performed.
- **Known scope gap:** Dev product template currently has **no existing sticky add-to-cart surface**, so the existing-surface adapter has **not** been live-tested there. Do not claim sticky functionality PASS. Adding a new floating purchase button would be a separate UX change requiring design / native-function approval.
- **Owner visual signoff:** PENDING. **WWW:** NOT TOUCHED. PR remains DRAFT / not merged.

## Integration design

These files extend the existing plugin, they are **not** an installable independent WordPress plugin:
- `includes/class-byl-bf2026-promotion-ui.php`
- `assets/promotion-ui.css`
- `assets/promotion-ui.js`
- `tests/test-promotion-ui.php`
- `tools/apply-dev-r3.py` (exact source-SHA guard, backup/readback, Dev-only)

The existing plugin main PHP adds:

```php
// BYL_BF2026_PROMOTION_UI_R3: presentation-only module, switches default OFF.
require_once __DIR__ . '/includes/class-byl-bf2026-promotion-ui.php';
BYL_BF2026_Promotion_UI::boot();
```

The deployed plugin header version is `0.2.3-dev-rc4-promo-ui1`. The patch was applied to the Dev baseline after verifying original `black-friday-2026.php` SHA256 `fb57d51946b1e9c74a7892f5494c5015c04fc601a75b4cd996465f4c13f0b6a3`. Deployed main readback SHA256: `71ec8da0452d3c32aad75bffc95b62a4df36ec3b90651e87ba6b41223c560dab`. Deployment script retains a complete rollback copy in a protected non-public directory.

## Safety model

- This is **display-only**. No hooks write Woo product price, cart, coupon, gift, order, payment or ERP state.
- Top banner and sale decorations use the fixed Los Angeles-time campaign schedule, and a read-only no-store REST endpoint for current server time. UI hides on status failure/expiry.
- Woo regular/effective price determines integer floor percentage; never conflate Woo coupon discount with a product-sale badge.
- Variation parent does not claim a made-up percentage. `gift` SKU/category and non-positive-price items are excluded.
- Custom Drawer reads Woo Store API **GET** cart by item key; does not replace Woo's native cart controls.
- Live Dev presentation settings are enabled only for test/Owner UAT; user-specific cart sessions from browser testing do not submit real orders.
- Historical Dev plugin README has older gift qualification thresholds. Do **not** use it to overwrite current Owner-frozen US/non-US policy; no gift logic altered in this PR.
- No WWW installation/activation/merge approval is implied. WWW Shoptimizer theme requires independent integration and testing.

## Remaining gates

1. Owner confirms Dev page visual and placement. Start with `https://dev.beyourlover.com/clock/` and `https://dev.beyourlover.com/cart/`.
2. Decide whether to **add a new** Dev Sticky Add to Cart component; current plugin only decorates one if supplied by existing theme.
3. Reconcile latest WWW black-friday plugin baseline and gift code before any WWW candidate. Do not replace current WWW installed plugin with this Dev package.
4. Separate Owner production approval and WWW rollback plan after exact-head checks.

## Testing receipts

- PHP lint of source and deployed main: PASS
- Pure calculation: 11 discount cases, LA end, bad date, disabled defaults: PASS
- JS syntax: PASS
- Isolated browser active/inactive + gift exclusion + phone 390 overflow: PASS
- **Live Dev Chrome**: desktop+mobile banner and product pricing badge: PASS
- **Live Dev Woo session**: native Add to cart → V3 Drawer badge → classic Cart badge: PASS
- Sticky existing surface: NOT PRESENT / NOT VERIFIED
- Owner visual: PENDING
