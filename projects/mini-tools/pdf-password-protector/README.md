# PDF Password Protector

Add a password to a PDF — entirely in the browser, no upload, no server round trip.

## Files

- `index.html` — markup/structure
- `style.css` — warm paper styling shared with the other mini-tools
- `script.js` — form handling, validation, and orchestration
- `encrypt.js` — hand-implemented PDF standard security handler (write side): generates a random file key, derives `/O`, `/U`, `/OE`, `/UE` and `/Perms` from the password, AES-256-encrypts every stream and string against pdf-lib's low-level object model, and attaches the resulting `/Encrypt` dictionary
- `vendor/pdf-lib.min.js` — [pdf-lib](https://pdf-lib.js.org/), the low-level object model `encrypt.js` encrypts against and re-serializes with
- `vendor/pdf.min.js` + `vendor/pdf.worker.min.js` — [pdf.js](https://mozilla.github.io/pdf.js/) (Mozilla), used only to verify the result genuinely requires the password before it's offered for download
- `fonts/` — self-hosted Inter and JetBrains Mono (variable woff2, copied from the site's own `/fonts`)

## Usage

Open `index.html` in any modern browser. No build step, no server, no network calls.

1. Drag in a PDF (or click to choose one).
2. Enter and confirm a password.
3. Optionally uncheck any of "Printing", "Copying text & images", or "Editing & annotating" to restrict what's allowed once the file is unlocked — all three are allowed by default.
4. Click **Add password**. Once verified, click **Download protected PDF** to save the result, named `<original>-protected.pdf`.

## How it works

- pdf-lib can read and write PDFs, but has no encryption support at all — it will refuse to load anything already encrypted, and has no code path for producing an encrypted file. `encrypt.js` implements the write side of the modern PDF standard security handler from scratch: AES-256, revision 6 (the "hardened hash" scheme from ISO 32000-2, used by Acrobat 9+ and supported by every current PDF reader — Acrobat, Preview, Chrome, Firefox, mobile apps).
- A random 32-byte file key is generated, then `/O`, `/U`, `/OE`, and `/UE` are derived from your password per spec (the password doubles as both the "user" and "owner" password, since this tool only exposes one password field), plus a `/Perms` integrity block encoding the permission checkboxes.
- Every stream and string in the document is then AES-256-CBC encrypted in place (via the browser's native `SubtleCrypto`, with a fresh random IV each time), the resulting `/Encrypt` dictionary is attached to the trailer, and pdf-lib re-saves the file.
- Before the download link appears, the result is handed to pdf.js with an empty password purely as a self-check — confirming it actually throws (i.e. genuinely requires the real password) rather than silently shipping something unprotected.
- If the input file already has a password, this tool won't touch it — remove the existing password first with the [PDF Password Remover](../pdf-password-remover/index.html), then add a new one here.

## Limitations

- Only supports adding fresh AES-256 protection to an unencrypted PDF — it can't change an existing password or add a *separate* owner (permissions-only) password, since the UI only exposes a single password used for both roles.
- The permission checkboxes are advisory, per the PDF spec — they're honored by compliant readers (which is effectively all of them) but aren't a hard technical barrier the way the password itself is.
- There's no password recovery. If you forget it, the file is unrecoverable without the password (this is the whole point of encryption) — treat this the same as any other password you'd need to remember or store.

## Privacy

Everything happens locally via the File API, pdf-lib, and pdf.js's WebWorker. The PDF is never uploaded — encryption and verification all happen client-side, and nothing is logged or sent to a server.
