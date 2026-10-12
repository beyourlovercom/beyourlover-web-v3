'use strict';
/** Source-only M5 Cart V2 browser contract test; never hits real network. */
const vm=require('node:vm'),fs=require('node:fs'),path=require('node:path'),assert=require('node:assert/strict');
const js=fs.readFileSync(path.join(__dirname,'../theme/beyourlover-v3/assets/js/cart-drawer.js'),'utf8');
function element() {
  return {hidden:false,textContent:'',innerHTML:'',dataset:{},attrs:{},children:[],style:{},
    classList:{add(){},remove(){},toggle(){}}, setAttribute(k,v){this.attrs[k]=v;},
    appendChild(child){this.children.push(child);},focus(){},getClientRects(){return [1]},
    querySelectorAll(){return []},querySelector(){return null},addEventListener(){}};
}
const good={
  schema_version:1,state:'ready',show_progress:true,gift_enabled:false,
  claim_free_shipping:false,reason:'read_only_projection',authority:'woocommerce',
  qualified_minor:4500,threshold_minor:4900,remaining_minor:400,percent:91
};
function cart(ext=good) {
  return {
    items:[{key:'a',id:3,name:'Test',quantity:1,permalink:'https://example.invalid/',
      quantity_limits:{editable:true,minimum:1,maximum:4},
      variation:[],images:[],prices:{price:'4500',regular_price:'4500'},
      totals:{line_total:'4500'}}],
    totals:{total_items:'4500',total_price:'4500',total_shipping:'0',
      currency_minor_unit:2,currency_code:'USD'},
    coupons:[],
    shipping_rates:[{shipping_rates:[{selected:true,rate_id:'flat_rate:8'}]}],
    extensions:ext?{andy_commerce_marketing_v1:ext}:{}
  };
}
const cases=[
 ['OFF ignores valid API data',false,cart(),false],
 ['ON missing extension stays hidden',true,cart(null),false],
 ['ON trusted extension renders',true,cart(),true],
 ['ON misleading percent hidden',true,cart({...good,percent:99}),false],
 ['ON noncanonical threshold hidden',true,cart({...good,threshold_minor:-1}),false],
 ['ON unsafe free-shipping claim hidden',true,cart({...good,claim_free_shipping:true}),false],
 ['ON unexpected gift enabled hidden',true,cart({...good,gift_enabled:true}),false],
 ['ON multiple packages hidden',true,{...cart(),shipping_rates:[
   {shipping_rates:[{selected:true,rate_id:'flat_rate:1'}]},
   {shipping_rates:[{selected:true,rate_id:'flat_rate:2'}]}]},false],
 ['ON XSS rate ID hidden',true,{...cart(),shipping_rates:[
   {shipping_rates:[{selected:true,rate_id:'<script>'}]}]},false],
 ['ON stale legacy reason hidden',true,cart({...good,state:'hidden',
   show_progress:false,reason:'legacy_policy_migration_pending'}),false],
 ['ON 100 pct remains non-award',true,cart({...good,qualified_minor:4900,
   remaining_minor:0,percent:100}),true]
];
async function run(name,enabled,server,expected){
  const keys=['.byl-drawer__panel','[data-drawer-count]','[data-drawer-feedback]',
    '[data-drawer-delivery]','[data-drawer-m5-progress]','[data-drawer-empty]',
    '[data-drawer-items]','[data-drawer-coupon-section]','[data-drawer-coupon-form]',
    '[data-drawer-active-coupons]','[data-drawer-totals]','[data-drawer-footer]',
    '[data-drawer-footer-total]','[data-drawer-recommendations]',
    '[data-drawer-mobile-recs]','[data-drawer-checkout]','[data-drawer-marketing-slots]'];
  const elems=Object.fromEntries(keys.map(k=>[k,element()]));
  const root=element();root.hidden=true;
  root.querySelector=sel=>elems[sel]??null;
  const document={createElement:()=>element(),activeElement:element(),
    documentElement:element(),querySelector:s=>s==='[data-byl-cart-drawer]'?root:null,
    querySelectorAll:()=>[],addEventListener(){}};
  const window={BYLCartDrawerPreview:{giftEnabled:false,marketingEnabled:false,shippingProgressEnabled:enabled,
    cartApi:'https://example.invalid/cart',nonce:'mock',cartUrl:'https://example.invalid/cart/',
    checkoutUrl:'https://example.invalid/checkout/'},
    location:{href:'https://example.invalid/'},setTimeout,clearTimeout};
  const fetch=async()=>({ok:true,headers:{get:()=>null},json:async()=>server});
  vm.runInNewContext(js,{window,document,fetch,URL,Intl,Math,Number,String,
    Promise,Set,Map,Array,Object,Error,setTimeout,clearTimeout,console});
  let loops=0;
  while(!elems['[data-drawer-items]'].innerHTML.includes('Test')&&loops++<200){
    await new Promise(resolve=>setTimeout(resolve,5));
  }
  assert.ok(loops<200,'Woo response rendered '+name);
  const slot=elems['[data-drawer-m5-progress]'];
  assert.equal(slot.hidden,!expected,name+' visibility');
  assert.equal(elems['[data-drawer-marketing-slots]'].hidden,true,
    'No coupon/gift marketing banner activated');
  if(expected){
    assert.equal(slot.children.length,2,'passive label and bar');
    assert.match(slot.children[0].textContent,/\$4\.00|Threshold reached/,
      'calculated from Woo extension');
    assert.ok(!slot.children[0].textContent.includes('Free shipping applied'),
      'no fake shipping award');
  }else{
    assert.equal(slot.children.length,0,'no side effects when hidden');
  }
}
(async()=>{
  for(const [name,enabled,snapshot,expected] of cases)await run(name,enabled,snapshot,expected);
  const php=fs.readFileSync(path.join(__dirname,
    '../theme/beyourlover-v3/inc/cart-drawer.php'),'utf8');
  assert.match(php,/'marketingEnabled'\s*=>\s*false/,'server source marketing OFF');
  assert.match(php,/data-drawer-m5-progress[^>]*hidden/,'server HTML hidden');
  console.log('M5_CART_V2_OFFLINE_GATED_PROGRESS_PASS='+ (cases.length+2)+'; BUSINESS_ACTIVATION=OFF; NO_NETWORK=YES');
})().catch(e=>{console.error(e);process.exitCode=1;});
