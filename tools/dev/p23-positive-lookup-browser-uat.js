'use strict';
// Safe Dev-only positive and negative order lookup UAT. No payment/checkout mutation.
// Run with PLAYWRIGHT_MODULE_PATH set if Playwright isn't installed in local node_modules.
const {chromium} = require(process.env.PLAYWRIGHT_MODULE_PATH || 'playwright');
const url='https://dev.beyourlover.com/track-your-order/';
const orderId=process.env.P23_SYNTHETIC_ORDER_ID || '39921';
if(!/^\d{4,12}$/.test(orderId)) throw new Error('Expected synthetic order ID');
const goodEmail='byl-p23-lookup@example.invalid';
const badEmail='byl-p23-wrong@example.invalid';
(async()=>{
 const browser=await chromium.launch({channel:'chrome',headless:true});
 let bad=0;
 try{
  for(const width of [390,1536]){
   const page=await browser.newPage({viewport:{width,height:width===390?844:900}});
   const errors=[];page.on('pageerror',e=>errors.push(e.message));
   for(const [label,email,expectedPositive] of [['correct-email',goodEmail,true],['wrong-email',badEmail,false]]){
     const load=await page.goto(url,{waitUntil:'domcontentloaded',timeout:45000});
     const form=page.locator('form.track_order');
     await form.locator('input[name=orderid]').fill(orderId);
     await form.locator('input[name=order_email]').fill(email);
     const noncePresent=(await form.locator('input[name=woocommerce-order-tracking-nonce]').count())===1;
     await Promise.allSettled([
       page.waitForNavigation({waitUntil:'domcontentloaded',timeout:20000}),
       form.locator('button').first().click(),
     ]);
     const state=await page.evaluate(()=>{
       const nativeStatus=document.querySelector('.order-info');
       const err=document.querySelector('.woocommerce-error');
       const detailSection=document.querySelector('.woocommerce-order-details,.woocommerce-order-overview');
       const aftership=document.querySelector('table.my_account_tracking,.as-track-button');
       return {
         statusText:nativeStatus?.innerText?.trim().slice(0,150)||'',
         hasNativeStatus:!!nativeStatus,
         hasError:!!err,
         errorText:err?.innerText?.slice(0,140)||'',
         hasWooOrderDetails:!!detailSection,
         hasAfterShip:!!aftership,
         overflow:Math.max(0,document.documentElement.scrollWidth-innerWidth),
         unsafeSecretsInUrl:location.href.includes('order_key='),
       };
     });
     const pass=load.status()===200&&noncePresent&&state.overflow===0&&!state.unsafeSecretsInUrl&&!errors.length&&(
       expectedPositive?(state.hasNativeStatus&&state.statusText.includes(orderId)&&!state.hasError&&!state.hasAfterShip):
       (!state.hasNativeStatus&&state.hasError&&!state.hasWooOrderDetails&&!state.hasAfterShip)
     );
     if(!pass)bad++;
     console.log('P23_POSITIVE_LOOKUP_UAT',JSON.stringify({width,scenario:label,pass,noncePresent,...state,jsErrors:errors.slice(0,2)}));
   }
   await page.close();
  }
 }finally{await browser.close();}
 console.log('P23_POSITIVE_LOOKUP_OVERALL',bad===0?'PASS':'FAIL '+bad);
 if(bad)process.exitCode=2;
})().catch(e=>{console.error('P23_POSITIVE_LOOKUP_ERROR',e.message);process.exitCode=2});
