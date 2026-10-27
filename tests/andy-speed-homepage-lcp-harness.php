<?php
declare(strict_types=1);
$s = file_get_contents(dirname(__DIR__).'/theme/beyourlover-v3/front-page.php');
function check_lcp($pass,$msg) { if(!$pass){fwrite(STDERR,"FAIL: ".$msg."\n");exit(1);} echo "PASS ".$msg."\n"; }
check_lcp(str_contains($s, '<div class="v5-hero-image"><img loading="eager" fetchpriority="high" decoding="async"'), 'Hero explicitly has eager and high priority');
check_lcp(substr_count($s,'<div class="v5-hero-image"><img')===1, 'Exactly one primary hero element');
check_lcp(!str_contains($s,'alt="Original BeYourLover sensual fashion illustration" loading="eager"'), 'No duplicate loading attribute in hero');
foreach (['2026/09/14054606/creator-signed-edtions-scaled.png','2026/06/16070501/redman-img07.png','2025/03/20020642/Brain-Orgasm-banner.jpg'] as $name) {
  preg_match_all('~<img src="https://img\.beyourlover\.com/'.preg_quote($name,'~').'"[^>]*>~', $s, $matches);
  $qualified=array_values(array_filter($matches[0],static fn($tag)=>str_contains($tag,'width=') && str_contains($tag,'height=')));
  check_lcp(count($qualified)===1,'Single sized below-fold image '.$name);
  check_lcp(str_contains($qualified[0],'loading="lazy"') && !str_contains($qualified[0],'fetchpriority="high"'),'Below-fold image not high priority '.$name);
}
echo "PASS homepage LCP resource priority isolated contract\n";
