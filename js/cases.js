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
  // a game sold in a cardboard box of its own instead of its console's case: a PC big box, or a bundle
  // with a controller in it (box: its size in mm)
  const ownBox = g => g.box || (g.big && window.BIGBOX);
  const ps3Case = g => styleOf(g) === 'ps3' && !ownBox(g) && !g.steelbook;

  /* ---------- the case's shape ----------
     A case keeps its console's real height and is built around its cover scan,
     so the scan fills the front exactly: nothing cut, no gaps. A plastic case
     keeps a thin rim and its hinge round the scan; a cardboard box simply is its
     scan. Without a scan the case has its real width, and so has a case whose
     picture is only the game's key art (cover.plain): the console's band is
     printed across its top and the art fills the rest. A SteelBook (a tin case) is metal right to its edge,
     with no rim: it keeps its real width and its own art fills it. */
  function layout(g, s) {
    const c = C()[styleOf(g)];
    // a PC game in a thick plastic case (g.bigCase) keeps its case's kind, only bigger
    const m = ownBox(g) || (g.bigCase && window.PCBIGCASE) || c;
    const kind = ownBox(g) ? 'box' : c.kind;
    // a clear DS case is a little thicker than the black ones
    const h = m.h * s, d = (g.clearCase && !ownBox(g) && c.dClear || m.d) * s;
    const r = g.cover && g.cover.ratio;
    let rim = [0, 0, 0, 0]; // top, right, bottom, left
    if (kind === 'keep' && !g.steelbook) { const t = m.w * s * 0.018; rim = [t, t, t, m.w * s * 0.045]; }
    // a PS3 case is clear, with a moulded strip across its top above the cover (Blu-ray Disc and PLAYSTATION 3 raised in it)
    if (ps3Case(g)) rim[0] = h * 0.065;
    // a PlayStation jewel case shows its black tray down a wide hinge on the left
    if (kind === 'jewel') { const t = m.w * s * 0.012; rim = [t, t, t, m.w * s * (styleOf(g) === 'ps1' ? 0.1 : 0.07)]; }
    const ih = h - rim[0] - rim[2];
    const iw = r && !g.cover.plain && !g.steelbook ? ih * r : m.w * s - rim[1] - rim[3];
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
    switch: '<i class="joy"><b></b><b></b></i>', switch2: '<span class="s2l"><i class="joy"><b></b><b></b></i><b>2</b></span>', pc: 'PC',
  };

  const px = v => v.toFixed(1) + 'px';
  function vars(g, L) {
    let v = `--w:${px(L.w)};--h:${px(L.h)};--d:${px(L.d)};--ix:${px(L.ix)};--iy:${px(L.iy)};--iw:${px(L.iw)};--ih:${px(L.ih)}`;
    // a case that came in another colour than its console's usual one (a red Wii case, a white Wii U one)
    if (/^#[0-9a-f]{6}$/i.test(g.caseColor || '')) {
      const c = g.caseColor, f = ink(c);
      // a PS2 spine is the white paper sleeve, so only the plastic takes the colour
      if (styleOf(g) === 'ps2') return v + `;--pl:${c}`;
      // so does a case with EA's printed PC GAME spine (the Sims' grey ones), whose spine goes on taking the cover's colours below
      if (spineOf(g)?.style !== 'ea-pcgame') return v + `;--pl:${c};--sb:${c};--sf:${f};--st:${c};--stf:${f}`;
      v += `;--pl:${c}`;
    }
    // a spine printed in its own colour (a SteelBook's black spine)
    // (on a cardboard box the colour runs right up the spine, the console's name on it too)
    if (/^#[0-9a-f]{6}$/i.test(g.spineColor || '')) {
      const c = g.spineColor, f = ink(c);
      return v + `;--sb:${c};--sf:${f}` + (L.kind === 'box' ? `;--st:${c};--stf:${f}` : '');
    }
    // spines that carry the game's own colours take them from the cover (Kinect games keep their purple, PS1 Platinum ones their silver)
    if (L.c.spineArt && g.colors && !g.kinect && !(g.platinum && styleOf(g) === 'ps1')) {
      const [a, b] = g.colors;
      // a Switch 2 cover's art wraps round the spine, so it shows all the cover's colours down it
      if (styleOf(g) === 'switch2') v += `;--sb:linear-gradient(${g.colors.join(',')}${g.colors.length > 1 ? '' : ',' + a});--sf:${ink(a)}`;
      else v += L.kind === 'box' ? `;--sb:linear-gradient(${a},${b || a});--sf:${ink(a)}` : `;--sb:${a};--sf:${ink(a)}`;
    }
    return v;
  }
  const cls = (g, L) => `k-${styleOf(g)}${g.big ? ' big' : ''}${g.kinect ? ' kinect' : ''}${g.platinum ? ' platinum' : ''}${g.caseColor ? ' tinted' : ''}${g.clearCase ? ' clear' : ''}${g.steelbook ? ' steel' : ''}${g.spineColor ? ' own-spine' : ''} kind-${L.kind}`;
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
  // the clear strip across the top of a PS3 case, with the Blu-ray Disc logo and PLAYSTATION 3 moulded in it
  const PS3_HEAD = '<div class="hdr"><i class="bdl"><b></b><span>Blu-ray Disc</span></i><span class="ps3w">PLAYSTATION 3</span></div>';
  // a Platinum copy's silver band across the top of the cover, over the black one on the scan
  // (left off when the scan is already of the Platinum print, named *-platinum.jpg)
  const PLAT = '<div class="plat"><em>PlayStation<sup>®</sup><b>2</b></em><span>Platinum</span></div>';
  const drawPlat = g => g.platinum && styleOf(g) === 'ps2' && !/-platinum\.\w+$/.test(g.cover.file);
  function frontHTML(g, L, lazy) {
    const inner = g.cover
      ? `<div class="ins${g.cover.plain ? ' plain' : ''}">${g.cover.plain ? `<div class="band">${LOGO[styleOf(g)]}</div>` : ''}${img(g, 'scan', lazy)}${drawPlat(g) ? PLAT : ''}</div>`
      : `<div class="ins ph">
          <div class="band">${LOGO[styleOf(g)]}</div>
          <div class="phc"><b class="pht">${esc(g.title)}</b><i class="phr"></i><span class="phs">${year(g)}${g.publisher ? ' · ' + esc(g.publisher) : ''}</span></div>
          <i class="phn">Cover to come</i>${launcherStrip(g)}
        </div>`;
    return `<div class="cv ${cls(g, L)}" style="${vars(g, L)}">${inner}${ps3Case(g) ? PS3_HEAD : ''}<div class="gl"></div></div>`;
  }
  const front = (g, s, lazy = true) => el(frontHTML(g, layout(g, s), lazy));

  /* ---------- spine ---------- */
  /* ---------- printed spines: a box family's own spine design, drawn ----------
     g.spine says how a copy's spine is printed (README, "Printed spines"). Every part is drawn, so it
     stays sharp at any size: the platform's logo at the top, the title (or the series logo, logos/*.webp,
     turned to read down the spine) and the badges at the foot, each turned to read the same way.
       ea-pcgame  EA's thick PC cases of the early 2000s: black ends, the cover's art squeezed and blurred
                  down the middle under the logo and the pack's name (the default for g.bigCase)
       white      the plain white spine of the DVD-case years: the title in small capitals
       sims4      The Sims 4: a coloured band at each end round the logo, the pack's icon and name below
       art        the cover's art right down the spine under the title */
  const spineOf = g => g.spine || (g.bigCase ? { style: 'ea-pcgame' } : null);
  const SPINE_TOP = {
    pcgame: '<span class="pt-pcgame"><b>P<i>C</i></b><small>GAME</small></span>',
    pccd: '<span class="pt-pccd"><b>PC</b><b>CD</b></span>',
    'pccd-tile': '<span class="pt-tile"><b>PC</b>CD</span>',
    pcdvd: '<span class="pt-dvd"><b>PC</b><b class="dvd">DVD</b><small>ROM</small></span>',
    'pcdvd-mac': '<span class="pt-box"><span class="pt-dvd"><b>PC</b><b class="dvd">DVD</b><small>ROM</small></span><em>Mac</em></span>',
    'pcdvd-mac-dl': '<span class="pt-box"><span class="pt-dvd"><b>PC</b><b class="dvd">DVD</b><small>ROM</small></span><em>MAC</em><small class="dl">DIGITAL DOWNLOAD</small></span>',
    pcmac: '<span class="pt-box pt-pcmac"><small>PC/MAC</small><i class="arrow"></i></span>',
    // PC DVD-ROM, or PC CD ROM, in white on a dark block (Bully, the Sold Out budget range)
    'pcdvd-block': '<span class="pt-dvd pt-light"><b>PC</b><b class="dvd">DVD</b><small>ROM</small></span>',
    'pccdrom-block': '<span class="pt-pccd pt-cdrom"><b>PC</b><b>CD</b><small>ROM</small></span>',
    // Games for Windows: the Windows orb over its name in a white band, LIVE under it (GTA IV) or an orange line (the Episodes)
    gfw: '<span class="pt-gfw"><i class="orb"></i><span><b>Games</b><small>for Windows</small></span></span>',
    'gfw-live': '<span class="pt-gfw"><i class="orb"></i><span><b>Games</b><small>for Windows</small><em>LIVE</em></span></span>',
  };
  // the pack's icon on a Sims 4 spine (white, on the pack's colour)
  const SIMS4_ICON = {
    together: '<circle cx="12" cy="7" r="3"/><circle cx="5" cy="9" r="2.4"/><circle cx="19" cy="9" r="2.4"/><path d="M7 21v-5a5 5 0 0 1 10 0v5z"/><path d="M1 21v-4a4 4 0 0 1 5-3.9V21zM23 21v-4a4 4 0 0 0-5-3.9V21z"/>',
    work: '<path d="M9 5V3h6v2h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1zm2 0h2V4h-2z"/>',
    city: '<path d="M2 22V10h5V6h4V2h5v8h6v12zM5 13h2v2H5zm5-4h2v2h-2zm0 4h2v2h-2zm5-8h2v2h-2zm0 4h2v2h-2zm0 4h2v2h-2zm4 2h2v2h-2z" fill-rule="evenodd"/>',
    paw: '<ellipse cx="12" cy="16" rx="5" ry="4.5"/><circle cx="5" cy="10" r="2.3"/><circle cx="19" cy="10" r="2.3"/><circle cx="9" cy="5.5" r="2.3"/><circle cx="15" cy="5.5" r="2.3"/>',
    seasons: '<circle cx="9" cy="8" r="4"/><path d="M7 21a5 5 0 0 1 .6-9.9 6 6 0 0 1 11.3 2A4 4 0 0 1 18 21z"/>',
    famous: '<path d="M3 9h4l9-5v16l-9-5H3zm3 7h3l1 5H7z"/><path d="M18 8l3-2v12l-3-2z"/>',
    island: '<path d="M11 22c0-6 1-10 3-13-3-1-6 0-8 2 1-3 4-5 7-4-1-2-4-3-6-2 2-2 6-2 8 1 1-2 4-2 6-1-2 0-4 1-5 3 3 0 5 2 6 4-2-2-5-2-7-1-1 3-2 7-2 11z"/><path d="M3 22c3-2 15-2 18 0z"/>',
  };
  // MA in its white hexagon, as Australia's MA15+ label prints it
  const MA_HEX = '<svg viewBox="0 0 32 20" aria-hidden="true"><polygon points="7.5,1.2 24.5,1.2 30.8,10 24.5,18.8 7.5,18.8 1.2,10" fill="none" stroke="#fff" stroke-width="1.6"/><text x="16" y="14.2" text-anchor="middle" fill="#fff" font-family="Arial, sans-serif" font-weight="700" font-size="11.5">MA</text></svg>';
  // the badges at the foot: the age rating (Australia's, or another country's), then EA's logo as that year printed it
  function spineBadge(k) {
    // a badge ending in ^ stands upright, across the spine, instead of turned to read down it (LucasArts' PG)
    if (k.endsWith('^')) return spineBadge(k.slice(0, -1)).replace('class="sbg', 'class="sbg up');
    if (k === 'M' || k === 'G' || k === 'PG' || k === 'MA15') return `<i class="sbg rate rate-${k}"><b>${k === 'MA15' ? 'MA' : k}</b></i>`;
    // Australia's later MA15+ label: MA in a white hexagon and 15+ on red, RESTRICTED under them
    if (k === 'MA15R') return `<i class="sbg ma15r"><b>${MA_HEX}<span>15+</span></b><small>RESTRICTED</small></i>`;
    // a British 18: white numbers on a red disc
    if (k === 'bbfc18') return '<i class="sbg pegi bbfc18"><b>18</b></i>';
    if (k === 'bbfc15') return '<i class="sbg pegi bbfc15"><b>15</b></i>';
    // PC DVD in a grey block at the very foot (the Games for Windows years), on one line or two
    if (k === 'pcdvd' || k === 'pcdvd2') return `<i class="sbg txt pcdvd">PC${k === 'pcdvd2' ? '<br>' : ' '}<b>DVD</b></i>`;
    if (k === 'rockstar') return '<i class="sbg rockstar"><img src="logos/rockstar.webp" alt="" loading="lazy" decoding="async" draggable="false"></i>';
    // small print across the spine (\n breaks it), or with txtv: turned to read down it (a catalogue number)
    if (/^txtv?:/.test(k)) return `<i class="sbg txt${k[3] === 'v' ? ' down' : ''}">${esc(k.slice(k.indexOf(':') + 1)).replace(/\n/g, '<br>')}</i>`;
    if (/^\d+$/.test(k)) return `<i class="sbg pegi"><b>${k}</b></i>`;
    if (k === 'ea-games') return '<i class="sbg ea-games"><b>EA</b><small>GAMES</small></i>';
    if (k === 'ea-mark') return '<i class="sbg ea-mark">EA</i>';
    return `<i class="sbg ea ${k}"><b>EA</b></i>`;
  }
  // Sold Out Software's budget spine: its logo, then the orange band and the fine lines before the title
  const SOLDOUT = '<i class="so-logo"><b>$old</b><b class="out">Out</b><small>software</small></i><i class="so-band"></i><i class="so-lines"></i>';
  function printedSpineHTML(g, L, P) {
    const style = P.style || 'white';
    const solid = /^#[0-9a-f]{6}$/i.test(P.color || '') ? P.color : null;
    const art = (style === 'ea-pcgame' || style === 'art') && !solid && g.cover && g.cover.file ? `--cv:url('${esc(g.cover.file)}');` : '';
    // the title as printed: \n breaks a line, *…* is set smaller (EXPANSION PACK*)
    const text = P.title || g.title;
    const fit = Math.min(1, 22 / text.replace(/\*[^*]*\*/g, m => ' '.repeat(Math.ceil(m.length * .6))).replace(/\n.*/, '').length);
    const words = esc(text).replace(/\*([^*]+)\*/g, '<small>$1</small>').replace(/\n/g, '<br>').replace(/[™®]/g, '<sup>$&</sup>');
    const logo = P.logo ? `<img class="slogo${P.wide ? ' wide' : ''}" src="${esc(P.logo)}" alt="" loading="lazy" decoding="async" draggable="false">` : '';
    // the pack's name after the logo; subStyle prints it another way (gold for the Episodes, a plaque for Bully)
    const sub = P.sub ? `<span class="ssub${P.sub.length > 14 && !P.subStyle ? ' long' : ''}${P.subStyle ? ' ss-' + P.subStyle : ''}"><b>${esc(P.sub)}</b>${P.small ? `<small>${esc(P.small)}</small>` : ''}</span>` : '';
    const ttl = logo || P.sub ? `${logo}${sub}` : `<span class="stext${P.caps ? ' caps' : ''}${P.font ? ' f-' + P.font : ''}" style="--fit:${fit.toFixed(2)}">${words}</span>`;
    const top = SPINE_TOP[P.top || (style === 'ea-pcgame' ? 'pcgame' : 'pccd')] || '';
    const foot = (P.foot || (style === 'ea-pcgame' ? ['ea-games'] : ['ea'])).map(spineBadge).join('');
    const vs = `${vars(g, L)};${art}${solid ? `--band:${solid};--bandf:${ink(solid)};` : ''}${P.accent ? `--acc:${P.accent};` : ''}${P.stripe ? `--stripe:${P.stripe};` : ''}`;
    const k = `sp ${cls(g, L)} printed ps-${style} top-${P.top || 'default'}${style === 'sims4' && !P.label ? ' s4-short' : ''}${art ? ' art' : ''}${solid ? ' solid' : ''}`;
    if (style === 'sims4') return `<div class="${k}" style="${vs}">
      <div class="s4-a"><div class="ptop">${top}</div>${P.label ? `<span class="s4-label">${esc(P.label)}</span>` : ''}</div>
      <div class="s4-b">${logo}</div>
      <div class="s4-c">${P.icon && SIMS4_ICON[P.icon] ? `<svg class="s4-icon" viewBox="0 0 24 24" aria-hidden="true">${SIMS4_ICON[P.icon]}</svg>` : ''}<span class="s4-name">${esc(P.sub || '')}</span><div class="pfoot">${foot}</div></div>
    </div>`;
    // badges straight under the top (San Andreas' Rockstar logo), and the budget label's own band before the title
    const head = P.head ? `<div class="phead">${P.head.map(spineBadge).join('')}</div>` : '';
    const deco = P.deco === 'soldout' ? SOLDOUT : '';
    return `<div class="${k}" style="${vs}">
      <div class="ptop">${top}</div>${P.stripe ? '<i class="pstripe"></i>' : ''}${head}
      <div class="pmid">${P.band === 'classics' ? '<i class="pclassics"><b>CLASSICS</b></i>' : ''}${deco}<div class="pttl">${ttl}</div></div>
      <div class="pfoot">${foot}</div>
    </div>`;
  }

  function spineHTML(g, L) {
    // a picture of the real side (a box whose side is printed in its own way: The Sims 3 Collector's Edition's)
    if (g.cover && g.cover.spine && g.format !== 'digital') return `<div class="sp ${cls(g, L)} photo" style="${vars(g, L)}"><img class="scan" src="${esc(g.cover.spine)}" alt="" loading="lazy" decoding="async" draggable="false"></div>`;
    const P = spineOf(g);
    if (P && g.format !== 'digital') return printedSpineHTML(g, L, P);
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
    // a picture of the real back (a SteelBook's), else a back printed from the game's details
    if (g.cover && g.cover.back) return `<div class="bk ${cls(g, L)}" style="${vars(g, L)}"><div class="ins"><img class="scan" src="${esc(g.cover.back)}" alt="" loading="lazy" decoding="async" draggable="false"></div><div class="gl"></div></div>`;
    return `<div class="bk ${cls(g, L)}" style="${vars(g, L)}">
      <div class="ins${g.cover ? '' : ' ph'}">${img(g, 'blur')}
        <div class="band">${LOGO[styleOf(g)]}</div>
        <div class="bkin">
          <div class="bkt">${esc(g.title)}</div>
          <div class="bkm"><b>Released</b> ${date(g.released, true)}<br>${[g.publisher, g.developer].filter(Boolean).map(esc).join(' · ')}<br>${esc(g.genre || '')}</div>
          <div class="bkf"><span class="rg">${esc(R.name)}</span><span class="bar"></span></div>
        </div>
      </div>
    </div>`;
  }

  /* ---------- cartridges and game cards ----------
     Drawn from the real ones, in millimetres: the outline, the grip ridges, the
     raised logo, the recess the label sits in. The label is the cover art, or the
     title. The SNES, Game Boy, GBA and Switch shapes are traced from photos of real
     cartridges; the ones they replaced are kept in legacy/cases.js. */
  // a groove: a dark line with the light catching its lower lip
  const groove = (x1, x2, y, dark, w = .7) => `<path d="M${x1} ${y}H${x2}" stroke="#000" stroke-opacity="${dark ? 0.45 : 0.2}" stroke-width="${w}"/><path d="M${x1} ${y + w * .75}H${x2}" stroke="#fff" stroke-opacity="${dark ? 0.08 : 0.4}" stroke-width="${w * .6}"/>`;
  // lettering moulded into the plastic: a shadow above, the light below
  const emboss = (x, y, size, text, dark, extra = '') => `
    <text x="${x}" y="${y + size * .05}" font-size="${size}" ${extra} fill="#fff" fill-opacity="${dark ? 0.1 : 0.45}">${text}</text>
    <text x="${x}" y="${y}" font-size="${size}" ${extra} fill="#000" fill-opacity="${dark ? 0.5 : 0.22}">${text}</text>`;
  const SANS = `font-family="Arial, Helvetica, sans-serif" font-weight="700"`;
  // glitter moulded into the plastic (the Gold and Silver Pokémon cartridges): fine specks of light scattered
  // over the face, the same every time; skip: [x0, y0, x1, y1] areas outside the outline (a notch)
  const glitter = (w, h, skip = [], n = 520) => {
    let seed = 7;
    const rnd = () => (seed = (seed * 16807) % 2147483647) / 2147483647;
    let out = '';
    for (let i = 0; i < n; i++) {
      const x = rnd() * w, y = rnd() * h, r = .07 + rnd() * .11, o = .35 + rnd() * .6;
      if (skip.some(([x0, y0, x1, y1]) => x >= x0 && x <= x1 && y >= y0 && y <= y1)) continue;
      out += `<circle cx="${x.toFixed(2)}" cy="${y.toFixed(2)}" r="${r.toFixed(2)}" fill="#fff" fill-opacity="${o.toFixed(2)}"/>`;
    }
    return `<g aria-hidden="true">${out}</g>`;
  };
  // the Game Boy cartridge (DMG), traced from photos: the notch in its top right corner, the raised "Nintendo
  // GAME BOY™" in a long hollow between two sets of grip ridges, the label down its lower part between grey
  // strips, the arrow under it. It came grey, and in colour for some games (Pokémon Blue, Yellow, the glittery
  // Gold and Silver). g.cartMould: what an unofficial shell says in place of Nintendo GAME BOY ("GAME AMY":
  // GAME big, the rest small)
  const gbBody = (g, f, dark = isDark(f)) => {
    const [big, ...small] = (g.cartMould || '').split(' ');
    const mould = g.cartMould
      ? emboss(28.3, 9.9, 4.4, `${big}${small.length ? `<tspan font-size="2.2" dx="1.2">${small.join(' ')}</tspan>` : ''}`, dark, `${SANS} text-anchor="middle"${/^GAME$/.test(g.cartMould) ? ' font-style="italic" font-weight="400"' : ''}`)
      : emboss(9.2, 9.9, 2.4, 'Nintendo', dark, `${SANS} font-style="italic" textLength="10" lengthAdjust="spacingAndGlyphs"`)
        + emboss(21.3, 9.9, 4.4, 'GAME BOY', dark, `${SANS} textLength="21.5" lengthAdjust="spacingAndGlyphs"`)
        + emboss(43.2, 10, 1.3, 'TM', dark, SANS);
    return `
    <path d="M2 0H50.4Q50.8 0 50.8 .4V4.1Q50.8 4.5 51.2 4.5H57V63Q57 65 55 65H2Q0 65 0 63V2Q0 0 2 0Z" fill="${f}"/>
    <path d="M1.4 27V63.6M55.6 27V63.6M.3 27H1.4M55.6 27H56.7" stroke="#000" stroke-opacity="${dark ? 0.45 : 0.16}" stroke-width=".35" fill="none"/>
    <path d="M1.75 27.3V63.4M55.95 27.3V63.4" stroke="#fff" stroke-opacity="${dark ? 0.07 : 0.3}" stroke-width=".25"/>
    <path d="M2 .45H50.3M51.2 4.95H56.6" stroke="#fff" stroke-opacity="${dark ? 0.14 : 0.5}" stroke-width=".6"/>
    ${[5.6, 7.7, 9.7, 11.7].map(y => groove(.8, 5.4, y, dark) + groove(46.6, 56.4, y, dark)).join('')}
    <rect x="6.1" y="2.2" width="39.7" height="11.6" rx="5.8" fill="#000" fill-opacity="${dark ? 0.25 : 0.07}"/>
    <path d="M8 2.75H44" stroke="#000" stroke-opacity="${dark ? 0.35 : 0.16}" stroke-width=".6"/>
    <path d="M8.4 13.3H43.6" stroke="#fff" stroke-opacity="${dark ? 0.1 : 0.45}" stroke-width=".55"/>
    ${mould}
    <rect x="6.1" y="17.2" width="44.4" height="38" rx="1.4" fill="#000" fill-opacity="${dark ? 0.3 : 0.08}"/>
    <path d="M7.4 17.6H49.2" stroke="#000" stroke-opacity="${dark ? 0.4 : 0.18}" stroke-width=".5"/>
    <rect x="7.6" y="18.9" width="40.5" height="35.5" rx=".7" fill="#d2d2ce"/>
    <rect x="8.2" y="22.5" width=".5" height="27" fill="#000" fill-opacity=".22"/><rect x="46.9" y="31" width=".5" height="14" fill="#000" fill-opacity=".22"/>
    <path d="M24.8 56.8H33.3L29 60.6Z" fill="#000" fill-opacity="${dark ? 0.35 : 0.1}"/>
    <path d="M33.3 56.8L29 60.6L24.8 56.8" stroke="#fff" stroke-opacity="${dark ? 0.08 : 0.35}" stroke-width=".45" fill="none" transform="translate(0 .35)"/>
    <rect y="62" width="57" height="3" rx="1.5" fill="#000" fill-opacity=".08"/>
    ${g.cartGlitter ? glitter(57, 65, [[50.8, 0, 57, 4.5]]) : ''}`;
  };
  // the green circuit board inside a clear Game Boy Color-only cartridge, standing in the middle of its thickness
  // where it shows through both sides of the shell. Its front side (cgbBoard): the chips, the traces and vias, and the
  // coin battery of a game that saves (g.cartBattery: its middle, in mm, when not the usual place); most of it lies
  // behind the label. Its back side (cgbBoardBack, drawn as seen from behind): the traces, the gold contacts along the
  // bottom, © 1998 Nintendo printed down its side, the holes for the dimple's post and the screw
  const BOARD = 'M4.6 15.4H52.4Q53 15.4 53 16V64.6H4V16Q4 15.4 4.6 15.4Z';
  const vias = pts => pts.map(([x, y]) => `<circle cx="${x}" cy="${y}" r=".32" fill="#c9a24e"/><circle cx="${x}" cy="${y}" r=".13" fill="#0d2a1a"/>`).join('');
  const traces = (paths, col) => paths.map(d => `<path d="${d}" fill="none" stroke="${col}" stroke-width=".22" stroke-linejoin="round"/>`).join('');
  const cgbBoard = g => {
    const [bx, by] = Array.isArray(g.cartBattery) ? g.cartBattery : [38, 13.5];
    return `<svg viewBox="0 0 57 65" preserveAspectRatio="none" aria-hidden="true">
    <path d="${BOARD}" fill="#1a5638"/>
    <path d="M4.6 15.6H52.4" stroke="#8fd0a4" stroke-opacity=".5" stroke-width=".25"/>
    ${traces(['M6 17.2H30L32 19.2H46', 'M6 18.4H25', 'M6 19.6H22L24 21.6V30', 'M44 21V40L40 44H18', 'M8 24V52L12 56H44', 'M48 26V58', 'M12 60H45', 'M30 16.4V20'], '#2f8a5a')}
    ${vias([[7, 21.5], [9, 21.5], [27, 16.6], [33, 20.6], [45.5, 19.8], [47.5, 22], [10, 58.8], [16, 58.8], [22, 58.8], [41, 58.8], [47, 60.5], [50, 25]])}
    <rect x="13.6" y="16.3" width="8" height="2.4" rx=".2" fill="#151515"/>
    ${Array.from({ length: 8 }, (_, k) => `<rect x="${(13.9 + k * .95).toFixed(2)}" y="18.7" width=".4" height=".55" fill="#bdb6a4"/>`).join('')}
    <rect x="26.6" y="15.9" width="1.8" height="1" rx=".2" fill="#c8b48a"/><rect x="24" y="17.6" width="1.2" height=".7" fill="#4a4036"/>
    <rect x="11" y="27" width="17" height="11" rx=".4" fill="#131313"/><rect x="31" y="29" width="10" height="8" rx=".3" fill="#131313"/>
    <rect x="15" y="44" width="11" height="6" rx=".3" fill="#131313"/>
    ${g.cartBattery ? `<circle cx="${bx}" cy="${by}" r="8" fill="#c9ccd0"/>
      <circle cx="${bx}" cy="${by}" r="7.3" fill="none" stroke="#8a8d93" stroke-width=".3"/>
      <circle cx="${bx - 2}" cy="${by - 2.5}" r="3" fill="#fff" fill-opacity=".25"/>
      <rect x="${bx - 6}" y="${by + 3.6}" width="13" height="1.8" rx=".9" fill="#e2e4e6" stroke="#6d7076" stroke-opacity=".6" stroke-width=".2"/>` : ''}
    </svg>`;
  };
  const cgbBoardBack = () => `<svg viewBox="0 0 57 65" preserveAspectRatio="none" aria-hidden="true">
    <path d="${BOARD}" fill="#1a5638"/>
    ${traces(['M12 22V40L20 46', 'M18 18V30L34 36', 'M40 20V44L30 50', 'M46 24V52', 'M8 30V54', 'M22 20H36L38 22V30', 'M14 56V50H40V56', 'M26 24V34'], '#2f8a5a')}
    ${vias([[12, 22], [18, 18], [22, 20], [40, 20], [46, 24], [8, 30], [26, 24], [34, 36], [20, 46], [30, 50], [14, 50], [40, 50], [36, 26], [44, 30]])}
    ${Array.from({ length: 32 }, (_, k) => `<rect x="${(4.9 + k * 1.5).toFixed(2)}" y="57.8" width=".95" height="6.6" rx=".15" fill="#d8b766"/>`).join('')}
    <text x="49.6" y="27" transform="rotate(90 49.6 27)" ${SANS} font-size="1.7" fill="#d9b860">© 1998 Nintendo</text>
    <circle cx="28.5" cy="31.3" r="1.6" fill="#0d2a1a"/><circle cx="28.5" cy="46.3" r="2.6" fill="#0d2a1a"/>
    </svg>`;
  // the Game Boy Color-only cartridge, traced from photos of real ones: the same size as a Game Boy cartridge
  // but in clear smoky plastic, with no notch (so it stays out of the original Game Boy's power lock), the top
  // corners rounded. GAME BOY COLOR is moulded big in hollow letters across the top, with two ribs under it,
  // short grip ridges down the top of each side, the shell's walls showing as a frame inside its edge, the
  // circuit board behind, the label lower down than on a Game Boy cartridge, the faint arrow under it
  // the clear plastic: how much of what is behind it each side of the shell hides
  const CLEAR = .42, CGB_OUTLINE = 'M2.5 0H54.5Q57 0 57 2.5V64Q57 65 56 65H1Q0 65 0 64V2.5Q0 0 2.5 0Z';
  const cgbBody = (g, f, dark = isDark(f)) => `
    <path d="${CGB_OUTLINE}" fill="${f}" fill-opacity="${CLEAR}"/>
    <path d="M2.6 2.4H54.4V62.6H2.6Z" fill="none" stroke="#000" stroke-opacity=".35" stroke-width=".55"/>
    <path d="M3.1 2.9H53.9" stroke="#fff" stroke-opacity=".22" stroke-width=".3"/>
    <rect x="6.6" y="3.2" width="2.4" height="13.6" rx=".4" fill="#000" fill-opacity=".22"/>
    <rect x="48.8" y="4.4" width="2" height="12.6" rx=".4" fill="#000" fill-opacity=".3"/>
    ${[5.8, 7.6, 9.4, 11.2, 13].map(y => `<path d="M0 ${y}H.9M56.1 ${y}H57" stroke="#000" stroke-opacity=".5" stroke-width=".55"/>`).join('')}
    ${[['#fff', .3, .12], ['#000', .55, 0]].map(([col, o, dy]) => `<text x="28.4" y="${(11.5 + dy).toFixed(2)}" font-family="'Arial Black', 'Arial Rounded MT Bold', Arial, sans-serif" font-weight="900" font-style="italic" font-size="4.2" text-anchor="middle" textLength="37.4" lengthAdjust="spacingAndGlyphs" fill="none" stroke="${col}" stroke-opacity="${o}" stroke-width=".22">GAME BOY COLOR</text>`).join('')}
    <path d="M15 15.4H41.6M13.5 17.3H40" stroke="#000" stroke-opacity=".45" stroke-width=".35"/>
    <path d="M15 15.75H41.6M13.5 17.65H40" stroke="#fff" stroke-opacity=".3" stroke-width=".22"/>
    <rect x="6" y="20.3" width="45" height="37.9" rx=".9" fill="#000" fill-opacity=".18" stroke="#fff" stroke-opacity=".25" stroke-width=".3"/>
    <path d="M26 59.6H31L28.5 62Z" fill="none" stroke="#fff" stroke-opacity=".25" stroke-width=".3"/>
    <path d="M1.5 62.6H55.5" stroke="#000" stroke-opacity=".4" stroke-width=".4"/>
    <path d="M2.5 .35H54.5" stroke="#fff" stroke-opacity=".35" stroke-width=".5"/>
    <path d="M57 0L0 65V0Z" fill="#fff" fill-opacity=".04"/>`;
  // a DS game card: a touch taller than wide, the bottom left corner cut off, the label in a frame
  // round its edge: white, with the logo across the top and a white strip at the bottom
  const dsBody = f => `
    <path d="M1.6 0H31.4Q33 0 33 1.6V33.4Q33 35 31.4 35H3.2L0 31.8V1.6Q0 0 1.6 0Z" fill="${f}"/>
    <path d="M1.6 .35H31.4" stroke="#fff" stroke-opacity=".16" stroke-width=".35"/>
    <path d="M2.8 1.6H30.2Q31.4 1.6 31.4 2.8V32.2Q31.4 33.4 30.2 33.4H5.5L1.6 29.5V2.8Q1.6 1.6 2.8 1.6Z" fill="#000" fill-opacity=".35"/>
    <path d="M31.1 2.4V32.6M5.6 33.1H30.4" stroke="#fff" stroke-opacity=".12" stroke-width=".3" fill="none"/>
    <path d="M3.3 2.2H29.7Q30.4 2.2 30.4 2.9V31.3Q30.4 32 29.7 32H5.6L2.6 29V2.9Q2.6 2.2 3.3 2.2Z" fill="#f6f7f8"/>
    <text x="16.6" y="6.15" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-weight="700" fill="#2a2a2e"><tspan font-size="1.25" letter-spacing=".12">NINTENDO</tspan><tspan font-size="3.2" font-weight="900" dx=".2">DS</tspan></text>
    <path d="M2.6 7.3H30.4M2.6 28.8H30.4" stroke="#000" stroke-opacity=".12" stroke-width=".2"/>
    <rect x="21.5" y="30" width="7" height=".5" fill="#000" fill-opacity=".18"/>`;
  // a 3DS game card: light grey, the same size as a DS card but with the tab sticking out of its right
  // side at the top, flush with the top edge and sloping back in below (it stops the card going into a
  // DS), and the label in a frame with rounded corners: white, the logo across the top, a white strip below
  const threeDsBody = f => `
    <path d="M1.4 0H33.6Q34.8 0 34.8 1.2V5.6L33 7.9V33.6Q33 35 31.6 35H1.8L0 33.2V1.4Q0 0 1.4 0Z" fill="${f}"/>
    <path d="M1.4 .35H33.5M34.45 1.2V5.5" stroke="#fff" stroke-opacity=".75" stroke-width=".35" fill="none"/>
    <path d="M33 8V33.6Q33 34.7 31.6 34.7H1.9" stroke="#000" stroke-opacity=".14" stroke-width=".35" fill="none"/>
    <path d="M3.3 1.5H29.7Q30.8 1.5 30.8 2.6V32Q30.8 33.2 29.6 33.2H3.6Q2.3 33.2 2.3 31.9V2.5Q2.3 1.5 3.3 1.5Z" fill="#000" fill-opacity=".1"/>
    <path d="M30.5 2.4V32.2Q30.5 32.9 29.6 32.9H3.6" stroke="#fff" stroke-opacity=".7" stroke-width=".3" fill="none"/>
    <path d="M3.6 2.2H29.4Q30 2.2 30 2.8V31.4Q30 32.4 29 32.4H4.4Q3 32.4 3 31V2.8Q3 2.2 3.6 2.2Z" fill="#f7f8f9"/>
    <text x="16.9" y="6.5" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-weight="700" fill="#2a2a2e"><tspan font-size="1.65" letter-spacing=".16" fill="#4a4b50">NINTENDO</tspan><tspan font-size="4.2" font-weight="900" dx=".25" letter-spacing="-.1"><tspan fill="#fff" stroke="#d0021b" stroke-width=".22">3</tspan>DS</tspan><tspan font-size=".6" dx=".1">.</tspan></text>
    <path d="M3 7.3H30M3 27.7H30" stroke="#000" stroke-opacity=".12" stroke-width=".2"/>`;
  // the region at the end of the code printed under a Switch card's label
  const cardRegion = g => ({ AUS: 'AUS', USA: 'USA', 'NTSC-U': 'USA', JPN: 'JPN', 'NTSC-J': 'JPN' }[g.region] || 'EUR');
  // a Switch game card: rounded all round, the little tab at the top, the label with the console's red
  // band over the art and its code on a black strip, the arrow under it
  const cardBody = (band, two) => (g, f) => `
    <path d="M1.7 0H19.3Q21 0 21 1.7V29.3Q21 31 19.3 31H1.7Q0 31 0 29.3V1.7Q0 0 1.7 0Z" fill="${f}"/>
    <path d="M1.7 .3H19.3" stroke="#fff" stroke-opacity=".2" stroke-width=".3"/>
    <rect x=".9" y="2.2" width="19.2" height="25.8" rx="1.3" fill="#000" fill-opacity=".12" stroke="#000" stroke-opacity=".25" stroke-width=".25"/>
    <rect x="7.2" y="1.1" width="6.6" height="1.5" rx=".45" fill="#000" fill-opacity=".3"/>
    <rect x="1.4" y="2.5" width="18.2" height="24.4" rx=".9" fill="#141414"/>
    <path d="M2.3 2.5H18.7Q19.6 2.5 19.6 3.4V8.5H1.4V3.4Q1.4 2.5 2.3 2.5Z" fill="${band}"/>
    ${two ? `
      <rect x="7.6" y="3.4" width="1.2" height="2.9" rx=".45" fill="#fff"/><circle cx="8.2" cy="4.2" r=".28" fill="${band}"/>
      <rect x="9.05" y="3.55" width="1" height="2.6" rx=".4" fill="none" stroke="#fff" stroke-width=".28"/><circle cx="9.55" cy="5.4" r=".25" fill="#fff"/>
      <text x="10.5" y="6.25" ${SANS} font-size="3.6" fill="#fff">2</text>
      <text x="10.5" y="7.25" ${SANS} font-size=".72" letter-spacing=".08" text-anchor="middle" fill="#fff">NINTENDO</text>
      <text x="10.5" y="8.15" ${SANS} font-size=".9" text-anchor="middle" fill="#fff">SWITCH</text>` : `
      <rect x="3.4" y="3.7" width="1.4" height="3.4" rx=".55" fill="#fff"/><circle cx="4.1" cy="4.6" r=".32" fill="${band}"/>
      <rect x="5.05" y="3.85" width="1.15" height="3.1" rx=".45" fill="none" stroke="#fff" stroke-width=".3"/><circle cx="5.62" cy="6.1" r=".3" fill="#fff"/>
      <text x="7" y="5.25" ${SANS} font-size="1.45" letter-spacing=".22" fill="#fff" textLength="10.6" lengthAdjust="spacingAndGlyphs">NINTENDO</text>
      <text x="6.9" y="7.55" ${SANS} font-size="2.4" fill="#fff" textLength="10.9" lengthAdjust="spacingAndGlyphs">SWITCH</text>`}
    <text x="10.5" y="26.1" font-family="'DejaVu Sans Mono', Menlo, Consolas, monospace" font-size="1.05" letter-spacing=".12" text-anchor="middle" fill="#e8e8e8">${two ? 'LB-XXXXX-XXX' : 'LA-H-XXXXX'}-${cardRegion(g)}</text>
    <path d="M9.2 28.4H11.8L10.5 29.8Z" fill="#000" fill-opacity=".35"/>`;
  // the GBA's moulded GAMEBOY ADVANCE: hollow outlined letters inside a raised rounded frame, between the raised
  // rim's arch (rimHTML) and the label recess
  const gbaLogo = dark => `
    <rect x="15.3" y="4.15" width="27" height="2.85" rx=".9" fill="#fff" fill-opacity="${dark ? 0.03 : 0.08}" stroke="#000" stroke-opacity="${dark ? 0.5 : 0.25}" stroke-width=".22"/>
    <rect x="15.55" y="4.4" width="26.5" height="2.35" rx=".7" fill="none" stroke="#fff" stroke-opacity="${dark ? 0.12 : 0.45}" stroke-width=".16"/>
    ${[['#fff', dark ? 0.14 : 0.5, .1], ['#000', dark ? 0.55 : 0.3, 0]].map(([col, o, dy]) => `<text x="28.65" y="${(6.47 + dy).toFixed(2)}" ${SANS} font-size="2.3" text-anchor="middle" textLength="25" lengthAdjust="spacingAndGlyphs" fill="none" stroke="${col}" stroke-opacity="${o}" stroke-width=".13">GAMEBOY ADVANCE</text>`).join('')}`;
  const GB_PTS = [[0, 1], [1, 0], [50.8, 0], [50.8, 4.5], [57, 4.5], [57, 64], [56, 65], [1, 65], [0, 64]];
  // the American cartridge's screws: Nintendo's security screws, or the cross-head ones of an unofficial shell
  const snesScrews = g => [12, 120].map(x => g.cartScrew === 'phillips'
    ? `<circle cx="${x}" cy="78.4" r="2.7" fill="#000" fill-opacity=".5"/><circle cx="${x}" cy="78.4" r="1.9" fill="#a9abae"/><circle cx="${x}" cy="78.4" r="1.9" fill="none" stroke="#fff" stroke-opacity=".4" stroke-width=".2"/><path d="M${x - 1.1} 78.4H${x + 1.1}M${x} 77.3V79.5" stroke="#3a3b3e" stroke-width=".42"/>`
    : `<circle cx="${x}" cy="78.4" r="2.7" fill="#6e6440"/><circle cx="${x}" cy="78.4" r="2.1" fill="#c8b06a"/><path d="M${x - .9} 77.9 L${x} 77.4 L${x + .9} 77.9 V78.9 L${x} 79.4 L${x - .9} 78.9Z" fill="#4c4428"/>`).join('');

  /* The PAL cartridge, the same shell as the Super Famicom's (Europe, Australia, Japan), traced from photos of a
     real one (Super Mario Kart, SNSP-MK-AUS) with each part placed at its share of the width and height, at the
     size measured on real ones: 129 mm wide, 87 high, 20 thick. Narrower and taller than the American one and
     smooth in front: the middle stands half a millimetre above the sides at the top, the top corners are round, five
     slots run through each side near the top, and seams part the middle from the sides. In front, a band across the
     top, the wide label recess under it (the label all but fills it: 111 by 37 mm), the long rounded grip with ribs
     in it (its middle third raised) and the screws deep in holes at the bottom corners. Behind (BACKS.sfc), fine ribs
     all over the middle, the raised panel with the Nintendo logo, the recess with the silver warning sticker and
     the dimple, the holes at the bottom corners, and two notches cut into the top of the back half only. There
     is no top label: nothing is printed on the top edge */
  const SFC = (() => {
    const W = 129, H = 87, sl = 18.9, sr = 110.1, R = 3.3, r = 1.2, wy = .5;
    const slots = [7.5, 11.4, 15.3, 19.2, 23.1], sh = .9, sd = 1.3;
    const gaps = [[25.5, 35], [94, 103.5]], gd = 1.7;
    const arc = (cx, cy, rad, a0, a1, n = 5) => Array.from({ length: n + 1 }, (_, i) => {
      const a = (a0 + (a1 - a0) * i / n) * Math.PI / 180;
      return [+(cx + rad * Math.cos(a)).toFixed(2), +(cy + rad * Math.sin(a)).toFixed(2)];
    });
    const outline = withGaps => [
      ...arc(R, wy + R, R, 180, 270), [sl, wy], [sl, 0],
      ...(withGaps ? gaps.flatMap(([a, b]) => [[a, 0], [a, gd], [b, gd], [b, 0]]) : []),
      [sr, 0], [sr, wy], ...arc(W - R, wy + R, R, 270, 360),
      ...slots.flatMap(y => [[W, y - sh], [W - sd, y - sh + .25], [W - sd, y + sh - .25], [W, y + sh]]),
      ...arc(W - r, H - r, r, 0, 90, 3), ...arc(r, H - r, r, 90, 180, 3),
      ...[...slots].reverse().flatMap(y => [[0, y + sh], [sd, y + sh - .25], [sd, y - sh + .25], [0, y - sh]]),
    ];
    return { W, H, sl, sr, gd, pts: outline(true), front: outline(false), gaps: gaps.map(([a, b]) => [a, 0, b, gd]) };
  })();
  // a screw deep in its hole: Nintendo's security screw, its six-lobed head round a pin
  const deepScrew = (x, y) => `
      <circle cx="${x}" cy="${y}" r="2.35" fill="#000" fill-opacity=".38"/>
      <path d="M${x - 2.1} ${y + 1.05}A2.35 2.35 0 0 0 ${x + 2.1} ${y + 1.05}" stroke="#fff" stroke-opacity=".45" stroke-width=".3" fill="none"/>
      <circle cx="${x + .15}" cy="${y + .15}" r="1.65" fill="#857259"/><circle cx="${x + .15}" cy="${y + .15}" r="1.65" fill="none" stroke="#000" stroke-opacity=".35" stroke-width=".25"/>
      <path d="${Array.from({ length: 6 }, (_, i) => { const a = i * Math.PI / 3; return `${i ? 'L' : 'M'}${(x + .15 + Math.cos(a) * .85).toFixed(2)} ${(y + .15 + Math.sin(a) * .85).toFixed(2)}`; }).join('')}Z" fill="#3c3226"/>
      <circle cx="${x + .15}" cy="${y + .15}" r=".3" fill="#a08a6a"/>`;
  function sfcBody(g, c) {
    const { W, H, sl, sr } = SFC, R = 3.3;
    // the grip's ribs, 0.7 mm apart
    const ribs = Array.from({ length: 6 }, (_, i) => 55.1 + i * .7);
    return `
      <polygon points="${SFC.front.join(' ')}" fill="${c}"/>
      <path d="M${sl} .25H${sr}M${R} .75H${sl}M${sr} .75H${W - R}" stroke="#fff" stroke-opacity=".6" stroke-width=".5"/>
      <path d="M.3 4V${H - 1.5}" stroke="#fff" stroke-opacity=".45" stroke-width=".4"/>
      <path d="M${W - .3} 4V${H - 1.5}M1.5 ${H - .3}H${W - 1.5}" stroke="#000" stroke-opacity=".12" stroke-width=".5"/>
      <rect x="${sl}" y="0" width="${sr - sl}" height="3.5" fill="#fff" fill-opacity=".12"/>
      <path d="M${sl} 3.5H${sr}" stroke="#000" stroke-opacity=".22" stroke-width=".35"/><path d="M${sl} 3.85H${sr}" stroke="#fff" stroke-opacity=".35" stroke-width=".25"/>
      ${[sl, sr].map(x => `<path d="M${x} 0V3.9M${x} 42.4V${H}" stroke="#000" stroke-opacity=".38" stroke-width=".45"/><path d="M${x + .45} .6V3.9M${x + .45} 42.4V${H - .3}" stroke="#fff" stroke-opacity=".4" stroke-width=".3"/>`).join('')}
      <rect x="8" y="3.9" width="113" height="38.5" rx="4" fill="#000" fill-opacity=".06" stroke="#000" stroke-opacity=".24" stroke-width=".45"/>
      <path d="M8.4 38.4Q8.4 42 12 42H117Q120.6 42 120.6 38.4V8" stroke="#fff" stroke-opacity=".4" stroke-width=".3" fill="none" transform="translate(.25 .35)"/>
      <path d="M29.45 53.3H99.55A3.95 3.95 0 0 1 99.55 61.2H29.45A3.95 3.95 0 0 1 29.45 53.3Z" fill="#000" fill-opacity=".09"/>
      <path d="M26 57.25A3.95 3.95 0 0 1 29.45 53.3H99.55A3.95 3.95 0 0 1 103 57.25" stroke="#000" stroke-opacity=".3" stroke-width=".5" fill="none"/>
      <path d="M26 57.25A3.95 3.95 0 0 0 29.45 61.2H99.55A3.95 3.95 0 0 0 103 57.25" stroke="#fff" stroke-opacity=".55" stroke-width=".45" fill="none"/>
      <rect x="27.6" y="54.4" width="73.8" height="5.7" rx="2.8" fill="#000" fill-opacity=".08"/>
      <rect x="51.5" y="54.4" width="26" height="5.7" fill="#fff" fill-opacity=".14"/>
      ${ribs.map(y => `<path d="M28.6 ${y}H100.4" stroke="#000" stroke-opacity=".28" stroke-width=".2"/><path d="M28.6 ${(y + .22).toFixed(2)}H100.4" stroke="#fff" stroke-opacity=".3" stroke-width=".14"/>`).join('')}
      <path d="M51.5 54.4V60.1M77.5 54.4V60.1" stroke="#000" stroke-opacity=".32" stroke-width=".3"/>
      ${deepScrew(5.6, 81)}${deepScrew(W - 5.6, 81)}`;
  }
  const CARD_PTS = [[0, 1.2], [.4, .4], [1.2, 0], [19.8, 0], [20.6, .4], [21, 1.2], [21, 29.8], [20.6, 30.6], [19.8, 31], [1.2, 31], [.4, 30.6], [0, 29.8]];
  const CART = {
    // the American SNES cartridge: the label housing standing up in the middle, the ridged sides,
    // the grip hollowed out under the label, the two screws, and its label with the Nintendo seal
    // and the rating down the left and the SNES logo down the right (an unofficial shell's own label, g.cartLabel, is
    // stuck on the plastic instead, without the printed one)
    snes: { w: 132, h: 86, label: [37.8, 2.8, 43.9, 35.4], pos: 'center top', crop: [0, 0, 0, 0.12], c: '#bab9c1', dp: 20,
      pts: [[21, 0], [111, 0], [111, 2.9], [132, 2.9], [132, 86], [0, 86], [0, 2.9], [21, 2.9]], conn: [27, 105, 2.5], top: [24.7, 107.2],
      // the whole black label, the seal and code down its left, the logo down its right
      sticker: [24.7, 1.2, 107.2, 39], stickerShape: 'border-radius:2% / 4.2%', body: (g, c, dk = isDark(c)) => `
      <path d="M22 0H110Q111 0 111 1V2.9H130Q132 2.9 132 4.9V84Q132 86 130 86H2Q0 86 0 84V4.9Q0 2.9 2 2.9H21V1Q21 0 22 0Z" fill="${c}"/>
      <path d="M22 .4H110M2 3.3H21M111 3.3H130" stroke="#fff" stroke-opacity="${dk ? 0.16 : 0.55}" stroke-width=".7"/>
      <path d="M21 3V86M111 3V86" stroke="#000" stroke-opacity="${dk ? 0.5 : 0.13}" stroke-width=".7"/>
      <path d="M21.6 3V86M111.6 3V86" stroke="#fff" stroke-opacity="${dk ? 0.08 : 0.3}" stroke-width=".4"/>
      ${[16.7, 30.6, 44.4, 58.2, 72.1].map(y => groove(.6, 20.6, y, dk, 1) + groove(111.4, 131.4, y, dk, 1)).join('')}
      ${snesScrews(g)}
      <path d="M25.5 86V54.7Q25.5 51.7 28.5 51.7H104.2Q107.2 51.7 107.2 54.7V86Z" fill="#000" fill-opacity=".07"/>
      <path d="M25.5 85V54.7Q25.5 51.7 28.5 51.7H104.2Q107.2 51.7 107.2 54.7V85" stroke="#000" stroke-opacity=".2" stroke-width=".7" fill="none"/>
      <path d="M54 52.4Q51.4 68 53.4 85.6M78 52.4Q75.4 68 77.4 85.6" stroke="#000" stroke-opacity="${dk ? 0.45 : 0.1}" stroke-width=".6" fill="none"/>
      <path d="M54.6 52.4Q52 68 54 85.6M78.6 52.4Q76 68 78 85.6" stroke="#fff" stroke-opacity="${dk ? 0.07 : 0.3}" stroke-width=".4" fill="none"/>
      ${g.cartLabel ? '' : `
      <rect x="24.7" y="1.2" width="82.5" height="37.8" rx="1.6" fill="#121214"/>
      <text x="31.2" y="3.9" ${SANS} font-size="1.1" letter-spacing=".1" text-anchor="middle" fill="#ddd">LICENSED BY</text>
      <rect x="27.4" y="4.9" width="7.6" height="2.5" rx="1.25" fill="#fff"/><rect x="27.8" y="5.25" width="6.8" height="1.8" rx=".9" fill="none" stroke="#e60012" stroke-width=".25"/>
      <text x="31.2" y="6.7" ${SANS} font-size="1.35" text-anchor="middle" fill="#e60012">Nintendo</text>
      <path d="M27.6 9H29.6L28.6 10.2Z" fill="#7b5cc8"/>
      <rect x="28.3" y="11.4" width="5.8" height="7.6" fill="#fff"/><rect x="28.8" y="12.6" width="4.8" height="5" fill="none" stroke="#111" stroke-width=".35"/>
      <text x="31.2" y="16.3" ${SANS} font-size="2.6" text-anchor="middle" fill="#111">K-A</text>
      <circle cx="31.2" cy="23.4" r="3" fill="#d9c98f"/><circle cx="31.2" cy="23.4" r="2.2" fill="#f2ead0"/><rect x="29.4" y="22.8" width="3.6" height="1.2" rx=".6" fill="none" stroke="#8a7a48" stroke-width=".2"/>
      <text x="31.2" y="29" ${SANS} font-size="1.25" text-anchor="middle" fill="#ddd">SNS-USA</text>
      <text x="31.2" y="30.8" font-family="Arial, Helvetica, sans-serif" font-size="1.1" text-anchor="middle" fill="#ddd">MADE IN JAPAN</text>
      <rect x="83" y="2.4" width="22.5" height="1.4" fill="#e8583b"/>
      <rect x="93" y="12.4" width="11.6" height="13.6" fill="#fff" fill-opacity=".08"/>
      ${Array.from({ length: 12 }, (_, i) => `<rect x="93" y="${(12.8 + i * 1.12).toFixed(2)}" width="11.6" height=".5" fill="#9a9aa0"/>`).join('')}
      <ellipse cx="97.6" cy="17.3" rx="3.1" ry="2.2" transform="rotate(-38 97.6 17.3)" fill="none" stroke="#121214" stroke-width="1.1"/>
      <ellipse cx="100.2" cy="21.2" rx="3.1" ry="2.2" transform="rotate(-38 100.2 21.2)" fill="none" stroke="#121214" stroke-width="1.1"/>
      <text x="84" y="33.4" font-family="Impact, 'Arial Narrow', Arial, sans-serif" font-style="italic" font-size="3.6" fill="#e8583b" textLength="21.6" lengthAdjust="spacingAndGlyphs">SUPER NINTENDO</text>
      <rect x="84" y="34.6" width="21.6" height="1.6" fill="#e8583b"/>
      <text x="94.8" y="35.85" ${SANS} font-size="1.1" letter-spacing=".15" text-anchor="middle" fill="#121214" textLength="20" lengthAdjust="spacingAndGlyphs">ENTERTAINMENT SYSTEM</text>`}` },
    // the PAL and Super Famicom cartridge (SFC above): no top label, the label across the recess under the top band
    sfc: { w: SFC.W, h: SFC.H, label: [8.9, 4.4, 111.2, 37.2], pos: 'center', c: '#c6c5c2', dp: 20, pts: SFC.pts, gaps: SFC.gaps, conn: [22, 107, 2.5],
      sticker: [8.9, 4.4, 120.1, 41.6], stickerShape: 'border-radius:2.9% / 8.6%', body: sfcBody },
    // crop: the share of the box art, left, right, top and bottom, taken by the console's name strip, which a cartridge label leaves out
    gb:      { tag: true, w: 57, h: 65, label: [9.5, 19.1, 35.8, 35.1], pos: 'center 25%', crop: [0.18, 0], c: '#acaca8', dp: 8, pts: GB_PTS, conn: [4.5, 52.5, 1.5], body: gbBody,
      sticker: [7.6, 18.9, 48.1, 54.4], stickerShape: 'border-radius:1.7% / 2%' },
    // the Game Boy Color-only cartridge: clear, no notch, the label lower down (Game Boy Color games that also
    // play on a Game Boy came in the Game Boy's shape, in black or in colour: caseStyle 'gb')
    gbc:     { tag: true, w: 57, h: 65, label: [8.4, 21.6, 40.2, 35.6], pos: 'center 25%', crop: [0.18, 0], c: '#5b5e63', dp: 8, clear: true, conn: [4.5, 52.5, 1.5], body: cgbBody,
      pts: [[0, 2.5], [.7, .7], [2.5, 0], [54.5, 0], [56.3, .7], [57, 2.5], [57, 64], [56, 65], [1, 65], [0, 64]],
      sticker: [7.1, 20.9, 49.9, 57.6], stickerShape: 'border-radius:1.7% / 2%' },
    // GBA: wider than tall, the top thicker than the rest: a raised rim along it, ending below in a long arch
    // that ramps down to the face (rim), the sides stepped in under it, the corners stepped in again at the bottom
    // by the recessed arrow, only in the back half (notch): from the front it runs full width to the bottom. The label
    // is printed like a real one: the box art small in the middle, over a soft wash of its own colours,
    // with the Nintendo seal on the left and the code on the right
    gba: { tag: true, w: 57, h: 35, label: [7, 8.3, 43, 23.2], pos: 'center', crop: [0.19, 0], small: true, c: '#3c3d41', dp: 6.2,
      notch: [[1.4, 33, 3, 35], [54, 33, 55.6, 35]],
      rim: { h: 1.3, y: 6.6, arch: [6.5, 50.5, 1.3], ramp: 1.8, sides: [1.4, 55.6], outline: 'M0 6.6V3.5Q0 0 3.5 0H53.5Q57 0 57 3.5V6.6' },
      sticker: [5.8, 7.3, 51.2, 32.3], stickerShape: 'border-radius:2.6% / 4.8%',
      pts: [[0, 2], [1, .5], [3, 0], [54, 0], [56, .5], [57, 2], [57, 6.6], [55.6, 6.6], [55.6, 33], [54, 33], [54, 35], [3, 35], [3, 33], [1.4, 33], [1.4, 6.6], [0, 6.6]], conn: [4.5, 52.5, 1.5], body: (g, c) => `
      <path d="M3.5 0H53.5Q57 0 57 3.5V6.6H55.6V35H1.4V6.6H0V3.5Q0 0 3.5 0Z" fill="${c}"/>
      <path d="M3.5 .4H53.5" stroke="#fff" stroke-opacity=".2" stroke-width=".5"/>
      ${gbaLogo(isDark(c))}
      <rect x="5.8" y="7.3" width="45.4" height="25" rx="1.2" fill="#000" fill-opacity=".3"/>
      <path d="M5 32.3H52" stroke="#fff" stroke-opacity=".1" stroke-width=".35"/>
      <path d="M24.3 32.75H32.7L28.5 34.55Z" fill="#000" fill-opacity=".22"/>
      <path d="M24.3 32.75H32.7" stroke="#000" stroke-opacity=".55" stroke-width=".3"/>
      <path d="M24.6 32.95L28.5 34.6L32.4 32.95" stroke="#fff" stroke-opacity=".13" stroke-width=".25" fill="none"/>
      <rect x="1.4" y="33.6" width="54.2" height="1.4" fill="#000" fill-opacity=".1"/>` },
    ds:      { w: 33,   h: 35, label: [2.6, 7.4, 27.8, 21.4], pos: 'center 30%', crop: [0.13, 0], c: '#38383c', dp: 3.8,
      pts: [[0, 1], [1, 0], [32, 0], [33, 1], [33, 34], [32, 35], [3.2, 35], [0, 31.8]], body: (g, c) => dsBody(c),
      // the sticker: the whole white recess, logo to code, its bottom left corner cut off like the card's
      sticker: [2.6, 2.2, 30.4, 32], stickerShape: 'clip-path:polygon(0 0,100% 0,100% 100%,10.8% 100%,0 89.9%);border-radius:2.5% / 2.3%' },
    // 3DS cards have the little tab at the top of their right side that stops them going into a DS
    '3ds':   { w: 34.8, h: 35, label: [3, 7.3, 27, 20.4], pos: 'center 30%', crop: [0, 0.11], c: '#d9dadd', dp: 3.8,
      pts: [[0, 1.4], [1.4, 0], [33.6, 0], [34.8, 1.2], [34.8, 5.6], [33, 7.9], [33, 33.6], [31.6, 35], [1.8, 35], [0, 33.2]], body: (g, c) => threeDsBody(c),
      sticker: [3, 2.2, 30, 32.4], stickerShape: 'border-radius:2.5% / 2.3%' },
    // the art shows in the white part of the label, under the red band (the box's own band is cut off)
    switch:  { w: 21, h: 31, label: [1.4, 8.5, 18.2, 16], pos: 'center 25%', c: '#48484b', dp: 3.3, pts: CARD_PTS, body: cardBody('#e4262e', false),
      sticker: [1.4, 2.5, 19.6, 26.9], stickerShape: 'border-radius:5% / 3.7%' },
    // Switch 2 game cards are red
    switch2: { w: 21, h: 31, label: [1.4, 8.5, 18.2, 16], pos: 'center 25%', c: '#de3a3d', dp: 3.3, pts: CARD_PTS, body: cardBody('#c8262b', true),
      sticker: [1.4, 2.5, 19.6, 26.9], stickerShape: 'border-radius:5% / 3.7%' },
  };
  const pct = (v, of) => (v / of * 100).toFixed(2) + '%';

  // a dark colour, which takes light mouldings
  const isDark = hex => { const n = parseInt(hex.slice(1), 16); return (0.2126 * (n >> 16) + 0.7152 * ((n >> 8) & 255) + 0.0722 * (n & 255)) / 255 < 0.4; };
  // a colour lighter (t > 0) or darker (t < 0)
  function shade(hex, t) {
    const n = parseInt(hex.slice(1), 16), to = t > 0 ? 255 : 0, k = Math.abs(t);
    return '#' + [n >> 16, (n >> 8) & 255, n & 255].map(v => Math.round(v + (to - v) * k).toString(16).padStart(2, '0')).join('');
  }
  // the backs, drawn as seen from behind (so a notch on the front's left is on the right here), from
  // photos of real ones. Moulded lettering is drawn as a shadow with the light catching its lower edge
  const mould = (x, y, size, text, dark, extra = '') => `<text x="${x}" y="${y}" font-size="${size}" text-anchor="middle" ${SANS} ${extra} fill="#000" fill-opacity="${dark ? 0.45 : 0.2}">${text}</text><text x="${x}" y="${y + size * .06}" font-size="${size}" text-anchor="middle" ${SANS} ${extra} fill="#fff" fill-opacity="${dark ? 0.07 : 0.3}">${text}</text>`;
  // the Nintendo logo moulded in an oval
  const ovalLogo = (x, y, w, dark) => `<rect x="${x - w / 2}" y="${y - w * .13}" width="${w}" height="${w * .26}" rx="${w * .13}" fill="none" stroke="#000" stroke-opacity="${dark ? 0.45 : 0.2}" stroke-width="${w * .025}"/>${mould(x, y + w * .07, w * .19, 'Nintendo', dark, `textLength="${w * .72}" lengthAdjust="spacingAndGlyphs"`)}`;
  const printed = (x, y, size, text) => `<text x="${x}" y="${y}" font-size="${size}" text-anchor="middle" font-family="'DejaVu Sans Mono', Menlo, Consolas, monospace" font-weight="700" letter-spacing="${size * .12}" fill="#1c1a3a" fill-opacity=".8">${text}</text>`;
  // the long window at the bottom of a DS or 3DS card: 17 gold contacts behind thin ribs, the green board above them
  const dsPins = (x, y, w, h, rib) => {
    const n = 17, step = w / n;
    return `<rect x="${x}" y="${y}" width="${w}" height="${h}" rx=".4" fill="#121214"/>
      <rect x="${x + .3}" y="${y + .3}" width="${w - .6}" height="${h * .2}" fill="#5a8a4e"/>
      <rect x="${x + .3}" y="${y + h * .2 + .3}" width="${w - .6}" height="${h * .62}" fill="#c99a48"/>
      <rect x="${x + .3}" y="${y + h * .82 + .3}" width="${w - .6}" height="${h * .12}" fill="#d8cfa4"/>
      ${Array.from({ length: n - 1 }, (_, k) => `<rect x="${(x + step * (k + 1) - .3).toFixed(2)}" y="${y}" width=".6" height="${h}" fill="${rib}"/>`).join('')}
      <path d="M${x + .55} ${y + .6}h.8l-.4 .6z" fill="#fff" fill-opacity=".8"/>`;
  };
  // a Switch card's five windows, each with its contacts: two strips, the right one broken in the middle (three in the last)
  const switchPins = (rib) => [[2.3, 2.3], [5.4, 2.3], [8.6, 2.3], [11.8, 2.3], [14.9, 3.5]].map(([x, w], k) => {
    const strips = k === 4 ? 3 : 2, sw = (w - .5) / strips - .15;
    return `<rect x="${x}" y="13.5" width="${w}" height="15.9" rx=".4" fill="#141416"/>
      <rect x="${x + .25}" y="14.1" width="${w - .5}" height="2.2" fill="#55605a"/>
      ${Array.from({ length: strips }, (_, i) => {
        const sx = (x + .25 + i * (sw + .15)).toFixed(2);
        return i === strips - 1 && i > 0
          ? `<rect x="${sx}" y="16.4" width="${sw.toFixed(2)}" height="5.2" fill="#d4b26a"/><rect x="${sx}" y="22.6" width="${sw.toFixed(2)}" height="5.6" fill="#d4b26a"/>`
          : `<rect x="${sx}" y="16.4" width="${sw.toFixed(2)}" height="11.8" fill="#d4b26a"/>`;
      }).join('')}
      <rect x="${x + .25}" y="28.4" width="${w - .5}" height=".7" fill="#2a2b2e"/>`;
  }).join('') + `<path d="M2.7 14.2h.9l-.45 .7z" fill="#fff" fill-opacity=".75"/>`;
  // the screw in the back of a Game Boy cartridge: a tri-wing in a round well (a cross-head on many unofficial ones)
  const screw = (x, y, dark, kind) => `
      <circle cx="${x}" cy="${y}" r="2.3" fill="#000" fill-opacity="${dark ? 0.55 : 0.3}"/><circle cx="${x}" cy="${y}" r="1.55" fill="#8b8a84"/>
      <circle cx="${x}" cy="${y}" r="1.55" fill="none" stroke="#fff" stroke-opacity=".35" stroke-width=".2"/>
      ${kind === 'phillips' ? `<path d="M${x - .8} ${y}H${x + .8}M${x} ${y - .8}V${y + .8}" stroke="#2c2c2e" stroke-width=".38"/>`
        : [90, 210, 330].map(a => `<path d="M${x} ${y}l${(Math.cos(a * Math.PI / 180) * .85).toFixed(2)} ${(-Math.sin(a * Math.PI / 180) * .85).toFixed(2)}" stroke="#2c2c2e" stroke-width=".42" stroke-linecap="round"/>`).join('')}`;
  const BACKS = {
    // the raised panel with the Nintendo logo, the moulded warning, the two small holes by the bottom corners
    snes: (T, dark) => `
      ${[16.7, 30.6, 44.4, 58.2, 72.1].map(y => groove(.6, 20.6, y, dark, 1) + groove(111.4, 131.4, y, dark, 1)).join('')}
      <path d="M21 3V86M111 3V86" stroke="#000" stroke-opacity=".13" stroke-width=".7"/>
      <rect x="27" y="8" width="78" height="44" rx="2" fill="#000" fill-opacity=".05" stroke="#000" stroke-opacity=".12" stroke-width=".5"/>
      <circle cx="66" cy="4.5" r="1" fill="#000" fill-opacity=".15"/>
      ${ovalLogo(66, 17, 26, dark)}
      ${mould(66, 29, 2.6, 'IMPORTANT', dark)}
      ${mould(66, 33.5, 2, 'POWER MUST BE OFF BEFORE LOADING OR REMOVING', dark, 'textLength="68" lengthAdjust="spacingAndGlyphs"')}
      ${mould(66, 37, 2, 'THE GAME PAK. CLEAN IT REGULARLY.', dark, 'textLength="52" lengthAdjust="spacingAndGlyphs"')}
      ${mould(66, 46, 2.2, 'MADE IN JAPAN', dark)}
      ${[10.5, 121.5].map(x => `<circle cx="${x}" cy="80" r="1.4" fill="#000" fill-opacity=".35"/>`).join('')}`,
    // the PAL cartridge from behind (traced from the back of SNSP-MK-AUS): fine ribs over the middle between its
    // seams, the smooth raised panel with the Nintendo logo and PAT. PEND. MADE IN JAPAN, the recess under it with
    // the silver warning sticker (its text read off a torn one, the lost half made up to match) over the dimple,
    // and the holes for the screws at the bottom corners
    sfc: (T, dark, g = {}) => {
      const base = shade(T.c, -0.08), yr = String(g.released || '').slice(0, 4) || '1992';
      const ribs = Array.from({ length: 58 }, (_, i) => 2.6 + i * 1.45).filter(y => y < T.h - .8);
      const line = (y, text, size = 2.05, extra = '') => `<text x="33" y="${y}" font-family="Arial, Helvetica, sans-serif" font-weight="700" font-style="italic" font-size="${size}" fill="#141414" ${extra}>${text}</text>`;
      const dot = y => `<circle cx="31.6" cy="${y - .65}" r=".45" fill="#141414"/>`;
      return `
      ${ribs.map(y => groove(20.9, 108.1, y, dark, .3)).join('')}
      ${[20.3, 108.7].map(x => `<path d="M${x} ${SFC.gd}V${T.h}" stroke="#000" stroke-opacity=".35" stroke-width=".45"/><path d="M${x + .45} ${SFC.gd}V${T.h}" stroke="#fff" stroke-opacity=".35" stroke-width=".3"/>`).join('')}
      <rect x="40.2" y="7.5" width="48.6" height="18.1" rx="2.4" fill="${base}" stroke="#000" stroke-opacity=".22" stroke-width=".4"/>
      <rect x="40.6" y="7.9" width="47.8" height="17.3" rx="2.1" fill="#fff" fill-opacity=".06" stroke="#fff" stroke-opacity=".35" stroke-width=".25"/>
      ${ovalLogo(64.5, 14.6, 30.7, dark)}
      ${mould(64.5, 22.2, 1.75, 'PAT. PEND.&#160;&#160;&#160;MADE IN JAPAN', dark, 'textLength="31" lengthAdjust="spacingAndGlyphs"')}
      <rect x="25.4" y="47.3" width="78.2" height="32.3" rx="2.2" fill="${base}" stroke="#000" stroke-opacity=".25" stroke-width=".4"/>
      <rect x="25.95" y="47.85" width="77.1" height="31.2" rx="1.8" fill="none" stroke="#000" stroke-opacity=".12" stroke-width=".25"/>
      <rect x="26.6" y="48.3" width="75.2" height="30.3" rx="1" fill="#bfc1c4"/>
      <path d="M26.6 49.3Q26.6 48.3 27.6 48.3H100.8Q101.8 48.3 101.8 49.3V58L26.6 70Z" fill="#fff" fill-opacity=".16"/>
      <circle cx="64.9" cy="56.8" r="1.7" fill="#000" fill-opacity=".06"/><circle cx="64.7" cy="56.6" r="1.5" fill="none" stroke="#fff" stroke-opacity=".3" stroke-width=".2"/>
      ${line(53.1, 'IMPORTANT', 3.6, 'font-style="normal"')}
      ${dot(57.8)}${line(57.8, 'DO NOT TURN THE POWER ON OR OFF REPEATEDLY.')}
      ${dot(61.8)}${line(61.8, 'DO NOT TOUCH THE EDGE CONNECTOR.')}
      ${dot(65.8)}${line(65.8, 'THIS GAME PAK MUST BE KEPT CLEAN AND DRY.')}
      ${line(69.2, 'REFER TO THE CONSUMER INFORMATION BOOKLET FOR')}
      ${line(72.6, 'ADDITIONAL PRECAUTIONS.')}
      <circle cx="30.9" cy="76.15" r=".85" fill="none" stroke="#141414" stroke-width=".2"/><text x="30.9" y="76.62" font-family="Arial, Helvetica, sans-serif" font-size="1.2" text-anchor="middle" fill="#141414">M</text>
      <text x="32.4" y="76.8" font-family="Arial, Helvetica, sans-serif" font-size="1.75" fill="#141414">©${yr} NINTENDO&#160;&#160;&#160;MADE IN JAPAN</text>
      ${[5.8, T.w - 5.8].map(x => `<circle cx="${x}" cy="81" r="2.4" fill="#000" fill-opacity=".45"/><path d="M${x - 2.15} ${81 + 1.05}A2.4 2.4 0 0 0 ${x + 2.15} ${81 + 1.05}" stroke="#fff" stroke-opacity=".4" stroke-width=".3" fill="none"/>`).join('')}`;
    },
    // traced from photos of real ones: MADE IN JAPAN in a moulded frame at the top with PAT. PEND. under it, the
    // dimple, the screw lower down, the step above the end that plugs in, the faint lines of the side rails (the
    // contacts show only in the bottom edge). g.cartBack: what an unofficial shell has instead, 'pat-pend' (PAT.
    // PEND. alone) or 'bare' (nothing moulded, a recessed panel round the screw, no dimple)
    gb: (T, dark, g = {}) => `
      <path d="M12.5 1.2V53.3M44.5 1.2V53.3" stroke="#000" stroke-opacity="${dark ? 0.3 : 0.08}" stroke-width=".3"/>
      <path d="M.6 53.3H56.4" stroke="#000" stroke-opacity="${dark ? 0.45 : 0.16}" stroke-width=".4"/><path d="M.6 53.7H56.4" stroke="#fff" stroke-opacity="${dark ? 0.08 : 0.3}" stroke-width=".3"/>
      ${g.cartBack === 'bare' ? `<rect x="10" y="12" width="37" height="31" rx=".6" fill="#000" fill-opacity="${dark ? 0.15 : 0.05}" stroke="#000" stroke-opacity="${dark ? 0.4 : 0.15}" stroke-width=".3"/>` : `
      <rect x="${g.cartBack === 'pat-pend' ? 21.5 : 18.6}" y="1.4" width="${g.cartBack === 'pat-pend' ? 14 : 19.8}" height="3.6" rx=".3" fill="none" stroke="#000" stroke-opacity="${dark ? 0.4 : 0.15}" stroke-width=".25"/>
      ${g.cartBack === 'pat-pend' ? mould(28.5, 4.15, 2, 'PAT. PEND.', dark) : mould(28.5, 4.2, 2, 'MADE IN JAPAN', dark, 'textLength="17.6" lengthAdjust="spacingAndGlyphs"') + mould(28.5, 7.6, 1.7, 'PAT. PEND.', dark)}
      <circle cx="28.5" cy="31.3" r="1.5" fill="#000" fill-opacity="${dark ? 0.3 : 0.1}"/><circle cx="28.3" cy="31.1" r=".9" fill="#fff" fill-opacity="${dark ? 0.08 : 0.3}"/><circle cx="28.6" cy="31.3" r=".22" fill="#fff" fill-opacity=".7"/>`}
      ${screw(28.5, 47, dark, g.cartScrew)}`,
    // a Game Boy Color-only cartridge from behind: the clear shell (the board shows through it, cgbBoardBack), the
    // walls inside its edge, the Nintendo logo moulded at the top, MODEL NO. CGB-002, the dimple and the screw
    gbc: (T, dark, g = {}) => `
      <path d="M2.6 2.4H54.4V62.6H2.6Z" fill="none" stroke="#000" stroke-opacity=".35" stroke-width=".55"/>
      ${ovalLogo(28.5, 7.8, 17, true)}
      ${mould(14, 25, 1.1, 'MODEL NO. CGB-002', true)}
      <circle cx="28.5" cy="31.3" r="1.5" fill="#fff" fill-opacity=".14" stroke="#fff" stroke-opacity=".35" stroke-width=".2"/>
      ${screw(28.5, 46.3, true, g.cartScrew)}`,
    // the raised panel with the logo and MODEL NO. AGB-002, the screw at the top, the dimple under the panel
    gba: (T, dark) => `
      <circle cx="28.5" cy="2.6" r="1.3" fill="#000" fill-opacity=".45"/><circle cx="28.5" cy="2.6" r=".8" fill="#7d7e83"/>
      <rect x="17.2" y="6.5" width="22.6" height="12.9" rx="1" fill="#fff" fill-opacity=".04" stroke="#000" stroke-opacity=".35" stroke-width=".35"/>
      ${ovalLogo(28.5, 10, 12, true)}
      ${mould(28.5, 14.6, 1.3, 'MODEL NO. AGB-002', true)}
      ${mould(28.5, 16.9, 1.1, 'PAT.PEND. MADE IN JAPAN', true)}
      <circle cx="28.5" cy="21.6" r=".9" fill="#000" fill-opacity=".4"/>
      <path d="M1.4 29.3H55.6" stroke="#000" stroke-opacity=".45" stroke-width=".35"/><path d="M1.4 29.3H55.6V33H54V35H3V33H1.4Z" fill="#000" fill-opacity=".14"/>`,
    // the raised panel with the logo, NTR-005 PAT. PEND. and the printed batch code, the contacts window at the bottom
    ds: (T, dark) => `
      <rect x="3.5" y="2.2" width="26.2" height="20.3" rx="1.2" fill="#fff" fill-opacity=".04" stroke="#000" stroke-opacity=".35" stroke-width=".3"/>
      ${ovalLogo(16.6, 9.3, 14.9, true)}
      ${mould(16.6, 14, 1.8, 'NTR-005 PAT. PEND.', true)}
      ${printed(16.6, 17.8, 1.5, 'CDYPN0J20')}
      ${dsPins(2.8, 25, 25.7, 9.2, T.c)}`,
    '3ds': (T, dark) => `
      <rect x="5.3" y="2.6" width="26" height="19.4" rx="1.2" fill="none" stroke="#000" stroke-opacity=".07" stroke-width=".3"/>
      ${ovalLogo(18.3, 9.3, 14, false)}
      ${printed(18.3, 17.4, 1.5, 'ADAP210221')}
      ${dsPins(5.1, 24.7, 26.4, 9.6, T.c)}`,
    // the Nintendo logo, the model number, the printed code and the CE mark, the five windows of contacts below
    switch: (T, dark) => `
      <rect x="7" y=".7" width="6.6" height=".6" rx=".3" fill="#000" fill-opacity=".35"/>
      <rect x="1.6" y="1.7" width="17.4" height="11.1" rx=".8" fill="#fff" fill-opacity=".03" stroke="#000" stroke-opacity=".3" stroke-width=".25"/>
      ${ovalLogo(10.2, 4.2, 12, true)}
      ${mould(6.3, 8.6, 1.3, 'HAC-006', true)}
      ${printed(6.8, 10.8, .95, 'BAAZA20Y000')}
      ${mould(16.4, 11.3, 3.6, 'CE', true, 'font-weight="400"')}
      ${switchPins(T.c)}`,
  };
  BACKS.switch2 = BACKS.switch;
  function backSVG(T, type, g) {
    const mirror = T.pts.map(([x, y]) => [+(T.w - x).toFixed(2), y]);
    const dark = isDark(T.c);
    return `<svg viewBox="0 0 ${T.w} ${T.h}" preserveAspectRatio="none" aria-hidden="true">
      <polygon points="${mirror.join(' ')}" fill="${shade(T.c, -0.08)}"${T.clear ? ` fill-opacity="${CLEAR}"` : ''}/>
      <polygon points="${mirror.join(' ')}" fill="none" stroke="#fff" stroke-opacity="${dark ? 0.08 : 0.35}" stroke-width=".35"/>
      ${(BACKS[type] || (() => ''))(T, dark, g)}
      ${g && g.cartGlitter ? glitter(T.w, T.h, [[0, 0, T.w - 50.8, 4.5]]) : ''}
    </svg>`;
  }

  // a raised rim across the top of the front (the GBA's), from photos and a 3D model of a real one: its face stands
  // h mm proud of the cartridge's and ends below in a long arch, a circle through its two ends on the line y and
  // its peak. Along the arch it slopes down to the face in a ramp, widest in the middle and steep at the ends, made
  // of thin strips each turned to follow the curve; beside the arch, level with its ends, it steps straight down.
  // Lit from above, the ramp faces away from the light: the dark crescent under the arch on a real one
  function rimHTML(T, s, c) {
    const { h, y, arch: [x0, x1, top], ramp, sides: [sl, sr], outline } = T.rim;
    const Z = T.dp * s / 2 + h * s, mid = (x0 + x1) / 2, hw = (x1 - x0) / 2, sag = y - top;
    const r = (hw * hw + sag * sag) / (2 * sag), archY = x => top + r - Math.sqrt(r * r - (x - mid) ** 2);
    const N = 60, pts = Array.from({ length: N + 1 }, (_, i) => [x0 + (x1 - x0) * i / N, archY(x0 + (x1 - x0) * i / N)]);
    const deg = a => (a * 180 / Math.PI).toFixed(2) + 'deg';
    const strips = pts.slice(0, -1).map(([ax, ay], i) => {
      const [bx, by] = pts[i + 1], phi = Math.atan2(by - ay, bx - ax), u = ((ax + bx) / 2 - mid) / hw;
      const w = ramp * Math.pow(Math.max(0, 1 - u * u), 1.5), tilt = Math.atan2(h, w);
      // how much it faces the light (up and to the left), the steeper the more
      const lit = (0.45 * Math.sin(phi) - 0.89 * Math.cos(phi)) * (0.45 + 0.55 * Math.sin(tilt));
      return `<i class="crp" style="width:${px(Math.hypot(bx - ax, by - ay) * s + .6)};height:${px(Math.hypot(w, h) * s)};transform:translate3d(${px(ax * s)},${px(ay * s)},${px(Z)}) rotateZ(${deg(phi)}) rotateX(${deg(-tilt)});background:${shade(c, lit > 0 ? lit * 0.2 : lit * 0.75)}"></i>`;
    }).join('');
    const walls = [[sl, x0], [x1, sr]].map(([a, b]) => `<i class="crp" style="width:${px((b - a) * s + .3)};height:${px(h * s)};transform:translate3d(${px(a * s)},${px(y * s)},${px(Z)}) rotateX(-90deg);background:${shade(c, -0.4)}"></i>`).join('');
    const dark = isDark(c), edge = `M${sl} ${y}H${x0}A${r.toFixed(2)} ${r.toFixed(2)} 0 0 1 ${x1} ${y}H${sr}`;
    return `<div class="crim" style="transform:translateZ(${px(Z)})"><svg viewBox="0 0 ${T.w} ${T.h}" preserveAspectRatio="none" aria-hidden="true">
      <path d="${outline}H${x1}A${r.toFixed(2)} ${r.toFixed(2)} 0 0 0 ${x0} ${y}Z" fill="${c}"/>
      <path d="M3.5 .4H53.5" stroke="#fff" stroke-opacity="${dark ? 0.2 : 0.5}" stroke-width=".5"/>
      <path d="${edge}" stroke="#fff" stroke-opacity="${dark ? 0.18 : 0.5}" stroke-width=".3" fill="none" transform="translate(0 -.18)"/>
    </svg></div>${strips}${walls}`;
  }

  // whether a cartridge has a label over its top edge with the title on it (the American SNES one; not the PAL
  // one, nor an unofficial one whose label stops at the edge: g.topLabel false)
  const hasTop = (g, T) => !!T.top && g.topLabel !== false;
  // whether one stands on its side on the shelf with its title showing on its top: one with a top label, or a Game
  // Boy, Game Boy Color or GBA cartridge (T.tag), whose label stops at the face, with a small title tag drawn there,
  // or one marked to stand on its side (g.faceOut false), given a black title label there like the American one
  const titledTop = (g, T) => hasTop(g, T) || !!T.tag || g.faceOut === false;
  // the edges all round, following the outline, each lit by which way it faces (the light is above, on the left)
  function edgesHTML(T, s, g) {
    const D = T.dp * s;
    return T.pts.map((p, i) => {
      const q = T.pts[(i + 1) % T.pts.length], dx = (q[0] - p[0]) * s, dy = (q[1] - p[1]) * s, len = Math.hypot(dx, dy);
      const lit = (dy * -0.45 + -dx * -0.89) / len;
      // the bottom edge of a cartridge that plugs in end first: the opening with the gold edge connector in it
      const slot = T.conn && p[1] === T.h && q[1] === T.h
        ? `<b class="slot" style="left:${px((p[0] - T.conn[1]) * s)};width:${px((T.conn[1] - T.conn[0]) * s)};--pp:${px(T.conn[2] * s)}"></b>`
        // the top edge of a SNES cartridge: the label wrapping over it, with the title
        : hasTop(g, T) && p[1] === 0 && q[1] === 0 ? `<b class="tl" style="left:${px((T.top[0] - p[0]) * s)};width:${px((T.top[1] - T.top[0]) * s)};--ch:${px(D)}"><span>${esc(g.title)}</span></b>` : '';
      // round the raised rim's part (the GBA's top) the edge stands out to the rim's face; the sides of a
      // corner notched out of the back half only (the GBA's bottom corners) stand in the back half
      const up = T.rim && p[1] <= T.rim.y && q[1] <= T.rim.y ? T.rim.h * s : 0;
      const back = (T.notch || []).concat(T.gaps || []).some(([x0, y0, x1, y1]) => [p, q].every(([x, y]) => x >= x0 && x <= x1 && y >= y0 && y <= y1));
      const H = back ? D / 2 : D + up, dz = back ? -D / 4 : up / 2;
      return `<i class="ce${slot.includes('slot') ? ' conn' : ''}" style="left:${px(p[0] * s)};top:${px(p[1] * s - H / 2)};width:${px(len + .6)};height:${px(H)};transform:rotate(${(Math.atan2(dy, dx) * 180 / Math.PI).toFixed(2)}deg) rotateX(90deg)${dz ? ` translateY(${px(dz)})` : ''};background:${shade(T.c, lit > 0 ? lit * 0.2 : lit * 0.45)}">${slot}</i>`;
    }).join('')
      // a notch cut into the top of the back half only (the PAL cartridge's): the front half's top runs on across it
      + (T.gaps || []).map(([x0, , x1]) => `<i class="ce" style="left:${px(x0 * s)};top:${px(-D / 4)};width:${px((x1 - x0) * s + .6)};height:${px(D / 2)};transform:rotateX(90deg) translateY(${px(D / 4)});background:${shade(T.c, 0.89 * 0.2)}"></i>`).join('') + (T.notch || []).map(([x0, y0, x1, y1]) => {
      // the front half round a notch: its side and bottom, and its underside, seen through the notch from behind
      const right = x0 > T.w / 2, side = right ? x1 : x0, half = `height:${px(D / 2)};transform-origin:0 50%`;
      return `<i class="ce" style="left:${px(side * s)};top:${px((right ? y0 : y1) * s - D / 4)};width:${px((y1 - y0) * s + .6)};${half};transform:rotate(${right ? 90 : -90}deg) rotateX(90deg) translateY(${px(D / 4)});background:${shade(T.c, right ? -0.2 : 0.09)}"></i>`
        + `<i class="ce" style="left:${px((right ? x1 : x1) * s)};top:${px(y1 * s - D / 4)};width:${px((x1 - x0) * s + .6)};${half};transform:rotate(180deg) rotateX(90deg) translateY(${px(D / 4)});background:${shade(T.c, -0.4)}"></i>`
        + `<i class="crp" style="width:${px((x1 - x0) * s)};height:${px((y1 - y0) * s)};transform:translate3d(${px(x0 * s)},${px(y0 * s)},0);background:${shade(T.c, -0.3)}"></i>`;
    }).join('');
  }
  // a GBA label: the box art small in the middle over a wash of its colours, the seal and the code
  function smallLabel(g, keep) {
    const r = (g.cover.ratio || 1) * keep;
    return `<img class="wash" src="${esc(g.cover.file)}" alt="" loading="lazy" decoding="async" draggable="false">
      <div class="art" style="aspect-ratio:${r.toFixed(3)}">${img(g)}</div>
      <i class="seal"></i><b class="code">AGB-XXXX-${cardRegion(g)}</b>`;
  }

  function cartHTML(g, s, type) {
    const T = CART[type];
    const [x, y, lw, lh] = T.label;
    // Japanese box art has no name strip down the side
    const [cl, cr, ct = 0, cb = 0] = T.crop && window.REGIONS[g.region]?.std !== 'NTSC-J' ? T.crop : [0, 0], keep = 1 - cl - cr, keepY = 1 - ct - cb;
    const label = !g.cover ? `<span>${esc(g.title)}</span>` : T.small ? smallLabel(g, keep) : img(g);
    // a cartridge that came in its own colour (a yellow Pokémon Yellow)
    const c = /^#[0-9a-f]{6}$/i.test(g.cartColor || '') ? g.cartColor : T.c, S = c === T.c ? T : { ...T, c };
    // the real sticker (cover.label, cut from a photo of the card by tools/fetch-cards.mjs): its own logo strip, art
    // and code, lying in the card's label recess over the drawn one
    // (an unofficial cartridge's own label, g.cartLabel, lies where it was stuck: [left, top, right, bottom] in mm)
    const sticker = g.cover && g.cover.label && (g.cartLabel || T.sticker);
    const lab = sticker
      ? `<div class="cstk" style="left:${pct(sticker[0], T.w)};top:${pct(sticker[1], T.h)};width:${pct(sticker[2] - sticker[0], T.w)};height:${pct(sticker[3] - sticker[1], T.h)};${g.cartLabel ? 'border-radius:2.5% / 6%' : T.stickerShape || ''}"><img src="${esc(g.cover.label)}" alt="" loading="lazy" decoding="async" draggable="false"></div>`
      : `<div class="clab${T.small && g.cover ? ' small' : ''}" style="left:${pct(x, T.w)};top:${pct(y, T.h)};width:${pct(lw, T.w)};height:${pct(lh, T.h)};--lp:${T.pos};--liw:${pct(1, keep)};--lix:${pct(-cl, keep)};--lih:${pct(1, keepY)};--liy:${pct(-ct, keepY)}">${label}</div>`;
    // a clear cartridge: its board standing inside, one picture for each side, and the far side of the shell as
    // seen through the near one (they come first, so that where the cartridge is drawn flat they lie under its faces)
    const inside = T.clear ? `<div class="cin"><svg viewBox="0 0 ${T.w} ${T.h}" preserveAspectRatio="none" aria-hidden="true"><path d="${CGB_OUTLINE}" fill="${shade(c, -0.08)}" fill-opacity="${CLEAR}"/></svg></div>
      <div class="cin cin-b"><svg viewBox="0 0 ${T.w} ${T.h}" preserveAspectRatio="none" aria-hidden="true"><path d="${CGB_OUTLINE}" fill="${c}" fill-opacity="${CLEAR}"/></svg></div>
      <div class="cpcb">${cgbBoard(g)}</div><div class="cpcb cpcb-b">${cgbBoardBack()}</div>` : '';
    return `<div class="cart c-${type} k-${styleOf(g)}${g.cartGlitter ? ' glit' : ''}${T.clear ? ' clr' : ''}" style="--cw:${px(T.w * s)};--ch:${px(T.h * s)};--cd:${px(T.dp * s)}">
      ${inside}
      <div class="cf">
        <svg viewBox="0 0 ${T.w} ${T.h}" preserveAspectRatio="none" aria-hidden="true">${T.body(g, c)}</svg>
        ${lab}
      </div>
      <div class="cb">${backSVG(S, type, g)}</div>
      ${edgesHTML(S, s, g)}${S.rim ? rimHTML(S, s, c) : ''}
    </div>`;
  }

  /* ---------- the Pokéwalker ----------
     The pedometer HeartGold and SoulSilver came with (g.extras: 'pokewalker'), from photos of a real one: a disc
     48 mm across and 14 thick, its front a domed Poké Ball (red over a black band, white under it) with the grey
     LCD in a black frame across the band and three white buttons in a row below; its back a black dome with the
     belt clip, the screw beside it and the strap loop at the bottom; the infrared window in the top edge. Its
     sides are rings of thin strips: the front shell coloured as the ball, the back shell black. */
  const PW_N = 72;
  // the front shell's colour at a height y (mm from the middle, down positive)
  const pwShell = y => y < -1 ? '#d3261f' : y > 1 ? '#ecebe7' : '#141416';
  // a ring of strips from radius r0 at depth z0 to radius r1 at z1 (a cylinder when r0 = r1, else a cone);
  // col(y, i) colours each strip; n strips round it, st more style on each (a texture)
  function pwRing(s, r0, z0, r1, z1, col, n = PW_N, st = '') {
    const rm = (r0 + r1) / 2, zm = (z0 + z1) / 2, h = Math.hypot(r1 - r0, z1 - z0) * s;
    const a = Math.atan2(z1 - z0, r0 - r1) * 180 / Math.PI, w = 2 * Math.PI * rm * s / n + .6;
    let out = '';
    for (let i = 0; i < n; i++) {
      const t = i * 360 / n, c = Math.cos(t * Math.PI / 180), sn = Math.sin(t * Math.PI / 180);
      // lit from the top left, as the cartridges are
      const lit = 0.85 * c - 0.5 * sn;
      out += `<i style="width:${px(w)};height:${px(h)};margin:${px(-h / 2)} 0 0 ${px(-w / 2)};transform:translateZ(${px(zm * s)}) rotateZ(${t}deg) translateY(${px(-rm * s)}) rotateX(${a.toFixed(2)}deg);background:${shade(col(-rm * c, i), lit > 0 ? lit * 0.18 : lit * 0.22)}${st}"></i>`;
    }
    return out;
  }
  function walkerHTML(s) {
    const front = `<svg viewBox="-20 -20 40 40" aria-hidden="true">
      <rect x="-13" y="-11.5" width="26" height="18" rx="2" fill="#121214"/>
      <rect x="-11.6" y="-10.1" width="23.2" height="15.2" rx=".8" fill="#a2a898"/>
      <path d="M-11.6 -10.1h9l-6 15.2h-3z" fill="#fff" fill-opacity=".1"/>
      ${[[-10.4, 2.8], [0, 3.3], [10.4, 2.8]].map(([x, r]) => `<circle cx="${x}" cy="11.6" r="${r + .5}" fill="#c9c8c3"/><circle cx="${x}" cy="11.4" r="${r}" fill="#fbfbf9"/><ellipse cx="${x - r * .3}" cy="${11.4 - r * .4}" rx="${r * .45}" ry="${r * .28}" fill="#fff"/>`).join('')}
    </svg>`;
    const back = `<svg viewBox="-19 -19 38 38" aria-hidden="true">
      <path d="M-10.5 -21h21v27a3 3 0 0 1 -3 3h-15a3 3 0 0 1 -3 -3z" fill="#222225" stroke="#0a0a0b" stroke-width=".5"/>
      ${mould(0, -9.5, 2.2, 'NINTENDO DS', true)}${mould(0, -6.6, 1.5, 'NTR-032', true)}${mould(0, -4.4, 1.3, '© 2009 Nintendo', true)}
      ${ovalLogo(0, 2.6, 9, true)}
      <circle cx="-13.2" cy="1.5" r="1.2" fill="#8c9096"/><path d="M-13.9 1.5h1.4M-13.2 .8v1.4" stroke="#3a3c40" stroke-width=".35"/>
      <rect x="-3.4" y="13.2" width="6.8" height="4.4" rx="1.2" fill="#0d0d0e" stroke="#2c2c30" stroke-width=".3"/>
      <rect x="-2.2" y="14.2" width="4.4" height="1" rx=".5" fill="#000"/><rect x="-2.2" y="15.8" width="4.4" height="1" rx=".5" fill="#000"/>
    </svg>`;
    const D = px(40 * s), B = px(38 * s);
    return `<div class="pw xtra" style="--pw:${px(48 * s)}">
      ${pwRing(s, 19, -7, 24, -5, () => '#1c1c1f')}
      ${pwRing(s, 24, -5, 24, -1, () => '#1c1c1f')}
      ${pwRing(s, 24, -1, 24, 4, (y, i) => i < 2 || i > PW_N - 2 ? '#2a0d0e' : pwShell(y))}
      ${pwRing(s, 24, 4, 20, 7, pwShell)}
      <div class="pwf" style="width:${D};height:${D};margin:${px(-20 * s)} 0 0 ${px(-20 * s)};transform:translateZ(${px(7 * s)})">${front}</div>
      <div class="pwb" style="width:${B};height:${B};margin:${px(-19 * s)} 0 0 ${px(-19 * s)};transform:rotateY(180deg) translateZ(${px(7 * s)})">${back}</div>
    </div>`;
  }

  /* ---------- the Guitar Grip ----------
     The controller Guitar Hero: On Tour came with (g.extras: 'guitargrip'), from photos of a real one: a block of
     clear plastic about 72 mm long, 52 deep and 17 thick that plugs into the DS's Game Boy Advance slot. Its big
     face holds the printed insert, silver with black tribal curls and the logo, under a black fabric hand strap
     that wraps round the ends; the four fret buttons, green, red, yellow and blue arches with black middles, run
     along one long edge; one end curves out in a clear wave. It stands up out of the box with the buttons on top,
     leant back so they show. */
  // the six faces of a block w × h × d (px) round its middle; f.front etc. are [style, html]
  function cuboid(w, h, d, f, at = '') {
    const face = (k, fw, fh, tr) => f[k] ? `<div class="xf xf-${k}" style="width:${px(fw)};height:${px(fh)};margin:${px(-fh / 2)} 0 0 ${px(-fw / 2)};transform:${tr};${f[k][0] || ''}">${f[k][1] || ''}</div>` : '';
    return `<div class="xc" style="transform:${at}">
      ${face('front', w, h, `translateZ(${px(d / 2)})`)}${face('back', w, h, `rotateY(180deg) translateZ(${px(d / 2)})`)}
      ${face('right', d, h, `rotateY(90deg) translateZ(${px(w / 2)})`)}${face('left', d, h, `rotateY(-90deg) translateZ(${px(w / 2)})`)}
      ${face('top', w, d, `rotateX(90deg) translateZ(${px(h / 2)})`)}${face('bottom', w, d, `rotateX(-90deg) translateZ(${px(h / 2)})`)}
    </div>`;
  }
  // a tribal flourish: a black stroke from (x, y) heading at angle a0, thick at its root and tapering as it
  // winds into a curl r across (dir 1 clockwise, -1 the other way), with a thorn off its back
  function curl(x, y, r, a0, dir) {
    const n = 36, L = [], R = [];
    let px0 = x, py0 = y, a = a0;
    for (let i = 0; i <= n; i++) {
      const t = i / n, w = r * 0.32 * (1 - t) ** 1.3 + 0.12;
      const nx = -Math.sin(a), ny = Math.cos(a);
      L.push([px0 + nx * w, py0 + ny * w]); R.push([px0 - nx * w, py0 - ny * w]);
      const step = r * 0.11 * (1 - t * 0.6);
      a += dir * (0.05 + t * t * 0.42);
      px0 += Math.cos(a) * step; py0 += Math.sin(a) * step;
    }
    const pts = L.concat(R.reverse()).map((p, i) => (i ? 'L' : 'M') + p[0].toFixed(2) + ' ' + p[1].toFixed(2)).join('');
    // the thorn, off the outside of the stroke a third of the way along
    const [tx, ty] = R[Math.round(n * 0.62)], ta = a0 + dir * 0.9 + Math.PI * (dir > 0 ? -0.5 : 0.5);
    const thorn = `M${(tx - Math.cos(ta - 1.4) * r * .12).toFixed(2)} ${(ty - Math.sin(ta - 1.4) * r * .12).toFixed(2)}L${(tx + Math.cos(ta) * r * .38).toFixed(2)} ${(ty + Math.sin(ta) * r * .38).toFixed(2)}L${(tx + Math.cos(ta - 1.4) * r * .12).toFixed(2)} ${(ty + Math.sin(ta - 1.4) * r * .12).toFixed(2)}z`;
    return `<path d="${pts}z${thorn}" fill="#111"/>`;
  }
  const GRIP = { l: 72, p: 52, t: 17 };
  // left to right as they show from the printed side, the clear wave at the right-hand end
  const FRETS = ['#1f62d4', '#f4c20d', '#e0263a', '#1fb46c'];
  function gripHTML(s) {
    const { l: L, p: P, t: T } = GRIP, W = L * s, H = P * s, D = T * s;
    const clear = 'background:linear-gradient(160deg,#7d8791,#3c434b 55%,#2a2f35);box-shadow:inset 0 0 0 1px rgba(235,242,250,.55),inset 0 0 6px rgba(255,255,255,.25)';
    // the printed insert: silver, black curls round the edges, the logo in the upper half (the strap covers the lower)
    const insert = `<svg viewBox="0 0 ${L} ${P}" preserveAspectRatio="none" aria-hidden="true">
      ${curl(1, 3, 9, 0.35, 1)}${curl(71, 3, 9, Math.PI - 0.35, -1)}${curl(1, 33, 8, -0.5, -1)}${curl(71, 33, 8, Math.PI + 0.5, 1)}
      ${curl(25, 1, 5, 2.4, -1)}${curl(47, 1, 5, 0.75, 1)}${curl(14, 34, 4.5, -0.9, 1)}${curl(58, 34, 4.5, Math.PI + 0.9, -1)}
      <g transform="translate(36 15) skewX(-8)" text-anchor="middle" font-family="Impact, 'Arial Black', 'Arial Narrow Bold', sans-serif" font-weight="900">
        <text y="0" font-size="8.6" fill="#f4f4f4" stroke="#111" stroke-width="1.6" paint-order="stroke" letter-spacing=".3">GUITAR</text>
        <text y="8.6" font-size="9.6" fill="#f4f4f4" stroke="#111" stroke-width="1.6" paint-order="stroke" letter-spacing=".3">HERO</text>
      </g>
      <path transform="translate(-14 6.3) scale(2 1.3)" transform-origin="50 22" d="M44 20.5l3 -1.6l1.4 1.2l4.2 -1.8l-.6 2l3.4 .4l-2.6 1.6l2 1.8l-4 -.2l-1.2 1.8l-2 -1.4l-3.6 1l1 -1.8z" fill="#111"/>
      <text x="36" y="29.7" font-size="3.5" text-anchor="middle" font-family="'Arial Black', Impact, sans-serif" font-weight="900" font-style="italic" fill="#f5d20f">ON TOUR</text>
    </svg>`;
    // the button edge, seen from above (front, the insert's side, at the bottom): the four arches' sides, darker,
    // in a black channel; their tops float a little over it
    const arch = (x, inset) => { const w = 10.5 - inset * 2, z0 = 2.5 + inset, z1 = 14.6 - inset; return `M${x + inset} ${z0}h${w}v${z1 - z0 - w / 2}a${w / 2} ${w / 2} 0 0 1 ${-w} 0z`; };
    const xs = [4, 19.5, 35, 50.5];
    const bar = `<svg viewBox="0 0 ${L} ${T}" preserveAspectRatio="none" aria-hidden="true">
      <rect x="1.8" y="1.6" width="61.6" height="14" rx="2" fill="#0d0e10"/>
      ${xs.map((x, i) => `<path d="${arch(x, 0)}" fill="${shade(FRETS[i], -0.45)}"/>`).join('')}
    </svg>`;
    const caps = `<svg viewBox="0 0 ${L} ${T}" preserveAspectRatio="none" aria-hidden="true">
      ${xs.map((x, i) => `<path d="${arch(x, 0)}" fill="${FRETS[i]}"/><path d="${arch(x, 1.6)}" fill="#141416"/><path d="${arch(x, .35)}" fill="none" stroke="#fff" stroke-opacity=".35" stroke-width=".35"/>`).join('')}
    </svg>`;
    // the wave the clear end curves out in
    const wave = `<svg viewBox="0 0 ${T} ${P}" preserveAspectRatio="none" aria-hidden="true"><path d="M0 0h${T}v${P * .55}c-5 2 -7 8 -${T * .55} 12c-4 3 -${T * .3} 6 -${T * .45} ${P * .45 - 12}H0z" fill="#fff" fill-opacity=".14"/></svg>`;
    // the strap: black woven fabric round the lower part, padded out from the insert, the logo on it in grey
    const S = 16 * s, SY = 16 * s, fabric = 'background:repeating-linear-gradient(90deg,rgba(255,255,255,.05) 0 1px,transparent 1px 3px),repeating-linear-gradient(0deg,rgba(0,0,0,.25) 0 1px,transparent 1px 2.5px),linear-gradient(#26262a,#141416);box-shadow:inset 0 1px 0 rgba(255,255,255,.12),inset 0 -1px 2px rgba(0,0,0,.6)';
    const strapLogo = `<svg viewBox="0 0 75 16" aria-hidden="true"><g transform="translate(37.5 10.4) skewX(-8)" text-anchor="middle" font-family="Impact, 'Arial Black', sans-serif" font-weight="900" fill="#9a9a9e"><text font-size="6.2" letter-spacing=".4">GUITAR HERO</text></g></svg>`;
    return `<div class="gg xtra" style="--gw:${px(W)};--gh:${px(H)}"><div class="ggi">
      ${cuboid(W, H, D, {
        front: ['background:linear-gradient(135deg,#f2f3f4,#c9ccd0 45%,#e6e8ea 70%,#aeb2b7);box-shadow:inset 0 0 0 ' + px(1.4 * s) + ' rgba(205,220,235,.75),inset 0 0 0 ' + px(1.8 * s) + ' rgba(255,255,255,.6)', insert],
        back: [clear], right: [clear, wave], left: [clear], bottom: [clear],
        top: ['background:linear-gradient(#3a4048,#22262b);box-shadow:inset 0 0 0 1px rgba(235,242,250,.5)', bar],
      })}
      ${cuboid(W, H, D, { top: ['background:none', caps] }, `translateY(${px(-1.2 * s)})`)}
      ${cuboid(W + 3 * s, S, D + 4 * s, { front: [fabric, strapLogo], back: [fabric], left: [fabric], right: [fabric] }, `translateY(${px(SY)})`)}
    </div></div>`;
  }

  /* ---------- the microphone ----------
     One of the two USB microphones Let's Sing 2024's 2 Mic Pack came with (g.extras: 'mic', once for each), from
     the mic printed on the box's sides: about 182 mm long and slim, all black. From the bottom: the ribbed rubber
     sleeve the cable comes out of, the end cap, the gloss handle widening from 24 mm across to 33, a chrome ring and
     the mesh head 38 across, its top a dome. It stands up beside the card, a turned shape drawn as rings of strips
     (pwRing) round its length, upright. */
  const MIC_N = 36;
  // a strip's angle round the mic, 0 facing front; the light is at the front left (as on the cartridges)
  const micGlint = (i, p) => Math.max(0, Math.cos(i * 2 * Math.PI / MIC_N + 0.5)) ** p;
  const mix = (a, b, k) => { const A = parseInt(a.slice(1), 16), B = parseInt(b.slice(1), 16);
    return '#' + [16, 8, 0].map(o => Math.round(((A >> o) & 255) * (1 - k) + ((B >> o) & 255) * k).toString(16).padStart(2, '0')).join(''); };
  // its outline from the bottom up: [radius, height] (mm), and how each band between two points is coloured
  const MIC = [
    [[2.3, 0], [3.6, 2.4], [3.3, 3], [3.7, 5.4], [3.4, 6], [3.9, 8.4], [3.6, 9], [4.1, 11.4], [4.4, 14], 'rubber'],
    [[4.4, 14], [10.5, 15.2], [11.4, 17], 'cap'], [[11.4, 17], [11.4, 26], 'cap'], [[11.4, 26], [12, 27], 'seam'],
    [[12, 27], [13.7, 70], [15.3, 110], [16.4, 134], 'gloss'], [[16.4, 134], [18.4, 136.4], 'gloss'],
    [[18.4, 136.4], [19.2, 137.6], [19.2, 142.4], [18.6, 143.4], 'chrome'],
    [[18.9, 143.4], [19.1, 150], [19.1, 160], [18.1, 167], [15.6, 173], [11.7, 177.6], [6.6, 180.8], [0, 182], 'mesh'],
  ];
  const MIC_COL = {
    rubber: i => mix('#0c0c0d', '#2e2f33', micGlint(i, 6) * .8),
    cap: i => mix('#0f1012', '#3c3f45', micGlint(i, 8) * .9),
    seam: () => '#050506',
    gloss: i => mix('#08090a', '#6a6f77', micGlint(i, 16) * .8),
    // chrome: the room round it in bands, the light a hard white streak
    chrome: i => { const g = micGlint(i, 1); return g > .93 ? '#ffffff' : mix('#3e434a', '#e8ebef', (Math.sin(g * 11) + 1) / 2 * .6 + g * .4); },
    mesh: i => mix('#111214', '#3c3e43', micGlint(i, 3) * .7),
  };
  function micHTML(s) {
    const H = 182, R = 19.2, d = px(Math.max(1.2, 1.1 * s));
    // the mesh: a fine grid of holes over the head
    const mesh = `;background-image:radial-gradient(circle,rgba(0,0,0,.75) 32%,transparent 44%);background-size:${d} ${d}`;
    // (heights up are negative z once it is stood up; each band runs top to bottom so its strips face out)
    const rings = MIC.map(b => {
      const col = MIC_COL[b[b.length - 1]], pts = b.slice(0, -1);
      return pts.slice(1).map((q, k) => pwRing(s, q[0], -q[1], pts[k][0], -pts[k][1], (y, i) => col(i), MIC_N, b[b.length - 1] === 'mesh' ? mesh : '')).join('');
    }).join('');
    return `<div class="mic xtra" style="--mw:${px(2 * R * s)};--mh:${px(H * s)}"><div class="mi">${rings}</div></div>`;
  }

  // what else a game's box holds, beside its card: each extra's height (mm, for the room it needs coming out), its
  // model and how much of it comes out of the box (out, a share of its height: the mics only their heads, so the box
  // needn't shrink far to make room)
  const EXTRAS = { pokewalker: { h: 48, html: walkerHTML }, guitargrip: { h: GRIP.p, html: gripHTML }, mic: { h: 182, out: 0.4, html: micHTML } };
  const extrasOf = g => (g.extras || []).filter(k => EXTRAS[k]);

  /* ---------- a box built from its parts (g.model) ----------
     Most boxes are a block with a picture on each face; a few special editions are built from what they are made of.
     sims3-ce, The Sims 3 Collector's Edition (USA), measured from photos of a real one: a clear plastic box 148 × 208
     × 60 mm. A printed card sleeve wraps its lower 110 mm; the DVD's card holder stands 18 mm deep across its back, the
     size of a DVD case, its front the green holographic panel and its sides printed (WIN MAC, EA) full height. In the
     clear window above the sleeve the plumbob USB drive stands point down in front of the panel: a six-sided
     bipyramid 72 mm tall, its lower half the cap over the USB plug, a ring at its top. Taking it out lifts the plumbob
     out through the lid, takes its cap off the plug and turns it, as a disc spins in an open case.
     The card faces are the photos straightened (cover.file the front, cover.back, cover.spine the left side,
     cover.side the right), each cut to the part that is card; the clear parts are drawn as plastic. */
  // a flat part w × h (px) with its middle at (x, y, z): x and y from the top left of the front, z from the middle of
  // the depth, toward the front; rot turns it about its middle
  const part = (k, w, h, [x, y, z], rot = '', st = '', html = '') =>
    `<div class="mp ${k}" style="width:${px(w)};height:${px(h)};transform:translate3d(${px(x - w / 2)},${px(y - h / 2)},${px(z)}) ${rot};${st}">${html}</div>`;
  // vectors
  const sub = (a, b) => a.map((v, i) => v - b[i]), len = a => Math.hypot(...a), unit = a => a.map(v => v / len(a));
  const cross = (a, b) => [a[1] * b[2] - a[2] * b[1], a[2] * b[0] - a[0] * b[2], a[0] * b[1] - a[1] * b[0]];
  const dot = (a, b) => a.reduce((t, v, i) => t + v * b[i], 0);
  // the light, from above on the left and in front, as on the cartridges
  const LIGHT = unit([-0.45, -0.7, 0.55]);
  // a triangle with its point at A and its base from P to Q (px): a div laid on it with matrix3d
  function facet(A, P, Q, col, alpha) {
    const M = P.map((v, i) => (v + Q[i]) / 2), w = len(sub(Q, P)), h = len(sub(M, A));
    const u = unit(sub(Q, P)), v = unit(sub(M, A)), n = cross(u, v), O = A.map((a, i) => a - u[i] * w / 2);
    // its outward side, and how much of it the light falls on
    const out = unit(cross(sub(P, A), sub(Q, A))), mid = [A, P, Q].reduce((t, p) => t.map((s, i) => s + p[i] / 3), [0, 0, 0]);
    const lit = dot(dot(out, mid) < 0 ? out.map(x => -x) : out, LIGHT);
    const f = shade(col, lit > 0 ? lit * 0.5 : lit * 0.55);
    const m = [...u, 0, ...v, 0, ...n, 0, ...O, 1].map(x => +x.toFixed(4));
    return `<div class="pf" style="width:${px(w)};height:${px(h)};transform:matrix3d(${m.join(',')})"><svg viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
      <path d="M50 0L100 100H0Z" fill="${f}" fill-opacity="${alpha}" stroke="${shade(col, .55)}" stroke-opacity=".5" stroke-width="1.2" vector-effect="non-scaling-stroke"/>
      <path d="M50 0L62 100H38Z" fill="#eaffd8" fill-opacity="${(Math.max(0, lit) * .3).toFixed(2)}"/></svg></div>`;
  }
  const PB = { r: 21.5, up: 36, down: 36, col: '#24901c' };
  // the plumbob, round its middle (where its two halves meet), its point down
  function plumbobHTML(s) {
    const { r, up, down, col } = PB;
    // the six corners round its middle, a flat face to the front
    const ring = Array.from({ length: 6 }, (_, k) => { const a = (k * 60 + 30) * Math.PI / 180; return [r * Math.sin(a) * s, 0, r * Math.cos(a) * s]; });
    const top = ring.map((p, k) => facet([0, -up * s, 0], p, ring[(k + 1) % 6], col, .8)).join('');
    const cap = ring.map((p, k) => facet([0, down * s, 0], ring[(k + 1) % 6], p, shade(col, -0.06), .86)).join('');
    // inside: the circuit board in the top half, the USB plug standing down into the cap
    const board = part('pb-board', 12 * s, 22 * s, [0, -9 * s, 0], '', '', '<i></i><i></i>');
    const metal = 'background:linear-gradient(90deg,#8e9196,#e3e5e8 35%,#b4b7bb 60%,#7c7f84)';
    const plug = [
      part('pb-plug', 12 * s, 17 * s, [0, 12.5 * s, 2.25 * s], '', metal, '<i></i><i></i>'),
      part('pb-plug', 12 * s, 17 * s, [0, 12.5 * s, -2.25 * s], 'rotateY(180deg)', metal, '<i></i><i></i>'),
      part('pb-plug', 4.5 * s, 17 * s, [-6 * s, 12.5 * s, 0], 'rotateY(-90deg)', metal + ';filter:brightness(.8)'),
      part('pb-plug', 4.5 * s, 17 * s, [6 * s, 12.5 * s, 0], 'rotateY(90deg)', metal + ';filter:brightness(.8)'),
      part('pb-plug', 12 * s, 4.5 * s, [0, 21 * s, 0], 'rotateX(-90deg)', metal + ';filter:brightness(.65)'),
    ].join('');
    // the split ring through the hole at its top, seen as two crossing loops
    const loop = [0, 90].map(a => part('pb-ring', 6.5 * s, 6.5 * s, [0, (-up - 2.4) * s, 0], `rotateY(${a}deg)`, `border-width:${px(.9 * s)}`)).join('');
    return `<div class="pb-spin"><div class="pb-body">${board}${plug}${top}${loop}</div><div class="pb-cap">${cap}</div></div>`;
  }
  const MODELS = {
    'sims3-ce': (g, L, s) => {
      const { w: W, h: H, d: D } = L, C = g.cover;
      // db: the card holder's depth at the back; hs: the sleeve's height; wy: the sleeve's top; cd: the clear part's depth
      const db = 18 * s, hs = 110 * s, wy = H - hs, cd = D - db;
      const bg = (f, w, pos) => `background:url('${esc(f)}') ${pos} / ${px(w)} ${px(H)} no-repeat`;
      // the plumbob: its middle 52 mm down, halfway between the window and the panel, turned a little
      const lift = 118 * s, os = 0.64;
      const parts = [
        part('m-back', W, H, [W / 2, H / 2, -D / 2], 'rotateY(180deg)', bg(C.back, W, '0 0')),
        part('m-panel', W, H, [W / 2, H / 2, -D / 2 + db]),
        part('m-card', W, db, [W / 2, 0, -D / 2 + db / 2], 'rotateX(90deg)'),
        part('m-card m-bot', W, D, [W / 2, H, 0], 'rotateX(-90deg)'),
        part('m-sleeve', W, hs, [W / 2, H - hs / 2, D / 2], '', bg(C.file, W, '0 100%')),
        part('m-card m-ledge', W, cd, [W / 2, wy, D / 2 - cd / 2], 'rotateX(90deg)'),
        // the left side (the back on its left as you look at it) and the right (the back on its right): the holder's
        // side full height, the sleeve's below the window
        part('m-side', db, H, [0, H / 2, -D / 2 + db / 2], 'rotateY(-90deg)', bg(C.spine, D, '0 0')),
        part('m-side', cd, hs, [0, H - hs / 2, db / 2], 'rotateY(-90deg)', bg(C.spine, D, '100% 100%')),
        part('m-side m-r', db, H, [W, H / 2, -D / 2 + db / 2], 'rotateY(90deg)', bg(C.side, D, '100% 0')),
        part('m-side m-r', cd, hs, [W, H - hs / 2, db / 2], 'rotateY(90deg)', bg(C.side, D, '0 100%')),
        `<div class="pb" style="transform:translate3d(${px(W / 2)},${px(52 * s)},${px(D / 2 - cd / 2)}) rotateY(12deg)">${plumbobHTML(s)}</div>`,
        // the clear plastic: the window, the sides above the sleeve, and the lid, hinged at the holder
        part('m-clear', W, wy, [W / 2, wy / 2, D / 2]),
        part('m-clear', cd, wy, [0, wy / 2, db / 2], 'rotateY(-90deg)'),
        part('m-clear', cd, wy, [W, wy / 2, db / 2], 'rotateY(90deg)'),
        `<div class="m-hinge" style="transform:translate3d(${px(W / 2)},0,${px(-D / 2 + db)})"><div class="m-clear m-lid" style="width:${px(W)};height:${px(cd)};left:${px(-W / 2)}"></div></div>`,
      ];
      // taken out, the box shrinks to the bottom of its outline, as a cardboard box does, and the plumbob rises above it
      return { html: parts.join(''), vars: `--os:${os};--oy:${px(H * (1 - os) / 2)};--ty:${px(lift)};--cap:${px(24 * s)}` };
    },
    /* medieval-ce, The Sims Medieval Collector's Edition (AUS), measured from photos of a real one: a gold book
       146 × 206 × 30 mm in a card sleeve 148 × 207 × 32, open at the top and the bottom, so the book's gilt page
       edges show at both ends (cover.parts.top and .bottom, photographed down the sleeve). The book's cover takes
       its spine with it as it opens, hinged at the back: the kingdom's map is printed inside it, the spine is cream
       inside. In the book lies the plumbob board, a strip of what is under it showing along the spine, and its
       fore-edge is open on the DVD case's spine and the papers beside it. Taking it out slides the sleeve down off
       the book and sets it aside behind, then the cover swings open. Only the cover's outside, never photographed,
       is drawn: plain gold card. */
    'medieval-ce': (g, L, s) => {
      const { w: W, h: H, d: D } = L, C = g.cover, P = C.parts;
      const t = 1 * s, bt = 2.2 * s;                        // the sleeve's card, the book's boards
      const y0 = .5 * s, bh = H - s, ym = H / 2, bw = W - 2 * t, bd = D - 2 * t;
      // the body between the boards, set in a little from their edges (not on the spine side)
      const bx0 = t + .3 * s, bx1 = W - t - 1.5 * s, by0 = y0 + 1.5 * s, by1 = H - y0 - 1.5 * s, bz = bd / 2 - bt;
      const img = (f, w, h, pos = '0 0') => `background:url('${esc(f)}') ${pos} / ${px(w)} ${px(h)} no-repeat`;
      const gold = 'm-gold';
      const sleeve = [
        part('m-sl', W, H, [W / 2, ym, D / 2], '', img(C.file, W, H)),
        part('m-sl', W, H, [W / 2, ym, -D / 2], 'rotateY(180deg)', img(C.back, W, H)),
        part('m-sl', D, H, [0, ym, 0], 'rotateY(-90deg)', img(C.spine, D, H)),
        part('m-sl m-r', D, H, [W, ym, 0], 'rotateY(90deg)', img(C.side, D, H)),
        // its inside, plain card, for looking down it empty
        part('m-sli', W, H, [W / 2, ym, D / 2 - .4 * s], 'rotateY(180deg)'),
        part('m-sli', W, H, [W / 2, ym, -D / 2 + .4 * s]),
        part('m-sli', D, H, [.4 * s, ym, 0], 'rotateY(90deg)'),
        part('m-sli', D, H, [W - .4 * s, ym, 0], 'rotateY(-90deg)'),
      ].join('');
      // the cover and the spine, as one, from the hinge at the back of the spine: x right, z forward from there
      const lid = [
        part(gold, bd, bh, [0, ym, bd / 2], 'rotateY(-90deg)'),
        part('m-band', bd, bh, [.6 * s, ym, bd / 2], 'rotateY(90deg)', img(P.band, bd, bh)),
        part(gold + ' m-cover', bw, bh, [bw / 2, ym, bd]),
        part('m-map', bw, bh, [bw / 2, ym, bd - bt], 'rotateY(180deg)', img(P.map, bw, bh)),
        part(gold + ' m-r', bt, bh, [bw, ym, bd - bt / 2], 'rotateY(90deg)'),
        part(gold + ' m-t', bw, bt, [bw / 2, y0, bd - bt / 2], 'rotateX(90deg)'),
        part(gold + ' m-b', bw, bt, [bw / 2, H - y0, bd - bt / 2], 'rotateX(-90deg)'),
      ].join('');
      const book = [
        // the back board
        part(gold, bw, bh, [W / 2, ym, -bd / 2], 'rotateY(180deg)'),
        part(gold + ' m-r', bt, bh, [W - t, ym, -bd / 2 + bt / 2], 'rotateY(90deg)'),
        part(gold + ' m-t', bw, bt, [W / 2, y0, -bd / 2 + bt / 2], 'rotateX(90deg)'),
        part(gold + ' m-b', bw, bt, [W / 2, H - y0, -bd / 2 + bt / 2], 'rotateX(-90deg)'),
        // the body: the plumbob board, the open fore-edge, the page edges
        part('m-in', bx1 - bx0, by1 - by0, [(bx0 + bx1) / 2, ym, bz], '', img(P.inside, bx1 - bx0, by1 - by0)),
        part('m-in m-r', 2 * bz, by1 - by0, [bx1, ym, 0], 'rotateY(90deg)', img(P.edge, 2 * bz, by1 - by0)),
        part('m-in m-t', bx1 - bx0, 2 * bz, [(bx0 + bx1) / 2, by0, 0], 'rotateX(90deg)', img(P.pages, bx1 - bx0, 2 * bz)),
        part('m-in m-b', bx1 - bx0, 2 * bz, [(bx0 + bx1) / 2, by1, 0], 'rotateX(-90deg)', img(P.pages, bx1 - bx0, 2 * bz)),
        part('m-sli', 2 * bz, by1 - by0, [bx0, ym, 0], 'rotateY(-90deg)'),
        `<div class="m-hinge" style="transform:translate3d(${px(t)},0,${px(-bd / 2)})"><div class="m-leaf">${lid}</div></div>`,
        // the ends as they show down the sleeve, the sleeve's edges round them; gone once it comes off
        part('m-end', W, D, [W / 2, 0, 0], 'rotateX(90deg)', img(P.top, W, D)),
        part('m-end m-b', W, D, [W / 2, H, 0], 'rotateX(-90deg)', img(P.bottom, W, D)),
      ].join('');
      // open, the whole shrinks a little and moves right, to make room for the cover swung out to the left
      const os = .7;
      return {
        cls: 'm-book',
        html: `<div class="m-sleeve">${sleeve}</div>${book}`,
        vars: `--os:${os};--oy:0px;--ox:${px(40 * s * os)};--slide:${px(H * 1.06)};--aside:translate3d(${px(W * .38)},${px(-H * .99)},${px(-D * 2.3)}) rotateY(-10deg)`,
      };
    },
  };
  function modelBox(g, s) {
    const L = layout(g, s), M = MODELS[g.model](g, L, s);
    return el(`<div class="bx model ${M.cls || ''} ${cls(g, L)}" style="${vars(g, L)};--sd:${px(L.d)};${M.vars};width:${px(L.w)};height:${px(L.h)}">${M.html}</div>`);
  }


  /* ---------- what is inside ---------- */
  // a PAL or Japanese SNES game comes in the Super Famicom's shell (CART.sfc)
  const mediaType = g => {
    if (g.big || g.bigCase) return 'cd';
    const m = C()[styleOf(g)].media;
    return m === 'snes' && ['PAL', 'NTSC-J'].includes(window.REGIONS[g.region]?.std) ? 'sfc' : m;
  };
  const isCart = g => !!CART[mediaType(g)];
  function mediaHTML(g, s) {
    const type = mediaType(g);
    if (!CART[type]) return discHTML(g, s, type);
    // in a plastic case the card sits in the moulded holder low down inside (css/cases.css);
    // out of a cardboard box it stands on its own
    // a bundle: what came with it (a Pokéwalker, a Guitar Grip) comes out beside the card
    if (layout(g, s).kind !== 'keep') return extrasOf(g).length ? `<div class="duo" style="--gap:${px(8 * s)}">${cartHTML(g, s, type)}${extrasOf(g).map(k => EXTRAS[k].html(s)).join('')}</div>` : cartHTML(g, s, type);
    const T = CART[type];
    // a case sold with a download code instead of the card: the holder empty, the code's slip of paper lying above it
    const code = g.format === 'code-in-box';
    return `${code ? '<div class="codeslip"><b>Download code</b><i></i><i></i></div>' : ''}<div class="holder${code ? ' empty' : ''}" style="--hw:${px((T.w + 5) * s)};--hh:${px((T.h + 5) * s)}">${code ? '' : cartHTML(g, s, type)}</div>`;
  }
  /* A disc lies in its tray with its data side up, the side the laser reads, so each console's own look shows:
     a PlayStation disc is black, a PS2 one blue-violet (the colour of its CD-ROMs; most of its DVDs were
     silver), a Blu-ray a cool blue-grey silver, the rest plain silver. Out from the hole: the clear hub with
     its stacking ring, the mirror band with the pressing's codes moulded round it, then the data to the rim.
     An Xbox disc has its hologram round the hub, XBOX in it; a Wii or Wii U disc has its BCA, the ring of
     barcode stripes just inside the data. A Wii U disc's edges are rounded. Drawn in mm, 120 across. */
  const DISC = {
    ps1:  { face: 'black',  mould: 'PlayStation', fmt: 'CD-ROM' },
    ps2:  { face: 'violet', mould: 'PlayStation 2', fmt: 'DVD-ROM' },
    ps3:  { face: 'bd',     mould: 'PlayStation 3', fmt: 'BD-ROM' },
    ps4:  { face: 'bd',     mould: 'PS4', fmt: 'BD-ROM' },
    ps5:  { face: 'bd',     mould: 'PS5', fmt: 'BD-ROM' },
    xbox: { face: 'silver', mould: 'Microsoft', fmt: 'DVD-ROM', holo: 'XBOX' },
    x360: { face: 'silver', mould: 'Microsoft', fmt: 'DVD-ROM', holo: 'XBOX 360' },
    xone: { face: 'bd',     mould: 'Microsoft', fmt: 'BD-ROM', holo: 'XBOX ONE' },
    wii:  { face: 'silver', mould: 'Nintendo', fmt: 'RVL', bca: true },
    wiiu: { face: 'silver', mould: 'Nintendo', fmt: 'WUP', bca: true, round: true },
    pc:   { face: 'silver', mould: 'PC', fmt: 'DVD-ROM' },
  };
  let discN = 0;
  function discHTML(g, s, type) {
    const T = g.big ? { face: 'silver', mould: 'PC', fmt: 'CD-ROM' } : DISC[styleOf(g)] || { face: 'silver', mould: '', fmt: '' };
    const id = 'dsc' + ++discN;
    // the same marks every time for the same game
    let seed = [...String(g.id || g.title)].reduce((h, ch) => (h * 31 + ch.charCodeAt(0)) % 2147483647, 7) || 7;
    const rnd = () => (seed = (seed * 16807) % 2147483647) / 2147483647;
    const code = `${T.mould} ${T.fmt}`.trim().toUpperCase();
    const ring = [code, esc(g.title.toUpperCase().slice(0, 34)), esc(window.REGIONS[g.region]?.name || '')].filter(Boolean).join('  ·  ');
    const circ = (r, sweep = 1) => `M0,${-r}A${r},${r} 0 1,${sweep} 0,${r}A${r},${r} 0 1,${sweep} 0,${-r}`;
    // the BCA: thin stripes of uneven widths and gaps round a narrow band
    let bca = '';
    if (T.bca) {
      const at = (r, a) => `${(r * Math.sin(a)).toFixed(2)},${(-r * Math.cos(a)).toFixed(2)}`;
      for (let a = 0; a < 2 * Math.PI;) {
        const w = .06 + rnd() * .26;  // mm
        bca += `<path d="M${at(22.4, a)}L${at(23.5, a)}" stroke-width="${w.toFixed(2)}"/>`;
        a += (w + .12 + rnd() * .6) / 23;
      }
    }
    const holo = T.holo ? `<linearGradient id="${id}g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#ff4f9a"/><stop offset=".25" stop-color="#ffd84a"/><stop offset=".5" stop-color="#4dffb0"/><stop offset=".75" stop-color="#4f9bff"/><stop offset="1" stop-color="#c06bff"/></linearGradient>
        <circle class="hlr" r="13.6" fill="none" stroke="url(#${id}g)" stroke-width="4"/><text class="hl" font-size="2.2"><textPath href="#${id}h" textLength="${(2 * Math.PI * 13.6 - 2).toFixed(1)}" lengthAdjust="spacing">${`${T.holo}  ·  `.repeat(T.holo.length > 4 ? 3 : 5)}</textPath></text>` : '';
    return `<div class="disc d-${T.face}${T.holo ? ' holo' : ''}${T.round ? ' round' : ''}" style="--dd:${px(120 * s)}"><div class="dfa">
      <svg class="dsp" viewBox="-60 -60 120 120" aria-hidden="true"><defs><path id="${id}m" d="${circ(19.6)}"/><path id="${id}h" d="${circ(13.6)}"/></defs>
        <text class="mo" font-size="1.55" letter-spacing=".12"><textPath href="#${id}m">${ring}</textPath></text>
        ${holo}${bca ? `<g class="bca">${bca}</g>` : ''}</svg></div></div>`;
  }
  const cart = (g, s) => el(cartHTML(g, s, mediaType(g)));

  /* ---------- 3D case ---------- */
  // stands each face of a 3D case in its place round the middle of the case
  // The depth is the --sd property (and the spine's lettering --sfs), not a fixed size, so a flight can widen
  // or narrow the spine smoothly: the rows draw spines much wider than real ones (css/cases.css registers both)
  function assemble(b, L) {
    const W = L.w, H = L.h, D = 'var(--sd)';
    const set = (sel, w, h, l, t, tr) => { const f = b.querySelector(sel); if (f) Object.assign(f.style, { width: w, height: h, left: l, top: t, transform: tr }); };
    set('.f-front', W + 'px', H + 'px', 0, 0, `translateZ(calc(${D} / 2))`);
    set('.f-tray', W + 'px', H + 'px', 0, 0, `translateZ(calc(${D} / 2 - 1px))`);
    set('.f-back', W + 'px', H + 'px', 0, 0, `rotateY(180deg) translateZ(calc(${D} / 2))`);
    set('.f-left', D, H + 'px', `calc((${W}px - ${D}) / 2)`, 0, `rotateY(-90deg) translateZ(${W / 2}px)`);
    set('.f-right', D, H + 'px', `calc((${W}px - ${D}) / 2)`, 0, `rotateY(90deg) translateZ(${W / 2}px)`);
    set('.f-top', W + 'px', D, 0, `calc((${H}px - ${D}) / 2)`, `rotateX(90deg) translateZ(${H / 2}px)`);
    set('.f-bot', W + 'px', D, 0, `calc((${H}px - ${D}) / 2)`, `rotateX(-90deg) translateZ(${H / 2}px)`);
    b.style.width = W + 'px';
    b.style.height = H + 'px';
    b.style.setProperty('--sd', px(L.d));
    b.style.setProperty('--sfs', px(Math.min(15, Math.max(7, L.d * 0.55))));
    b.querySelectorAll('.f-left > .sp, .lsp > .sp').forEach(sp => { sp.style.setProperty('--d', 'var(--sd)'); sp.style.fontSize = 'var(--sfs)'; });
    return b;
  }

  // just the front and the spine: a case turning as it goes between the shelf and the grid. solid: the edges
  // round it too, so it holds up seen from any side (the showcase), with its cover loaded only once it shows
  function slab(g, s, solid = false) {
    // a box built from its parts is that model wherever it faces out, and as it flies between the views
    if (MODELS[g.model]) return modelBox(g, s);
    const L = layout(g, s);
    // a picture of the real right side, when there is one
    const side = g.cover && g.cover.side ? ` style="background:url('${esc(g.cover.side)}') center / 100% 100%"` : '';
    return assemble(el(`<div class="bx slab ${cls(g, L)}" style="${vars(g, L)}">
      <div class="fc f-front">${frontHTML(g, L, solid)}</div>
      <div class="fc f-left">${spineHTML(g, L)}</div>
      ${solid ? `<div class="fc f-right"><div class="edge"${side}></div></div><div class="fc f-top"><div class="edge"></div></div><div class="fc f-bot"><div class="edge"></div></div>` : ''}
    </div>`), L);
  }

  // the manual's page of text: paragraphs of grey lines, the last line of each short
  const MANUAL = [4, 3, 5, 2, 4, 3, 5, 4].map((n, i) => '<i></i>'.repeat(n - 1) + `<i style="width:${[60, 45, 72, 35][i % 4]}%"></i>`).join('<b></b>');
  function box(g, s) {
    if (MODELS[g.model]) return modelBox(g, s);
    const L = layout(g, s);
    const H = L.h, W = L.w;
    // a plastic case opens in two halves, hinged at the seam in the middle: the lid takes the front half of the
    // spine and the walls round it with it, the back keeps the other half
    const split = L.kind !== 'box';
    // a cardboard box photographed all round: its right side and its top printed as they are (cover.side, cover.top)
    const pic = f => g.cover && g.cover[f] ? ` style="background:url('${esc(g.cover[f])}') center / 100% 100%"` : '';
    const b = el(`<div class="bx ${cls(g, L)}" style="${vars(g, L)}">
      <div class="fc f-tray"><div class="tray">${mediaHTML(g, s)}</div></div>
      <div class="fc f-front"><div class="leaf">${frontHTML(g, L, false)}<div class="inner"><div class="manual"><div class="mt">${esc(g.title)}</div><div class="ml">${MANUAL}</div></div></div>${split ? `<div class="lsp">${spineHTML(g, L)}</div><i class="edge lw lw-l"></i><i class="edge lw lw-r"></i><i class="edge lw lw-t"></i><i class="edge lw lw-b"></i>` : ''}</div></div>
      <div class="fc f-back">${backHTML(g, L)}</div>
      <div class="fc f-left">${spineHTML(g, L)}</div>
      ${split ? '<div class="fc f-lin"><div class="edge"></div></div>' : ''}
      <div class="fc f-right"><div class="edge"${pic('side')}></div></div>
      <div class="fc f-top">${split ? '<div class="edge"></div>' : `<div class="flap"><div class="edge"${pic('top')}></div><i class="fin"></i></div>`}</div>
      <div class="fc f-bot"><div class="edge"></div></div>
    </div>`);
    assemble(b, L);
    // a cardboard box is card inside too: the back, the two sides and the floor, facing in a hair inside the outside
    // ones, so they show when the top flap is open and the box is turned to look in
    if (!split) {
      const D = 'var(--sd)';
      b.insertAdjacentHTML('beforeend', `<div class="fc fi" style="width:${W}px;height:${H}px;left:0;top:0;transform:translateZ(calc(${D} / -2 + .5px))"></div>
        <div class="fc fi fi-s" style="width:${D};height:${H}px;left:calc(${W / 2}px - ${D} / 2);top:0;transform:rotateY(90deg) translateZ(${px(-W / 2 + .5)})"></div>
        <div class="fc fi fi-s" style="width:${D};height:${H}px;left:calc(${W / 2}px - ${D} / 2);top:0;transform:rotateY(-90deg) translateZ(${px(-W / 2 + .5)})"></div>
        <div class="fc fi fi-b" style="width:${W}px;height:${D};left:0;top:calc(${H / 2}px - ${D} / 2);transform:rotateX(90deg) translateZ(${px(-H / 2 + .5)})"></div>`);
    }
    if (split) {
      // the back half's spine and walls, from the back to the seam; the inside of the spine; the tray on the back, inside them
      const D = 'var(--sd)', Q = `translateZ(calc(${D} / -4)) `;
      const set = (sel, st) => Object.assign(b.querySelector(sel).style, st);
      set('.f-left', { width: `calc(${D} / 2)`, left: `calc(${W / 2}px - ${D} / 4)`, transform: Q + `rotateY(-90deg) translateZ(${W / 2}px)` });
      set('.f-right', { width: `calc(${D} / 2)`, left: `calc(${W / 2}px - ${D} / 4)`, transform: Q + `rotateY(90deg) translateZ(${W / 2}px)` });
      set('.f-top', { height: `calc(${D} / 2)`, top: `calc(${H / 2}px - ${D} / 4)`, transform: Q + `rotateX(90deg) translateZ(${H / 2}px)` });
      set('.f-bot', { height: `calc(${D} / 2)`, top: `calc(${H / 2}px - ${D} / 4)`, transform: Q + `rotateX(-90deg) translateZ(${H / 2}px)` });
      set('.f-lin', { width: `calc(${D} / 2)`, height: H + 'px', left: `calc(${W / 2}px - ${D} / 4)`, top: 0, transform: Q + `rotateY(90deg) translateZ(${-W / 2 + 0.5}px)` });
      set('.f-tray', { transform: `translateZ(calc(${D} / -2 + 1px))` });
    }
    // taking it out of a cardboard box: the box shrinks to the bottom of its outline and the
    // cartridge rises three quarters out of the top, so the open box needs no more room than the closed one
    if (L.kind === 'box') {
      const T = CART[mediaType(g)], ch = (T ? T.h : 120) * s, ex = extrasOf(g), mh = Math.max(ch / s, ...ex.map(k => EXTRAS[k].h)) * s;
      // (what came with it comes all but out, so a Pokéwalker's or a Guitar Grip's buttons show over the box's edge)
      const k = ex.length ? Math.min(...ex.map(k => EXTRAS[k].out || 0.92)) : 0.75, os = Math.min(0.85, H / (H + k * mh));
      // beside something that comes only part way out, the card is lifted to still come three quarters out
      b.style.setProperty('--lift', px(Math.max(0, mh * (1 - k) - ch * 0.25)));
      b.style.setProperty('--os', os.toFixed(3));
      b.style.setProperty('--oy', px(H * (1 - os) / 2));
      b.style.setProperty('--ty', px(H / 2 + (k - 0.5) * mh));
    }
    return b;
  }

  /* ---------- the top of a cartridge ----------
     A SNES cartridge carries its title on the top edge, where the label wraps over. Lying in a pile
     with that edge facing out, this is what shows: the raised middle with the black top label and
     the title on it, the lower ridged sides either end. Its size is the cartridge's width by its thickness.
     Standing on its side (side = true) it is turned upright, so the title reads down like a book's spine.
     thick: how thick to draw it, in px, when that is not its real thickness (the shelf and the rows draw it
     thicker, as they do the spines beside it, so its title can be read) */
  function topHTML(g, s, side, thick) {
    const T = CART[mediaType(g)];
    const c = /^#[0-9a-f]{6}$/i.test(g.cartColor || '') ? g.cartColor : T.c;
    const t = thick || T.dp * s;
    const strip = `<div class="ctop c-${mediaType(g)}${g.cartGlitter ? ' glit' : ''}" style="--cw:${px(T.w * s)};--ch:${px(t)};--cc:${c}">
      <i class="side"></i><i class="mid">${titledTop(g, T) ? `<b>${esc(g.title)}</b>` : ''}</i><i class="side"></i>
    </div>`;
    return side ? `<div class="cside" style="width:${px(t)};height:${px(T.w * s)}">${strip}</div>` : strip;
  }
  const top = (g, s, side, thick) => el(topHTML(g, s, side, thick));
  const topSize = (g, s, side, thick) => { const T = CART[mediaType(g)], t = thick || (T && T.dp * s); return !T ? [0, 0] : side ? [t, T.w * s] : [T.w * s, t]; };

  // a cartridge's size in px at scale s, for laying one out
  const cartSize = (g, s) => { const T = CART[mediaType(g)]; return T ? [T.w * s, T.h * s] : [0, 0]; };

  // a loose cartridge that shows its title on its top edge (or a tag with it), so it can stand on its side on the shelf
  const showsTop = g => { const T = CART[mediaType(g)]; return !!T && titledTop(g, T); };

  window.Case = { dims, layout, front, spine, box, slab, cart, cartSize, top, topSize, showsTop, isCart, date, year, esc, styleOf };
})();
