/* Cart presentation-only quantity steppers; Woo cart form/nonce remains authoritative. */
(function () {
  'use strict';
  function decorate() {
    document.querySelectorAll('.byl-cart-runtime .woocommerce-cart-form .quantity input.qty').forEach(function (input) {
      if (input.closest('.byl-cart-qty-stepper')) return;
      var container = document.createElement('span');
      container.className = 'byl-cart-qty-stepper';
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
      input.parentNode.insertBefore(container, input);
      container.appendChild(minus);
      container.appendChild(input);
      container.appendChild(plus);
      var pending;
      function updateNativeCart() {
        var form = input.closest('form.woocommerce-cart-form');
        var submit = form && form.querySelector('button[name="update_cart"]');
        if (!form || !submit) return;
        submit.disabled = false;
        window.clearTimeout(pending);
        pending = window.setTimeout(function () {
          if (!document.body.contains(input)) return;
          if (typeof form.requestSubmit === 'function') form.requestSubmit(submit);
          else submit.click();
        }, 650);
      }
      function adjust(direction) {
        var step = Number(input.step) > 0 ? Number(input.step) : 1;
        var minimum = Number(input.min) > 0 ? Number(input.min) : 1;
        var maximum = input.max !== '' && Number(input.max) > 0 ? Number(input.max) : Infinity;
        var next = Math.min(maximum, Math.max(minimum, (Number(input.value) || minimum) + direction * step));
        if (next === Number(input.value)) return;
        input.value = String(next);
        input.dispatchEvent(new Event('change', {bubbles: true}));
        updateNativeCart();
      }
      minus.addEventListener('click', function () { adjust(-1); });
      plus.addEventListener('click', function () { adjust(1); });
      input.addEventListener('change', updateNativeCart);
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', decorate);
  else decorate();
}());
