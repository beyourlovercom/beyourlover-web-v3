# BYL Checkout Native Woo Controls — Country/State P0
2026-10-16. Owner reported Country / Region cannot be selected on Dev Drawer. Functional UAT FAIL.

## Architecture
This Checkout is NOT WooCommerce stock UI. It renders the actual Woo Classic checkout shortcode, fields, validation and gateway controls inside a custom BYL PHP/CSS/JS Drawer. The native Woo fields are preserved, but custom overlay can break native interactions.

## Root cause verified from actual Dev Woo source
1. Native Woo country-select.js initializes selectWoo for country and state selects.
2. Woo selectWoo.full.js appends opened dropdowns to the document body by default.
3. Woo select2.css sets dropdown z-index 1051. BYL checkout-drawer.css sets Drawer overlay z-index 1200.
4. Thus the popup opens behind the BYL Drawer and cannot receive clicks.
5. Isolated browser uses read-only GET-fetched *actual Dev installed Woo scripts*, not a fake replacement selector. On M320/M390/PC1440, pointer hit-testing reproduced blocked popups before the fix. After scoped source CSS, Germany and China choices work; native state options and change events update; the Bavaria province native popup can be clicked. No real cart/order/payment/session mutation.

## Minimal CSS runtime patch
Only existing theme/beyourlover-v3/assets/css/checkout-drawer.css changes:

    body.byl-checkout-drawer-mode > .select2-container.select2-container--open {
      z-index:1250!important;
    }

Do NOT reinitialize Woo SelectWoo, create new country selectors, change Woo country data or override gateway scripts. This narrow CSS rule belongs in Drawer **foundation**, so disabling/rolling back Visual V2 cannot reintroduce broken native country select. It only affects the active native Woo dropdown attached to body within Drawer mode; plain checkout fallback is unchanged.

## Verification and release gate
- Source guard: scripts/test_checkout_native_woo_controls_p0.py.
- Manual actual Woo vendor browser test: scripts/manual_checkout_native_selectwoo_overlay_p0.cjs (requires publicly fetched Woo jQuery, SelectWoo, country-select.js and select2.css stored in private temporary directory BYL_NATIVE_WOO_ASSETS, not committed).
- Synthetic only screenshots contain no personal/customer/cart data.
- After newly approved one-runtime-file Dev-only Canary: real populated-cart visual/click test Country US/China/Germany, State/Province reset and options, Woo shipping rate refresh, address autocomplete fallback, keyboard selection, mobile and desktop focus, Shipping → Payment back, native field validation.
- Track B alone owns payment gateway activation/eligibility. No real checkout orders, payments, database changes, emails, WWW deploy, main merge or READY restarts.
- The previous PR122 exact-head authorization does NOT cover this new head. Require fresh Owner exact-head approval plus Dev baseline SHA, verified offwebroot backup, rollback, local CI exception if GitHub billing still blocks CI.

ROADMAP: Root cause PASS → source and native-vendor isolated tests PASS → Draft PR → new Dev-only Canary PENDING → real Owner functional/visual UAT PENDING → integrated Checkout Final HOLD.
