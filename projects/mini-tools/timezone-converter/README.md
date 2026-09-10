# Timezone Converter

Pick a date/time in one zone and see it translated into a list of other cities, DST-aware, with same-day/next-day indicators.

## Files

- `index.html` — markup/structure
- `style.css` — warm paper styling, shared with the site's other mini-tools
- `script.js` — conversion logic and UI behavior
- `fonts/` — self-hosted Inter and JetBrains Mono (variable woff2, copied from the site's own `/fonts`)

## Usage

Open `index.html` in any modern browser. No build step, no server required.

Set a date/time and its zone, then add any number of cities to compare. Conversion uses the browser's native `Intl` timezone data, so DST is handled automatically.

## How it works

Timezone conversion derives the UTC instant for a given wall-clock time in a zone by comparing `Intl.DateTimeFormat`'s rendered fields against a UTC guess, then reformats that instant into every target zone — no timezone library needed.

## Privacy

Everything runs entirely in the browser. Nothing is ever sent anywhere.
