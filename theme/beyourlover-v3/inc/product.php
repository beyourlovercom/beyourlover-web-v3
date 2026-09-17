<?php
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

function byl_v3_attachment_image_data(int $attachment_id, string $size = 'full'): array {
    if ($attachment_id <= 0) { return []; }
    $meta = wp_get_attachment_metadata($attachment_id);
    $meta = is_array($meta) ? $meta : [];
    $attached = (string) get_post_meta($attachment_id, '_wp_attached_file', true);
    $path = trim((string) get_post_meta($attachment_id, 'advmo_path', true), '/');
    $offloaded = (string) get_post_meta($attachment_id, 'advmo_offloaded', true);
    $file = wp_basename($attached);
    $width = (int)($meta['width'] ?? 0);
    $height = (int)($meta['height'] ?? 0);
    if ($size !== 'full' && !empty($meta['sizes'][$size]) && is_array($meta['sizes'][$size])) {
        $file = (string)($meta['sizes'][$size]['file'] ?? $file);
        $width = (int)($meta['sizes'][$size]['width'] ?? $width);
        $height = (int)($meta['sizes'][$size]['height'] ?? $height);
    }
    if ($offloaded === '1' && $path !== '' && $file !== '') {
        $url = 'https://img.beyourlover.com/'.trailingslashit($path).rawurlencode($file);
    } else {
        $src = wp_get_attachment_image_src($attachment_id, $size);
        $url = is_array($src) && !empty($src[0]) ? (string)$src[0] : byl_v3_attachment_url($attachment_id);
        if (is_array($src)) { $width = (int)$src[1]; $height = (int)$src[2]; }
    }    $alt = trim((string) get_post_meta($attachment_id, '_wp_attachment_image_alt', true));
    if ($alt === '') { $alt = byl_v3_brand_display_text((string) get_the_title($attachment_id)); }
    return ['id'=>$attachment_id,'url'=>$url,'width'=>$width,'height'=>$height,'alt'=>$alt];
}

function byl_v3_product_gallery(WC_Product $product): array {
    $ids = array_filter(array_merge([(int)$product->get_image_id()], array_map('intval', $product->get_gallery_image_ids())));
    $ids = array_values(array_unique($ids));
    $gallery = [];
    foreach (array_slice($ids, 0, 12) as $id) {
        $main = byl_v3_attachment_image_data((int)$id, 'woocommerce_single');
        if (empty($main['url'])) { $main = byl_v3_attachment_image_data((int)$id, 'full'); }
        if (empty($main['url'])) { continue; }
        $thumb = byl_v3_attachment_image_data((int)$id, 'woocommerce_thumbnail');
        $main['thumb_url'] = (string)($thumb['url'] ?? $main['url']);
        $main['full_url'] = (string)(byl_v3_attachment_image_data((int)$id, 'full')['url'] ?? $main['url']);
        $gallery[] = $main;
    }
    return $gallery;
}

function byl_v3_product_primary_category(WC_Product $product): ?WP_Term {
    $terms = wp_get_post_terms($product->get_id(), 'product_cat');
    if (is_wp_error($terms) || !$terms) { return null; }
    $generic = ['all-sex-toys','sex-toys','all-female-sex-toys','all-male-sex-toys'];
    foreach ($terms as $term) { if ($term instanceof WP_Term && !in_array($term->slug, $generic, true)) { return $term; } }
    return $terms[0] instanceof WP_Term ? $terms[0] : null;
}
function byl_v3_product_lead(WC_Product $product, int $words = 52): string {
    $text = trim(wp_strip_all_tags($product->get_short_description()));
    return $text === '' ? '' : wp_trim_words($text, $words, '…');
}

function byl_v3_product_rating(WC_Product $product): string {
    $rating = (float)$product->get_average_rating();
    $count = (int)$product->get_review_count();
    if ($count <= 0) { return '<span class="byl-product-rating is-empty">No reviews yet</span>'; }
    $label = number_format($rating, 1).' out of 5 from '.$count.' reviews';
    return '<a class="byl-product-rating" href="#reviews" aria-label="'.esc_attr($label).'"><span aria-hidden="true">★</span><strong>'.esc_html(number_format($rating, 1)).'</strong><span>'.esc_html('('.$count.')').'</span></a>';
}

function byl_v3_related_products(WC_Product $product, int $limit = 4): array {
    if (!function_exists('wc_get_related_products')) { return []; }
    $ids = wc_get_related_products($product->get_id(), $limit, [$product->get_id()]);
    $items = [];
    foreach ($ids as $id) {
        $related = wc_get_product((int)$id);
        if ($related instanceof WC_Product && $related->get_status() === 'publish') { $items[] = $related; }
    }
    return $items;
}

function byl_v3_product_availability_label(WC_Product $product): string {
    if (!$product->is_in_stock()) { return 'Out of stock'; }
    if ($product->is_on_backorder(1)) { return 'Available on backorder'; }
    return 'In stock';
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


function byl_v3_product_description_html(WC_Product $product): string {
    $html = (string)$product->get_description();
    if ($html === '') { return ''; }
    $html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html) ?? $html;
    $html = preg_replace('#<style\b[^>]*>.*?</style>#is', '', $html) ?? $html;
    $html = preg_replace('#<link\b[^>]*>#is', '', $html) ?? $html;
    $html = do_shortcode($html);
    return wp_kses_post($html);
}

function byl_v3_product_attributes_html(WC_Product $product): string {
    if (!$product->has_attributes() && !$product->has_weight() && !$product->has_dimensions()) { return ''; }
    ob_start();
    wc_display_product_attributes($product);
    return (string)ob_get_clean();
}

function byl_v3_product_rich_text(WC_Product $product): string {
    $content = (string)get_post_meta($product->get_id(), '_custom_rich_text', true);
    return $content === '' ? '' : wp_kses_post(do_shortcode($content));
}

add_action('wp', static function (): void {
    if (!function_exists('is_product') || !is_product() || !function_exists('wc_get_product')) { return; }
    $product = wc_get_product(get_queried_object_id());
    if ($product instanceof WC_Product && function_exists('WC') && WC() && isset(WC()->structured_data)) {
        WC()->structured_data->generate_product_data($product);
    }
}, 20);
