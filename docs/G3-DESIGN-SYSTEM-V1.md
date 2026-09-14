# G3 — BYL Design System V1

Status: implementation candidate for localnew UAT.

## Frozen brand inputs
- Canonical brand: BEYOURLOVER existing logo/identity.
- Canonical brand red: `#9B3749` (legacy code also contains near-identical `#9C3749`; V3 normalizes to one token).
- Typeface: Poppins, weights 400/500/600/700.
- Visual direction: calm, premium, commerce-first, Apple/HIG-informed without copying Apple UI.

## Token contract
- Brand: `--byl-brand`, `--byl-brand-hover`, `--byl-brand-soft`.
- Neutral: Ink / secondary ink / tertiary ink / line / surface / soft surface.
- Spacing: 4, 8, 12, 16, 24, 32, 48, 64, 96.
- Radius: 10, 16, 24, pill.
- Container: 1440px with responsive gutter.
- Motion: 160ms fast, 260ms base, 520ms slow using one canonical ease.

## Motion levels
- L0 Utility: Cart, Checkout, Login, Account — feedback only.
- L1 Editorial: Blog, FAQ, Support, Guide — restrained orientation motion.
- L2 Commerce: Product, Category, Collection — controlled gallery/product transitions.
- L3 Experience: Homepage, App, Brand, Campaign — richer storytelling allowed.

## Primitive contract
Buttons: Primary / Secondary / Quiet.
Cards: standard + soft surface.
Chip: compact semantic label.
Typography: Display XL/L, Heading XL/L/M, Body L/M/S, Eyebrow.
All motion respects `prefers-reduced-motion`.

G3 does not freeze final page templates. G4/G5 build shell/components from these primitives.
