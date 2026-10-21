<?php
/**
 * Controlled DEV-ONLY Woo native shipping settings migration.
 * Invoke via WP-CLI (Woo plugins loaded, theme skipped):
 *   BYL_DEV_RESTWORLD_PHASE=inspect wp --path=DEV --skip-themes eval-file THIS
 *   BYL_DEV_RESTWORLD_PHASE=apply   wp --path=DEV --skip-themes eval-file THIS
 *
 * CLI defaults to INSPECT. No orders/payments/emails or WWW writes.
 * Apply first saves a restricted JSON backup outside the WordPress document root.
 * This migration is single-use; never auto-applied by GitHub or a theme hook.
 */
declare(strict_types=1);
if (!defined('ABSPATH')
    || !function_exists('home_url')
    || rtrim(home_url('/'), '/') !== 'https://dev.beyourlover.com'
    || !function_exists('wp_get_environment_type')
    || wp_get_environment_type() !== 'staging'
    || !defined('WP_CLI')
    || !WP_CLI
    || !class_exists('WC_Shipping_Zone')
    || !class_exists('WC_Shipping_Zones')
    || !function_exists('get_option')) {
    throw new RuntimeException('DENIED: Dev+staging+Woo+WPCLI required');
}
$phase = getenv('BYL_DEV_RESTWORLD_PHASE') ?: 'inspect';
if (!in_array($phase, ['inspect','apply'], true)) {
    throw new RuntimeException('DENIED: unsupported operation');
}
$zone = WC_Shipping_Zones::get_zone(0);
if (!is_object($zone)
    || !method_exists($zone, 'add_shipping_method')
    || !method_exists($zone, 'delete_shipping_method')) {
    throw new RuntimeException('DENIED: missing Woo native zone mutation API');
}
$methods = $zone->get_shipping_methods(false);
$active = [];
foreach ($methods as $method) {
    $active[] = [
        'type' => (string) ($method->id ?? ''),
        'id' => (int) $method->get_instance_id(),
        'enabled' => (string) ($method->enabled ?? ''),
    ];
}
$flatKey='woocommerce_flat_rate_3_settings';
$oldFlat = get_option($flatKey, null);
$bylKey='byl_shipping_promotion_settings';
$andyKey='andy_commerce_shipping_promotion_settings_v1';
$oldBYL = get_option($bylKey, null);
$oldAndy = get_option($andyKey, null);
$flagKey='byl_dev_restworld_shipping_active_v1';
$oldFlag = get_option($flagKey, null);
if (!is_array($oldFlat) || !is_array($oldBYL) || !is_array($oldAndy)) {
    throw new RuntimeException('DENIED: missing existing settings arrays');
}
$check = static function(bool $passed,string $label): void {
    if (!$passed) {
        throw new RuntimeException('DENIED: drift at '.$label);
    }
};
$check(count($active) === 1
    && $active[0]['type'] === 'flat_rate'
    && $active[0]['id'] === 3
    && $active[0]['enabled'] === 'yes', 'zone0 exactly one flat rate');
$check((string)($oldFlat['cost'] ?? '') === '0', 'flat3 original cost zero');
$check((string)($oldFlat['no_class_cost'] ?? '') === '0',
    'flat3 no class original');
$check((string)($oldFlat['type'] ?? '') === 'class', 'flat3 per class');
foreach ([932=>'0',1874=>'10',1939=>'30'] as $id=>$amount) {
    $check((string)($oldFlat['class_cost_'.$id] ?? '') === $amount,
        'class cost '.$id);
}
$check(($oldBYL['schema_version'] ?? null) === 1
    && ($oldBYL['mode'] ?? null) === 'automatic'
    && (float)($oldBYL['global_threshold'] ?? -1) === 49.0,
    'BYL historic global49');
$check(($oldAndy['schema_version'] ?? null) === 1
    && ($oldAndy['mode'] ?? null) === 'automatic'
    && (float)($oldAndy['global_threshold'] ?? -1) === 69.0,
    'Andy global69');
foreach ([['BYL',$oldBYL],['Andy',$oldAndy]] as [$name,$settings]) {
    $override=$settings['zone_overrides']['0'] ?? null;
    $check(is_array($override)
        && ($override['mode'] ?? '') === 'global',
        $name.' zone0 original global');
}
$check($oldFlag !== 'yes', 'new Dev feature already active');
$check(is_file(
    WP_CONTENT_DIR.'/themes/beyourlover-v3/inc/dev-restworld-shipping-v1.php'
), 'new Dev-only theme adapter file staged');
echo json_encode([
    'phase'=>$phase,
    'identity'=>'https://dev.beyourlover.com',
    'zone0_existing'=>$active,
    'old_flat_base'=>(string)$oldFlat['cost'],
    'old_extra_class_1939'=>(string)$oldFlat['class_cost_1939'],
    'old_byl_zone0'=>'global49',
    'old_andy_zone0'=>'global69',
    'new_adapter_flag'=>$oldFlag,
    'plan'=>'flat3.base20; new Woo free(min69,after_coupon); BYL+Andy zone0 override69; activate last'
], JSON_UNESCAPED_SLASHES).PHP_EOL;
if ($phase === 'inspect') {
    echo "DEV_RESTWORLD_CONFIG_PREFLIGHT_PASS_NO_WRITES\n";
    return;
}
$dir='/home/u247343654/.byl-dev-restworld-config-v1';
if (!is_dir($dir) && !mkdir($dir,0700,true)) {
    throw new RuntimeException('DENIED: cannot create Dev offwebroot backup dir');
}
$check(is_writable($dir),'backup location writability');
$backup=$dir.'/snapshot-'.gmdate('YmdHis').'-'.bin2hex(random_bytes(4)).'.json';
$state=[
    'identity'=>'https://dev.beyourlover.com',
    'flat_option'=>$oldFlat,
    'byl_option'=>$oldBYL,
    'andy_option'=>$oldAndy,
    'flag_option'=>$oldFlag,
    'pre_methods'=>$active,
    'new_free_instance_id'=>null,
];
$persist = static function(array $data) use ($backup): void {
    $json=json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
    if (!is_string($json) || file_put_contents($backup,$json,LOCK_EX)===false) {
        throw new RuntimeException('DENIED: cannot save Dev option snapshot');
    }
    @chmod($backup,0600);
};
$persist($state);
$newInstance = null;
try {
    $nextFlat=$oldFlat;
    $nextFlat['cost']='20';
    $check(update_option($flatKey,$nextFlat,false),
        'Woo Flat cost20 update');
    $newInstance = (int)$zone->add_shipping_method('free_shipping');
    $check($newInstance > 0,'Woo Free Shipping add');
    $state['new_free_instance_id']=$newInstance;
    $persist($state); // crash-recovery pointer for newly inserted Woo method
    $freeKey='woocommerce_free_shipping_'.$newInstance.'_settings';
    $check(get_option($freeKey,null)===null,
        'new Free instance options must not preexist');
    $check(update_option($freeKey,[
        'title'=>'Free shipping',
        'requires'=>'min_amount',
        'min_amount'=>'69',
        'ignore_discounts'=>'no',
    ],false),'Woo amount-gated Free shipping save');
    foreach ([[$bylKey,$oldBYL],[$andyKey,$oldAndy]] as [$key,$existing]) {
        $next=$existing;
        $next['zone_overrides']['0']['mode']='override';
        $next['zone_overrides']['0']['threshold']=69.0;
        $check(update_option($key,$next,false),
            'Zone0 threshold override '.$key);
    }
    $check(update_option($flagKey,'yes',false),'Dev adapter activate LAST');
    if (class_exists('WC_Cache_Helper')
        && method_exists('WC_Cache_Helper','invalidate_cache_group')) {
        WC_Cache_Helper::invalidate_cache_group('shipping');
    }
    echo json_encode([
        'result'=>'DEV_RESTWORLD_CONFIG_APPLY_PASS',
        'zone0_flat_instance'=>3,
        'zone0_base'=>20,
        'new_free_instance'=>$newInstance,
        'free_requirement'=>'min_amount',
        'min_amount'=>69,
        'ignore_discounts'=>'no',
        'special_1939_original_extra'=>30,
        'byl_andy_zone0_override'=>69,
        'active'=>true,
        'backup_path'=>$backup
    ],JSON_UNESCAPED_SLASHES).PHP_EOL;
} catch (Throwable $error) {
    // All rollback operations are confined to the verified Dev options/zone.
    if ($newInstance !== null && $newInstance > 0) {
        try { $zone->delete_shipping_method($newInstance); } catch (Throwable $ignored) {}
        delete_option('woocommerce_free_shipping_'.$newInstance.'_settings');
    }
    update_option($flatKey,$oldFlat,false);
    update_option($bylKey,$oldBYL,false);
    update_option($andyKey,$oldAndy,false);
    if ($oldFlag===null) { delete_option($flagKey); }
    else { update_option($flagKey,$oldFlag,false); }
    echo "DEV_RESTWORLD_CONFIG_ROLLBACK_ATTEMPTED\n";
    throw $error;
}
