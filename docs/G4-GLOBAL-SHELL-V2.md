# G4 — BYL Global Shell V2

Status: implementation branch `feat/g4-global-shell-v2`

## Purpose
G4 owns the persistent site chrome only. It turns the G3 Design System into a real navigable website shell without implementing G5 components or G6 page templates.

## Canonical desktop shell
- Existing BeYourLover logo; media resolves through the canonical R2 origin when offloaded.
- Primary navigation: Products / Explore / App / Learn / Support.
- Utility actions: Search / Account / Cart.
- Sticky translucent header with restrained blur and low-contrast divider.
- Optional context local navigation below the global header.

## Canonical mobile shell
- Brand logo remains visible.
- Search and Cart remain first-class actions.
- Global navigation moves into one full-height menu.
- Account is available inside the mobile menu.
- Menu state locks document scrolling and exposes `aria-expanded`.

## Search contract
- Native site search form; no heavyweight search plugin is required for the shell.
- Search opens as a modal layer and returns focus to the trigger when closed.
- `Cmd/Ctrl + K` opens search; Escape closes overlays.

## Footer contract
- Three groups: Shop / Explore / Support.
- Uses the same canonical brand identity as the header.
- Footer links must remain free of 404s.

## Accessibility and behavior gates
- Skip link must land on `#main-content` on every theme template.
- Current global navigation uses `aria-current="page"` where the target URL matches.
- Interactive controls keep visible keyboard focus and respect reduced motion.
- Desktop and mobile layouts must have zero horizontal overflow.

## Scope boundary
G4 does not own product cards, galleries, tabs, commerce widgets, homepage storytelling, checkout UI, Docs OS content rendering, or template families. Those remain G5–G8 responsibilities.
