<?php
/**
 * Blog category archives — real WordPress main query and native pagination.
 * Independent editorial shell if PR #102 is installed; existing fallback if not.
 * Does not change custom Pages, products, post content or stored categories.
 */
declare(strict_types=1);
defined('ABSPATH') || exit;
require_once get_stylesheet_directory().'/inc/blog-editorial-shell.php';
$term = get_queried_object();
$title = $term instanceof WP_Term ? $term->name : single_cat_title('', false);
$description = $term instanceof WP_Term ? term_description($term) : '';
byl_blog_editorial_header();
?>
<main id="main-content" class="byl-blog-archive" data-byl-editorial-archive>
  <div class="byl-blog-archive__heading">
    <div class="byl-blogv1-container">
      <nav class="byl-blog-archive__crumb" aria-label="Breadcrumb">
        <a href="<?php echo esc_url(home_url('/blog/')); ?>">Blog</a><span aria-hidden="true">/</span>
        <span aria-current="page"><?php echo esc_html($title); ?></span>
      </nav>
      <h1><?php echo esc_html($title); ?></h1>
      <?php if ($description): ?>
        <div class="byl-blog-archive__description"><?php echo wp_kses_post($description); ?></div>
      <?php endif; ?>
    </div>
  </div>
  <div class="byl-blogv1-container byl-blog-archive__content">
    <div class="byl-blog-archive__actions">
      <span class="byl-blog-archive__count">
        <?php global $wp_query; echo isset($wp_query) ? (int) $wp_query->found_posts : 0; ?> published articles
      </span>
      <a href="<?php echo esc_url(home_url('/blog/')); ?>">Explore all categories ↗</a>
    </div>
    <?php if (have_posts()): ?>
      <div class="byl-blog-archive__grid">
      <?php while (have_posts()): the_post(); ?>
        <article <?php post_class('byl-blog-archive__card'); ?>>
          <a class="byl-blog-archive__image" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
          <?php if (has_post_thumbnail()): ?>
            <?php the_post_thumbnail('medium_large', ['loading'=>'lazy','alt'=>get_the_title()]); ?>
          <?php else: ?><span class="byl-blog-archive__image-fallback" aria-hidden="true">BeYourLover</span><?php endif; ?>
          </a>
          <div class="byl-blog-archive__card-info">
            <time datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(get_the_date()); ?></time>
            <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
            <p><?php echo esc_html(wp_trim_words(wp_strip_all_tags(get_the_excerpt()), 20)); ?></p>
            <a class="byl-blog-archive__more" href="<?php the_permalink(); ?>">Read story →</a>
          </div>
        </article>
      <?php endwhile; ?>
      </div>
      <nav class="byl-blog-archive__pagination" aria-label="Article pages">
      <?php the_posts_pagination([
          'mid_size'=>2,'prev_text'=>'← Previous','next_text'=>'Next →',
          'screen_reader_text'=>'Blog category page navigation',
      ]); ?>
      </nav>
    <?php else: ?>
      <div class="byl-blog-archive__empty">No published articles in this category yet.</div>
    <?php endif; ?>
  </div>
</main>
<?php byl_blog_editorial_footer(); ?>
