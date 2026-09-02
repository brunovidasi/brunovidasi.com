# Accordion eDM Module (3 Items)

`index.html` is a self-contained, table-based HTML email that exists to
showcase one thing: a **3-panel interactive accordion** built entirely with
HTML/CSS (no JavaScript, no AMP for Email). Everything else in the file —
header, hero banner, footer, legal copy — is placeholder chrome so the
accordion has a realistic email around it. All content is generic: the logo
is a plain "Logo" badge, every link points to `#LINK`, and the legal copy is
Lorem Ipsum.

There are no image assets in this template at all — not even nav arrows
(this accordion design doesn't use any, unlike the carousel module). The
logo, hero banner, each panel's clickable trigger bar, and each panel's
"photo" are all colored HTML/CSS blocks, not pictures. That's deliberate: it
proves a panel isn't limited to an image, it can hold any HTML content (text,
a button, a table, whatever the campaign needs).

This module uses an **orange** palette (`#C1560C` / `#6B3308`) instead of the
carousel module's teal, purely so the two module files are easy to tell apart
at a glance.

## How the accordion works

Each panel is a hidden checkbox (`#em_drop`, `#em_drop2`, `#em_drop3`) paired
with a `<label for="...">` that wraps the clickable trigger bar. Clicking the
bar toggles its checkbox, and CSS shows/hides that panel's content:

- `div .acc / .acc2 / .acc3` holds each panel's content, with a CSS
  `transition` on `margin-top` so the open/close is animated, not instant.
- `@media screen and (-webkit-min-device-pixel-ratio:0) { div .acc { margin-top: -300%; } }`
  collapses all 3 panels by default — but only in clients that match that
  WebKit/Blink media-feature hack.
- `#em_drop:checked + .acc { margin-top: 0%; }` pulls a panel back into view
  when its checkbox is checked (i.e. when its bar has been clicked).

Unlike the carousel module, there's no separate fallback markup here. The
degradation is built into the media query itself: a client that doesn't
match `-webkit-min-device-pixel-ratio` never applies the `-300%` collapse
rule, so it falls back to the plain base rule (`margin-top: 0%`) — meaning
all 3 panels simply render permanently open, stacked one after another. No
duplicated content, no broken layout either way.

## Where it works vs. where it doesn't

**Renders as a real click-to-expand accordion:**
- Apple Mail — macOS and iOS
- Outlook for Mac
- Gmail app — iOS and Android
- Gmail webmail — desktop browser
- Most other WebKit/Blink-based mail renderers

**Falls back to all 3 panels permanently expanded (nothing hidden, nothing broken, just not collapsible):**
- Outlook for Windows — all versions, including Microsoft 365 desktop
  (Word's rendering engine ignores the media query entirely; this is also
  why the file is full of `<!--[if mso]>` conditional table markup)
- Outlook.com / Outlook Web Access
- Yahoo Mail, Samsung Email app, Windows 10/11 Mail app, and other clients
  that don't evaluate the `-webkit-min-device-pixel-ratio` media feature

Either way every reader can read every panel — the worst case is just no
collapse/expand interaction, not lost content.

## Reusing this module elsewhere

The whole block between `<!-- START: Accordion -->` and `<!-- END: Accordion -->`
can be copy-pasted into another template as-is. Keep these untouched or the
mechanism breaks:
- The checkbox `id`s (`em_drop`, `em_drop2`, `em_drop3`) and the `.accN` /
  `.em_wrapperN` classes tying each `<label>` to its content block
- The `<style>` block at the top of the accordion (the `:checked` rule, the
  `-300%` collapse rule, and the transition) — leave this exactly as it is
- Only 3 panels are wired up this way; adding a 4th means adding a matching
  `em_drop4` / `.acc4` pair to the CSS too

Safe to customize per panel: the trigger bar's label text, the placeholder
photo block (swap for a real photo, or any other HTML), the heading, the
button label/link, and the brand colors (`#C1560C` orange, `#6B3308` dark
orange).

## Before using this for a real campaign

- Replace every `#LINK` href with a real URL
- Replace the "Logo" text badge with a real logo image or wordmark
- Replace the 3 colored trigger bars and 3 colored photo blocks with real
  content
- Replace the Lorem Ipsum legal line with real terms and conditions
- Add back any personalization your ESP needs (this version has none — no
  merge tags, the header text is static)
