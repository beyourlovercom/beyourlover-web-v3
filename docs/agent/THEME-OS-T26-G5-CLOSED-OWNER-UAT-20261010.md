# BeYourLover Theme OS V1 — T2.6 Dev Owner UAT gate: G5 cleared, browser evidence

**Date:** October 10, 2026 (US Pacific)  
**Scope:** Dev-only read-only UAT gate; no GitHub merge or Theme v1.0.0 release.  
**G5 source:** [Issue #31 closure](https://github.com/beyourlovercom/beyourlover-web-v3/issues/31), [deferred Provider risk #72](https://github.com/beyourlovercom/beyourlover-web-v3/issues/72).

## G5 closure reconciliation

G5 development and Dev Owner UAT were **closed with documented exceptions**. Declined-card, 3DS success/cancel, same-order #39936 failed→paid retry, offline submit lock, Woo native webhook and AfterShip protections were accepted for G5's scoped stage. Official successful-provider event redelivery, out-of-order replays, and genuine parallel payment POSTs **were not tested**; Owner accepted the residual risk for the **G5 phase only**. This did not authorize Production. Source-of-truth GitHub `main` is `1325969335a6fd656e47b96d078b312242a5420e` (G5 PR #71 merged); Foundation CI push run `38108108414` SUCCESS.

## Fresh live Dev safety preflight

Using existing self-hosted Windows Desktop Commander → configured `byl-dev-hostinger` SSH, performed **read-only** checks:

- G2/P13 private Permit **ABSENT**. No reopening or revoking the permit.
- Theme OS deployed `functions.php` SHA256: `a3fb03e5887f462de14f0d1463c8bca62210fd28eb01dd2e153e9d931eb1a828`.
- Bootstrap SHA256: `7905b57d63a5f8a8c9c3f9c0bed79654f71a5ae42f870d8656831f4dc4a6ee0e`.
- Assets SHA256: `8fe0964954864b260b63a377e6e5633a43d9222087b730ba60d3cd91499e43af`.
- Theme OS manifest SHA256: `0ff9a5a456cf95b857f97619675103298ebb36303bdb33dcc3a7a58d88928b4b`.
- Original Dev entrypoint backup `/home/u247343654/byl-v3-staging/theme-os-t26-pr70-backup-20261010-6fafad2/functions.php` preserved at SHA256 `6a335c1bfc7f5de45a18f2dfbb698e3581a5cc369ca1098d8199dd691772edfb`.
- Active Theme: BeYourLover V3 0.1.0, Dev home HTTP 200. Dev G5 MU-plugin currently SHA256 `7b8e3bc570b259766083aed75d58d03c3ca676c5704ef69a6d07434e33826500` (changed from earlier T2 check under independent G5 activity, **not edited by this Theme OS turn**).
- PR #69 and integration PR #70 still Draft/unmerged. Deployed *code* derives from PR #70 earlier code HEAD `6fafad284a4f4b0cb0ad424ba8a3b3ffdec72ac1`; later commits are **documentation only**. Never claim later PR documentation HEAD was deployed.

## Actual live Dev browser verification

USA Mac mini independent headless Chrome (390 CSS px and 1440 CSS px) against `https://dev.beyourlover.com` with separate browser contexts; no real logged-in account or order mutation. Intercepted/aborted **all POST and third-party requests** and isolated one known WooPayments JS script **only in the diagnostic browser**, without disabling or changing it on Dev.

- Home, Shop/Catalog, public PDP, Free Shipping, Track Order, Privacy, Shipping Policy, Terms, empty Cart, empty Checkout redirect: **80/80** scoped Theme OS checks **PASS** across the two viewports, 0 JS page errors, 0 actual mutation POST attempts, 16 screenshots and no horizontal overflow.
- Existing PR #63 Cart Drawer: no global Drawer on normal URL or `?byl_cart_drawer_preview=0`; Dev-only `?byl_cart_drawer_preview=1` loads Drawer DOM, CSS and JS, gift remains OFF, header Cart button opens the Drawer, Esc closes it. Both 390px and 1440px checks pass, without cart mutations.
- Evidence files local on US Mac mini at `/tmp/byl-theme-os-t25.yWaKVw/dev-t26-owner-ready-20261010/results.json`; screenshot PNGs in same folder. These are **automated screenshots, not Owner visual approval**.
- **Important non-passing default-browser observation retained:** with unmodified WooPayments script, 52/56 check PASS, four failures because `woocommerce-payments/dist/woopay-direct-checkout.js?ver=11.2.0` throws `Invalid URL` and attempts two `POST /cart/null` per viewport on empty cart. All requests were blocked by test harness; no payments or orders were submitted. Theme-only browser isolation of that specific script yielded 56/56 PASS. This does **not** establish WooPayments PASS. Remains G5-owned follow-up.
- **Not tested** on live Dev: populated cart, real payment Checkout submission, actual 3DS, real Order Received with a secret order key, mail/ERP callbacks, or authentic concurrent mutation. These are excluded from the Theme Owner read-only UAT.

## Owner visual UAT — intervention now required

Check both a mobile viewport and desktop viewport, do not place a new order:

1. [Home](https://dev.beyourlover.com/) — shell/header/footer, content layout and navigation.
2. [Shop](https://dev.beyourlover.com/shop/) and any product detail — cards, variants, responsive content.
3. [Cart](https://dev.beyourlover.com/cart/) — empty-cart design or existing nonpayment cart state.
4. [Cart Drawer opt-in preview](https://dev.beyourlover.com/?byl_cart_drawer_preview=1) — click header Cart icon, Drawer opens, Esc/Close works; confirm mobile/fullscreen and desktop panel appearance. Gift remains OFF.
5. [Checkout](https://dev.beyourlover.com/checkout/) — when empty it redirects to Cart. **Do not submit payment**, initiate a new test order or change G2 Permit.
6. [Shipping](https://dev.beyourlover.com/free-shipping/), [Track Order](https://dev.beyourlover.com/track-your-order/), [Privacy](https://dev.beyourlover.com/privacy-policies/), [Terms](https://dev.beyourlover.com/terms-and-conditions/) — layout, footer links, readability.

**Exit condition:** Owner provides T2.6 visual PASS. Then T2.7 can proceed to closure review **without automatically merging PR #69/#70**; independent exact-head merge authorization required, and reconcile latest `main` including G5 changes before any merge.

**Stage disposition:** G5 phase CLOSED WITH EXCEPTIONS; T2.6 Dev-only implementation and automatic Theme-scope browser checks PASS; **Owner visual UAT PENDING**. No WWW/theme release/DB/payment edits.
