<?php
/**
 * Plugin Name: BYL Dev Safety
 * Description: Fail-closed staging environment; tightly scoped P1.3 sandbox checkout UAT.
 *
 * Source: dev/mu-plugins/byl-dev-safety.php in beyourlover-web-v3.
 * Deploy to dev.beyourlover.com/wp-content/mu-plugins ONLY, not www.
 */
defined('ABSPATH') || exit;
if (wp_get_environment_type() !== 'staging') { return; }

/**
 * Permission for one synthetic test customer + one sandbox gateway only.
 * The gate config is outside the webroot and is absent/expired by default.
 * No credentials, identity tokens or account IDs are part of this source.
 */
function byl_dev_p13_checkout_gate(): array {
    static $result = null;
    if ($result !== null) { return $result; }
    $result = [];
    if (home_url() !== 'https://dev.beyourlover.com' || !is_ssl()) { return $result; }

    $file = '/home/u247343654/byl-v3-staging/p13-sandbox-gate.json';
    if (!is_readable($file)) { return $result; }
    $raw = file_get_contents($file);
    $config = is_string($raw) ? json_decode($raw, true) : null;
    if (!is_array($config) || empty($config['enabled']) ||
        !isset($config['expires_at'], $config['customer_id'], $config['cookie_hash'], $config['gateway'])) {
        return $result;
    }

    $expires = (int) $config['expires_at'];
    if ($expires <= time() || $expires > time() + 3600) { return $result; }
    $user_id = get_current_user_id();
    if (!$user_id || $user_id !== (int) $config['customer_id']) { return $result; }
    $cookie = $_COOKIE['byl_p13_sandbox'] ?? '';
    if (!is_string($cookie) || strlen($cookie) < 40 || strlen($cookie) > 256 ||
        !hash_equals((string) $config['cookie_hash'], hash('sha256', $cookie))) {
        return $result;
    }

    $gateway = (string) $config['gateway'];
    $mode = false;
    if ($gateway === 'stripe') {
        $settings = (array) get_option('woocommerce_stripe_settings', []);
        $mode = ($settings['testmode'] ?? 'no') === 'yes';
    } elseif ($gateway === 'woocommerce_payments') {
        $settings = (array) get_option('woocommerce_woocommerce_payments_settings', []);
        $mode = ($settings['test_mode'] ?? 'no') === 'yes';
    } elseif ($gateway === 'ppcp') {
        $settings = (array) get_option('woocommerce_ppcp_api_settings', []);
        $mode = ($settings['environment'] ?? '') === 'sandbox';
    }
    if (!$mode) { return $result; }
    $result = ['customer_id' => $user_id, 'gateway' => $gateway];
    return $result;
}

// No external outgoing email, even from an authorized test order.
add_filter('pre_wp_mail', static function () { return false; }, 99);

// Ordinary visitors see no payment methods. Tester sees exactly one eligible rail.
add_filter('woocommerce_available_payment_gateways', static function (array $gateways): array {
    $context = byl_dev_p13_checkout_gate();
    if (!$context) { return []; }
    $id = $context['gateway'];
    return isset($gateways[$id]) ? [$id => $gateways[$id]] : [];
}, 99);

add_filter('woocommerce_allow_tracking', '__return_false', 99);

// Store API Checkout is a separate order-creation/payment path from classic
// checkout. Its checkout/{order_id} route can also process an existing order.
// The existing synthetic email, six-order cap and audit marker are implemented
// ONLY for classic Checkout. Never open Store API using that classic permit.
// Until Store API has independently verified equivalent safeguards, deny its
// Checkout routes even when a classic-only sandbox test permit is valid.
add_filter('rest_pre_dispatch', static function ($result, $server, $request) {
    if ($result !== null || !($request instanceof WP_REST_Request)) { return $result; }
    $route = $request->get_route();
    if (!preg_match('#^/wc/store(?:/v1)?/checkout(?:/[0-9]+)?/?$#', $route)) { return $result; }
    // OPTIONS advertises endpoint metadata and has no order-creation effect.
    if ($request->get_method() === 'OPTIONS') { return $result; }
    return new WP_Error(
        'byl_dev_sandbox_checkout_closed',
        'Store API Checkout is disabled on the development environment.',
        ['status' => 403]
    );
}, 99, 3);


// Stop all checkout orders, except a narrowly scoped synthetic customer.
// Limit the number of test orders; do not weaken server-side Woo validations.
add_action('woocommerce_checkout_process', static function (): void {
    $context = byl_dev_p13_checkout_gate();
    if ($context) {
        $email = sanitize_email(wp_unslash($_POST['billing_email'] ?? ''));
        if ($email !== 'byl-p13@example.invalid') {
            wc_add_notice('Sandbox order requires the isolated testing identity.', 'error');
            return;
        }
        $existing = wc_get_orders([
            'customer_id' => $context['customer_id'],
            'limit' => 6,
            'return' => 'ids',
        ]);
        if (count($existing) >= 6) {
            wc_add_notice('Sandbox order limit reached. No additional orders allowed.', 'error');
        }
        return;
    }
    if (function_exists('wc_add_notice')) {
        wc_add_notice('Orders are disabled on the development environment.', 'error');
    }
}, PHP_INT_MAX);

// Mark the order BEFORE its initial save so audit/ERP filters can recognize it.
add_action('woocommerce_checkout_create_order', static function ($order): void {
    if (byl_dev_p13_checkout_gate() && is_a($order, 'WC_Order')) {
        $order->update_meta_data('_byl_dev_sandbox_p13', 'yes');
    }
}, 10, 1);

add_action('send_headers', static function () {
    header('X-Robots-Tag: noindex, nofollow, noarchive', true);
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0', true);
    header('Pragma: no-cache', true);
    header('X-LiteSpeed-Cache-Control: no-cache', true);
});
add_filter('wp_robots', static function (array $robots): array {
    $robots['noindex'] = true;
    $robots['nofollow'] = true;
    $robots['noarchive'] = true;
    return $robots;
});

add_filter('wp_get_attachment_url', static function ($url, $attachment_id) {
    $attachment_id = (int) $attachment_id;
    $relative = get_post_meta($attachment_id, '_wp_attached_file', true);
    if (!is_string($relative) || $relative === '') { return $url; }
    $uploads = wp_get_upload_dir();
    $local = trailingslashit($uploads['basedir']) . ltrim($relative, '/');
    if (is_file($local)) { return $url; }
    $offloaded = get_post_meta($attachment_id, 'advmo_offloaded', true);
    $r2_path = get_post_meta($attachment_id, 'advmo_path', true);
    if ((string) $offloaded === '1' && is_string($r2_path) && $r2_path !== '') {
        return 'https://img.beyourlover.com/' . trim($r2_path, '/') . '/' . wp_basename($relative);
    }
    return $url;
}, 50, 2);
