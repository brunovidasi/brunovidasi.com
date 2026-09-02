# Tabs eDM Module (4 Items)

`index.html` is a self-contained, table-based HTML email that exists to
showcase one thing: a **4-tab interactive switcher** built entirely with
HTML/CSS (no JavaScript, no AMP for Email). Everything else in the file —
header, hero banner, footer, legal copy — is placeholder chrome so the tabs
have a realistic email around them. All content is generic: the logo is a
plain "Logo" badge, every link points to `#LINK`, and the legal copy is
Lorem Ipsum.

There are no image assets in this template. The original design used a
background-image chevron on each tab pill as a decorative direction cue —
since that file wasn't available, it's recreated as a plain `▸` character
after each tab's label instead. Everything else — the logo, hero banner,
and each tab's "photo" banner — is a colored HTML/CSS block, not a picture.
That's deliberate: it proves a tab isn't limited to an image, it can hold
any HTML content (text, a button, a table, whatever the campaign needs).

This module uses a **blue** palette (`#1665A8` / `#0A3350`) instead of the
carousel's teal or the accordion's orange, purely so the three module files
are easy to tell apart at a glance.

## How the tabs work

Four radio inputs (`#tab1`–`#tab4`, sharing `name="tab-a"` so only one can be
checked at a time) sit inside a chain of nested `<label>` elements. Clicking
a tab's label checks its radio, and CSS does the rest:

- `#tab1:checked ~* .content1` (and the equivalent for tabs 2–4) shows that
  tab's content panel.
- `#tab1:checked + span .title1` (and equivalents) recolors the active tab's
  pill to the dark blue so it blends into the panel background, while the
  inactive tabs stay the bright blue clickable color.
- A separate always-checked "detector" radio (`.tabcheck`) exists purely to
  test whether the client supports `:checked` + general-sibling (`~`)
  selectors at all: `.tabcheck:checked ~ .fallback { display:none; }` hides
  the fallback markup only in clients where that CSS actually works.

Like the carousel module (and unlike the accordion), this ships **two
versions of the same 4 panels**: the interactive tab-switcher, and a
`.fallback` block with all 4 panels stacked statically. A client either gets
one or the other — never a broken or empty module.

On narrow screens the tab bar switches from 4 side-by-side pills to 4
full-width stacked bars (see the `@media (max-width:619px)` rule in the
module's own `<style>` block).

## Where it works vs. where it doesn't

**Renders as a real click-to-switch tab bar:**
- Apple Mail — macOS and iOS
- Outlook for Mac
- Gmail app — iOS and Android
- Gmail webmail — desktop browser, wide viewport

**Falls back to all 4 panels stacked and fully visible (nothing hidden, nothing broken, just not switchable):**
- Outlook for Windows — all versions, including Microsoft 365 desktop
  (Word's rendering engine has no CSS3 support; this is also why the file
  is full of `<!--[if mso]>` conditional table markup)
- Outlook.com / Outlook Web Access
- Yahoo Mail, Samsung Email app, Gmail on narrow/mobile web, Windows 10/11
  Mail app, and other clients that don't support the checkbox-hack pattern

This is inherent to the technique in general, not a bug in this file — every
reader can read every tab's content either way.

## Reusing this module elsewhere

The whole block between `<!-- START: Tabs -->` and `<!-- END: Tabs -->` can
be copy-pasted into another template as-is. Keep these untouched or the
mechanism breaks:
- The radio `id`s (`tab1`–`tab4`, plus the `.tabcheck` detector) and the
  `.titleN` / `.contentN` classes tying each tab to its panel
- The nested `<label>` chain — each tab's `<label>` wraps the next one, so
  don't try to flatten it
- The module's own `<style>` block (the `:checked` rules, the fallback
  detector, and the mobile stacking rule) — leave this exactly as it is
- The `.fallback` block at the end, with all 4 panels duplicated

Safe to customize per tab: the label text, the placeholder banner (swap for
a real photo, or any other HTML), the heading, the button label/link, and
the brand colors (`#1665A8` blue, `#0A3350` dark blue).

## Before using this for a real campaign

- Replace every `#LINK` href with a real URL
- Replace the "Logo" text badge with a real logo image or wordmark
- Replace the 4 colored placeholder banners with real photos (or other HTML) —
  remember each one appears twice (interactive + fallback) and both copies
  need updating
- Replace the Lorem Ipsum legal line with real terms and conditions
- Add back any personalization your ESP needs (this version has none — no
  merge tags, the header text is static)
