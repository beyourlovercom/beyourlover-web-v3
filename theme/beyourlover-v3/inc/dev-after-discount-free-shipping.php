<?php
/**
 * Dev-only isolated post-discount free-shipping compatibility experiment.
 *
 * The canonical policy engine belongs to Andy Commerce, not to the Theme.
 * This narrow staging bridge exposes the owner-approved AFTER-coupon policy
 * without modifying any shared plugin or Woo shipping zone configuration.
 * Do not ship this diagnostic adapter on WWW; Owner handles all WWW changes.
 */
declare(strict_types=1);
defined('ABSPATH') || exit;

function byl_dev_after_discount_enabled(): bool {
    return function_exists('home_url')
        && function_exists('wp_get_environment_type')
        && function_exists('wp_parse_url')
        && wp_parse_url(home_url('/'), PHP_URL_HOST) === 'dev.beyourlover.com'
        && wp_get_environment_type() === 'staging'
        && function_exists('WC')
        && is_object(WC());
        // Woo's cart session can be uninitialized at woocommerce_init;
        // defer existence checks until the actual shipping/REST callback.
}

/** The separate Dev PILLOW adapter already owns all mixed-cart packages. */
function byl_dev_after_discount_has_pillow(array $cart): bool {
    foreach ($cart as $item) {
        if (is_array($item) && (int) ($item['product_id'] ?? 0) === 38047) {
            return true;
        }
    }
    return false;
}

/** Do not override untested BYL49's special coupon/zone contract. */
function byl_dev_after_discount_byl49_active(): bool {
    $cart = WC()->cart ?? null;
    if (!is_object($cart) || !method_exists($cart, 'get_applied_coupons')) {
        return false;
    }
    foreach ((array) $cart->get_applied_coupons() as $coupon) {
        if (strtolower((string) $coupon) === 'byl49') {
            return true;
        }
    }
    return false;
}

/** Woo line_total = item merchandise AFTER coupon, BEFORE tax and shipping. */
function byl_dev_after_discount_subtotal(array $contents): float {
    $sum = 0.0;
    foreach ($contents as $item) {
        if (!is_array($item)) { continue; }
        $sum += max(0.0, (float) ($item['line_total'] ?? 0));
    }
    return round($sum, function_exists('wc_get_price_decimals') ? wc_get_price_decimals() : 2);
}

/**
 * Only amount-gated core Woo Free Shipping. Never suppress coupon-only or
 * coupon-OR-amount methods (legitimate coupon relief could override minimum).
 */
function byl_dev_after_discount_rate_minimum($rate): ?float {
    if (!is_object($rate) || !method_exists($rate, 'get_method_id')
        || $rate->get_method_id() !== 'free_shipping'
        || !method_exists($rate, 'get_instance_id')) {
        return null;
    }
    $id = (int) $rate->get_instance_id();
    if ($id <= 0) { return null; }
    $opts = get_option('woocommerce_free_shipping_' . $id . '_settings', []);
    if (!is_array($opts)) { return null; }
    $requires = (string) ($opts['requires'] ?? '');
    if ($requires !== 'min_amount' && $requires !== 'both') {
        return null;
    }
    $min = $opts['min_amount'] ?? null;
    return is_numeric($min) ? max(0.0, (float) $min) : null;
}

/**
 * Woo free-shipping method minima are stored in the site's base USD, while
 * Woo cart line totals are already converted into the shopper currency by
 * YayCurrency Pro. Forward-convert the USD threshold only; never inverse-FX
 * cart subtotals, and never change the native shipping method/rate authority.
 */
function byl_dev_after_discount_display_threshold(float $usdMinimum): ?float {
    if (!function_exists('get_option')
        || (string)get_option('woocommerce_currency', 'USD') !== 'USD'
        || $usdMinimum < 0 || !is_finite($usdMinimum)) {
        return null;
    }
    $currency = 'USD';
    $helper = 'Yay_Currency\\Helpers\\YayCurrencyHelper';
    if (class_exists($helper)) {
        if (!method_exists($helper, 'detect_current_currency')) return null;
        try { $selected = $helper::detect_current_currency(); }
        catch (Throwable $e) { return null; }
        if (!is_array($selected) || (int)($selected['status'] ?? 0) !== 1) return null;
        $currency = (string)($selected['currency'] ?? '');
        if (!in_array($currency, ['USD','EUR','GBP','AUD','CAD'], true)) return null;
    }
    $amount = $usdMinimum;
    if ($currency !== 'USD') {
        if (!function_exists('has_filter') || has_filter('yay_currency_convert_price') === false
            || !function_exists('apply_filters')) return null;
        try {
            $amount = apply_filters(
                'yay_currency_convert_price',
                number_format($usdMinimum, 2, '.', '')
            );
        } catch (Throwable $e) { return null; }
    }
    if (!is_scalar($amount) || !is_numeric($amount)
        || !is_finite((float)$amount) || (float)$amount < 0
        || (float)$amount > 1000000) return null;
    return round((float)$amount, function_exists('wc_get_price_decimals') ? wc_get_price_decimals() : 2);
}

/**
 * Used only for a one-cent FX rounding collision at the exact threshold.
 * With coupons or nonstandard/dynamic prices, no USD entitlement is guessed.
 */
function byl_dev_after_discount_source_without_coupon(array $contents): ?float {
    $cart = WC()->cart ?? null;
    if (!is_object($cart) || !method_exists($cart,'get_applied_coupons')
        || (array)$cart->get_applied_coupons()) return null;
    $usd=0.0;
    foreach ($contents as $item) {
        if (!is_array($item) || !is_object($item['data'] ?? null)) return null;
        $p=$item['data'];
        if (!method_exists($p,'get_price')) return null;
        $q=$item['quantity'] ?? null;
        $base=$p->get_price('edit');
        $view=$p->get_price('view');
        $after=$item['line_total'] ?? null;
        if (!is_numeric($q) || $q<=0 || $q>100
            || !is_numeric($base) || !is_numeric($view) || !is_numeric($after)) return null;
        if (abs(round((float)$view*(float)$q,2)-(float)$after)>0.011) return null;
        $usd += (float)$base*(float)$q;
    }
    return round($usd, 2);
}

function byl_dev_after_discount_rates(array $rates, array $package): array {
    if (!byl_dev_after_discount_enabled() || byl_dev_after_discount_byl49_active()
        || ($package['byl_dev_pillow_kind'] ?? '') === 'pillow'
        || !is_array($package['contents'] ?? null)) {
        return $rates;
    }
    $cart = WC()->cart;
    if (is_object($cart) && method_exists($cart, 'get_cart')
        && byl_dev_after_discount_has_pillow((array) $cart->get_cart())) {
        // PR #150 correctly calculates both packages for mixed PILLOW carts.
        return $rates;
    }
    $postDiscount = byl_dev_after_discount_subtotal($package['contents']);
    foreach ($rates as $key => $rate) {
        $min = byl_dev_after_discount_rate_minimum($rate);
        if ($min === null) { continue; }
        $minDisplayed = byl_dev_after_discount_display_threshold($min);
        // Unsupported or unverified FX: never grant a free rate from this gate.
        if ($minDisplayed === null || $postDiscount + 0.000001 < $minDisplayed) {
            unset($rates[$key]);
            continue;
        }
        // The same rounded GBP/EUR minor unit can represent USD 68.99 and 69.
        // Accept an equality edge only with verified no-coupon source USD.
        $helper = 'Yay_Currency\\Helpers\\YayCurrencyHelper';
        $selectedCurrency = 'USD';
        if (class_exists($helper) && method_exists($helper, 'detect_current_currency')) {
            try {
                $selected = $helper::detect_current_currency();
                $selectedCurrency = is_array($selected) ? (string)($selected['currency'] ?? '') : '';
            } catch (Throwable $e) { $selectedCurrency = ''; }
        }
        if ($selectedCurrency !== 'USD' && abs($postDiscount - $minDisplayed) <= 0.005) {
            $verifiedUSD = byl_dev_after_discount_source_without_coupon($package['contents']);
            if ($verifiedUSD === null || $verifiedUSD + 0.000001 < $min) {
                unset($rates[$key]);
            }
        }
    }
    return $rates;
}

/**
 * Make native Cart/Drawer Store API progress truthful for ordinary-only carts.
 * Only re-label existing BYL promotion metadata, NEVER touch actual WC totals.
 */
function byl_dev_after_discount_promo_correct(array $promo, array $cartItems): array {
    if (empty($promo['active']) || !is_numeric($promo['threshold'] ?? null)
        || byl_dev_after_discount_has_pillow($cartItems)
        || byl_dev_after_discount_byl49_active()) {
        return $promo;
    }
    $subtotal = byl_dev_after_discount_subtotal($cartItems);
    // New Commerce exposes shopper display threshold with basis_currency=USD;
    // legacy Commerce exposes the same field in source USD. Do not double-FX.
    $newCommerce = ($promo['basis_currency'] ?? '') === 'USD';
    $min = $newCommerce
        ? max(0.0, (float)$promo['threshold'])
        : byl_dev_after_discount_display_threshold(max(0.0, (float)$promo['threshold']));
    if ($min === null) {
        $promo['eligible'] = false;
        $promo['unlocked'] = false;
        $promo['state'] = 'unverified_fx_threshold';
        return $promo;
    }
    $promo['threshold'] = $min;
    $precision = function_exists('wc_get_price_decimals') ? wc_get_price_decimals() : 2;
    $isEligible = $subtotal + 0.000001 >= $min;
    $mode = (string) ($promo['mode'] ?? 'automatic');
    $policyCodeApplied = !empty($promo['policy_code_applied']);
    $otherCode = (string) ($promo['applied_code'] ?? '');
    $promo['subtotal'] = $subtotal;
    $promo['remaining'] = max(0.0, round($min - $subtotal, $precision));
    $promo['eligible'] = $isEligible;
    $promo['unlocked'] = $isEligible && ($mode !== 'require_code' || $policyCodeApplied);
    if (!$isEligible) {
        $promo['state'] = $mode === 'require_code' && $policyCodeApplied ? 'require_code_applied' : 'below_threshold';
    } elseif ($mode !== 'require_code') {
        $promo['state'] = $otherCode !== '' ? 'automatic_code' : 'automatic_unlocked';
    } elseif ($policyCodeApplied) {
        $promo['state'] = 'require_code_unlocked';
    } elseif ($otherCode !== '') {
        $promo['state'] = 'require_code_other';
    } else {
        $promo['state'] = 'require_code_available';
    }
    return $promo;
}

function byl_dev_after_discount_rest($response, $server, $request) {
    if (!byl_dev_after_discount_enabled() || !is_object($request)
        || !method_exists($request, 'get_route')
        || !preg_match('#^/wc/store/v1/cart(?:/[^/?]+)?$#D', (string) $request->get_route())
        || !is_object($response) || !method_exists($response, 'get_data')
        || !method_exists($response, 'set_data')
        || !is_object(WC()->cart) || !method_exists(WC()->cart, 'get_cart')) {
        return $response;
    }
    $data = $response->get_data();
    if (!is_array($data)) { return $response; }
    $rawExtensions = $data['extensions'] ?? null;
    $extensions = is_object($rawExtensions) ? get_object_vars($rawExtensions) : $rawExtensions;
    if (!is_array($extensions)) { return $response; }
    $rawPromo = $extensions['byl-shipping-promotion'] ?? null;
    $promo = is_object($rawPromo) ? get_object_vars($rawPromo) : $rawPromo;
    if (!is_array($promo)) { return $response; }
    $fixed = byl_dev_after_discount_promo_correct($promo, (array) WC()->cart->get_cart());
    if ($fixed !== $promo) {
        $extensions['byl-shipping-promotion'] = is_object($rawPromo) ? (object) $fixed : $fixed;
        $data['extensions'] = is_object($rawExtensions) ? (object) $extensions : $extensions;
        $response->set_data($data);
    }
    return $response;
}

function byl_dev_after_discount_boot(): void {
    if (!byl_dev_after_discount_enabled()) { return; }
    // After all ordinary plugins and PILLOW adapter's 99999 rate filter.
    add_filter('woocommerce_package_rates', 'byl_dev_after_discount_rates', 100000, 2);
    add_filter('rest_post_dispatch', 'byl_dev_after_discount_rest', 10000, 3);
}
add_action('woocommerce_init', 'byl_dev_after_discount_boot', 99);
