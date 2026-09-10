# JS Beautifier

The inverse of the [JS Minifier](../js-minifier): paste minified JavaScript and get it reformatted with consistent indentation and line breaks — every identifier, string, regex, template literal, and comment kept exactly as written, with only whitespace added. A built-in verification pass confirms nothing else changed.

## Files

- `index.html` — markup/structure
- `style.css` — warm paper styling, shared with the other mini-tools
- `script.js` — the tokenizer, formatter, verification pass, and UI behavior
- `fonts/` — self-hosted Inter and JetBrains Mono (variable woff2, copied from the site's own `/fonts`)

## Usage

Open `index.html` in any modern browser. No build step, no server, no network calls.

1. Paste minified JS into the input box, or use **Load file** / **Try example**.
2. The formatted result appears on the right as you type.
3. Choose an indent size (2 spaces, 4 spaces, or a tab).
4. Click **Copy** to copy the formatted output.

## How it works

Uses the exact same tokenizer as the [JS Minifier](../js-minifier) (strings, template literals with nested `${ ... }`, regex literals, comments), so the two tools — and this tool's own verification check — can never disagree about what a token is.

Formatting only ever does two things: re-indents based on `{ }` nesting depth, and breaks the line at statement boundaries (`;`, `{`, `}`). Everything else — every identifier, operator, string, regex, and template literal — is copied verbatim. In particular, no space is ever added *around* an operator, because doing that safely means first telling multi-character operators (`===`, `&&`, `=>`, `++`, …) apart from adjacent single-character punctuation, and getting that wrong risks turning `a+ +b` into `a++b`. That's a real limitation for readability polish, but it means every space this tool inserts is provably safe.

The one piece of real ambiguity — a `;` inside a `for (;;)` header vs. one ending a statement — is resolved by tracking paren depth: a semicolon only breaks the line when it's outside any `(...)`. A `}` that closes a `${ ... }` template interpolation is tracked separately from a real block-closing `}`, so formatting never inserts a line break in the middle of a template expression.

Commas are never given their own line — that keeps function-call arguments on one line, as a side effect object/array literal entries can end up sharing a line with a nested block that follows them. Indentation and statement breaks (the two things minified code actually needs to become readable) are unaffected either way.

After formatting, both the original and the formatted source are re-normalized (whitespace and comments stripped, via the shared tokenizer) and compared. If they don't match, a warning is shown instead of a false "verified".

## Privacy

Everything happens locally in the browser. Nothing is sent to a server.
