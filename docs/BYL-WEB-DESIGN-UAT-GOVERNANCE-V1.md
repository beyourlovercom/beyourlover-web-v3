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
