# BYL TranslatePress SEO Gate R9 (Dev-only)

## Contract
Only human-approved language versions of a published `post`, `page`, or `product` can be publicly served (HTTP 200) as a localized singular URL. Unapproved versions return HTTP 404 with `noindex`; default-language pages keep working. TranslatePress generated hreflang is suppressed and replaced by reciprocal links for the English original and approved versions only. These controls are Dev-only and start OFF.

No translated URL is inferred from an English slug. For each approval, record the **exact reviewed URL**, after confirming it resolves and contains the intended localized content. This prevents fake language URLs and invalid SEO references.

Dev remains `blog_public=0`. Do not turn on indexing to test the sitemap. Never deploy this Dev-only module to WWW, even by copying the file; WWW cutover requires its own independent gate.

## Approval schema
Use the native WordPress post-meta key `_byl_trp_seo_review` on the **English original**. The value is a serialized PHP array keyed by `es_ES`, `ko_KR`, or `ru_RU`, with mandatory fields:

- `status: approved` only after human inspection, otherwise omit/revoke;
- `source_sha256`: SHA-256 of raw WordPress `post_title + "\\n" + post_content`;
- `body_verified: 1` and nonempty `reviewer`, `reviewed_at` — no automatic approval;
- `title`: manually reviewed localized SEO title (12+ chars);
- `description`: manually reviewed localized SEO description (70+ chars);
- `slug`: exact reviewed URL's trailing slug (`[a-z0-9-]+`);
- `url`: exact absolute language URL on `dev.beyourlover.com`, prefix `/es/`, `/ko/`, or `/ru/`, and matching trailing slug.

Approval invalidates immediately when the English original's raw content/title changes or the post is no longer published. Zero initial approvals is intentional. P0 should not be mass-approved based solely on machine-translation database status.

## SEO rules
1. `trp-exclude-hreflang`: suppress all automatic hreflang; add reviewed English+language set on eligible singular URLs only.
2. Per-language canonical and approved meta description; approved title via WordPress document title filter. Verify browser output to catch duplicated third-party tags or TranslatePress retranslation.
3. `trp_add_language_url_to_sitemap`: disallow unapproved translations in integration-enabled SEO plugin sitemaps.
4. Dev-only `/byl-trp-seo-sitemap.xml`: translated URLs only, from stored approved metadata; never submit the Dev sitemap. The site's default sitemap may stay 404 while `blog_public=0`.
5. Non-singular translated pages receive noindex pending separate taxonomy/archive coverage. English pages stay available.

## Translation budget
- Set TranslatePress option `trp_machine_translation_settings.machine_translation_limit=15000` and `machine_translation_limit_enabled=yes` in **Dev only**, after saving a permissions-restricted pre-change backup outside the webroot.
- Runtime module reads `trp_machine_translated_characters[MM-YYYY]` and disables automatic translations for new requests once the monthly count reaches 500000; fail closed when month counter malformed.
- This is a WordPress-level safety gate, not a Google Cloud budget cap. A request may overshoot near threshold; validate Google Cloud's API quotas/billing restrictions separately. Do not reset counters to force translations.
- Current day count already exceeds 15000; keep automatic generation stopped until the next daily reset.

## Deployment / rollback
1. Push this module to a review branch and verify exact commit/source checksum; do not patch TranslatePress core or merge to main without required approval.
2. Backup existing Dev MU module/file and translation settings if present.
3. Deploy exact reviewed module to `wp-content/mu-plugins/byl-trp-seo-gate.php`; default `byl_trp_seo_gate_enabled=no`, then enable only under supervised Dev UAT via `wp option update byl_trp_seo_gate_enabled yes`.
4. Check English article/home/product HTTP 200, untranslated Spanish article HTTP 404 with noindex, no unauthorized hreflang, XML sitemap valid, source-change invalidation, SEO metadata on approved fixture, perf and budget checks.
5. Roll back by `wp option update byl_trp_seo_gate_enabled no` and remove the MU file if needed. Restore prior translation settings only after explicit budget change approval.

## Open verification
- Need live browser test of an approved Spanish pilot with **content, title, description, slug, canonical, reciprocal hreflang, sitemap, status** all PASS. No approved fixture as of 2026-11-06.
- Must benchmark product/category and home behavior after enabling Dev gate; ensure fallback theme 404 is correct.
- This module is not a completed Google indexing launch. Migration parity, canonical production host, and deployment remain separate gates.
