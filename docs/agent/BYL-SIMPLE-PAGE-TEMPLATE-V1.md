# BYL V3 — Simple Page Template V1

**Purpose:** For text-first WordPress **Pages**, such as legal policies, help, FAQ, editorial notes or lightweight one-column content. Reuse the original BYL V3 header/footer, theme fonts/colors, Gutenberg post content, existing URLs and publishing workflow.

## Opt-in rule

- WordPress **Pages → Edit → Page settings → Template → BYL Simple Page**.
- Backing file: `theme/beyourlover-v3/template-simple-page.php` (WordPress page template header); only this template loads `assets/css/page-simple.css`.
- No automatic migration, no modifications to `index.php`, `theme.json`, Woo templates, Checkout, Cart, G2/G3/G5 payment scripts, URLs, DB content, or Production.
- **Terms and Conditions** (post #11735) is the proposed first Dev pilot. **Do not assign or deploy until Owner visually approves the rendering.** Privacy (#7271) and other Pages stay on their existing templates unless deliberately assigned.

## Layout contract

| Viewport | Side gutters | Reading column | Vertical padding |
|---|---|---|---|
| 390px mobile | 20px left/right (or safe-area inset if larger) | available content width, about 350px | 36px top / 64px bottom |
| 768px tablet | clamp(20px, 4vw, 56px) | <= 760px, centered | responsive |
| 1440px desktop | centered around maximum 760px reading width | 760px | 72px top / 100.8px bottom |
| Wider desktop | always centered | maximum 760px | capped at 72px / 104px |

- Includes a true `h1` from the Page title; post content follows without rewrite.
- Gentle paragraphs, list/heading and section-divider spacing are scoped strictly under `.byl-simple-page`.
- Long URLs and media cannot force page-level horizontal overflow.
- No new brand colors, no large hero, no additional plugin, no global page CSS changes.

## Non-transactional testing

1. Confirm theme PHP lint and check `Template Name: BYL Simple Page` and `Template Post Type: page`.
2. In an isolated headless preview, wrap real Dev Terms content with the proposed reading container and stylesheet; verify 390px, tablet and 1440px content measure, overflow and title.
3. After a separately approved Dev-only code deployment, assign only Terms (#11735) using the normal authenticated WordPress Page editor **after backing up its current `_wp_page_template` value**. Screenshot the actual Dev page at 390px and 1440px and wait for Owner PASS.
4. Rollback: restore previous Terms Page template selection; revert new template files as needed. Legal text remains unchanged. Never apply to www without separate cutover approval.
