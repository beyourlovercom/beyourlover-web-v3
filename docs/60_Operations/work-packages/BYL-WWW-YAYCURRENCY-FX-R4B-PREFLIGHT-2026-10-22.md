# R4-B · Five-currency Shipping Acceptance and Trusted Pro Artifact Gate

**Date:** 2026-10-22  
**Status:** Source-only UAT contract; **real YayCurrency Pro runtime UAT NOT DONE, WWW HOLD**.
**Owner boundaries:** No GitHub main merge; no WWW SSH; no changes to live WordPress or production plugins; no real checkout order/payment; no restarting READY devices; no Dev plugin install without distinct authorization.

## 1. Verified environment facts
- BeYourLover WWW currently runs **active YayCurrency Pro 3.2.1**, with USD, EUR, GBP, AUD, CAD checkout enabled. Its old release is before currency shipping-class conversion updates and `Order`-type fixes; Zone 3 uses Flat Rate mode `order`.
- Dev WooCommerce currently has **no YayCurrency installed**. The Dev Store API Checkout is intentionally blocked by `403 byl_dev_sandbox_checkout_closed`; do **not** bypass the sandbox.
- Source-only production candidate remains at `scripts/release-candidates/www-shipping-production-candidate-v1.php`, **not wired into theme or plugin startup**, and rejects non-USD checkout unless a separate reviewed vendor integration is eventually provided. YayCurrency version floor is now **>=3.3.5** as a conservative prerequisite, **not** a safety guarantee or completion of FX UAT.

## 2. Candidate ZIP found, but NOT trusted/installed
An existing **`yaycurrency-pro_v3.3.5.zip`** was discovered in authorized China Mac Downloads (read-only inventory). Its ZIP archive integrity test passed, and `yaycurrency-pro/yay-currency.php` declares Version `3.3.5`. The originating download URLs, however, point to **third-party distribution domains (oyzet.com / zifury.com)** rather than a YayCommerce account or WooCommerce.com. This does not prove the code is malicious or legally unauthorized, but the archive's integrity check and internal version string **DO NOT authenticate publisher provenance**.

**No installation, extraction into a WordPress plugin directory, execution, upload, transfer to Dev, licensing change, or use in runtime testing has occurred.** This third-party ZIP must **not** be the production or Dev UAT deployment source. Do not commit or share the package bytes. Any future runnable Pro artifact should come from a verifiable vendor/authorized purchase source:
- YayCommerce dashboard: https://yaycommerce.com/dashboard/#downloads
- Vendor's update instructions: https://docs.yaycommerce.com/manual/getting-started/update-plugin
- YayCommerce Pro product: https://yaycommerce.com/downloads/yaycurrency-pro-woocommerce-multi-currency-switcher/
- WooCommerce.com purchase/download channel is a supported alternative if this is where the entitlement was acquired.

The operator must separately verify license rights and release provenance, and authorize an **isolated Dev-only install** before any plugin code is executed in WordPress. No such permission is assumed now.

## 3. Verified official public source: critical hook order
The official public **YayCurrency Lite v3.3.5** source was downloaded and **only statically read** on the US Mac mini from `downloads.wordpress.org`, not installed. In `includes/Engine/FEPages/WooCommerceCurrency.php` it registers:
- `woocommerce_package_rates` at **priority 10** for `change_shipping_cost()`, converting native Flat Rate/class costs and recalculating taxes;
- `woocommerce_shipping_free_shipping_instance_option` at **priority 20**, with selected-currency minimum conversion;
- currency helpers `detect_current_currency()` and `get_apply_currency()` and vendor filter `yay_currency_convert_price`.

Our production candidate's `byl_www_ship_rates` filter is configured at **priority 100200**, later than the publicly documented currency converter. Therefore a future naive implementation that simply writes `30.0` as a Woo rate **after** currency conversion risks displaying 30 GBP/EUR/AUD/CAD instead of converted USD $30. **Do not remove the fail-closed non-USD gate or enable multi-FX as a shortcut.** This hook order is grounded in the publicly distributed Lite source; independently verify that the **official licensed Pro** installed for UAT uses compatible hooks and priority before approving any bridge.

Official documentation: https://docs.yaycommerce.com/yaycurrency/configurations/developer-zone/available-yaycurrency-hooks

## 4. Exact fixture matrix: 180 source-only cases
The new `scripts/test_www_shipping_fx_acceptance_matrix_v1.py` and Foundation CI verify coverage for five Woo zones × five customer-selected currencies, with the agreed **USD base freight** as the oracle:
- Zone 0: ordinary Flat $20 under after-coupon $69; Free when qualified.
- Zone 1 (HK): ordinary Free without minimum.
- Zone 3: ordinary Flat $30 under $169; Free at/above.
- Zone 4: ordinary Flat $20 under $129; Free at/above.
- Zone 5: ordinary Flat $10 under $69; Free at/above.
- Three special SKUs: **$30 per special parcel**, including two units/mixed special SKU assortment.
- PILLOW: **$30 per unit**, hence 2×=$60.
- Mixed ordinary+$30 special+2 pillows: under threshold, Flat for zone ordinary + $90; at threshold, $90.
- Valid native Free Shipping coupon can waive ordinary Zone0 freight only, with special/PILLOW mandatory paid rates remaining $90.

All of these are **USD base values, not guessed exchange rates**. The future actual selected-currency oracle must obtain the real vendor checkout-specific conversion values, check currency-specific rounding, and compare taxes and displayed Checkout shipping to the same underlying USD fee.

The source-only candidate already has **167** rate/eligibility/safety assertions (including vendor-priority fixture cases); the new matrix additionally covers **180** expected cart/currency/zone combinations. Neither is live FX or real Checkout proof.

## 5. Execution checklist after a trusted Pro release is available and Dev install authorized
1. Snapshot isolated Dev plugin list and restore method, confirm target is `dev.beyourlover.com`. Do not change WWW, do not restart READY devices, and keep payment/order sandbox closed.
2. Verify ZIP authenticity/release/licensing. Install **on approved isolated Dev only**, not production. Audit selected currency detector and Woo hooks/priority *as actually registered*.
3. Run source matrix for each of USD/EUR/GBP/AUD/CAD and each five shipping zones. Test at/under threshold after coupon, a genuine Woo Free Shipping coupon in Zone0, 1×/2× specials, 1×/2× PILLOW and mixed.
4. Verify a **USD $30 special charge converts exactly once** into the selected currency; rates must not be double-converted or left as 30 in every currency. Verify native Woo package-rate cache invalidation when changing currency, destination, coupon, or enabled shipping method.
5. Tax re-computation and the Zone3 `order` Flat type require explicit fixture comparison, not just a currency symbol. Preserve original sale tax context and check rounding.
6. Native Checkout sandbox remains restricted: display-only anonymous flow is permissible; **do not bypass any 403** or submit an order/payment to claim UAT. If meaningful checkout validation cannot be done safely, leave that check **BLOCKED**.
7. Only after Owner-reviewed real results, draft a new reviewed FX bridge or shipping fee conversion adapter and get distinct exact-head main/production authorization. Until then feature ON is prohibited.

## 6. Latest governance
- PR #156: isolated WWW source-only candidate, **no theme bootstrap**.
- PR #157: Checkout ghost-free exception guard with latest PR #156 source; full CI success at its prior reviewed exact HEAD.
- PR #142: independent owner Checkout Draft; never overwritten or merged by this R4-B track.
- WWW shipping settings, YayCurrency plugin, subscriptions, orders, payments remain unchanged.

**Conclusion: source-level FX risk is identified and gated; trustworthy Pro artifact and explicit Dev installation permission are prerequisites to closing live multi-currency UAT.**
