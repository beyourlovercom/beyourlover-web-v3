<?php
/**
 * N6 isolated empty-cart presentation template (WooCommerce remains authority).
 * Loaded ONLY by the opt-in woocommerce_locate_template gate.
 */
declare(strict_types=1);
defined('ABSPATH') || exit;

// Preserve third-party Woo hooks/notices; do not swallow messages or errors.
do_action('woocommerce_cart_is_empty');

$shop_url = function_exists('wc_get_page_permalink')
    ? wc_get_page_permalink('shop')
    : home_url('/shop/');
$shop_url = apply_filters('woocommerce_return_to_shop_redirect', $shop_url);
if (!is_string($shop_url) || $shop_url === '') { $shop_url = home_url('/shop/'); }
?>
<section class="byl-empty-state byl-n6-empty-cart" aria-labelledby="byl-cart-empty-heading">
  <div class="byl-empty-state__symbol" aria-hidden="true">
    <svg viewBox="0 0 48 48" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.6" focusable="false">
      <path d="M12 18h24l-2 23H14l-2-23Z"/>
      <path d="M18 19v-6a6 6 0 0 1 12 0v6"/>
    </svg>
  </div>
  <h2 id="byl-cart-empty-heading"><?php esc_html_e('Your cart is empty', 'beyourlover-v3'); ?></h2>
  <p><?php esc_html_e("Discover something you'll love. Your next favorite is waiting.", 'beyourlover-v3'); ?></p>
  <a class="byl-n6-empty-cart__cta" href="<?php echo esc_url($shop_url); ?>">
    <?php esc_html_e('Explore products', 'beyourlover-v3'); ?>
    <span aria-hidden="true">→</span>
  </a>
</section>
