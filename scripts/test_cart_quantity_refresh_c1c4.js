'use strict';
const assert = require('assert');
const fs = require('fs');
const vm = require('vm');
const path = require('path');
const source = fs.readFileSync(path.join(__dirname,'../theme/beyourlover-v3/assets/js/cart-experience.js'),'utf8');
let submitCount=0, timerId=1, scheduled = new Map(), hooks={}, observerCallback;
const badgeLink={
  aria:'',badge:null,
  setAttribute(name,value){if(name==='aria-label')this.aria=value;},
  querySelector(){return this.badge;},
  appendChild(node){this.badge=node;},
};
function element(tag) {
  return {
    tag,nodeType:1,events:{},className:'',textContent:'',type:'',
    setAttribute(name,value){this[name]=value;},
    addEventListener(name,fn){this.events[name]=fn;},
    dispatchEvent(ev){this.events[ev.type]?.(ev);},
    appendChild(item){this.children=this.children||[];this.children.push(item);if(tag==='span'&&item.tag==='input')item.wrapper=this;else if(item.className==='byl-focus-cart-badge')badgeLink.badge=item;},
    remove(){if(this===badgeLink.badge)badgeLink.badge=null;},
  };
}
function formFactory(value='1', minimum='1', maximum='9') {
 const form={connected:true,submitCount:0,bylCartUpdateTimer:null,inputs:[],submit:{disabled:true},
  querySelector(selector){return selector.includes('update_cart')?this.submit:null;},
  querySelectorAll(selector){return selector.includes('input.qty')?this.inputs:[];},
  requestSubmit(button){assert.equal(button,this.submit);this.submitCount++;submitCount++;},
 };
 const input=element('input');
 input.min=minimum; input.max=maximum; input.step='1'; input.value=value; input.connected=true; input.wrapper=null;
 input.parentNode={insertBefore(wrapper,original){assert.strictEqual(original,input); input.wrapper=wrapper;}};
 input.closest=function(selector){if(selector==='.byl-cart-qty-stepper')return this.wrapper; return selector==='form.woocommerce-cart-form'?form:null;};
 form.inputs=[input]; return {form,input};
}
let {form,input}=formFactory('1'), current={form,input};
const scope={
 querySelectorAll(selector){return selector.includes('input.qty')&&current.form?[current.input]:[];},
 querySelector(selector){return selector==='.woocommerce-cart-form'?current.form:null;},
};
const document={readyState:'complete',body:{contains(node){return !!node.connected;}},querySelector(selector){
 if(selector==='.byl-cart-runtime')return scope;
 if(selector==='.byl-focus-cart-icon')return badgeLink;
 return null;
},createElement:element};
const jquery=function(){return{on(events,handler){events.split(' ').forEach(name=>hooks[name]=handler);}}};
const window={jQuery:jquery,clearTimeout(id){scheduled.delete(id);},setTimeout(fn){const id=timerId++;scheduled.set(id,fn);return id;}};
const Event=class {constructor(type){this.type=type;}};
const MutationObserver=class {constructor(cb){observerCallback=cb;}observe(){}};
vm.runInNewContext(source,{document,window,Event,MutationObserver});
function tick(){const callbacks=[...scheduled.values()];scheduled.clear();callbacks.forEach(fn=>fn());}
assert(input.wrapper,'initial decoration');
assert(input.wrapper.className==='byl-cart-qty-stepper');
const plus=input.wrapper.children[2], minus=input.wrapper.children[0];
plus.dispatchEvent(new Event('click'));
assert.equal(input.value,'2');
plus.dispatchEvent(new Event('click'));
assert.equal(input.value,'3');
assert.equal(scheduled.size,1,'rapid taps coalesce to one native Woo form update');
tick();
assert.equal(submitCount,1,'one native Woo form submit');
assert.equal(form.submit.disabled,false,'update button enabled before submit');
input.value='9';
plus.dispatchEvent(new Event('click'));
assert.equal(input.value,'9','max stock respected');
input.value='1';
minus.dispatchEvent(new Event('click'));
assert.equal(input.value,'1','min quantity respected');
hooks.updated_wc_div();
assert.equal(input.wrapper.children.length,3,'same input never wrapped twice');
// Woo re-renders the cart form as a fresh fragment.
form.connected=false;input.connected=false;
const next=formFactory('4','1','6');
current=next;form=next.form;input=next.input;
hooks.updated_wc_div();
assert(input.wrapper,'replacement Woo fragment gets steppers');
assert.equal(badgeLink.aria,'Cart, 4 items','header cart count reflects fresh Woo cart form');
assert.equal(badgeLink.badge.textContent,'4');
plus.dispatchEvent(new Event('click'));tick();
assert.equal(submitCount,1,'detached input cannot submit after Woo fragment update');
input.wrapper.children[2].dispatchEvent(new Event('click'));tick();
assert.equal(submitCount,2,'new active input updates via native Woo form');
// A second replacement detected by the MutationObserver also gets controls.
const third=formFactory('2');
current=third;form=third.form;input=third.input;
observerCallback([{addedNodes:[{nodeType:1,matches:()=>false,querySelector:()=>true}]}]);
assert(input.wrapper,'observer reattaches steppers for DOM replacement');
// Empty cart: Woo removes the form; no stale header count.
current={form:null,input:null};
hooks.updated_wc_div();
assert.equal(badgeLink.aria,'Cart, 0 items');
assert.equal(badgeLink.badge,null,'empty cart badge removed');
assert(!source.includes('window.location.reload()'),'native Woo updates stay authoritative, no forced reload');
console.log('PASS cart C1-C4 native quantity stepper: debounce, min/max, one submit, refresh/observer, stale input, badge, empty cart');
