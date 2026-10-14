'use strict';
// Blog WWW Parity H3 scoped source contract, no DB, network, orders or mail.
const fs=require('fs'),assert=require('assert'),path=require('path');
const home=path.resolve(__dirname,'..'),theme=path.join(home,'theme/beyourlover-v3');
const read=p=>fs.readFileSync(path.join(theme,p),'utf8');
const template=read('page-blog.php'),css=read('assets/css/blog-parity-v1.css'),js=read('assets/js/blog-parity-v1.js');
const manifest=JSON.parse(read('theme-os.json'));
const editor=manifest.modules.editorial.files;
for(const file of ['page-blog.php','assets/css/blog-parity-v1.css','assets/js/blog-parity-v1.js']){
 assert(editor.includes(file),'editorial ownership '+file);
 assert(fs.existsSync(path.join(theme,file)),'file exists '+file);
}
assert(!template.includes('byl_blog_v1_preview') && !template.includes("require get_stylesheet_directory() . '/index.php';"),'Blog template active directly on Dev /blog/ for UAT');
assert(template.includes('get_header();')&&template.includes('get_footer();'),'reuse V3 shell');
assert(template.includes('new WP_Query([')&&template.includes("'post_status'=>'publish'")&&template.includes("'cat'=>(int) $term->term_id"),'published WordPress articles, scoped terms');
assert(template.includes("get_term_by('slug'")&&template.includes('get_term_link($term)'),'real category permalinks');
assert(template.includes('get_the_post_thumbnail_url')&&template.includes('wp_json_encode'),'real post media / JSON encoding');
assert(template.includes('JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT'),'JSON HTML-safe serialization');
assert(!template.includes('the_content()'),'legacy Page126 content must not be emitted in preview');
assert(!/wp_insert_post|wp_update_post|wp_mail\s*\(|wp_remote_post|woocommerce_checkout|WC_Order|update_option\s*\(/i.test(template),'no mutation or payment calls');
assert(!js.includes('fetch(')&&!js.includes('XMLHttpRequest')&&!js.includes('localStorage')&&!js.includes('document.cookie'),'JS local read-only content');
assert(js.includes('data-col-done'),'column chooser includes Owner-approved Done control');
assert(js.includes('data-page-size')&&js.includes('data-col-toggle')&&js.includes('data-col-move')&&js.includes('data-hero-select')&&js.includes('data-view'),'H2 interactive controls');
assert(js.includes('dialog.hidden = false')&&js.includes('event.key===\'Escape\''),'dialog interactive and escape');
assert(css.includes('body.byl-blog-parity-v1')&&css.includes('.byl-blogv1-category')&&css.includes('grid-template-columns:repeat(2'),'route-scoped WWW visual baseline');
assert(!template.includes('localdev.beyourlover.com'),'no stale dev host dependency');
assert(!template.includes('<form action=')&&!template.includes('type="email"'),'no deceptive newsletter submission');
assert(template.includes('home_url(\'/newsletter/\')'),'newsletter routes to existing page');
console.log('BLOG_WWW_PARITY_V1_H3_SOURCE_CONTRACT=PASS');
