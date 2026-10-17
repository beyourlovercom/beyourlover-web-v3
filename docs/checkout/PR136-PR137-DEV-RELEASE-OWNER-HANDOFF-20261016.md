# Checkout Shipping Canonical Reference — PR #136 + #137 Dev-only release gate

Date: 2026-10-16
Purpose: Finish Owner annotated Shipping visual: correct real Woo field order (PR #136), real two-card free-shipping eligibility (PR #137), without reverting standard Checkout or misrepresenting data.

## 0. Stop and verify exact prerequisites
- **NOT A DEPLOYMENT RECEIPT.** Source tests and candidate browser injection are not proof of live Dev installation.
- GitHub chain: main -> Draft PR #130 -> Draft PR #131 -> Draft PR #135 -> Draft PR #136 -> Draft PR #137. **No merges**.
- Theme destination ONLY: `/home/u247343654/domains/dev.beyourlover.com/public_html/wp-content/themes/beyourlover-v3/`.
- Verify WordPress `home=https://dev.beyourlover.com`, environment `staging`, stylesheet `beyourlover-v3`.
- Before changing any file, on-host SHA must match the table exactly. STOP if drifted.
- No DB write, SMTP, subscriber activation, Woo shipping config, gateway setting, real order/payment or WWW change. No READY device restart.
- OpenAI platform safety blocked source staging to a remote Windows device earlier. Do not try to defeat that check by switching agents or rewording instructions. Use only an independently authorized trusted/manual release mechanism.

## 1. Exact allowlist — only three active theme files
| Relative path | Live Dev BEFORE SHA256 | Candidate from PR #137 SOURCE SHA256 |
|---|---|---|
| `inc/checkout.php` | `63724e8be201aa8b85bf9212177d4e123e6c10f2457672912eb9d91a768eac14` | `888d13dc80a563c9b198f28100f247fe0d131d3994447e0c2fe60af617cd9eed` |
| `assets/js/checkout.js` | `5eb5dcefd5928708b518fe5b2cdddbaf2bba7605ad5549d2bfa1cb54b5ab91d2` | `ace14ec4152d508b902a2daafa8a713668fb281508821bdaa5e05367719cacdd` |
| `assets/css/checkout.css` | `7cfc5d237d7fb8d7ac9aa61681650bfe465df81df5e73060e582b60b70a5a440` | `f636f3487a78d1eb5fa8f936d16ed594a025c7be670780649f0c85d1453306f0` |

These three candidates are fetched from GitHub PR #137's exact source commit before this evidence document (`b605d28b9cf21c684cb906b6b42f0ac19af986e4`); any newer PR HEAD requires re-verification of each source SHA. Do not use Windows working-copy files unless their BYTE SHA equals the rightmost column.

**Important:** Dev `inc/checkout.php` was observed as mode **666** (group/other writable), whereas JS/CSS were 644. Treat the PHP mode as a separate hardening defect: release should preserve correct ownership and use secure mode 644 under authorized filesystem change control. Do not quietly claim permissions checked if they were not. PHP must pass `php -l` before and after release.

## 2. Backup/rollout/rollback
1. Authenticate exact Dev docroot; read-only record SHA, size, owner/mode. STOP on unexpected symlink, package difference or drift.
2. Save exact originals of all three files **outside webroot** and verify backup SHA against BEFORE values.
3. Obtain candidates by GitHub exact-commit blob/raw download, verify their SHA, PHP syntax and JS `node --check`. Any file-scope mismatch is HOLD.
4. Under an approved trusted release mechanism, replace exact three theme paths with atomic same-directory staging plus a recoverable rollback. If using Hostinger File Manager (manual overwrite), **atomicity is not established**; document this exception explicitly, do not call it atomic.
5. Verify on-host SHA for all three files; check PHP lint, file mode/owner and public CSS+JS (normal and cache-busted) SHA. Ensure no 500/fatal or duplicate Checkout Drawer assets. Keep old versions for rollback.
6. Fail closed on health mismatch: restore all three originals, confirm BACKUP SHA, note that runtime rollback occurred.

## 3. Live Dev browser acceptance (not simulated)
- Isolated guest test cart only; no checkout submit/order/payment. 390, 320, 690 and 1440px: exact field order Country, first/last, Street, Add apt, City/State, Phone, Postal, actual conditional TIN if supplied, Add organization; same keyboard order; no horizontal overflow.
- MX real amount-gated free delivery: if Woo returns only `flat_rate:7` ($20), verify second card is **disabled** and reflects Woo configured minimum $129; when Woo returns genuine free rate the disabled card disappears and real native Free shipping becomes selectable. No checkout totals ever derived from the UI card.
- US actual rates: $10 flat, Free when legitimately eligible at configured $69 minimum. Cart total and shipping fee calculated by Woo.
- Country/state popup (EC Bolívar/Azuay, DE Bavaria, US State) retains Owner-approved burgundy. Shipping -> Payment -> Shipping preserves user values; mobile footer stays Woo synced. Re-test intermittent ~2s delay separately under Issue #132.
- Do not call exact 1:1 Owner visual PASS unless physical iPhone Owner confirms.

## 4. Real feature gaps (do not fake)
- Newsletter: Andy Core consent + double opt-in routes exist but Dev API/outbound flags are OFF. Needs approved activation, actual subscribe and confirmation path. No real mail triggered by this release.
- Save address: Dev guest checkout ON, registration OFF. Guest address cannot be falsely promised to persist.
- Separate billing same-as-shipping: current native form uses billing address as the only shipping address. A genuine unchecked billing-address path needs real Woo redesign.
- Mexico RFC/TIN: native MX field absent in observed guest Woo form. Must be built/validated by backend rule before placing screenshot-like field.
- Transit-day claims or Express $15 are not configured real methods or SLAs. Do not manufacture them.

## 5. Closure
Only after Dev on-host hashes and runtime checks are clean and physical Owner screenshot comparison has passed should GitHub merge/WWW release be considered in a separate authorized integration gate. Keep Draft PRs unmerged until then.
