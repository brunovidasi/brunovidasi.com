# Currency Converter

40+ currencies, converted at a live exchange rate, with quick-amount shortcuts.

## Files

- `index.html` — markup/structure
- `style.css` — warm paper styling, shared with the site's other mini-tools
- `script.js` — conversion logic and UI behavior
- `fonts/` — self-hosted Inter and JetBrains Mono (variable woff2, copied from the site's own `/fonts`)

## Usage

Open `index.html` in any modern browser. No build step required.

Type an amount and pick the two currencies. Rates are fetched once on load; a static, clearly-labeled approximate table is used as a fallback if the network request fails.

## How it works

Currency conversion normalizes everything through USD using rates from a free, keyless API (`open.er-api.com`).

## Privacy

The tool makes one outbound request to a free exchange-rate API to get live rates; nothing else is sent anywhere, and the tool still works offline with approximate rates.
