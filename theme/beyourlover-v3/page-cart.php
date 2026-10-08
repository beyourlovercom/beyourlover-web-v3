<?php
/**
 * BYL V3 Cart V2 — focused commerce shell and native Woo Cart runtime.
 * All prices, shipping, quantities, coupons, and totals remain owned by WooCommerce.
 * Historical page content with Smart Coupons / BOGO is intentionally not rendered.
 */
declare(strict_types=1);
defined('ABSPATH') || exit;
get_header('cart');
?>
<main id="main-content" class="byl-content byl-commerce-content byl-cart-page">
  <header class="byl-cart-heading">
    <a class="byl-cart-continue" href="<?php echo esc_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/')); ?>">← <?php esc_html_e('Continue shopping', 'beyourlover-v3'); ?></a>
    <h1><?php esc_html_e('Your Cart', 'beyourlover-v3'); ?></h1>
    <p><?php esc_html_e('Review your items, update quantities, then continue to secure checkout.', 'beyourlover-v3'); ?></p>
  </header>
  <section class="byl-cart-runtime" aria-label="<?php esc_attr_e('Shopping cart', 'beyourlover-v3'); ?>">
    <?php
    if (function_exists('WC')) {
        echo do_shortcode('[woocommerce_cart]'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    } else {
        echo '<p>'.esc_html__('The shopping cart is temporarily unavailable.', 'beyourlover-v3').'</p>';
    }
    ?>
  </section>
</main>
<?php get_footer('cart'); ?>
