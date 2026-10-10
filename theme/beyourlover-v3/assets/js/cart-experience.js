/* Cart controls enhance native WooCommerce forms, validation and nonce.
 * No custom cart mutations: native Woo is the transaction authority. */
(function () {
  'use strict';
  var scope = document.querySelector('.byl-cart-runtime');
  if (!scope) return;

  function updateNativeCart(input) {
    var form = input.closest('form.woocommerce-cart-form');
    var submit = form && form.querySelector('button[name="update_cart"]');
    if (!form || !submit || !document.body.contains(form)) return;
    submit.disabled = false;
    // Coalesce fast +/- taps and manual edits to one native Woo update.
    window.clearTimeout(form.bylCartUpdateTimer);
    form.bylCartUpdateTimer = window.setTimeout(function () {
      if (!document.body.contains(form) || !document.body.contains(input)) return;
      if (typeof form.requestSubmit === 'function') form.requestSubmit(submit);
      else submit.click();
    }, 650);
  }

  function decorate() {
    scope.querySelectorAll('.woocommerce-cart-form .quantity input.qty').forEach(function (input) {
      if (input.closest('.byl-cart-qty-stepper')) return;
      var parent = input.parentNode;
      if (!parent) return;
      var wrapper = document.createElement('span');
      wrapper.className = 'byl-cart-qty-stepper';
      var minus = document.createElement('button');
      var plus = document.createElement('button');
      minus.type = 'button';
      plus.type = 'button';
      minus.className = 'byl-cart-qty-minus';
      plus.className = 'byl-cart-qty-plus';
      minus.textContent = '−';
      plus.textContent = '+';
      minus.setAttribute('aria-label', 'Decrease quantity');
      plus.setAttribute('aria-label', 'Increase quantity');
      parent.insertBefore(wrapper, input);
      wrapper.appendChild(minus);
      wrapper.appendChild(input);
      wrapper.appendChild(plus);
      function adjust(direction) {
        var step = Number(input.step) > 0 ? Number(input.step) : 1;
        var minimum = input.min !== '' && Number.isFinite(Number(input.min))
          ? Number(input.min) : 1;
        // Quantity zero is only allowed when Woo explicitly permits it; the
        // dedicated Remove action remains available independently.
        var maximum = input.max !== '' && Number.isFinite(Number(input.max))
          ? Number(input.max) : Infinity;
        var current = Number(input.value);
        if (!Number.isFinite(current)) current = Math.max(minimum, 1);
        var next = Math.min(maximum, Math.max(minimum, current + direction * step));
        if (!Number.isFinite(next) || next === current) return;
        input.value = String(Number(next.toFixed(6)));
        input.dispatchEvent(new Event('change', {bubbles:true}));
      }
      minus.addEventListener('click', function () { adjust(-1); });
      plus.addEventListener('click', function () { adjust(1); });
      input.addEventListener('change', function () { updateNativeCart(input); });
    });
  }

  function syncCartBadge() {
    var form = scope.querySelector('.woocommerce-cart-form');
    var badgeLink = document.querySelector('.byl-focus-cart-icon');
    if (!badgeLink) return;
    var count = 0;
    if (form) {
      form.querySelectorAll('tr.cart_item input.qty').forEach(function (input) {
        var qty = Number(input.value);
        if (Number.isFinite(qty) && qty > 0) count += qty;
      });
    }
    count = Math.max(0, Math.round(count));
    badgeLink.setAttribute('aria-label', 'Cart, ' + count + ' items');
    var badge = badgeLink.querySelector('.byl-focus-cart-badge');
    if (!count) { if (badge) badge.remove(); return; }
    if (!badge) {
      badge = document.createElement('span');
      badge.className = 'byl-focus-cart-badge';
      badgeLink.appendChild(badge);
    }
    badge.textContent = String(count);
  }

  function onUpdated() { decorate(); syncCartBadge(); }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', decorate);
  else decorate();

  // Classic WooCommerce can replace the form fragment after quantity, coupon,
  // or remove changes. Reinstall controls on the *new* inputs; never submit
  // from an orphaned input or attach handlers twice.
  if (window.jQuery && typeof window.jQuery === 'function') {
    window.jQuery(document.body).on('updated_wc_div updated_cart_totals', onUpdated);
  }
  var observer = new MutationObserver(function (changes) {
    if (changes.some(function (change) {
      return Array.prototype.some.call(change.addedNodes || [], function (node) {
        return node.nodeType === 1 && (node.matches &&
          (node.matches('.woocommerce-cart-form,.woocommerce,.quantity') ||
           node.querySelector && node.querySelector('.woocommerce-cart-form .quantity input.qty')));
      });
    })) { onUpdated(); }
  });
  observer.observe(scope, {childList:true,subtree:true});
}());
