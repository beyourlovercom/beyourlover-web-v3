# BeYourLover WEB PLATFORM V3

Greenfield foundation for the BeYourLover V3 rebuild.

## Canonical rules
- Highest VI authority: docs/BYL-VI-CANONICAL.md; visible brand name is always exactly BeYourLover.
- Code source of truth: GitHub.
- Transactional data source of truth: Production.
- Code flows GitHub -> localnew -> staging -> Production.
- Data flows Production -> staging/local; never reverse a stale staging DB onto Production.
- Existing Woo product IDs, variation IDs, SKU bindings and ERP external references are identities.
- `img.beyourlover.com` remains the canonical public media origin.
- V3.0 preserves SEO identity while rebuilding presentation.
- Business/integration logic does not belong in the theme.

## Layout
- `theme/beyourlover-v3/` — presentation only.
- `platform/byl-platform/` — **frozen transitional source only**, pending verified Dev retirement into Andy Commerce. No new features here.
- `docs/` — environment, release, cache/WAF and migration contracts.
- `scripts/` — release and smoke tooling.
- `.github/workflows/` — foundation CI.

G2 is a foundation gate only. Production deployment is not authorized.
