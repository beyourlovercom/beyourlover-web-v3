# Creator Dashboard Visual Fidelity V2 — Owner UAT packet
Date: 2026-10-17 | Target: Dev only /my-affiliate/

## Source of truth
Owner-approved 1448 × 1086 desktop reference uploaded in the design conversation.
Nine creator-v2 WebP images are direct crops from that exact reference, committed as independent files:

- creator-v2-hero-photo.webp: origin (745,56), 492x189
- creator-v2-avatar.webp: (19,84), 78x78 (decorative design portrait, not verified profile photograph)
- creator-v2-promo-product.webp: (1196,362), 200x143
- creator-v2-creative-confidence.webp: (280,944), 174x106
- creator-v2-creative-product.webp: (463,944), 130x106
- creator-v2-creative-message.webp: (600,944), 116x106
- creator-v2-creative-lifestyle.webp: (725,944), 179x106
- creator-v2-support.webp: (1190,851), 205x194
- creator-v2-sidebar-poster.webp: (15,692), 217x350

Higher-resolution source media would be required to increase crop clarity beyond the design screenshot.

## Scope and fidelity
The creator-specific header replaces the storefront header only on body.byl-affiliate-route.
At 1448px width, the main design Hero begins x258 and ends x1408; 244px sidebar, 14px gutter, 40px right margin. Hero is 189px tall; 5 KPI cards are 97px high. Section sequence matches the approved mockup. Mobile layouts at 390/414 use responsive cards with sticky creator navigation.

## Truthful data exception from the mockup
No illustrative financial amounts, coupon code EMMA10, invented payout dates, fake trends, or order numbers are displayed as actual records. Source for verified stats/refs/payouts is exclusively the existing signed read-only WWW AffiliateWP API. KPI labels: Paid Earnings, Pending Commission, Total Clicks/visits, Conversion Rate (not independently verified), Total Referred Orders. Mockup creative images are explicitly visual previews and are not represented as approved downloadable marketing materials. Unverified promotion code is marked pending; unknown payout date is not scheduled. Payout settings link points to unchanged WWW Affiliate Area.

## Validation and governance
- PHP lint, JavaScript syntax, dashboard data-isolation contracts, remote bridge tests PASS.
- Isolated Chrome preview passes at 1448 / 390 / 414: all visible images decode, no horizontal overflow.
- At 1448 Hero rect x258 width1150. Exactly 5 KPI cards rendered.
- Creative category filter, no-results state, and Referral Orders expand toggles PASS.
- Dev-only deploy uses 12-file allowlist with exact GitHub hashes, off-webroot old-file backups and rollback script.
- No WWW theme/payment/commission/order changes; no PR merge.
- Synthetic Chrome results are NOT authenticated Owner visual PASS. Require Owner desktop and iPhone 14 screenshot review.
- Platform-blocked live cross-tenant negative security tests remain unverified, not PASS.
