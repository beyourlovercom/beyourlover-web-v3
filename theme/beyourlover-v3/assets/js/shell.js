(() => {
  'use strict';
  const header = document.querySelector('[data-byl-shell-header]');
  const menuButton = document.querySelector('[data-byl-menu-toggle]');
  const mobileMenu = document.getElementById('byl-mobile-menu');
  const searchDialog = document.querySelector('[data-byl-search-dialog]');
  const searchOpeners = document.querySelectorAll('[data-byl-search-open]');
  const searchCloser = document.querySelector('[data-byl-search-close]');
  const searchInput = document.getElementById('byl-global-search');
  let searchReturnFocus = null;

  const closeMenu = () => {
    if (!menuButton || !mobileMenu) return;
    menuButton.setAttribute('aria-expanded', 'false');
    mobileMenu.hidden = true;
    document.body.classList.remove('byl-shell-menu-open');
  };
  const openMenu = () => {
    if (!menuButton || !mobileMenu) return;
    menuButton.setAttribute('aria-expanded', 'true');
    mobileMenu.hidden = false;
    document.body.classList.add('byl-shell-menu-open');
  };
  menuButton?.addEventListener('click', () => {
    menuButton.getAttribute('aria-expanded') === 'true' ? closeMenu() : openMenu();
  });
  mobileMenu?.querySelectorAll('a').forEach(link => link.addEventListener('click', closeMenu));

  const openSearch = event => {
    searchReturnFocus = event?.currentTarget instanceof HTMLElement ? event.currentTarget : document.activeElement;
    closeMenu();
    if (!searchDialog) return;
    if (typeof searchDialog.showModal === 'function') searchDialog.showModal();
    else searchDialog.setAttribute('open', '');
    window.setTimeout(() => searchInput?.focus(), 60);
  };
  const closeSearch = () => {
    if (!searchDialog) return;
    if (typeof searchDialog.close === 'function') searchDialog.close();
    else searchDialog.removeAttribute('open');
    if (searchReturnFocus instanceof HTMLElement) searchReturnFocus.focus();
    searchReturnFocus = null;
  };
  searchOpeners.forEach(button => button.addEventListener('click', openSearch));
  searchCloser?.addEventListener('click', closeSearch);
  searchDialog?.addEventListener('click', event => { if (event.target === searchDialog) closeSearch(); });

  let ticking = false;
  const updateHeader = () => {
    header?.classList.toggle('is-scrolled', window.scrollY > 8);
    ticking = false;
  };
  window.addEventListener('scroll', () => {
    if (!ticking) { window.requestAnimationFrame(updateHeader); ticking = true; }
  }, { passive: true });
  updateHeader();

  window.addEventListener('resize', () => { if (window.innerWidth > 980) closeMenu(); });
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape') closeMenu();
    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') { event.preventDefault(); openSearch(); }
  });
})();
