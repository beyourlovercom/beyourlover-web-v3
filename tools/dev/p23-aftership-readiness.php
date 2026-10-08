<?php
/**
 * P2.3 read-only Dev Woo + AfterShip readiness. Do not emit email, order keys, tracking numbers or tokens.
 * Example: wp eval-file tools/dev/p23-aftership-readiness.php
 */
if (home_url() !== 'https://dev.beyourlover.com' || wp_get_environment_type() !== 'staging') {
    throw new RuntimeException('This test is only permitted on BYL Dev staging');
}
if (!function_exists('wc_get_orders') || !function_exists('is_plugin_active')) {
    throw new RuntimeException('WooCommerce is unavailable');
}
$settings = get_option('aftership_option_name', []);
$aftership = $GLOBALS['AfterShip'] ?? null;
$hook_registered = false;
if (is_object($aftership) && isset($aftership->actions)) {
    $hook_registered = has_action('woocommerce_view_order', [$aftership->actions, 'display_tracking_info']) !== false;
}
$rows = [];
foreach (wc_get_orders(['limit' => 10, 'orderby' => 'date', 'order' => 'DESC']) as $order) {
    $shipments = $order->get_meta('_aftership_tracking_items', true);
    $rows[] = [
        'id' => $order->get_id(),
        'created_via' => $order->get_created_via(),
        'status' => $order->get_status(),
        'billing_email_present' => (bool) $order->get_billing_email(),
        'aftership_tracking_items_present' => is_array($shipments) && count($shipments) > 0,
        'order_key_present' => (bool) $order->get_order_key(),
        'sandbox_fixture' => $order->get_meta('_byl_dev_sandbox_p13', true) === 'yes'
            || $order->get_meta('_byl_dev_sandbox_p23', true) === 'yes',
    ];
}
$results = [
    'site' => home_url(),
    'aftership_plugin_active' => is_plugin_active('aftership-woocommerce-tracking/aftership-woocommerce-tracking.php'),
    'aftership_config_flag_connected' => is_array($settings) && !empty($settings['connected']),
    'aftership_registered_on_woo_view_order' => $hook_registered,
    'woo_tracking_template_invokes_view_order' =>
        is_file(WC()->plugin_path() . '/templates/order/tracking.php'),
    'outbound_woocommerce_webhooks' => count(get_posts([
        'post_type' => 'shop_webhook', 'post_status' => 'any', 'numberposts' => 100, 'fields' => 'ids'
    ])),
    'orders' => $rows,
    'dev_checkout_test_gate_open' => file_exists('/home/u247343654/byl-v3-staging/p13-sandbox-gate.json'),
];
echo wp_json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), PHP_EOL;
