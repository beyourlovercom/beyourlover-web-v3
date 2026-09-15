# G2 Plugin Runtime Allowlist

Status: **Foundation policy — applies to V3 local/staging/runtime composition.**

## Rule

V3 does not inherit the legacy WordPress plugin set by default. Every plugin must have an explicit runtime owner and page/flow scope. Production plugin presence is evidence, not automatic V3 authorization.

## CORE / REQUIRED

- `woocommerce` — commerce data and order engine.
- `byl-platform` — first-party V3 platform and migration runner.
- Advanced Media Offloader / selected R2 offloader — canonical `img.beyourlover.com` media delivery.
- Rank Math or the exact selected SEO engine — preserve V3.0 SEO identity contract.
- AffiliateWP — affiliate/referral/commission source of truth where those flows are required.
- ACF Pro — transitional only where frozen Product/Blog/KOL fields remain in V3.0 migration scope.

## G8-CONTROLLED PAYMENT ENGINES

Payment plugins are not foundation-global dependencies. G8 selects the final provider set and loads provider SDKs only on eligible payment flows.

- Stripe **or** WooPayments for primary cards.
- Airwallex where country/currency/method rules allow.
- PayPal PPCP where eligible.
- Redundant overlapping methods must not all remain globally active by default.

## REVIEW / CONDITIONAL

Keep only with an explicit V3 owner, dependency, page scope, and measurable reason:

- BetterDocs / BetterDocs Pro.
- Checkout Field Editor.
- Order Import/Export utilities.
- Permalink Manager Pro, only if required for preserved URL identity.
- Smart Coupon plugins.
- Woo email-template customizers.
- WPForms or other form runtimes.
- Trustpilot widgets/review embeds.
- One analytics bootstrap owner for GTM/ecommerce events.
- At most one approved session-recording/behavior tool by default; Clarity, Hotjar, and Lucky Orange are not all global dependencies.
- Ad/marketing scripts only behind explicit consent, owner, and page policy.

## LEGACY / DO NOT COPY INTO V3 RUNTIME

- `yby-core` / Andy Core legacy runtime unless a V3 contract explicitly adopts a bounded capability.
- Shoptimizer theme-specific helpers and CommerceKit dependencies that exist only for the legacy theme.
- Elementor/page-builder residue and generated CSS/JS.
- Duplicate GTM injection mechanisms.
- Legacy cache/minify stacks that conflict with BYL Asset Orchestrator or Cloudflare policy.
- Dead shortcode, abandoned template, and obsolete workaround plugins.
- Any plugin that performs schema/data mutation on bootstrap without a versioned V3 migration.

## Runtime Gate

Before a plugin is activated in staging or production V3, record:

1. owner and purpose;
2. exact plugin/version;
3. required pages/flows;
4. external network/data writes;
5. cache/privacy/consent impact;
6. rollback path;
7. whether a first-party V3 adapter can replace it.

Local/staging safety overrides remain mandatory: no real payment capture, no real outbound customer mail, no ERP writes, no webhook delivery, and indexing blocked unless a specific UAT gate authorizes otherwise.

## Current G2 Decision

The G2 runtime baseline is WooCommerce + BYL Platform with only explicitly required compatibility plugins retained. Legacy plugin presence in the production-derived database does not grant runtime authority. G8, G9, and G11 may narrow this list further; they may not silently broaden it.
