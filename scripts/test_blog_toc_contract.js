'use strict';
// Source contract smoke only; browser-interaction checks are exercised on Dev
// content in an isolated real Chrome runner before a Dev-only deployment.
const fs = require('node:fs');
const assert = require('node:assert/strict');
const php = fs.readFileSync('theme/beyourlover-v3/single-post.php', 'utf8');
const css = fs.readFileSync('theme/beyourlover-v3/assets/css/post-single.css', 'utf8');
const js = fs.readFileSync('theme/beyourlover-v3/assets/js/post-toc.js', 'utf8');
const sidebar = fs.readFileSync('theme/beyourlover-v3/blog-sidebar.php', 'utf8');
const endmatter = fs.readFileSync('theme/beyourlover-v3/blog-endmatter.php', 'utf8');
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
check(js.includes('const bannerCleared = !hero || hero.getBoundingClientRect().bottom <= 0'), 'Floating INDEX waits until the rendered featured banner completely leaves viewport');
check(js.includes('scroll > 650 && bannerCleared'), 'Dynamic banner clearance is mandatory to show the desktop INDEX');
check(js.includes("heroImage.addEventListener('load', requestUpdate"), 'Delayed featured-image loading triggers visibility recalculation');
check(js.includes('readingProgress < 0.9'), 'Floating index retires at 90% of article body');
check(js.includes('contentRect.height') && js.includes('contentStart'), 'Reading progress measured against article body');
check(php.includes("get_stylesheet_directory().'/blog-sidebar.php'"), 'Post only includes dedicated sidebar');
check(sidebar.includes("Join Our Newsletter") && sidebar.includes("New Arrivals") && sidebar.includes("Recent Posts"), 'WWW three sidebar headings present');
check(sidebar.includes("wc_get_products") && sidebar.includes("get_posts"), 'New arrivals and recent posts from real data');
check(sidebar.includes("home_url('/newsletter/')"), 'Newsletter CTA links to existing page rather than fake form submission');
check(!sidebar.includes('<form') && !sidebar.includes('wp_mail('), 'Sidebar does not create unapproved contact/write pipeline');
check(css.includes('left: calc(50% + 360px)') && css.includes('width: 250px'), 'WWW desktop rail placed beside the 660px reading column');
check(css.includes('.byl-blog-sidebar { display: none; }'), 'Right rail absent on narrow layouts');
check(php.includes("get_stylesheet_directory().'/blog-endmatter.php'"), 'Post-only editorial endmatter included after content');
check(endmatter.includes('Liz B.') && endmatter.includes('Sexologist') && endmatter.includes('img.beyourlover.com/2025/03/20054038/Bella-profile-scaled.webp'), 'WWW verified public editorial identity and R2 avatar');
check(endmatter.includes('byl-blog-author__social') && endmatter.includes('www.facebook.com/beyourlovercom') && endmatter.includes('www.youtube.com/beyourlovercom'), 'Verified public social destinations');
check(endmatter.includes('get_previous_post') === false && php.includes('get_previous_post'), 'Existing adjacent Post API retained from outer template');
check(endmatter.includes("get_permalink($byl_previous)") && endmatter.includes("get_permalink($byl_next)"), 'Previous and next links come from real WordPress Posts');
check(endmatter.includes('Related Content') && endmatter.includes("get_posts") && endmatter.includes("'post_status' => 'publish'"), 'Published related Posts from live WordPress, not invented titles');
check(endmatter.includes('array_slice($byl_related_posts, 0, 5)'), 'Related Post list capped at 5');
check(css.includes('.byl-blog-author') && css.includes('.byl-blog-related') && css.includes('.byl-post-single__nav-arrow'), 'Post-only WWW footer styling present');
console.log('BYL_BLOG_TOC_OFFLINE_CONTRACT=PASS ' + n + ' assertions');
