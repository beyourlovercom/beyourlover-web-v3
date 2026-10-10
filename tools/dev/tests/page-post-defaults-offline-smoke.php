<?php
/**
 * Offline smoke against real BYL Page and single Post PHP templates.
 * No WordPress bootstrap, HTTP, order, gateway, DB, session or file write.
 */
declare(strict_types=1);
define('ABSPATH', '/byl-isolated-offline/');
final class WP_Post {
    public function __construct(public string $post_content = '') {}
}
final class WP_Term {
    public function __construct(public int $term_id, public string $name) {}
}
$GLOBALS['byl_fixture'] = ['page' => new WP_Post(''), 'blocks' => [], 'slug' => '', 'commerce' => false, 'cursor' => 0, 'thumbnail' => false];
function is_cart(): bool { return $GLOBALS['byl_fixture']['commerce']; }
function is_checkout(): bool { return false; }
function is_account_page(): bool { return false; }
function is_page($slug): bool { return $GLOBALS['byl_fixture']['slug'] === $slug; }
function get_queried_object(): WP_Post { return $GLOBALS['byl_fixture']['page']; }
function get_queried_object_id(): int { return 11735; }
function get_page_template_slug($id = 0): string { return (string)($GLOBALS['byl_fixture']['template'] ?? ''); }
function parse_blocks($raw): array { return $GLOBALS['byl_fixture']['blocks']; }
function get_stylesheet_directory(): string { return dirname(__DIR__, 3).'/theme/beyourlover-v3'; }
function get_stylesheet_directory_uri(): string { return 'https://offline.invalid/theme'; }
function add_action($hook, $callback, $priority = 10): void {
    // Tests inspect actual template output, not enqueues or network.
}
function get_header(): void { echo 'BYL_HEADER'; }
function get_footer(): void { echo 'BYL_FOOTER'; }
function have_posts(): bool { return $GLOBALS['byl_fixture']['cursor']++ === 0; }
function the_post(): void {}
function post_class($class = ''): void { echo 'class="'.htmlspecialchars((string) $class, ENT_QUOTES).'"'; }
function get_the_title($post = null): string { return 'Fixture Article Title'; }
function esc_html($x): string { return htmlspecialchars((string)$x, ENT_QUOTES); }
function esc_attr($x): string { return htmlspecialchars((string)$x, ENT_QUOTES); }
function esc_url($x): string { return htmlspecialchars((string)$x, ENT_QUOTES); }
function esc_html_e($text, $domain = ''): void { echo esc_html($text); }
function esc_attr_e($text, $domain = ''): void { echo esc_attr($text); }
function esc_attr__($text, $domain = ''): string { return (string)$text; }
function the_content(): void { echo '<p>OFFLINE_FIXTURE_CONTENT</p>'; }
function home_url($path = ''): string { return 'https://offline.invalid'.$path; }
function has_post_thumbnail(): bool { return $GLOBALS['byl_fixture']['thumbnail']; }
function the_post_thumbnail($size, $attr): void { echo '<img src="fixture.png" alt="fixture">'; }
function get_the_category(): array { return [new WP_Term(1,'NEWS')]; }
function get_category_link($id): string { return '/category/news/'; }
function get_previous_post() { return null; }
function get_next_post() { return null; }
function get_the_date($format = ''): string { return $format === 'c' ? '2026-10-09T00:00:00+00:00' : 'October 9, 2026'; }
function get_the_tag_list($before = '', $sep = ', ') { return false; }
function wp_link_pages($args = []): void {}
function wp_kses_post($value): string { return (string)$value; }
function byl_case(string $file, array $attrs): string {
    $GLOBALS['byl_fixture'] = array_merge([
        'page'=>new WP_Post('<p>Fixture</p>'),
        'blocks'=>[['blockName'=>'core/paragraph','attrs'=>[],'innerHTML'=>'<p>Fixture</p>','innerBlocks'=>[]]],
        'slug'=>'test', 'commerce'=>false, 'cursor'=>0, 'thumbnail'=>false, 'template'=>'',
    ], $attrs);
    ob_start();
    require get_stylesheet_directory().'/'.$file;
    $output = (string)ob_get_clean();
    if (substr_count($output, 'BYL_HEADER') !== 1 || substr_count($output, 'BYL_FOOTER') !== 1) {
        throw new RuntimeException('Missing or repeated shell for '.$file);
    }
    return $output;
}
function byl_test(bool $pass, string $case): void {
    if (!$pass) { throw new RuntimeException('FAIL '.$case); }
    echo 'PASS '.$case.PHP_EOL;
}

$text = byl_case('page.php', []);
byl_test(str_contains($text, 'data-byl-page-template="simple"')
    && str_contains($text, '<h1') && str_contains($text, 'OFFLINE_FIXTURE_CONTENT'),
    'default text Page selects reading template and preserves content');
$classic = byl_case('page.php', ['blocks'=>[['blockName'=>null,'innerHTML'=>'<p>Plain</p>','innerBlocks'=>[]]]]);
byl_test(str_contains($classic, 'data-byl-page-template="simple"'),
    'classic uncomplicated HTML Page uses reading layout');
$rich = byl_case('page.php', ['blocks'=>[['blockName'=>'core/columns','attrs'=>[],'innerHTML'=>'','innerBlocks'=>[]]]]);
byl_test(str_contains($rich, 'data-byl-page-template="default"')
    && !str_contains($rich, 'data-byl-page-template="simple"'),
    'media and rich Page remain wide');
$wide = byl_case('page.php', ['blocks'=>[['blockName'=>'core/group','attrs'=>['align'=>'full'],'innerHTML'=>'','innerBlocks'=>[]]]]);
byl_test(str_contains($wide, 'data-byl-page-template="default"'),
    'authored full-width group remains wide');
$legacy = byl_case('page.php', ['template'=>'template-canvas.php']);
byl_test(!str_contains($legacy, 'data-byl-page-template='),
    'legacy assigned canvas Page retains pre-existing index fallback');
$oldDefault = byl_case('page.php', ['template'=>'template-custom-default-page.php']);
byl_test(!str_contains($oldDefault, 'data-byl-page-template='),
    'old imported custom-default Page remains unchanged');
$explicitDefault = byl_case('page.php', ['template'=>'default']);
byl_test(str_contains($explicitDefault, 'data-byl-page-template="simple"'),
    'Page explicitly set to Default receives auto reading layout');
$blog = byl_case('page.php', ['slug'=>'blog']);
byl_test(!str_contains($blog, 'data-byl-page-template='),
    'existing Blog Page remains pre-existing index fallback');
$commerce = byl_case('page.php', ['commerce'=>true]);
byl_test(!str_contains($commerce, 'data-byl-page-template='),
    'commerce Page retains existing index fallback');
$post = byl_case('single-post.php', ['thumbnail'=>true]);
byl_test(str_contains($post, 'data-byl-post-template="default"')
    && str_contains($post, 'Fixture Article Title')
    && str_contains($post, '2026-10-09T00:00:00+00:00')
    && str_contains($post, 'byl-post-single__hero')
    && str_contains($post, 'OFFLINE_FIXTURE_CONTENT'),
    'single Post preserves title, date, feature image and body');
$postWithoutImage = byl_case('single-post.php', ['thumbnail'=>false]);
byl_test(!str_contains($postWithoutImage, 'byl-post-single__hero"'),
    'single Post without featured image omits hero');
echo 'BYL_DEFAULT_PAGES_POSTS_OFFLINE=PASS 11 isolated scenarios'.PHP_EOL;
