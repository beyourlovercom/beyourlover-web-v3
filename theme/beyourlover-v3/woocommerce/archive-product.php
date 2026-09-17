<?php
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

get_header();
$title = byl_v3_archive_title();
$description = byl_v3_archive_description();
$discovery_terms = byl_v3_archive_discovery_terms();
$below_content = byl_v3_archive_below_content();
$breadcrumbs = byl_v3_archive_breadcrumbs();
$sort_options = byl_v3_archive_sort_options();
$current_orderby = byl_v3_archive_current_orderby();
$is_shop_archive = function_exists('is_shop') && is_shop();
?>
<main id="main-content" class="byl-archive" data-byl-template="archive-v1">
  <section class="byl-archive-hero">
    <div class="byl-container">
      <nav class="byl-breadcrumbs" aria-label="Breadcrumb">
        <ol>
          <?php foreach ($breadcrumbs as $crumb): ?>
            <li>
              <?php if ($crumb['url'] !== ''): ?><a href="<?php echo esc_url($crumb['url']); ?>"><?php echo esc_html($crumb['label']); ?></a>
              <?php else: ?><span aria-current="page"><?php echo esc_html($crumb['label']); ?></span><?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ol>
      </nav>
      <div class="byl-archive-heading">
        <p class="byl-eyebrow"><?php echo esc_html($is_shop_archive ? 'Shop' : 'Collection'); ?></p>
        <h1><?php echo esc_html($title); ?></h1>
        <?php if ($description !== ''): ?>
          <div class="byl-archive-description byl-body-m"><?php echo wp_kses_post($description); ?></div>
        <?php endif; ?>
      </div>

      <?php if ($discovery_terms !== []): ?>
        <nav class="byl-archive-discovery" aria-label="Browse related categories">
          <?php foreach ($discovery_terms as $discovery_term):
              $term_url = get_term_link($discovery_term);
              if (is_wp_error($term_url)) { continue; }
          ?>
            <a class="byl-chip byl-archive-chip" href="<?php echo esc_url((string) $term_url); ?>">
              <?php echo esc_html($discovery_term->name); ?>
            </a>
          <?php endforeach; ?>
        </nav>
      <?php endif; ?>
    </div>
  </section>

  <section class="byl-archive-products">
    <div class="byl-container">
      <?php if (function_exists('woocommerce_output_all_notices')) { woocommerce_output_all_notices(); } ?>
      <div class="byl-archive-toolbar">
        <p class="byl-archive-result" aria-live="polite"><?php echo esc_html(byl_v3_archive_result_label()); ?></p>
        <form class="byl-archive-sort" method="get">
          <label for="byl-orderby">Sort by</label>
          <select id="byl-orderby" name="orderby" class="byl-select">
            <?php foreach ($sort_options as $value => $label): ?>
              <option value="<?php echo esc_attr((string) $value); ?>" <?php selected($current_orderby, (string) $value); ?>><?php echo esc_html((string) $label); ?></option>
            <?php endforeach; ?>
          </select>
          <?php foreach ($_GET as $key => $value):
              if (in_array((string) $key, ['orderby','paged','product-page','submit'], true) || !is_scalar($value)) { continue; }
          ?>
            <input type="hidden" name="<?php echo esc_attr((string) $key); ?>" value="<?php echo esc_attr(wp_unslash((string) $value)); ?>">
          <?php endforeach; ?>
          <button type="submit" class="byl-btn byl-btn-secondary">Apply</button>
        </form>
      </div>

      <?php if (have_posts()): ?>
        <div class="byl-product-grid byl-archive-grid">
          <?php while (have_posts()): the_post();
              $archive_product = function_exists('wc_get_product') ? wc_get_product(get_the_ID()) : null;
              if ($archive_product instanceof WC_Product) { echo byl_v3_product_card($archive_product, 'eager'); }
          endwhile; ?>
        </div>
        <?php $pagination = byl_v3_archive_pagination(); if ($pagination !== ''): ?>
          <nav class="byl-archive-pagination" aria-label="Product pagination"><?php echo wp_kses_post($pagination); ?></nav>
        <?php endif; ?>
      <?php else: ?>
        <div class="byl-empty byl-archive-empty">
          <?php echo byl_v3_icon('bag'); ?>
          <h2 class="byl-heading-m">No products found</h2>
          <p class="byl-body-s">Try another collection or return to the shop.</p>
          <a class="byl-btn byl-btn-primary" href="<?php echo esc_url(function_exists('wc_get_page_permalink') ? (string) wc_get_page_permalink('shop') : home_url('/shop/')); ?>">Shop all products</a>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <?php if ($below_content !== ''): ?>
    <section class="byl-archive-seo" aria-label="About this collection">
      <div class="byl-container">
        <div class="byl-reading byl-archive-seo-content"><?php echo $below_content; ?></div>
      </div>
    </section>
  <?php endif; ?>
</main>
<?php get_footer();
