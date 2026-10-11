// Brings the Steam library into the site.
//
//   cd tools && npm install && node steam-sync.mjs        (the newest export in api/steam-exporter/exports)
//   node steam-sync.mjs path/to/steam-export.json         (one export in particular)
//   node steam-sync.mjs --dry                             (report what would change, write nothing)
//   node steam-sync.mjs --no-covers                       (don't download the library art)
//
// Two files come out of it:
//
//   data/steam.json — everything Steam says about each game, by app id: playtime, when it was
//     last played, achievements, and the store page (description, genres, platforms, pictures…).
//     This file belongs to the sync: it is written whole every time, so don't edit it by hand.
//
//   data/games.json — the copies on the shelf. A copy is tied to Steam by "steamAppId". A game
//     in the library with no copy tied to it gets a new digital one ("launcher": "steam"), with
//     the Steam library art as its cover. A digital PC copy with the same title, no "steamAppId"
//     and no other launcher is tied to it instead; a boxed copy never is, as the Steam one is a
//     copy of its own. (A disc that activates on Steam can be tied by hand with "steamAppId" and
//     "alsoDigital": true.) After that the copy is yours:
//     the sync only fills fields that are still empty and never changes one that is set.
//     To keep a Steam game off the site, give its copy "hidden": true rather than deleting
//     it, or the next sync adds it back.
//
// The export comes from the PHP exporter (api/steam-exporter). When the site talks to the
// Steam API itself, have it hand fromExport() the same shape, or write another reader that
// returns what fromExport() returns.

import fs from 'node:fs';
import path from 'node:path';
import sharp from 'sharp';
import { palette } from './palette.mjs';

const ROOT = path.resolve(path.dirname(new URL(import.meta.url).pathname), '..');
const DATA = path.join(ROOT, 'data/games.json');
const OUT = path.join(ROOT, 'data/steam.json');
const EXPORTS = path.join(ROOT, 'api/steam-exporter/exports');
const args = process.argv.slice(2);
const DRY = args.includes('--dry');
const COVERS = !args.includes('--no-covers');
const HEIGHT = 520; // as fetch-covers.mjs
const CDN = 'https://shared.akamai.steamstatic.com/store_item_assets/steam/apps';

// the store's prices say which shop the account buys in, and so the copy's region
const REGION_BY_CURRENCY = { AUD: 'AUS', NZD: 'AUS', GBP: 'UK', EUR: 'EUR', USD: 'USA', JPY: 'JPN' };
// Steam's genres that say how it is sold rather than what it is
const NOT_A_GENRE = new Set(['Free To Play', 'Free to Play', 'Indie', 'Early Access', 'Massively Multiplayer']);

/* ---------- reading an export ---------- */

function newestExport() {
  const files = fs.existsSync(EXPORTS) ? fs.readdirSync(EXPORTS).filter(f => /^steam-export-.*\.json$/.test(f)).sort() : [];
  if (!files.length) throw new Error(`no export in ${path.relative(ROOT, EXPORTS)}: run php export.php there first`);
  return path.join(EXPORTS, files.at(-1));
}

const clean = s => (s || '').replace(/[™®©]/g, '').replace(/\s+/g, ' ').trim();
const text = html => clean((html || '').replace(/<br\s*\/?>/gi, '\n').replace(/<\/(p|li|h\d)>/gi, '\n').replace(/<[^>]+>/g, '')
  .replace(/&nbsp;/g, ' ').replace(/&amp;/g, '&').replace(/&quot;/g, '"').replace(/&#39;/g, "'").replace(/&lt;/g, '<').replace(/&gt;/g, '>'));
const MONTHS = { jan: 1, feb: 2, mar: 3, apr: 4, may: 5, jun: 6, jul: 7, aug: 8, sep: 9, oct: 10, nov: 11, dec: 12 };
// "6 Feb, 2013" or "Feb 6, 2013" → "2013-02-06"; "2013" → "2013"
function isoDate(s) {
  if (!s) return null;
  const pad = n => String(n).padStart(2, '0');
  let m = s.match(/^(\d{1,2}) (\w{3})\w*,? (\d{4})$/);
  if (m && MONTHS[m[2].toLowerCase()]) return `${m[3]}-${pad(MONTHS[m[2].toLowerCase()])}-${pad(m[1])}`;
  m = s.match(/^(\w{3})\w* (\d{1,2}),? (\d{4})$/);
  if (m && MONTHS[m[1].toLowerCase()]) return `${m[3]}-${pad(MONTHS[m[1].toLowerCase()])}-${pad(m[2])}`;
  m = s.match(/^(\w{3})\w* (\d{4})$/);
  if (m && MONTHS[m[1].toLowerCase()]) return `${m[2]}-${pad(MONTHS[m[1].toLowerCase()])}`;
  m = s.match(/^(\d{4})$/);
  return m ? m[1] : null;
}
const when = t => t ? new Date(t * 1000).toISOString() : null;
const list = v => Array.isArray(v) ? v : v && typeof v === 'object' ? Object.values(v) : [];

/* The export, as the site keeps it: one record per game, by app id. The account's own
   details (its SteamID, bans, badges) stay out: the site is public. */
export function fromExport(raw) {
  const d = raw.data || {};
  const owned = list(d.owned_games);
  const store = d.store_details_by_appid || {};
  const ach = d.achievements_by_appid || {};
  const schemas = d.game_schemas_by_appid || {};
  const recent = new Map(list(d.recently_played).map(r => [r.appid, r.playtime_2weeks || 0]));
  const currency = Object.values(store).find(s => s?.price_overview)?.price_overview.currency || null;

  const games = {};
  for (const o of owned) {
    const id = String(o.appid);
    const s = store[id] || null;
    const stats = ach[id]?.playerstats;
    const got = stats?.success !== false ? list(stats?.achievements) : [];
    const defs = list(schemas[id]?.schema?.availableGameStats?.achievements);
    const unlocked = new Map(got.filter(a => a.achieved).map(a => [a.apiname, a.unlocktime || null]));
    const total = s?.achievements?.total ?? (defs.length || null);
    games[id] = {
      appid: o.appid,
      name: clean(s?.name || o.name),
      storeUrl: `https://store.steampowered.com/app/${o.appid}`,
      // minutes
      playtime: {
        total: o.playtime_forever || 0,
        windows: o.playtime_windows_forever || 0,
        mac: o.playtime_mac_forever || 0,
        linux: o.playtime_linux_forever || 0,
        deck: o.playtime_deck_forever || 0,
        lastTwoWeeks: o.playtime_2weeks || recent.get(o.appid) || 0,
      },
      lastPlayed: when(o.rtime_last_played),
      achievements: {
        total,
        // null when Steam didn't say (a private profile, or a game without them)
        unlocked: got.length ? unlocked.size : null,
        list: defs.map(a => ({
          id: a.name,
          name: a.displayName,
          description: a.description || null,
          icon: a.icon || null,
          hidden: !!a.hidden,
          unlocked: unlocked.has(a.name) ? (when(unlocked.get(a.name)) || true) : got.length ? false : null,
        })),
      },
      images: {
        library: `${CDN}/${o.appid}/${o.capsule_filename || 'library_600x900.jpg'}`,
        icon: o.img_icon_url ? `https://media.steampowered.com/steamcommunity/public/images/apps/${o.appid}/${o.img_icon_url}.jpg` : null,
        header: s?.header_image || null,
        capsule: s?.capsule_imagev5 || s?.capsule_image || null,
        background: s?.background_raw || s?.background || null,
      },
      store: s && {
        type: s.type,
        free: !!s.is_free,
        description: clean(s.short_description),
        about: text(s.about_the_game),
        released: isoDate(s.release_date?.date),
        comingSoon: !!s.release_date?.coming_soon,
        developers: (s.developers || []).map(clean),
        publishers: (s.publishers || []).map(clean),
        genres: (s.genres || []).map(g => g.description),
        categories: (s.categories || []).map(c => c.description),
        platforms: s.platforms || null,
        controllerSupport: s.controller_support || null,
        languages: text(s.supported_languages).replace(/\*/g, '').split(/\s*,\s*/).map(l => l.split('\n')[0].trim()).filter(Boolean),
        metacritic: s.metacritic || null,
        recommendations: s.recommendations?.total ?? null,
        ageRatings: Object.fromEntries(Object.entries(s.ratings || {}).filter(([, r]) => r?.rating).map(([k, r]) => [k, r.rating])),
        price: s.price_overview ? { currency: s.price_overview.currency, initial: s.price_overview.initial / 100, final: s.price_overview.final / 100, discount: s.price_overview.discount_percent } : null,
        dlc: s.dlc || [],
        website: s.website || null,
        screenshots: (s.screenshots || []).map(x => x.path_full),
        trailers: (s.movies || []).map(m => ({ name: m.name, thumbnail: m.thumbnail, video: m.hls_h264 || m.dash_h264 || null })),
      },
      flags: { workshop: !!o.has_workshop, market: !!o.has_market, dlc: !!o.has_dlc },
    };
  }
  return {
    synced: raw.exported_at_utc || new Date().toISOString(),
    source: 'api/steam-exporter',
    currency,
    totals: { games: owned.length, playtime: owned.reduce((n, o) => n + (o.playtime_forever || 0), 0) },
    games,
  };
}

/* ---------- the shelf ---------- */

const norm = s => clean(s).normalize('NFKD').replace(/[̀-ͯ]/g, '').toLowerCase().replace(/['’`]/g, '').replace(/[^a-z0-9]+/g, ' ').trim();
const slug = s => norm(s).replace(/ /g, '-');

// a new copy for a game in the library that isn't on the shelf
function newCopy(s, region) {
  const st = s.store || {};
  return {
    id: 'pc-' + slug(s.name),
    console: 'pc',
    title: s.name,
    region,
    released: st.released || null,
    dateSource: 'steam',
    publisher: st.publishers?.join(', ') || null,
    developer: st.developers?.join(', ') || null,
    genre: (st.genres || []).find(g => !NOT_A_GENRE.has(g)) || st.genres?.[0] || null,
    format: 'digital',
    launcher: 'steam',
    ...(st.platforms?.mac && { mac: true }),
    steamAppId: s.appid,
    cover: null,
    colors: null,
  };
}

// the fields the sync may fill in when they are empty, and nothing else
function fillBlanks(g, s) {
  const fresh = newCopy(s, g.region);
  const filled = [];
  for (const k of ['released', 'publisher', 'developer', 'genre']) {
    if (g[k] == null && fresh[k] != null) { g[k] = fresh[k]; filled.push(k); if (k === 'released') g.dateSource = 'steam'; }
  }
  return filled;
}

async function libraryArt(g, s) {
  const r = await fetch(s.images.library);
  if (!r.ok) throw new Error(`${s.images.library} ${r.status}`);
  const buf = Buffer.from(await r.arrayBuffer());
  const rel = `covers/pc/${g.id.slice(3)}.jpg`;
  const abs = path.join(ROOT, rel);
  fs.mkdirSync(path.dirname(abs), { recursive: true });
  await sharp(buf).flatten({ background: '#ffffff' }).resize({ height: HEIGHT, withoutEnlargement: true }).jpeg({ quality: 80, mozjpeg: true }).toFile(abs);
  const meta = await sharp(abs).metadata();
  g.cover = { file: rel, ratio: +(meta.width / meta.height).toFixed(4), source: 'steam', ref: `${s.name} · Steam library art · app ${s.appid}` };
  g.colors = await palette(buf);
}

/* ---------- main ---------- */

const file = args.find(a => !a.startsWith('--')) || newestExport();
const steam = fromExport(JSON.parse(fs.readFileSync(file, 'utf8')));
const db = JSON.parse(fs.readFileSync(DATA, 'utf8'));
const region = REGION_BY_CURRENCY[steam.currency] || 'PAL';
console.log(`${path.relative(ROOT, file)}: ${steam.totals.games} games, exported ${steam.synced}`);

const byApp = new Map(db.games.filter(g => g.steamAppId).map(g => [g.steamAppId, g]));
const ids = new Set(db.games.map(g => g.id));
const lastPc = () => db.games.findLastIndex(g => g.console === 'pc');
const report = { added: [], linked: [], filled: [], covers: [], gone: [] };

for (const s of Object.values(steam.games)) {
  let g = byApp.get(s.appid);
  if (!g) {
    // a digital PC copy already on the shelf under the same name, not in another launcher, is this game
    g = db.games.find(x => x.console === 'pc' && x.format === 'digital' && !x.steamAppId && (!x.launcher || x.launcher === 'steam')
      && [x.title, x.title + ' ' + (x.edition || '')].some(t => norm(t) === norm(s.name)));
    if (g) {
      g.steamAppId = s.appid;
      g.launcher = 'steam';
      report.linked.push(`${g.id} ← ${s.appid} ${s.name}`);
    } else {
      g = newCopy(s, region);
      while (ids.has(g.id)) g.id += '-steam';
      ids.add(g.id);
      db.games.splice(lastPc() + 1, 0, g);
      report.added.push(`${g.id} (${s.appid})`);
    }
    byApp.set(s.appid, g);
  }
  const filled = fillBlanks(g, s);
  if (filled.length) report.filled.push(`${g.id}: ${filled.join(', ')}`);
  if (COVERS && !g.cover && !DRY) {
    try { await libraryArt(g, s); report.covers.push(g.id); } catch (e) { console.warn(`  no cover for ${g.id}: ${e.message}`); }
  }
}
// a copy tied to Steam that the library no longer lists stays; it is only reported
for (const g of db.games) if (g.steamAppId && !steam.games[g.steamAppId]) report.gone.push(`${g.id} (${g.steamAppId})`);

for (const [k, v] of Object.entries(report)) if (v.length) console.log(`${k === 'gone' ? 'not in the library any more' : k}:\n  ${v.join('\n  ')}`);
if (!DRY) {
  if (report.added.length || report.linked.length || report.filled.length || report.covers.length) db.updated = new Date().toISOString().slice(0, 10);
  fs.writeFileSync(DATA, JSON.stringify(db, null, 2) + '\n');
  fs.writeFileSync(OUT, JSON.stringify(steam, null, 2) + '\n');
  console.log(`wrote ${path.relative(ROOT, OUT)} and ${path.relative(ROOT, DATA)}`);
}
