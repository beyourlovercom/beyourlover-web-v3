<?php
/**
 * Checkout Shipping address + optional independent Billing address.
 *
 * Existing Woo billing_* input fields are the actual DELIVERY address in the
 * canonical native Checkout. Woo's shipping zones and shipping tax basis
 * must continue using these untouched real inputs until order creation.
 *
 * This module translates real verified Checkout data into native WC_Order
 * shipping/billing properties and, ONLY when a logged-in shopper opts in,
 * the WC_Customer saved account addresses. No guest account creation, no
 * AJAX endpoint, and no cross-website persistence.
 */
defined('ABSPATH') || exit;

function byl_v3_address_from_checkout(array $data, string $prefix): array {
    $keys = ['first_name', 'last_name', 'company', 'country', 'address_1',
        'address_2', 'city', 'state', 'postcode'];
    $out = [];
    foreach ($keys as $key) {
        $value = $data[$prefix . $key] ?? '';
        $out[$key] = is_scalar($value)
            ? sanitize_text_field((string) $value)
            : '';
    }
    return $out;
}

/**
 * Once the shopper has explicitly saved shipping and billing independently,
 * the next Checkout must prefill the Shipping screen from the *shipping*
 * account address, not Woo's usual billing account address. Do not affect
 * POST repopulation, guest checkout or tax/RFC/contact values.
 */
add_filter('woocommerce_checkout_get_value', static function ($value, $key) {
    if ($value !== null || !is_user_logged_in() || !is_string($key)) return $value;
    if (!preg_match('/^billing_(first_name|last_name|company|country|address_1|address_2|city|state|postcode)$/', $key, $m)) {
        return $value;
    }
    // One persisted customer read per request, not a separate DB lookup
    // for each of the nine shipping fields on mobile Checkout.
    static $customer = null;
    // Account address prefill must use the persisted customer store; Woo's
    // second constructor argument TRUE selects the transient SESSION store.
    if ($customer === null) $customer = new WC_Customer(get_current_user_id());
    $getter = 'get_shipping_' . $m[1];
    if (!is_callable([$customer, $getter])) return $value;
    $saved = (string) $customer->{$getter}();
    return $saved !== '' ? $saved : $value;
}, 30, 2);

/** Distinct invoice fields are never trusted merely because JS showed them. */
function byl_v3_checkout_separate_billing(array $data): bool {
    return isset($data['byl_use_shipping_for_billing'])
        && (int) $data['byl_use_shipping_for_billing'] === 0;
}

/** Woo does not automatically collect non-Woo custom form control names. */
add_filter('woocommerce_checkout_posted_data', static function (array $data): array {
    // Woo checkout validates the original form nonce; values are used only
    // within that same Woo checkout request. No independent write endpoint.
    // phpcs:disable WordPress.Security.NonceVerification.Missing
    $same = isset($_POST['byl_use_shipping_for_billing'])
        ? wc_clean(wp_unslash($_POST['byl_use_shipping_for_billing'])) : '1';
    $data['byl_use_shipping_for_billing'] = $same === '0' ? 0 : 1;
    $save = isset($_POST['byl_save_address'])
        ? wc_clean(wp_unslash($_POST['byl_save_address'])) : '';
    $data['byl_save_address'] = is_user_logged_in() && $save === '1' ? 1 : 0;
    foreach (['first_name','last_name','company','country','address_1',
        'address_2','city','state','postcode'] as $key) {
        $raw = $_POST['byl_invoice_' . $key] ?? '';
        $data['byl_invoice_' . $key] = is_scalar($raw)
            ? sanitize_text_field(wp_unslash((string) $raw))
            : '';
    }
    // phpcs:enable WordPress.Security.NonceVerification.Missing
    return $data;
}, 20);

/**
 * Genuine separate billing must have a full address. Woo native shipping
 * remains authoritative for shipping zones, payment eligibility and taxes.
 */
add_action('woocommerce_after_checkout_validation', static function (array $data, $errors): void {
    if (!byl_v3_checkout_separate_billing($data)) return;
    $invoice = byl_v3_address_from_checkout($data, 'byl_invoice_');

    $countries = WC()->countries;
    $country = $invoice['country'];
    $allowed = $countries->get_allowed_countries();
    if ($country === '' || !isset($allowed[$country])) {
        $errors->add('byl_invoice_country_invalid',
            __('Select a valid billing country.', 'beyourlover-v3'));
        return;
    }
    // Woo core country locale is the ONLY authority for required/optional
    // address fields. Germany's state can be optional; UAE has no postcode.
    $native = $countries->get_address_fields($country, 'billing_');
    foreach (['first_name'=>'First name', 'last_name'=>'Last name',
        'address_1'=>'Street address', 'city'=>'City',
        'state'=>'State / Region', 'postcode'=>'Postal code'] as $key => $label) {
        $required = !empty($native['billing_' . $key]['required']);
        if ($required && $invoice[$key] === '') {
            $errors->add('byl_invoice_' . $key . '_required',
                sprintf(__('Billing %s is required.', 'beyourlover-v3'), $label));
        }
    }
    $states = $countries->get_states($country);
    if ($invoice['state'] !== '' && is_array($states) && $states
        && !isset($states[$invoice['state']])) {
        $errors->add('byl_invoice_state_invalid',
            __('Select a valid billing state / region.', 'beyourlover-v3'));
    }
    if ($invoice['postcode'] !== ''
        && !WC_Validation::is_postcode($invoice['postcode'], $country)) {
        $errors->add('byl_invoice_postcode_invalid',
            __('Enter a valid billing postal code.', 'beyourlover-v3'));
    }
}, 20, 2);

/** Map one array of address fields into the real WC_Order / WC_Customer. */
function byl_v3_apply_wc_address($object, string $kind, array $address): void {
    foreach ($address as $key => $value) {
        $setter = 'set_' . $kind . '_' . $key;
        if (is_callable([$object, $setter])) $object->{$setter}($value);
    }
}

/**
 * Woo initializes order.billing from the theme's shipping-first billing_*
 * inputs. Preserve them as actual order.shipping, then set order.billing
 * to either the same data or the independently supplied invoice address.
 * WC_Order CRUD is compatible with HPOS.
 */
add_action('woocommerce_checkout_create_order', static function ($order, array $data): void {
    if (!$order instanceof WC_Order) return;
    $delivery = byl_v3_address_from_checkout($data, 'billing_');
    byl_v3_apply_wc_address($order, 'shipping', $delivery);
    if (!empty($data['shipping_phone']) && is_callable([$order, 'set_shipping_phone'])) {
        $order->set_shipping_phone(sanitize_text_field((string) $data['shipping_phone']));
    }
    if (byl_v3_checkout_separate_billing($data)) {
        byl_v3_apply_wc_address($order, 'billing',
            byl_v3_address_from_checkout($data, 'byl_invoice_'));
    }
}, 35, 2);

/**
 * Default Woo automatically rewrites logged-in customer billing address at
 * Checkout even when shopper didn't ask to save. Gate ALL Checkout customer
 * updates behind the real optional checkbox.
 */
add_filter('woocommerce_checkout_update_customer_data', static function ($allowed): bool {
    // phpcs:disable WordPress.Security.NonceVerification.Missing
    $consent = isset($_POST['byl_save_address']) && is_scalar($_POST['byl_save_address'])
        ? wc_clean(wp_unslash((string) $_POST['byl_save_address'])) : '';
    // phpcs:enable WordPress.Security.NonceVerification.Missing
    return (bool) $allowed && is_user_logged_in() && $consent === '1';
}, 20);

/**
 * Called only when logged in AND save address explicitly checked above.
 * Woo will save WC_Customer AFTER this hook. The stored shipping address
 * is the delivery address; the saved billing address is the invoice address.
 */
add_action('woocommerce_checkout_update_customer', static function ($customer, array $data): void {
    if (!$customer instanceof WC_Customer || !is_user_logged_in()
        || empty($data['byl_save_address'])) return;

    byl_v3_apply_wc_address($customer, 'shipping',
        byl_v3_address_from_checkout($data, 'billing_'));
    if (!empty($data['shipping_phone']) && is_callable([$customer, 'set_shipping_phone'])) {
        $customer->set_shipping_phone(sanitize_text_field((string) $data['shipping_phone']));
    }
    if (byl_v3_checkout_separate_billing($data)) {
        byl_v3_apply_wc_address($customer, 'billing',
            byl_v3_address_from_checkout($data, 'byl_invoice_'));
    }
}, 35, 2);
