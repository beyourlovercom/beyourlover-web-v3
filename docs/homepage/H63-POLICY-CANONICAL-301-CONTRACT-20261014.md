# H6.3-P — Footer Privacy URL canonical and alias contract

**Status:** Read-only Dev/browser URL inventory and Draft contract, not a redirect or page-content deployment. Owner canonical confirmation pending. Parent #114, Homepage #76, Theme OS #66.

## Evidence
- Owner's prior literal request: `/privacy-polices/` (historical typo).
- Real live WWW and Dev Homepage Footer anchor currently resolves to **`/privacy-policies`**.
- Dev `/privacy-policies/` and `/privacy-policies` = **200**; Dev `/privacy-polices/` = **404** (direct GET).
- Dev `/terms-and-conditions/`, `/shipping-policies/`, `/cookies-policies` return HTTP 200. Do not alter these by this PR.
- WWW homepage renders 200 through browser; CLI `curl` to WWW policy pages returns edge **445**, not definitive origin 404. Test redirected routes via permitted browser/authorized owner rather than claiming absence.

## Preferred proposal (not yet approved)
**Canonical `/privacy-policies/`** stays the real published legal Page; **legacy alias `/privacy-polices/` → HTTP 301 to `/privacy-policies/`**. Keep existing homepage Footer visual and text as-is. This corrects the older literal path with a working backwards-compatible alias and no duplicate canonical legal content. If Owner instead explicitly requires misspelled `/privacy-polices/` to be the *canonical* visible path, stop and redesign route, canonical, sitemap and 301 direction under separate approval. Never publish two independent copies of the policy.

## Enforcement contract
1. Redirect only the exact legacy typo path (case/trailing slash normalized by WordPress) on **GET/HEAD**; never intercept POST, REST, wp-admin, Cart, Checkout, Woo endpoints, or query-based checkout callbacks.
2. Target must be site-local current `home_url('/privacy-policies/')` with an allowlisted path; preserve HTTPS and avoid loops, open redirects, query/header token propagation.
3. Canonical, sitemap and Woo/WordPress Privacy Page settings must all agree; preserve legal content and page ownership. If the actual Page slug differs, fail closed until explicitly reviewed.
4. Use an independently reviewed route hook or verified WordPress redirect mechanism; avoid editing WordPress core, migrating legal Page content, or adding unapproved plugins.
5. Verify 200 canonical + 301 legacy alias + final 200 + a **single hop**, all existing Footer links, M390/PC1440 legal UX, SEO crawl behavior and rollback.
6. Rollback reverts only this route/alias change; it must not roll back WordPress orders, payment sessions, or newer legal text.

### Staging & Production gates
P0 audit done. P1 decide canonical Owner. P2 code/Draft PR (not automatically opened from this contract). P3 synthetic and Dev-only read-only redirect fixture UAT followed by separately authorized Dev deploy if necessary. P4 WWW cutover under T6 Owner exact-release authorization only.

**No runtime/code, Dev DB/legal-page content, WWW/DNS, PHP template or payment changes are made by this draft.**
