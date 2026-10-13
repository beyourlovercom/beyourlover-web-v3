import assert from 'node:assert/strict';
import fs from 'node:fs';
const read=p=>fs.readFileSync(new URL('../'+p,import.meta.url),'utf8');
const css=read('theme/beyourlover-v3/assets/css/notification-compact-cart.css');
const php=read('theme/beyourlover-v3/inc/notification-compact-cart.php');
const manifest=JSON.parse(read('theme/beyourlover-v3/theme-os.json'));
const boot=read('theme/beyourlover-v3/inc/theme-os-bootstrap.php');
const ci=read('.github/workflows/foundation-ci.yml');
assert(boot.includes("require_once get_stylesheet_directory().'/inc/notification-compact-cart.php';"));
assert(manifest.modules.notification.kind==='presentation');
for(const f of ['assets/css/notification-compact-cart.css','inc/notification-compact-cart.php'])
  assert(manifest.modules.notification.files.includes(f),'Theme OS missing '+f);
assert(css.includes('body.byl-cpn-cart-preview .byl-cart-runtime'));
for(const k of ['.woocommerce-message','.woocommerce-info','.woocommerce-error'])assert(css.includes(k));
for(const k of ['13px/1.38','padding:7px 10px 7px 34px','width:16px','min-height:44px','overflow-wrap:anywhere'])
  assert(css.includes(k),'Missing compact design '+k);
for(const bad of ['.byl-toast','.byl-field-error','.byl-checkout-error','.byl-cart-promotion__error',
 '.payment_box','text-overflow:ellipsis','overflow:hidden','display:none!important'])
 assert(!css.includes(bad),'Too-broad CSS: '+bad);
for(const k of ["dev.beyourlover.com","REQUEST_METHOD","wp_unslash","is_cart","wp_enqueue_scripts","body_class","byl_v3_notification_cart_bridge_enabled"])
 assert(php.includes(k),'Missing gate '+k);
for(const bad of ['wc_add_notice','wc_print_notice','WC()->cart','wp_insert_post','wp_redirect','woocommerce_payment_complete'])
 assert(!php.includes(bad),'Woo mutation risk '+bad);
assert(ci.includes('php scripts/test_notification_compact_cart.php'));
console.log('CPN_C1_CART_CSS_CONTRACT_PASS');
