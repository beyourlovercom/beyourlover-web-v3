<?php
/**
 * Order Pay Address V1: read-only address confirmation on the existing-order
 * payment route. WooCommerce remains the payment and authorization authority.
 */
declare(strict_types=1);
defined('ABSPATH') || exit;

/*
 * This hook fires in WC_Shortcode_Checkout::order_pay() only after WooCommerce
 * has checked the order key, pay permission and any guest email verification.
 * Never render personal data on a raw order-id / unverified request.
 */
add_action('before_woocommerce_pay_form', static function ($order): void {
    if (!($order instanceof WC_Order) || !$order->needs_payment()) {
        return;
    }
    if (!function_exists('is_checkout_pay_page') || !is_checkout_pay_page()) {
        return;
    }

    $shipping = trim((string) $order->get_formatted_shipping_address());
    $billing  = trim((string) $order->get_formatted_billing_address());
    $shipping_phone = method_exists($order, 'get_shipping_phone') ? (string) $order->get_shipping_phone() : '';
    $billing_phone = (string) $order->get_billing_phone();
    $email = (string) $order->get_billing_email();
    ?>
    <section class="byl-orderpay-address" data-byl-orderpay-address aria-labelledby="byl-orderpay-address-heading">
        <div class="byl-orderpay-address__header">
            <span class="byl-orderpay-address__eyebrow"><?php esc_html_e('Before you pay', 'beyourlover-v3'); ?></span>
            <h2 id="byl-orderpay-address-heading"><?php esc_html_e('Confirm your details', 'beyourlover-v3'); ?></h2>
            <p><?php esc_html_e('Please check the delivery information saved with this order before completing payment.', 'beyourlover-v3'); ?></p>
        </div>
        <div class="byl-orderpay-address__card">
            <h3><?php esc_html_e('Shipping address', 'beyourlover-v3'); ?></h3>
            <?php if ($shipping !== '') : ?>
                <address><?php echo wp_kses_post($shipping); ?></address>
            <?php else : ?>
                <p class="byl-orderpay-address__missing"><?php esc_html_e('No separate shipping address was provided. If this order requires delivery, contact support before paying.', 'beyourlover-v3'); ?></p>
            <?php endif; ?>
            <?php if ($shipping_phone !== '') : ?>
                <p class="byl-orderpay-address__detail"><span><?php esc_html_e('Phone', 'beyourlover-v3'); ?></span> <?php echo esc_html($shipping_phone); ?></p>
            <?php endif; ?>
        </div>
        <div class="byl-orderpay-address__card">
            <h3><?php esc_html_e('Billing address', 'beyourlover-v3'); ?></h3>
            <?php if ($billing !== '') : ?>
                <address><?php echo wp_kses_post($billing); ?></address>
            <?php else : ?>
                <p class="byl-orderpay-address__missing"><?php esc_html_e('No billing address was provided.', 'beyourlover-v3'); ?></p>
            <?php endif; ?>
            <?php if ($billing_phone !== '') : ?>
                <p class="byl-orderpay-address__detail"><span><?php esc_html_e('Phone', 'beyourlover-v3'); ?></span> <?php echo esc_html($billing_phone); ?></p>
            <?php endif; ?>
            <?php if ($email !== '') : ?>
                <p class="byl-orderpay-address__detail"><span><?php esc_html_e('Email', 'beyourlover-v3'); ?></span> <?php echo esc_html($email); ?></p>
            <?php endif; ?>
        </div>
        <p class="byl-orderpay-address__help">
            <?php esc_html_e('Need to correct an address? Contact our support team before paying.', 'beyourlover-v3'); ?>
            <a href="<?php echo esc_url(home_url('/customer-help/')); ?>"><?php esc_html_e('Contact support', 'beyourlover-v3'); ?></a>
        </p>
    </section>
    <?php
}, 10, 1);
