<?php
/**
 * P7 isolated read-only Woo webhook guard tests.
 * Run: php tools/dev/tests/p7-sandbox-webhook-isolation-smoke.php
 *
 * Mocks WP/Woo and never calls actual WordPress, AfterShip or HTTP.
 */
declare(strict_types=1);

define('ABSPATH', '/fake-wp/');
$GLOBALS['p7_state'] = ['site' => 'https://dev.beyourlover.com', 'https' => true];
$GLOBALS['p7_filters'] = [];
$GLOBALS['p7_orders'] = [];

function add_filter($name, $callback, $priority = 10, $args = 1): void {
    $GLOBALS['p7_filters'][$name][$priority][] = $callback;
}
function add_action($name, $callback, $priority = 10, $args = 1): void {}
function wp_get_environment_type(): string { return 'staging'; }
function home_url($path = ''): string { return $GLOBALS['p7_state']['site'].$path; }
function is_ssl(): bool { return $GLOBALS['p7_state']['https']; }

final class WC_Order {
    public function __construct(private string $marker, private string $email) {}
    public function get_meta($key, $single = false): string {
        return $key === '_byl_dev_sandbox_p13' ? $this->marker : '';
    }
    public function get_billing_email(): string { return $this->email; }
}
final class P7Webhook {
    public function __construct(private string $topic) {}
    public function get_topic(): string { return $this->topic; }
}
function wc_get_order($id) { return $GLOBALS['p7_orders'][(int) $id] ?? false; }

$source = dirname(__DIR__, 3).'/dev/mu-plugins/byl-dev-safety.php';
if (!is_file($source)) { throw new RuntimeException('Missing Dev Safety source'); }
$copy = tempnam(sys_get_temp_dir(), 'p7-safety-');
if ($copy === false) { throw new RuntimeException('Cannot make isolated plugin copy'); }
try {
    $contents = file_get_contents($source);
    $private = '/home/u247343654/byl-v3-staging/p13-sandbox-gate.json';
    if (!str_contains($contents, $private)) { throw new RuntimeException('Private path changed'); }
    file_put_contents($copy, str_replace($private, '/tmp/p7-never-opened-gate.json', $contents));
    require $copy;
} finally {
    unlink($copy);
}

$registered = $GLOBALS['p7_filters']['woocommerce_webhook_should_deliver'] ?? [];
if (count($registered[PHP_INT_MAX] ?? []) !== 1) {
    throw new RuntimeException('Expected one high-priority outbound Woo webhook guard');
}
$guard = $registered[PHP_INT_MAX][0];
$cases = [
    'marker_yes_blocks_created' => [true, 'order.created', 721, false],
    'marker_yes_blocks_updated' => [true, 'order.updated', 721, false],
    'email_only_blocks_when_private_meta_missing' => [true, 'order.created', 722, false],
    'mixed_case_email_blocks_without_marker' => [true, 'order.updated', 723, false],
    'normal_woo_order_is_preserved' => [true, 'order.created', 724, true],
    'explicit_negative_marker_is_preserved' => [true, 'order.updated', 725, true],
    'normal_order_not_sent_if_woo_denied' => [false, 'order.created', 724, false],
    'product_webhook_not_blocked_by_collision' => [true, 'product.updated', 721, true],
    'unknown_order_not_blocked' => [true, 'order.created', 999, true],
    'non_numeric_arg_not_blocked' => [true, 'order.created', 'no-id', true],
];
$GLOBALS['p7_orders'] = [
    721 => new WC_Order('yes', 'normal@example.test'),
    722 => new WC_Order('', 'byl-p13@example.invalid'),
    723 => new WC_Order('no', 'BYL-P13@EXAMPLE.INVALID'),
    724 => new WC_Order('', 'normal@example.test'),
    725 => new WC_Order('no', 'normal@example.test'),
];
$passed = 0;
foreach ($cases as $case => [$allow, $topic, $arg, $expected]) {
    $actual = $guard($allow, new P7Webhook($topic), $arg);
    if ($actual !== $expected) { throw new RuntimeException('FAIL '.$case); }
    $passed++;
    echo 'PASS '.$case.PHP_EOL;
}
$direct = $guard(true, new P7Webhook('order.updated'), $GLOBALS['p7_orders'][721]);
if ($direct !== false) { throw new RuntimeException('FAIL direct_order_object'); }
$passed++; echo "PASS direct_order_object\n";
$GLOBALS['p7_state']['site'] = 'https://www.beyourlover.com';
if ($guard(true, new P7Webhook('order.created'), 721) !== true) { throw new RuntimeException('FAIL production_unmodified'); }
$passed++; echo "PASS production_unmodified\n";
$GLOBALS['p7_state'] = ['site' => 'https://dev.beyourlover.com', 'https' => false];
if ($guard(true, new P7Webhook('order.created'), 721) !== true) { throw new RuntimeException('FAIL http_unmodified'); }
$passed++; echo "PASS http_unmodified\n";

echo 'P7_WOO_WEBHOOK_ISOLATION=PASS '.$passed.' offline cases'.PHP_EOL;
