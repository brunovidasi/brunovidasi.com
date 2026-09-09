# Password Generator

Generate strong random passwords or memorable Diceware-style passphrases, with a live entropy breakdown (bits, combinations, and estimated offline crack time).

## Files

- `index.html` — markup/structure
- `style.css` — warm paper styling shared with the other mini-tools
- `script.js` — random generation via Web Crypto, entropy math, and UI behavior
- `wordlist.js` — the EFF short wordlist (1296 words, CC BY 3.0 US) used for passphrases
- `fonts/` — self-hosted Inter and JetBrains Mono (variable woff2, copied from the site's own `/fonts`)

## Usage

Open `index.html` in any modern browser. No build step, no server, no network calls.

1. Pick **Password** (random characters, 4–64 length, toggle lower/upper/digits/symbols, optionally exclude visually ambiguous characters) or **Passphrase** (3–10 dictionary words, optional capitalization, optional trailing digit, choice of separator).
2. Click **Generate** (or press Enter) to get a new value; **Copy** puts it on the clipboard.
3. The **Entropy** panel updates live: bits of entropy, a strength bar, the size of the character/word pool used, total possible combinations, and a rough offline brute-force crack-time estimate.
4. The last 5 generated values are kept in an in-memory **Recent** list for the session (never persisted) so you can compare options before picking one.

## How it works

- Randomness comes from `crypto.getRandomValues` (Web Crypto CSPRNG), rejection-sampled to avoid modulo bias — never `Math.random()`.
- Password entropy is `length × log2(pool size)`; passphrase entropy is `words × log2(wordlist size)`, plus `log2(10)` if a random digit is appended.
- Crack-time assumes a 10¹² guesses/second offline attacker and averages half the keyspace, which is the standard back-of-envelope estimate — real-world time varies hugely with hashing algorithm, hardware, and whether the attack is online or offline.

## Privacy

Everything happens locally in the browser. Nothing is logged, stored, or sent to a server — including the "recent" history, which lives only in page memory and disappears on reload.
