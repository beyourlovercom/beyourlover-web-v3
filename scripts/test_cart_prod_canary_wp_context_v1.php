<?php
/** B4 standalone WP context matrix. No WP boot, network, payment, order, DB. */
declare(strict_types=1);
define('ABSPATH', __DIR__);
$root = dirname(__DIR__);
$GLOBALS['wp_canary_mock'] = [
    'home' => 'https://www.beyourlover.com/',
    'env' => 'production',
    'logged_in' => true,
    'id' => 17,
    'can' => true,
    'admin' => false,
    'checkout' => false,
    'stylesheet' => 'shoptimizer-child-theme',
    'parent' => 'shoptimizer',
    'theme_root' => $root.'/theme/beyourlover-v3',
];

function home_url(string $path = '/'): string {return $GLOBALS['wp_canary_mock']['home'];}
function wp_parse_url(string $url, int $component = -1) {return parse_url($url, $component);}
function wp_get_environment_type(): string {return $GLOBALS['wp_canary_mock']['env'];}
function is_user_logged_in(): bool {return $GLOBALS['wp_canary_mock']['logged_in'];}
function get_current_user_id() {return $GLOBALS['wp_canary_mock']['id'];}
function current_user_can(string $cap): bool {
    return $cap === 'manage_woocommerce' && $GLOBALS['wp_canary_mock']['can'];
}
function get_stylesheet_directory(): string {return $GLOBALS['wp_canary_mock']['theme_root'];}
function is_admin(): bool {return $GLOBALS['wp_canary_mock']['admin'];}
function is_checkout(): bool {return $GLOBALS['wp_canary_mock']['checkout'];}
class CartMockTheme {
    public function get_stylesheet(): string {return $GLOBALS['wp_canary_mock']['stylesheet'];}
    public function get_template(): string {return $GLOBALS['wp_canary_mock']['parent'];}
}
function wp_get_theme(): CartMockTheme {return new CartMockTheme();}

$sha = str_repeat('a',40);
$theme = hash_file('sha256', $root.'/theme/beyourlover-v3/functions.php');
if (!is_string($theme) || strlen($theme)!==64) {
    fwrite(STDERR,"FAIL mock theme fingerprint\n"); exit(1);
}
$mandatory = [
    'master_enabled','incident_p0_cleared','commerce_e2e_cleared',
    'active_theme_verified','legacy_fallback_verified',
    'cache_isolation_verified','runtime_hash_verified',
    'legacy_cart_fragments_recovered',
    'legacy_cold_guest_empty_cart_pass','legacy_cold_guest_filled_cart_pass',
    'legacy_fragment_latency_under_timeout_pass',
    'www_dynamic_ajax_503_stable','woo_post_upgrade_integrity_verified',
    'payment_gateway_readonly_health_verified',
    'legacy_drawer_verified','legacy_cart_page_verified',
    'www_post_hotfix_hashes_reconciled','legacy_restore_artifact_verified',
    'www_theme_identity_attested','www_integration_point_attested',
    'guest_after_staff_cache_test_pass',
    'woocommerce_cart_session_isolated','checkout_route_untouched',
    'staff_html_uncacheable','cloudflare_guest_segregated',
    'hostinger_guest_segregated','woo_fragments_legacy_preserved',
    'drawer_adapter_attested','drawer_trigger_no_double_bind',
    'drawer_woo_store_api_contract_pass','drawer_phase_owner_pass',
    'page_template_route_attested','page_native_woo_cart_authority_attested',
    'page_legacy_fallback_attested','page_empty_state_independently_gated',
];
$policy = array_fill_keys($mandatory,true);
$policy += [
    'drawer_enabled'=>true,'page_enabled'=>false,
    'approved_stylesheet'=>'shoptimizer-child-theme',
    'approved_parent_template'=>'shoptimizer',
    'approved_theme_fingerprint'=>$theme,
    'approved_release_sha'=>$sha,
    'installed_release_sha'=>$sha,
    'allowed_staff_ids'=>[17],
    // Untrusted looking overrides MUST NOT be able to replace WP-derived facts.
    'host'=>'evil.test','user_id'=>999,'wp_environment'=>'staging',
    'is_logged_in'=>false,'can_manage_woocommerce'=>false,
    'live_theme_fingerprint'=>str_repeat('b',64),
];
define('BYL_CART_CANARY_V1_SERVER_POLICY', $policy);
define('DONOTCACHEPAGE', true);
require $root.'/theme/beyourlover-v3/inc/cart-production-canary-gates.php';
require $root.'/theme/beyourlover-v3/inc/cart-production-canary-compatibility.php';
require $root.'/theme/beyourlover-v3/inc/cart-production-canary-wp-context.php';

$checks=0;
function assert_wp_canary(bool $ok,string $reason): void {
    global $checks;
    if (!$ok) {fwrite(STDERR, "FAIL B4 WP mock adapter: ".$reason."\n");exit(1);}
    $checks++;
}
$ctx = byl_v3_cart_prod_canary_wp_context();
assert_wp_canary($ctx!==[], 'valid server-attested context only');
assert_wp_canary(($ctx['host']??null)==='www.beyourlover.com', 'no host policy override');
assert_wp_canary(($ctx['wp_environment']??null)==='production', 'environment WP-derived');
assert_wp_canary(($ctx['user_id']??null)===17, 'user id WP-derived');
assert_wp_canary(($ctx['is_logged_in']??null)===true, 'login WP-derived');
assert_wp_canary(($ctx['can_manage_woocommerce']??null)===true, 'capability WP-derived');
assert_wp_canary(($ctx['live_theme_fingerprint']??null)===$theme, 'on-disk theme fingerprint');
assert_wp_canary(($ctx['staff_html_uncacheable']??null)===true, 'cache lock');
assert_wp_canary(byl_v3_cart_prod_canary_wp_decision()['reason']==='staff_drawer_only',
    'valid mocked internal Drawer only');
assert_wp_canary($ctx['page_enabled']===false, 'new cart page remains OFF');

$base = $GLOBALS['wp_canary_mock'];
$negative = [
    'home'=>['https://dev.beyourlover.com/', 'https://www.beyourlover.com.evil.test/','http://localhost/'],
    'env'=>['staging','development','local'],
    'logged_in'=>[false],
    'id'=>[0,22,'17',null],
    'can'=>[false],
    'admin'=>[true],
    'checkout'=>[true],
    'stylesheet'=>['shoptimizer','beyourlover-v3',''],
    'parent'=>['another-theme','beyourlover-v3',''],
    'theme_root'=>['/nonexistent/never-existing-cart-canary-dir',''],
];
foreach ($negative as $field=>$badValues) {
    foreach ($badValues as $bad) {
        $GLOBALS['wp_canary_mock'] = $base;
        $GLOBALS['wp_canary_mock'][$field]=$bad;
        assert_wp_canary(byl_v3_cart_prod_canary_wp_context()===[],
            'deny mutated WP authority '.$field.' '.get_debug_type($bad));
        $decision=byl_v3_cart_prod_canary_wp_decision();
        assert_wp_canary($decision['drawer']==='legacy'&&$decision['page']==='legacy',
            'legacy fallback on '.$field.' failure');
    }
}
$GLOBALS['wp_canary_mock']=$base;
// Even a valid WP identity CANNOT override missing prerequisites or elevate
// a legacy public user by placing values in query parameters or WP options.
foreach ([
    'incident_p0_cleared','legacy_cold_guest_empty_cart_pass',
    'legacy_cold_guest_filled_cart_pass','legacy_fragment_latency_under_timeout_pass',
    'www_dynamic_ajax_503_stable','woo_post_upgrade_integrity_verified',
    'payment_gateway_readonly_health_verified',
    'cache_isolation_verified','drawer_adapter_attested'
] as $gate) {
    $v=$ctx; $v[$gate]=false;
    $p=byl_v3_cart_prod_canary_compatibility_decision($v);
    assert_wp_canary($p['drawer']==='legacy'&&$p['page']==='legacy',
        'denied if external verification missing: '.$gate);
}
foreach ([
    'functions.php','inc/theme-os-bootstrap.php','inc/theme-os-assets.php',
    'inc/cart-drawer.php','inc/notification-cart.php'
] as $runtimeFile) {
    $s=file_get_contents($root.'/theme/beyourlover-v3/'.$runtimeFile);
    assert_wp_canary(!str_contains($s,'cart-production-canary-wp-context.php'),
        'WP context is NOT included or enabled by '.$runtimeFile);
}
$s=file_get_contents($root.'/theme/beyourlover-v3/inc/cart-production-canary-wp-context.php');
assert_wp_canary(!str_contains($s,'add_action(')
    && !str_contains($s,'add_filter(')
    && !str_contains($s,'$_GET')
    && !str_contains($s,'$_POST')
    && !str_contains($s,'$_COOKIE')
    && !str_contains($s,'update_option(')
    && !str_contains($s,'wp_enqueue_script('),
    'no hooks, request activation, options or checkout mutation');
assert_wp_canary(!str_contains($s,"define('BYL_CART_CANARY_V1_SERVER_POLICY'"),
    'policy never defined by production source code');
echo 'PASS Cart B4 WP mock adapter: '.$checks.
    ' offline checks; NO_RUNTIME_HOOKS; PROD_CANARY=HOLD; 0 transactions'.PHP_EOL;
