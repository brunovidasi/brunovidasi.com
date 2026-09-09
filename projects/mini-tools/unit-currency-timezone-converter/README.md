# Unit / Currency / Timezone Converter

Three converters in one tool, switchable by tab:

- **Units** — length, weight, temperature, volume, area, speed, data storage, and time, with a full breakdown into every unit in the category as you type.
- **Currency** — 40+ currencies, converted at a live exchange rate.
- **Timezones** — pick a date/time in one zone and see it translated into a list of other cities, DST-aware, with same-day/next-day indicators.

## Files

- `index.html` — markup/structure
- `style.css` — warm paper styling, shared with the site's other mini-tools
- `script.js` — conversion math and UI behavior
- `fonts/` — self-hosted Inter and JetBrains Mono (variable woff2, copied from the site's own `/fonts`)

## Usage

Open `index.html` in any modern browser. No build step, no server required for units/timezones.

1. Pick a tab: **Units**, **Currency**, or **Timezones**.
2. **Units** — choose a category, type a value, pick from/to units (or swap them). Every other unit in that category updates live below.
3. **Currency** — type an amount and pick the two currencies. Rates are fetched once on load; a static, clearly-labeled approximate table is used as a fallback if the network request fails.
4. **Timezones** — set a date/time and its zone, then add any number of cities to compare. Conversion uses the browser's native `Intl` timezone data, so DST is handled automatically.

## How it works

Unit conversion goes through a common base unit per category (meters, kilograms, liters, etc.) via linear factors; temperature uses dedicated formulas since it isn't a linear scale from zero. Currency conversion normalizes everything through USD using rates from a free, keyless API (`open.er-api.com`). Timezone conversion derives the UTC instant for a given wall-clock time in a zone by comparing `Intl.DateTimeFormat`'s rendered fields against a UTC guess, then reformats that instant into every target zone — no timezone library needed.

## Privacy

Unit and timezone math run entirely in the browser. The currency tab makes one outbound request to a free exchange-rate API to get live rates; nothing else is sent anywhere, and the tool still works offline with approximate rates.
