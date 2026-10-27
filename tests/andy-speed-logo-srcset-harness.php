<?php
declare(strict_types=1);
$s=file_get_contents(dirname(__DIR__).'/theme/beyourlover-v3/functions.php');
if(substr_count($s,'andy-speed-dev-logo-360.webp 360w')!==1){fwrite(STDERR,"FAIL: responsive Logo image source\n");exit(1);}
if(!str_contains($s,'(max-width: 640px) 160px, 190px')){fwrite(STDERR,"FAIL: responsive Logo sizes\n");exit(1);}
if(!str_contains($s,'src="'.chr(39).'.esc_url($url).'.chr(39).'"')){fwrite(STDERR,"FAIL: original logo retained\n");exit(1);}
echo "PASS logo keeps original source and serves responsive version\n";
