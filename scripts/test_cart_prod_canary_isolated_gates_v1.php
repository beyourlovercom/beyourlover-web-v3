<?php
/** Offline PHP matrix: production Canary checker, no WP bootstrap or Woo writes. */
declare(strict_types=1);
define('ABSPATH', __DIR__);

require dirname(__DIR__).'/theme/beyourlover-v3/inc/cart-production-canary-gates.php';

$checks = 0;
function check_canary(bool $ok, string $label): void {
    global $checks;
    if (!$ok) { fwrite(STDERR, "FAIL Cart Canary gate: ".$label."\n"); exit(1); }
    $checks++;
}

$sha = str_repeat('a', 40);
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
    'user_id' => 14,
    'allowed_staff_ids' => [14, 22],
    'approved_release_sha' => $sha,
    'installed_release_sha' => $sha,
    'drawer_enabled' => true,
    'page_enabled' => false,
];

$eligible = static fn(string $surface, array $c): bool =>
    byl_v3_cart_prod_canary_eligible($surface, $c);
check_canary($eligible('drawer', $base), 'explicit staff-only drawer path positive');
check_canary(!$eligible('page', $base), 'page remains OFF when drawer ON');
check_canary(!$eligible('checkout', $base), 'Checkout cannot be controlled here');
check_canary(!$eligible('', $base), 'unknown surface always OFF');

$page = array_replace($base, ['drawer_enabled'=>false, 'page_enabled'=>true]);
check_canary($eligible('page', $page), 'page independently ON for authorized staff');
check_canary(!$eligible('drawer', $page), 'drawer independently OFF when page ON');

$bothOff = array_replace($base, ['drawer_enabled'=>false, 'page_enabled'=>false]);
check_canary(!$eligible('drawer', $bothOff) && !$eligible('page', $bothOff), 'both OFF');
$bothOn = array_replace($base, ['drawer_enabled'=>true, 'page_enabled'=>true]);
check_canary($eligible('drawer', $bothOn) && $eligible('page', $bothOn),
    'only separately enabled surfaces are eligible');

foreach ([
    'master_enabled','incident_p0_cleared','commerce_e2e_cleared',
    'active_theme_verified','legacy_fallback_verified',
    'cache_isolation_verified','runtime_hash_verified',
    'is_logged_in','can_manage_woocommerce','drawer_enabled',
] as $required) {
    foreach ([false, null, 1, '1', 'true', '', []] as $bad) {
        $c = $base;
        $c[$required] = $bad;
        check_canary(!$eligible('drawer', $c), $required.' fails closed for '.get_debug_type($bad));
    }
    $c = $base;
    unset($c[$required]);
    check_canary(!$eligible('drawer', $c), 'missing '.$required.' fails closed');
}

foreach (['dev.beyourlover.com','WWW.beyourlover.com','www.beyourlover.com.evil.test','',null] as $bad) {
    $c = $base; $c['host'] = $bad;
    check_canary(!$eligible('drawer', $c), 'exact WWW host boundary');
}
foreach (['staging','development','',null] as $bad) {
    $c = $base; $c['wp_environment'] = $bad;
    check_canary(!$eligible('drawer', $c), 'WP environment must be production');
}
foreach ([0,-3,'14',null,22,999] as $bad) {
    $c = $base; $c['user_id'] = $bad; $c['allowed_staff_ids'] = [14];
    check_canary(!$eligible('drawer', $c), 'strict explicit staff identity');
}
foreach ([[],null,['14'],['22'],['not-a-user']] as $bad) {
    $c = $base; $c['allowed_staff_ids'] = $bad;
    check_canary(!$eligible('drawer', $c), 'invalid staff allowlist fails closed');
}
foreach (['',null,str_repeat('b',40),'f00','AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA','01234567890123456789012345678901234567890'] as $bad) {
    $c = $base; $c['approved_release_sha'] = $bad;
    check_canary(!$eligible('drawer', $c), 'invalid or mismatched approved release');
    $c = $base; $c['installed_release_sha'] = $bad;
    check_canary(!$eligible('drawer', $c), 'invalid or mismatched installed release');
}

$source = file_get_contents(
    dirname(__DIR__).'/theme/beyourlover-v3/inc/cart-production-canary-gates.php'
);
check_canary(is_string($source) && !str_contains($source, '$_GET')
    && !str_contains($source, '$_POST') && !str_contains($source, '$_COOKIE')
    && !str_contains($source, 'add_action(')
    && !str_contains($source, 'add_filter('),
    'helper has no request-activated settings or WordPress hooks');

foreach (['functions.php', 'inc/theme-os-bootstrap.php', 'inc/theme-os-assets.php',
          'inc/cart-drawer.php', 'inc/notification-cart.php'] as $entry) {
    $text = file_get_contents(dirname(__DIR__).'/theme/beyourlover-v3/'.$entry);
    check_canary(is_string($text)
        && !str_contains($text, 'cart-production-canary-gates.php'),
        'runtime not wired: '.$entry);
}

check_canary(str_contains(file_get_contents(
    dirname(__DIR__).'/theme/beyourlover-v3/inc/cart-drawer.php'
), "\$host === 'dev.beyourlover.com'"), 'existing cart still Dev-only');

echo "PASS Cart Canary isolated eligibility: ".$checks.
     " assertions; PRODUCTION_RUNTIME=UNCHANGED; PRODUCTION_CANARY=HOLD; 0 transactions\n";
