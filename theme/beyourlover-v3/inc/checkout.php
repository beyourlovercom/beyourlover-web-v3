<?php
/** Checkout field contract. Applies to classic Woo checkout and wc-ajax processing alike. */
defined('ABSPATH') || exit;

add_filter('woocommerce_checkout_fields', static function (array $fields): array {
    unset($fields['billing']['billing_phone']);
    unset($fields['shipping']['shipping_phone']);
    // Personal checkout: shipping company is suppressed; billing organization is optional.
    // The organization field is optional and collapsed by the Theme.
    // It remains a real Woo field (not a fake front-end input).
    unset($fields['shipping']['shipping_company']);
    if (isset($fields['billing']['billing_company'])) {
        $fields['billing']['billing_company']['required'] = false;
        $fields['billing']['billing_company']['priority'] = 110;
        $fields['billing']['billing_company']['class'] = ['form-row-wide'];
    }

    if (isset($fields['billing']['billing_email'])) {
        $fields['billing']['billing_email']['priority'] = 1;
        $fields['billing']['billing_email']['label'] = 'Email Address';
        $fields['billing']['billing_email']['class'] = ['form-row-wide'];
    }

    // Owner-locked Shipping sequence. Woo field priorities control native
    // server markup; Woo native field order is authoritative for standard Checkout.
    // The original email stays a real Woo field and is reparented above rates.
    // Never clone a second field or override Woo validation / country data.
    foreach ([
        'billing_country' => 5,
        'billing_first_name' => 10,
        'billing_last_name' => 20,
        'billing_address_1' => 40,
        'billing_address_2' => 45,
        'billing_city' => 55,
        'billing_state' => 60,
        'billing_postcode' => 85,
    ] as $id => $priority) {
        if (isset($fields['billing'][$id])) {
            $fields['billing'][$id]['priority'] = $priority;
        }
    }

    $fields['billing']['shipping_phone'] = [
        'type' => 'tel',
        'label' => 'Phone',
        'description' => 'We will only use this number in case we need to contact you regarding delivery purposes.',
        'required' => true,
        'priority' => 70,
        'class' => ['form-row-wide'],
        'autocomplete' => 'tel',
        'validate' => ['phone'],
    ];

    return $fields;
}, 9999);

add_action('wp', static function (): void {
    if (function_exists('is_checkout') && is_checkout() && !(function_exists('is_order_received_page') && is_order_received_page())) {
        remove_action('woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10);
    }
});

add_filter('woocommerce_form_field_args', static function (array $args, string $key): array {
    if ($key === 'shipping_phone') {
        $args['label'] = 'Phone';
        $args['description'] = 'We will only use this number in case we need to contact you regarding delivery purposes.';
    }
    return $args;
}, 99999, 2);


/**
 * Dev-only Cart Terms handoff UAT. Woo Classic Checkout normally renders a
 * required checkbox AND validates it on wc-ajax; hiding only the HTML would
 * cause blocked checkouts. Return no checkbox label in both request contexts.
 *
 * The Cart CTA now links directly to the actual Terms page and states the
 * assent disclosure. Scope strictly to dev.beyourlover.com; production
 * retains its existing checkout consent behavior until separate review.
 */
function byl_v3_dev_checkout_terms_handoff_enabled(): bool {
    return function_exists('home_url')
        && (string) wp_parse_url(home_url('/'), PHP_URL_HOST) === 'dev.beyourlover.com';
}

add_filter('woocommerce_checkout_show_terms', static function ($show) {
    return byl_v3_dev_checkout_terms_handoff_enabled() ? false : $show;
}, 20);

add_filter('woocommerce_get_terms_and_conditions_checkbox_text', static function ($text) {
    // wc_terms_and_conditions_checkbox_enabled() also uses this server-side,
    // so only when dev opt-in is active can a checkbox-less order be valid.
    return byl_v3_dev_checkout_terms_handoff_enabled() ? '' : $text;
}, 20);


/**
 * A second, non-interactive Shipping choice is shown only when this package's
 * CURRENT Woo zone has an enabled amount-gated free rate not yet eligible.
 * The real Woo rate inputs, chosen methods, thresholds, discounts and totals
 * remain authoritative; this is display metadata, never a checkout rate.
 *
 * This hook is rendered inside the AJAX-refreshed review-order <tfoot>,
 * so moving Country/State also refreshes the honest locked-rate notice.
 */
add_action('woocommerce_review_order_after_shipping', static function (): void {
    if (!function_exists('is_checkout') || !is_checkout() || !class_exists('WC_Shipping_Zones')
        || !function_exists('WC') || !WC()->shipping()) {
        return;
    }

    $packages = WC()->shipping()->get_packages();
    if (!is_array($packages)) {
        return;
    }
    foreach ($packages as $package_index => $package) {
        if (!is_array($package) || empty($package['destination']['country'])) {
            continue;
        }
        // Future WWW special and PILLOW packages are mandatory paid
        // shipments. The disabled "Free shipping available from $X" card
        // belongs only to ordinary merchandise; it must never be shown next
        // to a $30 exceptional shipment or $30-per-unit PILLOW package.
        // This is presentation-only; actual Woo rates/totals are untouched.
        // On existing Dev/WWW installations without the new kind marker,
        // this branch stays behaviorally identical.
        if (in_array((string)($package['byl_www_ship_kind'] ?? ''),
            ['special', 'pillow'], true)) {
            continue;
        }

        // Dev-only PILLOW package always charges $30 per item. Showing an
        // amount-gated Free Shipping placeholder here would promise a method
        // that Woo never offers for this specific oversized package.
        // Also mark the read-only Checkout promo so its copy never claims
        // all packages are free when a paid oversized package is present.
        if (function_exists('byl_dev_pillow_shipping_enabled')
            && byl_dev_pillow_shipping_enabled()
            && ($package['byl_dev_pillow_kind'] ?? '') === 'pillow') {
            echo '<tr class="byl-dev-pillow-package-source" hidden style="display:none"'
               . ' data-byl-pillow-package-index="' . esc_attr((string) $package_index) . '">'
               . '<td colspan="2"></td></tr>';
            continue;
        }

        $available_rates = isset($package['rates']) && is_array($package['rates'])
            ? $package['rates'] : [];
        $free_available = false;
        foreach ($available_rates as $rate) {
            if ($rate instanceof WC_Shipping_Rate && $rate->get_method_id() === 'free_shipping') {
                $free_available = true;
                break;
            }
        }
        if ($free_available) {
            continue; // Never duplicate an actually selectable Woo free rate.
        }

        $zone = WC_Shipping_Zones::get_zone_matching_package($package);
        if (!$zone instanceof WC_Shipping_Zone) {
            continue;
        }
        foreach ($zone->get_shipping_methods(true) as $method) {
            if ($method->id !== 'free_shipping'
                || $method->get_option('requires', '') !== 'min_amount') {
                continue;
            }
            $minimum = wc_format_decimal($method->get_option('min_amount', '0'));
            if (!is_numeric($minimum) || (float) $minimum <= 0) {
                continue;
            }
            $threshold = html_entity_decode(
                wp_strip_all_tags(wc_price((float) $minimum)),
                ENT_QUOTES,
                get_bloginfo('charset') ?: 'UTF-8'
            );
            // The free rate is not in the actual Woo rate list. A disabled
            // guidance card can therefore explain the genuine zone threshold.
            echo '<tr class="byl-locked-free-rate-source"'
               . ' data-byl-package-index="' . esc_attr((string) $package_index) . '"'
               . ' data-byl-free-threshold="' . esc_attr($threshold) . '">'
               . '<td colspan="2"><span class="screen-reader-text">'
               . esc_html(sprintf('Free shipping requires an eligible order subtotal of at least %s.', $threshold))
               . '</span></td></tr>';
            break;
        }
    }
}, 20);

/** Mexico RFC is a real native Woo field, not a visual-only placeholder. */
require_once __DIR__ . '/checkout-mx-rfc.php';

/** Real separate billing and consent-gated account address persistence. */
require_once __DIR__ . '/checkout-address-contract.php';
