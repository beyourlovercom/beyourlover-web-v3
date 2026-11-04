# BYL WWW → Dev — A3-C5 Dev-only batch migration (2026-11-03)

**CURRENT STATUS (2026-11-04): A3-C5 CLOSED — 37/37 PASS. A3-C6 TECHNICAL UAT PASS — 37/37, visual review PENDING.** This document records executed work, not a claim that all 37 are complete. PR #168 remains **Draft**, no merge/deployment.

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

## Draft migration — first two waves PASS (historical checkpoint)

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

**Historical first-two-wave totals:** 8 new Dev drafts, **35** new media attachments, **179** new tags, **29** source posts remaining at that earlier checkpoint. See the **33/37 superseding checkpoint** below. The two batch receipts returned `BATCH_PASS`; subsequent independent, read-only WordPress checks returned `A3C5_INDEPENDENT_READBACK_PASS` for **8/8**. Every tested saved body SHA matched a second reconstruction from the original WXR body plus the exact new image URL/ID transformation. Draft status, categories, tags, featured attachment were also verified.

## Execution checkpoint and next operation

- **Historical index-8 checkpoint (superseded below):** #40475 was subsequently migrated; the current index is **33**, and the final four are on a safety HOLD. Never reimport earlier indexes. Previously created drafts/assets must not be reimported.
- Private receipts on Windows: `C:\Users\Administrator\AppData\Local\BYL-A3-Private\a3c5-progress-8-of-37-20261103.json`. SHA-256: `04e0d3d8938d52f6b4dc23e2dffb953107290cb22db3c9ec98fce602c8bdd635`.
- The pinned private 37-post transfer payload SHA-256 is `a93815ec71561a4f46863a8b6c2caeae9262ecedfc326b0cb766148340b93d7c`; it is **not** committed here.
- Before every further batch: repeat live Dev-only preflight for its target slugs, site identity, isolation MU hashes, tag/category IDs, and source SHA. Run at most five posts per batch and stop at any error. Keep saved per-media/per-post receipt and perform separate independent readback.
- **No approval to publish these drafts** or merge this PR, modify WWW, restore Action Scheduler/Jetpack, or perform previously blocked A2 SKU writes.

C5 remains **open** until the remaining **4** drafts and their media can be handled through an authorized, unblocked path. **Do not circumvent the execution safety denial.** C6 has begun as read-only UAT for the 33 existing drafts; final C6 visual UAT remains unapproved.


## Superseding checkpoint — 2026-11-03 C5 33/37 + C6 read-only entry

- Batch windows `0..2`, `3..7`, `8..12`, `13..17`, `18..22`, `23..27`, `28..32`: **7/7 batch write receipts PASS**, **7/7 independent readback windows PASS**.
- Combined actual Dev writes: **33 draft posts**, **118 new image attachments**, **179 previously missing post tags**. WWW write count **0**; public article publication **0**. All 33 drafts remain drafts.
- Source-to-Dev mapping uses newly created Dev IDs, not source numeric IDs. The new R2 media ID/class/src mappings, saved body hashes, featured images, categories and tags passed the independent batch readbacks for **33/33**.
- C6 separate metadata read-only cross-check of the 33 drafts: **33/33** status, post title, excerpt, original GMT date, Rank Math SEO fields (title, focus keyword, primary category) and featured image presence. Zero discrepancies. Private local audit: `C:\Users\Administrator\AppData\Local\BYL-A3-Private\a3c6-metadata-readonly-33-of-37.json`.
- Public-url smoke check from US Mac: `/blog/dry-humping-vs-frotting/`, `/blog/7-ways-to-masturbate-as-a-woman/`, `/blog/period-sex-tips/`, and the original Canary `/blog/sex-during-pregnancy/` all returned **HTTP 404** for anonymous requests, consistent with preserved draft isolation.
- Remaining source whitelist indexes **33..36**, all in dry-run preflight **PASS**: source IDs **41901** (`ruined-orgasms`), **41931** (`guidance-on-sucking-boobs`), **42012** (`moaning-during-sex`), **42034** (`pleasure-tips-plus-size-couples`), requiring **4 drafts and 12 new attachments**.
- **Safety HOLD:** the Dev write request for the final four was rejected by the execution tool's security check before execution. **No article/attachment write was performed in that attempt.** Do not retry via an alternate tool, device, endpoint, encoding, or route to evade the block. A fresh authorized path or explicit platform resolution is needed before importing these four. Avoid claiming C5 37/37 or full C6 PASS.
- Next valid work under existing permissions: read-only UAT for the 33 drafts, Owner visual review through existing authenticated Dev admin, maintaining the 404/isolation behavior; do not publish until separately approved. Production/WWW, external workers, and GitHub main remain untouched.


## Final superseding checkpoint — 2026-11-04 (C5 CLOSED / C6 technical PASS)

Owner gave new `pass` approval to continue the same original Dev-only workflow. After renewed staging/slug/media preflight passed, the original authorized execution route completed the last four articles. The former safety HOLD is now **historical**, not an outstanding blocker. No alternate tool, device or route was used to evade the earlier block.

- Last batch source IDs to Dev draft IDs: `41901 → 40300`, `41931 → 40303`, `42012 → 40306`, `42034 → 40313`.
- Last batch: 4 drafts, 12 media attachments, `BATCH_PASS`, zero publications, zero WWW writes; independent readback returned **4/4 PASS**.
- **A3-C5 final total: 37/37 new drafts, 130/130 new media attachments, 179/179 missing tags created.** Exact 8 batch windows: `0–2`, `3–7`, `8–12`, `13–17`, `18–22`, `23–27`, `28–32`, `33–36`. All 8 write receipts `BATCH_PASS`; independent verification re-run for **37/37**.
- **A3-C6 technical UAT: 37/37 PASS** for exact source-body SHA + Gutenberg inline image ID/class/URL remap, saved draft status, categories, tags, featured media; and 37/37 post titles, excerpts, original GMT dates and Rank Math SEO fields (title, focus keyword, primary category). No differences.
- **A3-C6 media: 130/130 HTTPS HTTP 200** via a fresh independent WordPress media attachment audit; all 130 attached to the expected new Dev posts with isolated `a3c5-...` file names, unique attachment IDs and CDN URLs. No missing/duplicate attachments.
- **Anonymous exposure check: 37/37 draft URLs returned HTTP 404** on independent China Mac HTTP requests; no accidental public article release.
- All audit evidence stored privately on owner-controlled Windows/China Mac and Hostinger user home, NOT copied into GitHub. Windows summary receipt: `C:\Users\Administrator\AppData\Local\BYL-A3-Private\a3c5-final-c6-technical-receipt-20261104.json`; SHA-256: `560b8d8fc5fe0d01256f00a528a8d35285c7938748a0c5ce2e36efe2f2fbc8e2`.
- **Visual/UAT HOLD for manual site review:** server-side body/media validation is not a screenshot or layout-level assertion. Browser connection did not expose an authenticated Dev tab, so 37/37 visual equivalence is **NOT claimed**. Owner can inspect via authenticated WordPress editor/preview. This is the only remaining C6 acceptance item.
- WWW Production unchanged. Do NOT publish draft articles, resume Action Scheduler/Jetpack, alter A2 SKU, merge this Draft PR or deploy from main without separate approval. The previously noted shared-post body discrepancies remain a separate analysis scope, not resolved by this new-article migration.


### Media SHA-256 / decoded-pixel reconciliation — 2026-11-04

A strict source-versus-Dev CDN GET audit was run for all 130 images beyond the prior 130/130 HTTP-200 test. The initial pass showed 122 exact binary matches, seven different PNG encodings, and one transient CDN GET timeout. A longer retry of the timed-out image returned a **byte-identical file**. All seven different PNG files were separately downloaded and compared with Pillow using decoded RGBA pixel SHA-256, dimensions, alpha channels and color metadata: **7/7 decoded-pixel identical**. Every source PNG alpha channel was fully opaque (`min=max=255`); WordPress's Dev image processing converted `RGBA` to `RGB`, producing smaller but **pixel-identical** PNG files. No ICC/gamma/transparency metadata difference was found.

**Final image fidelity:** 123/130 byte-identical; 7/130 byte-different but pixel-identical; **130/130 visually lossless**. For archival purposes, exact original bytes remain accessible at the source CDN URLs; this new Dev copy is not claimed to be bit-for-bit identical for those seven media. No R2 object overwrite was attempted. Private Windows evidence: `a3c6-media-bytehash-130.json`, `a3c6-media-lossless-pixel-diffs-7.json`, and revised `a3c5-final-c6-technical-receipt-20261104.json` (SHA-256: `52d67ac019c55a0cf95717e07f6a3e4f4153d4927240a136de44c2084daa6c5b`).

**Latest stage state:** A3-C5 **CLOSED**; A3-C6 content, SEO, images and draft-isolation technical UAT **PASS**. Authenticated browser-layout/visual inspection remains **PENDING OWNER**, therefore no claim of full visual approval or article publication. All original Dev isolation MU hashes were rechecked unchanged; `blog_public=0`, `DISABLE_WP_CRON=1`, PR #168 Draft/unmerged.
