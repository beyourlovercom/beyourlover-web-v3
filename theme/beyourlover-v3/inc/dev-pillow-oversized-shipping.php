<?php
/**
 * PILLOW Oversized Shipping V1 — BeYourLover Dev-only test adapter.
 *
 * This deliberately does NOT modify WooCommerce core, Andy Core, Andy Commerce
 * or the existing shipping classes, zones, coupons or checkout payments.
 * It is guarded by exact Dev host and staging environment. Do not enable on
 * WWW; any WWW changes must be carried out manually by the site Owner.
 *
 * Woo shipping packages are separated by product identity, not display name.
 * A PILLOW package costs USD $30 per unit. Ordinary goods keep their normal
 * Woo methods and qualify for free shipping only against THEIR discounted
 * merchandise subtotal (never against the pillow line totals).
 */
declare(strict_types=1);
defined('ABSPATH') || exit;

function byl_dev_pillow_shipping_enabled(): bool {
    return function_exists('home_url')
        && function_exists('wp_get_environment_type')
        && function_exists('wp_parse_url')
        && wp_parse_url(home_url('/'), PHP_URL_HOST) === 'dev.beyourlover.com'
        && wp_get_environment_type() === 'staging'
        && class_exists('WC_Shipping_Rate')
        && function_exists('WC');
}

/** For variations, Woo cart product_id is the variable parent. */
function byl_dev_pillow_shipping_is_pillow(array $item): bool {
    return (int) ($item['product_id'] ?? 0) === 38047
        || (is_object($item['data'] ?? null)
            && method_exists($item['data'], 'get_parent_id')
            && (int) $item['data']->get_parent_id() === 38047);
}

function byl_dev_pillow_shipping_qty(array $items): int {
    $count = 0;
    foreach ($items as $item) {
        if (is_array($item) && byl_dev_pillow_shipping_is_pillow($item)) {
            $count += max(0, (int) ($item['quantity'] ?? 0));
        }
    }
    return $count;
}

/** Use Woo's after-coupon line totals (exclusive of taxes and shipping). */
function byl_dev_pillow_shipping_items_total(array $items): float {
    $total = 0.0;
    foreach ($items as $item) {
        if (is_array($item)) {
            $total += max(0, (float) ($item['line_total'] ?? 0));
        }
    }
    return round($total, 2);
}

function byl_dev_pillow_shipping_packages(array $packages): array {
    if (!byl_dev_pillow_shipping_enabled()) {
        return $packages;
    }

    $separated = [];
    foreach ($packages as $package) {
        if (!is_array($package) || !is_array($package['contents'] ?? null)) {
            $separated[] = $package;
            continue;
        }

        $special = [];
        $ordinary = [];
        foreach ($package['contents'] as $key => $item) {
            if (is_array($item) && byl_dev_pillow_shipping_is_pillow($item)) {
                $special[$key] = $item;
            } else {
                $ordinary[$key] = $item;
            }
        }

        if (!$special) {
            $separated[] = $package;
            continue;
        }

        if ($ordinary) {
            $normalPackage = $package;
            $normalPackage['contents'] = $ordinary;
            $normalPackage['contents_cost'] = byl_dev_pillow_shipping_items_total($ordinary);
            $normalPackage['byl_dev_pillow_kind'] = 'ordinary';
            $separated[] = $normalPackage;
        }

        $pillowPackage = $package;
        $pillowPackage['contents'] = $special;
        $pillowPackage['contents_cost'] = byl_dev_pillow_shipping_items_total($special);
        $pillowPackage['byl_dev_pillow_kind'] = 'pillow';
        $pillowPackage['byl_dev_pillow_quantity'] = byl_dev_pillow_shipping_qty($special);
        $separated[] = $pillowPackage;
    }
    return $separated;
}

/**
 * Resolve the existing enabled zone minimum before choosing a normal rate.
 * We never change a shipping zone or all-site BYL49 policy setting.
 * Returns null for non-minimum/coupon-only schemes to preserve their rules.
 */
function byl_dev_pillow_shipping_minimum($method): ?float {
    if (!is_object($method) || !method_exists($method, 'get_instance_id')) {
        return null;
    }
    $instance = (int) $method->get_instance_id();
    if ($instance <= 0) {
        return null;
    }
    $settings = get_option('woocommerce_free_shipping_' . $instance . '_settings', []);
    if (!is_array($settings)) {
        return null;
    }
    $requires = (string) ($settings['requires'] ?? '');
    if ($requires !== 'min_amount') {
        return null;
    }
    $minimum = $settings['min_amount'] ?? null;
    return is_numeric($minimum) ? max(0.0, (float) $minimum) : null;
}

/**
 * Filter at the very end, after the legacy BYL / Andy shipping promotions.
 * In mixed carts, the regular free method must not be unlocked by pillows.
 */
function byl_dev_pillow_shipping_rates(array $rates, array $package): array {
    if (!byl_dev_pillow_shipping_enabled()) {
        return $rates;
    }
    $kind = $package['byl_dev_pillow_kind'] ?? null;
    if ($kind === 'pillow') {
        $qty = (int) ($package['byl_dev_pillow_quantity'] ?? 0);
        if ($qty < 1) {
            return $rates;
        }
        $cost = 30.0 * $qty;

        // Preserve the *existing Woo flat-rate method identity* if available,
        // so native Cart/Checkout and order shipping lines stay Woo-owned.
        $rate = null;
        foreach ($rates as $candidate) {
            if (is_object($candidate) && method_exists($candidate, 'get_method_id')
                && $candidate->get_method_id() === 'flat_rate') {
                $rate = clone $candidate;
                break;
            }
        }
        if (!$rate) {
            // Some zones have no flat method; a valid, separately identified
            // shipping rate is required so free shipping cannot zero pillows.
            $rate = new WC_Shipping_Rate(
                'byl_dev_pillow_oversized', 'Oversized Pillow Shipping',
                $cost, [], 'byl_dev_pillow_oversized'
            );
        }
        if (method_exists($rate, 'set_label')) {
            $rate->set_label('Oversized Pillow Shipping');
        }
        if (method_exists($rate, 'set_cost')) {
            $rate->set_cost($cost);
        }
        if (method_exists($rate, 'set_taxes')) {
            $taxes = [];
            if (class_exists('WC_Tax') && method_exists('WC_Tax', 'get_shipping_tax_rates')
                && method_exists('WC_Tax', 'calc_shipping_tax')) {
                $taxes = WC_Tax::calc_shipping_tax($cost, WC_Tax::get_shipping_tax_rates());
            }
            $rate->set_taxes(is_array($taxes) ? $taxes : []);
        }
        return [$rate->get_id() => $rate];
    }

    if ($kind !== 'ordinary') {
        return $rates;
    }

    $normalSubtotal = byl_dev_pillow_shipping_items_total((array) ($package['contents'] ?? []));
    foreach ($rates as $key => $rate) {
        if (!is_object($rate) || !method_exists($rate, 'get_method_id')
            || $rate->get_method_id() !== 'free_shipping') {
            continue;
        }
        $minimum = byl_dev_pillow_shipping_minimum($rate);
        if ($minimum !== null && $normalSubtotal + 0.0001 < $minimum) {
            unset($rates[$key]);
        }
    }
    return $rates;
}

function byl_dev_pillow_shipping_boot(): void {
    if (!byl_dev_pillow_shipping_enabled()) {
        return;
    }
    add_filter('woocommerce_cart_shipping_packages', 'byl_dev_pillow_shipping_packages', 900, 1);
    add_filter('woocommerce_package_rates', 'byl_dev_pillow_shipping_rates', 99999, 2);
}
add_action('woocommerce_init', 'byl_dev_pillow_shipping_boot', 99);
