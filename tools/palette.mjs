// The three colours that cover most of a cover, for its spine. Shared by fetch-covers.mjs and steam-sync.mjs.

import sharp from 'sharp';

const hex = ([r, g, b]) => '#' + [r, g, b].map(v => Math.round(v).toString(16).padStart(2, '0')).join('');
// A tiny k-means over a 24×24 thumbnail: the three colours that cover most of the cover.
export async function palette(buf) {
  const { data } = await sharp(buf).resize(24, 24, { fit: 'fill' }).removeAlpha().raw().toBuffer({ resolveWithObject: true });
  const px = [];
  for (let i = 0; i < data.length; i += 3) px.push([data[i], data[i + 1], data[i + 2]]);
  let cs = [px[0], px[Math.floor(px.length / 2)], px[px.length - 1], px[Math.floor(px.length / 3)]];
  let groups;
  for (let it = 0; it < 12; it++) {
    groups = cs.map(() => []);
    for (const p of px) {
      let bi = 0, bd = Infinity;
      cs.forEach((c, i) => { const d = (p[0] - c[0]) ** 2 + (p[1] - c[1]) ** 2 + (p[2] - c[2]) ** 2; if (d < bd) { bd = d; bi = i; } });
      groups[bi].push(p);
    }
    cs = groups.map((g, i) => g.length ? [0, 1, 2].map(k => g.reduce((s, p) => s + p[k], 0) / g.length) : cs[i]);
  }
  return cs.map((c, i) => ({ c, n: groups[i].length })).sort((a, b) => b.n - a.n).slice(0, 3).map(x => hex(x.c));
}
