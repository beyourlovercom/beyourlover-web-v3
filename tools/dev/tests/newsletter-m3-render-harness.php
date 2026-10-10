<?php
/** Render the real theme template with synthetic, read-only WP/Core stubs.
 * Usage: php newsletter-m3-render-harness.php off|on|policy_missing
 * No network, database, WordPress runtime, or wp_mail calls are made.
 */
declare(strict_types=1);
define('ABSPATH', __DIR__);
define('OBJECT', 'OBJECT');
$byl_test_mode = $argv[1] ?? 'off';
if (!in_array($byl_test_mode, ['off', 'on', 'policy_missing'], true)) {
    fwrite(STDERR, "Invalid isolated scenario\n");
    exit(2);
}
class WP_Post { public string $post_status = 'publish'; }
class YBY_Newsletter_REST_Controller {
    public static function enabled(): bool {
        global $byl_test_mode;
        return $byl_test_mode !== 'off';
    }
    public static function outbound_enabled(): bool {
        global $byl_test_mode;
        return $byl_test_mode !== 'off';
    }
}
function get_page_template_slug(int $id): string { return ''; }
function get_queried_object_id(): int { return 12537; }
function get_stylesheet_directory(): string {
    return dirname(__DIR__, 3) . '/theme/beyourlover-v3';
}
function get_stylesheet_directory_uri(): string { return 'https://example.test/theme'; }
function wp_enqueue_style(...$args): void {}
function wp_get_theme(): object {
    return new class { public function get(string $key): string { return '3.0.0'; } };
}
function shortcode_exists(string $name): bool { return $name === 'yby_subscribe'; }
function get_page_by_path(string $slug, $output, string $type): ?WP_Post {
    global $byl_test_mode;
    return $slug === 'privacy-policies' && $byl_test_mode !== 'policy_missing' ? new WP_Post() : null;
}
function get_privacy_policy_url(): string { return ''; }
function get_permalink(WP_Post $p): string { return 'https://example.test/privacy-policies/'; }
function get_header(): void { echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Newsletter isolated test</title><link rel="stylesheet" href="file://' . htmlspecialchars(get_stylesheet_directory() . '/assets/css/newsletter.css', ENT_QUOTES) . '"></head><body>'; }
function get_footer(): void { echo '</body></html>'; }
function esc_html_e(string $s, string $domain): void {
    echo htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function esc_url(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function home_url(string $s): string { return 'https://example.test' . $s; }
function do_shortcode(string $s): string {
    if ($s !== '[yby_subscribe]') { throw new RuntimeException('Unexpected shortcode'); }
    return '<form data-yby-subscribe-contract data-yby-newsletter-enabled="1"><input type="email" name="email" required placeholder="Email address"><label><input name="marketing_consent" type="checkbox" value="1" required> I agree to receive marketing emails</label><button type="submit">Subscribe</button><p role="status" aria-live="polite"></p></form>';
}
require get_stylesheet_directory() . '/page-newsletter.php';
