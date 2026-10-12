/* BYL Notification UX V1 — presentation-only, opt-in runtime.
 * No Woo mutation, API request, notice interception, payment or order operations. */
(function (global, document) {
  'use strict';
  if (global.BYL && typeof global.BYL.notify === 'function') return;
  const rootNS = global.BYL || (global.BYL = {});
  const HOLD = 2000, EXIT = 230, DEDUPE = 1200, MAX_QUEUE = 3, MAX_AGE = 4000;
  const queue = [], recent = new Map();
  let host = null, active = null, holdTimer = 0, exitTimer = 0;
  let epoch = 0, disposed = false;

  function modalBlocks() {
    if (!document.querySelectorAll) return false;
    const dialogs = document.querySelectorAll('dialog[open], [role="dialog"][aria-modal="true"]');
    return Array.from(dialogs).some(node =>
      !node.hidden && !(node.closest && node.closest('[data-byl-cart-drawer]')));
  }
  function typing() {
    const node = document.activeElement;
    return !!(node && node.matches && node.matches('input, textarea, select, [contenteditable="true"]'));
  }
  function blocked() {
    return document.visibilityState === 'hidden' || typing() || modalBlocks();
  }
  function ensureHost() {
    if (host && host.isConnected) return host;
    if (!document.body) return null;
    host = document.createElement('div');
    host.className = 'byl-toast-stack';
    host.setAttribute('data-byl-notification', 'v1');
    host.setAttribute('aria-live', 'polite');
    host.setAttribute('aria-relevant', 'additions text');
    document.body.appendChild(host);
    return host;
  }
  function cancelTimers() {
    global.clearTimeout(holdTimer);
    global.clearTimeout(exitTimer);
    holdTimer = 0;
    exitTimer = 0;
  }
  function purge(context) {
    for (let i = queue.length - 1; i >= 0; i--) {
      if (!context || queue[i].context === context) queue.splice(i, 1);
    }
    if (active && (!context || active.context === context)) {
      cancelTimers();
      if (active.node) active.node.remove();
      active = null;
    }
    drain();
  }
  function drain() {
    if (disposed || active || blocked()) return;
    while (queue.length && Date.now() - queue[0].at > MAX_AGE) queue.shift();
    if (!queue.length) return;
    const parent = ensureHost();
    if (!parent) return;
    const data = queue.shift();
    if (data.epoch !== epoch) { drain(); return; }
    const node = document.createElement('div');
    node.className = 'byl-toast byl-toast--' + data.type;
    node.setAttribute('role', 'status');
    node.setAttribute('aria-atomic', 'true');
    node.setAttribute('aria-label', data.message);
    const icon = document.createElement('span');
    icon.className = 'byl-toast__icon';
    icon.setAttribute('aria-hidden', 'true');
    icon.textContent = data.type === 'success' ? '✓' : 'i';
    const message = document.createElement('span');
    message.className = 'byl-toast__text';
    message.textContent = data.message; // NEVER inject Woo content as markup.
    node.appendChild(icon);
    node.appendChild(message);
    parent.appendChild(node);
    active = { context: data.context, node: node, epoch: epoch };
    const current = active;
    const show = () => {
      if (active === current) node.classList.add('is-visible');
    };
    if (global.requestAnimationFrame) global.requestAnimationFrame(show);
    else show();
    holdTimer = global.setTimeout(() => {
      if (active !== current) return;
      node.classList.remove('is-visible');
      node.classList.add('is-leaving');
      exitTimer = global.setTimeout(() => {
        if (active !== current) return;
        node.remove();
        active = null;
        drain();
      }, EXIT);
    }, HOLD);
  }
  function notify(options) {
    if (disposed || !options || typeof options !== 'object') return false;
    const type = options.type;
    if (type !== 'success' && type !== 'info') return false;
    // Caller must validate the Woo response before marking a success confirmed.
    if (type === 'success' && options.confirmed !== true) return false;
    const message = String(options.message == null ? '' : options.message).trim();
    if (!message) return false;
    const context = String(options.context || 'global').slice(0, 64);
    const key = String(options.key || message).slice(0, 128);
    const fingerprint = context + ':' + type + ':' + key;
    const now = Date.now();
    if (recent.has(fingerprint) && now - recent.get(fingerprint) < DEDUPE) return false;
    recent.set(fingerprint, now);
    if (recent.size > 50) {
      for (const [id, time] of recent) if (now - time > DEDUPE) recent.delete(id);
    }
    if (queue.length >= MAX_QUEUE) queue.shift(); // Prefer fresh action feedback.
    queue.push({type: type, message: message, context: context, at: now, epoch: epoch});
    drain();
    return true;
  }
  notify.clear = function (context) { purge(context ? String(context) : ''); };
  notify.invalidateAll = function () {
    epoch++;
    recent.clear();
    purge('');
  };
  notify.inlineError = function (element, value) {
    if (!element || !('textContent' in element)) return false;
    const message = String(value == null ? '' : value);
    element.textContent = message;
    element.hidden = !message;
    element.setAttribute('role', 'alert');
    return true;
  };
  notify.status = function (element, value) {
    if (!element || !('textContent' in element)) return false;
    element.textContent = String(value == null ? '' : value);
    element.setAttribute('role', 'status');
    return true;
  };
  notify.version = '1.0.0';
  rootNS.notify = notify;
  document.addEventListener('focusout', drain, true);
  document.addEventListener('close', drain, true);
  document.addEventListener('visibilitychange', drain);
  document.addEventListener('focusin', () => { if (typing()) purge(''); }, true);
  global.addEventListener('pagehide', () => {
    disposed = true;
    cancelTimers();
    queue.length = 0;
    if (active && active.node) active.node.remove();
    active = null;
    if (host) host.remove();
  });
})(window, document);
