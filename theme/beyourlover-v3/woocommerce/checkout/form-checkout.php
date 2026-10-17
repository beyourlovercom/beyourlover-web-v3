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
  <header class="byl-checkout-heading">
    <h1 data-byl-checkout-heading>Shipping</h1>
    <span class="byl-checkout-security">Secure checkout</span>
    <a class="byl-checkout-mobile-close" href="<?php echo esc_url(wc_get_cart_url()); ?>" aria-label="Close checkout and return to cart">&times;</a>
  </header>
  <div class="byl-checkout-return"><a href="<?php echo esc_url(wc_get_cart_url()); ?>"><span aria-hidden="true">&#8249;</span> Your Cart (<?php echo esc_html($cart ? (string) $cart->get_cart_contents_count() : '0'); ?>)</a></div>
  <nav class="byl-checkout-track" aria-label="Checkout steps">
    <a href="<?php echo esc_url(wc_get_cart_url()); ?>" data-byl-track="cart"><span aria-hidden="true"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3 3h2l2.4 11H19l2-8H6.2" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><circle cx="9" cy="20" r="1.5" fill="currentColor"/><circle cx="18" cy="20" r="1.5" fill="currentColor"/></svg></span>Cart</a>
    <span data-byl-track="shipping" aria-current="step"><span aria-hidden="true"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 22s7-7.2 7-12a7 7 0 0 0-14 0c0 4.8 7 12 7 12Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><circle cx="12" cy="10" r="2.4" fill="currentColor"/></svg></span>Shipping</span>
    <span data-byl-track="payment"><span aria-hidden="true"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><rect x="2" y="5" width="20" height="14" rx="2" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M2 10h20" fill="none" stroke="currentColor" stroke-width="1.8"/></svg></span>Payment</span>
  </nav>
  <form name="checkout" method="post" class="checkout woocommerce-checkout byl-checkout-form" action="<?php echo esc_url(wc_get_checkout_url()); ?>" enctype="multipart/form-data" aria-label="<?php echo esc_attr__('Checkout', 'woocommerce'); ?>">
    <div class="byl-checkout-grid">
      <div class="byl-checkout-stages">
        <?php if ($checkout->get_checkout_fields()) :
          // Woo express providers stay on their original native checkout hook.
          do_action('woocommerce_checkout_before_customer_details');
        ?>
        <section class="byl-checkout-step is-active" data-byl-step="shipping" aria-labelledby="byl-heading-shipping">
          <button type="button" class="byl-step-toggle" data-byl-open-step="shipping" aria-expanded="true"><span class="byl-step-number">2</span><span class="byl-step-heading"><strong id="byl-heading-shipping">Shipping</strong><small data-byl-step-summary="shipping">Contact, delivery option and address</small></span><span class="byl-step-edit">Edit</span></button>
          <div class="byl-step-body">
            <div class="byl-contact-card" aria-label="Your email and account">
              <div class="byl-account-entry">
              <?php if (is_user_logged_in()) : ?>
                <a href="<?php echo esc_url(wc_get_account_endpoint_url('edit-account')); ?>">Edit your profile</a>
              <?php else : ?>
                <a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>">Log in or create an account</a>
              <?php endif; ?>
            </div>
            <div class="byl-account-email-host" data-byl-account-email-host></div>
              <?php
                // Checkout opt-in is completely independent of the Woo order.
                // Andy Core requires code+site gates, transport readiness and
                // a confirmation email before a subscriber is confirmed.
                $byl_newsletter_ready = class_exists('YBY_Newsletter_REST_Controller')
                    && is_callable(['YBY_Newsletter_REST_Controller', 'subscribe_ready'])
                    && YBY_Newsletter_REST_Controller::subscribe_ready();
                $byl_newsletter_policy = $byl_newsletter_ready
                    ? YBY_Newsletter_REST_Controller::policy_version() : '';
              ?>
              <div class="byl-checkout-marketing-optin"
                   data-byl-checkout-newsletter
                   data-byl-newsletter-ready="<?php echo $byl_newsletter_ready ? '1' : '0'; ?>"
                   data-byl-newsletter-endpoint="<?php echo esc_url(rest_url('andy-core/v1/newsletter/subscribe')); ?>"
                   data-byl-newsletter-policy="<?php echo esc_attr($byl_newsletter_policy); ?>">
                <label class="byl-checkout-marketing-label">
                  <input type="checkbox" data-byl-newsletter-consent value="1" <?php disabled(!$byl_newsletter_ready); ?>>
                  <span>Notify me of exclusive offers</span>
                </label>
                <p class="byl-checkout-marketing-status" data-byl-newsletter-status role="status" aria-live="polite"><?php
                  if (!$byl_newsletter_ready) {
                      echo esc_html__('Email offers are not available yet.', 'beyourlover-v3');
                  }
                ?></p>
              </div>
            </div>
            <div class="byl-choice-card">
              <div class="byl-shipping-rate-section" aria-labelledby="byl-delivery-choice-heading">
              <h2 id="byl-delivery-choice-heading">Choose your shipping option</h2>
              <div class="byl-delivery-options" data-byl-delivery-options aria-live="polite"></div>
              <p class="byl-step-hint">Available delivery options depend on your destination.</p>
              </div>
            </div>
            <div class="byl-address-card">
              <h2 class="byl-shipping-details-heading">Add shipping details</h2>
              <div id="customer_details"><div class="byl-checkout-billing"><?php do_action('woocommerce_checkout_billing'); ?></div><div class="byl-checkout-shipping"><?php do_action('woocommerce_checkout_shipping'); ?></div></div>

              <div class="byl-address-persistence" data-byl-address-persistence>
                <label class="byl-address-choice" for="byl_save_address">
                  <input type="checkbox" id="byl_save_address" name="byl_save_address" value="1" <?php disabled(!is_user_logged_in()); ?>>
                  <span>Save this address for future orders</span>
                </label>
                <?php if (!is_user_logged_in()) : ?>
                  <p class="byl-address-account-hint">Log in to save an address for future orders.</p>
                <?php else : ?>
                  <p class="byl-address-account-hint" hidden>Saved to your account only if you choose this option when placing an order.</p>
                <?php endif; ?>
                <input type="hidden" name="byl_use_shipping_for_billing" value="0">
                <label class="byl-address-choice" for="byl_use_shipping_for_billing">
                  <input type="checkbox" id="byl_use_shipping_for_billing" name="byl_use_shipping_for_billing" value="1" checked>
                  <span>Use this address for billing</span>
                </label>
              </div>

              <section class="byl-invoice-address" data-byl-invoice-address hidden aria-label="Separate billing address">
                <h3>Billing address</h3>
                <p>Enter the address associated with your payment method. Delivery and shipping costs still use the shipping address above.</p>
                <fieldset data-byl-invoice-fields disabled>
                  <?php
                    // Read stored billing address only when authenticated;
                    // never claim guest addresses will persist on this site.
                    $byl_invoice_customer = is_user_logged_in()
                        ? new WC_Customer(get_current_user_id()) : null;
                    $byl_invoice_input = static function (string $field): string {
                        // Re-use only on a failed checkout submission, not arbitrary GET data.
                        if (isset($_POST['woocommerce-process-checkout-nonce'])
                            && isset($_POST['byl_invoice_' . $field])
                            && is_scalar($_POST['byl_invoice_' . $field])) {
                            return sanitize_text_field(wp_unslash((string) $_POST['byl_invoice_' . $field]));
                        }
                        return '';
                    };
                    $byl_invoice_fields = [
                      'country' => ['type'=>'country', 'label'=>'Country / Region', 'required'=>true, 'class'=>['form-row-wide']],
                      'first_name' => ['type'=>'text', 'label'=>'First name', 'required'=>true, 'class'=>['form-row-first']],
                      'last_name' => ['type'=>'text', 'label'=>'Last name', 'required'=>true, 'class'=>['form-row-last']],
                      'address_1' => ['type'=>'text', 'label'=>'Street address', 'required'=>true, 'class'=>['form-row-wide']],
                      'address_2' => ['type'=>'text', 'label'=>'Apartment, suite, floor', 'required'=>false, 'class'=>['form-row-wide']],
                      'city' => ['type'=>'text', 'label'=>'City', 'required'=>true, 'class'=>['form-row-first']],
                      'state' => ['type'=>'state', 'label'=>'State / Region', 'required'=>true, 'class'=>['form-row-last']],
                      'postcode' => ['type'=>'text', 'label'=>'Postal code', 'required'=>true, 'class'=>['form-row-wide']],
                      'company' => ['type'=>'text', 'label'=>'Organization', 'required'=>false, 'class'=>['form-row-wide']],
                    ];
                    foreach ($byl_invoice_fields as $key => $field) {
                        $get = 'get_billing_' . $key;
                        $value = $byl_invoice_input($key);
                        if ($value === '' && $byl_invoice_customer && is_callable([$byl_invoice_customer, $get])) {
                            $value = (string) $byl_invoice_customer->{$get}();
                        }
                        if ($key === 'state') {
                            $country_get = $byl_invoice_input('country');
                            if ($country_get === '' && $byl_invoice_customer) {
                                $country_get = (string) $byl_invoice_customer->get_billing_country();
                            }
                            $field['country'] = $country_get;
                        }
                        $field['id'] = 'byl_invoice_' . $key;
                        $field['input_class'] = ['input-text'];
                        woocommerce_form_field('byl_invoice_' . $key, $field, $value);
                    }
                  ?>
                </fieldset>
              </section>

              <div data-byl-notes-host></div>
              <div data-byl-country-notices hidden></div>
              <div class="byl-duties-note" role="note">
                <span class="byl-duties-icon" aria-hidden="true">i</span>
                <div><strong>Estimated shipping, duties and taxes</strong>
                  <p>Shipping charges and any applicable duties or taxes depend on your destination. Charges not included at checkout may be assessed by local authorities.</p>
                </div>
              </div>
            </div>
          </div>
        </section>
        <?php do_action('woocommerce_checkout_after_customer_details'); endif; ?>
        <section class="byl-checkout-step" data-byl-step="payment" aria-labelledby="byl-heading-payment">
          <button type="button" class="byl-step-toggle" data-byl-open-step="payment" aria-expanded="false"><span class="byl-step-number">3</span><span class="byl-step-heading"><strong id="byl-heading-payment">Payment</strong><small data-byl-step-summary="payment">Secure and encrypted</small></span><span class="byl-step-edit">Edit</span></button>
          <div class="byl-step-body">
            <h2 class="byl-payment-options-heading">Choose a payment option</h2>
            <div data-byl-payment-host></div>
            <p class="byl-step-hint">Available payment methods are provided securely by WooCommerce and your chosen payment provider.</p>
            <div data-byl-terms-host></div>
          </div>
        </section>
        <div class="byl-checkout-action-bar">
          <p class="byl-checkout-error" data-byl-error role="alert" hidden></p>
          <button class="byl-mobile-order-toggle" type="button" data-byl-mobile-summary-toggle aria-expanded="false" aria-controls="byl-checkout-summary-panel">
            <strong>Order total (<?php echo esc_html($cart ? (string) $cart->get_cart_contents_count() : '0'); ?>)</strong>
            <span class="byl-mobile-see-details">See details <span aria-hidden="true">&#8964;</span></span>
            <span class="byl-mobile-total" data-byl-mobile-total><?php echo wp_kses_post($cart ? $cart->get_total() : ''); ?></span>
          </button>
          <button type="button" class="byl-checkout-next" data-byl-next>Continue to Payment <span aria-hidden="true">→</span></button></div>
      </div>
      <aside class="byl-checkout-summary" id="byl-checkout-summary-panel" aria-label="Order summary">
        <div class="byl-summary-card">
          <button type="button" class="byl-summary-toggle" data-byl-summary-toggle aria-expanded="true"><strong>Order Summary</strong><span> (<?php echo esc_html($cart ? (string) $cart->get_cart_contents_count() : '0'); ?> items)</span><span class="byl-summary-total"><?php echo wp_kses_post($cart ? $cart->get_total() : ""); ?></span><span class="byl-summary-chevron" aria-hidden="true">⌄</span></button>
          <div class="byl-summary-body" data-byl-summary-body>
            <div class="byl-summary-products">
              <?php if ($cart) : foreach ($cart->get_cart() as $line) :
                $product = $line['data'] ?? null;
                if (!$product instanceof WC_Product || !$product->exists()) { continue; }
              ?><div class="byl-summary-product"><div class="byl-summary-image"><?php echo wp_kses_post($product->get_image('woocommerce_thumbnail')); ?></div><div><strong><?php echo esc_html($product->get_name()); ?></strong><small>Qty: <?php echo esc_html((string) $line['quantity']); ?></small></div><span><?php echo wp_kses_post($cart->get_product_subtotal($product, $line['quantity'])); ?></span></div><?php endforeach; endif; ?>
            </div>
            <div class="byl-payment-review" aria-label="Shipping and billing details">
              <dl class="byl-review-details">
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
