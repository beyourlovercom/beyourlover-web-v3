import assert from 'node:assert/strict';
import fs from 'node:fs';
const read=path=>fs.readFileSync(new URL('../'+path,import.meta.url),'utf8');
const base='theme/beyourlover-v3/';
const os=JSON.parse(read(base+'theme-os.json'));
const bootstrap=read(base+'inc/theme-os-bootstrap.php');
const assets=read(base+'inc/theme-os-assets.php');
const workflow=read('.github/workflows/foundation-ci.yml');
const source=read('scripts/test_theme_os_contract.py');
const noticeTest=read('scripts/test_notification_ux_v1.js');
const routes=['cart','checkout','orderpay'];
assert.equal(os.modules.notification.kind,'presentation');
assert.equal(os.modules.notification.files.length,20);
assert(noticeTest.includes('manifest.modules.notification.files.length,20'));
let last=bootstrap.indexOf("'/inc/cart-drawer.php'");
assert(last>=0,'original bootstrap chain present');
for (const route of routes){
  const php='inc/notification-compact-'+route+'.php';
  const css='assets/css/notification-compact-'+route+'.css';
  for (const p of [php,css])assert(os.modules.notification.files.includes(p),'Theme OS owns '+p);
  const needle="require_once get_stylesheet_directory().'/"+php+"';";
  assert.equal(bootstrap.split(needle).length-1,1,'one eager guarded module '+route);
  const index=bootstrap.indexOf(needle);assert(index>last,'original hooks stay first '+route);last=index;
  assert(source.includes('"'+php+'"'),'Theme OS expected boot chain '+route);
  const p=read(base+php);
  const style=read(base+css);
  assert(p.includes('dev.beyourlover.com') && p.includes('REQUEST_METHOD') && p.includes("'GET'"),
    'Dev/GET strict route gate '+route);
  assert(p.includes('byl_compact_notice_preview') && p.includes("'"+route+"'"),
    'exact opt-in query token '+route);
  assert(p.includes('is_admin') && p.includes('wp_doing_ajax'),'admin/ajax excluded '+route);
  assert(style.includes('body.byl-cpn-'+route+'-preview'),'route selector required '+route);
  for(const bad of ['.byl-toast','.payment_box','.byl-checkout-error','text-overflow:ellipsis','overflow:hidden','display:none!important']){
    assert(!style.includes(bad),'forbidden selector/action '+bad+' in '+route);
  }
  for(const dangerous of ['wc_add_notice','wc_print_notice','wc_create_order','wp_insert_post','wp_redirect(']){
    assert(!p.includes(dangerous),'business write forbidden '+route);
  }
  assert(!assets.includes(css),'no automatic global enqueue '+route);
  assert(workflow.includes('php scripts/test_notification_compact_'+route+'.php'), 'missing PHP CI '+route);
  assert(workflow.includes('node scripts/test_notification_compact_'+route+'.js'),'missing JS CI '+route);
}
for(const route of ['checkout','orderpay']){
  const style=read(base+'assets/css/notification-compact-'+route+'.css');
  assert(style.includes('width:100%'),'full width notice '+route);
  for(const [,selector,body] of style.matchAll(/([^{}]+)\{([^{}]+)\}/g)){
    if(!body.includes('width:16px')||!body.includes('height:16px'))continue;
    const arms=selector.split('\n').map(v=>v.trim().replace(/,$/,'')).filter(v=>v.startsWith('body.'));
    assert.equal(arms.length,2,'both native selector arms '+route);
    assert(arms.every(v=>v.endsWith('::before')),'icon pseudo must not style banner '+route);
  }
}
const c2=read(base+'notification-checkout-c2-preview.php');
const c3=read(base+'notification-orderpay-c3-preview.php');
for(const raw of [c2,c3]){
  // PHP may encode literal single quotes as backslash-escaped quotes.
  const page=raw.replaceAll(String.raw`\'`,"'");
  assert(page.includes("connect-src 'none'")&&page.includes("form-action 'none'"),
    'visual preview must deny network connections and form submissions');
  assert(!page.includes('wp-load.php')&&!page.includes('<form'),'isolated visuals only');
}
assert(c3.includes('You are paying for a guest order. Please continue with payment only if you recognize this order.'));
assert(workflow.includes('scripts/test_compact_persistent_c1c3_integrated.js'));
console.log('COMPACT_PERSISTENT_C1C3_INTEGRATED_OFFLINE_PASS: 3 isolated GET-only routes, 20 files, ordered boot, no runtime business writes');
