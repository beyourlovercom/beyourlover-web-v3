"""G5 offline source-security readiness audit; never contacts Woo, providers or Dev.

PASS here means only the named source guards are present. It does NOT verify
3DS, issuer declines, payment-capture idempotency or external notifications.
"""
from pathlib import Path
import re

ROOT = Path(__file__).resolve().parents[1]
safety = (ROOT / "dev/mu-plugins/byl-dev-safety.php").read_text(encoding="utf-8")
checkout = (ROOT / "theme/beyourlover-v3/assets/js/checkout.js").read_text(encoding="utf-8")
form = (ROOT / "theme/beyourlover-v3/woocommerce/checkout/form-checkout.php").read_text(encoding="utf-8")

checks = {
    "dev staging host boundary": "wp_get_environment_type() !== 'staging'" in safety and "https://dev.beyourlover.com" in safety,
    "closed guest gateway list": "if (!$context) { return []; }" in safety and "woocommerce_available_payment_gateways" in safety,
    "one permitted payment rail": "return isset($gateways[$id]) ? [$id => $gateways[$id]] : [];" in safety,
    "WooPayments-only valid G2 grant": "$permit['gateway'] === 'woocommerce_payments'" in safety,
    "G2 expires in at most 30 minutes": "'expires_at' => time() + 1800" in safety,
    "synthetic identity required": "billing_email" in safety and "byl-p13@example.invalid" in safety,
    "six synthetic orders maximum": "if (count($existing) >= 6)" in safety,
    "synthetic metadata created before initial save": "woocommerce_checkout_create_order" in safety and "update_meta_data('_byl_dev_sandbox_p13', 'yes')" in safety,
    "Store API checkout guard": "rest_pre_dispatch" in safety and "byl_dev_sandbox_checkout_closed" in safety and "['status' => 403]" in safety,
    "Woo outbound synthetic webhook guard": "woocommerce_webhook_should_deliver" in safety and "str_starts_with($topic, 'order.')" in safety and "return false;" in safety,
    "standard WP mail short-circuited": "add_filter('pre_wp_mail'" in safety and "return false;" in safety,
    "native payment container retained": "paymentHost.appendChild(payment)" in checkout and "data-byl-payment-host" in form,
    "gateway required before native order action": "if (!paymentConfigured())" in checkout and "nativeButton.click()" in checkout,
    "native Woo owns final checkout submission": "document.getElementById('place_order')" in checkout and "form.addEventListener('submit'" in checkout,
    "Woo checkout error releases UI busy state": re.search(r"on\('checkout_error',\s*\(\)\s*=>\s*\{\s*submitting\s*=\s*false", checkout) is not None,
    "no custom processor confirmation in theme JS": not any(marker in checkout for marker in ("payment_intents/", "confirmCardPayment(", "wc_create_order(", "update_status(")),
}
failed = [name for name, result in checks.items() if not result]
for name, result in checks.items():
    print(("PASS" if result else "FAIL") + " " + name)
if failed:
    raise SystemExit("G5_OFFLINE_READINESS=FAIL: " + ", ".join(failed))

# The existing 6-second UI unlock does not provide authorization/attempt
# idempotency, particularly when 3DS or network responses exceed 6 seconds.
fixed_unlock = re.search(r"setTimeout\s*\(\s*\(\)\s*=>\s*\{\s*submitting\s*=\s*false;?\s*\}\s*,\s*6000\s*\)", checkout)
print("G5_05_CLIENT_SUBMIT_IDEMPOTENCY=" + ("HOLD_FIXED_6S_UNLOCK" if fixed_unlock else "UNVERIFIED_REVIEW_IMPLEMENTATION"))
print("G5_01_TO_04_PROVIDER_DECLINE_3DS_RETRY=HOLD_NO_TRANSACTIONS")
print("G5_06_INBOUND_PROVIDER_REPLAY_IDEMPOTENCY=HOLD_NOT_COVERED_BY_OUTBOUND_WEBHOOK_GUARD")
print("G5_07_PROVIDER_SIDE_NOTIFICATIONS=HOLD_NOT_COVERED_BY_WP_MAIL_GUARD")
print("G5_08_LIVE_GATE_REVOCATION=HOLD_NEEDS_READ_ONLY_DEV_VERIFICATION")
print(f"G5_OFFLINE_READINESS=PASS {len(checks)} source assertions; PROVIDER_UAT=HOLD")
