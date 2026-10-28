<?php
declare(strict_types=1);
$s=file_get_contents(dirname(__DIR__).'/theme/beyourlover-v3/front-page.php');
foreach (["shortcode_exists('andy_currency_switcher')",'[andy_currency_switcher style=','byl-www-static-currency'] as $marker) {
    if (!str_contains($s,$marker)) { fwrite(STDERR, "FAIL currency preservation: ".$marker."\n"); exit(1); }
}
echo "PASS currency footer shortcode plus fallback preserved from Dev source\n";
