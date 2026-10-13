<?php
/** C2 isolated visual fixture. Does not bootstrap WordPress or WooCommerce. */
declare(strict_types=1);
$host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? ''));
$flag = $_GET['byl_checkout_c2_visual'] ?? null;
if ($host !== 'dev.beyourlover.com' || $method !== 'GET' || !is_string($flag) || $flag !== '1') {
    http_response_code(404);
    exit;
}
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: private, no-store, max-age=0');
header('X-Robots-Tag: noindex, nofollow, noarchive');
header('Content-Security-Policy: default-src \'none\'; style-src \'self\' \'unsafe-inline\'; script-src \'unsafe-inline\'; img-src \'none\'; connect-src \'none\'; form-action \'none\'; frame-ancestors \'none\'; base-uri \'none\'');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>BYL Checkout C2 · Compact Notice Visual Canary</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#f9f8f8;color:#171517;font-family:"PingFang SC",system-ui,Arial,sans-serif}
.preview-header{padding:13px 18px;background:#fff;border-bottom:1px solid #eee4e6;display:flex;align-items:center;justify-content:space-between;gap:12px}
.preview-header strong{font-size:14px;color:#9b3749}.preview-header span{font-size:11px;color:#6e6067}
.layout{max-width:1040px;margin:0 auto;padding:20px 16px 50px}
.heading{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:16px}
.heading h1{font-size:24px;line-height:1.15;margin:0}.heading p{margin:6px 0 0;font-size:11px;color:#716971}
.switch{display:flex;gap:3px;background:#eee9eb;border-radius:9px;padding:3px}
.switch button{appearance:none;min-height:40px;cursor:pointer;font-size:12px;padding:7px 13px;background:transparent;border:0;border-radius:7px;color:#604f56}
.switch button[aria-pressed="true"]{background:#9b3749;color:#fff}
.panels{display:grid;grid-template-columns:minmax(0,1.6fr) minmax(240px,1fr);gap:16px}
.card{min-width:0;background:#fff;padding:20px;border:1px solid #ece6e8;border-radius:12px}
.card h2{margin:0 0 13px;font-size:16px}.card p{font-size:13px;line-height:1.5;color:#655e63}
.demo-caption{margin:11px 0 6px;font-size:10px;color:#716971;font-weight:650;letter-spacing:.03em}
.woocommerce-notices-wrapper > :is(.woocommerce-error,.woocommerce-message,.woocommerce-info){
 position:relative;display:block;margin:0 0 14px;padding:16px 16px 16px 53px;
 font:500 16px/1.58 Arial,sans-serif;border:1px solid #e8d3d7;border-radius:12px;
 background:#fff1f3;color:#282229;list-style:none;overflow-wrap:anywhere}
.woocommerce-notices-wrapper>.woocommerce-message{background:#f1f8f4;border-color:#c8e4d3}
.woocommerce-notices-wrapper>.woocommerce-info{background:#f3f7fb;border-color:#d5e2f0}
.woocommerce-notices-wrapper>:is(.woocommerce-error,.woocommerce-message,.woocommerce-info)::before{
 content:'!';position:absolute;left:16px;top:16px;display:grid;place-items:center;border-radius:50%;
 width:23px;height:23px;background:#a52c45;color:white;font:bold 13px/1 Arial}
.woocommerce-notices-wrapper>.woocommerce-message::before{content:'✓';background:#216349}
.woocommerce-notices-wrapper>.woocommerce-info::before{content:'i';background:#55545a}
.woocommerce-notices-wrapper>.woocommerce-error li{padding:0;margin:0 0 5px}
.dummy-row{height:42px;border:1px solid #ddd8da;border-radius:7px;background:#fcfafb;margin:9px 0}
.summary-row{display:flex;justify-content:space-between;font-size:12px;padding:10px 0;border-bottom:1px solid #ede5e8}
.footnote{margin-top:15px;padding-top:10px;border-top:1px solid #eee;font-size:11px;color:#746970;line-height:1.6}
@media(max-width:700px){.panels{grid-template-columns:minmax(0,1fr)}.card{padding:13px}.heading h1{font-size:20px}.layout{padding:14px 12px 40px}}
</style>
<link rel="stylesheet" href="./assets/css/notification-compact-checkout.css">
</head>
<body class="byl-cpn-checkout-preview">
<header class="preview-header"><strong>BeYourLover</strong><span>Dev · Static Checkout C2 Canary</span></header>
<main id="main-content" class="byl-commerce-content layout">
<div class="heading"><div><h1>Checkout notices</h1><p>Real C2 CSS · synthetic messages · no orders or WooCommerce</p></div>
<div class="switch" role="group" aria-label="Notice size"><button type="button" data-view="compact" aria-pressed="true">Compact C2</button><button type="button" data-view="original" aria-pressed="false">Original</button></div></div>
<div class="woocommerce">
  <div class="woocommerce-notices-wrapper">
    <div class="demo-caption">Error · long message</div>
    <ul class="woocommerce-error" role="alert"><li>The shipping address needs review. Please check your city, postal code and delivery details before continuing. This is synthetic notice text for layout testing only.</li></ul>
    <div class="demo-caption">Information</div>
    <div class="woocommerce-info" role="status">Available delivery options depend on your destination and entered address.</div>
    <div class="demo-caption">Success · action retained</div>
    <div class="woocommerce-message" role="status">Your delivery details were saved successfully. This example is not a real checkout operation.</div>
  </div>
</div>
<div class="panels">
  <section class="card" aria-label="Simulated checkout fields"><h2>1 · Shipping Address</h2><p>Mocked form structure. No input, validation or payment functionality.</p><div class="dummy-row"></div><div class="dummy-row"></div><div class="dummy-row"></div></section>
  <aside class="card" aria-label="Simulated order summary"><h2>Order Summary</h2><p>No product or order data is loaded.</p><div class="summary-row"><span>Subtotal</span><span>—</span></div><div class="summary-row"><span>Shipping</span><span>—</span></div><div class="summary-row"><strong>Total</strong><strong>—</strong></div></aside>
</div>
<p class="footnote">Visual fixture only. This standalone Dev PHP does not call WordPress, WooCommerce, AJAX, Store API, create orders or trigger payments. The normal Dev checkout still redirects empty carts to /cart/; real functional UAT remains separate.</p>
</main>
<script>
(()=>{'use strict';const buttons=[...document.querySelectorAll('[data-view]')];
buttons.forEach(b=>b.addEventListener('click',()=>{
 const compact=b.dataset.view==='compact';
 document.body.classList.toggle('byl-cpn-checkout-preview',compact);
 buttons.forEach(x=>x.setAttribute('aria-pressed',String(x===b)));
}));
})();
</script>
</body>
</html>
