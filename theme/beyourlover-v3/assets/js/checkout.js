(function ($) {
    'use strict';

    var config = window.BYLShippingPromotionCheckout || {};
    var state = { editing: false, busy: false, promotion: null };

    function esc(value) { return String(value || '').replace(/[&<>'"]/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]; }); }
    function checkoutForm() { return document.querySelector('form.checkout'); }
    function section(title, className) {
        var node = document.createElement('section');
        node.className = 'byl-checkout-section ' + className;
        node.innerHTML = '<div class="byl-checkout-section__heading"><h2>' + title + '</h2><button type="button" class="byl-checkout-edit" hidden>Edit</button></div><div class="byl-checkout-section__body"></div><p class="byl-checkout-section__summary" hidden></p>';
        return node;
    }
    function firstMissing(root) {
        var fields = root.querySelectorAll('.validate-required input, .validate-required select, .validate-required textarea, input[aria-required="true"], select[aria-required="true"], textarea[aria-required="true"]');
        for (var i = 0; i < fields.length; i += 1) if (!fields[i].disabled && !String(fields[i].value || '').trim()) return fields[i];
        return null;
    }
    function summary(fields) {
        var values = [];
        fields.querySelectorAll('input, select').forEach(function (field) { if (field.value && field.name !== 'billing_email') values.push(field.value); });
        return values.slice(0, 4).join(', ');
    }
    function setCollapsed(sectionNode, value, text, editable) {
        sectionNode.classList.toggle('is-collapsed', value);
        sectionNode.querySelector('.byl-checkout-section__body').hidden = value;
        sectionNode.querySelector('.byl-checkout-section__summary').hidden = !value;
        sectionNode.querySelector('.byl-checkout-section__summary').textContent = text || '';
        sectionNode.querySelector('.byl-checkout-edit').hidden = !(value && editable);
    }
    function proxyShipping(delivery) {
        var real = document.querySelectorAll('#shipping_method input[type="radio"]');
        var list = delivery.querySelector('.byl-delivery-methods');
        if (!list) return;
        list.innerHTML = '';
        real.forEach(function (radio, index) {
            var label = document.querySelector('label[for="' + radio.id + '"]');
            var item = document.createElement('label');
            item.className = 'byl-delivery-option';
            item.innerHTML = '<input type="radio" name="byl_delivery_proxy" value="' + esc(radio.value) + '" ' + (radio.checked ? 'checked' : '') + '><span>' + esc(label ? label.textContent : radio.value) + '</span>';
            item.querySelector('input').addEventListener('change', function () {
                // Use one native activation path so WooCommerce recalculates once.
                radio.checked = true;
                radio.dispatchEvent(new Event('change', {bubbles: true}));
            });
            list.appendChild(item);
        });
        if (!real.length) list.innerHTML = '<p class="byl-checkout-muted">Shipping methods will appear after your address is entered.</p>';
    }
    function syncShippingRows() {
        document.querySelectorAll('#order_review .woocommerce-shipping-totals.shipping').forEach(function (row) {
            row.classList.add('byl-shipping-row-hidden');
        });
    }
    function syncPhoneDescription() {
        var phoneDescription = document.querySelector('#billing_phone-description');
        if (phoneDescription) phoneDescription.setAttribute('aria-hidden', 'false');
    }
    function promoMessage(data) {
        if (!data) return '';
        if (data.state === 'below_threshold') return 'Add ' + Number(data.remaining || 0).toFixed(2) + ' more for free shipping';
        if (data.state === 'automatic_unlocked' || data.state === 'require_code_unlocked') return 'Free shipping unlocked';
        if (data.state === 'require_code_available') return 'Free shipping available';
        if (data.state === 'require_code_other') return 'Replace your current code with ' + esc(data.policy_code) + ' to unlock free shipping.';
        return '';
    }
    function renderPromo() {
        var root = document.querySelector('.byl-checkout-promotion');
        var data = state.promotion;
        if (!root || !data || !data.active) { if (root) root.hidden = true; return; }
        root.hidden = false;
        var applied = data.has_code && data.applied_code;
        var html = '<h3>Free shipping</h3><p aria-live="polite">' + promoMessage(data) + '</p>';
        if (data.state === 'require_code_available') html += '<p class="byl-checkout-promotion__hint">Use code ' + esc(data.policy_code) + '</p>';
        if (applied && !state.editing) html += '<p class="byl-checkout-promotion__applied"><strong>' + esc(data.applied_code) + ' applied</strong> <button type="button" class="byl-promo-edit">Edit</button></p>';
        else html += '<div class="byl-promo-form"><label class="screen-reader-text" for="byl-checkout-promo-code">Promo code</label><input id="byl-checkout-promo-code" name="code" type="text" autocomplete="off" placeholder="Promo code" required><button type="button">Apply</button></div>' + (applied ? '<button type="button" class="byl-promo-remove">Remove</button>' : '');
        root.innerHTML = html + '<p class="byl-checkout-promotion__error" role="alert" hidden></p>';
        var edit = root.querySelector('.byl-promo-edit');
        if (edit) edit.addEventListener('click', function () { state.editing = true; renderPromo(); });
        var remove = root.querySelector('.byl-promo-remove');
        if (remove) remove.addEventListener('click', function () { mutate(config.removeUrl, {code: data.applied_code}); });
        var promoForm = root.querySelector('.byl-promo-form');
        if (promoForm) promoForm.querySelector('button').addEventListener('click', function () { mutate(config.applyUrl, {code: promoForm.querySelector('input').value.trim()}); });
    }
    function mutate(url, body) {
        if (!url || state.busy) return;
        state.busy = true;
        var headers = {'Content-Type': 'application/json'}; if (config.nonce) headers.Nonce = config.nonce;
        fetch(url, {method: 'POST', credentials: 'same-origin', headers: headers, body: JSON.stringify(body)}).then(function (response) {
            return response.json().then(function (json) { if (!response.ok) throw new Error(json.message || 'Unable to update the promo code.'); return json; });
        }).then(function () { state.editing = false; return fetchState(); }).catch(function (error) { var e = document.querySelector('.byl-checkout-promotion__error'); if (e) { e.textContent = error.message; e.hidden = false; } }).finally(function () { state.busy = false; });
    }
    function fetchState() {
        if (!config.cartUrl) return Promise.resolve();
        var headers = {}; if (config.nonce) headers.Nonce = config.nonce;
        return fetch(config.cartUrl, {credentials: 'same-origin', headers: headers}).then(function (response) { return response.json(); }).then(function (cart) { state.promotion = cart && cart.extensions && cart.extensions['byl-shipping-promotion']; renderPromo(); });
    }
    function resyncPayment(pay) {
        var replacement = document.querySelector('#payment');
        if (!replacement) return;
        if (!pay.querySelector('#payment')) pay.querySelector('.byl-checkout-section__body').appendChild(replacement);
        var place = replacement.querySelector('.place-order');
        if (place && !replacement.querySelector('.byl-review-label')) {
            var label = document.createElement('p'); label.className = 'byl-review-label'; label.textContent = 'Review';
            place.insertAdjacentElement('beforebegin', label);
        }
    }
    function enhance() {
        var form = checkoutForm(), customer = document.querySelector('#customer_details'), review = document.querySelector('#order_review'), payment = document.querySelector('#payment');
        if (!form || !customer || !review || form.classList.contains('byl-checkout-enhanced')) return;
        syncPhoneDescription();
        form.classList.add('byl-checkout-enhanced');
        var layout = document.createElement('div'); layout.className = 'byl-checkout-layout';
        var main = document.createElement('main'); main.className = 'byl-checkout-main';
        var aside = document.createElement('aside'); aside.className = 'byl-checkout-summary';
        var address = section('Shipping Address', 'byl-address-section');
        var delivery = section('Delivery Method', 'byl-delivery-section');
        var pay = section('Payment', 'byl-payment-section');
        var addressBody = address.querySelector('.byl-checkout-section__body');
        var billingFields = customer.querySelector('.woocommerce-billing-fields');
        var accountFields = customer.querySelector('.woocommerce-account-fields');
        var additionalFields = customer.querySelector('.woocommerce-additional-fields');
        if (billingFields) addressBody.appendChild(billingFields);
        if (accountFields && (!billingFields || !billingFields.contains(accountFields))) addressBody.appendChild(accountFields);
        if (additionalFields && (!billingFields || !billingFields.contains(additionalFields))) addressBody.appendChild(additionalFields);
        customer.hidden = true;
        customer.setAttribute('aria-hidden', 'true');
        var continueAddress = document.createElement('button'); continueAddress.type = 'button'; continueAddress.className = 'button byl-checkout-continue'; continueAddress.textContent = 'Continue to delivery'; address.querySelector('.byl-checkout-section__body').appendChild(continueAddress);
        delivery.querySelector('.byl-checkout-section__body').innerHTML = '<div class="byl-delivery-methods"></div><button type="button" class="button byl-checkout-continue">Continue to payment</button>';
        pay.querySelector('.byl-checkout-section__body').appendChild(payment || document.createElement('div'));
        var reviewLabel = document.createElement('p'); reviewLabel.className = 'byl-review-label'; reviewLabel.textContent = 'Review';
        if (payment) { var place = payment.querySelector('.place-order'); if (place) place.insertAdjacentElement('beforebegin', reviewLabel); }
        main.append(address, delivery, pay);
        var promo = document.createElement('section'); promo.className = 'byl-checkout-promotion'; promo.setAttribute('aria-label', 'Free shipping promotion');
        aside.append(document.querySelector('#order_review_heading') || document.createElement('h2'), review, promo);
        layout.append(main, aside); form.appendChild(layout);
        setCollapsed(delivery, true, 'Complete Shipping Address first', false);
        setCollapsed(pay, true, 'Select a delivery method', false);
        document.querySelectorAll('.woocommerce-shipping-fields, .ship-to-different-address').forEach(function (node) { node.hidden = true; });
        syncShippingRows();
        proxyShipping(delivery); renderPromo(); fetchState();
        continueAddress.addEventListener('click', function () { var missing = firstMissing(address); if (missing) { missing.focus(); return; } setCollapsed(address, true, summary(address), true); setCollapsed(delivery, false); });
        delivery.querySelector('.byl-checkout-continue').addEventListener('click', function () { var selected = delivery.querySelector('input:checked'); if (selected) { setCollapsed(delivery, true, selected.nextElementSibling.textContent, true); setCollapsed(pay, false); } });
        [address, delivery, pay].forEach(function (node) { node.querySelector('.byl-checkout-edit').addEventListener('click', function () { setCollapsed(node, false); }); });
        $(document.body).on('updated_checkout.bylS6', function () { syncShippingRows(); syncPhoneDescription(); resyncPayment(pay); proxyShipping(delivery); renderPromo(); fetchState(); });
    }
    $(function () { enhance(); });
}(jQuery));
