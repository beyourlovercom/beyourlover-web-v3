# BYL Website V3 — Blog Inline Woo Product Card Compatibility, Dev Canary V1

**Code source:** GitHub `beyourlovercom/beyourlover-web-v3` main `f93582c54a5393817c91a9650bbe3a4f57e1c5a5`; branch `feat/blog-product-card-shortcode-compat-20261010`.
**Owner scope:** Close remaining Blog inline product callout parity after PR #53 GitHub main merge. No WWW deployment, no plugin creation, no stored content migration.

## Exact publicly observed failure

- `www.beyourlover.com/blog/dom-and-brat-play/`: inline `.c-product-card` renders real product **BDSM RED BONDAGE KIT**, Woo ID 9153, photo, name, description excerpt, price, sale badge, and View Product link.
- `dev.beyourlover.com/blog/dom-and-brat-play/`: imported Gutenberg text currently displays literal `[product_card id=”9153″]` (smart curly quotes) instead of a product card. Neither Pages nor Cart/Checkout are implicated.
- Independent anonymous Dev Woo Store API `/wp-json/wc/store/v1/products/9153` confirmed real visible product `BDSM RED BONDAGE KIT`, ID 9153, and product permalink `https://dev.beyourlover.com/red-bondage-kit/`. The Dev product page is HTTP 200. Therefore there is no justification to invent product records, pricing or external redirect.
- Other audited article `/blog/fingering-positions/` has no inline product-card marker; must remain unchanged.

## Scoped implementation

- `single-post.php` loads `inc/blog-product-card.php` only for normal WordPress single Posts. No new WordPress plugin, directory or global checkout hook.
- `the_content` priority-10 filter canonicalizes **only** legacy smart-quoted numeric `[product_card id=”123″]` into `[product_card id="123"]`; preserves unrelated text and block editor content. WordPress's existing priority-11 `do_shortcode` then resolves the registered shortcode.
- Renderer looks up the real local Woo product with `wc_get_product`, requires `status=publish` and `is_visible()`, prints WP/Woo-sanitized live product name, thumbnail, short-description excerpt, current price HTML, bounded sale badge and product permalink. No static dollar amounts, cross-domain URLs, shopping-cart modifications or product DB writes.
- `post-single.css` adds WWW-like 100px-desktop/80px-mobile thumbnail, bordered/rounded product card and View Product CTA, all scoped to `.byl-post-single__content .byl-blog-product-card`.
- Offline self-contained PHP assertions validate quote normalization, approved data, XSS-safe IDs, hidden/draft/missing product fail closed, and non-Post fail closed. Existing Page/Post fixture adds only harmless registration mocks.
- Before Dev rollout: exact-head CI PASS, Hostinger Dev host/user and G2 Closed check, exact source SHA lock and predeployment backup of changed PHP/CSS, no existing new component file; rollback ready. Afterwards real anonymous browser UAT at 390/1440 for the affected and unaffected Posts, G2 Checkout 403, Privacy/Terms 200.
- Owner visual UAT remains needed for real product card. PR stays Draft/unmerged unless explicitly approved exact-head for main. No WWW/ERP/AfterShip production action.

## Follow-on gaps

- Dev `dry-humping-vs-frotting` missing article (404), cannot fake NEXT link.
- Newsletter CTA points to existing Page whose form functionality is unverified; do not claim subscriptions work.
- Historical article links containing `localdev.beyourlover.com` are independent content/link hygiene; this code does not rewrite them.
