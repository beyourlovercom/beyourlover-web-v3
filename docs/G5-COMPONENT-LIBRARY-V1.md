# G5 — BYL Component Library V1

Status: Owner UAT candidate on `feat/g5-component-library-v1`.

## Contract
G5 turns the G3 Design System into reusable production UI components. Components own presentation, responsive behavior, accessibility state and restrained interaction; page templates own composition and business flow.

## Component families
- Foundation: Button, Link, Icon Button, Badge, Chip, Surface, Divider.
- Commerce: Product Media, Product Card, SKU/Kicker, Rating, Price, Sale/Availability signal, Product CTA.
- Content: Editorial Card, Docs Card, Support Card.
- Utility: Input, Select, Checkbox, Notice, Empty, Loading/Skeleton, Disabled/Error states.

## Data contract
- Product Card consumes real WooCommerce `WC_Product` data.
- Media resolves through the existing R2-aware attachment resolver.
- Component code must not depend on Shoptimizer DOM, CSS, JS or `shoptimizer_*` runtime functions.
- Missing Woo data fails closed into a neutral empty state.

## Interaction contract
- Minimum primary target height is 48px; icon actions are 44px minimum.
- Keyboard focus remains visible.
- Hover and press feedback is restrained and never required to understand state.
- `prefers-reduced-motion` disables nonessential component animation.
- No component may create horizontal overflow at 1440 / 768 / 390 widths.

## Scope boundary
G5 does not define Homepage, Product, Category, Cart, Checkout, Account or Docs page composition. G6 consumes these components to rebuild templates.

## UAT surface
`/component-library/` is the canonical local showcase. It must render Foundation, Commerce with real Woo products, Content, Forms and State families in one page.

## G5.6 Visual Polish
- Product media surfaces are normalized with a consistent aspect ratio, warm neutral surface, contain-fit image treatment and safe padding.
- Hero technical stats are compressed into one restrained meta row.
- Editorial, Docs and Support cards share one skeleton but expose distinct information priority.
- Forms/states reduce outer card chrome; section density is tightened without changing component contracts.
