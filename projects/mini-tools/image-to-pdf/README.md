# Image to PDF

Combine one or more images into a single PDF — entirely in the browser, no upload, no server round trip.

## Files

- `index.html` — markup/structure
- `style.css` — warm paper styling shared with the other mini-tools
- `script.js` — image handling, reordering, and PDF assembly
- `vendor/pdf-lib.min.js` — [pdf-lib](https://pdf-lib.js.org/), used to build the PDF and embed each image
- `fonts/` — self-hosted Inter and JetBrains Mono (variable woff2, copied from the site's own `/fonts`)

## Usage

Open `index.html` in any modern browser. No build step, no server, no network calls.

1. Drag in one or more images (or click to choose them).
2. Reorder with the ↑/↓ buttons on each card, or remove any you don't want.
3. Pick a page size — "Fit to each image" or "A4" — and click **Convert to PDF**.
4. Click the download link to save the result.

## How it works

Each image becomes one page, added in the order shown in the grid. JPEG and PNG files are embedded directly with pdf-lib; any other format (WebP, GIF, BMP, …) is first redrawn onto a `<canvas>` and re-encoded as PNG, since pdf-lib only embeds JPEG and PNG natively. "Fit to each image" makes every page exactly that image's own proportions; "A4" centers the image on a standard A4 page, choosing portrait or landscape automatically based on the image's aspect ratio.

## Limitations

- Very large images or a large number of them will take longer to encode and use more memory, since everything happens in the tab.
- Animated GIFs are flattened to their first frame — the PDF page is a still image.

## Privacy

Everything happens locally via the File API, `<canvas>`, and pdf-lib. Images are never uploaded — reading, embedding, and assembly all happen client-side, and nothing is logged or sent to a server.
