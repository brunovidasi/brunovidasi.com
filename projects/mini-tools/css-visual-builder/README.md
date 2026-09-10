# CSS Visual Builder

Build `clip-path`, gradients (linear/radial/conic), and `box-shadow` with sliders and a live preview, then copy the generated CSS.

## Files

- `index.html` — markup/structure
- `style.css` — warm paper styling shared with the other mini-tools
- `script.js` — state/render logic for all three builders
- `fonts/` — self-hosted Inter and JetBrains Mono (variable woff2, copied from the site's own `/fonts`)

## Usage

Open `index.html` in any modern browser. No build step, no server, no network calls.

1. Pick a builder from the top tabs: **Clip-path**, **Gradient**, or **Box-shadow**.
2. **Clip-path** — choose a shape (inset, circle, ellipse, or polygon) and tune it with sliders. Polygon also offers 13 preset shapes (triangle, star, arrow, message bubble, etc.) plus an editable raw point list for full control.
3. **Gradient** — choose linear/radial/conic, tune angle/position/size, toggle repeating, and add/remove/reposition color stops.
4. **Box-shadow** — stack up to 6 shadow layers, each with its own offset, blur, spread, color, opacity, and inset toggle.
5. Every panel has a live preview and a **Copy CSS** button that copies the ready-to-paste declaration.

## Privacy

Everything happens locally in the browser. Nothing is sent to a server.
