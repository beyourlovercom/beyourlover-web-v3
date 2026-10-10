# BYL Theme OS V1 — maintenance and version governance

Phase 1 is **code-only**: no theme activation, database migration, Dev/WWW deployment, child theme or CommerceKit dependency.

## Ownership
The BYL Theme owns VI, markup, page/commerce presentation, scoped CSS/JS and UI state. Andy Core owns generic WP foundation. Andy Commerce owns B2C gift, promotions and shipping policies. WooCommerce and gateways own inventory, Cart Session, taxes, totals, payment and orders. BYL ERP owns fulfillment and accounting. There is one commerce authority; Theme JS may not calculate a parallel order total.

The Theme stays a **standalone BYL-maintained WordPress theme**. A new BYL Child Theme is not required for BYL-owned parent upgrades. Shoptimizer/CommerceKit must not be introduced as runtime dependencies without independent Owner review.

## Module manifest
The manifest at theme/beyourlover-v3/theme-os.json is the one registry of Theme file owners and dependencies. The 12 modules are: foundation, shell, homepage, catalog, product, shipping-ui, cart, checkout, postpurchase, editorial, pages, and support. Every Theme file must have exactly one owning module. CI rejects unregistered files, duplicates, symlinks, unsafe paths, missing dependencies and dependency cycles.

Cart, Checkout and postpurchase are protected Woo-adjacent UI modules. Changes to shared modules cause dependent-module regression gates. The registry is a **CI contract only**, not a runtime loader; Theme functions.php, page URLs and Woo hook order are unchanged by this initial implementation.

Future modules must reuse Woo APIs, use narrow stable UI interfaces, handle missing/network/expired-Nonce states, and keep page-specific JS/CSS out of global bundles. Do not duplicate CommerceKit or Woo fragments. Free Gift remains OFF until server-side Andy Commerce entitlement, stock and checkout verification are approved.

## SemVer and releases
The current baseline is WordPress Theme Version **0.1.0** (not yet 1.0.0). It must match the Theme OS manifest.
- PATCH: backwards-compatible fixes
- MINOR: additive features with backwards-compatible component API
- MAJOR: breaking template, hook or customer experience contract; separate migration/UAT gate

A future stable tag uses theme-specific naming, e.g. byl-theme-v1.0.0, only after CI, Dev Owner UAT and explicit merge/release approval. A Theme release never silently releases Andy Core/Commerce.

Build commands:
    python3 scripts/theme_os.py validate
    python3 -m unittest discover -s scripts -p test_theme_os_contract.py -v
    python3 scripts/theme_os.py impact --base origin/main
    python3 scripts/theme_os.py build --out-dir /tmp/byl-theme-os-build
    python3 scripts/theme_os.py verify --zip PATH --receipt PATH

Build output is an allowlisted, deterministic WordPress installable Theme ZIP, fixed metadata, per-file SHA256 receipt, Git exact-head reference, and Owner-UAT/Production **NOT_AUTHORIZED** flags. Packaging refuses dirty tracked worktrees and untracked Theme files; --allow-dirty exists only for offline development with a receipt clearly marked dirty. CI builds twice and compares ZIP checksums. ZIP does not include databases, plugins, uploads, secrets or vendor theme code.

## Upgrade and rollback gate
1. GitHub PR scopes its modules; run static registry, PHP/JS, and affected Woo/Page/Shell tests. CI must PASS.
2. Record exact head, ZIP SHA256 and a compatible stack matrix (WP, PHP, Woo, Andy Core/Commerce, Checkout mode, gateways).
3. Before any Dev runtime mutation, independently confirm existing hashes, readable backup and rollback pointer and that G2/G5 safety controls allow the scoped test. Never override another testing window.
4. Dev-only install is an independent gate, followed by real phone 390px / desktop 1440px Cart/Checkout UAT. No Place Order outside separately authorized payment tests.
5. Owner Dev UAT PASS and main merge require separate exact-head approval. WWW cutover/rollback needs an independent approval and delta-data plan.
6. If regression fails, restore only verified Theme code from previous ZIP. Never roll back genuine Woo/ERP business transactions with a stale DB backup.

## Theme OS roadmap
T0 file and authority inventory → T1 release/version/CI/package foundation → T2 runtime module entrypoints refactor in separate small PRs → T3 Woo native compatibility and update regression → T4 Theme v1.0.0 candidate + rollback rehearsal → T5 Dev Owner Final UAT → T6 independently authorized WWW cutover.

**Parallel branches:** Cart Drawer PR #63 must add its future new Cart files to this registry when rebased after Theme OS merge; PR #61 and G3/G5 remain independent. No Dev theme switch in this branch.
