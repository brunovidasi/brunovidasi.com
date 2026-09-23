/* The shop's spotlight: the cover you clicked, stood up on the left while its
 * drawer opens on the right. Same choreography as the collection's own
 * (js/spotlight.js) — a copy lifts off where it was clicked and grows into
 * place, closing plays it backwards — but simpler: a listing has one picture,
 * not a sleeve with discs to fan out, so there is no tile to build, just an
 * image.
 *
 * Needs js/common.js (openDrawer calls into this by the global name
 * `Spotlight`) and css/spotlight.css for the shared layer/text styling;
 * css/selling.css adds the cover's own look.
 */

const Spotlight = (() => {
  const MIN_STAGE = 480; // narrower than this and there is no room beside the drawer
  const INFO_H = 110;    // what each block of text is expected to take
  const GAP = 28;         // between the cover and the text above and below it

  const clamp = (v, a, b) => Math.max(a, Math.min(b, v));
  const lerp = (a, b, t) => a + (b - a) * t;
  const reduced = () => matchMedia('(prefers-reduced-motion: reduce)').matches;
  const pose = (x, y, s) => `translate(${x.toFixed(1)}px,${y.toFixed(1)}px) scale(${s.toFixed(3)})`;

  let cur = null; // { id, source, box, layer, figure, L, closing, flight, timer, raf, hidden }

  /** The part of a clicked element that is the cover itself. */
  function boxOf(el) {
    if (!el) return null;
    if (el.classList.contains('sale-card')) return el.querySelector('.sale-cover');
    if (el.classList.contains('lrow')) return el.querySelector('.lthumb');
    return el;
  }

  function poseOf(box) {
    const r = box.getBoundingClientRect();
    return {
      cx: r.left + r.width / 2,
      cy: r.top + r.height / 2,
      size: box.offsetWidth,
      seen: r.bottom > 0 && r.top < innerHeight && r.right > 0 && r.left < innerWidth,
    };
  }

  /** The stage the cover stands on: the page less the drawer, square and centred. */
  function layout() {
    const lw = innerWidth - $('drawer').offsetWidth;
    if (lw < MIN_STAGE) return null;

    const vh = innerHeight;
    const margin = clamp(vh * 0.06, 28, 64);
    const availH = vh - margin * 2 - (INFO_H + GAP) * 2;
    if (availH < 140) return null;

    const padX = clamp(lw * 0.08, 28, 96);
    const size = Math.min(lw - padX * 2, availH, vh * 0.5, 460);
    return { lw, cx: padX + (lw - padX * 2) / 2, cy: vh / 2, size };
  }

  /** The cover's pose at one spot: it is laid out at its final size, so this only moves and scales it. */
  const at = (L, cx, cy, scale) => pose(cx - L.size / 2, cy - L.size / 2, scale);

  function infoHtml(it) {
    return `
      <div class="spot-info above">
        <p class="spot-eyebrow"><i class="kc ${esc(it.kind)}">${esc(KIND_LABEL[it.kind] || it.kind)}</i>${esc(it.regionName || '')}</p>
        <h2>${esc(it.title)}</h2>
      </div>
      <div class="sale-spot-cover">${it.spotCover ? `<img src="${esc(it.spotCover)}" alt="">` : ''}</div>
      <div class="spot-info below">
        <div class="spot-below-row">
          <div class="spot-below-text">
            <p class="spot-who">${esc(it.artist)}${it.year ? ' · ' + it.year : ''}</p>
            ${it.priceLabel ? `<p class="spot-price">${esc(it.priceLabel)}</p>` : ''}
          </div>
          ${it.ebay ? `<a class="spot-buy" href="${esc(it.ebay)}" target="_blank" rel="noopener">Buy on eBay ↗</a>` : ''}
        </div>
      </div>`;
  }

  /** Sizes and places the cover and its text against the layout. Measures the text, so it runs after the layer is in the page. */
  function place(a) {
    const { L, layer, figure } = a;
    const above = layer.querySelector('.spot-info.above');
    const below = layer.querySelector('.spot-info.below');

    figure.style.width = L.size.toFixed(1) + 'px';
    figure.style.height = L.size.toFixed(1) + 'px';

    const x0 = L.cx - L.size / 2;
    const textW = Math.min(Math.max(L.size, 340), L.lw - x0 - 24);
    for (const info of [above, below]) {
      info.style.left = x0.toFixed(1) + 'px';
      info.style.width = textW.toFixed(1) + 'px';
    }

    const top = L.cy - L.size / 2, bottom = L.cy + L.size / 2;
    above.style.bottom = (innerHeight - top + GAP).toFixed(1) + 'px';
    below.style.top = (bottom + GAP).toFixed(1) + 'px';

    document.body.style.setProperty('--spot-x', L.cx.toFixed(0) + 'px');
    document.body.style.setProperty('--spot-y', L.cy.toFixed(0) + 'px');
  }

  /* ---------- Opening ---------- */

  function open(id, source) {
    if (document.body.classList.contains('crate-on')) return false;
    if (cur && !cur.closing && cur.id === id) return true;

    const it = cardIndex.get(id);
    const cover = it && (it.cover || it.thumb);
    const L = it && cover && layout();
    if (!L) return false;

    if (cur) finish(cur);

    const box = boxOf(source);
    const from = box && box.isConnected ? poseOf(box) : null;
    const anim = !reduced();

    const layer = document.createElement('div');
    layer.className = 'spot';
    layer.setAttribute('aria-hidden', 'true');
    layer.innerHTML = infoHtml({ ...it, spotCover: cover });

    const figure = layer.querySelector('.sale-spot-cover');
    const a = cur = { id, source, box, layer, figure, L, closing: false, flight: null, timer: 0, raf: 0, hidden: [] };

    if (from) { box.style.visibility = 'hidden'; a.hidden.push(box); }

    $('drawer').before(layer);
    place(a); // in the page, so the text can be measured
    document.body.classList.add('spot-on');
    $('drawer').classList.add('spot-on');
    void layer.offsetWidth; // flush styles so the fades and the drawer's slide run

    const rest = at(L, L.cx, L.cy, 1);
    figure.style.transform = rest;

    if (!anim) return true;

    if (from) {
      const s0 = from.size / L.size;
      const dist = Math.hypot(from.cx - L.cx, from.cy - L.cy);
      const lift = 40 + Math.min(90, dist * 0.12);
      a.flight = figure.animate([
        { transform: at(L, from.cx, from.cy, s0) },
        { transform: at(L, (from.cx + L.cx) / 2, (from.cy + L.cy) / 2 - lift, (s0 + 1) / 2), offset: 0.45 },
        { transform: rest },
      ], { duration: 720 + Math.min(280, dist * 0.25), easing: 'cubic-bezier(.3,.05,.2,1)', fill: 'backwards' });
    } else {
      // nothing to fly from: it rises into place
      a.flight = figure.animate([
        { transform: at(L, L.cx, L.cy + 60, 0.94), opacity: 0 },
        { transform: rest, opacity: 1 },
      ], { duration: 650, easing: 'cubic-bezier(.2,.8,.25,1)', fill: 'backwards' });
    }

    return true;
  }

  /* ---------- Closing ---------- */

  /** The element the cover should land on: the one it came from, or whichever now shows it. */
  function homeOf(a) {
    if (a.box && a.box.isConnected) return a.box;
    for (const el of document.querySelectorAll('.sale-card, .lrow')) {
      if (!el._it || el._it.id !== a.id || el.closest('.spot')) continue;
      return boxOf(el);
    }
    return null;
  }

  function finish(a) {
    if (!a || a.done) return;
    a.done = true;
    clearTimeout(a.timer);
    cancelAnimationFrame(a.raf);
    a.figure.getAnimations().forEach(x => x.cancel());
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

  function close() {
    const a = cur;
    if (!a || a.closing) return;
    a.closing = true;
    clearTimeout(a.timer);
    if (reduced()) { finish(a); return; }

    a.layer.classList.add('leaving');

    const home = homeOf(a);
    const to = home ? poseOf(home) : null;

    let from = { cx: a.L.cx, cy: a.L.cy, scale: 1 };
    if (a.flight && a.flight.playState === 'running') {
      const m = new DOMMatrix(getComputedStyle(a.figure).transform);
      from = { cx: m.e + a.L.size / 2, cy: m.f + a.L.size / 2, scale: Math.hypot(m.a, m.b) };
    }
    a.figure.getAnimations().forEach(x => x.cancel());

    if (!to || !to.seen) {
      // nowhere to go (scrolled away, or filtered out): it sinks and fades
      a.flight = a.figure.animate([
        { transform: at(a.L, from.cx, from.cy, from.scale), opacity: 1 },
        { transform: at(a.L, a.L.cx, a.L.cy + 40, 0.94), opacity: 0 },
      ], { duration: 420, easing: 'cubic-bezier(.4,0,.2,1)', fill: 'forwards' });
      a.flight.finished.then(() => finish(a), () => {});
      a.timer = setTimeout(() => finish(a), 700);
      return;
    }

    if (home !== a.box) { home.style.visibility = 'hidden'; a.hidden.push(home); }

    const dist = Math.hypot(to.cx - from.cx, to.cy - from.cy);
    const duration = 700 + Math.min(280, dist * 0.22);
    const t0 = performance.now();
    let goal = to;

    const frame = now => {
      if (a.done) return;
      const t = clamp((now - t0) / duration, 0, 1);
      const u = t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;
      if (home.isConnected) goal = poseOf(home);

      const s1 = goal.size / a.L.size;
      a.figure.style.transform = at(a.L, lerp(from.cx, goal.cx, u), lerp(from.cy, goal.cy, u), lerp(from.scale, s1, u));

      if (t < 1) a.raf = requestAnimationFrame(frame);
      else finish(a);
    };
    a.raf = requestAnimationFrame(frame);
    a.timer = setTimeout(() => finish(a), duration + 400);
  }

  /* ---------- The rest of the page's dealings with it ---------- */

  function cover(url) {
    if (!cur || cur.closing) return;
    const img = cur.figure.querySelector('img') || new Image();
    img.alt = '';
    img.src = url;
    if (!img.isConnected) cur.figure.prepend(img);
  }

  /** A record whose drawer is already open (a link opened with #item-…) gets its spotlight now. */
  function adopt() {
    if (cur || currentItemId === null || !$('drawer').classList.contains('open')) return;
    open(currentItemId, null);
  }

  let resizeT = 0;
  addEventListener('resize', () => {
    clearTimeout(resizeT);
    resizeT = setTimeout(() => {
      if (!cur || cur.closing) return;
      const L = layout();
      if (!L) { finish(cur); return; } // no longer room for it: the drawer carries on alone
      cur.L = L;
      cur.figure.getAnimations().forEach(x => x.cancel());
      place(cur);
      cur.figure.style.transform = at(L, L.cx, L.cy, 1);
    }, 120);
  });

  return { open, close, cover, adopt };
})();
