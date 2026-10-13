<?php
/**
 * BeYourLover legacy WWW Shoptimizer Order Pay Address V1.
 *
 * Source: beyourlover-web-v3/release/byl-www-orderpay-v1.php
 * Install path: shoptimizer-child-theme/inc/byl-www-orderpay-v1.php
 * Loaded with require_once by the existing child theme functions.php.
 *
 * Presentation only: no payment actions, checkout filters, order updates,
 * database queries, or new plugin. WooCommerce retains all authorization.
 */
defined('ABSPATH') || exit;

add_action('before_woocommerce_pay_form', static function ($order): void {
    // WooCommerce 10.9.4 emits this hook AFTER its own order-key,
    // pay_for_order capability, and guest-email verification gates.
    // Recheck the identity to fail closed if another caller fires the hook.
    if (!function_exists('is_checkout_pay_page') || !is_checkout_pay_page()
        || !($order instanceof WC_Order) || !$order->needs_payment()) {
        return;
    }

    $id = absint(get_query_var('order-pay'));
    $key = isset($_GET['key']) && is_string($_GET['key'])
        ? wc_clean(wp_unslash($_GET['key']))
        : '';
    if ($id !== (int) $order->get_id()
        || $key === ''
        || !hash_equals((string) $order->get_order_key(), $key)
        || !current_user_can('pay_for_order', $id)) {
        return;
    }

    $shipping = trim((string) $order->get_formatted_shipping_address());
    $billing  = trim((string) $order->get_formatted_billing_address());
    $shipping_phone = method_exists($order, 'get_shipping_phone')
        ? trim((string) $order->get_shipping_phone()) : '';
    $billing_phone = trim((string) $order->get_billing_phone());
    $billing_email = trim((string) $order->get_billing_email());
    ?>
    <section class="byl-www-orderpay-address" aria-labelledby="byl-www-orderpay-heading" data-byl-www-orderpay-address>
        <header class="byl-www-orderpay-address__heading">
            <span class="byl-www-orderpay-address__eyebrow"><?php esc_html_e('Before you pay', 'woocommerce'); ?></span>
            <h2 id="byl-www-orderpay-heading"><?php esc_html_e('Confirm your details', 'woocommerce'); ?></h2>
            <p><?php esc_html_e('Please check the delivery information saved with this order before completing payment.', 'woocommerce'); ?></p>
        </header>
        <div class="byl-www-orderpay-address__group">
            <h3><?php esc_html_e('Shipping address', 'woocommerce'); ?></h3>
            <?php if ($shipping !== '') : ?>
                <address><?php echo wp_kses_post($shipping); ?></address>
            <?php else : ?>
                <p class="byl-www-orderpay-address__missing"><?php esc_html_e('No shipping address is saved. If your order needs delivery, contact support before paying.', 'woocommerce'); ?></p>
            <?php endif; ?>
            <?php if ($shipping_phone !== '') : ?>
                <p class="byl-www-orderpay-address__detail"><strong><?php esc_html_e('Phone', 'woocommerce'); ?></strong> <?php echo esc_html($shipping_phone); ?></p>
            <?php endif; ?>
        </div>
        <div class="byl-www-orderpay-address__group">
            <h3><?php esc_html_e('Billing address', 'woocommerce'); ?></h3>
            <?php if ($billing !== '') : ?>
                <address><?php echo wp_kses_post($billing); ?></address>
            <?php else : ?>
                <p class="byl-www-orderpay-address__missing"><?php esc_html_e('No billing address is saved.', 'woocommerce'); ?></p>
            <?php endif; ?>
            <?php if ($billing_phone !== '') : ?>
                <p class="byl-www-orderpay-address__detail"><strong><?php esc_html_e('Phone', 'woocommerce'); ?></strong> <?php echo esc_html($billing_phone); ?></p>
            <?php endif; ?>
            <?php if ($billing_email !== '') : ?>
                <p class="byl-www-orderpay-address__detail"><strong><?php esc_html_e('Email', 'woocommerce'); ?></strong> <?php echo esc_html($billing_email); ?></p>
            <?php endif; ?>
        </div>
        <p class="byl-www-orderpay-address__footer">
            <?php esc_html_e('Need to correct an address? Contact support before paying.', 'woocommerce'); ?>
            <a href="<?php echo esc_url(home_url('/contact-us/')); ?>"><?php esc_html_e('Contact support', 'woocommerce'); ?></a>
        </p>
    </section>
    <?php
}, 10, 1);

add_action('wp_enqueue_scripts', static function (): void {
    if (!function_exists('is_checkout_pay_page') || !is_checkout_pay_page()) {
        return;
    }
    $path = get_stylesheet_directory() . '/assets/css/byl-www-orderpay-v1.css';
    if (!is_file($path)) {
        return;
    }
    wp_enqueue_style(
        'byl-www-orderpay-v1',
        get_stylesheet_directory_uri() . '/assets/css/byl-www-orderpay-v1.css',
        array(),
        (string) filemtime($path)
    );
}, 99);
