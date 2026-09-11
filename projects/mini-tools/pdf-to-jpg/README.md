# PDF to JPG

Convert every page of a PDF into a JPG image — entirely in the browser, no upload, no server round trip.

## Files

- `index.html` — markup/structure
- `style.css` — warm paper styling shared with the other mini-tools
- `script.js` — page rendering, JPG encoding, and zip packaging
- `vendor/pdf.min.js` + `vendor/pdf.worker.min.js` — [pdf.js](https://mozilla.github.io/pdf.js/) (Mozilla), used to render each page onto a canvas
- `vendor/jszip.min.js` — [JSZip](https://stuk.github.io/jszip/), used to bundle every page into one `.zip` download
- `fonts/` — self-hosted Inter and JetBrains Mono (variable woff2, copied from the site's own `/fonts`)

## Usage

Open `index.html` in any modern browser. No build step, no server, no network calls.

1. Drag in a PDF (or click to choose one). Every page renders automatically.
2. Adjust **Quality** and **Resolution** if needed — quality re-encodes instantly, resolution re-renders the pages.
3. Download pages one at a time, or click **Download all (.zip)** for the whole set.

## How it works

pdf.js renders each page onto an off-screen `<canvas>` at the chosen resolution (1x/2x/3x the page's native size), then `canvas.toBlob('image/jpeg', quality)` encodes it as a JPG. Changing the quality slider just re-encodes the already-rendered canvases (fast); changing the resolution re-renders every page from the PDF (slower, since it changes the pixel dimensions). Multi-page downloads are bundled client-side with JSZip.

## Limitations

- This rasterizes each page — the output is a picture of the page, not selectable text. Use the [PDF ⇄ Word Converter](../pdf-word-converter/index.html) if you need the text itself.
- Very high resolution on a PDF with many pages uses more memory and takes longer, since every rendered page is kept in memory until you leave or replace the file.
- A password-protected PDF must have its password removed first, with [Unlock PDF](../pdf-password-remover/index.html).

## Privacy

Everything happens locally via the File API, pdf.js's WebWorker, `<canvas>`, and JSZip. The PDF is never uploaded — rendering, encoding, and zipping all happen client-side, and nothing is logged or sent to a server.
