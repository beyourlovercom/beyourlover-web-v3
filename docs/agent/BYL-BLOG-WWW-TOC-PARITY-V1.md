# BYL V3 — Blog Post TOC / Floating INDEX vs WWW

**Source of truth:** GitHub. **Target:** Dev `dev.beyourlover.com` first, never modify WWW Production.
**Dependency:** PR #47 default Page/Post, on which this scoped Blog branch is based. The Privacy and Disclaimer Owner PASS evidence in PR #47 remains intact.
**Reference:** Public WWW [Dom and Brat Play](https://www.beyourlover.com/blog/dom-and-brat-play/) compared directly with the identical imported Dev article. Read-only authentic 1440/390 headless Chrome DOM audit and screenshots on Owner CN Mac `~/Downloads/BYL-Blog-WWW-TOC-Audit-20261009/`.

## What WWW actually does

- At desktop 1440: article body `.entry-content` ≈ **660px wide at x390**, original leading Summary div `.articre_toc` immediately **after introductory paragraphs and before first H2**; seven H2 chapters in this article. Explicit anchor links `#test_0`…`#test_6`.
- Desktop floating `#toc-spy.toc.stiky`: an **INDEX** on the left; after scrolling shows at approximately **x164, y100, width187**, with red highlighted active item, same seven chapter links.
- At mobile 390: inline Summary is shown within the 350px content measure; `#toc-spy` has CSS `display:none` and must never overlap body.
- WWW retains a right column with newsletter/product suggestions/recent Posts, various authored in-content product callouts, an author bio and other plugin/theme widgets. **Those are real additional parity requirements, not included in this TOC-only wave.** Do not claim total “pixel-perfect WWW replica” after passing TOC alone.

## V3 first-stage Blog TOC implementation

- Use existing WordPress single `post` route from PR #47. The original `the_content()` is preserved; no saved HTML, content, metadata, DB, URLs, external sync or shortcode data are altered.
- Enqueue one small `post-toc.js` only from `single-post.php`; no plugin, no global theme JS or Woo interactions.
- Progressive enhancement reads the displayed Post's existing H2 nodes, assigns unique IDs **only in the browser DOM**, creates an in-article Summary immediately before first H2, and links the same headings to the desktop floating INDEX. Prevent duplicate insertion on repeat invocation and defer to already-authored in-content TOCs.
- Scroll-spy determines active visible H2, highlights associated navigation with `aria-current=location`; passive event listener + requestAnimationFrame. Normal `href="#..."` deep links, keyboard access, semantic navs; no custom history or scroll hijacking.
- Desktop `min-width:1280px`: right-of-left-rail body 660px at x390 for 1440px viewport, floating menu at x160/y100/187px wide after article scrolling, zero overflow.
- Mobile: body remains 350px with 20px gutters; inline summary displayed and floating menu always hidden, zero overflow.
- Scoped post H2 heading color uses BYL VI brand red to better match WWW; no impact on accepted Terms/Privacy or checkout.

## Verified isolated browser checks

Actual public Dev article #39899 and exact branch JS/CSS loaded into separate **headless browser memory only**:
- 390px: 7 actual H2 / 7 Summary / 7 INDEX links, valid anchors, original body intact except temporary TOC nodes and IDs; floating hidden, H1 single, width350, zero overflow.
- 1440px: H2/TOC counts and links PASS; viewport scrollY=1100 → INDEX visible, left160, top100, width187, one active `aria-current`, article left390, width660, zero overflow.
- Clicking Summary link updates browser hash to a real `#byl-post-section-1` target; checks run without a click that submits any payment or contacts an external service.
- Chrome local UAT screenshots: `~/Downloads/BYL-Blog-TOC-V1-UAT-20261009/Final-TOC-{390,1440}-scroll.png`.
- Repo CI: `node --check` and `scripts/test_blog_toc_contract.js`; browser interaction UAT is additional and not replaced by static checks.

## Gates

1. CI PASS and base PR #47 exact head confirmed, then create **stacked Draft PR against PR #47 branch**. Do not directly merge into GitHub main.
2. Guarded Dev-only JS/CSS/PHP deploy, backups + SHA, G2 remains Closed, no WWW/Production writes; verify TOC under real Dev HTML at mobile and desktop.
3. Owner final Blog visual UAT (Summary, floating menu, anchor navigation, typography). **Do not mark the entire Blog WWW replica PASS until right sidebar, dynamic product callouts and author/footer modules are explicitly checked** against approved examples.
4. PR #47 remains unmerged while Blog is pending. After Blog owner PASS, reconcile stacked branch into PR #47 with exact-head approval, then separately gate main merge.

