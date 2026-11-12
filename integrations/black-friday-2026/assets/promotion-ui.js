/* BYL BF2026 Promotion UI: read-only browser adapters, no cart/price mutations. */
(function () {
  'use strict';
  var cfg = window.BYLBf2026Promotion;
  if (!cfg || !cfg.statusUrl) return;
  var yes = function (k) {return cfg.flags && cfg.flags[k] === 'yes';};
  var banner = document.querySelector('[data-byl-bf-banner]');
  var clock = document.querySelector('[data-byl-bf-countdown]');
  var active = false, anchor = 0, start = 0, end = 0, clockTimer = null;
  var pendingCart = false, snapshot = null;

  function percent(regular, current) {
    var a = Number(regular), b = Number(current);
    if (!isFinite(a) || !isFinite(b) || a <= 0 || b <= 0 || b >= a) return 0;
    return Math.max(0, Math.min(99, Math.floor(((a - b) / a) * 100 + 1e-7)));
  }
  function makeBadge(value) {
    value = Number(value);
    if (!(value > 0 && value < 100)) return null;
    var el = document.createElement('span');
    el.className = 'byl-bf2026-sale-badge';
    el.setAttribute('data-byl-bf-badge','');
    el.setAttribute('data-byl-bf-percent',String(value));
    el.textContent = 'Black Friday · ' + value + '% OFF';
    return el;
  }
  function stop() {
    active = false;
    if (banner) banner.hidden = true;
    if (clockTimer) clearInterval(clockTimer);
    document.querySelectorAll('[data-byl-bf-badge]').forEach(function (e) {e.remove();});
  }
  function tick() {
    var seconds = Math.max(0, Math.ceil((end - (anchor + performance.now() - start)) / 1000));
    if (!seconds) {stop(); return;}
    var d = Math.floor(seconds / 86400), h = Math.floor(seconds % 86400 / 3600);
    var m = Math.floor(seconds % 3600 / 60), s = seconds % 60;
    var pad = function (x) {return String(x).padStart(2,'0');};
    if (clock) clock.textContent = 'Ends in ' + d + 'd ' + pad(h) + 'h ' + pad(m) + 'm ' + pad(s) + 's';
  }
  function sticky() {
    if (!active || !yes('sticky')) return;
    var source = document.querySelector('.single-product .summary [data-byl-bf-percent], .product .summary [data-byl-bf-percent]');
    if (!source) return;
    var value = Number(source.getAttribute('data-byl-bf-percent'));
    if (!value) return;
    document.querySelectorAll('.sticky-add-to-cart, .shoptimizer-sticky-add-to-cart, .sticky-atc, [data-sticky-add-to-cart], [data-byl-sticky-atc]').forEach(function (root) {
      if (root.querySelector('[data-byl-bf-badge]')) return;
      var price = root.querySelector('.price, .product-price, .sticky-add-to-cart__price');
      var tag = makeBadge(value);
      if (price && tag) {tag.classList.add('byl-bf2026-sticky-badge'); price.insertAdjacentElement('afterend',tag);}
    });
  }
  function renderDrawer() {
    if (!active || !snapshot || !Array.isArray(snapshot.items)) return;
    var byKey = new Map(snapshot.items.map(function (x) {return [String(x.key || ''), x];}));
    document.querySelectorAll('.byl-drawer__item[data-drawer-key]').forEach(function (row) {
      var item = byKey.get(String(row.getAttribute('data-drawer-key') || ''));
      var pct = item && String(item.sku || '').toLowerCase() !== 'gift' ? percent(item.prices && item.prices.regular_price, item.prices && item.prices.price) : 0;
      var old = row.querySelector('[data-byl-bf-badge]');
      if (old && Number(old.getAttribute('data-byl-bf-percent')) !== pct) old.remove();
      if (!pct || row.querySelector('[data-byl-bf-badge]')) return;
      var price = row.querySelector('.byl-drawer__item-price'), tag = makeBadge(pct);
      if (price && tag) price.insertAdjacentElement('afterend',tag);
    });
  }
  async function drawer() {
    if (!active || !yes('drawer') || pendingCart || !document.querySelector('[data-drawer-items]')) return;
    pendingCart = true;
    try {
      var result = await fetch(cfg.storeCartUrl, {credentials:'same-origin',cache:'no-store'});
      if (!result.ok) throw Error('Woo Store API blocked');
      var data = await result.json();
      if (!Array.isArray(data.items)) throw Error('Invalid cart response');
      snapshot = data; renderDrawer();
    } catch (e) {
      snapshot = null;
      document.querySelectorAll('.byl-drawer__item [data-byl-bf-badge]').forEach(function (x) {x.remove();});
    } finally {pendingCart=false;}
  }
  function observers() {
    var root = document.querySelector('[data-drawer-items]');
    if (root && yes('drawer')) {
      var scheduled = false;
      new MutationObserver(function (changes) {
        if (!changes.some(function (change) {
          return Array.from(change.addedNodes).concat(Array.from(change.removedNodes)).some(function (node) {
            return node.nodeType === 1 && !(node.matches && node.matches('[data-byl-bf-badge]'));
          });
        })) return;
        if (scheduled) return;
        scheduled=true;
        requestAnimationFrame(function () {scheduled=false;if (active) drawer();});
      }).observe(root,{childList:true,subtree:false});
    }
    if (yes('sticky')) {
      var last=0;
      document.addEventListener('scroll',function () {
        var now=performance.now();
        if (now-last<900) return;
        last=now;sticky();
      },{passive:true});
    }
  }
  async function readStatus() {
    try {
      var response=await fetch(cfg.statusUrl,{credentials:'same-origin',cache:'no-store'});
      if (!response.ok) throw Error('Status unavailable');
      var data=await response.json();
      if (!data.active || !isFinite(Number(data.now)) || !isFinite(Number(data.end))) {stop();return;}
      active=true;anchor=Number(data.now)*1000;end=Number(data.end)*1000;start=performance.now();
      if (banner && yes('announcement')) banner.hidden=false;
      tick();
      clockTimer=setInterval(tick,1000);
      if (yes('sticky')) sticky();
      if (yes('drawer')) drawer();
    } catch(e) {stop();}
  }
  observers();
  readStatus();
})();