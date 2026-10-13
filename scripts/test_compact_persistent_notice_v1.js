'use strict';
/* Compact Persistent Notice V1: source-only, fail-closed offline guard. */
const assert=require('node:assert/strict');
const fs=require('node:fs');
const path=require('node:path');
const root=path.resolve(__dirname,'..');
const get=(p)=>fs.readFileSync(path.join(root,p),'utf8');
const css=get('theme/beyourlover-v3/assets/css/notification-persistent-compact.css');
const manifest=JSON.parse(get('theme/beyourlover-v3/theme-os.json'));
const module=manifest.modules.notification;
const boot=get('theme/beyourlover-v3/inc/theme-os-bootstrap.php');
const cart=get('theme/beyourlover-v3/inc/notification-cart.php');
const original=get('theme/beyourlover-v3/assets/css/notification-surfaces.css');
const workflow=get('.github/workflows/foundation-ci.yml');
const expected='assets/css/notification-persistent-compact.css';
assert(module.kind==='presentation','Compact Notice remains presentation-only');
assert(module.files.includes(expected),'Theme OS ZIP includes opt-in CSS');
assert(original.includes('.byl-notice{'),'existing notice base remains intact');
assert(css.includes('.byl-notice.byl-notice--compact{'),'explicit opt-in required');
assert(!css.includes('!important'),'no force-overrides');
for(const variant of ['success','error','warning','info']){
  assert(css.includes('.byl-notice--'+variant+'{'),'missing semantic variant '+variant);
}
for(const entry of ['gap:8px','padding:7px 10px','font:400 13px/1.38','width:16px','height:16px','border-inline-start:3px','@media(min-width:768px)','@media(max-width:450px)','min-height:44px','overflow-wrap:anywhere'])
  assert(css.includes(entry),'compact layout/accessibility token missing: '+entry);
for(const forbidden of ['.woocommerce-', '.byl-toast','.byl-field-error','.byl-confirm','position:fixed','display:none!important','text-overflow:ellipsis','-webkit-line-clamp','max-height:','height:40px','overflow:hidden'])
  assert(!css.includes(forbidden),'unsafe global, clipped or transient rule: '+forbidden);
assert(!boot.includes('notification-persistent-compact.css'),'runtime bootstrap unchanged');
assert(!cart.includes('notification-persistent-compact.css'),'Dev N6 runtime not enabled by this change');
assert(workflow.includes('node scripts/test_compact_persistent_notice_v1.js'),'offline regression in CI');
console.log('COMPACT_PERSISTENT_NOTICE_V1_OFFLINE_PASS: scoped opt-in, 4 types, 13px, small spacing, accessible controls, no runtime activation');
