<?php
/**
 * Offline blog product-card shortcode behavior test.
 * No WordPress/Woo bootstrap, HTTP, DB, orders, email or sync.
 */
declare(strict_types=1);
define('ABSPATH', '/offline/');
$GLOBALS['byl_shortcode_posts_only'] = true;
$GLOBALS['byl_mock_product'] = null;
$GLOBALS['byl_filters'] = [];
$GLOBALS['byl_shortcodes'] = [];
function add_filter(string $name, $callback, int $priority = 10): void {
    $GLOBALS['byl_filters'][$name][$priority][] = $callback;
}
function add_shortcode(string $name, $callback): void { $GLOBALS['byl_shortcodes'][$name] = $callback; }
function is_singular(string $type): bool {
    return $type === 'post' && $GLOBALS['byl_shortcode_posts_only'];
}
function wc_get_product(int $id) {
    return $id === 9153 ? $GLOBALS['byl_mock_product'] : false;
}
function esc_html($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function esc_attr($s): string { return esc_html($s); }
function esc_url($s): string { return esc_html($s); }
function esc_html_e($s, $domain = ''): void { echo esc_html($s); }
function wp_kses_post($s): string { return (string)$s; }
function wp_strip_all_tags($s): string { return strip_tags((string)$s); }
function wp_trim_words($s, $limit, $more = '…'): string {
    $parts = preg_split('/\s+/u', trim($s));
    return count($parts) <= $limit ? $s : implode(' ', array_slice($parts, 0, $limit)).$more;
}
class BlogProductFixture {
    public string $status = 'publish';
    public bool $visible = true;
    public function get_status(): string { return $this->status; }
    public function is_visible(): bool { return $this->visible; }
    public function get_name(): string { return 'BDSM RED BONDAGE KIT'; }
    public function get_permalink(): string { return 'https://dev.beyourlover.com/red-bondage-kit/'; }
    public function get_image($size, $args): string {
        return '<img src="https://img.beyourlover.com/example.webp" alt="BDSM RED BONDAGE KIT">';
    }
    public function get_short_description(): string { return '<p>Explore and transform through passion.</p>'; }
    public function get_price_html(): string { return '<del>$209.00</del> <ins>$179.00</ins>'; }
    public function is_on_sale(): bool { return true; }
    public function get_regular_price(): string { return '209'; }
    public function get_sale_price(): string { return '179'; }
}
function test(bool $condition, string $label): void {
    if (!$condition) { throw new RuntimeException('FAIL '.$label); }
    echo 'PASS '.$label.PHP_EOL;
}
require dirname(__DIR__, 3).'/theme/beyourlover-v3/inc/blog-product-card.php';
$GLOBALS['byl_mock_product'] = new BlogProductFixture();

test(isset($GLOBALS['byl_shortcodes']['product_card']), 'real product_card shortcode registered');
test(isset($GLOBALS['byl_filters']['the_content'][10]), 'normalization registered at priority 10');
$input = '<p>[product_card id=”9153″]</p>';
$fixed = byl_v3_normalize_blog_product_card($input);
test($fixed === '<p>[product_card id="9153"]</p>', 'historical smart-quoted shortcode normalized exactly');
test(byl_v3_normalize_blog_product_card('[product_card id="9153"]') === '[product_card id="9153"]',
    'already-canonical authored shortcode unchanged');
test(byl_v3_normalize_blog_product_card('<p>Other [coupon id=”9153″] untouched</p>')
    === '<p>Other [coupon id=”9153″] untouched</p>', 'unrelated content unmodified');
$html = byl_v3_render_blog_product_card(['id' => '9153']);
test(str_contains($html, 'BDSM RED BONDAGE KIT'), 'real product name rendered');
test(str_contains($html, 'https://dev.beyourlover.com/red-bondage-kit/'), 'Dev permalink from real product');
test(str_contains($html, 'img.beyourlover.com/example.webp'), 'product thumbnail from Woo object');
test(str_contains($html, '$179.00') && str_contains($html, '14% OFF'), 'dynamic price + Woo-derived discount');
test(str_contains($html, 'View Product') && !str_contains($html, 'www.beyourlover.com'), 'no cross-site URL or fake checkout');
test(byl_v3_render_blog_product_card(['id' => '9153x']) === '', 'non-numeric injection fails closed');
test(byl_v3_render_blog_product_card(['id' => '9154']) === '', 'unknown product fails closed');
$GLOBALS['byl_mock_product']->visible = false;
test(byl_v3_render_blog_product_card(['id' => '9153']) === '', 'hidden product fails closed');
$GLOBALS['byl_mock_product']->visible = true;
$GLOBALS['byl_mock_product']->status = 'draft';
test(byl_v3_render_blog_product_card(['id' => '9153']) === '', 'unpublished product fails closed');
$GLOBALS['byl_mock_product']->status = 'publish';
$GLOBALS['byl_shortcode_posts_only'] = false;
test(byl_v3_render_blog_product_card(['id' => '9153']) === '', 'non-blog route fails closed');
echo 'BYL_BLOG_PRODUCT_CARD_OFFLINE=PASS 15 isolated assertions'.PHP_EOL;
