<?php
/**
 * BYL V3 A1 — authorized Dev-only runtime READ-ONLY status receipt.
 *
 * Run ONLY through an independently authorized Dev WP-CLI account:
 *   wp eval-file /trusted/read-only/byl_a1_dev_runtime_readonly.php --quiet
 *
 * Does not send mail, invoke payment gateways, write WordPress/options/DB,
 * read provider secrets, call any external URL or touch WWW. Keep full output
 * in private Owner-approved evidence storage; do not commit it to GitHub.
 * A reported hook or test-mode option is not a true provider-side guarantee.
 */
if (!defined('WP_CLI') || !WP_CLI || !defined('ABSPATH')
    || !function_exists('get_option') || !function_exists('wp_get_environment_type')) {
    fwrite(STDERR, "BYL_A1_DENY: Authorized Dev WP-CLI required\n");
    exit(2);
}
if (untrailingslashit(home_url()) !== 'https://dev.beyourlover.com'
    || wp_get_environment_type() !== 'staging') {
    fwrite(STDERR, "BYL_A1_DENY: Dev host/staging identity mismatch\n");
    exit(2);
}

function byl_a1_enabled_state($value): string {
    return $value === 'yes' ? 'ENABLED'
        : ($value === 'no' ? 'DISABLED' : 'UNKNOWN');
}
function byl_a1_test_state($value): string {
    return $value === 'yes' ? 'TEST'
        : ($value === 'no' ? 'LIVE_OR_UNSAFE' : 'UNKNOWN');
}
function byl_a1_gateway_receipt(string $key, string $testField): array {
    $settings = get_option($key, []);
    if (!is_array($settings)) { $settings = []; }
    return [
        'enabled' => byl_a1_enabled_state($settings['enabled'] ?? null),
        'test_mode' => byl_a1_test_state($settings[$testField] ?? null),
    ];
}

// Read only: static plugin headers and active slugs, without secrets/options.
require_once ABSPATH . 'wp-admin/includes/plugin.php';
$plugins = function_exists('get_plugins') ? get_plugins() : [];
$active = (array) get_option('active_plugins', []);
$known = [
    'woocommerce/woocommerce.php',
    'woocommerce-payments/woocommerce-payments.php',
    'woo-stripe-payment/woocommerce-stripe.php',
    'woocommerce-gateway-stripe/woocommerce-gateway-stripe.php',
    'affiliate-wp/affiliate-wp.php',
    'affiliatewp/affiliatewp.php',
    'aftership-woocommerce-tracking/aftership-woocommerce-tracking.php',
    'yaycurrency/yaycurrency.php',
];
$knownPlugins = [];
foreach ($known as $slug) {
    $metadata = $plugins[$slug] ?? null;
    $knownPlugins[$slug] = [
        'installed' => is_array($metadata),
        'active' => in_array($slug, $active, true),
        'version' => is_array($metadata) ? (string) ($metadata['Version'] ?? '') : null,
    ];
}
$mu = defined('WPMU_PLUGIN_DIR') ? WPMU_PLUGIN_DIR . '/byl-dev-safety.php' : '';
$muExists = is_string($mu) && $mu !== '' && is_file($mu) && is_readable($mu);
$muDigest = $muExists ? hash_file('sha256', $mu) : false;

// Cron option is read only. The number does not prove external cron/queue
// isolation, and no event hooks/arguments are serialized or exposed.
$cron = get_option('cron', []);
$cronTimestamps = 0;
$cronEvents = 0;
if (is_array($cron)) {
    foreach ($cron as $time => $hooks) {
        if (!is_numeric($time) || !is_array($hooks)) { continue; }
        $cronTimestamps++;
        foreach ($hooks as $entries) {
            if (is_array($entries)) { $cronEvents += count($entries); }
        }
    }
}

$wcPayments = byl_a1_gateway_receipt('woocommerce_woocommerce_payments_settings', 'test_mode');
$stripe = byl_a1_gateway_receipt('woocommerce_stripe_settings', 'testmode');
$paypalSettings = get_option('woocommerce_ppcp_api_settings', []);
if (!is_array($paypalSettings)) { $paypalSettings = []; }
$paypalMode = ($paypalSettings['environment'] ?? null) === 'sandbox'
    ? 'SANDBOX' : (($paypalSettings['environment'] ?? null) === 'production'
        ? 'PRODUCTION_OR_UNSAFE' : 'UNKNOWN');

$receipt = [
    'schema' => 'byl-a1-dev-runtime-readonly-v1',
    'scope' => 'DEV_ONLY_AUTHORIZED_WPCLI_SNAPSHOT_NOT_EXTERNAL_PROVIDER_ATTESTATION',
    'site' => 'https://dev.beyourlover.com',
    'generated_utc' => gmdate('c'),
    'runtime' => [
        'environment_type' => wp_get_environment_type(),
        'wp_version' => get_bloginfo('version'),
        'woo_version' => defined('WC_VERSION') ? WC_VERSION : 'UNKNOWN',
        'php_version' => PHP_VERSION,
        'current_theme_stylesheet' => (string) get_stylesheet(),
        'active_plugin_count' => count($active),
        'known_plugins' => $knownPlugins,
    ],
    'dev_safety' => [
        'mu_file_present_readable' => $muExists,
        'mu_file_sha256' => is_string($muDigest) ? $muDigest : null,
        'runtime_sentinel_present' => function_exists('byl_dev_p13_checkout_gate')
            && function_exists('byl_dev_p13_runtime_ready'),
        'pre_wp_mail_hook_present' => has_filter('pre_wp_mail') !== false,
        'pre_http_request_hook_present' => has_filter('pre_http_request') !== false,
        'woocommerce_checkout_process_hook_present' =>
            has_action('woocommerce_checkout_process') !== false,
    ],
    'search_engine' => [
        'blog_public_option' => (string) get_option('blog_public', 'UNKNOWN'),
    ],
    'payment_config_hints_only' => [
        'woocommerce_payments' => $wcPayments,
        'stripe' => $stripe,
        'paypal_ppcp' => [
            'enabled' => byl_a1_enabled_state(get_option('woocommerce_ppcp_settings', [])['enabled'] ?? null),
            'mode' => $paypalMode,
        ],
        'other_gateways_not_exhaustively_audited' => true,
    ],
    'scheduled_work' => [
        'disable_wp_cron_constant' => defined('DISABLE_WP_CRON') ? (bool) DISABLE_WP_CRON : null,
        'wp_cron_timestamp_count' => $cronTimestamps,
        'wp_cron_event_records' => $cronEvents,
        'external_server_cron_and_action_scheduler' => 'NOT_VERIFIED',
    ],
    'external_provider_isolation' => [
        'aftership_outbound' => 'NOT_VERIFIED',
        'erp_shipout_sm_wms_crm' => 'NOT_VERIFIED',
        'newsletter_real_transport' => 'NOT_VERIFIED',
        'external_webhook_consumers' => 'NOT_VERIFIED',
    ],
    'backup_restore' => 'NOT_VERIFIED',
    'a1_overall' => 'HOLD',
    'a2_import_authorized' => false,
];

$json = wp_json_encode($receipt, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
if (!is_string($json)) {
    fwrite(STDERR, "BYL_A1_FAIL: Sanitized runtime receipt encoding error\n");
    exit(2);
}
fwrite(STDOUT, $json . "\n");
