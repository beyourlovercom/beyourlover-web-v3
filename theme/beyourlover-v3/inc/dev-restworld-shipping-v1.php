<?php
/**
 * BYL Rest of World shipping policy experiment — DEV/STAGING ONLY.
 *
 * Ordinary post-coupon eligible goods: < $69 -> Woo configured $20 Flat Rate;
 * >= $69 -> core Woo Free Shipping only. Three existing class-1939 products
 * preserve $30 once per special shipment (NOT $30 per item). PILLOW remains
 * owned by the existing separate $30/unit Dev adapter.
 *
 * This file must never be activated by domain alone. Explicit Dev option
 * byl_dev_restworld_shipping_active_v1 must also be "yes".
 * No Woo settings, coupon, order, DB, tax table, or global plugin writes here.
 */
declare(strict_types=1);
defined('ABSPATH') || exit;

function byl_dev_restworld_active(): bool {
    return function_exists('home_url')
        && function_exists('wp_parse_url')
        && function_exists('wp_get_environment_type')
        && wp_parse_url(home_url('/'), PHP_URL_HOST) === 'dev.beyourlover.com'
        && wp_get_environment_type() === 'staging'
        && function_exists('WC')
        && class_exists('WC_Shipping_Zones')
        && class_exists('WC_Shipping_Rate')
        && function_exists('byl_dev_pillow_shipping_is_pillow')
        && function_exists('byl_dev_pillow_shipping_enabled')
        && byl_dev_pillow_shipping_enabled()
        && get_option('byl_dev_restworld_shipping_active_v1', 'no') === 'yes';
}

function byl_dev_restworld_special(array $item): bool {
    // IDs explicitly authorized; never convert other products by class alone.
    $id = (int) ($item['product_id'] ?? 0);
    if (in_array($id, [9145, 9153, 32251], true)) {
        return true;
    }
    $product = $item['data'] ?? null;
    return is_object($product)
        && method_exists($product, 'get_parent_id')
        && in_array((int) $product->get_parent_id(), [9145, 9153, 32251], true);
}

function byl_dev_restworld_pillow(array $item): bool {
    // Recognize the actual PILLOW parent even if the old helper disappears.
    $id = (int) ($item['product_id'] ?? 0);
    $product = $item['data'] ?? null;
    if ($id === 38047 || (is_object($product)
        && method_exists($product, 'get_parent_id')
        && (int) $product->get_parent_id() === 38047)) {
        return true;
    }
    return function_exists('byl_dev_pillow_shipping_is_pillow')
        && byl_dev_pillow_shipping_is_pillow($item);
}

function byl_dev_restworld_sum(array $items): float {
    $total = 0.0;
    foreach ($items as $item) {
        if (is_array($item)) {
            $total += max(0.0, (float) ($item['line_total'] ?? 0));
        }
    }
    return round($total, 2);
}

function byl_dev_restworld_zone0(array $package): bool {
    if (!byl_dev_restworld_active()
        || !is_array($package['destination'] ?? null)) {
        return false;
    }
    // Woo is the destination authority. Never assume that GB/FR or a shipping
    // country is in zone0 merely because of its code.
    $zone = WC_Shipping_Zones::get_zone_matching_package($package);
    return is_object($zone) && method_exists($zone, 'get_id')
        && (int) $zone->get_id() === 0;
}

/** Run after PILLOW's priority-900 splitter, preserving its dedicated package. */
function byl_dev_restworld_packages(array $packages): array {
    if (!byl_dev_restworld_active()) {
        return $packages;
    }
    $out = [];
    foreach ($packages as $package) {
        if (!is_array($package)
            || !is_array($package['contents'] ?? null)
            || ($package['byl_dev_pillow_kind'] ?? '') === 'pillow'
            || !byl_dev_restworld_zone0($package)) {
            $out[] = $package;
            continue;
        }
        // The pre-existing PR150 priority-900 PILLOW splitter must already
        // have taken pillow items into their own package at this point.
        $unsplitPillow = false;
        foreach ($package['contents'] as $item) {
            if (is_array($item) && byl_dev_restworld_pillow($item)) {
                $unsplitPillow = true;
                break;
            }
        }
        if ($unsplitPillow) {
            $package['byl_dev_restworld_kind'] = 'unsafe_unsplit_pillow';
            $out[] = $package;
            continue;
        }
        $ordinary = [];
        $special = [];
        foreach ($package['contents'] as $key => $item) {
            if (is_array($item) && byl_dev_restworld_special($item)) {
                $special[$key] = $item;
            } else {
                $ordinary[$key] = $item;
            }
        }
        if (!$special) {
            $package['byl_dev_restworld_kind'] = 'ordinary';
            $out[] = $package;
            continue;
        }
        if ($ordinary) {
            $normal = $package;
            $normal['contents'] = $ordinary;
            $normal['contents_cost'] = byl_dev_restworld_sum($ordinary);
            $normal['byl_dev_restworld_kind'] = 'ordinary';
            $out[] = $normal;
        }
        $surcharge = $package;
        $surcharge['contents'] = $special;
        $surcharge['contents_cost'] = byl_dev_restworld_sum($special);
        $surcharge['byl_dev_restworld_kind'] = 'special30';
        $out[] = $surcharge;
    }
    return $out;
}

function byl_dev_restworld_flat_rate(array $rates) {
    foreach ($rates as $rate) {
        if (is_object($rate)
            && method_exists($rate, 'get_method_id')
            && $rate->get_method_id() === 'flat_rate'
            && method_exists($rate, 'get_id')) {
            return clone $rate;
        }
    }
    return null;
}

function byl_dev_restworld_set_shipping_tax($rate, float $cost): void {
    if (!method_exists($rate, 'set_taxes')) {
        return;
    }
    $taxes = [];
    if (class_exists('WC_Tax')
        && method_exists('WC_Tax', 'get_shipping_tax_rates')
        && method_exists('WC_Tax', 'calc_shipping_tax')) {
        $taxes = WC_Tax::calc_shipping_tax(
            $cost, WC_Tax::get_shipping_tax_rates()
        );
    }
    $rate->set_taxes(is_array($taxes) ? $taxes : []);
}

/**
 * Final scope-limited Woo rate filter: never generate an unconfigured
 * destination method. Correct subtotal based on actual discounted line_total,
 * even if older BYL/Andy pre-discount hooks offered an invalid free rate.
 */
function byl_dev_restworld_rates(array $rates, array $package): array {
    if (!byl_dev_restworld_zone0($package)
        || ($package['byl_dev_pillow_kind'] ?? '') === 'pillow') {
        return $rates;
    }
    if (($package['byl_dev_restworld_kind'] ?? '') === 'special30') {
        $flat = byl_dev_restworld_flat_rate($rates);
        if (!$flat) {
            return []; // Fail closed, do not invent a paid shipping method.
        }
        if (method_exists($flat, 'set_cost')) {
            $flat->set_cost(30.0);
        }
        if (method_exists($flat, 'set_label')) {
            $flat->set_label('Extra $30 Shipping');
        }
        byl_dev_restworld_set_shipping_tax($flat, 30.0);
        return [$flat->get_id() => $flat];
    }
    $eligible = byl_dev_restworld_sum(
        (array) ($package['contents'] ?? [])
    ) + 0.000001 >= 69.0;
    $free = [];
    $flat = [];
    $other = [];
    foreach ($rates as $key => $rate) {
        $method = is_object($rate) && method_exists($rate, 'get_method_id')
            ? (string) $rate->get_method_id() : '';
        if ($method === 'free_shipping'
            && method_exists($rate, 'get_cost')
            && (float) $rate->get_cost() === 0.0) {
            $free[$key] = $rate;
        } elseif ($method === 'flat_rate') {
            $flat[$key] = $rate;
        } else {
            $other[$key] = $rate;
        }
    }
    // Leave local pickup and future Woo methods alone; enforce regular rates
    // only when an actual Woo free method is present.
    if ($eligible && $free) {
        return $free + $other;
    }
    return $flat + $other;
}

/** Correct the existing promotion presentation; Woo monetary rates stay source. */
function byl_dev_restworld_promo_correct(array $promo, array $cart): array {
    if (empty($promo['active'])
        || (int) ($promo['zone_id'] ?? -1) !== 0) {
        return $promo;
    }
    $regular = [];
    foreach ($cart as $k => $item) {
        if (!is_array($item)
            || byl_dev_restworld_special($item)
            || byl_dev_restworld_pillow($item)) {
            continue;
        }
        $regular[$k] = $item;
    }
    $amount = byl_dev_restworld_sum($regular);
    $qualified = $amount + 0.000001 >= 69.0;
    $mode = (string) ($promo['mode'] ?? 'automatic');
    $code = (string) ($promo['applied_code'] ?? '');
    $codeOk = !empty($promo['policy_code_applied']);
    $promo['threshold'] = 69.0;
    $promo['subtotal'] = $amount;
    $promo['remaining'] = max(0.0, round(69.0 - $amount, 2));
    $promo['eligible'] = $qualified;
    $promo['unlocked'] = $qualified
        && ($mode !== 'require_code' || $codeOk);
    if (!$qualified) {
        $promo['state'] = $mode === 'require_code' && $codeOk
            ? 'require_code_applied' : 'below_threshold';
    } elseif ($mode !== 'require_code') {
        $promo['state'] = $code !== ''
            ? 'automatic_code' : 'automatic_unlocked';
    } elseif ($codeOk) {
        $promo['state'] = 'require_code_unlocked';
    } else {
        $promo['state'] = $code !== ''
            ? 'require_code_other' : 'require_code_available';
    }
    return $promo;
}

function byl_dev_restworld_store_response($response, $server, $request) {
    if (!byl_dev_restworld_active()
        || !is_object($request) || !method_exists($request, 'get_route')
        || !preg_match('#^/wc/store/v1/cart(?:/[^/?]+)?$#D',
            (string) $request->get_route())
        || !is_object($response) || !method_exists($response, 'get_data')
        || !method_exists($response, 'set_data')
        || !WC() || !is_object(WC()->cart ?? null)
        || !method_exists(WC()->cart, 'get_cart')) {
        return $response;
    }
    $data = $response->get_data();
    if (!is_array($data)) {
        return $response;
    }
    $rawRegistry = $data['extensions'] ?? null;
    $registry = is_object($rawRegistry)
        ? get_object_vars($rawRegistry) : $rawRegistry;
    if (!is_array($registry)) {
        return $response;
    }
    $rawPromo = $registry['byl-shipping-promotion'] ?? null;
    $promo = is_object($rawPromo) ? get_object_vars($rawPromo) : $rawPromo;
    if (!is_array($promo)) {
        return $response;
    }
    $fixed = byl_dev_restworld_promo_correct(
        $promo, (array) WC()->cart->get_cart()
    );
    if ($fixed !== $promo) {
        $registry['byl-shipping-promotion'] = is_object($rawPromo)
            ? (object) $fixed : $fixed;
        $data['extensions'] = is_object($rawRegistry)
            ? (object) $registry : $registry;
        $response->set_data($data);
    }
    return $response;
}

function byl_dev_restworld_boot(): void {
    if (!byl_dev_restworld_active()) {
        return;
    }
    add_filter('woocommerce_cart_shipping_packages', 'byl_dev_restworld_packages', 950, 1);
    add_filter('woocommerce_package_rates', 'byl_dev_restworld_rates', 100010, 2);
    add_filter('rest_post_dispatch', 'byl_dev_restworld_store_response', 10001, 3);
}
add_action('woocommerce_init', 'byl_dev_restworld_boot', 101);
