<?php
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

function byl_v3_icon(string $name): string {
    $paths = [
        'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'heart' => '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8Z"/>',
        'bag' => '<path d="M5 8h14l-1 12H6L5 8Z"/><path d="M9 9V6a3 3 0 0 1 6 0v3"/>',
        'book' => '<path d="M4 5.5A3.5 3.5 0 0 1 7.5 2H20v16H7.5A3.5 3.5 0 0 0 4 21.5v-16Z"/><path d="M4 18h13"/>',
        'check' => '<path d="m5 12 4 4L19 6"/>',
        'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 10v6M12 7h.01"/>',
    ];
    $path = $paths[$name] ?? $paths['arrow'];
    return '<span class="byl-icon" aria-hidden="true"><svg viewBox="0 0 24 24">'.$path.'</svg></span>';
}

function byl_v3_product_image_url(WC_Product $product): string {
    $image_id = (int) $product->get_image_id();
    if ($image_id > 0) {
        $url = byl_v3_attachment_url($image_id);
        if ($url !== '') { return $url; }
    }
    return function_exists('wc_placeholder_img_src') ? (string) wc_placeholder_img_src('woocommerce_thumbnail') : '';
}

function byl_v3_product_card(WC_Product $product): string {
    $url = get_permalink($product->get_id());
    $image = byl_v3_product_image_url($product);
    $rating = (float) $product->get_average_rating();
    $count = (int) $product->get_review_count();
    $badge = $product->is_on_sale() ? 'Sale' : (!$product->is_in_stock() ? 'Out of stock' : '');
    ob_start();
    ?>
    <article class="byl-product-card<?php echo !$product->is_in_stock() ? ' is-unavailable' : ''; ?>">
      <a class="byl-product-media" href="<?php echo esc_url((string)$url); ?>">
        <?php if ($badge !== ''): ?><span class="byl-badge byl-badge-overlay"><?php echo esc_html($badge); ?></span><?php endif; ?>
        <?php if ($image !== ''): ?><img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($product->get_name()); ?>" loading="lazy"><?php endif; ?>
      </a>
      <div class="byl-product-body">
        <div class="byl-product-kicker"><?php echo esc_html($product->get_sku() ?: 'BeYourLover'); ?></div>
        <h3 class="byl-product-title"><a href="<?php echo esc_url((string)$url); ?>"><?php echo esc_html($product->get_name()); ?></a></h3>
        <div class="byl-product-meta">
          <div class="byl-rating" aria-label="<?php echo esc_attr($rating > 0 ? $rating.' out of 5 stars' : 'No ratings yet'); ?>">
            <span aria-hidden="true">&#9733;</span><strong><?php echo $rating > 0 ? esc_html(number_format($rating, 1)) : '—'; ?></strong>
            <?php if ($count > 0): ?><small><?php echo esc_html('('.$count.')'); ?></small><?php endif; ?>
          </div>
          <div class="byl-price"><?php echo wp_kses_post($product->get_price_html()); ?></div>
        </div>
        <a class="byl-product-cta" href="<?php echo esc_url((string)$url); ?>">
          <span>View product</span><?php echo byl_v3_icon('arrow'); ?>
        </a>
      </div>
    </article>
    <?php
    return (string) ob_get_clean();
}

function byl_v3_showcase_products(int $limit = 4): array {
    if (!function_exists('wc_get_products')) { return []; }
    $products = wc_get_products(['status' => 'publish', 'limit' => $limit, 'orderby' => 'date', 'order' => 'DESC']);
    return array_values(array_filter($products, static fn($p): bool => $p instanceof WC_Product));
}
