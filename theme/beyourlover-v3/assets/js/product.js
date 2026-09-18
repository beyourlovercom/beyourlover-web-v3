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
