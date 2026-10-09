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

/**
 * WooPayments and other gateways may provide HTML (logo <img> + masked card)
 * as a payment title in browser contexts. Render plain text, never raw HTML,
 * and never escape HTML tags into a visible order-information paragraph.
 */
function byl_v3_ty_payment_label(string $title): string {
    $decoded = html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = trim((string) preg_replace('/\s+/u', ' ', wp_strip_all_tags($decoded)));
    $has_markup = str_contains($decoded, '<');

    // A plain Woo title, such as "Visa credit card", must remain unchanged.
    if (!$has_markup) {
        return $text !== '' ? $text : 'Pending confirmation';
    }

    // Markup-only labels (for example <span>PayPal</span>) are not cards.
    if (!preg_match('/<img\b/i', $decoded)) {
        return $text !== '' ? $text : 'Pending confirmation';
    }

    $brand = '';
    if (preg_match('~<img\b[^>]*\bsrc\s*=\s*["\x27]([^"\x27]+)~i', $decoded, $src_match)) {
        $path = (string) parse_url($src_match[1], PHP_URL_PATH);
        $file = strtolower((string) pathinfo($path, PATHINFO_FILENAME));
        $brands = [
            'visa' => 'Visa',
            'mastercard' => 'Mastercard',
            'master-card' => 'Mastercard',
            'amex' => 'American Express',
            'americanexpress' => 'American Express',
            'discover' => 'Discover',
            'jcb' => 'JCB',
            'maestro' => 'Maestro',
            'unionpay' => 'UnionPay',
        ];
        $brand = $brands[$file] ?? '';
    }
    if ($brand === '' && preg_match('~<img\b[^>]*\balt\s*=\s*["\x27]([^"\x27]*)~i', $decoded, $alt_match)) {
        $brand = trim(wp_strip_all_tags($alt_match[1]));
    }
    if ($brand === '') { $brand = 'Card'; }

    // Avoid showing the gateway's logo URL, SVG markup or HTML tag text.
    // Only the brand and the already-masked last four digits are needed.
    if (preg_match('/(?:^|\D)(\d{4})\s*$/u', $text, $digits)) {
        return $brand.' •••• '.$digits[1];
    }
    if ($text !== '' && stripos($text, $brand) !== 0) {
        return $brand.' '.$text;
    }
    return $text !== '' ? $text : $brand;
}
