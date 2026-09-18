<?php
declare(strict_types=1);
defined('ABSPATH') || exit;
get_header();

while (have_posts()) : the_post();
    global $product;
    if (!$product instanceof WC_Product) { $product = wc_get_product(get_the_ID()); }
    if (!$product instanceof WC_Product) { continue; }
    $gallery = byl_v3_product_gallery($product);
    $primary = byl_v3_product_primary_category($product);
    $display_name = byl_v3_product_display_name($product);
    $subtitle = byl_v3_product_subtitle($product);
    $rich = byl_v3_product_rich_text($product);
    $description = byl_v3_product_description_html($product);
    $attributes = byl_v3_product_attributes_html($product);
    $lead = byl_v3_product_lead($product);
    $related = byl_v3_related_products($product, 4);
    $availability = byl_v3_product_availability_label($product);
?>
<main id="main" class="byl-product-page" data-product-id="<?php echo (int)$product->get_id(); ?>">
  <div class="byl-product-shell">
    <nav class="byl-product-breadcrumb" aria-label="Breadcrumb">
      <?php woocommerce_breadcrumb(['delimiter'=>'<span aria-hidden="true">/</span>','wrap_before'=>'<div>','wrap_after'=>'</div>']); ?>
    </nav>
    <?php woocommerce_output_all_notices(); ?>

    <section class="byl-product-hero" aria-labelledby="byl-product-title">
      <div class="byl-product-gallery" data-byl-product-gallery>
        <?php if ($gallery): $first = $gallery[0]; ?>
          <div class="byl-product-main-media">
            <?php if ($product->is_on_sale()): ?><span class="byl-product-sale-badge">Sale</span><?php endif; ?>            <img class="byl-product-main-image" src="<?php echo esc_url((string)$first['url']); ?>" data-full-src="<?php echo esc_url((string)$first['full_url']); ?>" alt="<?php echo esc_attr((string)($first['alt'] ?: $display_name)); ?>" loading="eager" fetchpriority="high" decoding="async"<?php if (!empty($first['width']) && !empty($first['height'])): ?> width="<?php echo (int)$first['width']; ?>" height="<?php echo (int)$first['height']; ?>"<?php endif; ?>>
          </div>
          <?php if (count($gallery) > 1): ?>
          <div class="byl-product-thumbs" role="list" aria-label="Product images">
            <?php foreach ($gallery as $index => $item): ?>
              <button type="button" class="byl-product-thumb<?php echo $index === 0 ? ' is-active' : ''; ?>" data-byl-product-thumb data-src="<?php echo esc_url((string)$item['url']); ?>" data-full-src="<?php echo esc_url((string)$item['full_url']); ?>" data-alt="<?php echo esc_attr((string)($item['alt'] ?: $display_name)); ?>" aria-pressed="<?php echo $index === 0 ? 'true' : 'false'; ?>">
                <img src="<?php echo esc_url((string)$item['thumb_url']); ?>" alt="" loading="lazy" decoding="async">
              </button>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        <?php else: ?>
          <div class="byl-product-main-media is-empty"><span>Product image unavailable</span></div>
        <?php endif; ?>
      </div>

      <div class="byl-product-summary">
        <div class="byl-product-eyebrow"><?php echo esc_html($primary instanceof WP_Term ? $primary->name : ($product->get_sku() ?: 'BeYourLover')); ?></div>
        <h1 id="byl-product-title"><?php echo esc_html($display_name); ?></h1>
        <?php if ($subtitle !== ''): ?><p class="byl-product-subtitle"><?php echo esc_html($subtitle); ?></p><?php endif; ?>
        <div class="byl-product-summary-row">
          <?php echo byl_v3_product_rating($product); ?>
          <?php if ($product->get_sku()): ?><span class="byl-product-sku">SKU <?php echo esc_html($product->get_sku()); ?></span><?php endif; ?>
        </div>
        <div class="byl-product-price"><?php echo wp_kses_post($product->get_price_html()); ?></div>        <?php if ($lead !== ''): ?><p class="byl-product-lead"><?php echo esc_html($lead); ?></p><?php endif; ?>
        <div class="byl-product-availability<?php echo $product->is_in_stock() ? ' is-in-stock' : ' is-out'; ?>">
          <span aria-hidden="true"></span><?php echo esc_html($availability); ?>
        </div>

        <div class="byl-product-buybox">
          <?php woocommerce_template_single_add_to_cart(); ?>
        </div>

        <dl class="byl-product-facts">
          <div><dt>Availability</dt><dd><?php echo esc_html($availability); ?></dd></div>
          <?php if ($product->get_sku()): ?><div><dt>SKU</dt><dd><?php echo esc_html($product->get_sku()); ?></dd></div><?php endif; ?>
          <?php if ($primary instanceof WP_Term): ?><div><dt>Category</dt><dd><a href="<?php echo esc_url((string)get_term_link($primary)); ?>"><?php echo esc_html($primary->name); ?></a></dd></div><?php endif; ?>
        </dl>
      </div>
    </section>

    <?php byl_v3_render_product_modules($product); ?>

    <?php if (trim($product->get_short_description()) !== ''): ?>
    <section class="byl-product-highlights" aria-labelledby="byl-product-highlights-title">
      <div class="byl-product-section-heading"><span>Product</span><h2 id="byl-product-highlights-title">Highlights</h2></div>
      <div class="byl-product-richtext"><?php echo wp_kses_post(wpautop($product->get_short_description())); ?></div>
    </section>
    <?php endif; ?>

    <?php if ($description !== ''): ?>
    <section class="byl-product-details" aria-labelledby="byl-product-description-title">
      <div class="byl-product-section-heading"><span>Details</span><h2 id="byl-product-description-title">Description</h2></div>
      <div class="byl-product-description byl-product-richtext"><?php echo $description; ?></div>
    </section>
    <?php endif; ?>

    <?php if ($attributes !== ''): ?>
    <section class="byl-product-attributes" aria-labelledby="byl-product-attributes-title">
      <div class="byl-product-section-heading"><span>Specifications</span><h2 id="byl-product-attributes-title">Product information</h2></div>
      <div class="byl-product-attributes-native"><?php echo $attributes; ?></div>
    </section>
    <?php endif; ?>

    <?php if (comments_open() || $product->get_review_count() > 0): ?>
    <section id="reviews" class="byl-product-reviews" aria-labelledby="byl-product-reviews-title">
      <div class="byl-product-section-heading"><span>Reviews</span><h2 id="byl-product-reviews-title">What customers say</h2></div>
      <div class="byl-product-review-native"><?php comments_template(); ?></div>
    </section>
    <?php endif; ?>
    <?php if ($rich !== ''): ?>
    <section class="byl-product-legacy-rich" aria-label="Additional product information">
      <div class="byl-product-section-heading"><span>More</span><h2>Additional information</h2></div>
      <div class="byl-product-richtext"><?php echo $rich; ?></div>
    </section>
    <?php endif; ?>
    <?php if ($related): ?>
    <section class="byl-product-related" aria-labelledby="byl-product-related-title">
      <div class="byl-product-section-heading"><span>Discover</span><h2 id="byl-product-related-title">You may also like</h2></div>
      <div class="byl-product-related-grid">
        <?php foreach ($related as $item): echo byl_v3_product_card($item, 'lazy'); endforeach; ?>
      </div>
    </section>
    <?php endif; ?>
  </div>
</main>
<?php
endwhile;
get_footer();
