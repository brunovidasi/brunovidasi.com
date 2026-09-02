# Social Links

A Linktree-style "link in bio" landing page for Bruno Vieira / @brunovidasi, grouping all social, professional, chat, and gaming profiles into collapsible categories.

## Files

- `index.html` — markup/structure and the icon sprite (each social/platform icon is an SVG `<symbol>`)
- `style.css` — styling and theming
- `script.js` — the `links` data array and the logic that renders/groups the buttons by category
- `favicon.ico` / `images/` — page icon and image assets

## Usage

Open `index.html` in any modern browser. No build step, server, or dependencies required.

To add, remove, or reorder a link, edit the `links` array in `script.js`:

```js
{ category: 'Social', name: 'Instagram', url: 'https://instagram.com/brunovidasi', icon: 'instagram', color: '#...' }
```

- `icon` must match a `<symbol id="icon-...">` defined in the sprite at the top of `index.html`. To add a new platform, add a `<symbol>` there first, then reference its id here.
- `color` accepts any CSS background value (hex or a gradient string); omit it to fall back to the accent colour (`DEFAULT_COLOR`).
- Links are grouped by `category` in array order — a new category header is created whenever `category` changes between consecutive entries, so keep same-category entries adjacent.

## How it works

`script.js` walks the `links` array, inserting a clickable `category-header` (with a collapse/expand arrow) whenever it hits a new category, then appends a styled `link-item` anchor for each entry into that category's section. External links open in a new tab (`target="_blank"`, `rel="noopener"`); `mailto:` links open normally.
