<?php
/**
 * BeYourLover Business Partnerships — B2 frontend Owner UAT candidate.
 *
 * WordPress page slug: business. This remains a Dev-only interactive preview:
 * application submission is local-only and sends no data, requests or email.
 */
declare(strict_types=1);
defined('ABSPATH') || exit;

// Absolute host allowlist prevents this unfinished B2B funnel from appearing on WWW.
$business_host = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
if (!in_array($business_host, ['dev.beyourlover.com', 'localdev.beyourlover.com'], true)) {
    wp_die(esc_html__('This page is not available.', 'beyourlover-v3'), '', ['response' => 404]);
    return;
}

// Respect explicitly assigned custom WP templates. Avoid overriding existing pages.
$business_template = function_exists('get_page_template_slug') ? (string) get_page_template_slug(get_queried_object_id()) : '';
if ($business_template !== '' && $business_template !== 'default') {
    require get_stylesheet_directory() . '/index.php';
    return;
}
$business_dir = get_stylesheet_directory();
$business_uri = get_stylesheet_directory_uri();
$business_version = static function (string $path) use ($business_dir): string {
    $file = $business_dir . '/' . ltrim($path, '/');
    return is_file($file) ? (string) filemtime($file) : (string) wp_get_theme()->get('Version');
};
wp_enqueue_style('byl-business-v1', $business_uri . '/assets/css/business.css', ['byl-v3-motion'], $business_version('assets/css/business.css'));
wp_enqueue_script('byl-business-v1', $business_uri . '/assets/js/business.js', [], $business_version('assets/js/business.js'), true);
wp_script_add_data('byl-business-v1', 'strategy', 'defer');

$programs = [
    ['id'=>'wholesale', 'category'=>'retail', 'label'=>'Wholesale', 'type'=>'Bulk Purchasing',
     'summary'=>'Flexible opening orders and wholesale supply for qualified retailers.',
     'detail'=>'Discuss products, quantities, sample availability and a tailored commercial quotation.', 'icon'=>'01'],
    ['id'=>'dropshipping', 'category'=>'online', 'label'=>'Dropshipping', 'type'=>'Low-Inventory Selling',
     'summary'=>'Offer eligible products without holding inventory, subject to warehouse availability.',
     'detail'=>'Product eligibility, neutral packaging, shipping fees, tax responsibilities and service regions require approval.', 'icon'=>'02'],
    ['id'=>'retail', 'category'=>'retail', 'label'=>'Retail Partners', 'type'=>'In-Store Sales',
     'summary'=>'Product displays, sample support and materials for stores and home-sales teams.',
     'detail'=>'Explore available showcase products, demonstration options and sales training resources.', 'icon'=>'03'],
    ['id'=>'distribution', 'category'=>'enterprise', 'label'=>'Distributors', 'type'=>'Regional Cooperation',
     'summary'=>'Long-term cooperation with qualified businesses developing regional markets.',
     'detail'=>'Territory conditions, commercial targets and any exclusive arrangements require separate review.', 'icon'=>'04'],
    ['id'=>'oem', 'category'=>'enterprise', 'label'=>'OEM / Private Label', 'type'=>'Custom Projects',
     'summary'=>'Explore product, brand and packaging customization.',
     'detail'=>'Customization feasibility, tooling, sampling, MOQ and lead time are evaluated per project.', 'icon'=>'05'],
    ['id'=>'corporate', 'category'=>'enterprise', 'label'=>'Corporate Orders', 'type'=>'Project Procurement',
     'summary'=>'Bulk sourcing for businesses, campaigns and special events.',
     'detail'=>'Request custom project quotations for bundles, packaging and agreed delivery terms.', 'icon'=>'06'],
];
$faqs = [
    ['Do you offer wholesale pricing?', 'Qualified business partners can request individual wholesale quotations. Prices depend on product, quantity, market and approval.'],
    ['Can I order samples before a larger purchase?', 'Sample options may be available from our China factory or eligible Germany warehouse stock. We will confirm product versions, costs and shipping.'],
    ['Do you offer dropshipping in Europe?', 'Selected products may be eligible subject to stock, packing, tax and agreed fulfillment terms. Dropshipping must be approved before orders are placed.'],
    ['Is there a minimum opening order?', 'Minimum order quantities differ by product and business program. Include your estimated quantity and we can suggest a suitable start.'],
    ['How soon can an order arrive in Belgium?', 'Lead times depend on SKU, warehouse inventory, location and shipping method. We confirm costs and estimated delivery with each quote.'],
];
get_header();
?>
<main id="main-content" class="byl-business" data-byl-business="dev-preview-v1">
    <div class="byl-business__devnote" role="status">
        <?php esc_html_e('Dev preview — partnership applications are simulated only. No information is sent.', 'beyourlover-v3'); ?>
    </div>
    <nav class="byl-business__subnav byl-business__wrap" aria-label="Business partnerships navigation">
        <a href="#byl-business-home" class="byl-business__subbrand">Business <span>Partnerships</span></a>
        <div class="byl-business__sublinks">
            <a href="#byl-business-programs">Programs</a>
            <a href="#byl-business-support">Our Support</a>
            <a href="#byl-business-faq">FAQ</a>
        </div>
        <button type="button" class="byl-business__button byl-business__button--small" data-business-apply>Become a Partner <span aria-hidden="true">→</span></button>
    </nav>

    <section id="byl-business-home" class="byl-business__hero" aria-labelledby="byl-business-title">
        <div class="byl-business__hero-content">
            <span class="byl-business__eyebrow">BUSINESS PARTNERSHIPS</span>
            <h1 id="byl-business-title">Grow together.<br><em>Sell beautifully.</em></h1>
            <p>Wholesale, dropshipping and retail partnerships designed to help you bring BeYourLover products to more customers.</p>
            <div class="byl-business__actions">
                <button type="button" class="byl-business__button" data-business-apply>Become a Partner <span aria-hidden="true">→</span></button>
                <a class="byl-business__button byl-business__button--outline" href="#byl-business-programs">Explore Programs ↓</a>
            </div>
            <small>Flexible cooperation · International supply · Dedicated business support</small>
        </div>
        <div class="byl-business__hero-art" role="img" aria-label="Abstract BeYourLover business partnership illustration; no product imagery">
            <div class="byl-business__art-ring" aria-hidden="true"></div>
            <div class="byl-business__art-panel"><span>BeYourLover</span><strong>Made for meaningful partnerships.</strong><span>BUSINESS COLLECTION</span></div>
            <div class="byl-business__art-caption">From samples to scale <small>Flexible B2B cooperation</small></div>
        </div>
    </section>

    <div class="byl-business__trust" aria-label="Business support highlights">
        <span>◇ Flexible wholesale supply</span><span>◎ European fulfillment options</span><span>✧ Product &amp; sample support</span>
    </div>

    <section id="byl-business-programs" class="byl-business__section byl-business__wrap" aria-labelledby="byl-business-programs-title">
        <div class="byl-business__sectionhead">
            <div><span class="byl-business__eyebrow">PARTNERSHIP PROGRAMS</span>
                <h2 id="byl-business-programs-title">Find a partnership<br>that fits your business.</h2></div>
            <p>Whether you run an independent store, an online shop or a sales network, explore how we can work together.</p>
        </div>
        <div class="byl-business__filters">
            <label class="byl-business__sr" for="byl-business-search">Search programs</label>
            <input id="byl-business-search" type="search" placeholder="Search programs…" autocomplete="off">
            <label class="byl-business__sr" for="byl-business-filter">Filter partnership type</label>
            <select id="byl-business-filter"><option value="all">All partnership types</option><option value="retail">Retail &amp; Wholesale</option><option value="online">Online Selling</option><option value="enterprise">Enterprise &amp; Projects</option></select>
        </div>
        <div class="byl-business__program-grid" id="byl-business-grid">
            <?php foreach ($programs as $program): ?>
            <article class="byl-business__program" data-business-program="<?php echo esc_attr($program['id']); ?>"
                data-category="<?php echo esc_attr($program['category']); ?>"
                data-search="<?php echo esc_attr(strtolower($program['label'].' '.$program['type'].' '.$program['summary'])); ?>"
                data-label="<?php echo esc_attr($program['label']); ?>"
                data-description="<?php echo esc_attr($program['detail']); ?>">
                <div class="byl-business__programtop"><span class="byl-business__program-icon" aria-hidden="true"><?php echo esc_html($program['icon']); ?></span><span class="byl-business__programtype"><?php echo esc_html($program['type']); ?></span></div>
                <h3><?php echo esc_html($program['label']); ?></h3>
                <p><?php echo esc_html($program['summary']); ?></p>
                <button type="button" class="byl-business__textbutton" data-business-details="<?php echo esc_attr($program['id']); ?>">Explore program →</button>
            </article>
            <?php endforeach; ?>
        </div>
        <p class="byl-business__noresults" id="byl-business-noresults" hidden>No matching programs. Try another search.</p>
    </section>

    <section id="byl-business-support" class="byl-business__section byl-business__section--soft" aria-labelledby="byl-business-support-title">
        <div class="byl-business__wrap byl-business__split">
            <div class="byl-business__support-art" aria-hidden="true">
                <div><small>SUPPLY &amp; FULFILLMENT</small><strong>Flexible sourcing options</strong><span>Availability and shipping terms confirmed individually.</span></div>
                <span class="byl-business__art-blocks">▰ ▰ ▰</span>
            </div>
            <div>
                <span class="byl-business__eyebrow">HOW WE SUPPORT YOU</span>
                <h2 id="byl-business-support-title">More ways to grow,<br>less complexity.</h2>
                <p>Build your offering with flexible purchasing and fulfillment options tailored to your market.</p>
                <ul class="byl-business__benefits">
                    <li><strong>Wholesale ordering</strong> — Volume-based quotations and supply planning</li>
                    <li><strong>Germany warehouse options</strong> — Stock-dependent fulfillment for eligible SKUs</li>
                    <li><strong>Factory-direct samples</strong> — Explore the latest available product versions</li>
                    <li><strong>Partner resources</strong> — Product information and available sales materials</li>
                </ul>
                <button type="button" class="byl-business__button byl-business__button--outline" data-business-apply>Request Business Information →</button>
            </div>
        </div>
    </section>

    <section class="byl-business__section byl-business__wrap" aria-labelledby="byl-business-steps-title">
        <span class="byl-business__eyebrow">GETTING STARTED</span><h2 id="byl-business-steps-title">Simple steps. Strong partnerships.</h2>
        <div class="byl-business__steps">
        <?php foreach ([
            ['01','Apply','Tell us about your company and business goals.'],
            ['02','Review','We evaluate your application and requirements.'],
            ['03','Quote & Samples','Discuss products, samples and commercial terms.'],
            ['04','Grow','Start with a first order and expand over time.'],
        ] as $step): ?>
            <article><span><?php echo esc_html($step[0]); ?></span><h3><?php echo esc_html($step[1]); ?></h3><p><?php echo esc_html($step[2]); ?></p></article>
        <?php endforeach; ?>
        </div>
        <div class="byl-business__match">
            <span class="byl-business__eyebrow">PARTNER MATCH</span><h3>What best describes your business?</h3>
            <div class="byl-business__match-options" role="group" aria-label="Choose your business type">
                <button type="button" aria-pressed="true" data-business-match="store">I run a retail store</button>
                <button type="button" aria-pressed="false" data-business-match="online">I sell online</button>
                <button type="button" aria-pressed="false" data-business-match="network">I manage a sales network</button>
                <button type="button" aria-pressed="false" data-business-match="brand">I own a brand</button>
            </div>
            <div class="byl-business__recommend" aria-live="polite">
                <div><span class="byl-business__eyebrow">YOUR SUGGESTED PROGRAM</span><strong id="byl-business-recommend-title">Retail Partners</strong><p id="byl-business-recommend-copy">A practical starting point for shop displays, samples and regular replenishment.</p></div>
                <button type="button" class="byl-business__button" id="byl-business-recommend-apply" data-business-apply data-preselect="retail">Apply →</button>
            </div>
        </div>
    </section>

    <section id="byl-business-faq" class="byl-business__section byl-business__section--soft" aria-labelledby="byl-business-faq-title">
        <div class="byl-business__wrap byl-business__faqs">
            <div class="byl-business__faqheading"><span class="byl-business__eyebrow">FREQUENTLY ASKED QUESTIONS</span><h2 id="byl-business-faq-title">Good to know.</h2></div>
            <?php foreach ($faqs as $index => $faq): ?>
                <details <?php echo $index === 0 ? 'open' : ''; ?>><summary><?php echo esc_html($faq[0]); ?></summary><p><?php echo esc_html($faq[1]); ?></p></details>
            <?php endforeach; ?>
        </div>
    </section>
    <section class="byl-business__closing">
        <div class="byl-business__wrap byl-business__closing-inner"><div><span class="byl-business__eyebrow">LET'S WORK TOGETHER</span><h2>Let's build something great.</h2><p>Tell us about your business and discover the right partnership with BeYourLover.</p></div>
        <button type="button" class="byl-business__button byl-business__button--white" data-business-apply>Apply for Partnership →</button></div>
    </section>

    <dialog class="byl-business__dialog" id="byl-business-dialog" aria-labelledby="byl-business-dialog-title">
        <div class="byl-business__dialog-heading"><div><span class="byl-business__eyebrow">BeYourLover BUSINESS</span><h2 id="byl-business-dialog-title">Become a Partner</h2><p id="byl-business-dialog-intro">Demo preview only — nothing is submitted.</p></div><button type="button" class="byl-business__dismiss" data-business-close aria-label="Close dialog">×</button></div>
        <div id="byl-business-dialog-details" class="byl-business__dialog-details" hidden></div>
        <form id="byl-business-form" autocomplete="off">
            <div class="byl-business__form-grid">
                <label>Company / Store Name *<input required name="company" maxlength="120"></label>
                <label>Contact Name *<input required name="contact" maxlength="120"></label>
                <label>Business Email *<input required name="email" type="email" maxlength="254"></label>
                <label>Country / Region *<select required name="country"><option value="">Select country</option><option>Belgium</option><option>Germany</option><option>France</option><option>Netherlands</option><option>United Kingdom</option><option>United States</option><option>Spain</option><option>Italy</option><option>Other</option></select></label>
                <label class="byl-business__full">Business Website / Profile<input name="website" type="url" placeholder="https://example.com"></label>
                <fieldset class="byl-business__full"><legend>Interested Partnership Programs *</legend>
                <div class="byl-business__checks"><?php foreach ($programs as $program): ?><label><input name="program[]" type="checkbox" value="<?php echo esc_attr($program['id']); ?>"><?php echo esc_html($program['label']); ?></label><?php endforeach; ?></div>
                <p class="byl-business__form-error" id="byl-business-program-error" hidden>Please select at least one partnership program.</p></fieldset>
                <label>Estimated Initial Quantity<select name="volume"><option>Not sure yet</option><option>1–20 units</option><option>21–50 units</option><option>51–200 units</option><option>201–500 units</option><option>500+ units</option></select></label>
                <label>Preferred Supply<select name="supply"><option>Discuss options</option><option>Europe warehouse if available</option><option>China factory</option></select></label>
                <label class="byl-business__full">Tell us about your business<textarea name="message" rows="3" maxlength="2000"></textarea></label>
                <label class="byl-business__full byl-business__privacy"><input required name="privacy" type="checkbox">I agree to the processing of my information for this business inquiry. *</label>
            </div>
            <div class="byl-business__dialog-actions"><button type="button" class="byl-business__button byl-business__button--outline" data-business-close>Cancel</button><button type="submit" class="byl-business__button">Preview Submission →</button></div>
        </form>
        <div id="byl-business-success" class="byl-business__success" role="status" hidden><span aria-hidden="true">✓</span><h3>Application preview complete.</h3><p>This was a simulation. Your application has not been sent or saved.</p><button type="button" class="byl-business__button" data-business-close>Done</button></div>
    </dialog>
</main>
<?php get_footer(); ?>
