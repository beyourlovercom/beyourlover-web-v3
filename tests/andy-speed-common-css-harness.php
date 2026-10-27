<?php
declare(strict_types=1);
define('ABSPATH', __DIR__);
$GLOBALS['styles'] = [];
$GLOBALS['scripts'] = [];
$GLOBALS['speed_css_flag'] = false;
$GLOBALS['theme_root'] = dirname(__DIR__).'/theme/beyourlover-v3';
$GLOBALS['stylesheet_callbacks'] = [];
function add_action($h,$fn,$priority=10) { $GLOBALS['stylesheet_callbacks'][$h][]=$fn; }
function get_stylesheet_directory(){return $GLOBALS['theme_root'];}
function get_stylesheet_directory_uri(){return 'http://localhost/wp-content/themes/beyourlover-v3';}
function get_stylesheet_uri(){return get_stylesheet_directory_uri().'/style.css';}
function wp_get_theme(){return new class {function get($k){return '1.0';}};}
function apply_filters($name,$v){return $name === 'andy_speed_byl_v3_common_css_bundle' ? $GLOBALS['speed_css_flag'] : $v;}
function wp_enqueue_style($h,$url=false,$deps=[],$version=null){if($url===false && isset($GLOBALS['styles'][$h])){return;} $GLOBALS['styles'][$h] = ['url'=>$url,'deps'=>$deps];}
function wp_register_style($h,$url=false,$deps=[],$version=null){$GLOBALS['styles'][$h] = ['url'=>$url,'deps'=>$deps];}
function wp_enqueue_script($h,$url,$deps=[],$version=null,$footer=false){$GLOBALS['scripts'][$h] = $url;}
function wp_script_add_data($h,$key,$value){}
function is_front_page(){return false;}
function is_page($what=null){return false;}
require dirname(__DIR__).'/theme/beyourlover-v3/inc/theme-os-assets.php';
function assert_css($pass,$description){if(!$pass){fwrite(STDERR,"FAIL: ".$description."\n");exit(1);} echo "PASS ".$description."\n";}
$callback = $GLOBALS['stylesheet_callbacks']['wp_enqueue_scripts'][0];
$run = static function($flag) use($callback) { $GLOBALS['styles']=[]; $GLOBALS['speed_css_flag']=$flag; $callback(); return $GLOBALS['styles']; };
$off = $run(false);
assert_css(count($off)===6,'Default has six legacy styles');
assert_css(str_contains($off['byl-v3-tokens']['url'],'tokens.css'),'Default legacy token URL preserved');
$on = $run(true);
assert_css(count($on)===6,'All six CSS dependency handles remain');
assert_css(count(array_filter($on,static fn($s)=>$s['url']!==false))===1,'Bundled mode issues exactly one CSS request');
assert_css(str_contains($on['byl-v3-foundation']['url'],'common.bundle.css'),'Bundle source used');
assert_css($on['byl-v3-motion']['deps']===['byl-v3-shell'],'Dependency order preserved');
$root = $GLOBALS['theme_root'];
$tmp = sys_get_temp_dir().'/aspeed-css-'.bin2hex(random_bytes(6));
foreach(['style.css','assets/css/tokens.css','assets/css/components.css','assets/css/component-library.css','assets/css/shell.css','assets/css/motion.css','assets/css/common.bundle.css','assets/css/common.bundle.manifest.json'] as $f){
 if(!is_dir(dirname($tmp.'/'.$f))){mkdir(dirname($tmp.'/'.$f),0755,true);}
 copy($root.'/'.$f,$tmp.'/'.$f);
}
file_put_contents($tmp.'/assets/css/tokens.css',"/* changed */\n",FILE_APPEND);
$GLOBALS['theme_root']=$tmp;
$fallback=$run(true);
assert_css(count(array_filter($fallback,static fn($s)=>$s['url']!==false))===6,'Mismatch reverts to six original files');
$GLOBALS['theme_root']=$root;
assert_css($run(false)===$off,'Emergency OFF recreates original contract');
foreach(['style.css','assets/css/tokens.css','assets/css/components.css','assets/css/component-library.css','assets/css/shell.css','assets/css/motion.css','assets/css/common.bundle.css','assets/css/common.bundle.manifest.json'] as $f){unlink($tmp.'/'.$f);}
rmdir($tmp.'/assets/css');rmdir($tmp.'/assets');rmdir($tmp);
echo "PASS checksum guarded BYL V3 CSS optimization and rollback\n";
