# Checkout LELO Canonical V1 — L4 Dev-only Canary and Owner UAT Gate
Date 2026-10-15 | Repo beyourlovercom/beyourlover-web-v3 | Issue #89 | Draft PR #119

## Current truth — do not mark this as a completed deployment
- Owner PASS was for Option B three-step **visual design**, not the Dev Woo Checkout runtime.
- L3 #119 is isolated Theme OS checkout source only: the secure **/checkout/** page presents Shipping and Payment after Cart Drawer; a full cross-page Checkout Drawer overlay/modal has NOT been implemented.
- Source + isolated G5 and Woo DOM fixture checks PASS; GitHub hosted Foundation runner has NOT started (account billing); no valid hosted CI green run exists for current exact head.
- No Dev or WWW deployment, payment, order, customer registration, mail or database writes were performed.
- Country Notices, country Tax ID collection, automatic customer account + Mail OS, Express SLA guarantees remain **separate unimplemented contracts**.

## Authorization before deployment / write access
- Obtain a specific Owner **Dev-only** deploy authorization identifying GitHub PR #119 exact immutable head SHA, allowlisted files, backup+rollback and target dev.beyourlover.com.
- Before any Dev code deployment, read-only verify current Theme OS checkout files and configured Woo Classic Checkout version (host + theme); compare hashes and ensure Dev-only feature gate or owner-approved limited release.
- Full Woo/G5/FX/Taxes/Shipping/Account plugin configuration inspection needs *read-only authenticated access*; anonymous Woo Store Cart returned 200 empty, Checkout endpoint 403, WordPress settings endpoint 401.
- Do **not** start a real Woo order, charge, customer account, registration email, subscription, shipping label, ERP sync or database migration as part of visual UAT. A pre-existing isolated test cart or explicitly approved synthetic fixture can be used only after separate scope approval.
- Never deploy or merge by interpreting 'continue/pass' as authorization to alter payments, orders, Woo settings or WWW.

## Technical L4 preflight matrix
| Gate | Method | Status |
|---|---|---|
| Native Woo Classic secure /checkout/ form and payment button single instance | DOM inspection on authorised Dev fixture | PENDING |
| Guest email at top, no email helper; existing login/profile link; marketing not opted in | M390/M320 screenshots, HTML attributes | PENDING |
| Phone ahead of ZIP, full-width privacy reminder; optional street2/organization collapsed | M390/M320 screenshots, field order and hydration | PENDING |
| Native Woo rates under Shipping; hidden single-rate and multi-package selection | Dev read-only fixture / isolated approved test cart | Offline mock PASS; Dev PENDING |
| Native Woo updated_checkout payment fragment survives shipping/coupon/country change with exactly one #payment and #place_order | DOM inspector and gateway lifecycle | Offline mock PASS; Dev PENDING |
| Only eligible providers, payment hosted fields, discount/shipping/tax/currency Woo source of truth | Read-only gateway/FX inspector, no charge | PENDING |
| One mobile fixed CTA; no duplicate floating Cart or Payment buttons; scroll and focus | screenshots M320 / M390 / iPhone14 / PC1440 | PENDING |
| G5 lock and 3DS flow, checkout_error recovery, Enter/click double-submission | Existing isolated G5 7-case suite; Dev no-charge provider test only when separately approved | Offline 7/7 PASS; Dev PENDING |
| Mandatory legal assent in production without adding extra checkbox; no synthetic consent or marketing precheck | Woo terms/consent plugin read-only configuration and legal sign-off | PENDING; production differs from Dev |
| Country Notices/Tax ID/admin revisions; account+Mail OS lifecycle | Independent plugin integration branches and authorized reviews | NOT IMPLEMENTED |
| Full **Checkout Drawer overlay/modal** transition from product/cart with gateway and fallback | independent L3-Drawer slice + real gateway compatibility | NOT IMPLEMENTED |

## Owner visual UAT criteria (after permitted Dev rollout)
- Reference: Owner-approved Cart → Shipping → Payment visual; BYL independent burgundy branding, black text, PingFang on mobile.
- PC1440 screenshot: Payment summary/contact on left, eligible gateway selection on right; visible Woo totals not fake.
- M390 and M320 screenshot: one sticky CTA, keyboard-safe phone/ZIP, email at top, collapsible summary, one native payment area, no horizontal overflow.
- M14 iPhone Owner screenshot UAT required for high-impact checkout pages.
- If any checkout source lacks a Woo gateway, keep Payment CTA unavailable (show a recoverable explanation), do not enable fake Pay Now.
- If payment JS fails, state/session compatibility breaks, in-flight 3DS is impaired, or new vs stale Woo payment nodes appear, **STOP** Canary, restore prior allowlisted files and previous functional /checkout/ fallback.

## Safe next slices
1. L3-Drawer: plan one native Woo form and provider-approved session/3DS compatibility, feature flag OFF by default, /checkout/ direct fallback.
2. Country Notices/Tax ID admin: separate Andy Core schema and versioned country wording, country-specific customs validation by logistics. Norway VOEC is seller scheme, not a mandatory purchaser 7-digit personal ID.
3. Account and transactional notifications: Woo/Andy Core/Mail OS server-owned, deduped at safe verified milestone, no user creation on email keystroke.
4. Reconcile actual Woo legal Terms and third-party optional Review invite at Payment (approved design has no additional distracting checkboxes).

## Closure standard
GitHub main is the sole merged-code authority. Require exact-head independent CI evidence or specific approved exception, explicit Owner approval for merge/Dev/WWW; do not infer green from the local test suite. Keep #87 E2E and WWW T6 HOLD until Cart Owner Final + Checkout Owner Final + fresh G5 evidence.
