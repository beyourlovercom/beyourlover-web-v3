<?php
/**
 * Plugin Name: BYL Dev Checkout Gateway P0 - Read-only Audit
 * Description: Inert source-only diagnostic. Do not install without separate Dev-only Owner approval.
 * Version: 0.1.0-draft
 */
defined('ABSPATH') || exit;

/** Deny public, production, AJAX, CLI, order-pay and non-admin checkout requests. */
function byl_p0_payment_audit_allowed(): bool {
    if (is_admin() || strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'GET') {
        return false;
    }
    if (!function_exists('wp_get_environment_type') || wp_get_environment_type() !== 'staging') {
        return false;
    }
    if (!function_exists('home_url') || (string) wp_parse_url(home_url('/'), PHP_URL_HOST) !== 'dev.beyourlover.com') {
        return false;
    }
    if (!function_exists('is_checkout') || !is_checkout() || !current_user_can('manage_woocommerce')) {
        return false;
    }
    if ((function_exists('is_order_received_page') && is_order_received_page())
        || (function_exists('is_checkout_pay_page') && is_checkout_pay_page())
        || (function_exists('is_wc_endpoint_url') && is_wc_endpoint_url('order-pay'))) {
        return false;
    }
    return function_exists('WC') && WC() && WC()->cart && WC()->payment_gateways();
}

function byl_p0_payment_audit_ids($gateways): array {
    if (!is_array($gateways)) {
        return [];
    }
    return array_values(array_filter(array_map('strval', array_keys($gateways)), static function ($id) {
        return (bool) preg_match('/^[a-z0-9_-]{1,90}$/i', $id);
    }));
}

/** Capture before and after other Woo filters, preserving original data exactly. */
add_filter('woocommerce_available_payment_gateways', static function ($gateways) {
    if (byl_p0_payment_audit_allowed()) {
        $GLOBALS['byl_p0_audit_before'] = $GLOBALS['byl_p0_audit_before'] ?? [];
        if (count($GLOBALS['byl_p0_audit_before']) < 8) {
            $GLOBALS['byl_p0_audit_before'][] = byl_p0_payment_audit_ids($gateways);
        }
    }
    return $gateways;
}, -999999, 1);

add_filter('woocommerce_available_payment_gateways', static function ($gateways) {
    if (byl_p0_payment_audit_allowed()) {
        $GLOBALS['byl_p0_audit_after'] = $GLOBALS['byl_p0_audit_after'] ?? [];
        if (count($GLOBALS['byl_p0_audit_after']) < 8) {
            $GLOBALS['byl_p0_audit_after'][] = byl_p0_payment_audit_ids($gateways);
        }
    }
    return $gateways;
}, 999999, 1);

function byl_p0_payment_audit_callbacks(): array {
    global $wp_filter;
    if (!isset($wp_filter['woocommerce_available_payment_gateways'])) {
        return [];
    }
    $out = [];
    foreach ($wp_filter['woocommerce_available_payment_gateways']->callbacks as $priority => $rows) {
        foreach ($rows as $row) {
            $fn = $row['function'] ?? null;
            if (is_string($fn)) {
                $name = $fn;
            } elseif (is_array($fn) && count($fn) === 2) {
                $name = (is_object($fn[0]) ? get_class($fn[0]) : (string) $fn[0]) . '::' . (string) $fn[1];
            } elseif ($fn instanceof Closure) {
                $name = 'Closure';
            } else {
                $name = 'other callable';
            }
            $out[] = ['priority' => (int) $priority, 'callback' => substr($name, 0, 120)];
            if (count($out) >= 50) {
                return $out;
            }
        }
    }
    return $out;
}

/** JSON-only, admin-visible, non-executable evidence at end of native Woo Checkout. */
add_action('wp_footer', static function (): void {
    if (!byl_p0_payment_audit_allowed()) {
        return;
    }
    $registered = [];
    foreach (WC()->payment_gateways()->payment_gateways() as $id => $gateway) {
        if (!is_object($gateway) || !preg_match('/^[a-z0-9_-]{1,90}$/i', (string) $id)) {
            continue;
        }
        $registered[] = ['id' => (string) $id, 'enabled' => 'yes' === (string) ($gateway->enabled ?? 'no')];
    }
    $country = WC()->customer ? (string) WC()->customer->get_billing_country() : '';
    $country = preg_match('/^[A-Z]{2}$/', $country) ? $country : '';
    $trace = [
        'scope' => 'dev-staging-admin-get-only',
        'drawer' => isset($_GET['byl_checkout_drawer']) && '1' === (string) wp_unslash($_GET['byl_checkout_drawer']),
        'currency_code' => function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : null,
        'billing_country_code' => $country,
        'needs_payment' => (bool) WC()->cart->needs_payment(),
        'registered' => $registered,
        'before_other_filters' => $GLOBALS['byl_p0_audit_before'] ?? [],
        'after_other_filters' => $GLOBALS['byl_p0_audit_after'] ?? [],
        'callback_names' => byl_p0_payment_audit_callbacks(),
    ];
    echo '<script type="application/json" id="byl-p0-payment-audit">'
        . wp_json_encode($trace, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
        . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}, 999999);
