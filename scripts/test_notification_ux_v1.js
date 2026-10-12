'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const root = path.resolve(__dirname, '..');
const source = fs.readFileSync(path.join(root,'theme/beyourlover-v3/assets/js/notification.js'),'utf8');
const css = fs.readFileSync(path.join(root,'theme/beyourlover-v3/assets/css/notification.css'),'utf8');
const html = fs.readFileSync(path.join(root,'docs/ux/byl-notification-v1-preview.html'),'utf8');
const manifest = JSON.parse(fs.readFileSync(path.join(root,'theme/beyourlover-v3/theme-os.json'),'utf8'));
class Element {
  constructor(tag) {
    this.tag = tag; this.children = []; this.attributes = {}; this.textContent = '';
    this.hidden = false; this.isConnected = true; this.removed = false; this.classes = new Set();
    this.classList = { add:(v)=>this.classes.add(v),remove:(v)=>this.classes.delete(v) };
  }
  appendChild(c) {this.children.push(c); c.parent = this; c.isConnected=true; return c;}
  remove() {this.removed=true; this.isConnected=false; if(this.parent) this.parent.children=this.parent.children.filter(c=>c!==this);}
  setAttribute(n,v) {this.attributes[n]=String(v);}
}
let id=0, timers=new Map(), clock=0, events={};
const window = {
  BYL:{}, setTimeout(fn, delay){const key=++id;timers.set(key,{fn,at:clock+delay});return key;},
  clearTimeout(id){timers.delete(id)}, requestAnimationFrame(fn){fn()},
  addEventListener(name,fn){events[name]=fn}
};
const body = new Element('body');
const document = {body, visibilityState:'visible',
  activeElement:{matches:()=>false},
  createElement(tag){return new Element(tag)},
  querySelectorAll(){return []},
  addEventListener(name,fn){events[name]=fn}
};
vm.runInNewContext(source,{window,document,Date,Map,Array,String,Number}, {filename:'notification.js'});
const notify=window.BYL.notify;
function tick(ms) {
  const end=clock+ms;
  for(let i=0;i<60;i++){
    const tasks=[...timers].sort((a,b)=>a[1].at-b[1].at);
    if(!tasks.length||tasks[0][1].at>end)break;
    clock=tasks[0][1].at;timers.delete(tasks[0][0]);tasks[0][1].fn();
  }
  clock=end;
}
assert.equal(typeof notify,'function');
assert.equal(notify({type:'success',message:'Unsafe optimistic action'}),false);
assert.equal(notify({type:'error',message:'Wrong severity'}),false);
assert.equal(body.children.length,0);
assert.equal(notify({type:'success',confirmed:true,context:'cart',key:'A',message:'Added to your cart'}),true);
assert.equal(body.children.length,1);
const stack=body.children[0];
assert.equal(stack.children.length,1);
assert.equal(stack.children[0].children[1].textContent,'Added to your cart');
assert.equal(stack.children[0].attributes['aria-label'],'Added to your cart');
assert.equal(notify({type:'success',confirmed:true,context:'cart',key:'A',message:'Added to your cart'}),false);
notify({type:'info',message:'Hi',key:'B'});
assert.equal(stack.children.length,1,'only one toast visible');
tick(2230);
assert.equal(stack.children.length,1,'queued message follows after first exits');
tick(2230);
assert.equal(stack.children.length,0,'toast eventually disappears');
notify({type:'success',confirmed:true,context:'cart',key:'X',message:'<img src=x onerror=alert(1)>'});
assert.equal(stack.children[0].children[1].textContent,'<img src=x onerror=alert(1)>');
assert.equal(stack.children[0].children.length,2,'no HTML nodes from message');
notify.clear('cart');
assert.equal(stack.children.length,0);
const error=new Element('p');
assert.equal(notify.inlineError(error,'Invalid coupon'),true);
assert.equal(error.hidden,false);
assert.equal(error.attributes.role,'alert');
tick(10000);
assert.equal(error.textContent,'Invalid coupon','error persists');
notify.inlineError(error,'');
assert.equal(error.hidden,true);
const status=new Element('p');
notify.status(status,'Free shipping available, not applied');
assert.equal(status.textContent,'Free shipping available, not applied');
document.activeElement={matches:()=>true};
notify({type:'info',key:'focus',message:'Deferred'});
assert.equal(stack.children.length,0,'focused input avoids overlay');
document.activeElement={matches:()=>false};events.focusout();
assert.equal(stack.children.length,1);
notify.invalidateAll();assert.equal(stack.children.length,0);
notify({type:'info',key:'page',message:'Page change'});
events.pagehide();
assert.equal(body.children.length,0);
assert.equal(notify({type:'info',message:'After navigation'}),false);
assert(css.includes('--byl-brand') && css.includes('prefers-reduced-motion'));
assert(css.includes('calc(100vw - 24px)'));
assert(css.includes('.byl-toast.is-leaving{transform:translateX(calc(100vw + 24px));opacity:0}'), 'Toast must exit to the right');
assert(!css.includes('translateX(calc(-100vw'), 'Toast must never slide left on exit');
assert(html.includes('viewport-fit=cover') && html.includes('nonfunctional mock'));
assert(manifest.modules.notification && manifest.modules.notification.kind==='presentation');
assert.equal(manifest.modules.notification.files.length,7);
const n5php = fs.readFileSync(path.join(root, 'theme/beyourlover-v3/notification-preview-v1.php'), 'utf8');
const n5js = fs.readFileSync(path.join(root, 'theme/beyourlover-v3/assets/js/notification-preview.js'), 'utf8');
const n5css = fs.readFileSync(path.join(root, 'theme/beyourlover-v3/assets/css/notification-preview.css'), 'utf8');
assert(n5php.includes("$host !== 'dev.beyourlover.com'"));
assert(n5php.includes("$flag !== '1'"));
assert(n5php.includes('no-store') && n5php.includes("connect-src 'none'"));
assert(n5php.includes('No WordPress bootstrap'));
assert(n5php.includes('id="preview-empty"') && n5php.includes('id="preview-notices"'));
assert(n5php.includes('id="preview-dialog"') && n5php.includes('id="preview-form"'));
assert(n5php.includes('notification-surfaces.js') && n5php.includes('notification-surfaces.css'));
const surfaceJS=fs.readFileSync(path.join(root,'theme/beyourlover-v3/assets/js/notification-surfaces.js'),'utf8');
const surfaceCSS=fs.readFileSync(path.join(root,'theme/beyourlover-v3/assets/css/notification-surfaces.css'),'utf8');
assert(surfaceJS.includes('renderNotice') && surfaceJS.includes('renderEmpty') && !surfaceJS.includes('fetch('));
assert(surfaceJS.includes('textContent') && !surfaceJS.includes('.innerHTML'));
for (const type of ['success','error','warning','info']) assert(surfaceCSS.includes('.byl-notice--'+type) || type === 'success' && surfaceCSS.includes('.byl-notice{'));
assert(surfaceCSS.includes('.byl-empty-state') && surfaceCSS.includes('.byl-confirm'));
assert(surfaceCSS.includes('prefers-reduced-motion'));
assert(n5js.includes('data-demo-action') && !n5js.includes('fetch('));
assert(n5css.includes('@media(max-width:720px)'));

assert(!source.includes('.innerHTML'), 'dynamic notification text must never use innerHTML');
console.log('Notification UX V1 N2/N3/N4 offline behavior/contract PASS');
