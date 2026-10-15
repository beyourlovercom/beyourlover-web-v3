<?php
/**
 * Isolated, no-network H6.3-P privacy alias contract regression.
 * Uses only stubbed WordPress responses. Never mutates pages, DB, or Woo.
 */
declare(strict_types=1);
define('ABSPATH', __DIR__);
define('OBJECT', 'OBJECT');
$case = (string)($argv[1] ?? '');
if ($case === 'rest') { define('REST_REQUEST', true); }
if ($case === 'wpcli') { define('WP_CLI', true); }
$GLOBALS['byl_test_hooks'] = [];
$GLOBALS['byl_test_redirects'] = [];
$GLOBALS['byl_test_flags'] = [];
$GLOBALS['byl_test_page'] = null;

class WP_Post {
    public int $ID = 7271;
    public string $post_status;
    public string $post_name;
    public function __construct(string $status, string $slug) {
        $this->post_status = $status;
        $this->post_name = $slug;
    }
}
function add_action($name, $callback, $priority = 10): void {
    $GLOBALS['byl_test_hooks'][] = [$name, $callback, $priority];
}
function is_admin(): bool { return !empty($GLOBALS['byl_test_flags']['admin']); }
function wp_doing_ajax(): bool { return !empty($GLOBALS['byl_test_flags']['ajax']); }
function wp_doing_cron(): bool { return !empty($GLOBALS['byl_test_flags']['cron']); }
function get_page_by_path($path, $output = null, $type = null) {
    if ($path !== 'privacy-policies' || $type !== 'page') { throw new RuntimeException('Unexpected Page query'); }
    return $GLOBALS['byl_test_page'];
}
function get_option(string $key, $default = 0) {
    if ($key !== 'wp_page_for_privacy_policy') { throw new RuntimeException('Unexpected option'); }
    return $GLOBALS['byl_test_flags']['privacy_page_id'] ?? 7271;
}
function home_url(string $path = '/'): string { return 'https://dev.beyourlover.com' . $path; }
function wp_parse_url(string $url) { return parse_url($url); }
function wp_safe_redirect(string $url, int $status = 302, string $by = ''): bool {
    $GLOBALS['byl_test_redirects'][] = [$url, $status, $by];
    return true;
}

require dirname(__DIR__, 3) . '/theme/beyourlover-v3/inc/privacy-policy-alias.php';

if (count($GLOBALS['byl_test_hooks']) !== 1
    || $GLOBALS['byl_test_hooks'][0] !== ['template_redirect', 'byl_v3_privacy_alias_redirect', 0]) {
    throw new RuntimeException('The alias must hook only early frontend template_redirect.');
}

function byl_assert(bool $condition, string $message): void {
    if (!$condition) { throw new RuntimeException($message); }
}

if ($case !== '') {
    $uri = '/privacy-polices/';
    $method = 'GET';
    $page = new WP_Post('publish', 'privacy-policies');
    if ($case === 'head') { $method = 'HEAD'; }
    if ($case === 'canonical') { $uri = '/privacy-policies/'; }
    if ($case === 'query') { $uri = '/privacy-polices/?utm_source=promo'; }
    if ($case === 'secret') { $uri = '/privacy-polices/?key=payment-token'; }
    if ($case === 'external') { $uri = '//evil.example/privacy-polices/'; }
    if ($case === 'encoded') { $uri = '/privacy%2Dpolices/'; }
    if ($case === 'cart') { $uri = '/cart/'; }
    if ($case === 'checkout') { $uri = '/checkout/order-pay/123/?key=wc_secret'; }
    if ($case === 'rest_path') { $uri = '/wp-json/andy-core/v1/newsletter/subscribe'; }
    if ($case === 'post') { $method = 'POST'; }
    if ($case === 'put') { $method = 'PUT'; }
    if ($case === 'unpublished') { $page = new WP_Post('draft', 'privacy-policies'); }
    if ($case === 'wrong_slug') { $page = new WP_Post('publish', 'privacy-polices'); }
    if ($case === 'missing_page') { $page = null; }
    if ($case === 'wrong_privacy_option') { $GLOBALS['byl_test_flags']['privacy_page_id'] = 9999; }
    if (in_array($case, ['admin', 'ajax', 'cron'], true)) {
        $GLOBALS['byl_test_flags'][$case] = true;
    }
    $_SERVER['REQUEST_METHOD'] = $method;
    $_SERVER['REQUEST_URI'] = $uri;
    $GLOBALS['byl_test_page'] = $page;

    // On a genuine redirect the theme exits. The shutdown callback emits
    // the captured WP redirect arguments to this isolated test subprocess.
    register_shutdown_function(static function (): void {
        echo json_encode($GLOBALS['byl_test_redirects'], JSON_UNESCAPED_SLASHES), "\n";
    });
    byl_v3_privacy_alias_redirect();
    exit(0);
}

$checks = [
    ['/privacy-polices/', 'GET', true],
    ['/privacy-polices', 'GET', true],
    ['/privacy-polices/', 'HEAD', true],
    ['/privacy-polices/', 'POST', false],
    ['/privacy-polices/', 'PUT', false],
    ['/privacy-policies/', 'GET', false],
    ['/privacy-policies', 'GET', false],
    ['/privacy-polices/?key=secret', 'GET', false],
    ['/privacy-polices/#anchor', 'GET', false],
    ['//evil.example/privacy-polices/', 'GET', false],
    ['/privacy%2Dpolices/', 'GET', false],
    ['/cart/', 'GET', false],
    ['/checkout/order-pay/123/?key=wc_token', 'GET', false],
    ['/wp-json/andy-core/v1/newsletter', 'GET', false],
];
foreach ($checks as [$uri, $method, $expected]) {
    byl_assert(byl_v3_privacy_alias_matches($uri, $method) === $expected,
        'Eligibility mismatch: ' . $method . ' ' . $uri);
}
$cases = [
    'happy' => true,
    'head' => true,
    'canonical' => false,
    'query' => false,
    'secret' => false,
    'external' => false,
    'encoded' => false,
    'post' => false,
    'put' => false,
    'unpublished' => false,
    'wrong_slug' => false,
    'missing_page' => false,
    'wrong_privacy_option' => false,
    'cart' => false,
    'checkout' => false,
    'rest_path' => false,
    'rest' => false,
    'wpcli' => false,
    'admin' => false,
    'ajax' => false,
    'cron' => false,
];
foreach ($cases as $key => $redirect) {
    $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' ' . escapeshellarg($key);
    $output = [];
    $status = 0;
    exec($command . ' 2>&1', $output, $status);
    byl_assert($status === 0, $key . ' subprocess failed: ' . implode(';', $output));
    $events = json_decode(implode("\n", $output), true);
    byl_assert(is_array($events), $key . ' invalid redirect JSON: ' . implode(';', $output));
    if ($redirect) {
        byl_assert($events === [['https://dev.beyourlover.com/privacy-policies/', 301, 'BYL V3 Privacy Alias']],
            $key . ' wrong destination/status/redirect count');
    } else {
        byl_assert($events === [], $key . ' unexpectedly redirected');
    }
}

echo "BYL_PRIVACY_ALIAS_LITERAL_MATCH=14/14_PASS\n";
echo "BYL_PRIVACY_ALIAS_EXECUTION=21/21_PASS\n";
echo "BYL_PRIVACY_ALIAS_NO_NETWORK_NO_DB_NO_WOO=PASS\n";
