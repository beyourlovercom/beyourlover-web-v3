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

/** Return a rate only when it is the original Woo Zone0 Flat Rate #3. */
function byl_dev_restworld_flat_rate(array $rates): ?array {
    $key = 'flat_rate:3';
    $rate = $rates[$key] ?? null;
    if (!is_object($rate)
        || !method_exists($rate, 'get_method_id')
        || $rate->get_method_id() !== 'flat_rate'
        || !method_exists($rate, 'get_id')
        || $rate->get_id() !== $key
        || !method_exists($rate, 'get_cost')) {
        return null;
    }
    return [$key, clone $rate];
}

/**
 * A new Dev Free Shipping method receives an ID assigned by WooCommerce.
 * Never assume its ID equals WWW's historical #1. Validate the actual
 * enabled amount-gated Woo settings, not merely a method's displayed label.
 */
function byl_dev_restworld_valid_free($rate): bool {
    if (!is_object($rate) || !method_exists($rate, 'get_method_id')
        || $rate->get_method_id() !== 'free_shipping'
        || !method_exists($rate, 'get_instance_id')
        || !method_exists($rate, 'get_id')
        || !method_exists($rate, 'get_cost')) {
        return false;
    }
    $id = (int) $rate->get_instance_id();
    if ($id <= 0 || $rate->get_id() !== 'free_shipping:' . $id
        || (float) $rate->get_cost() !== 0.0) {
        return false;
    }
    $opts = get_option('woocommerce_free_shipping_' . $id . '_settings', []);
    return is_array($opts)
        && (string) ($opts['requires'] ?? '') === 'min_amount'
        && is_numeric($opts['min_amount'] ?? null)
        && abs((float) $opts['min_amount'] - 69.0) < 0.000001
        && ($opts['ignore_discounts'] ?? 'no') !== 'yes';
}

/** Recalculate taxes instead of preserving the cloned rate's old cost tax. */
function byl_dev_restworld_set_shipping_tax($rate, float $cost): bool {
    if (!method_exists($rate, 'set_taxes')
        || !class_exists('WC_Tax')
        || !method_exists('WC_Tax', 'get_shipping_tax_rates')
        || !method_exists('WC_Tax', 'calc_shipping_tax')) {
        return false; // No guessed / erased shipping tax.
    }
    $taxes = WC_Tax::calc_shipping_tax(
        $cost, WC_Tax::get_shipping_tax_rates()
    );
    if (!is_array($taxes)) {
        return false;
    }
    $rate->set_taxes($taxes);
    return true;
}

/**
 * Final per-package Woo rate filter: fail closed on lost PILLOW/special
 * markers, only allow the actual Woo configured rate, no custom free rate.
 * Other Woo zones and already-separated PILLOW packages bypass unchanged.
 */
function byl_dev_restworld_rates(array $rates, array $package): array {
    if (!byl_dev_restworld_zone0($package)
        || ($package['byl_dev_pillow_kind'] ?? '') === 'pillow') {
        return $rates;
    }
    if (!is_array($package['contents'] ?? null)
        || !$package['contents']) {
        return [];
    }
    $specialFound = false;
    $normalFound = false;
    $pillowFound = false;
    foreach ($package['contents'] as $item) {
        if (!is_array($item)) {
            return [];
        }
        if (byl_dev_restworld_pillow($item)) {
            $pillowFound = true;
        } elseif (byl_dev_restworld_special($item)) {
            $specialFound = true;
        } else {
            $normalFound = true;
        }
    }
    if ($pillowFound || ($specialFound && $normalFound)) {
        return []; // A required upstream package split was lost.
    }
    $kind = (string) ($package['byl_dev_restworld_kind'] ?? '');
    $flatPair = byl_dev_restworld_flat_rate($rates);
    if ($specialFound) {
        if ($kind !== 'special30' || $flatPair === null) {
            return [];
        }
        [$key, $flat] = $flatPair;
        if (!method_exists($flat, 'set_cost')
            || !byl_dev_restworld_set_shipping_tax($flat, 30.0)) {
            return [];
        }
        $flat->set_cost(30.0);
        if (method_exists($flat, 'set_label')) {
            $flat->set_label('Extra $30 Shipping');
        }
        return [$key => $flat]; // Preserve Woo's actual rate array key.
    }
    if ($kind !== 'ordinary') {
        return []; // Unexpected contents/markers must not become free.
    }
    $eligible = byl_dev_restworld_sum($package['contents'])
        + 0.000001 >= 69.0;
    $free = [];
    $pickup = [];
    foreach ($rates as $key => $rate) {
        if (byl_dev_restworld_valid_free($rate)) {
            $free[$key] = $rate;
        } elseif (is_object($rate)
            && method_exists($rate, 'get_method_id')
            && $rate->get_method_id() === 'local_pickup') {
            $pickup[$key] = $rate;
        }
    }
    if ($eligible && $free) {
        return $free + $pickup;
    }
    // Woo's base/actual Flat must be $20 for a regular package.
    // If missing or the class formula unexpectedly adds more, do not
    // create a fake free/paid rate or silently overcharge.
    if ($flatPair === null || (float) $flatPair[1]->get_cost() !== 20.0) {
        return $pickup;
    }
    return [$flatPair[0] => $flatPair[1]] + $pickup;
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
