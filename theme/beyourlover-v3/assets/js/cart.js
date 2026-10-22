(function () {
    'use strict';

    var config = window.BYLShippingPromotionCart || {};
    var root = null;
    var busy = false;

    function store() {
        var blocks = window.wc && window.wc.wcBlocksData;
        return blocks && blocks.cartStore;
    }

    // Classic Cart uses Woo shipping packages and the customer's session.
    // Blocks Store API cart state is independent and may still have an old zone.
    function nativeCart() {
        return document.querySelector('.byl-cart-runtime .cart_totals, .cart_totals');
    }

    function state() {
        var classic = nativeCart();
        if (classic) {
            var embedded = classic.querySelector('[data-byl-native-promotion]');
            if (!embedded) return null; // Keep native Woo coupon; never infer from stale Blocks.
            try { return JSON.parse(embedded.getAttribute('data-byl-native-promotion')); }
            catch (error) { return null; }
        }
        var descriptor = store();
        if (!window.wp || !wp.data || !descriptor) return null;
        var selected = wp.data.select(descriptor);
        if (!selected) return null;
        if (typeof selected.getCartData === 'function') {
            var cart = selected.getCartData();
            return cart && cart.extensions && cart.extensions['byl-shipping-promotion'];
        }
        return null;
    }

    function currency(value) {
        try {
            var format = window.wc && window.wc.wcSettings && window.wc.wcSettings.getSetting('currency', {});
            var symbol = format && format.symbol ? format.symbol : '$';
            return symbol + Number(value || 0).toFixed(2);
        } catch (error) { return '$' + Number(value || 0).toFixed(2); }
    }

    // Whole-dollar thresholds should read "$69", not "$69.00".
    // Keep fractional thresholds accurate if a future zone needs cents.
    function thresholdCurrency(value) {
        var numeric = Number(value || 0);
        if (!Number.isFinite(numeric)) return currency(0);
        return Number.isInteger(numeric) ? currency(numeric).replace(/\.00$/, '') : currency(numeric);
    }

    function escapeHtml(value) {
        return String(value || '').replace(/[&<>'"]/g, function (character) {
            return {'&':'&amp;', '<':'&lt;', '>':'&gt;', "'":'&#39;', '"':'&quot;'}[character];
        });
    }

    function messageFor(data) {
        if (data.state === 'below_threshold') return 'Add ' + currency(data.remaining) + ' more for free shipping';
        if (data.state === 'automatic_unlocked' || data.state === 'require_code_unlocked') return "🎉 Congrats! You've unlocked free shipping.";
        if (data.state === 'free_shipping_available') return "🎉 Congrats! You've unlocked free shipping.";
        if (data.state === 'shipping_unavailable') return 'Free shipping is not available for this shipping address.';
        if (data.state === 'require_code_available') return 'Free shipping available';
        if (data.state === 'require_code_other') return 'Replace your current code with ' + escapeHtml(data.policy_code) + ' to unlock free shipping.';
        return '';
    }

    function render(data) {
        if (!root) return;
        // The free-shipping headline is a static pointer to the full
        // destination policy, never a promise about this cart's actual rate.
        var shippingHeadline = '<p class="byl-cart-promotion__message">' +
            '<a href="/free-shipping/">Free shipping from $69 (special items excluded)</a></p>';
        if (!data || !data.active) {
            root.innerHTML = shippingHeadline;
            document.body.classList.remove('byl-cart-coupon-enhanced');
            return; // Leave native Woo coupons visible when promo API is off.
        }
        var applied = data.has_code && data.applied_code;
        var html = shippingHeadline;
        // One predictable Coupon Code entry point, regardless of free-shipping or applied-code state.
        html += '<form class="byl-cart-promotion__form"><label for="byl-promotion-code">Promo / Coupon Code</label><div class="byl-cart-promotion__inputs"><input id="byl-promotion-code" name="code" type="text" autocomplete="off" placeholder="Enter coupon code" required><button type="submit"' + (busy ? ' disabled' : '') + '>Apply</button></div></form>';
        if (applied) html += '<p class="byl-cart-promotion__applied">' + escapeHtml(data.applied_code) + ' applied <button type="button" class="byl-cart-promotion__remove">Remove</button></p>';
        root.innerHTML = html + '<p class="byl-cart-promotion__error" role="alert" hidden></p>';
        document.body.classList.add('byl-cart-coupon-enhanced');
        bind(data);
    }

    function showError(message) {
        var error = root && root.querySelector('.byl-cart-promotion__error');
        if (error) { error.textContent = message; error.hidden = !message; }
    }

    function bind(data) {
        var remove = root.querySelector('.byl-cart-promotion__remove');
        if (remove) remove.addEventListener('click', function () { mutate(config.removeUrl, {code: data.applied_code}, 'remove'); });
        var form = root.querySelector('.byl-cart-promotion__form');
        if (form) form.addEventListener('submit', function (event) {
            event.preventDefault();
            var input = form.querySelector('input');
            var proposed = input ? input.value.trim() : '';
            if (data.has_code && data.applied_code) {
                showError('Remove the current coupon before applying a different code.');
                return;
            }
            if (proposed) mutate(config.applyUrl, {code: proposed}, 'apply');
        });
    }

    function mutate(url, body, action) {
        if (!url || busy) return;
        busy = true;
        showError('');
        var headers = {'Content-Type': 'application/json'};
        if (config.nonce) headers.Nonce = config.nonce;
        if (config.cartToken) headers['Cart-Token'] = config.cartToken;
        fetch(url, {method: 'POST', credentials: 'same-origin', headers: headers, body: JSON.stringify(body)})
            .then(function (response) {
                var nonce = response.headers.get('Nonce');
                if (nonce) config.nonce = nonce;
                var cartToken = response.headers.get('Cart-Token');
                if (cartToken) config.cartToken = cartToken;
                return response.json().then(function (json) { return {ok: response.ok, json: json}; });
            })
            .then(function (result) {
                busy = false;
                if (!result.ok) throw new Error((result.json && (result.json.message || result.json.code)) || 'Unable to update the promo code.');
                var descriptor = store();
                var dispatcher = window.wp && wp.data && descriptor ? wp.data.dispatch(descriptor) : null;
                // Classic Woo totals and its embedded shipping context must
                // be refreshed by Woo, not a separate Blocks cart snapshot.
                if (nativeCart()) window.location.reload();
                else if (dispatcher && typeof dispatcher.receiveCart === 'function') dispatcher.receiveCart(result.json);
                else window.location.reload();
                return action;
            })
            .catch(function (error) { busy = false; showError(error.message || 'Unable to update the promo code.'); });
    }

    function mount() {
        var target = document.querySelector('.wc-block-cart__totals, .wc-block-cart .wc-block-components-totals-wrapper, .cart_totals');
        if (!target) return false;
        root = target.querySelector('.byl-cart-promotion');
        if (!root) {
            root = document.createElement('section');
            root.className = 'byl-cart-promotion';
            root.setAttribute('aria-label', 'Free shipping promotion');
            var checkout = target.querySelector('.wc-proceed-to-checkout');
            if (checkout) target.insertBefore(root, checkout);
            else target.appendChild(root);
            render(state());
        }
        return true;
    }

    function start() {
        var observer = new MutationObserver(function () { if (!root || !document.body.contains(root)) mount(); });
        observer.observe(document.body, {childList: true, subtree: true});
        mount();
        if (window.wp && wp.data && typeof wp.data.subscribe === 'function') wp.data.subscribe(function () {
            if (nativeCart()) return; // Classic Woo HTML owns state.
            var data = state(); if (data) { mount(); render(data); }
        });
        // Woo updates classic cart totals after a shipping-address change.
        if (window.jQuery && typeof window.jQuery === 'function') {
            window.jQuery(document.body).on('updated_cart_totals updated_wc_div updated_shipping_method', function () {
                mount(); render(state());
            });
        }
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start); else start();
}());
