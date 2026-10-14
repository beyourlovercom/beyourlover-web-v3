/**
 * Standalone Blog header/footer behavior for a Post or category archive.
 * Public WP REST search is GET-only. No cookies, orders, mail or DB writes.
 */
(() => {
  'use strict';
  const header=document.querySelector('[data-blog-header]');
  if(!header||header.dataset.editorialReady==='1')return;
  header.dataset.editorialReady='1';
  const menu=header.querySelector('[data-blog-menu-toggle]');
  const menuPane=document.getElementById('byl-blog-mobile-menu');
  const search=header.querySelector('[data-blog-search-toggle]');
  const searchPane=document.getElementById('byl-blog-header-search');
  const input=searchPane?.querySelector('[data-blog-header-query]');
  const aboutButton=document.querySelector('[data-blog-about-toggle]');
  const aboutPane=document.getElementById('byl-blog-about-extra');
  const toggle=(button,pane,open)=>{
    if(!button||!pane)return;
    const next=open===undefined?pane.hidden:Boolean(open);
    pane.hidden=!next;
    button.setAttribute('aria-expanded',String(next));
  };
  menu?.addEventListener('click',()=>toggle(menu,menuPane));
  search?.addEventListener('click',()=>{toggle(search,searchPane);if(!searchPane.hidden)input?.focus();});
  searchPane?.querySelector('[data-blog-search-close]')?.addEventListener('click',()=>toggle(search,searchPane,false));
  aboutButton?.addEventListener('click',()=>{
    toggle(aboutButton,aboutPane);
    const expanded=aboutButton?.getAttribute('aria-expanded')==='true';
    aboutButton.innerHTML=expanded?'READ LESS <span aria-hidden="true">⌃</span>':'READ MORE <span aria-hidden="true">⌄</span>';
  });
  document.addEventListener('keydown',e=>{if(e.key==='Escape'){toggle(menu,menuPane,false);toggle(search,searchPane,false);}});
  let results=searchPane?.querySelector('[data-blog-search-results]');
  if(!results&&searchPane){
    results=document.createElement('div');
    results.dataset.blogSearchResults='';
    results.hidden=true;results.setAttribute('aria-live','polite');
    searchPane.append(results);
  }
  const runSearch=async()=>{
    const term=(input?.value||'').trim().slice(0,100);
    if(!results)return;
    results.replaceChildren();
    results.hidden=false;
    const status=document.createElement('p');
    if(term.length<2){status.textContent='Enter at least 2 characters.';results.append(status);return;}
    status.textContent='Searching published Blog stories…';results.append(status);
    try{
      const endpoint=new URL('/wp-json/wp/v2/search',window.location.origin);
      endpoint.searchParams.set('search',term);
      endpoint.searchParams.set('per_page','8');
      endpoint.searchParams.set('type','post');
      const response=await fetch(endpoint.href,{method:'GET',credentials:'omit',headers:{'Accept':'application/json'}});
      if(!response.ok)throw Error('Read-only search unavailable');
      const found=await response.json();
      results.replaceChildren();
      if(!Array.isArray(found)||!found.length){status.textContent='No published stories matched.';results.append(status);return;}
      for(const item of found){
        if(typeof item?.title!=='string'||typeof item?.url!=='string')continue;
        const link=new URL(item.url,window.location.origin);
        if(link.origin!==window.location.origin)continue;
        const a=document.createElement('a');a.href=link.href;a.textContent=item.title;results.append(a);
      }
    }catch(_){results.replaceChildren();status.textContent='Search currently unavailable. Browse all stories from the Blog home.';results.append(status);}
  };
  searchPane?.querySelector('[data-blog-header-submit]')?.addEventListener('click',runSearch);
  input?.addEventListener('keydown',e=>{if(e.key==='Enter'){e.preventDefault();runSearch();}});
})();
