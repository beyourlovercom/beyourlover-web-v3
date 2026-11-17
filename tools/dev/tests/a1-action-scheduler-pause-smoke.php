<?php
/** Offline, fail-closed Dev Action Scheduler queue-pause contract. */
error_reporting(E_ALL);
define('ABSPATH', __DIR__);
$GLOBALS['a1_scheduler_fixture']=['site'=>'https://dev.beyourlover.com','env'=>'staging','callback'=>null];
function home_url() { return $GLOBALS['a1_scheduler_fixture']['site']; }
function wp_get_environment_type() { return $GLOBALS['a1_scheduler_fixture']['env']; }
function untrailingslashit($str) { return rtrim($str,'/'); }
function add_filter($hook,$callback,$priority,$argc) {
    if ($hook==='action_scheduler_queue_runner_concurrent_batches'
        && $priority===PHP_INT_MAX && $argc===1) {
        $GLOBALS['a1_scheduler_fixture']['callback']=$callback;
    }
}
require __DIR__ . '/../../../dev/mu-plugins/byl-a1-action-scheduler-pause.php';
function check($condition,$message) {
    if (!$condition) { fwrite(STDERR,"FAIL ".$message."\n"); exit(1); }
}
$cb=$GLOBALS['a1_scheduler_fixture']['callback'];
check(is_callable($cb),'queue runner concurrency filter loaded');
check($cb(1)===0,'Dev staging normal queue runner paused');
check($cb(5)===0,'Dev staging multiple queue workers paused');
$GLOBALS['a1_scheduler_fixture']['site']='https://www.beyourlover.com';
check($cb(1)===1,'WWW queue untouched');
$GLOBALS['a1_scheduler_fixture']['site']='https://dev.beyourlover.com';
$GLOBALS['a1_scheduler_fixture']['env']='production';
check($cb(3)===3,'production environment untouched');
$GLOBALS['a1_scheduler_fixture']['env']='staging';
$GLOBALS['a1_scheduler_fixture']['site']='https://other.example';
check($cb(2)===2,'unrelated staging sites untouched');
$GLOBALS['a1_scheduler_fixture']['site']='https://dev.beyourlover.com/';
check($cb(2)===0,'Dev slash normalizes');
echo "PASS 7 Dev queue-runner pause contracts: no action execution/cancellation\n";
