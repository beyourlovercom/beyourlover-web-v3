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

(()=>{
  const root=document.querySelector('[data-byl-pdp-m10]');
  if(!root)return;
  let items=[];try{items=JSON.parse(root.querySelector('.byl-pdp-m10-data')?.textContent||'[]')}catch(e){return}
  if(!Array.isArray(items)||!items.length)return;
  let index=0,startX=null,dragX=0;
  const stage=root.querySelector('[data-byl-pdp-m10-stage]');
  const rail=root.querySelector('[data-byl-pdp-m10-mobile-rail]');
  const mobileActive=root.querySelector('[data-byl-pdp-m10-mobile-active]');
  const mobileNext=root.querySelector('[data-byl-pdp-m10-mobile-next]');
  const count=root.querySelector('[data-byl-pdp-m10-count]');
  const html=(f,active)=>'<video muted playsinline preload="metadata"'+(active?' controls':'')+' src="'+f.src+'"></video><div class="byl-pdp-m10-badge">'+(active?'ACTIVE VIDEO':'NEXT')+'</div><div class="byl-pdp-m10-creator"><a href="'+f.url+'" target="_blank" rel="nofollow noopener">'+f.creator+'</a></div>';
  const pauseAll=()=>root.querySelectorAll('video').forEach(v=>{try{v.pause()}catch(e){}});
  const renderDesktop=()=>{
    if(!stage)return; stage.innerHTML='';
    [-1,0,1].forEach(offset=>{const i=(index+offset+items.length)%items.length,f=items[i],card=document.createElement('article');card.className='byl-pdp-m10-card'+(offset===0?' is-active':'');card.innerHTML=html(f,offset===0);card.addEventListener('click',e=>{if(e.target.tagName==='A')return;if(offset!==0){pauseAll();index=i;render()}});stage.appendChild(card)});
    if(count)count.textContent=String(index+1).padStart(2,'0')+' / '+String(items.length).padStart(2,'0');
    [...stage.querySelectorAll('video')].forEach((v,i)=>{v.controls=i===1;if(i!==1)v.pause()});
  };
  const renderMobile=(dir=0)=>{
    if(!mobileActive||!mobileNext)return;const a=items[index],n=items[(index+1)%items.length];mobileActive.innerHTML=html(a,true);mobileNext.innerHTML=html(n,false);mobileActive.querySelector('video').controls=true;mobileNext.querySelector('video').controls=false;mobileNext.querySelector('video').pause();if(mobileActive.animate)mobileActive.animate([{opacity:.35,transform:'translateX('+(dir>0?'24px':dir<0?'-24px':'0')+') scale(.96)'},{opacity:1,transform:'translateX(0) scale(1)'}],{duration:320,easing:'cubic-bezier(.2,.7,.2,1)'})
  };
  const render=(dir=0)=>{renderDesktop();renderMobile(dir)};
  const go=d=>{pauseAll();index=(index+d+items.length)%items.length;render(d)};
  root.querySelector('[data-byl-pdp-m10-prev]')?.addEventListener('click',()=>go(-1));root.querySelector('[data-byl-pdp-m10-next]')?.addEventListener('click',()=>go(1));
  mobileNext?.addEventListener('click',e=>{if(e.target.tagName!=='A')go(1)});
  rail?.addEventListener('touchstart',e=>{if(e.touches.length!==1)return;startX=e.touches[0].clientX;dragX=startX},{passive:true});rail?.addEventListener('touchmove',e=>{if(startX===null||e.touches.length!==1)return;dragX=e.touches[0].clientX},{passive:true});rail?.addEventListener('touchend',()=>{if(startX===null)return;const dx=dragX-startX;if(Math.abs(dx)>42)go(dx<0?1:-1);startX=null;dragX=0},{passive:true});
  rail?.addEventListener('pointerdown',e=>{if(e.pointerType==='touch')return;startX=e.clientX;dragX=e.clientX});rail?.addEventListener('pointermove',e=>{if(startX===null||e.pointerType==='touch')return;dragX=e.clientX});rail?.addEventListener('pointerup',e=>{if(startX===null||e.pointerType==='touch')return;const dx=dragX-startX;if(Math.abs(dx)>42)go(dx<0?1:-1);startX=null;dragX=0});render();
})();
