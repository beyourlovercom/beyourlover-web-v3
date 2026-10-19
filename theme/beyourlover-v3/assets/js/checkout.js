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
    const sameBilling = root.querySelector('#byl_use_shipping_for_billing');
    const invoiceBilling = ['first_name','last_name','address_1','city','state','postcode','country']
      .map(key => field('byl_invoice_' + key)).filter(Boolean).join(', ');
    review('billing', sameBilling && !sameBilling.checked
      ? (invoiceBilling || 'Enter your separate billing address')
      : 'Same as shipping address');
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
    // Separate billing is a second real address, not just a visual section.
    // Block progression before Woo/native payment submission when incomplete.
    const invoiceBox = root.querySelector('[data-byl-invoice-address]');
    if (invoiceBox && !invoiceBox.hidden) {
      const billingFieldset = invoiceBox.querySelector('fieldset');
      const invalid = Array.from(invoiceBox.querySelectorAll('.validate-required')).find(wrapper => {
        if (wrapper.hidden || getComputedStyle(wrapper).display === 'none') return false;
        const input = wrapper.querySelector('input:not([type="hidden"]), select, textarea');
        return !!(input && !input.disabled &&
          (!String(input.value || '').trim() || (input.validity && !input.validity.valid)));
      });
      if (!billingFieldset || billingFieldset.disabled || invalid) {
        message('Please complete the separate billing address.');
        const first = invalid && invalid.querySelector('input:not([type="hidden"]), select, textarea');
        if (first) first.focus({preventScroll:false});
        return false;
      }
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
    syncMexicoRFCField();
    syncBillingAddressChoice();
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

    // Only the Dev PILLOW adapter emits this Woo review-order source marker.
    // The legacy BYL49 extension can reflect the WHOLE cart subtotal, even
    // though Woo now charges the pillow shipment independently. Do not promise
    // cart-wide free shipping while a paid oversized package is present.
    const hasOversizedPillow = !!root.querySelector(
      '#order_review .byl-dev-pillow-package-source'
    );
    const label = document.createElement('strong');
    label.className = 'byl-promo-title';
    label.textContent = hasOversizedPillow ? 'Shipping & promo code' : 'Free shipping';
    host.appendChild(label);

    const messageNode = document.createElement('p');
    messageNode.className = 'byl-promo-message';
    messageNode.setAttribute('aria-live', 'polite');
    messageNode.textContent = hasOversizedPillow
      ? 'Oversized pillow shipping is $30 per item. Free shipping applies only to eligible regular items.'
      : promotionMessage(data);
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

  // This is one genuine Woo field, registered and server-validated in PHP.
  // Its identifier is never sent to browser storage or analytics.
  function syncMexicoRFCField() {
    const taxField = root.querySelector('#billing_rfc_tax_id_field');
    const country = root.querySelector('#billing_country');
    if (!taxField || !country) return;
    const enabled = country.value === 'MX';
    taxField.classList.toggle('is-country-active', enabled);
    taxField.hidden = !enabled;
    taxField.setAttribute('aria-hidden', enabled ? 'false' : 'true');
    const input = taxField.querySelector('#billing_rfc_tax_id');
    if (input) {
      input.disabled = !enabled;
      input.required = enabled;
      if (!enabled && input.value) input.value = '';
      input.setAttribute('aria-required', enabled ? 'true' : 'false');
    }
    const label = taxField.querySelector('label');
    if (label) {
      let marker = label.querySelector('[data-byl-country-required]');
      if (enabled && !marker) {
        marker = document.createElement('abbr');
        marker.className = 'required';
        marker.setAttribute('title', 'required');
        marker.dataset.bylCountryRequired = '1';
        marker.textContent = ' *';
        label.appendChild(marker);
      } else if (!enabled && marker) {
        marker.remove();
      }
      const optional = label.querySelector('.optional');
      if (optional) optional.hidden = enabled;
    }
  }

  // Use the *same* Woo country locale as native Woo billing fields: e.g.
  // Germany's region optional; UAE postcode optional/hidden. Server validation
  // reads WC()->countries->get_address_fields(), not client-side claims.
  function syncInvoiceLocale() {
    const countryEl = root.querySelector('#byl_invoice_country');
    if (!countryEl) return;
    let locale;
    try {
      const raw = window.wc_address_i18n_params && window.wc_address_i18n_params.locale;
      locale = raw && JSON.parse(String(raw).replace(/&quot;/g, '"'));
    } catch (_e) { return; }
    if (!locale || !locale.default) return;

    // Woo's core country-select.js only rewrites #billing_state and
    // #shipping_state. Our separately named invoice state is intentionally
    // outside that selector, so build its country list from Woo's ORIGINAL
    // localized countries map, not a hardcoded country/region directory.
    const stateRow = root.querySelector('#byl_invoice_state_field');
    let stateInput = root.querySelector('#byl_invoice_state');
    try {
      const rawStates = window.wc_country_select_params && window.wc_country_select_params.countries;
      const countries = rawStates && JSON.parse(String(rawStates).replace(/&quot;/g, '"'));
      const states = countries && countries[countryEl.value];
      if (stateRow && stateInput && states && typeof states === 'object'
          && Object.keys(states).length) {
        const previous = stateInput.value;
        if (stateInput.tagName !== 'SELECT') {
          const select = document.createElement('select');
          select.id = 'byl_invoice_state';
          select.name = 'byl_invoice_state';
          select.className = 'state_select input-text';
          select.setAttribute('autocomplete', 'address-level1');
          stateInput.replaceWith(select);
          stateInput = select;
          delete stateRow.dataset.bylInvoiceStatesCountry;
        }
        if (stateRow.dataset.bylInvoiceStatesCountry !== countryEl.value) {
          const empty = new Option('Choose state / region', '');
          const options = [empty];
          Object.keys(states).forEach(code => {
            options.push(new Option(states[code], code));
          });
          stateInput.replaceChildren(...options);
          stateInput.value = Object.prototype.hasOwnProperty.call(states, previous)
            ? previous : '';
          stateRow.dataset.bylInvoiceStatesCountry = countryEl.value;
        }
      } else {
        if (stateRow && stateInput && stateInput.tagName === 'SELECT') {
          const field = document.createElement('input');
          field.type = 'text';
          field.id = 'byl_invoice_state';
          field.name = 'byl_invoice_state';
          field.className = 'input-text';
          field.setAttribute('autocomplete', 'address-level1');
          stateInput.replaceWith(field);
          delete stateRow.dataset.bylInvoiceStatesCountry;
        }
      }
    } catch (_error) {
      // Never replace real Woo data with invented state values when its
      // localization is unavailable. Server validation remains authoritative.
    }
    const chosen = locale[countryEl.value] || {};
    for (const key of ['state','postcode']) {
      const row = root.querySelector('#byl_invoice_' + key + '_field');
      const input = root.querySelector('#byl_invoice_' + key);
      if (!row || !input) continue;
      const defaults = locale.default[key] || {};
      const countrySpec = chosen[key] || {};
      const spec = Object.assign({}, defaults, countrySpec);
      const hidden = spec.hidden === true;
      const required = !hidden && spec.required !== false;
      row.hidden = hidden;
      input.disabled = hidden || !!row.closest('fieldset[disabled]');
      input.required = required;
      input.setAttribute('aria-required', required ? 'true' : 'false');
      row.classList.toggle('validate-required', required);
      const label = row.querySelector('label');
      if (!label) continue;
      if (required) {
        label.querySelectorAll('.optional').forEach(node => node.remove());
        if (!label.querySelector('.required')) {
          const star = document.createElement('span');
          star.className = 'required';
          star.dataset.bylInvoiceRequired = '1';
          star.setAttribute('aria-hidden','true');
          star.textContent = ' *';
          label.appendChild(star);
        }
      } else {
        label.querySelectorAll('.required').forEach(node => node.remove());
        if (!label.querySelector('.optional')) {
          const optional = document.createElement('span');
          optional.className = 'optional';
          optional.textContent = ' (optional)';
          label.appendChild(optional);
        }
      }
    }
  }

  // Optional account saving never writes any address from the browser.
  // Woo Checkout's server-side customer-update gate owns persistence.
  function syncBillingAddressChoice() {
    const useShipping = root.querySelector('#byl_use_shipping_for_billing');
    const section = root.querySelector('[data-byl-invoice-address]');
    const fields = section && section.querySelector('[data-byl-invoice-fields]');
    if (!useShipping || !section || !fields) return;
    const separate = !useShipping.checked;
    section.hidden = !separate;
    fields.disabled = !separate;
    useShipping.setAttribute('aria-expanded', separate ? 'true' : 'false');
    if (separate && section.dataset.bylInvoiceInitialized !== '1') {
      section.dataset.bylInvoiceInitialized = '1';
      const country = section.querySelector('#byl_invoice_country');
      // Re-use Woo's native country→state list, never a cloned selector.
      if (country && window.jQuery) window.jQuery(country).trigger('change');
    }
    if (separate) syncInvoiceLocale();
    updateDetails();
  }
  $(document).on('change', '#byl_invoice_country', syncInvoiceLocale);
  const useShippingForBilling = root.querySelector('#byl_use_shipping_for_billing');
  if (useShippingForBilling) {
    useShippingForBilling.addEventListener('change', syncBillingAddressChoice);
    syncBillingAddressChoice();
  }
  $(document).on('change', '#billing_country', syncMexicoRFCField);
  form.addEventListener('input', updateDetails);
  form.addEventListener('change', updateDetails);
  $(document.body).on('updated_checkout', () => {
    // The initial Woo review AJAX is no longer needed to fix the first-paint
    // field order; allow the normal post-AJAX hook to keep owning later updates.
    stopInitialOrderObserver();
    mountNative();
    mountWooAccountEmail();
    relabelPhone();
    syncPhoneDescription();
    wireOptionalAddressFields();
    alignShippingAddressFields();
    syncMexicoRFCField();
    syncBillingAddressChoice();
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
  // Native Woo Country/State initialization can reparent direct form rows
  // *after* our first synchronous Shipping render and *before* the first
  // update_order_review response. The previous code waited for that AJAX
  // callback (occasionally 600ms+) to repair the DOM/tab order.
  //
  // A short-lived direct-child observer re-aligns after Woo's initial DOM
  // mutations, without waiting on network and without polling/rebuilding any
  // SelectWoo input. It is disconnected on first Woo update OR window load.
  let initialOrderObserver = null;
  function stopInitialOrderObserver() {
    if (!initialOrderObserver) return;
    initialOrderObserver.disconnect();
    initialOrderObserver = null;
  }
  function watchInitialFieldOrder() {
    const host = document.getElementById('billing_first_name_field')?.parentElement;
    if (!host || typeof MutationObserver === 'undefined') return;
    initialOrderObserver = new MutationObserver(() => {
      // alignShippingAddressFields() is idempotent and only writes on mismatch.
      // A second observer callback from its own appendChild operations is a
      // no-op. No listener is left active during the user's form interaction.
      alignShippingAddressFields();
    });
    initialOrderObserver.observe(host, {childList:true});
    window.addEventListener('load', () => {
      alignShippingAddressFields();
      stopInitialOrderObserver();
    }, {once:true});
  }
  root.classList.add('is-enhanced');
  mountNative();
  syncMexicoRFCField();
  fetchPromotion();
  // Initial render starts at the page header; user-driven step changes may scroll.
  setStep('shipping', {scroll:false});
  watchInitialFieldOrder();
})(jQuery);
