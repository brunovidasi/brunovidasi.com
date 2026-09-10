# QR Code Generator / Reader

Generate QR codes from any text or URL, and decode QR codes from an uploaded photo or a live camera feed — a from-scratch implementation of the QR Code spec (ISO/IEC 18004), not a wrapper around a library.

## Files

- `index.html` — markup/structure
- `style.css` — warm paper styling shared with the other mini-tools
- `qr-common.js` — GF(256) arithmetic, Reed-Solomon encode/decode, the error-correction block table, alignment-pattern positions, and format/version-info BCH codes (shared by the encoder and decoder so their bit ordering can never drift apart)
- `qr-encoder.js` — text → module matrix (mode selection, version sizing, masking) and matrix → canvas/SVG rendering
- `qr-decoder.js` — module matrix → text (format/version info, unmasking, Reed-Solomon correction, segment parsing), plus the image pipeline that turns a photo/camera frame into that matrix (adaptive binarization, finder-pattern detection, perspective correction)
- `script.js` — UI wiring for both tabs
- `fonts/` — self-hosted Inter and JetBrains Mono (variable woff2, copied from the site's own `/fonts`)

## Usage

Open `index.html` in any modern browser. No build step, no server, no network calls.

**Generate**: type text or a URL, pick an error-correction level (L/M/Q/H — higher survives more damage but needs a bigger code), and tweak module size and quiet zone. Style it with dot and corner (finder-eye) shapes — square, rounded, or dots — solid or gradient (linear/radial) fill, and an optional transparent background; colors can be set from either the swatch or a typed hex code. A contrast warning appears if the chosen colors are too close in lightness to scan reliably. Download as PNG or SVG, or copy the image directly.

**Scan**: upload an image file, or use your camera (scans continuously until it finds a code). Decoded text shows with a copy button, and a direct "Open" link if it's a URL.

## How it works

**Encoding** follows the spec directly: text is placed into numeric, alphanumeric, or byte mode (whichever fits tightest), padded and split into error-correction blocks per the standard block table, encoded with Reed-Solomon over GF(256), interleaved, and written into the module grid in the standard zigzag order. All 8 mask patterns are tried and scored by the four standard penalty rules; the best-scoring one is used.

**Decoding** reverses this: an adaptive local-threshold binarization (robust to uneven lighting) finds the three finder patterns by their 1:1:3:1:1 run-length signature, builds a projective transform from their centers (handling rotation and moderate perspective skew), and samples the module grid through it. From there it reads the format info, undoes the mask, runs Reed-Solomon error correction on each block, and parses the resulting bitstream back into text.

The encoder and decoder share their module-placement logic (`qr-common.js`) rather than reimplementing it twice, specifically so the write order and read order can never quietly disagree.

**Styling** never changes the encoded structure — a "rounded" module still covers its whole cell (just with clipped corners), and "dots" shrink it by a bounded, scanner-tolerant amount. Finder eyes are drawn as one clean shape (an even-odd "ring" plus a solid core) rather than per-module, so they stay structurally identical to a normal finder pattern under any style.

**Correctness**: the encoder was cross-validated byte-for-byte against an independent reference implementation across versions, error-correction levels, and encoding modes, and its output decodes correctly with an independent QR reader (OpenCV). The decoder was fuzz-tested against thousands of Reed-Solomon error patterns and damaged matrices, against photos with rotation, noise, and scale variation, and against every dot/corner/gradient/transparency style combination.

## Privacy

Everything happens locally in the browser, including camera scanning — frames are read from `<video>` into a canvas and processed in place. Nothing is uploaded anywhere.
