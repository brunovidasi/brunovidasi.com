const USERNAME = 'brunovidasi';
const CACHE_KEY = 'vinyl_collection_cache_v3';
const OLD_CACHE_KEYS = ['vinyl_collection_cache_v2']; // vinyl-only caches from before CDs/DVDs were shown
const PREFS_KEY = 'vinyl_prefs_v1';
const CACHE_TTL = 1000 * 60 * 60 * 12; // 12 hours

let releases = [];   // raw (slimmed) Discogs collection entries
let items = [];      // one view-model per entry, built from `releases`
let currentRelease = null;
const releaseCache = new Map();
const prefs = loadPrefs();

const $ = id => document.getElementById(id);

function esc(value) {
  return String(value ?? '').replace(/[&<>"']/g, c => (
    { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
  ));
}

function hash(s) {
  let h = 0;
  for (const c of String(s)) h = (h * 31 + c.charCodeAt(0)) >>> 0;
  return h;
}

function cleanArtistName(name) {
  return (name || '').replace(/\s\(\d+\)$/, '');
}

function formatArtists(basic) {
  if (!basic.artists || !basic.artists.length) return 'Unknown artist';
  return basic.artists.map(a => cleanArtistName(a.name)).join(', ');
}

function formatLabel(l) {
  return l.catno && l.catno !== 'none' ? `${l.name} · ${l.catno}` : l.name;
}

function formatFormat(f) {
  const qty = Number(f.qty) > 1 ? `${f.qty} × ` : '';
  const extras = [f.text, ...(f.descriptions || [])].filter(Boolean).join(', ');
  return extras ? `${qty}${f.name} (${extras})` : `${qty}${f.name}`;
}

function formatDate(iso) {
  return iso ? new Date(iso).toLocaleDateString() : '';
}

function formatPrice(amount) {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount);
}

function formatSeconds(total) {
  const s = Number(total) || 0;
  return `${Math.floor(s / 60)}:${String(s % 60).padStart(2, '0')}`;
}

// Only allow http(s) links from API data.
function safeUrl(url) {
  return /^https?:\/\//i.test(url || '') ? url : '';
}

// Release notes use Discogs markup ([a=Artist], [url=...]text[/url], [b]…); flatten it to text.
function cleanNotes(text) {
  return String(text || '')
    .replace(/\[url=[^\]]*\]([\s\S]*?)\[\/url\]/g, '$1')
    .replace(/\[[almr]=([^\]]+)\]/g, '$1')
    .replace(/\[\/?[a-z]\]/g, '')
    .trim();
}

// Groups credit entries ({ name, role, tracks }) as Map(role -> [names]).
function groupByRole(credits) {
  const groups = new Map();
  (credits || []).forEach(c => {
    const role = c.role || 'Credit';
    const name = cleanArtistName(c.name) + (c.tracks ? ` (${c.tracks})` : '');
    if (!groups.has(role)) groups.set(role, []);
    groups.get(role).push(name);
  });
  return groups;
}

function coverFallbackSVG() {
  return `<div class="cover-fallback"><svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
    <circle cx="50" cy="50" r="48" fill="#F2EAD8"/>
    <circle cx="50" cy="50" r="16" fill="#C99A2E"/>
    <circle cx="50" cy="50" r="3" fill="#17140F"/>
  </svg></div>`;
}

function assertOk(res) {
  if (res.status === 429) {
    throw new Error('Discogs is rate-limiting requests right now. Wait a minute and try again.');
  }
  if (!res.ok) {
    throw new Error(`Discogs responded with ${res.status}`);
  }
}

/* ---------- Preferences (view, messiness, format filter) ---------- */

function loadPrefs() {
  const defaults = { view: 'floor', mess: 0.7, fmt: 'all' };
  try { return { ...defaults, ...JSON.parse(localStorage.getItem(PREFS_KEY) || '{}') }; }
  catch (e) { return defaults; }
}

function savePrefs() {
  try { localStorage.setItem(PREFS_KEY, JSON.stringify(prefs)); } catch (e) { /* storage unavailable — ignore */ }
}

/* ---------- Data ---------- */

// Full release details (tracklist, credits, notes, …) are only on the per-release
// endpoint, and anonymous requests are capped at 25/min, so fetch on demand.
async function fetchRelease(id) {
  if (releaseCache.has(id)) return releaseCache.get(id);
  const res = await fetch(`https://api.discogs.com/releases/${id}`);
  assertOk(res);
  const data = await res.json();
  releaseCache.set(id, data);
  return data;
}

// Keep only what the page and the drawer use, so the whole collection fits in localStorage.
function slim(r) {
  const b = r.basic_information;
  return {
    id: r.id,
    instance_id: r.instance_id,
    date_added: r.date_added,
    rating: r.rating,
    basic_information: {
      id: b.id,
      title: b.title,
      year: b.year,
      artists: (b.artists || []).map(a => ({ name: a.name })),
      labels: (b.labels || []).map(l => ({ name: l.name, catno: l.catno })),
      formats: b.formats,
      genres: b.genres,
      styles: b.styles,
      cover_image: b.cover_image,
      thumb: b.thumb,
    },
  };
}

// The collection holds vinyl, CDs, DVDs and Blu-rays; all of them are shown.
async function fetchAllReleases(onProgress) {
  const perPage = 100;
  let page = 1;
  let totalPages = 1;
  let all = [];

  do {
    const res = await fetch(`https://api.discogs.com/users/${USERNAME}/collection/folders/0/releases?page=${page}&per_page=${perPage}`);
    assertOk(res);
    const data = await res.json();
    all = all.concat((data.releases || []).map(slim));
    totalPages = data.pagination ? data.pagination.pages : 1;
    if (onProgress) onProgress(page, totalPages);
    page++;
  } while (page <= totalPages);

  return all;
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
    OLD_CACHE_KEYS.forEach(k => localStorage.removeItem(k));
    localStorage.setItem(CACHE_KEY, JSON.stringify({ timestamp: Date.now(), data }));
  } catch (e) { /* storage full or unavailable — ignore */ }
}

function clearCache() {
  releaseCache.clear();
  try { localStorage.removeItem(CACHE_KEY); } catch (e) { /* ignore */ }
}

/* ---------- View model: what physical discs does each release have? ---------- */

const KIND_LABEL = { vinyl: 'Vinyl', cd: 'CD', dvd: 'DVD', bd: 'Blu-ray' };

const VINYL_COLORS = [
  ['red', '#c8322b'], ['pink', '#f06aa8'], ['blue', '#2f6fdd'], ['aqua', '#3cc6d4'], ['turquoise', '#2bc0b4'], ['green', '#2f9e58'],
  ['yellow', '#f0c828'], ['orange', '#ee7d1e'], ['purple', '#7a3fb5'], ['violet', '#7a3fb5'], ['gold', '#d4a836'], ['silver', '#b8bcc2'],
  ['grey', '#8a8d92'], ['gray', '#8a8d92'], ['smoky', '#6c6f74'], ['white', '#f1f1ec'], ['brown', '#7a4b2a'], ['clear', '#dfe8ec'], ['black', '#131313'],
];

// Discogs describes coloured pressings as free text ("Red Translucent", "Clear w/ Powder Fill").
function vinylColor(text) {
  const t = (text || '').toLowerCase();
  for (const [word, color] of VINYL_COLORS) {
    if (t.includes(word)) return { c: color, tr: /transl|transp|clear|smoky/.test(t) };
  }
  return { c: null, tr: false };
}

function toItem(r) {
  const basic = r.basic_information;
  const formats = basic.formats || [];
  const discs = [];

  formats.forEach(f => {
    const d = f.descriptions || [];
    const n = Math.min(3, Number(f.qty) || 1);
    for (let i = 0; i < n; i++) {
      if (f.name === 'Vinyl') discs.push({ t: 'v', ...vinylColor(f.text), pic: d.includes('Picture Disc'), sz: d.includes('7"') ? 7 : d.includes('10"') ? 10 : 12 });
      else if (f.name === 'CD') discs.push({ t: 'cd' });
      else if (f.name === 'DVD') discs.push({ t: 'dvd' });
      else if (f.name === 'Blu-ray') discs.push({ t: 'bd' });
    }
  });
  if (!discs.length) discs.push({ t: 'cd' });

  const has = t => discs.some(d => d.t === t);
  const first = formats[0] || {};
  const title = (basic.title || '').trim();
  const artist = formatArtists(basic);

  return {
    raw: r,
    key: r.instance_id || r.id,
    title,
    artist,
    year: basic.year || 0,
    cover: basic.cover_image || basic.thumb || '',
    thumb: basic.thumb || basic.cover_image || '',
    added: r.date_added || '',
    discs: discs.slice(0, 3),
    box: formats.some(f => f.name === 'Box Set'),
    kind: has('v') ? 'vinyl' : has('cd') ? 'cd' : has('bd') ? 'bd' : 'dvd',
    fmt: [first.name, (first.descriptions || [])[0], (first.text || '').trim()].filter(Boolean).join(' · '),
    fmtRest: [(first.descriptions || [])[0], (first.text || '').trim()].filter(Boolean).join(' · '),
    hay: (artist + ' ' + title).toLowerCase(),
  };
}

// Sleeve size in px at full scale, following the real objects: LP 12.4", CD case 5.6", DVD case 5.3 x 7.5", Blu-ray case 5.3 x 6.7".
function dims(it) {
  if (it.kind === 'dvd') return [116, 163];
  if (it.kind === 'bd') return [116, 147];
  if (it.kind === 'cd') return [112, 112];
  const d = it.discs[0];
  const w = d.sz === 7 ? 150 : d.sz === 10 ? 200 : (it.discs.length > 1 || it.box) ? 270 : 250;
  return [w, w];
}

/* ---------- Filtering & sorting ---------- */

const SORTS = {
  'artist': (a, b) => a.artist.localeCompare(b.artist) || a.year - b.year,
  'year-desc': (a, b) => (b.year || 0) - (a.year || 0),
  'year-asc': (a, b) => (a.year || 9999) - (b.year || 9999),
  'added': (a, b) => new Date(b.added) - new Date(a.added),
};

function visibleItems() {
  const query = $('search').value.trim().toLowerCase();
  return items
    .filter(it => (prefs.fmt === 'all' || it.kind === prefs.fmt) && (!query || it.hay.includes(query)))
    .sort(SORTS[$('sort').value] || SORTS.artist);
}

function renderChips() {
  const counts = {};
  items.forEach(it => { counts[it.kind] = (counts[it.kind] || 0) + 1; });
  if (prefs.fmt !== 'all' && !counts[prefs.fmt]) prefs.fmt = 'all';
  $('formats').innerHTML = [['all', 'All'], ...Object.entries(KIND_LABEL)]
    .filter(([k]) => k === 'all' || counts[k])
    .map(([k, label]) => `<button type="button" data-k="${k}" class="${prefs.fmt === k ? 'on' : ''}">${label} <i>${k === 'all' ? items.length : counts[k]}</i></button>`)
    .join('');
}

/* ---------- Rendering ---------- */

function renderLoading() {
  $('content').innerHTML = '<div class="state"><p>Loading the collection…</p></div>';
}

function renderError(message) {
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
  $('content').innerHTML = `
    <div class="state">
      <h2>${query ? `Nothing matches "${esc(query)}"` : 'Nothing in this format'}</h2>
      <p>Try a different search or format.</p>
    </div>`;
}

function buildTile(it) {
  const [w, h] = dims(it);
  const hs = hash(String(it.key));
  const t = document.createElement('div');
  t.className = `tile kind-${it.kind}`;
  t.tabIndex = 0;
  t.setAttribute('role', 'button');
  t.setAttribute('aria-label', `${it.title} — ${it.artist}. Open details`);
  t._it = it;
  // a stable "random" rotation and nudge per record, scaled by the messiness slider
  t.style.setProperty('--w', w);
  t.style.setProperty('--h', h);
  t.style.setProperty('--r', (((hs % 2000) / 1000 - 1) * 9).toFixed(2));
  t.style.setProperty('--x', ((((hs >>> 8) % 1000) / 500 - 1) * 18).toFixed(1));
  t.style.setProperty('--y', ((((hs >>> 14) % 1000) / 500 - 1) * 22).toFixed(1));

  const hue = hs % 360;
  const src = w >= 200 ? (it.cover || it.thumb) : (it.thumb || it.cover); // big sleeves get the big image
  t.innerHTML = `<div class="sleeve" style="--fb:linear-gradient(135deg,hsl(${hue} 40% 34%),hsl(${(hue + 40) % 360} 45% 16%))">${src ? `<img loading="lazy" decoding="async" alt="" src="${esc(src)}">` : ''}</div>`;
  const img = t.querySelector('img');
  if (img) img.addEventListener('error', () => img.remove());
  return t;
}

function renderFloor(list) {
  const floor = document.createElement('div');
  floor.className = 'floor';
  floor.style.setProperty('--mess', prefs.mess);
  list.forEach(it => floor.appendChild(buildTile(it)));
  $('content').replaceChildren(floor);
}

function renderList(list) {
  const wrap = document.createElement('div');
  wrap.className = 'list';
  wrap.innerHTML = '<div class="lrow lhead"><span></span><span>Title</span><span>Artist</span><span>Year</span><span>Format</span><span>Added</span></div>';
  list.forEach(it => {
    const row = document.createElement('div');
    row.className = 'lrow';
    row.tabIndex = 0;
    row.setAttribute('role', 'button');
    row.setAttribute('aria-label', `${it.title} — ${it.artist}. Open details`);
    row._it = it;
    row.innerHTML = `
      <span class="lthumb">${it.thumb ? `<img loading="lazy" decoding="async" alt="" src="${esc(it.thumb)}">` : ''}</span>
      <span class="ltitle"><b title="${esc(it.title)}">${esc(it.title)}</b><small>${esc(it.artist)}</small></span>
      <span class="lartist" title="${esc(it.artist)}">${esc(it.artist)}</span>
      <span class="lyear">${esc(it.year || '—')}</span>
      <span class="lfmt"><i class="kc ${it.kind}">${KIND_LABEL[it.kind]}</i>${esc(it.fmtRest)}</span>
      <span class="ladded">${esc(formatDate(it.added))}</span>`;
    const img = row.querySelector('img');
    if (img) img.addEventListener('error', () => img.remove());
    wrap.appendChild(row);
  });
  $('content').replaceChildren(wrap);
}

function render() {
  if (!items.length) return;
  document.body.classList.toggle('view-list', prefs.view === 'list');
  $('messWrap').hidden = prefs.view === 'list';

  const list = visibleItems();
  const query = $('search').value.trim();
  $('countMeta').textContent = list.length === items.length ? `${items.length} items` : `${list.length} of ${items.length} items`;

  if (!list.length) { renderEmpty(query); return; }
  if (prefs.view === 'list') renderList(list); else renderFloor(list);
}

/* ---------- Hover: the disc(s) slide out of the sleeve ---------- */

function discEl(it, k) {
  const d = it.discs[k];
  const el = document.createElement('i');
  const sp = document.createElement('span');
  el.className = `d ${d.t === 'v' ? 'v' : d.t}${d.tr ? ' tr' : ''}${d.pic ? ' pic' : ''}`;
  el.style.setProperty('--k', k);
  el.style.setProperty('--dd', d.t === 'v' ? 0.96 : 0.88);
  el.style.setProperty('--reach', (1.42 + k * 0.16).toFixed(2)); // later discs peek out a little further, behind the first
  el.style.zIndex = String(3 - k);
  if (d.c) el.style.setProperty('--vc', d.c);
  // only vinyl shows the cover (label / picture disc); CDs, DVDs and Blu-rays show the plain reading side
  if (d.t === 'v' && it.cover) el.style.setProperty('--art', `url("${it.cover.replace(/"/g, '%22')}")`);
  sp.className = 'sp';
  sp.innerHTML = '<b class="lbl"></b>';
  el.appendChild(sp);
  return el;
}

function reveal(t) {
  const it = t._it;
  if (!t._discs) {
    const wrap = document.createElement('div');
    wrap.className = 'discs';
    it.discs.forEach((_, k) => wrap.appendChild(discEl(it, k)));
    t.prepend(wrap);
    t._discs = wrap;
  }
  // slide out toward whichever side has room
  const rc = t.getBoundingClientRect();
  const reach = 0.45 + 0.16 * (it.discs.length - 1);
  t.classList.toggle('left', rc.right + rc.width * reach > document.documentElement.clientWidth - 6);
  void t.offsetWidth; // flush styles so the slide-out transition runs
  t.classList.add('hot');
}

const tipEl = $('tip');

function showTip(it) {
  tipEl.innerHTML = `<b>${esc(it.title)}</b>${esc(it.artist)}${it.year ? ' · ' + it.year : ''}<br><span>${esc(it.fmt)}</span>`;
  tipEl.style.opacity = 1;
}

function hoverOn(t, e) {
  t._on = true;
  reveal(t);
  if (!e || e.pointerType !== 'touch') showTip(t._it);
}

function hoverOff(t) {
  t._on = false;
  t.classList.remove('hot');
  tipEl.style.opacity = 0;
}

const content = $('content');
content.addEventListener('pointerover', e => { const t = e.target.closest('.tile'); if (t && !t._on) hoverOn(t, e); });
content.addEventListener('pointerout', e => { const t = e.target.closest('.tile'); if (t && !t.contains(e.relatedTarget)) hoverOff(t); });
content.addEventListener('focusin', e => { const t = e.target.closest('.tile'); if (t && !t._on) hoverOn(t); });
content.addEventListener('focusout', e => { const t = e.target.closest('.tile'); if (t) hoverOff(t); });
addEventListener('pointermove', e => {
  tipEl.style.left = Math.min(e.clientX + 14, innerWidth - 270) + 'px';
  tipEl.style.top = (e.clientY + 18) + 'px';
});

// clicking (or pressing Enter/Space on) any sleeve or list row opens its drawer
content.addEventListener('click', e => {
  const el = e.target.closest('.tile, .lrow');
  if (el && el._it) openDrawer(el._it.raw);
});
content.addEventListener('keydown', e => {
  if (e.key !== 'Enter' && e.key !== ' ') return;
  const el = e.target.closest('.tile, .lrow');
  if (el && el._it) { e.preventDefault(); openDrawer(el._it.raw); }
});

/* ---------- Drawer ---------- */

function fact(label, valueHtml) {
  return valueHtml ? `<dt>${esc(label)}</dt><dd>${valueHtml}</dd>` : '';
}

function section(title, bodyHtml) {
  return bodyHtml ? `<section class="detail"><h4>${esc(title)}</h4>${bodyHtml}</section>` : '';
}

function lines(items) {
  return items.map(esc).join('<br>');
}

function creditsHtml(credits) {
  return [...groupByRole(credits)]
    .map(([role, names]) => `<div class="credit"><span class="role">${esc(role)}</span> ${esc(names.join(', '))}</div>`)
    .join('');
}

function trackHtml(t) {
  if (t.type_ === 'heading') return `<li class="track-heading">${esc(t.title)}</li>`;
  const artist = t.artists && t.artists.length ? `<div class="track-artist">${esc(formatArtists(t))}</div>` : '';
  const subs = t.sub_tracks && t.sub_tracks.length
    ? `<ol class="tracklist sub">${t.sub_tracks.map(trackHtml).join('')}</ol>`
    : '';
  return `<li class="track">
    <span class="pos">${esc(t.position)}</span>
    <div class="track-main">${esc(t.title)}${artist}<div class="track-credits">${creditsHtml(t.extraartists)}</div>${subs}</div>
    <span class="dur">${esc(t.duration)}</span>
  </li>`;
}

function companiesHtml(companies) {
  const groups = new Map();
  (companies || []).forEach(c => {
    const type = c.entity_type_name || 'Company';
    const name = c.catno ? `${c.name} (${c.catno})` : c.name;
    if (!groups.has(type)) groups.set(type, []);
    groups.get(type).push(name);
  });
  return [...groups]
    .map(([type, names]) => `<div class="credit"><span class="role">${esc(type)}</span> ${esc(names.join(', '))}</div>`)
    .join('');
}

function identifiersHtml(identifiers) {
  return (identifiers || []).map(i => `
    <div class="credit"><span class="role">${esc(i.type)}</span> ${esc(i.value)}${i.description ? ` <span class="muted">(${esc(i.description)})</span>` : ''}</div>`
  ).join('');
}

function videosHtml(videos) {
  return (videos || []).filter(v => safeUrl(v.uri)).map(v => `
    <div class="credit"><a href="${esc(safeUrl(v.uri))}" target="_blank" rel="noopener">${esc(v.title)}</a>${v.duration ? ` <span class="muted">${formatSeconds(v.duration)}</span>` : ''}</div>`
  ).join('');
}

function communityHtml(full) {
  const c = full.community;
  if (!c) return '';
  const rating = c.rating && c.rating.count
    ? `${c.rating.average} / 5 <span class="muted">(${c.rating.count} ratings)</span>`
    : '';
  const submitter = c.submitter ? esc(c.submitter.username) : '';
  return `<dl class="facts">
    ${fact('Have', c.have != null ? esc(c.have) : '')}
    ${fact('Want', c.want != null ? esc(c.want) : '')}
    ${fact('Rating', rating)}
    ${fact('For sale', full.num_for_sale != null ? esc(full.num_for_sale) : '')}
    ${fact('Lowest price', full.lowest_price != null ? esc(formatPrice(full.lowest_price)) : '')}
    ${fact('Submitted by', submitter)}
    ${fact('Contributors', c.contributors && c.contributors.length ? esc(c.contributors.length) : '')}
  </dl>`;
}

function galleryHtml(images) {
  if (!images || images.length < 2) return '';
  return `<div class="gallery">${images.map(i => `
    <button class="thumb" data-cover="${esc(i.uri)}" aria-label="Show ${esc(i.type)} image"><img src="${esc(i.uri150 || i.uri)}" alt="" loading="lazy"></button>`
  ).join('')}</div>`;
}

function linksHtml(full) {
  const links = [];
  if (safeUrl(full.uri)) links.push(`<a href="${esc(full.uri)}" target="_blank" rel="noopener">Release on Discogs</a>`);
  if (full.master_id) links.push(`<a href="https://www.discogs.com/master/${esc(full.master_id)}" target="_blank" rel="noopener">Master release</a>`);
  return links.length ? `<div class="credit">${links.join(' · ')}</div>` : '';
}

function detailsHtml(full, error) {
  if (error) {
    return `<div class="detail-state">Couldn't load the full details. ${esc(error)} <button class="ghost" data-retry>Try again</button></div>`;
  }
  if (!full) return '<div class="detail-state">Loading full details from Discogs…</div>';

  const notes = cleanNotes(full.notes);
  return [
    section('Community', communityHtml(full)),
    section('Tracklist', full.tracklist && full.tracklist.length ? `<ol class="tracklist">${full.tracklist.map(trackHtml).join('')}</ol>` : ''),
    section('Credits', creditsHtml(full.extraartists)),
    section('Companies', companiesHtml(full.companies)),
    section('Series', (full.series || []).length ? lines(full.series.map(s => s.catno ? `${s.name} · ${s.catno}` : s.name)) : ''),
    section('Identifiers', identifiersHtml(full.identifiers)),
    section('Notes', notes ? `<p class="notes">${esc(notes)}</p>` : ''),
    section('Videos', videosHtml(full.videos)),
    section('Links', linksHtml(full)),
  ].join('');
}

function renderDrawerBody(r, full, error) {
  const basic = r.basic_information;
  const src = full || basic;
  const genresStyles = [...(src.genres || []), ...(src.styles || [])];
  const labels = (src.labels || []).map(formatLabel);
  const formats = (src.formats || []).map(formatFormat);

  $('drawerBody').innerHTML = `
    <h2>${esc(basic.title)}</h2>
    <div class="artist">${esc(formatArtists(full || basic))}</div>
    ${galleryHtml(full && full.images)}
    <dl class="facts">
      ${fact('Released', esc((full && full.released_formatted) || basic.year || '—'))}
      ${fact('Country', full && esc(full.country))}
      ${fact('Label', lines(labels) || '—')}
      ${fact('Format', lines(formats) || '—')}
      ${fact('Weight', full && full.estimated_weight ? `${esc(full.estimated_weight)} g` : '')}
      ${fact('Data quality', full && esc(full.data_quality))}
      ${fact('Added', esc(formatDate(r.date_added)) || '—')}
      ${fact('Your rating', r.rating ? `${'●'.repeat(r.rating)}${'○'.repeat(5 - r.rating)}` : '')}
      ${fact('Release ID', esc(r.id))}
      ${fact('Last edited', full && esc(formatDate(full.date_changed)))}
    </dl>
    ${genresStyles.length ? `<div class="tags">${genresStyles.map(g => `<span class="tag">${esc(g)}</span>`).join('')}</div>` : ''}
    ${detailsHtml(full, error)}
  `;
}

async function loadDetails(r) {
  try {
    const full = await fetchRelease(r.id);
    if (currentRelease === r) renderDrawerBody(r, full);
  } catch (err) {
    if (currentRelease === r) renderDrawerBody(r, null, err.message);
  }
}

function openDrawer(r) {
  currentRelease = r;
  const basic = r.basic_information;
  const img = basic.cover_image || basic.thumb;

  $('drawerCover').innerHTML = img
    ? `<img src="${esc(img)}" alt="">`
    : coverFallbackSVG();

  const cached = releaseCache.get(r.id);
  renderDrawerBody(r, cached || null);
  $('drawer').scrollTop = 0;

  $('overlay').classList.add('open');
  $('drawer').classList.add('open');
  if (!cached) loadDetails(r);
}

function closeDrawer() {
  currentRelease = null;
  $('overlay').classList.remove('open');
  $('drawer').classList.remove('open');
}

$('drawerBody').addEventListener('click', e => {
  const thumb = e.target.closest('[data-cover]');
  if (thumb) {
    $('drawerCover').innerHTML = `<img src="${esc(thumb.dataset.cover)}" alt="">`;
    return;
  }
  if (e.target.closest('[data-retry]') && currentRelease) {
    renderDrawerBody(currentRelease, null);
    loadDetails(currentRelease);
  }
});

$('overlay').addEventListener('click', closeDrawer);
$('drawerClose').addEventListener('click', closeDrawer);
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDrawer(); });

/* ---------- Controls ---------- */

$('search').addEventListener('input', render);
$('sort').addEventListener('change', render);

$('formats').addEventListener('click', e => {
  const b = e.target.closest('button');
  if (!b) return;
  prefs.fmt = b.dataset.k;
  savePrefs();
  renderChips();
  render();
});

function syncViewToggle() {
  document.querySelectorAll('#viewToggle button').forEach(b => b.classList.toggle('on', b.dataset.view === prefs.view));
}

$('viewToggle').addEventListener('click', e => {
  const b = e.target.closest('button');
  if (!b) return;
  prefs.view = b.dataset.view;
  savePrefs();
  syncViewToggle();
  render();
});

$('mess').addEventListener('input', e => {
  prefs.mess = Number(e.target.value);
  const floor = document.querySelector('.floor');
  if (floor) floor.style.setProperty('--mess', prefs.mess);
  savePrefs();
});

$('refresh').addEventListener('click', () => {
  clearCache();
  init(true);
});

function setReleases(list) {
  releases = list;
  items = list.map(toItem);
  renderChips();
  if (!items.length) $('content').innerHTML = '<div class="state"><h2>The shelf is empty</h2><p>Nothing in this Discogs collection yet.</p></div>';
}

async function init(forceFetch) {
  renderLoading();
  $('countMeta').textContent = '';

  if (!forceFetch) {
    const cached = loadCache();
    if (cached && cached.length) {
      setReleases(cached);
      render();
      return;
    }
  }

  try {
    const all = await fetchAllReleases((page, total) => {
      $('countMeta').textContent = `Loading page ${page} of ${total}…`;
    });
    saveCache(all);
    setReleases(all);
    render();
  } catch (err) {
    renderError(err.message);
  }
}

$('mess').value = prefs.mess;
syncViewToggle();
init(false);
