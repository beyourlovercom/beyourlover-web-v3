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
const out=path.join(os.tmpdir(),'byl-m5-cdp-real-viewports-20261012');
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
   await pause(5500);
   const expression=`(()=>{const d=document.documentElement;
     const drawer=document.querySelector('[data-byl-cart-drawer]');
     const progress=document.querySelector('[data-drawer-m5-progress]');
     const promo=document.querySelector('[data-drawer-marketing-slots]');
     const recs=[...document.querySelectorAll('.byl-drawer__rec-add')]
       .map(el=>({left:Math.round(el.getBoundingClientRect().left),
                  right:Math.round(el.getBoundingClientRect().right)}));
     return {width:innerWidth,height:innerHeight,scrollWidth:d.scrollWidth,
       drawerOpen:!!drawer&&!drawer.hidden,progressExists:!!progress,
       progressHidden:!progress||progress.hidden,promoHidden:!promo||promo.hidden,
       navigationVisible:!!document.querySelector('.byl-drawer__steps'),
       recommendations:recs,shippingSource:'Woo read-only'};})()`;
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
  }
  fs.writeFileSync(path.join(out,'evidence.json'),JSON.stringify(evidence,null,2));
  console.log('M5_DEV_REAL_VIEWPORTS=PASS; GET_ONLY=YES; FILES='+out);
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
