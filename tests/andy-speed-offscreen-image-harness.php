<?php
declare(strict_types=1);
$s = file_get_contents(dirname(__DIR__).'/theme/beyourlover-v3/front-page.php');
function chk($valid,$message){if(!$valid){fwrite(STDERR,"FAIL ".$message."\n");exit(1);} echo "PASS ".$message."\n";}
foreach (['2025/03/20052232/influencers-instagram-4.webp','2025/03/20052543/influencers-instagram-6.webp','2026/09/14054606/creator-signed-edtions-scaled.png'] as $u) {
  $target='src="https://img.beyourlover.com/'.$u.'" alt="Original BeYourLover editorial illustration" loading="lazy"';
  chk(substr_count($s,$target)===1,$u.' non-visible artwork lazy');
}
$target='src="https://img.beyourlover.com/2026/06/16071043/influencers-instagram-25.png" alt="BeYourLover influencer Instagram post 1" width="300" height="300" loading="lazy" decoding="async"';
chk(substr_count($s,$target)===1,'below-fold Instagram photo lazy');
chk(str_contains($s,'<div class="v5-hero-image"><img loading="eager" fetchpriority="high" decoding="async"'),'LCP hero remains eager high');
echo "PASS offscreen optimization fixture\n";
