# BYL V3 — /free-shipping/ (Owner visual design PASS)

## Architecture / source
Theme-owned slug-specific template `theme/beyourlover-v3/page-free-shipping.php`
with stylesheet `assets/css/free-shipping.css` enqueued only on this Page.
Use existing global header/footer; do not add a third business plugin.
A preexisting independent SVG media attachment (Dev WP ID 9900) occupies
`/free-shipping/`. This media item is preserved untouched. Create the
real Dev WP Page with internal slug `free-shipping-guide`, title `Free Shipping`,
empty body. An exact-path request filter resolves the public URL
`/free-shipping/` to the Page; `page_link` supplies the preferred public
permalink and `redirect_canonical` avoids redirecting it back to an
internal slug. `page-free-shipping-guide.php` includes the approved template.
Do not rename/delete/mutate media metadata or alter other routes.
Never overwrite another Page on Production.

## Design
Replicates approved activity-style hero, gift-art SVG, progress panel,
how-it-works, destination policies, FAQ accordion. Mobile first 390 px.
Remove preview-only $59/$69/$89 controls and duplicate header/footer.
No outgoing API calls, payment processing, Woo mutations or new content plugins.

## Runtime authority
Commerce shipping policy's **persisted, effective** zone-specific threshold;
WooCommerce Cart current displayed pre-discount subtotal and shipping package.
No fake universal $69 or invented cart values.
- Below threshold: `Add $10.00 more for free shipping` (example $59, Zone 5 $69)
- Qualified with available zone free method: `🎉 Congrats! You've unlocked free shipping.`
- If paid shipping remains chosen, instruct customers to choose Free Shipping
- Empty/unknown/ineligible zone: state is explanatory, never falsely unlocked.
- Woo Cart/Checkout continue to own actual selected shipping fee.

## Deployment and gates
Source PR Foundation CI PASS, exact source hash, Dev-only backup of functions.php
and public theme files before replace; verify no parallel file modifications.
Publish only the Dev page and ensure no conflict with existing page slug.
Set no-cache on session-dependent page output. Run PHP CLI read-only fixture,
HTTP response checks, anonymous empty state, Dev safety Checkout 403.
No WWW, real orders, payment, DB migration or business-data writes beyond one
authorized Dev WordPress page create.

## Owner UAT
Desktop and 390px mobile page display, FAQ accordion, guest/empty cart,
known Iowa Zone 5 cart $59 / threshold $69, qualifying $69 and choice status.
Owner PASS required before GitHub merge or future WWW cutover.
