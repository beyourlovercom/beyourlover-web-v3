# BeYourLover — VI Canonical Rules

Status: **HIGHEST-PRIORITY / GLOBAL / BINDING**

This document is the highest visual-identity authority for the BeYourLover platform. If any page, component, template, plugin UI, marketing surface, documentation rule, or implementation conflicts with this file, **this file wins**.

## VI-001 — Brand name casing

The canonical displayed brand name and wordmark text is exactly:

**BeYourLover**

This casing is globally fixed and must not be transformed.

### Forbidden display forms
- `BEYOURLOVER`
- `beyourlover`
- `Beyourlover`
- `Be Your Lover`
- Any other casing, spacing, abbreviation, or typographic rewrite used as the visible brand name.

### Applies to
- Logo / wordmark fallbacks
- Header, footer, navigation, hero and campaign surfaces
- Product, category, editorial, support and account UI
- Email / notification / app-facing brand labels
- Accessible `alt`, `aria-label`, title and fallback brand text
- Theme/plugin visible metadata where the brand is presented to a human

### Non-display exceptions
Technical identifiers are not renamed by this rule when casing is structurally required or already canonical for the system. Examples:
- Domains and URLs such as `beyourlover.com`
- Git repositories, file paths, text domains and package identifiers
- CSS classes, PHP/JS function names, database keys and API fields
- Existing immutable external IDs

These exceptions must never leak into visible brand presentation.

## Governance
- Every new template/component must use `BeYourLover` when rendering the brand name as text.
- Existing visible violations are defects and should be corrected when discovered.
- G3 Design System, G4 Global Shell, G5 Component Library, G6 Templates and all later gates inherit this rule automatically.
- A visual treatment may change font, size, color or placement, but may not change the spelling/casing of `BeYourLover`.
