# PDF Metadata Viewer & Stripper

See exactly what metadata is embedded in a PDF's Document Info dictionary — title, author, the app that created it, custom tracking fields some tools inject — plus whether it carries an embedded XMP metadata block, then edit any field, delete individual ones, add new ones, or strip everything out in one click.

## Files

- `index.html` — markup/structure
- `style.css` — warm paper styling shared with the other mini-tools
- `script.js` — metadata extraction, editing, and stripping logic (built on pdf-lib) and UI behavior
- `vendor/pdf-lib.min.js` — [pdf-lib](https://pdf-lib.js.org/), used to parse and rewrite the PDF's Info dictionary and Metadata (XMP) entry
- `vendor/jszip.min.js` — used only for the optional "download all as .zip" bulk action
- `fonts/` — self-hosted Inter and JetBrains Mono (variable woff2, copied from the site's own `/fonts`)

## Usage

Open `index.html` in any modern browser. No build step, no server, no network calls.

1. Drag in one or more PDFs (or click to choose them).
2. Each file shows a field count and, if present, a "sensitive" badge — click **View & edit** to see every Document Info field (standard ones like Title/Author/Creator/Producer plus any custom keys some scanners and editors add) and whether an XMP metadata block is present.
3. Every field shows as an inline input — change a value directly. Dates use a plain `YYYY-MM-DD HH:MM:SS UTC` format. Every field has a ✕ to remove it individually. The **+ Add field** picker at the bottom adds a missing standard field or a custom key of your choice.
4. Click **Save changes** to apply your edits/deletions/additions — the file is rebuilt and the card shows the new size with a **Download** link. Closing the dialog without saving discards any unsaved changes.
5. **Strip & download** removes *all* Document Info fields and the XMP metadata block from a file in one click, no dialog needed. **Download all cleaned (.zip)** does the same for every loaded file at once, without touching what's shown on screen.

## How it works

- Files are parsed with [pdf-lib](https://pdf-lib.js.org/), which reads the PDF's structure directly rather than rasterizing anything — the original vector/text content is untouched, only the metadata layer is read and rewritten.
- The Document Info dictionary is enumerated key-by-key (not just the well-known fields), so custom metadata some PDF editors and scanning apps quietly add — a tracking ID, a device name, an internal username — shows up too, not just Title/Author.
- The optional XMP metadata stream (`/Metadata` on the document catalog) is detected and previewed as raw XML; it's view/delete-only since editing arbitrary XMP packets safely is out of scope here, but it's fully removed by "Strip & download" or an individual delete.
- Saving calls pdf-lib's writer, which performs a full rewrite of the file's cross-reference table — so **stripping doesn't just blank the fields, it also drops any earlier incremental-save revision history** that might otherwise still contain old metadata.

## Limitations

- **Password-protected PDFs aren't supported.** pdf-lib can't decrypt them, so metadata can't be read or edited while a PDF is locked — unlock it first with this site's [Unlock PDF](../pdf-password-remover/index.html), then drop the result back in here.
- Metadata that can live *inside* embedded files, form fields, or page-level annotations isn't covered — this tool only handles the document-level Info dictionary and the XMP metadata stream, which is where the overwhelming majority of author/software/tracking metadata actually lives.
- The XMP metadata block can be viewed and removed, but not edited field-by-field — it's a raw XML packet, not a simple key/value list.

## Privacy

Everything happens locally in the browser via the File API and pdf-lib. Files are never uploaded — reading, parsing, editing, and rewriting all happen client-side, and nothing is logged or sent to a server.
