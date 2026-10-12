(() => {
  'use strict';
  const root = document.querySelector('[data-byl-cart-drawer]');
  const config = window.BYLCartDrawerPreview;
  // WordPress wp_localize_script serializes PHP false as an empty string.
  // Keep the no-gift guard strict: only explicit off representations enable
  // this preview; true/1/missing flags must never activate unapproved gifts.
  const giftsDisabled = !!config && Object.prototype.hasOwnProperty.call(config,'giftEnabled') &&
    [false,'',0,'0'].includes(config.giftEnabled);
  if (!root || !giftsDisabled) return;

  const $ = selector => root.querySelector(selector);
  const elements = {
    panel: $('.byl-drawer__panel'), count: $('[data-drawer-count]'),
    feedback: $('[data-drawer-feedback]'), delivery: $('[data-drawer-delivery]'),
    m5progress: $('[data-drawer-m5-progress]'),
    empty: $('[data-drawer-empty]'), items: $('[data-drawer-items]'),
    couponSection: $('[data-drawer-coupon-section]'), couponForm: $('[data-drawer-coupon-form]'),
    coupons: $('[data-drawer-active-coupons]'), totals: $('[data-drawer-totals]'),
    footer: $('[data-drawer-footer]'), footerTotal: $('[data-drawer-footer-total]'),
    desktopRecs: $('[data-drawer-recommendations]'), mobileRecs: $('[data-drawer-mobile-recs]'),
    checkout: $('[data-drawer-checkout]'),
    marketing: $('[data-drawer-marketing-slots]')
  };
  if (!elements.panel || !elements.items) return;
  // Marketing V1 is a pure off-by-default design reservation, not an advert launcher.
  // No localStorage, URL param, injected markup, or Woo response enables it.
  if (elements.marketing) elements.marketing.hidden = true;
  elements.mobileRecs.innerHTML = elements.desktopRecs.innerHTML;

  let state = null, nonce = String(config.nonce || ''), loading = false;
  let lastFocus = null;
  const pendingQuantity = new Map();
  let mutationQueue = Promise.resolve();

  const escape = value => String(value ?? '').replace(/[&<>"']/g, ch =>
    ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch]));
  const currency = c => String(c?.totals?.currency_code || c?.items?.[0]?.prices?.currency_code || 'USD');
  const minorUnit = c => Math.pow(10, Number(c?.totals?.currency_minor_unit ?? c?.items?.[0]?.prices?.currency_minor_unit ?? 2));
  const money = (minor, c) => {
    const code = currency(c), amount = Number(minor) / minorUnit(c);
    if (!Number.isFinite(amount)) return '—';
    try { return new Intl.NumberFormat('en-US', {style:'currency',currency:code}).format(amount); }
    catch (_) { return code + ' ' + amount.toFixed(2); }
  };
  const decodeWooText = value => String(value ?? '').replace(
    /&(#x[0-9a-f]+|#[0-9]+|amp|lt|gt|quot|apos);/gi,
    (full,raw) => {
      const entity=raw.toLowerCase();
      if(entity[0]==='#') {
        const code=entity[1]==='x' ? parseInt(entity.slice(2),16) : parseInt(entity.slice(1),10);
        return Number.isInteger(code) && code>=32 && code<=0x10ffff
          ? String.fromCodePoint(code) : full;
      }
      return {amp:'&',lt:'<',gt:'>',quot:'"',apos:"'"}[entity] ?? full;
    }
  );
  const setFeedback = (message, error=false) => {
    // Woo returns entity-encoded errors; textContent guarantees safe display.
    elements.feedback.textContent = decodeWooText(message);
    elements.feedback.classList.toggle('is-error', error);
  };
  const safeHref = value => {
    try {
      const uri = new URL(String(value), window.location.href);
      return ['https:','http:'].includes(uri.protocol) ? uri.href : String(config.cartUrl);
    } catch (_) { return String(config.cartUrl); }
  };
  const safeImage = url => {
    if (!url || !String(url).trim()) return '';
    try { const uri = new URL(String(url), window.location.href); return ['https:','http:'].includes(uri.protocol) ? uri.href : ''; }
    catch (_) { return ''; }
  };
  const shippingRates = cart => (cart.shipping_rates || []).flatMap(pack => pack.shipping_rates || []);
  const line = (text, value, c, suffix='') =>
    '<div class="byl-drawer__sumline ' + suffix + '"><span>' + escape(text) +
    '</span><strong>' + money(value, c) + '</strong></div>';

  function renderItem(item, cart) {
    const quantity = Math.max(1, Number(item.quantity) || 1);
    const image = safeImage(item.images?.[0]?.thumbnail || item.images?.[0]?.src || '');
    const img = image ? '<img loading="lazy" alt="" src="'+escape(image)+'">' : '<span aria-hidden="true">▢</span>';
    const soldOut = item.quantity_limits?.editable === false;
    const key = escape(item.key);
    const max = Number(item.quantity_limits?.maximum);
    const min = Number(item.quantity_limits?.minimum);
    const lineTotal = item.totals?.line_total ?? item.prices?.price;
    const sale = Number(item.prices?.regular_price) > Number(item.prices?.price);
    return '<article class="byl-drawer__item" data-drawer-key="' + key + '">' +
      '<a class="byl-drawer__product-image" href="' + escape(safeHref(item.permalink || config.cartUrl)) + '">' + img + '</a>' +
      '<div class="byl-drawer__item-info"><div class="byl-drawer__item-top"><a href="' +
      escape(safeHref(item.permalink || config.cartUrl)) + '">' + escape(item.name) + '</a>' +
      (soldOut ? '<span class="byl-drawer__qty-locked">' + quantity + '</span>' :
       '<div class="byl-drawer__quantity" role="group" aria-label="Quantity for ' + escape(item.name) + '">' +
       '<button type="button" data-drawer-qty="-1" aria-label="Decrease quantity"'+(Number.isFinite(min) && quantity<=min?' disabled':'')+'>−</button>' +
       '<span aria-live="off">' + quantity + '</span><button type="button" data-drawer-qty="1" aria-label="Increase quantity"'+(Number.isFinite(max) && max>0 && quantity>=max?' disabled':'')+'>+</button></div>') +
      '</div><div class="byl-drawer__variant">' + escape((item.variation || []).map(v=>v.value).filter(Boolean).join(' / ') || '') + '</div>' +
      '<p class="byl-drawer__item-price ' + (sale?'is-sale':'')+'">' + money(lineTotal, cart) + '</p>' +
      '<div class="byl-drawer__item-actions"><button type="button" data-drawer-remove>Remove</button></div>' +
      '</div></article>';
  }

  // M5 progress is a read-only presentation of the accepted Woo Store API
  // extension, not a client-side promotion or a shipping qualification engine.
  function renderM5Progress(cart) {
    const slot = elements.m5progress;
    if (!slot) return;
    slot.hidden = true;
    slot.textContent = '';
    // The server-owned marketing gate is still OFF, even on Dev. Do not let
    // query parameters, local storage or cart payloads activate promotions.
    // Dedicated Dev M4 shipping gate is independent of Marketing campaigns.
    // Marketing/BOGO/Free Gift remain OFF even when read-only progress is shown.
    if (config.shippingProgressEnabled !== true) return;
    const ext = cart?.extensions?.andy_commerce_marketing_v1;
    if (!ext || ext.schema_version !== 1 || ext.state !== 'ready' ||
        ext.show_progress !== true || ext.gift_enabled !== false ||
        ext.claim_free_shipping !== false || ext.authority !== 'woocommerce' ||
        ext.reason !== 'read_only_projection') return;
    const valid = n => Number.isSafeInteger(n) && n >= 0 && n <= 1000000000;
    const qualified = ext.qualified_minor, threshold = ext.threshold_minor;
    const remaining = ext.remaining_minor, percent = ext.percent;
    if (!valid(qualified) || !valid(threshold) || threshold === 0 ||
        !valid(remaining) || !valid(percent) || percent > 100 ||
        remaining !== Math.max(0, threshold - qualified) ||
        percent !== Math.min(100, Math.floor(qualified * 100 / threshold))) return;
    // Shipping eligibility remains under Woo's authority; this is only a
    // progress illustration. One package and one confirmed selected rate.
    const packs = Array.isArray(cart?.shipping_rates) ? cart.shipping_rates : [];
    if (packs.length !== 1 || !Array.isArray(packs[0]?.shipping_rates) ||
        packs[0].shipping_rates.filter(r => r?.selected === true).length !== 1) return;
    const selected = packs[0].shipping_rates.find(r => r?.selected === true);
    if (typeof selected.rate_id !== 'string' ||
        !/^[A-Za-z0-9_-]+:[A-Za-z0-9_.-]+$/.test(selected.rate_id)) return;
    const currencyCode = cart?.totals?.currency_code;
    const units = cart?.totals?.currency_minor_unit;
    if (typeof currencyCode !== 'string' || !/^[A-Z]{3}$/.test(currencyCode) ||
        !Number.isInteger(units) || units < 0 || units > 4) return;
    const label = document.createElement('strong');
    label.textContent = remaining > 0
      ? 'Add ' + money(remaining, cart) + ' toward the free-shipping threshold'
      : 'Threshold reached. Shipping is confirmed by Woo at checkout.';
    const track = document.createElement('div');
    track.className = 'byl-drawer__m5-track';
    track.setAttribute('aria-hidden','true');
    const fill = document.createElement('span');
    fill.style.width = percent + '%';
    track.appendChild(fill);
    slot.appendChild(label);
    slot.appendChild(track);
    slot.hidden = false;
  }

  function render(cart) {
    state = cart;
    const items = Array.isArray(cart.items) ? cart.items : [];
    const count = items.reduce((n,item)=>n+(Number(item.quantity)||0),0);
    elements.count.textContent = count ? '('+count+')' : '';
    document.querySelectorAll('.byl-shell-cart-count').forEach(el => {
      el.textContent = String(count);
      el.hidden = !count;
    });
    document.querySelectorAll('a.byl-shell-cart').forEach(el => el.setAttribute('aria-label','Cart, '+count+' items'));

    elements.empty.hidden = items.length !== 0;
    elements.items.hidden = items.length === 0;
    elements.items.innerHTML = items.map(item=>renderItem(item,cart)).join('');
    elements.couponSection.hidden = items.length === 0;
    elements.footer.hidden = items.length === 0;
    elements.totals.hidden = items.length === 0;
    elements.delivery.hidden = items.length === 0;
    renderM5Progress(cart);

    // Only actual shipping rates from WooCommerce are authoritative.
    if (items.length) {
      const rates = shippingRates(cart);
      const chosen = rates.find(rate=>rate && rate.selected === true);
      const free = rates.find(rate=>rate && String(rate.rate_id || '').startsWith('free_shipping:'));
      let status = 'Shipping options depend on your delivery address.';
      if (free && chosen?.rate_id === free.rate_id) {
        status = '🎉 Free shipping applied to your order.';
      } else if (free) {
        status = '🎉 Free shipping available — select Free Shipping to apply it.';
      }
      elements.delivery.textContent = status;
      elements.totals.innerHTML =
        line('Subtotal',cart.totals?.total_items || 0,cart) +
        (Number(cart.totals?.total_discount)>0 ? line('Discount',-Number(cart.totals.total_discount),cart,'is-discount') : '') +
        line('Shipping',cart.totals?.total_shipping || 0,cart) +
        line('Order total',cart.totals?.total_price || 0,cart,'is-total');
      elements.footerTotal.textContent = money(cart.totals?.total_price || 0,cart);
    }

    const activeCoupons = (cart.coupons || []).map(c =>
      '<span class="byl-drawer__applied-coupon">' + escape(c.code) +
      ' <button type="button" data-drawer-remove-coupon="' + escape(c.code) + '" aria-label="Remove coupon ' + escape(c.code) + '">×</button></span>').join('');
    elements.coupons.innerHTML = activeCoupons;
    const existing = new Set(items.map(it=>String(it.id)));
    [elements.desktopRecs,elements.mobileRecs].forEach(container=>
      container.querySelectorAll('[data-product-id]').forEach(row=>{
        const selected=existing.has(row.dataset.productId);
        const button=row.querySelector('[data-drawer-add]');
        if(button){button.textContent=selected?'Added':'Add to cart';button.disabled=selected;}
      }));
  }

  async function request(endpoint, data=null) {
    if (loading) return null; // In-flight or skipped actions are not accepted Woo mutations.
    loading = true;
    elements.panel.setAttribute('aria-busy','true');
    setFeedback(data ? 'Updating your cart…' : 'Loading cart…');
    try {
      const method=data ? 'POST' : 'GET';
      const headers={'Accept':'application/json'};
      if (data) {headers['Content-Type']='application/json';headers['Nonce']=nonce;}
      const response=await fetch(String(config.cartApi).replace(/\/$/,'')+(endpoint?'/'+endpoint:''),{
        method,credentials:'same-origin',cache:'no-store',headers,
        ...(data ? {body:JSON.stringify(data)} : {})
      });
      if(response.headers.get('Nonce')) nonce=response.headers.get('Nonce');
      const result=await response.json();
      if(!response.ok) throw Error(result.message||'Cart update was not accepted.');
      if(!result || !Array.isArray(result.items)) throw Error('Unexpected Cart API response.');
      const previous = state;
      render(result);
      setFeedback('');
      // Notify only after Woo accepted and returned an authoritative Cart.
      if (data && window.BYL?.cartNoticeBridge?.confirm) {
        try { window.BYL.cartNoticeBridge.confirm({endpoint, payload:data, before:previous, after:result}); }
        catch (_) { /* Presentation cannot affect Woo success/error. */ }
      }
      return result;
    }catch(error){
      // A failed Woo mutation must not leave optimistic quantity text onscreen.
      // The previously accepted Woo response remains our only displayed truth.
      if (state) render(state);
      else elements.empty.hidden = false;
      setFeedback(error.message || 'Could not update your cart. Please try again.',true);
      throw error;
    } finally {
      loading=false;
      elements.panel.removeAttribute('aria-busy');
    }
  }
  const update = (endpoint,data) => {
    // Preserve the success/failure result for callers (especially coupon UI).
    // Keep the serial queue alive after an API error so a retry can proceed.
    const operation = mutationQueue.then(()=>request(endpoint,data));
    mutationQueue = operation.catch(()=>{});
    return operation.then(result=>Boolean(result && Array.isArray(result.items)),()=>false);
  };
  function open() {
    if(!root.hidden) return;
    lastFocus = document.activeElement;
    root.hidden=false;
    document.documentElement.classList.add('byl-drawer-open');
    elements.panel.focus();
    update('',null);
  }
  function close() {
    if(root.hidden) return;
    root.hidden=true;
    document.documentElement.classList.remove('byl-drawer-open');
    if(lastFocus?.isConnected && typeof lastFocus.focus === 'function') lastFocus.focus();
  }
  document.addEventListener('click',ev=>{
    const openLink=ev.target.closest('a.byl-shell-cart, a[data-byl-drawer-open]');
    if (openLink) {ev.preventDefault();open();return;}
  });
  root.addEventListener('click',event=>{
    if (event.target.closest('[data-drawer-close]')) {close();return;}
    // Network updates are serialized. Never accept an event based on the
    // previous Woo cart snapshot while an update is still in flight.
    if (loading) return;
    const remove=event.target.closest('[data-drawer-remove]');
    if(remove && state){
      const row=remove.closest('[data-drawer-key]');
      if(row) update('remove-item',{key:row.dataset.drawerKey});
      return;
    }
    const change=event.target.closest('[data-drawer-qty]');
    if(change && state){
      const row=change.closest('[data-drawer-key]');
      const item=state.items?.find(it=>it.key===row?.dataset.drawerKey);
      if(!item) return;
      const previous = pendingQuantity.get(item.key);
      const next = Number(previous?.quantity ?? item.quantity) + Number(change.dataset.drawerQty);
      const limits=item.quantity_limits || {};
      if (next < Number(limits.minimum ?? 1) ||
          (Number(limits.maximum)>0 && next>Number(limits.maximum))) return;
      // Coalesce quick taps independently for each Woo cart-line key.
      if(previous?.timer) window.clearTimeout(previous.timer);
      const timer = window.setTimeout(()=>{
        pendingQuantity.delete(item.key);
        update('update-item',{key:item.key,quantity:next});
      },300);
      pendingQuantity.set(item.key,{timer,quantity:next});
      const visual=row?.querySelector('.byl-drawer__quantity span');
      if(visual) visual.textContent=String(next);
      return;
    }
    const add=event.target.closest('[data-drawer-add]');
    if(add){update('add-item',{id:Number(add.dataset.drawerAdd),quantity:1});return;}
    const coupon=event.target.closest('[data-drawer-remove-coupon]');
    if(coupon){update('remove-coupon',{code:coupon.dataset.drawerRemoveCoupon});}
  });
  elements.couponForm.addEventListener('submit',event=>{
    event.preventDefault();
    if (loading) return;
    const field=elements.couponForm.querySelector('input[name="coupon_code"]');
    const code=(field?.value||'').trim();
    if(!code) {setFeedback('Enter a coupon code first.',true);return;}
    const coupons = Array.isArray(state?.coupons) ? state.coupons : [];
    if(coupons.length) {
      setFeedback('Only one promo code can be used at a time. Remove the applied code first.',true);
      return;
    }
    update('apply-coupon',{code}).then(applied=>{
      // Failed/expired/invalid coupons must remain editable for a retry.
      if(applied && field) field.value='';
    });
  });
  document.addEventListener('keydown',event=>{
    if(root.hidden) return;
    if(event.key==='Escape'){event.preventDefault();close();return;}
    if(event.key!=='Tab')return;
    const tabbables=[...root.querySelectorAll('a[href],button:not([disabled]),input:not([disabled])')]
      .filter(el=>el.getClientRects().length>0);
    if(!tabbables.length)return;
    const first=tabbables[0],last=tabbables[tabbables.length-1];
    if(event.shiftKey && document.activeElement===first){event.preventDefault();last.focus();}
    else if(!event.shiftKey && document.activeElement===last){event.preventDefault();first.focus();}
  });
  window.BYL_Cart_Drawer_Preview={open,close,refresh:()=>update('',null)};
  // Explicit opt-in preview should open automatically. Do not affect ordinary visitors.
  open();
})();
