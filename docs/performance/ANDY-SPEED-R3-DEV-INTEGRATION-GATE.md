# Andy Speed R3 — Dev integration and source-drift gate

**Status:** Draft PR only, no merge or full-theme ZIP replacement authorized.
**Source of truth:** GitHub for tracked code; actual BYL Dev runtime wins for
unmerged active features. Never replace current Dev files without exact
SHA precondition, backup and rollback.

## Verified 2026-10-28

- Dev front-page.php and this PR front-page.php have the same SHA-256:
  948695691eb7d9e4d0f14c7afc589fe4947e55446f834e814871ca3bd8b20ace.
  This includes the actual working andy_currency_switcher footer shortcode
  and a static currency fallback. Mobile image changes are already applied to Dev.
- Dev functions.php SHA-256 is
  544fd6f06e7c81d3a8a563d0fd09e70644846596f16699a46c50fabe9d327592.
  It differs from this PR functions.php because Dev additionally includes
  four independently managed, Dev-only shipping modules:
  inc/dev-pillow-oversized-shipping.php,
  inc/dev-after-discount-free-shipping.php,
  inc/dev-restworld-shipping-v1.php, and
  inc/dev-global-special-shipping-v1.php.
- Do not commit those Dev-only loaders or their direct requires into the
  stable theme. They belong to a separately approved shipping feature branch.
  Do not deploy this PR as a full ZIP to Dev: it would remove their loaders.
  Never change their status during Speed work.
- Andy Speed v0.2.1 is active on Dev in Safe / CSS ON; Newsletter and Analytics
  optimization OFF. CSS bundling is fail-closed via source SHA checks;
  Emergency OFF restores original six stylesheet URLs. Bundle matches Dev
  file bytes and cannot be reused on WWW without rebuilding and testing.
- GTranslater 0.1.0 remains installed but deactivated. Production unchanged.

## Release gates

1. Recheck HEAD and CI. Take a backup of live Dev files immediately before
   writing and verify the SHA precondition.
2. Resolve Dev-only shipping loader with its feature owner: either keep the
   loader intact through surgical file deployment or migrate to separately
   tested Dev-only code. No automatic removal.
3. Confirm source parity again before a merge or deployment.
4. Real mobile/desktop browser regression: fonts, currency switcher,
   product add-to-cart, populated cart and checkout payment UI. No real order,
   charge, notification or email without explicit owner permission.
5. Repeat Lighthouse and field CWV. Mobile Dev synthetic scores 87/92/93
   (median 92), LCP median 3.19s, above the 2.5s target. Root responds with
   no-store/no-cache; observed TTFB median about 0.61s. Do not enable
   full-page cache until account/cart/checkout, affiliate attribution,
   currency, tax, shipping and nonce variation are analyzed.
6. Keep PR Draft and WWW unchanged until explicit production approval.

## Optimization rejection

Throttled Chrome exploratory tests with PhotoSwipe CSS blocked improved
TTFB-adjusted LCP by ~23ms median, inadequate justification to change Woo
gallery styles. Do not disable AffiliateWP, jQuery Migrate, payment scripts
or conversion tracking to chase a Lighthouse score.
