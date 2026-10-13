import assert from 'node:assert/strict';
import fs from 'node:fs';
const read=p=>fs.readFileSync(new URL('../'+p,import.meta.url),'utf8');
const css=read('theme/beyourlover-v3/assets/css/notification-compact-orderpay.css');
const php=read('theme/beyourlover-v3/inc/notification-compact-orderpay.php');
const fixture=read('theme/beyourlover-v3/notification-orderpay-c3-preview.php');
const bootstrap=read('theme/beyourlover-v3/inc/theme-os-bootstrap.php');
const os=JSON.parse(read('theme/beyourlover-v3/theme-os.json'));
const ci=read('.github/workflows/foundation-ci.yml');
assert(bootstrap.includes("require_once get_stylesheet_directory().'/inc/notification-compact-orderpay.php';"));
for(const path of ['inc/notification-compact-orderpay.php','assets/css/notification-compact-orderpay.css','notification-orderpay-c3-preview.php'])
 assert(os.modules.notification.files.includes(path),'Theme OS missing '+path);
assert(os.modules.notification.kind==='presentation');
assert(css.includes('body.byl-cpn-orderpay-preview.woocommerce-order-pay'));
assert(css.includes('width:100%'),'full-width notice contract');
for(const [,selectors,body] of css.matchAll(/([^{}]+)\{([^{}]+)\}/g)){
  if(!body.includes('width:16px')||!body.includes('height:16px'))continue;
  const arms=selectors.split('\n').map(x=>x.trim()).filter(x=>x.startsWith('body.'));
  assert(arms.length===2,'both native notice arms must be styled');
  for(const arm of arms){
    assert(arm.replace(/,$/,'').endsWith('::before'),
      'icon sizing must never affect the notice container');
  }
}
for(const type of ['.woocommerce-error','.woocommerce-info','.woocommerce-message'])assert(css.includes(type));
for(const token of ['13px/1.38','padding:7px 10px 7px 34px','width:16px','min-height:44px','overflow-wrap:anywhere'])
 assert(css.includes(token),'Style missing '+token);
for(const token of ['is_checkout_pay_page','is_order_received_page','dev.beyourlover.com','REQUEST_METHOD','wp_doing_ajax','wp_enqueue_scripts','body_class'])
 assert(php.includes(token),'Gate missing '+token);
for(const unsafe of ['.payment_box','.byl-orderpay-address','.byl-checkout-error','.byl-toast','overflow:hidden','line-clamp','text-overflow:ellipsis','display:none!important'])
 assert(!css.includes(unsafe),'Unsafe CSS '+unsafe);
for(const unsafe of ['wc_add_notice','wc_print_notice','wp_insert_post','wp_redirect','woocommerce_payment_complete','WC()->'])
 assert(!php.includes(unsafe),'Unsafe PHP '+unsafe);
assert(fixture.includes('You are paying for a guest order. Please continue with payment only if you recognize this order.'));
for(const token of ['byl_orderpay_c3_visual','Content-Security-Policy',"connect-src 'none'", "form-action 'none'",'notification-compact-orderpay.css','data-density="compact"','data-density="original"'])
 assert(fixture.includes(token),'Fixture missing '+token);
for(const unsafe of ['wp-load.php','WC()->','<form','fetch(','XMLHttpRequest'])
 assert(!fixture.includes(unsafe),'Fixture unsafe '+unsafe);
assert(ci.includes('php scripts/test_notification_compact_orderpay.php'));
console.log('CPN_C3_ORDERPAY_CSS_AND_FIXTURE_PASS');
