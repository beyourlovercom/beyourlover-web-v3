# BYL A3-C2 — WWW post 41548 → Dev single-article Canary preflight (2026-11-03)

## Scope and authority

Source: original Owner-exported WWW Posts WXR `beyourlover.WordPress.2026-11-03 (1).xml`, SHA-256 `b4e17b669b2f7071514ddd8a8e959f2f4372df8280df6b8201063c9761eb5453`. Keep the XML and article content private, never commit to this repository or Notion.

**Current authorization:** source-read-only preparation and Draft PR development. **NOT authorized:** Dev posts/tags/attachments writes, WP content publish, WWW changes, A2 SKU retry, Action Scheduler/Jetpack/background queue resume, PR merge, production deploy.

Run source-only preflight from Owner's China Mac against the original file:

```bash
python3 scripts/byl_a3_c2_post_41548_source_preflight.py \
  --wxr "$HOME/Downloads/beyourlover.WordPress.2026-11-03 (1).xml"
```

The script checks exact source checksum, identity, category/tag scope, SEO fields, all six media rows, Gutenberg block IDs and image hosts. It does **not** emit body HTML, historical comments, authors, passwords, offloader keys or customer/order data. `dev_write_authorized=false` by construction. Source-only run: PASS. Offline synthetic fail-closed regression: **9/9 PASS** on China Mac. Foundation CI result for new HEAD must be checked independently.

## Exact source facts

| Field | Source / expected |
| --- | --- |
| WordPress source post ID | `41548` (evidence only; not Dev insert ID) |
| Post type/status | `post` / `publish` |
| Slug | `sex-during-pregnancy` |
| Original published UTC | `2026-10-16 08:43:57` |
| Title | Sex With a Pregnant Partner? Let’s Make It Simple for Penis Owners |
| Raw WXR content SHA-256 | `69044491bbdec913cb4674f0d6aa9e2c7715be307214580fbfc1aaacb5474e58` |
| Native source content length | 20,937 Python code points |
| Categories (match by slug) | `sex-educations` → Dev `121`; `sex-positions` → Dev `954`; `sex-tips-advice` → Dev `120` |
| Tags | None. No new term is required for this Canary |
| Rank Math title | `Sex During Pregnancy: A Guide for Partners %sep% %sitename%` |
| Rank Math focus | `Sex With a Pregnant Partner,Sex During Pregnancy: A Guide for Partners` |
| Source Rank Math primary category | old `120`, identify by its source slug; do not transfer old ID blindly |
| Rank Math description | No explicit source value; do not invent one |
| Media | 1 featured + 5 inline images, six separate WWW IDs, all source CDN URLs HEAD 200 in prior A3-C1 |

### Exact image identity

| Role | Old WWW attachment ID | Original CDN URL | Inline CDN URL (if any) |
| --- | --- | --- | --- |
| Featured | 41549 | https://img.beyourlover.com/2026/10/16084011/sex-with-a-pregnant-partner.png | — |
| Inline 1 | 41550 | https://img.beyourlover.com/2026/10/16084244/image-8.png | https://img.beyourlover.com/2026/10/16084244/image-8-1024x700.png |
| Inline 2 | 41554 | https://img.beyourlover.com/2026/10/16084245/image-12.png | https://img.beyourlover.com/2026/10/16084245/image-12-1024x700.png |
| Inline 3 | 41551 | https://img.beyourlover.com/2026/10/16084245/image-9.png | https://img.beyourlover.com/2026/10/16084245/image-9-1024x700.png |
| Inline 4 | 41553 | https://img.beyourlover.com/2026/10/16084245/image-11.png | https://img.beyourlover.com/2026/10/16084245/image-11-1024x700.png |
| Inline 5 | 41552 | https://img.beyourlover.com/2026/10/16084245/image-10.png | https://img.beyourlover.com/2026/10/16084245/image-10-1024x700.png |

Source `_wp_attached_file` filenames are not sufficient to reconstruct the CDN URL: original R2 URLs include an extra timestamp directory and Gutenberg inline URLs have size suffixes. Do not copy WWW attachment IDs or assume the same attachment IDs exist on Dev.

## Before requesting precise Dev write PASS

1. Repeat **read-only** Hostinger Dev WPCLI check on the authorized existing Dev service: exact home/staging, A1 MU safeguard hashes, scheduler/Jetpack still paused, no Woo/provider side effects.
2. Recheck Dev slug and custom permalink collision, native WP ID 41548 collision, all three category slugs, taxonomy state, current SEO/Permalink Manager handling, and six-image file mapping/offloader behavior. Do not assume a new Dev post ID equals 41548.
3. Verify Dev R2 uploads are correctly integrated and creating **new** Dev media attachments is supported. CDN accessibility is not the same as Dev WordPress media ownership. Media creation must use supported WP media APIs, avoid raw offloader metadata and avoid copying `_thumbnail_id`.
4. Record exact preimage receipts and rollback plan for new Dev-only post and six Dev-owned attachments. Never edit existing post/page/product IDs. In particular preserve 378 shared records, all 11 Dev-only pages including `black-friday`, and A2 five SKU values as-is.
5. Secure separate Owner approval describing the exact Dev-only write set; do not infer it from approval of A3-C1.

## Proposed reversible write design — NOT EXECUTED

* First create, at most, **six fresh Dev attachment records** from the six verified original CDN files; use WordPress media sideload APIs and validate image decoding, mime/size, URL and Dev's media offload integration. Record mapping `old_source_id → new_Dev_attachment_id` and every new Dev media object in a private receipt. No raw SQL insertion of attachment records.
* Parse the five Gutenberg image blocks and rewrite **only** their exact image-block `id`, `wp-image-{old}` classes and their exact source inline URLs to the new Dev-owned media representations, preserving all other HTML, links, blocks and text. Do not use global content-wide string replacement.
* Create **one** new Dev post first as **draft** with the original content, title, slug, UTC publish date, 3 mapped category IDs, zero tags and relevant SEO title/focus meta; map primary category by slug, with no source user/author ID transfer, comments, old offloader/R2 meta, analytics or custom historical permalink IDs.
* Verify Dev preview vs WWW: paragraphs/headings/embedded media, 5 Gutenberg image blocks, 1 featured image, categories, keywords, post date, canonical/URL, no new broken R2 URLs or internal link regressions. Only after an explicit separate visual/UAT pass may the post change from draft to source publication status.
* Rollback is by the **recorded new Dev IDs only**: unpublish/delete the Canary post and delete only Canary-owned unused new media records after reference checks. Retain a private receipt. Never touch WWW, any existing Dev page/post/media or background queues.
* Confirm no taxonomies inserted (this source post has no tags), no product/SKU/checkout/payment/WMS/AfterShip calls, and no A2 security-denied write rerun.

## Gate status

| Gate | Result |
| --- | --- |
| A3-C1 40-post source field mapping | PASS |
| A3-C2 exact WXR source checksum + post/SEO/media manifest | **PASS** |
| A3-C2 isolated synthetic tests | **9/9 PASS** |
| Dev current preimage + media-offload capability | PENDING read-only renewal |
| Owner permission for new Dev article and attachments | **NOT GRANTED** |
| Dev article/media writes | **0** |
| A2 blocked SKU writes | **0/5; unrelated and must remain blocked** |
| WWW / shared Dev content / queue writes | **0** |

No raw source WXR, user data, comments or article body is stored in GitHub. This file is an auditable Dev Canary implementation plan, not a completed live migration.
