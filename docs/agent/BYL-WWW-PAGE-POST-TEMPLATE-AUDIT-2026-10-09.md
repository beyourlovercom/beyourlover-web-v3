# BYL V3 — WWW Page / Post Template Read-only Audit
Audit date: 2026-10-09
Source of live design evidence: public rendered pages on https://www.beyourlover.com/
GitHub target: beyourlovercom/beyourlover-web-v3, **Dev-only code Draft; no WWW file read or modification**.

## Sampled live WWW pages and actual WordPress template identities

| Reference URL | WWW body-class template type | 1440px content measure | 390px content measure |
|---|---|---|---|
| /terms-and-conditions/ | page-template-template-custom-default-page-php | `#main` / `.entry-content` ≈1170px | ≈360px; ~15px gutters |
| /privacy-policies/ | page-template-default | ≈1170px | ≈360px; ~15px gutters |
| /company/ | page-template-default, complex company Page | ≈1170px | width 360px |
| /blog/beyourlover-release-moji-thrusting-dildo-machine/ | post-template-default single-post | outer content grid 1170px; article main 700px including 20px padding; actual text 660px | article text 350px; 20px gutters |
| /blog/ | page-template-template-blog-homepage-php | Full-width rich design with slider and illustration/category tiles | mobile illustrated slider/cards |

Evidence: real browser DOM bounding-box measurements, body template classes, captured 1440px and 390px screenshots on Owner China Mac:
`~/Downloads/BYL-WWW-Template-Audit-20261009/`.

**Important provenance limitation:** Public HTML reveals rendered templates, layout and WordPress template slugs. It does **not** expose original Shoptimizer child-theme PHP source or prove the behavior of every historically published Page and Post. Do not claim a code-level exact copy of legacy templates.

## Lessons retained and improvements made

- WWW posts have a large featured media area before the title and narrow long-form text, then category/date, original body media and post navigation; retain this hierarchy, remove dependence on the legacy sidebars / duplicated global headers, use BYL V3 existing global shell.
- WWW plain legal Pages are effectively too wide on desktop, with hidden archive H1 and thin 15px mobile gutters; use visible semantic Page H1, 20px gutters mobile, consistent vertical spacing and 760px max reading measure.
- WWW company/structured Pages need wider layout. The default `page.php` detects text-only Gutenberg/core/classic blocks conservatively; unknown/media/column/full-aligned or flex content uses a restrained 1120px-wide layout.
- The new `single-post.php` is specifically for normal WordPress **Posts**, not WooCommerce products or other custom post types; 1120px wide optional featured image, 760px reading measure, single H1, date/category, tags, post navigation.
- Keep **Blog homepage** separate as a dedicated visual page. In Dev, `/blog/` is an imported Page with legacy `_wp_page_template=template-blog-homepage.php` and has rendered images. Default `page.php` explicitly defers this slug to the pre-existing index fallback rather than constraining it. A true BYL V3 blog landing upgrade is a **separate gated task**.
- Keep specialist routes (Cart, Checkout, My Account) on their existing template/fallback paths. Existing `page-cart.php`, `page-checkout.php`, `page-track-your-order.php` are **untouched**.
- Existing imported Dev Terms post #11735 still reports `_wp_page_template=template-custom-default-page.php` while the V3 theme lacks that file; WordPress may fall back to `page.php`. Clear / assign the template explicitly only after Owner acceptance, not as an unreviewed DB write.
- The existing optional `template-simple-page.php` remains available for manually chosen text Pages even if a text-first block classifier cannot identify a custom block.

## V3 template routing contract

| WordPress content | Default file | Exclusions / overrides |
|---|---|---|
| Ordinary Page, text-first | `page.php` -> `template-simple-page.php` | 760px reading, original the_content() |
| Ordinary Page, rich/custom | `page.php` -> rich content wrapper | 1120px max; untouched blocks |
| Explicit BYL Simple Page | `template-simple-page.php` | Select from WordPress Page Templates |
| Post, single | `single-post.php` | 1120px optional hero / 760px text; no template assignment required |
| Blog homepage | Existing `index.php` fallback | Specific /blog/ route kept out of Page auto-layout |
| Checkout / Cart / My Account | Prior specialized template or index fallback | No payment hook changes |
| Product / other post types | Native WooCommerce/custom routing | single-post.php not selected |

## Verification and gates

- New isolated PHP mock smoke asserts text/legacy/rich/full-width/Blog/commerce Page decisions and Single Post with and without hero.
- Foundation CI includes the mock and PHP lint for all theme files. CI PASS does not mean live Dev deploy.
- Prior BYL Simple Page isolated preview with live Dev Terms HTML: 390px -> 350px centered, 768px -> 706.6px, 1440px -> 760px; horizontal overflow 0.
- Deployment and actual Dev Page/Post UI UAT require **Owner preview PASS**, safeguarded Dev file readback/backup, scoped Dev deployment, 390px/1440px screenshots on existing Dev Pages/Posts, and no change to legal content or Woo checkout.
- Neither www content nor any DB or production service is mutated by this template code branch.
