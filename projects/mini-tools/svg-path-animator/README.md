# SVG Path Animator

Draw or paste an SVG path, preview it as a stroke draw-in, a fill reveal, or a dot traveling along it, tune the easing curve, and copy ready-to-use CSS or SMIL.

## Files

- `index.html` — markup/structure
- `style.css` — warm paper styling, matching the other mini-tools
- `script.js` — path parsing, freehand drawing, animation, and code export
- `fonts/` — self-hosted Inter and JetBrains Mono (variable woff2, copied from the site's own `/fonts`)

## Usage

Open `index.html` in any modern browser. No build step, no server, no network calls.

1. Pick a preset shape, paste a path `d` attribute or a full `<svg>…</svg>` snippet, or click **Draw your own** and drag on the preview canvas to sketch a freehand path.
2. Pick an animation type — **Draw** (stroke draws itself in), **Draw + fill** (stroke draws in, then the shape fills), or **Traveling dot** (a dot moves along the path via CSS motion paths).
3. Tune duration, delay, loop, colors/sizes, and the easing curve (presets or a draggable cubic-bezier editor).
4. Use the transport controls to play/pause/restart the live preview.
5. Copy the generated **CSS** (`@keyframes` + `stroke-dasharray`/`offset-path`) or **SVG (SMIL)** (`<animate>`/`<animateMotion>`) snippet.

## How it works

The live preview is driven by the Web Animations API (`element.animate()`), which makes play/pause/restart trivial. The path's `d` attribute is used to auto-fit the viewBox to its bounding box (`getBBox()`), and `getTotalLength()` gives the stroke length used for the classic dash-offset "draw" effect. Freehand drawing samples pointer positions and smooths them into a quadratic-bezier path; preset shapes like the star, wave, spiral, blob, and infinity symbol are generated parametrically (the blob uses a closed Catmull-Rom-to-Bezier conversion). The exported code is generated independently from the same state, as static CSS/SMIL that works with no JavaScript at all.

## Privacy

Everything happens locally in the browser. Nothing is sent to a server.
