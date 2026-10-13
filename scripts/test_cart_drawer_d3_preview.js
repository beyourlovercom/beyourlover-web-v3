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
assert.match(functions,/inc\/theme-os-bootstrap\.php/);
assert.match(read('inc/theme-os-bootstrap.php'),/inc\/cart-drawer\.php/);
assert.match(php,/giftEnabled' => false/);
assert.match(php,/wc_get_checkout_url\(\)/);
assert.match(php,/wp_footer/);
assert.match(php,/\}, 5\);/,'Drawer markup must be inserted before wp_print_footer_scripts at wp_footer priority 20');
assert.match(php,/require_once get_stylesheet_directory\(\)\.'\/inc\/notification-cart\.php'/, 'N6 adapter included after Cart Drawer registration');
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
// D3.4: empty-cart icon and 3-personalized + monthly fallback candidates.
const empty=php.match(/<div class="byl-drawer__empty-icon"[\s\S]*?<\/div>/)?.[0]||'';
assert.match(empty,/<svg viewBox="0 0 24 24"/,'empty cart must use actual cart SVG');
assert.doesNotMatch(empty,/♧/,'never show decorative club glyph');
assert.match(css,/\.byl-drawer__empty-icon svg/,'empty cart SVG styling');
assert.match(php,/byl_v3_cart_drawer_recent_ids/,'Woo session viewed ids');
assert.match(php,/woocommerce_recently_viewed/,'reads existing first-party recent cookie');
assert.match(php,/byl_v3_cart_drawer_monthly_best_seller_ids/,'uses real 30-day paid-unit sales');
assert.match(php,/SUM\(item\.product_qty\) DESC/,'orders by actual unit count');
assert.match(php,/orders\.status IN \('wc-processing','wc-completed'\)/,'excludes pending, failed, refunded orders');
assert.match(php,/30 \* DAY_IN_SECONDS/,'rolling 30 days, not lifetime total_sales');
assert.doesNotMatch(php,/orderby['"]\s*=>\s*['"]popularity/,'forbid lifetime popularity fallback');
assert.match(php,/\$product->is_type\('simple'\)/,'non-simple items must link to View options');
assert.match(php,/>View options<\/a>/,'support recently viewed variation parent without unsafe direct add');
assert.match(php,/nocache_headers\(\)/,'avoid caching personalized guest markup');
assert.match(php,/data-drawer-recommendations/);
assert.match(php,/data-drawer-mobile-recs/);
assert.match(php,/data-drawer-coupon-form/);
assert.match(php,/data-drawer-items/);
assert.match(php,/data-drawer-footer/);
// M1 Campaign/banner + service reminder slots: no visible promotions in current Dev.
assert.match(php,/function byl_v3_cart_marketing_slots_enabled\(\): bool \{\s*return false;/,
  'marketing policy must fail closed in PHP, never be driven by a query/cookie/option');
assert.match(php,/'marketingEnabled' => false/,'marketing must be OFF in localized JS data');
assert.match(php,/<section class="byl-drawer__marketing" data-drawer-marketing-slots aria-label="Cart promotions" hidden>/,
  'marketing container must start hidden');
assert.match(php,/data-drawer-marketing-banner hidden/,'one banner slot reserved but hidden');
assert.match(php,/data-drawer-marketing-note hidden/,'one service text slot reserved but hidden');
assert.match(css,/\.byl-drawer__marketing\[hidden\],\.byl-drawer__marketing \[hidden\]\{display:none!important\}/,
  'OFF slots must have no footprint on mobile or desktop');
assert.match(js,/if \(elements\.marketing\) elements\.marketing\.hidden = true/,
  'Cart runtime cannot activate marketing without separate gated policy');
assert.doesNotMatch(php,/byl_v3_cart_marketing_slots_enabled\(\)\s*===?\s*true.*get_option/s,
  'marketing must not be wired to an uncontrolled WP option');
assert.match(js,/rate\.selected === true/,'selected shipping source is authoritative');
assert.match(js,/String\(rate\.rate_id \|\| ''\)\.startsWith\('free_shipping:'\)/,
  'shipping rate IDs normalized; no synthetic free threshold');
assert.match(php,/data-drawer-checkout>SECURE CHECKOUT →<\/a>/,
  'native Checkout GET anchor remains untouched');

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
assert.match(js,/return operation\.then\(result=>Boolean\(result && Array\.isArray\(result\.items\)\),\(\)=>false\)/, 'mutation success must be backed by a valid accepted Woo cart response');
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
