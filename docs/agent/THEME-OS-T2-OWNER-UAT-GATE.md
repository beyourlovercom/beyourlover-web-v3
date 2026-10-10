# BeYourLover Theme OS V1 — T2 Owner UAT gate checklist
Date: 2026-10-10. Candidate PR #69 is stacked on unmerged PR #65. This checklist is **not a deployment permit**.

## Entry gates
- [ ] T1 PR #65 independent exact-head merge approval, matching CI and GitHub main receipt. Without Owner approval, stop; PR #69 stays stacked.
- [ ] Reconcile PR #69 on then-current GitHub main without overwriting Cart Drawer PR #63, Cart CSS PR #61 or G3/G5 files; rerun Foundation CI at its exact head.
- [ ] Obtain separate Owner Dev-only runtime authorization. Read Dev versions for WP, PHP, Woo, Andy Core, Andy Commerce, Checkout mode and gateways; check Theme hashes.
- [ ] Verify G2/P13 Permit and ongoing G5 Windows 3DS state independently. If G5 in progress or permit ambiguous, STOP; do not remove, bypass or regenerate the permit.
- [ ] Back up exact Dev Theme files, record checksums and a valid code-only rollback pointer. Never revert genuine Woo orders with a stale database backup.
- [ ] For T2.5, use an independently isolated WP + Woo test instance with no real business data, mail, payment, ERP callbacks or external gateway.

## Completed code gates
- T2.0 audited nine original eager PHP requires, tenth policy include, hooks and route-scope assets.
- T2.1 retained identical ordered Bootstrap Loader.
- T2.2 retained identical asset registration callback and hook position. Cart/Checkout dependencies, Nonce and localization preserved.
- T2.3 source audit identified future catalog query and Checkout phone-policy ownership risks; neither changed here.
- T2.4 nine-route isolated PHP/CI smoke verifies handles, CSS/JS order, missing wc-checkout fallback, localized Woo Store API endpoints/nonces and absence of cross-route leakage. This is not a real WordPress browser test.

## Required UAT after entry gates
| Surface | M390 | PC1440 | Read-only or nonpayment acceptance |
|---|---|---|---|
| Home / Shell | [ ] | [ ] | Nav, header/footer, no Cart/Checkout assets globally |
| PLP / PDP | [ ] | [ ] | Woo products, stock/variations, catalog sort/limit, image gallery |
| Free Shipping / Track Order | [ ] | [ ] | Exact route alias, forms, Woo zones, responsive layout |
| Cart empty + populated | [ ] | [ ] | Native quantity, fragment refresh, coupon error, shipping, no fabricated totals |
| Checkout GET | [ ] | [ ] | Native Woo payment container and consent, shipping phone, no order submit |
| Order received read-only fixture | [ ] | [ ] | Checkout JS excluded, Woo order status not mutated |
| Pages, Post, Policies | [ ] | [ ] | Theme tokens, TOC, responsive and footer policy links |

Capture real 390 CSS px and 1440 CSS px browser screenshots, console errors, network requests and the test environment URLs. No Place Order, no declined-card, no 3DS, no retry, no real emails/payments. Those belong to the separately authorized G5 window.

## Exit criteria
All applicable functional tests PASS, verified files and hashes unchanged outside scope, G2/G5 undisturbed and Owner visual UAT PASS. Any changed Woo hooks, asset dependencies, Cart/Checkout behavior or safety controls is FAIL and triggers scoped code rollback. T2 PR main merge and WWW cutover always require separate approvals.

**Present status 2026-10-10:** T2.5 isolated real WordPress 7.1.3 / WooCommerce 11.2.1 / PHP 8.3.33 technical rehearsal **PASS** (80/80 M390 + PC1440 browser checks, 12/12 additional native Cart/Coupon/Order Received checks, 14 local screenshots, zero synthetic orders, no outbound). Evidence: `THEME-OS-T2-ISOLATED-WP-WOO-EVIDENCE.md`. T2.6 **Dev installed stack preflight + code-only deployment + Owner UAT NOT PERFORMED**; G5/P13 safety state unverified and independent Owner Dev deployment authorization still required. PR #69 remains Draft; WWW unchanged.
