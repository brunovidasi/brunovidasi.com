/* The records themselves: sleeves, cases, and the discs that slide out of them.
 *
 * This is the shelf's own way of drawing a record, lifted out of script.js so
 * the artist pages draw them identically. Every page that shows a record uses
 * these, so a change to how a 7" or a Blu-ray case looks happens once.
 *
 * Needs, in the page: a #tip element, and the drawer markup wireDrawer() wants.
 */

const KIND_LABEL = { vinyl: 'Vinyl', cd: 'CD', dvd: 'DVD', bd: 'Blu-ray', other: 'Other' };

/* Every card the page has been given, by id, so that the drawer can stand a record
   up from its id alone (js/spotlight.js). Filled by prepareItems. */
const cardIndex = new Map();

/* Sleeve size in px at full scale, following the real objects: LP 12.4",
   CD case 5.6", DVD case 5.3 x 7.5", Blu-ray case 5.3 x 6.7". Goes by the case
   actually drawn (it.shape), which for most records is just the format — a DVD
   set by hand to show its CD-sized case is the one exception. */
function dims(it) {
  if (it.shape === 'dvd') return [116, 163];
  if (it.shape === 'bd') return [116, 147];
  if (it.shape === 'cd' || it.shape === 'other') return [112, 112];
  const d = it.discs[0] || {};
  const w = d.sz === 7 ? 150 : d.sz === 10 ? 200 : (it.discs.length > 1 || it.box) ? 270 : 250;
  return [w, w];
}

function buildTile(it) {
  const [w, h] = dims(it);
  const hs = hash(String(it.id));
  const t = document.createElement('div');
  t.className = `tile kind-${it.shape}`;
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
  if (it.discs.length) discWatcher ? discWatcher.observe(t) : preloadDiscs(t);
  return t;
}

/* ---------- The discs, revealed on hover ---------- */

/* A picture set for this disc in the admin (d.art) is its own; otherwise the
   record's one disc picture, if it has one, is on every disc. */
function discArt(it, d) {
  return d.art || (d.t === 'v' ? (d.pic && it.disc) || it.cover : it.disc);
}

/* The disc elements only exist once a tile is first hovered, and a CSS
   background isn't fetched before then, so the picture would start downloading
   just as the disc slides out. Fetching it as the tile nears the screen, the way
   the sleeve's lazy <img> is, leaves it in the cache by the time anyone hovers. */
const preloaded = new Set();

function preloadDiscs(t) {
  for (const d of t._it.discs) {
    const art = discArt(t._it, d);
    if (!art || preloaded.has(art)) continue;
    preloaded.add(art);
    const img = new Image();
    img.decoding = 'async';
    img.src = art;
  }
}

const discWatcher = 'IntersectionObserver' in window
  ? new IntersectionObserver(entries => {
      for (const e of entries) {
        if (!e.isIntersecting) continue;
        discWatcher.unobserve(e.target);
        preloadDiscs(e.target);
      }
    }, { rootMargin: '400px' })
  : null;

function discEl(it, k) {
  const d = it.discs[k];
  const el = document.createElement('i');
  const sp = document.createElement('span');
  el.className = `d ${d.t === 'v' ? 'v' : d.t}${d.tr ? ' tr' : ''}${d.pic ? ' pic' : ''}`;
  el.style.setProperty('--k', k);
  const dd = d.t === 'v' ? 0.96 : 0.88;
  el.style.setProperty('--dd', dd);
  el.style.setProperty('--reach', (1 + dd / 3 + k * 0.16).toFixed(2)); // a third of the first disc peeks out; later ones a little further, behind it
  el.style.zIndex = String(3 - k);
  if (d.c) el.style.setProperty('--vc', d.c);
  // Vinyl shows the cover on the label, unless it is a picture disc with its own
  // image picked in the admin; a CD, DVD or Blu-ray shows the plain reading side
  // unless a disc image was picked for it in the admin.
  const art = discArt(it, d);
  if (art) el.style.setProperty('--art', `url("${art.replace(/"/g, '%22')}")`);
  if (d.t !== 'v' && (d.art || it.disc)) el.classList.add('art');
  sp.className = 'sp';
  sp.innerHTML = '<b class="lbl"></b>';
  el.appendChild(sp);
  return el;
}

function reveal(t) {
  const it = t._it;
  if (!it.discs.length) {
    // nothing physical to slide out: the sleeve just lifts
    t.classList.add('hot', 'bare');
    return;
  }
  if (!t._discs) {
    const wrap = document.createElement('div');
    wrap.className = 'discs';
    it.discs.forEach((_, k) => wrap.appendChild(discEl(it, k)));
    t.prepend(wrap);
    t._discs = wrap;
  }
  // slide out toward whichever side has room
  const rc = t.getBoundingClientRect();
  const reach = 0.33 + 0.16 * (it.discs.length - 1);
  t.classList.toggle('left', rc.right + rc.width * reach > document.documentElement.clientWidth - 6);
  void t.offsetWidth; // flush styles so the slide-out transition runs
  t.classList.add('hot');
}

/* ---------- Sorting ---------- */

const collator = new Intl.Collator(undefined, { numeric: true, sensitivity: 'base' });

/* What each column sorts by. `date` is the full release date as a sortable
   "YYYY-MM-DD" (00 where the month or day isn't known), which is why the Year
   column orders by day and not just by year; those and `added` compare as plain
   text. `added` has no column, but the shelf's sort menu offers it. */
const SORT_BY = {
  title:   { label: 'Title',   value: it => it.title },
  barcode: { label: 'Barcode', value: it => it.barcode },
  artist:  { label: 'Artist',  value: it => it.artist },
  date:    { label: 'Year',    value: it => it.date, plain: true },
  region:  { label: 'Region',  value: it => it.region },
  kind:    { label: 'Format',  value: it => KIND_LABEL[it.kind] },
  details: { label: 'Details', value: it => it.fmtRest },
  added:   { label: 'Added',   value: it => it.added, plain: true },
};

const DEFAULT_SORT = { key: 'date', dir: 'desc' };

const compareText = (a, b) => (a < b ? -1 : a > b ? 1 : 0);

function validSort(sort, fallback = DEFAULT_SORT) {
  return sort && SORT_BY[sort.key] && (sort.dir === 'asc' || sort.dir === 'desc')
    ? { key: sort.key, dir: sort.dir }
    : fallback;
}

/** What clicking a column header does: a new column runs ascending, the same one flips. */
function nextSort(sort, key) {
  return { key, dir: sort.key === key && sort.dir === 'asc' ? 'desc' : 'asc' };
}

/** A sorted copy. Records with nothing in the column go last whichever way it runs. */
function sortList(list, { key, dir }) {
  const { value, plain } = SORT_BY[key];
  const compare = plain ? compareText : collator.compare;
  const sign = dir === 'desc' ? -1 : 1;
  // Within a tie: the earlier release, then artist, then title.
  const tie = (a, b) => compareText(a.date, b.date) || collator.compare(a.artist, b.artist) || collator.compare(a.title, b.title);

  return [...list].sort((a, b) => {
    const x = value(a) || '';
    const y = value(b) || '';
    if (!x || !y) return (!x - !y) || tie(a, b);
    return sign * compare(x, y) || tie(a, b);
  });
}

/* ---------- The list: one row per record ---------- */

function listHead(columns, sort) {
  const head = document.createElement('div');
  head.className = 'lrow lhead';
  head.innerHTML = '<span></span>' + columns.map(key => {
    const { label } = SORT_BY[key];
    const on = sort && sort.key === key;
    const state = on ? `, sorted ${sort.dir === 'asc' ? 'ascending' : 'descending'}` : '';
    return `<span><button type="button" class="lsort${on ? ` on ${sort.dir}` : ''}" data-key="${key}" aria-label="Sort by ${label}${state}">${label}<i aria-hidden="true"></i></button></span>`;
  }).join('');
  return head;
}

/**
 * One row per record, under headers that sort.
 *
 * The list is drawn in the order it is given; the page sorts it (sortList) and
 * keeps the sort, so every table on a page follows the same one.
 *
 * @param {object[]} list
 * @param {object} options
 * @param {boolean} [options.artist=true] false on an artist's own page, where a
 *        column of the same name down every row says nothing.
 * @param {{key: string, dir: string}} [options.sort] the sort the list is in, for the arrows.
 * @param {(key: string) => void} [options.onSort] called with a column's key when its header is clicked.
 */
function listEl(list, { artist = true, sort, onSort } = {}) {
  const wrap = document.createElement('div');
  wrap.className = artist ? 'list' : 'list no-artist';
  wrap.appendChild(listHead(artist
    ? ['title', 'barcode', 'artist', 'date', 'region', 'kind', 'details']
    : ['title', 'barcode', 'date', 'region', 'kind', 'details'], sort));

  wrap.addEventListener('click', e => {
    const button = e.target.closest('.lsort');
    if (!button || !onSort) return;
    const at = [...document.querySelectorAll('.list')].indexOf(wrap);
    onSort(button.dataset.key);
    // The click drew the tables again; put the keyboard back on the header it was on.
    document.querySelectorAll('.list')[at]?.querySelector(`.lsort[data-key="${button.dataset.key}"]`)?.focus({ preventScroll: true });
  });

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
      <span class="lbarcode">${esc(it.barcode)}</span>
      ${artist ? `<span class="lartist" title="${esc(it.artist)}">${esc(it.artist)}</span>` : ''}
      <span class="lyear"${it.released ? ` title="${esc(it.released)}"` : ''}>${esc(it.year || '—')}</span>
      <span class="lregion" title="${esc(it.regionName)}">${esc(it.region)}</span>
      <span class="lfmt"><i class="kc ${it.kind}">${KIND_LABEL[it.kind]}</i></span>
      <span class="ldetails" title="${esc(it.fmtRest)}">${esc(it.fmtRest)}</span>`;
    const img = row.querySelector('img');
    if (img) img.addEventListener('error', () => img.remove());
    wrap.appendChild(row);
  });
  return wrap;
}

/* ---------- The label that follows the pointer ---------- */

let tipEl = null;

function showTip(it) {
  if (!tipEl) return;
  tipEl.innerHTML = `<b>${esc(it.title)}</b>${esc(it.artist)}${it.year ? ' · ' + it.year : ''}<br><span>${esc(it.fmt)}</span>`;
  tipEl.style.opacity = 1;
}

function hideTip() {
  if (tipEl) tipEl.style.opacity = 0;
}

function hoverOn(t, event, showLabel) {
  t._on = true;
  reveal(t);
  if (showLabel && (!event || event.pointerType !== 'touch')) showTip(t._it);
}

function hoverOff(t) {
  t._on = false;
  t.classList.remove('hot');
  hideTip();
}

/* ---------- A tap, on a touch screen ----------
 *
 * A finger has no hover to bring the discs out ahead of the click, and lifting
 * it counts as the pointer leaving, so a tap used to flash them out and straight
 * back in just as the drawer covered the page. A tap now plays the hover on
 * purpose: the record comes out of its sleeve, the drawer opens once the discs
 * are most of the way out, and they stay out until it closes, then slide home.
 * Where the spotlight has room it carries the discs on itself, so there is no
 * wait. Reduced motion, a list row, or a record with no discs: no wait either.
 */

const PULL_MS = 380;  // the discs' slide is .6s; most of the way out by this
let heldTile = null;
let pullTimer = 0;

function pressTile(el) {
  const t = el.classList.contains('tile') ? el : el.querySelector('.tile');
  if (!t || matchMedia('(prefers-reduced-motion: reduce)').matches) { openDrawer(el._it.id, false, el); return; }
  if (pullTimer && heldTile === t) return; // a second tap while it is coming out

  releaseTile(true);
  heldTile = t;
  t._on = true;
  reveal(t);

  const room = typeof Spotlight !== 'undefined' && Spotlight.fits && Spotlight.fits(t._it.id);
  if (!t._it.discs.length || room) { openDrawer(el._it.id, false, el); return; }
  pullTimer = setTimeout(() => {
    pullTimer = 0;
    openDrawer(el._it.id, false, el);
  }, PULL_MS);
}

/** The drawer closed: the held record's discs go back in once it is out of the way. */
function releaseTile(now) {
  clearTimeout(pullTimer);
  pullTimer = 0;
  const t = heldTile;
  heldTile = null;
  if (!t) return;
  const back = () => { if (heldTile !== t) hoverOff(t); };
  if (now) back();
  else setTimeout(back, 220); // the drawer's slide away
}

/**
 * Wires a container full of tiles: hover reveals the discs, click (or Enter, or
 * Space) opens the drawer.
 *
 * @param {Element} content
 * @param {() => boolean} wantsLabel whether the floating label should show —
 *        the grid has captions under every sleeve already, so it doesn't.
 */
function wireTiles(content, wantsLabel = () => true) {
  tipEl ??= document.getElementById('tip');

  // A finger's over and out are the tap itself (pressTile), and the focus a tap
  // leaves behind isn't a keyboard's, so none of them count as hovering.
  let pointer = 'mouse';
  content.addEventListener('pointerdown', e => { pointer = e.pointerType; });
  content.addEventListener('pointerover', e => { const t = e.target.closest('.tile'); if (t && !t._on && e.pointerType !== 'touch') hoverOn(t, e, wantsLabel()); });
  content.addEventListener('pointerout', e => { const t = e.target.closest('.tile'); if (t && t !== heldTile && e.pointerType !== 'touch' && !t.contains(e.relatedTarget)) hoverOff(t); });
  content.addEventListener('focusin', e => { const t = e.target.closest('.tile'); if (t && !t._on && t.matches(':focus-visible')) hoverOn(t, null, wantsLabel()); });
  content.addEventListener('focusout', e => { const t = e.target.closest('.tile'); if (t && t !== heldTile) hoverOff(t); });

  content.addEventListener('click', e => {
    const el = e.target.closest('.tile, .lrow, .cell');
    if (!el || !el._it) return;
    if (pointer === 'touch') pressTile(el);
    else openDrawer(el._it.id, false, el);
  });
  content.addEventListener('keydown', e => {
    if (e.key !== 'Enter' && e.key !== ' ') return;
    const el = e.target.closest('.tile, .lrow, .cell');
    if (el && el._it) { e.preventDefault(); openDrawer(el._it.id, false, el); }
  });

  addEventListener('pointermove', e => {
    if (!tipEl) return;
    tipEl.style.left = Math.min(e.clientX + 14, innerWidth - 270) + 'px';
    tipEl.style.top = (e.clientY + 18) + 'px';
  });
}

/* ---------- The two ways a set of records is laid out ---------- */

/** A pile on the floor: overlapping, rotated, scaled by the messiness slider. */
function floorEl(list, mess) {
  const floor = document.createElement('div');
  floor.className = 'floor';
  floor.style.setProperty('--mess', mess);
  list.forEach(it => floor.appendChild(buildTile(it)));
  return floor;
}

/** The same objects, stood up on a baseline with a caption under each. */
function gridEl(list) {
  const grid = document.createElement('div');
  grid.className = 'grid';

  list.forEach(it => {
    const cell = document.createElement('div');
    cell.className = 'cell';
    cell._it = it;

    const stage = document.createElement('div');
    stage.className = 'stage';
    stage.appendChild(buildTile(it));

    const cap = document.createElement('div');
    cap.className = 'cap';
    cap.setAttribute('aria-hidden', 'true');
    cap.innerHTML = `
      <b title="${esc(it.title)}">${esc(it.title)}</b>
      <small title="${esc(it.artist)}">${esc(it.artist)}</small>
      <span class="m"><i class="kc ${it.kind}">${KIND_LABEL[it.kind]}</i>${it.region ? `<span class="gregion" title="${esc(it.regionName)}">${esc(it.region)}</span>` : ''}${esc(it.year || '')}</span>`;

    cell.append(stage, cap);
    grid.appendChild(cell);
  });

  return grid;
}

/** Lower case with the accents off, so "beyonce" finds Beyoncé. */
function fold(text) {
  return String(text).normalize('NFD').replace(/\p{M}/gu, '').toLowerCase();
}

/**
 * Whether a record matches what was typed. Every word has to be somewhere in
 * the record — its table columns and the details the server sent along — in any
 * order, so "gaga clear vinyl" works. A barcode typed the way it is printed on
 * the sleeve ("6 02537 51737 4") matches the bare digits too.
 */
function matchesQuery(it, query) {
  const words = fold(query).split(/\s+/).filter(Boolean);
  if (!words.length) return true;

  const digits = query.replace(/[\s-]/g, '');
  if (/^\d{8,}$/.test(digits) && it.barcode.includes(digits)) return true;

  return words.every(word => it.hay.includes(word));
}

/**
 * The API's card shape, plus what only the browser needs: the short kind name
 * the CSS is written against, and a haystack to search.
 */
function prepareItems(list) {
  const cards = list.map(({ search, ...it }) => ({
    ...it,
    kind: it.k,
    // The case actually drawn: the format's own, unless one was set by hand
    // (a DVD in a CD-sized jewel case) — see dims() and buildTile().
    shape: it.case || it.k,
    hay: fold([
      it.title, it.artist, it.barcode, it.year || '', it.released, it.region, it.regionName,
      KIND_LABEL[it.k], it.fmt, search,
    ].join(' | ')),
  }));
  cards.forEach(card => cardIndex.set(card.id, card));
  // a drawer opened by a link is waiting for the record it names
  if (typeof Spotlight !== 'undefined') Spotlight.adopt();
  return cards;
}
