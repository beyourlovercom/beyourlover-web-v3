# BYL Notification UX V1 — N5 Dev-only Canary evidence
Date: 2026-10-12 (Asia/Shanghai). Environment: dev.beyourlover.com, static opt-in mock only.

## Source and CI
- GitHub main observed exact: `1325969335a6fd656e47b96d078b312242a5420e`, unchanged during N5.
- Notification source deployment exact HEAD: `14436c003f796d349fed6a3e94ac55bf6cdf9488`.
- [Foundation CI #38193084449](https://github.com/beyourlovercom/beyourlover-web-v3/actions/runs/38193084449): SUCCESS (PHP lint; isolated Node/Theme OS/ZIP; G5/Newsletter/Checkout regressions).
- Cart Draft PR #63 remained unmerged and independent; newer head observed: `1fd92ae171eaa4834697e4c02c14b68fe9e843ea`.
- Draft PR #73 remains unmerged. No GitHub main merge.

## Canary endpoint
`https://dev.beyourlover.com/wp-content/themes/beyourlover-v3/notification-preview-v1.php?byl_notification_preview=1`
- GET with exact `dev.beyourlover.com` host and flag = 1: HTTP 200 (static HTML).
- Missing flag: HTTP 404, no UI.
- PHP guard disallows non-GET, unexpected host and absent opt-in. Exact preview source lacks WordPress bootstrap and live Woo access.
- Response CSP: `default-src 'none'`, `connect-src 'none'`, `form-action 'none'`, self-hosted scripts/styles/fonts, `no-store`, `noindex`.
- The initial visual preview mock badge overlapped the toast; removed in commit `14436c...`; repeated screenshot validation below.

## Isolation and Dev safety
- Existing Windows SSH alias `byl-dev-hostinger` (non-Production Dev root): `/home/u247343654/domains/dev.beyourlover.com/public_html`.
- Hostinger WP read-only: Dev `siteurl` and `home` both `https://dev.beyourlover.com`, active theme `beyourlover-v3`; no WordPress checkout/action invoked.
- G2 gate file `/home/u247343654/byl-v3-staging/p13-sandbox-gate.json`: ABSENT before and after deploy; no permission changes.
- Theme files changed at Dev: **only five newly added** files (`notification-preview-v1.php`, `assets/js/notification.js`, `assets/css/notification.css`, `assets/js/notification-preview.js`, `assets/css/notification-preview.css`); later one PHP-only mock label fix with a prior-file backup.
- Dev `functions.php`, `theme-os.json` and `wp-content/mu-plugins/byl-dev-safety.php`: checked **SHA256 unchanged** after upload.
- Private backup/rollback baseline: `/home/u247343654/byl-v3-staging/notification-v1-n5-fd0cc44/` with mode 0700 directory / 0600 receipts. Original five assets all absent, so rollback is removing only those five exact new files after matching their SHA; prior version of preview PHP separately saved.
- Critical isolation limit: Dev-only WP identity/site path verified; WWW backend DB credentials were not accessed or compared. This is a static DB-free mock only, **not** permission for live Woo mutation or Checkout/Production data access. N6 requires stricter Dev/Production DB independence verification first.

## Exact deployed file SHA256
| File (under Dev `wp-content/themes/beyourlover-v3`) | SHA256 |
|---|---|
| `notification-preview-v1.php` | `7827d7a7d7e250fae6f7bc302e2e14e61c84e8c46e59fd5a12cfc58d09ba2ad6` |
| `assets/js/notification.js` | `3c9602d4a7fc78e3eaaf3067ea14e65426972050c93a608e7ec98cec80dcc705` |
| `assets/css/notification.css` | `98e1a8bbc6729edf672598dae74f8377424f20a548d101dfbe8243946e51800d` |
| `assets/js/notification-preview.js` | `64dc76a8ded77c47cce7a8897e3f052a06140f97518e0ae808c460b0489b8888` |
| `assets/css/notification-preview.css` | `770b0604e910ae9c76283eee0d394f90726ac36cc7427152abecaf80f0884d5c` |

## Chrome viewport screenshot UAT (real Dev endpoint, separate headless Chrome profile; automated technical evidence)
| Case | Viewport & overflow | Toast / status | Evidence |
|---|---|---|---|
| PC success | innerWidth=1440, scrollWidth=1440 | 1 toast, 40px high, `Added to your cart` | [PC1440](./evidence/notification-n5-pc1440.png) |
| M390 success | innerWidth=390, scrollWidth=390 | 1 toast, right x=208.06 / right edge 378, 40px high, footer fully in viewport | [M390 success](./evidence/notification-n5-m390.png) |
| M390 error | innerWidth=390, scrollWidth=390 | no toast; persistent editable coupon error | [M390 error](./evidence/notification-n5-m390-error.png) |
| M360 success | innerWidth=360, scrollWidth=360 | 1 toast, right edge 348, footer fully in viewport | [M360](./evidence/notification-n5-m360.png) |

No order, real product, Woo Cart POST, Checkout POST, payment, email, ERP sync, WWW deploy, database migration, user account or shipping operation. Browser tests used synthetic mock action only.

## N5 result and N6 hold
**N5 static Dev preview: TECHNICAL PASS**. NOT an Owner iPhone Safari PASS, real Woo Cart integration PASS, or Production release.
N6 requires explicit Cart PR #63 dependency/combination authorization, new exact SHA/CI audit, Dev/Prod DB isolation proof, isolated guest cart, strict request/response truth and rollback. Owner N7/UAT remains pending.
