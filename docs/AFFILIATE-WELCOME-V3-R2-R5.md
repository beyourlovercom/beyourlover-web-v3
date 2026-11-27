# Affiliate Welcome Email V3 — R2–R5 candidate

Design authority: Owner accepted BYL_Affiliate_Welcome_Email_V3.html, SHA256
94bd6f413bbacb7868adc7fd62bf08da1268203415d8fe360d6300142524abad.
The runtime HTML removes preview-only metadata and replaces examples with escaped tokens;
the original layout remains unchanged, including the 140px top-left logo and safe area.
The referral link is explicitly clickable. Passwords are never included.

## Scope and authority

WWW AffiliateWP source: includes/emails/actions.php, affwp_notify_on_approval,
affwp_set_affiliate_status action and affwp_notify_on_approval filter. Current
Application Accepted email is enabled; sender/reply-to pr@beyourlover.com.
Actual WWW preview showed empty registration coupon and /affiliate-login/ URL.
Core main 59ffcfd: signed ERP strict creation and separate verified coupon provisioning.
ERP KolCreateAutoProvisionService provisions Affiliate first and coupon second.
Core structured Email OS renderer has its own wrapper; do not duplicate its
administration or change its global rendering. Site-specific layout belongs
in existing BYL theme, not generic Core or an unauthorized plugin.

## Runtime and gates

Feature defaults OFF; explicit BYL_AFFILIATE_WELCOME_V3_ENABLED=true is required,
only on verified dev/www BYL hosts. Existing connector APIs/response schema stay
unchanged. Native approval filter is suppressed only during strict ERP creation
request, then removed. A user_register observed in the request, successful signed
connector result and durable ready binding are required to enroll. Replayed or
historical identities do not enroll. Coupon provisioning success or replay
must match exact authoritative coupon + affiliate binding, active user,
published unexpired percent coupon, actual rates, and current referral URL.
Fixed discounts or flat rates are not within the frozen percent-based design;
they stop dispatch rather than displaying incorrect information.
Native notification settings and opt-outs remain respected.
One atomic DB unique-option claim precedes password reset-key issuance and mail.
Mail transport uses existing wp_mail/WP Mail SMTP, with the configured sender.
No automated retry after an attempted dispatch, including false or ambiguous
transport. The existing options table stores non-autoloaded pending/dispatch
markers and no passwords or reset tokens; operational logs contain only
affiliate ID and machine-readable status. Mail acceptance is not delivery.
A failed/ambiguous dispatch needs manual investigation before any resend.
Ordinary WordPress customer and non-ERP AffiliateWP flows are not modified.

## Deployment and validation

No WWW changes in R2–R5. Dev safety currently denies all outbound mail except
a separately authorized newsletter canary; do not weaken it. Dev creator
dashboard also supports a WWW read-only bridge; never point a Dev test at
Production provider. Isolated offline harness tests generated HTML, missing
and mismatched data, opted-out accounts, replays, duplicate dispatch, and
failed/ambiguous mail. Real Dev database concurrency and external inbox
delivery are not established by this harness. Dev integration requires exact
SHA, backup, isolated connector/DB, controlled inbox or local mail sink,
and explicit safe mail-test path. No production R6 before these are verified.

For separately approved legacy WWW patch, install the two canonical files from
theme/beyourlover-v3/inc/ into the actual active legacy child theme inc/
directory and include the PHP file once from functions.php. Do not deploy the
new website theme to the legacy store. Back up exact affected files, verify
SHA, enable feature only after runtime/preflight, and retain a one-line flag
rollback. Do not remove dispatch markers on rollback or perform bulk historical
mail sends. Dev/WWW release target and mail-test gate are distinct.
