# CSS Minifier

A byte-safe CSS minifier: paste CSS and get whitespace (and optionally comments) stripped out, with nothing else touched — no value rewriting, no semicolon dropping, no color shortening. A built-in verification pass confirms the output is semantically identical to the input before you trust it.

## Files

- `index.html` — markup/structure
- `style.css` — warm paper styling, shared with the other mini-tools
- `script.js` — the minifier, verification pass, and UI behavior
- `fonts/` — self-hosted Inter and JetBrains Mono (variable woff2, copied from the site's own `/fonts`)

## Usage

Open `index.html` in any modern browser. No build step, no server, no network calls.

1. Paste CSS into the input box, or use **Load file** / **Try example**.
2. The minified result appears on the right as you type, with a byte-size comparison and percentage saved.
3. Toggle **Strip comments** to drop all comments, and **Keep `/*! ... */` & conditional/MSO comments** to always preserve license banners and IE conditional comments even when stripping.
4. Click **Copy** to copy the minified output.

## How it works

A hand-rolled tokenizer walks the CSS character by character, tracking whether it's inside a string literal, a comment, or plain code. Whitespace is collapsed to a single space and dropped only where it's provably safe (around `{ } ; : , > + ~ (` `)`); trailing semicolons before a closing brace are dropped too. String contents (`content: " / ";`, `url("a;b.css")`) are always copied verbatim, so nothing inside a string is ever touched.

After minifying, both the original and the minified CSS are re-normalized (whitespace and comments stripped from each) and compared. If they don't match, a warning is shown instead of a false "verified" — a safety net so the output can be trusted to be a 1:1 match of the input.

## Privacy

Everything happens locally in the browser. Nothing is sent to a server.
