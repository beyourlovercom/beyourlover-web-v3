# BYL V3 — Default Pages / Posts V2 after Terms Closure

**Canonical source:** GitHub `beyourlovercom/beyourlover-web-v3`
**Base:** main merge commit `6ec004cb953856d289183b939f8e55e4d13619ac` (Terms-only PR #45, Owner UAT PASS)
**Legacy proposal:** PR #43 was built before PR #45 landed and became non-mergeable due to identical added template files. This successor branch only carries the default-Page/Post delta; **do not re-add or overwrite** the Terms files already merged.

## Standard defaults

| WordPress route/content | Template | Visual contract |
|---|---|---|
| New / explicit Default **text-first Page** | `page.php` → merged `template-simple-page.php` | No visible outer card; global Header/Footer shell; centered 760px text, 20px mobile gutters |
| New / explicit Default **rich Page** | `page.php` → `.byl-default-page` | Existing blocks left intact, max-width 1120px, 20px mobile gutters |
| Ordinary **Post** single | `single-post.php` | Wide optional featured image, centered 760px text, one H1, native date/category, tag and adjacent-article links |
| **Terms** | Already merged `page-terms-and-conditions.php` | Keep Owner PASS rendering; none of this PR's files modify it |
| Imported Page with non-default `_wp_page_template` value | Existing `index.php` fallback | **Fail-closed preservation of legacy layouts** until explicitly migrated in a separate ticket |
| Cart/Checkout/My Account | Specialized templates or existing `index.php` | No change, no payment or order writes |
| Blog landing /blog/ | Existing `index.php` fallback | Full-width custom content stays separate |
| Woo single products and custom post types | Their existing template hierarchy | Not affected by WordPress `single-post.php` |

WordPress honors specific `page-{slug}.php` and explicit installed page template files over `page.php`. Do not bulk update `_wp_page_template`.

## Why legacy Pages are excluded by default

Actual Dev public REST sample (50 published Pages checked read-only, 2026-10-09) shows old custom template assignments, including `template-canvas.php`, `template-fullwidth.php`, `elementor-fullwidth.php`, `Template-custom-default-page.php`, and `template-custom-default-page.php`. Those historical assets may not exist in the new V3 theme; WordPress otherwise falls back to `page.php`. Applying a generic content-width rule to all those pages would silently change complex commercial landing pages. This V2 first checks `get_page_template_slug()`; nonempty non-default historical assignments always retain the current `index.php` renderer.

**Privacy Policies** (post #7271) was rechecked against the actual live Dev REST API: its template value is **empty**, meaning it is legitimately on the WordPress **Default** template. Accordingly, the default `page.php` automatically applies the already-approved no-frame `BYL Simple Page` reading layout on Dev. No Page metadata, content, slug, URL or database fields were modified. It needs its own visual Owner UAT, but it is NOT an imported custom-template exception. Earlier preflight notes assuming it had a legacy assignment were incorrect.

## Offline tests and real-content preview

- `tools/dev/tests/page-post-defaults-offline-smoke.php`: 11 cases, including text/core/classic/rich/full-width, old canvas/imported template preservation, explicit Default template handling, Blog/Commerce exclusions, Post hero and no-hero.
- The default Page/Post CSS rules are scoped within `.byl-default-page` and `.byl-post-single`.
- Existing read-only WWW layout audit: `docs/agent/BYL-WWW-PAGE-POST-TEMPLATE-AUDIT-2026-10-09.md`.
- Prior PR #43 local preview of actual Dev Post #39899: mobile 350px, tablet ~707px, desktop article 760px with 1120px hero; 10,773 original characters; overflow zero. These are isolated local previews, **not** real deployment evidence or Owner visual PASS.
- After an explicit Dev-only staged rollout, verify one ordinary text Page, one rich Page, one 390/1440 real Post, Blog homepage, Terms, Cart/Checkout safety. Keep main / WWW production separate and keep G2 closed.

## Guardrails / rollback

Deploy nothing until successor PR exact head CI PASS and scoped Dev safety gate confirmed. Default `page.php`, `single-post.php` and CSS do affect multiple non-commerce routes; use file-before hash/backup, fail-if-exists staging and atomic installs on Dev only; rollback by removing only files added by this PR when they exactly match staged bytes. No WordPress database changes, new posts, transactions, messages, WWW deployment, AfterShip or ERP effects.

## Actual Dev-only V2 canary results (read-only browser, no business effects)

- Guarded 4-file Dev installation after exact GitHub SHA / PHP lint / host+G2 / target-absent checks succeeded. Rollback script: `/home/u247343654/byl-v3-backups/pr47-default-v2-35d2d806-20261009/rollback.sh`.
- Live browser rendered Terms, Privacy, Disclaimer, a rich Default Page, historical canvas Landing, Blog homepage and two normal Posts at 390px and 1440px (18 checks). All HTTP 200. 16 test expectations passed; the only two failed expectations were the incorrect assumption that Privacy had a legacy non-default template, now corrected by direct Dev REST `template:""` evidence.
- Real Privacy V2: semantic H1, reading width 350px mobile / 760px desktop, no outer frame or horizontal overflow. Legal body unchanged; no stale localdev support email.
- Real Post V2: wide 1120px optional desktop feature image, readable 760px article and 350px mobile article, exactly one H1, no overflow; 2 samples PASS.
- Pre-existing unrelated issues observed: one legacy canvas landing has mobile docWidth 479px at viewport 390px (not a default-template change); Random Gift Policies text still contains stale `support@localdev.beyourlover.com` (not changed by template work). Track independently; do not rewrite Page text under this code PR.
