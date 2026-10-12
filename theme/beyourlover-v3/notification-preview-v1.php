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
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="color-scheme" content="light">
<title>BYL Notification UX V1 — Message Lab</title>
<link rel="stylesheet" href="./assets/css/tokens.css">
<link rel="stylesheet" href="./assets/css/notification.css">
<link rel="stylesheet" href="./assets/css/notification-surfaces.css">
<link rel="stylesheet" href="./assets/css/notification-preview.css">
<script defer src="./assets/js/notification.js"></script>
<script defer src="./assets/js/notification-surfaces.js"></script>
<script defer src="./assets/js/notification-preview.js"></script>
</head>
<body>
<header class="demo-header"><div class="demo-header__inner">
  <div class="demo-logo">BEYOURLOVER<span class="demo-logo__dot">.</span></div>
  
</div></header>
<main class="demo" id="main-content">
  <div class="demo-intro">
    <div class="demo-kicker"><span class="demo-kicker__dot"></span> COMPONENT LIBRARY · DEV-ONLY MOCK</div>
    <h1>Every message.<br><em>One beautiful language.</em></h1>
    <p class="demo-lede">Clear feedback, without the clutter. Explore how BeYourLover handles everyday actions, WooCommerce notices and states that need your attention.</p>
  </div>
  <nav class="demo-nav" aria-label="Message previews">
    <a href="#preview-toast">01 · Toast</a>
    <a href="#preview-notices">02 · Notices</a>
    <a href="#preview-form">03 · Form &amp; status</a>
    <a href="#preview-empty">04 · Empty state</a>
    <a href="#preview-dialog">05 · Dialog</a>
  </nav>

  <section class="demo-section" id="preview-toast" aria-labelledby="toast-heading">
    <div class="demo-section__title">
      <div><span class="demo-step">01 / TRANSIENT</span><h2 id="toast-heading">Toast notifications</h2><p>Short-lived feedback after a confirmed action. One message at a time.</p></div>
      <span class="demo-tag">2 seconds · automatic</span>
    </div>
    <div class="demo-panel demo-panel--toast">
      <div class="demo-toast-guide"><span class="demo-swatch"></span><div><strong>Quick. Quiet. Reassuring.</strong><p>Burgundy success, neutral information. Slides in from the right and back out to the right. No layout shift.</p></div></div>
      <div class="demo-actions demo-actions--wrap">
        <button type="button" data-demo-action="add">✓ &nbsp; Add confirmed</button>
        <button type="button" class="demo-outline" data-demo-action="remove">Remove confirmed</button>
        <button type="button" class="demo-outline" data-demo-action="toast-info">Information Toast</button>
        <button type="button" class="demo-outline" data-demo-action="burst">Rapid actions ×5</button>
      </div>
      <p class="demo-caption">These buttons simulate successful server responses. No cart mutation occurs.</p>
    </div>
  </section>

  <section class="demo-section" id="preview-notices" aria-labelledby="notices-heading">
    <div class="demo-section__title">
      <div><span class="demo-step">02 / PERSISTENT</span><h2 id="notices-heading">WooCommerce &amp; WordPress notices</h2><p>Four clear visual treatments. These examples stay visible until the underlying state changes.</p></div>
      <span class="demo-tag">No auto-dismiss</span>
    </div>
    <div class="demo-note"><strong>Source distinction</strong> WooCommerce classic provides success, error and notice (info). Warning is a BYL visual extension; WordPress admin notices remain out of scope.</div>
    <div class="demo-notice-list" id="demo-notice-list" aria-label="Notification variants">
      <div class="byl-notice byl-notice--success" role="status" data-demo-notice="success">
        <span class="byl-notice__icon" aria-hidden="true">✓</span>
        <div class="byl-notice__body"><strong>Success</strong><p>Your coupon has been applied successfully.</p></div>
        <span class="byl-notice__type">Woo success</span>
      </div>
      <div class="byl-notice byl-notice--error" role="alert" data-demo-notice="error">
        <span class="byl-notice__icon" aria-hidden="true">!</span>
        <div class="byl-notice__body"><strong>Something needs attention</strong><p>Sorry, this promo code has expired. Check the code and try again.</p></div>
        <button type="button" class="byl-notice__action" data-demo-action="notice-retry">Try again</button>
      </div>
      <div class="byl-notice byl-notice--warning" role="status" data-demo-notice="warning">
        <span class="byl-notice__icon" aria-hidden="true">!</span>
        <div class="byl-notice__body"><strong>A little heads-up</strong><p>This item may have limited availability. Please confirm stock before checkout.</p></div>
        <span class="byl-notice__type">BYL warning</span>
      </div>
      <div class="byl-notice byl-notice--info" role="status" data-demo-notice="info">
        <span class="byl-notice__icon" aria-hidden="true">i</span>
        <div class="byl-notice__body"><strong>Good to know</strong><p>Shipping options are calculated after you enter your delivery address.</p></div>
        <span class="byl-notice__type">Woo info</span>
      </div>
    </div>
    <div class="demo-actions demo-actions--compact">
      <button class="demo-outline" type="button" data-demo-action="notices-reset">Reset notices</button>
      <button class="demo-outline" type="button" data-demo-action="notices-long">Test long messages</button>
    </div>
  </section>

  <section class="demo-section" id="preview-form" aria-labelledby="form-heading">
    <div class="demo-section__title">
      <div><span class="demo-step">03 / ACTIONABLE</span><h2 id="form-heading">Form errors &amp; live status</h2><p>Errors never disappear while the customer is trying to fix them.</p></div>
      <span class="demo-tag">Persistent · contextual</span>
    </div>
    <div class="demo-grid">
      <article class="demo-panel" aria-labelledby="coupon-title">
        <span class="demo-label">INLINE FORM ERROR</span><h3 id="coupon-title">Apply a promo code</h3>
        <label for="demo-coupon">Promo code</label>
        <div class="demo-coupon"><input id="demo-coupon" type="text" placeholder="Enter promo code" autocomplete="off" aria-describedby="demo-error"><button class="demo-outline" type="button" data-demo-action="invalid">Apply</button></div>
        <p class="byl-field-error" id="demo-error" role="alert" hidden></p>
        <div class="demo-actions demo-actions--compact"><button type="button" class="demo-outline" data-demo-action="coupon">Simulate valid code</button><button type="button" class="demo-text-button" data-demo-action="form-clear">Clear error</button></div>
        <p class="demo-caption">Input remains editable. Incorrect codes produce persistent feedback and do not trigger a success Toast.</p>
      </article>
      <article class="demo-panel" aria-labelledby="status-title">
        <span class="demo-label">AUTHORITATIVE STATUS</span><h3 id="status-title">Shipping information</h3>
        <div class="byl-status-strip" role="status" id="demo-shipping-strip"><span class="byl-status-strip__dot" aria-hidden="true"></span><span id="demo-status">Shipping is calculated at checkout.</span></div>
        <div class="demo-actions demo-actions--compact"><button class="demo-outline" type="button" data-demo-action="shipping-available">Available</button><button class="demo-outline" type="button" data-demo-action="shipping-applied">Applied</button><button class="demo-outline" type="button" data-demo-action="shipping-reset">Reset</button></div>
        <p class="demo-caption">These states are mock-only. In production, shipping availability and selection always come from Woo.</p>
      </article>
    </div>
  </section>

  <section class="demo-section" id="preview-empty" aria-labelledby="empty-heading">
    <div class="demo-section__title">
      <div><span class="demo-step">04 / PAGE STATE</span><h2 id="empty-heading">Empty states, reimagined</h2><p>An empty cart is a page state, not an error notice. No grey strip or floating return button.</p></div>
      <span class="demo-tag">Cart / Wishlist / Search</span>
    </div>
    <div class="demo-empty-tabs" role="group" aria-label="Choose empty state">
      <button type="button" class="is-selected" aria-pressed="true" data-demo-empty="cart">Empty cart</button>
      <button type="button" aria-pressed="false" data-demo-empty="wishlist">Empty wishlist</button>
      <button type="button" aria-pressed="false" data-demo-empty="search">No results</button>
    </div>
    <div class="demo-panel demo-panel--empty">
      <div class="byl-empty-state" id="demo-empty-state" role="region" aria-label="Empty state preview">
        <div class="byl-empty-state__symbol" aria-hidden="true">
          <svg viewBox="0 0 48 48" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 18h24l-2 23H14l-2-23Z"/><path d="M18 19v-6a6 6 0 0 1 12 0v6"/></svg>
        </div>
        <h3 id="demo-empty-title">Your cart is empty</h3>
        <p id="demo-empty-description">Discover something you'll love. Your next favorite is waiting.</p>
        <button type="button" data-demo-action="empty-cta" id="demo-empty-cta">Explore products <span aria-hidden="true">→</span></button>
      </div>
      <div class="demo-empty-footnote" id="demo-empty-footnote"><span aria-hidden="true">◌</span> Recently viewed products appear only when actual browsing data exists. None are invented in this preview.</div>
    </div>
  </section>

  <section class="demo-section" id="preview-dialog" aria-labelledby="dialog-heading">
    <div class="demo-section__title">
      <div><span class="demo-step">05 / CONFIRMATION</span><h2 id="dialog-heading">Confirmation dialog</h2><p>For decisions that require an explicit choice—not a timer.</p></div>
      <span class="demo-tag">Keyboard / focus / Escape</span>
    </div>
    <div class="demo-panel demo-panel--dialog">
      <div><h3>Clear all items?</h3><p>Important actions should give customers time to decide.</p></div>
      <button type="button" data-demo-action="dialog-open">Preview confirmation <span aria-hidden="true">↗</span></button>
    </div>
  </section>
  <p class="demo-disclaimer"><strong>Design-only Canary.</strong> No WordPress bootstrap, WooCommerce requests, real cart changes, checkout, payment, orders, email or database writes. Admin notices are visual references only; the WordPress dashboard is unchanged.</p>
</main>
<dialog class="byl-confirm" id="demo-dialog" aria-labelledby="demo-dialog-title" aria-describedby="demo-dialog-desc">
  <div class="byl-confirm__inner">
    <div class="byl-confirm__symbol" aria-hidden="true">?</div>
    <h2 id="demo-dialog-title">Clear your shopping bag?</h2>
    <p id="demo-dialog-desc">This is only a preview. No real items will be removed.</p>
    <div class="byl-confirm__actions">
      <button type="button" class="byl-confirm__cancel" data-demo-action="dialog-close">Keep my items</button>
      <button type="button" class="byl-confirm__confirm" data-demo-action="dialog-confirm">Clear items (mock)</button>
    </div>
  </div>
</dialog>
</body>
</html>
