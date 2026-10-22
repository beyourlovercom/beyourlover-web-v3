(function () {
    'use strict';
    var root = document.querySelector('[data-byl-fs-lookup]');
    if (!root) return;
    var country = root.querySelector('[data-byl-fs-country]');
    var stateWrap = root.querySelector('[data-byl-fs-state-wrapper]');
    var stateSelect = root.querySelector('[data-byl-fs-state]');
    var output = root.querySelector('[data-byl-fs-result]');
    if (!country || !stateWrap || !stateSelect || !output) return;
    var latest = 0;
    function amount(value) {
        var number = Number(value || 0);
        var cents = !Number.isInteger(number);
        var symbol = root.getAttribute('data-currency') || '$';
        return symbol + number.toFixed(cents ? 2 : 0);
    }
    function write(message, success) {
        output.textContent = message;
        output.classList.toggle('byl-free__lookup-result--success', !!success);
    }
    function lookup() {
        var chosenCountry = country.value || '';
        var chosenState = stateSelect.value || '';
        var needsState = chosenCountry === 'US';
        stateWrap.hidden = !needsState;
        if (!needsState) stateSelect.value = '';
        latest += 1;
        var request = latest;
        if (!chosenCountry) return write('Choose a destination to see its current free-shipping rule.', false);
        if (needsState && !chosenState) return write('Select your U.S. state or territory for the correct shipping tier.', false);
        write('Checking the latest shipping rules…', false);
        var params = new URLSearchParams({
            action: 'byl_v3_fs_lookup',
            nonce: root.getAttribute('data-nonce') || '',
            country: chosenCountry,
            state: needsState ? chosenState : ''
        });
        fetch(root.getAttribute('data-endpoint') || '', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'},
            body: params.toString()
        }).then(function (response) {
            if (!response.ok) throw new Error('The lookup is unavailable right now.');
            return response.json();
        }).then(function (payload) {
            if (request !== latest) return;
            if (!payload || !payload.success || !payload.data) throw new Error('Unable to verify this destination.');
            var data = payload.data;
            if (data.status === 'needs_state') return write('Please select a U.S. state.', false);
            if (data.status === 'no_free_method') return write('Free shipping is not currently offered for this destination. See Cart for delivery options.', false);
            if (data.status === 'conditional_free') return write('Free shipping requires additional coupon or destination conditions here. Check the eligible methods in your Cart.', false);
            if (data.status === 'not_served') return write('This destination is not currently available for shipping. See Checkout for options.', false);
            if (data.status !== 'free_method_configured') return write('Shipping rates for this destination could not be confirmed.', false);
            var message = Number(data.threshold) === 0
                ? 'Free shipping may be available with no order minimum.'
                : 'Free shipping on eligible orders of ' + amount(data.threshold) + ' or more.';
            if (data.basis === 'after_coupon' && Number(data.threshold) > 0) {
                message += ' The minimum applies after eligible coupon discounts; special items do not qualify.';
            } else if (data.basis === 'before_coupon' && Number(data.threshold) > 0) {
                message += ' This zone currently checks the minimum before coupon discounts.';
            }
            if (data.base_flat_cost !== null && Number(data.base_flat_cost) > 0) {
                message += ' Standard shipping base rate: ' + amount(data.base_flat_cost) + ' before shipping-class charges.';
            }
            message += ' Confirm the available delivery method in Cart or Checkout.';
            write(message, true);
        }).catch(function () {
            if (request === latest) write('We could not load this destination. Please check the delivery options in Cart.', false);
        });
    }
    country.addEventListener('change', lookup);
    stateSelect.addEventListener('change', lookup);
})();
