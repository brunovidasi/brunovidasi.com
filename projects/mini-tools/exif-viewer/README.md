# EXIF Viewer & Editor

See exactly what metadata is embedded in your JPEG and PNG photos — camera make/model, exposure settings, timestamps, software, and GPS coordinates — then edit any field, delete individual fields, add new ones, or strip everything out at once, losslessly, straight from the raw file bytes.

## Files

- `index.html` — markup/structure
- `style.css` — warm paper styling shared with the other mini-tools
- `script.js` — JPEG/PNG binary parsing, a hand-written TIFF/EXIF reader *and* writer, PNG chunk rebuilding (with CRC32), and UI behavior
- `vendor/jszip.min.js` — used only for the optional "download all as .zip" bulk action
- `fonts/` — self-hosted Inter and JetBrains Mono (variable woff2, copied from the site's own `/fonts`)

## Usage

Open `index.html` in any modern browser. No build step, no server, no network calls.

1. Drag in one or more JPEG/PNG files (or click to choose them).
2. Each file shows a field count and, if present, a "sensitive" badge — click **View & edit** to see every field, grouped (GPS, Camera, Exposure, Date & Time, Software, Image), with privacy-sensitive fields (GPS coordinates, serial numbers, owner name) highlighted.
3. Editable fields (camera make/model, software, artist/copyright, dates, exposure time, F-number, ISO, focal length, GPS coordinates/altitude, lens info, user comment, orientation) show as inline inputs — change a value directly. Every field, editable or not, has a ✕ to remove it individually. The **+ Add field** picker at the bottom lets you add a new field (even to a file with no metadata at all).
4. Click **Save changes** to apply your edits/deletions/additions — the file is rebuilt and the card shows the new size with a **Download** link. Closing the dialog without saving discards any unsaved changes.
5. **Strip & download** removes *all* metadata from a file in one click. **Download all cleaned (.zip)** does the same for every loaded file at once. The "Keep color profile (ICC)" toggle controls whether the ICC profile survives stripping.

## How it works

- Files are parsed at the byte level: JPEG is walked marker-by-marker (`0xFFD8`... segments) to find `APP1` (EXIF/XMP), `APP13` (IPTC), `APP2` (ICC) and comment segments; PNG is walked chunk-by-chunk to find `tEXt`/`zTXt`/`iTXt`/`eXIf`/`tIME` chunks. EXIF data itself is a hand-written TIFF/IFD reader that walks IFD0, the Exif sub-IFD, and the GPS sub-IFD.
- **Editing** required writing a TIFF/IFD serializer, not just a reader: on save, every live field (edited or untouched) is re-encoded into a fresh IFD0/Exif/GPS structure with correctly computed value offsets, wrapped back into a JPEG `APP1` segment. For PNG, only the touched chunk is rebuilt (as `iTXt`/`tIME`, with a fresh CRC32) — untouched chunks, including ones this tool can't decode (compressed `zTXt`), are copied through byte-for-byte to avoid any lossy round-trip.
- In both cases, **only the metadata segments/chunks are touched — the actual pixel/scan data is always copied byte-for-byte**, so editing or stripping never re-encodes the image and never loses quality (unlike doing this via a `<canvas>` redraw).
- GPS coordinates are converted between degrees/minutes/seconds rationals and decimal degrees for editing, and linked to OpenStreetMap (a plain static link — nothing is fetched or sent until you click it).

## Limitations

- WebP and HEIC are not supported (JPEG and PNG cover the overwhelming majority of camera/phone/screenshot exports).
- Not every field is editable — enums like flash mode, white balance, or metering mode, and composite values like the GPS timestamp, are view/delete-only. Anything can still be removed individually even if it can't be edited.
- A PNG's embedded EXIF (the `eXIf` chunk, uncommon in practice) is view/delete-only as a whole block, not editable field-by-field.
- Compressed PNG text chunks (`zTXt`, and compressed `iTXt`) are detected and can be removed, but their content isn't decompressed for display — only their keyword is shown.

## Privacy

Everything happens locally in the browser via the File API. Files are never uploaded — reading, parsing, editing, and rewriting all happen client-side, and nothing is logged or sent to a server.
