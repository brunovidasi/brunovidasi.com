/* The shop: a grid or a list of cards, not the collection's messy pile — a
 * buyer wants a cover, a price and a link to buy, not something to dig
 * through. Clicking a card (off the eBay link) opens the drawer with the
 * spotlight (js/sale-spotlight.js) for anyone curious about the tracklist or
 * credits.
 */

const CACHE_KEY = 'vinyl_selling_v1';
const CACHE_TTL = 1000 * 60 * 60 * 6;
const PREFS_KEY = 'vinyl_selling_prefs_v2';
const VIEWS = ['grid', 'list'];

let items = [];
const prefs = loadPrefs();

function loadPrefs() {
  const defaults = { sort: 'listed-desc', fmt: 'all', view: 'grid' };
  try {
    const p = { ...defaults, ...JSON.parse(localStorage.getItem(PREFS_KEY) || '{}') };
    if (!VIEWS.includes(p.view)) p.view = defaults.view;
    return p;
  } catch (e) { return defaults; }
}

function savePrefs() {
  try { localStorage.setItem(PREFS_KEY, JSON.stringify(prefs)); } catch (e) { /* storage unavailable — ignore */ }
}

function sorted(list) {
  const byPrice = (a, b) => (a.price ?? Infinity) - (b.price ?? Infinity);
  if (prefs.sort === 'price-asc') return [...list].sort(byPrice);
  if (prefs.sort === 'price-desc') return [...list].sort((a, b) => byPrice(b, a));
  return [...list].sort((a, b) => (b.listed || '').localeCompare(a.listed || ''));
}

/** The kind badge + region + year line, the same idea as the collection's grid caption. */
function metaHtml(it) {
  return `
    <div class="sale-meta">
      <i class="kc ${esc(it.kind)}">${esc(KIND_LABEL[it.kind] || it.kind)}</i>
      ${it.condition ? `<span class="sale-condition ${esc(it.condition)}">${esc(it.conditionLabel)}</span>` : ''}
      ${it.region ? `<span class="sale-region" title="${esc(it.regionName)}">${esc(it.region)}</span>` : ''}
      ${it.year ? `<span class="sale-year">${esc(it.year)}</span>` : ''}
    </div>`;
}

function priceRowHtml(it, buyLabel) {
  return `
    <div class="sale-row">
      <span class="sale-price">${it.priceLabel ? esc(it.priceLabel) : 'Ask'}</span>
      ${it.ebay ? `<a class="buy" href="${esc(it.ebay)}" target="_blank" rel="noopener">${buyLabel}</a>` : ''}
    </div>`;
}

function cardEl(it) {
  const card = document.createElement('article');
  card.className = 'sale-card';
  card.tabIndex = 0;
  card.setAttribute('role', 'button');
  card.setAttribute('aria-label', `${it.title} — ${it.artist}. Open details`);
  card._it = it;

  // The full picture, not the small thumbnail — the card is far bigger than a
  // shelf tile, and a buyer is looking closely at what they're paying for.
  const cover = it.cover || it.thumb;
  card.innerHTML = `
    <div class="sale-cover">${cover ? `<img src="${esc(cover)}" alt="" loading="lazy">` : ''}</div>
    <div class="sale-body">
      <h3>${esc(it.title)}</h3>
      <div class="sale-artist">${esc(it.artist)}</div>
      ${metaHtml(it)}
      ${priceRowHtml(it, 'Buy on eBay ↗')}
    </div>`;
  const img = card.querySelector('img');
  if (img) img.addEventListener('error', () => img.remove());
  return card;
}

function rowEl(it) {
  const row = document.createElement('div');
  row.className = 'lrow';
  row.tabIndex = 0;
  row.setAttribute('role', 'button');
  row.setAttribute('aria-label', `${it.title} — ${it.artist}. Open details`);
  row._it = it;

  const cover = it.cover || it.thumb;
  row.innerHTML = `
    <span class="lthumb">${cover ? `<img loading="lazy" decoding="async" alt="" src="${esc(cover)}">` : ''}</span>
    <span class="ltitle"><b title="${esc(it.title)}">${esc(it.title)}</b><small>${esc(it.artist)}</small></span>
    <span class="lyear"${it.released ? ` title="${esc(it.released)}"` : ''}>${esc(it.year || '—')}</span>
    <span class="lregion" title="${esc(it.regionName)}">${esc(it.region)}</span>
    <span class="lfmt"><i class="kc ${esc(it.kind)}">${esc(KIND_LABEL[it.kind] || it.kind)}</i></span>
    <span class="lcondition">${it.condition ? `<span class="sale-condition ${esc(it.condition)}">${esc(it.conditionLabel)}</span>` : '—'}</span>
    <span class="lprice">${it.priceLabel ? esc(it.priceLabel) : 'Ask'}${it.ebay ? ` <a class="buy" href="${esc(it.ebay)}" target="_blank" rel="noopener">Buy ↗</a>` : ''}</span>`;
  const img = row.querySelector('img');
  if (img) img.addEventListener('error', () => img.remove());
  return row;
}

function listEl(list) {
  const wrap = document.createElement('div');
  wrap.className = 'list selling';
  const head = document.createElement('div');
  head.className = 'lrow lhead';
  head.innerHTML = '<span></span><span>Title</span><span>Year</span><span>Region</span><span>Format</span><span>Condition</span><span>Price</span>';
  wrap.appendChild(head);
  list.forEach(it => wrap.appendChild(rowEl(it)));
  return wrap;
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

function render() {
  const query = $('search').value.trim();
  const visible = sorted(items.filter(it =>
    (prefs.fmt === 'all' || it.kind === prefs.fmt) && matchesQuery(it, query)));

  $('countMeta').textContent = visible.length === items.length
    ? `${items.length} for sale`
    : `${visible.length} of ${items.length} for sale`;

  if (!visible.length) {
    $('content').innerHTML = `
      <div class="state">
        <h2>${query ? `Nothing matches "${esc(query)}"` : 'Nothing for sale right now'}</h2>
        <p>${query ? 'Try a different title.' : 'Check back soon.'}</p>
      </div>`;
    return;
  }

  if (prefs.view === 'list') {
    $('content').replaceChildren(listEl(visible));
    return;
  }

  const grid = document.createElement('div');
  grid.className = 'sale-grid';
  visible.forEach(it => grid.appendChild(cardEl(it)));
  $('content').replaceChildren(grid);
}

function setData(data) {
  items = prepareItems(data.items);
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
    const data = await fetchJSON('selling');
    saveCache(CACHE_KEY, data);
    setData(data);
    render();
  } catch (error) {
    $('content').innerHTML = `<div class="state"><h2>This didn't load</h2><p>${esc(error.message)}</p></div>`;
  }
}

$('content').addEventListener('click', e => {
  if (e.target.closest('a.buy')) return; // let the eBay link navigate on its own
  const el = e.target.closest('.sale-card, .lrow:not(.lhead)');
  if (el && el._it) openDrawer(el._it.id, false, el);
});
$('content').addEventListener('keydown', e => {
  if (e.key !== 'Enter' && e.key !== ' ') return;
  const el = e.target.closest('.sale-card, .lrow:not(.lhead)');
  if (el && el._it) { e.preventDefault(); openDrawer(el._it.id, false, el); }
});

$('search').addEventListener('input', render);

$('sort').value = prefs.sort;
dropdown($('sort'));
$('sort').addEventListener('change', e => {
  prefs.sort = e.target.value;
  savePrefs();
  render();
});

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

wireDrawer();
syncViewToggle();
init();
