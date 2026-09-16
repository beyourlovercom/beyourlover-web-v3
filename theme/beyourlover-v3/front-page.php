<?php
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }
get_header();
?>
<main id="main-content" class="byl-ds" data-byl-v3-design-system="v1" data-byl-g4-shell-preview="true">
  <section class="byl-ds-hero">
    <div class="byl-container byl-ds-hero-grid">
      <div class="byl-ds-copy" data-byl-motion="rise">
        <p class="byl-eyebrow">BEYOURLOVER · V3</p>
        <h1 class="byl-display-xl">Designed for desire.<br>Built for clarity.</h1>
        <p class="byl-body-l">A calm, premium commerce system built from reusable tokens, accessible primitives and restrained motion.</p>
        <div class="byl-ds-actions">
          <a class="byl-btn byl-btn-primary" href="#primitives">Explore primitives</a>
          <a class="byl-btn byl-btn-secondary" href="#type">View typography</a>
        </div>
      </div>
      <aside class="byl-ds-brand-panel" aria-label="Canonical brand color" data-byl-motion="scale">
        <p class="byl-eyebrow byl-ds-on-brand">Canonical brand</p>
        <strong>#9B3749</strong><span>Existing BYL red · preserved, not reinvented.</span>
      </aside>
    </div>
  </section>

  <section class="byl-ds-section" id="color">
    <div class="byl-container">
      <div class="byl-ds-section-head">
        <div><p class="byl-eyebrow">Color</p><h2 class="byl-heading-xl">Brand first. Neutral by default.</h2></div>
        <p class="byl-body-m">Brand red carries emphasis and conversion. Ink, white and soft neutrals carry the interface.</p>
      </div>
      <div class="byl-grid-3">
        <div class="byl-swatch byl-swatch-brand">Brand Red<small>#9B3749</small></div>
        <div class="byl-swatch byl-swatch-ink">Ink<small>#161316</small></div>
        <div class="byl-swatch byl-swatch-soft">Brand Soft<small>#F7ECEF</small></div>
      </div>
    </div>
  </section>

  <section class="byl-ds-section" id="type">
    <div class="byl-container">
      <div class="byl-ds-section-head">
        <div><p class="byl-eyebrow">Typography</p><h2 class="byl-heading-xl">Poppins, with hierarchy.</h2></div>
        <p class="byl-body-m">Editorial scale for brand moments, controlled headings for commerce, and readable body copy for guides and support.</p>
      </div>
      <div class="byl-type-stack">
        <div class="byl-type-row"><p class="byl-eyebrow">Display L · 500</p><p class="byl-display-l">Feel more. Find your fit.</p></div>
        <div class="byl-type-row"><p class="byl-eyebrow">Heading L · 500</p><p class="byl-heading-l">Made to be explored.</p></div>
        <div class="byl-type-row"><p class="byl-eyebrow">Body L · 400</p><p class="byl-body-l">Comfortable reading, clear choices, and less visual noise around the action that matters.</p></div>
      </div>
    </div>
  </section>

  <section class="byl-ds-section" id="primitives">
    <div class="byl-container">
      <div class="byl-ds-section-head">
        <div><p class="byl-eyebrow">Foundation primitives</p><h2 class="byl-heading-xl">One behavior system, everywhere.</h2></div>
        <p class="byl-body-m">These are low-level building blocks only. Product cards, galleries, tabs and commerce patterns arrive later in G5.</p>
      </div>
      <div class="byl-grid-3">
        <article class="byl-card byl-stack">
          <span class="byl-chip">Buttons</span>
          <h3 class="byl-heading-m">Clear actions</h3>
          <p class="byl-body-m">48px minimum target, visible focus, disabled state and reduced-motion-safe press feedback.</p>
          <div class="byl-cluster">
            <button class="byl-btn byl-btn-primary">Primary</button>
            <button class="byl-btn byl-btn-secondary">Secondary</button>
            <button class="byl-btn byl-btn-quiet">Quiet</button>
            <button class="byl-btn byl-btn-secondary" disabled>Disabled</button>
          </div>
        </article>
        <article class="byl-card byl-card-soft byl-stack">
          <span class="byl-chip">Surface</span>
          <h3 class="byl-heading-m">Soft structure</h3>
          <p class="byl-body-m">Low-contrast borders, restrained shadow and consistent section spacing keep the interface calm.</p>
          <hr class="byl-divider">
          <a class="byl-link" href="#reading">Discoverable text link</a>
        </article>
        <article class="byl-card byl-stack byl-motion-hover-lift">
          <span class="byl-chip">Icon + motion</span>
          <h3 class="byl-heading-m">Purposeful feedback</h3>          <p class="byl-body-m">Icons inherit text color; motion reinforces state instead of decorating every surface.</p>
          <div class="byl-cluster" aria-label="Icon size examples">
            <span class="byl-icon byl-icon-sm" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
            <span class="byl-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
            <span class="byl-icon byl-icon-lg" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
          </div>
        </article>
      </div>
    </div>
  </section>

  <section class="byl-ds-section byl-surface-soft" id="reading">
    <div class="byl-container">
      <div class="byl-reading byl-ds-reading-card">
        <p class="byl-eyebrow">Editorial / Docs foundation</p>
        <h2 class="byl-heading-l">Readable enough for a guide. Focused enough for support.</h2>
        <p class="byl-body-l">The reading column is capped at 760px so future tutorials, FAQs and support articles remain comfortable on large displays without depending on BetterDocs styling.</p>
        <p class="byl-body-m">Links stay visible and keyboard focus stays explicit. This gives the future <strong>Andy Core Docs OS</strong> a native foundation while keeping the actual tutorial content in WordPress posts.</p>
        <p><a class="byl-link" href="#motion">See motion policy</a></p>
      </div>
    </div>
  </section>

  <section class="byl-ds-section" id="motion">
    <div class="byl-container">
      <div class="byl-ds-section-head">
        <div><p class="byl-eyebrow">Motion levels</p><h2 class="byl-heading-xl">Motion follows the job.</h2></div>
        <p class="byl-body-m">L0 utility stays nearly still. L1 editorial orients. L2 commerce supports product exploration. L3 brand can tell a richer story.</p>
      </div>
      <div class="byl-grid-3">
        <article class="byl-card byl-stack"><span class="byl-chip">L0</span><h3 class="byl-heading-m">Utility</h3><p class="byl-body-s">Cart · Checkout · Login · Account</p><div class="byl-motion-demo byl-motion-press" aria-hidden="true"></div></article>
        <article class="byl-card byl-stack"><span class="byl-chip">L1–L2</span><h3 class="byl-heading-m">Content + Commerce</h3><p class="byl-body-s">Blog · Guide · Product · Category</p><div class="byl-motion-demo" data-byl-motion="rise" aria-hidden="true"></div></article>
        <article class="byl-card byl-stack"><span class="byl-chip">L3</span><h3 class="byl-heading-m">Experience</h3><p class="byl-body-s">Homepage · App · Brand · Campaign</p><div class="byl-motion-demo" data-byl-motion="scale" aria-hidden="true"></div></article>
      </div>
      <p class="byl-ds-footnote">All motion automatically respects <code>prefers-reduced-motion</code>.</p>
    </div>
  </section>
</main>
<?php get_footer();