/* Progressive checkout presentation. WooCommerce remains payment, checkout validation and shipping authority. */
(function ($) {
  'use strict';
  const root = document.querySelector('[data-byl-progressive-checkout]');
  if (!root || !root.querySelector('form.checkout')) return;
  const form = root.querySelector('form.checkout');
  const steps = ['address', 'delivery', 'payment', 'review'];
  const next = root.querySelector('[data-byl-next]');
  const error = root.querySelector('[data-byl-error]');
  let active = 'address';
  let furthest = 0;
  let submitting = false;
  const notesHost = root.querySelector('[data-byl-notes-host]');
  const termsHost = root.querySelector('[data-byl-terms-host]');
  const paymentHost = root.querySelector('[data-byl-payment-host]');
  const deliveryHost = root.querySelector('[data-byl-delivery-options]');
  const summary = root.querySelector('.byl-summary-card');
  const summaryToggle = root.querySelector('[data-byl-summary-toggle]');
  const promoConfig = window.BYLShippingPromotionCheckout || {};
  const promotion = { data: null, editing: false, busy: false, cartToken: '' };

  function field(id) {
    const el = document.getElementById(id);
    return el ? (el.value || '').trim() : '';
  }
  function message(value) {
    error.textContent = value || '';
    error.hidden = !value;
  }
  function selectedShipping() {
    return document.querySelector('input[name^="shipping_method"]:checked');
  }
  function shippingLabel() {
    const input = selectedShipping();
    if (!input) return 'Awaiting delivery selection';
    const label = document.querySelector('label[for="' + CSS.escape(input.id) + '"]');
    return label ? label.textContent.replace(/\s+/g, ' ').trim() : input.value;
  }
  function selectedGateway() {
    return document.querySelector('#payment input[name="payment_method"]:checked');
  }
  function paymentConfigured() {
    if (root.dataset.needsPayment === '0') return true;
    return !!selectedGateway();
  }
  function paymentLabel() {
    if (root.dataset.needsPayment === '0') return 'No payment required';
    const g = selectedGateway();
    const l = g && document.querySelector('label[for="' + CSS.escape(g.id) + '"]');
    return l ? l.textContent.replace(/\s+/g, ' ').trim() : 'Payment not configured';
  }
  function relabelPhone() {
    const label = root.querySelector('#shipping_phone_field > label');
    if (!label) return;
    const text = Array.from(label.childNodes).find(node => node.nodeType === 3);
    if (text) text.nodeValue = 'Shipping phone ';
  }
  function syncPhoneDescription() {
    const description = root.querySelector('#shipping_phone-description');
    if (description) description.setAttribute('aria-hidden', 'false');
  }
  function mountNative() {
    const payment = document.getElementById('payment');
    if (payment && payment.parentElement !== paymentHost) paymentHost.appendChild(payment);
    const terms = document.querySelector('.woocommerce-terms-and-conditions-wrapper');
    if (terms && terms.parentElement !== termsHost) termsHost.appendChild(terms);
    // Woo fragments can reinsert the same terms block on updated_checkout.
    const blocks = Array.from(termsHost.querySelectorAll('.woocommerce-terms-and-conditions-wrapper'));
    blocks.slice(1).forEach(block => block.remove());
    const privacy = termsHost.querySelector('.woocommerce-privacy-policy-text p');
    if (privacy && !privacy.dataset.bylCompact) {
      const link = privacy.querySelector('a');
      if (link) {
        privacy.replaceChildren(document.createTextNode('See our '), link, document.createTextNode(' for how we use your data.'));
      }
      privacy.dataset.bylCompact = '1';
    }
    const reviewInvite = termsHost.querySelector('.cr-customer-consent .woocommerce-terms-and-conditions-checkbox-text');
    if (reviewInvite) reviewInvite.textContent = 'Send me an optional review invitation with gift card rewards.';
    const notes = document.querySelector('.woocommerce-additional-fields');
    if (notes && notes.parentElement !== notesHost) notesHost.appendChild(notes);
  }
  function deliveryOptions() {
    deliveryHost.replaceChildren();
    const native = Array.from(document.querySelectorAll('#order_review input[name^="shipping_method"]'));
    if (!native.length) {
      const p = document.createElement('p');
      p.className = 'byl-step-hint';
      p.textContent = 'Enter your delivery address to see available shipping methods.';
      deliveryHost.appendChild(p);
      return;
    }
    native.forEach(input => {
      const label = document.querySelector('label[for="' + CSS.escape(input.id) + '"]');
      const title = label ? label.textContent.replace(/\s+/g, ' ').trim() : input.value;
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'byl-delivery-rate' + (input.checked ? ' is-selected' : '');
      button.setAttribute('aria-pressed', input.checked ? 'true' : 'false');
      const radio = document.createElement('span');
      radio.className = 'byl-delivery-radio';
      radio.setAttribute('aria-hidden', 'true');
      const description = document.createElement('span');
      description.className = 'byl-delivery-label';
      const strong = document.createElement('strong');
      strong.textContent = title;
      description.appendChild(strong);
      button.append(radio, description);
      button.addEventListener('click', () => {
        input.checked = true;
        input.dispatchEvent(new Event('change', {bubbles: true}));
        deliveryOptions();
        updateDetails();
      });
      deliveryHost.appendChild(button);
    });
  }
  function review(target, val) {
    const el = root.querySelector('[data-byl-review="' + target + '"]');
    if (el) el.textContent = val || 'Not provided';
  }
  function updateShippingSummary() {
    const cell = root.querySelector('#order_review tr.woocommerce-shipping-totals td, #order_review tr.shipping td');
    if (cell) {
      let display = cell.querySelector('[data-byl-shipping-display]');
      if (!display) {
        display = document.createElement('span');
        display.dataset.bylShippingDisplay = '';
        display.className = 'byl-shipping-display';
        cell.appendChild(display);
      }
      display.textContent = selectedShipping() ? shippingLabel() : 'Calculated at delivery';
    }
    const total = root.querySelector('#order_review .order-total .woocommerce-Price-amount');
    const compact = root.querySelector('.byl-summary-total');
    if (total && compact) compact.textContent = total.textContent;
  }
  function updateDetails() {
    const name = [field('billing_first_name'), field('billing_last_name')].filter(Boolean).join(' ');
    const address = [field('billing_address_1'), field('billing_address_2'), field('billing_city'), field('billing_state'), field('billing_postcode'), field('billing_country')].filter(Boolean).join(', ');
    const contact = [field('billing_email'), field('shipping_phone')].filter(Boolean).join(' · ');
    review('address', [name, address].filter(Boolean).join(' · '));
    review('contact', contact);
    review('delivery', shippingLabel());
    review('payment', paymentLabel());
    updateShippingSummary();
    const addressSummary = root.querySelector('[data-byl-step-summary="address"]');
    const deliverySummary = root.querySelector('[data-byl-step-summary="delivery"]');
    const paymentSummary = root.querySelector('[data-byl-step-summary="payment"]');
    if (addressSummary && name && address) addressSummary.textContent = name + ' · ' + address;
    if (deliverySummary && selectedShipping()) deliverySummary.textContent = shippingLabel();
    if (paymentSummary && selectedGateway()) paymentSummary.textContent = paymentLabel();
  }
  function validateAddress() {
    let firstInvalid = null;
    root.querySelectorAll('.byl-checkout-billing .validate-required').forEach(wrapper => {
      const input = wrapper.querySelector('input:not([type=hidden]), select, textarea');
      if (!input || input.disabled) return;
      const empty = !String(input.value || '').trim();
      const badEmail = input.type === 'email' && !!input.value && !input.validity.valid;
      wrapper.classList.toggle('woocommerce-invalid', empty || badEmail);
      if ((empty || badEmail) && !firstInvalid) firstInvalid = input;
    });
    if (firstInvalid) {
      message('Please complete the required shipping and contact information.');
      firstInvalid.focus({preventScroll:false});
      return false;
    }
    return true;
  }
  function updateAction() {
    const labels = { address:'Continue to Delivery', delivery:'Continue to Payment', payment:'Continue to Review', review:'Place Order' };
    next.innerHTML = '';
    next.appendChild(document.createTextNode(labels[active] + ' '));
    const arrow = document.createElement('span');
    arrow.textContent = '→';
    arrow.setAttribute('aria-hidden','true');
    next.appendChild(arrow);
    if (active === 'review' && !paymentConfigured()) {
      next.disabled = true;
      message('Payment is not configured on Dev yet. Order submission is disabled.');
    } else {
      next.disabled = false;
    }
  }
  function setStep(step) {
    const index = steps.indexOf(step);
    if (index < 0 || index > furthest) return;
    active = step;
    root.querySelectorAll('[data-byl-step]').forEach(section => {
      const name = section.dataset.bylStep;
      const number = steps.indexOf(name);
      const isActive = name === active;
      section.classList.toggle('is-active', isActive);
      section.classList.toggle('is-complete', number < furthest);
      const trigger = section.querySelector('.byl-step-toggle');
      if (trigger) trigger.setAttribute('aria-expanded', isActive ? 'true' : 'false');
    });
    message('');
    mountNative();
    relabelPhone();
    syncPhoneDescription();
    deliveryOptions();
    updateDetails();
    updateAction();
    const heading = root.querySelector('[data-byl-step="' + step + '"]');
    if (heading) heading.scrollIntoView({block:'start',behavior:'auto'});
  }
  next.addEventListener('click', () => {
    if (submitting) return;
    if (active === 'address' && !validateAddress()) return;
    if (active === 'delivery' && !selectedShipping()) {
      message('Please select an available delivery method.');
      return;
    }
    if (active === 'review') {
      if (!paymentConfigured()) {
        message('A real payment gateway must be configured before an order can be placed.');
        return;
      }
      const nativeButton = document.getElementById('place_order');
      if (!nativeButton) {
        message('WooCommerce payment confirmation is unavailable.');
        return;
      }
      submitting = true;
      window.setTimeout(() => {submitting = false;}, 6000);
      nativeButton.click(); // Native Woo validation, nonce and gateway callback remain authoritative.
      return;
    }
    furthest = Math.max(furthest, steps.indexOf(active) + 1);
    setStep(steps[steps.indexOf(active) + 1]);
    if (active === 'delivery') $('body').trigger('update_checkout');
  });
  root.addEventListener('click', e => {
    const edit = e.target.closest('[data-byl-open-step]');
    if (edit) { e.preventDefault(); setStep(edit.dataset.bylOpenStep); }
  });
  form.addEventListener('submit', e => {
    if (active !== 'review' || !paymentConfigured() || !validateAddress() || !selectedShipping()) {
      e.preventDefault();
      e.stopImmediatePropagation();
      message('Complete all checkout steps before placing your order.');
    }
  }, true);

  function promotionMessage(data) {
    if (!data) return '';
    if (data.state === 'below_threshold' || data.state === 'require_code_applied') {
      return 'Add ' + Number(data.remaining || 0).toFixed(2) + ' more for free shipping';
    }
    if (['automatic_unlocked', 'automatic_code', 'require_code_unlocked'].includes(data.state)) {
      return 'Free shipping unlocked';
    }
    if (data.state === 'require_code_available') return 'Free shipping available';
    if (data.state === 'require_code_other') {
      return 'Replace your current code with ' + String(data.policy_code || '').toUpperCase() + ' to unlock free shipping.';
    }
    return '';
  }

  function promotionHeaders(jsonRequest) {
    const headers = {};
    if (jsonRequest) headers['Content-Type'] = 'application/json';
    if (promoConfig.nonce) headers.Nonce = promoConfig.nonce;
    if (promotion.cartToken) headers['Cart-Token'] = promotion.cartToken;
    return headers;
  }

  function capturePromotionHeaders(response) {
    const nonce = response.headers.get('Nonce');
    const token = response.headers.get('Cart-Token');
    if (nonce) promoConfig.nonce = nonce;
    if (token) promotion.cartToken = token;
  }

  function renderPromotion() {
    const host = root.querySelector('[data-byl-promotion]');
    const data = promotion.data;
    if (!host) return;
    if (!data || !data.active) {
      host.hidden = true;
      return;
    }

    host.hidden = false;
    host.replaceChildren();

    const label = document.createElement('strong');
    label.className = 'byl-promo-title';
    label.textContent = 'Free shipping';
    host.appendChild(label);

    const messageNode = document.createElement('p');
    messageNode.className = 'byl-promo-message';
    messageNode.setAttribute('aria-live', 'polite');
    messageNode.textContent = promotionMessage(data);
    host.appendChild(messageNode);

    if (data.state === 'require_code_available') {
      const hint = document.createElement('p');
      hint.className = 'byl-promo-hint';
      hint.textContent = 'Use code ' + String(data.policy_code || '').toUpperCase();
      host.appendChild(hint);
    }

    const applied = !!(data.has_code && data.applied_code);
    if (applied && !promotion.editing) {
      const row = document.createElement('p');
      row.className = 'byl-promo-applied';
      const text = document.createElement('strong');
      text.textContent = data.applied_code + ' applied';
      const edit = document.createElement('button');
      edit.type = 'button';
      edit.className = 'byl-promo-edit';
      edit.textContent = 'Edit';
      edit.addEventListener('click', () => {
        promotion.editing = true;
        renderPromotion();
      });
      row.append(text, document.createTextNode(' '), edit);
      host.appendChild(row);
    } else {
      const promoForm = document.createElement('div');
      promoForm.className = 'byl-summary-promo-row byl-promo-form';
      const input = document.createElement('input');
      input.id = 'byl-promo-code';
      input.name = 'code';
      input.type = 'text';
      input.autocomplete = 'off';
      input.placeholder = 'Promo code';
      input.setAttribute('aria-label', 'Promo code');
      if (applied) input.value = data.applied_code;
      const apply = document.createElement('button');
      apply.type = 'button';
      apply.textContent = 'Apply';
      apply.disabled = promotion.busy;
      apply.addEventListener('click', () => mutatePromotion(promoConfig.applyUrl, {code: input.value.trim()}));
      promoForm.append(input, apply);
      host.appendChild(promoForm);

      if (applied) {
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'byl-promo-remove';
        remove.textContent = 'Remove';
        remove.disabled = promotion.busy;
        remove.addEventListener('click', () => mutatePromotion(promoConfig.removeUrl, {code: data.applied_code}));
        host.appendChild(remove);
      }
    }

    const errorNode = document.createElement('p');
    errorNode.className = 'byl-promo-error';
    errorNode.setAttribute('role', 'alert');
    errorNode.hidden = true;
    host.appendChild(errorNode);
  }

  function showPromotionError(value) {
    const node = root.querySelector('.byl-promo-error');
    if (!node) return;
    node.textContent = value || '';
    node.hidden = !value;
  }

  async function fetchPromotion() {
    if (!promoConfig.cartUrl) return;
    try {
      const response = await fetch(promoConfig.cartUrl, {
        credentials: 'same-origin',
        headers: promotionHeaders(false)
      });
      capturePromotionHeaders(response);
      const json = await response.json();
      promotion.data = json && json.extensions ? json.extensions['byl-shipping-promotion'] : null;
      renderPromotion();
    } catch (e) {
      // Checkout remains usable; Woo remains authoritative if presentation data cannot load.
    }
  }

  async function mutatePromotion(url, body) {
    if (!url || promotion.busy) return;
    if (!body || !String(body.code || '').trim()) {
      showPromotionError('Enter a promo code first.');
      return;
    }

    promotion.busy = true;
    renderPromotion();
    try {
      const response = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: promotionHeaders(true),
        body: JSON.stringify(body)
      });
      capturePromotionHeaders(response);
      const json = await response.json().catch(() => ({}));
      if (!response.ok) throw new Error(json.message || 'Could not update this promo code.');
      promotion.data = json && json.extensions ? json.extensions['byl-shipping-promotion'] : promotion.data;
      promotion.editing = false;
      renderPromotion();
      $('body').trigger('update_checkout');
    } catch (e) {
      renderPromotion();
      showPromotionError(e && e.message ? e.message : 'Could not update this promo code.');
    } finally {
      promotion.busy = false;
      renderPromotion();
    }
  }

  form.addEventListener('input', updateDetails);
  form.addEventListener('change', updateDetails);
  $(document.body).on('updated_checkout', () => {
    mountNative();
    relabelPhone();
    syncPhoneDescription();
    deliveryOptions();
    updateDetails();
    updateAction();
    fetchPromotion();
  });
  $(document.body).on('checkout_error', () => { submitting = false; });
  if (summaryToggle) summaryToggle.addEventListener('click', () => {
    const open = !summary.classList.contains('is-open');
    summary.classList.toggle('is-open',open);
    summaryToggle.setAttribute('aria-expanded',open?'true':'false');
  });
  summary.classList.toggle('is-open', !window.matchMedia('(max-width: 760px)').matches);
  summaryToggle.setAttribute('aria-expanded', summary.classList.contains('is-open') ? 'true' : 'false');
  root.classList.add('is-enhanced');
  mountNative();
  fetchPromotion();
  setStep('address');
})(jQuery);
