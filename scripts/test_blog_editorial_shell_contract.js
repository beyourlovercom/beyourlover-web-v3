'use strict';
const fs=require('fs'),path=require('path'),assert=require('assert');
const root=path.resolve(__dirname,'..'),theme=path.join(root,'theme/beyourlover-v3');
const read=f=>fs.readFileSync(path.join(theme,f),'utf8');
const single=read('single-post.php'),category=read('category.php'),bridge=read('inc/blog-editorial-shell.php'),
      css=read('assets/css/blog-archive-v1.css'),js=read('assets/js/blog-editorial-shell.js');
const registry=JSON.parse(read('theme-os.json')).modules.editorial.files;
for(const f of ['single-post.php','category.php','inc/blog-editorial-shell.php','assets/css/blog-archive-v1.css','assets/js/blog-editorial-shell.js']){
 assert(registry.includes(f),'editorial registry '+f);
 assert(fs.existsSync(path.join(theme,f)),'editorial source '+f);
}
for(const f of [single,category]){
 assert(f.includes("require_once get_stylesheet_directory().'/inc/blog-editorial-shell.php';"),'single/category require only editorial shim');
 assert(f.includes('byl_blog_editorial_header();')&&f.includes('byl_blog_editorial_footer();'),'standalone header/footer calls');
 assert(!f.includes('get_header();')&&!f.includes('get_footer();'),'no unconditional Shop header/footer calls');
 assert(!['wp_insert_post','wp_update_post','wp_delete_post','update_post_meta','wp_mail(','wp_remote_post('].some(token=>f.includes(token)),'only published read content');
}
assert(bridge.includes("is_file($base . '/blog-parts/header.php')")&&bridge.includes("is_file($base . '/blog-parts/footer.php')"),'requires PR102 independent shell');
assert(bridge.includes('get_header();')&&bridge.includes('get_footer();'),'fail-closed backwards-compatible fallback without PR102');
assert(bridge.includes("is_single() && get_post_type() === 'post'")&&bridge.includes('is_category()'),'exclude product/Page/Cart/Checkout');
assert(category.includes('have_posts()')&&category.includes('the_post()')&&category.includes('the_posts_pagination('),'native WP main category query and pagination');
assert(category.includes('get_the_post_thumbnail')||category.includes('the_post_thumbnail('),'featured image category cards');
assert(category.includes('home_url(\'/blog/\')'),'existing Blog permalink');
assert(js.includes('method:\'GET\'')&&js.includes('type\',\'post\''),'public read-only REST search');
assert(!js.includes('POST')&&!js.includes('localStorage')&&!js.includes('document.cookie'),'search does not mutate business data');
assert(js.includes('link.origin!==window.location.origin'),'no offsite link injection');
assert(js.includes('createElement(\'a\')')&&js.includes('textContent=item.title'),'HTML-safe public titles');
assert(css.includes('.byl-blog-archive__grid')&&css.includes('repeat(2,minmax(0,1fr))'),'responsive category grid');
assert(css.includes('body.byl-blog-standalone'),'scoped to editorial shell');
console.log('BLOG_EDITORIAL_ARTICLE_CATEGORY_CONTRACT=PASS');
