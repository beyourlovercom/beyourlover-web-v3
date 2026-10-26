# BYL Website V3 — Performance Release Gate V1

Status: Proposed for mandatory adoption in BYL Dev-first release contract
Effective scope: `https://dev.beyourlover.com` source-integrated releases and the later, separately approved full-site WWW cutover
Owner: Website V3 release engineering
Date: 2026-10-26
Authority: root `AGENTS.md`, alongside Native-First Development & Dual UAT V1

## Purpose and precedence

Performance is a **release and page-closure gate**, not optional visual polish. A working, attractive page is **NOT release-ready** when its tested customer-facing loading or interaction performance fails this contract. All existing native WooCommerce, Owner UAT, safety, merge, payment, exact-SHA, and separate WWW approval requirements remain in force. This document does not authorize deployment, merge, real orders, external mail, or any WWW change.

GitHub is the only canonical source. Dev is the new-site integration/UAT environment, not the legacy live WWW. Measurement MUST be from the **actual installed Dev candidate**, with its exact deployed source/asset SHA, not a static preview or unrelated GitHub main revision.

## Scope matrix — performance follows customer exposure

| Surface/change | Required performance gate | Functional/visual gate |
| --- | --- | --- |
| Customer-visible public front end (home, category, PDP, LP, blog, Cart/Checkout, account surfaces) | Mobile Lighthouse lab gate on affected templates, plus desktop sample where layout/JS materially changes | Actual Dev functional/visual UAT |
| Shared theme shell, CSS, fonts, asset loader, public Andy Core/Commerce scripts, caching/CDN | Mobile on affected representative templates **and** home + PDP + Cart + Checkout where applicable; include active Ads landing pages if shared asset is changed | Functional regression on all affected native flows |
| Checkout, Cart, pricing/shipping/payment visible frontend | Gate with safe non-mutating authentic Dev state; record checkout blockers, never invent order/payment success | Native Woo and provider-owned controls retain authority |
| Plugin `wp-admin` **backend only** with no public asset/output impact | Exempt from mobile Lighthouse score. Desktop-only UI, response latency, browser console and functional regression remain required | Desktop-only Owner UAT |
| Docs, CI-only config, backend-only PHP not changing public rendering/response | No unnecessary Lighthouse rerun; contract CI/tests still required | Risk-scaled validation |
| Full Dev-to-WWW cutover | Complete critical-template performance inventory and controlled evidence on Dev before requesting cutover | All independent cutover and Owner gates |

`Desktop-only` applies **only to Andy plugin administration**, never customer-visible carts, product pages, inquiry popups, checkout, blogs, or landing pages.

## Minimum measurement policy

1. Run against `https://dev.beyourlover.com` **after** deploying the exact approved candidate (the normal independent Dev-deploy authorization still applies). Use a real Chrome/Lighthouse installation with default mobile form-factor emulation / simulated throttling; record the Chrome, Lighthouse and throttling versions. Do not substitute source-only tests, a Pagespeed screenshot, or localhost preview for Dev evidence.
2. Measure each affected **public** URL three independent mobile runs from the same test configuration, preferably clean/incognito contexts and no authenticated admin toolbar. Evaluate the median for stability; each run must have a valid page load and report. A Dev login wall, CDN bot challenge, HTTP error, or missing report is **BLOCKED**, not PASS.
3. Mandatory **mobile lab** release budget (median of 3): **Performance >= 90/100; LCP <= 2,500 ms; FCP <= 1,800 ms; TBT <= 200 ms; CLS <= 0.10**. No individual run may have a Performance score < 85. Every budget must pass; a high composite score does not override a failed LCP or TBT.
4. Inspect a desktop 1440px browser/representative Lighthouse result for front-end release regressions. Do not claim mobile PASS from desktop Lighthouse results. Affected controls, loading/hover/focus and browser errors remain part of existing functional UAT.
5. Also record the resource waterfall, largest-contentful-paint element, request counts/transfer, cache hit/miss where visible, web fonts, third-party tags, and server response. These identify root cause, not merely the overall score. If available, audit poor INP after actual user interaction and perform checkout input-response sampling; Lighthouse's TBT is **not** a substitute for measured field INP.
6. Guard against regressions on an **equivalent baseline**: record previous matching Dev URL/template, cache/throttling configuration and exact revision. A material degradation of Performance (>= 5 points) or LCP (>= 20%) even if absolute thresholds pass requires documented investigation and approval of the reason before closure. Baseline absent is explicitly `NO BASELINE`, never a fabricated regression PASS.
7. Preserve third-party scripts necessary for actual Ads attribution, legitimate payment, required mail/lead conversion, and native Woo functionality. Optimization must not hide broken form sends, remove conversion tags without authorization, defer native checkout validation incorrectly, or break the approved Owner visuals.

## Real-user Core Web Vitals — monitoring, not fabricated instantaneous Dev PASS

For public pages with sufficient Chrome UX Report / RUM history, review p75 **LCP <= 2.5 s, INP <= 200 ms, CLS <= 0.10** and source/date/traffic coverage. A new Dev page may have no 28-day field history; record **NO FIELD DATA** and keep the mobile lab gate binding. Field history is not an immediate measurement of the just-deployed SHA; do not use it to declare a candidate PASS or automatically fail a new page with no history. Revisit after rollout.

## Coverage selection

- Small isolated public component change: affected URL(s) + any directly shared responsive variant.
- Global navigation, font, cache/CDN, common public CSS/JS or shared plugin frontend: home, representative category, PDP, Cart, Checkout, blog/content, and each active paid-campaign LP that consumes the changed bundle. Skip genuinely unaffected surfaces with a written reason.
- Full launch/cutover: all critical templates, current paid landing pages, mobile at 390px and desktop around 1440px; include genuine but safe states (e.g., empty cart vs existing safe test session) and test native user interactions.
- No external payment, real order, mail sends, unsafe Woo mutations or customer-data extraction merely to satisfy Lighthouse.

## Evidence format and promotion decision

Each applicable release or page-closure record must include:

- `target_url`, template/surface, tested time/timezone, environment, exact Git SHA and installed plugin/theme/asset hashes.
- 3 mobile Lighthouse JSON report paths or attached non-sensitive artifacts, Chrome/Lighthouse versions, throttling, cold/warm cache conditions, score/LCP/FCP/TBT/CLS **per run** and medians.
- Appropriate browser mobile + desktop screenshots/screens, Console/Network errors, LCP culprit and applicable CWV p75/source or `NO FIELD DATA`.
- Before/after baseline with same configuration or `NO BASELINE`, a list of resolved issues and any known measurable regression.
- Separate results: `PERF PASS / PERF FAIL / PERF BLOCKED / PERF NOT APPLICABLE`, existing `Functional LIVE DEV PASS`, `Owner Visual PASS`, CI and deployment status. No combined PASS when one relevant gate is missing.
- A reviewer-readable summary and exact rollback plan. Keep raw test outputs out of Git where they could contain customer URLs, cookies, PII, tokens or database snapshots.

**Hard stop:** A public candidate with `PERF FAIL` or `PERF BLOCKED` is **not release-ready**, and must not be treated as page-closed or eligible for WWW cutover. Fix and retest on the same exact candidate, or obtain an **explicit Owner exception** for the exact URL/release, specific exceeded metrics, conversion/user impact, rollback/monitor plan and expiry date. Exceptions are labeled `OWNER-EXCEPTION — NOT PERF PASS`; security/payment/native-function failures cannot be waived by a performance exception. Never silently raise budgets, omit reports, or test a different SHA to obtain green status.

## Repeatable audit procedure

The committed standard-library checker is `scripts/performance_gate.py`; it checks mobile Lighthouse JSON exported from the real Dev URLs. Example (manual / authorized test station, without extra paid services):

```sh
URL=https://dev.beyourlover.com/
npx --yes lighthouse "$URL" --only-categories=performance --output=json --output-path=/tmp/byl-mobile-1.json --chrome-flags="--headless"
npx --yes lighthouse "$URL" --only-categories=performance --output=json --output-path=/tmp/byl-mobile-2.json --chrome-flags="--headless"
npx --yes lighthouse "$URL" --only-categories=performance --output=json --output-path=/tmp/byl-mobile-3.json --chrome-flags="--headless"
python3 scripts/performance_gate.py --url "$URL" /tmp/byl-mobile-1.json /tmp/byl-mobile-2.json /tmp/byl-mobile-3.json
```

Use the installed official Lighthouse toolchain, pin version/config in the test record, and reproduce the same runs after repairs. The **offline contract CI** validates this checker and the governance references only: it does **not** run Lighthouse, touch Dev or certify a real URL. Real URL audits after Dev deployment are mandatory independently of source CI. For sites with protected or rate-limited Dev pages, resolve the legitimate access/test setup first; don't disable production safety controls to game the audit.

## Cost and rollout

Use existing GitHub, Dev infrastructure and no-cost Lighthouse CLI. Do not purchase optimization services, change hosting packages, or enable paid monitoring without separate Owner approval. This policy is prospective; it does not retroactively certify existing Dev pages. Baseline existing customer-facing templates before their next release and open measured remediation work for any failing surfaces.
