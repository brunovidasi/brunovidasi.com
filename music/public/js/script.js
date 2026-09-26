/* The shelf: the whole collection on the floor, in a grid or in a list.
 *
 * "Put albums in a crate" throws the floor's records into a crate to flip
 * through (js/crate.js), and "Back to the mess" tips them out again. The crate
 * is a state of the floor view rather than a view of its own: it follows the
 * search and the format chips, is a crate of vinyl, and is put away by
 * choosing Grid or List. */

const CACHE_KEY = 'vinyl_collection_v5';
const OLD_CACHE_KEYS = ['vinyl_collection_v4', 'vinyl_collection_cache_v3', 'vinyl_collection_cache_v2'];
const VIEWS = ['floor', 'grid', 'list'];

/* The sort menu and the list's column headers set the same sort. */
const SORT_CHOICES = {
  'date-desc': { key: 'date', dir: 'desc' },
  'date-asc': { key: 'date', dir: 'asc' },
  'artist': { key: 'artist', dir: 'asc' },
  'added': { key: 'added', dir: 'desc' },
};

let items = [];
let crateOn = false;

const prefs = storedPrefs(
  'vinyl_prefs_v1',
  { view: 'floor', mess: 0.7, fmt: 'all', sort: DEFAULT_SORT, organise: DEFAULT_CRATE_ORDER },
  p => {
    if (!VIEWS.includes(p.view)) p.view = 'floor';
    if (!CRATE_ORDERS[p.organise]) p.organise = DEFAULT_CRATE_ORDER;
    p.sort = validSort(p.sort);
    return p;
  }
);

/** Points the sort menu at the current sort; a column's sort the menu has no choice for gets a line of its own. */
function syncSortMenu() {
  const [choice] = Object.entries(SORT_CHOICES).find(([, c]) => c.key === prefs.sort.key && c.dir === prefs.sort.dir) || [];
  const custom = $('sort').querySelector('option[value="custom"]');
  const label = `${SORT_BY[prefs.sort.key].label} ${prefs.sort.dir === 'asc' ? '↑' : '↓'}`;
  custom.hidden = Boolean(choice);
  custom.textContent = `Sorted by ${label}`;
  custom.dataset.short = label;
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
  prefs.save();
  render();
}

function renderChips() {
  renderFormatChips(items, prefs);
}

/** Sets the format filter and the chips with it; false when there is nothing in that format. */
function setFormat(fmt) {
  if (fmt !== 'all' && !items.some(it => it.kind === fmt)) return false;
  prefs.fmt = fmt;
  renderChips();
  return true;
}

/* ---------- Drawing ---------- */

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

/** Shows the controls that belong to what is on screen: the pile's, the crate's, or neither. */
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
  // Mid-flight, draw once the records have landed.
  if (Crate.busy) { Crate.whenIdle(render); return; }

  markView(VIEWS, prefs.view);
  syncControls();
  syncSortMenu();

  const list = visibleItems();
  const query = $('search').value.trim();
  $('countMeta').textContent = list.length === items.length ? `${items.length} items` : `${list.length} of ${items.length} items`;

  if (!list.length) renderEmpty(query);
  else if (crateOn) Crate.show(list, prefs.organise);
  else if (prefs.view === 'list') $('content').replaceChildren(listEl(list, { sort: prefs.sort, onSort: key => setSort(nextSort(prefs.sort, key)) }));
  else if (prefs.view === 'grid') $('content').replaceChildren(gridEl(list));
  else $('content').replaceChildren(floorEl(list, prefs.mess));
}

/* ---------- Controls ---------- */

// The grid captions every sleeve, so the floating label would only repeat them.
wireTiles($('content'), () => prefs.view !== 'grid');
wireDrawer();

$('search').addEventListener('input', render);
$('sort').addEventListener('change', e => { if (SORT_CHOICES[e.target.value]) setSort(SORT_CHOICES[e.target.value]); });
bindFormatChips(prefs, renderChips, render);
bindMessSlider(prefs);

onToggle('viewToggle', 'view', view => {
  if (crateOn && view === 'floor') return;
  // Grid and List have no crate: choosing one puts it away, even mid-flight.
  if (crateOn || (Crate.busy && view !== 'floor')) {
    if (crateOn) setFormat('all');
    crateOn = false;
    Crate.destroy();
  }
  // Floor and grid are the same records laid out two ways, so they are carried across.
  const before = Morph.capture($('content'));
  prefs.view = view;
  prefs.save();
  markToggle('viewToggle', 'view', view);
  render();
  Morph.play(before, $('content'));
});

/* ---------- The crate ---------- */

$('organiseBy').innerHTML = Object.entries(CRATE_ORDERS)
  .map(([key, { label }]) => `<option value="${key}">${esc(label)}</option>`)
  .join('');

$('crateBtn').addEventListener('click', () => {
  if (crateOn || Crate.busy || prefs.view !== 'floor') return;
  const before = prefs.fmt;
  setFormat('vinyl');
  const list = visibleItems();
  if (!list.length) { setFormat(before); return; }
  prefs.save();
  crateOn = true;
  syncControls();
  Crate.enter(list, prefs.organise, prefs.mess);
});

$('messBtn').addEventListener('click', () => {
  if (!crateOn || Crate.busy) return;
  crateOn = false;
  setFormat('all');
  prefs.save();
  syncControls();
  Crate.exit(visibleItems(), prefs.mess);
});

$('organiseBy').addEventListener('change', e => {
  prefs.organise = e.target.value;
  prefs.save();
  render();
});

$('digBtn').addEventListener('click', () => Crate.dig());

/* ---------- Loading ---------- */

async function init(force) {
  $('content').innerHTML = '<div class="state"><p>Loading the collection…</p></div>';
  $('countMeta').textContent = '';
  OLD_CACHE_KEYS.forEach(clearCache);

  try {
    items = prepareItems(await loadData(CACHE_KEY, 'collection', { force, usable: cached => cached.length, keep: data => data.items }));
  } catch (error) {
    renderError(error.message);
    return;
  }

  renderChips();
  if (!items.length) {
    $('content').innerHTML = '<div class="state"><h2>The shelf is empty</h2><p>Nothing has been synced from Discogs yet.</p></div>';
  }
  render();
}

$('organiseBy').value = prefs.organise;
dropdown($('sort'));
dropdown($('organiseBy'));
markToggle('viewToggle', 'view', prefs.view);
init(false);
