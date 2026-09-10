# PDF ⇄ Word Converter

Convert a PDF to an editable Word document, or a Word document to PDF — both directions, entirely in the browser, no upload, no server round trip.

## Files

- `index.html` — markup/structure, with a mode switch between the two directions
- `style.css` — warm paper styling shared with the other mini-tools
- `script.js` — text extraction/regrouping for PDF→Word, HTML rendering/pagination for Word→PDF
- `vendor/pdf.min.js` + `vendor/pdf.worker.min.js` — [pdf.js](https://mozilla.github.io/pdf.js/) (Mozilla), used to read each PDF page's text content
- `vendor/docx.umd.js` — [docx](https://docx.js.org/), used to build a real `.docx` file from the extracted text
- `vendor/mammoth.browser.min.js` — [mammoth.js](https://github.com/mwilliamson/mammoth.js), used to convert a `.docx` file's paragraphs, headings, lists, and images to HTML
- `vendor/html2canvas.min.js` + `vendor/jspdf.umd.min.js` — [html2canvas](https://html2canvas.hertzen.com/) and [jsPDF](https://github.com/parallax/jsPDF), used together to render that HTML onto paginated A4 PDF pages
- `fonts/` — self-hosted Inter and JetBrains Mono (variable woff2, copied from the site's own `/fonts`)

## Usage

Open `index.html` in any modern browser. No build step, no server, no network calls.

**PDF → Word:** drag in a PDF (or click to choose one). Each page's text is extracted automatically and downloaded as a `.docx` you can open and edit in Word, Google Docs, etc.

**Word → PDF:** switch to the "Word → PDF" tab, drag in a `.docx` file, and download the rendered PDF.

## How it works

- **PDF → Word.** pdf.js reads each page's text items along with their on-page coordinates. Items are regrouped into lines by clustering on vertical position, then into paragraphs by looking for larger-than-normal vertical gaps between lines. Each PDF page becomes a Word page (via a page break), and the result is assembled into a real `.docx` with the `docx` library. This preserves reading order and rough paragraph structure, but **not** exact visual layout — multi-column text, tables, and images are not reconstructed, and all text is normalized to a standard document style. A page with little or no extractable text (for example, a scanned image) is flagged as such rather than left blank without explanation.
- **Word → PDF.** mammoth.js converts the `.docx` file's paragraphs, headings, lists, bold/italic runs, tables, and inline images into HTML. That HTML is rendered off-screen and handed to jsPDF's `html()` method, which uses html2canvas to rasterize it and automatically paginates the result onto A4 pages. Complex Word features — headers/footers, footnotes, multi-column sections, exact pagination — are not reproduced, but ordinary text documents convert cleanly.

## Limitations

- **PDF → Word** is a text-extraction-and-rebuild process, not a layout-preserving conversion: fonts, columns, tables, and images from the original PDF are not carried over, only the text itself, laid out top-to-bottom.
- Scanned/image-only PDFs have no embedded text for pdf.js to read, so PDF → Word will produce little or nothing from those pages.
- **Word → PDF** only accepts modern `.docx` files, not the legacy binary `.doc` format.
- Because Word → PDF works by rasterizing rendered HTML, very long documents take a bit longer and the output, while visually accurate, isn't text-selectable in the same way a native PDF export from Word would be.

## Privacy

Everything happens locally via the File API, pdf.js's WebWorker, mammoth.js, html2canvas, jsPDF, and the `docx` library. Files are never uploaded — extraction, rendering, and reassembly all happen client-side, and nothing is logged or sent to a server.
