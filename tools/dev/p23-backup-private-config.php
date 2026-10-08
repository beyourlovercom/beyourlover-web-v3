<?php
// Used by shell-only private Dev backup. NEVER print DB credentials.
if(home_url()!=='https://dev.beyourlover.com' || wp_get_environment_type()!=='staging')throw new RuntimeException('Not BYL Dev');
$dir='/home/u247343654/byl-v3-backups/p23-positive-lookup-20261008';
$host=DB_HOST;$port=null;
if(preg_match('/^(.+):([0-9]{2,5})$/',$host,$m)){$host=$m[1];$port=$m[2];}
$esc=static function($s){return '"'.str_replace(["\\",'"',"","
"],["\\\\",'\"',"",""],(string)$s).'"';};
$values=["[client]","user=".$esc(DB_USER),"password=".$esc(DB_PASSWORD),"host=".$esc($host)];
if($port){$values[]="port=".$esc($port);}
$p=$dir.'/.backup-client.cnf';
if(file_exists($p)||file_put_contents($p,implode("\n",$values)."\n",LOCK_EX)===false)throw new RuntimeException('Cannot create private mysql config');
chmod($p,0600);
echo "P23_DB_PRIVATE_CONFIG_READY\n";
