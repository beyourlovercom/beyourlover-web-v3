<?php
/**
 * BYL Notification UX V1 — Dev-only, isolated visual mock.
 * No WordPress bootstrap, WooCommerce, order, payment or session access.
 */
declare(strict_types=1);
$host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$flag = $_GET['byl_notification_preview'] ?? null;
if ($host !== 'dev.beyourlover.com' || $method !== 'GET' || $flag !== '1') {
    http_response_code(404);
    exit;
}
header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow, noarchive');
header('Cache-Control: private, no-store, max-age=0');
header('Referrer-Policy: no-referrer');
header("Content-Security-Policy: default-src 'none'; style-src 'self'; script-src 'self'; font-src 'self'; img-src 'none'; connect-src 'none'; form-action 'none'; base-uri 'none'; frame-ancestors 'none'");
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="color-scheme" content="light">
<title>BYL Notification UX V1 — Dev Mock</title>
<link rel="stylesheet" href="./assets/css/tokens.css">
<link rel="stylesheet" href="./assets/css/notification.css">
<link rel="stylesheet" href="./assets/css/notification-preview.css">
<script defer src="./assets/js/notification.js"></script>
<script defer src="./assets/js/notification-preview.js"></script>
</head>
<body>
<header class="demo-header"><div class="demo-header__inner">
  <div class="demo-logo">BEYOURLOVER<span class="demo-logo__dot">.</span></div>
</div></header>
<main class="demo">
  <div class="demo-kicker">INTERFACE COMPONENT · N5 CANARY</div>
  <h1>A better way to<br><em>say it.</em></h1>
  <p class="demo-lede">Small, elegant feedback for meaningful actions. A two-second moment that never interrupts your journey.</p>
  <div class="demo-grid">
    <section class="demo-card" aria-labelledby="demo-cart-title">
      <div class="demo-card__heading"><div><span class="demo-step">01 — ACTION PREVIEW</span><h2 id="demo-cart-title">Shopping bag</h2></div><span class="demo-small-badge">Mock</span></div>
      <div class="demo-product"><div class="demo-product__art" aria-hidden="true"><span class="demo-product__shape"></span></div><div class="demo-product__detail"><strong>Sample item</strong><p>Visual demonstration only</p><span class="demo-price">$—</span></div></div>
      <div class="demo-actions"><button type="button" data-demo-action="add">Add confirmed</button><button type="button" class="demo-outline" data-demo-action="remove">Remove confirmed</button></div>
      <div class="demo-rule"></div>
      <label for="demo-coupon">Promo code</label>
      <div class="demo-coupon"><input id="demo-coupon" type="text" placeholder="Enter a promo code" autocomplete="off"><button class="demo-outline" type="button" data-demo-action="coupon">Apply</button></div>
      <p class="demo-error" id="demo-error" role="alert" hidden></p>
      <p class="demo-status" id="demo-status">Shipping eligibility is controlled by WooCommerce, not this mock.</p>
    </section>
    <aside class="demo-card demo-card--side">
      <span class="demo-step">02 — BEHAVIOR LAB</span><h2>One message.<br>At a time.</h2>
      <p>Success appears only after an accepted result. Errors stay visible until corrected.</p>
      <div class="demo-lab"><button class="demo-outline" type="button" data-demo-action="invalid">Persistent coupon error</button><button class="demo-outline" type="button" data-demo-action="burst">Rapid actions × 5</button><button class="demo-outline" type="button" data-demo-action="clear">Reset messages</button></div>
      <div class="demo-spec"><span>ANIMATION</span><strong>Right in → left out</strong><span>HOLD</span><strong>2,000 ms</strong><span>COLOR</span><strong>BYL Burgundy</strong></div>
    </aside>
  </div>
  <p class="demo-disclaimer">Isolated user-interface mock. No live cart, product, promotion, order, payment, email, tracking or API calls.</p>
</main>
<div class="demo-footer" aria-hidden="true"><div class="demo-footer__inner"><span>Sample subtotal <strong>$—</strong></span><span class="demo-checkout">Checkout <span>→</span></span></div></div>
</body>
</html>
