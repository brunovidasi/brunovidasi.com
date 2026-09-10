# JS Minifier

A byte-safe JavaScript minifier: paste JS and get comments, indentation, and blank lines stripped out, with nothing else touched — no identifier renaming, no line-joining, no semicolon insertion or removal. A built-in verification pass confirms the output is semantically identical to the input before you trust it.

## Files

- `index.html` — markup/structure
- `style.css` — warm paper styling, shared with the other mini-tools
- `script.js` — the tokenizer, minifier, verification pass, and UI behavior
- `fonts/` — self-hosted Inter and JetBrains Mono (variable woff2, copied from the site's own `/fonts`)

## Usage

Open `index.html` in any modern browser. No build step, no server, no network calls.

1. Paste JS into the input box, or use **Load file** / **Try example**.
2. The minified result appears on the right as you type, with a byte-size comparison and percentage saved.
3. Toggle **Strip comments** to drop all comments, and **Keep `/*! ... */`, `@license` & `@preserve` comments** to always preserve license banners even when stripping.
4. Click **Copy** to copy the minified output.

## How it works

A single tokenizer walks the source once, classifying every character as one of: a string, a template literal (including nested `${ ... }` expressions, which can themselves contain more templates, strings, regexes, and comments), a regex literal, a line/block comment, or plain code. Both the minifier and the verification check consume this *same* tokenizer, so they can never disagree about where a comment or string starts and ends.

Regex vs. division (`/`) is resolved with the standard heuristic: it's a regex unless the previous token was something a value could follow — an identifier or number that isn't a keyword like `return`, a string/template/regex literal, or a closing `)`/`]`. This covers ordinary code correctly; the verification pass exists precisely because no regex-free heuristic is airtight against deliberately adversarial input.

Whitespace handling is deliberately conservative:
- A run of whitespace containing a newline **always** collapses to exactly one `\n` — it is never dropped outright, so automatic semicolon insertion (ASI) can never be affected by minifying (the classic `return\n{...}` hazard).
- A purely horizontal run of spaces/tabs collapses to a single space, or is dropped entirely only when adjacent to punctuation that can never merge with a neighboring token (`{ } ( ) [ ] ; , :`) — never around operators, so `a - -b` can never become `a--b`.

After minifying, both the original and the minified source are re-normalized (all whitespace and comments stripped via the same tokenizer) and compared. If they don't match, a warning is shown instead of a false "verified".

## Privacy

Everything happens locally in the browser. Nothing is sent to a server.
