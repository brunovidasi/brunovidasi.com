// Brings what Rockstar Games knows about the account into the site.
//
//   cd tools && node rockstar-sync.mjs                 (the export in api/rockstar-exporter/exports)
//   node rockstar-sync.mjs path/to/exports-folder      (another export)
//   node rockstar-sync.mjs --dry                       (report what would change, write nothing)
//   node rockstar-sync.mjs --no-add                    (tie copies, but add none)
//
// Two files come out of it:
//
//   data/rockstar.json — by Rockstar's short name for a game (gtav, rdr2, lan): every game the Rockstar account
//     has played, the platforms it was played on and when it was last seen in each, and for a PC game the
//     achievements unlocked, with when. Rockstar keeps no play time anyone can read (the launcher counts it, but
//     never shows it), so there is none. The sync writes it whole every time, so don't edit it by hand.
//
//   data/games.json — a PC copy is tied to Rockstar by "rockstarTitle". A game played on PC is tied to the one PC copy
//     with the same name (or listedAs); of several, to the one that is on the Rockstar Games Launcher. When it is
//     still several, it is only reported: tie it by hand. A game played on PC with no copy at all was bought in the
//     launcher, so it gets a new digital one ("launcher": "rockstar"), with its title and Rockstar as publisher.
//     After that the copy is yours. To keep one off the site, give it "hidden": true rather than deleting it, or
//     the next sync adds it back. The console games are left to PSN and Xbox, which know their play time.
//
// Rockstar names its games by short codes, which TITLES turns into the names on the shelf. A code that isn't in
// it is only reported: add it there.

import fs from 'node:fs';
import path from 'node:path';

const ROOT = path.resolve(path.dirname(new URL(import.meta.url).pathname), '..');
const DATA = path.join(ROOT, 'data/games.json');
const OUT = path.join(ROOT, 'data/rockstar.json');
const args = process.argv.slice(2);
const DRY = args.includes('--dry');
const ADD = !args.includes('--no-add');
const DIR = args.find(a => !a.startsWith('--')) || path.join(ROOT, 'api/rockstar-exporter/exports');

// Rockstar's short names (the Social Club's, and the launcher's), the same game under each
const TITLES = {
  gtav: 'Grand Theft Auto V',
  gtavgen9: 'Grand Theft Auto V',
  gtaiv: 'Grand Theft Auto IV',
  gtaeflc: 'Grand Theft Auto: Episodes from Liberty City',
  gtasa: 'Grand Theft Auto: San Andreas',
  gtavc: 'Grand Theft Auto: Vice City',
  gtaiii: 'Grand Theft Auto III',
  gta3unreal: 'Grand Theft Auto III – The Definitive Edition',
  gtavcunreal: 'Grand Theft Auto: Vice City – The Definitive Edition',
  gtasaunreal: 'Grand Theft Auto: San Andreas – The Definitive Edition',
  rdr: 'Red Dead Redemption',
  rdr2: 'Red Dead Redemption 2',
  lan: 'L.A. Noire',
  lanvr: 'L.A. Noire: The VR Case Files',
  mp3: 'Max Payne 3',
  bully: 'Bully: Scholarship Edition',
};
// the same game, one entry: GTA V Enhanced is GTA V
const SAME = { gtavgen9: 'gtav' };
const NOT_A_GAME = new Set(['launcher']);

/* ---------- reading an export ---------- */

const read = f => fs.existsSync(path.join(DIR, f)) ? JSON.parse(fs.readFileSync(path.join(DIR, f), 'utf8')).data : null;
const norm = s => (s || '').replace(/[™®©]/g, '').normalize('NFKD').replace(/[̀-ͯ]/g, '').toLowerCase()
  .replace(/['’`.]/g, '').replace(/&/g, ' and ').replace(/[^a-z0-9]+/g, ' ').trim();
const slug = s => norm(s).replace(/ /g, '-');
// "GTAV_PC", "GTAV" → "gtav"
const codeOf = g => String(g.name ?? g.title ?? g.titleName ?? '').toLowerCase().replace(/_(pc|ps\d|xbox\w*|xb\w*)$/, '');
// pcalt is the PC too: GTA V's second PC version (Enhanced)
const platformOf = g => String(g.platform ?? g.titleName?.split('_')[1] ?? 'pc').toLowerCase().replace(/^pcalt$/, 'pc');
const latest = (a, b) => (!a || (b && b > a)) ? b : a;

/* The export, as the site keeps it. The account's ids and nickname stay out: the site is public. */
export function fromExport({ games = [], achievements = {}, exportedAt = null }) {
  const out = {}, unknown = new Set();
  for (const g of games) {
    const raw = codeOf(g);
    if (!raw || NOT_A_GAME.has(raw)) continue;
    const code = SAME[raw] || raw;
    if (!TITLES[code]) unknown.add(raw);
    const e = out[code] ||= { code, name: TITLES[code] || raw.toUpperCase(), platforms: {}, lastPlayed: null, achievements: null };
    const p = platformOf(g), seen = g.lastSeen || g.lastPlayed || null;
    e.platforms[p] = latest(e.platforms[p] ?? null, seen);
    e.lastPlayed = latest(e.lastPlayed, seen);
  }
  for (const [raw, list] of Object.entries(achievements)) {
    const code = SAME[raw] || raw, e = out[code];
    if (!e) continue;
    const unlocked = Object.entries(list || {}).map(([id, a]) => ({ id, date: a?.dateAchieved || null }))
      .sort((x, y) => (y.date || '').localeCompare(x.date || ''));
    if (!unlocked.length) continue;
    // Rockstar gives only the ones unlocked, so how many there are in all isn't known here
    const prev = e.achievements?.unlocked || [];
    e.achievements = { earned: prev.length + unlocked.length, unlocked: [...prev, ...unlocked].sort((x, y) => (y.date || '').localeCompare(x.date || '')) };
  }
  return {
    synced: exportedAt,
    source: 'api/rockstar-exporter',
    totals: { games: Object.keys(out).length, pc: Object.values(out).filter(e => e.platforms.pc !== undefined).length },
    games: out,
    unknown: [...unknown].sort(),
  };
}

/* ---------- the shelf ---------- */

// the copies a Rockstar game could be: a PC copy not tied yet with the same name; of several, the ones on its launcher
function candidates(db, game) {
  const n = norm(game.name);
  const same = db.games.filter(g => g.console === 'pc' && !g.hidden && !g.rockstarTitle && [g.title, g.listedAs].filter(Boolean).some(t => norm(t) === n));
  if (same.length < 2) return same;
  const inLauncher = same.filter(g => g.launcher === 'rockstar');
  return inLauncher.length ? inLauncher : same;
}

const newCopy = game => ({
  id: 'pc-' + slug(game.name),
  console: 'pc',
  title: game.name,
  region: 'PAL',
  released: null,
  publisher: 'Rockstar Games',
  developer: null,
  genre: null,
  format: 'digital',
  launcher: 'rockstar',
  rockstarTitle: game.code,
  cover: null,
  colors: null,
});

/* ---------- main ---------- */

const gamesFile = path.join(DIR, 'games.json');
if (!fs.existsSync(gamesFile)) throw new Error(`no export in ${path.relative(ROOT, DIR)}: run php export.php in api/rockstar-exporter first`);
const rs = fromExport({
  games: read('games.json') || [],
  achievements: read('achievements.json') || {},
  exportedAt: JSON.parse(fs.readFileSync(gamesFile, 'utf8')).exported_at_utc || fs.statSync(gamesFile).mtime.toISOString(),
});
const db = JSON.parse(fs.readFileSync(DATA, 'utf8'));
console.log(`${path.relative(ROOT, DIR)}: ${rs.totals.games} games played, ${rs.totals.pc} on PC, exported ${rs.synced}`);

const report = { linked: [], added: [], ambiguous: [], 'not added (--no-add)': [], 'unknown code (add it to TITLES)': rs.unknown, 'not in the export any more': [] };
const tied = new Set(db.games.map(g => g.rockstarTitle).filter(Boolean));
const ids = new Set(db.games.map(g => g.id));
const lastPc = () => db.games.findLastIndex(g => g.console === 'pc');
for (const game of Object.values(rs.games)) {
  if (tied.has(game.code) || game.platforms.pc === undefined || !TITLES[game.code]) continue;
  const cs = candidates(db, game);
  if (cs.length === 1) {
    cs[0].rockstarTitle = game.code;
    report.linked.push(`${cs[0].id} ← ${game.code} (${game.name})`);
  } else if (cs.length) report.ambiguous.push(`${game.code} (${game.name}): ${cs.map(g => g.id).join(', ')}`);
  else if (!ADD) report['not added (--no-add)'].push(`${game.code} (${game.name})`);
  else {
    const g = newCopy(game);
    while (ids.has(g.id)) g.id += '-rockstar';
    ids.add(g.id);
    db.games.splice(lastPc() + 1, 0, g);
    report.added.push(`${g.id} (${game.code})`);
  }
  tied.add(game.code);
}
for (const g of db.games) if (g.rockstarTitle && !rs.games[g.rockstarTitle]) report['not in the export any more'].push(`${g.id} (${g.rockstarTitle})`);

for (const [k, v] of Object.entries(report)) if (v.length) console.log(`${k}:\n  ${v.join('\n  ')}`);
if (!DRY) {
  if (report.linked.length || report.added.length) db.updated = new Date().toISOString().slice(0, 10);
  fs.writeFileSync(DATA, JSON.stringify(db, null, 2) + '\n');
  fs.writeFileSync(OUT, JSON.stringify(rs, null, 2) + '\n');
  console.log(`wrote ${path.relative(ROOT, OUT)} and ${path.relative(ROOT, DATA)}`);
}
