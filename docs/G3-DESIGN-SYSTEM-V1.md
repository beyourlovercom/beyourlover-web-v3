# G3 — BYL Design System V1

Status: implementation candidate for localnew UAT.

## Frozen brand inputs
- Canonical brand: BEYOURLOVER existing logo/identity.
- Canonical brand red: `#9B3749` (legacy code also contains near-identical `#9C3749`; V3 normalizes to one token).
- Typeface: Poppins, weights 400/500/600/700.
- Visual direction: calm, premium, commerce-first, Apple/HIG-informed without copying Apple UI.

## Token contract
- Brand: `--byl-brand`, `--byl-brand-hover`, `--byl-brand-soft`.
- Neutral: Ink / secondary ink / muted ink / line / surface / soft surface.
- Semantic: success / warning / danger / focus.
- Spacing: 4, 8, 12, 16, 24, 32, 48, 64, 96.
- Radius: 10, 16, 24, pill.
- Layout: shell 1440px / content 1200px / reading 760px / responsive gutter.
- Motion: 120ms fast / 220ms base / 420ms slow with canonical easing.

## Typography contract
- Display XL/L, Heading XL/L/M, Body L/M/S, Eyebrow.
- Responsive display and large-heading sizes are CSS token driven.
- WordPress editor presets mirror the canonical type scale.
- Long-form/editorial links keep native discoverability; G3 does not globally remove link decoration.

## Motion levels
- L0 Utility: Cart, Checkout, Login, Account — feedback only.
- L1 Editorial: Blog, FAQ, Support, Guide — restrained orientation motion.
- L2 Commerce: Product, Category, Collection — controlled gallery/product transitions.
- L3 Experience: Homepage, App, Brand, Campaign — richer storytelling allowed.
- All motion respects `prefers-reduced-motion`.

## Primitive contract
- Buttons: Primary / Secondary / Quiet.
- Cards: standard + soft surface.
- Chip: compact semantic label.
- Layout helpers: shell / content / reading-width containers.
- Focus-visible is canonical and must remain keyboard-visible.

G3 does not freeze final page templates. G4/G5 build shell/components from these primitives.
Docs OS is a later G7 runtime; G3 only ensures editorial typography/link/focus foundations do not depend on BetterDocs.

## Foundation primitive rules
- Buttons use a 48px minimum touch target, visible focus, explicit disabled states, and reduced-motion-safe press feedback.
- Links remain visibly links by default; branded link primitives may change color but must not remove discoverability.
- Surface / soft-surface / divider primitives provide structure without page-specific styling.
- Icon sizing is tokenized at 16 / 20 / 24px; icons inherit text color and do not carry product-specific meaning in G3.
- Stack / cluster / grid helpers are layout primitives only; composed product and content components belong to G5.
- Reading-width content stays capped at 760px and is compatible with future Andy Core Docs OS.
## Apple polish contract
- Poppins remains canonical, but large display and heading styles use weight 500 rather than relying on heavy 600/700 presentation.
- Large type uses tighter optical tracking with slightly smaller maximum scale to keep the page premium rather than poster-heavy.
- Buttons retain a 48px target while hover motion stays restrained; active feedback remains subtle.
- Shadows and borders are intentionally lighter; spacing carries hierarchy before decoration does.
- Apple/HIG is an interaction and precision reference, not a visual clone. BYL red and Poppins remain brand-owned signals.
