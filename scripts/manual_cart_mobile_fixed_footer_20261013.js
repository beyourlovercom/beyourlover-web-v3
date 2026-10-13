'use strict';
/**
 * Dev-only manual M5 Cart Drawer visual probe using isolated Chrome CDP pipe.
 * Exact M390/PC1440 *viewport* (avoids Chrome headless 500px window minimum).
 * GET-only: no test orders, payments, coupons or cart mutations.
 * Run from Windows: node scripts/manual_m5_dev_viewport_uat.js
 */
const fs=require('node:fs'),path=require('node:path'),os=require('node:os');
const {spawn}=require('node:child_process');
const chrome=process.env.CHROME_EXE||'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const out=path.join(os.tmpdir(),'byl-cart-mobile-fixed-footer-m7-20261013');
if(fs.existsSync(out))throw Error('Refusing to overwrite prior screenshots: '+out);
fs.mkdirSync(out,{recursive:true});
const profile=path.join(out,'isolated-profile');
const args=[
 '--headless=new','--remote-debugging-pipe','--no-first-run',
 '--no-default-browser-check','--disable-background-networking',
 '--disable-extensions', '--disable-gpu','--hide-scrollbars',
 '--user-data-dir='+profile,'about:blank'
];
const p=spawn(chrome,args,{stdio:['ignore','ignore','pipe','pipe','pipe'],windowsHide:true});
let nextId=1,buf='',errors='';
const pending=new Map();
p.stderr.on('data',b=>{errors+=String(b).slice(0,3000)});
p.stdio[4].on('data',chunk=>{
 buf+=chunk.toString('utf8');
 let pos;
 while((pos=buf.indexOf('\0'))>=0){
  const raw=buf.slice(0,pos);buf=buf.slice(pos+1);
  if(!raw)continue;
  let value;
  try{value=JSON.parse(raw)}catch{continue}
  const receiver=pending.get(value.id);
  if(!receiver)continue;
  pending.delete(value.id);
  if(value.error) receiver.reject(Error(value.error.message||JSON.stringify(value.error)));
  else receiver.resolve(value.result||{});
 }
});
function call(method,params={},sessionId=undefined,timeout=20000){
 const id=nextId++;
 const message={id,method,params};
 if(sessionId)message.sessionId=sessionId;
 const promise=new Promise((resolve,reject)=>{
  const deadline=setTimeout(()=>{pending.delete(id);reject(Error('CDP timeout '+method))},timeout);
  pending.set(id,{resolve:x=>{clearTimeout(deadline);resolve(x)},
    reject:x=>{clearTimeout(deadline);reject(x)}});
 });
 p.stdio[3].write(JSON.stringify(message)+'\0');
 return promise;
}
function pause(ms){return new Promise(r=>setTimeout(r,ms))}
(async()=>{
 try {
  const {targetId}=await call('Target.createTarget',{url:'about:blank'});
  const {sessionId}=await call('Target.attachToTarget',{targetId,flatten:true});
  await call('Page.enable',{},sessionId);
  const url='https://dev.beyourlover.com/?byl_cart_drawer_preview=1';
  const evidence=[];
  for(const c of [{name:'M390',width:390,height:850,mobile:true},
                  {name:'PC1440',width:1440,height:900,mobile:false}]){
   await call('Emulation.setDeviceMetricsOverride',{
    width:c.width,height:c.height,deviceScaleFactor:1,mobile:c.mobile,
    screenWidth:c.width,screenHeight:c.height,scale:1
   },sessionId);
   await call('Emulation.setTouchEmulationEnabled',{enabled:c.mobile,maxTouchPoints:c.mobile?1:1},sessionId);
   await call('Page.navigate',{url},sessionId);
   // Wait for the footer-loaded drawer JS and initial Woo GET; site assets
   // can take longer than Page.navigate's commit event to become interactive.
   for(let attempt=0;attempt<30;attempt++){
     await pause(1000);
     try{
       const ready=await call('Runtime.evaluate',{expression:
         "Boolean(window.BYL_Cart_Drawer_Preview && document.querySelector('[data-byl-cart-drawer]') && !document.querySelector('[data-byl-cart-drawer]').hidden)",returnByValue:true},sessionId);
       if(ready.result?.value===true)break;
     }catch(e){if(attempt===29)console.error('READY_WAIT',e.message)}
   }
   // Apply exact current candidate CSS to real Dev DOM, without writing to
   // WWW/Dev, so layout UAT is independent of the deployment gate.
   const css=fs.readFileSync(path.join(__dirname,'../theme/beyourlover-v3/assets/css/cart-drawer.css'),'utf8');
   const cssJS='(()=>{let st=document.createElement("style");st.id="byl-fixed-footer-candidate";st.textContent='+JSON.stringify(css)+';document.head.appendChild(st);return true})()';
   await call('Runtime.evaluate',{expression:cssJS,returnByValue:true},sessionId);
   // Intercept only the browser's Store API cart GET with a screenshot fixture.
   // No POST, coupon, cart mutation, order or payment request is executed.
   const mockedCart={
     items:[
       {key:'tara',id:51,name:'TARA S2 tara',quantity:2,permalink:'https://example.invalid/',
        quantity_limits:{editable:true,minimum:1,maximum:10},variation:[],images:[],
        prices:{price:'7900',regular_price:'9900'},totals:{line_total:'15800'}},
       {key:'skull',id:52,name:'SKULL MAN',quantity:1,permalink:'https://example.invalid/',
        quantity_limits:{editable:true,minimum:1,maximum:10},variation:[],images:[],
        prices:{price:'9600',regular_price:'14900'},totals:{line_total:'9600'}}
     ],
     coupons:[],shipping_address:{country:'US',state:'CA',city:'',postcode:'',address_1:''},
     has_calculated_shipping:true,needs_shipping:true,
     shipping_rates:[{shipping_rates:[{selected:true,rate_id:'flat_rate:10'}]}],
     totals:{currency_code:'USD',currency_minor_unit:2,total_items:'25400',
       total_discount:'0',total_shipping:'1000',total_price:'26400'},
     extensions:{andy_commerce_marketing_v1:{schema_version:1,state:'hidden',show_progress:false,
       authority:'woocommerce',claim_free_shipping:false,gift_enabled:false}}
   };
   const fixtureJS=JSON.stringify(mockedCart);
   const injectResponse="(async()=>{const fixture="+fixtureJS+";"+
     "const original=window.fetch.bind(window);"+
     "window.fetch=(resource,init={})=>{const url=String(resource);"+
       "if(url.includes('/wc/store/v1/cart')){if(init.method&&init.method!=='GET')throw Error('NO_MUTATIONS');"+
       "return Promise.resolve({ok:true,headers:{get(){return null}},json:async()=>fixture});}"+
       "return original(resource,init);};"+
     "await window.BYL_Cart_Drawer_Preview.refresh();return true;})()";
   const updated=await call('Runtime.evaluate',{
     expression:injectResponse,awaitPromise:true,returnByValue:true},sessionId);
   if(updated.result?.value!==true)throw Error('Mocked cart did not render '+c.name);
   const capture='(()=>{let s=document.querySelector(".byl-drawer__scroll"),f=document.querySelector(".byl-drawer__footer"),cta=document.querySelector("[data-drawer-checkout]"),terms=document.querySelector(".byl-drawer__terms"),logos=document.querySelector(".byl-drawer__payment-methods");let rect=e=>e?{top:Math.round(e.getBoundingClientRect().top),bottom:Math.round(e.getBoundingClientRect().bottom)}:null;return {scrollTop:s.scrollTop,scrollHeight:s.scrollHeight,clientHeight:s.clientHeight,cartScroll:document.querySelector(".byl-drawer__cart").scrollTop,footer:rect(f),cta:rect(cta),terms:rect(terms),logos:rect(logos),viewport:innerHeight}})()';
   const before=(await call('Runtime.evaluate',{expression:capture,returnByValue:true},sessionId)).result?.value;
   await call('Runtime.evaluate',{expression:
     "(()=>{const x=document.querySelector('.byl-drawer__scroll');x.scrollTop=x.scrollHeight;return x.scrollTop})()",returnByValue:true},sessionId);
   await pause(800);
   const after=(await call('Runtime.evaluate',{expression:capture,returnByValue:true},sessionId)).result?.value;
   console.log('FOOTER_FIXED_'+c.name+'='+JSON.stringify({before,after}));
   if(c.mobile){
     if(!(after.scrollTop>before.scrollTop)||after.scrollHeight<=after.clientHeight)
       throw Error('Mobile content does not scroll independently');
     for(const key of ['footer','cta','terms','logos']){
       if(!before[key]||!after[key]||
          Math.abs(before[key].top-after[key].top)>1 ||
          Math.abs(before[key].bottom-after[key].bottom)>1)
         throw Error('Mobile '+key+' incorrectly moves with cart scrolling');
     }
     if(after.logos.bottom>c.height+1||after.footer.bottom>c.height+1)
       throw Error('Bottom footer extends beyond viewport');
   }
   const expression=`(()=>{const d=document.documentElement;
     const drawer=document.querySelector('[data-byl-cart-drawer]');
     const progress=document.querySelector('[data-drawer-m5-progress]');
     const promo=document.querySelector('[data-drawer-marketing-slots]');
     const recs=[...document.querySelectorAll('.byl-drawer__rec-add')]
       .map(el=>({left:Math.round(el.getBoundingClientRect().left),
                  right:Math.round(el.getBoundingClientRect().right)}));
     return {width:innerWidth,height:innerHeight,scrollWidth:d.scrollWidth,
       readyState:document.readyState,
       scriptPresent:!!document.querySelector('script[src*="cart-drawer.js"]'),
       apiPresent:!!window.BYL_Cart_Drawer_Preview,
       configPresent:!!window.BYLCartDrawerPreview,
       giftValue:window.BYLCartDrawerPreview?.giftEnabled,
       drawerOpen:!!drawer&&!drawer.hidden,progressExists:!!progress,
       progressHidden:!progress||progress.hidden,promoHidden:!promo||promo.hidden,
       navigationVisible:!!document.querySelector('.byl-drawer__steps'),
       recommendations:recs,
       totalsText:document.querySelector('[data-drawer-totals]')?.innerText,
       footerText:document.querySelector('[data-drawer-footer-total]')?.textContent,
       deliveryText:document.querySelector('[data-drawer-delivery]')?.textContent,
       termsVisible:!!document.querySelector('.byl-drawer__terms') && getComputedStyle(document.querySelector('.byl-drawer__terms')).display!=='none',
       termsText:document.querySelector('.byl-drawer__terms')?.innerText,
       noticeText:document.querySelector('.byl-drawer__charges-notice')?.innerText,
       paymentCount:[...document.querySelectorAll('.byl-drawer__payment-methods img')].filter(el=>el.naturalWidth>0).length,
       paymentAlts:[...document.querySelectorAll('.byl-drawer__payment-methods img')].map(el=>el.alt),
       shippingSource:'isolated browser GET fixture (no Woo writes)'};})()`;
   const ev=await call('Runtime.evaluate',{expression,returnByValue:true},sessionId);
   const result=ev.result?.value;
   if(!result)throw Error('No viewport probe result '+c.name);
   const shot=await call('Page.captureScreenshot',{format:'png',fromSurface:true,
     captureBeyondViewport:false},sessionId);
   const png=path.join(out,c.name+'.png');
   fs.writeFileSync(png,Buffer.from(shot.data,'base64'));
   evidence.push({name:c.name,...result,file:png,bytes:fs.statSync(png).size});
   console.log('VIEWPORT_'+c.name+'='+JSON.stringify(result));
   if(result.width!==c.width||!result.drawerOpen||!result.progressExists||!result.promoHidden)
    throw Error('Bad viewport or Cart state '+c.name);
   if(result.scrollWidth>c.width+1)throw Error('Horizontal overflow '+c.name+': '+result.scrollWidth);
   if(!String(result.totalsText).includes('Shipping (estimated)') ||
      !String(result.totalsText).includes('$10.00') ||
      !String(result.totalsText).includes('$264.00') ||
      !String(result.totalsText).includes('$254.00') ||
      result.footerText!=='$264.00' ||
      !String(result.noticeText).includes('import duties and local taxes') ||
      !result.termsVisible || !String(result.termsText).includes('Terms and Conditions') ||
      result.paymentCount < 4)
     throw Error('Owner footer or estimated total failed '+c.name);


  }
  fs.writeFileSync(path.join(out,'evidence.json'),JSON.stringify(evidence,null,2));
  console.log('CART_MOBILE_FIXED_FOOTER_SCROLL_INDEPENDENCE=PASS; MOCK_ONLY=YES; GET_ONLY=YES; FILES='+out);
  await call('Browser.close',{},undefined,3000).catch(()=>{});
 }catch(e){console.error('M5_VIEWPORT_FAIL',e.stack||e.message);
  if(errors)console.error('CHROME_STDERR',errors.slice(-700));
  process.exitCode=1;
 }finally{
  try{p.stdio[3].end()}catch{}
  if(!p.killed)p.kill();
  for(const v of pending.values())v.reject(Error('Browser closed'));
 }
})();
