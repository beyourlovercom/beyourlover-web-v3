<?php
/**
 * Template Name: BYL Simple Page
 * Template Post Type: page
 *
 * Reusable text-first WordPress Page template. Only the outer reading layout
 * and light typographic rhythm are defined here; the post content is untouched.
 * Opt-in via Page > Template, so Cart, Checkout and other Pages are unaffected.
 */
declare(strict_types=1);
defined('ABSPATH') || exit;

// Register before get_header()/wp_head() to keep the stylesheet template-scoped.
// The normal theme foundation/token/motion stylesheet graph remains authoritative.
add_action('wp_enqueue_scripts', static function (): void {
    $relative = '/assets/css/page-simple.css';
    $path = get_stylesheet_directory().$relative;
    $version = is_file($path) ? (string) filemtime($path) : (string) wp_get_theme()->get('Version');
    wp_enqueue_style(
        'byl-v3-page-simple',
        get_stylesheet_directory_uri().$relative,
        ['byl-v3-motion'],
        $version
    );
}, 20);

get_header();
?>
<main id="main-content" class="byl-simple-page" data-byl-page-template="simple">
  <div class="byl-simple-page__container">
    <?php while (have_posts()) : the_post(); ?>
      <article <?php post_class('byl-simple-page__article'); ?>>
        <header class="byl-simple-page__heading">
          <h1 class="byl-simple-page__title"><?php echo esc_html(get_the_title()); ?></h1>
        </header>
        <div class="byl-simple-page__content">
          <?php the_content(); ?>
        </div>
      </article>
    <?php endwhile; ?>
  </div>
</main>
<?php get_footer();
