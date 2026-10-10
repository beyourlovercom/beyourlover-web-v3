# BeYourLover Theme OS V1 — T2.0 runtime audit and T2.1 bootstrap
Date: 2026-10-10. Baseline main: `8223932b740012037f0e5f30a5d4b7322d35721a`. T1 prerequisite PR #65 head: `1fc6bb491119c3ded613562539258dc807c40a21`.

## Scope and authority
Stacked code-only branch derived from T1 because PR #65 is still unmerged and requires separate Owner exact-head approval. This is **not** a Dev/WWW release, Theme activation, payment test, or permission change. Theme remains Version 0.1.0. Do not touch G2/P13 Permit, G5 3DS Windows tests, Woo database, mail or ERP.

## Actual old eager PHP include order (functions.php)
1. inc/components.php (foundation)
2. inc/shell-profiles.php (shell)
3. inc/homepage.php (homepage)
4. inc/archive.php (catalog)
5. inc/product.php (product)
6. inc/product-modules.php (product)
7. inc/checkout.php (checkout)
8. inc/thank-you.php (postpurchase)
9. inc/cart-experience.php (cart)

An additional `inc/free-shipping-v2-policy.php` (shipping-ui) is included *after* the main wp_enqueue_scripts callback, not with the first nine. This exact placement is intentionally retained. Ordering is not the same as the static module dependency graph; **do not reorder by manifest**. All nine modules were eager-loaded on every request before T2.1; T2.1 still eager-loads all nine. The new Theme-internal loader has no runtime routing, lazy loading, plugin, feature flag, or new Woo contract.

## Hooks registered by eager includes
- shell-profiles.php: admin_init, admin_menu, add_meta_boxes, save_post
- archive.php: woocommerce_product_query (priority 20)
- product.php: wp
- checkout.php: woocommerce_checkout_fields, wp, woocommerce_form_field_args; removes classic Woo coupon form on wp, with original priority
- thank-you.php: wp_enqueue_scripts (order-received presentation styles)
- cart-experience.php: woocommerce_after_cart (priority 30), woocommerce_cart_totals_before_order_total (priority 5)
- free-shipping-v2-policy.php: wp_ajax_byl_v3_fs_lookup, wp_ajax_nopriv_byl_v3_fs_lookup

The Theme entrypoint additionally registers after_setup_theme support, after_switch_theme legacy menu mapping, wp_enqueue_scripts, request (priority 8), redirect_canonical, permalink_manager_filter_redirect (priority 20), page_link, gettext, and body_class. Do not promote any visual code into payment authority.

## Current asset inventory (T2.2 code extracted verbatim)
Global sequence: style.css → tokens.css → components.css → component-library.css → shell.css → motion.css; shell.js deferred. Route-specific conditional loading:
- front_page: homepage.css
- shop/product taxonomy: archive.css
- product: product.css, product.js deferred
- free-shipping/free-shipping-guide: free-shipping.css, free-shipping-v2.js deferred
- track-your-order: track-order.css
- cart: cart.css, cart-focus.css, cart-experience.js, cart.js (depends on wp-data and wc-blocks-data-store; coupon native Store API URL and wc_store_api nonce localized; defer cart.js)
- checkout, excluding order-received: checkout.css, checkout-shell.css, checkout.js (jquery; wc-checkout if registered/enqueued; native Store API URLs and wc_store_api nonce localized; defer)
- order-received: thank-you.php independently enqueues presentation CSS.

All asset versions use the existing filemtime fallback to Theme Version; change to fingerprinting / cache busting only under a separate parity test. Cart and Checkout bundles are **already** route scoped in the baseline; no global removal is necessary.

## T2.1 change / rollback
Entrypoint replaces only the ordered nine require_once statements with one `inc/theme-os-bootstrap.php` require. The loader uses the exact same nine statements and preserves the position before all subsequent global hooks. The tenth policy include stays in place. Validate manifest owner registration, order contract, PHP lint and preexisting offline regression in Foundation CI. Rollback is to revert this isolated T2 branch commit(s), never change the live Dev Theme without backup, exact SHA, independent Owner approval and M390/PC1440 UAT.

## T2.2–T2.7 gates
1. T2.2: DONE — same existing wp_enqueue_scripts callback moved byte-for-byte to inc/theme-os-assets.php, included at its original functions.php position; six existing source-sensitive legacy tests retargeted with unchanged semantic assertions
2. T2.3: audit Woo native adapter for duplicated inventory/total/checkout logic. Any business-logic move is a separate PR outside behavior-preserving refactor
3. T2.4: DONE at code/CI level — nine-route isolated PHP fixture verifies handles, dependency order, localized coupon endpoints, nonce, no Cart/Checkout bleed, and conditional wc-checkout fallback; existing Foundation CI and deterministic ZIP tests remain in place
4. T2.5: isolated WP + Woo functional rehearsal with no mail/payment/outbound/data writes, comparing M390/PC1440 Cart/Checkout/Postpurchase
5. T2.6: separately approved Dev-only exact-hash backup and runtime Owner UAT; do not disturb G5 permit or 3DS test
6. T2.7: exact-head review + Owner Final PASS then separate merge authorization, no production deployment

**Known conflicts:** Cart Drawer draft PR #63 adds new Cart assets and must register their owner after T1 merge; Cart CSS PR #61 and G3/G5 branches are independent. Avoid their functions.php/CI edits being silently overwritten.

Status as of 2026-10-10: T2.0–T2.4 code-level audit, loader/asset extraction and Foundation CI PASS; see separate T2 Woo-native audit. Independent exact-source comparison confirms the original nine-require block and 4,946-character asset callback are preserved verbatim. T2.5 independent WordPress 7.1.3 / WooCommerce 11.2.1 rehearsal has now passed 80 browser checks plus 12 native Cart/Coupon/receipt checks at M390 and PC1440; see `THEME-OS-T2-ISOLATED-WP-WOO-EVIDENCE.md`. T2.6 still requires a distinct Dev-only exact-hash backup/permit gate and Owner UAT. No Dev/WWW install or payment test took place.
