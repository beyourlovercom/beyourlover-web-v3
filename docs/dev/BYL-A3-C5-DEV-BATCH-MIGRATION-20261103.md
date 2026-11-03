# BYL WWW → Dev — A3-C5 Dev-only batch migration (2026-11-03)

**Status: IN PROGRESS — 8 / 37 articles imported and independently verified.** This document records executed work, not a claim that all 37 are complete. PR #168 remains **Draft**, no merge/deployment.

## Source and authority

- Owner authorized Dev-only batch migration after A3-C3 and A3-C4.
- Immutable WWW Posts WXR SHA-256: `b4e17b669b2f7071514ddd8a8e959f2f4372df8280df6b8201063c9761eb5453`.
- C4 whitelist: 37 distinct published WWW post IDs/slugs, excluding the completed Canary #41548 → Dev #40136. No existing Dev content may be overwritten.
- Source article bodies, original full XML, private migration bundle and attachment-by-attachment receipts **remain on owner-controlled devices/Hostinger in private paths, not in GitHub**.
- Target site: `https://dev.beyourlover.com` only; staging, `blog_public=0`, disabled WordPress Cron, four pinned A1 safety MU plugins. No WWW writes, no public article publication, no external queue unpause, no product/SKU operations.

## Exact tags — PASS

C5 executed a fail-closed dry-run, then created exactly **179** previously missing `post_tag` terms. Existing tag names and slugs were preserved. The Dev post-tag total after creation was **1,636**. The 259 source unique tag slugs therefore map to 80 existing and 179 newly created terms.

An early preflight correctly halted when an unrelated Dev attachment reused a WWW numeric post ID; importer gate was corrected to check destination **slug**, never source numeric IDs. A second dry-run found Permalink Manager `tax-...` entries created with tag slugs; the gate was refined to check only **numeric-keyed post/page custom URI entries**. These were gate design corrections, **not bypasses of an access-control refusal**.

WordPress WXR did not include usable `wp:post_mime_type` for attachments. Importer now constrains source filename extensions to PNG/JPEG and verifies downloaded image bytes using `getimagesize()`, expected MIME, and size limits.

## Draft migration — two waves PASS

All article imports use fresh Dev post/attachment IDs and `wp_slash()` to retain literal backslashes. Media files get unique `a3c5-p<SOURCE>-src<ATTACHMENT>-<digest>` names, and new R2 URLs are independently checked for HTTP 200. Each post is inserted as **draft**, and source Gutenberg block IDs/classes/src URLs are mapped only to the new Dev media IDs. Metadata, six mapped categories and 259 source-tag mappings are checked per article.

| WWW source ID | Dev draft ID | Media | Image blocks | Outcome |
|---:|---:|---:|---:|---|
| 39967 | 40150 | 3 | 2 | PASS |
| 40021 | 40155 | 4 | 3 | PASS |
| 40126 | 40159 | 3 | 2 | PASS |
| 40171 | 40168 | 8 | 7 | PASS |
| 40262 | 40171 | 2 | 1 | PASS |
| 40311 | 40182 | 10 | 9 | PASS |
| 40378 | 40185 | 2 | 1 | PASS |
| 40421 | 40189 | 3 | 2 | PASS |

**Totals:** 8 new Dev drafts, **35** new media attachments, **179** new tags, **29** source posts remaining. The two batch receipts returned `BATCH_PASS`; subsequent independent, read-only WordPress checks returned `A3C5_INDEPENDENT_READBACK_PASS` for **8/8**. Every tested saved body SHA matched a second reconstruction from the original WXR body plus the exact new image URL/ID transformation. Draft status, categories, tags, featured attachment were also verified.

## Execution checkpoint and next operation

- **Resume at zero-based source index 8 / 37** (next source #40475, `extreme-sex-positions`), **not** index 0. Previously created drafts/assets must not be reimported.
- Private receipts on Windows: `C:\Users\Administrator\AppData\Local\BYL-A3-Private\a3c5-progress-8-of-37-20261103.json`. SHA-256: `04e0d3d8938d52f6b4dc23e2dffb953107290cb22db3c9ec98fce602c8bdd635`.
- The pinned private 37-post transfer payload SHA-256 is `a93815ec71561a4f46863a8b6c2caeae9262ecedfc326b0cb766148340b93d7c`; it is **not** committed here.
- Before every further batch: repeat live Dev-only preflight for its target slugs, site identity, isolation MU hashes, tag/category IDs, and source SHA. Run at most five posts per batch and stop at any error. Keep saved per-media/per-post receipt and perform separate independent readback.
- **No approval to publish these drafts** or merge this PR, modify WWW, restore Action Scheduler/Jetpack, or perform previously blocked A2 SKU writes.

C5 remains **open** until the remaining 29 drafts and their media pass the same independent checks; C6 can then cover final content/visual UAT and controlled release decisions.
