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

  const setupMobileSubmenus = () => {
    mobileMenu?.querySelectorAll('.menu-item-has-children').forEach((item, index) => {
      const submenu = item.querySelector(':scope > .sub-menu');
      const link = item.querySelector(':scope > a');
      if (!submenu || !link || item.querySelector(':scope > .byl-mobile-submenu-toggle')) return;
      const id = submenu.id || `byl-mobile-submenu-${index + 1}`;
      submenu.id = id;
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'byl-mobile-submenu-toggle';
      button.setAttribute('aria-expanded', 'false');
      button.setAttribute('aria-controls', id);
      button.setAttribute('aria-label', `Expand ${link.textContent?.trim() || 'submenu'}`);
      button.textContent = '+';
      link.insertAdjacentElement('afterend', button);
      button.addEventListener('click', () => {
        const open = button.getAttribute('aria-expanded') === 'true';
        button.setAttribute('aria-expanded', open ? 'false' : 'true');
        button.setAttribute('aria-label', `${open ? 'Expand' : 'Collapse'} ${link.textContent?.trim() || 'submenu'}`);
        item.classList.toggle('is-open', !open);
      });
    });
  };

  const resetMobileSubmenus = () => {
    mobileMenu?.querySelectorAll('.menu-item-has-children.is-open').forEach(item => item.classList.remove('is-open'));
    mobileMenu?.querySelectorAll('.byl-mobile-submenu-toggle[aria-expanded="true"]').forEach(button => button.setAttribute('aria-expanded', 'false'));
  };

  const closeMenu = () => {
    if (!menuButton || !mobileMenu) return;
    menuButton.setAttribute('aria-expanded', 'false');
    mobileMenu.hidden = true;
    document.body.classList.remove('byl-shell-menu-open');
    resetMobileSubmenus();
  };
  const openMenu = () => {
    if (!menuButton || !mobileMenu) return;
    menuButton.setAttribute('aria-expanded', 'true');
    mobileMenu.hidden = false;
    document.body.classList.add('byl-shell-menu-open');
  };

  setupMobileSubmenus();
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
