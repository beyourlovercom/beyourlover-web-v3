'use strict';
/**
 * P2.3 Dev-only negative query regression. Never submits a valid order/email pair.
 * PLAYWRIGHT_MODULE_PATH may point to an already installed Playwright module.
 * EXISTING_TEST_ORDER_ID is optional; only safe wrong-email probes are submitted.
 */
const {chromium} = require(process.env.PLAYWRIGHT_MODULE_PATH || 'playwright');
const URL = 'https://dev.beyourlover.com/track-your-order/';
const existing = process.env.EXISTING_TEST_ORDER_ID;
if (existing && !/^\d{1,12}$/.test(existing)) throw Error('Invalid test order ID');
const cases = [{name:'unknown-order',id:'999999998'}];
if (existing) cases.push({name:'existing-order-wrong-email',id:existing});

(async ()=>{
  const browser = await chromium.launch({channel:'chrome',headless:true});
  let failures = 0;
  try {
    for (const width of [390,1536]) {
      const page=await browser.newPage({viewport:{width,height:width===390?844:900}});
      const errors=[];page.on('pageerror',e=>errors.push(e.message));
      for(const example of cases) {
        const response=await page.goto(URL,{waitUntil:'domcontentloaded',timeout:45000});
        const form=page.locator('form.track_order');
        await form.locator('input[name=orderid]').fill(example.id);
        await form.locator('input[name=order_email]').fill('byl-track-negative@example.invalid');
        const nonce=await form.locator('input[name=woocommerce-order-tracking-nonce]').count();
        await Promise.allSettled([
          page.waitForNavigation({waitUntil:'domcontentloaded',timeout:18000}),
          form.locator('button').first().click(),
        ]);
        const state=await page.evaluate(()=>({
          orderDetails:document.querySelectorAll('.woocommerce-order-details,.woocommerce-order-overview,table.order_details').length,
          trackingTable:document.querySelectorAll('.woocommerce-order-tracking table').length,
          wooErrorCount:document.querySelectorAll('.woocommerce-error').length,
          orderStatusExposed:!!document.querySelector('.order-status.processing,.order-status.completed'),
          horizontalOverflow:Math.max(0,document.documentElement.scrollWidth-innerWidth),
        }));
        const pass=response.status()===200 && nonce===1 &&
          state.orderDetails===0 && state.trackingTable===0 && !state.orderStatusExposed &&
          state.wooErrorCount>=1 && state.horizontalOverflow===0 && errors.length===0;
        if(!pass)failures++;
        console.log('P23_LOOKUP_NEGATIVE',JSON.stringify({width,case:example.name,pass,...state,jsErrors:errors.length}));
      }
      await page.close();
    }
  }finally{await browser.close();}
  console.log('P23_LOOKUP_NEGATIVE_RESULT',failures===0?'PASS':'FAIL '+failures);
  if(failures)process.exitCode=1;
})().catch(e=>{console.error('P23_LOOKUP_NEGATIVE_FAILED',e.message);process.exitCode=1});
