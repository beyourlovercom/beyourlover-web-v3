# BYL Newsletter M3 — read-only Dev audit and isolated technical UAT

**Date:** 2026-10-10. **Branch:** `feature/newsletter-m3-theme-20261010`. **Draft PR:** #62. **Website authority:** `main` was `8223932b740012037f0e5f30a5d4b7322d35721a` at this review; refresh before any deployment or merge.

## Read-only Dev facts (no production action)

- Dev `https://dev.beyourlover.com/newsletter/` returned HTTP 200; WP REST read-only `/wp-json/wp/v2/pages/12537` reports page ID `12537`, slug `newsletter`, publish, **template empty string**. This means the default route-specific `page-newsletter.php` hierarchy is eligible, absent later changes.
- Historical body still contains `[wpforms ...]` placeholder. The dedicated theme template deliberately **never outputs `the_content()`** on this route, avoiding the misleading old form and duplicate submission.
- Current Dev `/privacy-polices/` and `/privacy-policy/` both returned 404. The **published real Page** is ID `7271`, slug `privacy-policies`; `https://dev.beyourlover.com/privacy-policies/` returned HTTP 200. Theme dynamically resolves this published WordPress Page, with the configured WordPress privacy-page fallback. No invented URL.
- The Blog sidebar CTA already points to `/newsletter/`; no Blog component changed.
- Andy Core #68/#70 were Draft and unmerged on audit. Do not infer installation or subscription viability from a global popup `data-yby-subscribe-contract` marker.

## Core integration and default-off behavior

- The theme calls the Core `[yby_subscribe]` **at most once** and only if the M1 controller class, public methods, shortcode registration, API gate, outbound gate **and** a privacy URL are all available. A disabled or missing dependency displays the plain-language unavailable message and **no form**. It never sets Core flags.
- The Core fixture's opt-in box is unchecked by default. The PHP template does not store subscribers, call `wp_mail`, define REST routes, or touch ERP/Woo/AfterShip.
- Exact live confirmation email and ERP synchronization remain separately approved cross-workstream work, **not** Website M3 UI PASS.

## Isolated technical verification

- `scripts/test_newsletter_m3_contract.js` executes synthetic rendered PHP fixtures with **34 PASS assertions**: Core absent, Core gate off, Core gate on, privacy unavailable, one Core form only, unchecked marketing consent, live privacy slug, no legacy WPForms, and unchanged sidebar/PHP page fallback.
- The existing Foundation CI workflow remains intact; a dedicated Newsletter M3 PHP syntax and Node/PHP isolated test step is added.
- On China Mac's **isolated** temp checkout (never Dev/Production), headless Chrome DevTools emulation rendered Core-off and Core-on mock states at **390, 768, 1440 CSS px**. All six cases had `document.documentElement.scrollWidth === innerWidth`; **no horizontal overflow**. Each mock produces a PNG.
- Screenshots copied for Owner review to `~/Downloads/BYL-Newsletter-M3-Isolated-UAT-20261010/` on China Mac (six PNGs, `cdp-off-*.png` and `cdp-on-*.png`). These are *isolated mock renders*, **not live Dev screenshots**, not proof of a physical M14 UAT, and not proof of functional Core email delivery. The `on` state uses stubbed Core markup.
- Template PHP lint and harness PHP lint PASS on isolated clone. Exact PR-head Foundation CI conclusion must be read from GitHub Actions after final commit.

## Blocking Owner gates

1. **Dev release authorization:** separately approve Dev-only theme deployment plus safe backup/checksum/rollback, exact head pin, and non-invasive readback. No Dev changes were made here.
2. **Owner visual:** verify real Dev 390px / M14 / 768px / 1440px, keyboard, Footer, Blog CTA, policies. Owner visual PASS is not implied by synthetic screenshots.
3. **Core:** separately approve M0/M1 main/Dev installation, authorized non-production schema, controlled API activation. Outbound real email requires **another** explicit grant.
4. **ERP:** Native signed subscription snapshot and M2 idempotency, canary confirmation/opt-out tests independently required before true E2E closure.
5. **Website main / WWW:** both require later independent exact-head Owner approval. G2 remains CLOSED.

**No WWW/Production deploy, DB writes/migration, real emails, real subscriber imports, Woo/AfterShip/ERP operations, G2 activation or READY-device restart.**
