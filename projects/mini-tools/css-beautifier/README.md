# CSS Beautifier

The inverse of the [CSS Minifier](../css-minifier): paste minified or messy CSS and get it reformatted with consistent indentation, one declaration per line, and (optionally) one selector per line — every selector, declaration, and comment kept exactly as written, with only whitespace added. A built-in verification pass confirms nothing else changed.

## Files

- `index.html` — markup/structure
- `style.css` — warm paper styling, shared with the other mini-tools
- `script.js` — the formatter, verification pass, and UI behavior
- `fonts/` — self-hosted Inter and JetBrains Mono (variable woff2, copied from the site's own `/fonts`)

## Usage

Open `index.html` in any modern browser. No build step, no server, no network calls.

1. Paste minified CSS into the input box, or use **Load file** / **Try example**.
2. The formatted result appears on the right as you type.
3. Choose an indent size (2 spaces, 4 spaces, or a tab), and toggle **One selector per line** for multi-selector rules like `.a, .b, .c {`.
4. Click **Copy** to copy the formatted output.

## How it works

The same string/comment-aware character walk used by the CSS Minifier buffers characters into a "segment" — a selector, a single declaration, or a standalone comment — until it hits a top-level `{`, `;`, or `}` (top-level meaning outside any `(...)`/`[...]`, so `:not(:hover)` and `rgba(0, 0, 0, .5)` never trip the boundary detection). What a segment *is* — a selector needing comma-splitting, or a `property: value` pair needing a space after the colon — is only knowable once you see what terminated it, which is exactly the ambiguity a naive find-and-replace formatter gets wrong: `a:hover{` and `color:red;` both contain a bare `:`, but only one of them should ever become `: `.

Selector text, declaration text, and comments are otherwise copied verbatim — no property names, values, units, or colors are ever rewritten, only the whitespace around them.

After formatting, both the original and the formatted CSS are re-normalized (whitespace and comments stripped) and compared, treating a `;` immediately before `}` as insignificant (the formatter may add or the input may have omitted one there). If they don't match, a warning is shown instead of a false "verified".

## Privacy

Everything happens locally in the browser. Nothing is sent to a server.
