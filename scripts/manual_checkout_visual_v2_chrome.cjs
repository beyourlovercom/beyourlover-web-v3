/* Visual Polish V2 local-only Chrome matrix; NO external requests / payments. */
'use strict';
const assert=require('node:assert/strict');
const fs=require('node:fs');
const path=require('node:path');
const {pathToFileURL}=require('node:url');
const {chromium}=require('playwright-core');
const executable=process.env.BYL_CHROME_BIN;
if(!executable || !fs.existsSync(executable)) throw Error('Set BYL_CHROME_BIN to isolated Chrome');
const html=path.resolve(__dirname,'fixtures/checkout-visual-v2-owner-fixture.html');
const out=path.resolve(__dirname,'../docs/checkout-visual-v2/fixtures');
(async()=>{
 fs.mkdirSync(out,{recursive:true});
 const browser=await chromium.launch({executablePath:executable,headless:true,args:['--no-first-run','--disable-extensions']});
 try{
  for(const v of [
    {label:'M320-Shipping',width:320,height:740,step:'shipping'},
    {label:'M390-Shipping',width:390,height:844,step:'shipping'},
    {label:'M390-Payment',width:390,height:844,step:'payment'},
    {label:'iPhone14-Shipping',width:390,height:844,step:'shipping'},
    {label:'PC1440-Payment',width:1440,height:900,step:'payment'}
  ]){
   const context=await browser.newContext({
     viewport:{width:v.width,height:v.height},deviceScaleFactor:1,
     isMobile:v.label.startsWith('iPhone14'),hasTouch:v.label.startsWith('iPhone14')
   });
   const page=await context.newPage();
   await page.emulateMedia({reducedMotion:'reduce'});
   const errors=[],offsite=[];
   page.on('pageerror',e=>errors.push(e.message));
   page.on('request',r=>{if(!r.url().startsWith('file://'))offsite.push(r.url())});
   await page.goto(pathToFileURL(html).href+'?step='+v.step,{waitUntil:'load'});
   await page.waitForTimeout(100);
   const state=await page.evaluate(()=>{
     const measure=sel=>{const el=document.querySelector(sel);if(!el)return null;const r=el.getBoundingClientRect();return{x:r.x,y:r.y,w:r.width,h:r.height,bottom:r.bottom}};
     const shown=sel=>{const el=document.querySelector(sel);return !!el && getComputedStyle(el).display!=='none' && el.getBoundingClientRect().height>0};
     return{
       width:innerWidth,scrollWidth:document.documentElement.scrollWidth,
       bodyClasses:document.body.className,
       adminBarVisibility:getComputedStyle(document.querySelector('#wpadminbar')).visibility,
       panel:measure('.byl-checkout-drawer-panel'),
       cta:measure('[data-byl-next]'),
       summary:measure('.byl-checkout-summary'),
       payment:shown('[data-byl-step="payment"]>.byl-step-body'),
       shipping:shown('[data-byl-step="shipping"]>.byl-step-body'),
       formCount:document.querySelectorAll('form.checkout').length,
       ctaCount:document.querySelectorAll('[data-byl-next]').length,
       promotionalRate:document.querySelector('[data-byl-promotion]')?.dataset.bylVisualRate,
       promoText:document.querySelector('.byl-promo-message')?.textContent,
       accountLabel:document.querySelector('.byl-account-entry a')?.textContent,
       paymentTotal:measure('.byl-drawer-payment-total')
     };
   });
   assert.deepEqual(errors,[],v.label+': browser errors');
   assert.deepEqual(offsite,[],v.label+': offsite browser requests disallowed');
   assert.equal(state.scrollWidth,v.width,v.label+': horizontal overflow');
   assert.equal(state.formCount,1,v.label+': duplicate native form');
   assert.equal(state.ctaCount,1,v.label+': duplicated primary CTA');
   assert.equal(state.shipping,v.step==='shipping');
   assert.equal(state.payment,v.step==='payment');
   assert.equal(state.adminBarVisibility,'hidden','WP admin toolbar must not overlay close icon');
   assert.equal(state.accountLabel,'Account','Logged-in profile link must be quiet');
   assert.equal(state.promotionalRate,'available','Only Woo shipping option makes free rate available');
   assert.doesNotMatch(state.promoText,/unlocked/,'Paid selected rate is not free shipping applied');
   if(v.width<=760){
     assert.equal(state.panel.w,v.width,'mobile Drawer must cover width');
     assert(state.cta.w > v.width*.65,'mobile primary CTA width');
     const before=await page.locator('[data-byl-next]').boundingBox();
     await page.locator('[data-byl-checkout-drawer-scroll]').evaluate(node=>node.scrollTop=400);
     const after=await page.locator('[data-byl-next]').boundingBox();
     assert(Math.abs(before.y-after.y)<1,'CTA must remain fixed while scrolling');
     await page.locator('[data-byl-checkout-drawer-scroll]').evaluate(node=>node.scrollTop=0);
   } else {
     assert.equal(Math.round(state.panel.x+state.panel.w),v.width,'desktop right Drawer edge');
     assert.equal(Math.round(state.panel.w),1010,'original Cart width invariant');
   }
   await page.screenshot({path:path.join(out,'SYNTHETIC-LOCAL-'+v.label+'.png'),animations:'disabled'});
   console.log('VISUAL_V2_CHROME_PASS '+v.label+' '+JSON.stringify(state));
   await context.close();
  }
  console.log('VISUAL_V2_BROWSER_MATRIX_ALL_PASS (synthetic only; genuine Owner Dev UAT not claimed)');
 }finally{await browser.close();}
})().catch(e=>{console.error(e);process.exitCode=1});
