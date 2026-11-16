# BYL_PR_IMAGE_RIGHTS_V1 — Press Room 肖像与图片授权规则

- **Status:** Owner APPROVED · Active for Press Room development and review
- **Approved:** 2026-11-16
- **Priority:** P0 editorial/media-rights gate
- **Scope:** BeYourLover Press Room index, featured cards, archive cards, detail pages, media kit, social/SEO preview images, responsive variants, and future Press Room revisions. Applies on `dev.beyourlover.com` immediately and to any future WWW release **only after the separately authorized launch**.
- **Non-goals:** This document does not authorize any WWW edits, publication, deployment, site migration, or reuse rights for third-party content.

## Owner 冻结原文（不得弱化）

> **BYL_PR_IMAGE_RIGHTS_V1：没有明确肖像授权的照片，一律不得用于 PR 页面。优先使用无人物产品图、授权媒体素材或原创文字视觉。**

## Mandatory acceptance criteria

1. **No identifiable face without documented portrait permission.** Do not place a recognizable person, influencer, model, employee, customer or attendee in any Press Room photo/video frame, thumbnail, cropped image, hero or background unless explicit, traceable authorization covers the particular PR/editorial/promotional use. An influencer partnership, published social post, an image already on the website, or source-media coverage is **not** automatically permission to reuse the person's likeness in PR.
2. **Prefer face-free imagery.** In order: (a) relevant BeYourLover product-only photography with no identifiable people, (b) media imagery with verified reuse/licensing permission, or (c) original face-free typographic/abstract editorial graphics. If suitable authorized imagery is unavailable, use an original text-only visual; do not invent a magazine cover.
3. **No misleading substitutions.** Replacement visuals must not imply that an unrelated SKU was featured in the article or that a brand-created graphic is an original magazine scan/event photograph. Use accurate alt text and captions (e.g., “BeYourLover editorial graphic — not the original magazine image”).
4. **Rights evidence before use.** For any proposed image containing an identifiable person or third-party publication artwork, record its URL/file reference, rightsholder, exact permission scope (channel, purpose, period), evidence location, and named internal approver. Missing/ambiguous evidence = **not approved**; do not use. No agent may infer consent from public availability.
5. **End-to-end image audit.** Before accepting a Press Room page, inspect image references and rendered output across hero, featured cards, archive, detail, related sections, and social preview metadata. Check at minimum desktop (~1440 px) and mobile (~390 px). Ensure no disallowed assets remain referenced, images are not broken, source links and article facts are preserved, and no horizontal overflow is introduced.
6. **Fail closed, resolve without stalling unrelated stories.** If an image lacks permission, remove/replace it on Dev immediately, mark only that image as blocked for rights evidence, and proceed with rights-safe visual alternatives. Do not publish or migrate the unapproved version to WWW.
7. **No global deletion.** Removing an image from Press Room content does not authorize deleting the original file from the site's shared media library, as other site areas may depend on it.

## Current Dev application (2026-11-15)

- Preview: https://dev.beyourlover.com/press-room-v3-preview/
- The identifiable influencer hero image and three recurrent person/character illustrations were removed from the Press Room content.
- The index and six related detail pages were updated with face-free product photography or source-honest typographic media visuals; 18 article destinations remain.
- This is **Dev-only remediation**, not a WWW/Production release.

## Release gate

**PASS** only when image-rights evidence is present for any identifiable person/third-party media image **or** every in-scope visual is face-free and otherwise legitimately reusable. Recheck after any subsequent image swap, card template change or migration. Owner's existing Design PASS, Dev UAT and WWW release gates still apply.
