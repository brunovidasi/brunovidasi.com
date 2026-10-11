// style-previews.mjs — builds a preview of each style prototype in prototypes/styles/.
// For every stylesheet listed below it writes prototypes/styles/<id>/index.html:
// a copy of the site's page (The Sims and GTA are ?sims and ?gta on it) with the prototype's fonts
// and stylesheet added, so each style can be opened through the same web server as
// the site. It also writes the gallery, prototypes/styles/index.html.
// The site's pages are only read, never changed. Run it again after changing them:
//
//   node tools/style-previews.mjs

import { readFileSync, writeFileSync, mkdirSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..');
const DIR = join(ROOT, 'prototypes/styles');

// in the order they were made; url is the published version of each
const STYLES = [
  { id: 'neon', url: 'https://claude.ai/artifact/8sHfoRwGokMYFadXMNX9s1', blurb: 'A synthwave night: a neon sign for a title, a sun setting over a moving grid, black racks lit by LED tubes.' },
  { id: 'hud', url: 'https://claude.ai/artifact/VWQEiG9b3UkjV76fvMVaiM', blurb: 'A sci-fi archive seen through a heads-up display: cyan and amber, cut corners, scanlines, a targeting bracket on the case you point at.' },
  { id: 'pixel', url: 'https://claude.ai/artifact/7RGrFwLVboQNwPdq988PZi', blurb: 'A 16-bit RPG: the bookcase is a blue menu window, the shelves are stone platforms, and a pointer bounces over the case you are on.' },
  { id: 'dashboard', url: 'https://claude.ai/artifact/NvCaoa9BEqGxvc5RqtGdDe', blurb: 'A console home screen: deep blue light, frosted glass, white for what is selected, and floating glass shelves with reflections.' },
  { id: 'museum', url: 'https://claude.ai/artifact/T88hpHgDHX2rwa3vckF9Fz', blurb: 'A museum exhibition: green gallery walls, a spotlight on every shelf, stone plinths, ivory placards and a velvet rope.' },
  { id: 'atomic', url: 'https://claude.ai/artifact/AuuxU4zDeTmwdd136hMLkx', blurb: 'Light: see-through purple Y2K plastic over a circuit board, clear acrylic shelves with screws, jelly buttons.', light: true },
  { id: 'comic', url: 'https://claude.ai/artifact/7iHTnxWFieVjEEmo2D2mRC', blurb: 'Light: a 90s games magazine with halftone dots, thick outlines, sticker labels, a speech-bubble tooltip and a burst of rays.', light: true },
  { id: 'console', url: 'https://claude.ai/artifact/JknhKqoVoxkbmgq4D25HVi', blurb: 'Light: the site built like a 16-bit console, with grey plastic, vents, cartridge slots and the four coloured face buttons.', light: true },
  { id: 'glitch', url: 'https://claude.ai/artifact/TwLTq5c7DJmKEz6J6jiMnn', blurb: 'A cyberpunk street: black and hazard yellow, warning-stripe shelves, yellow tape labels, and cases that glitch red and cyan.' },
  { id: 'leonida', url: 'https://claude.ai/artifact/Q9kec55FzHz6VSphbgNXr7', blurb: "After GTA VI's Vice City: a sunset over the water, palms in silhouette, and the shelf as a collage of panels split by black gutters." },
  { id: 'datacore', url: 'https://claude.ai/artifact/Ucxqb8ao48GkY3P6ZqVaKj', blurb: 'A server rack in a data core: numbered rack units with status lights, a laser scan across the cases, a holographic projector pad.' },
];

const PAGES = ['index.html'];
const esc = s => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

// each page lives two folders down, so <base> points it back at the site root, where
// its stylesheets, scripts, data and covers are. With <base> set, a link or address
// that is only "#something" would point at the root page instead; this keeps those
// on the preview's own address.
const SHIM = `<script>
(function () {
  var here = function (u) { return typeof u === 'string' && u.charAt(0) === '#' ? location.pathname + location.search + u : u; };
  ['pushState', 'replaceState'].forEach(function (k) { var f = history[k].bind(history); history[k] = function (s, t, u) { return f(s, t, here(u)); }; });
  document.addEventListener('click', function (e) {
    var a = e.target.closest && e.target.closest('a[href^="#"]');
    if (!a || e.defaultPrevented) return;
    e.preventDefault();
    var id = a.getAttribute('href').slice(1);
    history.pushState(null, '', '#' + id);
    var el = id && document.getElementById(id);
    if (el) el.scrollIntoView({ behavior: 'smooth' });
  });
})();
</script>`;

function meta(id) {
  const css = readFileSync(join(DIR, id + '.css'), 'utf8');
  const name = css.match(/Style prototype \d+ — ([^.\n]+)\./)[1];
  const fonts = css.match(/Fonts: (https:\/\/fonts\.googleapis\.com\/\S+)/)[1];
  return { name, fonts };
}

const pages = Object.fromEntries(PAGES.map(p => [p, readFileSync(join(ROOT, p), 'utf8')]));
STYLES.forEach((s, i) => Object.assign(s, meta(s.id), { n: i + 1 }));

for (const s of STYLES) {
  const out = join(DIR, s.id);
  mkdirSync(out, { recursive: true });
  const self = `prototypes/styles/${s.id}/`;
  for (const [page, html] of Object.entries(pages)) {
    let h = html;
    h = h.replace('<head>', `<head>\n<base href="../../../">\n${SHIM}`);
    h = h.replace(/(<title>)([^<]*)(<\/title>)/, (m, a, t, b) => `${a}${t} · ${esc(s.name)} style${b}`);
    // the prototype's fonts and stylesheet go after the page's own stylesheets
    const last = [...h.matchAll(/<link rel="stylesheet" href="css\/[^"]+">\n/g)].pop();
    const at = last.index + last[0].length;
    h = h.slice(0, at) + `<link rel="stylesheet" href="${esc(s.fonts)}">\n<link rel="stylesheet" href="prototypes/styles/${s.id}.css">\n` + h.slice(at);
    // the top bar stays inside this style
    h = h.replace(/href="\.\/"/g, `href="${self}"`)
      .replace(/href="\?(\w+)"/g, `href="${self}?$1"`);
    // a way back to the gallery
    h = h.replace('</body>', `<a href="prototypes/styles/" style="position:fixed;left:12px;bottom:calc(12px + env(safe-area-inset-bottom,0px));z-index:1050;padding:6px 12px;border-radius:99px;background:rgba(0,0,0,.72);color:#fff;font:600 12px/1.2 system-ui,sans-serif;text-decoration:none;backdrop-filter:blur(6px)">← ${esc(s.name)} · all styles</a>\n</body>`);
    writeFileSync(join(out, page), h);
  }
}

const cards = STYLES.map(s => `
    <article class="card">
      <a class="shot" href="${s.id}/"><img src="shots/${s.id}.jpg" alt="The shelf in the ${esc(s.name)} style" loading="lazy" width="700" height="500"></a>
      <div class="txt">
        <h2><span>${String(s.n).padStart(2, '0')}</span>${esc(s.name)}${s.light ? ' <em>light</em>' : ''}</h2>
        <p>${esc(s.blurb)}</p>
        <div class="links">
          <a class="go" href="${s.id}/">Open the preview</a>
          <a href="${s.id}/?sims">The Sims</a>
          <a href="${s.id}/?gta">GTA</a>
          <a href="${esc(s.url)}" target="_blank" rel="noopener">Published ↗</a>
        </div>
      </div>
    </article>`).join('');

writeFileSync(join(DIR, 'index.html'), `<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>Style Prototypes · Bruno's Games</title>
<link rel="icon" href="../../favicon.svg" type="image/svg+xml">
<!-- written by tools/style-previews.mjs -->
<style>
  @font-face { font-family: 'Fraunces'; src: url('../../fonts/fraunces-latin.woff2') format('woff2'); font-weight: 400 700; font-display: swap; }
  @font-face { font-family: 'Space Grotesk'; src: url('../../fonts/space-grotesk-latin.woff2') format('woff2'); font-weight: 400 600; font-display: swap; }
  :root { --bg: #0f0e12; --card: #18171d; --text: #efedf3; --muted: rgba(239,237,243,.62); --line: rgba(239,237,243,.12); --accent: #ffb547; color-scheme: dark; }
  * { box-sizing: border-box; }
  body { margin: 0; background: var(--bg); color: var(--text); font: 15px/1.5 'Space Grotesk', system-ui, sans-serif; }
  .wrap { max-width: 1240px; margin: 0 auto; padding: calc(48px + env(safe-area-inset-top, 0px)) 20px 72px; }
  header { max-width: 62ch; margin-bottom: 34px; }
  .eyebrow { font-size: 12px; letter-spacing: .18em; text-transform: uppercase; color: var(--accent); }
  h1 { font: 600 clamp(34px, 5vw, 54px)/1 'Fraunces', serif; margin: 10px 0 14px; letter-spacing: -.02em; }
  header p { margin: 0 0 8px; color: var(--muted); }
  header a { color: var(--text); }
  .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 360px), 1fr)); gap: 22px; }
  .card { display: flex; flex-direction: column; background: var(--card); border: 1px solid var(--line); border-radius: 12px; overflow: hidden; }
  .shot { display: block; aspect-ratio: 7 / 5; overflow: hidden; border-bottom: 1px solid var(--line); }
  .shot img { display: block; width: 100%; height: 100%; object-fit: cover; object-position: top; transition: transform .4s ease; }
  .shot:hover img, .shot:focus-visible img { transform: scale(1.03); }
  .txt { padding: 16px 18px 18px; display: flex; flex-direction: column; gap: 8px; flex: 1; }
  h2 { margin: 0; font: 600 21px/1.2 'Fraunces', serif; display: flex; align-items: baseline; gap: 10px; }
  h2 span { font: 500 12px 'Space Grotesk', sans-serif; color: var(--accent); font-variant-numeric: tabular-nums; }
  h2 em { font: 500 10px 'Space Grotesk', sans-serif; font-style: normal; letter-spacing: .1em; text-transform: uppercase; color: var(--muted); border: 1px solid var(--line); border-radius: 99px; padding: 2px 8px; }
  .txt p { margin: 0; color: var(--muted); font-size: 14px; flex: 1; }
  .links { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 6px; }
  .links a { font-size: 13px; color: var(--text); text-decoration: none; border: 1px solid var(--line); border-radius: 99px; padding: 5px 11px; }
  .links a:hover { border-color: var(--muted); }
  .links a.go { background: var(--accent); border-color: var(--accent); color: #1a1206; font-weight: 600; }
  :focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }
  @media (prefers-reduced-motion: reduce) { .shot img { transition: none; } }
</style>
</head>
<body>
<div class="wrap">
  <header>
    <div class="eyebrow">Bruno's Games · style prototypes</div>
    <h1>Eleven looks for the shelf</h1>
    <p>Each one is the real site (the shelf, the grid, the list, picking a game up, the Sims and GTA pages) with one extra stylesheet that changes only how it looks. None of them is the live site yet.</p>
    <p>Open them through the same web server as the site. The <a href="../../">live site</a> keeps its own look. The stylesheets are in this folder; <code>node tools/style-previews.mjs</code> rebuilds these previews after the site's pages change.</p>
  </header>
  <div class="grid">${cards}
  </div>
</div>
</body>
</html>
`);

console.log(`Wrote ${STYLES.length} styles × ${PAGES.length} pages and the gallery.`);
