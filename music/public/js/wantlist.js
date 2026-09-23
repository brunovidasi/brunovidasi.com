/* What isn't on the shelf yet, drawn like what is.
 *
 * Two sections — the Discogs wantlist, and the records being hunted that
 * Discogs has no page for. Same tiles, same drawer, same floor as everywhere
 * else; the only difference is that these are records Bruno doesn't have, so
 * they sit a little more spaced out and the hunting ones say why they're here.
 */

const CACHE_KEY = 'vinyl_wantlist_v2';
const PREFS_KEY = 'vinyl_wantlist_prefs_v1';
const CACHE_TTL = 1000 * 60 * 60 * 6;
const VIEWS = ['floor', 'grid'];

let sections = [];
let total = 0;
const prefs = loadPrefs();

function loadPrefs() {
  const defaults = { view: 'floor', mess: 0.6, fmt: 'all' };
  try {
    const p = { ...defaults, ...JSON.parse(localStorage.getItem(PREFS_KEY) || '{}') };
    if (!VIEWS.includes(p.view)) p.view = defaults.view;
    return p;
  } catch (e) { return defaults; }
}

function savePrefs() {
  try { localStorage.setItem(PREFS_KEY, JSON.stringify(prefs)); } catch (e) { /* storage unavailable — ignore */ }
}

function visibleSections() {
  const query = $('search').value.trim();

  return sections.map(section => ({
    ...section,
    items: section.items.filter(it =>
      (prefs.fmt === 'all' || it.kind === prefs.fmt) && matchesQuery(it, query)),
  }));
}

function renderChips() {
  const counts = {};
  sections.forEach(s => s.items.forEach(it => { counts[it.kind] = (counts[it.kind] || 0) + 1; }));
  if (prefs.fmt !== 'all' && !counts[prefs.fmt]) prefs.fmt = 'all';

  $('formats').innerHTML = [['all', 'All'], ...Object.entries(KIND_LABEL)]
    .filter(([k]) => k === 'all' || counts[k])
    .map(([k, label]) => `<button type="button" data-k="${k}" class="${prefs.fmt === k ? 'on' : ''}">${label} <i>${k === 'all' ? total : counts[k]}</i></button>`)
    .join('');
}

function sectionEl(section) {
  const wrap = document.createElement('section');
  wrap.className = 'era';
  wrap.id = `era-${section.slug}`;

  const head = document.createElement('div');
  head.className = 'era-head';
  head.innerHTML = `
    <div class="era-title">
      <h2>${esc(section.name)}</h2>
      <div class="era-sub">${esc(section.tagline)}</div>
    </div>
    <div class="era-counts">${section.items.length} ${section.items.length === 1 ? 'record' : 'records'}</div>`;

  wrap.append(head, prefs.view === 'grid' ? gridEl(section.items) : floorEl(section.items, prefs.mess));
  return wrap;
}

function render() {
  VIEWS.forEach(v => document.body.classList.toggle('view-' + v, prefs.view === v));
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
  document.querySelectorAll('.floor').forEach(floor => floor.style.setProperty('--mess', prefs.mess));
  savePrefs();
});

function setData(data) {
  sections = [
    { slug: 'wanted', name: 'On the wantlist', tagline: 'Tracked on Discogs', items: prepareItems(data.wanted) },
    { slug: 'hunting', name: 'Still hunting', tagline: "Not listed on Discogs — if you have one, get in touch", items: prepareItems(data.hunting) },
  ].filter(section => section.items.length);

  total = sections.reduce((n, section) => n + section.items.length, 0);
  renderChips();
}

async function init() {
  const cached = loadCache(CACHE_KEY, CACHE_TTL);
  if (cached) {
    setData(cached);
    render();
    return;
  }

  try {
    const data = await fetchJSON('wantlist');
    saveCache(CACHE_KEY, data);
    setData(data);
    render();
  } catch (error) {
    $('content').innerHTML = `<div class="state"><h2>This didn't load</h2><p>${esc(error.message)}</p></div>`;
  }
}

$('mess').value = prefs.mess;
syncViewToggle();
init();
