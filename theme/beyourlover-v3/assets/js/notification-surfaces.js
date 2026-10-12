/* BYL Notification UX V1 — DOM-safe semantic surfaces; no business or network access. */
((global) => {
  'use strict';
  if (global.BYL && global.BYL.surfaces) return;
  const root = global.BYL || (global.BYL = {});
  const semantics = Object.freeze({
    success:{title:'Success',icon:'✓',role:'status'},
    error:{title:'Something needs attention',icon:'!',role:'alert'},
    warning:{title:'A little heads-up',icon:'!',role:'status'},
    info:{title:'Good to know',icon:'i',role:'status'}
  });
  const clean = value => String(value == null ? '' : value);
  function addElement(parent, tag, className, value) {
    const node = parent.ownerDocument.createElement(tag);
    if (className) node.className = className;
    if (value !== undefined) node.textContent = clean(value);
    parent.appendChild(node);
    return node;
  }
  function renderNotice(host, options) {
    if (!host || !host.ownerDocument || !options || !semantics[options.type]) return null;
    const kind = options.type, config = semantics[kind];
    const node = host.ownerDocument.createElement('div');
    node.className = 'byl-notice byl-notice--' + kind;
    node.setAttribute('role', config.role);
    addElement(node, 'span','byl-notice__icon', config.icon).setAttribute('aria-hidden','true');
    const body = addElement(node,'div','byl-notice__body');
    addElement(body,'strong','',options.title || config.title);
    addElement(body,'p','',options.message || '');
    if (options.action && typeof options.action.label==='string' && typeof options.action.onClick==='function') {
      const button=addElement(node,'button','byl-notice__action',options.action.label);
      button.type='button';
      button.addEventListener('click',options.action.onClick);
    }
    host.replaceChildren(node);
    return node;
  }
  function renderEmpty(host, options) {
    if (!host || !host.ownerDocument || !options) return null;
    const node=host.ownerDocument.createElement('section');
    node.className='byl-empty-state';
    node.setAttribute('aria-label',clean(options.title || 'Empty state'));
    const symbol=addElement(node,'div','byl-empty-state__symbol',options.symbol || '♡');
    symbol.setAttribute('aria-hidden','true');
    addElement(node,'h3','',options.title || 'Nothing here yet');
    addElement(node,'p','',options.message || '');
    if (options.cta && typeof options.cta.label==='string' && typeof options.cta.onClick==='function') {
      const b=addElement(node,'button','',options.cta.label);
      b.type='button';b.addEventListener('click',options.cta.onClick);
    }
    // No synthetic product cards or links. Real recently viewed remains upstream owned.
    host.replaceChildren(node);
    return node;
  }
  root.surfaces=Object.freeze({renderNotice,renderEmpty,types:Object.keys(semantics)});
})(window);
