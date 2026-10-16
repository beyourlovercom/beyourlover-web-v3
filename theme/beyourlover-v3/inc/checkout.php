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
    // server markup; the scoped Drawer CSS is the defensive visual fallback.
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
 * Option B: render one native Woo Classic Checkout form in a real visual
 * drawer on the secure checkout route. This is intentionally Dev/staging only.
 * No iframe, AJAX payment-form cloning, payment/session alteration or WWW flag.
 */
function byl_v3_checkout_drawer_enabled(): bool {
    if (is_admin() || !function_exists('is_checkout') || !is_checkout()) { return false; }
    if (!function_exists('wp_get_environment_type') || wp_get_environment_type() !== 'staging') { return false; }
    if ((string) wp_parse_url(home_url('/'), PHP_URL_HOST) !== 'dev.beyourlover.com') { return false; }
    if (!isset($_GET['byl_checkout_drawer'])
        || (string) wp_unslash($_GET['byl_checkout_drawer']) !== '1') { return false; }
    if ((function_exists('is_order_received_page') && is_order_received_page())
        || (function_exists('is_checkout_pay_page') && is_checkout_pay_page())
        || (function_exists('is_wc_endpoint_url') && is_wc_endpoint_url('order-pay'))) { return false; }
    return true;
}
add_filter('body_class', static function (array $classes): array {
    if (byl_v3_checkout_drawer_enabled()) { $classes[] = 'byl-checkout-drawer-mode'; }
    return $classes;
});
