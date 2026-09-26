/* Floor <-> grid: each record on screen is carried from where one view had it
 * to where the other lays it, in a short arc, straightening out or settling
 * into the pile, the vinyl ones sliding their discs out on the way.
 * Shelf page only. */

const Morph = (() => {
  const MAX = 64; // records that travel; any more just fade in with the rest

  const kindOf = el => (el.querySelector(':scope > .floor') ? 'floor' : el.querySelector(':scope > .grid') ? 'grid' : null);
  const onScreen = r => r.bottom > -40 && r.top < innerHeight + 40 && r.right > -40 && r.left < innerWidth + 40;

  /** How far a tile is turned: the pile scatters them, the grid stands them straight. */
  const tiltOf = (tile, kind) => {
    if (kind !== 'floor') return 0;
    const mess = parseFloat(tile.parentElement.style.getPropertyValue('--mess')) || 0;
    return (parseFloat(tile.style.getPropertyValue('--r')) || 0) * mess;
  };

  /** Where every record on screen is now. Call it just before the view is drawn again. */
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

  /* The hover's discs, on a class of their own: .hot also moves the tile, and this tile is already moving. */
  function slideOut(tile) {
    if (!tile._it.discs.length) return;
    tileDiscs(tile);
    aimDiscs(tile);
    tile.classList.add('morph-hot');
  }

  /** Sets the records of the view just drawn moving from where `snap` had them. */
  function play(snap, container) {
    const kind = kindOf(container);
    if (!snap || !kind || kind === snap.kind || prefersReducedMotion()) return;
    hideTip();

    // Every read first, so the page is laid out once rather than once per record.
    const arrivals = [];
    for (const tile of container.querySelectorAll('.tile')) {
      const r = tile.getBoundingClientRect();
      if (!onScreen(r)) continue;
      arrivals.push({
        tile, r,
        from: snap.spots.get(tile._it.id),
        w: tile.offsetWidth,
        tilt: tiltOf(tile, kind),
        rest: getComputedStyle(tile).transform, // 'none' in the grid, the pile's scatter on the floor
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
      // The same functions in every keyframe, so each is interpolated on its own.
      const pose = (x, y, r, s) => `translate(${x.toFixed(1)}px,${y.toFixed(1)}px) ${rest}rotate(${r.toFixed(2)}deg) scale(${s.toFixed(3)})`;

      const duration = 700 + Math.min(300, dist * 0.3);
      const delay = Math.min(i, 50) * 10;

      a.tile.style.zIndex = 50; // over its neighbours while in the air
      const anim = a.tile.animate([
        { transform: pose(dx, dy, turn, k) },
        { transform: pose(dx / 2, dy / 2 - lift, turn / 2, (k + 1) / 2), offset: 0.5 },
        { transform: pose(0, 0, 0, 1) },
      ], { duration, delay, easing: 'cubic-bezier(.35,0,.25,1)', fill: 'backwards' });
      const land = () => { a.tile.style.zIndex = ''; };
      anim.finished.then(land, land);

      if (a.tile._it.k === 'vinyl') {
        setTimeout(() => slideOut(a.tile), delay + 40);
        setTimeout(() => a.tile.classList.remove('morph-hot'), delay + duration * 0.7);
      }
    });

    // The rest had no old spot to leave from, or is past the cap: it fades in.
    arrivals.filter(a => !flying.has(a.tile)).forEach(a => {
      a.tile.animate([{ opacity: 0 }, { opacity: 1 }], { duration: 400, delay: 250, fill: 'backwards' });
    });

    // The grid's captions come in once the sleeves are nearly home.
    if (kind === 'grid') {
      const grid = container.querySelector(':scope > .grid');
      grid.classList.add('morphing');
      setTimeout(() => grid.classList.remove('morphing'), 1600);
    }
  }

  return { capture, play };
})();
