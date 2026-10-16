/* Exact Woo SelectWoo and country-select from the installed read-only
 * WooCommerce public assets, NOT an imitation dropdown.
 * All test interactions run against file:// fixture. No orders/cart/posts.
 */
'use strict';
const assert=require('node:assert/strict');
const path=require('node:path'),fs=require('node:fs');
const {pathToFileURL}=require('node:url');
const {chromium}=require('playwright-core');
const executable=process.env.BYL_CHROME_BIN;
const vendor=process.env.BYL_NATIVE_WOO_ASSETS;
const fixture=path.resolve(__dirname,'fixtures/checkout-native-woo-selectwoo-overlay-p0.html');
const outputDir=path.resolve(__dirname,'../docs/checkout-visual-v2/fixtures');
if(!vendor || !fs.existsSync(path.join(vendor,'selectWoo.full.min.js')))throw Error('Set BYL_NATIVE_WOO_ASSETS to privately GET-fetched authentic WooCommerce SelectWoo assets.');
function isDescendantOf(el,p){return el===p||p.contains(el)}
(async()=>{
 const browser=await chromium.launch({executablePath:executable,headless:true,args:['--disable-extensions']});
 try{
  for(const width of [320,390,1440]){
   const context=await browser.newContext({viewport:{width,height:844}});
   const page=await context.newPage();
   const errors=[];
   page.on('pageerror',e=>errors.push(e.message));
   await page.goto(pathToFileURL(fixture).href,{waitUntil:'load'});
   await page.addStyleTag({path:path.join(vendor,'select2.css')});
   await page.addScriptTag({path:path.join(vendor,'jquery.min.js')});
   await page.addScriptTag({path:path.join(vendor,'selectWoo.full.min.js')});
   await page.evaluate(()=>{
     window.wc_country_select_params={
       countries:JSON.stringify({
         US:{CA:'California',NY:'New York'},
         DE:{BY:'Bavaria',BE:'Berlin'},
         CN:{YN:'Yunnan',BJ:'Beijing'}
       }),
       i18n_select_state_text:'Select a state / province',
       i18n_searching:'Searching...',
       i18n_no_matches:'No matches',
       i18n_input_too_long_1:'Too long',i18n_input_too_long_n:'Too long',
       i18n_input_too_short_1:'Too short',i18n_input_too_short_n:'Too short',
       i18n_load_more:'Load more',i18n_selection_too_long_1:'Too many',
       i18n_selection_too_long_n:'Too many'
     };
     window.__wooCountryChanged=[];
     window.jQuery(document.body).on('country_to_state_changed',(_event,country)=>{
       window.__wooCountryChanged.push(country)
     });
   });
   await page.addScriptTag({path:path.join(vendor,'country-select.js')});
   await page.waitForTimeout(120);
   const select=page.locator('#billing_country');
   assert.equal(await select.count(),1);
   const control=page.locator('#billing_country_field .select2-selection');
   assert.equal(await control.count(),1,'Native Woo must enhance country selector');
   await control.click();
   const option=page.locator('.select2-results__option').filter({hasText:'Germany'}).first();
   await option.waitFor({state:'visible'});
   const baseline=await option.evaluate(el=>{
     const r=el.getBoundingClientRect();
     const point=document.elementFromPoint(r.x+r.width/2,r.y+r.height/2);
     const selectContainer=el.closest('.select2-container--open');
     return {
       dropdownParent:selectContainer&&selectContainer.parentElement===document.body?'body':'not-body',
       wooDropdownZ:getComputedStyle(el.closest('.select2-dropdown')).zIndex,
       drawerZ:getComputedStyle(document.querySelector('.byl-checkout-drawer-root')).zIndex,
       hitBelongsToOption:point===el||el.contains(point),
       actualHit:point?.className?.toString().slice(0,100)
     };
   });
   assert.equal(baseline.dropdownParent,'body','Woo SelectWoo default dropdown attached to body');
   assert.equal(baseline.wooDropdownZ,'1051','Real Woo native CSS dropdown z-index');
   assert.equal(baseline.drawerZ,'1200','Real BYL drawer z-index');
   assert.equal(baseline.hitBelongsToOption,false,'Reproduce invisible/unclickable popup behind BYL Drawer');
   // Load the actual reviewed V2 CSS (not a test-only inline workaround).
   // This regression fails if the source code loses the native Woo fix.
   await page.addStyleTag({path:path.resolve(__dirname,
     '../theme/beyourlover-v3/assets/css/checkout-drawer.css')});
   const after=await option.evaluate(el=>{
     const r=el.getBoundingClientRect();
     const point=document.elementFromPoint(r.x+r.width/2,r.y+r.height/2);
     return {
       openContainerZ:getComputedStyle(el.closest('.select2-container--open')).zIndex,
       optionReceivesPointer:point===el||el.contains(point),
       actualHit:point?.className?.toString().slice(0,100)
     };
   });
   assert.equal(after.optionReceivesPointer,true,'Native popup must be click-accessible over BYL panel');
   await option.click();
   await page.waitForTimeout(170);
   assert.equal(await select.inputValue(),'DE','Real native Woo SelectWoo selected country');
   const stateOptions=await page.locator('#billing_state option').allTextContents();
   assert(stateOptions.some(s=>s==='Bavaria'), 'Native Woo country → province state list updates');
   const countryEvents=await page.evaluate(()=>window.__wooCountryChanged);
   assert(countryEvents.includes('DE'),'Real Woo country_to_state_changed event must fire');
   // Woo also creates a native SelectWoo state/province dropdown, appended
   // outside our Drawer. It must be clickable, not just the Country dropdown.
   const stateControl=page.locator('#billing_state_field .select2-selection');
   assert.equal(await stateControl.count(),1,'Woo native state SelectWoo initialized');
   await stateControl.click();
   const bavaria=page.locator('.select2-results__option').filter({hasText:'Bavaria'}).first();
   await bavaria.waitFor({state:'visible'});
   const provinceHit=await bavaria.evaluate(el=>{
     const r=el.getBoundingClientRect();
     const hit=document.elementFromPoint(r.x+r.width/2,r.y+r.height/2);
     return hit===el||el.contains(hit);
   });
   assert.equal(provinceHit,true,'Native province dropdown is above BYL overlay');
   await bavaria.click();
   assert.equal(await page.locator('#billing_state').inputValue(),'BY','Native Woo province selection commits');
   // Change country again to ensure Woo's state list refresh and re-inits.
   await control.click();
   // Native Woo keyboard/search contract, not only mouse clicking.
   const countrySearch=page.locator('body > .select2-container--open .select2-search__field');
   assert.equal(await countrySearch.count(),1,'Native Woo searchable country chooser remains accessible');
   await countrySearch.fill('China');
   await page.locator('.select2-results__option').filter({hasText:'China'}).first().waitFor({state:'visible'});
   await page.waitForTimeout(90);
   await countrySearch.press('Enter');
   await page.waitForTimeout(140);
   assert.equal(await select.inputValue(),'CN');
   const chinaStates=await page.locator('#billing_state option').allTextContents();
   assert(chinaStates.includes('Yunnan'),'Woo province options refreshed for new country');
   assert((await page.evaluate(()=>window.__wooCountryChanged)).includes('CN'));
   assert.deepEqual(errors,[],'No JS exceptions with native Woo SelectWoo');
   fs.mkdirSync(outputDir,{recursive:true});
   await page.screenshot({path:path.join(outputDir,'SYNTHETIC-NATIVE-WOO-SELECTWOO-'+width+'.png')});
   console.log('WOO_NATIVE_SELECTWOO_REPRO_AND_FIX_PASS '+JSON.stringify({width,baseline,after,selected:'DE',stateOptions,countryEvents}));
   await context.close();
  }
 }finally{await browser.close()}
})().catch(e=>{console.error(e);process.exitCode=1});
