/* Dependency: jsdom@26.x installed in isolated test temp only.
   NO network calls, real cart, checkout order, payment, account or mail. */
'use strict';
const assert=require('node:assert/strict');
const fs=require('node:fs');
const path=require('node:path');
const {JSDOM}=require('jsdom');
const root=path.resolve(__dirname,'../theme/beyourlover-v3');
const html='<!doctype html><html><body>'+
'<div data-byl-checkout-drawer-root>'+
'<button data-byl-checkout-drawer-scrim>Backdrop</button>'+
'<section data-byl-checkout-drawer-panel role="dialog" aria-modal="true" tabindex="-1">'+
'<button data-byl-checkout-drawer-close>Close</button>'+
'<div data-byl-checkout-drawer-scroll>'+
'<div data-byl-progressive-checkout data-byl-current-step="shipping">'+
'<header class="byl-checkout-heading"><h1 data-byl-checkout-heading>Shipping</h1></header>'+
'<nav class="byl-checkout-track"><a href="/cart/" data-byl-track="cart">Cart</a><span data-byl-track="shipping">Shipping</span><span data-byl-track="payment">Payment</span></nav>'+
'<form class="checkout"><div class="byl-checkout-stages">'+
'<button type="button" data-byl-open-step="shipping">Edit shipping</button>'+
'<button type="button" data-byl-open-step="payment">Edit payment</button>'+
'<div id="payment"><input type="radio" name="payment_method" value="fixture"></div>'+
'<div data-byl-drawer-payment-total><strong data-byl-drawer-payment-total-amount></strong><button type="button" data-byl-drawer-payment-total-details>See details</button></div>'+
'</div><aside class="byl-checkout-summary"><div class="byl-summary-card">'+
'<button type="button" data-byl-summary-toggle><strong>Order Summary</strong><span class="byl-summary-total">$25.00</span></button>'+
'<div class="byl-summary-body">Woo summary</div></div></aside></form>'+
'</div></div></section></div></body></html>';
const dom=new JSDOM(html,{
  url:'https://dev.beyourlover.com/checkout/?byl_checkout_drawer=1',
  referrer:'https://dev.beyourlover.com/product/safe-item/',
  runScripts:'outside-only',pretendToBeVisual:true
});
const w=dom.window,d=w.document;
w.matchMedia=()=>({matches:true,addListener(){},removeListener(){}});
w.BYLCheckoutDrawerConfig={fallbackUrl:'https://dev.beyourlover.com/cart/',checkoutUrl:'https://dev.beyourlover.com/checkout/'};
w.HTMLElement.prototype.getClientRects=function(){return [{}]};
let delegatedShipping=0;
d.querySelector('[data-byl-open-step="shipping"]').addEventListener('click',()=>{delegatedShipping++});
const script=fs.readFileSync(path.join(root,'assets/js/checkout-drawer.js'),'utf8');
w.eval(script);
const shell=d.querySelector('[data-byl-checkout-drawer-root]');
const checkout=d.querySelector('[data-byl-progressive-checkout]');
const stages=d.querySelector('.byl-checkout-stages');
const close=d.querySelector('[data-byl-checkout-drawer-close]');
const back=d.querySelector('[data-byl-checkout-drawer-back]');
assert.ok(shell.classList.contains('is-ready'),'Modal was initialized');
assert.ok(stages.contains(d.querySelector('.byl-checkout-heading')),'Native header moved into stage panel');
assert.ok(stages.contains(d.querySelector('.byl-checkout-track')),'Native progress moved into stage panel');
assert.equal(d.querySelectorAll('form.checkout').length,1,'Only one native Woo form');
assert.equal(d.querySelectorAll('#payment').length,1,'No duplicated gateway');
assert.equal(d.activeElement,close,'Close initially focused');
assert.equal(back.textContent,'‹ Your Cart');
const summaryHead=d.querySelector('.byl-drawer-summary-head');
assert.ok(summaryHead,'Desktop summary header exists');
assert.equal(summaryHead.querySelectorAll('.byl-drawer-edit-cart').length,1,'Exactly one Edit your cart control');
assert.equal(d.querySelector('[data-byl-drawer-payment-total-amount]').textContent,'$25.00','Payment amount mirrors native Woo summary');
assert.equal(back.previousElementSibling,d.querySelector('.byl-checkout-heading'),'Shipping back must precede progress as Owner PASS');

checkout.dataset.bylCurrentStep='payment';
const finish=async()=>{
 await new Promise(resolve=>setTimeout(resolve,0));
 const wooTotal=d.querySelector('.byl-summary-total');
 wooTotal.textContent='$29.00';
 await new Promise(resolve=>setTimeout(resolve,0));
 assert.equal(d.querySelector('[data-byl-drawer-payment-total-amount]').textContent,'$29.00',
   'Woo fragment updates must refresh the display-only Payment total');
 assert.equal(back.textContent,'‹ Shipping','Back label follows internal Payment state');
 assert.equal(back.previousElementSibling,d.querySelector('.byl-checkout-track'),'Payment back must follow progress as Owner PASS');

 assert.equal(shell.dataset.bylDrawerStep,'payment');
 back.click();
 assert.equal(delegatedShipping,1,'Payment back delegates to existing native Woo Shipping step');
 checkout.dataset.bylSubmitting='1';
 const ev=new w.KeyboardEvent('keydown',{key:'Escape',bubbles:true,cancelable:true});
 close.dispatchEvent(ev);
 assert.equal(ev.defaultPrevented,true,'Esc intercepted while sensitive drawer active');
 assert.equal(d.querySelectorAll('form.checkout').length,1);
 assert.equal(d.querySelectorAll('#payment').length,1);
 console.log('CHECKOUT_DRAWER_OPTION_B_DOM_PASS: focus, Payment back, Escape while busy, one native form/gateway, zero network/transaction');
 w.close();
};
finish().catch(err=>{console.error(err);process.exitCode=1});
