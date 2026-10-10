'use strict';
/**
 * Execute the actual Drawer browser JS inside an isolated Node VM with fake
 * Woo Store API responses. No network, browser, Woo orders, payments or DB.
 * This is a state-machine regression, NOT a replacement for real Dev UAT.
 */
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const assert = require('node:assert/strict');

const source = fs.readFileSync(path.join(__dirname, '../theme/beyourlover-v3/assets/js/cart-drawer.js'), 'utf8');
const events = {};
function domElement() {
  return {
    hidden: false, value: '', textContent: '', innerHTML: '', dataset: {},
    attrs: {},
    classList: {add(){}, remove(){}, toggle(){}, contains(){return false;}},
    setAttribute(k,v){this.attrs[k]=v;},
    removeAttribute(k){delete this.attrs[k];},
    focus(){},
    querySelector(){return null;},
    querySelectorAll(){return [];},
    getClientRects(){return [1];}
  };
}
function fixture() {
  const root=domElement();root.hidden=true;
  const keys=[
    '.byl-drawer__panel','[data-drawer-count]','[data-drawer-feedback]',
    '[data-drawer-delivery]','[data-drawer-empty]','[data-drawer-items]',
    '[data-drawer-coupon-section]','[data-drawer-coupon-form]',
    '[data-drawer-active-coupons]','[data-drawer-totals]',
    '[data-drawer-footer]','[data-drawer-footer-total]',
    '[data-drawer-recommendations]','[data-drawer-mobile-recs]',
    '[data-drawer-checkout]'
  ];
  const el=Object.fromEntries(keys.map(k=>[k,domElement()]));
  el['[data-drawer-recommendations]'].innerHTML='<div class="byl-drawer__rec">Demo</div>';
  const input=domElement();
  el['[data-drawer-coupon-form]'].querySelector=()=>input;
  el['[data-drawer-coupon-form]'].addEventListener=(kind,fn)=>{events['coupon.'+kind]=fn;};
  root.querySelector=selector=>el[selector]||null;
  root.addEventListener=(kind,fn)=>{events['root.'+kind]=fn;};
  const document={
    activeElement:domElement(),
    documentElement:domElement(),
    querySelector:selector=>selector==='[data-byl-cart-drawer]'?root:null,
    querySelectorAll:()=>[],
    addEventListener:(kind,fn)=>{events['document.'+kind]=fn;}
  };
  const window={
    BYLCartDrawerPreview:{
      cartApi:'https://dev.example.invalid/wp-json/wc/store/v1/cart',
      cartUrl:'https://dev.example.invalid/cart/',
      checkoutUrl:'https://dev.example.invalid/checkout/',
      nonce:'synthetic-nonce',giftEnabled:false
    },
    location:{href:'https://dev.example.invalid/'},
    setTimeout,clearTimeout
  };
  return {root,el,input,document,window};
}
function makeCart(q=1,coupons=[]) {
  const amount=q*5999;
  return {
    items:q?[{key:'line-one',id:71,name:'Safe Product',quantity:q,permalink:'javascript:alert(1)',
      quantity_limits:{minimum:1,maximum:9,editable:true},variation:[],images:[],
      prices:{price:'5999',regular_price:'5999'},totals:{line_total:String(amount)}}]:[],
    coupons:coupons.map(code=>({code})),
    shipping_rates:[],
    totals:{currency_code:'USD',currency_minor_unit:2,total_items:String(amount),
      total_discount:'0',total_shipping:'0',total_price:String(amount)}
  };
}
async function waitFor(test,ms=2500) {
  const start=Date.now();
  while(Date.now()-start<ms) {
    if(test())return;
    await new Promise(resolve=>setTimeout(resolve,10));
  }
  throw new Error('Timed out waiting for mocked Woo Cart operation');
}
function qtyClick(row,delta) {
  const qtyBtn={dataset:{drawerQty:String(delta)},closest(sel){
    return sel==='[data-drawer-qty]'?this:sel==='[data-drawer-key]'?row:null;
  }};
  events['root.click']({target:qtyBtn});
}
async function main() {
  const {root,el,input,document,window}=fixture();
  let server=makeCart(),failNextUpdate=true;
  const calls=[];
  const fetch=async(url,init={})=>{
    const endpoint=new URL(url).pathname.split('/').pop();
    const method=init.method||'GET';
    const body=init.body?JSON.parse(init.body):{};
    calls.push({endpoint,method,body});
    if(method==='POST'&&endpoint==='apply-coupon'){
      if(body.code==='BAD')return {ok:false,headers:{get(){return null;}},json:async()=>({message:'Coupon is invalid'})};
      server=makeCart(server.items[0]?.quantity||0,[body.code]);
    }else if(method==='POST'&&endpoint==='update-item'){
      if(failNextUpdate){failNextUpdate=false;return {ok:false,headers:{get(){return null;}},json:async()=>({message:'Quantity rejected by Woo'})};}
      server=makeCart(Number(body.quantity),server.coupons.map(c=>c.code));
    }
    return {ok:true,headers:{get(){return null;}},json:async()=>structuredClone(server)};
  };
  const context=vm.createContext({window,document,fetch,URL,Intl,Math,Number,String,
    Promise,Set,Map,Array,Object,Error,setTimeout,clearTimeout,console});
  vm.runInContext(source,context,{filename:'cart-drawer.js'});
  await waitFor(()=>window.BYL_Cart_Drawer_Preview && el['[data-drawer-items]'].innerHTML.includes('Safe Product'));
  assert.equal(root.hidden,false,'explicit preview must open the Drawer');
  assert.ok(!el['[data-drawer-items]'].innerHTML.includes('href="javascript:'),'unsafe Woo product permalink scheme must be rejected');
  assert.ok(!el['[data-drawer-items]'].innerHTML.includes('<img '),'empty Woo thumbnail must not trigger homepage image requests');
  assert.ok(el['[data-drawer-items]'].innerHTML.includes('https://dev.example.invalid/cart/'),'unsafe href falls back to cart URL');
  assert.equal(el['[data-drawer-footer-total]'].textContent,'$59.99','display Woo-authoritative minor-unit totals');

  // Invalid coupon stays in form. A subsequent successful request still works.
  input.value='BAD';
  events['coupon.submit']({preventDefault(){}});
  await waitFor(()=>el['[data-drawer-feedback]'].textContent==='Coupon is invalid');
  assert.equal(input.value,'BAD','failed coupon not silently cleared');
  input.value='GOOD';
  events['coupon.submit']({preventDefault(){}});
  await waitFor(()=>el['[data-drawer-active-coupons]'].innerHTML.includes('GOOD'));
  assert.equal(input.value,'','accepted coupon cleared');
  assert.equal(server.coupons[0].code,'GOOD');

  // API rejects first optimistic quantity change -> restore Woo response.
  const label=domElement(),row={dataset:{drawerKey:'line-one'},querySelector:()=>label};
  qtyClick(row,1);
  assert.equal(label.textContent,'2','quantity uses a short debounce for two rapid taps');
  await waitFor(()=>el['[data-drawer-feedback]'].textContent==='Quantity rejected by Woo',3500);
  assert.equal(server.items[0].quantity,1,'server quantity stayed unchanged on rejection');
  assert.ok(el['[data-drawer-items]'].innerHTML.includes('<span aria-live="off">1</span>'),
    'rejected optimistic quantity must visibly rollback to last Woo-confirmed 1');

  // Retry succeeds; rapid 2 taps submit ONE +2 request, not two stale state requests.
  qtyClick(row,1);qtyClick(row,1);
  assert.equal(label.textContent,'3');
  await waitFor(()=>server.items[0].quantity===3,3500);
  assert.equal(calls.filter(c=>c.endpoint==='update-item').length,2,
    'one rejected update + one coalesced success');
  assert.equal(el['[data-drawer-footer-total]'].textContent,'$179.97');

  // Escape works globally, including after focus vanishes during a rerender.
  events['document.keydown']({key:'Escape',preventDefault(){}});
  assert.equal(root.hidden,true);
  assert.ok(!calls.some(c=>/checkout|place-order|payment/i.test(c.endpoint)),
    'state test must never call Checkout mutations');
  console.log('PASS Drawer Woo mocked runtime: safe href, coupon fail/retry, qty rollback, debounce, totals, Escape, no payments');
}
main().catch(error=>{console.error(error);process.exitCode=1;});
