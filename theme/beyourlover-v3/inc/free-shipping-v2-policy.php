<?php
/**
 * Read-only destination lookup for the BYL free-shipping information page.
 * Shipping and promotion configuration belongs to WooCommerce / Andy Commerce.
 * No session mutation, no orders, no option or migration writes.
 */
declare(strict_types=1);
defined('ABSPATH') || exit;

function byl_v3_fs_zone_free_method(int $zone_id, ?array $zones = null): bool {
    if (!class_exists('WC_Shipping_Zones')) { return false; }
    $zones = $zones ?? WC_Shipping_Zones::get_zones();
    foreach ((array)($zones[$zone_id]['shipping_methods'] ?? []) as $method) {
        if (!is_object($method)) { continue; }
        $type = method_exists($method,'get_method_id') ? (string)$method->get_method_id() : (string)($method->id ?? '');
        if ($type === 'free_shipping' && (string)($method->enabled ?? 'yes') !== 'no') { return true; }
    }
    return false;
}

function byl_v3_fs_zone_flat_cost(int $zone_id, ?array $zones = null): ?float {
    if (!class_exists('WC_Shipping_Zones')) { return null; }
    $zones = $zones ?? WC_Shipping_Zones::get_zones();
    foreach ((array)($zones[$zone_id]['shipping_methods'] ?? []) as $method) {
        if (!is_object($method)) { continue; }
        $type = method_exists($method,'get_method_id') ? (string)$method->get_method_id() : (string)($method->id ?? '');
        if ($type !== 'flat_rate' || (string)($method->enabled ?? 'yes') === 'no') { continue; }
        $cost = method_exists($method,'get_option') ? $method->get_option('cost') : null;
        return is_numeric($cost) ? (float)$cost : null;
    }
    return null;
}

function byl_v3_fs_destination_policy(string $country, string $state = ''): array {
    $country = strtoupper(trim($country));
    $state = strtoupper(trim($state));
    $neutral = ['status'=>'unknown','zone_id'=>null,'threshold'=>null,'base_flat_cost'=>null];
    if (!function_exists('WC') || !WC() || !isset(WC()->countries) ||
        !class_exists('WC_Shipping_Zones') || !class_exists('Andy_Commerce_Shipping_Promotion_Policy')) { return $neutral; }
    $shipping_countries = (array)WC()->countries->get_shipping_countries();
    if (!isset($shipping_countries[$country])) { return ['status'=>'not_served'] + $neutral; }
    // Subnational routing is essential: Alaska/Hawaii differ from continental US.
    if ($country === 'US') {
        $states = (array)WC()->countries->get_states('US');
        if (!isset($states[$state])) { return ['status'=>'needs_state'] + $neutral; }
    }
    $zone = WC_Shipping_Zones::get_zone_matching_package([
        'destination'=>['country'=>$country, 'state'=>$state, 'postcode'=>'', 'city'=>''],
        'contents'=>[],
    ]);
    $zone_id = $zone && method_exists($zone, 'get_id') ? (int)$zone->get_id() : 0;
    $zones = WC_Shipping_Zones::get_zones();
    // Zone 0 has no Free Shipping method on Dev. Do not infer availability
    // from the historical default amount ($49 vs $69).
    if (!byl_v3_fs_zone_free_method($zone_id, $zones)) {
        return ['status'=>'no_free_method','zone_id'=>$zone_id,'threshold'=>null,'base_flat_cost'=>null];
    }
    $policy = 'Andy_Commerce_Shipping_Promotion_Policy';
    if (!$policy::has_persisted_settings()) { return $neutral; }
    $threshold = $policy::get_persisted_zone_effective_threshold($zone_id);
    if (!is_numeric($threshold)) { return $neutral; }
    return [
        'status'=>'free_method_configured',
        'zone_id'=>$zone_id,
        'threshold'=>(float)$threshold,
        'base_flat_cost'=>byl_v3_fs_zone_flat_cost($zone_id,$zones),
    ];
}

function byl_v3_fs_lookup_ajax(): void {
    if (!check_ajax_referer('byl_v3_fs_lookup', 'nonce', false)) {
        wp_send_json_error(['message'=>'Please reload the page and try again.'], 403);
    }
    $country = isset($_POST['country']) && is_string($_POST['country'])
        ? strtoupper(sanitize_text_field(wp_unslash($_POST['country']))) : '';
    $state = isset($_POST['state']) && is_string($_POST['state'])
        ? strtoupper(sanitize_text_field(wp_unslash($_POST['state']))) : '';
    if (strlen($country) !== 2 || strlen($state) > 6) {
        wp_send_json_error(['message'=>'Choose a valid destination.'], 400);
    }
    wp_send_json_success(byl_v3_fs_destination_policy($country, $state));
}
add_action('wp_ajax_byl_v3_fs_lookup','byl_v3_fs_lookup_ajax');
add_action('wp_ajax_nopriv_byl_v3_fs_lookup','byl_v3_fs_lookup_ajax');
