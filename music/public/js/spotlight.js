/* The collection's spotlight: the record clicked, stood up on the left while
 * its drawer opens on the right. It is the tile the shelf draws, its discs
 * sliding the rest of the way out as it flies and spinning once it lands, with
 * the title and the basics above and below it. Not in the crate, which pulls
 * its records out its own way. */

const Spotlight = (() => {
  const INFO_H = 130; // what each block of text is expected to take, before it is measured
  const GAP = 30;     // between the record and the text above and below it
  // How far a disc slides out: the first shows most of itself, later ones fan out behind it.
  const OUT_BASE = 0.62;
  const OUT_STEP = 0.36;

  /** How far a disc's far edge reaches, in sleeve widths from the sleeve's left edge. */
  const reachOf = (d, k) => 1 + discSize(d) * (OUT_BASE + k * OUT_STEP);

  /**
   * The stage: the page less the drawer, the record in the middle with its
   * discs out, a block of text above and below. place() shrinks it if the text
   * turns out taller than expected.
   */
  function layout(it) {
    const lw = innerWidth - $('drawer').offsetWidth;
    if (lw < MIN_SPOT_STAGE) return null;

    const vh = innerHeight;
    const [w, h] = dims(it);
    const reach = Math.max(1, ...it.discs.map(reachOf));

    const padX = clamp(lw * 0.07, 28, 96);
    const margin = clamp(vh * 0.06, 28, 64);
    const availH = vh - margin * 2 - (INFO_H + GAP) * 2;
    if (availH < 140) return null;

    const k = Math.min((lw - padX * 2) / (w * reach), Math.min(availH, vh * 0.5) / h, 3.4);
    return sized({ w, h, reach, padX, margin, lw, cy: vh / 2 }, k);
  }

  /** The layout at one scale: the record's size, and where it stands across the stage. */
  function sized(L, k) {
    const rw = L.w * k, rh = L.h * k, gw = rw * L.reach;
    const x0 = L.padX + (L.lw - L.padX * 2 - gw) / 2;
    return Object.assign(L, { k, rw, rh, gw, x0, cx: x0 + rw / 2 });
  }

  /** What it is and its title above; who made it, when, and the pressing below. Rows arrive one after another (--n). */
  function infoHtml(it) {
    const number = (rows, from) => rows.map((row, n) => row.replace(/^<(\w+)/, `<$1 style="--n:${from + n}"`)).join('');

    const above = [
      `<p class="spot-eyebrow"><i class="kc ${esc(it.kind)}">${esc(KIND_LABEL[it.kind])}</i>${esc(it.regionName || '')}</p>`,
      `<h2>${esc(it.title)}</h2>`,
    ];
    const below = [
      `<p class="spot-who">${esc(it.artist)}${it.year ? ' · ' + it.year : ''}</p>`,
      it.fmtRest ? `<p class="spot-rest">${esc(it.fmtRest)}</p>` : '',
    ].filter(Boolean);
    return `<div class="spot-info above">${number(above, 0)}</div><div class="spot-info below">${number(below, above.length)}</div>`;
  }

  /** The record in the hand: the shelf's tile with its discs built and the full-size cover; a hovered one starts from the hover. */
  function build(it, box, layer) {
    layer.innerHTML = `<div class="spot-floor"></div>${infoHtml(it)}`;

    const t = buildTileCopy(it);
    t.classList.add('spot-tile');

    const img = t.querySelector('img');
    if (img) {
      img.loading = 'eager';
      if (it.cover && img.getAttribute('src') !== it.cover) {
        const big = new Image();
        big.onload = () => { img.src = it.cover; };
        big.src = it.cover;
      }
    }

    if (it.discs.length) {
      [...tileDiscs(t).children].forEach((disc, k) => disc.style.setProperty('--reach2', reachOf(it.discs[k], k).toFixed(3)));
    }

    if (box && box.classList.contains('hot')) t.classList.add('hot');
    if (box && box.classList.contains('left')) t.classList.add('left');
    layer.appendChild(t);
    return t;
  }

  /** Sizes and places the record and the text against it, making the record smaller if the text would run off the page. */
  function place(a) {
    const { L, layer, piece: tile } = a;
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

      // Half the record and the taller block of text must fit between the middle and the edge.
      const room = innerHeight / 2 - L.margin - GAP - Math.max(above.offsetHeight, below.offsetHeight);
      if (L.rh / 2 <= room || pass) break;
      sized(L, L.k * Math.max(0.4, (room * 2) / L.rh));
    }

    const top = L.cy - L.rh / 2, bottom = L.cy + L.rh / 2;
    above.style.bottom = (innerHeight - top + GAP).toFixed(1) + 'px';
    below.style.top = (bottom + GAP).toFixed(1) + 'px';

    const floor = layer.querySelector('.spot-floor');
    floor.style.left = (L.x0 - L.gw * 0.04).toFixed(1) + 'px';
    floor.style.width = (L.gw * 1.08).toFixed(1) + 'px';
    floor.style.top = (bottom - 16).toFixed(1) + 'px';

    document.body.style.setProperty('--spot-x', (L.x0 + L.gw / 2).toFixed(0) + 'px');
    document.body.style.setProperty('--spot-y', L.cy.toFixed(0) + 'px');
  }

  return makeSpotlight({
    layout,
    size: L => [L.rw, L.rh],
    build,
    place,
    boxOf(el) {
      if (!el) return null;
      if (el.classList.contains('cell')) return el.querySelector('.tile');
      if (el.classList.contains('lrow')) return el.querySelector('.lthumb');
      return el;
    },
    homes: '.tile, .lrow',
    notIn: '.crate-stage, .spot',
    turns: true,
    open: { lift: [50, 110], duration: [820, 320] },
    rise: { drop: 70, duration: 700 },
    close: { lift: dist => 40 + Math.min(100, dist * 0.1), duration: [760, 300] },
    sink: { drop: 50, duration: 450 },
    // The discs carry on out from the hover's third, partway through the flight.
    opened(a, animated, fromBox) {
      if (!animated) a.piece.classList.add('spot-out');
      else a.timer = setTimeout(() => a.piece.classList.add('spot-out'), fromBox ? 260 : 350);
    },
    closing(a) {
      a.piece.classList.remove('spot-out', 'hot');
    },
    cover(a, url) {
      const sleeve = a.piece.querySelector('.sleeve');
      let img = sleeve.querySelector('img');
      if (!img) {
        img = new Image();
        img.alt = '';
        sleeve.prepend(img);
      }
      img.src = url;
    },
  });
})();
