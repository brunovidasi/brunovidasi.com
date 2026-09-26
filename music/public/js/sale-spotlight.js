/* The shop's spotlight: the cover clicked, stood up on the left while its
 * drawer opens on the right, with the price and a Buy button under it. The
 * flight is the collection's (js/spotlight-core.js); a listing has only a
 * picture to fly. */

const Spotlight = (() => {
  const INFO_H = 110; // what each block of text is expected to take
  const GAP = 28;     // between the cover and the text above and below it

  /** The stage: the page less the drawer, the cover square in the middle. Null without room, or without a cover. */
  function layout(it) {
    if (!(it.cover || it.thumb)) return null;
    const lw = innerWidth - $('drawer').offsetWidth;
    if (lw < MIN_SPOT_STAGE) return null;

    const vh = innerHeight;
    const margin = clamp(vh * 0.06, 28, 64);
    const availH = vh - margin * 2 - (INFO_H + GAP) * 2;
    if (availH < 140) return null;

    const padX = clamp(lw * 0.08, 28, 96);
    const size = Math.min(lw - padX * 2, availH, vh * 0.5, 460);
    return { lw, cx: padX + (lw - padX * 2) / 2, cy: vh / 2, size };
  }

  function build(it, box, layer) {
    const cover = it.cover || it.thumb;
    layer.innerHTML = `
      <div class="spot-info above">
        <p class="spot-eyebrow"><i class="kc ${esc(it.kind)}">${esc(KIND_LABEL[it.kind] || it.kind)}</i>${esc(it.regionName || '')}</p>
        <h2>${esc(it.title)}</h2>
      </div>
      <div class="sale-spot-cover">${cover ? `<img src="${esc(cover)}" alt="">` : ''}</div>
      <div class="spot-info below">
        <div class="spot-below-row">
          <div class="spot-below-text">
            <p class="spot-who">${esc(it.artist)}${it.year ? ' · ' + it.year : ''}</p>
            ${it.priceLabel ? `<p class="spot-price">${esc(it.priceLabel)}</p>` : ''}
          </div>
          ${it.ebay ? `<a class="spot-buy" href="${esc(it.ebay)}" target="_blank" rel="noopener">Buy on eBay ↗</a>` : ''}
        </div>
      </div>`;
    return layer.querySelector('.sale-spot-cover');
  }

  function place(a) {
    const { L, layer, piece: figure } = a;
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

    above.style.bottom = (innerHeight - (L.cy - L.size / 2) + GAP).toFixed(1) + 'px';
    below.style.top = (L.cy + L.size / 2 + GAP).toFixed(1) + 'px';

    document.body.style.setProperty('--spot-x', L.cx.toFixed(0) + 'px');
    document.body.style.setProperty('--spot-y', L.cy.toFixed(0) + 'px');
  }

  return makeSpotlight({
    layout,
    size: L => [L.size, L.size],
    build,
    place,
    boxOf(el) {
      if (!el) return null;
      if (el.classList.contains('sale-card')) return el.querySelector('.sale-cover');
      if (el.classList.contains('lrow')) return el.querySelector('.lthumb');
      return el;
    },
    homes: '.sale-card, .lrow',
    notIn: '.spot',
    turns: false,
    open: { lift: [40, 90], duration: [720, 280] },
    rise: { drop: 60, duration: 650 },
    close: { lift: () => 0, duration: [700, 280] },
    sink: { drop: 40, duration: 420 },
    cover(a, url) {
      const img = a.piece.querySelector('img') || new Image();
      img.alt = '';
      img.src = url;
      if (!img.isConnected) a.piece.prepend(img);
    },
  });
})();
