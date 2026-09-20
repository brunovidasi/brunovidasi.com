/* The shelf: floor, grid and list views of the whole collection.
 *
 * The records are drawn by js/tiles.js, which the artist pages share; what
 * lives here is only this page's own business — the three views, the format
 * chips, the sort, and the search across everything at once.
 *
 * The data comes from api/collection on this site (see js/common.js), not from
 * Discogs, so it carries Bruno's own fields, his chosen covers, and nothing he
 * has hidden.
 */

const CACHE_KEY = 'vinyl_collection_v4';
const OLD_CACHE_KEYS = ['vinyl_collection_cache_v3', 'vinyl_collection_cache_v2'];
const PREFS_KEY = 'vinyl_prefs_v1';
const CACHE_TTL = 1000 * 60 * 60 * 6;
const VIEWS = ['floor', 'grid', 'list'];

let items = [];
const prefs = loadPrefs();

/* ---------- Preferences (view, messiness, format filter) ---------- */

function loadPrefs() {
  const defaults = { view: 'floor', mess: 0.7, fmt: 'all' };
  try {
    const p = { ...defaults, ...JSON.parse(localStorage.getItem(PREFS_KEY) || '{}') };
    if (!VIEWS.includes(p.view)) p.view = defaults.view;
    return p;
  } catch (e) { return defaults; }
}

function savePrefs() {
  try { localStorage.setItem(PREFS_KEY, JSON.stringify(prefs)); } catch (e) { /* storage unavailable — ignore */ }
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
      <p class="hint">If nothing has been synced from Discogs yet, the shelf is genuinely empty — run a sync from the admin.</p>
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

function renderList(list) {
  $('content').replaceChildren(listEl(list));
}

function render() {
  if (!items.length) return;
  VIEWS.forEach(v => document.body.classList.toggle('view-' + v, prefs.view === v));
  $('messWrap').hidden = prefs.view !== 'floor';

  const list = visibleItems();
  const query = $('search').value.trim();
  $('countMeta').textContent = list.length === items.length ? `${items.length} items` : `${list.length} of ${items.length} items`;

  if (!list.length) { renderEmpty(query); return; }
  if (prefs.view === 'list') renderList(list);
  else if (prefs.view === 'grid') $('content').replaceChildren(gridEl(list));
  else $('content').replaceChildren(floorEl(list, prefs.mess));
}

// The grid captions every sleeve, so the floating label would only repeat them.
wireTiles($('content'), () => prefs.view !== 'grid');
wireDrawer();

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
  clearCache(CACHE_KEY);
  init(true);
});

function setItems(list) {
  items = prepareItems(list);
  renderChips();
  if (!items.length) $('content').innerHTML = '<div class="state"><h2>The shelf is empty</h2><p>Nothing has been synced from Discogs yet.</p></div>';
}

async function init(force) {
  renderLoading();
  $('countMeta').textContent = '';
  OLD_CACHE_KEYS.forEach(clearCache);

  if (!force) {
    const cached = loadCache(CACHE_KEY, CACHE_TTL);
    if (cached && cached.length) {
      setItems(cached);
      render();
      return;
    }
  }

  try {
    const data = await fetchJSON('collection');
    saveCache(CACHE_KEY, data.items);
    setItems(data.items);
    render();
  } catch (err) {
    renderError(err.message);
  }
}

$('mess').value = prefs.mess;
syncViewToggle();
init(false);
