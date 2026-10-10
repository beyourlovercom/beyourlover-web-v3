<?php
/**
 * Blog single sidebar: real Dev Woo products and published WordPress Posts.
 * Read-only render; the newsletter CTA links to the existing page rather
 * than pretending to submit a subscription through an unverified endpoint.
 */
declare(strict_types=1);
defined('ABSPATH') || exit;

$byl_sidebar_products = [];
if (function_exists('wc_get_products')) {
    $byl_sidebar_products = wc_get_products([
        'status' => 'publish',
        'limit' => 3,
        'orderby' => 'date',
        'order' => 'DESC',
        'visibility' => 'visible',
    ]);
    if (!is_array($byl_sidebar_products)) {
        $byl_sidebar_products = [];
    }
}

$byl_sidebar_posts = get_posts([
    'post_type' => 'post',
    'post_status' => 'publish',
    'posts_per_page' => 5,
    'post__not_in' => [(int) get_the_ID()],
    'orderby' => 'date',
    'order' => 'DESC',
    'ignore_sticky_posts' => true,
    'no_found_rows' => true,
]);
?>
<aside class="byl-blog-sidebar" aria-label="<?php esc_attr_e('Blog sidebar', 'beyourlover-v3'); ?>">
  <section class="byl-blog-sidebar__module byl-blog-sidebar__newsletter" aria-labelledby="byl-blog-newsletter-heading">
    <h2 id="byl-blog-newsletter-heading"><?php esc_html_e('Join Our Newsletter', 'beyourlover-v3'); ?></h2>
    <p><?php esc_html_e('Get the latest updates and offers.', 'beyourlover-v3'); ?></p>
    <a class="byl-blog-sidebar__subscribe" href="<?php echo esc_url(home_url('/newsletter/')); ?>"><?php esc_html_e('Subscribe', 'beyourlover-v3'); ?></a>
  </section>

  <?php if ($byl_sidebar_products) : ?>
  <section class="byl-blog-sidebar__module" aria-labelledby="byl-blog-new-arrivals-heading">
    <h2 id="byl-blog-new-arrivals-heading"><?php esc_html_e('New Arrivals', 'beyourlover-v3'); ?></h2>
    <ul class="byl-blog-sidebar__items">
      <?php foreach ($byl_sidebar_products as $byl_sidebar_product) : ?>
        <?php if (!is_object($byl_sidebar_product) || !method_exists($byl_sidebar_product, 'is_visible') || !$byl_sidebar_product->is_visible()) { continue; } ?>
        <li class="byl-blog-sidebar__item">
          <a class="byl-blog-sidebar__image" href="<?php echo esc_url($byl_sidebar_product->get_permalink()); ?>" aria-label="<?php echo esc_attr($byl_sidebar_product->get_name()); ?>">
            <?php echo wp_kses_post($byl_sidebar_product->get_image('woocommerce_thumbnail', ['loading' => 'lazy'])); ?>
          </a>
          <div class="byl-blog-sidebar__item-body">
            <a class="byl-blog-sidebar__item-name" href="<?php echo esc_url($byl_sidebar_product->get_permalink()); ?>"><?php echo esc_html($byl_sidebar_product->get_name()); ?></a>
            <?php $byl_price = $byl_sidebar_product->get_price_html(); ?>
            <?php if (is_string($byl_price) && $byl_price !== '') : ?>
              <div class="byl-blog-sidebar__price"><?php echo wp_kses_post($byl_price); ?></div>
            <?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>
  <?php endif; ?>

  <?php if ($byl_sidebar_posts) : ?>
  <section class="byl-blog-sidebar__module" aria-labelledby="byl-blog-recent-posts-heading">
    <h2 id="byl-blog-recent-posts-heading"><?php esc_html_e('Recent Posts', 'beyourlover-v3'); ?></h2>
    <ul class="byl-blog-sidebar__items">
      <?php foreach ($byl_sidebar_posts as $byl_sidebar_post) : ?>
        <?php if (!($byl_sidebar_post instanceof WP_Post)) { continue; } ?>
        <li class="byl-blog-sidebar__item">
          <?php if (has_post_thumbnail($byl_sidebar_post)) : ?>
          <a class="byl-blog-sidebar__image" href="<?php echo esc_url(get_permalink($byl_sidebar_post)); ?>" aria-label="<?php echo esc_attr(get_the_title($byl_sidebar_post)); ?>">
            <?php echo get_the_post_thumbnail($byl_sidebar_post, 'thumbnail', ['loading' => 'lazy']); ?>
          </a>
          <?php endif; ?>
          <div class="byl-blog-sidebar__item-body">
            <a class="byl-blog-sidebar__item-name" href="<?php echo esc_url(get_permalink($byl_sidebar_post)); ?>"><?php echo esc_html(get_the_title($byl_sidebar_post)); ?></a>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>
  <?php endif; ?>
</aside>
