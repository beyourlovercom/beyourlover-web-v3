# BYL V3 Pages + Blog — Clean Integration Closure Gate (2026-10-10 UTC)

## Ownership and exact provenance

- Canonical GitHub repository: `beyourlovercom/beyourlover-web-v3`; merge target `main` only after explicit Owner exact-head approval.
- Branch cut at latest main `cc29d5efd16c6c38a90fd2079d4b0bab90d6a98a`, which includes **Terms PR #45** and **Free Shipping PR #52**.
- Integrated runtime code and historical offline tests copied **unchanged** from the final stacked Blog PR #50 head `b6f7936440275bd3fdbc14f4df2fc4f70308d0f6` (which contains parent PR #47).
- Exceptions: `.github/workflows/foundation-ci.yml` was rebuilt from latest `main` to retain new Free Shipping regression and re-add Page/Post and Blog checks. This document is newly authored for audit/closure.
- Historical Draft PR #47 and stacked Draft PR #50 are **superseded for future main merge by this clean integration proposal only**. Preserve them until this proposal is safely reviewed; do not merge older PRs independently.

## Included scope

1. Default Page routing in `page.php` for new/Default text versus rich Pages; imported historic custom Page slugs retain prior renderer. Terms-only PR #45 `template-simple-page.php`, `page-terms-and-conditions.php` and `page-simple.css` remain untouched.
2. `single-post.php`, `post-single.css`, `post-toc.js`: WWW-like article appearance; progressive Summary and desktop INDEX from real H2; floating menu waits for featured banner to be completely above viewport and retires at 90% of the article body; mobile hides floating rail.
3. Read-only right blog sidebar for newsletter CTA, real WooCommerce new arrivals and recent local published Posts; real editorial Liz B. footer card, actual previous/next WordPress navigation and five related published Posts.
4. Offline Page/Post smoke with import-legacy preservation, Blog TOC safety/real-data contract, exact code PHP syntax, plus existing Free Shipping, G2, P5, P7 and Checkout CI steps from current main.

## Owner Dev UAT evidence already completed

- Terms Owner UAT PASS and PR #45 already merged.
- Privacy Policies / BeYourLover Disclaimer Owner visual PASS on Dev.
- Blog INDEX banner avoidance final fix Owner visual PASS; live read-only browser tests for 390/1280/1440/1586px, 90% hide and non-overlap passed, recorded in PR #50.
- Blog Summary, three right-side modules, editorial footer, related Posts and mobile responsive layout received Dev technical UAT. Owner acceptance of the **entire blog / all WWW parity** is not assumed from the scoped sticky menu PASS.
- Existing Dev runtime is not changed by this integration branch: it was previously installed through independent staged/backup guarded canaries.

## Remaining out-of-scope/content-dependent gates

- WWW's adjacent article `dry-humping-vs-frotting` is absent from Dev (404). Do not invent or force a next-post card.
- Existing `/newsletter/` subscription form shortcode remains an independent functionality gap; the sidebar links to the page but does not claim to submit a working form.
- Additional in-body WWW-specific product callouts and any remaining pixel-fidelity requests require separate explicit review. This PR makes no claim that all published article bodies are identical between WWW and Dev.

## Merge/production policy

- This PR remains Draft and unmerged pending exact-head Foundation CI and Owner integration gate.
- No WWW Production deployment, no DB migrations/metadata/content writes, no G2 activation, no orders/payments, no Woo webhook/AfterShip/ERP sync, no READY device restart.
- Merge `main` requires later explicit Owner authorization referring to this proposal's **fresh exact head SHA and passing CI**, after confirming main did not drift.
- Independent WWW deployment remains a separate Release gate after Dev and Owner final signoff.
