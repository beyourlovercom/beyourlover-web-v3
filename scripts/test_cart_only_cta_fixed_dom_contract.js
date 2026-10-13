'use strict';
/**
 * M7.2 ownership regression: ONLY mobile checkout CTA is fixed.
 * Validate the actual PHP DOM hierarchy and no misleading CSS/test claims.
 * Source-only, no Woo cart/order/payment actions.
 */
const assert=require('node:assert/strict');
const fs=require('node:fs');
const path=require('node:path');
const dir=path.resolve(__dirname,'../theme/beyourlover-v3');
const php=fs.readFileSync(path.join(dir,'inc/cart-drawer.php'),'utf8');
const css=fs.readFileSync(path.join(dir,'assets/css/cart-drawer.css'),'utf8');
const js=fs.readFileSync(path.join(dir,'assets/js/cart-drawer.js'),'utf8');
let n=0;
const chk=(cond,name)=>{n++;assert.ok(cond,name)};
const at=(needle)=>php.indexOf(needle);
const cart=at('class="byl-drawer__cart"');
const scroll=at('class="byl-drawer__scroll"');
const header=at('class="byl-drawer__header"');
const steps=at('class="byl-drawer__steps"');
const totals=at('data-drawer-totals');
const legal=at('class="byl-drawer__legal-payment"');
const terms=at('class="byl-drawer__terms"');
const logos=at('class="byl-drawer__payment-methods"');
const footer=at('class="byl-drawer__footer"');
const cta=at('data-drawer-checkout');
chk([cart,scroll,header,steps,totals,legal,terms,logos,footer,cta].every(x=>x>=0),'all elements present');
chk(cart<scroll&&scroll<header&&header<steps&&steps<totals,'steps INSIDE scrolling content');
chk(totals<legal&&legal<terms&&terms<logos&&logos<footer,'Terms and payment logos INSIDE scrolling content');
chk(footer<cta,'CTA only after fixed footer starts');
const footerContent=php.slice(footer,php.indexOf('</footer>',footer));
chk(!/byl-drawer__terms|byl-drawer__payment-methods|byl-drawer__steps/.test(footerContent),'fixed footer has NO terms/payment/steps');
const scrollerContent=php.slice(scroll,footer);
chk(/data-drawer-legal-payment/.test(scrollerContent),'legal section is in scroll');
chk(/data-drawer-footer/.test(footerContent),'footer marker remains');
chk(/data-drawer-checkout/.test(footerContent),'native checkout link remains');
chk(/if \(elements\.legal\) elements\.legal\.hidden = items\.length === 0;/.test(js),'legal visibility tracks cart state');
chk(css.includes('M7.2: exactly ONE mobile viewport-fixed element'),'old incorrect CSS replaced');
chk(!css.includes('M7.1 mobile viewport footer'),'old static footer design deleted');
chk(!css.includes('.byl-drawer__footer .byl-drawer__terms'),'terms not styled as fixed footer');
chk(!css.includes('.byl-drawer__footer .byl-drawer__payment-methods'),'payment logos not styled as fixed footer');
chk(css.includes('.byl-drawer__legal-payment .byl-drawer__terms'),'legal text now styled in scroll');
chk(css.includes('.byl-drawer__legal-payment .byl-drawer__payment-methods'),'payment logos now styled in scroll');
chk(css.includes('height:100dvh')&&css.includes('overflow-y:auto'),'mobile viewport with internal scroll');
chk(css.includes('.byl-drawer__footer a[data-drawer-checkout]'),'one sticky CTA remains');
console.log('CART_ONLY_CTA_FIXED_DOM_CONTRACT_PASS='+n+'; ORDERS=0; PAYMENT=0; WWW=UNCHANGED');
