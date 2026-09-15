<?php
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }
get_header();
?>
<main id="main-content">
<?php while (have_posts()) { the_post(); the_content(); } ?>
</main>
<?php get_footer();
