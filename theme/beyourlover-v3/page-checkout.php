<?php
/** Dedicated checkout page: do not render legacy page-embedded CSS/shortcode markup. */
declare(strict_types=1);
defined('ABSPATH') || exit;
// The completed order/thank-you endpoint keeps its established site shell.
$byl_received = function_exists('is_order_received_page') && is_order_received_page();
get_header($byl_received ? null : 'checkout');
?>
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
<?php get_footer($byl_received ? null : 'checkout'); ?>
