# WWW Native Woo Shipping + FX — Read-only Admin Evidence (2026-10-22)

**Source:** Authorized China Mac, authenticated existing Google Chrome browser, brand-new dedicated Woo settings tab, WooCommerce native WordPress Settings pages via ordinary GET navigation and read-only JavaScript DOM inspection. **NO WWW SSH, no plugin installed, no WP settings saved, no checkout/order/payment action, no GitHub main merge or WWW deployment.** This is a source/contract evidence ledger, NOT a release authorization.

## 1. Actual WWW zone methods / instance values

WooCommerce Shipping Zones page lists five zone IDs: fallback 0 (Rest of World), Hong Kong 1, selected high-cost locations 3, extended US/other locations 4, main US/Asia 5.

| Actual Woo Zone | Free instance | Free `requires` | `min_amount` | `ignore_discounts` (checked?) | Flat instance | Flat `cost` | `type` | `tax_status` |
| --- | --- | --- | ---: | --- | --- | ---: | --- | --- |
| 0 Rest of World | `free_shipping:1` | **`either` (amount OR eligible free shipping coupon)** | 69 | **no** (after coupon) | `flat_rate:3` | 20 | `class` | taxable |
| 1 Hong Kong | `free_shipping:2` | empty (= no requirement) | 0 | no | **none** | — | — | — |
| 3 selected | `free_shipping:6` | `min_amount` | 169 | no | `flat_rate:5` | 30 | `order` | taxable |
| 4 extended | `free_shipping:8` | `min_amount` | 129 | no | `flat_rate:7` | 20 | `class` | taxable |
| 5 US/Asia | `free_shipping:9` | `min_amount` | 69 | no | `flat_rate:10` | 10 | `class` | taxable |

**Key blocker:** The live WWW Zone 0 Free#1 is `either`, not the strict `min_amount` in the Dev source / draft production contract. This can allow approved free-shipping coupons to waive the amount. DO NOT silently flip to `min_amount`; BYL49 / shipping coupon exception must receive a reviewed decision. `ignore_discounts = no` for all existing minimum-qualified methods means coupons are taken into account where the amount rule applies.

### WWW native Flat shipping class rows

| Flat instance (zone) | `class_cost_932` | `class_cost_1874` | `class_cost_1939` | `no_class_cost` |
| --- | ---: | ---: | ---: | --- |
| #3 (zone0) | 0 | 10 | 30 | 0 |
| #5 (zone3) | 0 | 10 | 20 | empty |
| #7 (zone4) | empty | 0 | 10 | empty |
| #10 (zone5) | empty | 0 | 20 | empty |

The class additions differ per zone and may stack with Flat base depending on calculation type. They are NOT the approved final charge per special shipment and must not be casually added to the promised $30. Product/quantity-specific Woo rate logic must enforce **total $30 per special package** and **PILLOW $30 per unit**, while ordinary regional shipping remains native.

## 2. Actual shipping classes and product IDs
- `932` — Fast Discreet Shipping (`fast-discreet-shipping`), 0 assigned products.
- `1874` — $10 Heavy-packages (`heavy-packages`), 0 assigned products.
- `1939` — Extra $30 Shipping (`extra-30`), **4 assigned products**.

Clicked the native **product-count** read-only list for class `1939`; exact WWW rows:
- `post-38047` — **PILLOW**, SKU `pillow`.
- `post-32251` — **SAKURA**, SKU `MW35`.
- `post-9153` — **BDSM RED BONDAGE KIT**, SKU `VM465A`.
- `post-9145` — **BDSM BONDAGE KIT - GREEN**, SKU `VM467A`.

The hardcoded **parent product IDs** match Dev source; however shipping class `1939` groups two **different fee semantics**. Do not apply "$30 per class" as a one-size fee: the 3 non-PILLOW products share $30/shipment; PILLOW is $30/**unit**.

## 3. BYL49 coupon and other promotion rule
The WooCommerce/Smart Coupons admin listing search `post_type=shop_coupon&s=BYL49` returned **"No coupons found"** in that particular native coupon search. This does NOT establish absence of another campaign/Andy Commerce BYL49 rule or disabled/hidden coupon. Therefore BYL49 exceptional behavior remains a **separate review** before modifying Zone0 `either`, and no code was removed. No coupon was created, changed or redeemed.

## 4. Multi-currency WWW (actual YayCurrency UI)
- YayCurrency active/admin visible; default Woo base is **USD**; five currencies offered: **USD, EUR, GBP, AUD, CAD**.
- **Checkout in different currency = ON**, including all five rows enabled.
- **Auto select currency by countries = ON**; UK → GBP, Australia → AUD, Canada → CAD (as displayed); EUR also enabled for checkout.
- **Update exchange rate automatically = ON**.
- A current WordPress admin notice reports **YayCurrency Pro license expired** and requests renewal for updates; do NOT treat this as proof the currency-switching runtime is broken.
- **Additional same-day read-only Plugins UI confirmation:** `yaycurrency-pro/yay-currency.php` is **active, Version 3.2.1**. The user never authorized a plugin update or license renewal, and the audit-only tab was closed. Vendor changelog's 3.3.3 shipping-class currency correction and 3.3.4 `Order` shipping type correction are later than the installed version. Treat both EUR/GBP/AUD/CAD and selected USD shipping conversion on this installation as pending real runtime UAT, not automatically wrong or correct. The source candidate's version-floor guard is now ` >=3.3.5` AND a separately reviewed FX bridge; no production update occurred.
- Current source-only candidate checks `get_woocommerce_currency() === 'USD'` — this is inadequate alone to verify the customer's YayCurrency selected/checkout currency. A separately reviewed effective-currency integration and FX conversion UAT across **EUR/GBP/AUD/CAD** is REQUIRED. Until proven, WWW enable is NO-GO. No plugin updates/license changes authorized.

### Native WooCommerce Shipping settings (global options)

Read-only GET of `WooCommerce → Settings → Shipping → Shipping settings`:

| WP/Woo setting | WWW checked value |
| --- | --- |
| `woocommerce_enable_shipping_calc` — show Cart shipping calculator | **no** |
| `woocommerce_shipping_cost_requires_address` — hide rates until address entered | **no** |
| `woocommerce_shipping_hide_rates_when_free` — hide Flat rates if Free exists | **no** |
| `woocommerce_shipping_debug_mode` — bypass shipping-rate cache | **no** |

The Woo rate calculation cache is therefore normally active. The production candidate's **runtime-specific package SHA marker** is important: a Woo session can otherwise reuse a pre-release Free Shipping quote without invoking `woocommerce_package_rates` again. Woo core defines package-based cached rates in `WC_Shipping::calculate_shipping_for_package`, and recent Woo has a configurable package-hash ignored-fields hook. Future WWW runtime UAT must prove that the candidate's custom marker is not ignored by any installed theme/plugin integration, and that a change to shipping-zone settings/currency causes a new quoted rate without order creation.

## R4-B subsequent source-only update (the WWW admin settings above are unchanged)

The independently reviewed Dev WooCommerce 11.2.1 `WC_Shipping_Free_Shipping::is_available()` checks `WC()->cart->get_coupons()` objects using `is_valid() && get_free_shipping()`, then combines that boolean with the discounted minimum under `either`. PR #156's unconnected production candidate now models this **native Woo behavior** without asking Owner to change the Rest of World Free #1 setting. Under-threshold ordinary items can qualify with a real valid Free Shipping coupon; arbitrary `BYL49` code text does not itself grant free freight; special packages and PILLOW units remain paid. Offline test coverage **136 PASS**. Native WWW Andy Commerce BYL49 behavior is not independently established and stays a release risk.

The active WWW currency switcher permits checkout in **USD, EUR, GBP, AUD and CAD**. YayCurrency's public 2026 changelog specifically includes shipping class-conversion fixes (3.3.3) and **shipping calculation type Order** fixes (3.3.4). This is materially relevant because Zone3 Flat#5 uses type `order`. **WWW installed YayCurrency version is not verified in this document**; do not presume compatibility, auto currency conversion correctness or available updates based on vendor documentation alone. See https://wordpress.org/plugins/yaycurrency/ and https://docs.yaycommerce.com/yaycurrency/configurations/checkout-options .

A **real Dev Woo 11.2.1 core** read-only `WC_Shipping::get_package_hash` probe confirmed the candidate's custom configuration marker changes the native shipping cache hash on OFF→ON and on settings-marker change; Woo's ignored `subtotal` field did not. This is stronger than pure PHP mocks but is **not** end-to-end shipping session, multi-currency or native Checkout UAT.

Checkout PR #142 is still Draft and not mergeable at its latest observed head. The Checkout PHP's display-only "locked free shipping" helper skips Dev-only PILLOW, but it does **not** yet recognize future WWW special/pillow package markers and could create misleading ghost free options. Future production integration must skip exceptional WWW packages in that visual helper, preserving genuine Woo rates and Owner-approved one-line free-shipping marketing text. No Checkout shared file was overwritten for this source-only update.

### Additional public security evidence (not a WWW site edit)

Published CVE-2026-16058 concerns `yaycurrency` before 3.3.5 (Dokan integration unauthenticated information disclosure; advisory 2026-08-19). Source: https://opencve.alliance.unm.edu/cve/CVE-2026-16058 . Whether the separately installed **Pro** 3.2.1 package is affected is not independently verified, but a conservative shipping production candidate now uses **3.3.5** as its source-only minimum. This supersedes earlier `>=3.3.4` prerequisite; the actual WWW Pro installation has NOT been upgraded, and the version gate does NOT replace five-currency selected-checkout and tax/rate verification.

## 5. Production-safe gates / next actions
1. Preserve existing WWW settings; treat source ledger as **read-only**, not a request to save/change settings. No WWW SSH ever.
2. Production candidate must fail closed on `either` rule conflict, or explicitly implement a separately reviewed free-coupon exception; DO NOT silently waive fees for the 3 special SKUs or PILLOW.
3. A Woo rate implementation must isolate exactly 4 product IDs, split special/pillow/ordinary packages, ignore varying class cost addends when computing the exception's **final** freight, preserve destination-specific minimums and native Woo selected rates. Add tests for Zone0 `either`, Zone3 `order` vs Zone0/4/5 `class`, four parent IDs.
4. Verify actual Woo session shipping-rate hash/cache invalidation upon OFF→ON and when options change, without real payment/order or clearing production caches.
5. Create a separately approved production-source activation and rollback UAT including YayCurrency checkout converter and exact Owner version. **No current WWW write permission** and no R4 code should be called production-ready yet.

## 6. Sources and evidence policy
This document records values actually rendered in the existing Owner-authenticated WooCommerce/YayCurrency WP admin pages **on 2026-10-22**. These are live administrative observations, not independent backend database-option digests. Do NOT compute a WordPress raw-option SHA256 from UI fields or infer that hidden plugin options were fully audited. If settings drift, re-inspect before a future release. Only WWW source was read; no product/order settings mutated.
