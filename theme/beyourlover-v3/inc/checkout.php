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
        'description' => 'Your number is used only to help the carrier deliver your order. We do not use it for marketing.',
        'required' => true,
        'priority' => 115,
        'class' => ['form-row-wide'],
        'autocomplete' => 'tel',
        'validate' => ['phone'],
    ];

    return $fields;
}, 9999);

add_action('wp', static function (): void {
    if (function_exists('is_checkout') && is_checkout() && !(function_exists('is_order_received_page') && is_order_received_page())) {
        remove_action('woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10);
    }
});

add_filter('woocommerce_form_field_args', static function (array $args, string $key): array {
    if ($key === 'shipping_phone') {
        $args['label'] = 'Shipping phone';
        $args['description'] = 'Your number is used only to help the carrier deliver your order. We do not use it for marketing.';
    }
    return $args;
}, 99999, 2);
