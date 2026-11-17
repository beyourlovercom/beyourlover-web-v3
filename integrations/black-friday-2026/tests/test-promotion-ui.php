<?php
// Test the pure calculations without loading WooCommerce or contacting a store.
define('ABSPATH',__DIR__);
require dirname(__DIR__).'/includes/class-byl-bf2026-promotion-ui.php';
$cases=[
    [100,70,30],[99,79.2,20],[100,0,0],[100,100,0],
    [50,60,0],[0,1,0],['x',20,0],['100','99',1],[100,99.99,0],
    [100,-4,0],[100,0.01,99]
];
foreach($cases as $idx=>$case){
    $actual=BYL_BF2026_Promotion_UI::discount_percent($case[0],$case[1]);
    if($actual!==$case[2])throw new Exception("Percent case $idx => $actual");
}
if(BYL_BF2026_Promotion_UI::epoch('2026-12-01 00:00')!==strtotime('2026-12-01T08:00:00+00:00'))throw new Exception('LA expiry mismatch');
if(BYL_BF2026_Promotion_UI::epoch('2026-02-30 12:00')!==0)throw new Exception('Invalid date accepted');
if(BYL_BF2026_Promotion_UI::settings()['enabled']!=='no')throw new Exception('Not fail-closed');
echo "PASS 11 discount cases; LA date; invalid date; default OFF\n";