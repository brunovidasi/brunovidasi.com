// Brings what Xbox Live knows about the Xbox games into the site.
//
//   cd tools && node xbox-sync.mjs                 (the export in api/xbox-exporter/exports)
//   node xbox-sync.mjs path/to/exports-folder      (another export)
//   node xbox-sync.mjs --dry                       (report what would change, write nothing)
//
// Two files come out of it:
//
//   data/xbox.json — by Xbox title id: every game played on the Xbox profile (Xbox 360, Xbox One,
//     Series X|S and Xbox on PC), with when it was last played, the minutes played Xbox keeps for it
//     (Xbox One, Series and PC games only: Xbox 360 ones have none), its achievements (how many, how
//     many unlocked, the gamerscore, and the ones unlocked, with when) and whether it came with Game
//     Pass; and the profile's gamerscore. The sync writes it whole every time, so don't edit it by hand.
//
//   data/games.json — a copy is tied to Xbox by "xboxTitleId". An Xbox game is tied to the one copy on
//     a console it was played on (an Xbox 360 game to the x360 copy, even when it was played on an
//     Xbox One; an Xbox One or Series game to the xone copy; a game played in the Xbox app to the pc
//     copy) with the same name (or listedAs), or a name that starts with it: "Guitar Hero III" is
//     "Guitar Hero III: Legends of Rock". Xbox shortens some names: "GTA V", "Bully Scholarship Ed.".
//     When there are none or several, it is only reported: tie it by hand. A copy with more than one
//     game in it takes a list: "xboxTitleId": ["…", …]. No copy is ever added: a game Xbox lists may
//     have been played on Game Pass or for free, and it isn't on the shelf until it's added by hand.
//     The Windows games the Xbox Game Bar noticed running (the EA Sims games) say nothing but when
//     they were last played, so they stay out. Nothing else in a copy changes.
//
// The export comes from the PHP exporter (api/xbox-exporter). When the site talks to Xbox Live itself,
// have it hand fromExport() the same responses, or write another reader that returns what fromExport() returns.

import fs from 'node:fs';
import path from 'node:path';

const ROOT = path.resolve(path.dirname(new URL(import.meta.url).pathname), '..');
const DATA = path.join(ROOT, 'data/games.json');
const OUT = path.join(ROOT, 'data/xbox.json');
const args = process.argv.slice(2);
const DRY = args.includes('--dry');
const DIR = args.find(a => !a.startsWith('--')) || path.join(ROOT, 'api/xbox-exporter/exports');

/* ---------- reading an export ---------- */

const read = f => fs.existsSync(path.join(DIR, f)) ? JSON.parse(fs.readFileSync(path.join(DIR, f), 'utf8')).data : null;
const clean = s => (s || '').replace(/[™®©]/g, '').replace(/\s+/g, ' ').trim();
// the consoles a title was played on, as the shelf names them; a 360 game played on an Xbox One is still a 360 game
function consolesOf(devices = []) {
  if (devices.includes('Xbox360')) return ['x360'];
  const cs = [];
  if (devices.includes('XboxOne') || devices.includes('XboxSeries')) cs.push('xone');
  if (devices.includes('PC')) cs.push('pc');
  return cs;
}
const NEVER = '0001-01-01';
const when = t => t && !t.startsWith(NEVER) ? new Date(t).toISOString() : null;

// a title's achievements, from the newer service (contract 2) or the Xbox 360 one (contract 1)
function achievementsOf(entry) {
  if (!entry) return null;
  const list = entry.contract === 2
    ? entry.achievements.filter(a => !a.isRevoked).map(a => ({
        name: a.name,
        description: a.progressState === 'Achieved' || !a.isSecret ? a.description || a.lockedDescription : null,
        gamerscore: +(a.rewards || []).find(r => r.type === 'Gamerscore')?.value || 0,
        unlocked: a.progressState === 'Achieved' ? when(a.progression?.timeUnlocked) || true : null,
        rarity: a.rarity?.currentPercentage ?? null,
        icon: (a.mediaAssets || []).find(m => m.type === 'Icon')?.url || null,
      }))
    : (() => {
        const got = new Map((entry.earned || []).map(a => [a.id, a]));
        return entry.achievements.filter(a => !a.isRevoked).map(a => {
          const e = got.get(a.id);
          return {
            name: a.name,
            description: e || !a.isSecret ? a.description || a.lockedDescription : null,
            gamerscore: a.gamerscore || 0,
            unlocked: e ? when(e.timeUnlocked) || true : null,
            rarity: null,
            icon: null,
          };
        });
      })();
  return list;
}

/* The export, as the site keeps it. The profile's ids and gamertag stay out: the site is public. */
export function fromExport({ history, minutes = {}, achievements = {}, profile = null, exportedAt = null }) {
  const titles = {};
  for (const t of history?.titles || []) {
    if ((t.type || 'Game') !== 'Game') continue;
    const id = String(t.titleId);
    const a = t.achievement || {};
    const list = achievementsOf(achievements[id]);
    const unlocked = (list || []).filter(x => x.unlocked);
    titles[id] = {
      id,
      name: clean(t.name),
      consoles: consolesOf(t.devices),
      devices: t.devices || [],
      // minutes, when Xbox keeps them for the game
      playtime: minutes[id] ?? null,
      lastPlayed: when(t.titleHistory?.lastTimePlayed),
      gamePass: !!t.gamePass?.isGamePass,
      image: t.displayImage?.replace(/^http:/, 'https:') || null,
      achievements: list?.length || a.totalAchievements ? {
        // the title history sometimes says 0 achievements for a game that has them: the list knows
        total: list?.length || a.totalAchievements || 0,
        earned: list ? unlocked.length : a.currentAchievements || 0,
        gamerscore: a.currentGamerscore || 0,
        gamerscoreTotal: a.totalGamerscore || (list || []).reduce((s, x) => s + x.gamerscore, 0),
        // the ones unlocked, newest first
        unlocked: unlocked
          .map(({ name, description, gamerscore, unlocked, rarity, icon }) => ({ name, description, gamerscore, date: unlocked === true ? null : unlocked, rarity, icon }))
          .sort((x, y) => (y.date || '').localeCompare(x.date || '')),
      } : null,
    };
  }
  const settings = Object.fromEntries((profile?.profileUsers?.[0]?.settings || []).map(s => [s.id, s.value]));
  return {
    synced: exportedAt,
    source: 'api/xbox-exporter',
    gamerscore: settings.Gamerscore != null ? +settings.Gamerscore : Object.values(titles).reduce((s, t) => s + (t.achievements?.gamerscore || 0), 0),
    titles,
  };
}

/* ---------- the shelf ---------- */

const norm = s => clean(s).normalize('NFKD').replace(/[̀-ͯ]/g, '').toLowerCase()
  .replace(/\bgta\b/g, 'grand theft auto').replace(/\bed\./g, 'edition')
  .replace(/['’`]/g, '').replace(/&/g, ' and ').replace(/[^a-z0-9]+/g, ' ').trim();

// the copies an Xbox game could be: on a console it was played on, not tied yet, the same name, else a name that starts with it
function candidates(db, name, consoles) {
  const free = db.games.filter(g => consoles.includes(g.console) && !g.hidden && ![g.xboxTitleId || []].flat().length);
  const n = norm(name);
  const names = g => [g.title, g.listedAs].filter(Boolean).map(norm);
  const same = free.filter(g => names(g).includes(n));
  return same.length ? same : free.filter(g => names(g).some(x => x.startsWith(n + ' ')));
}

/* ---------- main ---------- */

const historyFile = path.join(DIR, 'title-history.json');
if (!fs.existsSync(historyFile)) throw new Error(`no export in ${path.relative(ROOT, DIR)}: run php export.php in api/xbox-exporter first`);
const xbox = fromExport({
  history: read('title-history.json'),
  minutes: read('minutes-played.json') || {},
  achievements: read('achievements.json') || {},
  profile: read('profile.json'),
  exportedAt: JSON.parse(fs.readFileSync(historyFile, 'utf8')).exported_at_utc || fs.statSync(historyFile).mtime.toISOString(),
});
const db = JSON.parse(fs.readFileSync(DATA, 'utf8'));
console.log(`${path.relative(ROOT, DIR)}: ${Object.keys(xbox.titles).length} games played, gamerscore ${xbox.gamerscore}, exported ${xbox.synced}`);

const report = { linked: [], ambiguous: [], 'not on the shelf': [], 'left out (Windows games the Game Bar noticed)': [], 'not in the export any more': [] };
const tied = new Set(db.games.flatMap(g => [g.xboxTitleId || []].flat()));
for (const t of Object.values(xbox.titles)) {
  if (tied.has(t.id)) continue;
  if (!t.consoles.length) { report['left out (Windows games the Game Bar noticed)'].push(`${t.id} ${t.name}`); continue; }
  const cs = candidates(db, t.name, t.consoles);
  const how = `${t.id} ${t.name} (${t.consoles.join('/')}${t.gamePass ? ', Game Pass' : ''})`;
  if (cs.length === 1) {
    cs[0].xboxTitleId = t.id;
    tied.add(t.id);
    report.linked.push(`${cs[0].id} ← ${how}`);
  } else if (cs.length) report.ambiguous.push(`${how}: ${cs.map(g => g.id).join(', ')}`);
  else report['not on the shelf'].push(how);
}
for (const g of db.games) for (const id of [g.xboxTitleId || []].flat()) if (!xbox.titles[id]) report['not in the export any more'].push(`${g.id} (${id})`);

for (const [k, v] of Object.entries(report)) if (v.length) console.log(`${k}:\n  ${v.join('\n  ')}`);
if (!DRY) {
  if (report.linked.length) db.updated = new Date().toISOString().slice(0, 10);
  fs.writeFileSync(DATA, JSON.stringify(db, null, 2) + '\n');
  fs.writeFileSync(OUT, JSON.stringify(xbox, null, 2) + '\n');
  console.log(`wrote ${path.relative(ROOT, OUT)} and ${path.relative(ROOT, DATA)}`);
}
