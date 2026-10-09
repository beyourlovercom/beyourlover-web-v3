<?php
/** P7.1 order confirmation delivery estimator (offline, no WP or web access). */
declare(strict_types=1);
define('ABSPATH','/isolated-php-test/');
require dirname(__DIR__,3).'/theme/beyourlover-v3/inc/thank-you-estimate.php';

function ty_assert(bool $ok, string $case): void {
    if (!$ok) { fwrite(STDERR, 'FAIL '.$case.PHP_EOL); exit(1); }
    echo 'PASS '.$case.PHP_EOL;
}
$us = byl_v3_ty_estimate('2026-10-09','US');
ty_assert($us['range'] === ['2026-10-16','2026-10-21'], 'October 2026 Columbus Day excluded');
ty_assert(str_contains($us['holiday_note'], 'holidays'), 'Holiday-adjusted disclosure present');
$observed = byl_v3_ty_estimate('2026-07-02','US');
ty_assert($observed['range'] === ['2026-07-09','2026-07-14'], 'Independence Day Friday observance excluded');
$added = byl_v3_ty_estimate('2026-10-09','US',['2026-10-16'=>'US warehouse closure']);
ty_assert($added['range'] === ['2026-10-19','2026-10-22'], 'Additional closure shifts ETA correctly');
$other = byl_v3_ty_estimate('2026-10-09','DE');
ty_assert($other['range'] === null && $other['transit_min'] === 5 && $other['transit_max'] === 7, 'European unverified calendar no invented exact dates');
$europe = byl_v3_ty_estimate('2026-10-09','DE',['2026-10-13'=>'Verified closure']);
ty_assert($europe['range'] !== null && $europe['range'][0] < $europe['range'][1], 'Verified regional calendar enables bounded estimate');
$international = byl_v3_ty_estimate('2026-10-09','JP');
ty_assert($international['transit_min'] === 7 && $international['transit_max'] === 15 && $international['range'] === null, 'Unverified international holiday calendar fail-closed');
$invalid = byl_v3_ty_estimate('2026-02-31','US');
ty_assert($invalid['range'] === null, 'Impossible date fails closed');
$empty = byl_v3_ty_estimate('','US');
ty_assert($empty['range'] === null, 'Missing date fails closed');
$jan1 = byl_v3_ty_us_holidays(2028);
ty_assert(isset($jan1['2027-12-31']), 'Next-year Jan 1 observed on prior December 31');
ty_assert(byl_v3_ty_nth_weekday(2026,11,4,4)==='2026-11-26','Thanksgiving fourth Thursday');
ty_assert(byl_v3_ty_last_weekday(2026,5,1)==='2026-05-25','Memorial Day last Monday');
echo 'P71_THANK_YOU_ETA_OFFLINE=PASS 12 cases'.PHP_EOL;
