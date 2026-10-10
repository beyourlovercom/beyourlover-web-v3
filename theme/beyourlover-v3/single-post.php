<?php
/**
 * BYL V3 — canonical default for individual WordPress blog Posts only.
 *
 * Single Post precedence: single-post.php; does not affect Woo products,
 * Pages, dedicated Blog home, Cart, Checkout, or custom post types.
 */
declare(strict_types=1);
defined('ABSPATH') || exit;

add_action('wp_enqueue_scripts', static function (): void {
    $relative = '/assets/css/post-single.css';
    $path = get_stylesheet_directory().$relative;
    wp_enqueue_style(
        'byl-v3-post-single',
        get_stylesheet_directory_uri().$relative,
        ['byl-v3-motion'],
        is_file($path) ? (string) filemtime($path) : (string) wp_get_theme()->get('Version')
    );
    $toc_relative = '/assets/js/post-toc.js';
    $toc_path = get_stylesheet_directory().$toc_relative;
    wp_enqueue_script(
        'byl-v3-post-toc',
        get_stylesheet_directory_uri().$toc_relative,
        [],
        is_file($toc_path) ? (string) filemtime($toc_path) : (string) wp_get_theme()->get('Version'),
        true
    );
}, 20);

get_header();
?>
<main id="main-content" class="byl-post-single" data-byl-post-template="default">
  <?php while (have_posts()) : the_post(); ?>
    <?php
    $byl_categories = get_the_category();
    $byl_first_category = is_array($byl_categories) && $byl_categories
        ? $byl_categories[0] : null;
    $byl_previous = get_previous_post();
    $byl_next = get_next_post();
    ?>
    <div class="byl-post-single__frame">
      <nav class="byl-post-single__breadcrumb" aria-label="<?php esc_attr_e('Breadcrumb', 'beyourlover-v3'); ?>">
        <a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Home', 'beyourlover-v3'); ?></a>
        <span aria-hidden="true">/</span>
        <a href="<?php echo esc_url(home_url('/blog/')); ?>"><?php esc_html_e('Blog', 'beyourlover-v3'); ?></a>
        <?php if ($byl_first_category instanceof WP_Term): ?>
          <span aria-hidden="true">/</span>
          <a href="<?php echo esc_url(get_category_link((int) $byl_first_category->term_id)); ?>"><?php echo esc_html($byl_first_category->name); ?></a>
        <?php endif; ?>
      </nav>

      <?php if (has_post_thumbnail()) : ?>
        <figure class="byl-post-single__hero">
          <?php the_post_thumbnail('full', ['class' => 'byl-post-single__hero-image', 'loading' => 'eager']); ?>
        </figure>
      <?php endif; ?>

      <?php /* Enhanced progressively by post-toc.js; never alters saved post body. */ ?>
      <nav class="byl-post-toc--floating" id="byl-post-floating-toc" aria-label="<?php esc_attr_e('Article index', 'beyourlover-v3'); ?>" hidden>
        <p class="byl-post-toc__rail-label"><?php esc_html_e('Index', 'beyourlover-v3'); ?></p>
        <ol class="byl-post-toc__floating-list"></ol>
      </nav>

      <div class="byl-post-single__body">
      <article <?php post_class('byl-post-single__article'); ?>>
        <header class="byl-post-single__header">
          <h1 class="byl-post-single__title"><?php echo esc_html(get_the_title()); ?></h1>
          <div class="byl-post-single__meta">
            <time datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(get_the_date()); ?></time>
            <?php if ($byl_first_category instanceof WP_Term): ?>
              <span aria-hidden="true">·</span>
              <a href="<?php echo esc_url(get_category_link((int) $byl_first_category->term_id)); ?>"><?php echo esc_html($byl_first_category->name); ?></a>
            <?php endif; ?>
          </div>
        </header>

        <div class="byl-post-single__content">
          <?php the_content(); ?>
          <?php wp_link_pages(['before' => '<nav class="byl-post-single__pagination" aria-label="' . esc_attr__('Article pages', 'beyourlover-v3') . '">', 'after' => '</nav>']); ?>
        </div>

        <?php $byl_tags = get_the_tag_list('', ', '); ?>
        <?php if (is_string($byl_tags) && $byl_tags !== '') : ?>
          <footer class="byl-post-single__tags" aria-label="<?php esc_attr_e('Tags', 'beyourlover-v3'); ?>">
            <span><?php esc_html_e('Tags:', 'beyourlover-v3'); ?></span>
            <?php echo wp_kses_post($byl_tags); ?>
          </footer>
        <?php endif; ?>

        <?php /* WWW endmatter: editorial bio, prior/next and related Posts. */ ?>
        <?php require get_stylesheet_directory().'/blog-endmatter.php'; ?>
      </article>
      <?php require get_stylesheet_directory().'/blog-sidebar.php'; ?>
      </div>
    </div>
  <?php endwhile; ?>
</main>
<?php get_footer();
