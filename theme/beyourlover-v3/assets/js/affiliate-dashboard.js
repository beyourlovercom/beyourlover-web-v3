/* BeYourLover Affiliate Dashboard V1: local-only copy interaction, no analytics writes. */
(() => {
  'use strict';
  const root = document.querySelector('[data-byl-affiliate-v1]');
  if (!root) return;

  root.addEventListener('click', async (event) => {
    const trigger = event.target.closest('[data-byl-copy]');
    if (!trigger || !root.contains(trigger)) return;
    const target = root.querySelector(trigger.getAttribute('data-byl-copy'));
    if (!target || !target.value) return;
    const oldText = trigger.textContent;
    let copied = false;
    try {
      if (navigator.clipboard && window.isSecureContext) {
        await navigator.clipboard.writeText(target.value);
        copied = true;
      } else {
        target.focus();
        target.select();
        copied = document.execCommand('copy');
        target.setSelectionRange(0, 0);
      }
    } catch (_) { copied = false; }
    trigger.textContent = copied ? 'Copied ✓' : 'Select & copy';
    trigger.setAttribute('aria-live', 'polite');
    if (!copied) target.select();
    window.setTimeout(() => { trigger.textContent = oldText; }, 1800);
  });

  const nav = root.querySelector('.byl-affiliate-menu');
  if (nav) {
    nav.addEventListener('click', (event) => {
      const link = event.target.closest('a[href^="#"]');
      if (!link || !nav.contains(link)) return;
      for (const anchor of nav.querySelectorAll('a[aria-current]')) anchor.removeAttribute('aria-current');
      link.setAttribute('aria-current', 'location');
    });
  }
})();
