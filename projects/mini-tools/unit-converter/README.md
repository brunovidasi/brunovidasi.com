# Unit Converter

Length, weight, temperature, volume, area, speed, data storage, and time — with a full breakdown into every unit in the category as you type.

## Files

- `index.html` — markup/structure
- `style.css` — warm paper styling, shared with the site's other mini-tools
- `script.js` — conversion math and UI behavior
- `fonts/` — self-hosted Inter and JetBrains Mono (variable woff2, copied from the site's own `/fonts`)

## Usage

Open `index.html` in any modern browser. No build step, no server required.

Choose a category, type a value, pick from/to units (or swap them). Every other unit in that category updates live below — you can also type directly into any of those cells to recompute from there.

## How it works

Unit conversion goes through a common base unit per category (meters, kilograms, liters, etc.) via linear factors; temperature uses dedicated formulas since it isn't a linear scale from zero.

## Privacy

Everything runs entirely in the browser. Nothing is ever sent anywhere.
