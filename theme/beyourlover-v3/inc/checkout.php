<?php
/**
 * S6 classic Checkout presentation filters. WooCommerce remains the owner of
 * checkout data, validation, rates, gateways, taxes, and order creation.
 */
declare(strict_types=1);

if (!defined('ABSPATH')) { exit; }

function byl_v3_checkout_context(): bool {
    return function_exists('is_checkout') && is_checkout() && (!function_exists('is_order_received_page') || !is_order_received_page());
}

add_filter('woocommerce_ship_to_different_address_checked', static function ($checked): bool {
    return byl_v3_checkout_context() ? false : (bool) $checked;
}, 999);

add_filter('woocommerce_checkout_fields', static function (array $fields): array {
    if (!byl_v3_checkout_context() || empty($fields['billing']['billing_phone'])) { return $fields; }
    // Woo native skipped-shipping-fieldset copy maps billing_phone -> shipping_phone; no custom order/post-data writes.
    $fields['billing']['billing_phone']['required'] = true;
    $fields['billing']['billing_phone']['label'] = 'Phone number required for delivery';
    $fields['billing']['billing_phone']['description'] = 'Your number is used only to help the carrier deliver your order. We do not use it for marketing.';
    $fields['billing']['billing_phone']['autocomplete'] = 'tel';
    $fields['billing']['billing_phone']['priority'] = 95;
    return $fields;
}, 999);
