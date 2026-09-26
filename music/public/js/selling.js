/* The shop: a grid or a list of cards rather than the collection's pile — a
 * buyer wants a cover, a price and a link to buy. Clicking a card (off the
 * eBay link) opens the drawer with the shop's spotlight. */

const CACHE_KEY = 'vinyl_selling_v1';
const VIEWS = ['grid', 'list'];

let items = [];

const prefs = storedPrefs(
  'vinyl_selling_prefs_v2',
  { sort: 'listed-desc', fmt: 'all', view: 'grid' },
  p => {
    if (!VIEWS.includes(p.view)) p.view = 'grid';
    return p;
  }
);

function sorted(list) {
  const byPrice = (a, b) => (a.price ?? Infinity) - (b.price ?? Infinity);
  if (prefs.sort === 'price-asc') return [...list].sort(byPrice);
  if (prefs.sort === 'price-desc') return [...list].sort((a, b) => byPrice(b, a));
  return [...list].sort((a, b) => (b.listed || '').localeCompare(a.listed || ''));
}

const kindBadge = it => `<i class="kc ${esc(it.kind)}">${esc(KIND_LABEL[it.kind] || it.kind)}</i>`;
const conditionBadge = it => `<span class="sale-condition ${esc(it.condition)}">${esc(it.conditionLabel)}</span>`;
const buyLink = (it, label) => `<a class="buy" href="${esc(it.ebay)}" target="_blank" rel="noopener">${label}</a>`;
const priceText = it => (it.priceLabel ? esc(it.priceLabel) : 'Ask');

/** An element for a listing that opens its drawer, with its picture dropped if it fails to load. */
function listingEl(tag, className, it, html) {
  const el = document.createElement(tag);
  el.className = className;
  el.tabIndex = 0;
  el.setAttribute('role', 'button');
  el.setAttribute('aria-label', `${it.title} — ${it.artist}. Open details`);
  el._it = it;
  el.innerHTML = html;

  const img = el.querySelector('img');
  if (img) img.addEventListener('error', () => img.remove());
  return el;
}

function cardEl(it) {
  // The full picture: the card is far bigger than a shelf tile.
  const cover = it.cover || it.thumb;

  return listingEl('article', 'sale-card', it, `
    <div class="sale-cover">${cover ? `<img src="${esc(cover)}" alt="" loading="lazy">` : ''}</div>
    <div class="sale-body">
      <h3>${esc(it.title)}</h3>
      <div class="sale-artist">${esc(it.artist)}</div>
      <div class="sale-meta">
        ${kindBadge(it)}
        ${it.condition ? conditionBadge(it) : ''}
        ${it.region ? `<span class="sale-region" title="${esc(it.regionName)}">${esc(it.region)}</span>` : ''}
        ${it.year ? `<span class="sale-year">${esc(it.year)}</span>` : ''}
      </div>
      <div class="sale-row">
        <span class="sale-price">${priceText(it)}</span>
        ${it.ebay ? buyLink(it, 'Buy on eBay ↗') : ''}
      </div>
    </div>`);
}

function rowEl(it) {
  const cover = it.cover || it.thumb;

  return listingEl('div', 'lrow', it, `
    <span class="lthumb">${cover ? `<img loading="lazy" decoding="async" alt="" src="${esc(cover)}">` : ''}</span>
    <span class="ltitle"><b title="${esc(it.title)}">${esc(it.title)}</b><small>${esc(it.artist)}</small></span>
    <span class="lyear"${it.released ? ` title="${esc(it.released)}"` : ''}>${esc(it.year || '—')}</span>
    <span class="lregion" title="${esc(it.regionName)}">${esc(it.region)}</span>
    <span class="lfmt">${kindBadge(it)}</span>
    <span class="lcondition">${it.condition ? conditionBadge(it) : '—'}</span>
    <span class="lprice">${priceText(it)}${it.ebay ? ` ${buyLink(it, 'Buy ↗')}` : ''}</span>`);
}

function saleListEl(list) {
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
  renderFormatChips(items, prefs);
}

function render() {
  const query = $('search').value.trim();
  const visible = sorted(items.filter(it => (prefs.fmt === 'all' || it.kind === prefs.fmt) && matchesQuery(it, query)));

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
    $('content').replaceChildren(saleListEl(visible));
    return;
  }

  const grid = document.createElement('div');
  grid.className = 'sale-grid';
  visible.forEach(it => grid.appendChild(cardEl(it)));
  $('content').replaceChildren(grid);
}

async function init() {
  try {
    items = prepareItems((await loadData(CACHE_KEY, 'selling')).items);
  } catch (error) {
    $('content').innerHTML = `<div class="state"><h2>This didn't load</h2><p>${esc(error.message)}</p></div>`;
    return;
  }
  renderChips();
  render();
}

const listingAt = target => {
  const el = target.closest('.sale-card, .lrow:not(.lhead)');
  return el && el._it ? el : null;
};

$('content').addEventListener('click', e => {
  if (e.target.closest('a.buy')) return; // the eBay link goes where it goes
  const el = listingAt(e.target);
  if (el) openDrawer(el._it.id, false, el);
});
$('content').addEventListener('keydown', e => {
  if (e.key !== 'Enter' && e.key !== ' ') return;
  const el = listingAt(e.target);
  if (el) { e.preventDefault(); openDrawer(el._it.id, false, el); }
});

$('search').addEventListener('input', render);

$('sort').value = prefs.sort;
dropdown($('sort'));
$('sort').addEventListener('change', e => {
  prefs.sort = e.target.value;
  prefs.save();
  render();
});

bindFormatChips(prefs, renderChips, render);
bindToggle('viewToggle', 'view', prefs, render);

wireDrawer();
markToggle('viewToggle', 'view', prefs.view);
init();
