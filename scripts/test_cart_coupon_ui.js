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
assert(unlocked.markup.includes("🎉 Congrats! You've unlocked free shipping."), 'selected free shipping celebration');
assert(unlocked.markup.includes('Free shipping applied to your order.'), 'selected free shipping explicit application confirmation');
assert(!unlocked.markup.includes('Select Free Shipping under Shipping'), 'selected state must not ask to select again');
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
assert(iowa.markup.includes('Free shipping on orders over $69</p>'), 'Iowa native Woo whole-dollar threshold with over');
assert(!iowa.markup.includes('byl-cart-promotion__heading'), 'remove ambiguous redundant Free shipping heading');
assert(!iowa.markup.includes('Free shipping on orders of '), 'no obsolete of copy');
assert(!iowa.markup.includes('Free shipping on orders over $69.00'), 'omit .00 on whole-dollar threshold');
assert(iowa.markup.includes('Add $10.00 more for free shipping'), 'Iowa native Woo $59/$69 difference');
assert(!iowa.markup.includes('Free shipping applied'), 'stale Blocks unlocked must not override native $10 flat');
assert(!iowa.markup.includes("🎉 Congrats!"), 'below threshold must not celebrate');
assert(iowa.markup.includes('role="progressbar"'), 'under-threshold progress bar');
assert(iowa.markup.indexOf('byl-cart-promotion__threshold') < iowa.markup.indexOf('byl-cart-promotion__message'), 'threshold before remaining');
assert(iowa.markup.indexOf('byl-cart-promotion__message') < iowa.markup.indexOf('byl-cart-promotion__meter'), 'meter immediately follows remaining');
assert(iowa.markup.indexOf('byl-cart-promotion__meter') < iowa.markup.indexOf('byl-cart-promotion__form'), 'coupon entry follows the meter');
const fractionalZone = run({active:true,state:'below_threshold',threshold:69.5,subtotal:59,remaining:10.5});
assert(fractionalZone.markup.includes('Free shipping on orders over $69.50'), 'preserve fractional zone thresholds without rounding misleadingly');
const availablePaid = run({active:true,state:'free_shipping_available',threshold:69,subtotal:79,remaining:0});
assert(availablePaid.markup.includes("🎉 Congrats! You've unlocked free shipping."), 'eligible customer sees celebration even while paid delivery selected');
assert(availablePaid.markup.includes('Select Free Shipping under Shipping above to apply it to your order.'), 'eligible but not selected asks to choose free delivery');
assert(!availablePaid.markup.includes('Free shipping applied to your order.'), 'paid selected must not claim shipping is applied');
const couponNeeded = run({active:true,state:'require_code_available',threshold:69,subtotal:89,remaining:0,policy_code:'BYL49'});
assert(!couponNeeded.markup.includes('🎉 Congrats!'), 'code-required offer cannot celebrate before code applied');
const couponSelected = run({active:true,state:'require_code_unlocked',threshold:69,subtotal:89,remaining:0,has_code:true,applied_code:'BYL49'});
assert(couponSelected.markup.includes("🎉 Congrats! You've unlocked free shipping."), 'code-required selected free delivery also celebrates');
assert(couponSelected.markup.includes('Free shipping applied to your order.'), 'code-required applied status correct');
const noFreeRate = run({active:true,state:'shipping_unavailable',threshold:0});
assert(noFreeRate.markup.includes('not available for this shipping address'), 'unavailable is not unlocked');
assert(!noFreeRate.markup.includes('🎉 Congrats!'), 'unavailable country must not celebrate');
const staleBlocksOnly = run(null, {active:true,state:'automatic_unlocked',threshold:0});
assert(!staleBlocksOnly.enhanced, 'classic cart must not use a stale Blocks promotion snapshot');
const inactive = run({active:false});
assert(!inactive.enhanced, 'inactive promotions must preserve native Woo coupon');
assert(css.includes('.woocommerce-cart.byl-cart-coupon-enhanced .woocommerce-cart-form .coupon'), 'native coupon hides only when enhanced code UI works');
assert(!/\.woocommerce-cart\s+\.woocommerce-cart-form\s+\.coupon\s*\{\s*display\s*:\s*none/.test(css), 'native coupon must not be always hidden');
console.log('PASS Cart coupon UI: unlocked, applied, inactive, Store API fallback, single native fallback');
