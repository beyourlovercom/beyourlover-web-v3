<?php
/**
 * Mexico RFC customs identifier — native WooCommerce Checkout contract.
 *
 * Scope: Mexico billing/shipping destination only. This theme currently uses
 * billing-address fields as the sole delivery address (Woo ship_to=billing).
 * Never collect the RFC for other countries. Never send it to newsletters,
 * analytics, third parties or an unauthenticated AJAX endpoint.
 *
 * This integration is source-only until specifically approved for Dev Canary.
 */
defined('ABSPATH') || exit;

/** Canonicalize a user-supplied RFC without leaking it to frontend logs. */
function byl_v3_checkout_normalize_mx_rfc($value): string {
    if (!is_scalar($value)) {
        return '';
    }
    return strtoupper(trim(sanitize_text_field((string) $value)));
}

/**
 * Mexican RFC is normally 12 characters for entities or 13 for individuals:
 * 3/4 initial letters, 6-digit date, 3-character homoclave.
 * Accept Ñ (&-variants) as valid tax authority characters.
 */
function byl_v3_checkout_valid_mx_rfc(string $rfc): bool {
    return 1 === preg_match('/^(?:[A-Z&Ñ]{3}|[A-Z&Ñ]{4})[0-9]{6}[A-Z0-9]{3}$/uD', $rfc);
}

add_filter('woocommerce_checkout_fields', static function (array $fields): array {
    $fields['billing']['billing_rfc_tax_id'] = [
        'type' => 'text',
        'label' => 'RFC Tax ID',
        // Country-specific required validation is below; Woo cannot safely
        // mark this globally required for non-Mexico checkout.
        'required' => false,
        'priority' => 95, // After Postal (85), before Organization (110).
        'class' => ['form-row-wide', 'byl-country-rfc-field'],
        'autocomplete' => 'off',
        'custom_attributes' => ['maxlength' => '13', 'autocapitalize' => 'characters'],
        'description' => 'Required for customs clearance in Mexico.',
    ];
    return $fields;
}, 10020);

/** Enforce the real requirement on Woo's server-side checkout validation. */
add_action('woocommerce_after_checkout_validation', static function (array $data, $errors): void {
    $country = strtoupper((string) ($data['billing_country'] ?? ''));
    if ($country !== 'MX') {
        return;
    }
    $value = byl_v3_checkout_normalize_mx_rfc($data['billing_rfc_tax_id'] ?? '');
    if (!byl_v3_checkout_valid_mx_rfc($value)) {
        $errors->add(
            'billing_rfc_tax_id_invalid',
            __('Enter a valid Mexico RFC Tax ID (12 or 13 characters).', 'beyourlover-v3')
        );
    }
}, 10, 2);

/**
 * The order is not created unless Woo validation already passes. HPOS-safe:
 * save only as private order meta via CRUD; do not write user meta here.
 */
add_action('woocommerce_checkout_create_order', static function ($order, array $data): void {
    if (!$order instanceof WC_Order) {
        return;
    }
    if (strtoupper((string) ($data['billing_country'] ?? '')) !== 'MX') {
        return;
    }
    $rfc = byl_v3_checkout_normalize_mx_rfc($data['billing_rfc_tax_id'] ?? '');
    if (byl_v3_checkout_valid_mx_rfc($rfc)) {
        $order->update_meta_data('_billing_rfc_tax_id', $rfc);
    }
}, 20, 2);
