#!/usr/bin/env node
'use strict';
/**
 * Checkout Newsletter isolated event tests. Intentionally NO browser network,
 * WordPress boot, real REST, subscriber records, emails or Woo orders.
 * Uses the exact checkout-newsletter.js file in this GitHub checkout.
 */
const vm=require('node:vm'),fs=require('node:fs'),path=require('node:path'),assert=require('node:assert/strict');
const script=fs.readFileSync(path.join(__dirname,'../theme/beyourlover-v3/assets/js/checkout-newsletter.js'),'utf8');
function field(values){
  return {...values,events:{},addEventListener(name,fn){(this.events[name]??=[]).push(fn)},async emit(name){for(const fn of this.events[name]||[])await fn()}};
}
async function session({ready=true,policy='newsletter-v1',endpoint='https://dev.beyourlover.com/wp-json/andy-core/v1/newsletter/subscribe',response=200,delayResponse=false}={}){
  const calls=[],consent=field({checked:false,disabled:!ready});
  const status=field({textContent:''}),email=field({value:'',validity:{valid:true}});
  const box={dataset:{bylNewsletterReady:ready?'1':'0',bylNewsletterEndpoint:endpoint,bylNewsletterPolicy:ready?policy:''},
    querySelector(s){return {'[data-byl-newsletter-consent]':consent,'[data-byl-newsletter-status]':status}[s]||null}};
  const root={querySelector(s){return {'[data-byl-checkout-newsletter]':box,'#billing_email':email}[s]||null}};
  const document={readyState:'complete',documentElement:{lang:'en'},querySelector(s){return s==='[data-byl-progressive-checkout]'?root:null}};
  const window={location:{origin:'https://dev.beyourlover.com',pathname:'/checkout/'}};
  let releaseResponse=()=>{};
  const responseGate=delayResponse?new Promise(resolve=>{releaseResponse=resolve}):null;
  const fetch=async(url,options)=>{
    calls.push({url,options,data:JSON.parse(options.body)});
    if(responseGate) await responseGate;
    return {ok:response===200,status:response,json:async()=>({success:response===200})};
  };
  vm.runInNewContext(script,{URL,AbortController,document,window,fetch,console},{filename:'checkout-newsletter.js'});
  return {calls,consent,email,status,releaseResponse};
}
async function flush(){await new Promise(r=>setTimeout(r,0))}
(async()=>{
  let assertions=0;
  function check(actual,expected,message){assert.deepEqual(actual,expected,message);assertions++}
  let s=await session({ready:false});
  check(s.consent.disabled,true,'OFF cannot be checked');
  check(s.consent.checked,false,'OFF unchecked');
  check(s.calls.length,0,'OFF no request');
  check(s.status.textContent,'Email offers are not available yet.','OFF message');

  s=await session();
  check(s.consent.disabled,false,'eligible enabled');
  check(s.consent.checked,false,'optional defaults unchecked');
  s.consent.checked=true;await s.consent.emit('change');await flush();
  check(s.calls.length,0,'no request with missing email');
  check(s.status.textContent.includes('Enter a valid email'),true,'email hint');
  s.email.value='qa+checkout@example.test';await s.email.emit('blur');await flush();
  check(s.calls.length,1,'exactly one request');
  check(s.calls[0].url,'https://dev.beyourlover.com/wp-json/andy-core/v1/newsletter/subscribe','same-site REST');
  check(s.calls[0].options.method,'POST','correct method');
  check(s.calls[0].data.email,'qa+checkout@example.test','provided email');
  check(s.calls[0].data.marketing_consent,true,'explicit consent');
  check(s.calls[0].data.consent_policy,'newsletter-v1','policy');
  check(s.calls[0].data.source,'byl_checkout_optional','isolated source');
  check(s.calls[0].options.credentials,'omit','no Woo cookies');
  check(s.calls[0].options.referrerPolicy,'no-referrer','no leaking referrer');
  check(s.status.textContent.includes('not subscribed yet'),true,'pending != subscribed');
  check(s.consent.disabled,false,'input unlocked');
  // An email change AFTER a pending confirmation needs renewed consent.
  s.email.value='qa+different@example.test';await s.email.emit('input');await flush();
  check(s.consent.checked,false,'email change revokes old checkbox consent');
  check(s.status.textContent.includes('Check the box again'),true,'renewed consent guidance');
  await s.email.emit('blur');await flush();
  check(s.calls.length,1,'blur must NOT subscribe a different email automatically');
  s.consent.checked=true;await s.consent.emit('change');await flush();
  check(s.calls.length,2,'explicit second check permits new pending confirmation');
  check(s.calls[1].data.email,'qa+different@example.test','request targets newly approved email');
  s.consent.checked=false;await s.consent.emit('change');
  check(s.calls.length,2,'uncheck does not resubmit');

  // Email edit during an outstanding network response must not leave the
  // prior opt-in attached to the new email or trigger another request on blur.
  s=await session({delayResponse:true});
  s.email.value='qa+inflight@example.test';s.consent.checked=true;
  await s.consent.emit('change');await flush();
  check(s.calls.length,1,'one pending request while fetch is in flight');
  check(s.consent.disabled,true,'control disabled during request');
  s.email.value='qa+modified@example.test';await s.email.emit('input');
  s.releaseResponse();await flush();await flush();
  check(s.consent.checked,false,'in-flight email change revokes old consent after response');
  check(s.status.textContent.includes('Email changed'),true,'no stale confirmation for new email');
  check(s.consent.disabled,false,'network completion restores checkbox');
  await s.email.emit('blur');await flush();
  check(s.calls.length,1,'email edited in flight cannot auto subscribe');

  s=await session({response:503});
  s.email.value='qa+fail@example.test';s.consent.checked=true;await s.consent.emit('change');await flush();
  check(s.calls.length,1,'failed submission attempted only once');
  check(s.consent.checked,false,'failure clears consent');
  check(s.consent.disabled,false,'failure releases control');
  check(s.status.textContent.includes('You can still complete checkout'),true,'checkout unaffected');

  s=await session();s.email.value='invalid';s.email.validity.valid=false;
  s.consent.checked=true;await s.consent.emit('change');await flush();
  check(s.calls.length,0,'invalid email no request');

  s=await session({endpoint:'https://untrusted.example.test/wp-json/andy-core/v1/newsletter/subscribe'});
  check(s.consent.disabled,true,'cross-origin blocked');
  check(s.calls.length,0,'no cross-origin request');
  console.log('PASS: '+assertions+' isolated Newsletter consent-event assertions; no network / mail / business writes');
})().catch(e=>{console.error(e);process.exitCode=1});
