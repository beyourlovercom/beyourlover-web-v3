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
<section class="byl-cart-empty-parity byl-drawer__empty" aria-labelledby="byl-cart-empty-heading">
  <div class="byl-drawer__empty-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" focusable="false"><circle cx="9" cy="20" r="1.5"/><circle cx="19" cy="20" r="1.5"/><path d="M2 3h2l3 13h13l2-9H5"/></svg></div>
  <h2 id="byl-cart-empty-heading"><?php esc_html_e('Your cart is empty', 'beyourlover-v3'); ?></h2>
  <p><?php esc_html_e('Explore our best sellers and find something for you.', 'beyourlover-v3'); ?></p>
  <a class="byl-drawer__browse" href="<?php echo esc_url($shop_url); ?>"><?php esc_html_e('SHOP BEST SELLERS', 'beyourlover-v3'); ?></a>
</section>
<?php
if (function_exists('byl_v3_cart_drawer_recommendations')) {
    $suggestions = array_slice(byl_v3_cart_drawer_recommendations(), 0, 3);
    if ($suggestions) : ?>
      <section class="byl-cart-empty-parity__recommendations" aria-labelledby="byl-cart-empty-recs-heading">
        <h3 id="byl-cart-empty-recs-heading">You might enjoy</h3>
        <div class="byl-cart-empty-parity__items">
          <?php foreach ($suggestions as $product) : ?>
            <article class="byl-cart-empty-parity__product">
              <a href="<?php echo esc_url(get_permalink($product->get_id())); ?>" class="byl-cart-empty-parity__image"><img loading="lazy" src="<?php echo esc_url(byl_v3_product_image_url($product)); ?>" alt="<?php echo esc_attr($product->get_name()); ?>"></a>
              <div><a href="<?php echo esc_url(get_permalink($product->get_id())); ?>"><?php echo esc_html($product->get_name()); ?></a><div><?php echo wp_kses_post($product->get_price_html()); ?></div></div>
            </article>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif;
}
?>
