/* Optional manual-only Playwright Core visual regression on the local fixture.
 * Usage:
 *  NODE_PATH=/tmp/byl-drawer-visual-pw/node_modules \
 *  BYL_CHROME_BIN="/Applications/Google Chrome.app/Contents/MacOS/Google Chrome" \
 *  node scripts/manual_checkout_drawer_option_b_visual.cjs
 * Does not contact any site, use Woo credentials or create orders/payments.
 */
'use strict';
const assert=require('node:assert/strict');
const path=require('node:path');
const fs=require('node:fs');
const {pathToFileURL}=require('node:url');
const {chromium}=require('playwright-core');
const outfile=path.resolve(__dirname,'../docs/checkout-drawer/fixtures');
const html=path.resolve(__dirname,'fixtures/checkout-drawer-owner-visual.html');
const executable=process.env.BYL_CHROME_BIN;
if(!executable || !fs.existsSync(executable)) throw Error('Set BYL_CHROME_BIN to an installed isolated Chrome/Chromium binary.');
(async()=>{
 const browser=await chromium.launch({executablePath:executable,headless:true,args:['--no-first-run','--disable-extensions']});
 try{
  fs.mkdirSync(outfile,{recursive:true});
  const views=[
   {label:'M320-Payment',width:320,height:740,step:'payment'},
   {label:'M390-Payment',width:390,height:844,step:'payment'},
   {label:'M390-Shipping',width:390,height:844,step:'shipping'},
   {label:'PC1440-Payment',width:1440,height:900,step:'payment'}
  ];
  for(const v of views){
   const page=await browser.newPage({viewport:{width:v.width,height:v.height},deviceScaleFactor:1});
   await page.emulateMedia({reducedMotion:'reduce'}); // deterministic geometry: no entering animation offset
   let offsite=0;const errors=[];
   page.on('pageerror',e=>errors.push(e.message));
   page.on('request',request=>{if(!request.url().startsWith('file://'))offsite++});
   await page.goto(pathToFileURL(html).href+'?step='+v.step,{waitUntil:'load'});
   const state=await page.evaluate(()=>{
    const bounding=sel=>{
      const r=document.querySelector(sel).getBoundingClientRect();
      return {x:r.x,y:r.y,width:r.width,height:r.height};
    };
    const visible=sel=>getComputedStyle(document.querySelector(sel)).display!=='none';
    return {
      panel:bounding('.byl-checkout-drawer-panel'),
      footer:bounding('.byl-checkout-next'),
      summary:bounding('.byl-checkout-summary'),
      windowWidth:innerWidth,scrollWidth:document.documentElement.scrollWidth,
      shippingStepVisible:visible('[data-byl-step="shipping"]>.byl-step-body'),
      paymentStepVisible:visible('[data-byl-step="payment"]>.byl-step-body'),
      totalTitle:document.querySelector('.byl-summary-toggle strong').textContent,
      ctaCount:document.querySelectorAll('[data-byl-next]').length,
      nativeFormCount:document.querySelectorAll('form.checkout').length
    };
   });
   assert.equal(offsite,0,v.label+': unexpected network access');
   assert.deepEqual(errors,[],v.label+': unexpected browser errors');
   assert.equal(state.scrollWidth,v.width,v.label+': horizontal overflow');
   assert.equal(state.ctaCount,1,v.label+': duplicate CTA');
   assert.equal(state.nativeFormCount,1,v.label+': duplicate Woo form');
   assert.equal(state.shippingStepVisible,v.step==='shipping',v.label+': wrong visible shipping state');
   assert.equal(state.paymentStepVisible,v.step==='payment',v.label+': wrong visible payment state');
   if(v.width<=760){
     assert.equal(state.panel.width,v.width,'Mobile panel edge-to-edge');
     assert.equal(state.totalTitle,'Order total');
     assert(state.footer.x>=0 && state.footer.width>v.width*0.6,'Single mobile footer stays inside viewport');
     const before=await page.locator('.byl-checkout-next').boundingBox();
     await page.locator('[data-byl-checkout-drawer-scroll]').evaluate(el=>el.scrollTop=450);
     const after=await page.locator('.byl-checkout-next').boundingBox();
     assert(Math.abs(before.y-after.y)<1,'CTA must remain fixed while checkout scrolls');
     await page.locator('[data-byl-checkout-drawer-scroll]').evaluate(el=>el.scrollTop=0);
   }else{
     assert.equal(Math.round(state.panel.x+state.panel.width),v.width,'PC Drawer must touch right screen edge');
     assert.equal(Math.round(state.panel.width),1010,'Desktop drawer mirrors existing Cart width');
     assert.equal(state.totalTitle,'Your Cart');
   }
   const filename=path.join(outfile,'LOCAL-FIXTURE-'+v.label+'.png');
   await page.screenshot({path:filename,animations:'disabled'});
   console.log('VISUAL_FIXTURE_PASS '+v.label+' '+JSON.stringify(state)+' screenshot='+filename);
   await page.close();
  }
  console.log('OWNER_OPTION_B_LOCAL_FIXTURE_BROWSER_MATRIX_PASS; actual Dev Woo Owner UAT remains PENDING');
 }finally{await browser.close()}
})().catch(err=>{console.error(err);process.exitCode=1});
