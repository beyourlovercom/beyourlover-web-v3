/* Track A isolated tests: native Woo values, presentation only, no network. */
'use strict';
const fs=require('node:fs');
const path=require('node:path');
const assert=require('node:assert/strict');
const {JSDOM}=require('jsdom');
const theme=path.resolve(__dirname,'../theme/beyourlover-v3');
const html='<!doctype html><html><body class="byl-checkout-drawer-mode admin-bar">'+
'<div id="wpadminbar"></div>'+
'<section data-byl-checkout-drawer-root><div data-byl-progressive-checkout data-byl-current-step="payment">'+
'<div class="byl-account-entry"><a href="/my-account/edit-account/">Edit your profile</a></div>'+
'<div data-byl-promotion><strong class="byl-promo-title">Free shipping</strong><p class="byl-promo-message">Free shipping unlocked</p></div>'+
'<div id="order_review">'+
'<input type="radio" name="shipping_method[0]" value="flat_rate:4" id="flat" checked>'+
'<input type="radio" name="shipping_method[0]" value="free_shipping:6" id="free">'+
'<div id="order_total">Woo total $185</div></div>'+
'<section data-byl-payment-host><div id="payment"><p class="woocommerce-info">Sorry, it seems that there are no available payment methods.</p>'+
'<input type="radio" name="payment_method" value="native_woo" checked></div></section>'+
'<p data-byl-error>No payment method is available. Please try again later.</p>'+
'<div class="byl-summary-card"><button type="button" data-byl-summary-toggle>Open total</button><div class="byl-summary-body">Existing Woo total</div></div>'+
'<button type="button" data-byl-drawer-payment-total-details>See details</button>'+
'</div></section></body></html>';
const dom=new JSDOM(html,{url:'https://dev.beyourlover.com/checkout/?byl_checkout_drawer=1',runScripts:'outside-only',pretendToBeVisual:true});
const w=dom.window,d=w.document;
const summary=d.querySelector('.byl-summary-card');
const toggle=d.querySelector('[data-byl-summary-toggle]');
toggle.addEventListener('click',()=>{
 summary.classList.toggle('is-open');
 toggle.setAttribute('aria-expanded',String(summary.classList.contains('is-open')));
});
w.HTMLElement.prototype.scrollIntoView=function(){};
let fetchCalled=0;
w.fetch=()=>{fetchCalled++;throw Error('visual-only must not fetch')};
w.eval(fs.readFileSync(path.join(theme,'assets/js/checkout-visual-v2.js'),'utf8'));
const tick=()=>new Promise(done=>setTimeout(done,40));
(async()=>{
 await tick();
 const host=d.querySelector('[data-byl-promotion]');
 const title=host.querySelector('.byl-promo-title');
 const promo=host.querySelector('.byl-promo-message');
 assert.equal(host.dataset.bylVisualRate,'available');
 assert.match(promo.textContent,/choose it above/);
 assert.equal(title.textContent,'Free shipping');
 d.querySelector('#flat').checked=false;
 d.querySelector('#free').checked=true;
 d.querySelector('#free').dispatchEvent(new w.Event('change',{bubbles:true}));
 await tick();
 assert.equal(host.dataset.bylVisualRate,'applied');
 assert.match(promo.textContent,/applied to your selected delivery/);
 d.querySelector('#free').remove();
 d.querySelector('#flat').checked=true;
 d.querySelector('#flat').dispatchEvent(new w.Event('change',{bubbles:true}));
 await tick();
 assert.equal(host.dataset.bylVisualRate,'not-available');
 assert.equal(title.textContent,'Shipping & offers');
 assert.doesNotMatch(promo.textContent,/unlocked|applied/);
 const error=d.querySelector('[data-byl-error]');
 assert.equal(error.dataset.bylV2Duplicate,'1','only duplicate Woo warning is hidden');
 error.textContent='Please check your billing address.';
 await tick();
 assert.equal(error.dataset.bylV2Duplicate,undefined,'new gateway/address error must be visible');
 error.textContent='No payment method is available. Please try again later.';
 d.querySelector('#payment .woocommerce-info').remove();
 await tick();
 assert.equal(error.dataset.bylV2Duplicate,undefined,'BYL warning must remain if native Woo notice disappeared');
 const link=d.querySelector('.byl-account-entry a');
 assert.equal(link.textContent,'Account');
 assert.equal(link.getAttribute('href'),'/my-account/edit-account/');
 assert.ok(link.closest('.byl-account-entry--signed-in'));
 const details=d.querySelector('[data-byl-drawer-payment-total-details]');
 assert.equal(summary.classList.contains('is-open'),false);
 details.click();
 assert.equal(summary.classList.contains('is-open'),true);
 details.click();
 assert.equal(summary.classList.contains('is-open'),true,'details must not collapse open Woo summary');
 assert.equal(d.querySelectorAll('#payment input[name="payment_method"]').length,1);
 assert.equal(fetchCalled,0);
 console.log('CHECKOUT_VISUAL_V2_DOM_PASS: selected actual Woo free rate vs paid rate; graceful no-free fallback; safe duplicate messages; real account link; order details open; no gateway changes, fetch, checkout/order/payment');
 w.dispatchEvent(new w.Event('pagehide'));
 w.close();
})().catch(e=>{console.error(e);process.exitCode=1;w.close()});
