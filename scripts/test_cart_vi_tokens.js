'use strict';
/** Verify Cart V2 styles consume canonical BYL V3 tokens, not a cross-brand VI. */
const fs = require('fs');
const path = require('path');
const assert = require('assert');
const theme = path.resolve(__dirname, '../theme/beyourlover-v3');
const tokens = fs.readFileSync(path.join(theme, 'assets/css/tokens.css'), 'utf8');
const focus = fs.readFileSync(path.join(theme, 'assets/css/cart-focus.css'), 'utf8');
const cart = fs.readFileSync(path.join(theme, 'assets/css/cart.css'), 'utf8');
for (const literal of [
  "--byl-font:'Poppins'",
  '--byl-brand:#9b3749',
  '--byl-brand-hover:#7f2d3c',
  '--byl-brand-soft:#f7ecef',
  '--byl-ink:#161316',
  '--byl-success:#247a52'
]) {
  assert(tokens.includes(literal), 'canonical token missing: ' + literal);
}
assert(focus.includes('font-family:var(--byl-font)'), 'Cart typography must inherit Poppins');
assert(focus.includes('background:var(--byl-brand)'), 'Cart CTAs must use BYL burgundy');
assert(focus.includes('background:var(--byl-brand-hover)'), 'Cart hover must use canonical hover');
assert(focus.includes('border-radius:var(--byl-radius-sm)'), 'Cart controls must use canonical radius');
assert(focus.includes('color:var(--byl-brand)'), 'Cart links must use brand color');
assert(!/#[0-9a-f]{3,8}(?![a-z0-9])/i.test(focus), 'Cart Focus CSS must not hard-code color values');
assert(!/\bgreen\b|PingFang|#075[a-f0-9]{3}|#064b3a/i.test(focus), 'legacy green/other-brand VI not allowed');
assert(cart.includes('background: var(--byl-brand)'), 'Legacy cart coupon component must inherit BYL brand');
assert(!/CanvasText|#a51d2d/.test(cart), 'generic system or legacy danger tokens not allowed');
const fn = fs.readFileSync(path.join(theme,'functions.php'),'utf8');
assert(fn.includes("'byl-v3-tokens'") && fn.includes("'byl-v3-cart-focus'"), 'Theme must load tokens + Cart VI');
console.log('PASS cart-vi-canonical — Poppins + Burgundy, semantic tokens, no green-brand overrides');
