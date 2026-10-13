'use strict';
/**
 * No-address $10 bug regression on existing Cart Drawer:
 * Woo shop-base US/CA fallback is not a customer-confirmed destination.
 * Offline, GET-only mocked Store API. Never creates Woo carts or orders.
 */
const fs=require('node:fs'),vm=require('node:vm'),path=require('node:path'),assert=require('node:assert/strict');
const js=fs.readFileSync(path.join(__dirname,'../theme/beyourlover-v3/assets/js/cart-drawer.js'),'utf8');
const emptyAddress={country:'US',state:'CA',city:'',postcode:'',address_1:''};
const savedAddress={country:'US',state:'CA',city:'Los Angeles',postcode:'90001',address_1:'123 Test Street'};
function el(){
 return {hidden:false,textContent:'',innerHTML:'',dataset:{},style:{},children:[],
  attrs:{},classList:{add(){},remove(){},toggle(){}},setAttribute(k,v){this.attrs[k]=v;},
  removeAttribute(k){delete this.attrs[k]},appendChild(x){this.children.push(x)},
  getClientRects(){return [1]},addEventListener(){},querySelector(){return null},
  querySelectorAll(){return []},focus(){}};
}
function cart(address,hasCalculated=true,freeSelected=false){
 return {
  items:[
   {key:'tara',id:51,name:'TARA S2 tara',quantity:2,permalink:'https://example.invalid/a',
    quantity_limits:{editable:true,minimum:1,maximum:10},variation:[],images:[],
    prices:{price:'7900',regular_price:'9900'},totals:{line_total:'15800'}},
   {key:'skull',id:52,name:'SKULL MAN',quantity:1,permalink:'https://example.invalid/b',
    quantity_limits:{editable:true,minimum:1,maximum:10},variation:[],images:[],
    prices:{price:'9600',regular_price:'14900'},totals:{line_total:'9600'}}],
  coupons:[],has_calculated_shipping:hasCalculated,needs_shipping:true,
  shipping_address:address,
  shipping_rates:[{shipping_rates:[
   {selected:!freeSelected,rate_id:'flat_rate:10'},
   {selected:freeSelected,rate_id:'free_shipping:9'}]}],
  totals:{currency_code:'USD',currency_minor_unit:2,total_items:'25400',
   total_discount:'0',total_shipping:freeSelected?'0':'1000',
   total_price:freeSelected?'25400':'26400'}
 };
}
async function run(label,payload,expected){
 const keys=['.byl-drawer__panel','[data-drawer-count]','[data-drawer-feedback]',
  '[data-drawer-delivery]','[data-drawer-m5-progress]','[data-drawer-empty]',
  '[data-drawer-items]','[data-drawer-coupon-section]','[data-drawer-coupon-form]',
  '[data-drawer-active-coupons]','[data-drawer-totals]','[data-drawer-footer]',
  '[data-drawer-footer-total]','[data-drawer-recommendations]',
  '[data-drawer-mobile-recs]','[data-drawer-checkout]','[data-drawer-marketing-slots]'];
 const nodes=Object.fromEntries(keys.map(k=>[k,el()]));
 const root=el();root.hidden=true;root.querySelector=key=>nodes[key]||null;
 const document={activeElement:el(),documentElement:el(),createElement:()=>el(),
  querySelector:key=>key==='[data-byl-cart-drawer]'?root:null,
  querySelectorAll:()=>[],addEventListener(){}};
 const window={BYLCartDrawerPreview:{
    cartApi:'https://example.invalid/cart',nonce:'test',marketingEnabled:false,
    giftEnabled:false,shippingProgressEnabled:true,
    cartSubtotalNotice:{
      enabled:expected.noticeEnabled!==false,
      text:expected.noticeText||'Please check shipping costs, import duties and local taxes for your country or region. Final charges depend on your delivery address.'
    },
    cartUrl:'https://example.invalid/cart/',checkoutUrl:'https://example.invalid/checkout/'
  },location:{href:'https://example.invalid/'},setTimeout,clearTimeout};
 const fetch=async()=>({ok:true,headers:{get:()=>null},json:async()=>payload});
 vm.runInNewContext(js,{window,document,fetch,URL,Intl,Number,Math,String,Boolean,
  Promise,Map,Set,Array,Object,Error,setTimeout,clearTimeout,console});
 let spin=0;while(!nodes['[data-drawer-items]'].innerHTML.includes('TARA')&&spin++<220)
  await new Promise(r=>setTimeout(r,5));
 assert.ok(spin<220,label+' ready');
 const totals=nodes['[data-drawer-totals]'].innerHTML;
 const footer=nodes['[data-drawer-footer-total]'].textContent;
 const delivery=nodes['[data-drawer-delivery]'].textContent;
 const provisional = !expected.confirmed;
 const shippingLabel = provisional ? 'Shipping (estimated)' : 'Shipping';
 assert.ok(totals.includes(shippingLabel),label+' shipping must be visible');
 assert.equal(footer,expected.total,label+' numeric footer');
 assert.ok(totals.includes('$254.00'),label+' subtotal stays visible');
 assert.ok(totals.includes(expected.shipping),label+' shipping amount');
 assert.ok(totals.includes(expected.total),label+' Woo numeric total');
 if(expected.noticeEnabled===false){
  assert.ok(!totals.includes('byl-drawer__charges-notice'),label+' notice disabled');
 }else if(expected.noticeText){
  assert.ok(totals.includes('Custom duties note'),label+' custom option text displayed');
  assert.ok(!totals.includes('<b>'),label+' unsafe admin formatting escaped');
 }else{
  assert.ok(totals.includes('import duties and local taxes'),label+' default notice displayed');
 }
 if(provisional){
  assert.match(delivery,/Estimated shipping shown/,label+' warn rather than promise free shipping');
  assert.ok(!delivery.includes('Free shipping confirmed'),label+' no false free award');
  assert.ok(totals.includes('Order total (estimated)'),label+' total is clearly estimated');
 }else{
  assert.ok(totals.includes('Order total'),label+' Woo confirmed amount');
 }
 assert.equal(nodes['[data-drawer-marketing-slots]'].hidden,true,'promotions OFF');
}
(async()=>{
 await run('shop-base country/state but no customer address',cart(emptyAddress),{confirmed:false,shipping:'$10.00',total:'$264.00'});
 await run('no address plus Woo rate calculated flag false',cart(emptyAddress,false),{confirmed:false,shipping:'$10.00',total:'$264.00'});
 await run('country/state only even with apparent free rate',cart(emptyAddress,true,true),{confirmed:false,shipping:'$0.00',total:'$254.00'});
 await run('logged-in profile shipping address from Woo',cart(savedAddress),{confirmed:true,shipping:'$10.00',total:'$264.00'});
 await run('confirmed shipping address free rate selected',cart(savedAddress,true,true),{confirmed:true,shipping:'$0.00',total:'$254.00'});
 await run('address saved but Woo calculation incomplete',cart(savedAddress,false),{confirmed:false,shipping:'$10.00',total:'$264.00'});
 const noRate=cart(savedAddress);noRate.shipping_rates=[];
 await run('address saved but no selected shipping rate',noRate,{confirmed:false,shipping:'$10.00',total:'$264.00'});
 await run('Admin can disable standalone notice',cart(emptyAddress),{confirmed:false,shipping:'$10.00',total:'$264.00',noticeEnabled:false});
 await run('Admin text is escaped on Cart',cart(emptyAddress),{confirmed:false,shipping:'$10.00',total:'$264.00',noticeText:'<b>Custom duties note</b>'});
 console.log('CART_DEFAULT_10_USD_ESTIMATE_WITH_NOTICE_PASS_9; REAL_ORDER_WRITES=0');
})().catch(e=>{console.error(e.stack||e);process.exitCode=1});
