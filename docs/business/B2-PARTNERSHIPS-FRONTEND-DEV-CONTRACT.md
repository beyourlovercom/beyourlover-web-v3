# BeYourLover Business Partnerships — B2 Theme Frontend (Dev Preview)

- Owner-approved ChatGPT interactive frontend design: PASS on 2026-10-14.
- B2 branch adds only \`page-business.php\`, page-scoped CSS/JS, and independent offline contract test.
- Theme path: \`theme/beyourlover-v3/page-business.php\`. WordPress **Page slug \`business\`** is required to render at \`/business/\`; the theme does not automatically create or write a page.
- English public-facing B2B page; Andy Core administration remains Chinese-first.
- Canonical BeYourLover name spelling and wine-red token #9B3749, using site Poppins theme styles.
- Six cooperation programs, category/search filters, match recommender, native FAQ disclosure, program details dialog, responsive M390/PC1440.
- Application form is **preview-only**: no POST, XHR, fetch, SMTP, WP database, ERP or third-party analytics payload. Does not promise transmission. B3 must separately implement real application intake using existing Core Inquiry OS, consent and fail-closed ERP authority.
- The template **fail-closes to HTTP 404 outside exact \`dev.beyourlover.com\` and \`localdev.beyourlover.com\` hosts**. No WWW / Production exposure or activation is authorized.
- No actual products or stock levels represented; decorative hero art is an abstract illustration, not a substitute product photo.
- No change to Cart, Checkout, WooCommerce, shipping or Andy Commerce.
- Never merge/deploy without exact SHA, CI and verified Dev-only backup/rollback. Both desktop and mobile Dev Owner UAT must PASS before closure.
- Offline validation: \`php -l theme/beyourlover-v3/page-business.php\`; \`node --check theme/beyourlover-v3/assets/js/business.js\`; \`node scripts/test_business_partnerships_b2_contract.js\`.
