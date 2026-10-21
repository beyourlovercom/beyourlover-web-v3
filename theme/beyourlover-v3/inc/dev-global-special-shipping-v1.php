<?php
/**
 * BeYourLover three-product special freight globally — Dev staging only.
 *
 * SAKURA #32251, BDSM BONDAGE KIT #9145/#9153: USD $30 per special
 * shipment/package regardless of normal country free-shipping entitlement.
 * PILLOW $30 per UNIT stays independently owned by the existing Dev adapter.
 * Ordinary zones/rates/settings are never changed by this file.
 *
 * OFF unless both Restworld adapter and separate global flag are enabled.
 */
declare(strict_types=1);
defined('ABSPATH') || exit;

function byl_dev_global_special_active(): bool {
    return function_exists('byl_dev_restworld_active')
        && byl_dev_restworld_active()
        && function_exists('byl_dev_restworld_special')
        && function_exists('byl_dev_restworld_pillow')
        && get_option('byl_dev_global_special_shipping_active_v1', 'no') === 'yes';
}

function byl_dev_global_special_zone(array $package): ?int {
    if (!is_array($package['destination'] ?? null)
        || !class_exists('WC_Shipping_Zones')) {
        return null;
    }
    $zone = WC_Shipping_Zones::get_zone_matching_package($package);
    return is_object($zone) && method_exists($zone, 'get_id')
        ? (int) $zone->get_id() : null;
}

/** priority 960: PILLOW priority 900; Restworld priority 950. */
function byl_dev_global_special_packages(array $packages): array {
    if (!byl_dev_global_special_active()) {
        return $packages;
    }
    $result = [];
    foreach ($packages as $package) {
        if (!is_array($package)
            || !is_array($package['contents'] ?? null)
            || ($package['byl_dev_pillow_kind'] ?? '') === 'pillow'
            || ($package['byl_dev_restworld_kind'] ?? '') === 'special30') {
            $result[] = $package;
            continue;
        }
        $zone = byl_dev_global_special_zone($package);
        if ($zone === 0) { // PR153 Restworld already owns its split.
            $result[] = $package;
            continue;
        }
        $special = [];
        $regular = [];
        $pillowPresent = false;
        foreach ($package['contents'] as $key => $item) {
            if (!is_array($item)) {
                $result[] = $package;
                continue 2;
            }
            if (byl_dev_restworld_pillow($item)) {
                $pillowPresent = true;
            } elseif (byl_dev_restworld_special($item)) {
                $special[$key] = $item;
            } else {
                $regular[$key] = $item;
            }
        }
        if (!$special) {
            $result[] = $package;
            continue;
        }
        if ($pillowPresent) {
            $package['byl_dev_global_special_kind'] = 'unsafe_unsplit_pillow';
            $result[] = $package;
            continue;
        }
        if ($regular) {
            $normal = $package;
            $normal['contents'] = $regular;
            $normal['contents_cost'] = byl_dev_restworld_sum($regular);
            $normal['byl_dev_global_special_kind'] = 'ordinary';
            $result[] = $normal;
        }
        $charge = $package;
        $charge['contents'] = $special;
        $charge['contents_cost'] = byl_dev_restworld_sum($special);
        $charge['byl_dev_global_special_kind'] = 'special30';
        $result[] = $charge;
    }
    return $result;
}

/**
 * Final Woo shipping-rate selection, scoped only to exceptional packages in
 * non-Restworld zones. A valid Woo method is mandatory for existing flat zones.
 * HK's free-only Zone1 may use a dedicated paid exception rate ONLY when
 * Woo actually emits its pre-existing free_shipping:2 for that destination.
 */
function byl_dev_global_special_rates(array $rates, array $package): array {
    if (!byl_dev_global_special_active()
        || ($package['byl_dev_pillow_kind'] ?? '') === 'pillow') {
        return $rates;
    }
    $zone = byl_dev_global_special_zone($package);
    if ($zone === 0) {
        return $rates; // Existing $30 Restworld implementation is authoritative.
    }
    $found = false;
    $ordinary = false;
    $pillow = false;
    foreach ((array) ($package['contents'] ?? []) as $item) {
        if (!is_array($item)) {
            return [];
        }
        if (byl_dev_restworld_pillow($item)) {
            $pillow = true;
        } elseif (byl_dev_restworld_special($item)) {
            $found = true;
        } else {
            $ordinary = true;
        }
    }
    if (!$found && !$pillow) {
        return $rates; // Preserve ordinary Woo country-specific policies.
    }
    if ($pillow || $ordinary
        || ($package['byl_dev_global_special_kind'] ?? '') !== 'special30') {
        return []; // Lost split cannot make specials eligible for free shipping.
    }
    $fee = 30.0;
    $flatInstances = [3 => 5, 4 => 7, 5 => 10];
    if (array_key_exists($zone, $flatInstances)) {
        $key = 'flat_rate:' . $flatInstances[$zone];
        $original = $rates[$key] ?? null;
        if (!is_object($original)
            || !method_exists($original, 'get_method_id')
            || $original->get_method_id() !== 'flat_rate'
            || !method_exists($original, 'get_id')
            || $original->get_id() !== $key
            || !method_exists($original, 'set_cost')
            || !method_exists($original, 'set_label')) {
            return [];
        }
        $fixed = clone $original;
        if (!byl_dev_restworld_set_shipping_tax($fixed, $fee)) {
            return []; // Tax ambiguity must never turn $30 into a guess.
        }
        $fixed->set_cost($fee);
        $fixed->set_label('Extra $30 Shipping');
        return [$key => $fixed];
    }
    if ($zone === 1) {
        $coreFree = $rates['free_shipping:2'] ?? null;
        if (!is_object($coreFree)
            || !method_exists($coreFree, 'get_method_id')
            || $coreFree->get_method_id() !== 'free_shipping'
            || !method_exists($coreFree, 'get_id')
            || $coreFree->get_id() !== 'free_shipping:2'
            || !method_exists($coreFree, 'get_cost')
            || (float) $coreFree->get_cost() !== 0.0) {
            return []; // HK is supported ONLY if actual Woo Free2 exists.
        }
        $fixed = new WC_Shipping_Rate(
            'byl_dev_global_special30_hk',
            'Extra $30 Shipping',
            $fee, [], 'byl_dev_global_special30_hk'
        );
        if (!byl_dev_restworld_set_shipping_tax($fixed, $fee)) {
            return [];
        }
        return [$fixed->get_id() => $fixed];
    }
    return []; // Unknown shipping zones never become eligible by invention.
}

/** Only correct the already existing BYL promotion fields in special carts. */
function byl_dev_global_special_promo(array $promo, array $cart): array {
    $zone = (int) ($promo['zone_id'] ?? -1);
    if (!byl_dev_global_special_active()
        || $zone === 0
        || !in_array($zone, [1, 3, 4, 5], true)
        || empty($promo['active'])) {
        return $promo;
    }
    $containsSpecial = false;
    $ordinary = [];
    foreach ($cart as $key => $item) {
        if (!is_array($item)) {
            continue;
        }
        if (byl_dev_restworld_special($item)) {
            $containsSpecial = true;
        } elseif (!byl_dev_restworld_pillow($item)) {
            $ordinary[$key] = $item;
        }
    }
    if (!$containsSpecial) {
        return $promo; // Preserve normal and PILLOW-only promotion states.
    }
    $threshold = is_numeric($promo['threshold'] ?? null)
        ? max(0.0, (float) $promo['threshold']) : 0.0;
    $subtotal = byl_dev_restworld_sum($ordinary);
    $qualified = (bool) $ordinary
        && $subtotal + 0.000001 >= $threshold;
    $promo['subtotal'] = $subtotal;
    $promo['remaining'] = max(0.0, round($threshold - $subtotal, 2));
    $promo['eligible'] = $qualified;
    // A charged special package means the *entire* cart is not free.
    $promo['unlocked'] = false;
    $promo['state'] = 'special_shipping_applies';
    $promo['special_shipping_applies'] = true;
    return $promo;
}

function byl_dev_global_special_store_response($response, $server, $request) {
    if (!byl_dev_global_special_active()
        || !is_object($request)
        || !method_exists($request, 'get_route')
        || !preg_match('#^/wc/store/v1/cart(?:/[^/?]+)?$#D',
            (string) $request->get_route())
        || !is_object($response)
        || !method_exists($response, 'get_data')
        || !method_exists($response, 'set_data')
        || !WC() || !is_object(WC()->cart ?? null)
        || !method_exists(WC()->cart, 'get_cart')) {
        return $response;
    }
    $data = $response->get_data();
    $registry = $data['extensions'] ?? null;
    $wasObject = is_object($registry);
    $fields = $wasObject ? get_object_vars($registry) : $registry;
    if (!is_array($fields)) {
        return $response;
    }
    $raw = $fields['byl-shipping-promotion'] ?? null;
    $promo = is_object($raw) ? get_object_vars($raw) : $raw;
    if (!is_array($promo)) {
        return $response;
    }
    $next = byl_dev_global_special_promo($promo, (array) WC()->cart->get_cart());
    if ($next !== $promo) {
        $fields['byl-shipping-promotion'] = is_object($raw)
            ? (object) $next : $next;
        $data['extensions'] = $wasObject ? (object) $fields : $fields;
        $response->set_data($data);
    }
    return $response;
}

function byl_dev_global_special_boot(): void {
    if (!byl_dev_global_special_active()) {
        return;
    }
    add_filter('woocommerce_cart_shipping_packages',
        'byl_dev_global_special_packages', 960, 1);
    add_filter('woocommerce_package_rates',
        'byl_dev_global_special_rates', 100020, 2);
    add_filter('rest_post_dispatch',
        'byl_dev_global_special_store_response', 10002, 3);
}
add_action('woocommerce_init', 'byl_dev_global_special_boot', 102);
