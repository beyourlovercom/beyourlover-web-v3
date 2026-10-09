<?php
/**
 * P7.1 Thank You wiring — independent of G3 payment/checkout JS.
 */
declare(strict_types=1);
defined('ABSPATH') || exit;
require_once __DIR__.'/thank-you-estimate.php';

add_action('wp_enqueue_scripts', static function (): void {
    if (!function_exists('is_order_received_page') || !is_order_received_page()) { return; }
    $path = '/assets/css/thank-you.css';
    $full = get_stylesheet_directory().$path;
    wp_enqueue_style(
        'byl-v3-thank-you',
        get_stylesheet_directory_uri().$path,
        ['byl-v3-motion'],
        is_file($full) ? (string) filemtime($full) : (string) wp_get_theme()->get('Version')
    );
}, 25);

function byl_v3_ty_order_eta($order): array {
    $country = strtoupper((string) ($order->get_shipping_country() ?: $order->get_billing_country()));
    $created = $order->get_date_created();
    $date = $created ? $created->date('Y-m-d') : '';
    $extra = apply_filters('byl_v3_ty_holidays', [], $country, $order);
    return byl_v3_ty_estimate($date, $country, is_array($extra) ? $extra : []);
}

function byl_v3_ty_date_label(string $iso): string {
    try {
        return (new DateTimeImmutable($iso, new DateTimeZone('UTC')))->format('M j, Y');
    } catch (Throwable $e) {
        return '';
    }
}

/** Do not silently claim payment success for failed, unpaid or cancelled orders. */
function byl_v3_ty_is_paid_success($order): bool {
    return $order && method_exists($order, 'is_paid')
        && $order->is_paid()
        && $order->has_status(['processing', 'completed']);
}
