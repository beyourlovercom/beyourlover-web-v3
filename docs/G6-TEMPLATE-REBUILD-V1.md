# G6 Template Rebuild V1

## G6.0 Template Contract
- Rebuild presentation, not WordPress/WooCommerce identity.
- Preserve existing URLs, slugs, canonical behavior, SEO metadata and core search intent.
- Existing production content/data is the source; Shoptimizer DOM/CSS is not.
- WooCommerce owns commerce data/behavior; BYL V3 owns markup, layout and interaction.
- No Checkout/Payment Router rewrite in G6; that remains G8.
- BetterDocs runtime replacement remains G7; ERP contract remains G7.5.

## Homepage SEO Identity Baseline
- Front page post ID: 3
- Post title: `Adult Sex Toys | BeYourLover Official Store`
- Slug: `homepage`
- Existing H1 intent: `BeYourLover Sex Toys`
- Existing core sections retained as intent: What can you get / What's on / Most Popular product.
- Rank Math metadata stays database-owned and is not rewritten by the template.

## G6.1 Homepage V3
- Replaces the former Design System preview on the real front-page template.
- Hero keeps the existing H1 identity and uses real Woo product media from R2.
- Discovery section maps live Women / Men / Couples product categories.
- Explore section routes to New Arrivals / App / Blog without legacy theme markup.
- Popular-products section renders real Woo Product Cards from the G5 component layer.
- Couples category uses a live product image fallback when taxonomy thumbnail is absent.
- 1440 / 768 / 390 responsive gates must remain overflow-free.
- Production remains untouched until later cutover gates.
