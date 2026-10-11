'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const dir = path.join(__dirname,'../theme/beyourlover-v3');
const read = p => fs.readFileSync(path.join(dir,p),'utf8');
const php = read('inc/cart-drawer.php');
const js = read('assets/js/cart-drawer.js');
const css = read('assets/css/cart-drawer.css');
const functions = read('functions.php');

assert.match(php,/function byl_v3_cart_drawer_preview_enabled\(\): bool/);
assert.match(php,/dev\.beyourlover\.com/);
assert.match(php,/byl_cart_drawer_preview/);
assert.match(php,/get_stylesheet_directory\(\)/);
assert.match(functions,/inc\/cart-drawer\.php/);
assert.match(php,/giftEnabled' => false/);
assert.match(php,/wc_get_checkout_url\(\)/);
assert.match(php,/wp_footer/);
assert.match(php,/\}, 5\);\s*$/,'Drawer markup must be inserted before wp_print_footer_scripts at wp_footer priority 20');
assert.match(php,/role="dialog" aria-modal="true"/);
// Owner's three-step UX contract: visual status now, real Checkout state later.
const progress=php.match(/<nav class="byl-drawer__steps"[\s\S]*?<\/nav>/)?.[0] || '';
assert.match(progress,/aria-label="Checkout progress"/);
assert.match(progress,/aria-current="step"/);
assert.equal((progress.match(/class="byl-drawer__step(?: is-active)?"/g)||[]).length,3,'exactly three progress steps');
assert.equal((progress.match(/<svg /g)||[]).length,3,'use semantic shopping bag / delivery truck / card SVGs');
assert.match(progress,/>Cart<\/span>/);
assert.match(progress,/>Shipping<\/span>/);
assert.match(progress,/>Payment<\/span>/);
assert.doesNotMatch(progress,/[♧⌖▤]/,'discard confusing placeholder symbols');
assert.doesNotMatch(progress,/<(?:a|button)\b/,'future Checkout steps must not pretend to be clickable today');
assert.match(css,/\.byl-drawer__steps\{height:74px;display:grid;grid-template-columns:repeat\(3,minmax\(0,1fr\)\)/);
assert.match(css,/\.byl-drawer__step\.is-active::after/);
assert.match(php,/data-drawer-recommendations/);
assert.match(php,/data-drawer-mobile-recs/);
assert.match(php,/data-drawer-coupon-form/);
assert.match(php,/data-drawer-items/);
assert.match(php,/data-drawer-footer/);
assert.match(php,/wp_localize_script/);
assert.match(js,/Object\.prototype\.hasOwnProperty\.call\(config,'giftEnabled'\)/, 'Drawer must require explicit gift feature policy flag');
assert.match(js,/\[false,'',0,'0'\]\.includes\(config\.giftEnabled\)/, 'WordPress-localized false must not prevent Drawer preview initialization');
assert.match(php,/wc\/store\/v1\/cart/);
assert.match(js,/Array\.isArray\(cart\.items\)/);
assert.match(js,/currency_code/);
assert.match(js,/Intl\.NumberFormat/);
assert.match(js,/total_price/);
assert.match(js,/shipping_rates/);
assert.match(js,/free_shipping:/);
assert.match(js,/update-item/);
assert.match(js,/remove-item/);
assert.match(js,/add-item/);
assert.match(js,/apply-coupon/);
assert.match(js,/remove-coupon/);
assert.match(js,/pendingQuantity\.get\(item\.key\)/);
assert.match(js,/document\.addEventListener\('keydown'/, 'Esc closes an open Drawer even after removing focused item');
assert.match(js,/if\(root\.hidden\) return;/, 'Global keydown must not run when Drawer closed');
assert.match(js,/const operation = mutationQueue\.then\(\(\)=>request\(endpoint,data\)\)/, 'serial Woo mutation queue required');
assert.match(js,/mutationQueue = operation\.catch\(\(\)=>\{\}\)/, 'failed requests must not poison subsequent retries');
assert.match(js,/return operation\.then\(\(\)=>true,\(\)=>false\)/, 'mutation success must reach caller');
assert.match(js,/if\(applied && field\) field\.value=''/, 'failed coupon must stay in editable field');
assert.match(js,/if \(state\) render\(state\)/, 'rejected Woo mutation must restore authoritative quantity UI');
assert.match(js,/const safeHref = value =>/, 'prevent non-HTTP product href injection');
assert.match(js,/Network updates are serialized/, 'discard stale Woo actions during in-flight requests');
assert.match(php,/gift offers are not active/);
assert.doesNotMatch(js,/\/checkout\/order-received|place_order|wc_store_api_checkout|process_payment/i);
assert.doesNotMatch(php,/wc_create_order|wp_insert_post|add_option|update_option|update_post_meta|register_activation_hook/i);
assert.match(css,/@media\(max-width:900px\)/);
assert.match(css,/\.byl-drawer__aside\{display:none\}/);
assert.match(css,/width:100vw/);
assert.match(css,/--byl-drawer-brand:var\(--byl-brand,#9b3749\)/, 'canonical brand token shared with Theme VI');
// D3.3 mobile LELO-inspired reading space; desktop layout must remain unchanged.
assert.match(css,/\.byl-drawer__cart\{height:100%;min-height:0;overflow-y:auto;/,
  'Mobile cart, not the inner content, owns scrolling so the header can scroll out');
assert.match(css,/\.byl-drawer__scroll\{flex:1 0 auto;min-height:auto;overflow:visible;/,
  'Mobile content must not be an independently scrolling pane');
assert.match(css,/\.byl-drawer__footer\{position:sticky;bottom:0;z-index:2;/,
  'Mobile Checkout CTA should float at the bottom of the cart scrollport');
assert.match(css,/\.byl-drawer__footer \.byl-drawer__total,\.byl-drawer__footer p\{display:none\}/,
  'Mobile fixed action should not show duplicate total or explanation');
assert.match(css,/\.byl-drawer__footer a\[data-drawer-checkout\]\{padding:15px;pointer-events:auto;/,
  'Floating CTA remains interactive through the transparent footer');
assert.match(css,/env\(safe-area-inset-bottom\)/, 'iOS footer honors safe area');
assert.match(css,/:has\(\.byl-drawer__coupon input:focus\)/, 'Focus on coupon field must not be hidden by floating CTA');
assert.match(php,/class="byl-drawer__totals" data-drawer-totals/, 'Woo authoritative totals stay in body');
assert.match(php,/data-drawer-checkout>SECURE CHECKOUT/, 'Checkout stays native GET navigation');
assert.match(css,/overscroll-behavior:contain/);
console.log('PASS D3 Dev-only opt-in Drawer preview: Woo Cart authority, no gift launch, mobile/fullscreen, no checkout mutation');
