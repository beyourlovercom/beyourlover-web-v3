<?php
/** Scoped T5 #97 tests: pure PHP, no WP, DB or network. */
declare(strict_types=1);
define('ABSPATH', __DIR__ . '/../');
$registered = [];
$test = ['blog'=>true, 'main'=>true, 'loop'=>true, 'queried_id'=>126, 'post_id'=>126];
function add_filter(string $hook, callable $callback, int $priority = 10): void {
    global $registered;
    $registered[] = compact('hook','callback','priority');
}
function is_page(string $slug): bool { global $test; return $slug === 'blog' && $test['blog']; }
function is_main_query(): bool { global $test; return $test['main']; }
function in_the_loop(): bool { global $test; return $test['loop']; }
function get_queried_object_id(): int { global $test; return $test['queried_id']; }
function get_the_ID(): int { global $test; return $test['post_id']; }
require __DIR__.'/../theme/beyourlover-v3/inc/blog-legacy-script-ampersand.php';
if (count($registered)!==1 || $registered[0]['hook']!=='the_content' || $registered[0]['priority']!==99) {
    throw new RuntimeException('Expected exactly one late scoped content filter');
}
$cb=$registered[0]['callback'];
$bad='if (catSlug &#038;&#038; blogmoreEl) {';
$good='if (catSlug && blogmoreEl) {';
$input='<a title="Fish &amp; Chips">Link</a><script>'.$bad.'</script>';
$output=$cb($input);
if ($output!=='<a title="Fish &amp; Chips">Link</a><script>'.$good.'</script>') {
    throw new RuntimeException('Exact Blog JS entity repair failed or unrelated markup modified');
}
if ($cb($output)!==$output) throw new RuntimeException('Fix not idempotent');
$other='<script>if (x &#038;&#038; y) {}</script>';
if ($cb($other)!==$other) throw new RuntimeException('Unknown JS unexpectedly modified');
foreach (['blog','main','loop'] as $gate) {
    $test[$gate]=false;
    if ($cb($input)!==$input) throw new RuntimeException('Gate '.$gate.' failed open');
    $test[$gate]=true;
}
$test['queried_id']=999;
if ($cb($input)!==$input) throw new RuntimeException('Other Page modified');
$test['queried_id']=126;
$test['post_id']=125;
if ($cb($input)!==$input) throw new RuntimeException('Nested content modified');
$test['post_id']=126;
if ($cb('')!=='') throw new RuntimeException('Empty content modified');
echo "T5_BLOG_97_SCOPED_AMPERSAND_REGRESSION=PASS\n";
