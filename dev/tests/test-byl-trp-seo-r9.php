<?php
/* Isolated WP-CLI fixture test; NO database writes or approvals. */
if (!function_exists('byl_trp_post_review')) { throw new RuntimeException('Gate not loaded'); }
function r9_assert($valid,$label) {
    echo ($valid ? 'PASS ' : 'FAIL ').$label."\n";
    if (!$valid) $GLOBALS['r9_fails']++;
}
$GLOBALS['r9_fails']=0;
$post_id=39873;
$post=get_post($post_id);
r9_assert($post && $post->post_status==='publish','pilot_published');
r9_assert(!byl_trp_post_review($post_id,'es_ES'),'unreviewed_blocks');
$fixture=array(
    'status'=>'approved',
    'source_sha256'=>byl_trp_source_hash($post),
    'body_verified'=>1,
    'reviewer'=>'synthetic-test-only',
    'reviewed_at'=>'2026-11-06',
    'title'=>'Posiciones para estimular con los dedos: guía detallada',
    'description'=>str_repeat('Ejemplo de descripción SEO revisada para una prueba sin publicar datos. ',2),
    'slug'=>'posiciones-de-digitacion',
    'url'=>'https://dev.beyourlover.com/es/blog/posiciones-de-digitacion/',
);
$override=$fixture;
$filter=function($value,$id,$key,$single) use (&$override,$post_id) {
    if ((int)$id===$post_id && $key==='_byl_trp_seo_review') {
        // WordPress metadata filter expects a row-list, even for single=true.
        return array(array('es_ES'=>$override));
    }
    return $value;
};
add_filter('get_post_metadata',$filter,10,4);
r9_assert((bool)byl_trp_post_review($post_id,'es_ES'),'fixture_approval_pass');
r9_assert(byl_trp_localized_url($post_id,'es_ES')===$fixture['url'],'exact_reviewed_url');
r9_assert(count(byl_trp_post_languages($post_id))===2,'hreflang_allowlist_only');
$override['source_sha256']='wrong';
r9_assert(!byl_trp_post_review($post_id,'es_ES'),'source_hash_invalidates');
$override=$fixture;$override['url']='https://www.beyourlover.com/es/blog/posiciones-de-digitacion/';
r9_assert(!byl_trp_post_review($post_id,'es_ES'),'www_url_rejected');
$override=$fixture;$override['body_verified']=0;
r9_assert(!byl_trp_post_review($post_id,'es_ES'),'qa_required');
remove_filter('get_post_metadata',$filter,10);
$now=get_option('trp_machine_translation_settings',array());
$monthly=get_option('trp_machine_translated_characters',array());
r9_assert((string)($now['machine_translation_limit']??'')==='15000','daily_limit_15000');
r9_assert(isset($monthly[date('m-Y')]),'monthly_counter_present');
$inject=function($val) { return array(date('m-Y')=>500000); };
add_filter('pre_option_trp_machine_translated_characters',$inject,10);
$blocked=get_option('trp_machine_translation_settings',array());
r9_assert(($blocked['machine-translation']??'')==='no','monthly_500k_stops_engine');
remove_filter('pre_option_trp_machine_translated_characters',$inject,10);
r9_assert(get_option('blog_public')==='0','dev_noindex_preserved');
echo 'R9_TEST_FAILS='.$GLOBALS['r9_fails']."\n";
if ($GLOBALS['r9_fails']>0) throw new RuntimeException('R9 fixture failed');