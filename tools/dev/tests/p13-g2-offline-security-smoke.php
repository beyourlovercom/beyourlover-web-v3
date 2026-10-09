<?php
/**
 * Isolated PHP smoke for existing Dev Safety MU module G2.
 * Run: php tools/dev/tests/p13-g2-offline-security-smoke.php
 *
 * Rewrites the private gate path ONLY inside a disposable /tmp copy, mocks WP
 * authentication, and never calls Dev/WWW or creates real cookies/sessions.
 */
declare(strict_types=1);

$scenarios = [
    'closed', 'pending', 'valid', 'wrong_cookie', 'wrong_user',
    'admin_user', 'custom_privileged_user', 'live_gateway', 'expired', 'wrong_gateway', 'wrong_site',
    'not_https', 'admin_flow', 'deny_admin_approve', 'deny_guest_activate',
    'deny_bad_nonce',
];
if ($argc === 1) {
    foreach ($scenarios as $scenario) {
        $cmd = escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' '.escapeshellarg($scenario);
        passthru($cmd, $status);
        if ($status !== 0) { exit($status); }
    }
    echo 'G2_SECURITY_SMOKE=PASS '.count($scenarios).' scenarios (local only)'.PHP_EOL;
    exit(0);
}

define('ABSPATH', '/isolated-test/');
$scenario = $argv[1];
$root = dirname(__DIR__, 3);
$source = $root.'/dev/mu-plugins/byl-dev-safety.php';
if (!is_file($source)) { throw new RuntimeException('Missing module source'); }
$tmp = sys_get_temp_dir().'/byl-p13-g2-offline-'.getmypid();
if (!mkdir($tmp, 0700)) { throw new RuntimeException('Cannot create isolated directory'); }
$plugin = $tmp.'/safety.php';
$privatePath = '/home/u247343654/byl-v3-staging/p13-sandbox-gate.json';
$code = file_get_contents($source);
if (!str_contains($code, $privatePath)) { throw new RuntimeException('Source gate path changed'); }
file_put_contents($plugin, str_replace($privatePath, $tmp.'/permit.json', $code));

$GLOBALS['hooks'] = [];
$GLOBALS['testState'] = [
    'site' => $scenario === 'wrong_site' ? 'https://www.beyourlover.com' : 'https://dev.beyourlover.com',
    'https' => $scenario !== 'not_https',
    'user' => in_array($scenario, ['admin_user', 'admin_flow', 'deny_bad_nonce'], true) ? 19
        : ($scenario === 'custom_privileged_user' ? 20
        : ($scenario === 'wrong_user' || $scenario === 'deny_guest_activate' ? 18 : 17)),
    'testmode' => $scenario !== 'live_gateway',
    'nonce' => $scenario !== 'deny_bad_nonce',
];

final class G2Redirected extends RuntimeException {}
final class G2Denied extends RuntimeException {}

function add_action($hook, $callback, $priority = 10, $args = 1): void {
    $GLOBALS['hooks'][$hook][] = $callback;
}
function add_filter($hook, $callback, $priority = 10, $args = 1): void {}
function wp_get_environment_type(): string { return 'staging'; }
function home_url($path = ''): string { return $GLOBALS['testState']['site'].$path; }
function is_ssl(): bool { return $GLOBALS['testState']['https']; }
function get_current_user_id(): int { return $GLOBALS['testState']['user']; }
function get_userdata($id) {
    return match ((int) $id) {
        17 => (object) ['ID' => 17, 'user_email' => 'byl-p13@example.invalid', 'roles' => ['customer']],
        18 => (object) ['ID' => 18, 'user_email' => 'other@example.invalid', 'roles' => ['customer']],
        19 => (object) ['ID' => 19, 'user_email' => 'byl-p13@example.invalid', 'roles' => ['administrator']],
        20 => (object) ['ID' => 20, 'user_email' => 'byl-p13@example.invalid', 'roles' => ['customer', 'custom_role']],
        default => null,
    };
}
function get_user_by($field, $value) {
    return $field === 'email' && $value === 'byl-p13@example.invalid'
        ? get_userdata(17) : null;
}
function get_option($key, $default = []) {
    return $key === 'woocommerce_woocommerce_payments_settings'
        ? ['enabled' => 'yes', 'test_mode' => $GLOBALS['testState']['testmode'] ? 'yes' : 'no']
        : $default;
}
function wc_get_orders(array $args): array { return []; }
function wp_json_encode($value, $flags = 0): string|false { return json_encode($value, $flags); }
function current_user_can($cap): bool { return $GLOBALS['testState']['user'] === 19; }
function user_can($user, $cap): bool {
    return in_array((int) $user->ID, [19, 20], true)
        && in_array((string) $cap, ['manage_options', 'manage_woocommerce'], true);
}
function check_admin_referer($action): void {
    if (!$GLOBALS['testState']['nonce']) { throw new G2Denied('CSRF_DENIED'); }
}
function wp_die($message = '', $title = '', $args = []): void { throw new G2Denied('DENIED'); }
function wp_safe_redirect($url): void { throw new G2Redirected('REDIRECT'); }
function admin_url($path = ''): string { return 'https://dev.beyourlover.com/wp-admin/'.$path; }
function wp_nonce_field($name): void {}
function add_management_page(...$args): void {}
function esc_html($value): string { return htmlspecialchars((string) $value); }
function esc_url($value): string { return (string) $value; }

require $plugin;
$path = byl_dev_p13_permit_path();
function g2_assert(bool $value, string $detail): void {
    if (!$value) { throw new RuntimeException('FAILED '.$detail); }
}
function g2_call_action(string $name, bool $expectRedirect): void {
    $cb = $GLOBALS['hooks'][$name][0] ?? null;
    g2_assert(is_callable($cb), 'missing action '.$name);
    try {
        $cb();
        throw new RuntimeException('FAILED no response for '.$name);
    } catch (G2Redirected $e) {
        g2_assert($expectRedirect, 'unexpected redirect '.$name);
    } catch (G2Denied $e) {
        g2_assert(!$expectRedirect, 'unexpected denial '.$name);
    }
}

$cookie = 'ISOLATED_OFFLINE_G2_FIXTURE_NOT_A_REAL_BROWSER_SESSION_1234567890';
$pending = [
    'enabled' => true, 'expires_at' => time() + 600,
    'customer_id' => 17, 'gateway' => 'woocommerce_payments',
];

if ($scenario === 'admin_flow') {
    g2_call_action('admin_post_byl_p13_approve', true);
    $approved = byl_dev_p13_read_permit();
    g2_assert(byl_dev_p13_is_live_permit($approved), 'admin grant must be live');
    g2_assert(!isset($approved['cookie_hash']), 'admin approval must NOT open checkout');
    $GLOBALS['testState']['user'] = 17;
    g2_call_action('admin_post_byl_p13_activate', true);
    $active = byl_dev_p13_read_permit();
    g2_assert(isset($active['cookie_hash']) && strlen($active['cookie_hash']) === 64, 'activation must store hash');
    g2_assert(!str_contains((string) file_get_contents($path), 'ISOLATED_OFFLINE'), 'plaintext must never persist');
    g2_assert((fileperms($path) & 0777) === 0600, 'private gate must be 0600');
    g2_call_action('admin_post_byl_p13_activate', false); // one-time activation
    $GLOBALS['testState']['user'] = 19;
    g2_call_action('admin_post_byl_p13_revoke', true);
    g2_assert(!is_file($path), 'admin revoke must close gate');
} elseif ($scenario === 'deny_admin_approve') {
    g2_call_action('admin_post_byl_p13_approve', false);
    g2_assert(!is_file($path), 'unauthorized user must not issue permit');
} elseif ($scenario === 'deny_bad_nonce') {
    g2_call_action('admin_post_byl_p13_approve', false);
    g2_assert(!is_file($path), 'CSRF must not issue permit');
} else {
    if ($scenario !== 'closed') {
        $fixture = $pending;
        if ($scenario === 'expired') { $fixture['expires_at'] = time() - 10; }
        if ($scenario === 'wrong_gateway') { $fixture['gateway'] = 'ppcp'; }
        if ($scenario === 'custom_privileged_user') { $fixture['customer_id'] = 20; }
        if ($scenario !== 'pending' && $scenario !== 'deny_guest_activate') {
            $fixture['cookie_hash'] = hash('sha256', $cookie);
        }
        // Even for wrong site/HTTPS, seed an offline-only permit directly.
        file_put_contents($path, json_encode($fixture));
        chmod($path, 0600);
    }
    if ($scenario === 'deny_guest_activate') {
        g2_call_action('admin_post_byl_p13_activate', false);
        g2_assert(!isset(byl_dev_p13_read_permit()['cookie_hash']), 'wrong tester cannot activate');
    } else {
        if (!in_array($scenario, ['closed', 'pending', 'wrong_cookie'], true)) {
            $_COOKIE['byl_p13_sandbox'] = $cookie;
        }
        if ($scenario === 'wrong_cookie') { $_COOKIE['byl_p13_sandbox'] = 'WRONG_OFFLINE_FIXTURE'; }
        $expected = $scenario === 'valid'
            ? ['customer_id' => 17, 'gateway' => 'woocommerce_payments'] : [];
        g2_assert(byl_dev_p13_checkout_gate() === $expected, 'gate decision for '.$scenario);
    }
}
@unlink($path);
@unlink($tmp.'/p13-sandbox-gate.lock');
@unlink($plugin);
@rmdir($tmp);
echo 'PASS '.$scenario.PHP_EOL;
