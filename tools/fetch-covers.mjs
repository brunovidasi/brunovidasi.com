// Finds box art for every game in data/games.json that has no cover yet.
//
//   cd tools && npm install && node fetch-covers.mjs            (games without a cover)
//   node fetch-covers.mjs --only ps2-final-fantasy-x            (one game, even if it has one)
//   node fetch-covers.mjs --dry                                 (report matches, download nothing)
//   node fetch-covers.mjs --recheck                             (swap covers for a better match, say after a region changed)
//   node fetch-covers.mjs --upgrade-wiki                        (swap Wikipedia pictures on the consoles below for real box scans)
//
// Sources (free, no keys; the first three on GitHub):
//   libretro-thumbnails — front box scans named after the release, region by region
//   aldostools/resources — PS3 covers named by disc serial (BLES…, BLUS…), with titleid.txt
//   xlenore/ps2-covers — PS2 covers named by disc serial (SLES-52047…), used when coverMatch is a serial
//   The Sims Wiki, then Wikipedia — PC games: the page image of the game's page
//   libretro-thumbnails DOS — PC games from the DOS days, when the wikis have nothing
//   TheGamesDB — real box scans, region by region, for PS4, PS5, Xbox One, Xbox 360, Switch, Switch 2 and PC
//   Wikipedia — what TheGamesDB lacks on those consoles (often key art, not the box)
//
// A game can steer the match with "coverMatch": the exact libretro file name without
// ".png", a PS3 serial such as "BLES00229", a PS2 serial such as "SLES-52047", or for a PC game
// the wiki page to take the box from, such as "SimCity (2013 video game)" (the Wikipedia page, too, for
// the consoles that only Wikipedia covers). "wikiCover": false stops a console game taking Wikipedia's box. Covers you add yourself (cover.source
// "manual") are never replaced: the script only measures their shape and colours if they are missing.
// "tgdbId": 39758 takes that TheGamesDB entry's box, when the search picks the wrong one.

import fs from 'node:fs';
import path from 'node:path';
import { execFileSync } from 'node:child_process';
import sharp from 'sharp';
import { palette } from './palette.mjs';

const ROOT = path.resolve(path.dirname(new URL(import.meta.url).pathname), '..');
const DATA = path.join(ROOT, 'data/games.json');
const CACHE = process.env.COVER_CACHE || path.join(ROOT, 'tools/.cache');
const args = process.argv.slice(2);
const DRY = args.includes('--dry');
const ONLY = args.includes('--only') ? args[args.indexOf('--only') + 1] : null;
const RECHECK = args.includes('--recheck');
const UPGRADE_WIKI = args.includes('--upgrade-wiki');
const HEIGHT = 520;

const LIBRETRO = {
  ps1: 'Sony_-_PlayStation', ps2: 'Sony_-_PlayStation_2', ps3: 'Sony_-_PlayStation_3',
  xbox: 'Microsoft_-_Xbox', x360: 'Microsoft_-_Xbox_360',
  wii: 'Nintendo_-_Wii', wiiu: 'Nintendo_-_Wii_U',
  ds: 'Nintendo_-_Nintendo_DS', '3ds': 'Nintendo_-_Nintendo_3DS', n3ds: 'Nintendo_-_Nintendo_3DS',
  gb: 'Nintendo_-_Game_Boy', gbc: 'Nintendo_-_Game_Boy_Color', gba: 'Nintendo_-_Game_Boy_Advance',
  snes: 'Nintendo_-_Super_Nintendo_Entertainment_System',
  dos: 'DOS', // PC games from the DOS days, such as the first Grand Theft Auto
};

/* ---------- listing a repo without downloading it ---------- */

function clone(owner, repo) {
  const dir = path.join(CACHE, `${owner}__${repo}`);
  if (!fs.existsSync(path.join(dir, '.git'))) {
    fs.mkdirSync(CACHE, { recursive: true });
    execFileSync('git', ['clone', '-q', '--filter=blob:none', '--no-checkout', '--depth', '1', `https://github.com/${owner}/${repo}`, dir], { stdio: 'inherit', env: { ...process.env, GIT_LFS_SKIP_SMUDGE: '1' } });
  }
  return dir;
}
const lists = {};
function listFiles(owner, repo, folder) {
  const key = `${owner}/${repo}/${folder}`;
  if (!lists[key]) {
    const out = execFileSync('git', ['-C', clone(owner, repo), 'ls-tree', '--name-only', `HEAD:${folder}`], { maxBuffer: 64 << 20 }).toString();
    lists[key] = out.split('\n').filter(Boolean);
  }
  return lists[key];
}

/* ---------- names ---------- */

const ARTICLE = /^(.*?), (The|A|An)( - .*)?$/;
// Loose enough that "Billy & Mandy", "Billy _ Mandy" and "Billy and Mandy" all agree.
export function norm(s) {
  return s.replace(/[\u2122\u00ae\u00a9]/g, ' ').normalize('NFKD').replace(/[\u0300-\u036f]/g, '')
    .toLowerCase().replace(/['\u2019`]/g, '')
    .replace(/[^a-z0-9]+/g, ' ').replace(/\band\b/g, ' ').replace(/\s+/g, ' ').trim().replace(/^the /, '');
}
// "Chronicles of Narnia, The - The Lion… (Europe) (En,Fr)" → { base: "the chronicles of narnia the lion…", tags: [...] }
function parseLibretro(file) {
  const stem = file.replace(/\.png$/i, '');
  const i = stem.indexOf(' (');
  let base = i < 0 ? stem : stem.slice(0, i);
  const m = base.match(ARTICLE);
  if (m) base = `${m[2]} ${m[1]}${m[3] || ''}`;
  return { stem, base: norm(base), tags: [...stem.matchAll(/\(([^)]*)\)/g)].map(x => x[1]) };
}

const BAD = /\b(beta|proto|prototype|demo|sample|kiosk|preview|trade|promo|unl|pirate|hack)\b/i;
const EU_OTHER = /\b(France|Germany|Spain|Italy|Netherlands|Scandinavia|Sweden|Russia|Poland|Portugal|Greece|Brazil|Korea|China|Taiwan|Asia)\b/;
// what each region code plays on (as in js/consoles.js)
const STD = { AUS: 'PAL', EUR: 'PAL', HOL: 'PAL', PAL: 'PAL', USA: 'NTSC-U', 'NTSC-U': 'NTSC-U', JPN: 'NTSC-J', 'NTSC-J': 'NTSC-J' };
// how well a scan's region tags suit a copy: 0 is its own box, 5 and up is another region's
function regionRank(tags, region) {
  const t = tags.join(' | ');
  if (BAD.test(t)) return 99;
  const std = STD[region] || 'PAL';
  let r;
  if (std === 'NTSC-J') r = /\bJapan\b/.test(t) ? 0 : /\bWorld\b/.test(t) ? 3 : /\bUSA\b/.test(t) ? 5 : 8;
  else if (std === 'NTSC-U') r = /\bUSA\b/.test(t) ? (/Europe/.test(t) ? 1 : 0) : /\bWorld\b/.test(t) ? 2 : /\bCanada\b/.test(t) ? 3 : /\bEurope\b/.test(t) ? 5 : 8;
  else if (region === 'HOL' && /\bNetherlands\b/.test(t)) r = 0;
  // a European copy: the European box first, an Australian one is next best
  else if (region === 'EUR' || region === 'HOL') r = /\bEurope\b/.test(t) ? (EU_OTHER.test(t) ? 4 : 0.5) : /\bUK\b/.test(t) ? 1 : /\bAustralia\b/.test(t) ? 2 : /\bWorld\b/.test(t) ? 3 : EU_OTHER.test(t) ? 6 : /\bUSA\b/.test(t) ? 5 : 8;
  // an Australian copy, or a PAL one from either: the Australian box first
  else r = /\bAustralia\b/.test(t) ? 0 : /\bEurope\b/.test(t) ? (EU_OTHER.test(t) ? 4 : 1) : /\bUK\b/.test(t) ? 2 : /\bWorld\b/.test(t) ? 3 : EU_OTHER.test(t) ? 6 : /\bUSA\b/.test(t) ? 5 : 8;
  if (/Virtual Console|Switch Online|Collection/.test(t)) r += 0.5;
  if (/Rev \d|v\d/.test(t)) r += 0.1;
  return r;
}

function findLibretro(g, sys) {
  const repo = LIBRETRO[sys];
  if (!repo) return null;
  const files = listFiles('libretro-thumbnails', repo, 'Named_Boxarts');
  if (g.coverMatch) {
    const f = files.find(x => x.replace(/\.png$/i, '') === g.coverMatch);
    return f ? { repo, file: f, ref: g.coverMatch } : null;
  }
  const want = norm(g.title);
  const hits = files.map(parseLibretro).filter(p => p.base === want);
  if (!hits.length) return null;
  hits.sort((a, b) => regionRank(a.tags, g.region) - regionRank(b.tags, g.region) || a.stem.length - b.stem.length);
  if (regionRank(hits[0].tags, g.region) >= 99) return null;
  return { repo, file: hits[0].stem + '.png', ref: hits[0].stem, offRegion: regionRank(hits[0].tags, g.region) >= 5 };
}

let ps3Titles;
function findPs3(g) {
  const covers = new Set(listFiles('aldostools', 'resources', 'COV'));
  if (!ps3Titles) {
    const dir = clone('aldostools', 'resources');
    const txt = execFileSync('git', ['-C', dir, 'show', 'HEAD:titleid.txt'], { maxBuffer: 64 << 20 }).toString();
    ps3Titles = txt.split('\n').map(l => l.trim()).filter(Boolean).map(l => {
      const [id, ...rest] = l.split(' ');
      const title = rest.join(' ');
      // "Hitman: Absolution™ [EUR]" → key "hitman absolution", tag "EUR"
      const tag = (title.match(/\[([^\]]*)\]/) || [])[1] || '';
      let t = title.replace(/\s*\[[^\]]*\]/g, '');
      const m = t.match(/^(.*?), (The|A|An)( - .*| [:\-–].*)?$/);
      if (m) t = `${m[2]} ${m[1]}${m[3] || ''}`;
      const rank = !tag ? 0 : /^(EUR|ENG|UK|EN)$/i.test(tag) ? 0.5 : /edition|platinum|essentials|greatest|classics|pack/i.test(tag) ? 2 : 5;
      return { id, title, key: norm(t), rank };
    });
  }
  const pick = ids => ids.map(id => ({ id, f: [...covers].find(c => c.toUpperCase() === id.toUpperCase() + '.JPG') })).filter(x => x.f);
  if (g.coverMatch) {
    const [x] = pick([g.coverMatch]);
    return x ? { file: x.f, ref: x.id } : null;
  }
  const want = norm(g.title);
  const std = STD[g.region] || 'PAL';
  const order = std === 'NTSC-U' ? ['BCUS', 'BLUS', 'NPUA', 'NPUB'] : std === 'NTSC-J' ? ['BCJS', 'BLJS', 'BLJM', 'NPJA', 'NPJB'] : ['BCES', 'BLES', 'NPEA', 'NPEB'];
  const rows = ps3Titles.filter(r => r.key === want);
  for (const pre of order) {
    const ids = rows.filter(r => r.id.startsWith(pre)).sort((a, b) => a.rank - b.rank || a.id.localeCompare(b.id)).map(r => r.id);
    const [x] = pick(ids);
    if (x) return { file: x.f, ref: `${x.id} · ${rows.find(r => r.id === x.id).title}` };
  }
  return null;
}

/* ---------- PC: the box art on a game's wiki page ----------
   None of the collections above have PC games. The Sims Wiki has a page for every Sims
   game and pack with its box in the infobox, and Wikipedia has the rest, with the box as
   the page image. (The Sims Wiki's page image is usually a screenshot, so it isn't used.)
   The page is the game's title (or its coverMatch); the wikis follow redirects. */
// Wikimedia turns away requests whose User-Agent doesn't say who is asking.
const UA = 'games-website-cover-fetcher/1.0 (https://github.com/brunovidasi/games-website)';
const getJSON = async url => {
  for (let i = 0; i < 4; i++) {
    const r = await fetch(url, { headers: { 'User-Agent': UA } });
    if (r.ok) return r.json();
    if (r.status !== 429) return null;
    await new Promise(res => setTimeout(res, 5000 * 2 ** i));
  }
  return null;
};

// The infobox's "image = [[File:The Sims 2 Apartment Life Cover.jpg|250px]]". A page with
// several boxes puts them in tabs ("Modernised=[[File:…]] |-| Original=[[File:…]]"): a box
// on the shelf takes the original one, a pack in the EA app the first, which is today's art.
function infoboxFile(text, digital) {
  const tabs = text.match(/\|\s*image\s*=\s*<tabber>([\s\S]*?)<\/tabber>/i)?.[1];
  if (!tabs) return text.match(/\|\s*image\s*=\s*\[\[\s*(?:File|Image)\s*:\s*([^|\]]+)/i)?.[1].trim();
  const files = tabs.split('|-|').map(t => ({
    name: t.split('=')[0].trim(),
    file: t.match(/\[\[\s*(?:File|Image)\s*:\s*([^|\]]+)/i)?.[1].trim(),
  })).filter(t => t.file);
  if (!files.length) return null;
  if (digital) return files[0].file;
  return (files.find(t => /original|1st|first/i.test(t.name)) || files[files.length - 1]).file;
}

async function findSimsWiki(page, g) {
  const api = 'https://sims.fandom.com/api.php';
  const j = await getJSON(`${api}?action=parse&format=json&redirects=1&prop=wikitext&section=0&page=${encodeURIComponent(page)}`);
  const text = j?.parse?.wikitext?.['*'];
  if (!text) return null;
  const file = infoboxFile(text, g.format === 'digital');
  if (!file) return null;
  const info = await getJSON(`${api}?action=query&format=json&prop=imageinfo&iiprop=url&titles=${encodeURIComponent('File:' + file)}`);
  const url = Object.values(info?.query?.pages || {})[0]?.imageinfo?.[0]?.url;
  return url ? { url, source: 'sims.fandom.com', ref: `${j.parse.title} · ${file}` } : null;
}

async function findWikipedia(page) {
  const j = await getJSON(`https://en.wikipedia.org/w/api.php?action=query&format=json&redirects=1&prop=pageimages&piprop=original&pilicense=any&titles=${encodeURIComponent(page)}`);
  // A box is a scan, never a drawing: an .svg page image is the game's logo.
  // A box is a scan, never a drawing: an .svg page image is the game's logo. A box is also
  // taller than it is wide, so a square store icon or a wide banner isn't one either.
  const p = Object.values(j?.query?.pages || {}).find(p => p.original?.source && !/\.svg(\?|$)/i.test(p.original.source)
    && !(p.original.width / p.original.height > 0.9));
  if (!p) return null;
  const img = p.original.source;
  return { url: img, source: 'en.wikipedia.org', ref: `${p.title} · ${decodeURIComponent(img.split('/').pop())}` };
}

// The consoles none of the collections have: the box on the game's Wikipedia page. That is
// often key art rather than a scan, and for a game on several consoles it can be another
// console's box, so check what comes back. "wikiCover": false keeps a wrong one from coming back.
const WIKIPEDIA_ONLY = ['ps4', 'ps5', 'xone', 'x360', 'switch', 'switch2'];
async function findConsoleWiki(g) {
  if (g.wikiCover === false) return null;
  for (const page of g.coverMatch ? [g.coverMatch] : [g.title, `${g.title} (video game)`]) {
    const hit = await findWikipedia(page);
    if (hit) return hit;
  }
  return null;
}

/* ---------- TheGamesDB ----------
   Its search page lists each release with its region and a front box scan: the scan of the
   copy's own region is taken (a PAL copy a British or Australian box, in English), else another. */
const TGDB_PLATFORM = { ps4: 4919, ps5: 4980, xone: 4920, x360: 15, switch: 4971, switch2: 5021, pc: 1 };
const tgdbBox = id => `https://cdn.thegamesdb.net/images/original/boxart/front/${id}-1.jpg`;
const tgdbRank = (region, std) => {
  const pal = /\bPAL\b/.test(region), ntsc = /\bNTSC/.test(region);
  const english = !region || /United Kingdom|Australia|United States|Canada|^\s*(PAL|NTSC(-U)?)\s*$/.test(region);
  // (an English box from elsewhere before a PAL one in another language)
  if (std === 'PAL') return pal && english ? 0 : !region ? 1.5 : english ? 2 : pal ? 2.2 : 3;
  if (std === 'NTSC-U') return ntsc && english ? 0 : !region ? 1 : ntsc ? 2 : 3;
  return /Japan|NTSC-J/.test(region) ? 0 : 1;
};
async function findTgdb(g, sys) {
  if (!TGDB_PLATFORM[sys]) return null;
  if (g.tgdbId) return { url: tgdbBox(g.tgdbId), source: 'thegamesdb.net', ref: `TheGamesDB #${g.tgdbId}` };
  const r = await fetch(`https://thegamesdb.net/search.php?name=${encodeURIComponent(g.title)}&platform_id%5B%5D=${TGDB_PLATFORM[sys]}`, { headers: { 'User-Agent': UA } });
  if (!r.ok) return null;
  const html = await r.text();
  const want = norm(g.title), std = STD[g.region] || 'PAL';
  const hits = [];
  // one card per release; a card without a box scan is passed over
  for (const card of html.split('<div class="col-6 col-md-2">').slice(1)) {
    const m = card.match(/game\.php\?id=(\d+)[\s\S]*?boxart\/front\/\d+-1\.jpg[\s\S]*?<p>([\s\S]*?)<\/p>([\s\S]*?)<p class="text-muted">/);
    if (!m) continue;
    const title = m[2].replace(/&amp;/g, '&').replace(/&#0?39;|&apos;/g, "'").trim();
    const edition = /\[[^\]]*\]/.test(title);
    if (norm(title.replace(/\[[^\]]*\]/g, '')) !== want) continue;
    const region = m[3].replace(/<[^>]+>/g, ' ').replace(/\d{4}-\d{2}-\d{2}/, '').replace(/\s+/g, ' ').trim();
    hits.push({ id: m[1], title, region, rank: tgdbRank(region, std) + (edition && !g.edition ? 3 : 0) });
  }
  if (!hits.length) return null;
  const best = hits.sort((a, b) => a.rank - b.rank)[0];
  return { url: tgdbBox(best.id), source: 'thegamesdb.net', ref: `${best.title} · ${best.region || 'no region'} · TheGamesDB #${best.id}`, offRegion: best.rank >= 2 };
}

async function findWiki(g) {
  const page = g.coverMatch || g.title;
  for (const find of [findSimsWiki, findWikipedia]) {
    try {
      const hit = await find(page, g);
      if (hit) return hit;
    } catch (e) { /* that wiki can't be reached: try the next */ }
  }
  return null;
}

/* ---------- download, resize, colours ---------- */

async function download(url) {
  for (let i = 0; i < 4; i++) {
    const r = await fetch(url, { headers: { 'User-Agent': UA } });
    if (r.ok) return Buffer.from(await r.arrayBuffer());
    if (r.status === 404) throw new Error(`404 ${url}`);
    await new Promise(res => setTimeout(res, 2000 * 2 ** i));
  }
  throw new Error(`failed ${url}`);
}

async function save(buf, g) {
  const rel = `covers/${g.console}/${g.id.slice(g.console.length + 1)}.jpg`;
  const abs = path.join(ROOT, rel);
  fs.mkdirSync(path.dirname(abs), { recursive: true });
  const img = sharp(buf).flatten({ background: '#ffffff' }).resize({ height: HEIGHT, withoutEnlargement: true });
  await img.jpeg({ quality: 80, mozjpeg: true }).toFile(abs);
  const meta = await sharp(abs).metadata();
  return { rel, ratio: +(meta.width / meta.height).toFixed(4), colors: await palette(buf) };
}

// A cover added by hand keeps its image. It only gets the shape and colours the case needs.
async function measure(g) {
  const abs = path.join(ROOT, g.cover.file || '');
  if ((g.cover.ratio && g.colors) || !g.cover.file || !fs.existsSync(abs)) return;
  const meta = await sharp(abs).metadata();
  g.cover.ratio = +(meta.width / meta.height).toFixed(4);
  g.colors = await palette(fs.readFileSync(abs));
}

/* ---------- main ---------- */

const PLAIN_ART = ['ps5', 'switch2'];
const db = JSON.parse(fs.readFileSync(DATA, 'utf8'));
const report = { found: [], offRegion: [], missing: [], skipped: 0 };

for (const g of db.games) {
  if (g.cover && g.cover.source === 'manual') { await measure(g); report.skipped++; continue; }
  // a Steam copy keeps its Steam library art (tools/steam-sync.mjs) unless it is asked for by name
  if (g.cover && g.cover.source === 'steam' && ONLY !== g.id) { report.skipped++; continue; }
  const had = g.cover && g.cover.file ? g.cover : null;
  const sys = g.caseStyle || g.console;
  const upgrade = UPGRADE_WIKI && had && had.source === 'en.wikipedia.org' && WIKIPEDIA_ONLY.includes(sys);
  if (ONLY ? g.id !== ONLY : (had && !RECHECK && !upgrade)) { report.skipped++; continue; }
  let hit = null, url = null, source = null;
  if (sys === 'ps2' && /^S[CL][EUP][SMD]-\d{5}$/.test(g.coverMatch || '')) {
    hit = { ref: g.coverMatch };
    source = 'xlenore/ps2-covers';
    url = `https://raw.githubusercontent.com/xlenore/ps2-covers/HEAD/covers/default/${g.coverMatch}.jpg`;
  }
  if (!hit && sys === 'ps3') {
    hit = findPs3(g);
    if (hit) { source = 'aldostools/resources'; url = `https://raw.githubusercontent.com/aldostools/resources/HEAD/COV/${encodeURIComponent(hit.file)}`; }
  }
  if (!hit && sys === 'pc') {
    hit = await findWiki(g);
    if (hit) ({ source, url } = hit);
  }
  if (!hit) {
    hit = findLibretro(g, sys === 'pc' ? 'dos' : sys);
    if (hit) { source = 'libretro-thumbnails'; url = `https://raw.githubusercontent.com/libretro-thumbnails/${hit.repo}/HEAD/Named_Boxarts/${encodeURIComponent(hit.file)}`; }
  }
  if (!hit && (WIKIPEDIA_ONLY.includes(sys) || sys === 'pc')) {
    try { hit = await findTgdb(g, sys); } catch (e) { /* TheGamesDB can't be reached: try Wikipedia */ }
    if (hit) ({ source, url } = hit);
  }
  if (!hit && upgrade) { report.skipped++; continue; }
  if (!hit && WIKIPEDIA_ONLY.includes(sys)) {
    try { hit = await findConsoleWiki(g); } catch (e) { /* Wikipedia can't be reached */ }
    if (hit) ({ source, url } = hit);
  }
  if (!hit) { if (had) report.skipped++; else report.missing.push(`${g.id}  (${g.title})`); continue; }
  // a scan already there stays unless the new one suits the copy's region better
  if (had && !ONLY && !upgrade && (hit.ref === had.ref || (had.source === source && source === 'libretro-thumbnails' && regionRank(parseLibretro(had.ref).tags, g.region) <= regionRank(parseLibretro(hit.ref).tags, g.region)))) { report.skipped++; continue; }
  (hit.offRegion ? report.offRegion : report.found).push(`${g.id}  ←  ${hit.ref}${had ? `  (was ${had.ref})` : ''}`);
  if (DRY) continue;
  try {
    const buf = await download(url);
    // a box is taller than it is wide (a DS or 3DS case a little wider than tall, a SNES box wide, 180 by 128 mm):
    // a wider picture is a photo of the box, or of something else
    const meta = await sharp(buf).metadata();
    if (meta.width / meta.height > ({ ds: 1.2, '3ds': 1.2, snes: 1.6 }[g.console] || 0.95)) { report.missing.push(`${g.id}  (only a wide picture: ${hit.ref})`); continue; }
    const out = await save(buf, g);
    // a sticker cut from a photo of the copy itself stays with the new scan
    g.cover = { file: out.rel, ratio: out.ratio, source, ref: hit.ref, ...(had && had.label && { label: had.label }) };
    // Wikipedia's PS5 and Switch 2 pictures are the game's key art, without the console's band: the site prints the band over it
    if (source === 'en.wikipedia.org' && PLAIN_ART.includes(sys)) g.cover.plain = true;
    g.colors = out.colors;
    process.stdout.write('.');
  } catch (e) {
    report.missing.push(`${g.id}  (download failed: ${e.message})`);
  }
}

if (!DRY) fs.writeFileSync(DATA, JSON.stringify(db, null, 2) + '\n');
console.log(`\n\nFound ${report.found.length + report.offRegion.length}, missing ${report.missing.length}, skipped ${report.skipped}`);
if (report.offRegion.length) console.log('\nCover from another region:\n  ' + report.offRegion.join('\n  '));
if (report.missing.length) console.log('\nNo cover found:\n  ' + report.missing.join('\n  '));
if (args.includes('--verbose')) console.log('\nMatched:\n  ' + report.found.join('\n  '));
