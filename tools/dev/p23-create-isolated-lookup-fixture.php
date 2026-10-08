<?php
/**
 * P2.3 Dev-only isolated Woo guest lookup fixture.
 * NEVER deploy or use on WWW.
 *
 * Usage:
 *   wp eval-file tools/dev/p23-create-isolated-lookup-fixture.php
 *   BYL_P23_ALLOW_SYNTHETIC_ORDER=1 wp eval-file tools/dev/p23-create-isolated-lookup-fixture.php
 *
 * Does not submit a checkout/payment or create real shipment/tracking data.
 * The order remains checkout-draft (not fulfillable).
 */
if (!defined('WP_CLI') || !WP_CLI ||
    home_url() !== 'https://dev.beyourlover.com' ||
    wp_get_environment_type() !== 'staging' ||
    !function_exists('wc_get_orders')) {
    throw new RuntimeException('Denied: must be BYL Dev Woo staging WP-CLI');
}
$gate = '/home/u247343654/byl-v3-staging/p13-sandbox-gate.json';
$backup = '/home/u247343654/byl-v3-backups/p23-positive-lookup-20261008/dev-db-before.sql';
if (is_file($gate)) throw new RuntimeException('Temporary checkout gate open: stop');
if (!is_readable($backup) || filesize($backup) < 1024 * 1024) {
    throw new RuntimeException('Fresh private database backup required');
}
if (!function_exists('wp_get_environment_type') || !function_exists('wc_create_order')) {
    throw new RuntimeException('Required WP/Woo functions missing');
}
$existing = wc_get_orders([
    'limit' => 2,
    'meta_key' => '_byl_dev_sandbox_p23',
    'meta_value' => 'yes',
    'return' => 'ids',
    'status' => array_keys(wc_get_order_statuses()),
]);
if ($existing) throw new RuntimeException('Existing P23 fixture found; refusing duplicate');
$result = [
    'site' => home_url(),
    'environment' => wp_get_environment_type(),
    'checkout_gate_closed' => true,
    'private_backup_present' => true,
    'existing_p23_fixtures' => count($existing),
    'fixture_status' => 'checkout-draft',
    'dummy_billing_email' => 'byl-p23-lookup@example.invalid',
    'gateway' => 'none',
    'no_line_items_or_shipping' => true,
    'external_email_blocked_by_dev_mu' => has_filter('pre_wp_mail') !== false,
];
if (!$result['external_email_blocked_by_dev_mu']) throw new RuntimeException('Dev mail guard is missing');
if (getenv('BYL_P23_ALLOW_SYNTHETIC_ORDER') !== '1') {
    echo 'READ_ONLY_PREFLIGHT=', wp_json_encode($result, JSON_UNESCAPED_SLASHES), PHP_EOL;
    exit;
}

/**
 * In this single short-lived CLI process, deny WP HTTP, email and new cron;
 * suppress all Woo order lifecycle listeners that could enqueue Jetpack,
 * WooPayments, AfterShip, email, stock, promotion or ERP actions.
 * No global plugin options or runtime settings are changed.
 */
$http_attempt_count = 0;
add_filter('pre_http_request', static function ($pre, $args, $url) use (&$http_attempt_count) {
    $http_attempt_count++;
    return new WP_Error('byl_p23_synthetic_no_network', 'External HTTP disabled during synthetic fixture creation');
}, PHP_INT_MAX, 3);
add_filter('pre_wp_mail', '__return_false', PHP_INT_MAX);
add_filter('pre_schedule_event', '__return_false', PHP_INT_MAX, 3);
add_filter('pre_reschedule_event', '__return_false', PHP_INT_MAX, 3);

foreach ([
    'woocommerce_new_order',
    'woocommerce_after_order_object_save',
    'woocommerce_before_order_object_save',
    'woocommerce_order_status_changed',
    'woocommerce_order_status_checkout-draft',
    'woocommerce_order_status_pending',
    'woocommerce_order_status_pending_to_checkout-draft',
    'woocommerce_order_status_pending_to_on-hold',
    'woocommerce_order_status_pending_to_processing',
    'woocommerce_order_status_checkout-draft_to_pending',
    'woocommerce_order_object_updated_props',
    'woocommerce_checkout_order_processed',
    'woocommerce_checkout_create_order',
    'save_post_shop_order',
    'save_post',
    'transition_post_status',
    'woocommerce_order_status_changed',
] as $hook) {
    remove_all_actions($hook);
}

$order = new WC_Order();
$order->set_status('checkout-draft');
$order->set_created_via('byl-p23-synthetic-fixture');
$order->set_billing_first_name('Sandbox');
$order->set_billing_last_name('Order Lookup Test');
$order->set_billing_email('byl-p23-lookup@example.invalid');
$order->set_customer_id(0);
$order->set_payment_method('');
$order->set_currency(get_woocommerce_currency());
$order->set_total(0);
$order->update_meta_data('_byl_dev_sandbox_p23', 'yes');
$order->update_meta_data('_byl_dev_never_fulfill', 'yes');
$order->update_meta_data('_byl_dev_fixture_scope', 'lookup_only_no_payment_no_shipment');
$id = $order->save();
if (!is_numeric($id) || (int)$id < 1) throw new RuntimeException('Woo fixture save failed');
$read_back = wc_get_order((int)$id);
if (!$read_back ||
    $read_back->get_status() !== 'checkout-draft' ||
    $read_back->get_billing_email() !== 'byl-p23-lookup@example.invalid' ||
    $read_back->get_meta('_byl_dev_sandbox_p23', true) !== 'yes' ||
    $read_back->get_meta('_byl_dev_never_fulfill', true) !== 'yes' ||
    $read_back->get_payment_method() !== '' ||
    $read_back->get_transaction_id() !== '' ||
    $read_back->get_items() ||
    $read_back->get_meta('_aftership_tracking_items', true)) {
    throw new RuntimeException('Fixture readback mismatch — manual inspection required');
}
echo 'P23_ISOLATED_SYNTHETIC_FIXTURE=', wp_json_encode([
    'order_id' => (int)$id,
    'status' => $read_back->get_status(),
    'email' => $read_back->get_billing_email(),
    'marker' => $read_back->get_meta('_byl_dev_sandbox_p23', true),
    'no_payment' => $read_back->get_payment_method() === '',
    'no_shipment' => empty($read_back->get_meta('_aftership_tracking_items',true)),
    'external_wp_http_attempts_blocked' => $http_attempt_count,
], JSON_UNESCAPED_SLASHES), PHP_EOL;
