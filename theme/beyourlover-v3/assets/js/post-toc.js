/**
 * BYL V3 Post TOC — WWW-inspired in-article Summary + desktop floating INDEX.
 * Progressive enhancement only; does not rewrite/save Gutenberg post content.
 * Scoped to regular single Posts, never Checkout or other page types.
 */
(() => {
  'use strict';

  function start() {
    const content = document.querySelector('.byl-post-single__content');
    const floating = document.getElementById('byl-post-floating-toc');
    const floatingList = floating?.querySelector('.byl-post-toc__floating-list');
    if (!content || !floating || !floatingList || content.dataset.bylTocReady) return;

    const headings = Array.from(content.querySelectorAll('h2')).filter(
      heading => !heading.closest('.byl-post-toc--inline, .articre_toc, .ez-toc-container, .lwptoc')
        && heading.textContent.trim()
    ).slice(0, 40);
    if (headings.length < 2) return;

    const existing = content.querySelector('.articre_toc, .ez-toc-container, .lwptoc, [data-byl-toc-inline]');
    const inline = document.createElement('nav');
    inline.className = 'byl-post-toc--inline';
    inline.setAttribute('aria-label', 'Article table of contents');
    inline.dataset.bylTocInline = 'true';
    const label = document.createElement('p');
    label.className = 'byl-post-toc__inline-label';
    label.textContent = 'Summary';
    const inlineList = document.createElement('ul');
    inlineList.className = 'byl-post-toc__inline-list';
    inline.append(label, inlineList);

    const sections = [];
    const seen = new Set();
    headings.forEach((heading, idx) => {
      let id = heading.id;
      // Preserve authored anchors; do not create duplicate IDs.
      if (!id || seen.has(id) || document.querySelectorAll('[id="' + CSS.escape(id) + '"]').length > 1) {
        id = 'byl-post-section-' + (idx + 1);
        while (document.getElementById(id)) id += '-section';
        heading.id = id;
      }
      seen.add(id);
      const title = heading.textContent.trim().replace(/\s+/g, ' ');
      const link = document.createElement('a');
      link.href = '#' + encodeURIComponent(id);
      link.textContent = title;
      const item = document.createElement('li');
      item.appendChild(link);
      inlineList.appendChild(item);

      const floatLink = document.createElement('a');
      floatLink.href = '#' + encodeURIComponent(id);
      floatLink.textContent = (idx + 1) + '. ' + title;
      const floatItem = document.createElement('li');
      floatItem.appendChild(floatLink);
      floatingList.appendChild(floatItem);
      sections.push({ heading, link: floatLink });
    });

    if (!existing) headings[0].before(inline);
    content.dataset.bylTocReady = 'true';

    const desktop = window.matchMedia('(min-width: 1280px)');
    let scheduled = false;
    let active = -1;

    function update() {
      scheduled = false;
      if (!content.isConnected || !desktop.matches) {
        floating.hidden = true;
        return;
      }
      const firstTop = headings[0].getBoundingClientRect().top + window.scrollY;
      const readingEnd = content.getBoundingClientRect().bottom + window.scrollY;
      const scroll = window.scrollY;
      // WWW reveals the INDEX after the lead/hero has scrolled away,
      // well before the first H2 reaches the top of the viewport.
      const allow = scroll > 650 && scroll + 140 < readingEnd;
      floating.hidden = !allow;
      if (!allow) return;

      let index = 0;
      for (let i = 0; i < headings.length; i++) {
        if (headings[i].getBoundingClientRect().top <= 145) index = i;
        else break;
      }
      if (index === active) return;
      sections.forEach(({ link }, i) => {
        link.classList.toggle('is-current', i === index);
        if (i === index) link.setAttribute('aria-current', 'location');
        else link.removeAttribute('aria-current');
      });
      active = index;
    }

    function requestUpdate() {
      if (scheduled) return;
      scheduled = true;
      window.requestAnimationFrame(update);
    }
    window.addEventListener('scroll', requestUpdate, { passive: true });
    window.addEventListener('resize', requestUpdate, { passive: true });
    window.addEventListener('hashchange', requestUpdate);
    if (desktop.addEventListener) desktop.addEventListener('change', requestUpdate);
    else desktop.addListener(requestUpdate);
    requestUpdate();
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, { once: true });
  else start();
})();
