/* The spotlight's flight, shared by the collection (js/spotlight.js) and the
 * shop (js/sale-spotlight.js): the thing clicked is copied, lifts off where it
 * lies and flies to the middle of the space the drawer leaves, growing as it
 * goes; closing flies it home to wherever the page has it now. From nowhere
 * (a link opened with #item-…) it simply rises into place.
 *
 * `look` says what is stood up and how it moves:
 *   layout(it)           the stage, or null when there is no room
 *   size(L)              [width, height] of the piece at rest
 *   build(it, box, layer)  fills the layer, and returns the element that flies
 *   place(a)             sizes and places the piece and its text
 *   boxOf(el)            the part of a clicked element that is the record
 *   homes, notIn         where the record can land when it goes home, and where it can't
 *   turns                whether the piece keeps the pile's tilt as it flies
 *   open, rise, close, sink  the flights' timings, as below
 *   opened(a, animated), closing(a), cover(a, url)  what else happens
 */

const MIN_SPOT_STAGE = 480; // narrower than this and there is no room beside the drawer

function makeSpotlight(look) {
  let cur = null;

  const pose = (x, y, r, s) => (look.turns
    ? `translate(${x.toFixed(1)}px,${y.toFixed(1)}px) rotate(${r.toFixed(2)}deg) scale(${s.toFixed(3)})`
    : `translate(${x.toFixed(1)}px,${y.toFixed(1)}px) scale(${s.toFixed(3)})`);

  /** The piece's pose with its centre at (cx, cy): it is laid out at its resting size, so this only moves, turns and scales it. */
  const at = (L, cx, cy, rot, scale) => {
    const [w, h] = look.size(L);
    return pose(cx - w / 2, cy - h / 2, rot, scale);
  };

  /** Where a box is on screen, and how it is turned and scaled (a hover's lift and the pile's tilt included). */
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

  function layoutFor(it) {
    return it && !document.body.classList.contains('crate-on') ? look.layout(it) : null;
  }

  /**
   * Stands the record up. Called just before the drawer opens, since it sets
   * the drawer and the overlay up for the fade too. Returns whether it did.
   */
  function open(id, source) {
    if (document.body.classList.contains('crate-on')) return false;
    if (cur && !cur.closing && cur.it.id === id) return true;

    const it = cardIndex.get(id);
    const L = layoutFor(it);
    if (!L) return false;

    if (cur) finish(cur); // another record was up: it goes without a flight
    hideTip();

    const box = look.boxOf(source);
    const from = box && box.isConnected ? poseOf(box) : null;

    const layer = document.createElement('div');
    layer.className = 'spot';
    layer.setAttribute('aria-hidden', 'true');
    const piece = look.build(it, box, layer);

    const a = cur = { it, source, box, layer, piece, L, closing: false, flight: null, timer: 0, raf: 0, hidden: [] };

    // It stands in for the tile it came from until it is put back.
    if (from) { box.style.visibility = 'hidden'; a.hidden.push(box); }

    $('drawer').before(layer);
    look.place(a); // in the page, so the text can be measured
    document.body.classList.add('spot-on');
    $('drawer').classList.add('spot-on');
    void layer.offsetWidth; // flush styles so the fades and the drawer's slide run

    const rest = at(L, L.cx, L.cy, 0, 1);
    piece.style.transform = rest;

    const animated = !prefersReducedMotion();
    if (animated) {
      const [w] = look.size(L);
      if (from) {
        const s0 = from.size / w;
        const dist = Math.hypot(from.cx - L.cx, from.cy - L.cy);
        const lift = look.open.lift[0] + Math.min(look.open.lift[1], dist * 0.12);
        a.flight = piece.animate([
          { transform: at(L, from.cx, from.cy, from.rot, s0) },
          { transform: at(L, (from.cx + L.cx) / 2, (from.cy + L.cy) / 2 - lift, from.rot / 2, (s0 + 1) / 2), offset: 0.45 },
          { transform: rest },
        ], { duration: look.open.duration[0] + Math.min(look.open.duration[1], dist * 0.25), easing: 'cubic-bezier(.3,.05,.2,1)', fill: 'backwards' });
      } else {
        a.flight = piece.animate([
          { transform: at(L, L.cx, L.cy + look.rise.drop, 0, 0.94), opacity: 0 },
          { transform: rest, opacity: 1 },
        ], { duration: look.rise.duration, easing: 'cubic-bezier(.2,.8,.25,1)', fill: 'backwards' });
      }
    }

    look.opened?.(a, animated, Boolean(from));
    return true;
  }

  /** Whether a record would get a spotlight now, rather than the drawer alone. */
  function fits(id) {
    return Boolean(layoutFor(cardIndex.get(id)));
  }

  /** The element the record lands on: the one it came from, or whichever shows it now. */
  function homeOf(a) {
    if (a.box && a.box.isConnected) return a.box;
    for (const el of document.querySelectorAll(look.homes)) {
      if (!el._it || el._it.id !== a.it.id || el.closest(look.notIn)) continue;
      return look.boxOf(el);
    }
    return null;
  }

  /** Takes it all away at once. */
  function finish(a) {
    if (!a || a.done) return;
    a.done = true;
    clearTimeout(a.timer);
    cancelAnimationFrame(a.raf);
    a.piece.getAnimations().forEach(x => x.cancel());
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

  /** The drawer is closing: the record flies home. */
  function close() {
    const a = cur;
    if (!a || a.closing) return;
    a.closing = true;
    clearTimeout(a.timer);
    if (prefersReducedMotion()) { finish(a); return; }

    a.layer.classList.add('leaving');
    look.closing?.(a);

    const home = homeOf(a);
    const to = home ? poseOf(home) : null;
    const [w, h] = look.size(a.L);

    // Where it is right now, which is not its resting place if it was still flying in.
    let from = { cx: a.L.cx, cy: a.L.cy, rot: 0, scale: 1 };
    if (a.flight && a.flight.playState === 'running') {
      const m = new DOMMatrix(getComputedStyle(a.piece).transform);
      from = { cx: m.e + w / 2, cy: m.f + h / 2, rot: Math.atan2(m.b, m.a) * 180 / Math.PI, scale: Math.hypot(m.a, m.b) };
    }
    a.piece.getAnimations().forEach(x => x.cancel());

    if (!to || !to.seen) {
      // Nowhere to go (scrolled away, or filtered out): it sinks and fades.
      a.flight = a.piece.animate([
        { transform: at(a.L, from.cx, from.cy, from.rot, from.scale), opacity: 1 },
        { transform: at(a.L, a.L.cx, a.L.cy + look.sink.drop, 0, 0.94), opacity: 0 },
      ], { duration: look.sink.duration, easing: 'cubic-bezier(.4,0,.2,1)', fill: 'forwards' });
      a.flight.finished.then(() => finish(a), () => {});
      a.timer = setTimeout(() => finish(a), 700); // in case the animation never reports back
      return;
    }

    // The tile it came from is under it again: put it back on the shelf.
    if (home !== a.box) { home.style.visibility = 'hidden'; a.hidden.push(home); }

    // Every frame aims at where the tile is at that moment, since the page can
    // scroll while the record is in the air.
    const dist = Math.hypot(to.cx - from.cx, to.cy - from.cy);
    const lift = look.close.lift(dist);
    const duration = look.close.duration[0] + Math.min(look.close.duration[1], dist * 0.22);
    const t0 = performance.now();
    let goal = to;

    const frame = now => {
      if (a.done) return;
      const t = clamp((now - t0) / duration, 0, 1);
      const u = easeInOutCubic(t);
      if (home.isConnected) goal = poseOf(home);

      a.piece.style.transform = at(
        a.L,
        lerp(from.cx, goal.cx, u),
        lerp(from.cy, goal.cy, u) - lift * Math.sin(Math.PI * u),
        lerp(from.rot, goal.rot, u),
        lerp(from.scale, goal.size / w, u),
      );

      if (t < 1) a.raf = requestAnimationFrame(frame);
      else finish(a);
    };
    a.raf = requestAnimationFrame(frame);
    a.timer = setTimeout(() => finish(a), duration + 400); // a hidden tab doesn't run frames
  }

  /** The drawer's gallery swapped the picture: the piece shows it too. */
  function cover(url) {
    if (cur && !cur.closing) look.cover(cur, url);
  }

  /** A drawer opened from a link, before its record arrived, gets its spotlight now. */
  function adopt() {
    if (cur || currentItemId === null || !$('drawer').classList.contains('open')) return;
    open(currentItemId, null);
  }

  let resizeTimer = 0;
  addEventListener('resize', () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => {
      if (!cur || cur.closing) return;
      const L = look.layout(cur.it);
      if (!L) { finish(cur); return; } // no room any more: the drawer carries on alone
      cur.L = L;
      cur.piece.getAnimations().forEach(x => x.cancel());
      look.place(cur);
      cur.piece.style.transform = at(L, L.cx, L.cy, 0, 1);
    }, 120);
  });

  return { open, close, cover, adopt, fits };
}
