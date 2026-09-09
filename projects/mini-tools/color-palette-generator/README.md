# Color Palette Generator

Generate an 11-step tonal palette (50–950) from a base color or an uploaded image's dominant colors, with live WCAG contrast-ratio checks for every shade.

## Files

- `index.html` — markup/structure
- `style.css` — warm paper styling shared with the other mini-tools
- `script.js` — color math, k-means image color extraction, WCAG contrast checks, and UI behavior
- `fonts/` — self-hosted Inter and JetBrains Mono (variable woff2, copied from the site's own `/fonts`)

## Usage

Open `index.html` in any modern browser. No build step, no server, no network calls.

1. Pick a base color (color picker, hex input, presets, or "Random"), or switch to **From image** and drop in a photo — its dominant colors are extracted client-side and any of them can become the base color.
2. The **Tonal palette** section generates 11 shades (50 lightest → 950 darkest) from that base's hue and saturation. Each swatch shows its hex (click to copy) and small `W`/`B` badges giving its contrast ratio against white and black text.
3. Use **Copy CSS variables** or **Copy JSON** to grab the whole palette at once.
4. The **Contrast checker** lets you pick any two colors from the palette (or white/black) as text/background, see a live preview, and check pass/fail against WCAG AA and AAA thresholds for normal and large text.

## How it works

- The tonal scale keeps the base color's hue and saturation and re-maps lightness to a fixed 11-step curve (97% → 11%), placing the exact input color at whichever step its own lightness is closest to.
- Image color extraction downsamples the image to a small canvas, then runs k-means clustering (k=6, k-means++ init) directly on pixel RGB values to find genuinely dominant colors rather than just the most frequent exact pixel value.
- Contrast ratios follow the WCAG 2 formula: relative luminance from linearized sRGB channels, then `(L1 + 0.05) / (L2 + 0.05)`.

## Privacy

Everything happens locally in the browser, including image processing (the image is drawn to an in-memory canvas and never leaves the page). Nothing is sent to a server.
