# Text Diff Checker

A side-by-side text diff tool: paste (or upload) two versions of a text and see exactly what changed, aligned line-by-line, with the specific words or characters that differ highlighted inside each changed line.

## Files

- `index.html` — markup/structure
- `style.css` — warm paper styling, shared with the other mini-tools
- `script.js` — the diff engine (a from-scratch Myers shortest-edit-script implementation) and UI behavior
- `fonts/` — self-hosted Inter and JetBrains Mono (variable woff2, copied from the site's own `/fonts`)

## Usage

Open `index.html` in any modern browser. No build step, no server, no network calls.

1. Paste or type text into **Original** and **Changed** (or use **Load file** to read a local `.txt`/`.md`/etc file into either side).
2. The diff updates live below, aligned side-by-side with line numbers.
3. Lines that were only edited (not fully added/removed) show the exact changed words or characters highlighted inside the line — toggle **Compare by** between Words and Characters.
4. **Ignore case** and **Ignore whitespace** affect both which lines count as equal and which words/characters are highlighted within a changed line.
5. **Collapse unchanged lines** hides long unchanged stretches behind a "click to expand" row, keeping a few lines of context around each change (like a code review diff).
6. **Copy diff** puts a plain `+`/`-` prefixed text version of the diff on the clipboard. **Swap** flips Original and Changed.

## How it works

Both the line-level diff and the intra-line word/character diff run through the same generic Myers diff (shortest edit script) implementation operating on arrays — lines for the outer diff, tokens (words or individual characters) for the inner one. A change block (a run of deleted lines immediately followed/preceded by inserted lines) pairs deletions with insertions positionally so each modified line gets its own intra-line diff; any leftover unpaired lines render as pure additions or removals.

## Privacy

Everything happens locally in the browser. Nothing is sent to a server — including files loaded with **Load file**, which are read with the `FileReader` API and never leave the page.
