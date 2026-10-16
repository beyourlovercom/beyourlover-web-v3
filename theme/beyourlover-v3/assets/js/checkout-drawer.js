/* Owner Option B: native Woo Classic checkout route displayed as a Drawer.
 * A single native form stays in its own checkout document. This code NEVER
 * clones a gateway, intercepts #place_order, creates an account or pays.
 */
(function () {
  'use strict';
  const shell = document.querySelector('[data-byl-checkout-drawer-root]');
  if (!shell) return;
  const panel = shell.querySelector('[data-byl-checkout-drawer-panel]');
  const scroll = shell.querySelector('[data-byl-checkout-drawer-scroll]');
  const closeButton = shell.querySelector('[data-byl-checkout-drawer-close]');
  const checkout = shell.querySelector('[data-byl-progressive-checkout]');
  const stages = checkout && checkout.querySelector('.byl-checkout-stages');
  const heading = checkout && checkout.querySelector('.byl-checkout-heading');
  const track = checkout && checkout.querySelector('.byl-checkout-track');
  const config = window.BYLCheckoutDrawerConfig || {};
  if (!panel || !scroll || !closeButton || !checkout || !stages || !heading || !track
      || shell.querySelectorAll('form.checkout').length !== 1) {
    // Missing Woo native host: do not replace or simulate checkout controls.
    return;
  }

  // Move presentation headers only. Payment fields, order review and all
  // gateway state remain children of Woo's sole native checkout form.
  stages.insertBefore(track, stages.firstChild);
  stages.insertBefore(heading, track);

  const fallbackUrl = (() => {
    try {
      const url = new URL(config.fallbackUrl || '/cart/', location.origin);
      return url.origin === location.origin ? url.href : new URL('/cart/', location.origin).href;
    } catch (_) { return new URL('/cart/', location.origin).href; }
  })();
  const previousUrl = (() => {
    try {
      const url = new URL(document.referrer);
      const here = new URL(location.href);
      // Never permit a cross-domain redirect, protocol downgrade or a
      // Checkout/Order Pay loop when closing the drawer.
      if (url.origin !== here.origin || url.pathname === here.pathname
          || /\/(checkout|order-pay|order-received)\//.test(url.pathname)) return '';
      return url.href;
    } catch (_) { return ''; }
  })();

  // Match Owner composition: desktop left column Your Cart; mobile fixed
  // footer Order total + See details, with Woo as the sole value source.
  const summaryToggle = checkout.querySelector('[data-byl-summary-toggle]');
  const summaryTitle = summaryToggle && summaryToggle.querySelector('strong');
  const summaryAmount = summaryToggle && summaryToggle.querySelector('.byl-summary-total');
  const details = document.createElement('span');
  details.className = 'byl-drawer-see-details';
  details.textContent = 'See details';
  if (summaryToggle && summaryAmount) summaryToggle.insertBefore(details, summaryAmount);
  // Desktop Owner PASS shows "YOUR CART (n)" on the left with one Edit action,
  // not a second sticky total beside that heading. Preserve the native Woo
  // summary toggle and totals; only regroup their existing presentation.
  if (summaryToggle) {
    const headerRow = document.createElement('div');
    headerRow.className = 'byl-drawer-summary-head';
    summaryToggle.parentNode.insertBefore(headerRow, summaryToggle);
    headerRow.appendChild(summaryToggle);
    const editCart = document.createElement('button');
    editCart.type = 'button';
    editCart.className = 'byl-drawer-edit-cart';
    editCart.textContent = 'Edit';
    editCart.setAttribute('aria-label', 'Edit your cart');
    headerRow.appendChild(editCart);
    editCart.addEventListener('click', () => exitDrawer());
  }
  // Display-only echo of Woo computed summary total; no arithmetic.
  const paymentTotalAmount = checkout.querySelector('[data-byl-drawer-payment-total-amount]');
  const paymentTotalDetails = checkout.querySelector('[data-byl-drawer-payment-total-details]');
  function refreshPaymentTotal() {
    if (paymentTotalAmount && summaryAmount) paymentTotalAmount.textContent = summaryAmount.textContent;
  }
  if (paymentTotalDetails && summaryToggle) paymentTotalDetails.addEventListener('click', () => {
    summaryToggle.click();
    const summary = summaryToggle.closest('.byl-summary-card');
    if (summary && summary.classList.contains('is-open')) summary.scrollIntoView({block:'nearest',behavior:'auto'});
  });
  if (summaryAmount && paymentTotalAmount) {
    const amountObserver = new MutationObserver(refreshPaymentTotal);
    amountObserver.observe(summaryAmount, {childList:true,characterData:true,subtree:true});
    window.addEventListener('pagehide', () => amountObserver.disconnect(), {once:true});
  }
  refreshPaymentTotal();
  function updateResponsiveSummaryLabel() {
    const mobile = window.matchMedia('(max-width:760px)').matches;
    if (summaryTitle) summaryTitle.textContent = mobile ? 'Order total' : 'Your Cart';
    details.hidden = !mobile;
  }
  updateResponsiveSummaryLabel();
  window.addEventListener('resize', updateResponsiveSummaryLabel, {passive:true});

  const back = document.createElement('button');
  back.type = 'button';
  back.className = 'byl-checkout-drawer-back';
  back.dataset.bylCheckoutDrawerBack = '';
  back.textContent = '‹ Your Cart';
  heading.insertAdjacentElement('afterend', back); // Shipping: back precedes progress.

  function closingDestination() {
    return previousUrl || fallbackUrl;
  }
  function exitDrawer() {
    if (checkout.dataset.bylSubmitting === '1') return;
    // A one-shot visual hint only; NEVER persist Woo/cart/payment/PII data.
    // The existing Dev Cart Drawer reopens on the previous page after the
    // secure native checkout route is exited.
    if (previousUrl) {
      try { window.sessionStorage.setItem('byl-checkout-return-cart-open', '1'); }
      catch (_) { /* Private browsing can restrict storage. */ }
    }
    location.assign(closingDestination());
  }
  closeButton.addEventListener('click', event => { event.preventDefault(); exitDrawer(); });
  const scrim = shell.querySelector('[data-byl-checkout-drawer-scrim]');
  if (scrim) scrim.addEventListener('click', exitDrawer);
  back.addEventListener('click', () => {
    if (checkout.dataset.bylCurrentStep === 'payment') {
      const existingNativeStep = stages.querySelector('[data-byl-open-step="shipping"]');
      if (existingNativeStep) existingNativeStep.click();
      return;
    }
    exitDrawer();
  });
  const cartProgress = track.querySelector('[data-byl-track="cart"]');
  if (cartProgress) cartProgress.addEventListener('click', function (event) {
    event.preventDefault();
    exitDrawer();
  });

  function setDrawerHeading() {
    const payment = checkout.dataset.bylCurrentStep === 'payment';
    const title = heading.querySelector('[data-byl-checkout-heading]');
    if (title) title.textContent = payment ? 'Payment' : 'Shipping';
    back.textContent = payment ? '‹ Shipping' : '‹ Your Cart';
    // Exact Owner Shipping/Payment image differences:
    // Shipping: title -> Your Cart -> 3 icons;
    // Payment: title -> 3 icons -> Shipping -> gateway list.
    if (payment) track.insertAdjacentElement('afterend', back);
    else heading.insertAdjacentElement('afterend', back);
    shell.dataset.bylDrawerStep = payment ? 'payment' : 'shipping';
    // Ensure the scrolling panel starts at the header after a phase change,
    // without scrolling the document or jumping to a different route.
    if (scroll.scrollTop > 0) scroll.scrollTop = 0;
  }
  const observer = new MutationObserver(setDrawerHeading);
  observer.observe(checkout, {attributes:true,attributeFilter:['data-byl-current-step']});

  function focusable() {
    return Array.from(panel.querySelectorAll(
      'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), ' +
      'textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
    )).filter(node => node.getClientRects().length > 0 && !node.hidden
      && !node.closest('[hidden], [aria-hidden="true"]'));
  }
  panel.addEventListener('keydown', event => {
    if (event.key === 'Escape') {
      // Do not hijack a provider-hosted iframe/3DS challenge. Native gateways
      // retain full control when their own modal has focused controls.
      if (document.activeElement && document.activeElement.closest(
        '.payment_box, .wc-stripe-elements-field, [role="alertdialog"]'
      )) return;
      event.preventDefault();
      exitDrawer();
      return;
    }
    if (event.key !== 'Tab') return;
    const items = focusable();
    if (!items.length) { event.preventDefault(); panel.focus(); return; }
    const first = items[0], last = items[items.length - 1];
    if (event.shiftKey && (document.activeElement === first || document.activeElement === panel)) {
      event.preventDefault(); last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault(); first.focus();
    }
  });

  document.documentElement.classList.add('byl-checkout-drawer-document');
  shell.classList.add('is-ready');
  setDrawerHeading();
  closeButton.focus({preventScroll:true});
  window.addEventListener('pagehide', () => observer.disconnect(), {once:true});
})();
