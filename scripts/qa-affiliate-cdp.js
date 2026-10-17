/* Isolated Chrome CDP responsive QA, no external browser libraries.
 * node scripts/qa-affiliate-cdp.js /absolute/path/to/affiliate-preview.html
 * Chrome must run with --remote-debugging-port=18777 on an isolated profile.
 */
'use strict';
const fs = require('node:fs');
const page = process.argv[2];
if (!page || !fs.existsSync(page)) throw new Error('pass existing synthetic HTML preview');
(async () => {
  const targets = await (await fetch('http://127.0.0.1:18777/json')).json();
  const tab = targets.find((item) => item.webSocketDebuggerUrl && item.type === 'page');
  if (!tab) throw new Error('no isolated CDP target');
  const socket = new WebSocket(tab.webSocketDebuggerUrl);
  await new Promise((resolve, reject) => { socket.onopen=resolve; socket.onerror=reject; });
  let counter=0;
  const pending = new Map();
  socket.onmessage = ({data}) => {
    const packet=JSON.parse(data);
    if (packet.id && pending.has(packet.id)) {
      const {resolve,reject}=pending.get(packet.id);
      pending.delete(packet.id);
      return packet.error ? reject(new Error(packet.error.message)) : resolve(packet.result);
    }
  };
  const call=(method,params={})=>new Promise((resolve,reject)=>{
    const id=++counter;
    pending.set(id,{resolve,reject});
    socket.send(JSON.stringify({id,method,params}));
  });
  try {
    await call('Page.enable');
    await call('Runtime.enable');
    const expr = 'JSON.stringify({innerWidth,scrollWidth:document.documentElement.scrollWidth,rootWidth:document.querySelector(".byl-affiliate").getBoundingClientRect().width,nav:getComputedStyle(document.querySelector(".byl-affiliate-bottom-nav")).display,sidebar:getComputedStyle(document.querySelector(".byl-affiliate-rail")).display,cards:[...document.querySelectorAll(".byl-affiliate-stat")].map(x=>Math.round(x.getBoundingClientRect().width)),lastCardRight:Math.round(document.querySelector(".byl-affiliate-stats article:last-child").getBoundingClientRect().right)})';
    for(const [width,height,mobile] of [[1440,1000,false],[390,844,true],[414,896,true]]) {
      await call('Emulation.setDeviceMetricsOverride',{width,height,deviceScaleFactor:1,mobile});
      await call('Page.navigate',{url:'file://'+page});
      await new Promise(r=>setTimeout(r,1300));
      const result=await call('Runtime.evaluate',{expression:expr,returnByValue:true});
      const metrics=JSON.parse(result.result.value);
      if (metrics.scrollWidth > width + 2) throw new Error('horizontal overflow '+width+' -> '+metrics.scrollWidth);
      if (mobile && (metrics.sidebar !== 'none' || metrics.nav === 'none')) throw new Error('mobile navigation failure');
      if (!mobile && metrics.nav !== 'none') throw new Error('desktop bottom-nav should be hidden');
      const shot=await call('Page.captureScreenshot',{format:'png',captureBeyondViewport:false});
      const target='/tmp/byl-affiliate-cdp-'+width+'.png';
      fs.writeFileSync(target,Buffer.from(shot.data,'base64'));
      console.log('PASS '+width+' viewport '+JSON.stringify(metrics)+' screenshot='+target);
    }
    console.log('CDP_RESPONSIVE_PASS');
  } finally { socket.close(); }
})().catch(e=>{ console.error('FAIL',e.message);process.exitCode=1; });
