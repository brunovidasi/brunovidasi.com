/* The spotlight: the record you clicked, stood up on the left while its drawer
 * opens on the right.
 *
 * Hovering a record slides a third of its disc out of the sleeve (reveal() in
 * js/tiles.js). Clicking carries that same state on: a copy of the sleeve lifts
 * off where it lies and travels to the bottom of the space the drawer leaves
 * free, growing as it goes, while the discs slide the rest of the way out and
 * spin. The title and the basics come in above it; everything else is the
 * drawer's. Closing plays it backwards, the record flying home to wherever the
 * page has it now.
 *
 * The copy is drawn from the record's card (a tile built by buildTile), not moved
 * from the page, so it works from a floor tile, a grid cell, a list row, or from
 * nowhere at all (a link opened with #item-…, or back/forward) where it simply
 * rises into place. It stays out of the crate, which already has its own way of
 * presenting the record it pulls out, and off screens too narrow to leave any
 * room beside the drawer.
 *
 * Needs js/common.js and js/tiles.js first; the drawer calls into it (open,
 * close, cover) if it is loaded, and does exactly what it always did if not.
 */

const Spotlight = (() => {
  const MIN_STAGE = 480; // narrower than this and there is no room beside the drawer
  const INFO_H = 130;    // what each block of text is expected to take, when first working out how big the record can be
  const GAP = 30;        // between the record and the text above and below it
  // How far a disc slides out: the first shows most of itself, later ones fan out behind it
  const OUT_BASE = 0.62, OUT_STEP = 0.36;

  const clamp = (v, a, b) => Math.max(a, Math.min(b, v));
  const lerp = (a, b, t) => a + (b - a) * t;
  const reduced = () => matchMedia('(prefers-reduced-motion: reduce)').matches;
  const pose = (x, y, r, s) => `translate(${x.toFixed(1)}px,${y.toFixed(1)}px) rotate(${r.toFixed(2)}deg) scale(${s.toFixed(3)})`;

  let cur = null; // the record on show: { it, source, layer, tile, L, flight, closing, … }

  /* ---------- Where things are ---------- */

  /** The part of a clicked element that is the record itself: a grid cell's tile, a list row's thumbnail. */
  function boxOf(el) {
    if (!el) return null;
    if (el.classList.contains('cell')) return el.querySelector('.tile');
    if (el.classList.contains('lrow')) return el.querySelector('.lthumb');
    return el;
  }

  /** Where a box is on screen and how it is turned and scaled, hover lift and pile tilt included. */
  function poseOf(box) {
    const r = box.getBoundingClientRect();
    const raw = getComputedStyle(box).transform;
    const m = raw && raw !== 'none' ? new DOMMatrix(raw) : new DOMMatrix();
    return {
      cx: r.left + r.width / 2,
      cy: r.top + r.height / 2,
      rot: Math.atan2(m.b, m.a) * 180 / Math.PI,
      size: box.offsetWidth * (Math.hypot(m.a, m.b) || 1),
      seen: r.bottom > 0 && r.top < innerHeight && r.right > 0 && r.left < innerWidth,
    };
  }

  /** How far out a disc's far edge reaches, in sleeve widths from the sleeve's own left edge. */
  const reachOf = (dd, k) => 1 + dd * (OUT_BASE + k * OUT_STEP);

  /**
   * The stage the record stands on: the page less the drawer, the record in the
   * exact middle of it and a block of text above and another below. Null when
   * there is no room for one. How tall the text turns out to be is only known once
   * it is on the page, so the size is checked against it, and trimmed, by place().
   */
  function layout(it) {
    const lw = innerWidth - $('drawer').offsetWidth;
    if (lw < MIN_STAGE) return null;

    const vh = innerHeight;
    const [w, h] = dims(it);
    const reach = Math.max(1, ...it.discs.map((d, k) => reachOf(d.t === 'v' ? 0.96 : 0.88, k)));

    const padX = clamp(lw * 0.07, 28, 96);
    const margin = clamp(vh * 0.06, 28, 64);
    const availH = vh - margin * 2 - (INFO_H + GAP) * 2;
    if (availH < 140) return null;

    // as big as the space allows, sleeve and fully drawn discs together
    const k = Math.min((lw - padX * 2) / (w * reach), Math.min(availH, vh * 0.5) / h, 3.4);
    return sized({ w, h, reach, padX, margin, lw, cy: vh / 2 }, k);
  }

  /** A layout at one scale: the record's size, and where it stands across the stage (centred, discs out). */
  function sized(L, k) {
    const rw = L.w * k, rh = L.h * k, gw = rw * L.reach;
    const x0 = L.padX + (L.lw - L.padX * 2 - gw) / 2;
    return Object.assign(L, { k, rw, rh, gw, x0, cx: x0 + rw / 2 });
  }

  /** The tile's pose at one spot: it is laid out at its final size, so this only moves and turns it. */
  const at = (L, cx, cy, rot, scale) => pose(cx - L.rw / 2, cy - L.rh / 2, rot, scale);

  /* ---------- Building it ---------- */

  /**
   * The text either side of the record: what it is and its title above; who made
   * it, when, and the pressing below. Rows arrive one after another (--n), top to bottom.
   */
  function infoHtml(it) {
    const rows = html => html.filter(Boolean);
    const number = (list, from) => list.map((row, n) => row.replace(/^<(\w+)/, `<$1 style="--n:${from + n}"`)).join('');

    const above = rows([
      `<p class="spot-eyebrow"><i class="kc ${esc(it.kind)}">${esc(KIND_LABEL[it.kind])}</i>${esc(it.regionName || '')}</p>`,
      `<h2>${esc(it.title)}</h2>`,
    ]);
    const below = rows([
      `<p class="spot-who">${esc(it.artist)}${it.year ? ' · ' + it.year : ''}</p>`,
      it.fmtRest ? `<p class="spot-rest">${esc(it.fmtRest)}</p>` : '',
    ]);
    return `<div class="spot-info above">${number(above, 0)}</div><div class="spot-info below">${number(below, above.length)}</div>`;
  }

  /**
   * A copy of the record as it looks in the hand: the same tile the shelf draws,
   * with its discs built and its picture upgraded to the full cover. If the record
   * was being hovered, the discs start where the hover left them.
   */
  function buildCopy(it, hovered, leftSide) {
    const t = buildTile(it);
    t.removeAttribute('tabindex');
    t.removeAttribute('role');
    t.removeAttribute('aria-label');
    t.classList.add('spot-tile');

    const img = t.querySelector('img');
    if (img) {
      img.loading = 'eager';
      // the sleeve on the shelf may be the small picture; this one is shown large
      if (it.cover && img.getAttribute('src') !== it.cover) {
        const big = new Image();
        big.onload = () => { img.src = it.cover; };
        big.src = it.cover;
      }
    }

    if (it.discs.length) {
      const wrap = document.createElement('div');
      wrap.className = 'discs';
      it.discs.forEach((d, k) => {
        const disc = discEl(it, k);
        disc.style.setProperty('--reach2', reachOf(d.t === 'v' ? 0.96 : 0.88, k).toFixed(3));
        wrap.appendChild(disc);
      });
      t.prepend(wrap);
      t._discs = wrap;
    }

    if (hovered) t.classList.add('hot');
    if (leftSide) t.classList.add('left');
    return t;
  }

  /**
   * Sizes and places everything for a layout: the record in the middle of the
   * page, the two blocks of text against it. The text is measured where it
   * stands, and if it would run off the page the record is made smaller to
   * leave room. Runs on open, with the layer already in the page, and again if
   * the window changes.
   */
  function place(a) {
    const { L, layer, tile } = a;
    const above = layer.querySelector('.spot-info.above');
    const below = layer.querySelector('.spot-info.below');

    for (let pass = 0; pass < 2; pass++) {
      tile.style.width = L.rw.toFixed(1) + 'px';
      tile.style.height = L.rh.toFixed(1) + 'px';
      tile.style.setProperty('--s', L.k.toFixed(3));

      for (const info of [above, below]) {
        info.style.left = L.x0.toFixed(1) + 'px';
        info.style.width = Math.min(Math.max(L.gw, 340), L.lw - L.x0 - 24).toFixed(1) + 'px';
      }

      // the record's half-height, and the taller block, must fit between the middle and the edge
      const room = innerHeight / 2 - L.margin - GAP - Math.max(above.offsetHeight, below.offsetHeight);
      if (L.rh / 2 <= room || pass) break;
      sized(L, L.k * Math.max(0.4, (room * 2) / L.rh));
    }

    const top = L.cy - L.rh / 2, bottom = L.cy + L.rh / 2;
    above.style.bottom = (innerHeight - top + GAP).toFixed(1) + 'px'; // grows upwards from the record
    below.style.top = (bottom + GAP).toFixed(1) + 'px';

    const floor = layer.querySelector('.spot-floor');
    floor.style.left = (L.x0 - L.gw * 0.04).toFixed(1) + 'px';
    floor.style.width = (L.gw * 1.08).toFixed(1) + 'px';
    floor.style.top = (bottom - 16).toFixed(1) + 'px';

    // the pool of light on the page behind
    document.body.style.setProperty('--spot-x', (L.x0 + L.gw / 2).toFixed(0) + 'px');
    document.body.style.setProperty('--spot-y', L.cy.toFixed(0) + 'px');
  }

  /* ---------- Opening ---------- */

  /**
   * Stands the record on show. Call it just before the drawer is opened, since it
   * sets up the drawer and the overlay for the fade too. `source` is the element
   * that was clicked, if any. Returns whether there is a spotlight.
   */
  function open(id, source) {
    if (document.body.classList.contains('crate-on')) return false;
    if (cur && !cur.closing && cur.it.id === id) return true;

    const it = cardIndex.get(id);
    const L = it && layout(it);
    if (!L) return false;

    if (cur) finish(cur); // another record was on show: it goes without a flight
    hideTip();

    const box = boxOf(source);
    const from = box && box.isConnected ? poseOf(box) : null;
    const hovered = Boolean(box && box.classList.contains('hot'));
    const anim = !reduced();

    const layer = document.createElement('div');
    layer.className = 'spot';
    layer.setAttribute('aria-hidden', 'true');
    layer.innerHTML = `<div class="spot-floor"></div>${infoHtml(it)}`;

    const tile = buildCopy(it, hovered, Boolean(box && box.classList.contains('left')));
    layer.appendChild(tile);

    const a = cur = { it, source, box, layer, tile, L, closing: false, flight: null, timer: 0, raf: 0, hidden: [] };

    // it stands in for the tile it came from until it is put back
    if (from) { box.style.visibility = 'hidden'; a.hidden.push(box); }

    $('drawer').before(layer);
    place(a); // in the page, so that the title can be measured
    document.body.classList.add('spot-on');
    $('drawer').classList.add('spot-on');
    void layer.offsetWidth; // flush styles so the fades and the drawer's slide run

    const rest = at(L, L.cx, L.cy, 0, 1);
    tile.style.transform = rest;

    if (!anim) {
      tile.classList.add('spot-out');
      return true;
    }

    if (from) {
      const s0 = from.size / L.rw;
      const dist = Math.hypot(from.cx - L.cx, from.cy - L.cy);
      const lift = 50 + Math.min(110, dist * 0.12);
      a.flight = tile.animate([
        { transform: at(L, from.cx, from.cy, from.rot, s0) },
        { transform: at(L, (from.cx + L.cx) / 2, (from.cy + L.cy) / 2 - lift, from.rot / 2, (s0 + 1) / 2), offset: 0.45 },
        { transform: rest },
      ], { duration: 820 + Math.min(320, dist * 0.25), easing: 'cubic-bezier(.3,.05,.2,1)', fill: 'backwards' });
    } else {
      // nothing to fly from: it rises into place
      a.flight = tile.animate([
        { transform: at(L, L.cx, L.cy + 70, 0, 0.94), opacity: 0 },
        { transform: rest, opacity: 1 },
      ], { duration: 700, easing: 'cubic-bezier(.2,.8,.25,1)', fill: 'backwards' });
    }

    // the discs carry on from the hover's third, partway through the flight
    a.timer = setTimeout(() => tile.classList.add('spot-out'), from ? 260 : 350);
    return true;
  }

  /* ---------- Closing ---------- */

  /** The element the record should land on: the one it came from, or whichever now shows it. */
  function homeOf(a) {
    if (a.box && a.box.isConnected) return a.box;
    for (const el of document.querySelectorAll('.tile, .lrow')) {
      if (!el._it || el._it.id !== a.it.id || el.closest('.crate-stage, .spot')) continue;
      return boxOf(el);
    }
    return null;
  }

  /** Takes it all away at once: the copy, and what the page was told to make room for it. */
  function finish(a) {
    if (!a || a.done) return;
    a.done = true;
    clearTimeout(a.timer);
    cancelAnimationFrame(a.raf);
    a.tile.getAnimations().forEach(x => x.cancel());
    a.layer.remove();
    a.hidden.forEach(el => { el.style.visibility = ''; });
    if (cur === a) {
      cur = null;
      document.body.classList.remove('spot-on');
      document.body.style.removeProperty('--spot-x');
      document.body.style.removeProperty('--spot-y');
      $('drawer').classList.remove('spot-on');
    }
  }

  /** The drawer is closing: the discs go back in and the record flies home. */
  function close() {
    const a = cur;
    if (!a || a.closing) return;
    a.closing = true;
    clearTimeout(a.timer);
    if (reduced()) { finish(a); return; }

    a.layer.classList.add('leaving');
    a.tile.classList.remove('spot-out', 'hot'); // the discs go all the way back in

    const home = homeOf(a);
    const to = home ? poseOf(home) : null;

    // wherever it is right now, which is not its resting place if it was still flying in
    let from = { cx: a.L.cx, cy: a.L.cy, rot: 0, scale: 1 };
    if (a.flight && a.flight.playState === 'running') {
      const m = new DOMMatrix(getComputedStyle(a.tile).transform);
      from = { cx: m.e + a.L.rw / 2, cy: m.f + a.L.rh / 2, rot: Math.atan2(m.b, m.a) * 180 / Math.PI, scale: Math.hypot(m.a, m.b) };
    }
    a.tile.getAnimations().forEach(x => x.cancel());

    if (!to || !to.seen) {
      // it has nowhere to go (scrolled away, or filtered out): it sinks and fades
      a.flight = a.tile.animate([
        { transform: at(a.L, from.cx, from.cy, from.rot, from.scale), opacity: 1 },
        { transform: at(a.L, a.L.cx, a.L.cy + 50, 0, 0.94), opacity: 0 },
      ], { duration: 450, easing: 'cubic-bezier(.4,0,.2,1)', fill: 'forwards' });
      a.flight.finished.then(() => finish(a), () => {});
      a.timer = setTimeout(() => finish(a), 700); // in case the animation never reports back
      return;
    }

    // the tile the record came from is under it again: put it back on the shelf
    if (home !== a.box) { home.style.visibility = 'hidden'; a.hidden.push(home); }

    // Every frame aims at where the tile is at that moment, not where it was when
    // the drawer began to close: the page can be scrolled while the record is in
    // the air, and a fixed destination would land it on the wrong spot.
    const dist = Math.hypot(to.cx - from.cx, to.cy - from.cy);
    const lift = 40 + Math.min(100, dist * 0.1);
    const duration = 760 + Math.min(300, dist * 0.22);
    const t0 = performance.now();
    let goal = to;

    const frame = now => {
      if (a.done) return;
      const t = clamp((now - t0) / duration, 0, 1);
      const u = t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;
      if (home.isConnected) goal = poseOf(home);

      const s1 = goal.size / a.L.rw;
      a.tile.style.transform = at(
        a.L,
        lerp(from.cx, goal.cx, u),
        lerp(from.cy, goal.cy, u) - lift * Math.sin(Math.PI * u),
        lerp(from.rot, goal.rot, u),
        lerp(from.scale, s1, u),
      );

      if (t < 1) a.raf = requestAnimationFrame(frame);
      else finish(a);
    };
    a.raf = requestAnimationFrame(frame);
    a.timer = setTimeout(() => finish(a), duration + 400); // a hidden tab doesn't run frames
  }

  /* ---------- The rest of the page's dealings with it ---------- */

  /** The drawer's gallery swapped the picture: the sleeve shows it too. */
  function cover(url) {
    if (!cur || cur.closing) return;
    const sleeve = cur.tile.querySelector('.sleeve');
    let img = sleeve.querySelector('img');
    if (!img) {
      img = new Image();
      img.alt = '';
      sleeve.prepend(img);
    }
    img.src = url;
  }

  /** Records that arrive after a drawer is already open (a link opened with #item-…) get their spotlight then. */
  function adopt() {
    if (cur || currentItemId === null || !$('drawer').classList.contains('open')) return;
    open(currentItemId, null);
  }

  let resizeT = 0;
  addEventListener('resize', () => {
    clearTimeout(resizeT);
    resizeT = setTimeout(() => {
      if (!cur || cur.closing) return;
      const L = layout(cur.it);
      if (!L) { finish(cur); return; } // no longer room for it: the drawer carries on alone
      cur.L = L;
      cur.tile.getAnimations().forEach(x => x.cancel());
      place(cur);
      cur.tile.style.transform = at(L, L.cx, L.cy, 0, 1);
    }, 120);
  });

  return { open, close, cover, adopt };
})();
