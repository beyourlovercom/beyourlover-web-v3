<?php
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

function byl_v3_home_products(int $limit = 4): array {
    if (!function_exists('wc_get_products')) { return []; }
    $products = wc_get_products([
        'status' => 'publish',
        'limit' => $limit,
        'orderby' => 'popularity',
        'order' => 'DESC',
    ]);
    if (!$products) { $products = byl_v3_showcase_products($limit); }
    return array_values(array_filter($products, static fn($p): bool => $p instanceof WC_Product));
}

function byl_v3_home_category(string $slug): array {
    $term = get_term_by('slug', $slug, 'product_cat');
    if (!$term instanceof WP_Term) { return []; }
    $thumb = (int) get_term_meta($term->term_id, 'thumbnail_id', true);
    $image = $thumb > 0 ? byl_v3_attachment_url($thumb) : '';
    if ($image === '' && function_exists('wc_get_products')) {
        $fallback = wc_get_products(['status'=>'publish','limit'=>1,'category'=>[$slug],'orderby'=>'date','order'=>'DESC']);
        if (!empty($fallback[0]) && $fallback[0] instanceof WC_Product) { $image = byl_v3_product_image_url($fallback[0]); }
    }
    return [
        'name' => $term->name,
        'url' => get_term_link($term),
        'count' => (int) $term->count,
        'image' => $image,
    ];
}
