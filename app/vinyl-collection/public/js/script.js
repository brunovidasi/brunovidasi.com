/* The shelf: floor, grid and list views of the whole collection.
 *
 * The records are drawn by js/tiles.js, which the artist pages share; what
 * lives here is only this page's own business — the three views, the format
 * chips, the sort, and the search across everything at once.
 *
 * The data comes from api/collection on this site (see js/common.js), not from
 * Discogs, so it carries Bruno's own fields, his chosen covers, and nothing he
 * has hidden.
 *
 * The floor can also be put in order: "Put albums in a crate" throws the
 * pile into a crate to flip through (js/crate.js), and "Back to the mess" tips it
 * out again. The crate is a state of the floor view, not a fourth view, so it
 * follows the same search and format filter and is left by choosing Grid or List.
 * It is a crate of records, so entering it picks the Vinyl format, and leaving
 * it goes back to All.
 */

const CACHE_KEY = 'vinyl_collection_v5';
const OLD_CACHE_KEYS = ['vinyl_collection_v4', 'vinyl_collection_cache_v3', 'vinyl_collection_cache_v2'];
const PREFS_KEY = 'vinyl_prefs_v1';
const CACHE_TTL = 1000 * 60 * 60 * 6;
const VIEWS = ['floor', 'grid', 'list'];

let items = [];
let crateOn = false;
const prefs = loadPrefs();

/* ---------- Preferences (view, messiness, format filter, sort, crate order) ---------- */

function loadPrefs() {
  const defaults = { view: 'floor', mess: 0.7, fmt: 'all', sort: DEFAULT_SORT, organise: DEFAULT_CRATE_ORDER };
  try {
    const p = { ...defaults, ...JSON.parse(localStorage.getItem(PREFS_KEY) || '{}') };
    if (!VIEWS.includes(p.view)) p.view = defaults.view;
    if (!CRATE_ORDERS[p.organise]) p.organise = defaults.organise;
    p.sort = validSort(p.sort);
    return p;
  } catch (e) { return defaults; }
}

function savePrefs() {
  try { localStorage.setItem(PREFS_KEY, JSON.stringify(prefs)); } catch (e) { /* storage unavailable — ignore */ }
}

/* ---------- Filtering & sorting ---------- */

/* The sort menu and the list's column headers are two ways to change the same
   sort (prefs.sort, see js/tiles.js), so the menu's choices are written as it. */
const SORT_CHOICES = {
  'date-desc': { key: 'date', dir: 'desc' },
  'date-asc': { key: 'date', dir: 'asc' },
  'artist': { key: 'artist', dir: 'asc' },
  'added': { key: 'added', dir: 'desc' },
};

/** Point the menu at the current sort; one the menu has no choice for (a column's) shows as its own line. */
function syncSortMenu() {
  const [choice] = Object.entries(SORT_CHOICES).find(([, c]) => c.key === prefs.sort.key && c.dir === prefs.sort.dir) || [];
  const custom = $('sort').querySelector('option[value="custom"]');
  custom.hidden = Boolean(choice);
  const arrow = prefs.sort.dir === 'asc' ? '↑' : '↓';
  custom.textContent = `Sorted by ${SORT_BY[prefs.sort.key].label} ${arrow}`;
  custom.dataset.short = `${SORT_BY[prefs.sort.key].label} ${arrow}`;
  $('sort').value = choice || 'custom';
  dropdownSync($('sort'));
}

function visibleItems() {
  const query = $('search').value.trim();
  return sortList(
    items.filter(it => (prefs.fmt === 'all' || it.kind === prefs.fmt) && matchesQuery(it, query)),
    prefs.sort
  );
}

function setSort(sort) {
  prefs.sort = sort;
  savePrefs();
  render();
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
  $('content').replaceChildren(listEl(list, { sort: prefs.sort, onSort: key => setSort(nextSort(prefs.sort, key)) }));
}

/** Which controls belong to what is on screen: the pile's, the crate's, or the grid's and list's. */
function syncControls() {
  const floor = prefs.view === 'floor';
  $('sort').hidden = crateOn;
  $('messWrap').hidden = !floor || crateOn;
  $('crateBtn').hidden = !floor || crateOn;
  $('organiseWrap').hidden = !crateOn;
  $('digBtn').hidden = !crateOn;
  $('messBtn').hidden = !crateOn;
}

function render() {
  if (!items.length) return;
  // a flight is in the air: draw once it has landed, from whatever the controls say by then
  if (Crate.busy) { Crate.whenIdle(render); return; }

  VIEWS.forEach(v => document.body.classList.toggle('view-' + v, prefs.view === v));
  syncControls();

  syncSortMenu();
  const list = visibleItems();
  const query = $('search').value.trim();
  $('countMeta').textContent = list.length === items.length ? `${items.length} items` : `${list.length} of ${items.length} items`;

  if (!list.length) { renderEmpty(query); return; }
  if (crateOn) Crate.show(list, prefs.organise);
  else if (prefs.view === 'list') renderList(list);
  else if (prefs.view === 'grid') $('content').replaceChildren(gridEl(list));
  else $('content').replaceChildren(floorEl(list, prefs.mess));
}

// The grid captions every sleeve, so the floating label would only repeat them.
wireTiles($('content'), () => prefs.view !== 'grid');
wireDrawer();

/* ---------- Controls ---------- */

$('search').addEventListener('input', render);
$('sort').addEventListener('change', e => { if (SORT_CHOICES[e.target.value]) setSort(SORT_CHOICES[e.target.value]); });

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
  if (crateOn && b.dataset.view === 'floor') return; // already on the floor, just in the crate
  // Grid and List have no crate: choosing one puts it away, and cuts short any flight to or from it
  if (crateOn || (Crate.busy && b.dataset.view !== 'floor')) {
    if (crateOn) setFormat('all');
    crateOn = false;
    Crate.destroy();
  }
  // Floor and Grid are the same records laid out two ways, so they are carried across
  const before = Morph.capture($('content'));
  prefs.view = b.dataset.view;
  savePrefs();
  syncViewToggle();
  render();
  Morph.play(before, $('content'));
});

$('mess').addEventListener('input', e => {
  prefs.mess = Number(e.target.value);
  const floor = document.querySelector('.floor');
  if (floor) floor.style.setProperty('--mess', prefs.mess);
  savePrefs();
});

/* ---------- The crate ---------- */

$('organiseBy').innerHTML = Object.entries(CRATE_ORDERS)
  .map(([key, { label }]) => `<option value="${key}">${esc(label)}</option>`)
  .join('');

/** Sets the format filter, keeping the chips in step; false when there is nothing of that format to show. */
function setFormat(fmt) {
  if (fmt !== 'all' && !items.some(it => it.kind === fmt)) return false;
  prefs.fmt = fmt;
  renderChips();
  return true;
}

$('crateBtn').addEventListener('click', () => {
  if (crateOn || Crate.busy || prefs.view !== 'floor') return;
  const before = prefs.fmt;
  setFormat('vinyl');
  const list = visibleItems();
  if (!list.length) { setFormat(before); return; }
  savePrefs();
  crateOn = true;
  syncControls();
  Crate.enter(list, prefs.organise, prefs.mess);
});

$('messBtn').addEventListener('click', () => {
  if (!crateOn || Crate.busy) return;
  crateOn = false;
  setFormat('all');
  savePrefs();
  syncControls();
  Crate.exit(visibleItems(), prefs.mess);
});

$('organiseBy').addEventListener('change', e => {
  prefs.organise = e.target.value;
  savePrefs();
  render();
});

$('digBtn').addEventListener('click', () => Crate.dig());

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
$('organiseBy').value = prefs.organise;
dropdown($('sort'));
dropdown($('organiseBy'));
syncViewToggle();
init(false);
