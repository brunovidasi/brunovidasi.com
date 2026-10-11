// Brings what PlayStation Network knows about the PlayStation games into the site.
//
//   cd tools && node psn-sync.mjs                 (the export in api/psn-exporter/exports)
//   node psn-sync.mjs path/to/exports-folder      (another export)
//   node psn-sync.mjs --dry                       (report what would change, write nothing)
//
// Two files come out of it:
//
//   data/psn.json — by PSN id: each game's play history (PS4 and PS5 only: how long, how many
//     times, when first and last played) and each trophy set (PS3, PS4 and PS5: how many trophies
//     of each grade it has and how many were earned), and the account's trophy level.
//     The sync writes it whole every time, so don't edit it by hand.
//
//   data/games.json — a copy is tied to PSN by "psnTitleId" (CUSA…, PPSA…: its play history)
//     and "psnTrophySet" (NPWR…: its trophies). A PSN game is tied to the one copy on its console
//     with the same name (or a name that starts with it: "The Sims 4" is "The Sims 4 Plus Cats &
//     Dogs"). When there are none or several, it is only reported: tie it by hand. A copy with more
//     than one game in it (a trilogy) takes a list: "psnTitleId": ["CUSA26613_00", …]. The PlayStation
//     games are discs on the shelf, so no copy is ever added, and nothing else in a copy changes.
//
// The export comes from the PHP exporter (api/psn-exporter, tustin/psn-php). When the site talks
// to PSN itself, have it hand fromExport() the same files, or write another reader that returns
// what fromExport() returns.

import fs from 'node:fs';
import path from 'node:path';

const ROOT = path.resolve(path.dirname(new URL(import.meta.url).pathname), '..');
const DATA = path.join(ROOT, 'data/games.json');
const OUT = path.join(ROOT, 'data/psn.json');
const args = process.argv.slice(2);
const DRY = args.includes('--dry');
const DIR = args.find(a => !a.startsWith('--')) || path.join(ROOT, 'api/psn-exporter/exports');

// apps that aren't games: they stay out
const NOT_A_GAME = /media_app|nongame|videoservice|web_app/;

/* ---------- reading an export ---------- */

const read = f => fs.existsSync(path.join(DIR, f)) ? JSON.parse(fs.readFileSync(path.join(DIR, f), 'utf8')) : null;
const clean = s => (s || '').replace(/[™®©]/g, '').replace(/\s*\(PlayStation\s*\d\)\s*$/i, '').replace(/\s+Troph(y|ies)( Collection)?$/i, '').replace(/\s+/g, ' ').trim();
// "PT26H12M11S" → 1572 (minutes)
function minutes(d) {
  const m = (d || '').match(/^P(?:(\d+)D)?T?(?:(\d+)H)?(?:(\d+)M)?(?:([\d.]+)S)?$/);
  return m ? Math.round((+m[1] || 0) * 1440 + (+m[2] || 0) * 60 + (+m[3] || 0) + (+m[4] || 0) / 60) : null;
}
// a play history's console, from its category or else its id
const titleConsole = t => /^ps5/.test(t.category) || /^PPSA/.test(t.id) ? 'ps5' : /^ps4/.test(t.category) || /^CUSA/.test(t.id) ? 'ps4' : null;
// a trophy set's: the newer trophy service is the PS5's; the older one serves the PS3, PS4 and Vita
const setConsoles = s => s.serviceName === 'trophy2' ? ['ps5'] : ['ps4', 'ps3'];
const grades = (o, pre = '', post = 'Count') => Object.fromEntries(['platinum', 'gold', 'silver', 'bronze'].map(k => [k, o[pre + (pre ? k[0].toUpperCase() + k.slice(1) : k) + post] || 0]));

/* The export, as the site keeps it. The account's ids, profile and friends stay out: the site is public. */
export function fromExport({ games = [], trophyTitles = [], summary = null, exportedAt = null }) {
  const titles = {};
  for (const t of games) {
    if (NOT_A_GAME.test(t.category || '')) continue;
    titles[t.id] = {
      id: t.id,
      name: clean(t.localizedName || t.name),
      console: titleConsole(t),
      // minutes
      playtime: minutes(t.playDuration),
      playCount: t.playCount ?? null,
      firstPlayed: t.firstPlayedDateTime || null,
      lastPlayed: t.lastPlayedDateTime || null,
      image: t.localizedImageUrl || t.imageUrl || null,
    };
  }
  const trophySets = {};
  for (const s of trophyTitles) {
    const earned = grades(s, 'earnedTrophies');
    trophySets[s.npCommunicationId] = {
      id: s.npCommunicationId,
      name: clean(s.name),
      detail: s.detail || null,
      consoles: setConsoles(s),
      version: s.trophySetVersion || null,
      icon: s.iconUrl || null,
      total: s.trophyCount ?? null,
      // the export leaves out how many platinums a set has: one, when it has one
      defined: { ...grades(s, '', 'TrophyCount'), platinum: s.platinumTrophyCount ?? (s.hasPlatinum ? 1 : 0) },
      earned,
      earnedTotal: Object.values(earned).reduce((a, b) => a + b, 0),
      progress: s.progress ?? null,
      lastUpdated: s.lastUpdatedDateTime || null,
    };
  }
  return {
    synced: exportedAt,
    source: 'api/psn-exporter',
    trophyLevel: summary && {
      level: summary.level ?? summary.raw?.trophyLevel ?? null,
      tier: summary.tier ?? null,
      progress: summary.progress ?? null,
      points: summary.raw?.trophyPoint ?? null,
      earned: { platinum: summary.platinum || 0, gold: summary.gold || 0, silver: summary.silver || 0, bronze: summary.bronze || 0 },
    },
    titles,
    trophySets,
  };
}

/* ---------- the shelf ---------- */

const norm = s => clean(s).normalize('NFKD').replace(/[̀-ͯ]/g, '').toLowerCase().replace(/['’`]/g, '').replace(/&/g, ' and ').replace(/[^a-z0-9]+/g, ' ').trim();

// the copies a PSN game could be: on its console, not tied yet, the same name, else a name that starts with it
function candidates(db, name, consoles, field) {
  const free = db.games.filter(g => consoles.includes(g.console) && !g[field]?.length);
  const n = norm(name);
  const same = free.filter(g => norm(g.title) === n);
  return same.length ? same : free.filter(g => norm(g.title).startsWith(n + ' '));
}

/* ---------- main ---------- */

const gamesFile = path.join(DIR, 'games.json');
if (!fs.existsSync(gamesFile)) throw new Error(`no export in ${path.relative(ROOT, DIR)}: run php export.php in api/psn-exporter first`);
const psn = fromExport({
  games: read('games.json') || [],
  trophyTitles: read('trophy-titles.json') || [],
  summary: read('trophy-summary.json'),
  exportedAt: fs.statSync(gamesFile).mtime.toISOString(),
});
const db = JSON.parse(fs.readFileSync(DATA, 'utf8'));
console.log(`${path.relative(ROOT, DIR)}: ${Object.keys(psn.titles).length} games played, ${Object.keys(psn.trophySets).length} trophy sets, exported ${psn.synced}`);

const report = { linked: [], ambiguous: [], 'not on the shelf': [], 'not in the export any more': [] };
const tie = (entries, field, consolesOf) => {
  const tied = new Set(db.games.flatMap(g => [g[field] || []].flat()));
  for (const e of Object.values(entries)) {
    if (tied.has(e.id)) continue;
    // a name PSN uses for more than one entry on these consoles can't say which copy is which
    const twins = Object.values(entries).filter(o => o !== e && !tied.has(o.id) && norm(o.name) === norm(e.name) && consolesOf(o).some(c => consolesOf(e).includes(c)));
    const cs = candidates(db, e.name, consolesOf(e), field);
    if (cs.length === 1 && !twins.length) {
      cs[0][field] = e.id;
      tied.add(e.id);
      report.linked.push(`${cs[0].id} ← ${e.id} ${e.name}`);
    } else if (cs.length) report.ambiguous.push(`${e.id} ${e.name} (${consolesOf(e).join('/')}): ${cs.map(g => g.id).join(', ')}${twins.length ? ` — and ${twins.map(t => t.id).join(', ')} has the same name` : ''}`);
    else report['not on the shelf'].push(`${e.id} ${e.name} (${consolesOf(e).join('/')})`);
  }
  for (const g of db.games) for (const id of [g[field] || []].flat()) if (!entries[id]) report['not in the export any more'].push(`${g.id} (${id})`);
};
tie(psn.titles, 'psnTitleId', t => [t.console]);
tie(psn.trophySets, 'psnTrophySet', s => s.consoles);

for (const [k, v] of Object.entries(report)) if (v.length) console.log(`${k}:\n  ${v.join('\n  ')}`);
if (!DRY) {
  if (report.linked.length) db.updated = new Date().toISOString().slice(0, 10);
  fs.writeFileSync(DATA, JSON.stringify(db, null, 2) + '\n');
  fs.writeFileSync(OUT, JSON.stringify(psn, null, 2) + '\n');
  console.log(`wrote ${path.relative(ROOT, OUT)} and ${path.relative(ROOT, DATA)}`);
}
