(() => {
  'use strict';
  const header = document.querySelector('[data-byl-shell-header]');
  const desktopNav = document.querySelector('[data-byl-desktop-nav]');
  const menuButton = document.querySelector('[data-byl-menu-toggle]');
  const mobileMenu = document.getElementById('byl-mobile-menu');
  const searchDialog = document.querySelector('[data-byl-search-dialog]');
  const searchOpeners = document.querySelectorAll('[data-byl-search-open]');
  const searchCloser = document.querySelector('[data-byl-search-close]');
  const searchInput = document.getElementById('byl-global-search');
  let searchReturnFocus = null;

  const setDesktopOpen = (item, open) => {
    item.classList.toggle('is-open', open);
    const link = item.querySelector(':scope > a');
    if (link) link.setAttribute('aria-expanded', open ? 'true' : 'false');
  };

  const closeDesktopMenus = except => {
    desktopNav?.querySelectorAll('.menu-item-has-children.is-open').forEach(item => {
      if (item !== except) setDesktopOpen(item, false);
    });
  };

  const setupDesktopMenus = () => {
    desktopNav?.querySelectorAll('.menu-item-has-children').forEach((item, index) => {
      const link = item.querySelector(':scope > a');
      const submenu = item.querySelector(':scope > .sub-menu');
      if (!link || !submenu) return;
      const id = submenu.id || `byl-desktop-submenu-${index + 1}`;
      submenu.id = id;
      link.setAttribute('aria-haspopup', 'true');
      link.setAttribute('aria-expanded', 'false');
      link.setAttribute('aria-controls', id);
      if (submenu.children.length >= 5) item.classList.add('is-mega');
      let openTimer = 0;
      let closeTimer = 0;
      item.addEventListener('mouseenter', () => {
        window.clearTimeout(closeTimer);
        openTimer = window.setTimeout(() => { closeDesktopMenus(item); setDesktopOpen(item, true); }, 90);
      });
      item.addEventListener('mouseleave', () => {
        window.clearTimeout(openTimer);
        closeTimer = window.setTimeout(() => setDesktopOpen(item, false), 140);
      });
      item.addEventListener('focusin', () => { closeDesktopMenus(item); setDesktopOpen(item, true); });
      link.addEventListener('click', event => {
        if (!item.classList.contains('is-open')) {
          event.preventDefault();
          closeDesktopMenus(item);
          setDesktopOpen(item, true);
        }
      });
    });
  };

  const setupMobileLayers = () => {
    const root = mobileMenu?.querySelector('.byl-mobile-wordpress-menu');
    if (!root) return;
    root.querySelectorAll('.menu-item-has-children').forEach((item, index) => {
      const link = item.querySelector(':scope > a');
      const submenu = item.querySelector(':scope > .sub-menu');
      if (!link || !submenu) return;
      const id = submenu.id || `byl-mobile-panel-${index + 1}`;
      submenu.id = id;
      submenu.classList.add('byl-mobile-panel');
      const next = document.createElement('button');
      next.type = 'button';
      next.className = 'byl-mobile-next';
      next.setAttribute('aria-expanded', 'false');
      next.setAttribute('aria-controls', id);
      next.setAttribute('aria-label', `Open ${link.textContent?.trim() || 'submenu'}`);
      next.innerHTML = '<span aria-hidden="true">›</span>';
      link.insertAdjacentElement('afterend', next);
      const backItem = document.createElement('li');
      backItem.className = 'byl-mobile-panel-back';
      const back = document.createElement('button');
      back.type = 'button';
      back.innerHTML = `<span aria-hidden="true">‹</span><strong>${link.textContent?.trim() || 'Back'}</strong>`;
      backItem.appendChild(back);
      submenu.insertBefore(backItem, submenu.firstChild);
      next.addEventListener('click', () => {
        submenu.classList.add('is-panel-open');
        next.setAttribute('aria-expanded', 'true');
        back.focus();
      });
      back.addEventListener('click', () => {
        submenu.classList.remove('is-panel-open');
        next.setAttribute('aria-expanded', 'false');
        next.focus();
      });
    });
  };

  const resetMobilePanels = () => {
    mobileMenu?.querySelectorAll('.byl-mobile-panel.is-panel-open').forEach(panel => panel.classList.remove('is-panel-open'));
    mobileMenu?.querySelectorAll('.byl-mobile-next[aria-expanded="true"]').forEach(button => button.setAttribute('aria-expanded', 'false'));
  };

  const closeMenu = () => {
    if (!menuButton || !mobileMenu) return;
    menuButton.setAttribute('aria-expanded', 'false');
    mobileMenu.hidden = true;
    document.body.classList.remove('byl-shell-menu-open');
    resetMobilePanels();
  };

  const openMenu = () => {
    if (!menuButton || !mobileMenu) return;
    menuButton.setAttribute('aria-expanded', 'true');
    mobileMenu.hidden = false;
    document.body.classList.add('byl-shell-menu-open');
  };
  setupDesktopMenus();
  setupMobileLayers();

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

  document.addEventListener('click', event => {
    if (desktopNav && !desktopNav.contains(event.target)) closeDesktopMenus();
  });
  let ticking = false;
  const updateHeader = () => {
    header?.classList.toggle('is-scrolled', window.scrollY > 8);
    ticking = false;
  };
  window.addEventListener('scroll', () => {
    if (!ticking) { window.requestAnimationFrame(updateHeader); ticking = true; }
  }, { passive: true });
  updateHeader();

  window.addEventListener('resize', () => {
    if (window.innerWidth > 980) closeMenu();
    else closeDesktopMenus();
  });

  document.addEventListener('keydown', event => {
    if (event.key === 'Escape') {
      const openPanels = mobileMenu ? [...mobileMenu.querySelectorAll('.byl-mobile-panel.is-panel-open')] : [];
      if (openPanels.length) {
        const panel = openPanels[openPanels.length - 1];
        panel.querySelector(':scope > .byl-mobile-panel-back button')?.click();
      } else {
        closeMenu();
        closeDesktopMenus();
      }
    }
    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
      event.preventDefault();
      openSearch();
    }
  });
})();
