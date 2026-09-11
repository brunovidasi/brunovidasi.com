# Unlock PDF

Unlock a password-protected PDF and download a clean, password-free copy — entirely in the browser, no upload, no server round trip.

## Files

- `index.html` — markup/structure
- `style.css` — warm paper styling shared with the other mini-tools
- `script.js` — password detection/retry flow, mode selection, and orchestration
- `decrypt.js` — hand-implemented PDF standard security handler: derives the real file encryption key from the password and decrypts the file's streams/strings in place, keeping the original vector/text content intact
- `vendor/pdf-lib.min.js` — [pdf-lib](https://pdf-lib.js.org/), used as the low-level object model `decrypt.js` decrypts against and re-serializes with
- `vendor/pdf.min.js` + `vendor/pdf.worker.min.js` — [pdf.js](https://mozilla.github.io/pdf.js/) (Mozilla), used to validate the password and, if needed, render each page for the image-based fallback
- `vendor/jspdf.umd.min.js` — [jsPDF](https://github.com/parallax/jsPDF), used only by the fallback to assemble rendered pages into a new PDF
- `fonts/` — self-hosted Inter and JetBrains Mono (variable woff2, copied from the site's own `/fonts`)

## Usage

Open `index.html` in any modern browser. No build step, no server, no network calls.

1. Drag in a PDF (or click to choose one).
2. If the file needs a password, a field appears — type it and click **Unlock** (or press Enter). A wrong password shows an inline error so you can try again.
3. With **"Keep the original PDF as-is"** checked (the default), the password is removed while preserving the exact original content — same vector text, same fonts, same images, just unlocked.
4. Click **Download unlocked PDF** to save the result, named `<original>-unlocked.pdf`.

## How it works

- **Vector-preserving removal (default).** `decrypt.js` implements the PDF standard security handler from scratch (PDF32000-1:2008 §7.6, plus the AES-256 "hardened hash" extension from ISO 32000-2 used by modern Acrobat): it derives the file's real encryption key from your password (trying it as both the user and owner password, same as any PDF viewer would), then walks every object in the file — decrypting stream content and string values in place with RC4 or AES (AES via the browser's native `SubtleCrypto`) — expands any compressed object streams along the way, drops the `/Encrypt` dictionary, and has pdf-lib re-save the result. The output is the *same* PDF: same fonts, same vector text, same images, just no longer encrypted.
- **Image-based fallback.** If a file uses something this hand-written decryptor doesn't support, vector-preserving removal throws and the tool automatically falls back to the original approach: pdf.js renders each page to a high-resolution `<canvas>` (~144 DPI, capped so very large pages don't blow past ~3000px), and those images are reassembled into a new PDF with jsPDF. This always works, but text in the result won't be selectable, searchable, or copyable.
- The first load attempt is silent (empty password): if the file isn't actually encrypted, it skips straight to offering the original file — nothing to remove.

## Limitations

- Vector-preserving removal covers the standard PDF security handler (RC4 40–128-bit; AES-128/256, including the modern "hardened hash" revision). Anything outside that — an unusual or non-standard encryption scheme — falls back to the image-based renderer, whose output loses selectable text as described above.
- You need to know the file's password — this removes the password, it doesn't crack or guess it.
- The image-based fallback is slower and uses noticeably more memory on long documents, since every page is rendered as a full-resolution image before being reassembled.

## Privacy

Everything happens locally via the File API, pdf-lib, and pdf.js's WebWorker. The PDF is never uploaded — decryption, rendering, and reassembly all happen client-side, and nothing is logged or sent to a server.
