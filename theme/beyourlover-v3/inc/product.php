<?php
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

function byl_v3_product_attachment_data(int $attachment_id): array {
    if ($attachment_id <= 0) { return ['url'=>'','width'=>0,'height'=>0,'alt'=>'']; }
    $meta = wp_get_attachment_metadata($attachment_id);
    $url = byl_v3_attachment_url($attachment_id);
    $alt = trim((string) get_post_meta($attachment_id, '_wp_attachment_image_alt', true));
    return [
        'url' => $url,
        'width' => is_array($meta) ? (int)($meta['width'] ?? 0) : 0,
        'height' => is_array($meta) ? (int)($meta['height'] ?? 0) : 0,
        'alt' => $alt,
    ];
}

function byl_v3_product_gallery(WC_Product $product): array {
    $ids = array_values(array_unique(array_filter(array_merge(
        [(int)$product->get_image_id()],
        array_map('absint', $product->get_gallery_image_ids())
    ))));
    $items = [];
    foreach ($ids as $id) {
        $image = byl_v3_product_attachment_data($id);
        if (($image['url'] ?? '') === '') { continue; }
        if (($image['alt'] ?? '') === '') { $image['alt'] = byl_v3_brand_display_text($product->get_name()); }
        $image['id'] = $id;
        $items[] = $image;
    }
    return $items;
}

function byl_v3_product_display_name(WC_Product $product): string {
    $acf = function_exists('get_field') ? trim((string)get_field('productname', $product->get_id())) : '';
    if ($acf !== '') { return byl_v3_brand_display_text($acf); }
    $stored = trim((string)get_post_meta($product->get_id(), 'productname', true));
    return byl_v3_brand_display_text($stored !== '' ? $stored : $product->get_name());
}

function byl_v3_product_subtitle(WC_Product $product): string {
    $acf = function_exists('get_field') ? trim((string)get_field('subtitle', $product->get_id())) : '';
    if ($acf !== '') { return $acf; }
    return trim((string)get_post_meta($product->get_id(), 'subtitle', true));
}

function byl_v3_product_breadcrumbs(WC_Product $product): array {
    $items = [['label'=>'Home','url'=>home_url('/')]];
    $shop = function_exists('wc_get_page_permalink') ? (string)wc_get_page_permalink('shop') : home_url('/shop/');
    $items[] = ['label'=>'Shop','url'=>$shop];
    $terms = get_the_terms($product->get_id(), 'product_cat');
    if (is_array($terms) && $terms !== []) {
        usort($terms, static fn(WP_Term $a, WP_Term $b): int => count(get_ancestors($b->term_id,'product_cat')) <=> count(get_ancestors($a->term_id,'product_cat')));
        $term = $terms[0];
        foreach (array_reverse(get_ancestors($term->term_id, 'product_cat')) as $ancestor_id) {
            $ancestor = get_term((int)$ancestor_id, 'product_cat');
            if ($ancestor instanceof WP_Term) {
                $url = get_term_link($ancestor);
                $items[] = ['label'=>$ancestor->name,'url'=>is_wp_error($url)?'':(string)$url];
            }
        }
        $url = get_term_link($term);
        $items[] = ['label'=>$term->name,'url'=>is_wp_error($url)?'':(string)$url];
    }
    $items[] = ['label'=>byl_v3_product_display_name($product),'url'=>''];
    return $items;
}

function byl_v3_product_rich_text(WC_Product $product): string {
    $content = (string)get_post_meta($product->get_id(), '_custom_rich_text', true);
    if ($content === '') { return ''; }
    return wp_kses_post(do_shortcode($content));
}

function byl_v3_product_related(WC_Product $product, int $limit = 4): array {
    if (!function_exists('wc_get_related_products')) { return []; }
    $ids = wc_get_related_products($product->get_id(), $limit, $product->get_upsell_ids());
    $products = [];
    foreach ($ids as $id) {
        $candidate = wc_get_product((int)$id);
        if ($candidate instanceof WC_Product && $candidate->is_visible()) { $products[] = $candidate; }
    }
    return $products;
}

add_action('wp', static function (): void {
    if (!function_exists('is_product') || !is_product() || !function_exists('wc_get_product')) { return; }
    $product = wc_get_product(get_queried_object_id());
    if ($product instanceof WC_Product && function_exists('WC') && WC() && isset(WC()->structured_data)) {
        WC()->structured_data->generate_product_data($product);
    }
}, 20);
