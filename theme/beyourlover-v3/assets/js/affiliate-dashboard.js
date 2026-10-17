/* BeYourLover Affiliate Dashboard V1: local-only copy interaction, no analytics writes. */
(() => {
  'use strict';
  const root = document.querySelector('[data-byl-affiliate-v1]');
  if (!root) return;

  root.addEventListener('click', async (event) => {
    const expansion = event.target.closest('[data-byl-expand-referrals]');
    if (expansion && root.contains(expansion)) {
      const panel = root.querySelector('.byl-affiliate-referrals');
      if (panel) {
        const expanded = panel.classList.toggle('is-expanded');
        expansion.setAttribute('aria-expanded', String(expanded));
        expansion.textContent = expanded ? 'Show Fewer ↑' : 'View All Orders →';
      }
      return;
    }
    const filter = event.target.closest('[data-byl-asset-filter]');
    if (filter && root.contains(filter)) {
      const category = filter.getAttribute('data-byl-asset-filter');
      root.querySelectorAll('[data-byl-asset-filter]').forEach((button) => {
        const active = button === filter;
        button.classList.toggle('byl-v2-filter-active', active);
        button.setAttribute('aria-pressed', String(active));
      });
      let visible = 0;
      root.querySelectorAll('[data-byl-asset-type]').forEach((item) => {
        const show = category === 'all' || category === item.getAttribute('data-byl-asset-type');
        item.hidden = !show;
        if (show) visible++;
      });
      const notice = root.querySelector('.byl-v2-no-assets');
      if (notice) notice.hidden = visible > 0;
      return;
    }
    const share = event.target.closest('[data-byl-share]');
    if (share && root.contains(share)) {
      const input = root.querySelector(share.getAttribute('data-byl-share'));
      if (!input || !input.value) return;
      const label = share.textContent;
      try {
        if (navigator.share) {
          await navigator.share({ title: 'BeYourLover', url: input.value });
        } else if (navigator.clipboard && window.isSecureContext) {
          await navigator.clipboard.writeText(input.value);
          share.textContent = 'Copied ✓';
        } else {
          input.select();
          const ok = document.execCommand('copy');
          share.textContent = ok ? 'Copied ✓' : 'Select link';
        }
      } catch (_) { /* User cancelled sharing. */ }
      window.setTimeout(() => { share.textContent = label; }, 1800);
      return;
    }
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
