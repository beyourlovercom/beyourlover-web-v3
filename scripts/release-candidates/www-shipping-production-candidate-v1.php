<?php
/**
 * WWW SHIPPING V1 — SOURCE-ONLY PRODUCTION CANDIDATE, NOT RELEASED.
 *
 * DO NOT require/include from functions.php or Theme OS in this PR. All hooks
 * are guarded and default OFF. Production activation requires:
 * - independent Owner approval for the exact production release,
 * - a separate wp-config constant + explicit persisted ON switch,
 * - a complete Owner-reviewed, site-specific Woo method settings contract,
 * - FX-reviewed checkout currency; legacy YayCurrency 3.2.1 is a separately gated source-only experiment.
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

/**
 * WWW shipping class 1939 is currently shared by exactly four parent
 * products: three per-shipment specials and the per-unit PILLOW. An
 * accidental fifth product assignment cannot inherit the native class
 * surcharge while the adapter treats it as ordinary.
 */
function byl_www_ship_class_membership(): ?array {
    if (!function_exists('get_objects_in_term')) { return null; }
    $objects = get_objects_in_term(1939, 'product_shipping_class');
    if (!is_array($objects)) { return null; }
    $ids = array_values(array_unique(array_map('intval', $objects)));
    sort($ids, SORT_NUMERIC);
    return $ids;
}
function byl_www_ship_product_classes_match(): bool {
    return byl_www_ship_class_membership() === [9145, 9153, 32251, 38047];
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
                $entry['minimum'], $entry['before_discount'],
                $entry['free_requirement'])
            || !is_int($entry['free_id'])
            || $entry['free_id'] < 1
            || !is_numeric($entry['minimum'])
            || !is_bool($entry['before_discount'])
            || !is_string($entry['free_requirement'])
            || !is_string($entry['free_digest'])
            || !preg_match('/^[a-f0-9]{64}$/D', $entry['free_digest'])) {
            return null;
        }
        $approvedFreeRequirement = $zoneId === 1 ? 'no'
            : ($zoneId === 0 ? 'either' : 'min_amount');
        if ($entry['free_requirement'] !== $approvedFreeRequirement) {
            return null; // Contract must describe actual Owner-reviewed Woo.
        }
        if ($zoneId === 1) {
            if (isset($entry['flat_id']) || (float)$entry['minimum'] !== 0.0) {
                return null; // HK ordinary remains free, but specials paid.
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
        || !method_exists('WC_Shipping_Zones', 'get_zone')
        || !method_exists('WC_Shipping_Zones', 'get_zones')) {
        return false;
    }
    // Reject any new/reordered destination zone added since Owner's audit.
    // A new region can shadow Zone0 even if its Free/Flat method digests
    // are unchanged, so a previous release contract cannot authorize it.
    $configuredZones = array_map('intval',
        array_keys((array)WC_Shipping_Zones::get_zones()));
    sort($configuredZones, SORT_NUMERIC);
    if ($configuredZones !== [1, 3, 4, 5]) { return false; }
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
        if (array_diff_key($byKey, $expect) !== []
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
        } elseif (($free['requires'] ?? null) !== $entry['free_requirement']
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
                || abs((float)$flat['cost'] - (float)$entry['flat_cost']) > 0.000001
                || ($flat['tax_status'] ?? 'taxable') !== 'taxable') {
                return false;
            }
        }
    }
    return true;
}

/**
 * "Armed" is a deliberate release state separate from runtime readiness.
 * If Owner arms the feature and a Woo option/currency drifts, return NO
 * SHIPPING rather than silently revert to an old free shipping rate.
 */
function byl_www_ship_armed(): bool {
    return defined('BYL_WWW_SHIPPING_V1_APPROVED')
        && BYL_WWW_SHIPPING_V1_APPROVED === true
        && function_exists('home_url')
        && function_exists('wp_parse_url')
        && function_exists('wp_get_environment_type')
        && wp_parse_url(home_url('/'), PHP_URL_HOST) === 'www.beyourlover.com'
        && wp_get_environment_type() === 'production'
        && function_exists('get_option')
        && get_option('byl_www_shipping_v1_enabled', 'no') === 'yes';
}
function byl_www_ship_gate(): bool {
    return byl_www_ship_armed()
        && class_exists('WC_Shipping_Rate')
        && function_exists('WC')
        && ($ledger = byl_www_ship_contract()) !== null
        && byl_www_ship_woo_methods_match($ledger)
        && byl_www_ship_product_classes_match();
}
/**
 * YayCurrency on WWW allows USD/EUR/GBP/AUD/CAD checkout. The Woo base
 * currency being USD DOES NOT identify a customer's effective checkout
 * currency. Require a future independently reviewed currency bridge before
 * any shipping pricing is armed. Until a GBP/EUR/AUD/CAD fee-conversion
 * contract passes runtime UAT, this candidate only permits a VERIFIED USD
 * checkout, and has no bootstrap/activation of its own.
 */
function byl_www_ship_effective_currency(): ?string {
    if (!function_exists('byl_www_shipping_v1_verified_checkout_currency')) {
        return null;
    }
    try {
        $currency = byl_www_shipping_v1_verified_checkout_currency();
    } catch (Throwable $error) {
        return null;
    }
    return is_string($currency)
        && in_array($currency, ['USD', 'EUR', 'GBP', 'AUD', 'CAD'], true)
        ? $currency : null;
}
/**
 * Verified WWW has YayCurrency Pro v3.2.1. Vendor later published
 * shipping class fixes v3.3.3 and shipping "Order" type fixes v3.3.4;
 * a public advisory for YayCurrency (<3.3.5, CVE-2026-16058) adds a
 * security review concern. Pro applicability must be vendor-confirmed.
 * Source-only floor >=3.3.5 is a conservative prerequisite, never proof
 * of safe real EUR/GBP/AUD/CAD checkout, tax, or payment settlement.
 */
function byl_www_ship_yaycurrency_version(): ?string {
    if (!defined('WP_PLUGIN_DIR')
        || !function_exists('get_option')
        || !function_exists('get_file_data')) {
        return null;
    }
    $path = 'yaycurrency-pro/yay-currency.php';
    if (!in_array($path, (array)get_option('active_plugins', []), true)
        || !is_file(WP_PLUGIN_DIR . '/' . $path)) {
        return null;
    }
    $header = get_file_data(WP_PLUGIN_DIR . '/' . $path,
        ['Version'=>'Version'], 'plugin');
    $version = is_array($header) ? ($header['Version'] ?? null) : null;
    return is_string($version) && $version !== '' ? $version : null;
}
function byl_www_ship_yaycurrency_prerequisite(): bool {
    $version = byl_www_ship_yaycurrency_version();
    return $version !== null && version_compare($version, '3.3.5', '>=');
}

/**
 * LEGACY SOURCE-ONLY PROPOSAL. Never treat this as vendor Pro 3.2.1 UAT.
 * A second explicit owner-reviewed sentinel is mandatory. Old versions do
 * not inherit the >=3.3.5 path, and no handler is bootstrapped here.
 */
function byl_www_ship_legacy321_supported(): bool {
    return defined('BYL_WWW_SHIPPING_V1_LEGACY_321_REVIEWED')
        && BYL_WWW_SHIPPING_V1_LEGACY_321_REVIEWED === true
        && byl_www_ship_yaycurrency_version() === '3.2.1'
        && function_exists('get_option')
        && get_option('woocommerce_currency', '') === 'USD'
        && function_exists('has_filter')
        && function_exists('apply_filters')
        && has_filter('yay_currency_convert_price') !== false
        && has_filter('yay_currency_revert_price') !== false
        && byl_www_ship_effective_currency() !== null;
}
/**
 * Converts BASE USD mandatory parcel charge exactly once, after the
 * observed vendor package_rates priority-10 converter. Refuses unknown,
 * nonnumeric, non-reversible or unexpected fee output; does NOT interpolate
 * a rate or relabel USD $30 as £30/A$30. No WordPress writes.
 */
function byl_www_ship_legacy321_fee(float $baseUsd): ?float {
    if (!byl_www_ship_legacy321_supported()
        || !is_finite($baseUsd) || $baseUsd <= 0.0) {
        return null;
    }
    try {
        $converted = apply_filters('yay_currency_convert_price', $baseUsd);
        if (!is_numeric($converted) || !is_finite((float)$converted)
            || (float)$converted <= 0.0) {
            return null;
        }
        $localFee = round((float)$converted, 2);
        $reverted = apply_filters('yay_currency_revert_price', $localFee);
        if (!is_numeric($reverted) || !is_finite((float)$reverted)
            || abs((float)$reverted - $baseUsd) > max(0.05, $baseUsd * 0.005)) {
            return null;
        }
        return $localFee;
    } catch (Throwable $error) {
        return null;
    }
}
function byl_www_ship_currency_ok(): bool {
    if (!defined('BYL_WWW_SHIPPING_V1_FX_REVIEWED')
        || BYL_WWW_SHIPPING_V1_FX_REVIEWED !== true
        || !function_exists('get_woocommerce_currency')
        || get_woocommerce_currency() !== 'USD') {
        return false;
    }
    if (byl_www_ship_legacy321_supported()) {
        // Conversion is required for all five currencies, including USD.
        return byl_www_ship_legacy321_fee(30.0) !== null;
    }
    return byl_www_ship_yaycurrency_prerequisite()
        && byl_www_ship_effective_currency() === 'USD';
}

/**
 * Woo caches shipping rates in WC()->session against a package hash. Without
 * an explicit release/config/currency marker, an already-cached Free rate
 * may survive an OFF->ON transition or later shipping-option drift, so the
 * package_rates fail-closed callback might never be invoked.
 *
 * Store only this SHA-256 in the package, not admin options or customer data.
 * The fingerprint includes all five reviewed Woo method settings, matched
 * zone coverage/order and the effective checkout currency. A change forces
 * Woo to compute a new package hash when the future adapter is armed.
 */
function byl_www_ship_runtime_fingerprint(): string {
    $ledger = byl_www_ship_contract();
    $state = [
        'enabled' => function_exists('get_option')
            ? get_option('byl_www_shipping_v1_enabled', 'no') : 'unknown',
        'ledger' => $ledger,
        'base_currency' => function_exists('get_woocommerce_currency')
            ? get_woocommerce_currency() : null,
        'effective_currency' => byl_www_ship_effective_currency(),
        'yaycurrency_pro_version' => byl_www_ship_yaycurrency_version(),
        'legacy321_reviewed' => defined('BYL_WWW_SHIPPING_V1_LEGACY_321_REVIEWED')
            && BYL_WWW_SHIPPING_V1_LEGACY_321_REVIEWED === true,
        // Currency/rate changes invalidate Woo's cached rate hash.
        'legacy321_usd30_local' => byl_www_ship_legacy321_supported()
            ? byl_www_ship_legacy321_fee(30.0) : null,
        'special_class_products' => byl_www_ship_class_membership(),
        'zones' => [],
    ];
    if (class_exists('WC_Shipping_Zones')
        && method_exists('WC_Shipping_Zones', 'get_zones')) {
        $state['all_zone_ids'] = array_keys((array)WC_Shipping_Zones::get_zones());
        sort($state['all_zone_ids'], SORT_NUMERIC);
    }
    if (is_array($ledger) && class_exists('WC_Shipping_Zones')) {
        foreach ($ledger as $id => $entry) {
            $zone = WC_Shipping_Zones::get_zone((int)$id);
            $methods = is_object($zone)
                && method_exists($zone, 'get_shipping_methods')
                ? (array)$zone->get_shipping_methods(true) : [];
            $enabled = [];
            foreach ($methods as $method) {
                if (is_object($method)
                    && method_exists($method, 'get_instance_id')) {
                    $enabled[] = (string)($method->id ?? '')
                        . ':' . (int)$method->get_instance_id();
                }
            }
            sort($enabled, SORT_STRING);
            $freeKey = 'woocommerce_free_shipping_'
                . (int)$entry['free_id'] . '_settings';
            $one = ['enabled_methods' => $enabled,
                'free' => get_option($freeKey, [])];
            if (isset($entry['flat_id'])) {
                $one['flat'] = get_option('woocommerce_flat_rate_'
                    . (int)$entry['flat_id'] . '_settings', []);
            }
            $one['locations'] = is_object($zone)
                && method_exists($zone, 'get_zone_locations')
                ? $zone->get_zone_locations() : null;
            $one['order'] = is_object($zone)
                && method_exists($zone, 'get_zone_order')
                ? $zone->get_zone_order() : null;
            $state['zones'][$id] = $one;
        }
    }
    return byl_www_ship_settings_digest($state);
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
    if (!byl_www_ship_armed()) { return $packages; }
    $marker = byl_www_ship_runtime_fingerprint();
    $ready = byl_www_ship_gate() && byl_www_ship_currency_ok();
    $out = [];
    foreach ($packages as $package) {
        if (!is_array($package)) {
            $out[] = $package;
            continue;
        }
        // Always change the Woo shipping cache key while armed, EVEN when
        // current zone/FX settings fail validation. Otherwise Woo might
        // serve old cached Free Shipping without re-running rate filters.
        $package['byl_www_ship_runtime_marker'] = $marker;
        if (!$ready || !is_array($package['contents'] ?? null)) {
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

/**
 * Mirror Woo 11.2.1 Free Shipping's own valid-coupon test. The historical
 * text "BYL49" in an Andy Commerce Dev preset is NOT evidence of an actual
 * free-shipping Woo coupon on WWW; a literal applied code alone must not
 * waive a $20 ordinary shipping charge.
 */
function byl_www_ship_valid_native_free_coupon(): bool {
    if (!function_exists('WC') || !is_object(WC()->cart ?? null)
        || !method_exists(WC()->cart, 'get_coupons')) {
        return false;
    }
    foreach ((array) WC()->cart->get_coupons() as $coupon) {
        if (!is_object($coupon) || !method_exists($coupon, 'is_valid')
            || !method_exists($coupon, 'get_free_shipping')) {
            continue;
        }
        try {
            if ($coupon->is_valid() && $coupon->get_free_shipping()) {
                return true;
            }
        } catch (Throwable $error) {
            return false;
        }
    }
    return false;
}

/** Must always run after legacy Woo/BYL shipping filters. */
function byl_www_ship_rates(array $rates, array $package): array {
    if (!byl_www_ship_armed()) { return $rates; }
    // A live settings/zone/fx mismatch or stale cached package must not
    // silently reinstate a previously eligible free shipping rate.
    if (!byl_www_ship_gate() || !byl_www_ship_currency_ok()
        || ($package['byl_www_ship_runtime_marker'] ?? '')
            !== byl_www_ship_runtime_fingerprint()) {
        return [];
    }
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
        // On the legacy multi-FX route reject native flat amounts that
        // YayCurrency 3.2.1 failed to convert (notably Zone3 Order type).
        if (byl_www_ship_legacy321_supported()
            && isset($zone['flat_id'])) {
            $flatKey = 'flat_rate:' . $zone['flat_id'];
            $nativeFlat = $rates[$flatKey] ?? null;
            $expected = byl_www_ship_legacy321_fee((float)$zone['flat_cost']);
            if ($expected === null || !is_object($nativeFlat)
                || !method_exists($nativeFlat, 'get_cost')
                || abs((float)$nativeFlat->get_cost() - $expected) > 0.051) {
                return []; // Never assume buggy native Order/class FX is correct.
            }
        }
        $sum = byl_www_ship_subtotal($contents);
        if ($sum + 0.000001 >= (float)$zone['minimum']) {
            return $rates; // Never invent a Free rate; Woo decides eligibility.
        }
        $freeKey = 'free_shipping:' . $zone['free_id'];
        // Preserve a VALID Woo native free-shipping coupon only under the
        // exact WWW Zone0 "either" requirement, matching Woo 11.2.1.
        // Applied promo text alone (including BYL49) grants no entitlement.
        // The special/PILLOW packages are always charged independently.
        $freeOptions = get_option('woocommerce_free_shipping_'
            . (int)$zone['free_id'] . '_settings', []);
        if ($zoneId === 0 && isset($rates[$freeKey])
            && is_array($freeOptions)
            && ($freeOptions['requires'] ?? null) === 'either'
            && byl_www_ship_valid_native_free_coupon()) {
            return $rates;
        }
        unset($rates[$freeKey]); // Never honor an unvalidated plain code.
        return $rates;
    }
    $fee = $kind === 'pillow'
        ? 30.0 * max(0, (int)($package['byl_www_ship_pillow_qty'] ?? 0))
        : 30.0;
    if ($fee <= 0) { return []; }
    if (byl_www_ship_legacy321_supported()) {
        $convertedFee = byl_www_ship_legacy321_fee($fee);
        if ($convertedFee === null) { return []; }
        $fee = $convertedFee;
    }
    if (isset($zone['flat_id'])) {
        $key = 'flat_rate:' . $zone['flat_id'];
        $raw = $rates[$key] ?? null;
        if (!is_object($raw)
            || !method_exists($raw, 'get_id')
            || !method_exists($raw, 'get_method_id')
            || !method_exists($raw, 'get_tax_status')
            || $raw->get_id() !== $key
            || $raw->get_method_id() !== 'flat_rate'
            || $raw->get_tax_status() !== 'taxable') {
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
    if (!byl_www_ship_armed()) { return; }
    // Deliberately install the fail-closed filter even when the contract
    // no longer matches. Otherwise the merchant could sell with stale free.
    // No hooks are attached at all until a future separately authorized
    // bootstrap includes this unwired file and invokes this function.
    add_filter('woocommerce_cart_shipping_packages', 'byl_www_ship_packages', 970, 1);
    add_filter('woocommerce_package_rates', 'byl_www_ship_rates', 100200, 2);
}
// IMPORTANT: No add_action() here. The candidate is intentionally not wired.
