#!/usr/bin/env node
/**
 * G5 Dev-only, no-network Checkout double-submit / 3DS wait regression.
 * Evaluates the actual event handler and updateAction() source with fake DOM.
 * Native place_order.click() is a counter ONLY; zero WP orders/payments/API calls.
 */
'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const checkout = fs.readFileSync('theme/beyourlover-v3/assets/js/checkout.js', 'utf8');

function between(start, end) {
  const a = checkout.indexOf(start);
  assert(a >= 0, 'Missing source anchor: ' + start);
  const b = checkout.indexOf(end, a + start.length);
  assert(b > a, 'Missing end anchor: ' + end);
  return checkout.slice(a, b);
}
const action = between('  function updateAction() {', '  function setStep(');
const click = between("  next.addEventListener('click', () => {", "  root.addEventListener('click', e => {");
const step = between('  function setStep(step, {scroll = true} = {}) {', "  next.addEventListener('click', () => {");
const formSubmit = between("  form.addEventListener('submit', e => {", "  function promotionMessage(");
assert(step.includes('if (submitting) return;'), 'Cannot switch Checkout step while payment is pending');
assert(formSubmit.includes('if (!submitting) {'), 'Native Enter form submit must synchronize busy state');
const error = between("  $(document.body).on('checkout_error',", '  if (summaryToggle)');
assert(!/setTimeout\s*\(\s*\(\s*\)\s*=>\s*\{\s*submitting\s*=\s*false/.test(click),
  'No timer may unlock an unconfirmed 3DS / payment submission');
assert(click.includes('nativeButton.click()'), 'Preserve Woo native checkout path');
assert(error.includes('submitting = false;') && error.includes('updateAction();'),
  'Native Woo checkout_error must explicitly release the UI lock');
assert(action.includes("next.setAttribute('aria-busy', 'true')"),
  'Busy state must be exposed to assistive technology');

function fixture({gateway = true, nativePresent = true} = {}) {
  const callbacks = new Map();
  const events = new Map();
  const counters = {native: 0, timers: 0, notices: []};
  const attrs = new Map();
  const next = {
    disabled: false, innerHTML: '', textNodes: [],
    addEventListener(name, callback) { callbacks.set(name, callback); },
    appendChild(el) { this.textNodes.push(el.textContent); },
    setAttribute(name, value) { attrs.set(name, value); },
    removeAttribute(name) { attrs.delete(name); },
  };
  const formCallbacks = new Map();
  const form = {addEventListener(name, callback) {formCallbacks.set(name, callback);}};
  const nativeButton = {click() { counters.native++; }};
  const document = {
    body: {},
    getElementById(name) { return name === 'place_order' && nativePresent ? nativeButton : null; },
    createTextNode(value) { return {textContent: value}; },
    createElement() { return {textContent: '', setAttribute() {}}; },
  };
  const context = vm.createContext({
    next, document, form,
    root: {dataset: {needsPayment: '1',needsShipping: '0'},querySelectorAll() {return [];},querySelector() {return null;}},
    shippingReady() {return true;},
    mountNative() {},mountWooAccountEmail() {},relabelPhone() {},
    syncPhoneDescription() {},wireOptionalAddressFields() {},
    alignShippingAddressFields() {},syncMexicoRFCField() {},
    syncBillingAddressChoice() {},deliveryOptions() {},updateDetails() {},
    steps: ['shipping', 'payment'],
    window: {setTimeout() {counters.timers++; throw Error('Unexpected timeout unlock');}},
    $() { return {on(name, callback) {events.set(name, callback);}}; },
    paymentConfigured() {return gateway;},
    validateAddress() {return true;},
    selectedShipping() {return true;},
    message(value) { counters.notices.push(value); },

  });
  vm.runInContext(
    "let submitting = false; let active = 'payment'; let furthest = 1;\n" +
      action + step + click + formSubmit + error +
      "\nthis.inspect = () => ({submitting, active});\n",
    context,
    {filename: 'isolated-G5-actual-checkout-handler.js', timeout: 1000},
  );
  assert.equal(typeof callbacks.get('click'), 'function', 'Checkout must register click handler');
  assert.equal(typeof events.get('checkout_error'), 'function', 'Woo error listener must register');
  context.updateAction();
  return {
    click: callbacks.get('click'), fail: events.get('checkout_error'),
    submit: formCallbacks.get('submit'),
    editStep: context.setStep,
    updatedCheckout: context.updateAction,
    state: context.inspect, counters, next, attrs,
  };
}
function verify(ok, description) {
  assert(ok, description);
  console.log('PASS ' + description);
}
let cases = 0;
function scenario(description, fn) {fn();cases++; console.log('CASE ' + description + ' PASS');}

scenario('G5-05 rapid double click; one native submission', () => {
  const f = fixture();
  verify(!f.next.disabled, 'Payment starts enabled with an eligible gateway');
  f.click();
  f.click();
  verify(f.counters.native === 1, 'rapid repeated click cannot invoke Woo twice');
  verify(f.next.disabled && f.attrs.get('aria-busy') === 'true', 'busy UI remains disabled');
});

scenario('G5-02 3DS challenge longer than six seconds: lock never releases by timer', () => {
  const f = fixture();
  f.click();
  for (let i = 0; i < 100; i++) {f.updatedCheckout(); f.click();}
  verify(f.counters.native === 1, '100 attempted re-clicks/fragment updates do not repeat checkout');
  verify(f.counters.timers === 0, 'no timer can authorize an extra submission');
  verify(f.state().submitting, 'lock retained while provider is still pending');
});

scenario('G5-01/G5-04 explicit Woo error permits user-initiated retry', () => {
  const f = fixture();
  f.click();
  f.fail();
  verify(!f.next.disabled && !f.attrs.has('aria-busy'), 'checkout_error unlocks UI');
  verify(!f.state().submitting, 'internal guard released only on Woo error');
  f.click();
  verify(f.counters.native === 2, 'one fresh explicit retry allowed after reported error');
  f.click();
  verify(f.counters.native === 2, 'retry still protected from double-click');
});

scenario('G5-07 no selected gateway remains fail-closed', () => {
  const f = fixture({gateway: false});
  verify(f.next.disabled, 'paymentless Payment CTA disabled');
  f.click();
  verify(f.counters.native === 0, 'no native checkout without an eligible gateway');
});

scenario('G5-08 missing native Woo submit button fails closed', () => {
  const f = fixture({nativePresent: false});
  f.click(); f.click();
  verify(f.counters.native === 0 && !f.state().submitting, 'no synthetic submit fallback');
});

scenario('G5-05 stage navigation cannot unlock pending payment', () => {
  const f = fixture();
  f.click();
  f.editStep('shipping');
  verify(f.state().active === 'payment', 'step-edit cannot escape a pending Payment attempt');
  verify(f.next.disabled && f.attrs.get('aria-busy') === 'true',
    'native pending state stays disabled after edit attempts');
  f.fail();
  verify(!f.state().submitting, 'Woo explicit error returns UI to editable state');
});

scenario('G5-04 Enter/native form path shares BYL pending lock', () => {
  const f = fixture();
  const evt={preventDefault() {throw Error('unexpected submit interception');},
    stopImmediatePropagation() {throw Error('unexpected Woo handler interception');}};
  f.submit(evt);
  verify(f.state().submitting && f.next.disabled, 'native form submit gets busy state');
  f.click();
  verify(f.counters.native === 0, 'BYL CTA cannot issue a second native click after Enter');
  f.fail();
  verify(!f.state().submitting && !f.next.disabled, 'Woo error unlocks Enter submission');
});

console.log('G5_OFFLINE_SUBMISSION_GUARD=PASS ' + cases + ' scenarios; zero requests / orders / charges');