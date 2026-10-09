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
const unlocked = run({active:true,state:'automatic_unlocked',has_code:false,applied_code:''});
assert(unlocked.enhanced, 'working promo UI must enable single-code slot');
assert(unlocked.markup.includes('Promo / Coupon Code'),'separate visible coupon label');
assert(unlocked.markup.includes('Enter coupon code'),'visible coupon entry');
assert(unlocked.markup.includes('Free shipping applied'),'selected free shipping message');
const applied = run({active:true,state:'require_code_unlocked',has_code:true,applied_code:'BYL49'});
assert(applied.enhanced, 'applied coupon still must show an input');
assert(applied.markup.includes('Enter coupon code'), 'applied promo must not hide code entry');
assert(applied.markup.includes('BYL49 applied'), 'applied code status');
assert(applied.markup.includes('Remove'), 'allow removing existing code');
const noData = run(null);
assert(!noData.enhanced, 'when Store API data is missing keep native Woo coupon fallback');
const iowa = run({
  active:true,state:'below_threshold',threshold:69,subtotal:59,remaining:10,
  has_code:false,applied_code:'',zone_id:5,
}, {active:true,state:'automatic_unlocked',threshold:0,remaining:0});
assert(iowa.markup.includes('Free shipping on orders of $69.00'), 'Iowa native Woo zone threshold $69');
assert(iowa.markup.includes('Add $10.00 more for free shipping'), 'Iowa native Woo $59/$69 difference');
assert(!iowa.markup.includes('Free shipping applied'), 'stale Blocks unlocked must not override native $10 flat');
assert(iowa.markup.includes('role="progressbar"'), 'under-threshold progress bar');
const availablePaid = run({active:true,state:'free_shipping_available',threshold:69,subtotal:79,remaining:0});
assert(availablePaid.markup.includes('select it under Shipping'), 'paid selection cannot claim free shipping applied');
const noFreeRate = run({active:true,state:'shipping_unavailable',threshold:0});
assert(noFreeRate.markup.includes('not available for this shipping address'), 'unavailable is not unlocked');
const staleBlocksOnly = run(null, {active:true,state:'automatic_unlocked',threshold:0});
assert(!staleBlocksOnly.enhanced, 'classic cart must not use a stale Blocks promotion snapshot');
const inactive = run({active:false});
assert(!inactive.enhanced, 'inactive promotions must preserve native Woo coupon');
assert(css.includes('.woocommerce-cart.byl-cart-coupon-enhanced .woocommerce-cart-form .coupon'), 'native coupon hides only when enhanced code UI works');
assert(!/\.woocommerce-cart\s+\.woocommerce-cart-form\s+\.coupon\s*\{\s*display\s*:\s*none/.test(css), 'native coupon must not be always hidden');
console.log('PASS Cart coupon UI: unlocked, applied, inactive, Store API fallback, single native fallback');
