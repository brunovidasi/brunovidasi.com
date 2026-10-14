/* cases.js — draws a game the way it really looks on a shelf.
   Case.front / spine / back give flat faces; Case.box puts them together into a
   3D case that can be turned around and opened. All sizes come from the real
   millimetre sizes in data.js times a scale (px per mm). */

(function () {
  const C = () => window.CONSOLE_BY_ID;
  let uid = 0;

  const esc = s => String(s).replace(/[&<>"']/g, ch => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch]));

  // Small deterministic random, so a game's cover is the same everywhere.
  function rng(seed) {
    let s = seed * 9301 + 49297;
    return () => ((s = (s * 9301 + 49297) % 233280) / 233280);
  }

  function dims(g, s) {
    const c = C()[g.console];
    const m = g.big ? window.BIGBOX : c;
    return { w: m.w * s, h: m.h * s, d: m.d * s, kind: g.big ? 'box' : c.kind, c };
  }

  /* ---------- cover art: one SVG per game, drawn from its colours + motif ---------- */

  function art(g, ratio) {
    const H = 100 * ratio;
    const [a, b, k] = g.colors;
    const r = rng(g.id);
    const id = 'g' + (++uid);
    let m = '';
    const cx = 30 + r() * 40, cy = H * (0.3 + r() * 0.25);

    switch (g.motif) {
      case 'sun': {
        const hy = H * 0.62;
        m += `<circle cx="${cx}" cy="${hy - 6}" r="${22 + r() * 10}" fill="${k}" opacity=".95"/>`;
        for (let i = 0; i < 6; i++) m += `<rect x="0" y="${hy - 18 + i * 5}" width="100" height="${0.6 + i * 0.5}" fill="${a}" opacity=".9"/>`;
        m += `<rect x="0" y="${hy}" width="100" height="${H - hy}" fill="${a}"/>`;
        m += `<path d="M0 ${hy + 6} Q 30 ${hy - 2} 55 ${hy + 8} T 100 ${hy + 4} V ${H} H 0Z" fill="#000" opacity=".25"/>`;
        break;
      }
      case 'peaks': {
        for (let L = 0; L < 3; L++) {
          let d = `M0 ${H}`;
          const base = H * (0.5 + L * 0.13);
          for (let x = 0; x <= 100; x += 10 + r() * 10) d += ` L${x} ${base - r() * (28 - L * 7)}`;
          d += ` L100 ${base} L100 ${H}Z`;
          m += `<path d="${d}" fill="${L === 0 ? k : a}" opacity="${0.35 + L * 0.3}"/>`;
        }
        m += `<circle cx="${cx}" cy="${H * 0.2}" r="6" fill="${k}" opacity=".8"/>`;
        break;
      }
      case 'rings':
        for (let i = 7; i > 0; i--) m += `<circle cx="${cx}" cy="${cy}" r="${i * 9}" fill="none" stroke="${k}" stroke-width="${0.6 + (i % 3)}" opacity="${0.15 + (7 - i) * 0.1}"/>`;
        m += `<circle cx="${cx}" cy="${cy}" r="7" fill="${k}"/>`;
        break;
      case 'orb':
        m += `<radialGradient id="${id}o"><stop offset="0" stop-color="${k}"/><stop offset=".35" stop-color="${k}" stop-opacity=".6"/><stop offset="1" stop-color="${k}" stop-opacity="0"/></radialGradient>`;
        m += `<circle cx="${cx}" cy="${cy}" r="48" fill="url(#${id}o)"/>`;
        m += `<circle cx="${cx}" cy="${cy}" r="13" fill="#fff" opacity=".85"/>`;
        for (let i = 0; i < 40; i++) m += `<circle cx="${r() * 100}" cy="${r() * H}" r="${r() * 0.7}" fill="#fff" opacity="${r()}"/>`;
        break;
      case 'grid': {
        const hy = H * 0.55;
        m += `<rect x="0" y="${hy}" width="100" height="${H - hy}" fill="#000" opacity=".35"/>`;
        for (let i = -10; i <= 10; i++) m += `<line x1="${50 + i * 4}" y1="${hy}" x2="${50 + i * 22}" y2="${H}" stroke="${k}" stroke-width=".4" opacity=".7"/>`;
        for (let i = 1; i < 9; i++) { const y = hy + Math.pow(i / 8, 2) * (H - hy); m += `<line x1="0" y1="${y}" x2="100" y2="${y}" stroke="${k}" stroke-width=".4" opacity=".7"/>`; }
        m += `<circle cx="50" cy="${hy - 12}" r="16" fill="${k}" opacity=".9"/>`;
        break;
      }
      case 'stripes':
        for (let i = -4; i < 12; i++) m += `<rect x="${i * 14}" y="-20" width="${5 + r() * 4}" height="${H + 40}" fill="${i % 2 ? k : '#000'}" opacity="${i % 2 ? 0.8 : 0.25}" transform="skewX(-24)"/>`;
        break;
      case 'burst':
        for (let i = 0; i < 18; i++) {
          const t1 = (i / 18) * Math.PI * 2, t2 = t1 + Math.PI / 30;
          m += `<path d="M${cx} ${cy} L${cx + Math.cos(t1) * 160} ${cy + Math.sin(t1) * 160} L${cx + Math.cos(t2) * 160} ${cy + Math.sin(t2) * 160}Z" fill="${k}" opacity=".28"/>`;
        }
        m += `<circle cx="${cx}" cy="${cy}" r="11" fill="${k}"/>`;
        break;
      case 'city': {
        let x = -2;
        while (x < 100) {
          const w = 6 + r() * 10, h = H * (0.2 + r() * 0.45);
          m += `<rect x="${x}" y="${H - h}" width="${w}" height="${h}" fill="#000" opacity="${0.45 + r() * 0.35}"/>`;
          for (let wy = H - h + 3; wy < H - 2; wy += 4) for (let wx = x + 1.5; wx < x + w - 1.5; wx += 3) if (r() > 0.55) m += `<rect x="${wx}" y="${wy}" width="1.2" height="1.6" fill="${k}" opacity=".8"/>`;
          x += w + 0.6;
        }
        break;
      }
      case 'dots':
        for (let y = 2; y < H; y += 6) for (let x = 2; x < 100; x += 6) {
          const d = Math.hypot(x - cx, y - cy);
          m += `<circle cx="${x + ((y / 6) % 2) * 3}" cy="${y}" r="${Math.max(0.3, 2.6 - d / 22)}" fill="${k}" opacity=".85"/>`;
        }
        break;
      case 'moon':
        for (let i = 0; i < 60; i++) m += `<circle cx="${r() * 100}" cy="${r() * H * 0.8}" r="${r() * 0.6}" fill="#fff" opacity="${r()}"/>`;
        m += `<circle cx="${cx}" cy="${cy}" r="16" fill="${k}"/><circle cx="${cx + 7}" cy="${cy - 4}" r="14" fill="${a}"/>`;
        m += `<path d="M0 ${H * 0.82} Q 25 ${H * 0.7} 50 ${H * 0.8} T 100 ${H * 0.75} V ${H} H0Z" fill="#000" opacity=".6"/>`;
        break;
      case 'wave':
        for (let i = 0; i < 9; i++) {
          const y = H * 0.3 + i * H * 0.07;
          m += `<path d="M0 ${y} Q 25 ${y - 8} 50 ${y} T 100 ${y}" fill="none" stroke="${k}" stroke-width="${1 + i * 0.25}" opacity="${0.2 + i * 0.08}"/>`;
        }
        break;
      case 'diag':
        m += `<path d="M0 ${H} L100 ${H * 0.15} L100 ${H}Z" fill="${k}" opacity=".85"/>`;
        m += `<path d="M0 ${H} L100 ${H * 0.35} L100 ${H}Z" fill="#000" opacity=".4"/>`;
        m += `<rect x="${cx - 2}" y="0" width="4" height="${H}" fill="${k}" opacity=".35" transform="rotate(25 ${cx} ${cy})"/>`;
        break;
    }

    return `<svg class="artsvg" viewBox="0 0 100 ${H.toFixed(2)}" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
      <defs><linearGradient id="${id}" x1="0" y1="0" x2=".4" y2="1"><stop offset="0" stop-color="${a}"/><stop offset="1" stop-color="${b}"/></linearGradient></defs>
      <rect width="100" height="${H}" fill="url(#${id})"/>${m}
      <rect width="100" height="${H}" fill="url(#${id})" opacity=".12"/></svg>`;
  }

  /* ---------- console logos (type only, drawn in CSS) ---------- */

  const rainbow = t => [...t].map((ch, i) => ch === ' ' ? ' ' : `<span style="color:${['#e8262b', '#f28b1d', '#f7d117', '#47b549', '#2a8fd6', '#7a4bc0'][i % 6]}">${ch}</span>`).join('');

  const LOGO = {
    ps1: '<i class="psd"><b></b><b></b><b></b><b></b></i><em>PlayStation</em>',
    ps2: '<em>PlayStation<sup>®</sup><b>2</b></em>',
    ps3: '<em>PS3</em>',
    ps4: '<em>PS4</em>',
    ps5: '<em>PS5</em>',
    xbox: '<em>XBOX</em>',
    x360: '<em>XBOX 360</em>',
    xone: '<em>XBOX ONE</em>',
    snes: '<em><b>SUPER NINTENDO</b><small>ENTERTAINMENT SYSTEM</small></em>',
    gbc: `<em>${rainbow('GAME BOY COLOR')}</em>`,
    gba: '<em>GAME BOY ADVANCE</em>',
    ds: '<em>NINTENDO<b>DS</b></em>',
    '3ds': '<em>NINTENDO<b>3DS</b></em>',
    n3ds: '<em><small>NEW</small>NINTENDO<b>3DS</b></em>',
    wii: '<em>Wii</em>',
    wiiu: '<em>Wii U</em>',
    switch: '<i class="joy"><b></b><b></b></i><em>NINTENDO SWITCH</em>',
    switch2: '<i class="joy"><b></b><b></b></i><em>NINTENDO SWITCH <b>2</b></em>',
    pc: '<em>PC</em>',
  };

  const SPINE_LOGO = {
    ps1: '<i class="psd"><b></b><b></b><b></b><b></b></i>', ps2: 'PS2', ps3: 'PS3', ps4: 'PS4', ps5: 'PS5',
    xbox: 'XBOX', x360: '360', xone: 'ONE', snes: 'SNES', gbc: rainbow('GBC'), gba: 'GBA',
    ds: 'DS', '3ds': '3DS', n3ds: '3DS', wii: 'Wii', wiiu: 'Wii U', switch: '<i class="joy"><b></b><b></b></i>', switch2: '<i class="joy"><b></b><b></b></i>2', pc: 'PC',
  };

  function launcherTag(g) {
    if (!g.launcher) return '';
    const L = window.LAUNCHERS[g.launcher];
    return `<div class="lch" style="--lb:${L.bg};--lf:${L.fg}"><i>${L.glyph}</i><span>${g.launcher === 'none' ? 'No activation needed' : 'Requires ' + esc(L.name)}</span></div>`;
  }

  function vars(g, d) {
    return `--w:${d.w.toFixed(1)}px;--h:${d.h.toFixed(1)}px;--d:${d.d.toFixed(1)}px;--a1:${g.colors[0]};--a2:${g.colors[1]};--a3:${g.colors[2]}`;
  }

  function el(html) {
    const t = document.createElement('template');
    t.innerHTML = html.trim();
    return t.content.firstElementChild;
  }

  function cls(g, d) {
    return `k-${g.console}${g.big ? ' big' : ''} kind-${d.kind}`;
  }

  /* ---------- front ---------- */

  function frontHTML(g, d) {
    const pos = ['top', 'mid', 'low'][g.id % 3];
    return `<div class="cv ${cls(g, d)}" style="${vars(g, d)}">
      <div class="ins">
        <div class="art">${art(g, d.h / d.w)}</div>
        <div class="ttl t-${pos} f-${g.font}"><b>${esc(g.title)}</b></div>
        <div class="band">${LOGO[g.console]}</div>
        ${g.console === 'n3ds' ? '<div class="only">New Nintendo 3DS only</div>' : ''}
        ${g.console === 'snes' ? '<div class="seal">OFFICIAL<br>SEAL</div>' : ''}
        <div class="esrb"><b>${g.esrb}</b></div>
        ${launcherTag(g)}
      </div>
      <div class="gl"></div>
    </div>`;
  }

  function front(g, s) { return el(frontHTML(g, dims(g, s))); }

  /* ---------- spine ---------- */

  function spineHTML(g, d) {
    return `<div class="sp ${cls(g, d)}" style="${vars(g, d)}">
      <div class="stop"><span>${SPINE_LOGO[g.console]}</span></div>
      <div class="sttl f-${g.font}">${esc(g.title)}</div>
      <div class="sbot">${g.launcher ? `<i class="lg" style="--lb:${window.LAUNCHERS[g.launcher].bg};--lf:${window.LAUNCHERS[g.launcher].fg}">${window.LAUNCHERS[g.launcher].glyph}</i>` : `<i class="er">${g.esrb}</i>`}</div>
    </div>`;
  }

  function spine(g, s) { return el(spineHTML(g, dims(g, s))); }

  /* ---------- back ---------- */

  function backHTML(g, d) {
    const blurb = `${esc(g.publisher)} · ${g.year}. ${['An adventure years in the making.', 'Explore a vast world full of secrets.', 'Fight, build and survive.', 'The legend continues.'][g.id % 4]}`;
    const shot = n => `<div class="shot" style="background:linear-gradient(${120 + n * 50}deg,${g.colors[n % 3]},${g.colors[(n + 1) % 3]})"></div>`;
    return `<div class="bk ${cls(g, d)}" style="${vars(g, d)}">
      <div class="ins">
        <div class="band">${LOGO[g.console]}</div>
        <div class="bkin">
          <div class="bkt">${esc(g.title)}</div>
          <div class="shots">${shot(0)}${shot(1)}${shot(2)}</div>
          <p>${blurb}</p>
          <div class="lines"><i></i><i></i><i></i><i style="width:60%"></i></div>
          <div class="bkf"><div class="esrb"><b>${g.esrb}</b></div><div class="bar"></div></div>
        </div>
      </div>
    </div>`;
  }

  /* ---------- what is inside ---------- */

  function mediaHTML(g, d, s) {
    const c = C()[g.console];
    const type = g.big ? (g.year < 2005 ? 'cd' : 'dvd') : c.media;
    const disc = { cd: 1, dvd: 1, bd: 1, wiiu: 1 }[type];
    if (disc) {
      const D = 120 * s;
      return `<div class="disc m-${type} ${g.console === 'ps1' ? 'ps1' : ''}" style="--dd:${D}px">
        <div class="dlab">${art(g, 1)}<span>${esc(g.title)}</span></div><i class="hub"></i></div>`;
    }
    const sizes = { switch: [21, 31], ds: [35, 33], '3ds': [35, 33], snes: [120, 86], gbc: [57, 65], gba: [57, 35] };
    const [w, h] = sizes[type];
    return `<div class="cart c-${type} k-${g.console}" style="--cw:${w * s}px;--ch:${h * s}px">
      <div class="clab">${art(g, 0.8)}<span>${esc(g.title)}</span></div></div>`;
  }

  /* ---------- 3D box ---------- */

  function box(g, s) {
    const d = dims(g, s);
    const W = d.w, H = d.h, D = d.d;
    const k = cls(g, d);
    const v = vars(g, d);
    const b = el(`<div class="bx ${k}" style="${v}">
      <div class="fc f-tray"><div class="tray">${mediaHTML(g, d, s)}</div></div>
      <div class="fc f-front"><div class="leaf">${frontHTML(g, d)}<div class="inner"><div class="manual"><div class="mt">${esc(g.title)}</div><div class="lines"><i></i><i></i><i></i><i></i><i style="width:50%"></i></div><div class="mthumb">${art(g, 0.6)}</div></div></div></div></div>
      <div class="fc f-back">${backHTML(g, d)}</div>
      <div class="fc f-left">${spineHTML(g, d)}</div>
      <div class="fc f-right"><div class="edge"></div></div>
      <div class="fc f-top"><div class="edge"></div></div>
      <div class="fc f-bot"><div class="edge"></div></div>
    </div>`);
    const set = (sel, w, h, l, t, tr) => Object.assign(b.querySelector(sel).style, { width: w + 'px', height: h + 'px', left: l + 'px', top: t + 'px', transform: tr });
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

  window.Case = { dims, art, front, spine, box, frontHTML, spineHTML, esc };
})();
