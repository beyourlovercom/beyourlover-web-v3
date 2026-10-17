/* Progressive checkout presentation. WooCommerce remains payment, checkout validation and shipping authority. */
(function ($) {
  'use strict';
  const root = document.querySelector('[data-byl-progressive-checkout]');
  if (!root || !root.querySelector('form.checkout')) return;
  const form = root.querySelector('form.checkout');
  const steps = ['shipping', 'payment'];
  const next = root.querySelector('[data-byl-next]');
  const error = root.querySelector('[data-byl-error]');
  let active = 'shipping';
  let furthest = 0;
  let submitting = false;
  const notesHost = root.querySelector('[data-byl-notes-host]');
  const termsHost = root.querySelector('[data-byl-terms-host]');
  const paymentHost = root.querySelector('[data-byl-payment-host]');
  const deliveryHost = root.querySelector('[data-byl-delivery-options]');
  const summary = root.querySelector('.byl-summary-card');
  const summaryToggle = root.querySelector('[data-byl-summary-toggle]');
  const mobileSummaryToggle = root.querySelector('[data-byl-mobile-summary-toggle]');
  const mobileSummaryTotal = root.querySelector('[data-byl-mobile-total]');
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
  function wooShippingGroups() {
    // Woo can return exactly one hidden rate instead of a checked radio.
    // Multi-package carts require ONE selected rate per native package name.
    const groups = new Map();
    root.querySelectorAll('#order_review input[name^="shipping_method"]').forEach(input => {
      if (!input.name) return;
      if (!groups.has(input.name)) groups.set(input.name, []);
      groups.get(input.name).push(input);
    });
    return groups;
  }
  function chosenShippingRates() {
    return Array.from(wooShippingGroups().values()).map(options => {
      const checked = options.find(input => input.checked && String(input.value || '').trim());
      if (checked) return checked;
      const only = options.length === 1 ? options[0] : null;
      return only && only.type === 'hidden' && String(only.value || '').trim() ? only : null;
    }).filter(Boolean);
  }
  function selectedShipping() {
    return chosenShippingRates()[0] || null;
  }
  function shippingLabel() {
    const choices = chosenShippingRates();
    if (!choices.length) return 'Awaiting delivery selection';
    return choices.map(input => {
      const label = input.id && document.querySelector('label[for="' + CSS.escape(input.id) + '"]');
      return label ? label.textContent.replace(/\s+/g, ' ').trim() : input.value;
    }).join(' · ');
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
    if (text) text.nodeValue = 'Phone ';
  }
  function mountWooAccountEmail() {
    // Reparent Woo's real email field instead of inventing a parallel email input.
    const host = root.querySelector('[data-byl-account-email-host]');
    const field = root.querySelector('#billing_email_field');
    if (host && field && field.parentElement !== host) host.appendChild(field);
  }
  function wireOptionalAddressFields() {
    // Progressive disclosure only. Never hide an already populated address.
    [
      ['billing_address_2_field', '+ Add apt, suite, floor or other (optional)'],
      ['billing_company_field', '+ Add organization (optional)'],
    ].forEach(([id, title]) => {
      const row = root.querySelector('#' + id);
      if (!row || row.dataset.bylOptionalWired === '1') return;
      const input = row.querySelector('input, textarea, select');
      if (!input) return;
      row.dataset.bylOptionalWired = '1';
      const trigger = document.createElement('button');
      trigger.type = 'button';
      trigger.className = 'byl-optional-address-trigger';
      trigger.textContent = title;
      trigger.dataset.bylOptionalFor = id;
      trigger.setAttribute('aria-controls', id);
      const expanded = String(input.value || '').trim().length > 0;
      row.hidden = !expanded;
      trigger.hidden = expanded;
      trigger.setAttribute('aria-expanded', expanded ? 'true' : 'false');
      row.parentElement.insertBefore(trigger, row);
      trigger.addEventListener('click', () => {
        row.hidden = false;
        trigger.hidden = true;
        trigger.setAttribute('aria-expanded', 'true');
        input.focus({preventScroll: false});
      });
    });
  }
  function alignShippingAddressFields() {
    // The same DOM/keyboard order must match the Owner reference at both
    // desktop and mobile widths. Move existing Woo wrappers ONLY, never clone
    // country/state/tax inputs or replace a live Woo SelectWoo control.
    const host = document.getElementById('billing_first_name_field')?.parentElement;
    if (!host) return;
    const known = {
      billing_country_field: 10,
      billing_first_name_field: 20,
      billing_last_name_field: 30,
      billing_address_1_field: 40,
      billing_address_2_field: 51,
      billing_city_field: 60,
      billing_state_field: 70,
      shipping_phone_field: 80,
      billing_postcode_field: 90,
      billing_company_field: 120,
    };
    const position = node => {
      if (node.dataset.bylOptionalFor === 'billing_address_2_field') return 50;
      if (node.dataset.bylOptionalFor === 'billing_company_field') return 119;
      if (Object.prototype.hasOwnProperty.call(known, node.id)) return known[node.id];
      // Conditional real tax/RFC/VAT/TIN fields from other Woo extensions, if
      // present, belong AFTER Postal code and BEFORE optional Organization.
      const key = (node.id || '') + ' ' + (node.querySelector('input,select,textarea')?.name || '');
      if (/(?:tax|tin|rfc|vat|cpf|fiscal|customs)/i.test(key)) return 100;
      return 110;
    };
    const current = Array.from(host.children);
    const desired = current.map((node, index) => ({node, index, priority: position(node)}))
      .sort((a, b) => a.priority - b.priority || a.index - b.index)
      .map(entry => entry.node);
    if (current.some((node, index) => node !== desired[index])) {
      // Only rearrange on a real mismatch. Reparenting on every Woo AJAX
      // update would steal field focus and cause the reported lag.
      desired.forEach(node => host.appendChild(node));
    }
  }
  function wireOptionalDeliveryNote() {
    // Woo owns this real optional field. Never clone it, remove the name or
    // invent a new data contract merely for visual presentation.
    const notes = root.querySelector('[data-byl-notes-host] .woocommerce-additional-fields');
    if (!notes || notes.dataset.bylDeliveryNoteWired === '1') return;
    const input = notes.querySelector('#order_comments, textarea[name="order_comments"]');
    if (!input) return;
    const host = notes.parentElement;
    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'byl-optional-address-trigger byl-optional-delivery-note';
    trigger.textContent = '+ Add delivery note (optional)';
    trigger.setAttribute('aria-controls', input.id || 'order_comments');
    const populated = !!String(input.value || '').trim();
    trigger.hidden = populated;
    notes.hidden = !populated;
    trigger.setAttribute('aria-expanded', populated ? 'true' : 'false');
    host.insertBefore(trigger, notes);
    trigger.addEventListener('click', () => {
      notes.hidden = false;
      trigger.hidden = true;
      trigger.setAttribute('aria-expanded', 'true');
      input.focus({preventScroll:false});
    });
    notes.dataset.bylDeliveryNoteWired = '1';
  }
  function syncPhoneDescription() {
    const description = root.querySelector('#shipping_phone-description');
    if (description) description.setAttribute('aria-hidden', 'false');
  }
  // One inline accordion for both legal documents. Terms remain Woo-owned;
  // Privacy is loaded read-only from the published WordPress Privacy page.
  let privacyPolicyPromise = null;
  function safePolicyFragment(markup) {
    const parsed = new DOMParser().parseFromString(markup, 'text/html');
    const allowed = new Set(['P','H2','H3','H4','STRONG','EM','B','I','UL','OL','LI','A','BR','HR','BLOCKQUOTE']);
    const ignored = new Set(['SCRIPT','STYLE','IFRAME','OBJECT','FORM','INPUT','BUTTON','SVG','NOSCRIPT']);
    function copyNode(source) {
      if (source.nodeType === Node.TEXT_NODE) return document.createTextNode(source.textContent);
      if (source.nodeType !== Node.ELEMENT_NODE || ignored.has(source.tagName)) return null;
      const output = allowed.has(source.tagName) ? document.createElement(source.tagName.toLowerCase()) : document.createDocumentFragment();
      if (source.tagName === 'A' && source.hasAttribute('href')) {
        try {
          const url = new URL(source.getAttribute('href'), location.origin);
          if (['https:', 'http:', 'mailto:'].includes(url.protocol)) {
            output.setAttribute('href', url.href);
            output.setAttribute('rel', 'noopener noreferrer');
          }
        } catch (_) { /* Invalid source link stays plain text. */ }
      }
      source.childNodes.forEach(child => { const safe = copyNode(child); if (safe) output.appendChild(safe); });
      return output;
    }
    const result = document.createDocumentFragment();
    parsed.body.childNodes.forEach(child => { const safe = copyNode(child); if (safe) result.appendChild(safe); });
    return result;
  }
  async function fetchPrivacyPolicy(link) {
    if (privacyPolicyPromise) return privacyPolicyPromise;
    privacyPolicyPromise = (async () => {
      const configured = new URL(link.href, location.href);
      if (configured.origin !== location.origin) throw new Error('Privacy Policy is not on this site.');
      const slug = configured.pathname.split('/').filter(Boolean).pop();
      if (!slug) throw new Error('Privacy Policy page is not configured.');
      const endpoint = new URL('/wp-json/wp/v2/pages', location.origin);
      endpoint.searchParams.set('slug', slug);
      endpoint.searchParams.set('_fields', 'id,link,content');
      const response = await fetch(endpoint.href, {credentials:'same-origin', headers:{Accept:'application/json'}});
      if (!response.ok) throw new Error('Privacy Policy is temporarily unavailable.');
      const pages = await response.json();
      const page = Array.isArray(pages) && pages.find(item => {
        try { return new URL(item.link).pathname.replace(/\/$/, '') === configured.pathname.replace(/\/$/, ''); }
        catch (_) { return false; }
      });
      const html = page && page.content && page.content.rendered;
      if (typeof html !== 'string' || !html.trim() || html.length > 150000) throw new Error('Privacy Policy content is unavailable.');
      return html;
    })().catch(err => { privacyPolicyPromise = null; throw err; });
    return privacyPolicyPromise;
  }
  function wireLegalAccordion() {
    if (termsHost.dataset.bylLegalWired === '1') return;
    termsHost.dataset.bylLegalWired = '1';
    termsHost.addEventListener('click', event => {
      const toggle = event.target.closest('a[data-byl-policy-toggle]');
      if (!toggle || !termsHost.contains(toggle)) return;
      event.preventDefault();
      event.stopPropagation(); // Do not invoke Woo's separate Terms-only click handler.
      const wrapper = toggle.closest('.woocommerce-terms-and-conditions-wrapper');
      const panel = wrapper && wrapper.querySelector('.woocommerce-terms-and-conditions');
      const termsBody = panel && panel.querySelector('[data-byl-policy-terms]');
      const privacyBody = panel && panel.querySelector('[data-byl-policy-privacy]');
      if (!panel || !termsBody || !privacyBody) return;
      const kind = toggle.dataset.bylPolicyToggle;
      const open = $(panel).css('display') !== 'none';
      const same = panel.dataset.bylPolicyShowing === kind;
      const toggles = wrapper.querySelectorAll('a[data-byl-policy-toggle]');
      if (same && open) {
        $(panel).stop(true, true).slideUp(160);
        toggles.forEach(link => link.setAttribute('aria-expanded', 'false'));
        return;
      }
      panel.dataset.bylPolicyShowing = kind;
      termsBody.hidden = kind !== 'terms';
      privacyBody.hidden = kind !== 'privacy';
      toggles.forEach(link => link.setAttribute('aria-expanded', String(link.dataset.bylPolicyToggle === kind)));
      $(panel).stop(true, true).slideDown(160);
      if (kind === 'privacy' && !privacyBody.dataset.bylLoaded) {
        privacyBody.textContent = 'Loading Privacy Policy…';
        fetchPrivacyPolicy(toggle).then(markup => {
          if (!privacyBody.isConnected) return;
          privacyBody.replaceChildren(safePolicyFragment(markup));
          privacyBody.dataset.bylLoaded = '1';
        }).catch(() => {
          if (privacyBody.isConnected) privacyBody.textContent = 'Privacy Policy could not load. Select Privacy Policy again to retry.';
        });
      }
    });
  }
  function mountNative() {
    // Woo refreshes order_review on address/shipping/coupon changes. When the
    // native #payment fragment is regenerated, it MUST replace our previous
    // moved fragment; document.getElementById would otherwise return the stale
    // moved node and leave duplicate #payment / #place_order controls.
    const review = root.querySelector('#order_review');
    const fresh = review && review.querySelector('#payment');
    const mounted = paymentHost.querySelector('#payment');
    const payment = fresh || mounted || root.querySelector('#payment');
    if (fresh && mounted && mounted !== fresh) mounted.remove();
    if (payment && payment.parentElement !== paymentHost) paymentHost.appendChild(payment);

    // Terms/Privacy are also Woo-owned. Prefer the refreshed terms from the
    // new payment fragment; otherwise we risk submitting stale legal fields.
    const oldTerms = termsHost.querySelector('.woocommerce-terms-and-conditions-wrapper');
    if (fresh && oldTerms) oldTerms.remove();
    const terms = (fresh ? payment && payment.querySelector('.woocommerce-terms-and-conditions-wrapper') : null)
      || (fresh ? null : oldTerms || (payment && payment.querySelector('.woocommerce-terms-and-conditions-wrapper')));
    if (terms && terms.parentElement !== termsHost) termsHost.appendChild(terms);
    // Fail closed: never retain a discarded Terms checkbox from an old gateway.
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
    // Keep the native Woo required checkbox, terms toggle and configured privacy URL.
    // Consolidate only the visible legal sentence; never synthesize consent or links.
    const nativeTerms = termsHost.querySelector('.woocommerce-terms-and-conditions-wrapper');
    if (nativeTerms) {
      const termsLink = nativeTerms.querySelector('a.woocommerce-terms-and-conditions-link');
      const legalText = termsLink && termsLink.closest('.woocommerce-terms-and-conditions-checkbox-text');
      const privacyBlock = nativeTerms.querySelector('.woocommerce-privacy-policy-text');
      const privacyLink = privacyBlock && privacyBlock.querySelector('a[href]');
      if (legalText && termsLink && privacyLink && !legalText.classList.contains('byl-legal-inline')) {
        termsLink.textContent = 'Terms and Conditions';
        privacyLink.textContent = 'Privacy Policy';
        legalText.replaceChildren(
          document.createTextNode('I have read and agree to the '),
          termsLink,
          document.createTextNode(' and have read the '),
          privacyLink,
          document.createTextNode('.')
        );
        legalText.classList.add('byl-legal-inline');
        privacyBlock.classList.add('byl-privacy-merged');
      }
      if (legalText && termsLink && privacyLink) {
        termsLink.removeAttribute('target');
        privacyLink.removeAttribute('target');
        termsLink.dataset.bylPolicyToggle = 'terms';
        privacyLink.dataset.bylPolicyToggle = 'privacy';
      }
      // The expanded legal text belongs AFTER the required and optional checkboxes.
      // Moving the existing node preserves Woo's native expand/collapse behavior.
      const expandedTerms = nativeTerms.querySelector('.woocommerce-terms-and-conditions');
      if (expandedTerms && expandedTerms.parentElement === nativeTerms && expandedTerms !== nativeTerms.lastElementChild) {
        nativeTerms.appendChild(expandedTerms);
      }
      if (expandedTerms && !expandedTerms.querySelector('[data-byl-policy-terms]')) {
        const termsBody = document.createElement('div');
        termsBody.dataset.bylPolicyTerms = '';
        while (expandedTerms.firstChild) termsBody.appendChild(expandedTerms.firstChild);
        const privacyBody = document.createElement('div');
        privacyBody.dataset.bylPolicyPrivacy = '';
        privacyBody.hidden = true;
        expandedTerms.append(termsBody, privacyBody);
        expandedTerms.dataset.bylPolicyShowing = 'terms';
        expandedTerms.id = expandedTerms.id || 'byl-checkout-legal-panel';
      }
      if (expandedTerms && termsLink && privacyLink) {
        [termsLink, privacyLink].forEach(link => {
          link.setAttribute('aria-controls', expandedTerms.id);
          link.setAttribute('aria-expanded', 'false');
        });
      }
    }
    wireLegalAccordion();
    const reviewInvite = termsHost.querySelector('.cr-customer-consent .woocommerce-terms-and-conditions-checkbox-text');
    if (reviewInvite) reviewInvite.textContent = 'Send me an optional review invitation with gift card rewards.';
    const notes = document.querySelector('.woocommerce-additional-fields');
    if (notes && notes.parentElement !== notesHost) notesHost.appendChild(notes);
    wireOptionalDeliveryNote();
  }
  function deliveryOptions() {
    deliveryHost.replaceChildren();
    const groups = wooShippingGroups();
    if (!groups.size) {
      const p = document.createElement('p');
      p.className = 'byl-step-hint';
      p.textContent = 'Enter your delivery address to see available shipping methods.';
      deliveryHost.appendChild(p);
      return;
    }
    Array.from(groups.entries()).forEach(([packageKey, options], groupIndex) => {
      const pack = document.createElement('div');
      pack.className = 'byl-delivery-package';
      if (groups.size > 1) {
        const title = document.createElement('strong');
        title.className = 'byl-delivery-package-title';
        title.textContent = 'Package ' + (groupIndex + 1);
        pack.appendChild(title);
      }
      options.forEach(input => {
        const label = input.id && document.querySelector('label[for="' + CSS.escape(input.id) + '"]');
         const fullLabel = label ? label.textContent.replace(/\s+/g, ' ').trim() : input.value;
         // The native Woo label and price remain the single pricing source.
         // Never synthesize rates, delivery windows or guaranteed transit times.
         const priceNode = label && label.querySelector('.woocommerce-Price-amount');
         const realPrice = priceNode ? priceNode.textContent.replace(/\s+/g, ' ').trim() : '';
         const title = realPrice ? fullLabel.replace(realPrice, '').replace(/:\s*$/, '').trim() : fullLabel;
         const visiblePrice = realPrice; // Do not double-label Woo's native 'Free shipping'.
        const autoSelected = input.type === 'hidden' && options.length === 1 && !!String(input.value || '').trim();
        const selected = input.checked || autoSelected;
        const button = document.createElement('button');
        button.type = 'button';
        button.disabled = input.type === 'hidden'; // Woo already chose its only possible rate.
        button.className = 'byl-delivery-rate' + (selected ? ' is-selected' : '');
        button.setAttribute('aria-pressed', selected ? 'true' : 'false');
        const radio = document.createElement('span');
        radio.className = 'byl-delivery-radio';
        radio.setAttribute('aria-hidden', 'true');
        const description = document.createElement('span');
        description.className = 'byl-delivery-label';
        const strong = document.createElement('strong');
        strong.textContent = title;
        description.appendChild(strong);
        button.append(radio, description);
         if (visiblePrice) {
           const cost = document.createElement('span');
           cost.className = 'byl-delivery-price';
           cost.textContent = visiblePrice;
           button.appendChild(cost);
         }
        if (input.type !== 'hidden') button.addEventListener('click', () => {
          input.checked = true;
          input.dispatchEvent(new Event('change', {bubbles: true}));
          deliveryOptions();
          updateDetails();
        });
        pack.appendChild(button);
      });
      // A Woo-configured amount-gated Free shipping method can exist in the
      // active zone while being unavailable below its real minimum. The source
      // row comes from the Woo-refreshable order-review fragment, not a
      // hardcoded price list. It must never be clickable or set an input.
      const match = /^shipping_method\[(\d+)\]$/.exec(packageKey);
      const lockedSource = match && Array.from(
        root.querySelectorAll('#order_review .byl-locked-free-rate-source')
      ).find(node => node.dataset.bylPackageIndex === match[1]);
      const threshold = lockedSource && lockedSource.dataset.bylFreeThreshold;
      const freeExists = options.some(input => String(input.value || '').startsWith('free_shipping:'));
      if (threshold && !freeExists) {
        const locked = document.createElement('div');
        locked.className = 'byl-delivery-rate is-locked';
        locked.setAttribute('role', 'note');
        locked.setAttribute('aria-disabled', 'true');
        const ring = document.createElement('span');
        ring.className = 'byl-delivery-radio';
        ring.setAttribute('aria-hidden', 'true');
        const label = document.createElement('span');
        label.className = 'byl-delivery-label';
        const title = document.createElement('strong');
        title.textContent = 'Free shipping';
        const explanation = document.createElement('small');
        explanation.className = 'byl-delivery-eligibility';
        explanation.textContent = 'Available from ' + threshold + ' eligible subtotal';
        label.append(title, explanation);
        locked.append(ring, label);
        pack.appendChild(locked);
      }
      deliveryHost.appendChild(pack);
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
    const nativeTotal = root.querySelector('.byl-summary-total');
    if (nativeTotal && mobileSummaryTotal) mobileSummaryTotal.textContent = nativeTotal.textContent;
    const shippingSummary = root.querySelector('[data-byl-step-summary="shipping"]');
    const paymentSummary = root.querySelector('[data-byl-step-summary="payment"]');
    if (shippingSummary && name && address) shippingSummary.textContent = name + ' · ' + address;
    if (paymentSummary) paymentSummary.textContent = paymentLabel();
  }
  function validateAddress() {
    let firstInvalid = null;
    root.querySelectorAll('.byl-checkout-billing .validate-required, [data-byl-account-email-host] .validate-required').forEach(wrapper => {
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
  function shippingReady() {
    return root.dataset.needsShipping !== '1' || (wooShippingGroups().size > 0 && chosenShippingRates().length === wooShippingGroups().size);
  }
  function updateAction() {
    // Guard accidental navigation during a native Woo payment/3DS attempt.
    root.dataset.bylSubmitting = submitting ? '1' : '0';
    const labels = { shipping:'Continue to Payment', payment:root.dataset.needsPayment === '1' ? 'Pay Now' : 'Place Order' };
    const processingPayment = submitting && active === 'payment';
    next.innerHTML = '';
    next.appendChild(document.createTextNode((processingPayment ? 'Processing payment' : labels[active]) + ' '));
    const arrow = document.createElement('span');
    arrow.textContent = '→';
    arrow.setAttribute('aria-hidden','true');
    next.appendChild(arrow);
    // A 3DS challenge can take longer than six seconds. Keep the visible
    // submission lock until Woo emits an explicit checkout_error or navigates.
    // This is a UI guard, NOT a provider-side idempotency guarantee.
    if (processingPayment) {
      next.disabled = true;
      next.setAttribute('aria-busy', 'true');
    } else {
      next.removeAttribute('aria-busy');
      if (active === 'payment' && !paymentConfigured()) {
        next.disabled = true;
        message('No payment method is available. Please try again later.');
      } else if (active === 'payment' && !shippingReady()) {
        next.disabled = true;
        message('Please return to Shipping and choose an available delivery method.');
      } else {
        next.disabled = false;
      }
    }
  }
  function setStep(step, {scroll = true} = {}) {
    // Never re-enable the checkout CTA by navigating to another step
    // while native WooPayments / 3DS authentication is in progress.
    if (submitting) return;
    const index = steps.indexOf(step);
    if (index < 0 || index > furthest) return;
    active = step;
    root.dataset.bylCurrentStep = step;
    const pageHeading = root.querySelector('[data-byl-checkout-heading]');
    if (pageHeading) pageHeading.textContent = step === 'shipping' ? 'Shipping' : 'Payment';
    root.querySelectorAll('[data-byl-track]').forEach(item => {
      if (item.dataset.bylTrack === step) item.setAttribute('aria-current', 'step');
      else item.removeAttribute('aria-current');
    });
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
    mountWooAccountEmail();
    relabelPhone();
    syncPhoneDescription();
    wireOptionalAddressFields();
    alignShippingAddressFields();
    deliveryOptions();
    updateDetails();
    updateAction();
    const heading = root.querySelector('[data-byl-step="' + step + '"]');
    if (heading && scroll) heading.scrollIntoView({block:'start',behavior:'auto'});
  }
  next.addEventListener('click', () => {
    if (submitting) return;
    if (active === 'shipping') {
      if (!validateAddress()) return;
      if (!shippingReady()) {
        message('Please select an available delivery method for your address.');
        return;
      }
      furthest = 1;
      setStep('payment');
      $('body').trigger('update_checkout'); // Woo recalculates totals and gateway eligibility.
      return;
    }
    if (active !== 'payment') return;
    if (!paymentConfigured()) {
      message('Select an available payment method to continue.');
      return;
    }
    if (!validateAddress() || !shippingReady()) {
      message('Please review the required shipping information.');
      furthest = 1;
      setStep('shipping');
      return;
    }
    const nativeButton = document.getElementById('place_order');
    if (!nativeButton) {
      message('WooCommerce payment confirmation is unavailable.');
      return;
    }
    submitting = true;
    updateAction();
    nativeButton.click(); // Native Woo validation, nonce and gateway callback remain authoritative.
  });
  root.addEventListener('click', e => {
    const edit = e.target.closest('[data-byl-open-step]');
    if (edit) { e.preventDefault(); setStep(edit.dataset.bylOpenStep); }
  });
  form.addEventListener('submit', e => {
    if (active !== 'payment' || !paymentConfigured() || !validateAddress() || !shippingReady()) {
      e.preventDefault();
      e.stopImmediatePropagation();
      message('Complete all checkout steps before placing your order.');
      return;
    }
    // Enter-key / native form submissions must display the same pending
    // state as the BYL CTA. Do not intercept Woo's authoritative submit flow.
    if (!submitting) {
      submitting = true;
      updateAction();
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
    mountWooAccountEmail();
    relabelPhone();
    syncPhoneDescription();
    wireOptionalAddressFields();
    alignShippingAddressFields();
    deliveryOptions();
    updateDetails();
    updateAction();
    fetchPromotion();
  });
  $(document.body).on('checkout_error', () => {
    // Release ONLY after Woo reports failure; no timer-driven blind resubmit.
    submitting = false;
    updateAction();
  });
  if (summaryToggle) summaryToggle.addEventListener('click', () => {
    const open = !summary.classList.contains('is-open');
    summary.classList.toggle('is-open', open);
    summaryToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    if (mobileSummaryToggle) mobileSummaryToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
  });
  if (mobileSummaryToggle) {
    mobileSummaryToggle.addEventListener('click', () => {
      if (summaryToggle) summaryToggle.click();
    });
  }
  summary.classList.toggle('is-open', !window.matchMedia('(max-width: 760px)').matches);
  summaryToggle.setAttribute('aria-expanded', summary.classList.contains('is-open') ? 'true' : 'false');
  root.classList.add('is-enhanced');
  mountNative();
  fetchPromotion();
  // Initial render starts at the page header; user-driven step changes may scroll.
  setStep('shipping', {scroll:false});
})(jQuery);
