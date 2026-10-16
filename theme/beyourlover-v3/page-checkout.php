<?php
/** Dedicated checkout page: do not render legacy page-embedded CSS/shortcode markup. */
declare(strict_types=1);
defined('ABSPATH') || exit;
// The completed order/thank-you endpoint keeps its established site shell.
$byl_received = function_exists('is_order_received_page') && is_order_received_page();
$byl_drawer = function_exists('byl_v3_checkout_drawer_enabled') && byl_v3_checkout_drawer_enabled();
get_header($byl_received ? null : 'checkout');
?>
<?php if ($byl_drawer) : ?>
<div class="byl-checkout-drawer-root" data-byl-checkout-drawer-root>
  <button class="byl-checkout-drawer-scrim" type="button" aria-label="Close checkout" data-byl-checkout-drawer-scrim></button>
  <section class="byl-checkout-drawer-panel" role="dialog" aria-modal="true" aria-label="Secure checkout" tabindex="-1" data-byl-checkout-drawer-panel>
    <a class="byl-checkout-drawer-close" href="<?php echo esc_url(function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/')); ?>" aria-label="Close checkout" data-byl-checkout-drawer-close>
      <span aria-hidden="true">×</span>
    </a>
    <div class="byl-checkout-drawer-scroll" data-byl-checkout-drawer-scroll>
<?php endif; ?>
<main id="main-content" class="byl-content byl-commerce-content">
<?php
while (have_posts()) {
    the_post();
    if (function_exists('WC')) {
        echo do_shortcode('[woocommerce_checkout]'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    } else {
        the_content();
    }
}
?>
</main>
<?php if ($byl_drawer) : ?>
    </div>
  </section>
</div>
<?php endif; ?>
<?php get_footer($byl_received ? null : 'checkout'); ?>
