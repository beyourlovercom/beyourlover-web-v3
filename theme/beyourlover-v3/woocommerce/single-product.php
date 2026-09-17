<?php
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }
get_header();

global $product;
if (!$product instanceof WC_Product) { $product = wc_get_product(get_the_ID()); }
if (!$product instanceof WC_Product) { get_footer(); return; }

do_action('woocommerce_before_single_product');
$name = byl_v3_product_display_name($product);
$subtitle = byl_v3_product_subtitle($product);
$gallery = byl_v3_product_gallery($product);
$rating = (float)$product->get_average_rating();
$reviews = (int)$product->get_review_count();
$short = (string)$product->get_short_description();
$rich = byl_v3_product_rich_text($product);
$related = byl_v3_product_related($product, 4);
?>
<main id="main" class="byl-product-page">
  <div class="byl-content">
    <nav class="byl-product-breadcrumbs" aria-label="Breadcrumb">
      <?php foreach (byl_v3_product_breadcrumbs($product) as $i=>$item): ?>
        <?php if ($i): ?><span aria-hidden="true">/</span><?php endif; ?>
        <?php if ($item['url'] !== ''): ?><a href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['label']); ?></a><?php else: ?><span aria-current="page"><?php echo esc_html($item['label']); ?></span><?php endif; ?>
      <?php endforeach; ?>
    </nav>

    <section class="byl-product-hero">
      <div class="byl-product-gallery" aria-label="Product gallery">
        <?php if ($gallery !== []): ?>
          <div class="byl-product-gallery-grid">
          <?php foreach ($gallery as $index=>$image): ?>
            <figure class="byl-product-gallery-item<?php echo $index===0?' is-primary':''; ?>">
              <img src="<?php echo esc_url($image['url']); ?>" alt="<?php echo esc_attr($image['alt']); ?>" loading="<?php echo $index===0?'eager':'lazy'; ?>" decoding="async"<?php if ($image['width'] && $image['height']): ?> width="<?php echo (int)$image['width']; ?>" height="<?php echo (int)$image['height']; ?>"<?php endif; ?>>
            </figure>
          <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="byl-product-gallery-empty"><?php echo wp_kses_post(wc_placeholder_img('woocommerce_single')); ?></div>
        <?php endif; ?>
      </div>

      <div class="byl-product-summary">
        <div class="byl-product-summary-inner">
          <?php if ($product->is_on_sale()): ?><span class="byl-chip">Sale</span><?php endif; ?>
          <p class="byl-eyebrow"><?php echo esc_html($product->get_sku() ?: 'BeYourLover'); ?></p>
          <h1 class="byl-product-name"><?php echo esc_html($name); ?></h1>
          <?php if ($subtitle !== ''): ?><p class="byl-product-subtitle"><?php echo esc_html($subtitle); ?></p><?php endif; ?>
          <div class="byl-product-rating" aria-label="<?php echo esc_attr($rating > 0 ? $rating.' out of 5 stars' : 'No ratings yet'); ?>">
            <span aria-hidden="true">★</span><strong><?php echo $rating > 0 ? esc_html(number_format($rating,1)) : '—'; ?></strong>
            <?php if ($reviews): ?><a href="#reviews"><?php echo esc_html(sprintf(_n('%d review','%d reviews',$reviews,'beyourlover-v3'),$reviews)); ?></a><?php endif; ?>
          </div>
          <div class="byl-product-price"><?php echo wp_kses_post($product->get_price_html()); ?></div>
          <?php if ($short !== ''): ?><div class="byl-product-short"><?php echo wp_kses_post(wpautop($short)); ?></div><?php endif; ?>
          <div class="byl-product-purchase">
            <?php woocommerce_template_single_add_to_cart(); ?>
          </div>
          <div class="byl-product-trust" aria-label="Shopping assurances">
            <span><?php echo byl_v3_icon('check'); ?> Secure checkout</span>
            <span><?php echo byl_v3_icon('check'); ?> Discreet shipping</span>
            <span><?php echo byl_v3_icon('check'); ?> Support when you need it</span>
          </div>
          <div class="byl-product-meta-native"><?php woocommerce_template_single_meta(); ?></div>
        </div>
      </div>
    </section>

    <section class="byl-product-details byl-section-compact" aria-labelledby="product-details-title">
      <div class="byl-product-details-head"><p class="byl-eyebrow">Product details</p><h2 id="product-details-title" class="byl-heading-xl">Everything you need to know.</h2></div>
      <div class="byl-product-details-body byl-reading"><?php the_content(); ?></div>
    </section>

    <?php if ($rich !== ''): ?>
    <section class="byl-product-rich byl-section-compact" aria-label="Additional product information"><div class="byl-reading"><?php echo $rich; ?></div></section>
    <?php endif; ?>

    <?php if (comments_open() || $reviews > 0): ?>
    <section id="reviews" class="byl-product-reviews byl-section-compact" aria-labelledby="reviews-title">
      <div class="byl-product-details-head"><p class="byl-eyebrow">Reviews</p><h2 id="reviews-title" class="byl-heading-xl">What customers say.</h2></div>
      <div class="byl-product-review-native"><?php comments_template(); ?></div>
    </section>
    <?php endif; ?>

    <?php if ($related !== []): ?>
    <section class="byl-product-related byl-section-compact" aria-labelledby="related-products-title">
      <div class="byl-product-section-head"><div><p class="byl-eyebrow">Continue exploring</p><h2 id="related-products-title" class="byl-heading-xl">You may also like.</h2></div><a class="byl-link" href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>">Shop all products</a></div>
      <div class="byl-product-related-grid"><?php foreach ($related as $item) { echo byl_v3_product_card($item); } ?></div>
    </section>
    <?php endif; ?>
  </div>
</main>
<?php
do_action('woocommerce_after_single_product');
get_footer();
