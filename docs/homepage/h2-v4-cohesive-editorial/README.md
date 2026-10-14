# Homepage H2 V4 — Cohesive Editorial Art Direction Reset (Owner review pending)

**State: New design candidate after explicit rejection of the ENTIRE V2/V3 page.** The latest visual is a separate static concept, not a runtime implementation or a basis for deployment. GitHub main is the sole eventual code merge target.

## Visual design concept
- One coordinated studio-like visual language across an editorial Hero, eight original product shortcuts, a manifesto/brand story, real-product New Arrivals, and three nonidentical collection illustration panels.
- Restrained warm chalk white, charcoal-plum, oxblood, sage gray and muted pink; deliberate space, large serif typography and simple rule line accents; abandon red-gradient card stacks.
- Original new editorial illustrations created as SVG line/vector artwork in the standalone HTML (not copied from another brand and not generated for immutable lower modules).
- The eight exact WWW original quick shortcut names, order and hrefs are retained. Image sources are real BeYourLover assets (some product shots differ from the old circular shortcut images). The quick links have no new cards/borders/labels, mobile swipe supports all eight.
- New Arrivals real Woo product image/name references: TARA S2, MINAKA, NAUGHTY CLOCK PINK, NAUGHTY CLOCK GREEN. Gallery includes all four on PC and M390. Prices have deliberately not been invented.
- Three collection banners in their original source positions (women/men/couples) now have distinct narrative treatments using coordinated SVG art, with existing category product rows between them unchanged.

## Strict immutable WWW lower
- Original full-page WWW read-only screenshot sourced from live WWW 2026-10-14 after lazy-loading, stored as WWW-Original-PC1440-Source.jpeg, WWW-Original-M390-Source.jpeg.
- Fullpage V4 image is a composite. After New Arrivals it embeds the original WWW raster from source desktop y=2260/mobile y=1880, then overlays only the three campaign banners.
- On PC the third banner overlay ends at original source y=5049, BEFORE Creator Signed Editions y=5597.
- On M390 the third overlay ends at original source y=3952, BEFORE Creator Signed Editions y=4654.
- All original visual pixels from Creator Signed Editions through Follow Us on Instagram, You & BeYourLover, Lover's Journal, media endorsement and original Footer are faithfully reproduced from the original WWW screenshot; no new generated art or rewritten copy in that region.
- In a future actual webpage implementation, preserve the original DOM/components and actual artwork, not a screenshot. The screenshot technique is only for faithful visual mockup review.

## Verification
- True Chrome static screenshot at 1440 and 390 (not UI redrawn by AI); 0 document horizontal overflow, all images loaded, no JS page errors.
- 8/8 quick shortcut link names/order/hrefs match WWW.
- Mobile the last item of the swipe bar is reachable.
- New Arrivals has four real items also on M390.
- See V4-layout-audit.json. No Dev, WWW, WP, checkout, orders, mail, ERP, payments or live business writes.

## Review files
- [PC1440 whole page](V4-Full-PC1440.jpeg)
- [M390 whole page](V4-Full-M390.jpeg)
- [PC1440 first fold](V4-Hero-PC1440.jpeg)
- [M390 first fold](V4-Hero-M390.jpeg)
- [Standalone design HTML](V4-Static-Visual.html)

**Gate:** H2 Owner/design-team art direction must explicitly PASS before we create production-quality implementation or touch Dev. WWW/T6 remains separately gated. Earlier V2/V3 design references remain rejected.
