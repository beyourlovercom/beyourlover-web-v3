<?php
/** BYL V3 progressive Woo checkout; matches Woo form-checkout.php v9.4.0. */
defined('ABSPATH') || exit;
do_action('woocommerce_before_checkout_form', $checkout);
if (!$checkout->is_registration_enabled() && $checkout->is_registration_required() && !is_user_logged_in()) {
    echo esc_html(apply_filters('woocommerce_checkout_must_be_logged_in_message', __('You must be logged in to checkout.', 'woocommerce')));
    return;
}
$cart = WC()->cart;
?>
<div class="byl-progressive-checkout" data-byl-progressive-checkout data-needs-payment="<?php echo esc_attr($cart && $cart->needs_payment() ? '1' : '0'); ?>">
  <header class="byl-checkout-heading"><h1>Checkout</h1><span>♧ Secure checkout</span></header>
  <form name="checkout" method="post" class="checkout woocommerce-checkout byl-checkout-form" action="<?php echo esc_url(wc_get_checkout_url()); ?>" enctype="multipart/form-data" aria-label="<?php echo esc_attr__('Checkout', 'woocommerce'); ?>">
    <div class="byl-checkout-grid">
      <div class="byl-checkout-stages">
        <?php if ($checkout->get_checkout_fields()) : do_action('woocommerce_checkout_before_customer_details'); ?>
        <section class="byl-checkout-step is-active" data-byl-step="address" aria-labelledby="byl-heading-address">
          <button type="button" class="byl-step-toggle" data-byl-open-step="address" aria-expanded="true"><span class="byl-step-number">1</span><span class="byl-step-heading"><strong id="byl-heading-address">Shipping Address</strong><small data-byl-step-summary="address">Where should we send your order?</small></span><span class="byl-step-edit">Edit</span></button>
          <div class="byl-step-body">
            <div id="customer_details"><div class="byl-checkout-billing"><?php do_action('woocommerce_checkout_billing'); ?></div><div class="byl-checkout-shipping"><?php do_action('woocommerce_checkout_shipping'); ?></div></div>
          </div>
        </section>
        <?php do_action('woocommerce_checkout_after_customer_details'); endif; ?>
        <section class="byl-checkout-step" data-byl-step="delivery" aria-labelledby="byl-heading-delivery">
          <button type="button" class="byl-step-toggle" data-byl-open-step="delivery" aria-expanded="false"><span class="byl-step-number">2</span><span class="byl-step-heading"><strong id="byl-heading-delivery">Delivery Method</strong><small data-byl-step-summary="delivery">Choose a shipping option</small></span><span class="byl-step-edit">Edit</span></button>
          <div class="byl-step-body"><div class="byl-delivery-options" data-byl-delivery-options aria-live="polite"></div><p class="byl-step-hint">Available delivery rates are calculated from your address.</p></div>
        </section>
        <section class="byl-checkout-step" data-byl-step="payment" aria-labelledby="byl-heading-payment">
          <button type="button" class="byl-step-toggle" data-byl-open-step="payment" aria-expanded="false"><span class="byl-step-number">3</span><span class="byl-step-heading"><strong id="byl-heading-payment">Payment</strong><small data-byl-step-summary="payment">Secure and encrypted</small></span><span class="byl-step-edit">Edit</span></button>
          <div class="byl-step-body"><div data-byl-payment-host></div><p class="byl-step-hint">Payment methods depend on your region and device.</p></div>
        </section>
        <section class="byl-checkout-step" data-byl-step="review" aria-labelledby="byl-heading-review">
          <button type="button" class="byl-step-toggle" data-byl-open-step="review" aria-expanded="false"><span class="byl-step-number">4</span><span class="byl-step-heading"><strong id="byl-heading-review">Review</strong><small>Confirm your order</small></span></button>
          <div class="byl-step-body"><dl class="byl-review-details">
            <div><dt>Contact</dt><dd data-byl-review="contact"></dd><button type="button" data-byl-open-step="address">Edit</button></div>
            <div><dt>Shipping Address</dt><dd data-byl-review="address"></dd><button type="button" data-byl-open-step="address">Edit</button></div>
            <div><dt>Delivery</dt><dd data-byl-review="delivery"></dd><button type="button" data-byl-open-step="delivery">Edit</button></div>
            <div><dt>Payment</dt><dd data-byl-review="payment"></dd><button type="button" data-byl-open-step="payment">Edit</button></div>
          </dl><div data-byl-notes-host></div><div data-byl-terms-host></div></div>
        </section>
        <div class="byl-checkout-action-bar"><p class="byl-checkout-error" data-byl-error role="alert" hidden></p><button type="button" class="byl-checkout-next" data-byl-next>Continue to Delivery <span aria-hidden="true">→</span></button></div>
      </div>
      <aside class="byl-checkout-summary" aria-label="Order summary">
        <div class="byl-summary-card">
          <button type="button" class="byl-summary-toggle" data-byl-summary-toggle aria-expanded="true"><strong>Order Summary</strong><span> (<?php echo esc_html($cart ? (string) $cart->get_cart_contents_count() : '0'); ?> items)</span><span class="byl-summary-total"><?php echo wp_kses_post($cart ? $cart->get_total() : ""); ?></span><span class="byl-summary-chevron" aria-hidden="true">⌄</span></button>
          <div class="byl-summary-body" data-byl-summary-body>
            <div class="byl-summary-products">
              <?php if ($cart) : foreach ($cart->get_cart() as $line) :
                $product = $line['data'] ?? null;
                if (!$product instanceof WC_Product || !$product->exists()) { continue; }
              ?><div class="byl-summary-product"><div class="byl-summary-image"><?php echo wp_kses_post($product->get_image('woocommerce_thumbnail')); ?></div><div><strong><?php echo esc_html($product->get_name()); ?></strong><small>Qty: <?php echo esc_html((string) $line['quantity']); ?></small></div><span><?php echo wp_kses_post($cart->get_product_subtotal($product, $line['quantity'])); ?></span></div><?php endforeach; endif; ?>
            </div>
            <div class="byl-summary-promo" data-byl-promotion>
              <label for="byl-promo-code">Promo code</label>
              <div class="byl-summary-promo-row"><input id="byl-promo-code" type="text" autocomplete="off" placeholder="Enter promo code"><button type="button" data-byl-apply-coupon>Apply</button></div>
              <p data-byl-promo-status>Only one promo code can be applied per order.</p>
            </div>
            <?php do_action('woocommerce_checkout_before_order_review_heading'); ?>
            <h2 id="order_review_heading" class="screen-reader-text"><?php esc_html_e('Your order', 'woocommerce'); ?></h2>
            <?php do_action('woocommerce_checkout_before_order_review'); ?>
            <div id="order_review" class="woocommerce-checkout-review-order"><?php do_action('woocommerce_checkout_order_review'); ?></div>
            <?php do_action('woocommerce_checkout_after_order_review'); ?>
            <p class="byl-summary-trust">Secure checkout · Discreet packaging</p>
          </div>
        </div>
      </aside>
    </div>
  </form>
</div>
<?php do_action('woocommerce_after_checkout_form', $checkout); ?>
