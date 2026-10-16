"""Checkout Option B static governance/source gate. No Woo transactions."""
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1]
theme=ROOT/"theme/beyourlover-v3"
files={
  "cart":(theme/"inc/cart-drawer.php").read_text(),
  "checkout":(theme/"inc/checkout.php").read_text(),
  "page":(theme/"page-checkout.php").read_text(),
  "asset":(theme/"inc/theme-os-assets.php").read_text(),
  "form":(theme/"woocommerce/checkout/form-checkout.php").read_text(),
  "script":(theme/"assets/js/checkout-drawer.js").read_text(),
  "native":(theme/"assets/js/checkout.js").read_text(),
  "css":(theme/"assets/css/checkout-drawer.css").read_text(),
  "registry":(theme/"theme-os.json").read_text(),
}
f=files
assert "byl_checkout_drawer" in f["cart"] and "add_query_arg('byl_checkout_drawer', '1'" in f["cart"]
assert "function byl_v3_checkout_drawer_enabled()" in f["checkout"]
for marker in ("dev.beyourlover.com","'staging'","is_order_received_page()","is_checkout_pay_page()","is_wc_endpoint_url('order-pay')"):
 assert marker in f["checkout"],marker
assert "add_filter('body_class'" in f["checkout"]
assert "byl_v3_checkout_drawer_enabled()" in f["page"]
assert "data-byl-checkout-drawer-root" in f["page"] and 'role="dialog"' in f["page"] and 'aria-modal="true"' in f["page"]
assert f["page"].count("do_shortcode('[woocommerce_checkout]')")==1
assert 'data-byl-checkout-drawer-scrim' in f["page"]
assert 'data-byl-checkout-drawer-close' in f["page"]
assert 'wc_get_cart_url' in f["page"] and 'href="<?php echo esc_url' in f["page"]
assert 'assets/css/checkout-drawer.css' in f["asset"] and 'assets/js/checkout-drawer.js' in f["asset"]
assert "wp_enqueue_script('byl-v3-checkout-drawer'" in f["asset"] and "['byl-v3-checkout']" in f["asset"]
assert "byl_v3_checkout_drawer_enabled()" in f["asset"]
assert 'assets/css/checkout-drawer.css' in f["registry"] and 'assets/js/checkout-drawer.js' in f["registry"]
assert 'data-byl-drawer-payment-total-amount' in f["form"]
assert 'data-byl-drawer-payment-total-details' in f["form"]
assert "paymentTotalAmount.textContent = summaryAmount.textContent" in f["script"]
assert ".byl-drawer-payment-total" in f["css"]
assert "const observer = new MutationObserver(setDrawerHeading)" in f["script"]
assert "stages.insertBefore(track" in f["script"] and "stages.insertBefore(heading" in f["script"]
assert "shell.querySelectorAll('form.checkout').length !== 1" in f["script"]
assert "root.querySelectorAll('form.checkout').length" not in f["script"]
assert "const previousUrl" in f["script"] and "url.origin !== here.origin" in f["script"]
assert "closest('[hidden], [aria-hidden=\"true\"]')" in f["script"]
assert "if (event.key !== 'Tab')" in f["script"] and "event.key === 'Escape'" in f["script"]
assert "dataset.bylSubmitting === '1'" in f["script"] and "root.dataset.bylSubmitting = submitting" in f["native"]
for bad in ("<iframe", "createElement('iframe')", "fetch(", "wc_create_order", "process_payment", "woocommerce_checkout_process", "update_option(", "payment_method ="):
 assert bad not in f["script"],bad
for name in ("cart","shipping","payment"):
 assert 'data-byl-track="'+name+'"' in f["form"]
assert f["form"].count('<form name="checkout"')==1
assert 'data-byl-step="delivery"' not in f["form"] and 'data-byl-step="review"' not in f["form"]
for scope in ("body.byl-checkout-drawer-mode", ".byl-checkout-drawer-panel",
              ".byl-checkout-drawer-close", ".byl-checkout-track", ".byl-checkout-next",
              ".byl-checkout-summary", "@media(max-width:760px)", "@media(max-width:375px)"):
 assert scope in f["css"],scope
assert 'width:min(1010px,78vw)' in f["css"],"Desktop must share Cart drawer panel width"
assert "height:100dvh" in f["css"],"Drawer edge-to-edge"
assert "position:fixed!important" in f["css"],"Mobile CTA needs one fixed component"
assert "top:auto!important;height:auto!important" in f["css"],"Fixed mobile summary cannot stretch across viewport"
assert "body.byl-checkout-drawer-mode" in f["css"]
print("CHECKOUT_DRAWER_OPTION_B_STATIC_GATE_PASS: exact owner 3-step shell, route-safe single Woo checkout, staging-only, no iframe/DB/order gateway writes")
