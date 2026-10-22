# Shipping PR #157 ↔ Checkout Owner PR #142: no-deployment integration preflight

**Date:** 2026-10-22
**Scope:** isolated source comparison and mock overlay only. NO GitHub main merge, no Dev/WWW deployment, no live Woo settings, no real orders, no WP SSH. User's Dev free-shipping one-line visual UAT remains PASS.

## Exact examined heads
- Checkout Owner Draft PR #142: `816885048316d85adc5c620c71f2dcc784b5f277`; has its own Owner visual/UAT history and is currently **non-mergeable**. Do not overwrite or force-merge it.
- Shipping Source-only Draft PR #156: `05aaff5fef960791e44c6b2f3c594b0308c2517b`, WWW prod adapter not wired into theme/boot.
- Stacked minimal Checkout visual compatibility Draft PR #157: `1b4dc677de066ce4c91a212cd3d1b4ec8ca39dfd` at creation, based on #156.
- Approved one-line shipping copy from previous Dev Owner UAT: `Free shipping from $69 (special items excluded)` (whole sentence links to `/free-shipping/`).

## Actual non-overlapping/conflicting source hunks

| File | Checkout #142 | Shipping #156/#157 | Exact reconciler |
| --- | --- | --- | --- |
| `assets/js/checkout.js` | Old conditional multi-line promotion title, `promotionMessage(data)`, possible code hint, hides banner if extension inactive | Owner PASS one-line always linked sentence, preserving coupon apply/remove after the shared `const applied...` anchor | Replace **only** `renderPromotion()` prefix before `const applied` with the reviewed single-link block. Do not copy all of shipping JS over #142. |
| `inc/checkout.php` | Locked Free Shipping source card with no awareness of exceptional WWW packages | PR #157 adds an early `byl_www_ship_kind in [special,pillow]` skip; shipping line also has an existing **Dev-only** PILLOW marker check | Insert only the two guarded early-skip blocks immediately before the PHP `$available_rates` scan, preserving every other Owner Checkout hook/field contract. This suppresses a **visual ghost free-rate**, not genuine Woo methods. |
| `assets/css/checkout.css` | See-details inline text and generic child span, no SVG chevron rotation | Owner-reviewed checkout reference uses `display:inline-flex;align-items:center;gap:5px`, `.byl-mobile-chevron` sizing/transition/rotation | Preserve all Owner #142 mobile layout CSS, but reconcile **only** these see-details/chevron rules using the shipping baseline; otherwise Shipping Reference Fidelity V1 source test fails. |
| `theme-os.json` | Owner #142 adds native Checkout and newsletter modules | Shipping branch adds separate PILLOW and shipping UI modules | Manual source-of-truth manifest reconciliation required; do not use one manifest wholesale. |

## Reproducible isolated overlay evidence
On the already authorized US Mac mini, clone of shipping #157 was used **only in /tmp**, with Checkout #142's original `checkout.js`, `checkout.css`, `checkout.php` overlaid (no branch push or website touch):
- Before applying any reconciliation, the exact tests intentionally failed: **approved single-line headline missing** and **WWW special/pillow early skip missing**.
- Applied source-only patches to the overlay: (1) only `renderPromotion` approved-prefix JS, (2) only exceptional WWW/Dev PILLOW PHP early skips, (3) see-details/chevron CSS rules. Everything else stayed from Owner #142.
- Then PASSED:
  - Checkout Shipping Reference Fidelity V1 — **44** source assertions
  - Woo genuine free eligibility card — **31** source assertions
  - Standard Checkout Cart→Shipping→Payment — **53** source assertions
  - Owner one-line shipping copy — PASS
  - Cart one-line coupon UI — PASS
  - WWW production source-only rate candidate — **152** assertions
  - PHP and JavaScript syntax checks — PASS
- No real Woo Checkout transaction, data write or physical M14 visual UAT was performed. This compatibility proof does **not** imply mergeability of PR #142, nor does it validate the live WWW native Checkout/payment behavior.

## Remaining gates
1. Exact-head Owner Checkout branch integration and full Checkout CI, preserving #142 visual UAT; change only the above clearly identified hunks.
2. WX Restworld Native Woo `either` coupon contract is now supported at source-level by #156, but separate Andy Commerce BYL49 runtime interaction still needs safe integration verification.
3. WWW YayCurrency Pro currently active **3.2.1**; its vendor's later shipping fixes and real EUR/GBP/AUD/CAD conversion are still untested. Do not upgrade plugins without a separate Owner instruction; no WWW enabling while this remains unverified.
4. WWW release is **HOLD** until separately approved release head, native Woo shipping settings ledger, physical visual/rate UAT and rollback.

**Conclusion:** PHP/JS/CSS source-level conflict is understood and has a minimally invasive, locally tested three-file reconciliation. It is **not yet merged or deployed**.
