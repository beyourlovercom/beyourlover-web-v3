'use strict';
// Source contract smoke only; browser-interaction checks are exercised on Dev
// content in an isolated real Chrome runner before a Dev-only deployment.
const fs = require('node:fs');
const assert = require('node:assert/strict');
const php = fs.readFileSync('theme/beyourlover-v3/single-post.php', 'utf8');
const css = fs.readFileSync('theme/beyourlover-v3/assets/css/post-single.css', 'utf8');
const js = fs.readFileSync('theme/beyourlover-v3/assets/js/post-toc.js', 'utf8');
let n = 0;
function check(predicate, why) {
  assert.ok(predicate, why); n++; console.log('PASS ' + why);
}
check(php.includes('byl-v3-post-toc') && php.includes('/assets/js/post-toc.js'), 'Post-only script enqueue');
check(php.includes('id="byl-post-floating-toc"') && php.includes(' hidden>'), 'Server-rendered hidden floating nav');
check(php.includes('<?php the_content(); ?>'), 'Original Gutenberg the_content preserved');
check(js.includes("content.querySelectorAll('h2')"), 'TOC derived from existing H2');
check(js.includes('headings[0].before(inline)'), 'Inline Summary inserted before first H2');
check(js.includes('if (!existing)'), 'Existing authored TOC not duplicated');
check(js.includes("link.href = '#' + encodeURIComponent(id)"), 'Real href anchors generated');
check(js.includes("link.setAttribute('aria-current', 'location')"), 'Active section accessible state');
check(js.includes("window.addEventListener('scroll', requestUpdate, { passive: true })"), 'Passive scroll handling');
check(js.includes('desktop.matches') && css.includes('min-width: 1280px'), 'Desktop-only floating INDEX');
check(css.includes('max-width: 660px') && css.includes('width: 187px'), 'WWW measured reading/rail dimensions');
check(css.includes('.byl-post-toc--floating[hidden]') && css.includes('display: none !important'), 'Hidden-state CSS fail-closed');
check(!/\.wp-site-blocks|woocommerce_available_payment_gateways|place_order/i.test(js), 'No global/Page/payment mutation code');
console.log('BYL_BLOG_TOC_OFFLINE_CONTRACT=PASS ' + n + ' assertions');
