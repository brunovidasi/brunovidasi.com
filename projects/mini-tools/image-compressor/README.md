# Image Compressor

A browser-based image compressor: drag in photos or screenshots, tune quality/format/size, and download the result — all client-side, nothing ever gets uploaded.

## Files

- `index.html` — markup/structure
- `style.css` — warm, paper-and-photos styling
- `script.js` — compression logic and UI behavior
- `fonts/` — self-hosted Inter and JetBrains Mono (variable woff2, copied from the site's own `/fonts`)
- `vendor/jszip.min.js` — self-hosted JSZip, used for the "download all" zip

## Usage

Open `index.html` in any modern browser. No build step or server required, and no network connection either — every asset is local, so the tool works fully offline.

1. Drag images into the dropzone (or click it to browse).
2. Pick an output format, or leave it on **Keep format** — it auto-picks JPEG for opaque images and WebP for images with transparency, whichever compresses better.
3. Adjust the **Quality** slider, or turn on **Limit max size** and give it a KB target (e.g. 500) — it searches for the highest quality that fits.
4. Optionally turn on **Resize to max** to cap the longest side in pixels.
5. Click a thumbnail to compare original vs. compressed with a drag slider. Download images one at a time or all together as a `.zip`.

## How it works

Each image is decoded and drawn to an off-screen `<canvas>`, then re-encoded with `canvas.toBlob()` at the chosen format/quality. When a KB target is set, it binary-searches quality (and, if that's not enough, progressively downscales) until the result fits — checking a cheap low-quality encode first so it doesn't waste time searching sizes that were never going to work.

If a re-encode ever comes out *larger* than the original (common when re-compressing an already-optimized file, or a lossless format like PNG), the tool just keeps the original file instead — you'll never end up with a heavier download than what you started with.

## Privacy

Everything happens locally in the browser via the Canvas API. Images are never sent to a server.
