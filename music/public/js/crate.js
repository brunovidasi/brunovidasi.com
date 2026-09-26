/* The crate: the floor's records stood up in a wooden crate, in the order the
 * "Organise by" menu says, to flip through. Picking one pulls it out, its disc
 * rising out of the sleeve in the colour of the pressing, and opens the drawer.
 *
 * Getting in and out is one small flight engine (fly): the records on the
 * floor are thrown into the crate one after another, and tipped back out to
 * where the floor lays them. Shelf page only, driven by js/script.js. */

/* What "Organise by" offers: the sort (as js/tiles.js has it) and what the plate on the crate reads out. */
const initialOf = text => {
  const c = fold(text).replace(/^[^a-z0-9]+/, '')[0];
  return !c ? '#' : /\d/.test(c) ? '0–9' : c.toUpperCase();
};

const addedMonth = it => {
  const d = new Date(String(it.added).slice(0, 7) + '-01T00:00:00');
  return isNaN(d) ? 'Unknown' : d.toLocaleDateString(undefined, { month: 'long', year: 'numeric' });
};

const CRATE_ORDERS = {
  'artist':    { label: 'Artist, A–Z',                sort: { key: 'artist', dir: 'asc' },  group: it => it.artist },
  'date-asc':  { label: 'Release year, oldest first', sort: { key: 'date', dir: 'asc' },    group: it => it.year || 'Year unknown' },
  'date-desc': { label: 'Release year, newest first', sort: { key: 'date', dir: 'desc' },   group: it => it.year || 'Year unknown' },
  'title':     { label: 'Title, A–Z',                 sort: { key: 'title', dir: 'asc' },   group: it => initialOf(it.title) },
  'kind':      { label: 'Format',                     sort: { key: 'kind', dir: 'asc' },    group: it => KIND_LABEL[it.kind] },
  'region':    { label: 'Region',                     sort: { key: 'region', dir: 'asc' },  group: it => it.regionName || it.region || 'Region unknown' },
  'added':     { label: 'Recently added',             sort: { key: 'added', dir: 'desc' },  group: it => addedMonth(it) },
};

const DEFAULT_CRATE_ORDER = 'artist';

const Crate = (() => {
  const content = $('content');

  const MAX_FLYERS = 64;
  const LOOK_AHEAD = 14; // how many records behind the front one are drawn

  let stage = null, box, scene, front, flyersEl;
  let recs = [], els = [], groups = [], groupSize = new Map(), orderKey = DEFAULT_CRATE_ORDER;
  const elCache = new Map();
  let pos = 0, target = 0, pull = 0, pullT = 0, raf = 0, shown = -1;
  let winLo = 0, winHi = -1, entering = false;
  let busy = false, gen = 0, pending = null, snapT = 0, digT = 0;
  const idle = [];

  /** Whether a crate is on the page right now (something else may have replaced it). */
  const alive = () => Boolean(stage && stage.isConnected);

  function settle() {
    busy = false;
    if (pending && alive()) {
      const { list, key } = pending;
      pending = null;
      setOrder(list, key);
    }
    pending = null;
    idle.splice(0).forEach(fn => fn());
  }

  /* ---------- One record in the crate ---------- */

  /** A record's width in the crate against a 12": real proportions, but nothing too small to read. */
  const crateWidth = it => clamp(dims(it)[0] / 250, 0.6, 1);

  function makeRec(it) {
    const [w, h] = dims(it);
    const f = crateWidth(it);
    const el = document.createElement('div');
    el.className = `rec kind-${it.shape}`;
    el.style.setProperty('--fw', f.toFixed(3));
    el.style.setProperty('--fh', (f * h / w).toFixed(3));
    el.innerHTML = '<div class="rec-body"><div class="discs"></div><div class="face"></div></div>';
    el._it = it;
    return el;
  }

  function recFor(it) {
    let el = elCache.get(it.id);
    if (!el) elCache.set(it.id, el = makeRec(it));
    return el;
  }

  /** The cover goes on only as a record nears the front, so 500 of them cost nothing up front. */
  function paint(el) {
    if (el._painted) return;
    el._painted = true;
    const it = el._it;
    const face = el.querySelector('.face');
    face.style.setProperty('--fb', sleeveFallback(it));

    const caption = () => face.insertAdjacentHTML('beforeend', `<span class="ctext"><b>${esc(it.title)}</b>${esc(it.artist)}</span>`);
    const src = it.cover || it.thumb;
    if (!src) { caption(); return; }

    const img = new Image();
    img.alt = '';
    img.decoding = 'async';
    img.addEventListener('error', () => { img.remove(); caption(); });
    img.src = src;
    face.appendChild(img);
  }

  /** The disc in its own colour, built the same way as the ones that slide out on the floor. */
  function addDiscs(el) {
    if (el._discs) return;
    el._discs = true;
    const wrap = el.querySelector('.discs');
    el._it.discs.forEach((_, k) => wrap.appendChild(discEl(el._it, k)));
  }

  /* ---------- Laying the crate out ---------- */

  function layout() {
    const n = els.length;
    if (!n) return;
    const cur = clamp(Math.round(target), 0, n - 1);
    const lo = clamp(Math.floor(pos) - 1, 0, n - 1);
    const hi = clamp(Math.ceil(pos) + LOOK_AHEAD, 0, n - 1);

    for (let i = winLo; i <= winHi; i++) {
      if ((i < lo || i > hi) && els[i]) els[i].style.display = 'none';
    }
    winLo = lo;
    winHi = hi;

    for (let i = lo; i <= hi; i++) {
      const el = els[i];
      const d = i - pos;

      if (!el.isConnected) {
        scene.appendChild(el);
        if (entering) {
          el.classList.add('enter');
          // Back to front, so the front record lands last.
          el.style.setProperty('--i', Math.max(0, 12 - i));
          el.addEventListener('animationend', () => el.classList.remove('enter'), { once: true });
        }
      }
      el.style.display = '';
      paint(el);

      let ty, tz, rx = 0, op = 1;
      if (d >= 0) {
        ty = -d * 30;
        tz = -d * 70;
        op = 1 - Math.max(0, d - 9) / 5;
      } else {
        const t = Math.min(1, -d);
        ty = t * 40;
        tz = 0;
        rx = -t * 82;
        op = 1 - t * t;
      }

      // The record being held comes up out of the crate, and its disc with it.
      const w = pull * Math.max(0, 1 - Math.abs(d) * 2);
      if (w > 0.002) addDiscs(el);

      el.style.transform = `translate3d(0,calc(${ty.toFixed(2)}px - var(--cw) * ${(w * 0.26).toFixed(4)}),${tz.toFixed(2)}px) rotateX(${rx.toFixed(2)}deg) scale(${(1 + 0.12 * w).toFixed(4)})`;
      el.style.opacity = op.toFixed(3);
      el.style.zIndex = d < 0 ? 700 : 600 - i;
      el.style.setProperty('--pull', w.toFixed(3));
      el.classList.toggle('pulled', w > 0.02);
    }

    if (cur !== shown) {
      shown = cur;
      updateInfo();
    }
  }

  function updateInfo() {
    const it = recs[shown];
    if (!it || !alive()) return;
    const q = sel => stage.querySelector(sel);
    const label = groups[shown];
    const count = groupSize.get(label);

    q('.plate b').textContent = label;
    q('.plate span').textContent = `${count} ${count === 1 ? 'record' : 'records'}`;
    q('.crate-info .eyebrow').textContent = `${shown + 1} of ${recs.length}`;
    q('.crate-info h2').textContent = it.title;
    q('.crate-info .who').textContent = `${it.artist}${it.year ? ' · ' + it.year : ''}`;
    q('.crate-info .what').innerHTML = `<i class="kc ${esc(it.kind)}">${esc(KIND_LABEL[it.kind])}</i>${esc(it.fmtRest)}`;
  }

  function tick() {
    pos += (target - pos) * 0.14;
    pull += (pullT - pull) * 0.16;
    if (Math.abs(target - pos) < 0.002) pos = target;
    if (Math.abs(pullT - pull) < 0.002) pull = pullT;
    layout();
    raf = (pos !== target || pull !== pullT) ? requestAnimationFrame(tick) : 0;
  }

  function kick() {
    target = clamp(target, 0, Math.max(0, els.length - 1));
    if (!raf) raf = requestAnimationFrame(tick);
  }

  function flipTo(index) {
    pullT = 0;
    target = index;
    kick();
  }

  /** Sorts the records into the crate and starts them off at the front. */
  function setOrder(list, key, base = 0) {
    orderKey = CRATE_ORDERS[key] ? key : DEFAULT_CRATE_ORDER;
    const order = CRATE_ORDERS[orderKey];

    els.forEach(el => el.classList.remove('enter'));
    scene.replaceChildren();

    recs = sortList(list, order.sort);
    groups = recs.map(it => String(order.group(it)));
    groupSize = new Map();
    groups.forEach(g => groupSize.set(g, (groupSize.get(g) || 0) + 1));
    els = recs.map((it, i) => Object.assign(recFor(it), { _i: i }));

    winLo = 0;
    winHi = -1;
    shown = -1;
    pos = target = pull = pullT = 0;
    stage.style.setProperty('--base', base + 'ms');

    entering = true;
    layout();
    entering = false;
  }

  /* ---------- Picking one ---------- */

  function select() {
    const it = recs[clamp(Math.round(target), 0, recs.length - 1)];
    if (!it || busy) return;
    pullT = 1;
    kick();
    openDrawer(it.id);
  }

  /** "Dig a random one": riffle to it, pull it out, and open it once it is up. */
  function dig() {
    if (!alive() || busy || !els.length) return;
    const from = Math.round(target);
    let i = Math.floor(Math.random() * els.length);
    if (els.length > 1 && i === from) i = (i + 1) % els.length;

    // A long way off would only be a blur of covers loading: start close instead.
    if (Math.abs(i - pos) > 8) pos = i + (i > pos ? -8 : 8);

    flipTo(i);
    clearTimeout(digT);
    digT = setTimeout(() => { if (alive() && Math.round(target) === i) select(); }, 1000);
  }

  /* ---------- Mounting ---------- */

  function buildStage() {
    const el = document.createElement('div');
    el.className = 'crate-stage';
    el.innerHTML = `
      <div class="crate-box" tabindex="0" role="group" aria-label="Record crate. Scroll, drag or use the arrow keys to flip through the records, Enter to pull one out.">
        <div class="crate-scene"></div>
        <div class="crate-flyers" aria-hidden="true"></div>
        <div class="crate-front">
          <i class="handle"></i><i class="handle r"></i>
          <div class="plate"><b></b><span></span></div>
        </div>
      </div>
      <div class="crate-info" aria-live="polite">
        <p class="eyebrow"></p>
        <h2></h2>
        <p class="who"></p>
        <p class="what"></p>
        <button type="button" class="crate-btn" data-open>Pull it out</button>
        <p class="hint">Scroll, drag or use ← → to flip through the crate. Click the front record to pull it out.</p>
      </div>`;
    return el;
  }

  function wire() {
    box.addEventListener('wheel', e => {
      e.preventDefault();
      if (busy) return;
      target += (e.deltaY + e.deltaX) / 170;
      pullT = 0;
      kick();
      clearTimeout(snapT);
      snapT = setTimeout(() => { target = Math.round(target); kick(); }, 140);
    }, { passive: false });

    box.addEventListener('pointerdown', e => {
      if (busy) return;
      drag = { x: e.clientX, y: e.clientY, t: target };
      moved = 0;
    });

    box.addEventListener('click', e => {
      const rec = e.target.closest('.rec');
      if (!rec || moved > 6 || busy) return;
      // A record further back comes forward first; the front one is pulled out.
      if (rec._i === clamp(Math.round(target), 0, els.length - 1)) select();
      else flipTo(rec._i);
    });

    stage.querySelector('[data-open]').addEventListener('click', select);
  }

  /** Puts a crate on the page. `over` lays it on top of what is there so the two can cross-fade. */
  function mount(over) {
    stage = buildStage();
    box = stage.querySelector('.crate-box');
    scene = stage.querySelector('.crate-scene');
    front = stage.querySelector('.crate-front');
    flyersEl = stage.querySelector('.crate-flyers');
    wire();

    document.body.classList.add('crate-on');
    if (over) {
      stage.classList.add('over');
      content.classList.add('crating');
      content.appendChild(stage);
    } else {
      content.replaceChildren(stage);
    }
  }

  function teardown() {
    gen++;
    cancelAnimationFrame(raf);
    clearTimeout(snapT);
    clearTimeout(digT);
    raf = 0;
    if (stage) stage.remove();
    stage = null;
    recs = els = groups = [];
    elCache.clear();
    content.classList.remove('crating');
    document.body.classList.remove('crate-on');
  }

  /* ---------- The flight ---------- */

  /** What the engine needs about the crate on one frame, read once so 60 flyers don't each ask. */
  function frameContext() {
    return {
      rect: box.getBoundingClientRect(),
      sx: scrollX,
      sy: scrollY,
      mid: box.offsetWidth / 2,
      lip: front.offsetTop,
    };
  }

  /**
   * A record about to fly: a copy of its sleeve in the crate's flyer layer, and
   * what the engine needs to place it. `doc` is where it lies on the floor, in
   * page coordinates, so it stays put while the page scrolls.
   */
  function flyer({ tile, rect, w, h, rot }, dir, cw) {
    const it = tile._it;
    const el = buildTileCopy(it);
    el.classList.add('flyer');
    el.style.width = w + 'px';
    el.style.height = h + 'px';
    flyersEl.appendChild(el);

    return {
      el, tile, dir, w, h, rot,
      doc: { x: rect.left + rect.width / 2 + scrollX, y: rect.top + rect.height / 2 + scrollY },
      // It goes into the crate at the size the crate's records are.
      sm: clamp((cw * crateWidth(it)) / w * 0.85, 0.3, 1.4),
      jx: (((hash(String(it.id)) >>> 5) % 1000) / 500 - 1) * cw * 0.28,
      vinyl: it.k === 'vinyl',
      hot: false,
    };
  }

  /** The position and pose of one flyer at one moment, written straight to its transform. */
  function place(f, t, ctx) {
    const { rect, sx, sy, mid, lip } = ctx;
    const u = easeInOutCubic(clamp(t, 0, 1));
    const floorPt = { x: f.doc.x - sx, y: f.doc.y - sy };
    // Just past the crate's lip, so it ends up mostly hidden behind the front board.
    const mouthPt = { x: rect.left + mid + f.jx, y: rect.top + lip + f.h * f.sm * 0.15 };
    const inward = f.dir === 'in';
    const [a, b] = inward ? [floorPt, mouthPt] : [mouthPt, floorPt];
    const [r0, r1] = inward ? [f.rot, 0] : [0, f.rot];
    const [s0, s1] = inward ? [1, f.sm] : [f.sm, 1];

    const x = lerp(a.x, b.x, u);
    const y = lerp(a.y, b.y, u) - f.lift * Math.sin(Math.PI * u);
    const c = clamp(t, 0, 1);
    const alpha = inward ? (c > 0.9 ? (1 - c) / 0.1 : 1) : (c < 0.1 ? c / 0.1 : 1);

    f.el.style.transform = `translate(${(x - rect.left - f.w / 2).toFixed(1)}px,${(y - rect.top - f.h / 2).toFixed(1)}px) rotate(${lerp(r0, r1, u).toFixed(2)}deg) scale(${lerp(s0, s1, u).toFixed(3)})`;
    f.el.style.opacity = alpha.toFixed(3);
  }

  /** Runs the flyers to the end. A vinyl's discs slide out for the flight and go back in before it lands. */
  function fly(flyers, onDone) {
    const mine = gen;
    const t0 = performance.now();
    let left = flyers.length;
    if (!left) { onDone(); return; }

    const frame = now => {
      if (mine !== gen) return;
      const ctx = frameContext();

      for (const f of flyers) {
        if (f.gone) continue;
        const t = (now - t0 - f.delay) / f.dur;

        if (f.vinyl) {
          const want = t >= 0 && t < f.hotEnd;
          if (want !== f.hot) {
            f.hot = want;
            if (want) reveal(f.el); else f.el.classList.remove('hot');
          }
        }

        if (t >= 1) {
          f.gone = true;
          f.el.remove();
          if (f.dir === 'out') f.tile.style.visibility = '';
          left--;
        } else {
          place(f, t, ctx);
        }
      }

      if (left > 0) requestAnimationFrame(frame);
      else onDone();
    };
    requestAnimationFrame(frame);
  }

  /** The tiles on screen as flyers, nearest the crate first, up to MAX_FLYERS. The rest fade with the floor. */
  function takeOff(floor, dir, mess) {
    const ctx = frameContext();
    const vw = innerWidth, vh = innerHeight;
    const mx = ctx.rect.left + ctx.mid, my = ctx.rect.top + ctx.lip;
    const cw = box.offsetWidth / 1.6;

    // Every read first, so the page is laid out once rather than once per record.
    const seen = [];
    for (const tile of floor.children) {
      const rect = tile.getBoundingClientRect();
      if (rect.bottom < -40 || rect.top > vh + 40 || rect.right < -40 || rect.left > vw + 40) continue;
      seen.push({
        tile, rect,
        w: tile.offsetWidth,
        h: tile.offsetHeight,
        rot: parseFloat(tile.style.getPropertyValue('--r') || 0) * mess,
        dist: Math.hypot(rect.left + rect.width / 2 - mx, rect.top + rect.height / 2 - my),
      });
    }
    seen.sort((a, b) => a.dist - b.dist);
    seen.length = Math.min(seen.length, MAX_FLYERS);

    const spread = dir === 'in' ? 560 : 520;
    const flyers = seen.map((s, i) => {
      const f = flyer(s, dir, cw);
      f.delay = 30 + (i / Math.max(1, seen.length - 1)) * spread;
      f.dur = 720 + Math.min(500, s.dist * 0.4);
      f.lift = 70 + Math.min(150, s.dist * 0.14);
      f.hotEnd = dir === 'in' ? 1 : 0.65;
      // The flyer takes the tile's place, drawn exactly on it.
      s.tile.style.visibility = 'hidden';
      return f;
    });

    flyers.forEach(f => place(f, 0, ctx));
    return flyers;
  }

  /* ---------- Coming and going ---------- */

  /** Scrolls the controls to the top of the screen, or further if that would leave the crate cut off. */
  function scrollToShelf() {
    const bar = document.querySelector('.controls').getBoundingClientRect().top + scrollY - 12;
    const bottom = box.getBoundingClientRect().bottom + scrollY - innerHeight + 16;
    const top = Math.max(0, bar, bottom);
    if (Math.abs(top - scrollY) > 4) scrollTo({ top, behavior: prefersReducedMotion() ? 'auto' : 'smooth' });
  }

  /** Floor to crate: the mess on screen is thrown into the crate, and the crate fills. */
  function enter(list, key, mess) {
    if (busy || alive()) return;
    busy = true;
    hideTip();

    const floor = content.querySelector('.floor');
    const animate = Boolean(floor) && !prefersReducedMotion();
    const mine = gen;

    mount(animate);
    setOrder(list, key, animate ? 850 : 0);
    scrollToShelf();

    if (!animate) { settle(); return; }

    stage.classList.add('arriving');
    const flyers = takeOff(floor, 'in', mess);

    // Whatever was not thrown fades out under the flight.
    floor.style.transition = 'opacity .5s ease .15s';
    floor.style.opacity = 0;

    fly(flyers, () => {
      if (mine !== gen) return;
      floor.remove();
      stage.classList.remove('over', 'arriving');
      content.classList.remove('crating');
      settle();
    });
  }

  /** Crate to floor: the records are tipped out and land where the floor lays them. */
  function exit(list, mess) {
    if (busy || !alive()) return;
    busy = true;
    clearTimeout(digT);
    pullT = 0;

    const old = stage;
    const mine = gen;

    if (prefersReducedMotion()) {
      teardown();
      content.replaceChildren(floorEl(list, mess));
      settle();
      return;
    }

    // The floor goes in under the crate, hidden, so its tiles can be measured.
    const floor = floorEl(list, mess);
    floor.style.opacity = 0;
    old.classList.add('over');
    content.classList.add('crating');
    content.insertBefore(floor, old);

    const flyers = takeOff(floor, 'out', mess);

    // The records in the crate lift out of it, front ones first.
    const here = Math.round(pos);
    els.forEach((el, i) => el.style.setProperty('--i', Math.min(12, Math.abs(i - here))));
    old.classList.add('emptying');

    // The floor's own fade-in starts once it has been measured.
    void floor.offsetWidth;
    floor.style.transition = 'opacity .7s ease .3s';
    floor.style.opacity = 1;

    setTimeout(() => { if (mine === gen) old.classList.add('leaving'); }, 650);

    fly(flyers, () => {
      if (mine !== gen) return;
      floor.style.transition = floor.style.opacity = '';
      teardown();
      settle();
    });
  }

  /** A crate without the flight: for when the records behind it change, or it has to come back. */
  function show(list, key) {
    if (busy) {
      if (alive()) pending = { list, key };
      return;
    }
    if (alive()) {
      setOrder(list, key);
      return;
    }
    teardown();
    mount(false);
    setOrder(list, key);
  }

  /* ---------- Input ---------- */

  let drag = null, moved = 0;

  addEventListener('pointermove', e => {
    if (!drag || !alive()) return;
    const dx = e.clientX - drag.x, dy = e.clientY - drag.y;
    moved = Math.max(moved, Math.hypot(dx, dy));
    if (moved > 6) {
      target = drag.t + (dy - dx) / 70;
      pullT = 0;
      kick();
    }
  });

  addEventListener('pointerup', () => {
    if (drag && moved > 6 && alive()) { target = Math.round(target); kick(); }
    drag = null;
    setTimeout(() => { moved = 0; }, 0);
  });

  addEventListener('keydown', e => {
    if (!alive() || busy || e.ctrlKey || e.metaKey || e.altKey) return;
    if ($('drawer').classList.contains('open')) return;
    // Keys belong to the search box, the menus and the buttons when they have the focus.
    if (e.target !== document.body && !box.contains(e.target)) return;

    const cur = clamp(Math.round(target), 0, els.length - 1);
    switch (e.key) {
      case 'ArrowRight': case 'ArrowDown': e.preventDefault(); flipTo(cur + 1); break;
      case 'ArrowLeft': case 'ArrowUp': e.preventDefault(); flipTo(cur - 1); break;
      case 'Enter': e.preventDefault(); select(); break;
      case 'Escape': pullT = 0; kick(); break;
    }
  });

  // Closing the drawer puts the record back in the crate.
  new MutationObserver(() => {
    if (alive() && pullT && !$('drawer').classList.contains('open')) { pullT = 0; kick(); }
  }).observe($('drawer'), { attributes: true, attributeFilter: ['class'] });

  return {
    enter,
    exit,
    show,
    dig,
    destroy() { teardown(); settle(); },
    get busy() { return busy; },
    /** Runs `fn` once no flight is in progress (now, if none is). */
    whenIdle(fn) { if (busy) idle.push(fn); else fn(); },
  };
})();
