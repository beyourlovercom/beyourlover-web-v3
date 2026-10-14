<?php
/** Dev Blog legacy block compatibility (#97).
 * The published Blog page contains valid && inside an inline script, but
 * WordPress content filters render this occurrence as &#038;&#038;.
 * Do not decode HTML entities globally or disable sanitization.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }
add_filter('the_content', static function ($content) {
    if (!is_string($content)
        || !is_page('blog')
        || !is_main_query()
        || !in_the_loop()
        || (int) get_queried_object_id() !== (int) get_the_ID()) {
        return $content;
    }
    // Correct ONLY this known invalid JS expression. Other encoded entities
    // and content remain untouched; no database or network activity.
    $invalid = 'if (catSlug &#038;&#038; blogmoreEl) {';
    $valid = 'if (catSlug && blogmoreEl) {';
    return strpos($content, $invalid) === false ? $content : str_replace($invalid, $valid, $content);
}, 99);
