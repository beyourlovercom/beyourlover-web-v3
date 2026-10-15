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
<div class="byl-progressive-checkout" data-byl-progressive-checkout data-needs-payment="<?php echo esc_attr($cart && $cart->needs_payment() ? '1' : '0'); ?>" data-needs-shipping="<?php echo esc_attr($cart && $cart->needs_shipping() ? '1' : '0'); ?>" data-byl-current-step="shipping">
  <header class="byl-checkout-heading"><h1 data-byl-checkout-heading>Shipping</h1><span>Secure checkout</span></header>
  <nav class="byl-checkout-track" aria-label="Checkout steps">
    <a href="<?php echo esc_url(wc_get_cart_url()); ?>" data-byl-track="cart"><span aria-hidden="true">1</span>Cart</a>
    <span data-byl-track="shipping" aria-current="step"><span aria-hidden="true">2</span>Shipping</span>
    <span data-byl-track="payment"><span aria-hidden="true">3</span>Payment</span>
  </nav>
  <form name="checkout" method="post" class="checkout woocommerce-checkout byl-checkout-form" action="<?php echo esc_url(wc_get_checkout_url()); ?>" enctype="multipart/form-data" aria-label="<?php echo esc_attr__('Checkout', 'woocommerce'); ?>">
    <div class="byl-checkout-grid">
      <div class="byl-checkout-stages">
        <?php if ($checkout->get_checkout_fields()) : do_action('woocommerce_checkout_before_customer_details'); ?>
        <section class="byl-checkout-step is-active" data-byl-step="shipping" aria-labelledby="byl-heading-shipping">
          <button type="button" class="byl-step-toggle" data-byl-open-step="shipping" aria-expanded="true"><span class="byl-step-number">2</span><span class="byl-step-heading"><strong id="byl-heading-shipping">Shipping</strong><small data-byl-step-summary="shipping">Contact, delivery option and address</small></span><span class="byl-step-edit">Edit</span></button>
          <div class="byl-step-body">
            <div class="byl-account-entry">
              <?php if (is_user_logged_in()) : ?>
                <a href="<?php echo esc_url(wc_get_account_endpoint_url('edit-account')); ?>">Edit your profile</a>
              <?php else : ?>
                <a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>">Log in or create an account</a>
              <?php endif; ?>
            </div>
            <div class="byl-account-email-host" data-byl-account-email-host></div>
            <div data-byl-marketing-optin-slot hidden></div>
            <div class="byl-shipping-rate-section" aria-labelledby="byl-delivery-choice-heading">
              <h2 id="byl-delivery-choice-heading">Choose your shipping option</h2>
              <div class="byl-delivery-options" data-byl-delivery-options aria-live="polite"></div>
              <p class="byl-step-hint">Available delivery options are calculated from your shipping address below.</p>
            </div>
            <h2 class="byl-shipping-details-heading">Add shipping details</h2>
            <div id="customer_details"><div class="byl-checkout-billing"><?php do_action('woocommerce_checkout_billing'); ?></div><div class="byl-checkout-shipping"><?php do_action('woocommerce_checkout_shipping'); ?></div></div>
            <div data-byl-country-notices hidden></div>
          </div>
        </section>
        <?php do_action('woocommerce_checkout_after_customer_details'); endif; ?>
        <section class="byl-checkout-step" data-byl-step="payment" aria-labelledby="byl-heading-payment">
          <button type="button" class="byl-step-toggle" data-byl-open-step="payment" aria-expanded="false"><span class="byl-step-number">3</span><span class="byl-step-heading"><strong id="byl-heading-payment">Payment</strong><small data-byl-step-summary="payment">Secure and encrypted</small></span><span class="byl-step-edit">Edit</span></button>
          <div class="byl-step-body">
            <h2 class="byl-payment-options-heading">Choose a payment option</h2>
            <div data-byl-payment-host></div>
            <p class="byl-step-hint">Available payment methods are provided securely by WooCommerce and your chosen payment provider.</p>
            <div data-byl-notes-host></div>
            <div data-byl-terms-host></div>
          </div>
        </section>
        <div class="byl-checkout-action-bar"><p class="byl-checkout-error" data-byl-error role="alert" hidden></p><button type="button" class="byl-checkout-next" data-byl-next>Continue to Payment <span aria-hidden="true">→</span></button></div>
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
            <div class="byl-payment-review" aria-label="Shipping and billing details"><dl class="byl-review-details">
            <div><dt>Contact</dt><dd data-byl-review="contact"></dd><button type="button" data-byl-open-step="shipping">Edit</button></div>
            <div><dt>Shipping Address</dt><dd data-byl-review="address"></dd><button type="button" data-byl-open-step="shipping">Edit</button></div>
            <div><dt>Delivery</dt><dd data-byl-review="delivery"></dd><button type="button" data-byl-open-step="shipping">Edit</button></div>
            <div><dt>Payment</dt><dd data-byl-review="payment"></dd><button type="button" data-byl-open-step="payment">Edit</button></div>
          </dl></div>
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
