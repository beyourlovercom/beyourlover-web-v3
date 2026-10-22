#!/usr/bin/env python3
"""Source-only shipping FX acceptance coverage, NOT an FX conversion test.

Generate and validate the complete country-zone × YayCurrency selection ×
shipping-package matrix. All expected amounts below are USD base-currency
charges. A future vendor-verified, licensed YayCurrency Pro Dev integration
MUST compare the selected-currency Woo Cart/Checkout amounts to converted
base charges after native coupon, taxes and shipping-package hash changes.
No plugin loading, network, Woo writes, orders, or checkout submissions.
"""
from decimal import Decimal
from collections import defaultdict

ZONES = {
    0: {"minimum": Decimal("69"), "ordinary_flat": Decimal("20")},
    1: {"minimum": Decimal("0"), "ordinary_flat": Decimal("0")},
    3: {"minimum": Decimal("169"), "ordinary_flat": Decimal("30")},
    4: {"minimum": Decimal("129"), "ordinary_flat": Decimal("20")},
    5: {"minimum": Decimal("69"), "ordinary_flat": Decimal("10")},
}
CURRENCIES = ("USD", "EUR", "GBP", "AUD", "CAD")
SPECIAL_FEE_USD = Decimal("30")
PILLOW_FEE_USD = Decimal("30")


def expected_cases(zone: int, policy: dict):
    """Charge USD by *eligible discounted ordinary subtotal only*."""
    floor = policy["minimum"]
    flat = policy["ordinary_flat"]

    def ordinary(subtotal: Decimal) -> Decimal:
        return Decimal("0") if subtotal >= floor else flat

    below = floor - Decimal(".01") if floor > 0 else Decimal("20")
    at = floor if floor > 0 else Decimal("20")

    return {
        "ordinary_below": (ordinary(below), below),
        "ordinary_at_threshold": (ordinary(at), at),
        "special_one": (SPECIAL_FEE_USD, Decimal("0")),
        "special_two_same_package": (SPECIAL_FEE_USD, Decimal("0")),
        "pillow_two_units": (PILLOW_FEE_USD * 2, Decimal("0")),
        "mixed_below": (
            ordinary(Decimal("20")) + SPECIAL_FEE_USD + PILLOW_FEE_USD * 2,
            Decimal("20"),
        ),
        "mixed_qualifying": (
            ordinary(at) + SPECIAL_FEE_USD + PILLOW_FEE_USD * 2, at
        ),
    }


def main():
    matrix = []
    for zone, policy in ZONES.items():
        cases = expected_cases(zone, policy)
        for curr in CURRENCIES:
            for name, (usd_shipping, eligible_post_coupon) in cases.items():
                matrix.append({
                    "zone": zone, "currency": curr,
                    "case": name, "expected_usd_shipping": usd_shipping,
                    "eligible_usd_after_coupon": eligible_post_coupon
                })
            if zone == 0:
                matrix.append({
                    "zone": zone, "currency": curr,
                    "case": "valid_native_free_coupon_mixed",
                    "expected_usd_shipping": Decimal("90"),
                    "eligible_usd_after_coupon": Decimal("20"),
                })
    assert len(matrix) == 180, len(matrix)  # 5 zones × 7 cases × 5 currencies + 5 coupon cases
    by_currency = defaultdict(list)
    for item in matrix:
        by_currency[item["currency"]].append(item)
        assert item["expected_usd_shipping"] >= 0
        assert isinstance(item["eligible_usd_after_coupon"], Decimal)
        assert item["currency"] in CURRENCIES
        assert item["zone"] in ZONES
        assert item["case"] != "valid_native_free_coupon_mixed" or item["zone"] == 0

    assert all(len(by_currency[x]) == 36 for x in CURRENCIES)
    for currency in CURRENCIES:
        matches = {(x["zone"], x["case"]): x for x in by_currency[currency]}
        for zone in ZONES:
            assert matches[(zone, "special_one")]["expected_usd_shipping"] == 30
            assert matches[(zone, "special_two_same_package")]["expected_usd_shipping"] == 30
            assert matches[(zone, "pillow_two_units")]["expected_usd_shipping"] == 60
            assert matches[(zone, "mixed_qualifying")]["expected_usd_shipping"] == 90
            assert matches[(zone, "ordinary_at_threshold")]["expected_usd_shipping"] == 0
        assert matches[(0, "valid_native_free_coupon_mixed")]["expected_usd_shipping"] == 90

    # Never provide fabricated conversion rates. The future real Woo/Yay
    # runtime test must use actual independently verified selected currency
    # and effective checkout rate; this matrix cannot establish conversion.
    print(
        "PASS shipping FX UAT contract matrix: "
        f"{len(matrix)} source-only rows, "
        f"{len(CURRENCIES)} currencies, {len(ZONES)} zones. "
        "LIVE FX/CHECKOUT/PAYMENT STILL UNVERIFIED"
    )


if __name__ == "__main__":
    main()
