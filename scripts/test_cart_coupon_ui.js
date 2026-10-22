'use strict';
const fs = require('fs');
const vm = require('vm');
const assert = require('assert');
const path = require('path');
const theme = path.resolve(__dirname, '../theme/beyourlover-v3');
const source = fs.readFileSync(path.join(theme,'assets/js/cart.js'),'utf8');
const css = fs.readFileSync(path.join(theme,'assets/css/cart.css'),'utf8');
function run(status, blockStatus) {
  const bodyClasses = new Set();
  const root = {
    className: '', setAttribute() {},
    innerHTML: '',
    querySelector() { return null; },
  };
  let child = null;
  const target = {
    querySelector(selector) {
      if (selector === '.byl-cart-promotion') return child;
      if (selector === '[data-byl-native-promotion]') return status ? {
        getAttribute() { return JSON.stringify(status); },
      } : null;
      return null;
    },
    appendChild(node) { child = node; },
    insertBefore(node) { child = node; },
  };
  const document = {
    readyState: 'complete',
    body: { classList: { add(name) { bodyClasses.add(name); }, remove(name) { bodyClasses.delete(name); } }, contains() { return true; } },
    querySelector(selector) { return selector.includes('.cart_totals') ? target : null; },
    createElement(name) { assert.equal(name,'section'); return root; },
  };
  const wp = {
    data: {
      select() { return { getCartData() { return { extensions: blockStatus ? {'byl-shipping-promotion':blockStatus} : {} }; } }; },
      subscribe() {},
    },
  };
  const context = {
    window: { BYLShippingPromotionCart: {}, wp, wc: { wcBlocksData: { cartStore: {} } } },
    document, wp,
    MutationObserver: class { observe() {} },
    console,
  };
  vm.runInNewContext(source, context);
  return { markup: root.innerHTML, enhanced: bodyClasses.has('byl-cart-coupon-enhanced') };
}
const headline = 'Free shipping from $69 (special items excluded)';
function headlineOnce(markup, label) {
  assert(markup.includes('<a href="/free-shipping/">'+headline+'</a>'), label+' contains policy link');
  assert.equal((markup.match(/Free shipping from \$69/g)||[]).length,1,label+' renders exactly one hint');
  assert(!/Add \$[\d.]+ more for free shipping|Free shipping applied to your order|Congrats!/.test(markup),
    label+' never creates competing campaign claims');
}
const unlocked = run({active:true,state:'automatic_unlocked',has_code:false,applied_code:''});
assert(unlocked.enhanced, 'working promo UI must retain single coupon form');
headlineOnce(unlocked.markup, 'unlocked');
assert(unlocked.markup.includes('Promo / Coupon Code'), 'coupon label preserved');
assert(unlocked.markup.includes('Enter coupon code'), 'coupon input preserved');

const applied = run({active:true,state:'require_code_unlocked',has_code:true,applied_code:'BYL49'});
headlineOnce(applied.markup, 'applied');
assert(applied.enhanced,'applied promo still uses working single coupon form');
assert(applied.markup.includes('BYL49 applied'), 'applied code preserved');
assert(applied.markup.includes('Remove'), 'coupon removal preserved');

const noData = run(null);
headlineOnce(noData.markup,'missing promotion data');
assert(!noData.enhanced,'native Woo coupon remains available without promotion extension');

const iowa = run({active:true,state:'below_threshold',threshold:69,subtotal:59,remaining:10,
  has_code:false,applied_code:'',zone_id:5},
  {active:true,state:'automatic_unlocked',threshold:0,remaining:0});
headlineOnce(iowa.markup,'Iowa below threshold');
assert(!iowa.markup.includes('role="progressbar"'),'no old destination-dependent progress bar');
assert(iowa.markup.includes('Enter coupon code'),'coupon still works below threshold');

const alaska = run({active:true,state:'below_threshold',threshold:129,subtotal:60,remaining:69});
headlineOnce(alaska.markup,'Alaska $129 threshold');
assert(!alaska.markup.includes('$129'),'no country rate tables in one-line message');

const selectedFree = run({active:true,state:'free_shipping_available',threshold:69,subtotal:80,remaining:0});
headlineOnce(selectedFree.markup,'free eligible but not selected');

const special = run({active:true,state:'special_shipping_applies',threshold:69,subtotal:0});
headlineOnce(special.markup,'special shipping charged');

const staleBlocksOnly = run(null,{active:true,state:'automatic_unlocked',threshold:0});
assert(!staleBlocksOnly.enhanced,'classic Cart never uses stale Blocks coupon');
headlineOnce(staleBlocksOnly.markup,'stale Blocks');

const inactive = run({active:false});
assert(!inactive.enhanced,'inactive promotion must preserve Woo coupon fallback');
headlineOnce(inactive.markup,'inactive promotion');

assert(css.includes('.woocommerce-cart.byl-cart-coupon-enhanced .woocommerce-cart-form .coupon'),
  'native coupon remains hidden only when enhanced code UI works');
assert(!/\.woocommerce-cart\s+\.woocommerce-cart-form\s+\.coupon\s*\{\s*display\s*:\s*none/.test(css),
  'native coupon must not be always hidden');
console.log('PASS Cart one-line shipping policy UX: active, coupon, disabled, country thresholds, specials');
