<?php
/**
 * BYL V3 — canonical default WordPress Page template.
 *
 * Ordinary text-only Pages automatically use the BYL Simple Page reading
 * layout; richer Gutenberg/media Pages use a wider restrained layout.
 * WordPress's explicit/slug-specific templates retain precedence.
 *
 * Cart, Checkout, My Account and the existing Blog landing are intentionally
 * delegated to the pre-existing index fallback, preserving their behavior.
 */
declare(strict_types=1);
defined('ABSPATH') || exit;

$byl_is_commerce_page =
    (function_exists('is_cart') && is_cart()) ||
    (function_exists('is_checkout') && is_checkout()) ||
    (function_exists('is_account_page') && is_account_page());

$byl_selected_template = function_exists('get_page_template_slug')
    ? (string) get_page_template_slug(get_queried_object_id())
    : '';

// Historical imported Pages often still reference unported legacy template
// slugs. WordPress falls back to page.php when that file does not exist:
// keep those pages on the previous index renderer, rather than silently
// imposing new default widths on approved special-purpose layouts.
// Only explicit "Default template" or new Pages participate in this V1.
$byl_has_legacy_template = $byl_selected_template !== ''
    && $byl_selected_template !== 'default';

if ($byl_is_commerce_page || is_page('blog') || $byl_has_legacy_template) {
    require get_stylesheet_directory().'/index.php';
    return;
}

$byl_page = get_queried_object();
$byl_raw_content = $byl_page instanceof WP_Post ? (string) $byl_page->post_content : '';
$byl_page_blocks = function_exists('parse_blocks') ? parse_blocks($byl_raw_content) : [];

// Allow only genuinely text-first core blocks. Any custom, dynamic or media
// block conservatively remains on the wide layout. Do not inspect/modify text.
$byl_text_block_types = [
    'core/paragraph', 'core/heading', 'core/list', 'core/list-item',
    'core/separator', 'core/quote', 'core/pullquote', 'core/table',
    'core/details', 'core/footnotes', 'core/preformatted', 'core/verse',
    'core/group',
];
$byl_blocks_are_text_only = static function (array $blocks) use (&$byl_blocks_are_text_only, $byl_text_block_types): bool {
    foreach ($blocks as $block) {
        if (!is_array($block)) { return false; }
        $type = $block['blockName'] ?? null;
        $attrs = is_array($block['attrs'] ?? null) ? $block['attrs'] : [];
        if (in_array(($attrs['align'] ?? ''), ['wide', 'full'], true)
            || (($attrs['layout']['type'] ?? '') === 'flex')) {
            return false; // Explicit wide/full layout and flex grids stay rich.
        }
        if ($type === null) {
            // Classic HTML remains eligible only if it is straightforward
            // text, not a shortcode, embedded widget, script or media region.
            $html = (string) ($block['innerHTML'] ?? '');
            if (preg_match('/<(?:div|section|figure|img|picture|video|audio|iframe|embed|script|style|form|input|canvas|object)\\b/i', $html)
                || preg_match('/\\[[a-z_][a-z0-9_-]*(?:\\s|\\]|\\/)/i', $html)) {
                return false;
            }
        } elseif (!in_array($type, $byl_text_block_types, true)) {
            return false;
        }
        if (!empty($block['innerBlocks']) && !$byl_blocks_are_text_only((array) $block['innerBlocks'])) {
            return false;
        }
    }
    return true;
};

$byl_is_reading_page = trim($byl_raw_content) !== ''
    && !empty($byl_page_blocks)
    && $byl_blocks_are_text_only($byl_page_blocks);

if ($byl_is_reading_page) {
    // This same template may also be explicitly chosen in the Page editor.
    require get_stylesheet_directory().'/template-simple-page.php';
    return;
}

add_action('wp_enqueue_scripts', static function (): void {
    $relative = '/assets/css/page-default.css';
    $path = get_stylesheet_directory().$relative;
    wp_enqueue_style(
        'byl-v3-page-default',
        get_stylesheet_directory_uri().$relative,
        ['byl-v3-motion'],
        is_file($path) ? (string) filemtime($path) : (string) wp_get_theme()->get('Version')
    );
}, 20);

get_header();
?>
<main id="main-content" class="byl-default-page" data-byl-page-template="default">
  <div class="byl-default-page__container">
    <?php while (have_posts()) : the_post(); ?>
      <article <?php post_class('byl-default-page__article'); ?>>
        <div class="byl-default-page__content">
          <?php the_content(); ?>
        </div>
      </article>
    <?php endwhile; ?>
  </div>
</main>
<?php get_footer();
