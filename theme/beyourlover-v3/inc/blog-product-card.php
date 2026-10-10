<?php
/**
 * BYL V3 — legacy [product_card id="123"] for published blog Posts only.
 * Compatible with curly-quoted historical shortcodes stored in WordPress.
 *
 * WooCommerce alone owns product visibility, pricing and permalink data.
 * No cart/checkout/payment mutations, external writes or historical edits.
 */
declare(strict_types=1);
defined('ABSPATH') || exit;

/** Repair imported smart quotes inside ONLY the legacy product_card marker. */
function byl_v3_normalize_blog_product_card(string $content): string {
    return (string) preg_replace(
        '~\[product_card\s+id\s*=\s*[\x{201C}\x{201D}\x{201E}](\d{1,10})[\x{201C}\x{201D}\x{201E}]\s*\]~u',
        '[product_card id="$1"]',
        $content
    );
}

/**
 * Fail closed unless a published, customer-visible real Woo product exists.
 * This is presentation-only: price/image/permalink are live Woo records.
 */
function byl_v3_render_blog_product_card($atts = []): string {
    if (!function_exists('is_singular') || !is_singular('post') || !function_exists('wc_get_product')) {
        return '';
    }
    $id = is_array($atts) ? (string) ($atts['id'] ?? '') : '';
    if (!preg_match('/^[1-9]\d{0,9}$/D', $id)) {
        return '';
    }
    $product = wc_get_product((int) $id);
    if (!is_object($product)
        || !method_exists($product, 'get_status')
        || !method_exists($product, 'is_visible')
        || $product->get_status() !== 'publish'
        || !$product->is_visible()
    ) {
        return '';
    }

    $name = (string) $product->get_name();
    $permalink = (string) $product->get_permalink();
    if ($name === '' || $permalink === '') {
        return '';
    }
    $image = $product->get_image('woocommerce_thumbnail', ['loading' => 'lazy']);
    $description = trim((string) wp_strip_all_tags((string) $product->get_short_description()));
    $excerpt = $description !== '' ? wp_trim_words($description, 28, '…') : '';
    $price_html = (string) $product->get_price_html();

    $sale_pct = 0;
    if (method_exists($product, 'is_on_sale') && $product->is_on_sale()
        && method_exists($product, 'get_regular_price')
        && method_exists($product, 'get_sale_price')
    ) {
        $regular = (float) $product->get_regular_price();
        $sale = (float) $product->get_sale_price();
        if ($regular > 0 && $sale > 0 && $sale < $regular) {
            $sale_pct = (int) round(($regular - $sale) * 100 / $regular);
        }
    }

    ob_start();
    ?>
    <div class="c-product-card byl-blog-product-card" data-byl-blog-product-id="<?php echo esc_attr($id); ?>">
      <div class="c-product-card__media">
        <a href="<?php echo esc_url($permalink); ?>" aria-label="<?php echo esc_attr($name); ?>">
          <?php echo wp_kses_post((string) $image); ?>
        </a>
      </div>
      <div class="c-product-card__content">
        <h3 class="c-product-card__title"><?php echo esc_html($name); ?></h3>
        <?php if ($excerpt !== '') : ?>
          <p class="c-product-card__description"><?php echo esc_html($excerpt); ?></p>
        <?php endif; ?>
        <?php if ($price_html !== '') : ?>
          <div class="c-product-card__price-row">
            <span class="c-product-card__price-current"><?php echo wp_kses_post($price_html); ?></span>
            <?php if ($sale_pct > 0) : ?>
              <span class="c-product-card__badge"><?php echo esc_html((string) $sale_pct); ?>% OFF</span>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
      <div class="c-product-card__action">
        <a href="<?php echo esc_url($permalink); ?>" class="c-product-card__button"><?php esc_html_e('View Product', 'beyourlover-v3'); ?></a>
      </div>
    </div>
    <?php
    return (string) ob_get_clean();
}

add_filter('the_content', static function ($content) {
    if (!is_string($content) || !is_singular('post')) {
        return $content;
    }
    return byl_v3_normalize_blog_product_card($content);
}, 10);
add_shortcode('product_card', 'byl_v3_render_blog_product_card');
