<?php
/**
 * Read-only, fail-closed estimated delivery calendar for the Thank You page.
 *
 * Source of service windows: existing BeYourLover shipping policy
 * https://www.beyourlover.com/shipping-policies/
 * The calendar excludes weekends and configured/public holidays. It NEVER
 * writes order meta, creates shipments, or promises a guaranteed delivery date.
 */
declare(strict_types=1);
defined('ABSPATH') || exit;

function byl_v3_ty_observed_us_holiday(DateTimeImmutable $date): string {
    $weekday = (int) $date->format('N');
    if ($weekday === 6) { $date = $date->modify('-1 day'); }
    if ($weekday === 7) { $date = $date->modify('+1 day'); }
    return $date->format('Y-m-d');
}

function byl_v3_ty_nth_weekday(int $year, int $month, int $weekday, int $nth): string {
    $date = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month), new DateTimeZone('UTC'));
    $delta = ($weekday - (int) $date->format('N') + 7) % 7;
    return $date->modify('+'.($delta + ($nth - 1) * 7).' days')->format('Y-m-d');
}

function byl_v3_ty_last_weekday(int $year, int $month, int $weekday): string {
    $date = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month), new DateTimeZone('UTC'));
    $date = $date->modify('last day of this month');
    $delta = ((int) $date->format('N') - $weekday + 7) % 7;
    return $date->modify('-'.$delta.' days')->format('Y-m-d');
}

/** Federal public holidays, including observed dates; returns ISO date => name. */
function byl_v3_ty_us_holidays(int $year): array {
    $out = [];
    // Include adjacent calendar years: observed New Year's may fall on Dec 31.
    foreach ([$year - 1, $year, $year + 1] as $y) {
        $fixed = [
            'New Year’s Day' => "$y-01-01",
            'Juneteenth' => "$y-06-19",
            'Independence Day' => "$y-07-04",
            'Veterans Day' => "$y-11-11",
            'Christmas Day' => "$y-12-25",
        ];
        foreach ($fixed as $name => $date) {
            $out[byl_v3_ty_observed_us_holiday(new DateTimeImmutable($date, new DateTimeZone('UTC')))] = $name;
        }
        $out[byl_v3_ty_nth_weekday($y, 1, 1, 3)] = 'Martin Luther King Jr. Day';
        $out[byl_v3_ty_nth_weekday($y, 2, 1, 3)] = 'Washington’s Birthday';
        $out[byl_v3_ty_last_weekday($y, 5, 1)] = 'Memorial Day';
        $out[byl_v3_ty_nth_weekday($y, 9, 1, 1)] = 'Labor Day';
        $out[byl_v3_ty_nth_weekday($y, 10, 1, 2)] = 'Columbus Day';
        $out[byl_v3_ty_nth_weekday($y, 11, 4, 4)] = 'Thanksgiving Day';
    }
    return $out;
}

function byl_v3_ty_delivery_region(string $country): array {
    $country = strtoupper($country);
    if ($country === 'US') {
        return ['min' => 3, 'max' => 5, 'name' => 'United States warehouse'];
    }
    $europe = ['AL','AD','AT','BE','BA','BG','HR','CY','CZ','DK','EE','FI','FR',
        'DE','GR','HU','IS','IE','IT','LV','LI','LT','LU','MT','MD','MC','ME','NL',
        'MK','NO','PL','PT','RO','SM','RS','SK','SI','ES','SE','CH','TR','UA','GB','VA'];
    if (in_array($country, $europe, true)) {
        return ['min' => 5, 'max' => 7, 'name' => 'European warehouse'];
    }
    return ['min' => 7, 'max' => 15, 'name' => 'International factory dispatch'];
}

/** Compute next N working dates after the order, never counting order day. */
function byl_v3_ty_business_date(DateTimeImmutable $order_date, int $days, array $holidays): array {
    $cursor = $order_date;
    $skipped = [];
    for ($n = 0, $added = 0; $added < $days && $n < 90; $n++) {
        $cursor = $cursor->modify('+1 day');
        $iso = $cursor->format('Y-m-d');
        if ((int) $cursor->format('N') >= 6) { continue; }
        if (isset($holidays[$iso])) { $skipped[$iso] = $holidays[$iso]; continue; }
        $added++;
    }
    return ['date' => $cursor, 'skipped_holidays' => $skipped];
}

/**
 * @return array{range:?array,region:string,transit_min:int,transit_max:int,holiday_note:string}
 */
function byl_v3_ty_estimate(string $created_ymd, string $country, array $extra_holidays = []): array {
    $region = byl_v3_ty_delivery_region($country);
    $fallback = [
        'range' => null,
        'region' => $region['name'],
        'transit_min' => $region['min'],
        'transit_max' => $region['max'],
        'holiday_note' => 'Estimated dates will be confirmed after dispatch.',
    ];
    $base = DateTimeImmutable::createFromFormat('!Y-m-d', $created_ymd, new DateTimeZone('UTC'));
    if (!$base || $base->format('Y-m-d') !== $created_ymd) { return $fallback; }
    $country = strtoupper($country);
    $holidays = $country === 'US' ? byl_v3_ty_us_holidays((int) $base->format('Y')) : [];
    foreach ($extra_holidays as $key => $name) {
        if (is_string($key) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $key)
            && is_string($name) && $name !== '') {
            $holidays[$key] = $name;
        }
    }
    // Never present globally precise dates when we cannot account for a
    // destination's public holidays. Businesses can supply a verified holiday
    // calendar through the filter below without changing checkout.
    if ($country !== 'US' && count($holidays) === 0) { return $fallback; }
    // Shipping policy: dispatch in 24–48 hours, then regional business-day
    // transit estimates. Conservatively budget 1–2 processing business days.
    $earliest = byl_v3_ty_business_date($base, 1 + $region['min'], $holidays);
    $latest = byl_v3_ty_business_date($base, 2 + $region['max'], $holidays);
    $skipped = array_merge($earliest['skipped_holidays'], $latest['skipped_holidays']);
    return [
        'range' => [$earliest['date']->format('Y-m-d'), $latest['date']->format('Y-m-d')],
        'region' => $region['name'],
        'transit_min' => $region['min'],
        'transit_max' => $region['max'],
        'holiday_note' => $skipped
            ? 'Weekends and public holidays are included in this estimate.'
            : 'Calculated in business days; delivery may vary.',
    ];
}
