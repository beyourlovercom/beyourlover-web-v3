<?php
/** Offline source-only Dev Jetpack Sync metadata and sender quarantine. */
error_reporting(E_ALL);
define('ABSPATH', __DIR__);
$GLOBALS['jetpack_test']=['env'=>'staging','origin'=>'https://dev.beyourlover.com','filters'=>[]];
function wp_get_environment_type(){return $GLOBALS['jetpack_test']['env'];}
function home_url(){return $GLOBALS['jetpack_test']['origin'];}
function untrailingslashit($value){return rtrim($value,'/');}
function add_filter($hook,$callback,$priority=10,$num=1){
    if ($priority!==PHP_INT_MAX || $num!==1)throw new RuntimeException('guard precedence mismatch');
    $GLOBALS['jetpack_test']['filters'][$hook]=$callback;
}
require __DIR__ . '/../../../dev/mu-plugins/byl-a1-jetpack-sync-pause.php';
function assertj($value,$message) {
    if (!$value){fwrite(STDERR, "FAIL ".$message."\n");exit(1);}
}
$checks=0;
$f=$GLOBALS['jetpack_test']['filters'];
assertj(count($f)===8,'seven product filters + sender');$checks++;
foreach ($f as $hook=>$cb) {
    $input=$hook==='jetpack_sync_sender_should_load'?true:['example'=>123];
    $got=$cb($input);
    assertj($got===false,'Dev stage must block '.$hook);
    $checks++;
}
$GLOBALS['jetpack_test']['origin']='https://www.beyourlover.com';
foreach ($f as $hook=>$cb) {
    $input=$hook==='jetpack_sync_sender_should_load'?true:['example'=>123];
    assertj($cb($input)===$input,'WWW unaffected '.$hook);
    $checks++;
}
$GLOBALS['jetpack_test']['origin']='https://dev.beyourlover.com';
$GLOBALS['jetpack_test']['env']='production';
foreach ($f as $hook=>$cb) {
    $input=$hook==='jetpack_sync_sender_should_load'?true:['example'=>123];
    assertj($cb($input)===$input,'Production env unaffected '.$hook);
    $checks++;
}
echo "PASS {$checks} Jetpack Sync Dev-only metadata/sender guard contracts\n";
