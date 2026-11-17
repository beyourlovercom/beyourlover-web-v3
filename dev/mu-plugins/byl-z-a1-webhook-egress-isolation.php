<?php
/**
 * Plugin Name: BYL A1 Dev External WooCommerce Webhook Egress Isolation
 * Description: Fail-closed, Dev-only guard for outbound WooCommerce webhook deliveries.
 * Version: 1.0.0
 * Author: BYL
 *
 * This file is intentionally separate from the existing provider-specific G5
 * guard. It prevents any external WooCommerce webhook destination from being
 * delivered by the Dev site while preserving webhook records and queued actions.
 * It does not block inbound provider callbacks or ordinary WooPayments TEST API
 * calls. The network-level rule is restricted to the observed merchant-notification
 * webhook endpoint.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (
    !function_exists('wp_get_environment_type')
    || wp_get_environment_type() !== 'staging'
    || !function_exists('home_url')
    || untrailingslashit(home_url()) !== 'https://dev.beyourlover.com'
) {
    return;
}

/**
 * Block outbound WooCommerce webhook deliveries to external hosts on Dev.
 *
 * This is deliberately registered in a filename sorted after byl-dev-safety.php,
 * so it is the final decision after the existing provider/test-order filter.
 */
add_filter('woocommerce_webhook_should_deliver', static function ($allow, $webhook, $arg): bool {
    if (!$allow) {
        return false;
    }

    if (!is_object($webhook) || !method_exists($webhook, 'get_delivery_url')) {
        return false;
    }

    $url = (string) $webhook->get_delivery_url();
    $host = strtolower(rtrim((string) wp_parse_url($url, PHP_URL_HOST), '.'));

    // Only same-site callbacks are permitted. All external providers are blocked.
    return $host === 'dev.beyourlover.com';
}, PHP_INT_MAX, 3);

/**
 * Defense in depth for the one already-pending WooPayments merchant notification.
 * This blocks only the webhook endpoint, not WooPayments TEST payment API traffic.
 */
add_filter('pre_http_request', static function ($pre, $args, $url) {
    if ($pre !== false || !is_string($url)) {
        return $pre;
    }

    $host = strtolower(rtrim((string) wp_parse_url($url, PHP_URL_HOST), '.'));
    $path = (string) wp_parse_url($url, PHP_URL_PATH);

    if (
        $host === 'pay.woo.com'
        && $path === '/wp-json/platform-checkout/v1/merchant-notification'
    ) {
        return new WP_Error(
            'byl_dev_woocommerce_webhook_egress_closed',
            'WooPayments merchant-notification webhook delivery is disabled on Dev.'
        );
    }

    return $pre;
}, PHP_INT_MAX, 3);
