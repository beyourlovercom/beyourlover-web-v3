from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
C2 = (ROOT / "docs/C2-CHECKOUT-SHIPPING-PROMOTION-FINAL-FLOW.md").read_text(encoding="utf-8")
C3 = (ROOT / "docs/C3-CHECKOUT-SHIPPING-PROMOTION-IMPLEMENTATION-CONTRACT.md").read_text(encoding="utf-8")
HTML = (ROOT / "docs/demos/c2-checkout-shipping-promotion-flow.html").read_text(encoding="utf-8")

for marker in (
    "Add $10 more for free shipping",
    "Free shipping unlocked",
    "EMMA10 applied",
    "Free shipping available",
    "Replace your current code with BYL49 to unlock free shipping.",
    "Shipping Address",
    "Delivery Method",
    "Payment",
    "Review",
    "Place Order",
    "Phone number required for delivery",
    "Your number is used only to help the carrier deliver your order. We do not use it for marketing.",
    "Normal paid shipping remains",
    "selected rate and amount once known",
    "Never show `FREE`",
):
    if marker not in C2:
        raise SystemExit(f"Missing C2 marker: {marker}")

for marker in (
    "get_displayed_subtotal",
    "woocommerce_get_shop_coupon_data",
    "virtual zero-discount",
    "has_persisted_settings()",
    "is_runtime_active()",
    "BYL49",
    "one total active promo slot",
    "free_shipping: false",
    "woocommerce_shipping_free_shipping_is_available",
    "get_zone_matching_package($package)",
    "return the original `$is_available` untouched",
    "Validate the candidate code first",
    "AffiliateWP referral attribution that is not itself a WooCommerce coupon remains separate",
    "ignore_discounts=no",
    "Production is unauthorized",
):
    if marker not in C3:
        raise SystemExit(f"Missing C3 marker: {marker}")

if "<script" in HTML.lower() or "https://" in HTML or "http://" in HTML:
    raise SystemExit("C2 demo must remain static and self-contained")

if '<div class="shipping"><span>Shipping</span><b>Available</b></div>' in HTML:
    raise SystemExit("C2 shipping rows must not use generic available copy")

if "Require-code + EMMA10" in HTML:
    emma_state = HTML.split("Require-code + EMMA10", 1)[1].split("</article>", 1)[0]
    if "FREE" in emma_state or "<b>Available</b>" in emma_state:
        raise SystemExit("Require-code + EMMA10 demo state must not use free or generic available shipping copy")

print("Checkout shipping promotion docs contract check PASS")
