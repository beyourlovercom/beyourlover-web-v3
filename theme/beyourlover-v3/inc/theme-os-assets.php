<?php
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }
/**
 * BeYourLover Theme OS T2.2: original WordPress route asset callback.
 * Handles, route guards, hook order, versions and Store API localization
 * are preserved verbatim. Woo remains the cart/payment authority.
 */
add_action('wp_enqueue_scripts', static function (): void {
    $theme_version = wp_get_theme()->get('Version');
    $dir = get_stylesheet_directory(); $uri = get_stylesheet_directory_uri();
    $v = static function (string $r) use ($dir, $theme_version): string { $p=$dir.'/'.ltrim($r,'/'); return is_file($p)?(string)filemtime($p):(string)$theme_version; };
    wp_enqueue_style('byl-v3-foundation', get_stylesheet_uri(), [], $v('style.css'));
    wp_enqueue_style('byl-v3-tokens', $uri.'/assets/css/tokens.css', ['byl-v3-foundation'], $v('assets/css/tokens.css'));
    wp_enqueue_style('byl-v3-components', $uri.'/assets/css/components.css', ['byl-v3-tokens'], $v('assets/css/components.css'));
    wp_enqueue_style('byl-v3-component-library', $uri.'/assets/css/component-library.css', ['byl-v3-components'], $v('assets/css/component-library.css'));
    wp_enqueue_style('byl-v3-shell', $uri.'/assets/css/shell.css', ['byl-v3-component-library'], $v('assets/css/shell.css'));
    wp_enqueue_style('byl-v3-motion', $uri.'/assets/css/motion.css', ['byl-v3-shell'], $v('assets/css/motion.css'));
    wp_enqueue_script('byl-v3-shell', $uri.'/assets/js/shell.js', [], $v('assets/js/shell.js'), true);
    wp_script_add_data('byl-v3-shell', 'strategy', 'defer');
    if (is_front_page()) { wp_enqueue_style('byl-v3-homepage', $uri.'/assets/css/homepage.css', ['byl-v3-motion'], $v('assets/css/homepage.css')); }
    if (function_exists('is_shop') && (is_shop() || (function_exists('is_product_taxonomy') && is_product_taxonomy()))) { wp_enqueue_style('byl-v3-archive', $uri.'/assets/css/archive.css', ['byl-v3-motion'], $v('assets/css/archive.css')); }
    if (function_exists('is_product') && is_product()) {
        wp_enqueue_style('byl-v3-product', $uri.'/assets/css/product.css', ['byl-v3-motion'], $v('assets/css/product.css'));
        wp_enqueue_script('byl-v3-product', $uri.'/assets/js/product.js', [], $v('assets/js/product.js'), true);
        wp_script_add_data('byl-v3-product', 'strategy', 'defer');
    }
    if (is_page('free-shipping') || is_page('free-shipping-guide')) {
        wp_enqueue_style('byl-v3-free-shipping', $uri.'/assets/css/free-shipping.css', ['byl-v3-motion'], $v('assets/css/free-shipping.css'));
        wp_enqueue_script('byl-v3-free-shipping-v2', $uri.'/assets/js/free-shipping-v2.js', [], $v('assets/js/free-shipping-v2.js'), true);
        wp_script_add_data('byl-v3-free-shipping-v2', 'strategy', 'defer');
    }
    if (is_page('track-your-order')) {
        wp_enqueue_style('byl-v3-track-order', $uri.'/assets/css/track-order.css', ['byl-v3-motion'], $v('assets/css/track-order.css'));
    }
    if (function_exists('is_cart') && is_cart()) {
        wp_enqueue_style('byl-v3-cart', $uri.'/assets/css/cart.css', ['byl-v3-motion'], $v('assets/css/cart.css'));
        wp_enqueue_style('byl-v3-cart-focus', $uri.'/assets/css/cart-focus.css', ['byl-v3-cart'], $v('assets/css/cart-focus.css'));
        wp_enqueue_script('byl-v3-cart-experience', $uri.'/assets/js/cart-experience.js', [], $v('assets/js/cart-experience.js'), true);
        wp_enqueue_script('byl-v3-cart', $uri.'/assets/js/cart.js', ['wp-data', 'wc-blocks-data-store'], $v('assets/js/cart.js'), true);
        wp_localize_script('byl-v3-cart', 'BYLShippingPromotionCart', [
            'applyUrl' => function_exists('rest_url') ? rest_url('wc/store/v1/cart/apply-coupon') : '',
            'removeUrl' => function_exists('rest_url') ? rest_url('wc/store/v1/cart/remove-coupon') : '',
            'nonce' => function_exists('wp_create_nonce') ? wp_create_nonce('wc_store_api') : '',
        ]);
        wp_script_add_data('byl-v3-cart', 'strategy', 'defer');
    }
    if (function_exists('is_checkout') && is_checkout() && !is_order_received_page()) {
        wp_enqueue_style('byl-v3-checkout', $uri.'/assets/css/checkout.css', ['byl-v3-motion'], $v('assets/css/checkout.css'));
        wp_enqueue_style('byl-v3-checkout-shell', $uri.'/assets/css/checkout-shell.css', ['byl-v3-checkout'], $v('assets/css/checkout-shell.css'));
        $checkout_dependencies = ['jquery'];
        if (wp_script_is('wc-checkout', 'registered') || wp_script_is('wc-checkout', 'enqueued')) {
            $checkout_dependencies[] = 'wc-checkout';
        }
        wp_enqueue_script('byl-v3-checkout', $uri.'/assets/js/checkout.js', $checkout_dependencies, $v('assets/js/checkout.js'), true);
        wp_localize_script('byl-v3-checkout', 'BYLShippingPromotionCheckout', [
            'cartUrl' => function_exists('rest_url') ? rest_url('wc/store/v1/cart') : '',
            'applyUrl' => function_exists('rest_url') ? rest_url('wc/store/v1/cart/apply-coupon') : '',
            'removeUrl' => function_exists('rest_url') ? rest_url('wc/store/v1/cart/remove-coupon') : '',
            'nonce' => function_exists('wp_create_nonce') ? wp_create_nonce('wc_store_api') : '',
        ]);
        wp_script_add_data('byl-v3-checkout', 'strategy', 'defer');
        // Dev-only native route Checkout Drawer. The Woo checkout shortcode
        // remains rendered exactly once; no iframe, gateway clone or order write.
        if (function_exists('byl_v3_checkout_drawer_enabled') && byl_v3_checkout_drawer_enabled()) {
            wp_enqueue_style('byl-v3-checkout-drawer', $uri.'/assets/css/checkout-drawer.css',
                ['byl-v3-checkout-shell'], $v('assets/css/checkout-drawer.css'));
            wp_enqueue_script('byl-v3-checkout-drawer', $uri.'/assets/js/checkout-drawer.js',
                ['byl-v3-checkout'], $v('assets/js/checkout-drawer.js'), true);
            wp_localize_script('byl-v3-checkout-drawer', 'BYLCheckoutDrawerConfig', [
                'fallbackUrl' => function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/'),
                'checkoutUrl' => function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : home_url('/checkout/'),
            ]);
            wp_script_add_data('byl-v3-checkout-drawer', 'strategy', 'defer');
        }
    }
    // CSS-only Order Pay treatment follows Checkout styles; no gateway changes.
    if (function_exists('is_checkout_pay_page') && is_checkout_pay_page()) {
        wp_enqueue_style('byl-v3-order-pay', $uri.'/assets/css/order-pay.css', ['byl-v3-checkout-shell'], $v('assets/css/order-pay.css'));
    }
});
