# H6.3-P — Footer Privacy URL canonical and alias contract

**Status:** Owner canonical decision **APPROVED**: `/privacy-policies/` is the only canonical legal page and `/privacy-polices/` is a legacy 301 alias. V3 Theme implementation is on Draft PR #117; no Dev or WWW deploy/merge permission has been granted. Parent #114, Homepage #76, Theme OS #66.

## Evidence
- Owner's prior literal request: `/privacy-polices/` (historical typo).
- Real live WWW and Dev Homepage Footer anchor currently resolves to **`/privacy-policies`**.
- Dev `/privacy-policies/` and `/privacy-policies` = **200**; Dev `/privacy-polices/` = **404** (direct GET).
- Dev `/terms-and-conditions/`, `/shipping-policies/`, `/cookies-policies` return HTTP 200. Do not alter these by this PR.
- WWW homepage renders 200 through browser; CLI `curl` to WWW policy pages returns edge **445**, not definitive origin 404. Test redirected routes via permitted browser/authorized owner rather than claiming absence.

## Owner-approved canonical decision (code-only at this stage)
**Canonical `/privacy-policies/`** stays the real published legal Page; **legacy alias `/privacy-polices/` → HTTP 301 to `/privacy-policies/`**. Keep existing homepage Footer visual and text as-is. This corrects the older literal path with a working backwards-compatible alias and no duplicate canonical legal content. Owner explicitly chose the correctly spelled canonical URL. Do not switch the legal Page's published slug or create a duplicate page.

## Enforcement contract
1. Redirect only the exact legacy typo path (case/trailing slash normalized by WordPress) on **GET/HEAD**; never intercept POST, REST, wp-admin, Cart, Checkout, Woo endpoints, or query-based checkout callbacks.
2. Target must be site-local current `home_url('/privacy-policies/')` with an allowlisted path; preserve HTTPS and avoid loops, open redirects, query/header token propagation.
3. Canonical, sitemap and Woo/WordPress Privacy Page settings must all agree; preserve legal content and page ownership. If the actual Page slug differs, fail closed until explicitly reviewed.
4. Use an independently reviewed route hook or verified WordPress redirect mechanism; avoid editing WordPress core, migrating legal Page content, or adding unapproved plugins.
5. Verify 200 canonical + 301 legacy alias + final 200 + a **single hop**, all existing Footer links, M390/PC1440 legal UX, SEO crawl behavior and rollback.
6. Rollback reverts only this route/alias change; it must not roll back WordPress orders, payment sessions, or newer legal text.

### Staging & Production gates
P0 read-only audit PASS. P1 Owner canonical decision PASS. P2 Theme V3 code, standalone PHP regression and Draft PR #117 IN PROGRESS. P3 exact-head independent CI and Dev-only runtime activation remain separate gated tasks. P4 WWW cutover is still T6 HOLD pending whole-site V3 Owner authorization; the Shoptimizer child on WWW is not modified by this Theme V3 patch.

**The V3 runtime-only alias source is included in Draft PR #117; it is not deployed. No legal Page data, Dev/WWW runtime, DNS, Woo/checkout/order/payment, mail or subscriber records have been modified.**

## WordPress Dev read-only identity (2026-10-14)

- Published legal Page ID **7271**, slug `privacy-policies`, status `publish`.
- WordPress `wp_page_for_privacy_policy` is **7271**, so Core's Privacy Page setting agrees with the chosen canonical. No DB or Page writes were needed.
- Alias implementation rejects mismatched Core privacy-page setting, unpublished/missing real Page, methods other than GET/HEAD, all query-bearing requests, percent-encoded lookalikes, and unrelated Cart/Checkout/REST paths. It never redirects from WordPress CLI, admin, AJAX or cron. The planned 301 requires a V3 theme and is not a standalone patch for WWW Shoptimizer.
