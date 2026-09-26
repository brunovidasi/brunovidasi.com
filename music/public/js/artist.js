/* An artist's page: their eras down the page, each split by format, and at the
 * end what is still wanted by them. The eras come from api/artist, so an era
 * added in the admin shows up here without any code. */

const SLUG = document.body.dataset.slug;
const CACHE_KEY = `vinyl_artist_${SLUG}_v4`;
const VIEWS = ['grid', 'list'];
const ORDERS = ['oldest', 'newest'];

/* The order formats appear in within an era. */
const GROUPS = Object.entries(KIND_LABEL);

let sections = [];
let total = 0;
let wantedTotal = 0;
// The wantlist starts folded away: it spoils what isn't on the shelf yet.
let wantedExpanded = false;

const prefs = storedPrefs(
  'vinyl_artist_prefs_v2',
  { view: 'grid', fmt: 'all', order: 'oldest', sort: { key: 'date', dir: 'asc' } },
  p => {
    if (!VIEWS.includes(p.view)) p.view = 'grid';
    if (!ORDERS.includes(p.order)) p.order = 'oldest';
    p.sort = validSort(p.sort, { key: 'date', dir: 'asc' });
    return p;
  }
);

const sortBy = sortByColumn(prefs, render);

/** Every section, with its records narrowed by the search box and the chips. */
function visibleSections() {
  const query = $('search').value.trim();

  return sections.map(section => ({
    ...section,
    items: section.items.filter(it => (prefs.fmt === 'all' || it.kind === prefs.fmt) && matchesQuery(it, query)),
  }));
}

function renderChips() {
  renderFormatChips(sections.flatMap(section => section.items), prefs, total + wantedTotal);
}

function renderMessage(html) {
  $('eraNav').hidden = true;
  $('content').innerHTML = `<div class="state">${html}</div>`;
}

function renderError(message) {
  renderMessage(`
      <h2>This page didn't load</h2>
      <p>${esc(message)}</p>
      <button class="ghost" id="retry">Try again</button>`);
  $('retry').addEventListener('click', () => init(true));
}

function renderEmpty(query) {
  renderMessage(`
      <h2>${query ? `Nothing matches "${esc(query)}"` : 'Nothing in this format'}</h2>
      <p>${query ? 'Try a different title or edition.' : 'Try a different format.'}</p>`);
}

/** An era's heading. The wanted section is not an era: it gets a mark for a number, and folds. */
function eraHead(section, index) {
  const head = document.createElement('div');
  head.className = 'era-head';
  const sub = [section.years, section.tagline].filter(Boolean).join(' · ');
  const count = section.items.length;

  head.innerHTML = `
    <span class="era-num">${section.wanted ? '+' : String(index + 1).padStart(2, '0')}</span>
    <div class="era-title">
      <h2>${esc(section.name)}</h2>
      ${sub ? `<div class="era-sub">${esc(sub)}</div>` : ''}
    </div>
    <div class="era-counts">${count} ${section.wanted ? 'wanted' : count === 1 ? 'record' : 'records'}</div>
    ${section.wanted ? '<span class="era-toggle" aria-hidden="true">&#9656;</span>' : ''}`;

  if (section.wanted) {
    head.setAttribute('role', 'button');
    head.setAttribute('tabindex', '0');
    head.setAttribute('aria-expanded', String(wantedExpanded));
    const toggle = () => { wantedExpanded = !wantedExpanded; render(); };
    head.addEventListener('click', toggle);
    head.addEventListener('keydown', e => {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(); }
    });
  }

  return head;
}

function eraEl(section, index) {
  const era = document.createElement('section');
  era.className = section.wanted ? 'era wanted' : 'era';
  if (section.wanted && wantedExpanded) era.classList.add('expanded');
  era.id = `era-${section.slug}`;
  era.appendChild(eraHead(section, index));

  if (section.wanted && !wantedExpanded) return era;

  // The list names each record's format, so an era is one table.
  if (prefs.view === 'list') {
    era.appendChild(listEl(sortList(section.items, prefs.sort), { artist: false, sort: prefs.sort, onSort: sortBy }));
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
  markView(VIEWS, prefs.view);

  const visible = visibleSections();
  const count = wanted => visible
    .filter(section => Boolean(section.wanted) === wanted)
    .reduce((n, section) => n + section.items.length, 0);
  const owned = count(false);
  const missing = count(true);
  const query = $('search').value.trim();

  $('countMeta').textContent = [
    owned === total ? `${total} items` : `${owned} of ${total} items`,
    wantedTotal && (missing === wantedTotal ? `${wantedTotal} wanted` : `${missing} of ${wantedTotal} wanted`),
  ].filter(Boolean).join(' - ');

  if (!owned && !missing) { renderEmpty(query); return; }

  // Numbered by the full list of eras, so an era keeps its number while the page is filtered.
  const shown = visible
    .map((section, index) => ({ section, index }))
    .filter(entry => entry.section.items.length);

  // Newest first flips the eras only: "More" and the wantlist stay at the bottom.
  if (prefs.order === 'newest') {
    const trailing = ({ section }) => section.wanted || section.slug === 'more';
    shown.splice(0, shown.length, ...shown.filter(e => !trailing(e)).reverse(), ...shown.filter(trailing));
  }

  const nav = $('eraNav');
  nav.innerHTML = `<div class="era-nav-inner">${shown.map(({ section }) =>
    `<a href="#era-${esc(section.slug)}">${esc(section.name)}<span class="n">${section.items.length}</span></a>`
  ).join('')}</div>`;
  nav.hidden = false;

  $('content').replaceChildren(...shown.map(({ section, index }) => eraEl(section, index)));
  markCurrentEra();
}

/* ---------- The era you are in ----------
   The nav lights the era whose heading has scrolled up under it, and slides
   along to keep that chip in view. At the very bottom the last one is lit,
   since a short final era may never reach the line. */

function markCurrentEra() {
  const nav = $('eraNav');
  if (nav.hidden) return;

  const eras = [...document.querySelectorAll('#content .era')];
  const line = nav.offsetHeight + 24;
  let current = eras.filter(era => era.getBoundingClientRect().top <= line).pop();

  const atBottom = window.scrollY > 0 && window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 2;
  if (atBottom && eras.length) current = eras[eras.length - 1];

  nav.querySelectorAll('a').forEach(link => {
    const on = Boolean(current) && link.hash === `#${current.id}`;
    if (on === link.classList.contains('current')) return;
    link.classList.toggle('current', on);
    if (on) link.setAttribute('aria-current', 'location'); else link.removeAttribute('aria-current');
    if (on) revealInNav(nav.firstElementChild, link);
  });
}

/** Scrolls the nav sideways, not the page, until the chip is in view. */
function revealInNav(strip, link) {
  const room = 16;
  if (link.offsetLeft < strip.scrollLeft + room) {
    strip.scrollTo({ left: link.offsetLeft - room, behavior: 'smooth' });
  } else if (link.offsetLeft + link.offsetWidth > strip.scrollLeft + strip.clientWidth - room) {
    strip.scrollTo({ left: link.offsetLeft + link.offsetWidth - strip.clientWidth + room, behavior: 'smooth' });
  }
}

let eraFrame = 0;
function scheduleCurrentEra() {
  if (eraFrame) return;
  eraFrame = requestAnimationFrame(() => { eraFrame = 0; markCurrentEra(); });
}

window.addEventListener('scroll', scheduleCurrentEra, { passive: true });
window.addEventListener('resize', scheduleCurrentEra);

/* ---------- Controls ---------- */

// The grid captions every sleeve and the list names every row, so the floating label would only repeat them.
wireTiles($('content'), () => false);
wireDrawer();

$('search').addEventListener('input', render);
bindFormatChips(prefs, renderChips, render);
bindToggle('viewToggle', 'view', prefs, render);
bindToggle('orderToggle', 'order', prefs, render);

// Jumping to the folded wantlist opens it first. That redraws the nav under the
// click, so the scroll is done by hand once the section exists.
$('eraNav').addEventListener('click', e => {
  const a = e.target.closest('a');
  if (!a || a.hash !== '#era-wanted' || wantedExpanded) return;
  e.preventDefault();
  wantedExpanded = true;
  render();
  $('era-wanted').scrollIntoView({ block: 'start' });
});

/* ---------- Loading ---------- */

function setSections(data) {
  sections = data.sections.map(section => ({ ...section, items: prepareItems(section.items) }));

  const wanted = data.wanted || [];
  if (wanted.length) {
    sections.push({ slug: 'wanted', name: 'Still wanted', years: '', tagline: 'Not on the shelf yet', wanted: true, items: prepareItems(wanted) });
  }

  total = data.count;
  wantedTotal = wanted.length;
  renderChips();
}

async function init(force) {
  $('countMeta').textContent = '';

  let data;
  try {
    data = await loadData(CACHE_KEY, `artist?slug=${encodeURIComponent(SLUG)}`, {
      force,
      usable: cached => cached.count || (cached.wanted || []).length,
    });
  } catch (error) {
    renderError(error.message);
    return;
  }

  if (!data.count && !(data.wanted || []).length) {
    renderMessage('<h2>Nothing here yet</h2><p>No records by this artist have been synced in.</p>');
    return;
  }

  setSections(data);
  render();
}

markToggle('viewToggle', 'view', prefs.view);
markToggle('orderToggle', 'order', prefs.order);
init(false);
