'use strict';
const fs = require('fs');
const vm = require('vm');
const assert = require('assert');
const path = require('path');
const theme = path.resolve(__dirname, '../theme/beyourlover-v3');
const source = fs.readFileSync(path.join(theme,'assets/js/cart.js'),'utf8');
const css = fs.readFileSync(path.join(theme,'assets/css/cart.css'),'utf8');
function run(status) {
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
      return null;
    },
    appendChild(node) { child = node; },
    insertBefore(node) { child = node; },
  };
  const document = {
    readyState: 'complete',
    body: { classList: { add(name) { bodyClasses.add(name); } }, contains() { return true; } },
    querySelector(selector) { return selector.includes('.cart_totals') ? target : null; },
    createElement(name) { assert.equal(name,'section'); return root; },
  };
  const wp = {
    data: {
      select() { return { getCartData() { return { extensions: status ? {'byl-shipping-promotion':status} : {} }; } }; },
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
assert(unlocked.markup.includes('Free shipping unlocked'),'separate shipping message');
const applied = run({active:true,state:'require_code_unlocked',has_code:true,applied_code:'BYL49'});
assert(applied.enhanced, 'applied coupon still must show an input');
assert(applied.markup.includes('Enter coupon code'), 'applied promo must not hide code entry');
assert(applied.markup.includes('BYL49 applied'), 'applied code status');
assert(applied.markup.includes('Remove'), 'allow removing existing code');
const noData = run(null);
assert(!noData.enhanced, 'when Store API data is missing keep native Woo coupon fallback');
const inactive = run({active:false});
assert(!inactive.enhanced, 'inactive promotions must preserve native Woo coupon');
assert(css.includes('.woocommerce-cart.byl-cart-coupon-enhanced .woocommerce-cart-form .coupon'), 'native coupon hides only when enhanced code UI works');
assert(!/\.woocommerce-cart\s+\.woocommerce-cart-form\s+\.coupon\s*\{\s*display\s*:\s*none/.test(css), 'native coupon must not be always hidden');
console.log('PASS Cart coupon UI: unlocked, applied, inactive, Store API fallback, single native fallback');
