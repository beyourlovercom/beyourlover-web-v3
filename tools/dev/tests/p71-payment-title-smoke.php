<?php
declare(strict_types=1);
/** P7.1 payment-title regression: mocked WordPress, NO orders, email or HTTP. */
define('ABSPATH', '/isolated/');
function add_action($name, $callback, $priority=10, $accepted_args=1): void {}
function wp_strip_all_tags($value): string { return strip_tags((string) $value); }
require dirname(__DIR__,3).'/theme/beyourlover-v3/inc/thank-you.php';
$cases=[
    ['Visa credit card','Visa credit card','plain gateway label'],
    ['','Pending confirmation','empty gateway title'],
    ['<div class="wc-payment-gateway-method-logo-wrapper wc-payment-card-logo"><img alt="Card" src="https://dev.beyourlover.com/wp-content/plugins/woocommerce-payments/assets/images/cards/visa.svg"> •••• 4242</div>','Visa •••• 4242','WooPayments Visa HTML logo'],
    ['<div><img alt="Card" src="https://dev.beyourlover.com/assets/cards/mastercard.svg"> •••• 5454</div>','Mastercard •••• 5454','Mastercard card logo'],
    ['<div><img alt="Card" src="/cards/amex.svg"> •••• 1005</div>','American Express •••• 1005','Amex card logo'],
    ['<span>PayPal</span>','Card PayPal','unrecognized markup no leak'],
    ['<div><img alt="Card" src="/cards/visa.svg" onerror="alert(1)"> •••• 4242</div>','Visa •••• 4242','malicious image attributes ignored'],
    ['&lt;div&gt;&lt;img alt="Card" src="/cards/visa.svg"&gt; •••• 4242&lt;/div&gt;','Visa •••• 4242','encoded HTML normalized'],
];
foreach($cases as [$input,$want,$label]){
    $got=byl_v3_ty_payment_label($input);
    if($got!==$want || str_contains($got,'<') || str_contains($got,'src=')){
        fwrite(STDERR,"FAIL $label: ".json_encode($got)."\n");exit(1);
    }
    echo "PASS $label\n";
}
echo "P71_PAYMENT_TITLE_PLAINTEXT=PASS ".count($cases)." cases\n";
