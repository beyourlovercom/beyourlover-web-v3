/**
 * Checkout email offers — explicit, OPTIONAL consent via Andy Core Newsletter.
 * NOT part of Woo order submission. No newsletter request until an eligible
 * shopper checks the optional checkbox and provides a valid email.
 * Andy Core owns double opt-in, throttling, pending state and mail readiness.
 */
(function () {
  'use strict';

  function boot() {
    const checkout = document.querySelector('[data-byl-progressive-checkout]');
    const box = checkout && checkout.querySelector('[data-byl-checkout-newsletter]');
    if (!box) return;

    const consent = box.querySelector('[data-byl-newsletter-consent]');
    const status = box.querySelector('[data-byl-newsletter-status]');
    const email = checkout.querySelector('#billing_email');
    if (!consent || !status || !email) return;

    const ready = box.dataset.bylNewsletterReady === '1';
    const policy = box.dataset.bylNewsletterPolicy || '';
    let url;
    try {
      url = new URL(box.dataset.bylNewsletterEndpoint || '', window.location.origin);
    } catch (_error) {
      url = null;
    }
    // Must be the exact same-origin, first-party Andy Core REST endpoint.
    const permitted = !!(url && url.origin === window.location.origin
      && url.pathname.endsWith('/andy-core/v1/newsletter/subscribe')
      && /^[a-zA-Z0-9_.-]{1,100}$/.test(policy));
    if (!ready || !permitted) {
      consent.checked = false;
      consent.disabled = true;
      status.textContent = 'Email offers are not available yet.';
      return;
    }

    consent.disabled = false;
    let inFlight = false;
    let lastRequestedEmail = '';
    let aborter = null;

    function announce(text) {
      status.textContent = text;
    }
    function normalizedEmail() {
      const value = String(email.value || '').trim();
      return email.validity.valid && value.length > 0 && value.length <= 190
        ? value : '';
    }

    async function requestPendingConfirmation() {
      // Checking a checkbox does not itself confer subscribed status.
      // Unchecked or incomplete checkout remains entirely unaffected.
      if (!consent.checked || inFlight) return;
      const address = normalizedEmail();
      if (!address) {
        announce('Enter a valid email address above to request email offers.');
        return;
      }
      if (lastRequestedEmail === address) {
        announce('Check your inbox to confirm your email offers request.');
        return;
      }

      inFlight = true;
      consent.disabled = true;
      announce('Requesting confirmation email…');
      aborter = new AbortController();
      try {
        const response = await fetch(url.toString(), {
          method: 'POST',
          mode: 'same-origin',
          credentials: 'omit',
          cache: 'no-store',
          referrerPolicy: 'no-referrer',
          signal: aborter.signal,
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({
            email: address,
            marketing_consent: true,
            consent_policy: policy,
            source: 'byl_checkout_optional',
            source_page: window.location.pathname.slice(0, 255),
            locale: (document.documentElement.lang || 'en').slice(0, 20),
            website: ''
          })
        });
        const result = await response.json().catch(() => ({}));
        if (!response.ok || result.success !== true) {
          throw new Error('SUBSCRIBE_NOT_READY');
        }
        lastRequestedEmail = address;
        // Email may change while an async confirmation request is in flight.
        // Never imply the *new* email was requested, or reuse earlier consent
        // to request confirmation for it.
        if (normalizedEmail() !== address) {
          consent.checked = false;
          announce('Email changed. Check the box again to request offers for the new address.');
        } else {
          announce('Check your inbox to confirm email offers. You are not subscribed yet.');
        }
      } catch (_error) {
        if (consent.checked) {
          // No false positives and never hold up Woo Checkout.
          consent.checked = false;
          announce('Email offers are unavailable. You can still complete checkout.');
        }
      } finally {
        inFlight = false;
        aborter = null;
        consent.disabled = false;
      }
    }

    consent.addEventListener('change', () => {
      if (!consent.checked) {
        if (aborter) aborter.abort();
        announce(lastRequestedEmail
          ? 'If you received a confirmation email, ignore it to remain unsubscribed.'
          : '');
        return;
      }
      requestPendingConfirmation();
    });
    email.addEventListener('blur', () => {
      // Supports the reference flow: tick first, then enter contact email.
      if (consent.checked && !inFlight) requestPendingConfirmation();
    });
    email.addEventListener('input', () => {
      // First opt-in may precede typing an email; preserve that intent until
      // initial blur. After a pending request for one specific email, a changed
      // address needs a new explicit checkbox action, not an automatic POST.
      if (consent.checked && lastRequestedEmail
          && String(email.value || '').trim() !== lastRequestedEmail) {
        consent.checked = false;
        announce('Email changed. Check the box again to request offers for the new address.');
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, {once:true});
  } else {
    boot();
  }
})();
