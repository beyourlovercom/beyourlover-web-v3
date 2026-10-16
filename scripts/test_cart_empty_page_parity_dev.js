'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const root = path.join(__dirname, '..', 'theme/beyourlover-v3');
const read = name => fs.readFileSync(path.join(root, name), 'utf8');
const gate = read('inc/notification-cart.php');
const empty = read('inc/notification-cart-empty.php');
const drawer = read('inc/cart-drawer.php');
const css = read('assets/css/cart-empty-parity.css');
assert(gate.includes('function byl_v3_cart_empty_page_parity_enabled(): bool'));
assert(gate.includes('byl_v3_cart_drawer_runtime_enabled()'), 'Dev staging and hostname guard');
assert(gate.includes("$template_name !== 'cart/cart-empty.php' || !byl_v3_cart_empty_page_parity_enabled()"));
assert(gate.includes('if (!byl_v3_notification_cart_bridge_enabled()) { return; }'), 'N6 notice adapter remains independently gated');
assert(empty.includes("do_action('woocommerce_cart_is_empty')"));
assert(empty.includes("woocommerce_return_to_shop_redirect"));
assert(empty.includes("array_slice(byl_v3_cart_drawer_recommendations(), 0, 3)"), 'drawer rec authority');
assert(empty.includes('wp_kses_post($product->get_price_html())'));
assert(!empty.includes('WC()->cart->') && !empty.includes('wc_add_notice'));
for (const s of ['byl-drawer__empty-icon','Your cart is empty','Explore our best sellers and find something for you.','SHOP BEST SELLERS']) {
  assert(empty.includes(s) && drawer.includes(s), `drawer parity: ${s}`);
}
assert(css.includes('.woocommerce:has(.byl-cart-empty-parity)>.wc-empty-cart-message'), 'legacy empty notice hidden');
assert(css.includes('.woocommerce:has(.byl-cart-empty-parity)>.return-to-shop'), 'legacy shop CTA hidden');
assert(css.includes('body.woocommerce-cart:has(.byl-cart-empty-parity) .byl-cart-heading'), 'non-empty carts keep heading');
console.log('PASS: Dev-only cart empty page parity: gate, legacy suppression, recs, semantic parity, nonempty isolation');
