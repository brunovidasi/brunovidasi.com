// Brings what Nintendo knows about the Switch and Switch 2 games into the site.
//
//   cd tools && node nintendo-sync.mjs              (the export in api/nintendo-exporter/exports)
//   node nintendo-sync.mjs path/to/play-histories.json  (another export)
//   node nintendo-sync.mjs --dry                    (report what would change, write nothing)
//   node nintendo-sync.mjs --no-add                 (tie copies, but add none)
//
// Two files come out of it:
//
//   data/nintendo.json — by Nintendo title id: each game's play history (how long, on how many
//     days, when first and last played), as the Nintendo Store app shows it. Every game played on
//     the account is in it, cartridge or download. The sync writes it whole every time, so don't
//     edit it by hand.
//
//   data/games.json — a copy is tied to its play history by "nintendoTitleId" (0100…). A game is
//     tied to the one Switch or Switch 2 copy with the same name (or a name that starts with it),
//     without its "Nintendo Switch 2 Edition"; when there are several, the one on the console it
//     was played on. When there are several, it is only reported: tie it by hand. A copy with more
//     than one game in it takes a list: "nintendoTitleId": ["0100…", …]. A game with no copy at all
//     was bought or downloaded on the eShop, so it gets a new digital one ("format": "digital", the
//     site calls it a Nintendo eShop copy), with only what the play history says filled in: give it
//     its release date, publisher, developer and genre by hand and fetch its cover with
//     fetch-covers.mjs. After that the copy is yours and the sync never changes it. To keep one off
//     the site, give it "hidden": true rather than deleting it, or the next sync adds it back.
//     Apps and what comes with Nintendo Switch Online (YouTube, Pokémon HOME, the Nintendo Classics,
//     Tetris 99) are never added. Nothing else in a copy changes.
//
// The export comes from the PHP exporter (api/nintendo-exporter). When the site talks to Nintendo
// itself, have it hand fromExport() the same response, or write another reader that returns what
// fromExport() returns.

import fs from 'node:fs';
import path from 'node:path';

const ROOT = path.resolve(path.dirname(new URL(import.meta.url).pathname), '..');
const DATA = path.join(ROOT, 'data/games.json');
const OUT = path.join(ROOT, 'data/nintendo.json');
const args = process.argv.slice(2);
const DRY = args.includes('--dry');
const FILE = args.find(a => !a.startsWith('--')) || path.join(ROOT, 'api/nintendo-exporter/exports/play-histories.json');
const ADD = !args.includes('--no-add');
const CONSOLES = ['switch', 'switch2'];
// apps, and games that come with Nintendo Switch Online rather than being bought: no copy for them
const NOT_OWNED = /^(YouTube|Netflix|Disney\+|Spotify|Twitch|Pokémon HOME|TETRIS 99|PAC-MAN 99|SUPER MARIO BROS\. 35|F-ZERO 99|.* – Nintendo Classics|.* Nintendo Switch Online)$/i;

/* ---------- reading an export ---------- */

const clean = s => (s || '').replace(/[™®©]/g, '').replace(/\s*[–—-]?\s*Nintendo Switch(?: 2)? Edition\b/gi, '').replace(/\s+/g, ' ').trim();
// the console it was played on, as Nintendo names it: "Nintendo Switch 2" (or its code, BEE) or the Switch (HAC)
const playedOn = h => /switch\s*2|switch_?2|^bee$|ounce/i.test(h.platform || h.deviceType || '') ? 'switch2' : 'switch';
const iso = t => t ? new Date(t).toISOString() : null;

/* The export, as the site keeps it. Nothing about the account goes in: the site is public. */
export function fromExport({ playHistories = [] }, exportedAt = null) {
  const titles = {};
  for (const h of playHistories) {
    const id = h.titleId || h.applicationId;
    if (!id) continue;
    titles[id] = {
      id,
      name: clean(h.titleName || h.name),
      console: playedOn(h),
      // minutes
      playtime: h.totalPlayedMinutes ?? null,
      playDays: h.totalPlayedDays ?? null,
      firstPlayed: iso(h.firstPlayedAt),
      lastPlayed: iso(h.lastPlayedAt),
      image: h.imageUrl || null,
    };
  }
  return { synced: exportedAt, source: 'api/nintendo-exporter', titles };
}

/* ---------- the shelf ---------- */

const norm = s => clean(s).normalize('NFKD').replace(/[̀-ͯ]/g, '').toLowerCase().replace(/['’`]/g, '').replace(/&/g, ' and ').replace(/[^a-z0-9]+/g, ' ').trim();

// the copies a game could be: a Switch or Switch 2 one, not tied yet, the same name (or what it is listed as),
// else a name that starts with it; of several, the ones on the console it was played on
function candidates(db, t) {
  const free = db.games.filter(g => CONSOLES.includes(g.console) && !g.nintendoTitleId?.length);
  const n = norm(t.name), names = g => [g.title, g.listedAs].filter(Boolean).map(norm);
  const same = free.filter(g => names(g).includes(n));
  // (but a name followed by a number is another game: Just Dance isn't Just Dance 2022)
  const cs = same.length ? same : free.filter(g => names(g).some(m => m.startsWith(n + ' ') && !/^\d|^(ii|iii|iv|v|vi)\b/.test(m.slice(n.length + 1))));
  const here = cs.filter(g => g.console === t.console);
  return here.length ? here : cs;
}

/* ---------- main ---------- */

if (!fs.existsSync(FILE)) throw new Error(`no export at ${path.relative(ROOT, FILE)}: run php export.php in api/nintendo-exporter first`);
const raw = JSON.parse(fs.readFileSync(FILE, 'utf8'));
const nintendo = fromExport(raw.data || raw, raw.exported_at_utc || fs.statSync(FILE).mtime.toISOString());
const db = JSON.parse(fs.readFileSync(DATA, 'utf8'));
const all = Object.values(nintendo.titles);
console.log(`${path.relative(ROOT, FILE)}: ${all.length} games played, ${Math.round(all.reduce((a, t) => a + (t.playtime || 0), 0) / 60)} hours, exported ${nintendo.synced}`);

const slug = s => norm(s).replace(/ /g, '-');
// a new digital copy, after the last copy on its console
function addCopy(t) {
  let id = `${t.console}-${slug(t.name)}`;
  while (db.games.some(g => g.id === id)) id += '-digital';
  const g = { id, console: t.console, title: t.name, region: 'PAL', released: null, dateSource: 'manual', publisher: null, developer: null, genre: null, format: 'digital', nintendoTitleId: t.id, cover: null, colors: null };
  const last = db.games.findLastIndex(o => o.console === t.console);
  db.games.splice(last + 1, 0, g);
  return g;
}

const report = { linked: [], added: [], ambiguous: [], 'not on the shelf': [], 'apps and Switch Online, left out': [], 'not in the export any more': [] };
const tied = new Set(db.games.flatMap(g => [g.nintendoTitleId || []].flat()));
for (const t of all) {
  if (tied.has(t.id)) continue;
  const twins = all.filter(o => o !== t && !tied.has(o.id) && norm(o.name) === norm(t.name));
  const cs = candidates(db, t);
  if (cs.length === 1 && !twins.length) {
    cs[0].nintendoTitleId = t.id;
    tied.add(t.id);
    report.linked.push(`${cs[0].id} ← ${t.id} ${t.name}`);
  } else if (cs.length) report.ambiguous.push(`${t.id} ${t.name} (${t.console}): ${cs.map(g => g.id).join(', ')}${twins.length ? ` — and ${twins.map(o => o.id).join(', ')} has the same name` : ''}`);
  else if (NOT_OWNED.test(t.name)) report['apps and Switch Online, left out'].push(`${t.id} ${t.name}`);
  else if (ADD) {
    const g = addCopy(t);
    tied.add(t.id);
    report.added.push(`${g.id} ← ${t.id} ${t.name} (${t.playtime ?? '?'} min)`);
  } else report['not on the shelf'].push(`${t.id} ${t.name} (${t.console}, ${t.playtime ?? '?'} min)`);
}
for (const g of db.games) for (const id of [g.nintendoTitleId || []].flat()) if (!nintendo.titles[id]) report['not in the export any more'].push(`${g.id} (${id})`);

for (const [k, v] of Object.entries(report)) if (v.length) console.log(`${k}:\n  ${v.join('\n  ')}`);
if (!DRY) {
  if (report.linked.length || report.added.length) db.updated = new Date().toISOString().slice(0, 10);
  fs.writeFileSync(DATA, JSON.stringify(db, null, 2) + '\n');
  fs.writeFileSync(OUT, JSON.stringify(nintendo, null, 2) + '\n');
  console.log(`wrote ${path.relative(ROOT, OUT)} and ${path.relative(ROOT, DATA)}`);
}
