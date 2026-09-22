/* Floor <-> grid: the same records, carried from where one view lays them to
 * where the other does, instead of the page cutting from one to the other.
 *
 * The two views are two draws of the same items, so nothing is kept alive
 * between them: capture() notes where each record on screen is before the view
 * is redrawn, and play() then sets each record of the new view going from that
 * spot to its own. It is the crate's throw (js/crate.js) without a crate: a
 * short arc, the pile's tilt straightening out or settling in, and the vinyl
 * ones sliding their discs out, in the pressing's colour, on the way.
 *
 * Main page only, like the crate. Needs js/common.js and js/tiles.js first.
 */

const Morph = (() => {
  const MAX = 64; // records that travel; any more just fade in with the rest

  const reduced = () => matchMedia('(prefers-reduced-motion: reduce)').matches;
  const kindOf = el => (el.querySelector(':scope > .floor') ? 'floor' : el.querySelector(':scope > .grid') ? 'grid' : null);

  const onScreen = r => r.bottom > -40 && r.top < innerHeight + 40 && r.right > -40 && r.left < innerWidth + 40;

  /** How far a tile is turned: the pile scatters them, the grid stands them straight. */
  const tiltOf = (tile, kind) => {
    if (kind !== 'floor') return 0;
    const mess = parseFloat(tile.parentElement.style.getPropertyValue('--mess')) || 0;
    return (parseFloat(tile.style.getPropertyValue('--r')) || 0) * mess;
  };

  /** Where every record on screen is now. Call it just before the view is redrawn. */
  function capture(container) {
    const kind = kindOf(container);
    if (!kind) return null;

    const spots = new Map();
    for (const tile of container.querySelectorAll('.tile')) {
      const r = tile.getBoundingClientRect();
      if (!onScreen(r)) continue;
      spots.set(tile._it.id, { x: r.left + r.width / 2, y: r.top + r.height / 2, w: tile.offsetWidth, tilt: tiltOf(tile, kind) });
    }
    return { kind, spots };
  }

  /**
   * The discs slide out of the sleeve as it travels. It is what a hover does
   * (reveal() in js/tiles.js) but on a class of its own, because `.hot` also
   * moves the tile and this tile's movement is already spoken for.
   */
  function slideOut(tile) {
    const it = tile._it;
    if (!it.discs.length) return;
    if (!tile._discs) {
      const wrap = document.createElement('div');
      wrap.className = 'discs';
      it.discs.forEach((_, k) => wrap.appendChild(discEl(it, k)));
      tile.prepend(wrap);
      tile._discs = wrap;
    }
    const rc = tile.getBoundingClientRect();
    tile.classList.toggle('left', rc.right + rc.width * (0.33 + 0.16 * (it.discs.length - 1)) > document.documentElement.clientWidth - 6);
    void tile.offsetWidth; // flush styles so the slide-out transition runs
    tile.classList.add('morph-hot');
  }

  /** Sets the records of the freshly drawn view moving from where `snap` had them. */
  function play(snap, container) {
    const kind = kindOf(container);
    if (!snap || !kind || kind === snap.kind || reduced()) return;
    hideTip();

    // every read first, so the page is laid out once and not once per record
    const arrivals = [];
    for (const tile of container.querySelectorAll('.tile')) {
      const r = tile.getBoundingClientRect();
      if (!onScreen(r)) continue;
      arrivals.push({
        tile, r,
        from: snap.spots.get(tile._it.id),
        w: tile.offsetWidth,
        tilt: tiltOf(tile, kind),
        rest: getComputedStyle(tile).transform, // the pose it settles into: 'none' in the grid, the pile's scatter on the floor
      });
    }
    arrivals.sort((a, b) => (a.r.top - b.r.top) || (a.r.left - b.r.left));

    const flights = arrivals.filter(a => a.from).slice(0, MAX);
    const flying = new Set(flights.map(a => a.tile));

    flights.forEach((a, i) => {
      const dx = a.from.x - (a.r.left + a.r.width / 2);
      const dy = a.from.y - (a.r.top + a.r.height / 2);
      const k = a.w ? a.from.w / a.w : 1;
      const turn = a.from.tilt - a.tilt;
      const dist = Math.hypot(dx, dy);
      const lift = 40 + Math.min(90, dist * 0.1);
      const rest = a.rest === 'none' ? '' : a.rest + ' ';
      // the same list of functions in every keyframe, so each one is interpolated on its own
      const pose = (x, y, r, s) => `translate(${x.toFixed(1)}px,${y.toFixed(1)}px) ${rest}rotate(${r.toFixed(2)}deg) scale(${s.toFixed(3)})`;

      const duration = 700 + Math.min(300, dist * 0.3);
      const delay = Math.min(i, 50) * 10;

      a.tile.style.zIndex = 50; // over its neighbours while it is in the air
      const anim = a.tile.animate([
        { transform: pose(dx, dy, turn, k) },
        { transform: pose(dx / 2, dy / 2 - lift, turn / 2, (k + 1) / 2), offset: 0.5 },
        { transform: pose(0, 0, 0, 1) },
      ], { duration, delay, easing: 'cubic-bezier(.35,0,.25,1)', fill: 'backwards' });
      anim.finished.then(() => { a.tile.style.zIndex = ''; }, () => { a.tile.style.zIndex = ''; });

      if (a.tile._it.k === 'vinyl') {
        setTimeout(() => slideOut(a.tile), delay + 40);
        setTimeout(() => a.tile.classList.remove('morph-hot'), delay + duration * 0.7);
      }
    });

    // the rest of what is on screen has no old spot to leave from, or is past the cap
    arrivals.filter(a => !flying.has(a.tile)).forEach(a => {
      a.tile.animate([{ opacity: 0 }, { opacity: 1 }], { duration: 400, delay: 250, fill: 'backwards' });
    });

    // the grid's captions come in once the sleeves are nearly home
    if (kind === 'grid') {
      const grid = container.querySelector(':scope > .grid');
      grid.classList.add('morphing');
      setTimeout(() => grid.classList.remove('morphing'), 1600);
    }
  }

  return { capture, play };
})();
