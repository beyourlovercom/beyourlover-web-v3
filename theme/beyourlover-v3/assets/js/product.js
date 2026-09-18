(()=>{
  const root=document.querySelector('[data-byl-product-gallery]');
  if(!root)return;
  const main=root.querySelector('.byl-product-main-image');
  const thumbs=[...root.querySelectorAll('[data-byl-product-thumb]')];
  if(!main||!thumbs.length)return;
  const activate=(button)=>{
    thumbs.forEach(item=>{const active=item===button;item.classList.toggle('is-active',active);item.setAttribute('aria-pressed',active?'true':'false')});
    const src=button.dataset.src||'';
    if(src)main.src=src;
    main.dataset.fullSrc=button.dataset.fullSrc||src;
    main.alt=button.dataset.alt||main.alt;
  };
  thumbs.forEach(button=>button.addEventListener('click',()=>activate(button)));
})();

(()=>{
  const rail=document.querySelector('[data-byl-pdp-m02-rail]');
  if(!rail)return;
  const cards=[...rail.querySelectorAll('.byl-pdp-m02-card')];
  cards.forEach(card=>card.setAttribute('tabindex','0'));
})();

(()=>{
  const root=document.querySelector('[data-byl-pdp-m06]');
  if(!root)return;
  const dataNode=root.querySelector('.byl-pdp-m06-data');
  let items=[];try{items=JSON.parse(dataNode?.textContent||'[]')}catch(e){return}
  if(!Array.isArray(items)||!items.length)return;
  const active=root.querySelector('[data-byl-pdp-m06-active]');
  const next=root.querySelector('[data-byl-pdp-m06-next]');
  const count=root.querySelector('[data-byl-pdp-m06-count]');
  let index=0;
  const card=(item,isActive)=>{
    const detail=item.detail?'<button type="button" class="byl-pdp-m06-more" aria-label="Toggle details">＋</button><div class="byl-pdp-m06-detail"><h4>'+item.title+'</h4><p>'+item.detail+'</p></div>':'';
    return '<div class="byl-pdp-m06-media" style="background-image:url(&quot;'+item.media+'&quot;)"></div><div class="byl-pdp-m06-scrim"></div>'+detail+'<div class="byl-pdp-m06-copy"><div class="byl-pdp-m06-eyebrow">FEATURE</div><h3>'+item.title+'</h3><p>'+item.short+'</p></div>';
  };
  const render=()=>{
    const a=items[index],n=items[(index+1)%items.length];active.classList.remove('is-expanded');active.innerHTML=card(a,true);next.innerHTML=card(n,false);
    if(count)count.textContent=String(index+1).padStart(2,'0')+' / '+String(items.length).padStart(2,'0');
    const more=active.querySelector('.byl-pdp-m06-more');if(more)more.addEventListener('click',()=>{active.classList.toggle('is-expanded');more.classList.toggle('is-open')});
  };
  const go=d=>{index=(index+d+items.length)%items.length;render()};
  root.querySelector('[data-byl-pdp-m06-prev]')?.addEventListener('click',()=>go(-1));
  root.querySelector('[data-byl-pdp-m06-next-button]')?.addEventListener('click',()=>go(1));
  next.addEventListener('click',()=>go(1));render();
})();
