<?php
/** Dev-only owner-scoped rollback; requires explicit backup snapshot env. */
declare(strict_types=1);
if (!defined('ABSPATH')
    || rtrim(home_url('/'), '/') !== 'https://dev.beyourlover.com'
    || wp_get_environment_type() !== 'staging'
    || !defined('WP_CLI') || !WP_CLI
    || !class_exists('WC_Shipping_Zones')) {
    throw new RuntimeException('DENIED non-Dev rollback');
}
$file = getenv('BYL_DEV_RESTWORLD_ROLLBACK_SNAPSHOT') ?: '';
$prefix = '/home/u247343654/.byl-dev-restworld-config-v1/snapshot-';
if (!str_starts_with($file,$prefix)
    || !preg_match('/^\/home\/u247343654\/\.byl-dev-restworld-config-v1\/snapshot-[0-9]{14}-[a-f0-9]{8}\.json$/D',$file)
    || !is_file($file)) {
    throw new RuntimeException('DENIED missing explicit restricted snapshot');
}
$data = json_decode((string)file_get_contents($file),true);
if (!is_array($data) || ($data['identity'] ?? '') !== 'https://dev.beyourlover.com'
    || !is_array($data['flat_option'] ?? null)
    || !is_array($data['byl_option'] ?? null)
    || !is_array($data['andy_option'] ?? null)) {
    throw new RuntimeException('DENIED malformed restricted snapshot');
}
$freeId=(int)($data['new_free_instance_id'] ?? 0);
if ($freeId<=0) {
    throw new RuntimeException('DENIED snapshot lacks new Woo instance');
}
$flatKey='woocommerce_flat_rate_3_settings';
$bylKey='byl_shipping_promotion_settings';
$andyKey='andy_commerce_shipping_promotion_settings_v1';
$flagKey='byl_dev_restworld_shipping_active_v1';
$freeKey='woocommerce_free_shipping_'.$freeId.'_settings';
$flat=get_option($flatKey,[]);
$byl=get_option($bylKey,[]);
$andy=get_option($andyKey,[]);
$free=get_option($freeKey,[]);
if (!is_array($flat) || (string)($flat['cost']??'')!=='20'
    || !is_array($byl)
    || (string)($byl['zone_overrides']['0']['mode']??'')!=='override'
    || (float)($byl['zone_overrides']['0']['threshold']??0)!==69.0
    || !is_array($andy)
    || (string)($andy['zone_overrides']['0']['mode']??'')!=='override'
    || (float)($andy['zone_overrides']['0']['threshold']??0)!==69.0
    || !is_array($free)
    || ($free['requires']??null)!=='min_amount'
    || (string)($free['min_amount']??'')!=='69'
    || get_option($flagKey,'no')!=='yes') {
    throw new RuntimeException('DENIED Dev config drift — do not overwrite');
}
$zone=WC_Shipping_Zones::get_zone(0);
$hasNew=false;
foreach ($zone->get_shipping_methods(false) as $method) {
    if ((int)$method->get_instance_id()===$freeId
        && $method->id==='free_shipping') {
        $hasNew=true;
    }
}
if (!$hasNew) { throw new RuntimeException('DENIED new method mismatch'); }
$zone->delete_shipping_method($freeId);
delete_option($freeKey);
update_option($flatKey,$data['flat_option'],false);
update_option($bylKey,$data['byl_option'],false);
update_option($andyKey,$data['andy_option'],false);
if (($data['flag_option']??null)===null) {
    delete_option($flagKey);
} else {
    update_option($flagKey,$data['flag_option'],false);
}
if (class_exists('WC_Cache_Helper')
    && method_exists('WC_Cache_Helper','invalidate_cache_group')) {
    WC_Cache_Helper::invalidate_cache_group('shipping');
}
echo "DEV_RESTWORLD_ROLLBACK_PASS restored_original_options_and_removed_free_instance\n";
