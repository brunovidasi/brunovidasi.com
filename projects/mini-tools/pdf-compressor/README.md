# PDF Compressor

Shrink a PDF by recompressing its embedded photos and scans — entirely in the browser, no upload, no server round trip.

## Files

- `index.html` — markup/structure
- `style.css` — warm paper styling shared with the other mini-tools
- `script.js` — form handling and orchestration (compression-level presets, progress, download)
- `compress.js` — walks the PDF's object graph via pdf-lib's low-level API, finds JPEG (`/DCTDecode`) image XObjects, decodes each with the browser's native JPEG decoder, redraws it on canvas at a lower quality/resolution, and swaps the re-encoded bytes back into the same stream
- `vendor/pdf-lib.min.js` — [pdf-lib](https://pdf-lib.js.org/), the low-level object model `compress.js` walks and re-saves with
- `fonts/` — self-hosted Inter and JetBrains Mono (variable woff2, copied from the site's own `/fonts`)

## Usage

Open `index.html` in any modern browser. No build step, no server, no network calls.

1. Drag in a PDF (or click to choose one).
2. Pick a compression level — Light, Recommended, or Strong (each trades more file size for more quality loss on embedded images).
3. Click **Compress PDF**, then **Download compressed PDF** to save the result, named `<original>-compressed.pdf`.

## How it works

- pdf-lib can read and write PDFs but has no image re-encoding support at all — it carries `/DCTDecode` (JPEG) streams through byte-for-byte. `compress.js` walks every indirect object in the file looking for Image XObjects whose sole filter is `/DCTDecode`.
- Each matching image's raw JPEG bytes are handed to the browser's own decoder via `createImageBitmap`, redrawn onto a `<canvas>` — downscaled first if it's larger than the level's resolution cap and has no soft mask (alpha) to keep pixel-aligned — then re-encoded with `canvas.toBlob('image/jpeg', quality)` at the level's quality setting.
- If the recompressed image isn't actually smaller, the original bytes are kept untouched — this only ever swaps in a result that shrinks that image.
- Once every image has been considered, pdf-lib re-saves the document with compact object streams. Text, fonts, vector graphics, and any non-JPEG images are never touched.

## Limitations

- Only recompresses images already stored as JPEG (`/DCTDecode`) — the overwhelming majority of what makes real-world PDFs large (photos, scanned pages). Other image encodings (raw bitmaps, JPEG2000, CCITT fax) are left as-is.
- Recompression is lossy — pick "Light" for anything you'll print or need to zoom into.
- A password-protected PDF must have its password removed first, with [Unlock PDF](../pdf-password-remover/index.html).

## Privacy

Everything happens locally via the File API, `<canvas>`, and pdf-lib. The PDF is never uploaded — decoding, recompression, and re-saving all happen client-side, and nothing is logged or sent to a server.
