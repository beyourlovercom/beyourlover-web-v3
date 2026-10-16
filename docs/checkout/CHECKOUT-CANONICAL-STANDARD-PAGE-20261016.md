# Canonical Checkout Architecture — Standard Page Only (2026-10-16)

## Owner decision
- **Cart Drawer: RETAINED.** It remains the supported cart overlay and the Woo `/cart/` fallback stays available.
- **Checkout Drawer: RETIRED PERMANENTLY** as an active implementation. Preserve prior code, design discussions and incident evidence **in Git history / archived closed PRs only**. Do not reintroduce it through a feature flag, `?byl_checkout_drawer=1` URL, templates, overlay CSS, Woo checkout scripts, browser storage resurrection or theme asset manifests.
- **The only customer-facing checkout is the real WooCommerce `/checkout/` page**, with one native Woo Classic checkout form and Woo shipping, tax, validation, gateway, 3DS and order authority. Customer journey: **Cart → Shipping → Payment → Woo Thank You**; the progress UI must show only Cart / Shipping / Payment, not extra review/payment screens. Cart is a navigation stage, not a duplicated checkout form stage.
- Reuse applicable LELO visual polish from historical work *only after porting it cleanly to the standalone page*, without drawer classes, body scroll locks, cloned checkout fields, fake gateway options or drawer-local submission code. Mobile-first, browser-native vertical scroll, no sticky overlays blocking Woo country/state dropdowns.

## GitHub source / status
As verified at the time of the owner decision, `main` at `78301dcfc1ad9abb2409d017bada18d3472e276a` **already had no Checkout Drawer runtime file** (`assets/css/checkout-drawer.css`, `assets/js/checkout-drawer.js` etc. absent); its prior ordinary checkout had an older multi-stage UI, **not yet accepted as final 3-stage visual**. Do not interpret absence of a drawer as ordinary-checkout Owner UAT PASS.

Historical Checkout Drawer Draft PRs **#119, #120, #122, #123** were closed **without merge** on 2026-10-16. Source commits and commentary remain available in Git history, but these branches **must not be merged** as product runtime. The prior P0 scroll failure is recorded in Issue **#128**, and native functional acceptance in Issue **#125**. Their history is useful for traceability, not an active feature backlog.

## Dev runtime retirement evidence
On 2026-10-16 the owner-approved Dev-only cleanup verified actual SHA before edits, protected **7 shared theme runtime files**, archived **4 dedicated Checkout Drawer CSS/JS assets off webroot**, removed their enqueues, conditional hooks and body class, restored Cart Drawer `checkoutUrl` and footer CTA directly to Woo `wc_get_checkout_url()`, stripped any native checkout markup tied exclusively to the Drawer, and updated `theme-os.json`. The Dev original files, manifest and rollback receipt reside **off-webroot** in `~/.byl-dev-checkout-canary/retired-checkout-drawer-20261016/`. The standalone Dev checkout remains subject to populated-cart and payment-provider non-transactional UAT. No Woo customer/transaction DB updates or checkout submission were authorized for this cleanup.

**WWW not attested:** the authenticated Dev Hostinger path does not include an accessible WWW deployment; read-only public probes of `https://www.beyourlover.com` were rejected (HTTP 445), so do not claim WWW is cleaned/healthy solely from Dev inspection. Production deployment or changes require an independent inventory and release gate.

## Release/acceptance gates
1. Every active theme PHP/JS/CSS/JSON and runtime route contains **zero Checkout Drawer entrypoints, overlays, scripts, asset registrations, body scroll locks or query-dependent behaviors**. Historical documents may contain the term.
2. Cart Drawer still opens/closes, updates Woo quantities and coupons, retains native Woo totals, and its primary CTA navigates to bare `/checkout/`, without drawer query string; `/cart/` remains fallback.
3. With a populated **isolated Dev test cart**, normal `/checkout/` renders **one** Woo form, real Country/State SelectWoo, editable shipping address, Woo shipping recalculation and native payment gateways. The obsolete query `?byl_checkout_drawer=1` must behave exactly like normal `/checkout/`.
4. At M320/M390/PC1440 and physical iPhone14, verify vertical scroll before/during/after country/state selection, no oscillation, focus, keyboard/touch, dropdown visibility, fixed button not covering input and normal screen-reader order.
5. Woo gateway availability is Track B, not an excuse to fabricate payment controls. No real test charge, transaction or customer order without a separate express authorization.
6. Commit future ordinary Checkout changes as isolated source changes and verify independent exact HEAD/CI, then Dev-only canary with off-webroot backup, atomic rollback, public health and Owner Functional + Visual UAT before independent WWW authorization.

**Non-regression:** no READY device restarts; no unsolicited SMTP, mail, ERP DB, gateway, production order/payment configuration writes.
