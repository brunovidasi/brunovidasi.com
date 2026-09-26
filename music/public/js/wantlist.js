/* What isn't on the shelf yet, drawn like what is: the Discogs wantlist, and
 * the records being hunted that Discogs has no page for. */

const CACHE_KEY = 'vinyl_wantlist_v2';
const VIEWS = ['floor', 'grid', 'list'];

let sections = [];
let total = 0;

const prefs = storedPrefs(
  'vinyl_wantlist_prefs_v1',
  { view: 'floor', mess: 0.6, fmt: 'all', sort: DEFAULT_SORT },
  p => {
    if (!VIEWS.includes(p.view)) p.view = 'floor';
    p.sort = validSort(p.sort);
    return p;
  }
);

const sortBy = sortByColumn(prefs, render);

function visibleSections() {
  const query = $('search').value.trim();

  return sections.map(section => ({
    ...section,
    items: section.items.filter(it => (prefs.fmt === 'all' || it.kind === prefs.fmt) && matchesQuery(it, query)),
  }));
}

function renderChips() {
  renderFormatChips(sections.flatMap(section => section.items), prefs);
}

function sectionEl(section) {
  const wrap = document.createElement('section');
  wrap.className = 'era';
  wrap.id = `era-${section.slug}`;

  const count = section.items.length;
  const head = document.createElement('div');
  head.className = 'era-head';
  head.innerHTML = `
    <div class="era-title">
      <h2>${esc(section.name)}</h2>
      <div class="era-sub">${esc(section.tagline)}</div>
    </div>
    <div class="era-counts">${count} ${count === 1 ? 'record' : 'records'}</div>`;
  wrap.appendChild(head);

  if (prefs.view === 'list') {
    wrap.appendChild(listEl(sortList(section.items, prefs.sort), { sort: prefs.sort, onSort: sortBy }));
  } else {
    wrap.appendChild(prefs.view === 'grid' ? gridEl(section.items) : floorEl(section.items, prefs.mess));
  }
  return wrap;
}

function render() {
  markView(VIEWS, prefs.view);
  $('messWrap').hidden = prefs.view !== 'floor';

  const visible = visibleSections().filter(section => section.items.length);
  const shown = visible.reduce((n, section) => n + section.items.length, 0);
  const query = $('search').value.trim();

  $('countMeta').textContent = shown === total ? `${total} records` : `${shown} of ${total} records`;

  if (!shown) {
    $('content').innerHTML = `
      <div class="state">
        <h2>${query ? `Nothing matches "${esc(query)}"` : 'Nothing here'}</h2>
        <p>${query ? 'Try a different title.' : 'The wantlist is empty — for now.'}</p>
      </div>`;
    return;
  }

  $('content').replaceChildren(...visible.map(sectionEl));
}

wireTiles($('content'), () => prefs.view !== 'grid');
wireDrawer();

$('search').addEventListener('input', render);
bindFormatChips(prefs, renderChips, render);
bindToggle('viewToggle', 'view', prefs, render);
bindMessSlider(prefs);

function setData(data) {
  sections = [
    { slug: 'wanted', name: 'On the wantlist', tagline: 'Tracked on Discogs', items: prepareItems(data.wanted) },
    { slug: 'hunting', name: 'Still hunting', tagline: 'Not listed on Discogs — if you have one, get in touch', items: prepareItems(data.hunting) },
  ].filter(section => section.items.length);

  total = sections.reduce((n, section) => n + section.items.length, 0);
  renderChips();
}

async function init() {
  try {
    setData(await loadData(CACHE_KEY, 'wantlist'));
  } catch (error) {
    $('content').innerHTML = `<div class="state"><h2>This didn't load</h2><p>${esc(error.message)}</p></div>`;
    return;
  }
  render();
}

markToggle('viewToggle', 'view', prefs.view);
init();
