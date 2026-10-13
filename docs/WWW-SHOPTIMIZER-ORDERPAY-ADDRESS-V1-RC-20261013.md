# WWW Shoptimizer Order Pay Address V1 — Release Candidate

**Status: GitHub code-only; WWW deployment NOT authorized.**

## Diagnosis

- WWW runtime: Shoptimizer Child Theme 092001, parent Shoptimizer 2.9.5, WooCommerce 10.9.4.
- A real Pending order was inspected in an existing authorized browser session. The Woo payment form rendered on the right, but no Shipping/Billing address element existed in its DOM.
- WooCommerce Status → Templates had no checkout/form-pay.php override. The inspected Child Theme functions.php (~102,861 characters and 2,624 lines) and inc/woocomerce.php had no address renderer for the Order Pay hook.
- Woo 10.9.4 runs before_woocommerce_pay_form only after order-key, capability and applicable guest-email checks.
- Historical precise trigger remains **unproven**. No claim that a given plugin update removed it.
- The approved Dev reference is merged PR #81. This is a separate legacy WWW Shoptimizer patch, not BYL V3 runtime deployment.

## Source, intended installation and rollback

| GitHub source | WWW destination (only after Production approval) |
|---|---|
| release/byl-www-orderpay-v1.php | wp-content/themes/shoptimizer-child-theme/inc/byl-www-orderpay-v1.php |
| release/byl-www-orderpay-v1.css | wp-content/themes/shoptimizer-child-theme/assets/css/byl-www-orderpay-v1.css |

Insert exactly once into existing Shoptimizer Child Theme functions.php, in PHP context:

    require_once get_stylesheet_directory() . '/inc/byl-www-orderpay-v1.php';

No additional plugin, no core Woo template override, no child theme replacement, and no payment/DB changes.

## Security and tests

- Run only after Woo validates order key and owner capability via before_woocommerce_pay_form.
- Extra fail-closed checks verify is_checkout_pay_page, valid payable WC_Order, route order ID, matching key with hash_equals, and pay_for_order capability.
- Address, billing email and phone fields are rendered read-only with WordPress escaping. No personal data on unverified paths.
- Order Pay CSS is route-specific: desktop two columns, M14 mobile one column, original Woo payment form unchanged.
- Existing contact route /contact-us/ was verified reachable. No new support endpoint.
- Offline smoke test: php -l release/byl-www-orderpay-v1.php; php -l scripts/test_www_shoptimizer_orderpay_v1.php; php scripts/test_www_shoptimizer_orderpay_v1.php.
- GitHub Foundation CI MUST pass on exact PR head.

## Mandatory STOP before WWW deployment

1. Confirm separate explicit Owner authorization for WWW **code-only** deploy, latest GitHub approved exact SHA and CI.
2. Using approved existing WWW file-manager access (do not assume SSH), back up exact live functions.php bytes with SHA256/rollback pointer, confirm target new paths absent, verify active child slug and PHP/Woo versions.
3. Confirm WP Rocket does not expose the order-pay address page through shared caching; preserve guest-email validation.
4. Copy only exact two approved new files, PHP lint; add exactly one require_once line without rewriting unrelated functions.php content. Verify diff and checksums.
5. Read-only positive/negative tests with an existing Pending order: valid authorized address/phone/email; invalid key/unverified customer no PII; Woo totals/gateway widgets unchanged. Verify normal Checkout, Thank You, M14 and no horizontal clipping.
6. If abnormal, restore byte-identical original functions.php and remove the new files; verify rollback and read-only smoke.
7. Stop for WWW Owner PC/M14 Visual UAT. Never process a real payment, change status, send email, restart services, change payment gateways, or globally purge caches.

**Opening or merging this PR does not itself authorize Production deploy.**
