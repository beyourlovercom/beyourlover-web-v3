/**
 * H3 Blog WWW Parity V1 (read-only): all data from inline published WordPress
 * posts. No fetch, REST write, cookies, user data, checkout or mail actions.
 */
(() => {
  'use strict';
  const root = document.querySelector('[data-byl-blogv1]');
  if (!root || root.dataset.enhanced === '1') return;
  root.dataset.enhanced = '1';
  const q = selector => root.querySelector(selector);
  // Independent Blog shell: do not interact with Shop shell/menu/search.
  const blogHeader = document.querySelector('[data-blog-header]');
  const menuToggle = blogHeader?.querySelector('[data-blog-menu-toggle]');
  const menu = document.querySelector('#byl-blog-mobile-menu');
  const searchToggle = blogHeader?.querySelector('[data-blog-search-toggle]');
  const searchPanel = document.querySelector('#byl-blog-header-search');
  const searchInput = searchPanel?.querySelector('[data-blog-header-query]');
  const aboutToggle = document.querySelector('[data-blog-about-toggle]');
  const aboutExtra = document.querySelector('#byl-blog-about-extra');
  function toggleBlogMenu(force){
    if (!menu || !menuToggle) return;
    const open = force === undefined ? menu.hidden : Boolean(force);
    menu.hidden = !open;
    menuToggle.setAttribute('aria-expanded', String(open));
  }
  function toggleBlogSearch(force){
    if (!searchPanel || !searchToggle) return;
    const open = force === undefined ? searchPanel.hidden : Boolean(force);
    searchPanel.hidden = !open;
    searchToggle.setAttribute('aria-expanded', String(open));
    if (open) searchInput?.focus();
  }
  function runBlogSearch(){
    if (!searchInput) return;
    const filter = root.querySelector('[data-explore-search] [name=search]');
    if (!filter) return;
    filter.value = searchInput.value;
    filter.dispatchEvent(new Event('input',{bubbles:true}));
    toggleBlogSearch(false);
    document.querySelector('#byl-blogv1-explore')?.scrollIntoView({block:'start',behavior:'smooth'});
  }
  menuToggle?.addEventListener('click',()=>toggleBlogMenu());
  searchToggle?.addEventListener('click',()=>toggleBlogSearch());
  searchPanel?.querySelector('[data-blog-header-submit]')?.addEventListener('click',runBlogSearch);
  searchPanel?.querySelector('[data-blog-search-close]')?.addEventListener('click',()=>toggleBlogSearch(false));
  searchInput?.addEventListener('keydown',event=>{if(event.key==='Enter'){event.preventDefault();runBlogSearch();}});
  aboutToggle?.addEventListener('click',()=>{
    if (!aboutExtra) return;
    aboutExtra.hidden = !aboutExtra.hidden;
    aboutToggle.setAttribute('aria-expanded',String(!aboutExtra.hidden));
    aboutToggle.innerHTML = aboutExtra.hidden?'READ MORE <span aria-hidden="true">⌄</span>':'READ LESS <span aria-hidden="true">⌃</span>';
  });
  document.addEventListener('keydown',event=>{
    if(event.key==='Escape'){toggleBlogMenu(false);toggleBlogSearch(false);}
  });
  const data = q('#byl-blogv1-data');
  let posts = [];
  try {
    const parsed = JSON.parse(data?.textContent || '[]');
    if (Array.isArray(parsed)) posts = parsed.filter(p => p && Number.isSafeInteger(p.id) && typeof p.title === 'string');
  } catch (_) { posts = []; }
  const toText = x => String(x == null ? '' : x);
  const escapeHtml = x => toText(x).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const safeUrl = url => {
    try {
      const u = new URL(toText(url), window.location.href);
      if (u.protocol === 'https:' || u.protocol === 'http:') return u.href;
    } catch (_) {}
    return '';
  };
  const columnKeys = ['title','category','date','readTime'];
  const columnTitles = {title:'Article title',category:'Category',date:'Published',readTime:'Read time'};
  const state = {query:'',category:'all',sort:'new',view:'cards',page:1,pageSize:6,columns:[...columnKeys],hidden:[]};
  const form = q('[data-explore-search]');
  const sizeSel = q('[data-page-size]');
  const resultEl = q('[data-explore-results]');
  const countEl = q('[data-result-count]');
  const pagesEl = q('[data-pages]');
  const columnsMenu = q('[data-col-menu]');
  const dialog = q('[data-dialog-wrap]');
  const dialogBody = q('[data-dialog-body]');
  let previousFocus = null;
  const articleById = id => posts.find(p => p.id === Number(id));
  function filtered() {
    const query = state.query.toLowerCase().trim();
    const list = posts.filter(p => (state.category === 'all' || p.slug === state.category) &&
      (!query || (p.title+' '+p.category).toLowerCase().includes(query)));
    list.sort((a,b)=>{
      switch(state.sort) {
        case 'old':return a.date.localeCompare(b.date);
        case 'title':return a.title.localeCompare(b.title);
        case 'title-desc':return b.title.localeCompare(a.title);
        case 'category':return a.category.localeCompare(b.category);
        default:return b.date.localeCompare(a.date);
      }
    });
    return list;
  }
  function previewContent(post){
    const title = escapeHtml(post.title);
    const url = safeUrl(post.url);
    const image = safeUrl(post.image);
    return (image ? '<img src="'+escapeHtml(image)+'" alt="'+title+'" loading="lazy">' : '')+
      '<h2 id="byl-blogv1-dialog-title">'+title+'</h2><p>'+escapeHtml(post.category)+' · '+escapeHtml(post.date)+' · '+Number(post.readTime||1)+' min read</p>'+
      '<p>'+escapeHtml(post.excerpt||'Open this published story to continue reading on WordPress.')+'</p>'+
      (url?'<p><a href="'+escapeHtml(url)+'">Read full article →</a></p>':'');
  }
  function openDialog(postId){
    const post = articleById(postId);
    if (!post || !dialog) return;
    previousFocus = document.activeElement;
    dialogBody.innerHTML = previewContent(post);
    dialog.hidden = false;
    const closer = q('[data-modal-close]');
    if (closer) closer.focus();
    document.addEventListener('keydown',onModalKey);
  }
  function onModalKey(event){
    if(event.key==='Escape')closeDialog();
    if(event.key==='Tab' && dialog && !dialog.hidden){
      const items=[...dialog.querySelectorAll('button,a[href]')].filter(x=>!x.disabled);
      if(!items.length){event.preventDefault();return;}
      const first=items[0],last=items[items.length-1];
      if(event.shiftKey&&document.activeElement===first){event.preventDefault();last.focus();}
      else if(!event.shiftKey&&document.activeElement===last){event.preventDefault();first.focus();}
    }
  }
  function closeDialog(){
    if(!dialog || dialog.hidden)return;
    dialog.hidden=true;
    dialogBody.textContent='';
    document.removeEventListener('keydown',onModalKey);
    if(previousFocus && typeof previousFocus.focus==='function')previousFocus.focus();
  }
  function buildColumns(){
    if(!columnsMenu)return;
    columnsMenu.innerHTML = '<strong>Visible columns · ↑↓ to reorder</strong>'+
      state.columns.map((key,i)=>'<div data-col-row>'+
        '<input type="checkbox" aria-label="Show '+escapeHtml(columnTitles[key])+'" data-col-toggle="'+key+'" '+(key==='title'?'disabled ':'')+(!state.hidden.includes(key)?'checked':'')+'>'+
        '<span>'+escapeHtml(columnTitles[key])+'</span>'+
        '<button type="button" data-col-move="'+key+'" data-move="-1" '+(i===0?'disabled':'')+' aria-label="Move column up">↑</button>'+
        '<button type="button" data-col-move="'+key+'" data-move="1" '+(i===state.columns.length-1?'disabled':'')+' aria-label="Move column down">↓</button></div>').join('')+'<button type="button" data-col-done>Done</button>';
  }
  const cell=(post,key)=>{
    const value = key==='readTime'?toText(post.readTime)+' min':toText(post[key]);
    return key==='title'?'<button type="button" data-article="'+post.id+'">'+escapeHtml(value)+'</button>':escapeHtml(value);
  };
  function render(){
    const all = filtered();
    const size = state.pageSize==='all'?Math.max(all.length,1):Number(state.pageSize);
    const max = Math.max(1,Math.ceil(all.length/size));
    state.page=Math.min(Math.max(1,state.page),max);
    const chosen=all.slice((state.page-1)*size,state.page*size);
    countEl.textContent=all.length+' published article'+(all.length===1?'':'s')+' · '+(chosen.length?(((state.page-1)*size)+1):0)+'–'+Math.min(all.length,state.page*size)+' shown';
    const views = [...root.querySelectorAll('[data-view]')];
    views.forEach(el=>el.setAttribute('aria-pressed',el.dataset.view===state.view?'true':'false'));
    if(!chosen.length){resultEl.innerHTML='<p>No published articles match your search.</p>';}
    else if(state.view==='cards'){
      resultEl.innerHTML='<div class="byl-blogv1-results">'+chosen.map(p=>{
        const image=safeUrl(p.image);
        return '<button class="byl-blogv1-result" type="button" data-article="'+p.id+'">'+
          (image?'<img loading="lazy" src="'+escapeHtml(image)+'" alt="'+escapeHtml(p.title)+'">':'<span class="byl-blogv1-image" aria-hidden="true"></span>')+
          '<div class="byl-blogv1-result-content"><em>'+escapeHtml(p.category)+'</em><strong>'+escapeHtml(p.title)+'</strong><small>'+escapeHtml(p.date)+' · '+Number(p.readTime||1)+' min read</small></div></button>';
      }).join('')+'</div>';
    } else {
      const visible=state.columns.filter(key=>!state.hidden.includes(key));
      resultEl.innerHTML='<div class="byl-blogv1-results-table"><table><thead><tr>'+visible.map(k=>'<th><button type="button" data-sort-column="'+k+'">'+escapeHtml(columnTitles[k])+' ↕</button></th>').join('')+
        '</tr></thead><tbody>'+chosen.map(p=>'<tr>'+visible.map(k=>'<td>'+cell(p,k)+'</td>').join('')+'</tr>').join('')+'</tbody></table></div>';
    }
    const buttons = ['<button type="button" data-page="'+(state.page-1)+'" '+(state.page===1?'disabled':'')+' aria-label="Previous page">‹</button>'];
    for(let i=1;i<=max;i++)if(max<=7 || i===1 || i===max || Math.abs(i-state.page)<=1){
      buttons.push('<button type="button" data-page="'+i+'" aria-current="'+(state.page===i?'true':'false')+'">'+i+'</button>');
    }
    buttons.push('<button type="button" data-page="'+(state.page+1)+'" '+(state.page>=max?'disabled':'')+' aria-label="Next page">›</button>');
    pagesEl.innerHTML=buttons.join('');
  }
  const heroSlides=[...root.querySelectorAll('[data-hero-slide]')];
  let heroIndex=0;
  function hero(n){
    if(!heroSlides.length)return;
    heroIndex=((n%heroSlides.length)+heroSlides.length)%heroSlides.length;
    heroSlides.forEach((el,i)=>el.classList.toggle('is-active',i===heroIndex));
    root.querySelectorAll('[data-hero-select]').forEach(el=>el.setAttribute('aria-current',Number(el.dataset.heroSelect)===heroIndex?'true':'false'));
  }
  function categoryScroll(slug,dir){
    const el=[...root.querySelectorAll('[data-cat-track]')].find(x=>x.dataset.catTrack===slug);
    if(el)el.scrollBy({left:dir*(el.clientWidth*.8),behavior:window.matchMedia('(prefers-reduced-motion: reduce)').matches?'auto':'smooth'});
  }
  root.addEventListener('click',event=>{
    const button=event.target.closest('button');
    if(!button || !root.contains(button))return;
    if(button.dataset.heroMove){hero(heroIndex+Number(button.dataset.heroMove));return;}
    if(button.dataset.heroSelect!==undefined){hero(Number(button.dataset.heroSelect));return;}
    if(button.dataset.scrollCat){categoryScroll(button.dataset.scrollCat,Number(button.dataset.direction));return;}
    if(button.dataset.article){openDialog(button.dataset.article);return;}
    if(button.hasAttribute('data-modal-close')){closeDialog();return;}
    if(button.dataset.view){state.view=button.dataset.view;render();return;}
    if(button.hasAttribute('data-col-open')){
      columnsMenu.hidden=!columnsMenu.hidden;
      button.setAttribute('aria-expanded',String(!columnsMenu.hidden));
      if(!columnsMenu.hidden)buildColumns();
      return;
    }
    if(button.hasAttribute('data-col-done')){
      columnsMenu.hidden=true;
      q('[data-col-open]').setAttribute('aria-expanded','false');
      return;
    }
    if(button.dataset.colMove){
      const i=state.columns.indexOf(button.dataset.colMove),j=i+Number(button.dataset.move);
      if(i>=0&&j>=0&&j<state.columns.length){[state.columns[i],state.columns[j]]=[state.columns[j],state.columns[i]];buildColumns();render();}
      return;
    }
    if(button.dataset.page){
      const n=Number(button.dataset.page);if(n>=1){state.page=n;render();}return;
    }
    if(button.dataset.sortColumn){
      const k=button.dataset.sortColumn;
      state.sort=k==='title'?'title':k==='category'?'category':k==='date'?'new':state.sort;
      form.elements.sort.value=state.sort;state.page=1;render();
    }
  });
  root.addEventListener('pointerdown',event=>{
    const toggle=q('[data-col-open]');
    if(!columnsMenu.hidden && !columnsMenu.contains(event.target) && !toggle.contains(event.target)){
      columnsMenu.hidden=true; toggle.setAttribute('aria-expanded','false');
    }
  });
  root.addEventListener('change',event=>{
    if(event.target.dataset.colToggle){
      const key=event.target.dataset.colToggle;
      state.hidden=event.target.checked?state.hidden.filter(x=>x!==key):[...new Set([...state.hidden,key])];
      buildColumns();render();return;
    }
    if(event.target===sizeSel){state.pageSize=sizeSel.value;state.page=1;render();}
    if(event.target.form===form){state.page=1;state.query=form.elements.search.value;state.category=form.elements.category.value;state.sort=form.elements.sort.value;render();}
  });
  root.addEventListener('input',event=>{
    if(event.target===form.elements.search){state.query=event.target.value;state.page=1;render();}
  });
  form?.addEventListener('submit',event=>event.preventDefault());
  dialog?.addEventListener('click',event=>{if(event.target.hasAttribute('data-modal-close'))closeDialog();});
  buildColumns();render();hero(0);
})();
