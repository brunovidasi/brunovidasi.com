// Brings the EA app library into the site.
//
//   cd tools && node ea-sync.mjs                 (the export in api/ea-exporter/exports)
//   node ea-sync.mjs path/to/exports-folder      (another export)
//   node ea-sync.mjs --dry                       (report what would change, write nothing)
//   node ea-sync.mjs --no-add                    (tie copies, but add none)
//
// Two files come out of it:
//
//   data/ea.json — by EA game slug: every game and add-on (expansion, game, stuff pack, kit) the EA account
//     owns on PC (bought, redeemed or granted), with the offers it is owned through and since when, and for a
//     game the play time and last session the EA app keeps for it and its achievements (how many, how many
//     unlocked, and the ones unlocked, with when). Left out: the parts EA ships a game in (Battlefield 6's
//     HD textures, multiplayer and single player), the free Battlefield REDSEC, and the extras that aren't a
//     pack (SimCity's city sets, the Sims 4 bonus items and bundles), which EA names but has no product page for.
//     EA keeps play time per game, not per offer, so the offers of one game (a disc's key and a later
//     re-release) are one entry. The sync writes it whole every time, so don't edit it by hand.
//
//   data/games.json — a copy is tied to the EA app by "eaSlug". A game or add-on is tied to the one PC copy with the
//     same name (or listedAs; ™, a year in brackets and "Expansion Pack" at the end don't count: "MySims™ (2007)"
//     is "MySims", "The Sims™ 3 Late Night Expansion Pack" is "The Sims 3: Late Night"); when
//     there are several, to the one that is digital or a disc also in the EA app ("alsoDigital"). When it
//     is still several, it is only reported: tie it by hand. EA names a few games its own way ("The Sims™ +
//     The Sims Packs" is The Sims Legacy Collection): those are tied by hand, and stay tied. A game with no
//     copy at all was bought in the EA app, so it gets a new digital one ("launcher": "ea"), with only its
//     title and region filled in; an add-on takes its game's name before its own ("The Sims 4: Grunge Revival
//     Kit"), its series, publisher, developer and genre, and a pack from its name. After that the copy is yours. To keep one off the site, give it
//     "hidden": true rather than deleting it, or the next sync adds it back. Nothing else in a copy changes.
//
// The export comes from the PHP exporter (api/ea-exporter). When the site talks to EA itself,
// have it hand fromExport() the same responses, or write another reader that returns what fromExport() returns.

import fs from 'node:fs';
import path from 'node:path';

const ROOT = path.resolve(path.dirname(new URL(import.meta.url).pathname), '..');
const DATA = path.join(ROOT, 'data/games.json');
const OUT = path.join(ROOT, 'data/ea.json');
const args = process.argv.slice(2);
const DRY = args.includes('--dry');
const ADD = !args.includes('--no-add');
const DIR = args.find(a => !a.startsWith('--')) || path.join(ROOT, 'api/ea-exporter/exports');

/* ---------- reading an export ---------- */

const read = f => fs.existsSync(path.join(DIR, f)) ? JSON.parse(fs.readFileSync(path.join(DIR, f), 'utf8')).data : null;
const clean = s => (s || '').replace(/[™®©]/g, '').replace(/\s+/g, ' ').trim();
// EA shouts a few names ("SPORE"): a name in capitals only is put in title case
const named = s => { const n = clean(s); return /[a-z]/.test(n) || !/[A-Z]{2}/.test(n) ? n : n.toLowerCase().replace(/\b\w/g, c => c.toUpperCase()); };
// the store a product is sold in is at the end of its id ("en-us_it-takes-two-…_VideoGameProduct_au_pc")
const REGION_BY_STORE = { au: 'AUS', nz: 'AUS', gb: 'UK', uk: 'UK', us: 'USA', jp: 'JPN' };
const regionOf = id => REGION_BY_STORE[(id || '').match(/_([a-z]{2})_pc$/)?.[1]] || 'PAL';

// the parts a game is shipped in, and Battlefield's free mode: not a pack of their own
const NOT_A_PACK = /HD Textures|Shared Data|\bMultiplayer\b|\bSingle Player\b|REDSEC/i;

const norm = s => clean(s).replace(/\s*\(\d{4}\)/g, '').replace(/\s+expansion pack$/i, '').normalize('NFKD').replace(/[̀-ͯ]/g, '').toLowerCase()
  .replace(/['’`]/g, '').replace(/&/g, ' and ').replace(/[^a-z0-9]+/g, ' ').trim();
const slug = s => norm(s).replace(/ /g, '-');

/* The export, as the site keeps it. The account's ids and entitlement ids stay out: the site is public. */
export function fromExport({ owned = [], addons = [], playTime = {}, achievements = {}, exportedAt = null }) {
  const games = {};
  const add = (kind, slug, name, region, type, o, u = {}) => {
    const g = games[slug] ||= { slug, kind, type, name: named(name), region, offers: [], playtime: null, lastPlayed: null, achievements: null };
    g.offers.push({ id: o.originOfferId, name: named(name), ownership: u.ownershipMethods || [], since: u.initialEntitlementDate || null });
  };
  const gameOffers = new Set();
  for (const o of owned) {
    const p = o.product || {};
    if (!p.gameSlug || p.trialDetails || (p.baseItem?.gameType && p.baseItem.gameType !== 'BASE_GAME')) continue;
    add('game', p.gameSlug, p.name, regionOf(p.id), 'BASE_GAME', o, p.gameProductUser);
    gameOffers.add(o.originOfferId);
  }
  const left = [];
  for (const o of addons) {
    const p = o.product, l = o.legacyOffer;
    if (gameOffers.has(o.originOfferId)) continue;
    if (p?.gameSlug && !NOT_A_PACK.test(p.name)) add('addon', p.gameSlug, p.name, regionOf(p.id), p.baseItem?.gameType || null, o, p.gameProductUser);
    // with no product page, only an expansion is a pack (SimCity: Cities of Tomorrow); EA's legacy offer names it
    else if (!p && l?.displayType === 'Expansion') add('addon', slug(l.displayName), l.displayName, 'PAL', 'EXPANSION_PACK', o);
    else left.push(clean(p?.name || l?.displayName || o.originOfferId));
  }
  for (const g of Object.values(games)) {
    if (g.kind === 'addon') { g.offers.sort((x, y) => (x.since || '').localeCompare(y.since || '')); continue; }
    const t = playTime[g.slug];
    // minutes; a game the EA app has no session of was never played in it
    g.playtime = t ? Math.round((t.totalPlayTimeSeconds || 0) / 60) : 0;
    g.lastPlayed = t?.lastSessionEndDate || null;
    const sets = [...new Set(g.offers.map(o => achievements.offerToSet?.[o.id]).filter(Boolean))];
    const list = sets.flatMap(s => achievements.sets?.[s] || []);
    if (list.length) {
      // a locked achievement's date is only when it was asked for
      const unlocked = list.filter(a => a.awardCount > 0).map(a => ({ name: clean(a.name), date: a.date || null }))
        .sort((x, y) => (y.date || '').localeCompare(x.date || ''));
      g.achievements = { total: list.length, earned: unlocked.length, unlocked };
    }
    g.offers.sort((x, y) => (x.since || '').localeCompare(y.since || ''));
  }
  const all = Object.values(games);
  return {
    synced: exportedAt,
    source: 'api/ea-exporter',
    totals: { games: all.filter(g => g.kind === 'game').length, addons: all.filter(g => g.kind === 'addon').length, playtime: all.reduce((s, g) => s + (g.playtime || 0), 0) },
    games,
    left: [...new Set(left)].sort(),
  };
}

/* ---------- the shelf ---------- */

// the copies an EA game could be: a PC copy not tied yet with the same name; of several, the ones in a launcher's library
function candidates(db, game) {
  const ns = new Set([game.name, ...game.offers.map(o => o.name)].map(norm));
  const same = db.games.filter(g => g.console === 'pc' && !g.hidden && !g.eaSlug && [g.title, g.listedAs].filter(Boolean).some(t => ns.has(norm(t))));
  if (same.length < 2) return same;
  const inApp = same.filter(g => g.format === 'digital' || g.alsoDigital);
  return inApp.length ? inApp : same;
}

// an add-on's game: the tied game copy whose title its name starts with ("The Sims 4 Grunge Revival Kit": The Sims 4)
function baseOf(db, ea, game) {
  const n = norm(game.name);
  return db.games.filter(g => g.console === 'pc' && g.eaSlug && ea.games[g.eaSlug]?.kind === 'game' && n.startsWith(norm(g.title) + ' '))
    .sort((a, b) => b.title.length - a.title.length)[0] || null;
}
const packOf = (game) => /\bkit$/i.test(game.name) ? 'Kit' : /\bstuff$/i.test(game.name) ? 'Stuff Pack'
  : game.type === 'EXPANSION_PACK' ? 'Expansion Pack' : 'Extra';

function newCopy(db, ea, game) {
  const base = game.kind === 'addon' ? baseOf(db, ea, game) : null;
  // an add-on reads as its game's: "The Sims 4: Grunge Revival Kit"
  const title = base ? `${base.title}: ${clean(game.name).slice(base.title.length).replace(/^[\s:]+/, '').replace(/\s+Expansion Pack$/i, '')}` : game.name;
  return {
    id: 'pc-' + slug(title),
    console: 'pc',
    title,
    region: game.region === 'PAL' && base ? base.region : game.region,
    released: null,
    publisher: base?.publisher || null,
    developer: base?.developer || null,
    genre: base?.genre || null,
    format: 'digital',
    launcher: 'ea',
    ...(base?.series && { series: base.series }),
    ...(game.kind === 'addon' && { pack: packOf(game) }),
    eaSlug: game.slug,
    cover: null,
    colors: null,
  };
}

/* ---------- main ---------- */

const ownedFile = path.join(DIR, 'owned-games.json');
if (!fs.existsSync(ownedFile)) throw new Error(`no export in ${path.relative(ROOT, DIR)}: run php export.php in api/ea-exporter first`);
const ea = fromExport({
  owned: read('owned-games.json') || [],
  addons: read('owned-addons.json') || [],
  playTime: read('play-time.json') || {},
  achievements: read('achievements.json') || {},
  exportedAt: JSON.parse(fs.readFileSync(ownedFile, 'utf8')).exported_at_utc || fs.statSync(ownedFile).mtime.toISOString(),
});
const db = JSON.parse(fs.readFileSync(DATA, 'utf8'));
console.log(`${path.relative(ROOT, DIR)}: ${ea.totals.games} games and ${ea.totals.addons} add-ons owned, ${Math.round(ea.totals.playtime / 60)} h played, exported ${ea.synced}`);

const report = { linked: [], added: [], ambiguous: [], 'not added (--no-add)': [], 'not in the export any more': [], 'left out (not a pack)': ea.left };
const tied = new Set(db.games.map(g => g.eaSlug).filter(Boolean));
const ids = new Set(db.games.map(g => g.id));
const lastPc = () => db.games.findLastIndex(g => g.console === 'pc');
// the games first, so an add-on added after finds its game
for (const game of Object.values(ea.games).sort((a, b) => (a.kind === 'game' ? 0 : 1) - (b.kind === 'game' ? 0 : 1))) {
  if (tied.has(game.slug)) continue;
  const cs = candidates(db, game);
  if (cs.length === 1) {
    cs[0].eaSlug = game.slug;
    tied.add(game.slug);
    report.linked.push(`${cs[0].id} ← ${game.slug} (${game.name})`);
  } else if (cs.length) report.ambiguous.push(`${game.slug} (${game.name}): ${cs.map(g => g.id).join(', ')}`);
  else if (!ADD) report['not added (--no-add)'].push(`${game.slug} (${game.name})`);
  else {
    const g = newCopy(db, ea, game);
    while (ids.has(g.id)) g.id += '-ea';
    ids.add(g.id);
    db.games.splice(lastPc() + 1, 0, g);
    report.added.push(`${g.id} (${game.slug})`);
  }
  tied.add(game.slug);
}
for (const g of db.games) if (g.eaSlug && !ea.games[g.eaSlug]) report['not in the export any more'].push(`${g.id} (${g.eaSlug})`);

for (const [k, v] of Object.entries(report)) if (v.length) console.log(`${k}:\n  ${v.join('\n  ')}`);
if (!DRY) {
  if (report.linked.length || report.added.length) db.updated = new Date().toISOString().slice(0, 10);
  fs.writeFileSync(DATA, JSON.stringify(db, null, 2) + '\n');
  fs.writeFileSync(OUT, JSON.stringify(ea, null, 2) + '\n');
  console.log(`wrote ${path.relative(ROOT, OUT)} and ${path.relative(ROOT, DATA)}`);
}
