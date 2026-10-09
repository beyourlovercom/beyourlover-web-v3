<?php
/**
 * BeYourLover P7.1: native WooCommerce order-received view.
 * This template receives $order from Woo. It never accepts an arbitrary order
 * ID from GET, never mutates the order and never handles payment submission.
 *
 * @var WC_Order|false $order
 */
declare(strict_types=1);
defined('ABSPATH') || exit;

if (!$order instanceof WC_Order) {
    wc_get_template('checkout/order-received.php', ['order' => $order]);
    return;
}

do_action('woocommerce_before_thankyou', $order->get_id());

$paid = byl_v3_ty_is_paid_success($order);
$failed = $order->has_status(['failed', 'cancelled', 'refunded']);
$status = wc_get_order_status_name($order->get_status());
$has_shipping = false;
foreach ($order->get_items('line_item') as $item) {
    $product = $item instanceof WC_Order_Item_Product ? $item->get_product() : false;
    if (!$product || !$product->is_virtual()) { $has_shipping = true; break; }
}
$eta = $paid && $has_shipping ? byl_v3_ty_order_eta($order) : null;
$track_url = home_url('/track-your-order/');
$shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/');
$currency = $order->get_currency();
$money = static fn($amount) => wc_price($amount, ['currency' => $currency]);
?>
<section class="byl-ty byl-container" data-byl-thank-you data-byl-order-status="<?php echo esc_attr($order->get_status()); ?>">
    <div class="byl-ty-hero">
        <div class="byl-ty-check" aria-hidden="true"><?php echo $paid ? '✓' : ($failed ? '!' : '⌁'); ?></div>
        <?php if ($paid): ?>
            <h1>Thank you for your order.</h1>
            <p>Your order is confirmed. We're preparing it for shipment.</p>
        <?php elseif ($failed): ?>
            <h1>We couldn't confirm your payment.</h1>
            <p>Please check the payment status in your account. No successful payment is shown for this order.</p>
        <?php else: ?>
            <h1>We've received your order.</h1>
            <p>Your payment is being confirmed. We'll update your order when it's ready.</p>
        <?php endif; ?>
    </div>

    <div class="byl-ty-facts" aria-label="Order information">
        <div><span>Order number</span><strong>#<?php echo esc_html($order->get_order_number()); ?></strong></div>
        <div><span>Date</span><strong><?php echo esc_html($order->get_date_created() ? wp_date('M j, Y', $order->get_date_created()->getTimestamp()) : '—'); ?></strong></div>
        <div><span>Total</span><strong><?php echo wp_kses_post($money($order->get_total())); ?></strong></div>
        <div><span>Payment</span><strong><?php echo esc_html(byl_v3_ty_payment_label((string) $order->get_payment_method_title())); ?></strong></div>
        <div><span>Status</span><strong class="byl-ty-status <?php echo $paid ? 'is-paid' : ($failed ? 'is-failed' : 'is-pending'); ?>"><?php echo esc_html($status); ?></strong></div>
    </div>

    <?php if ($eta): ?>
        <section class="byl-ty-delivery" aria-labelledby="byl-ty-delivery-heading">
            <span class="byl-ty-delivery-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M2.5 5.5h12v11.5h-12zM14.5 9.5h3.5l3.5 3.4V17h-7"/><circle cx="7" cy="18" r="1.6"/><circle cx="18" cy="18" r="1.6"/></svg></span>
            <div>
                <h2 id="byl-ty-delivery-heading">Estimated delivery</h2>
                <?php if ($eta['range']): ?>
                    <strong class="byl-ty-delivery-date"><?php echo esc_html(byl_v3_ty_date_label($eta['range'][0])); ?> – <?php echo esc_html(byl_v3_ty_date_label($eta['range'][1])); ?></strong>
                    <p><?php echo esc_html($eta['holiday_note']); ?> Not a guaranteed arrival date.</p>
                <?php else: ?>
                    <strong class="byl-ty-delivery-date"><?php echo esc_html($eta['transit_min'].'–'.$eta['transit_max']); ?> business days after dispatch</strong>
                    <p>Destination holiday calendar is not yet verified. Exact dates become available after shipment.</p>
                <?php endif; ?>
                <p class="byl-ty-delivery-small">Based on <?php echo esc_html($eta['region']); ?> and the published shipping policy. Carrier, customs and local closures may change timing.</p>
            </div>
        </section>
    <?php endif; ?>

    <div class="byl-ty-actions">
        <a class="byl-ty-button byl-ty-primary" href="<?php echo esc_url($track_url); ?>">Track your order <span aria-hidden="true">→</span></a>
        <a class="byl-ty-button byl-ty-secondary" href="<?php echo esc_url($shop_url); ?>">Continue shopping</a>
    </div>

    <?php if ($paid && $has_shipping): ?>
        <section class="byl-ty-progress" aria-labelledby="byl-ty-progress-title">
            <h2 id="byl-ty-progress-title">What happens next?</h2>
            <ol class="byl-ty-steps">
                <li class="is-current"><span class="byl-ty-step-number">1</span><strong>Order confirmed</strong><p>Your order has been received.</p></li>
                <li><span class="byl-ty-step-number">2</span><strong>Preparing your order</strong><p>We'll prepare it for shipment.</p></li>
                <li><span class="byl-ty-step-number">3</span><strong>Shipping &amp; tracking</strong><p>Tracking will be available when shipped.</p></li>
            </ol>
        </section>
    <?php endif; ?>

    <section class="byl-ty-products" aria-labelledby="byl-ty-items-heading">
        <h2 id="byl-ty-items-heading">Items ordered</h2>
        <div class="byl-ty-items">
            <?php foreach ($order->get_items('line_item') as $item):
                if (!$item instanceof WC_Order_Item_Product) { continue; }
                $product = $item->get_product();
                $thumbnail = $product ? $product->get_image('woocommerce_thumbnail') : '';
                $qty = max(1, (int) $item->get_quantity());
            ?>
                <div class="byl-ty-item">
                    <div class="byl-ty-item-photo"><?php echo $thumbnail ? wp_kses_post($thumbnail) : '<span aria-hidden="true">▢</span>'; ?></div>
                    <div class="byl-ty-item-name"><strong><?php echo esc_html($item->get_name()); ?></strong><small>Qty: <?php echo esc_html((string) $qty); ?></small></div>
                    <div class="byl-ty-item-price"><span><?php echo wp_kses_post($money((float) $item->get_total() / $qty)); ?> × <?php echo esc_html((string) $qty); ?></span><strong><?php echo wp_kses_post($money($item->get_total())); ?></strong></div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="byl-ty-totals" aria-label="Order totals">
            <div><span>Subtotal</span><span><?php echo wp_kses_post($money($order->get_subtotal())); ?></span></div>
            <?php if ((float) $order->get_discount_total() > 0): ?>
                <div><span>Discount</span><span>–<?php echo wp_kses_post($money($order->get_discount_total())); ?></span></div>
            <?php endif; ?>
            <div><span>Shipping</span><span><?php echo (float) $order->get_shipping_total() === 0.0 ? 'Free' : wp_kses_post($money($order->get_shipping_total())); ?></span></div>
            <?php if ((float) $order->get_total_tax() > 0): ?>
                <div><span>Tax</span><span><?php echo wp_kses_post($money($order->get_total_tax())); ?></span></div>
            <?php endif; ?>
            <div class="byl-ty-grand-total"><strong>Total</strong><strong><?php echo wp_kses_post($money($order->get_total())); ?></strong></div>
        </div>
    </section>

    <div class="byl-ty-addresses">
        <section><h2>Shipping address</h2><address><?php echo wp_kses_post($order->get_formatted_shipping_address() ?: 'No shipping address required.'); ?></address></section>
        <section><h2>Billing address</h2><address><?php echo wp_kses_post($order->get_formatted_billing_address() ?: 'Not provided.'); ?></address></section>
    </div>

    <aside class="byl-ty-help" aria-labelledby="byl-ty-help-heading">
        <div><h2 id="byl-ty-help-heading">Need help?</h2><p>We're here if you need a hand.</p></div>
        <a href="<?php echo esc_url($track_url); ?>">Shipping &amp; tracking <span aria-hidden="true">→</span></a>
        <a href="<?php echo esc_url(home_url('/shipping-policies/')); ?>">Shipping policy <span aria-hidden="true">→</span></a>
        <a href="<?php echo esc_url(home_url('/customer-help/')); ?>">Contact support <span aria-hidden="true">→</span></a>
    </aside>
</section>
<?php
// WooCommerce payment-gateway and analytics integrations still receive their
// standard Thank You hooks. Suppress ONLY core's duplicate order-details table.
remove_action('woocommerce_thankyou', 'woocommerce_order_details_table', 10);
do_action('woocommerce_thankyou_'.$order->get_payment_method(), $order->get_id());
do_action('woocommerce_thankyou', $order->get_id());
