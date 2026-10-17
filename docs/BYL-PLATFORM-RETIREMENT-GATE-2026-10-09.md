# BYL Platform → Andy Commerce Dev Retirement Gate

Approved: Owner, 2026-10-09.
Canonical plugin source: `beyourlovercom/YBY-Core`, `packages/andy-commerce/`.
Code candidate: Andy Commerce 1.0.1, PR #67.
Current source baseline: `beyourlovercom/beyourlover-web-v3` main.

## Sequence and hard stops

1. Confirm Andy Core 1.9.0+, Andy Commerce, BYL Platform and Woo versions on Dev (read-only).
2. Snapshot and back up two plugin folders, active plugin set, plugin options,
   `byl_shipping_promotion_settings` and
   `andy_commerce_shipping_promotion_settings_v1`, without exposing customer data.
3. Confirm no concurrent Cart/Checkout/G5/Templates deployment or editing conflict.
4. Deploy Andy Commerce 1.0.1 on Dev only, initially **without disabling** legacy plugin.
5. Nontransactional smoke Cart, Checkout, BYL49, automatic/require-code modes,
   zone overrides, multi-coupon rejection and both Store API and classic Woo hooks.
6. Confirm new Commerce Runtime owns shipping/coupon hooks on a controlled Dev
   deactivation of BYL Platform; check logs, cart totals and coupon persistence.
7. Check Dev Safety MU plugin, Store API Checkout 403 negative gate, no orders
   created, no mail/webhooks, no WWW changes.
8. Owner UAT PASS. Only then remove legacy runtime from the final release package,
   update V3 migration manifest/tests, and close the BYL Platform retirement.

## Compatibility and rollback

- Legacy `V3_DB_001` was a no-op baseline. Commerce reuses its applied ledger.
- No delete/reset/overwrite of promotion options. Commerce can read validated
  original BYL Platform configuration without a write.
- BYL `byl-shipping-promotion` Store API namespace must not change until
  Cart/Checkout are migrated and independently tested.
- Rollback: restore backed-up Commerce, re-enable the old BYL Platform package,
  clear cache and run read-only smoke. No migrations, no business data changes.
- WWW Production remains frozen until a separately approved full cutover.

## Architecture guard

`python scripts/check_first_party_plugin_architecture.py` rejects any new
first-party plugin under `platform/` and rejects changes to the frozen
BYL Platform source. Removing the entire legacy directory after Dev PASS is
permitted as the final retirement step.
