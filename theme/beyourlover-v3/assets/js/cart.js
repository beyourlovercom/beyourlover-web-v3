(function () {
    'use strict';

    var config = window.BYLShippingPromotionCart || {};
    var root = null;
    var editing = false;
    var busy = false;

    function store() {
        var blocks = window.wc && window.wc.wcBlocksData;
        return blocks && blocks.cartStore;
    }

    function state() {
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

    function escapeHtml(value) {
        return String(value || '').replace(/[&<>'"]/g, function (character) {
            return {'&':'&amp;', '<':'&lt;', '>':'&gt;', "'":'&#39;', '"':'&quot;'}[character];
        });
    }

    function messageFor(data) {
        if (data.state === 'below_threshold') return 'Add ' + currency(data.remaining) + ' more for free shipping';
        if (data.state === 'automatic_unlocked' || data.state === 'require_code_unlocked') return 'Free shipping unlocked';
        if (data.state === 'require_code_available') return 'Free shipping available';
        if (data.state === 'require_code_other') return 'Replace your current code with ' + escapeHtml(data.policy_code) + ' to unlock free shipping.';
        return '';
    }

    function render(data) {
        if (!root || !data || !data.active) return;
        var applied = data.has_code && data.applied_code;
        var html = '<p class="byl-cart-promotion__heading">Free shipping</p>';
        html += '<p class="byl-cart-promotion__message" aria-live="polite">' + messageFor(data) + '</p>';
        if (data.state === 'require_code_available') html += '<p class="byl-cart-promotion__hint">Use code ' + escapeHtml(data.policy_code) + '</p>';
        if (data.state === 'below_threshold') {
            var progress = data.threshold > 0 ? Math.min(100, Math.max(0, (data.subtotal / data.threshold) * 100)) : 0;
            html += '<div class="byl-cart-promotion__meter" role="progressbar" aria-label="Progress toward free shipping" aria-valuemin="0" aria-valuemax="100" aria-valuenow="' + progress.toFixed(0) + '"><span style="width:' + progress.toFixed(2) + '%"></span></div>';
        }
        if (applied && !editing) {
            html += '<p class="byl-cart-promotion__applied"><span>' + escapeHtml(data.applied_code) + ' applied</span> <button type="button" class="byl-cart-promotion__edit">Edit</button></p>';
        } else {
            html += '<form class="byl-cart-promotion__form"><label class="screen-reader-text" for="byl-promotion-code">Promo code</label><input id="byl-promotion-code" name="code" type="text" autocomplete="off" placeholder="Promo code" required><button type="submit"' + (busy ? ' disabled' : '') + '>Apply</button></form>';
            if (applied) html += '<button type="button" class="byl-cart-promotion__remove">Remove</button>';
        }
        root.innerHTML = html + '<p class="byl-cart-promotion__error" role="alert" hidden></p>';
        bind(data);
    }

    function showError(message) {
        var error = root && root.querySelector('.byl-cart-promotion__error');
        if (error) { error.textContent = message; error.hidden = !message; }
    }

    function bind(data) {
        var edit = root.querySelector('.byl-cart-promotion__edit');
        if (edit) edit.addEventListener('click', function () { editing = true; render(data); });
        var remove = root.querySelector('.byl-cart-promotion__remove');
        if (remove) remove.addEventListener('click', function () { mutate(config.removeUrl, {code: data.applied_code}, 'remove'); });
        var form = root.querySelector('.byl-cart-promotion__form');
        if (form) form.addEventListener('submit', function (event) {
            event.preventDefault();
            var input = form.querySelector('input');
            mutate(config.applyUrl, {code: input ? input.value.trim() : ''}, 'apply');
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
                editing = false;
                var descriptor = store();
                var dispatcher = window.wp && wp.data && descriptor ? wp.data.dispatch(descriptor) : null;
                if (dispatcher && typeof dispatcher.receiveCart === 'function') dispatcher.receiveCart(result.json);
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
        if (window.wp && wp.data && typeof wp.data.subscribe === 'function') wp.data.subscribe(function () { var data = state(); if (data) { mount(); render(data); } });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start); else start();
}());
