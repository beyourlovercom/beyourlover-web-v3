<?php
/**
 * Read-only destination lookup for the BYL free-shipping information page.
 * Shipping and promotion configuration belongs to WooCommerce / Andy Commerce.
 * No session mutation, no orders, no option or migration writes.
 */
declare(strict_types=1);
defined('ABSPATH') || exit;

/**
 * WC_Shipping_Zones::get_zones() excludes the fallback Zone 0. Read it
 * explicitly through Woo core, so the public country policy never reports
 * "no free shipping" when the real Rest of World fallback has an enabled
 * Woo Free Shipping instance.
 */
function byl_v3_fs_zone_methods(int $zone_id, ?array $zones = null): array {
    if (!class_exists('WC_Shipping_Zones')) { return []; }
    if ($zone_id === 0) {
        $fallback = WC_Shipping_Zones::get_zone(0);
        return is_object($fallback) && method_exists($fallback, 'get_shipping_methods')
            ? (array) $fallback->get_shipping_methods(true) : [];
    }
    $zones = $zones ?? WC_Shipping_Zones::get_zones();
    return (array) ($zones[$zone_id]['shipping_methods'] ?? []);
}

function byl_v3_fs_zone_free_method(int $zone_id, ?array $zones = null): bool {
    foreach (byl_v3_fs_zone_methods($zone_id, $zones) as $method) {
        if (!is_object($method)) { continue; }
        $type = method_exists($method,'get_method_id') ? (string)$method->get_method_id() : (string)($method->id ?? '');
        if ($type === 'free_shipping' && (string)($method->enabled ?? 'yes') !== 'no') { return true; }
    }
    return false;
}

function byl_v3_fs_zone_flat_cost(int $zone_id, ?array $zones = null): ?float {
    foreach (byl_v3_fs_zone_methods($zone_id, $zones) as $method) {
        if (!is_object($method)) { continue; }
        $type = method_exists($method,'get_method_id') ? (string)$method->get_method_id() : (string)($method->id ?? '');
        if ($type !== 'flat_rate' || (string)($method->enabled ?? 'yes') === 'no') { continue; }
        // Instance-specific Woo Flat Rate config is authoritative; do not
        // infer base cost from the shipping zone name or another method.
        $cost = method_exists($method,'get_instance_option')
            ? $method->get_instance_option('cost') : null;
        if (!is_numeric($cost) && method_exists($method,'get_option')) {
            $cost = $method->get_option('cost');
        }
        return is_numeric($cost) ? (float)$cost : null;
    }
    return null;
}

/**
 * Return the real Woo Free Shipping requirement, never merely a threshold
 * inferred from a plugin's marketing label. Unknown/coupon-OR-min policies
 * do not claim a universal amount qualification.
 */
function byl_v3_fs_zone_free_terms(int $zone_id, ?array $zones = null): ?array {
    foreach (byl_v3_fs_zone_methods($zone_id, $zones) as $method) {
        if (!is_object($method)
            || (string)($method->enabled ?? 'yes') === 'no') {
            continue;
        }
        $type = method_exists($method, 'get_method_id')
            ? (string)$method->get_method_id()
            : (string)($method->id ?? '');
        if ($type !== 'free_shipping') { continue; }
        $value = static function (string $key) use ($method) {
            if (method_exists($method,'get_instance_option')) {
                return $method->get_instance_option($key);
            }
            return method_exists($method,'get_option')
                ? $method->get_option($key) : null;
        };
        $requires = (string) $value('requires');
        if ($requires === '' || $requires === 'no') {
            return ['threshold'=>0.0,'basis'=>'no_minimum'];
        }
        if ($requires === 'min_amount' && is_numeric($value('min_amount'))) {
            return [
                'threshold'=>(float)$value('min_amount'),
                'basis'=>$value('ignore_discounts') === 'yes'
                    ? 'before_coupon' : 'after_coupon',
            ];
        }
        // A code requirement or "either" must not be advertised as plain
        // amount-only free shipping.
        return null;
    }
    return null;
}

function byl_v3_fs_destination_policy(string $country, string $state = ''): array {
    $country = strtoupper(trim($country));
    $state = strtoupper(trim($state));
    $neutral = ['status'=>'unknown','zone_id'=>null,'threshold'=>null,'base_flat_cost'=>null];
    if (!function_exists('WC') || !WC() || !isset(WC()->countries) ||
        !class_exists('WC_Shipping_Zones')) { return $neutral; }
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
    // Zone 0 is queried directly: both Dev and WWW may have Woo Free
    // Shipping enabled with different instance IDs and staging states.
    if (!byl_v3_fs_zone_free_method($zone_id, $zones)) {
        return ['status'=>'no_free_method','zone_id'=>$zone_id,'threshold'=>null,'base_flat_cost'=>null];
    }
    $terms = byl_v3_fs_zone_free_terms($zone_id, $zones);
    if ($terms === null) {
        return ['status'=>'conditional_free',
            'zone_id'=>$zone_id,'threshold'=>null,'base_flat_cost'=>byl_v3_fs_zone_flat_cost($zone_id,$zones)];
    }
    return [
        'status'=>'free_method_configured',
        'zone_id'=>$zone_id,
        'threshold'=>(float)$terms['threshold'],
        'basis'=>$terms['basis'],
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
