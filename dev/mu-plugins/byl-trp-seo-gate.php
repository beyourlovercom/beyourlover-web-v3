<?php
/**
 * Plugin Name: BYL TranslatePress SEO Gate (Dev)
 * Description: Dev-only approval gating for multilingual article SEO. Fail closed.
 * Version: 0.1.0-dev
 */
if (!defined('ABSPATH')) { exit; }
if (untrailingslashit((string) get_option('siteurl')) !== 'https://dev.beyourlover.com') { return; }
if (!defined('BYL_TRP_SEO_TYPES')) { define('BYL_TRP_SEO_TYPES', array('post','page','product')); }
function byl_trp_gate_active() {
    return get_option('byl_trp_seo_gate_enabled','no') === 'yes';
}
function byl_trp_source_hash($post) {
    return hash('sha256',$post->post_title."\n".$post->post_content);
}
function byl_trp_post_review($post_id,$language) {
    $p = get_post($post_id);
    if (!$p || $p->post_status !== 'publish' || !in_array($p->post_type,BYL_TRP_SEO_TYPES,true)) return false;
    $reviews = get_post_meta($post_id,'_byl_trp_seo_review',true);
    if (!is_array($reviews) || !isset($reviews[$language]) || !is_array($reviews[$language])) return false;
    $q = $reviews[$language];
    if (($q['status'] ?? '') !== 'approved' || ($q['source_sha256'] ?? '') !== byl_trp_source_hash($p)) return false;
    if (empty($q['reviewer']) || empty($q['reviewed_at']) || empty($q['body_verified'])) return false;
    if (mb_strlen((string)($q['title'] ?? '')) < 12 || mb_strlen((string)($q['description'] ?? '')) < 70) return false;
    $slug = (string)($q['slug'] ?? '');
    $url = (string)($q['url'] ?? '');
    if (!$slug || !preg_match('/^[a-z0-9-]+$/D',$slug)) return false;
    if (!$url || !filter_var($url,FILTER_VALIDATE_URL)) return false;
    $host=parse_url($url,PHP_URL_HOST);
    $path=parse_url($url,PHP_URL_PATH);
    $prefix=array('es_ES'=>'/es/','ko_KR'=>'/ko/','ru_RU'=>'/ru/');
    if (!isset($prefix[$language]) || $host!=='dev.beyourlover.com' || strpos($path,$prefix[$language])!==0) return false;
    if (basename(rtrim($path,'/'))!==$slug) return false;
    return $q;
}
function byl_trp_url_lang() {
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/',PHP_URL_PATH);
    $part = explode('/',trim((string)$uri,'/'));
    $map = array('es'=>'es_ES','ko'=>'ko_KR','ru'=>'ru_RU');
    return isset($map[$part[0]]) ? $map[$part[0]] : 'en_US';
}
function byl_trp_localized_url($post_id,$language) {
    if ($language==='en_US') return get_permalink($post_id);
    // Exact reviewed URL, not a guessed locale-prefixed WordPress permalink.
    $review=byl_trp_post_review($post_id,$language);
    return $review ? $review['url'] : '';
}
function byl_trp_post_languages($post_id) {
    $urls=array('en_US'=>get_permalink($post_id));
    foreach(array('es_ES','ko_KR','ru_RU') as $code) {
        $url=byl_trp_localized_url($post_id,$code);
        if ($url) $urls[$code]=$url;
    }
    return $urls;
}
function byl_trp_is_gate_post() { return is_singular(BYL_TRP_SEO_TYPES); }
function byl_trp_gate_request() {
    if (!byl_trp_gate_active() || is_admin() || wp_doing_ajax() || !byl_trp_is_gate_post()) return;
    $lang=byl_trp_url_lang();
    if ($lang === 'en_US') return;
    if (is_user_logged_in() && current_user_can('edit_post',get_queried_object_id())
        && isset($_GET['byl-trp-preview']) && $_GET['byl-trp-preview'] === '1') return;
    $post_id=get_queried_object_id();
    if (byl_trp_localized_url($post_id,$lang)) return;
    status_header(404);
    nocache_headers();
    header('X-Robots-Tag: noindex, nofollow',true);
    global $wp_query;
    $wp_query->set_404();
    $theme_404=get_404_template();
    if ($theme_404) include $theme_404;
    else echo '<!DOCTYPE html><html><head><meta name="robots" content="noindex"></head><body><h1>404 Not Found</h1></body></html>';
    exit;
}
add_action('template_redirect','byl_trp_gate_request',0);
add_filter('trp-exclude-hreflang',function($excluded) {
    return byl_trp_gate_active() ? true : $excluded;
},999);
add_action('wp',function() {
    if (byl_trp_gate_active() && byl_trp_is_gate_post()) remove_action('wp_head','rel_canonical');
});
function byl_trp_emit_head() {
    if (!byl_trp_gate_active() || !byl_trp_is_gate_post()) return;
    $id=get_queried_object_id();
    $lang=byl_trp_url_lang();
    $url=byl_trp_localized_url($id,$lang);
    if (!$url && $lang === 'en_US') $url=get_permalink($id);
    if (!$url) return;
    echo '<link rel="canonical" href="'.esc_url($url).'"/>'."\n";
    if ($lang !== 'en_US') {
        $review=byl_trp_post_review($id,$lang);
        if ($review) echo '<meta name="description" content="'.esc_attr($review['description']).'"/>'."\n";
    }
    $codes=array('en_US'=>'en-US','es_ES'=>'es-ES','ko_KR'=>'ko-KR','ru_RU'=>'ru-RU');
    foreach(byl_trp_post_languages($id) as $code=>$link) {
        echo '<link rel="alternate" hreflang="'.esc_attr($codes[$code]).'" href="'.esc_url($link).'"/>'."\n";
    }
}
add_action('wp_head','byl_trp_emit_head',11);
add_filter('pre_get_document_title',function($title) {
    if (!byl_trp_gate_active() || !byl_trp_is_gate_post()) return $title;
    $lang=byl_trp_url_lang();
    if ($lang==='en_US') return $title;
    $q=byl_trp_post_review(get_queried_object_id(),$lang);
    return $q ? $q['title'] : $title;
},99);
add_filter('wp_robots',function($robots) {
    if (byl_trp_gate_active() && byl_trp_url_lang()!=='en_US') {
        if (!byl_trp_is_gate_post() || !byl_trp_localized_url(get_queried_object_id(),byl_trp_url_lang())) {
            $robots['noindex']=true; $robots['nofollow']=true;
        }
    }
    return $robots;
},99);
add_filter('trp_add_language_url_to_sitemap',function($allow,$lang,$url) {
    if (!byl_trp_gate_active() || $lang==='en_US') return $allow;
    $post_id=url_to_postid($url['loc'] ?? '');
    return $post_id && (bool)byl_trp_localized_url($post_id,$lang);
},99,3);
function byl_trp_sitemap() {
    if (!byl_trp_gate_active()) return;
    if (parse_url($_SERVER['REQUEST_URI'] ?? '',PHP_URL_PATH)!=='/byl-trp-seo-sitemap.xml') return;
    status_header(200);
    nocache_headers();
    header('Content-Type: application/xml; charset=UTF-8',true);
    header('X-Robots-Tag: noindex',true); // Dev must never be indexed.
    echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">'."\n";
    $ids=get_posts(array('post_type'=>BYL_TRP_SEO_TYPES,'post_status'=>'publish',
        'posts_per_page'=>1000,'fields'=>'ids','meta_key'=>'_byl_trp_seo_review','no_found_rows'=>true));
    foreach($ids as $id) {
        $links=byl_trp_post_languages($id);
        if (count($links)<2) continue;
        foreach($links as $lang=>$url) {
            if ($lang==='en_US') continue;
            echo '<url><loc>'.esc_xml($url).'</loc>';
            foreach($links as $l=>$link) {
                echo '<xhtml:link rel="alternate" hreflang="'.esc_xml(str_replace('_','-',$l)).'" href="'.esc_xml($link).'"/>';
            }
            echo '<lastmod>'.esc_xml(get_post_modified_time('c',true,$id)).'</lastmod></url>'."\n";
        }
    }
    echo '</urlset>';
    exit;
}
add_action('init','byl_trp_sitemap',1);
add_filter('option_trp_machine_translation_settings',function($settings) {
    if (!is_array($settings)) return $settings;
    $monthly=get_option('trp_machine_translated_characters',array());
    $key=date('m-Y'); // Same month key format as TranslatePress logger.
    // A fresh month has no counter entry yet; treat that as zero rather than deadlock.
    if (!is_array($monthly) || (isset($monthly[$key]) && !is_numeric($monthly[$key]))
        || (int)($monthly[$key] ?? 0)>=500000) {
        $settings['machine-translation']='no';
    }
    return $settings;
},999);