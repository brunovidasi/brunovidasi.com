/* cases.js — draws a game the way it sits on the shelf.
   Case.front / spine / back give flat faces; Case.box puts them together into a
   3D case that can be turned around and opened; Case.cart draws a cartridge or
   game card. Sizes are the real ones in millimetres times a scale (px per mm). */

(function () {
  const C = () => window.CONSOLE_BY_ID;
  const esc = s => String(s ?? '').replace(/[&<>"']/g, ch => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch]));

  const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
  // "2001-07-19" → "19 Jul 2001" (or "19 July 2001"), "2014-10" → "Oct 2014", "2007" → "2007"
  function date(r, long) {
    if (!r) return 'Unknown';
    const [y, m, d] = r.split('-');
    const mon = m ? (long ? MONTHS[m - 1] : MONTHS[m - 1].slice(0, 3)) : '';
    return [d && +d, mon, y].filter(Boolean).join(' ');
  }
  const year = g => (g.released || '').slice(0, 4) || '—';
  const styleOf = g => g.caseStyle || g.console;

  /* ---------- the case's shape ----------
     A case keeps its console's real height and is built around its cover scan,
     so the scan fills the front exactly: nothing cut, no gaps. A plastic case
     keeps a thin rim and its hinge round the scan; a cardboard box simply is its
     scan. Without a scan the case has its real width, and so has a case whose
     picture is only the game's key art (cover.plain): the console's band is
     printed across its top and the art fills the rest. */
  function layout(g, s) {
    const c = C()[styleOf(g)];
    const m = g.big ? window.BIGBOX : c;
    const kind = g.big ? 'box' : c.kind;
    const h = m.h * s, d = m.d * s;
    const r = g.cover && g.cover.ratio;
    let rim = [0, 0, 0, 0]; // top, right, bottom, left
    if (kind === 'keep') { const t = m.w * s * 0.018; rim = [t, t, t, m.w * s * 0.045]; }
    // a PlayStation jewel case shows its black tray down a wide hinge on the left
    if (kind === 'jewel') { const t = m.w * s * 0.012; rim = [t, t, t, m.w * s * (styleOf(g) === 'ps1' ? 0.1 : 0.07)]; }
    const ih = h - rim[0] - rim[2];
    const iw = r && !g.cover.plain ? ih * r : m.w * s - rim[1] - rim[3];
    return { w: iw + rim[1] + rim[3], h, d, kind, c, ix: rim[3], iy: rim[0], iw, ih };
  }
  const dims = layout;

  // light or dark text for a background colour
  function ink(hex) {
    const n = parseInt(hex.slice(1), 16), r = n >> 16, gr = (n >> 8) & 255, b = n & 255;
    return (0.2126 * r + 0.7152 * gr + 0.0722 * b) / 255 > 0.6 ? '#141414' : '#ffffff';
  }

  /* ---------- console logos, drawn in type (for title cards and spines) ---------- */
  const rainbow = t => [...t].map((ch, i) => ch === ' ' ? ' ' : `<span style="color:${['#e8262b', '#f28b1d', '#f7d117', '#47b549', '#2a8fd6', '#7a4bc0'][i % 6]}">${ch}</span>`).join('');
  const LOGO = {
    ps1: '<i class="psd"><b></b><b></b><b></b><b></b></i><em>PlayStation</em>',
    ps2: '<em>PlayStation<sup>®</sup><b>2</b></em>',
    ps3: '<em>PS3</em>', ps4: '<em>PS4</em>', ps5: '<em>PS5</em>',
    xbox: '<em>XBOX</em>', x360: '<em>XBOX 360</em>', xone: '<em>XBOX ONE</em>',
    snes: '<em><b>SUPER NINTENDO</b><small>ENTERTAINMENT SYSTEM</small></em>',
    gb: '<em><b>GAME BOY</b><small>NINTENDO</small></em>',
    gbc: `<em>${rainbow('GAME BOY COLOR')}</em>`,
    gba: '<em>GAME BOY ADVANCE</em>',
    ds: '<em>NINTENDO<b>DS</b></em>', '3ds': '<em>NINTENDO<b>3DS</b></em>',
    wii: '<em>Wii</em>', wiiu: '<em>Wii U</em>',
    switch: '<i class="joy"><b></b><b></b></i><em>NINTENDO SWITCH</em>',
    switch2: '<i class="joy"><b></b><b></b></i><em>NINTENDO SWITCH <b>2</b></em>',
    pc: '<em>PC</em>',
  };
  const SPINE_LOGO = {
    ps1: '<i class="psd"><b></b><b></b><b></b><b></b></i>', ps2: 'PS2', ps3: 'PS3', ps4: 'PS4', ps5: 'PS5',
    xbox: 'XBOX', x360: '360', xone: 'ONE', snes: 'SNES', gb: 'GB', gbc: rainbow('GBC'), gba: 'GBA',
    ds: 'DS', '3ds': '3DS', wii: 'Wii', wiiu: 'Wii U',
    switch: '<i class="joy"><b></b><b></b></i>', switch2: '<i class="joy"><b></b><b></b></i>2', pc: 'PC',
  };

  const px = v => v.toFixed(1) + 'px';
  function vars(g, L) {
    let v = `--w:${px(L.w)};--h:${px(L.h)};--d:${px(L.d)};--ix:${px(L.ix)};--iy:${px(L.iy)};--iw:${px(L.iw)};--ih:${px(L.ih)}`;
    // a case that came in another colour than its console's usual one (a red Wii case, a white Wii U one)
    if (/^#[0-9a-f]{6}$/i.test(g.caseColor || '')) {
      const c = g.caseColor, f = ink(c);
      return v + `;--pl:${c};--sb:${c};--sf:${f};--st:${c};--stf:${f}`;
    }
    // spines that carry the game's own colours take them from the cover (Kinect games keep their purple)
    if (L.c.spineArt && g.colors && !g.kinect) {
      const [a, b] = g.colors;
      v += L.kind === 'box' ? `;--sb:linear-gradient(${a},${b || a});--sf:${ink(a)}` : `;--sb:${a};--sf:${ink(a)}`;
    }
    return v;
  }
  const cls = (g, L) => `k-${styleOf(g)}${g.big ? ' big' : ''}${g.kinect ? ' kinect' : ''}${g.platinum ? ' platinum' : ''}${g.caseColor ? ' tinted' : ''} kind-${L.kind}`;
  const img = (g, c = '', lazy = true) => g.cover ? `<img class="${c}" src="${esc(g.cover.file)}" alt="" ${lazy ? 'loading="lazy"' : ''} decoding="async" draggable="false">` : '';
  function el(html) {
    const t = document.createElement('template');
    t.innerHTML = html.trim();
    return t.content.firstElementChild;
  }

  // where a PC copy activates: a strip across the bottom of the title card
  function launcherStrip(g) {
    const L = g.launcher && window.LAUNCHERS[g.launcher];
    return L ? `<div class="lch" style="--lb:${L.bg};--lf:${L.fg}"><i>${L.glyph}</i><span>${g.launcher === 'none' ? 'No activation needed' : 'Requires ' + esc(L.name)}</span></div>` : '';
  }

  /* ---------- front ---------- */
  // a Platinum copy's silver band across the top of the cover, over the black one on the scan
  const PLAT = '<div class="plat"><em>PlayStation<sup>®</sup><b>2</b></em><span>Platinum</span></div>';
  function frontHTML(g, L, lazy) {
    const inner = g.cover
      ? `<div class="ins${g.cover.plain ? ' plain' : ''}">${g.cover.plain ? `<div class="band">${LOGO[styleOf(g)]}</div>` : ''}${img(g, 'scan', lazy)}${g.platinum ? PLAT : ''}</div>`
      : `<div class="ins ph">
          <div class="band">${LOGO[styleOf(g)]}</div>
          <div class="phc"><b class="pht">${esc(g.title)}</b><i class="phr"></i><span class="phs">${year(g)}${g.publisher ? ' · ' + esc(g.publisher) : ''}</span></div>
          <i class="phn">Cover to come</i>${launcherStrip(g)}
        </div>`;
    return `<div class="cv ${cls(g, L)}" style="${vars(g, L)}">${inner}<div class="gl"></div></div>`;
  }
  const front = (g, s, lazy = true) => el(frontHTML(g, layout(g, s), lazy));

  /* ---------- spine ---------- */
  function spineHTML(g, L) {
    const Ln = g.launcher && window.LAUNCHERS[g.launcher];
    return `<div class="sp ${cls(g, L)}${g.format === 'digital' ? ' ghost' : ''}" style="${vars(g, L)}">
      <div class="stop"><span>${SPINE_LOGO[styleOf(g)]}</span></div>
      <div class="sttl">${esc(g.title)}</div>
      ${Ln ? `<i class="slch" style="--lb:${Ln.bg};--lf:${Ln.fg}">${Ln.glyph}</i>` : `<div class="sbot">${g.format === 'digital' ? 'DIGITAL' : year(g)}</div>`}
    </div>`;
  }
  const spine = (g, s) => el(spineHTML(g, layout(g, s)));

  /* ---------- back ---------- */
  function backHTML(g, L) {
    const R = window.REGIONS[g.region] || { name: g.region };
    return `<div class="bk ${cls(g, L)}" style="${vars(g, L)}">
      <div class="ins${g.cover ? '' : ' ph'}">${img(g, 'blur')}
        <div class="band">${LOGO[styleOf(g)]}</div>
        <div class="bkin">
          <div class="bkt">${esc(g.title)}</div>
          <div class="bkm"><b>Released</b> ${date(g.released, true)}<br>${[g.publisher, g.developer].filter(Boolean).map(esc).join(' · ')}<br>${esc(g.genre || '')}</div>
          <div class="thumb">${img(g)}</div>
          <div class="bkf"><span class="rg">${esc(R.name)}</span><span class="bar"></span></div>
        </div>
      </div>
    </div>`;
  }

  /* ---------- cartridges and game cards ----------
     Drawn from the real ones, in millimetres: the outline, the grip ridges, the
     recess the label sits in. The label is the cover art, or the title. */
  const ridges = (xs, y, h, w = 1.3) => xs.map(x => `<rect x="${x}" y="${y}" width="${w}" height="${h}" rx="${w / 2}"/>`).join('');
  const lines = (x1, x2, ys) => ys.map(y => `<path d="M${x1} ${y}H${x2}"/>`).join('');
  const gbBody = (f, dark) => `
    <path d="M3 0H50L57 7V62Q57 65 54 65H3Q0 65 0 62V3Q0 0 3 0Z" fill="${f}"/>
    <g stroke="${dark ? '#fff' : '#000'}" stroke-opacity="${dark ? 0.1 : 0.14}" stroke-width=".7">${lines(5, 44, [3, 5.4, 7.8])}</g>
    <path d="M25.5 10.2H31.5L28.5 13.4Z" fill="${dark ? '#fff' : '#000'}" fill-opacity="${dark ? 0.12 : 0.15}"/>
    <rect x="5" y="16" width="47" height="41" rx="2" fill="#000" fill-opacity="${dark ? 0.3 : 0.1}"/>
    <path d="M3 .6H49.6" stroke="#fff" stroke-opacity="${dark ? 0.15 : 0.45}" stroke-width=".7"/>
    <rect y="61" width="57" height="4" rx="1.5" fill="#000" fill-opacity=".1"/>`;
  const dsBody = (f, tab, dark) => `
    <path d="M1.5 0H31L35 4${tab ? 'V5.6H36.2V10H35' : ''}V31.5Q35 33 33.5 33H1.5Q0 33 0 31.5V1.5Q0 0 1.5 0Z" fill="${f}"/>
    <g stroke="${dark ? '#fff' : '#000'}" stroke-opacity="${dark ? 0.13 : 0.16}" stroke-width=".5">${lines(3, 27, [1.7, 3.1, 4.5])}</g>
    <path d="M1.5 .4H30.6" stroke="#fff" stroke-opacity="${dark ? 0.12 : 0.5}" stroke-width=".4"/>`;
  const cardBody = f => `
    <path d="M1 0H17.4L21 3.6V30Q21 31 20 31H1Q0 31 0 30V1Q0 0 1 0Z" fill="${f}"/>
    <path d="M1.2 .45H17" stroke="#fff" stroke-opacity=".16" stroke-width=".4"/>`;
  const CART = {
    // the American SNES cartridge: shoulders at the top, grip ridges, a deep label recess
    snes: { w: 120, h: 86, label: [15, 16, 90, 62], pos: 'center', body: () => `
      <path d="M9 0H111Q115 0 115 4V7Q120 7 120 12V82Q120 86 116 86H4Q0 86 0 82V12Q0 7 5 7V4Q5 0 9 0Z" fill="#c0bfc7"/>
      <path d="M5 7.4H115" stroke="#000" stroke-opacity=".12" stroke-width=".8"/>
      <g fill="#000" fill-opacity=".11">${ridges([9.5, 12.5, 15.5, 18.5, 98.7, 101.7, 104.7, 107.7], 1.6, 4.2)}</g>
      <rect x="12.5" y="13.5" width="95" height="67" rx="3" fill="#000" fill-opacity=".11"/>
      <path d="M9 .7H111" stroke="#fff" stroke-opacity=".55" stroke-width=".8"/>
      <rect y="83" width="120" height="3" rx="1.5" fill="#000" fill-opacity=".09"/>` },
    // crop: the share of the box art, left and right, taken by the console's name strip, which a cartridge label leaves out
    gb:      { w: 57, h: 65, label: [6.5, 17.5, 44, 38], pos: 'center 25%', crop: [0.18, 0], body: () => gbBody('#adada7') },
    gbc:     { w: 57, h: 65, label: [6.5, 17.5, 44, 38], pos: 'center 25%', crop: [0.18, 0], body: () => gbBody('#2b2b2e', true) },
    // GBA: wider than tall, rounded on top, stepped in at the bottom corners
    gba: { w: 57, h: 35, label: [5, 5.4, 47, 21.5], pos: 'center 5%', crop: [0.17, 0], body: () => `
      <path d="M5 0H52Q57 0 57 5V29.5H54V35H3V29.5H0V5Q0 0 5 0Z" fill="#9e9ea5"/>
      <path d="M19 2.1H38" stroke="#000" stroke-opacity=".16" stroke-width="1" stroke-linecap="round"/>
      <rect x="3.8" y="4.3" width="49.4" height="23.7" rx="1.6" fill="#000" fill-opacity=".11"/>
      <path d="M5 .6H52" stroke="#fff" stroke-opacity=".45" stroke-width=".6"/>` },
    ds:      { w: 35,   h: 33, label: [2.4, 6.8, 30.2, 24.4], pos: 'center 30%', crop: [0.13, 0], body: () => dsBody('#3a3a40', false, true) },
    // 3DS cards have the little tab on the right that stops them going into a DS
    '3ds':   { w: 36.2, h: 33, label: [2.4, 6.8, 30.2, 24.4], pos: 'center 30%', crop: [0, 0.11], body: () => dsBody('#e4e4e7', true, false) },
    switch:  { w: 21, h: 31, label: [1.6, 4.4, 17.8, 25.2], pos: 'center', body: () => cardBody('#232326') },
    // Switch 2 game cards are red
    switch2: { w: 21, h: 31, label: [1.6, 4.4, 17.8, 25.2], pos: 'center', body: () => cardBody('#c0121d') },
  };
  const pct = (v, of) => (v / of * 100).toFixed(2) + '%';

  function cartHTML(g, s, type) {
    const T = CART[type];
    const [x, y, lw, lh] = T.label;
    // Japanese box art has no name strip down the side
    const [cl, cr] = T.crop && window.REGIONS[g.region]?.std !== 'NTSC-J' ? T.crop : [0, 0], keep = 1 - cl - cr;
    return `<div class="cart c-${type} k-${styleOf(g)}" style="--cw:${px(T.w * s)};--ch:${px(T.h * s)}">
      <svg viewBox="0 0 ${T.w} ${T.h}" preserveAspectRatio="none" aria-hidden="true">${T.body()}</svg>
      <div class="clab" style="left:${pct(x, T.w)};top:${pct(y, T.h)};width:${pct(lw, T.w)};height:${pct(lh, T.h)};--lp:${T.pos};--liw:${pct(1, keep)};--lix:${pct(-cl, keep)}">${g.cover ? img(g) : `<span>${esc(g.title)}</span>`}</div>
    </div>`;
  }

  /* ---------- what is inside ---------- */
  const mediaType = g => g.big ? 'cd' : C()[styleOf(g)].media;
  const isCart = g => !!CART[mediaType(g)];
  function mediaHTML(g, s) {
    const type = mediaType(g);
    if (CART[type]) return cartHTML(g, s, type);
    const D = 120 * s;
    const mark = { dvd: 'DVD', bd: 'BD', cd: '', wii: 'Wii', wiiu: 'Wii U' }[type] || '';
    return `<div class="disc m-${type} k-${styleOf(g)}" style="--dd:${D}px">
      <div class="dlab">${img(g)}${g.cover ? '' : `<span>${esc(g.title)}</span>`}</div>${mark ? `<i class="mark">${mark}</i>` : ''}<i class="hub"></i></div>`;
  }
  const cart = (g, s) => el(cartHTML(g, s, mediaType(g)));

  /* ---------- 3D case ---------- */
  // stands each face of a 3D case in its place round the middle of the case
  function assemble(b, L) {
    const W = L.w, H = L.h, D = L.d;
    const set = (sel, w, h, l, t, tr) => { const f = b.querySelector(sel); if (f) Object.assign(f.style, { width: w + 'px', height: h + 'px', left: l + 'px', top: t + 'px', transform: tr }); };
    set('.f-front', W, H, 0, 0, `translateZ(${D / 2}px)`);
    set('.f-tray', W, H, 0, 0, `translateZ(${D / 2 - 1}px)`);
    set('.f-back', W, H, 0, 0, `rotateY(180deg) translateZ(${D / 2}px)`);
    set('.f-left', D, H, (W - D) / 2, 0, `rotateY(-90deg) translateZ(${W / 2}px)`);
    set('.f-right', D, H, (W - D) / 2, 0, `rotateY(90deg) translateZ(${W / 2}px)`);
    set('.f-top', W, D, 0, (H - D) / 2, `rotateX(90deg) translateZ(${H / 2}px)`);
    set('.f-bot', W, D, 0, (H - D) / 2, `rotateX(-90deg) translateZ(${H / 2}px)`);
    b.style.width = W + 'px';
    b.style.height = H + 'px';
    return b;
  }

  // just the front and the spine: a case turning as it goes between the shelf and the grid
  function slab(g, s) {
    const L = layout(g, s);
    return assemble(el(`<div class="bx slab ${cls(g, L)}" style="${vars(g, L)}">
      <div class="fc f-front">${frontHTML(g, L, false)}</div>
      <div class="fc f-left">${spineHTML(g, L)}</div>
    </div>`), L);
  }

  function box(g, s) {
    const L = layout(g, s);
    const H = L.h;
    const b = el(`<div class="bx ${cls(g, L)}" style="${vars(g, L)}">
      <div class="fc f-tray"><div class="tray">${mediaHTML(g, s)}</div></div>
      <div class="fc f-front"><div class="leaf">${frontHTML(g, L, false)}<div class="inner"><div class="manual"><div class="mt">${esc(g.title)}</div><div class="ml"><i></i><i></i><i></i><i style="width:60%"></i></div><div class="mthumb">${img(g)}</div></div></div></div></div>
      <div class="fc f-back">${backHTML(g, L)}</div>
      <div class="fc f-left">${spineHTML(g, L)}</div>
      <div class="fc f-right"><div class="edge"></div></div>
      <div class="fc f-top"><div class="edge"></div></div>
      <div class="fc f-bot"><div class="edge"></div></div>
    </div>`);
    assemble(b, L);
    // taking it out of a cardboard box: the box shrinks to the bottom of its outline and the
    // cartridge rises three quarters out of the top, so the open box needs no more room than the closed one
    if (L.kind === 'box') {
      const T = CART[mediaType(g)], mh = T ? T.h * s : 120 * s;
      const os = Math.min(0.85, H / (H + 0.75 * mh));
      b.style.setProperty('--os', os.toFixed(3));
      b.style.setProperty('--oy', px(H * (1 - os) / 2));
      b.style.setProperty('--ty', px(H / 2 + 0.25 * mh));
    }
    return b;
  }

  // a cartridge's size in px at scale s, for laying one out
  const cartSize = (g, s) => { const T = CART[mediaType(g)]; return T ? [T.w * s, T.h * s] : [0, 0]; };

  window.Case = { dims, layout, front, spine, box, slab, cart, cartSize, isCart, date, year, esc, styleOf };
})();
