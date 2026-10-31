<?php
/**
 * BYL A0/A2: read-only WooCommerce catalog inventory for WP-CLI.
 * Invocation: wp eval-file /path/to/byl_a0_a2_catalog_export.php > catalog.json
 * No customers, orders, users, secrets, payment settings or product writes.
 */
if (!defined('WP_CLI') || !WP_CLI || !defined('ABSPATH') || !function_exists('wc_get_product')) {
    fwrite(STDERR, "BYL_A0_A2_DENY: WP-CLI with WooCommerce required\n");
    exit(2);
}
$site = untrailingslashit(home_url());
if (!in_array($site, ['https://www.beyourlover.com', 'https://dev.beyourlover.com'], true)) {
    fwrite(STDERR, "BYL_A0_A2_DENY: site allowlist mismatch\n");
    exit(2);
}
function byl_a0_terms($id, $taxonomy) {
    $terms = wp_get_post_terms($id, $taxonomy, ['fields' => 'slugs']);
    if (is_wp_error($terms)) { return []; }
    sort($terms, SORT_STRING);
    return array_values($terms);
}
function byl_a0_image($id) {
    $id = (int) $id;
    if (!$id) { return null; }
    return [
        'id' => $id,
        'url' => (string) (wp_get_attachment_url($id) ?: ''),
        'alt' => (string) get_post_meta($id, '_wp_attachment_image_alt', true),
        'exists' => get_post_type($id) === 'attachment',
    ];
}
function byl_a0_attributes($product) {
    $attrs = [];
    foreach ($product->get_attributes() as $key => $value) {
        if ($value instanceof WC_Product_Attribute) {
            $options = $value->is_taxonomy()
                ? byl_a0_terms($product->get_id(), $value->get_name())
                : array_map('strval', $value->get_options());
            sort($options, SORT_STRING);
            $attrs[$value->get_name()] = $options;
        } elseif (is_string($value)) {
            $attrs[(string) $key] = [$value];
        }
    }
    ksort($attrs, SORT_STRING);
    return $attrs;
}
$products = [];
$page = 1;
do {
    $query = new WP_Query([
        'post_type' => ['product', 'product_variation'],
        'post_status' => ['publish', 'private', 'draft', 'pending'],
        'posts_per_page' => 200,
        'paged' => $page,
        'fields' => 'ids',
        'orderby' => 'ID',
        'order' => 'ASC',
        'no_found_rows' => false,
        'suppress_filters' => true,
    ]);
    foreach ($query->posts as $id) {
        $product = wc_get_product((int) $id);
        if (!$product) { continue; }
        $parent_id = $product->is_type('variation') ? (int) $product->get_parent_id() : 0;
        $parent = $parent_id ? wc_get_product($parent_id) : null;
        $gallery = [];
        foreach ($product->get_gallery_image_ids() as $image_id) {
            $gallery[] = byl_a0_image($image_id);
        }
        $products[] = [
            'id' => (int) $id,
            'type' => (string) $product->get_type(),
            'status' => (string) $product->get_status(),
            'sku' => (string) $product->get_sku(),
            'parent_id' => $parent_id,
            'parent_sku' => $parent ? (string) $parent->get_sku() : '',
            'attributes' => byl_a0_attributes($product),
            'categories' => byl_a0_terms($parent_id ?: $id, 'product_cat'),
            'regular_price' => (string) $product->get_regular_price(),
            'sale_price' => (string) $product->get_sale_price(),
            'image' => byl_a0_image($product->get_image_id()),
            'gallery' => $gallery,
        ];
    }
    $has_more = $page < (int) $query->max_num_pages;
    wp_reset_postdata();
    $page++;
} while ($has_more);
$payload = [
    'schema' => 'byl-catalog-a0-v1',
    'site' => $site,
    'generated_at_utc' => gmdate('c'),
    'wp_version' => get_bloginfo('version'),
    'woo_version' => defined('WC_VERSION') ? WC_VERSION : 'unknown',
    'environment' => wp_get_environment_type(),
    'products' => $products,
];
$json = wp_json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
if (!is_string($json)) {
    fwrite(STDERR, "BYL_A0_A2_FAIL: could not encode catalog\n");
    exit(2);
}
fwrite(STDOUT, $json . "\n");
