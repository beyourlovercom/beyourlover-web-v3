/* N5.1 isolated all-message lab — synthetic DOM only; never calls Woo or network. */
(() => {
  'use strict';
  const notify=window.BYL && window.BYL.notify;
  const surfaces=window.BYL && window.BYL.surfaces;
  if (typeof notify!=='function' || !surfaces) return;
  const $=id=>document.getElementById(id);
  const error=$('demo-error'), field=$('demo-coupon'), shipping=$('demo-shipping-strip');
  const dialog=$('demo-dialog');
  let sequence=0, returnFocus=null;
  const success=(key,message)=>notify({type:'success',confirmed:true,context:'n5-mock',key:key+'-'+(++sequence),message});
  const resetError=()=>{notify.inlineError(error,'');field.removeAttribute('aria-invalid');};
  const setError=value=>{notify.inlineError(error,value);field.setAttribute('aria-invalid','true');};
  const setShipping=(message,type)=>{
    notify.status($('demo-status'),message);
    shipping.classList.toggle('is-applied',type==='applied');
    shipping.classList.toggle('is-available',type==='available');
  };
  const emptyStates={
    cart:{title:'Your cart is empty',message:"Discover something you'll love. Your next favorite is waiting.",label:'Explore products',symbol:'♧'},
    wishlist:{title:'Nothing saved yet',message:'Your favorite finds will appear here when you save them.',label:'Browse collection',symbol:'♡'},
    search:{title:'No results found',message:'Try a different search term or discover our collection.',label:'Explore all products',symbol:'⌕'}
  };
  const emptyMarkup={
    cart:'<svg viewBox="0 0 48 48" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 18h24l-2 23H14l-2-23Z"/><path d="M18 19v-6a6 6 0 0 1 12 0v6"/></svg>'
  };
  const showEmpty=type=>{
    if(!Object.prototype.hasOwnProperty.call(emptyStates,type))return;
    const s=emptyStates[type];
    // All values come from fixed reviewed strings; changing state never creates fake Woo products.
    $('demo-empty-title').textContent=s.title;
    $('demo-empty-description').textContent=s.message;
    const cta=$('demo-empty-cta');
    cta.replaceChildren(document.createTextNode(s.label+' '),(()=>{const n=document.createElement('span');n.textContent='→';n.setAttribute('aria-hidden','true');return n})());
    const symbol=document.querySelector('.byl-empty-state__symbol');
    if (symbol) {
      if(type==='cart')symbol.innerHTML=emptyMarkup.cart;
      else symbol.textContent=s.symbol;
    }
    $('demo-empty-footnote').hidden=type!=='cart';
    document.querySelectorAll('[data-demo-empty]').forEach(button=>{
      const selected=button.getAttribute('data-demo-empty')===type;
      button.classList.toggle('is-selected',selected);
      button.setAttribute('aria-pressed',String(selected));
    });
  };
  const noticesOriginal={};
  document.querySelectorAll('[data-demo-notice]').forEach(node=>{
    noticesOriginal[node.dataset.demoNotice]={
      title:node.querySelector('.byl-notice__body strong').textContent,
      message:node.querySelector('.byl-notice__body p').textContent
    };
  });
  const resetNotices=()=>{
    document.querySelectorAll('[data-demo-notice]').forEach(node=>{
      const b=node.querySelector('.byl-notice__body'), value=noticesOriginal[node.dataset.demoNotice];
      b.querySelector('strong').textContent=value.title;
      b.querySelector('p').textContent=value.message;
    });
  };
  const actions={
    add:()=>success('add','Added to your cart'),
    remove:()=>success('remove','Removed from your cart'),
    coupon:()=>{resetError();success('coupon','Promo code applied')},
    'toast-info':()=>notify({type:'info',message:'Your changes have been saved',context:'n5-mock',key:'info-'+(++sequence)}),
    burst:()=>{for(let i=0;i<5;i++)success('burst','Added to your cart')},
    invalid:()=>setError('This promo code has expired. Please check it and retry.'),
    'form-clear':resetError,
    'shipping-available':()=>setShipping('Free shipping available — choose this delivery option to apply it.','available'),
    'shipping-applied':()=>setShipping('Free shipping applied — selected by WooCommerce (mock only).','applied'),
    'shipping-reset':()=>setShipping('Shipping is calculated at checkout.','neutral'),
    'notices-reset':resetNotices,
    'notices-long':()=>{document.querySelector('[data-demo-notice="info"] .byl-notice__body p').textContent='Shipping options are calculated from the destination and available services. Longer notices wrap naturally without clipping information, pushing action controls offscreen, or hiding the details a customer needs to read.'},
    'notice-retry':()=>{setError('Please enter a different code to try again.');field.focus();$('preview-form').scrollIntoView({block:'center',behavior:'instant'});},
    'empty-cta':()=>notify({type:'info',message:'Preview only — no shop navigation',context:'n5-mock',key:'mock-cta-'+(++sequence)}),
    'dialog-open':()=>{
      returnFocus=document.activeElement;
      notify.invalidateAll();
      if(typeof dialog.showModal==='function')dialog.showModal();
      else dialog.setAttribute('open','');
      const cancel=dialog.querySelector('[data-demo-action="dialog-close"]');
      cancel.focus();
    },
    'dialog-close':()=>{if(typeof dialog.close==='function')dialog.close();else dialog.removeAttribute('open');},
    'dialog-confirm':()=>{actions['dialog-close']();notify({type:'info',message:'No items changed — preview only',context:'n5-mock',key:'dialog-'+(++sequence)})}
  };
  dialog.addEventListener('close',()=>{if(returnFocus && returnFocus.isConnected && typeof returnFocus.focus==='function')returnFocus.focus();returnFocus=null;});
  dialog.addEventListener('click',event=>{if(event.target===dialog)actions['dialog-close']()});
  document.querySelectorAll('[data-demo-action]').forEach(button=>{
    const action=actions[button.dataset.demoAction];
    if(action)button.addEventListener('click',action);
  });
  document.querySelectorAll('[data-demo-empty]').forEach(button=>button.addEventListener('click',()=>showEmpty(button.dataset.demoEmpty)));
  field.addEventListener('input',()=>{if(!error.hidden)resetError()});
  const mode=new URLSearchParams(location.search).get('scenario');
  if(mode==='success')actions.add();
  else if(mode==='error')actions.invalid();
  else if(mode==='empty')showEmpty('cart');
  else if(mode==='warning')document.querySelector('[data-demo-notice="warning"]').scrollIntoView({block:'center'});
})();
