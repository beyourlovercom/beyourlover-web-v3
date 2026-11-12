# BF2026 Promotion UI · R0–R3 (Dev-only candidate)

## Status (2026-11-12)
**R0 baseline: DONE. R1 source reconnaissance: DONE. R2 source candidate: DONE. R3 Dev live UAT: BLOCKED on dedicated 007 Windows backend access.**

Dev runtime uses `black-friday-2026` **0.2.3-dev-rc4**, WooCommerce, Andy Campaign 0.1.0, and BYL V3 Cart Drawer. Dev's old gift README differs from the Owner's newer US/non-US policy. **Do not copy, change or advertise those old gift thresholds.** WWW is a different Shoptimizer theme, outside this task's deploy permission.

## Integration into EXISTING plugin (no new standalone plugin)
Copy `includes/class-byl-bf2026-promotion-ui.php`, `assets/promotion-ui.css`, and `assets/promotion-ui.js` under the existing `black-friday-2026/` plugin root. Add only this bootstrap to its main file, after other includes:

```php
require_once __DIR__ . '/includes/class-byl-bf2026-promotion-ui.php';
BYL_BF2026_Promotion_UI::boot();
```

Then use dedicated **007 Windows** to open **Dev WordPress backend** and configure **Settings → BF2026 Promotion UI**. All settings default OFF. Announcement, product, classic Woo cart, V3 drawer, and sticky badges have independent switches. This is display-only: no discount, coupon, gift, session, order, payment or ERP mutation.

Campaign schedule defaults to **2026-11-10 00:00–2026-12-01 00:00 America/Los_Angeles** (exclusive end). Public read-only REST status `/wp-json/byl-bf2026/v1/promotion-status` returns current server time and end timestamp with no-store, so a cached HTML page cannot continue a false timer. JavaScript hides all badges if status fails or expires.

Woo's true `get_price()`, `get_regular_price()`, and `is_on_sale()` determine badge. Percent is **floored**, simple products only. Variable parents show only a generic `BLACK FRIDAY DEAL` until a real variation is known. Gift SKU/category and zero/negative price lines do not get badges. Promotions based only on an order coupon are **not** labeled as a per-item discount. V3 drawer uses Woo Store API **GET only** keyed by cart-item key; no handler overrides. Sticky badge is decorative only, using an existing product-sale badge as truth.

## Acceptance gates
1. PHP lint + Node syntax + pure calculation cases
2. Dev backend exact-version readback through 007 Windows, then display-only activation
3. Desktop 1440px and mobile 390px; simple/variable/gift SKUs; price/coupon/FX; native Woo cart + V3 drawer; sticky behavior; expiry; keyboard/reduced-motion
4. Owner functional + visual PASS. **No WWW deployment in R0–R3.**

**Known gap:** Woo Blocks cart item rows do not use classic PHP price filters. Inspect the actual Dev cart rendering before claiming cart row parity. A future Woo Blocks adapter must use verified native item data, not DOM price parsing.

**Connectivity blocker:** 007 Windows reaches Dev homepage (HTTP 200), but `/wp-admin/` and `/wp-login.php` returned HTTP 404 during 2026-11-12 preflight. Do not use China Mac, generic Windows or US Mac mini WordPress backend as a substitute. R3 Dev live browser status remains BLOCKED until access is restored on 007 Windows.