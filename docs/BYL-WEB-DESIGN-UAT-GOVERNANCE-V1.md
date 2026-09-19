# BYL Web Design & UAT Governance V1

Status: CANONICAL
Scope: all future BYL website page creation, redesign, optimization, and maintenance.

## Canonical workflow
1. Desktop design draft first: establish visual direction, hierarchy, spacing, module rhythm, and content architecture.
2. Owner Desktop Visual UAT must PASS before implementation.
3. Mobile design draft second: recompose for mobile rather than shrink desktop.
4. Owner Mobile Visual UAT must PASS before implementation.
5. Mobile is the final visual priority because the majority of site traffic is mobile.
6. Only after both design gates PASS may implementation begin.
7. Implement the approved design with real product/content assets; do not invent substitute product visuals or unsupported claims.
8. Open the implemented page on the CN Mac for Owner UAT.
9. Validate both Desktop and Mobile implementations; Mobile PASS is mandatory for final acceptance.
10. Only after implemented Desktop + Mobile UAT PASS may the page/module be LOCKED.

## Maintenance rule
The same workflow applies to later page edits, maintenance, refactors, visual changes, and new modules unless Owner explicitly marks a change as trivial/non-visual.

## Mobile-first acceptance
- Desktop PASS does not imply final PASS.
- Mobile layout may differ structurally from desktop when needed for symmetry, readability, interaction, and conversion.
- Preserve one dominant idea per viewport/module where practical.
- Prefer intentional whitespace, centered composition, readable tap targets, and mobile-safe media framing.
- Never crop instructional/product-critical media merely to mimic desktop composition without an approved mobile-safe asset.

## Execution environment
- Design approval precedes engineering implementation.
- Implemented UAT must be opened on the CN Mac whenever locally reachable.
- Windows-only availability is not sufficient for Owner UAT.
- Production remains untouched until the relevant release/deploy gate is explicitly approved.

## Default page-development priority

Page development defaults to P0 unless the change is explicitly classified as lower-risk.

### P0 — highest priority / default for important pages
Required for new pages, core-page rebuilds, difficult pages, high-aesthetic pages, high-conversion pages, and any page whose visual accuracy matters.

Mandatory sequence:
1. Desktop design draft -> Owner PASS.
2. Mobile design draft -> Owner PASS.
3. Implementation with real content/assets.
4. Screenshot UAT through DC/MCP at 1440 / 768 / 390, producing PNGs plus manifest.
5. Manual visual review using the M14 method: compare approved design vs implementation for spacing, proportions, alignment, typography, CTA treatment, iconography, wrapping, vertical rhythm, and mobile composition.
6. Fix mismatches before Owner review.
7. Open the real implementation on the CN Mac for Desktop + Mobile Owner UAT.
8. Mobile is the final acceptance priority.
9. LOCK only after final PASS.

M14 Compare Models is the canonical reference for this P0 review method.
DC through MCP screenshot generation/retrieval is a P0 capability, not an optional convenience.

### P1 — standard
May be used only for medium-complexity pages or extensions that inherit an already-LOCKED visual template and do not materially change composition.

### P2 — lightweight
May be used only for clearly trivial/non-visual maintenance such as small copy edits, tiny style fixes, or low-risk maintenance.

Downgrade rule:
- Do not ask whether an important page should use P0; P0 is the default.
- Downgrade to P1/P2 only when the task is clearly lower risk or Owner explicitly requests a lighter workflow.
