<?php
/**
 * P1.4/P2.0 Post-purchase Dev capability inventory (READ ONLY).
 *
 * Usage on confirmed Dev staging only:
 *   wp eval-file tools/dev/p14-postpurchase-capability-audit.php
 *
 * Outputs public page metadata, gateway-independent capability flags,
 * Woo email template enablement and webhook destination *hosts only*.
 * Never emits Woo order identifiers, customer PII or credentials.
 */
if (home_url() !== 'https://dev.beyourlover.com' || wp_get_environment_type() !== 'staging') {
    throw new RuntimeException('P2.0 audit only supports BYL Dev staging.');
}
if (!function_exists('WC') || !WC()) {
    throw new RuntimeException('WooCommerce is not available.');
}
$ids = [
    'checkout' => (int) get_option('woocommerce_checkout_page_id', 0),
    'myaccount' => (int) get_option('woocommerce_myaccount_page_id', 0),
];
$pages = [];
foreach ($ids as $key => $id) {
    $page = $id ? get_post($id) : null;
    $text = $page ? (string) $page->post_content : '';
    $pages[$key] = [
        'id' => $id,
        'status' => $page ? $page->post_status : 'missing',
        'url' => $page ? get_permalink($page) : '',
        'woocommerce_shortcode' => strpos($text, '[woocommerce_') !== false,
        'woocommerce_blocks' => strpos($text, 'wp:woocommerce/') !== false,
    ];
}
$tracking = get_page_by_path('track-your-order');
$tracking_shortcode = $tracking
    && strpos((string) $tracking->post_content, '[woocommerce_order_tracking]') !== false;
$emails = [];
foreach (WC()->mailer()->get_emails() as $key => $email) {
    $emails[$key] = $email->is_enabled();
}
$webhooks = [];
foreach (get_posts([
    'post_type' => 'shop_webhook',
    'post_status' => 'any',
    'numberposts' => 100,
    'fields' => 'ids',
]) as $webhook_id) {
    if (!class_exists('WC_Webhook')) { break; }
    $hook = new WC_Webhook((int) $webhook_id);
    $webhooks[] = [
        'topic' => $hook->get_topic(),
        'status' => $hook->get_status(),
        'delivery_host' => wp_parse_url($hook->get_delivery_url(), PHP_URL_HOST),
    ];
}
$aftership = false;
foreach ((array) get_option('active_plugins', []) as $plugin) {
    if (stripos((string) $plugin, 'aftership') !== false) {
        $aftership = true;
    }
}
$result = [
    'dev_site' => home_url(),
    'theme' => get_stylesheet(),
    'pages' => $pages,
    'order_tracking' => $tracking ? [
        'id' => $tracking->ID,
        'status' => $tracking->post_status,
        'url' => get_permalink($tracking),
        'woocommerce_order_tracking_shortcode' => $tracking_shortcode,
    ] : null,
    'aftership_plugin_active' => $aftership,
    'woo_email_template_enabled' => $emails,
    'woo_webhooks' => $webhooks,
    'any_order_exists' => !empty(wc_get_orders(['limit' => 1, 'return' => 'ids'])),
    'sandbox_checkout_gate_open' => is_file('/home/u247343654/byl-v3-staging/p13-sandbox-gate.json'),
];
echo wp_json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), PHP_EOL;
