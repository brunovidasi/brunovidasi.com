# Regex Tester

A regular expression tester: type a pattern and flags, see every match highlighted live in your test string (including nested capture groups), get named groups broken out with their captured values per match, and reach for a common-patterns library or a clickable cheat sheet when you're stuck.

## Files

- `index.html` — markup/structure
- `style.css` — warm paper styling, shared with the other mini-tools
- `script.js` — regex evaluation, highlighting, and UI behavior
- `fonts/` — self-hosted Inter and JetBrains Mono (variable woff2, copied from the site's own `/fonts`)

## Usage

Open `index.html` in any modern browser. No build step, no server, no network calls.

1. Type a pattern in the pattern bar and toggle flags (`g i m s u y`) as chips.
2. Type or paste text into the test string box — matches highlight live, with nested capture groups shown as an underline inside the match.
3. Click any entry in the **Common patterns** library to load a ready-made pattern (email, URL, IPv4, dates, etc.) plus a sample string.
4. Click any row in the **Cheat sheet** to insert that token at the cursor in the pattern field.
5. Named groups (`(?<name>...)`) get their own panel listing every value they captured across all matches.

## How it works

Matching runs through the browser's own `RegExp` engine with the `d` (hasIndices) flag always added internally, so every match and every capture group carries precise start/end offsets. Those offsets are turned into a tree of non-overlapping, properly nested intervals (a regex group's span is always nested inside its parent, never partially overlapping), which is rendered as nested `<mark>`/`<span>` elements behind a transparent-text textarea — the classic "highlight-behind-a-textarea" technique, kept in sync on scroll.

## Privacy

Everything happens locally in the browser. Nothing is sent to a server.
