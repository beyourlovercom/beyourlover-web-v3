/* BeYourLover Checkout Visual Polish V2 — Track A, presentation only.
 * No Woo checkout submission, Store API mutation, gateway state, order/payment,
 * address validation, consent or account settings modification.
 */
(() => {
  'use strict';
  const shell = document.querySelector('[data-byl-checkout-drawer-root]');
  const root = shell && shell.querySelector('[data-byl-progressive-checkout]');
  if (!shell || !root || !document.body.classList.contains('byl-checkout-drawer-mode')) return;

  const promotionHost = root.querySelector('[data-byl-promotion]');
  const error = root.querySelector('[data-byl-error]');
  const paymentHost = root.querySelector('[data-byl-payment-host]');
  const summary = root.querySelector('.byl-summary-card');
  const summaryToggle = root.querySelector('[data-byl-summary-toggle]');

  // Logged-in customers already see their Woo email. Do not draw their eye
  // to an account/profile navigation action in the middle of paying.
  const accountLink = root.querySelector('.byl-account-entry a');
  if (accountLink && accountLink.textContent.trim() === 'Edit your profile') {
    accountLink.closest('.byl-account-entry')?.classList.add('byl-account-entry--signed-in');
    accountLink.setAttribute('aria-label', 'Edit account settings');
    accountLink.textContent = 'Account';
  }

  function wooShippingGroups() {
    const groups = new Map();
    root.querySelectorAll('#order_review input[name^="shipping_method"]').forEach(input => {
      if (!input.name || !String(input.value || '').trim()) return;
      if (!groups.has(input.name)) groups.set(input.name, []);
      groups.get(input.name).push(input);
    });
    return [...groups.values()];
  }

  function isFreeRate(input) {
    // Native Woo method instance IDs have shape free_shipping:N.
    // Do not infer it from a visual label or a promotion threshold.
    return /^free_shipping(?::|$)/.test(String(input.value || ''));
  }

  function isSelected(input, group) {
    return input.checked || (group.length === 1 && input.type === 'hidden');
  }

  function syncPromotionTruth() {
    if (!promotionHost || promotionHost.hidden) return;
    const node = promotionHost.querySelector('.byl-promo-message');
    const title = promotionHost.querySelector('.byl-promo-title');
    if (!node || !title) return;

    // The Store API presentation owns the promotion eligibility message.
    // Only refine its "unlocked" wording against ACTUAL Woo shipping rates.
    const source = node.dataset.bylV2Source || node.textContent.trim();
    if (source !== 'Free shipping unlocked') return;
    if (!node.dataset.bylV2Source) node.dataset.bylV2Source = source;

    const groups = wooShippingGroups();
    let description, status;
    if (!groups.length) {
      status = 'pending';
      description = 'Shipping options depend on your delivery address.';
    } else if (groups.every(group => group.some(rate => isFreeRate(rate) && isSelected(rate, group)))) {
      status = 'applied';
      description = 'Free shipping applied to your selected delivery.';
    } else if (groups.every(group => group.some(isFreeRate))) {
      status = 'available';
      description = 'Free shipping is available — choose it above to apply.';
    } else {
      status = 'not-available';
      description = 'Shipping follows the rate available for your delivery address.';
    }
    promotionHost.dataset.bylVisualRate = status;
    const heading = status === 'pending' || status === 'not-available' ? 'Shipping & offers' : 'Free shipping';
    if (title.textContent !== heading) title.textContent = heading;
    if (node.textContent !== description) node.textContent = description;
  }

  function duplicateWooPaymentAlert() {
    if (!error || !paymentHost) return;
    // Only suppress the duplicate BYL-generated message when Woo has its own
    // accessible native warning. Never hide gateway errors or legal notices.
    const isPayment = root.dataset.bylCurrentStep === 'payment';
    const generated = error.textContent.trim();
    const isRedundant = generated === 'No payment method is available. Please try again later.';
    const notices = paymentHost.querySelectorAll('#payment .woocommerce-info, #payment .woocommerce-notice--info');
    const wooAlreadyExplained = [...notices].some(el =>
      /no available payment methods|no payment methods are available|payment methods are not available/i.test(
        el.textContent.replace(/\s+/g, ' ')
      )
    );
    const duplicate = isPayment && isRedundant && wooAlreadyExplained;
    if (duplicate) error.dataset.bylV2Duplicate = '1';
    else delete error.dataset.bylV2Duplicate;
  }

  function detailsMustExpandNotCollapse(event) {
    const target = event.target.closest('[data-byl-drawer-payment-total-details]');
    if (!target || !root.contains(target) || !summary || !summaryToggle) return;
    // Original Drawer handler toggled the OPEN left summary closed when
    // customers clicked "See details". Capture before that handler instead.
    event.preventDefault();
    event.stopImmediatePropagation();
    if (!summary.classList.contains('is-open')) summaryToggle.click();
    const orderReview = summary.querySelector('#order_review') || summary;
    orderReview.scrollIntoView?.({block:'nearest',behavior:'auto'});
  }
  root.addEventListener('click', detailsMustExpandNotCollapse, true);

  let pending = false;
  function scheduleSync() {
    if (pending) return;
    pending = true;
    const run = () => {
      pending = false;
      syncPromotionTruth();
      duplicateWooPaymentAlert();
    };
    if (typeof requestAnimationFrame === 'function') requestAnimationFrame(run);
    else run();
  }

  const observers = [];
  function watch(node) {
    if (!node || typeof MutationObserver === 'undefined') return;
    const observer = new MutationObserver(scheduleSync);
    observer.observe(node, {childList:true,subtree:true,characterData:true});
    observers.push(observer);
  }
  watch(promotionHost);
  watch(error);
  watch(paymentHost);

  // Woo refreshes shipping methods/gateways after address/rate changes.
  // Read new DOM only; never dispatch an update_checkout or other write here.
  if (window.jQuery) window.jQuery(document.body).on('updated_checkout.bylCheckoutVisualV2', scheduleSync);
  root.addEventListener('change', event => {
    if (event.target && /^shipping_method/.test(event.target.name || '')) scheduleSync();
  });
  if (typeof MutationObserver !== 'undefined') {
    const rootWatcher = new MutationObserver(() => {
      if (promotionHost) scheduleSync();
    });
    const review = root.querySelector('#order_review');
    if (review) {
      rootWatcher.observe(review, {childList:true,subtree:true});
      observers.push(rootWatcher);
    }
  }

  window.addEventListener('pagehide', () => {
    observers.forEach(observer => observer.disconnect());
    if (window.jQuery) window.jQuery(document.body).off('updated_checkout.bylCheckoutVisualV2');
  }, {once:true});
  scheduleSync();
})();
