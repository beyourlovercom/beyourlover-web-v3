#!/usr/bin/env python3
"""Owner-approved shipping UX/country policy contract, source-only and Dev-safe."""
from pathlib import Path
import re

root = Path(__file__).resolve().parents[1] / "theme/beyourlover-v3"
headline = "Free shipping from $69 (special items excluded)"
files = {
    "cart": (root / "assets/js/cart.js").read_text(),
    "checkout": (root / "assets/js/checkout.js").read_text(),
    "drawer": (root / "assets/js/cart-drawer.js").read_text(),
    "policy": (root / "page-free-shipping.php").read_text(),
    "helper": (root / "inc/free-shipping-v2-policy.php").read_text(),
    "lookup": (root / "assets/js/free-shipping-v2.js").read_text(),
}

def check(ok: bool, label: str) -> None:
    if not ok:
        raise SystemExit("FAIL Owner shipping copy contract: " + label)

for item in ["cart", "checkout", "drawer"]:
    check(files[item].count(headline) == 1, item + ": exactly one headline")
    check("/free-shipping/" in files[item], item + ": destination policy link")

cart_render = files["cart"].split("    function render(data) {", 1)[1].split(
    "    function showError", 1
)[0]
check("shippingHeadline" in cart_render, "Cart banner even if promo API disabled")
check("var html = shippingHeadline" in cart_render, "Cart banner not stacked")
for not_allowed in ["messageFor(data)", "__meter", "byl-cart-promotion__threshold",
                    "Free shipping applied to your order."]:
    check(not_allowed not in cart_render, "Cart no duplicate " + not_allowed)
check("byl-cart-promotion__form" in cart_render and "byl-cart-promotion__remove" in cart_render,
      "Cart existing coupon apply/remove retained")

checkout_render = files["checkout"].split("  function renderPromotion() {", 1)[1].split(
    "  function showPromotionError", 1
)[0]
check("policyLink.href = '/free-shipping/'" in checkout_render,
      "Checkout same-origin destination link")
for not_allowed in ["promotionMessage(data)", "byl-promo-title", "byl-promo-hint",
                    "Free shipping unlocked"]:
    check(not_allowed not in checkout_render, "Checkout no duplicate " + not_allowed)
check("mutatePromotion(promoConfig.applyUrl" in checkout_render
      and "mutatePromotion(promoConfig.removeUrl" in checkout_render,
      "Checkout coupon apply/remove retained")

drawer_render = files["drawer"].split("  function render(cart) {", 1)[1].split(
    "  function ", 1
)[0]
m5 = files["drawer"].split("  function renderM5Progress(cart) {", 1)[1].split(
    "  function render(cart)", 1
)[0]
check("elements.delivery.replaceChildren();" in drawer_render and
      "shippingLink.href = '/free-shipping/'" in drawer_render,
      "Drawer single clickable headline")
check("elements.totals.innerHTML = subtotal +" in drawer_render,
      "Drawer real Woo subtotal and shipping totals preserved")
check("chargesNotice" not in drawer_render, "Drawer no duplicate shipping warning")
check("slot.hidden = true" in m5 and "slot.appendChild" not in m5,
      "Drawer no duplicate progress banner")

p = files["policy"]
for zone in [0, 1, 3, 4, 5]:
    check(f"['zone'=>{zone}" in p, "country policy zone "+str(zone))
check("byl_v3_fs_zone_free_terms" in p, "native Woo free tier terms")
for x in ["SAKURA", "BDSM BONDAGE KIT", "PILLOW", "$30 per pillow",
          "after coupon discounts", "Rest of the World fallback"]:
    check(x in p, "page product/country detail " + x)
check("pre-discount merchandise subtotal" not in p,
      "obsolete user-facing before-discount FAQ removed")
check("line_total" in p, "page goal uses discounted Woo lines")
helper = files["helper"]
check("WC_Shipping_Zones::get_zone(0)" in helper, "Zone0 fallback real Woo methods")
check("get_instance_option" in helper, "Woo method settings drive lookup")
check("before_coupon" in helper and "after_coupon" in helper,
      "coupon basis never guessed")
check("conditional_free" in helper and "conditional_free" in files["lookup"],
      "code-gated free method never advertised as a plain amount-only rate")

for forbidden in ["update_option(", "wp_insert_post(", "wp_create_order(",
                  "process_payment(", "wp_update_post(", "delete_option("]:
    check(forbidden not in helper, "policy lookup remains read-only")
print("PASS Owner one-line $69 shipping copy across Cart/Checkout/Drawer and five-zone policy")
