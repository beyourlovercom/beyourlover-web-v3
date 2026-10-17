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
<section class="byl-cart-empty-parity" aria-labelledby="byl-cart-empty-heading">
  <div class="byl-cart-empty-parity__hero">
    <div class="byl-cart-empty-parity__symbol" aria-hidden="true">
      <svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" focusable="false">
        <path d="M9 12h5l4 19h19l3-14H16"/><circle cx="20" cy="37" r="2"/><circle cx="35" cy="37" r="2"/>
        <path d="M25 3v5M15 6l3 4M35 6l-3 4"/>
      </svg>
    </div>
    <h1 id="byl-cart-empty-heading"><?php esc_html_e('Your cart is empty', 'beyourlover-v3'); ?></h1>
    <p><?php esc_html_e("Looks like you haven't added anything yet. Discover our best sellers and treat yourself to something special.", 'beyourlover-v3'); ?></p>
    <a class="byl-cart-empty-parity__cta" href="<?php echo esc_url($shop_url); ?>"><?php esc_html_e('SHOP BEST SELLERS', 'beyourlover-v3'); ?> <span aria-hidden="true">→</span></a>
  </div>
</section>
<?php
if (function_exists('byl_v3_cart_drawer_recommendations')) {
    // Same recently-viewed / 30-day eligible catalog source as Drawer V2;
    // Drawer itself retains its original three-product default.
    $suggestions = byl_v3_cart_drawer_recommendations(4);
    if ($suggestions) : ?>
      <section class="byl-cart-empty-parity__recommendations" aria-labelledby="byl-cart-empty-recs-heading">
        <h2 id="byl-cart-empty-recs-heading"><?php esc_html_e('You may also like', 'beyourlover-v3'); ?></h2>
        <div class="byl-cart-empty-parity__items">
          <?php foreach ($suggestions as $product) :
            $product_id = $product->get_id();
            $simple = $product->is_type('simple');
            $product_url = get_permalink($product_id);
            $action_url = $simple ? $product->add_to_cart_url() : $product_url;
          ?>
            <article class="byl-cart-empty-parity__product">
              <a class="byl-cart-empty-parity__image" href="<?php echo esc_url($product_url); ?>" aria-label="<?php echo esc_attr($product->get_name()); ?>">
                <img loading="lazy" src="<?php echo esc_url(byl_v3_product_image_url($product)); ?>" alt="<?php echo esc_attr($product->get_name()); ?>">
              </a>
              <div class="byl-cart-empty-parity__detail">
                <a class="byl-cart-empty-parity__name" href="<?php echo esc_url($product_url); ?>"><?php echo esc_html($product->get_name()); ?></a>
                <?php if ($product->get_rating_count() > 0) : ?>
                  <div class="byl-cart-empty-parity__rating"><?php echo wp_kses_post(wc_get_rating_html((float)$product->get_average_rating(), (int)$product->get_rating_count())); ?></div>
                <?php endif; ?>
                <div class="byl-cart-empty-parity__price"><?php echo wp_kses_post($product->get_price_html()); ?></div>
                <a class="byl-cart-empty-parity__add" href="<?php echo esc_url($action_url); ?>" aria-label="<?php echo esc_attr(sprintf($simple ? __('Add %s to cart', 'beyourlover-v3') : __('View options for %s', 'beyourlover-v3'), $product->get_name())); ?>">
                  <?php echo $simple ? esc_html__('Add to cart', 'beyourlover-v3') : esc_html__('View options', 'beyourlover-v3'); ?>
                </a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif;
}
?>
