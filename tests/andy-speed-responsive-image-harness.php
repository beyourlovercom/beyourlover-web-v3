<?php
declare(strict_types=1);
$s=file_get_contents(dirname(__DIR__).'/theme/beyourlover-v3/front-page.php');
function check($ok,$msg){if(!$ok){fwrite(STDERR,"FAIL: ".$msg."\n");exit(1);}echo "PASS ".$msg."\n";}
check(substr_count($s,'andy-speed-dev-hero-640.webp 640w')===1,'Responsive LCP hero 640w');
check(substr_count($s,'andy-speed-dev-hero-900.webp 900w')===1,'Responsive LCP hero 900w');
check(substr_count($s,'influencers-instagram-4.webp 1141w')===1,'Original hero fallback srcset');
check(substr_count($s,'andy-speed-dev-minaka-420.webp 420w')===1,'Responsive Minaka thumbnail');
check(str_contains($s,'fetchpriority="high"'),'Priority preserved');
echo "PASS responsive image candidate contract\n";
