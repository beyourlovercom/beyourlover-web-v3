<?php
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

function byl_v3_archive_term(): ?WP_Term {
    if (!is_tax()) { return null; }
    $term = get_queried_object();
    return $term instanceof WP_Term ? $term : null;
}

function byl_v3_archive_title(): string {
    if (function_exists('is_shop') && is_shop()) {
        $shop_id = function_exists('wc_get_page_id') ? (int) wc_get_page_id('shop') : 0;
        $title = $shop_id > 0 ? get_the_title($shop_id) : 'Shop';
        return is_string($title) && $title !== '' ? $title : 'Shop';
    }
    $term = byl_v3_archive_term();
    return $term ? $term->name : (string) post_type_archive_title('', false);
}

function byl_v3_archive_description(): string {
    $term = byl_v3_archive_term();
    if (!$term) { return ''; }
    return (string) term_description($term->term_id, $term->taxonomy);
}

function byl_v3_archive_below_content(): string {
    $term = byl_v3_archive_term();
    if (!$term || $term->taxonomy !== 'product_cat') { return ''; }
    $content = (string) get_term_meta($term->term_id, 'below_category_content', true);
    if ($content === '') {
        $legacy = get_option('taxonomy_'.$term->term_id, []);
        if (is_array($legacy) && !empty($legacy['custom_term_meta'])) {
            $content = (string) $legacy['custom_term_meta'];
        }
    }
    if ($content === '') { return ''; }
    global $wp_embed;
    $content = wp_kses_post($content);
    if ($wp_embed instanceof WP_Embed) { $content = $wp_embed->autoembed($content); }
    return wp_kses_post(do_shortcode($content));
}

function byl_v3_archive_discovery_terms(): array {
    if (function_exists('is_shop') && is_shop()) {
        $slugs = ['sex-toys-for-women','sex-toys-for-men','sex-toys-for-couples'];
        $terms = [];
        foreach ($slugs as $slug) {
            $term = get_term_by('slug', $slug, 'product_cat');
            if ($term instanceof WP_Term) { $terms[] = $term; }
        }
        return $terms;
    }
    $term = byl_v3_archive_term();
    if (!$term || $term->taxonomy !== 'product_cat') { return []; }
    $children = get_terms([
        'taxonomy' => 'product_cat',
        'parent' => $term->term_id,
        'hide_empty' => true,
        'orderby' => 'name',
        'order' => 'ASC',
    ]);
    return is_wp_error($children) ? [] : array_values($children);
}

function byl_v3_archive_sort_options(): array {
    $options = [
        'menu_order' => 'Featured',
        'popularity' => 'Popularity',
        'rating' => 'Average rating',
        'date' => 'Newest',
        'price' => 'Price: low to high',
        'price-desc' => 'Price: high to low',
    ];
    return (array) apply_filters('woocommerce_catalog_orderby', $options);
}

function byl_v3_archive_current_orderby(): string {
    $value = isset($_GET['orderby']) ? sanitize_key(wp_unslash((string) $_GET['orderby'])) : '';
    return $value !== '' ? $value : 'menu_order';
}

function byl_v3_apply_archive_business_rules($query): void {
    if (is_admin() || !$query->is_main_query() || !function_exists('is_product_category') || !is_product_category()) { return; }
    $term = get_queried_object();
    if (!$term instanceof WP_Term) { return; }

    $requested = isset($_GET['orderby']) ? sanitize_key(wp_unslash((string) $_GET['orderby'])) : '';
    if ($requested === '' || $requested === 'menu_order') {
        $custom_order = get_term_meta($term->term_id, 'andy_custom_order', true);
        if (is_array($custom_order) && $custom_order !== []) {
            $ids = array_values(array_unique(array_filter(array_map('absint', $custom_order))));
            if ($ids !== []) {
                $query->set('post__in', $ids);
                $query->set('orderby', 'post__in');
                $query->set('order', 'ASC');
            }
        } else {
            $query->set('orderby', 'menu_order title');
            $query->set('order', 'ASC');
        }
    }

    $limit = (int) get_term_meta($term->term_id, 'andy_display_limit', true);
    if ($limit > 0) { $query->set('posts_per_page', $limit); }
}
add_action('woocommerce_product_query', 'byl_v3_apply_archive_business_rules', 20);

function byl_v3_archive_breadcrumbs(): array {
    $items = [['label' => 'Home', 'url' => home_url('/')]];
    $shop_url = function_exists('wc_get_page_permalink') ? (string) wc_get_page_permalink('shop') : home_url('/shop/');
    if (function_exists('is_shop') && is_shop()) {
        $items[] = ['label' => 'Shop', 'url' => ''];
        return $items;
    }
    $items[] = ['label' => 'Shop', 'url' => $shop_url];
    $term = byl_v3_archive_term();
    if (!$term) { return $items; }
    if ($term->taxonomy === 'product_cat') {
        $ancestors = array_reverse(get_ancestors($term->term_id, 'product_cat'));
        foreach ($ancestors as $ancestor_id) {
            $ancestor = get_term((int) $ancestor_id, 'product_cat');
            if ($ancestor instanceof WP_Term) {
                $url = get_term_link($ancestor);
                $items[] = ['label' => $ancestor->name, 'url' => is_wp_error($url) ? '' : (string) $url];
            }
        }
    }
    $items[] = ['label' => $term->name, 'url' => ''];
    return $items;
}

function byl_v3_archive_result_label(): string {
    global $wp_query;
    if (!$wp_query instanceof WP_Query) { return ''; }
    $total = (int) $wp_query->found_posts;
    if ($total <= 0) { return 'No products'; }
    $per_page = (int) $wp_query->get('posts_per_page');
    $page = max(1, (int) get_query_var('paged'));
    if ($per_page <= 0) { return sprintf('%d products', $total); }
    $start = (($page - 1) * $per_page) + 1;
    $end = min($page * $per_page, $total);
    return $start === $end
        ? sprintf('Showing %d of %d', $start, $total)
        : sprintf('Showing %d–%d of %d', $start, $end, $total);
}

function byl_v3_archive_pagination(): string {
    global $wp_query;
    if (!$wp_query instanceof WP_Query || (int) $wp_query->max_num_pages <= 1) { return ''; }
    $current = max(1, (int) get_query_var('paged'));
    $big = 999999999;
    $links = paginate_links([
        'base' => str_replace((string) $big, '%#%', esc_url(get_pagenum_link($big))),
        'format' => '?paged=%#%',
        'current' => $current,
        'total' => (int) $wp_query->max_num_pages,
        'type' => 'list',
        'prev_text' => '← Previous',
        'next_text' => 'Next →',
    ]);
    return is_string($links) ? $links : '';
}
