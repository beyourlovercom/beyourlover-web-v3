<?php
/**
 * BYL P2.3: read-only Dev inventory of installed AfterShip carrier slugs and
 * Woo order-meta schema. ERP mappings source: erpbeyourlovercom/config/erp_logistics.php.
 * Never log credentials, real tracking numbers, order keys or customer PII.
 */
if (!defined('WP_CLI') || !WP_CLI ||
    home_url() !== 'https://dev.beyourlover.com' ||
    wp_get_environment_type() !== 'staging') {
    throw new RuntimeException('Audit only permitted on BYL Dev staging WP-CLI');
}
if (!is_plugin_active('aftership-woocommerce-tracking/aftership-woocommerce-tracking.php')) {
    throw new RuntimeException('AfterShip not active');
}
if (!isset($GLOBALS['AfterShip'])) throw new RuntimeException('AfterShip global missing');
$expected = [
    '4PX' => '4px',
    'DHL' => 'dhl-api',
    'USPS' => 'usps',
    'UPS' => 'ups',
    'FedEx' => 'fedex',
    'Canada Post' => 'canada-post',
    'SF Express' => 'sf-express',
    'Singapore Post' => 'singapore-post',
];
$couriers = (array) ($GLOBALS['AfterShip']->couriers ?? []);
$slugs = [];
foreach ($couriers as $courier) {
    if (is_array($courier) && !empty($courier['slug'])) {
        $slugs[(string)$courier['slug']] = true;
    }
}
$result = [];
foreach ($expected as $name => $slug) {
    $result[] = [
        'erp_carrier' => $name,
        'aftership_slug' => $slug,
        'in_installed_plugin_catalog' => isset($slugs[$slug]),
    ];
}
$order = wc_get_order(39921);
if (!$order || $order->get_meta('_byl_dev_sandbox_p23',true) !== 'yes' ||
    $order->get_status() !== 'checkout-draft') {
    throw new RuntimeException('Expected synthetic read-only fixture missing or modified');
}
$settings = (array)get_option('aftership_option_name',[]);
echo 'P23_ERP_AFTERSHIP_READ_ONLY=',wp_json_encode([
    'site' => home_url(),
    'aftership_version' => defined('AFTERSHIP_VERSION') ? AFTERSHIP_VERSION : 'unknown',
    'woocommerce_version' => defined('WC_VERSION') ? WC_VERSION : 'unknown',
    'hpos_enabled' => function_exists('custom_orders_table_usage_is_enabled') ? custom_orders_table_usage_is_enabled() : null,
    'catalog_slug_count' => count($slugs),
    'erp_to_plugin_slug_presence' => $result,
    'plugin_connected_setting' => !empty($settings['connected']),
    'fixture_39921_unpaid_draft' => $order->get_status() === 'checkout-draft' && !$order->is_paid(),
    'fixture_has_no_aftership_items' => !($order->get_meta('_aftership_tracking_items',true)),
    'temporary_checkout_gate_closed' => !file_exists('/home/u247343654/byl-v3-staging/p13-sandbox-gate.json'),
],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),PHP_EOL;
