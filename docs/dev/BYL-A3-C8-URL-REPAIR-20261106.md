# A3-C8 Dev-only URL normalization and safe legacy redirects — 2026-11-06

## Scope and safety boundary

This repair was performed only on `https://dev.beyourlover.com` while WordPress environment type was `staging`.

- No WWW writes or configuration changes.
- No product, order, payment, inventory, or warehouse writes.
- No email, webhook delivery, Action Scheduler execution, WP-Cron run, or Jetpack sync.
- No change to Permalink Manager's global `old_slug_redirect` setting.
- Draft PR #168 remains unmerged; no production deployment.

## 1. Internal URL validation and normalization

The source scan covered 427 current Dev post/page records. The path validation covered 1,201 unique paths across the two source hostnames:

| Dev path result | Unique paths |
|---|---:|
| Valid response on Dev (HTTP 200, final host remains Dev) | 401 |
| Confirmed 404 | 716 |
| TLS/connection result remained unknown after low-concurrency retry | 84 |

The 401 valid paths include 396 exact path matches and five safe canonical path normalizations:
- `//` → `/`
- `/beyourlover-joy-cup-diamond-version/%C2%A0` → `/beyourlover-joy-cup-diamond-version/`
- `/blog/10-best-oral-sex-positions` → trailing-slash canonical path
- `/blog/foreplay-for-men` → trailing-slash canonical path
- `/login` → `/denglu/`

The exact HTML-attribute scan found 2,995 localdev `href` values and 549 WWW `href` values. Of those, 2,527 localdev and 468 WWW references had validated Dev targets. Four additional valid localdev `href` values were inside protected code/script/style/textarea blocks and were intentionally not changed.

**Applied result:**
- 333 posts/pages updated: 319 posts + 14 pages; 285 published, 47 drafts, 1 private.
- 2,991 `href` values converted to relative paths: 2,523 localdev + 468 WWW.
- Four core commerce pages were excluded from bulk mutation: Shop (11), Cart (12), Checkout (13), My Account (14).
- `<pre>`, `<code>`, `<script>`, `<style>`, and `<textarea>` segments were protected.
- 333/333 post-content SHA readbacks passed; all 333 `post_modified` and `post_modified_gmt` values were preserved.
- Post-repair scan found zero remaining validated absolute `href` references outside protected code blocks or excluded core pages.

**Intentionally unchanged:**
- 468 localdev and 81 WWW `href` values whose paths were 404 or unverified.
- 730 localdev `src` references and 3 localdev `data-src` references; none of the tested localdev upload paths was confirmed valid on Dev.
- Four valid hrefs inside protected code blocks.

No image URL was converted to a relative path without a confirmed Dev target.

## 2. Collision-safe old article URL redirects

The database contained 140 `_wp_old_slug` records across all content types. This repair excluded products, variations, and attachments. The published post/page subset contained 31 records across 11 distinct old slugs:

- Four old-slug groups were ambiguous duplicates (6, 7, 8, and 3 target records respectively); all four were excluded.
- Seven slugs had exactly one published post target.
- None collided with another current published post/page slug.
- None collided with a Permalink Manager custom URI value.
- All seven old paths returned 404 before repair; all seven current target URLs returned 200.

Added and installed the Dev-only allowlist MU plugin:

`dev/mu-plugins/byl-z-c8-safe-old-slug-redirects.php`

SHA-256: `e012b85742a94cc682d31f6caff72b41027f8762b06a6b904cb7612acdacc4eb`

**Runtime verification:** all 7/7 old paths now return HTTP 301 to the exact intended current Dev article URL. A query-string test also confirmed parameters are preserved. Representative ambiguous duplicate paths remain 404. Permalink Manager's global `old_slug_redirect` remains `0`; no broad redirect switch was enabled.

## 3. Backup and rollback

- A compressed, private preimage backup of all 333 affected post bodies and their original modification timestamps was created outside the webroot.
- Backup SHA-256: `fee2be14ab4bbcf05c15b9d6df1f756dd793e40c939b4753aa0dcf8546f51fc8`
- Apply receipt SHA-256: `b81bf3bceac970aab75c752608fe5df7e5e8f8ff1bfe9f6f1c8436f47a992aa6`
- Content plan SHA-256: `05fb878b973a60653982ca1e2dcce55c75d58c3b0cfa6ef005084ae4158969ab`
- A rollback script was saved outside the webroot and lint-checked, but not executed. It verifies the post-repair hashes before restoring and removes the C8 redirect plugin only if its SHA-256 still matches the deployed file.

Private evidence and backup copies are retained under the Windows BYL-A3 private evidence folder.

## 4. Post-repair safety recheck

- `pre_wp_mail`: BLOCKED.
- `DISABLE_WP_CRON=true`.
- Action Scheduler runner concurrency: `0`.
- Jetpack sync sender: `false`.
- Action Scheduler counts unchanged: 85 pending / 14 failed / 1,541 complete.
- Latest completed-action log unchanged at 2026-11-02 07:59:17 UTC.
- Published post/page `_wp_old_slug` rows remain unchanged (31).
- No product/order/payment/warehouse writes were made.

## 5. Status and remaining work

**A3-C8 URL-normalization and safe-301 subtask: PASS.** The overall C8 stage remains **PARTIAL / HOLD** because broken/unverified media references and links were intentionally left untouched, and source menu parity / SEO renderer parity are not yet established.

| Remaining work | Estimate | Gate |
|---|---:|---|
| Map and repair 730 `src` + 3 `data-src` references using attachment/R2 evidence | 3–5h | No speculative URL rewriting |
| Classify 549 remaining invalid/unverified absolute hrefs | 2–4h | Only repair after target proof |
| Rank Math renderer/config parity and WWW menu baseline | 2–4h | Requires source evidence/access |
| C7 review of 45 visible-text differences | 2–4h | Preserve V3 Cart |
| A1 Hostinger hPanel/provider-side scheduler attestation | 1–3h | Requires authorized panel/provider evidence |

A1 Dev-side isolation remains PASS; external scheduler attestation and restore proof remain HOLD. A2 import remains unauthorized.
