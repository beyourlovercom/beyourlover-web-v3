<?php
/** Checkout field contract. Applies to classic Woo checkout and wc-ajax processing alike. */
defined('ABSPATH') || exit;
add_filter('woocommerce_checkout_fields', static function (array $fields): array {
    unset($fields['billing']['billing_phone']);
    unset($fields['shipping']['shipping_phone']);
    if (isset($fields['billing']['billing_email'])) {
        $fields['billing']['billing_email']['priority'] = 1;
        $fields['billing']['billing_email']['class'] = ['form-row-wide'];
    }
    $fields['billing']['shipping_phone'] = [
        'type' => 'tel',
        'label' => 'Shipping phone',
        'required' => true,
        'priority' => 115,
        'class' => ['form-row-wide'],
        'autocomplete' => 'tel',
        'validate' => ['phone'],
    ];
    return $fields;
}, 20);
