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

// Owner-approved Dev-only G5 lifetime cap: previous 6 + 5 = 11.
// The same bound applies to both checkout and normal admin approval.
const BYL_DEV_P13_MAX_SYNTHETIC_ORDERS = 11;

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
    if (!byl_dev_p13_is_synthetic_customer($user_id)) { return $result; }
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

// P7: keep AfterShip / Automizely Woo webhooks for real orders intact,
// but never emit the isolated Dev Sandbox order via Woo's outbound webhook path.
// This filter runs in WC_Webhook::process() BEFORE remote HTTP delivery.
add_filter('woocommerce_webhook_should_deliver', static function ($allow, $webhook, $arg): bool {
    // CLI/WP-Cron dispatch may have no HTTPS request context: match the configured site, not is_ssl().
    if (wp_get_environment_type() !== 'staging' || home_url() !== 'https://dev.beyourlover.com'
        || !is_object($webhook) || !method_exists($webhook, 'get_topic')) {
        return (bool) $allow;
    }
    // An order ID must not be interpreted as a product/customer webhook.
    $topic = (string) $webhook->get_topic();
    if (!str_starts_with($topic, 'order.')) {
        return (bool) $allow;
    }
    $order = $arg instanceof WC_Order ? $arg : (
        is_numeric($arg) && (int) $arg > 0 && function_exists('wc_get_order') ? wc_get_order((int) $arg) : null
    );
    if (!$order instanceof WC_Order) {
        return (bool) $allow;
    }
    if ($order->get_meta('_byl_dev_sandbox_p13', true) === 'yes'
        || strtolower(trim((string) $order->get_billing_email())) === 'byl-p13@example.invalid') {
        return false;
    }
    return (bool) $allow;
}, PHP_INT_MAX, 3);

// G5: Dev-only outbound isolation for AfterShip / Automizely HTTP API hosts.
// Native Woo order webhook isolation above is necessary but not sufficient:
// third-party plugins can also call WP HTTP directly (including in WP-Cron).
// This filter is network-preemptive; it never initiates an external request.
// Provider-side jobs already running outside Dev WordPress are NOT covered.
add_filter('pre_http_request', static function ($pre, $args, $url) {
    if ($pre !== false || wp_get_environment_type() !== 'staging'
        || home_url() !== 'https://dev.beyourlover.com' || !is_string($url)) {
        return $pre;
    }
    $host = wp_parse_url($url, PHP_URL_HOST);
    if (!is_string($host) || $host === '') { return $pre; }
    $host = strtolower(rtrim($host, '.'));
    foreach (['automizely.com', 'aftership.com'] as $providerDomain) {
        // Dot boundary forbids hosts like api.aftership.com.evil.example.
        if ($host === $providerDomain || str_ends_with($host, '.' . $providerDomain)) {
            return new WP_Error(
                'byl_dev_aftership_outbound_closed',
                'AfterShip/Automizely external HTTP requests are disabled on Dev.'
            );
        }
    }
    // Critical: WooPayments/Stripe APIs and every unrelated host untouched.
    return $pre;
}, PHP_INT_MAX, 3);

/**
 * G5 Dev-only inbound Provider quarantine.
 *
 * AfterShip exposes /aftership-api/vN/orders AND query-var alias routes;
 * outbound HTTP filtering never stops an authenticated Provider from
 * pulling /wc/v3/orders with its existing read_write Woo API key.
 *
 * Deliberately restrict only Dev order/customer API surfaces, not wp-admin
 * pages or internal PHP WC_Order reads. This may interrupt external Dev
 * API clients and should be kept until G5 closes, then reviewed for rollback.
 * Does not revoke/delete credentials or affect WWW.
 */
function byl_dev_g5_aftership_inbound_path(string $uri, array $queryVars = [], array $query = []): bool {
    $path = wp_parse_url($uri, PHP_URL_PATH);
    if (is_string($path) && preg_match('#(?:^|/)aftership-api(?:/|$)#i', $path)) {
        return true;
    }
    // Rewrite-free equivalent: /?aftership-api-route=/orders
    // or legacy /?aftership-api=...
    foreach (['aftership-api-route', 'aftership-api'] as $name) {
        if (array_key_exists($name, $queryVars) || array_key_exists($name, $query)) {
            return true;
        }
    }
    return false;
}
add_action('parse_request', static function ($wp): void {
    if (wp_get_environment_type() !== 'staging'
        || home_url() !== 'https://dev.beyourlover.com') {
        return;
    }
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
    $vars = is_object($wp) && isset($wp->query_vars) && is_array($wp->query_vars)
        ? $wp->query_vars : [];
    if (!byl_dev_g5_aftership_inbound_path($uri, $vars, $_GET)) {
        return;
    }
    status_header(403);
    nocache_headers();
    exit;
}, -999, 1);

// Prevent a previously connected AfterShip/Woo consumer_key from polling
// the native Woo REST Orders and Customers collections or mutating records.
// Store API Cart and WooPayments internal server-side processing remain intact.
add_filter('rest_pre_dispatch', static function ($result, $server, $request) {
    if ($result !== null || !($request instanceof WP_REST_Request)
        || wp_get_environment_type() !== 'staging'
        || home_url() !== 'https://dev.beyourlover.com') {
        return $result;
    }
    if (!preg_match('#^/wc/(?:v1|v2|v3)/(?:orders|customers)(?:/|$)#', $request->get_route())) {
        return $result;
    }
    return new WP_Error(
        'byl_dev_g5_provider_inbound_quarantine',
        'Woo Orders/Customers REST API is quarantined on Dev for G5 payment UAT.',
        ['status' => 403]
    );
}, 98, 3);

// No external outgoing email, even from an authorized test order.
add_filter('pre_wp_mail', static function () { return false; }, 99);

// P5: Store API Checkout is a distinct order-creation/payment path from classic Woo.
// The G2 test permit is classic-checkout only. Even a valid tester grant must
// never authorize Store API Checkout until that surface has equivalent gates.
// OPTIONS only advertises route metadata; never create/payment process here.
add_filter('rest_pre_dispatch', static function ($result, $server, $request) {
    if ($result !== null || !($request instanceof WP_REST_Request)) { return $result; }
    if (wp_get_environment_type() !== 'staging' || home_url() !== 'https://dev.beyourlover.com') {
        return $result;
    }
    $route = $request->get_route();
    if (!preg_match('#^/wc/store(?:/v1)?/checkout(?:/[0-9]+)?/?$#', $route)) { return $result; }
    if ($request->get_method() === 'OPTIONS') { return $result; }
    return new WP_Error(
        'byl_dev_sandbox_checkout_closed',
        'Store API Checkout is disabled on the development environment.',
        ['status' => 403]
    );
}, 99, 3);
// Ordinary visitors see no payment methods. Tester sees exactly one eligible rail.
add_filter('woocommerce_available_payment_gateways', static function (array $gateways): array {
    $context = byl_dev_p13_checkout_gate();
    if (!$context) { return []; }
    $id = $context['gateway'];
    return isset($gateways[$id]) ? [$id => $gateways[$id]] : [];
}, 99);

add_filter('woocommerce_allow_tracking', '__return_false', 99);

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
            'limit' => BYL_DEV_P13_MAX_SYNTHETIC_ORDERS,
            'return' => 'ids',
        ]);
        if (count($existing) >= BYL_DEV_P13_MAX_SYNTHETIC_ORDERS) {
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


/**
 * P1.3 G2: human-authorized, browser-bound Sandbox activation.
 *
 * This extends the EXISTING Dev Safety MU plugin, and never operates on www.
 * Approval only queues a permit (no cookie_hash => checkout remains closed).
 * The real logged-in synthetic customer must POST a nonce-protected activation
 * form on My Account to receive the Secure/HttpOnly/host-only cookie. Neither
 * user credentials nor cookie values are exposed to admin or tool output.
 */
function byl_dev_p13_runtime_ready(): bool {
    return wp_get_environment_type() === 'staging'
        && home_url() === 'https://dev.beyourlover.com'
        && is_ssl();
}

function byl_dev_p13_is_synthetic_customer(int $id): bool {
    if ($id < 1) { return false; }
    $user = get_userdata($id);
    if (!$user || strtolower((string) $user->user_email) !== 'byl-p13@example.invalid') {
        return false;
    }
    $roles = (array) $user->roles;
    return in_array('customer', $roles, true)
        && !array_intersect($roles, ['administrator', 'shop_manager', 'editor'])
        // Custom roles or capabilities must never turn a privileged user into a tester.
        && !user_can($user, 'manage_options')
        && !user_can($user, 'manage_woocommerce');
}

function byl_dev_p13_synthetic_customer() {
    $user = get_user_by('email', 'byl-p13@example.invalid');
    return $user && byl_dev_p13_is_synthetic_customer((int) $user->ID) ? $user : null;
}

function byl_dev_p13_permit_path(): string {
    return '/home/u247343654/byl-v3-staging/p13-sandbox-gate.json';
}

function byl_dev_p13_read_permit(): array {
    $path = byl_dev_p13_permit_path();
    if (!is_file($path) || !is_readable($path)) { return []; }
    $raw = file_get_contents($path);
    $value = is_string($raw) ? json_decode($raw, true) : null;
    return is_array($value) ? $value : [];
}

/**
 * Serialized updates with atomic 0600 file replacement. A rejected callback
 * returns false; null revokes by unlinking under the same lock. This file is
 * deliberately outside webroot. No cookie plaintext is ever stored here.
 */
function byl_dev_p13_change_permit(callable $decide): bool {
    if (!byl_dev_p13_runtime_ready()) { return false; }
    $path = byl_dev_p13_permit_path();
    $dir = dirname($path);
    if (!is_dir($dir) || !is_writable($dir)) { return false; }
    $lock = fopen($dir . '/p13-sandbox-gate.lock', 'c');
    if (!$lock) { return false; }
    chmod($dir . '/p13-sandbox-gate.lock', 0600);
    try {
        if (!flock($lock, LOCK_EX)) { return false; }
        $next = $decide(byl_dev_p13_read_permit());
        if ($next === false) { return false; }
        if ($next === null) { return !is_file($path) || unlink($path); }
        if (!is_array($next)) { return false; }
        $json = wp_json_encode($next, JSON_UNESCAPED_SLASHES);
        if (!is_string($json)) { return false; }
        $tmp = tempnam($dir, 'p13-grant-');
        if (!$tmp) { return false; }
        chmod($tmp, 0600);
        if (file_put_contents($tmp, $json, LOCK_EX) === false) {
            unlink($tmp);
            return false;
        }
        if (!rename($tmp, $path)) {
            unlink($tmp);
            return false;
        }
        return true;
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

function byl_dev_p13_is_live_permit(array $permit): bool {
    return !empty($permit['enabled'])
        && isset($permit['expires_at'], $permit['customer_id'], $permit['gateway'])
        && (int) $permit['expires_at'] > time()
        && (int) $permit['expires_at'] <= time() + 3600
        && $permit['gateway'] === 'woocommerce_payments'
        && byl_dev_p13_is_synthetic_customer((int) $permit['customer_id']);
}

function byl_dev_p13_admin_guard(string $nonce): void {
    if (!byl_dev_p13_runtime_ready() || !current_user_can('manage_options')) {
        wp_die('Sandbox approval is restricted to the Dev administrator.', '', ['response' => 403]);
    }
    check_admin_referer($nonce);
}

add_action('admin_menu', static function (): void {
    if (!byl_dev_p13_runtime_ready()) { return; }
    add_management_page('BYL Sandbox UAT', 'BYL Sandbox UAT', 'manage_options', 'byl-p13-sandbox', 'byl_dev_p13_admin_page');
});

function byl_dev_p13_admin_page(): void {
    if (!byl_dev_p13_runtime_ready() || !current_user_can('manage_options')) { return; }
    $permit = byl_dev_p13_read_permit();
    $live = byl_dev_p13_is_live_permit($permit);
    $state = !$live ? 'Closed' : (empty($permit['cookie_hash']) ? 'Awaiting tester activation' : 'Tester activated');
    echo '<div class="wrap"><h1>BYL Dev Sandbox UAT</h1>';
    echo '<p>Dev-only, WooPayments Test Mode, one synthetic customer, 30-minute maximum. No Live payment.</p>';
    echo '<p><strong>Gate status: ' . esc_html($state) . '</strong></p>';
    if (!$live) {
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="byl_p13_approve">';
        wp_nonce_field('byl_p13_approve');
        echo '<button type="submit" class="button button-primary">Approve 30-minute test window</button></form>';
    } else {
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="byl_p13_revoke">';
        wp_nonce_field('byl_p13_revoke');
        echo '<button type="submit" class="button">Revoke authorization immediately</button></form>';
    }
    echo '</div>';
}

add_action('admin_post_byl_p13_approve', static function (): void {
    byl_dev_p13_admin_guard('byl_p13_approve');
    $user = byl_dev_p13_synthetic_customer();
    $settings = (array) get_option('woocommerce_woocommerce_payments_settings', []);
    if (!$user || !function_exists('wc_get_orders')
        || ($settings['enabled'] ?? '') !== 'yes' || ($settings['test_mode'] ?? '') !== 'yes'
        || count(wc_get_orders(['customer_id' => $user->ID, 'limit' => BYL_DEV_P13_MAX_SYNTHETIC_ORDERS, 'return' => 'ids'])) >= BYL_DEV_P13_MAX_SYNTHETIC_ORDERS) {
        wp_die('Sandbox preflight failed; no grant issued.', '', ['response' => 403]);
    }
    $ok = byl_dev_p13_change_permit(static function (array $current) use ($user): array|bool {
        if (byl_dev_p13_is_live_permit($current)) { return false; }
        // No cookie_hash: approval alone NEVER enables checkout.
        return [
            'enabled' => true,
            'expires_at' => time() + 1800,
            'customer_id' => (int) $user->ID,
            'gateway' => 'woocommerce_payments',
            'approved_by' => get_current_user_id(),
        ];
    });
    if (!$ok) { wp_die('Sandbox grant was not written; gate remains closed.', '', ['response' => 403]); }
    wp_safe_redirect(admin_url('tools.php?page=byl-p13-sandbox'));
    exit;
});

add_action('admin_post_byl_p13_revoke', static function (): void {
    byl_dev_p13_admin_guard('byl_p13_revoke');
    if (!byl_dev_p13_change_permit(static fn (array $current) => null)) {
        wp_die('Could not revoke sandbox authorization.', '', ['response' => 500]);
    }
    wp_safe_redirect(admin_url('tools.php?page=byl-p13-sandbox'));
    exit;
});

// Render only inside the normal, logged-in WooCommerce My Account dashboard.
add_action('woocommerce_account_dashboard', static function (): void {
    if (!byl_dev_p13_runtime_ready()
        || !byl_dev_p13_is_synthetic_customer(get_current_user_id())) { return; }
    $permit = byl_dev_p13_read_permit();
    echo '<section class="byl-p13-sandbox"><h3>Sandbox Payment Test</h3>';
    if (!byl_dev_p13_is_live_permit($permit)
        || (int) $permit['customer_id'] !== get_current_user_id()) {
        echo '<p>Awaiting Dev administrator approval. No payment methods are enabled.</p>';
    } elseif (!empty($permit['cookie_hash'])) {
        echo '<p>Approval activated. Use Checkout for the approved WooPayments Test Mode scenario.</p>';
    } else {
        echo '<p>An administrator approved a short-lived sandbox test. Activate it in this browser only.</p>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="byl_p13_activate">';
        wp_nonce_field('byl_p13_activate');
        echo '<button type="submit">Activate Sandbox Test</button></form>';
    }
    echo '</section>';
});

add_action('admin_post_byl_p13_activate', static function (): void {
    if (!byl_dev_p13_runtime_ready()
        || !byl_dev_p13_is_synthetic_customer(get_current_user_id())) {
        wp_die('Only the logged-in synthetic customer may activate.', '', ['response' => 403]);
    }
    check_admin_referer('byl_p13_activate');
    try {
        $secret = bin2hex(random_bytes(32));
    } catch (Throwable $e) {
        wp_die('Secure random generator unavailable; gate remains closed.', '', ['response' => 500]);
    }
    $expires = 0;
    $ok = byl_dev_p13_change_permit(static function (array $permit) use ($secret, &$expires): array|bool {
        if (!byl_dev_p13_is_live_permit($permit)
            || (int) $permit['customer_id'] !== get_current_user_id()
            || !empty($permit['cookie_hash'])) { return false; }
        $expires = (int) $permit['expires_at'];
        $permit['cookie_hash'] = hash('sha256', $secret);
        return $permit;
    });
    if (!$ok || $expires <= time()) {
        wp_die('Approval unavailable, expired or already activated.', '', ['response' => 403]);
    }
    if (!setcookie('byl_p13_sandbox', $secret, [
        'expires' => $expires,
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict',
    ])) {
        // Never leave an active server-side grant without a browser cookie.
        byl_dev_p13_change_permit(static fn (array $permit) => null);
        wp_die('Browser cookie could not be established; authorization revoked.', '', ['response' => 500]);
    }
    wp_safe_redirect(home_url('/my-account/'));
    exit;
});
