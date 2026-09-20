/* The records themselves: sleeves, cases, and the discs that slide out of them.
 *
 * This is the shelf's own way of drawing a record, lifted out of script.js so
 * the artist pages draw them identically. Every page that shows a record uses
 * these, so a change to how a 7" or a Blu-ray case looks happens once.
 *
 * Needs, in the page: a #tip element, and the drawer markup wireDrawer() wants.
 */

const KIND_LABEL = { vinyl: 'Vinyl', cd: 'CD', dvd: 'DVD', bd: 'Blu-ray', other: 'Other' };

/* Sleeve size in px at full scale, following the real objects: LP 12.4",
   CD case 5.6", DVD case 5.3 x 7.5", Blu-ray case 5.3 x 6.7". */
function dims(it) {
  if (it.kind === 'dvd') return [116, 163];
  if (it.kind === 'bd') return [116, 147];
  if (it.kind === 'cd' || it.kind === 'other') return [112, 112];
  const d = it.discs[0] || {};
  const w = d.sz === 7 ? 150 : d.sz === 10 ? 200 : (it.discs.length > 1 || it.box) ? 270 : 250;
  return [w, w];
}

function buildTile(it) {
  const [w, h] = dims(it);
  const hs = hash(String(it.id));
  const t = document.createElement('div');
  t.className = `tile kind-${it.kind}`;
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
  return t;
}

/* ---------- The discs, revealed on hover ---------- */

function discEl(it, k) {
  const d = it.discs[k];
  const el = document.createElement('i');
  const sp = document.createElement('span');
  el.className = `d ${d.t === 'v' ? 'v' : d.t}${d.tr ? ' tr' : ''}${d.pic ? ' pic' : ''}`;
  el.style.setProperty('--k', k);
  el.style.setProperty('--dd', d.t === 'v' ? 0.96 : 0.88);
  el.style.setProperty('--reach', (1.42 + k * 0.16).toFixed(2)); // later discs peek out a little further, behind the first
  el.style.zIndex = String(3 - k);
  if (d.c) el.style.setProperty('--vc', d.c);
  // Vinyl shows the cover on the label; a CD, DVD or Blu-ray shows the plain
  // reading side unless a disc image was picked for it in the admin.
  const art = d.t === 'v' ? it.cover : it.disc;
  if (art) el.style.setProperty('--art', `url("${art.replace(/"/g, '%22')}")`);
  if (d.t !== 'v' && it.disc) el.classList.add('art');
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
  const reach = 0.45 + 0.16 * (it.discs.length - 1);
  t.classList.toggle('left', rc.right + rc.width * reach > document.documentElement.clientWidth - 6);
  void t.offsetWidth; // flush styles so the slide-out transition runs
  t.classList.add('hot');
}

/* ---------- The list: one row per record ---------- */

function listEl(list) {
  const wrap = document.createElement('div');
  wrap.className = 'list';
  wrap.innerHTML = '<div class="lrow lhead"><span></span><span>Title</span><span>Artist</span><span>Year</span><span>Format</span><span>Added</span></div>';
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
      <span class="lartist" title="${esc(it.artist)}">${esc(it.artist)}</span>
      <span class="lyear">${esc(it.year || '—')}</span>
      <span class="lfmt"><i class="kc ${it.kind}">${KIND_LABEL[it.kind]}</i>${esc(it.fmtRest)}</span>
      <span class="ladded">${esc(formatDate(it.added))}</span>`;
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

  content.addEventListener('pointerover', e => { const t = e.target.closest('.tile'); if (t && !t._on) hoverOn(t, e, wantsLabel()); });
  content.addEventListener('pointerout', e => { const t = e.target.closest('.tile'); if (t && !t.contains(e.relatedTarget)) hoverOff(t); });
  content.addEventListener('focusin', e => { const t = e.target.closest('.tile'); if (t && !t._on) hoverOn(t, null, wantsLabel()); });
  content.addEventListener('focusout', e => { const t = e.target.closest('.tile'); if (t) hoverOff(t); });

  content.addEventListener('click', e => {
    const el = e.target.closest('.tile, .lrow, .cell');
    if (el && el._it) openDrawer(el._it.id);
  });
  content.addEventListener('keydown', e => {
    if (e.key !== 'Enter' && e.key !== ' ') return;
    const el = e.target.closest('.tile, .lrow, .cell');
    if (el && el._it) { e.preventDefault(); openDrawer(el._it.id); }
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
      <span class="m"><i class="kc ${it.kind}">${KIND_LABEL[it.kind]}</i>${esc(it.year || '')}</span>`;

    cell.append(stage, cap);
    grid.appendChild(cell);
  });

  return grid;
}

/**
 * The API's card shape, plus what only the browser needs: the short kind name
 * the CSS is written against, and a haystack to search.
 */
function prepareItems(list) {
  return list.map(it => ({
    ...it,
    kind: it.k,
    hay: `${it.artist} ${it.title} ${it.fmt}`.toLowerCase(),
  }));
}
