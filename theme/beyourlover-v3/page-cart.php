<?php
/**
 * Dedicated BYL V3 cart page.
 *
 * Runtime ownership:
 * - WooCommerce owns cart state, quantities, coupons, totals and checkout URL.
 * - BYL V3 owns page composition and presentation.
 * - Legacy page-builder / Smart Coupons / BOGO content stored in the Cart page is intentionally not rendered.
 */
declare(strict_types=1);

defined('ABSPATH') || exit;

get_header();
?>
<main id="main-content" class="byl-content byl-commerce-content byl-cart-page">
    <header class="byl-cart-heading">
        <div>
            <span class="byl-cart-kicker"><?php esc_html_e('Secure shopping', 'beyourlover-v3'); ?></span>
            <h1><?php esc_html_e('Your Cart', 'beyourlover-v3'); ?></h1>
            <p><?php esc_html_e('Review your items, update quantities, then continue to secure checkout.', 'beyourlover-v3'); ?></p>
        </div>
        <a class="byl-cart-continue" href="<?php echo esc_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/')); ?>">
            <?php esc_html_e('Continue shopping', 'beyourlover-v3'); ?>
        </a>
    </header>

    <section class="byl-cart-runtime" aria-label="<?php esc_attr_e('Shopping cart', 'beyourlover-v3'); ?>">
        <?php
        if (function_exists('WC')) {
            echo do_shortcode('[woocommerce_cart]'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        } else {
            while (have_posts()) {
                the_post();
                the_content();
            }
        }
        ?>
    </section>
</main>
<?php get_footer(); ?>
