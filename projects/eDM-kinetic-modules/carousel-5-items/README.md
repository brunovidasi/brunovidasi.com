# Carousel eDM Module (5 Items)

`index.html` is a self-contained, table-based HTML email that exists to showcase
one thing: a **5-slide interactive carousel** built entirely with HTML/CSS (no
JavaScript, no AMP for Email). Everything else in the file — header, hero
banner, footer, legal copy — is placeholder chrome so the carousel has a
realistic email around it. All content is generic: the logo is a plain "Logo"
badge, every link points to `#LINK`, and the legal copy is Lorem Ipsum.

The only real image assets in the whole template are `images/arrow_left.png`
and `images/arrow_right.png` (the carousel's nav arrows). Everything else —
the logo, the hero banner, and each slide's "photo" — is a colored HTML/CSS
block, not a picture. That's deliberate: it proves a slide isn't limited to
an image, it can hold any HTML content (text, a button, a table, whatever the
campaign needs).

## How the carousel works

The carousel uses the classic **"checkbox hack"**: a hidden radio-style
`<input type="checkbox">` per slide, paired with `<label for="...">` elements
that act as the dots and the left/right arrows. CSS `:checked` +
general‑sibling (`~`) selectors show/hide the matching slide and swap which
arrows/dots are visible — no script required.

Because CSS support for `:checked` and sibling combinators is inconsistent
across email clients, the module ships with **two versions of the same
content**:

- **`.interactive`** — the real carousel: one slide visible at a time, tap the
  dots or arrows to move between the 5 slides.
- **`.fallback`** — the same 5 slides stacked vertically, always visible,
  no interactivity.

A small detection block (`#webkitnocheck`) plus explicit overrides for Yahoo,
Samsung Mail, and Gmail at narrow widths decide which version each client
sees. A client either gets the working carousel, or it gets all 5 slides
stacked as plain content — it never sees a broken or empty module.

## Where it works vs. where it doesn't

**Renders as an interactive carousel (tap dots/arrows to move between slides):**
- Apple Mail — macOS and iOS
- Outlook for Mac
- Gmail app — iOS and Android
- Gmail webmail — desktop browser, wide viewport

**Falls back to 5 stacked static slides (looks like 5 separate modules, one after another — fully readable, just not interactive):**
- Outlook for Windows — all versions, including Microsoft 365 desktop (renders
  with Word's engine, which has no CSS3 support; this is also why the file is
  full of `<!--[if mso]>` conditional table markup)
- Outlook.com / Outlook Web Access
- Yahoo Mail (explicitly forced to fallback in the CSS)
- Samsung Email app (explicitly forced to fallback in the CSS)
- Gmail on mobile web / narrow viewports (explicitly forced to fallback)
- Windows 10/11 Mail app
- Other legacy or limited-CSS clients (e.g. older Lotus Notes/IBM Verse)

This is inherent to the checkbox-hack technique in general, not a bug in this
file — it's the standard, industry-accepted way to add interactivity to email
while guaranteeing every client still gets a usable, complete layout.

## Reusing this module elsewhere

The whole block between `<!-- START: Carousel -->` and `<!-- END: Carousel -->`
can be copy-pasted into another template as-is. Keep these untouched or the
mechanism breaks:
- The checkbox `id`s (`image1`–`image10`), the `.imageN` classes on the arrow
  labels, and the `.dotN` / `#imageN-content` selectors
- The nesting of `.fallback` and `.interactive` inside the same wrapper
- The two `<style>` blocks inside the carousel (the `:checked` rules and the
  dot colors) — leave these exactly as they are

Safe to customize per slide: the colored placeholder block (swap for a real
photo, or replace with any other HTML — text, a table, another button), the
heading, the button label/link, and the brand colors (currently teal
`#0E7C66` and dark teal `#0B2E28`, used consistently for buttons, the nav bar,
and the placeholder blocks).

## Before using this for a real campaign

- Replace every `#LINK` href with a real URL
- Replace the "Logo" text badge with a real logo image or wordmark
- Replace the 5 colored placeholder blocks in the carousel with real photos
  (or other HTML content)
- Replace the Lorem Ipsum legal line with real terms and conditions
- Add back any personalization your ESP needs (this version has none —
  no merge tags, the header text is static)
