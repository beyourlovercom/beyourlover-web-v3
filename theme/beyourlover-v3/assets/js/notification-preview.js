/* N5 isolated mock: event generation only. No network or Woo requests. */
(() => {
  'use strict';
  const notify = window.BYL && window.BYL.notify;
  if (typeof notify !== 'function') return;
  const error = document.getElementById('demo-error');
  const status = document.getElementById('demo-status');
  let sequence = 0;
  const success = (key, message) => notify({
    type:'success', confirmed:true, context:'n5-mock',
    key:key + '-' + (++sequence), message
  });
  const actions = {
    add: () => success('add', 'Added to your cart'),
    remove: () => success('remove', 'Removed from your cart'),
    coupon: () => {
      notify.inlineError(error, '');
      success('coupon', 'Promo code applied');
    },
    invalid: () => notify.inlineError(error, 'This promo code was not accepted. Please check it and retry.'),
    burst: () => { for (let i=0;i<5;i++) success('rapid','Added to your cart'); },
    clear: () => {
      notify.invalidateAll();
      notify.inlineError(error, '');
      notify.status(status, 'Shipping eligibility is controlled by WooCommerce, not this mock.');
    }
  };
  document.querySelectorAll('[data-demo-action]').forEach(button => {
    const action = actions[button.getAttribute('data-demo-action')];
    if (action) button.addEventListener('click', action);
  });
  const mode = new URLSearchParams(location.search).get('scenario');
  if (mode === 'success') actions.add();
  if (mode === 'error') actions.invalid();
})();
