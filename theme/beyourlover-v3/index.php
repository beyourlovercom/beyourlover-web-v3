<?php
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

get_header();

$is_commerce_content =
    (function_exists('is_cart') && is_cart()) ||
    (function_exists('is_checkout') && is_checkout()) ||
    (function_exists('is_account_page') && is_account_page());
?>
<main id="main-content"<?php echo $is_commerce_content ? ' class="byl-content byl-commerce-content"' : ''; ?>>
<?php while (have_posts()) { the_post(); the_content(); } ?>
</main>
<?php get_footer();
