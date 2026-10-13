<?php
/** C3: isolated visual fixture, no WordPress or WooCommerce bootstrap. */
declare(strict_types=1);
$host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? ''));
$flag = $_GET['byl_orderpay_c3_visual'] ?? null;
if ($host !== 'dev.beyourlover.com' || $method !== 'GET'
    || !is_string($flag) || $flag !== '1') {
    http_response_code(404);
    exit;
}
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: private, no-store, max-age=0');
header('X-Robots-Tag: noindex, nofollow, noarchive');
header("Content-Security-Policy: default-src 'none'; style-src 'self' 'unsafe-inline'; script-src 'unsafe-inline'; img-src 'none'; connect-src 'none'; form-action 'none'; frame-ancestors 'none'; base-uri 'none'");
?>
<!doctype html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Order Pay C3 | Static Visual Preview</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#faf9f9;color:#21191d;font-family:"PingFang SC",system-ui,sans-serif}
.preview-top{background:#fff;border-bottom:1px solid #eee;padding:14px 18px;font-size:13px;font-weight:700;color:#9b3749}
#main-content{max-width:980px;margin:0 auto;padding:16px 14px 55px}
h1{font-size:23px;margin:0 0 6px}p{font-size:12px;line-height:1.6;color:#6e6269}
.demo-switch{display:flex;gap:4px;margin:16px 0 20px;background:#eee9ea;border-radius:8px;padding:3px;width:max-content}
.demo-switch button{border:0;background:transparent;cursor:pointer;border-radius:6px;padding:9px 14px;min-height:44px;font-size:12px}
.demo-switch button[aria-pressed="true"]{background:#9b3749;color:#fff}
#main-content.byl-commerce-content > .woocommerce > .woocommerce-error{
display:block;position:relative;list-style:none;margin:0 0 17px;padding:17px 16px 17px 52px;
border:1px solid #e4bfc8;border-radius:12px;background:#fff1f3;
color:#231b20;font:500 16px/1.55 Arial,sans-serif;overflow-wrap:anywhere}
#main-content.byl-commerce-content > .woocommerce > .woocommerce-error::before{
content:"!";position:absolute;left:16px;top:17px;border-radius:50%;background:#b52b45;color:white;
display:grid;place-items:center;width:22px;height:22px;font:bold 12px Arial}
.woocommerce-error li{padding:0;margin:0}
.checkout-layout{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.card{background:#fff;border:1px solid #ebe5e7;border-radius:10px;padding:18px;min-width:0}
.card h2{font-size:16px;margin:0 0 8px}
.placeholder{height:35px;background:#f7f4f5;border-radius:6px;margin:9px 0}
.footnote{font-size:11px;line-height:1.6;margin-top:17px}
@media(max-width:700px){h1{font-size:20px}.checkout-layout{grid-template-columns:1fr}.card{padding:14px}}
</style>
<link rel="stylesheet" href="./assets/css/notification-compact-orderpay.css">
</head>
<body class="woocommerce-order-pay byl-cpn-orderpay-preview">
<header class="preview-top">BeYourLover · Dev C3 Visual Lab</header>
<main id="main-content" class="byl-commerce-content">
<h1>Guest order notice</h1><p>Original WooCommerce warning text. No real customer or payment data.</p>
<div class="demo-switch" role="group" aria-label="Notice size">
<button type="button" data-density="compact" aria-pressed="true">Compact C3</button>
<button type="button" data-density="original" aria-pressed="false">Original</button>
</div>
<div class="woocommerce">
<ul class="woocommerce-error" role="alert"><li>You are paying for a guest order. Please continue with payment only if you recognize this order.</li></ul>
<div class="checkout-layout">
<section class="card"><h2>Before you pay</h2><p>Confirm your details — mocked layout only.</p>
<div class="placeholder"></div><div class="placeholder"></div></section>
<aside class="card"><h2>Order review</h2><p>No order items or payment methods are loaded.</p>
<div class="placeholder"></div><div class="placeholder"></div></aside>
</div>
</div>
<p class="footnote">Isolated CSS preview. No WordPress, WooCommerce, order key, guest email verification, Store API or payment form. Actual order authorization remains WooCommerce-controlled.</p>
</main>
<script>
(()=>{'use strict';const buttons=[...document.querySelectorAll('[data-density]')];
buttons.forEach(b=>b.addEventListener('click',()=>{
 const compact=b.dataset.density==='compact';
 document.body.classList.toggle('byl-cpn-orderpay-preview',compact);
 buttons.forEach(x=>x.setAttribute('aria-pressed',String(x===b)));
}));})();
</script>
</body></html>
