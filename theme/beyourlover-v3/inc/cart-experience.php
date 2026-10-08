<?php
/**
 * Cart-only product recommendations, always backed by real WooCommerce products.
 * Priority: native cross-sells -> product upsells -> related categories -> best sellers.
 */
declare(strict_types=1);
defined('ABSPATH') || exit;

function byl_v3_cart_recommendations(int $limit = 4): array {
    if (!function_exists('WC') || !WC() || !WC()->cart || !function_exists('wc_get_products')) { return []; }
    $items = WC()->cart->get_cart();
    if (!$items) { return []; }

    $excluded = [];
    $candidate_ids = [];
    $category_slugs = [];
    foreach ($items as $item) {
        $product_id = (int)($item['product_id'] ?? 0);
        $variation_id = (int)($item['variation_id'] ?? 0);
        if ($product_id > 0) { $excluded[$product_id] = true; }
        if ($variation_id > 0) { $excluded[$variation_id] = true; }
        $product = $item['data'] ?? null;
        if ($product && method_exists($product, 'get_upsell_ids')) {
            foreach ($product->get_upsell_ids() as $id) { $candidate_ids[] = (int)$id; }
        }
        if ($product_id && function_exists('get_the_terms')) {
            $terms = get_the_terms($product_id, 'product_cat');
            if (is_array($terms)) {
                foreach ($terms as $term) { if (!empty($term->slug)) { $category_slugs[(string)$term->slug] = true; } }
            }
        }
    }

    $cross_sells = method_exists(WC()->cart, 'get_cross_sells') ? (array)WC()->cart->get_cross_sells() : [];
    $candidate_ids = array_merge(array_map('intval', $cross_sells), $candidate_ids);
    $selected = [];
    $consider = static function ($product) use (&$selected, $excluded, $limit): void {
        if (!$product || !is_object($product) || !method_exists($product, 'get_id')) { return; }
        $id = (int)$product->get_id();
        if (!$id || isset($excluded[$id]) || isset($selected[$id])) { return; }
        // Direct add-to-cart is available for simple products without option selection.
        if (!$product->is_type('simple') || !$product->is_visible() || !$product->is_purchasable() || !$product->is_in_stock()) { return; }
        if ($product->get_status() !== 'publish') { return; }
        if (count($selected) < $limit) { $selected[$id] = $product; }
    };

    foreach (array_unique($candidate_ids) as $id) {
        if (count($selected) >= $limit) { break; }
        $consider(wc_get_product((int)$id));
    }
    if (count($selected) < $limit && $category_slugs) {
        $products = wc_get_products([
            'status'=>'publish', 'category'=>array_keys($category_slugs),
            'limit'=>16, 'exclude'=>array_keys($excluded), 'orderby'=>'popularity',
            'order'=>'DESC',
        ]);
        foreach ($products as $product) { $consider($product); }
    }
    if (count($selected) < $limit) {
        $products = wc_get_products([
            'status'=>'publish', 'limit'=>16, 'exclude'=>array_keys($excluded),
            'orderby'=>'popularity', 'order'=>'DESC',
        ]);
        foreach ($products as $product) { $consider($product); }
    }
    return array_values($selected);
}

function byl_v3_render_cart_recommendations(): void {
    if (!function_exists('is_cart') || !is_cart()) { return; }
    $products = byl_v3_cart_recommendations(4);
    if (!$products) { return; }
    ?>
    <section class="byl-cart-recommendations" aria-label="<?php esc_attr_e('Recommended products', 'beyourlover-v3'); ?>">
      <div class="byl-cart-recommendations__heading">
        <h2><?php esc_html_e('You may also like', 'beyourlover-v3'); ?></h2>
        <a href="<?php echo esc_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/')); ?>"><?php esc_html_e('See more', 'beyourlover-v3'); ?> →</a>
      </div>
      <div class="byl-cart-recommendations__items">
        <?php foreach ($products as $product) : ?>
        <article class="byl-cart-recommendation">
          <a class="byl-cart-recommendation__image" href="<?php echo esc_url(get_permalink($product->get_id())); ?>">
            <?php echo wp_kses_post($product->get_image('woocommerce_thumbnail')); ?>
          </a>
          <div class="byl-cart-recommendation__detail">
            <a class="byl-cart-recommendation__name" href="<?php echo esc_url(get_permalink($product->get_id())); ?>"><?php echo esc_html($product->get_name()); ?></a>
            <div class="byl-cart-recommendation__price"><?php echo wp_kses_post($product->get_price_html()); ?></div>
            <a class="byl-cart-recommendation__add" href="<?php echo esc_url(add_query_arg('add-to-cart', $product->get_id(), wc_get_cart_url())); ?>" aria-label="<?php echo esc_attr(sprintf(__('Add %s to cart', 'beyourlover-v3'), $product->get_name())); ?>"><?php esc_html_e('Add to cart', 'beyourlover-v3'); ?></a>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    </section>
    <?php
}
add_action('woocommerce_after_cart', 'byl_v3_render_cart_recommendations', 30);
