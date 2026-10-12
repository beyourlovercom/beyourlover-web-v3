# BYL Notification UX V1 — N5.1 Unified Message Lab / Owner Preview Evidence
Date: 2026-10-12. **Dev-only isolated visual laboratory**, not integration with real Woo/WordPress notices.

## Explicit Owner scope
Owner requested "推进所有消息的改版，统一到刚才的预览页面做测试". Existing N5 static Dev preview extended with separate, reusable semantic surfaces; no change to live `/cart/`, Woo cart authority, checkout or WordPress admin.

## Exact GitHub source and CI
- Repository: `beyourlovercom/beyourlover-web-v3`.
- GitHub main baseline: `1325969335a6fd656e47b96d078b312242a5420e`, unchanged at start of work. Recheck latest main prior to N6.
- Notification branch functional DEV source exact commit: `89d4a011336acec77aaf7db28d7f8b4e4c33b669`.
- Foundation CI: [run #38195307993](https://github.com/beyourlovercom/beyourlover-web-v3/actions/runs/38195307993) **SUCCESS** (Theme OS, ZIP reproducibility, Checkout/G5/Newsletter regressions, Notification Node and PHP contract).
- PR #73 remains **Draft / unmerged**. PR #63 Cart remains separate; no cherry-pick/merge. No WWW deployment.
- Core new files: `assets/css/notification-surfaces.css`, `assets/js/notification-surfaces.js`. Preview changes: `notification-preview-v1.php`, `assets/css/notification-preview.css`, `assets/js/notification-preview.js`. Registered unique Theme OS ownership *only on GitHub feature branch*; Dev theme manifest unchanged.

## Dev-only preview endpoint
[Open Message Lab](https://dev.beyourlover.com/wp-content/themes/beyourlover-v3/notification-preview-v1.php?byl_notification_preview=1)
- `GET` on exact `dev.beyourlover.com` + explicit `byl_notification_preview=1` allowed: HTTP 200; absent flag: HTTP 404.
- PHP has **no WordPress bootstrap**, Store API, WooCommerce mutation, session, payment, database write, outbound email or network call.
- CSP: `connect-src 'none'`, `form-action 'none'`, assets self-hosted, `noindex`, `no-store`.
- Safely distinct surface UX: confirmed-action Toast (short), persistent success/error/warning/info Notice, durable inline field error, Woo-authoritative-*mock* shipping status, empty cart/wishlist/search, confirm Dialog.
- Woo classic 3 native type semantics remain distinct from WordPress Admin 4 native notice types; BYL warning is a proposed custom visual variant only. No global interception of Woo notices.
- 'Free shipping available' and 'Free shipping applied' are **fake demonstration labels only** and are distinct in the mock; real shipping rates are not queried.
- Recently viewed content is omitted when no verified source exists, never invented or hard-coded as real customer products.
- All demo buttons are synthetic; `Explore products` does not navigate to shop or touch Woo data.

## Dev backup and files
- Existing authorized Hostinger Dev SSH alias on Windows: `byl-dev-hostinger`.
- Dev theme root: `/home/u247343654/domains/dev.beyourlover.com/public_html/wp-content/themes/beyourlover-v3`.
- Private off-webroot backups: `/home/u247343654/byl-v3-staging/notification-v1-n51-8349e9e/`, 0700 folder, file backups/receipts mode 0600. Earlier files saved before replacement, newly introduced semantic CSS/JS absent before deploy.
- Original Dev `functions.php`, `theme-os.json` and `wp-content/mu-plugins/byl-dev-safety.php` all reverified unchanged by `sha256sum -c` after final update. G2 runtime permit file remains **ABSENT**.
- Rollback: validate pre-update backup checksums; restore the exact three replaced preview files from the private backup; remove only the two N5.1-owned new semantic surface assets; leave existing `notification.js`, `notification.css`, Theme OS manifest, Cart/G2/G5 files untouched.

### Exact final deployed SHA256
| Dev asset | SHA256 |
|---|---|
| `notification-preview-v1.php` | `0d01e2e8c7cc2530adc30616c94514581eb5bdd3d447eec793c4919864409959` |
| `assets/css/notification-preview.css` | `1f9eb85e085bbf35735247a7610e8d161f84cbab0e5f4fe8304c8ec813aba9ef` |
| `assets/js/notification-preview.js` | `0eac5df46ca11b9343be432900ceec9c19ad468c7245a87a250d0368a6a0b3d7` |
| `assets/css/notification-surfaces.css` | `a78592c6cbd4bd472df34dec7ed4ec21146566385c1b8bbb83e239a8e3088c9e` |
| `assets/js/notification-surfaces.js` | `729c0182922db1148398402e7a7fdb1a6b07054159f1898ff06770e356be9b77` |

## Real browser technical verification (separate temporary Chrome headless profile, PC & H5)
| Scenario | Observed result | Evidence |
|---|---|---|
| PC1440, success/notice | viewport 1440, scrollWidth 1440, four notices, successful confirmed Toast | [PC1440 Notices](./evidence/notification-n51-pc1440-notices.png) |
| M390, short Toast | viewport 390, scrollWidth 390, `Added to your cart`, no header-label collision after bug fix | [M390 Toast](./evidence/notification-n51-m390-top.png) |
| M390, 4 Woo/BYL semantics | Four persistent independent styles (success, error, warning, info) and retry button | [M390 Notices](./evidence/notification-n51-m390-notices.png) |
| M390, persistent field error | `role=alert`, input `aria-invalid=true`, error stays visible | [M390 Form](./evidence/notification-n51-m390-form-error.png) |
| M390, empty Cart | 'Your cart is empty', single Explore CTA, zero fabricated recommendations | [M390 Empty Cart](./evidence/notification-n51-m390-empty.png) |
| M390, dialog | Bottom sheet; no fake real-cart changes | [M390 Dialog](./evidence/notification-n51-m390-dialog.png) |
| M360, notices | viewport 360, scrollWidth 360, long messages wrap, no horizontal overflow | [M360 Notices](./evidence/notification-n51-m360-notices.png) |

Additional checked browser interactions:
- Empty wishlist toggle -> `Nothing saved yet`, `aria-pressed=true`, no fake recent-view content; search -> `No results found`.
- Form error `This promo code has expired...` remains visible with invalid input indicator until corrected.
- Shipping available vs applied state show clearly distinct copy; *mock only*.
- Native dialog open focuses Cancel; close button and Escape both restore focus to the opener after final focus fix.
- Four notice types in DOM. No horizontal overflow at all tested sizes. No real browser payment/order test.
- First UI review found header metadata under Toast; removed from PHP in follow-up fix.
- First dialog interaction showed unreliable focus on programmatic close; explicitly patched opener capture and synchronous focus restore, retested PASS.

## Status / forward gates
**N5.1 Unified Message Lab DEV TECHNICAL PASS**, not Owner UAT PASS and not a live Woo source integration. N6 real Cart integration requires independently explicit Owner approval, exact Cart PR #63 HEAD/CI, Dev/Production database separation proof, isolated guest cart authority, rollback and no Woo payment/order mutations. N7 requires Owner visual/touch evaluation on real iPhone and desktop; N8 closure follows.
