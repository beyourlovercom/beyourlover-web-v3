<?php
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }
get_header();
?>
<main class="byl-ds" data-byl-v3-design-system="v1">
  <nav class="byl-ds-nav" aria-label="V3 Design System preview">
    <div class="byl-container byl-ds-nav-inner">
      <div class="byl-ds-brand"><span class="byl-ds-mark" aria-hidden="true"></span><span>BEYOURLOVER</span></div>
      <div class="byl-ds-nav-meta">Design System V1 · G3</div>
    </div>
  </nav>
  <section class="byl-ds-hero">
    <div class="byl-container byl-ds-hero-grid">
      <div class="byl-ds-copy">
        <p class="byl-eyebrow">BEYOURLOVER · V3</p>
        <h1 class="byl-display-xl">Designed for desire.<br>Built for clarity.</h1>
        <p class="byl-body-l">A calmer, sharper visual system for commerce: Poppins typography, generous space, restrained motion and the existing BYL brand red.</p>
        <div class="byl-ds-actions"><a class="byl-btn byl-btn-primary" href="#components">Primary action</a><a class="byl-btn byl-btn-secondary" href="#type">Explore system</a></div>
      </div>
      <aside class="byl-ds-brand-panel" aria-label="Canonical brand color">
        <p class="byl-eyebrow" style="color:#fff">Canonical brand</p>
        <strong>#9B3749</strong><span>Existing BYL red · preserved, not reinvented.</span>
      </aside>
    </div>
  </section>
  <section class="byl-ds-section" id="color">
    <div class="byl-container">
      <div class="byl-ds-section-head"><div><p class="byl-eyebrow">Color</p><h2 class="byl-heading-xl">Brand first. Neutral by default.</h2></div><p class="byl-body-m">Use brand red for emphasis and conversion moments; let black, white and soft neutrals carry the interface.</p></div>
      <div class="byl-grid-3">
        <div class="byl-swatch byl-swatch-brand">Brand Red<small>#9B3749</small></div>
        <div class="byl-swatch byl-swatch-ink">Ink<small>#111111</small></div>
        <div class="byl-swatch byl-swatch-soft">Brand Soft<small>#F7ECEF</small></div>
      </div>
    </div>
  </section>

  <section class="byl-ds-section" id="type">
    <div class="byl-container">
      <div class="byl-ds-section-head"><div><p class="byl-eyebrow">Typography</p><h2 class="byl-heading-xl">Poppins, with hierarchy.</h2></div><p class="byl-body-m">Large headlines stay compact and editorial. Body copy stays readable and calm across commerce and content surfaces.</p></div>
      <div class="byl-type-stack">
        <div class="byl-type-row"><p class="byl-eyebrow">Display XL · 600</p><p class="byl-display-l">Feel more. Find your fit.</p></div>
        <div class="byl-type-row"><p class="byl-eyebrow">Heading L · 600</p><p class="byl-heading-l">Made to be explored.</p></div>
        <div class="byl-type-row"><p class="byl-eyebrow">Body L · 400</p><p class="byl-body-l">Comfortable reading, clear choices, and less visual noise around the action that matters.</p></div>
      </div>
    </div>
  </section>
  <section class="byl-ds-section" id="components">
    <div class="byl-container">
      <div class="byl-ds-section-head"><div><p class="byl-eyebrow">Primitives</p><h2 class="byl-heading-xl">Simple components, consistent behavior.</h2></div><p class="byl-body-m">Buttons, cards, chips and motion tokens are shared primitives. Product-specific UI will build on these in G5.</p></div>
      <div class="byl-grid-3">
        <article class="byl-card"><span class="byl-chip">Buttons</span><h3 class="byl-heading-m" style="margin-top:20px">Clear actions</h3><p class="byl-body-m">Primary is reserved for the main decision. Secondary stays quiet.</p><div class="byl-ds-actions"><button class="byl-btn byl-btn-primary">Add to bag</button><button class="byl-btn byl-btn-secondary">Learn more</button></div></article>
        <article class="byl-card byl-card-soft"><span class="byl-chip">Cards</span><h3 class="byl-heading-m" style="margin-top:20px">Soft structure</h3><p class="byl-body-m">Large radius, low-contrast borders, restrained shadow and more breathing room.</p></article>
        <article class="byl-card"><span class="byl-chip">Motion L1</span><h3 class="byl-heading-m" style="margin-top:20px">Purposeful motion</h3><p class="byl-body-m">Fast feedback for controls; slower motion only when it improves orientation.</p><div class="byl-motion-demo" aria-hidden="true"></div></article>
      </div>
    </div>
  </section>

  <section class="byl-ds-section">
    <div class="byl-container">
      <div class="byl-ds-section-head"><div><p class="byl-eyebrow">Motion levels</p><h2 class="byl-heading-xl">Motion follows the job.</h2></div><p class="byl-body-m">L0 Utility · L1 Editorial · L2 Product · L3 Brand. Checkout and account flows stay minimal; brand moments can be richer.</p></div>
      <div class="byl-grid-3">
        <div class="byl-card"><span class="byl-chip">L0</span><h3 class="byl-heading-m" style="margin-top:20px">Utility</h3><p class="byl-body-s">Cart · Checkout · Login · Account</p></div>
        <div class="byl-card"><span class="byl-chip">L1–L2</span><h3 class="byl-heading-m" style="margin-top:20px">Content + Commerce</h3><p class="byl-body-s">Blog · Guide · Product · Category</p></div>
        <div class="byl-card"><span class="byl-chip">L3</span><h3 class="byl-heading-m" style="margin-top:20px">Experience</h3><p class="byl-body-s">Homepage · App · Brand · Campaign</p></div>
      </div>
    </div>
  </section>
</main>
<?php get_footer();
