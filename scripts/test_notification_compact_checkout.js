import assert from 'node:assert/strict';
import fs from 'node:fs';
const get=p=>fs.readFileSync(new URL('../'+p,import.meta.url),'utf8');
const css=get('theme/beyourlover-v3/assets/css/notification-compact-checkout.css');
const php=get('theme/beyourlover-v3/inc/notification-compact-checkout.php');
const bootstrap=get('theme/beyourlover-v3/inc/theme-os-bootstrap.php');
const manifest=JSON.parse(get('theme/beyourlover-v3/theme-os.json'));
const workflow=get('.github/workflows/foundation-ci.yml');
assert(bootstrap.includes("require_once get_stylesheet_directory().'/inc/notification-compact-checkout.php';"));
assert(manifest.modules.notification.files.includes('assets/css/notification-compact-checkout.css'));
assert(manifest.modules.notification.files.includes('inc/notification-compact-checkout.php'));
assert(manifest.modules.notification.kind==='presentation');
assert(css.includes('body.byl-cpn-checkout-preview #main-content.byl-commerce-content > .woocommerce'));
for (const type of ['.woocommerce-message','.woocommerce-info','.woocommerce-error'])assert(css.includes(type),type);
for (const t of ['13px/1.38','padding:7px 10px 7px 34px','width:16px','min-height:44px','overflow-wrap:anywhere'])
 assert(css.includes(t),t);
for (const forbidden of ['.byl-checkout-error','.byl-promo-error','.payment_box','.byl-toast',
 '.woocommerce-NoticeGroup-checkout','.byl-cart-runtime','max-height:','text-overflow:ellipsis','display:none!important'])
 assert(!css.includes(forbidden),'broad/truncated CSS: '+forbidden);
for(const token of ["dev.beyourlover.com","REQUEST_METHOD","is_checkout()","is_checkout_pay_page()","is_order_received_page()","is_wc_endpoint_url('order-pay')","wp_doing_ajax","wp_enqueue_scripts"])
 assert(php.includes(token),'missing gate '+token);
for(const forbidden of ['wc_add_notice','wc_print_notice','wp_insert_post','wp_redirect','WC()->cart','woocommerce_checkout_process'])
 assert(!php.includes(forbidden),'business mutation '+forbidden);
assert(workflow.includes('scripts/test_notification_compact_checkout.php'));
console.log('CPN_C2_CHECKOUT_VISUAL_CONTRACT_PASS');
