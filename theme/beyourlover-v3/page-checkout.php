<?php
/** Dedicated checkout page: do not render legacy page-embedded CSS/shortcode markup. */
declare(strict_types=1);
defined('ABSPATH') || exit;
get_header();
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
<?php get_footer(); ?>
