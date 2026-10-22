<?php
/**
 * WWW SHIPPING V1 — SOURCE-ONLY PRODUCTION CANDIDATE, NOT RELEASED.
 *
 * DO NOT require/include from functions.php or Theme OS in this PR. All hooks
 * are guarded and default OFF. Production activation requires:
 * - independent Owner approval for the exact production release,
 * - a separate wp-config constant + explicit persisted ON switch,
 * - a complete Owner-reviewed, site-specific Woo method settings contract,
 * - a verified single-currency USD runtime (FX ships under a separate gate).
 *
 * No WordPress shipping settings are changed by this file. Never use Dev
 * module flags/instance IDs to enable WWW or guess production costs.
 */
declare(strict_types=1);
defined('ABSPATH') || exit;

function byl_www_ship_product_kind(array $item): string {
    $id = (int) ($item['product_id'] ?? 0);
    $parent = $item['data'] ?? null;
    $parent_id = is_object($parent) && method_exists($parent, 'get_parent_id')
        ? (int) $parent->get_parent_id() : 0;
    if ($id === 38047 || $parent_id === 38047) {
        return 'pillow';
    }
    if (in_array($id, [9145, 9153, 32251], true)
        || in_array($parent_id, [9145, 9153, 32251], true)) {
        return 'special';
    }
    return 'ordinary';
}

function byl_www_ship_subtotal(array $items): float {
    $sum = 0.0;
    foreach ($items as $item) {
        if (!is_array($item)) { continue; }
        $sum += max(0.0, (float) ($item['line_total'] ?? 0));
    }
    return round($sum, 2);
}

/**
 * All five actual Woo zone method settings must match a separately reviewed
 * OWNER RELEASE LEDGER before the feature becomes eligible to activate.
 * No DEV instance #11 assumption; contract IDs are supplied only AFTER a
 * production native-admin read-only audit. The settings digest includes
 * all shipping class and base cost fields, not just the headline threshold.
 */
function byl_www_ship_settings_digest(array $settings): string {
    ksort($settings, SORT_STRING);
    foreach ($settings as &$entry) {
        if (is_array($entry)) {
            $entry = byl_www_ship_settings_digest_normalize($entry);
        }
    }
    unset($entry);
    return hash('sha256', (string) json_encode($settings, JSON_UNESCAPED_SLASHES));
}
function byl_www_ship_settings_digest_normalize(array $data): array {
    ksort($data, SORT_STRING);
    foreach ($data as &$value) {
        if (is_array($value)) {
            $value = byl_www_ship_settings_digest_normalize($value);
        }
    }
    unset($value);
    return $data;
}

function byl_www_ship_contract(): ?array {
    if (!defined('BYL_WWW_SHIPPING_V1_METHOD_CONTRACT')
        || !is_array(BYL_WWW_SHIPPING_V1_METHOD_CONTRACT)) {
        return null;
    }
    $ledger = BYL_WWW_SHIPPING_V1_METHOD_CONTRACT;
    $expectedZones = [0, 1, 3, 4, 5];
    $ids = array_keys($ledger);
    sort($ids, SORT_NUMERIC);
    if ($ids !== $expectedZones) { return null; }
    foreach ($expectedZones as $zoneId) {
        $entry = $ledger[$zoneId] ?? null;
        if (!is_array($entry)
            || !isset($entry['free_id'], $entry['free_digest'],
                $entry['minimum'], $entry['before_discount'])
            || !is_int($entry['free_id'])
            || $entry['free_id'] < 1
            || !is_numeric($entry['minimum'])
            || !is_bool($entry['before_discount'])
            || !is_string($entry['free_digest'])
            || !preg_match('/^[a-f0-9]{64}$/D', $entry['free_digest'])) {
            return null;
        }
        if ($zoneId === 1) {
            if (isset($entry['flat_id']) || (float)$entry['minimum'] !== 0.0) {
                return null; // Existing HK is free-only for ordinary parcels.
            }
            continue;
        }
        if (!isset($entry['flat_id'], $entry['flat_digest'], $entry['flat_cost'])
            || !is_int($entry['flat_id']) || $entry['flat_id'] < 1
            || !is_numeric($entry['flat_cost'])
            || !is_string($entry['flat_digest'])
            || !preg_match('/^[a-f0-9]{64}$/D', $entry['flat_digest'])) {
            return null;
        }
        $targets = [0 => [69.0, 20.0], 3 => [169.0, 30.0],
                    4 => [129.0, 20.0], 5 => [69.0, 10.0]];
        [$minimum, $flatCost] = $targets[$zoneId];
        if (abs((float)$entry['minimum'] - $minimum) > 0.000001
            || abs((float)$entry['flat_cost'] - $flatCost) > 0.000001
            || $entry['before_discount']) {
            return null; // Only agreed country rules, after coupon.
        }
    }
    return $ledger;
}

function byl_www_ship_woo_methods_match(array $ledger): bool {
    if (!class_exists('WC_Shipping_Zones')
        || !method_exists('WC_Shipping_Zones', 'get_zone')) {
        return false;
    }
    foreach ($ledger as $zoneId => $entry) {
        $zone = WC_Shipping_Zones::get_zone((int)$zoneId);
        if (!is_object($zone) || !method_exists($zone, 'get_shipping_methods')) {
            return false;
        }
        $all = $zone->get_shipping_methods(true);
        if (!is_array($all)) { return false; }
        $byKey = [];
        foreach ($all as $method) {
            if (!is_object($method)
                || !method_exists($method, 'get_instance_id')) {
                return false;
            }
            $methodName = (string)($method->id ?? '');
            $instance = (int)$method->get_instance_id();
            $key = $methodName . ':' . $instance;
            // Any unexpected enabled FREE/FLAT can bypass the fixed-charge
            // contract; require Owner review instead of silently ignoring it.
            if (in_array($methodName, ['free_shipping', 'flat_rate'], true)) {
                $byKey[$key] = true;
            }
        }
        $freeKey = 'free_shipping:' . $entry['free_id'];
        $expect = [$freeKey => true];
        if (isset($entry['flat_id'])) {
            $expect['flat_rate:' . $entry['flat_id']] = true;
        }
        if ($byKey !== $expect && array_diff_key($byKey, $expect) !== []
            || array_diff_key($expect, $byKey) !== []) {
            return false;
        }
        $free = get_option('woocommerce_free_shipping_' . $entry['free_id'] . '_settings', []);
        if (!is_array($free)
            || !hash_equals($entry['free_digest'], byl_www_ship_settings_digest($free))) {
            return false;
        }
        if ($zoneId === 1) {
            if (!in_array((string)($free['requires'] ?? 'no'), ['no', ''], true)) {
                return false;
            }
        } elseif (($free['requires'] ?? null) !== 'min_amount'
            || !is_numeric($free['min_amount'] ?? null)
            || abs((float)$free['min_amount'] - (float)$entry['minimum']) > 0.000001
            || ($free['ignore_discounts'] ?? 'no') === 'yes') {
            return false;
        }
        if (isset($entry['flat_id'])) {
            $flat = get_option('woocommerce_flat_rate_' . $entry['flat_id'] . '_settings', []);
            if (!is_array($flat)
                || !hash_equals($entry['flat_digest'], byl_www_ship_settings_digest($flat))
                || !is_numeric($flat['cost'] ?? null)
                || abs((float)$flat['cost'] - (float)$entry['flat_cost']) > 0.000001) {
                return false;
            }
        }
    }
    return true;
}

function byl_www_ship_gate(): bool {
    return defined('BYL_WWW_SHIPPING_V1_APPROVED')
        && BYL_WWW_SHIPPING_V1_APPROVED === true
        && function_exists('home_url')
        && function_exists('wp_parse_url')
        && function_exists('wp_get_environment_type')
        && wp_parse_url(home_url('/'), PHP_URL_HOST) === 'www.beyourlover.com'
        && wp_get_environment_type() === 'production'
        && function_exists('get_option')
        && get_option('byl_www_shipping_v1_enabled', 'no') === 'yes'
        && function_exists('get_woocommerce_currency')
        && get_woocommerce_currency() === 'USD'
        && class_exists('WC_Shipping_Rate')
        && function_exists('WC')
        && ($ledger = byl_www_ship_contract()) !== null
        && byl_www_ship_woo_methods_match($ledger);
}

function byl_www_ship_zone_id(array $package): ?int {
    if (!class_exists('WC_Shipping_Zones')
        || !is_array($package['destination'] ?? null)) {
        return null;
    }
    $zone = WC_Shipping_Zones::get_zone_matching_package($package);
    return is_object($zone) && method_exists($zone, 'get_id')
        ? (int)$zone->get_id() : null;
}

function byl_www_ship_packages(array $packages): array {
    if (!byl_www_ship_gate()) { return $packages; }
    $out = [];
    foreach ($packages as $package) {
        if (!is_array($package) || !is_array($package['contents'] ?? null)) {
            $out[] = $package;
            continue;
        }
        $groups = ['ordinary' => [], 'special' => [], 'pillow' => []];
        foreach ($package['contents'] as $key => $item) {
            if (!is_array($item)) {
                $package['byl_www_ship_kind'] = 'invalid';
                $out[] = $package;
                continue 2;
            }
            $groups[byl_www_ship_product_kind($item)][$key] = $item;
        }
        // Splitting is only needed for exceptions. Preserve an ordinary
        // package without adding duplicate empty shipping packages.
        if (!$groups['special'] && !$groups['pillow']) {
            $package['byl_www_ship_kind'] = 'ordinary';
            $out[] = $package;
            continue;
        }
        foreach ($groups as $kind => $items) {
            if (!$items) { continue; }
            $part = $package;
            $part['contents'] = $items;
            $part['contents_cost'] = byl_www_ship_subtotal($items);
            $part['byl_www_ship_kind'] = $kind;
            if ($kind === 'pillow') {
                $part['byl_www_ship_pillow_qty'] = array_sum(array_map(
                    static fn(array $x): int => max(0, (int)($x['quantity'] ?? 0)),
                    $items
                ));
            }
            $out[] = $part;
        }
    }
    return $out;
}

function byl_www_ship_tax($rate, float $fee): bool {
    if (!method_exists($rate, 'set_taxes')
        || !class_exists('WC_Tax')
        || !method_exists('WC_Tax', 'get_shipping_tax_rates')
        || !method_exists('WC_Tax', 'calc_shipping_tax')) {
        return false;
    }
    $taxes = WC_Tax::calc_shipping_tax($fee, WC_Tax::get_shipping_tax_rates());
    if (!is_array($taxes)) { return false; }
    $rate->set_taxes($taxes);
    return true;
}

/** Must always run after legacy Woo/BYL shipping filters. */
function byl_www_ship_rates(array $rates, array $package): array {
    if (!byl_www_ship_gate()) { return $rates; }
    $kind = (string)($package['byl_www_ship_kind'] ?? '');
    $contents = $package['contents'] ?? null;
    if (!is_array($contents) || !$contents) { return []; }
    $actualKinds = [];
    foreach ($contents as $item) {
        if (!is_array($item)) { return []; }
        $actualKinds[byl_www_ship_product_kind($item)] = true;
    }
    if (count($actualKinds) !== 1 || !isset($actualKinds[$kind])) {
        return []; // Never silently waive charges for an unsplit package.
    }
    $zoneId = byl_www_ship_zone_id($package);
    $ledger = byl_www_ship_contract();
    if (!is_int($zoneId) || !is_array($ledger)
        || !isset($ledger[$zoneId])) {
        return []; // Unknown destination / zone must not invent a rate.
    }
    $zone = $ledger[$zoneId];
    if ($kind === 'ordinary') {
        $sum = byl_www_ship_subtotal($contents);
        if ($sum + 0.000001 >= (float)$zone['minimum']) {
            return $rates; // Never invent a Free rate; Woo decides eligibility.
        }
        if (function_exists('WC') && is_object(WC()->cart ?? null)
            && method_exists(WC()->cart, 'get_applied_coupons')
            && in_array('byl49', array_map('strtolower',
                (array)WC()->cart->get_applied_coupons()), true)) {
            return $rates; // Preserve special BYL49 protection / exemptions.
        }
        $freeKey = 'free_shipping:' . $zone['free_id'];
        unset($rates[$freeKey]); // Prevent obsolete pre-discount override.
        return $rates;
    }
    $fee = $kind === 'pillow'
        ? 30.0 * max(0, (int)($package['byl_www_ship_pillow_qty'] ?? 0))
        : 30.0;
    if ($fee <= 0) { return []; }
    if (isset($zone['flat_id'])) {
        $key = 'flat_rate:' . $zone['flat_id'];
        $raw = $rates[$key] ?? null;
        if (!is_object($raw)
            || !method_exists($raw, 'get_id')
            || !method_exists($raw, 'get_method_id')
            || $raw->get_id() !== $key
            || $raw->get_method_id() !== 'flat_rate') {
            return []; // Never coerce Free to paid under other zone IDs.
        }
        $rate = clone $raw;
    } else {
        $key = 'free_shipping:' . $zone['free_id'];
        $free = $rates[$key] ?? null;
        if (!is_object($free) || !method_exists($free, 'get_id')
            || !method_exists($free, 'get_method_id')
            || $free->get_id() !== $key
            || $free->get_method_id() !== 'free_shipping'
            || !method_exists($free, 'get_cost')
            || (float)$free->get_cost() !== 0.0) {
            return [];
        }
        $id = $kind === 'pillow'
            ? 'byl_www_pillow_oversized' : 'byl_www_special30_hk';
        $rate = new WC_Shipping_Rate($id,
            $kind === 'pillow' ? 'Oversized Pillow Shipping' : 'Extra $30 Shipping',
            $fee, [], $id
        );
    }
    if (!method_exists($rate, 'set_cost')
        || !method_exists($rate, 'set_label')
        || !byl_www_ship_tax($rate, $fee)) {
        return [];
    }
    $rate->set_cost($fee);
    $rate->set_label($kind === 'pillow'
        ? 'Oversized Pillow Shipping' : 'Extra $30 Shipping');
    return [$rate->get_id() => $rate];
}

function byl_www_ship_boot(): void {
    if (!byl_www_ship_gate()) { return; }
    add_filter('woocommerce_cart_shipping_packages', 'byl_www_ship_packages', 970, 1);
    add_filter('woocommerce_package_rates', 'byl_www_ship_rates', 100200, 2);
}
// IMPORTANT: No add_action() here. The candidate is intentionally not wired.
