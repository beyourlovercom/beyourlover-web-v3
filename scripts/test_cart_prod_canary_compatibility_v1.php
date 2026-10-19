<?php
/** Standalone compatibility decision test. No WordPress, Woo, network, payment or DB. */
declare(strict_types=1);
define('ABSPATH', __DIR__);
require dirname(__DIR__) . '/theme/beyourlover-v3/inc/cart-production-canary-gates.php';
require dirname(__DIR__) . '/theme/beyourlover-v3/inc/cart-production-canary-compatibility.php';

$checks = 0;
function check_compat(bool $ok, string $message): void {
    global $checks;
    if (!$ok) { fwrite(STDERR, "FAIL canary compatibility: ".$message."\n"); exit(1); }
    $checks++;
}
function is_legacy(array $result): bool {
    return $result['drawer'] === 'legacy' && $result['page'] === 'legacy';
}
$sha = str_repeat('a', 40);
$fingerprint = str_repeat('b', 64);
$base = [
    'host' => 'www.beyourlover.com',
    'wp_environment' => 'production',
    'master_enabled' => true,
    'incident_p0_cleared' => true,
    'commerce_e2e_cleared' => true,
    'active_theme_verified' => true,
    'legacy_fallback_verified' => true,
    'cache_isolation_verified' => true,
    'runtime_hash_verified' => true,
    'is_logged_in' => true,
    'can_manage_woocommerce' => true,
    'user_id' => 4,
    'allowed_staff_ids' => [4],
    'approved_release_sha' => $sha,
    'installed_release_sha' => $sha,
    'drawer_enabled' => true,
    'page_enabled' => false,

    // These are attestations made by a hypothetical verified WordPress adapter.
    // Never treat the source test as proof that any real WWW evidence exists.
    'legacy_cart_fragments_recovered' => true,
    'legacy_drawer_verified' => true,
    'legacy_cart_page_verified' => true,
    'www_post_hotfix_hashes_reconciled' => true,
    'legacy_restore_artifact_verified' => true,
    'www_theme_identity_attested' => true,
    'www_integration_point_attested' => true,
    'guest_after_staff_cache_test_pass' => true,
    'woocommerce_cart_session_isolated' => true,
    'checkout_route_untouched' => true,
    'live_theme_fingerprint' => $fingerprint,
    'approved_theme_fingerprint' => $fingerprint,
    'staff_html_uncacheable' => true,
    'cloudflare_guest_segregated' => true,
    'hostinger_guest_segregated' => true,
    'woo_fragments_legacy_preserved' => true,
    'drawer_adapter_attested' => true,
    'drawer_trigger_no_double_bind' => true,
    'drawer_woo_store_api_contract_pass' => true,
    'drawer_phase_owner_pass' => true,
    'page_template_route_attested' => true,
    'page_native_woo_cart_authority_attested' => true,
    'page_legacy_fallback_attested' => true,
    'page_empty_state_independently_gated' => true,
];

$go = static fn(array $c): array =>
    byl_v3_cart_prod_canary_compatibility_decision($c);

$drawer = $go($base);
check_compat($drawer['drawer']==='canary' && $drawer['page']==='legacy'
    && $drawer['reason']==='staff_drawer_only', 'staff-only Drawer remains separate');
$pageContext = array_replace($base,['drawer_enabled'=>false,'page_enabled'=>true]);
$page = $go($pageContext);
check_compat($page['drawer']==='legacy' && $page['page']==='canary'
    && $page['reason']==='staff_page_only', 'independent staff-only page after Drawer PASS');

check_compat(is_legacy($go(array_replace($base,['drawer_enabled'=>false]))), 'both surfaces OFF');
check_compat(is_legacy($go(array_replace($base,['page_enabled'=>true]))), 'both surfaces ON fail closed');
check_compat(is_legacy($go([])), 'empty runtime evidence fail closed');

$prerequisites = [
    'incident_p0_cleared','legacy_cart_fragments_recovered',
    'legacy_drawer_verified','legacy_cart_page_verified',
    'www_post_hotfix_hashes_reconciled','legacy_restore_artifact_verified',
    'www_theme_identity_attested','www_integration_point_attested',
    'guest_after_staff_cache_test_pass','woocommerce_cart_session_isolated',
    'checkout_route_untouched','staff_html_uncacheable',
    'cloudflare_guest_segregated','hostinger_guest_segregated',
    'woo_fragments_legacy_preserved',
    'drawer_adapter_attested','drawer_trigger_no_double_bind',
    'drawer_woo_store_api_contract_pass',
    'master_enabled','commerce_e2e_cleared','active_theme_verified',
    'legacy_fallback_verified','cache_isolation_verified',
    'runtime_hash_verified','is_logged_in','can_manage_woocommerce',
    'drawer_enabled'
];
foreach ($prerequisites as $field) {
    foreach ([false,null,'true',1,'1',0,[]] as $bad) {
        $v=$base; $v[$field]=$bad;
        check_compat(is_legacy($go($v)), $field.' fails closed on '.get_debug_type($bad));
    }
    $v=$base; unset($v[$field]);
    check_compat(is_legacy($go($v)), $field.' missing fails closed');
}
foreach ([
    'drawer_phase_owner_pass','page_template_route_attested',
    'page_native_woo_cart_authority_attested',
    'page_legacy_fallback_attested','page_empty_state_independently_gated'
] as $field) {
    foreach ([false,null,'true',1] as $bad) {
        $v=$pageContext; $v[$field]=$bad;
        check_compat(is_legacy($go($v)), 'page prerequisite '.$field.' fails closed');
    }
}
foreach ([
   'host'=>['dev.beyourlover.com', 'www.beyourlover.com.attacker.test'],
   'wp_environment'=>['staging','development'],
   'user_id'=>[5,'4',null],
   'allowed_staff_ids'=>[[5],[],['4']],
   'approved_release_sha'=>[str_repeat('c',40),str_repeat('a',39),''],
   'installed_release_sha'=>[str_repeat('c',40),'',null],
   'live_theme_fingerprint'=>[str_repeat('c',64), '', null],
   'approved_theme_fingerprint'=>[str_repeat('c',64), str_repeat('b',63), null]
] as $field=>$badValues) {
    foreach ($badValues as $bad) {
        $v=$base; $v[$field]=$bad;
        check_compat(is_legacy($go($v)), $field.' identity/fingerprint mismatch fails closed');
    }
}
$src = file_get_contents(dirname(__DIR__).'/theme/beyourlover-v3/inc/cart-production-canary-compatibility.php');
check_compat(is_string($src)
    && !str_contains($src, 'add_action(')
    && !str_contains($src, 'add_filter(')
    && !str_contains($src, '$_GET')
    && !str_contains($src, '$_POST')
    && !str_contains($src, '$_COOKIE'),
    'pure prototype has no hooks or request-derived activation');

foreach (['functions.php','inc/theme-os-bootstrap.php','inc/theme-os-assets.php',
          'inc/cart-drawer.php','inc/notification-cart.php'] as $runtime) {
    $source=file_get_contents(dirname(__DIR__).'/theme/beyourlover-v3/'.$runtime);
    check_compat(is_string($source)
        && !str_contains($source,'cart-production-canary-compatibility.php'),
        'new compatibility prototype not loaded at runtime: '.$runtime);
}
check_compat(str_contains(file_get_contents(
    dirname(__DIR__).'/theme/beyourlover-v3/inc/cart-drawer.php'),
    "dev.beyourlover.com"), 'legacy Dev-only Drawer still unchanged');

echo "PASS Cart compatibility source-only: ".$checks.
 " assertions; LEGACY_PUBLIC=UNCHANGED; WWW_CANARY=HOLD; zero transactions\n";
