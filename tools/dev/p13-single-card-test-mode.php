<?php
/**
 * P1.3 Dev sandbox payment presentation adjustment.
 * Run only with WP-CLI eval-file against dev.beyourlover.com.
 *
 * Owner-approved UX:
 *  - one ordinary Card gateway at a time (WooPayments first for Dev sandbox)
 *  - express wallet buttons limited to Cart and Checkout, never PDP
 *  - Stripe remains installed/configured for separate rollback/failover testing
 *
 * This is NOT a Production routing change. Never deploy/run on www.
 */
if (!defined('WP_CLI') || !WP_CLI || home_url() !== 'https://dev.beyourlover.com'
    || wp_get_environment_type() !== 'staging') {
    throw new RuntimeException('Wrong runtime: P1.3 is Dev WP-CLI only.');
}
$private_dir = '/home/u247343654/byl-v3-backups/p13-payment-method-presentation-20261008';
$gate_file = '/home/u247343654/byl-v3-staging/p13-sandbox-gate.json';
if (is_file($gate_file)) {
    throw new RuntimeException('Temporary sandbox gate open; do not change provider while a session is authorized.');
}
$woo = (array) get_option('woocommerce_woocommerce_payments_settings', []);
$stripe = (array) get_option('woocommerce_stripe_settings', []);
if (($woo['test_mode'] ?? '') !== 'yes' || ($woo['enabled'] ?? '') !== 'yes'
    || ($stripe['testmode'] ?? '') !== 'yes' || ($stripe['enabled'] ?? '') !== 'yes') {
    throw new RuntimeException('Expected enabled dual-card Test Mode baseline has changed; stop.');
}
$target_locations = ['cart', 'checkout'];
$current_locations = $woo['payment_request_button_locations'] ?? [];
if (!is_array($current_locations) || !in_array('product', $current_locations, true)
    || !in_array('cart', $current_locations, true) || !in_array('checkout', $current_locations, true)) {
    throw new RuntimeException('Unexpected WooPayments location baseline; stop.');
}
if (!is_array($woo['express_checkout_product_methods'] ?? null)) {
    throw new RuntimeException('Unexpected WooPayments product express methods; stop.');
}
if (!is_dir($private_dir) && !mkdir($private_dir, 0700, true)) {
    throw new RuntimeException('Could not create private backup directory.');
}
chmod($private_dir, 0700);
$backup = $private_dir . '/payment-options-before.json';
if (file_exists($backup)) {
    throw new RuntimeException('Pre-change backup already exists; avoid overwriting it.');
}
$payload = wp_json_encode([
    'site' => home_url(), 'created_utc' => gmdate('c'),
    'woocommerce_woocommerce_payments_settings' => $woo,
    'woocommerce_stripe_settings' => $stripe,
], JSON_UNESCAPED_SLASHES);
if (!is_string($payload) || file_put_contents($backup, $payload, LOCK_EX) === false) {
    throw new RuntimeException('Private options backup failed.');
}
chmod($backup, 0600);
$woo['payment_request_button_locations'] = $target_locations;
$woo['express_checkout_product_methods'] = [];
$stripe['enabled'] = 'no';
update_option('woocommerce_woocommerce_payments_settings', $woo, false);
update_option('woocommerce_stripe_settings', $stripe, false);
$woo_now = (array) get_option('woocommerce_woocommerce_payments_settings', []);
$stripe_now = (array) get_option('woocommerce_stripe_settings', []);
$pass = $woo_now['payment_request_button_locations'] === $target_locations
    && $woo_now['express_checkout_product_methods'] === []
    && ($woo_now['enabled'] ?? '') === 'yes'
    && ($woo_now['test_mode'] ?? '') === 'yes'
    && ($stripe_now['enabled'] ?? '') === 'no'
    && ($stripe_now['testmode'] ?? '') === 'yes';
if (!$pass) {
    update_option('woocommerce_woocommerce_payments_settings', $woo, false);
    update_option('woocommerce_stripe_settings', $stripe, false);
    throw new RuntimeException('Read-back verification failed; STOP and inspect private backup.');
}
echo "P13_SINGLE_CARD_DEV_TEST_MODE=PASS\n";
echo "VISIBLE_CARD_PROVIDER=woocommerce_payments\n";
echo "STRIPE_CARD=DISABLED_NOT_REMOVED\n";
echo "EXPRESS_LOCATIONS=cart,checkout\n";
echo "PRODUCT_EXPRESS_METHOD_COUNT=0\n";
echo "BACKUP_STORED_PRIVATE=YES\n";
