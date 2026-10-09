# BYL V3 — Default Pages and Posts Template System V1

**Canonical source:** GitHub `beyourlovercom/beyourlover-web-v3`.
**Audit reference:** `docs/agent/BYL-WWW-PAGE-POST-TEMPLATE-AUDIT-2026-10-09.md`.
**Deployment status:** Draft/source-only; Owner visual/UAT acceptance and Dev-only deployment separately gated.

## Fixed WordPress defaults (no mass template assignment)

1. **`page.php`** is the default entry for Pages not handled by an explicit/special WordPress template. It classifies text-first pages into a centered 760px reading layout and rich/media/unknown blocks into a capped 1120px responsive layout.
2. **`single-post.php`** is the default entry for regular `post` items. Its large optional featured image and narrow article text follow the useful parts of the WWW Post experience, with responsive spacing and accessible heading/meta.
3. **`template-simple-page.php`**, selectable as *BYL Simple Page* in the Page editor, remains a manual escape hatch for simple content needing a guaranteed narrow reading column.

No changes to `index.php`, global tokens, classic Checkout JS/CSS, Woo business hooks, or other sites.

### How the defaults are applied

WordPress automatically chooses `page.php` for default Pages and `single-post.php` for Posts based on its normal template hierarchy. **No database backfill or manual selection is required for new standard Pages/Posts.** Explicitly assigned templates and more specific theme templates may override these defaults.

Special routes:
- `page-cart.php`, `page-checkout.php`, `page-track-your-order.php`: retain route-specific priority and behavior.
- The legacy Dev Blog Page `/blog/` has a custom historical template assignment; `page.php` deliberately delegates its content to the current `index.php` until a separate Blog homepage upgrade.
- My Account and other Woo commerce Pages are deliberately delegated to `index.php`.
- Woo single products, post-type-specific templates and archives do **not** use `single-post.php`.

### Measured layout

| Content | 390px mobile | 1440px desktop |
|---|---|---|
| Simple Page | 350px reading width / 20px gutters | 760px centered reading width |
| Rich Page | content with at least 20px gutters, media constrained | max 1120px |
| Standard Post | 350px article text / 20px gutters | optional hero max 1120px, text max 760px |

All text/page margins use the existing BYL VI typography and colors. Scoped CSS only:
`assets/css/page-simple.css`, `assets/css/page-default.css`, `assets/css/post-single.css`.

## Quality gates

1. Source/CI: PHP lint all theme files, and `tools/dev/tests/page-post-defaults-offline-smoke.php` non-network fixture regression.
2. Isolated visual previews on actual Dev Terms and at least one Dev Post at 390/768/1440; no mutation of live site.
3. After explicit Owner design PASS, verify target Dev exact files and rollbacks, then install **only** template/CSS assets.
4. Dev real browser: inspect Terms #11735, Privacy #7271, a rich Page and a regular Post. Existing imported Terms template field may still point to the old Shoptimizer template filename; clear/explicitly map it only if the Dev runtime fails to fall back or after Owner approval, with prior metadata backup.
5. Owner final PASS before ready/merge/deployment decisions. GitHub main and WWW production remain unchanged until independently approved.

Do not apply BYL Simple Page to every image-rich Page. The defaults intentionally distinguish reading and rich use cases rather than blindly constraining every layout.
