/* An artist page: their eras down the page, each split by format.
 *
 * Same records, drawn the same way as the shelf (js/tiles.js) — the objects
 * stood up in a grid, or lined up in a list. The eras and
 * their order come from the server (api/artist), which is what makes one file
 * work for every artist, and an era added in the admin appear here with no code
 * touched.
 *
 * The last section isn't an era: it's what Bruno still wants by that artist (the
 * Discogs wantlist plus the records he's hunting), drawn the same way so a gap
 * on the shelf sits right under the records around it.
 */

const SLUG = document.body.dataset.slug;
const CACHE_KEY = `vinyl_artist_${SLUG}_v4`;
const PREFS_KEY = 'vinyl_artist_prefs_v2';
const CACHE_TTL = 1000 * 60 * 60 * 6;
// Grid first: an era is an album's pressings side by side, and lining them up
// is how you compare them. The list is a click away for the details.
const VIEWS = ['grid', 'list'];

// The order formats appear in within an era. 'bd', not 'bluray', matching the CSS.
const GROUPS = [
  ['vinyl', 'Vinyl'],
  ['cd', 'CD'],
  ['dvd', 'DVD'],
  ['bd', 'Blu-ray'],
  ['other', 'Other'],
];

let sections = [];
let total = 0;
let wantedTotal = 0;
const prefs = loadPrefs();

function loadPrefs() {
  const defaults = { view: 'grid', fmt: 'all' };
  try {
    const p = { ...defaults, ...JSON.parse(localStorage.getItem(PREFS_KEY) || '{}') };
    if (!VIEWS.includes(p.view)) p.view = defaults.view;
    return p;
  } catch (e) { return defaults; }
}

function savePrefs() {
  try { localStorage.setItem(PREFS_KEY, JSON.stringify(prefs)); } catch (e) { /* storage unavailable — ignore */ }
}

/* ---------- What's on screen ---------- */

/** Every section, with its items narrowed by the search box and the chips. */
function visibleSections() {
  const query = $('search').value.trim().toLowerCase();

  return sections.map(section => ({
    ...section,
    items: section.items.filter(it =>
      (prefs.fmt === 'all' || it.kind === prefs.fmt) && (!query || it.hay.includes(query))),
  }));
}

function renderChips() {
  const counts = {};
  sections.forEach(s => s.items.forEach(it => { counts[it.kind] = (counts[it.kind] || 0) + 1; }));
  const everything = total + wantedTotal;
  if (prefs.fmt !== 'all' && !counts[prefs.fmt]) prefs.fmt = 'all';

  $('formats').innerHTML = [['all', 'All'], ...GROUPS]
    .filter(([k]) => k === 'all' || counts[k])
    .map(([k, label]) => `<button type="button" data-k="${k}" class="${prefs.fmt === k ? 'on' : ''}">${label} <i>${k === 'all' ? everything : counts[k]}</i></button>`)
    .join('');
}

function renderError(message) {
  $('eraNav').hidden = true;
  $('content').innerHTML = `
    <div class="state">
      <h2>This page didn't load</h2>
      <p>${esc(message)}</p>
      <button class="ghost" id="retry">Try again</button>
    </div>`;
  $('retry').addEventListener('click', () => init(true));
}

function renderEmpty(query) {
  $('eraNav').hidden = true;
  $('content').innerHTML = `
    <div class="state">
      <h2>${query ? `Nothing matches "${esc(query)}"` : 'Nothing in this format'}</h2>
      <p>${query ? 'Try a different title or edition.' : 'Try a different format.'}</p>
    </div>`;
}

/** An era's heading: its number, name, years and what it is remembered for. */
function eraHead(section, index) {
  const head = document.createElement('div');
  head.className = 'era-head';
  const sub = [section.years, section.tagline].filter(Boolean).join(' · ');
  const count = section.items.length;

  // The wanted section isn't part of the chronology, so it takes a mark in
  // place of an era number.
  head.innerHTML = `
    <span class="era-num">${section.wanted ? '+' : String(index + 1).padStart(2, '0')}</span>
    <div class="era-title">
      <h2>${esc(section.name)}</h2>
      ${sub ? `<div class="era-sub">${esc(sub)}</div>` : ''}
    </div>
    <div class="era-counts">${count} ${section.wanted ? 'wanted' : count === 1 ? 'record' : 'records'}</div>`;

  return head;
}

function eraEl(section, index) {
  const era = document.createElement('section');
  era.className = section.wanted ? 'era wanted' : 'era';
  era.id = `era-${section.slug}`;
  era.appendChild(eraHead(section, index));

  // The list already says what each record is in its Format column, so an era
  // is one table, in the same format order the grid uses.
  if (prefs.view === 'list') {
    era.appendChild(listEl(GROUPS.flatMap(([kind]) => section.items.filter(it => it.kind === kind))));
    return era;
  }

  GROUPS.forEach(([kind, label]) => {
    const group = section.items.filter(it => it.kind === kind);
    if (!group.length) return;

    const heading = document.createElement('div');
    heading.className = 'group-label';
    heading.innerHTML = `${esc(label)} <i>${group.length}</i>`;

    era.append(heading, gridEl(group));
  });

  return era;
}

function render() {
  if (!sections.length) return;
  VIEWS.forEach(v => document.body.classList.toggle('view-' + v, prefs.view === v));

  const visible = visibleSections();
  const count = wanted => visible
    .filter(section => Boolean(section.wanted) === wanted)
    .reduce((n, section) => n + section.items.length, 0);
  const owned = count(false);
  const missing = count(true);
  const shown = owned + missing;
  const query = $('search').value.trim();

  $('countMeta').textContent = [
    owned === total ? `${total} records` : `${owned} of ${total} records`,
    wantedTotal && (missing === wantedTotal ? `${wantedTotal} wanted` : `${missing} of ${wantedTotal} wanted`),
  ].filter(Boolean).join(' · ');

  if (!shown) { renderEmpty(query); return; }

  // The numbering follows the full era list, so an era keeps its number while
  // the page is filtered.
  const withNumbers = visible
    .map((section, index) => ({ section, index }))
    .filter(entry => entry.section.items.length);

  const nav = $('eraNav');
  nav.innerHTML = `<div class="era-nav-inner">${withNumbers.map(({ section }) =>
    `<a href="#era-${esc(section.slug)}">${esc(section.name)}<span class="n">${section.items.length}</span></a>`
  ).join('')}</div>`;
  nav.hidden = false;

  $('content').replaceChildren(...withNumbers.map(({ section, index }) => eraEl(section, index)));
}

// The grid captions every sleeve and the list names every row, so the floating
// label would only repeat them.
wireTiles($('content'), () => false);
wireDrawer();

/* ---------- Controls ---------- */

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

function setSections(data) {
  sections = data.sections.map(section => ({ ...section, items: prepareItems(section.items) }));

  const wanted = data.wanted || [];
  if (wanted.length) {
    sections.push({
      slug: 'wanted',
      name: 'Still wanted',
      years: '',
      tagline: 'Not on the shelf yet',
      wanted: true,
      items: prepareItems(wanted),
    });
  }

  total = data.count;
  wantedTotal = wanted.length;
  renderChips();
}

async function init(force) {
  $('countMeta').textContent = '';

  if (!force) {
    const cached = loadCache(CACHE_KEY, CACHE_TTL);
    if (cached && (cached.count || (cached.wanted || []).length)) {
      setSections(cached);
      render();
      return;
    }
  }

  try {
    const data = await fetchJSON(`artist?slug=${encodeURIComponent(SLUG)}`);
    saveCache(CACHE_KEY, data);

    if (!data.count && !(data.wanted || []).length) {
      $('eraNav').hidden = true;
      $('content').innerHTML = '<div class="state"><h2>Nothing here yet</h2><p>No records by this artist have been synced in.</p></div>';
      return;
    }

    setSections(data);
    render();
  } catch (error) {
    renderError(error.message);
  }
}

syncViewToggle();
init(false);
