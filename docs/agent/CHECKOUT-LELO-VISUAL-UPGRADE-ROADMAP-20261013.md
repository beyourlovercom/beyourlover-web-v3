# Checkout LELO Visual Upgrade — GitHub Roadmap

> STATUS: PLANNED / L0 PENDING · 2026-10-13
> OWNER DIRECTIVE: formalize LELO-inspired Checkout visual redesign without replacing WooCommerce/G3/G5 and without bypassing cart/checkout integration gate.
> PRIMARY TRACKER: [Issue #89](https://github.com/beyourlovercom/beyourlover-web-v3/issues/89)
> TARGET: `beyourlovercom/beyourlover-web-v3` `main` (merge only with explicit Owner approval); **this document is planning-only and is proposed via Draft PR**.

## 0. Start here: what is already done vs not done

**Already exists (reuse; don't redevelop):**

- [PR #9](https://github.com/beyourlovercom/beyourlover-web-v3/pull/9) / [PR #10](https://github.com/beyourlovercom/beyourlover-web-v3/pull/10): Apple progressive Checkout V2, one classic Woo `/checkout/`, `Shipping Address → Delivery Method → Payment → Review`, Woo-authoritative steps, responsive sticky CTA and summary.
- [PR #37](https://github.com/beyourlovercom/beyourlover-web-v3/pull/37): accessible Order Summary mobile/desktop state toggle, Owner PASS and merged.
- [PR #39](https://github.com/beyourlovercom/beyourlover-web-v3/pull/39): Dev REST Store API Checkout fail-closed guard, merged, Dev-only safety UAT PASS.
- [PR #40](https://github.com/beyourlovercom/beyourlover-web-v3/pull/40): native Terms/Privacy consent placement. Recheck latest source and Dev UI before designing that area; do not duplicate ownership.
- Cart V2/Theme OS baseline [PR #79](https://github.com/beyourlovercom/beyourlover-web-v3/pull/79) and G5 submissions/3DS [PR #71](https://github.com/beyourlovercom/beyourlover-web-v3/pull/71) have independent owners and existing evidence; do not overwrite them.

**Not yet evidenced:** a specific approved LELO Checkout screenshot/design, Owner Visual PASS for this LELO-inspired direction, LELO implementation on Dev, and Checkout Owner Final UAT PASS for this visual upgrade. Existing historical Apple Checkout V2 PASS is *not* LELO Final PASS.

**Integration** [Issue #87](https://github.com/beyourlovercom/beyourlover-web-v3/issues/87) status HOLD; start only if Cart Owner Final PASS AND Checkout Owner Final PASS AND G5 evidence current/valid. G5 historical stage evidence is not full commerce journey evidence.

## 1. Ownership and constraints

| Owned by this line | Keep separate / consume without rewriting |
| --- | --- |
| Checkout page composition, visual tokens, hierarchy, responsive summary, spacing, step progress visual, accessible error/focus/validation presentation | Woo order/payment native authority, Card/3DS/retry logic G3/G5, Dev G2 gate, native Store API safety, provider availability, Woo taxes/shipping, Andy Commerce promotions, Cart V2, Thank You, Order Pay, ERP, AfterShip, global notices |

- Canonical BYL identity and theme tokens, not literal LELO branding/assets. Use visual inspiration only and require Owner-selected legitimate reference screenshot/URL for L0.
- Mobile-first UAT targets M390 and M320, plus M375, M430, tablet and PC1440; preserve fixed/safe-area single CTA only where validated.
- Never assume `LELO-style` specifies exact colors/typography/columns without Owner reference. Do not hard-code product/price/shipping/discount/payment/notice states.
- Preserve shipping_phone required with delivery-only wording, prevent billing phone duplication, respect native address autocomplete and required-field validation, live Woo selected shipping methods/totals, single promotion slot, Terms/Privacy consent, optional opt-in consent.
- Remain within existing BYL Theme OS modules; no unapproved new plugin, Woo Checkout replacement framework, copied third-party templates or direct WWW changes.

## 2. Milestone schedule (no date commitment until reference agreed)

| Stage | Deliverable | Exit gate | Status |
| --- | --- | --- | --- |
| L0 Source + visual reference audit | `main`/Dev visual baseline, LELO reference capture, Theme OS/Cart/G5/Notice dependency map | Owner confirms reference and precise inspiration boundary | TODO |
| L1 Design | BYL-branded PC1440 & M390 layouts, comparison with current Checkout | **Owner Visual PASS**; no runtime changes | TODO |
| L2 UX contract | Four-step states, responsive summary, fixed CTA, errors/3DS loading, coupons/consent/shipping UI spec | Owner interaction acceptance, technical safety review | TODO |
| L3 Scoped implementation | Small Theme OS Checkout-only Draft PR(s), tests, CI evidence | Exact-head CI SUCCESS and independently authorized Dev rollout; no automatic merge | TODO |
| L4 Real Dev UAT | Deployed SHA, rollback, M320/M390/PC1440 screenshots, address/delivery/payment/review nontransaction tests, native Woo totals/ARIA | UAT matrix PASS; zero unauthorized payments/orders; record exceptions | TODO |
| L5 Owner Final PASS | Final tested Dev exact SHA, screenshots and Owner acceptance | **Checkout Owner Final UAT PASS**, independent of Cart/G5 | TODO |
| L6 Integration handoff | Update [Issue #87](https://github.com/beyourlovercom/beyourlover-web-v3/issues/87) dependencies and evidence | HOLD remains until Cart PASS + Checkout PASS + G5 freshness | HOLD |

### L0: baseline capture (no writes)

- [ ] Verify latest `main`, PRs/issues still active, deployed Dev Theme OS Checkout asset/template hashes; locate visual regressions and parallel branches.
- [ ] Capture **actual** current Dev M390 and PC1440 Checkout screenshots and core DOM/step states without creating an order.
- [ ] Link exact LELO inspiration references and record the Owner's boundaries: layout, spacing, typography, ordering, imagery constraints and preferred sticky CTA.
- [ ] Establish file ownership collision table for Cart, G5, native Checkout, Checkout C2 notices, Theme OS bootstrap and Thank You.

### L1: design/Owner visual checkpoint

- [ ] Produce aligned Desktop 1440 and Mobile 390 mockups; smaller M320 overflow sanity check.
- [ ] Preserve 4-step semantics, address form, real shipping method, one Woo card gateway, Summary, coupon, consent, responsive focus/error states.
- [ ] Iterate until Owner explicitly says Visual PASS; an assistant mock alone does not mean accepted design.

### L2: UI contract

- [ ] Define address empty/valid/autocomplete/validation error; Delivery selected-rate and totals refresh; Payment provider absent/loaded/3DS processing; Review Terms/Privacy state and error handling.
- [ ] Define collapsed/open summary at mobile/desktop including `aria-expanded` and keyboard behavior; single CTA footer safe area; optional newsletter/review consent never prechecked.
- [ ] Preserve native Woo error/notice outputs; do not scope global notice CSS to checkout without owner agreement; no fake order totals, payment success or free-shipping messages.

### L3: code only on reviewed branch

- [ ] Derive exact active Theme OS files from `main` and coordinate with G5/Cart owners before changes; avoid parallel checkout.js conflict.
- [ ] Small scoped CSS/template/JS changes, reuse existing build/CI/test system; explicit screenshots/source tests for card+3DS lock and step behaviors.
- [ ] Draft PR, exact-head CI PASS, safety/regression code reviews, rollback and limited Dev-only deployment authorization before changing runtime.

### L4: Dev-only real UAT

- [ ] PC1440, M390 and M320 screenshots of actual deployed Dev runtime, plus responsive crosschecks; content legible and no horizontal overflow.
- [ ] Address→Delivery→Payment→Review nontransaction navigation and live shipping/coupon/tax summary presentation; keep G2 closed for anonymous test.
- [ ] Payment UI visually reviewed only in *properly* authorized customer session; no Sandbox charges or order creates without separate authorized gate.
- [ ] Keyboard/focus/ARIA, invalid addresses, alternative shipping, legal consent, 3DS pending lock mocked offline, high-latency/responsive checks.
- [ ] Record Foundation CI, exact SHA, Dev file hashes, rollback pointer and screenshots to Issue #89.

### L5/L6: Owner closure and integration gate

- [ ] Explicit Owner Checkout Final UAT PASS (not a mere earlier Apple-style historical PASS).
- [ ] Handoff to Issue #87 with fresh evidence and owners; keep gate HOLD until Cart V2 Owner Final PASS + Checkout Owner Final PASS + G5 evidence current/valid.
- [ ] Full Dev E2E testing/real Sandbox orders/WWW deploy remain separately approved; no unconditional automatic rollout.

## 3. Known blockers and separate operational safety

- **Reference pending:** Owner must provide/confirm specific LELO Checkout design screens or URLs before finalizing high-fidelity visual design.
- **G5 freshness:** current G5 stage evidence and known provider replay, concurrency, ERP/AfterShip residual exceptions are tracked in [Issue #72](https://github.com/beyourlovercom/beyourlover-web-v3/issues/72); do not reopen test cards/3DS to get design screenshots.
- **Production:** No WWW deployment, no production gateway/DB writes, no manual Woo order edits, no ERP/AfterShip/email changes, and no service or READY device restart as part of visual planning.
- **Theme governance:** GitHub is SSOT; Notion may mirror progress, but it is not the merge or deployment source.

## 4. Progress reporting

Update **Issue #89** with the stage, exact code commit and browser viewport screenshots, CI run, Dev tested asset hash, scope and Owner PASS/FAIL. Attach links to each stage PR. Use one final Roadmap sentence at the end of handoff/status replies. Do not declare `L6 PASS` or close Issue #87 merely because a code PR is merged.

### Immediate next instruction for the independent Checkout branch

> Read Issue #89 and this Roadmap. Begin L0 by read-only source/runtime audit; get LELO reference alignment; produce PC1440 and M390 designs before implementation. Do not touch G5/Cart/Thank You production lanes. Stop at Owner Visual PASS and later independent Dev-only deployment/Owner Final UAT gates. No unsolicited main merges or WWW changes.
