# JSON/CSV Converter

Paste messy JSON or CSV to validate, pretty-print, minify, and convert between the two formats — or switch to Compare mode and diff two versions line-by-line with word-level highlighting.

## Files

- `index.html` — markup/structure for both modes (Format & Convert, Compare Versions)
- `style.css` — warm paper styling, shared with the other mini-tools
- `script.js` — CSV parser/serializer, JSON helpers, JSON⇄CSV conversion, the diff engine (a from-scratch Myers shortest-edit-script implementation, reused from the Text Diff Checker), and UI wiring
- `fonts/` — self-hosted Inter and JetBrains Mono (variable woff2, copied from the site's own `/fonts`)

## Usage

Open `index.html` in any modern browser. No build step, no server, no network calls.

### Format & Convert

1. Paste JSON or CSV into **Input** — the format is auto-detected (or force it with the **Input is** toggle).
2. Choose **Output as** JSON or CSV. Picking the same type as the input just formats it; picking the other converts it.
3. JSON output options: indent width (2/4 spaces, tab, or minified) and **Sort keys**.
4. CSV options: input/output delimiter (comma, semicolon, or tab — auto-detected by default), whether the **first row is a header**, and whether to auto-detect number/boolean/null values when converting CSV to JSON.
5. Invalid JSON shows the exact line and column of the syntax error. **Copy** or **Download** the result.

### Compare Versions

1. Paste two versions of JSON or CSV into **Version A** and **Version B** (or load files).
2. Each side is parsed and re-serialized the same way (pretty JSON with optional key-sorting, or normalized CSV) before diffing, so differences in formatting or key order don't show up as noise unless you want them to.
3. The diff is shown side-by-side with line numbers; changed lines are further highlighted word-by-word. **Collapse unchanged lines** hides long unchanged stretches. **Copy diff** puts a plain `+`/`-` prefixed version on the clipboard.

## How it works

CSV parsing is a small hand-written RFC 4180-ish parser (handles quoted fields with embedded delimiters, quotes, and newlines). JSON parsing is native `JSON.parse`, with error positions decoded into line/column for readable messages. Converting JSON→CSV flattens an array of objects into a header + rows (nested values are stringified into the cell); CSV→JSON uses the header row as keys. The diff view reuses the same generic Myers diff (shortest edit script) as the Text Diff Checker, run first over lines and then, within changed lines, over words.

## Privacy

Everything happens locally in the browser. Nothing is sent to a server — including files loaded via **Load file**, which are read with the `FileReader` API and never leave the page.
