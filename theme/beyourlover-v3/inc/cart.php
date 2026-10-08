<?php
/** Cart rendering cleanup: preserve the canonical Woo Cart Block and discard legacy page residue. */
declare(strict_types=1);
defined('ABSPATH') || exit;

/**
 * The Dev cart page still contains historical Smart Coupons / BOGO HTML after
 * the canonical Woo Cart Block. Keep WooCommerce as cart authority while
 * ensuring obsolete page-embedded markup cannot render.
 */
function byl_v3_clean_cart_page_content(string $content): string {
    if (!function_exists('is_cart') || !is_cart()) {
        return $content;
    }
    if (function_exists('is_admin') && is_admin()) {
        return $content;
    }

    $content = preg_replace(
        '#<!--\s*wp:woocommerce-smart-coupons/available-coupons\s*/-->#i',
        '',
        $content
    ) ?? $content;

    $closing = '<!-- /wp:woocommerce/cart -->';
    $position = strpos($content, $closing);
    if ($position !== false) {
        $content = substr($content, 0, $position + strlen($closing));
    }

    return trim($content);
}
add_filter('the_content', 'byl_v3_clean_cart_page_content', 8);
