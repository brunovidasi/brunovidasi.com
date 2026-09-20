const USERNAME = 'brunovidasi';
const CACHE_KEY = 'gaga_collection_cache_v1';
const CACHE_TTL = 1000 * 60 * 60 * 12; // 12 hours

// Releases are placed in an era by their Discogs master id, so every pressing of
// an album (CD, reissue, colored vinyl…) lands together whatever year it came out.
// `masters` is also the display order within the era (album first, then singles);
// `releases` lists master-less releases by id and sorts after the masters.
const ERAS = [
  {
    id: 'the-fame', name: 'The Fame', years: '2008–2009', tagline: 'Just Dance · Poker Face · Paparazzi',
    masters: [11126, 77385, 77387, 77389, 104888, 146544, 77394, 397264],
    releases: [1482291, 1904079],
  },
  {
    id: 'the-fame-monster', name: 'The Fame Monster', years: '2009–2010', tagline: 'Bad Romance · Telephone · Alejandro',
    masters: [201057, 200007, 234339, 256023, 245419],
    releases: [2651622],
  },
  {
    id: 'born-this-way', name: 'Born This Way', years: '2011', tagline: 'Born This Way · Judas · The Edge of Glory',
    masters: [338175, 316302, 334051, 342489, 371513, 388411, 387281, 815330],
    releases: [3061184],
  },
  {
    id: 'artpop', name: 'ARTPOP', years: '2013–2014', tagline: 'Applause · Do What U Want · G.U.Y.',
    masters: [616094, 585303],
    releases: [],
  },
  {
    id: 'cheek-to-cheek', name: 'Cheek to Cheek', years: '2014', tagline: 'With Tony Bennett',
    masters: [735298, 819592],
    releases: [],
  },
  {
    id: 'joanne', name: 'Joanne', years: '2016–2017', tagline: 'Perfect Illusion · Million Reasons · The Cure',
    masters: [1077237, 1167109],
    releases: [],
  },
  {
    id: 'a-star-is-born', name: 'A Star Is Born', years: '2018', tagline: 'Shallow · Always Remember Us This Way',
    masters: [1433566],
    releases: [],
  },
  {
    id: 'chromatica', name: 'Chromatica', years: '2020–2022', tagline: 'Stupid Love · Rain On Me · 911',
    masters: [1746431, 1691089, 1742623, 2287330],
    releases: [],
  },
  {
    id: 'love-for-sale', name: 'Love For Sale', years: '2021', tagline: 'With Tony Bennett',
    masters: [2321728],
    releases: [],
  },
  {
    id: 'top-gun-wednesday', name: 'Top Gun & Wednesday', years: '2022–2023', tagline: 'Hold My Hand · Bloody Mary',
    masters: [2623907, 2650322, 3041759],
    releases: [],
  },
  {
    id: 'harlequin', name: 'Harlequin', years: '2024', tagline: 'Harlequin · Joker: Folie à Deux',
    masters: [3609260, 3615808],
    releases: [],
  },
  {
    id: 'mayhem', name: 'Mayhem', years: '2025', tagline: 'Abracadabra · Die With A Smile',
    masters: [3772997, 3572399],
    releases: [],
  },
];

// Anything not mapped above still shows up, at the end, so new purchases are never hidden.
const OTHER_ERA = { id: 'other', name: 'More Gaga', years: '', tagline: 'Not sorted into an era yet' };

const MASTER_ERA = new Map();
const RELEASE_ERA = new Map();
ERAS.forEach(era => {
  era.masters.forEach((master, rank) => MASTER_ERA.set(master, { era, rank }));
  era.releases.forEach(id => RELEASE_ERA.set(id, era));
});

// Format descriptions that add nothing on a card.
const NOISE = new Set(['Stereo', 'NTSC']);

let items = [];
let formatFilter = 'all';

const $ = id => document.getElementById(id);

function esc(value) {
  return String(value ?? '').replace(/[&<>"']/g, c => (
    { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
  ));
}

function cleanArtistName(name) {
  return (name || '').replace(/\s\(\d+\)$/, '');
}

function formatArtists(basic) {
  return (basic.artists || []).map(a => cleanArtistName(a.name)).join(', ') || 'Unknown artist';
}

// Only allow http(s) links from API data.
function safeUrl(url) {
  return /^https?:\/\//i.test(url || '') ? url : '';
}

function isGaga(release) {
  return (release.basic_information.artists || []).some(a => cleanArtistName(a.name) === 'Lady Gaga');
}

// CD-Rs (mostly promos) count as CDs. DVD-only and USB-stick releases return null and are dropped.
function physicalKind(formats) {
  const names = (formats || []).map(f => f.name);
  if (names.includes('Vinyl')) return 'vinyl';
  if (names.includes('CD') || names.includes('CDr')) return 'cd';
  return null;
}

// "LP · Limited Edition · Picture Disc", "Album · Box Set · + DVD", "Blue [Opaque Blue], Alternate Cover"…
function describeFormats(formats) {
  const parts = [];
  const add = v => { if (v && !parts.includes(v)) parts.push(v); };
  (formats || []).forEach(f => {
    if (f.name === 'DVD') { add('+ DVD'); return; }
    if (f.name === 'CDr') add('CD-R');
    if (f.name === 'Box Set') add('Box Set');
    (f.descriptions || []).forEach(d => { if (!NOISE.has(d)) add(d); });
    add((f.text || '').trim());
  });
  return parts.join(' · ');
}

function toItem(release) {
  const basic = release.basic_information;
  const placed = RELEASE_ERA.has(basic.id)
    ? { era: RELEASE_ERA.get(basic.id), rank: 1000 }
    : MASTER_ERA.get(basic.master_id) || { era: OTHER_ERA, rank: 1000 };
  const artists = formatArtists(basic);
  const variant = describeFormats(basic.formats);
  return {
    id: basic.id,
    kind: physicalKind(basic.formats),
    era: placed.era,
    rank: placed.rank,
    title: basic.title,
    artists,
    year: basic.year || 0,
    variant,
    cover: safeUrl(basic.cover_image) || safeUrl(basic.thumb),
    search: `${basic.title} ${artists} ${variant}`.toLowerCase(),
  };
}

function compareItems(a, b) {
  return a.rank - b.rank
    || (a.year || 9999) - (b.year || 9999)
    || a.title.localeCompare(b.title)
    || a.variant.localeCompare(b.variant)
    || a.id - b.id;
}

function assertOk(res) {
  if (res.status === 429) {
    throw new Error('Discogs is rate-limiting requests right now. Wait a minute and try again.');
  }
  if (!res.ok) {
    throw new Error(`Discogs responded with ${res.status}`);
  }
}

// Discogs can't filter a collection by artist, so page through all of it and keep
// only Lady Gaga's CDs and vinyl — trimmed to what the page uses, to keep the cache small.
async function fetchGagaReleases(onProgress) {
  const perPage = 100;
  let page = 1;
  let totalPages = 1;
  const kept = [];

  do {
    const res = await fetch(`https://api.discogs.com/users/${USERNAME}/collection/folders/0/releases?page=${page}&per_page=${perPage}`);
    assertOk(res);
    const data = await res.json();
    (data.releases || [])
      .filter(r => isGaga(r) && physicalKind(r.basic_information.formats))
      .forEach(r => {
        const b = r.basic_information;
        kept.push({
          basic_information: {
            id: b.id, master_id: b.master_id, title: b.title, year: b.year,
            artists: b.artists, formats: b.formats, cover_image: b.cover_image, thumb: b.thumb,
          },
        });
      });
    totalPages = data.pagination ? data.pagination.pages : 1;
    if (onProgress) onProgress(page, totalPages);
    page++;
  } while (page <= totalPages);

  return kept;
}

function loadCache() {
  try {
    const raw = localStorage.getItem(CACHE_KEY);
    if (!raw) return null;
    const parsed = JSON.parse(raw);
    if (Date.now() - parsed.timestamp > CACHE_TTL) return null;
    return parsed.data;
  } catch (e) { return null; }
}

function saveCache(data) {
  try {
    localStorage.setItem(CACHE_KEY, JSON.stringify({ timestamp: Date.now(), data }));
  } catch (e) { /* storage full or unavailable — ignore */ }
}

function clearCache() {
  try { localStorage.removeItem(CACHE_KEY); } catch (e) { /* ignore */ }
}

function coverFallbackSVG() {
  return `<div class="cover-fallback"><svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
    <circle cx="50" cy="50" r="48" fill="#17140F"/>
    <circle cx="50" cy="50" r="16" fill="#C99A2E"/>
    <circle cx="50" cy="50" r="3" fill="#17140F"/>
  </svg></div>`;
}

function renderSkeleton() {
  $('eraNav').hidden = true;
  $('content').innerHTML = `<div class="skeleton-grid">${'<div class="skeleton"></div>'.repeat(12)}</div>`;
}

function renderError(message) {
  $('eraNav').hidden = true;
  $('content').innerHTML = `
    <div class="state">
      <h2>The collection didn't load</h2>
      <p>${esc(message)}</p>
      <p class="hint">If you're opening this file directly from disk, some browsers block the request — try serving it from a local server or hosting it online instead.</p>
      <button class="ghost" id="retry">Try again</button>
    </div>`;
  $('retry').addEventListener('click', () => init(true));
}

function renderEmpty(query) {
  $('eraNav').hidden = true;
  $('content').innerHTML = `
    <div class="state">
      <h2>${query ? `No records match "${esc(query)}"` : 'No records here'}</h2>
      <p>${query ? 'Try a different title or edition.' : 'Try a different format.'}</p>
    </div>`;
}

function cardHtml(item) {
  const cover = item.cover
    ? `<img src="${esc(item.cover)}" alt="" loading="lazy">`
    : coverFallbackSVG();
  return `
    <a class="card" href="https://www.discogs.com/release/${esc(item.id)}" target="_blank" rel="noopener">
      <div class="cover-wrap">${cover}</div>
      <div class="rule"></div>
      <h3 title="${esc(item.title)}">${esc(item.title)}</h3>
      <div class="artist" title="${esc(item.artists)}">${esc(item.artists)}</div>
      <div class="year">${esc(item.year || '—')}</div>
      <div class="variant" title="${esc(item.variant)}">${esc(item.variant)}</div>
    </a>`;
}

function groupHtml(label, group) {
  return group.length
    ? `<div class="group-label">${label} · ${group.length}</div><div class="grid">${group.map(cardHtml).join('')}</div>`
    : '';
}

function eraHtml(era, index, eraItems) {
  const vinyl = eraItems.filter(i => i.kind === 'vinyl');
  const cds = eraItems.filter(i => i.kind === 'cd');
  const sub = [era.years, era.tagline].filter(Boolean).join(' · ');
  return `
    <section class="era" id="era-${esc(era.id)}">
      <div class="era-head">
        <span class="era-num">${String(index + 1).padStart(2, '0')}</span>
        <div class="era-title">
          <h2>${esc(era.name)}</h2>
          <div class="era-sub">${esc(sub)}</div>
        </div>
        <div class="era-counts">${eraItems.length} ${eraItems.length === 1 ? 'record' : 'records'}</div>
      </div>
      ${groupHtml('Vinyl', vinyl)}
      ${groupHtml('CD', cds)}
    </section>`;
}

function applyFiltersAndRender() {
  const query = $('search').value.trim().toLowerCase();

  const visible = items
    .filter(i => (formatFilter === 'all' || i.kind === formatFilter) && (!query || i.search.includes(query)))
    .sort(compareItems);

  const vinylCount = visible.filter(i => i.kind === 'vinyl').length;
  const cdCount = visible.length - vinylCount;
  const filtered = query || formatFilter !== 'all';
  $('countMeta').textContent =
    `${filtered ? `${visible.length} of ${items.length}` : items.length} records · ${vinylCount} vinyl · ${cdCount} CD`;

  if (!visible.length) { renderEmpty(query); return; }

  // Numbering follows the full era list so an era keeps its number while filtering.
  const eras = [...ERAS, OTHER_ERA];
  const sections = eras
    .map((era, index) => ({ era, index, eraItems: visible.filter(i => i.era === era) }))
    .filter(s => s.eraItems.length);

  const nav = $('eraNav');
  nav.innerHTML = `<div class="era-nav-inner">${sections.map(s =>
    `<a href="#era-${esc(s.era.id)}">${esc(s.era.name)}<span class="n">${s.eraItems.length}</span></a>`
  ).join('')}</div>`;
  nav.hidden = false;

  $('content').innerHTML = sections.map(s => eraHtml(s.era, s.index, s.eraItems)).join('');
}

// Cover URLs can fail (expired or blocked); swap in the record fallback like the main collection page.
$('content').addEventListener('error', e => {
  if (e.target.tagName !== 'IMG') return;
  const wrap = e.target.closest('.cover-wrap');
  if (wrap) wrap.innerHTML = coverFallbackSVG();
}, true);

$('search').addEventListener('input', applyFiltersAndRender);

$('formatFilter').addEventListener('click', e => {
  const button = e.target.closest('[data-format]');
  if (!button) return;
  formatFilter = button.dataset.format;
  $('formatFilter').querySelectorAll('[data-format]').forEach(b => {
    b.setAttribute('aria-pressed', String(b === button));
  });
  applyFiltersAndRender();
});

$('refresh').addEventListener('click', () => {
  clearCache();
  init(true);
});

function setReleases(releases) {
  items = releases.map(toItem);
}

async function init(forceFetch) {
  renderSkeleton();
  $('countMeta').textContent = '';

  if (!forceFetch) {
    const cached = loadCache();
    if (cached && cached.length) {
      setReleases(cached);
      applyFiltersAndRender();
      return;
    }
  }

  try {
    const releases = await fetchGagaReleases((page, total) => {
      $('countMeta').textContent = `Loading page ${page} of ${total}…`;
    });
    saveCache(releases);
    setReleases(releases);
    applyFiltersAndRender();
  } catch (err) {
    renderError(err.message);
  }
}

init(false);
