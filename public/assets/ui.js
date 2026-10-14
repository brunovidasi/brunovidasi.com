/* ui.js — the shell every prototype shares: the prototype switcher, the header,
   the console filter, the tooltip and the 3D detail view.
   The filter lives in the URL hash, so switching prototypes keeps it. */

(function () {
  const esc = Case.esc;
  const PROTOS = [
    ['1-shelf.html', 'Shelf'],
    ['2-wall.html', 'Display wall'],
    ['3-showcase.html', 'Showcase'],
    ['4-stacks.html', 'Stacks'],
  ];

  // icon colours per console: [plastic, banner]
  const MINI = {
    ps1: ['#cfd6dd', '#0b0b0b'], ps2: ['#1a1a1a', '#3a3a3a'], ps3: ['#24272e', '#0c0c0e'], ps4: ['#1e4ea8', '#003791'], ps5: ['#2456b0', '#ffffff'],
    xbox: ['#1a1a1a', '#4fae22'], x360: ['#2f8f33', '#e6e9ec'], xone: ['#1c7c1c', '#107c10'],
    snes: ['#8a8a96', '#3d3d44'], gbc: ['#b0a080', '#111'], gba: ['#8a8ad8', '#2c2a86'],
    ds: ['#2b2b30', '#ececec'], '3ds': ['#f3f3f3', '#d0021b'], n3ds: ['#f3f3f3', '#d0021b'],
    wii: ['#f7f7f7', '#c9cdd1'], wiiu: ['#1c9bd1', '#009ac7'], switch: ['#e60012', '#e60012'], switch2: ['#d8000f', '#111'],
    pc: ['#1a1a1a', '#66c0f4'],
  };
  const miniVars = id => {
    const c = CONSOLE_BY_ID[id];
    return `--mw:${(c.w * .14).toFixed(1)};--mh:${(c.h * .14).toFixed(1)};--mp:${MINI[id][0]};--mb:${MINI[id][1]}`;
  };

  /* ---------- state ---------- */
  const state = { sel: new Set(), launcher: '', status: '', q: '', sort: 'console' };

  function readHash() {
    const p = new URLSearchParams(location.hash.slice(1));
    state.sel = new Set((p.get('c') || '').split(',').filter(id => CONSOLE_BY_ID[id]));
    state.launcher = p.get('l') || '';
    state.status = p.get('s') || '';
    state.q = p.get('q') || '';
    state.sort = p.get('o') || 'console';
  }
  function writeHash() {
    const p = new URLSearchParams();
    if (state.sel.size) p.set('c', [...state.sel].join(','));
    if (state.launcher) p.set('l', state.launcher);
    if (state.status) p.set('s', state.status);
    if (state.q) p.set('q', state.q);
    if (state.sort !== 'console') p.set('o', state.sort);
    const h = p.toString();
    try { history.replaceState(null, '', h ? '#' + h : location.pathname + location.search); } catch (e) { /* some sandboxes refuse it; filters just won't carry over */ }
    document.querySelectorAll('.protonav a, .keephash').forEach(a => { a.href = a.getAttribute('data-href') + (h ? '#' + h : ''); });
  }

  const order = Object.fromEntries(CONSOLES.map((c, i) => [c.id, i]));
  const SORTS = {
    console: (a, b) => order[a.console] - order[b.console] || a.year - b.year || a.title.localeCompare(b.title),
    title:   (a, b) => a.title.localeCompare(b.title),
    newest:  (a, b) => b.year - a.year || a.title.localeCompare(b.title),
    oldest:  (a, b) => a.year - b.year || a.title.localeCompare(b.title),
    added:   (a, b) => b.added.localeCompare(a.added),
    hours:   (a, b) => b.hours - a.hours,
  };

  // everything except the console filter, so chip counts can show what a click would give
  function base() {
    const q = state.q.trim().toLowerCase();
    return GAMES.filter(g =>
      (!state.status || g.status === state.status) &&
      (!state.launcher || g.launcher === state.launcher) &&
      (!q || (g.title + ' ' + g.publisher + ' ' + CONSOLE_BY_ID[g.console].name + ' ' + g.year).toLowerCase().includes(q)));
  }
  function list() {
    return base().filter(g => !state.sel.size || state.sel.has(g.console)).sort(SORTS[state.sort] || SORTS.console);
  }

  /* ---------- shell ---------- */
  let onChange = () => {};

  function init(opts) {
    onChange = opts.onChange || onChange;
    readHash();
    const here = location.pathname.split('/').pop();

    const top = document.createElement('div');
    top.className = 'topbar';
    top.innerHTML = `<a class="home keephash" data-href="index.html" href="index.html">Bruno's Games</a>
      <nav class="protonav">${PROTOS.map(([f, n], i) => `<a data-href="${f}" href="${f}" ${f === here ? 'aria-current="page"' : ''}>${i + 1} · ${n}</a>`).join('')}</nav>`;
    document.body.prepend(top);

    const shell = document.getElementById('shell');
    const hours = GAMES.reduce((t, g) => t + g.hours, 0);
    const done = GAMES.filter(g => g.status === 'completed').length;
    shell.innerHTML = `
      <header class="hero">
        <div>
          <div class="eyebrow">Bruno's Games</div>
          <h1>${opts.title || 'The Game Shelf'}</h1>
          <p>${opts.intro || 'Every boxed game on the shelf, from the Super Nintendo to the Switch 2.'}</p>
        </div>
        <div class="stats">
          <div><b>${GAMES.length}</b><span>Games</span></div>
          <div><b>${new Set(GAMES.map(g => g.console)).size}</b><span>Platforms</span></div>
          <div><b>${done}</b><span>Finished</span></div>
          <div><b>${hours.toLocaleString()}</b><span>Hours</span></div>
        </div>
      </header>
      <div class="filters"><div class="filters-in">
        <div class="consoles" role="toolbar" aria-label="Filter by console">
          <button class="chip all" data-all><span class="lab"><b>All</b><i data-n="all"></i></span></button>
          ${FAMILIES.map(f => `<div class="fam" style="--fc:${f.color}">
            <button class="famname" data-fam="${f.id}">${f.name}</button>
            <div class="row">${CONSOLES.filter(c => c.fam === f.id).map(c => `
              <button class="chip" data-c="${c.id}" title="${c.name}" aria-pressed="false">
                <span class="mini" style="${miniVars(c.id)}"></span>
                <span class="lab"><b>${c.short}</b><i data-n="${c.id}"></i></span>
              </button>`).join('')}</div></div>`).join('')}
        </div>
        <div class="subbar">
          <label class="search"><span aria-hidden="true">⌕</span><input type="search" placeholder="Search title, publisher, year…" aria-label="Search games" value="${esc(state.q)}"></label>
          <div class="pills" data-status>
            <button data-s="">Any status</button>
            ${Object.entries(STATUS).map(([k, s]) => `<button data-s="${k}"><span class="dot" style="--c:${s.color}"></span>${s.name}</button>`).join('')}
          </div>
          <select class="sort" aria-label="Sort">
            <option value="console">By console</option><option value="title">Title A–Z</option>
            <option value="newest">Newest first</option><option value="oldest">Oldest first</option>
            <option value="added">Recently added</option><option value="hours">Most played</option>
          </select>
          <span class="count"></span>
        </div>
        <div class="launchers"><span>PC · activates on</span>
          <button class="lbtn" data-l=""><i style="--lb:#3a332a;--lf:#fff">∗</i>Any</button>
          ${Object.entries(LAUNCHERS).filter(([k]) => GAMES.some(g => g.launcher === k)).map(([k, L]) =>
            `<button class="lbtn" data-l="${k}"><i style="--lb:${L.bg};--lf:${L.fg}">${L.glyph}</i>${L.name} <em>${GAMES.filter(g => g.launcher === k).length}</em></button>`).join('')}
        </div>
      </div></div>`;

    const $ = s => shell.querySelector(s);
    shell.querySelectorAll('.chip[data-c]').forEach(b => b.addEventListener('click', e => {
      const id = b.dataset.c;
      if (e.shiftKey || e.metaKey || e.ctrlKey) state.sel.has(id) ? state.sel.delete(id) : state.sel.add(id);
      else state.sel = (state.sel.size === 1 && state.sel.has(id)) ? new Set() : new Set([id]);
      if (!state.sel.has('pc')) state.launcher = '';
      changed();
    }));
    shell.querySelectorAll('.famname').forEach(b => b.addEventListener('click', () => {
      const ids = CONSOLES.filter(c => c.fam === b.dataset.fam).map(c => c.id);
      const same = ids.length === state.sel.size && ids.every(i => state.sel.has(i));
      state.sel = same ? new Set() : new Set(ids);
      if (!state.sel.has('pc')) state.launcher = '';
      changed();
    }));
    $('[data-all]').addEventListener('click', () => { state.sel = new Set(); state.launcher = ''; changed(); });
    shell.querySelectorAll('[data-s]').forEach(b => b.addEventListener('click', () => { state.status = b.dataset.s; changed(); }));
    shell.querySelectorAll('[data-l]').forEach(b => b.addEventListener('click', () => { state.launcher = b.dataset.l; changed(); }));
    let t;
    $('.search input').addEventListener('input', e => { clearTimeout(t); t = setTimeout(() => { state.q = e.target.value; changed(); }, 120); });
    $('.sort').value = state.sort;
    $('.sort').addEventListener('change', e => { state.sort = e.target.value; changed(); });

    const tip = document.createElement('div');
    tip.id = 'tip';
    document.body.append(tip);

    window.addEventListener('hashchange', () => { readHash(); changed(true); });
    sync();
    writeHash();
    const on = shell.querySelector('.chip.on[data-c]');
    if (on) { const row = shell.querySelector('.consoles'); row.scrollLeft = on.offsetLeft - row.offsetLeft - 40; }
  }

  function sync() {
    const shell = document.getElementById('shell');
    const b = base();
    const counts = {};
    b.forEach(g => counts[g.console] = (counts[g.console] || 0) + 1);
    shell.querySelectorAll('[data-n]').forEach(i => {
      const k = i.dataset.n;
      const n = k === 'all' ? b.length : (counts[k] || 0);
      i.textContent = n + (n === 1 ? ' game' : ' games');
      if (k !== 'all') i.closest('.chip').classList.toggle('empty', !n);
    });
    shell.querySelectorAll('.chip[data-c]').forEach(c => { const on = state.sel.has(c.dataset.c); c.classList.toggle('on', on); c.setAttribute('aria-pressed', on); });
    shell.querySelector('[data-all]').classList.toggle('on', !state.sel.size);
    shell.querySelectorAll('.famname').forEach(f => {
      const ids = CONSOLES.filter(c => c.fam === f.dataset.fam).map(c => c.id);
      f.classList.toggle('on', ids.length === state.sel.size && ids.every(i => state.sel.has(i)));
    });
    shell.querySelectorAll('[data-s]').forEach(x => x.classList.toggle('on', x.dataset.s === state.status));
    shell.querySelectorAll('[data-l]').forEach(x => x.classList.toggle('on', x.dataset.l === state.launcher));
    shell.querySelector('.launchers').classList.toggle('vis', state.sel.has('pc'));
    const n = list().length;
    shell.querySelector('.count').textContent = `${n} of ${GAMES.length} games`;
  }

  function changed(fromHash) {
    if (!fromHash) writeHash();
    sync();
    onChange(list());
  }

  /* ---------- tooltip ---------- */
  function tip(target, g) {
    const t = document.getElementById('tip');
    target.addEventListener('pointerenter', () => {
      t.innerHTML = `<b>${esc(g.title)}</b><span>${CONSOLE_BY_ID[g.console].name} · ${g.year}${g.launcher ? ' · ' + LAUNCHERS[g.launcher].name : ''}</span>`;
      t.classList.add('vis');
    });
    target.addEventListener('pointermove', e => {
      const x = Math.min(e.clientX + 14, innerWidth - t.offsetWidth - 8);
      const y = e.clientY + 18 + t.offsetHeight > innerHeight ? e.clientY - t.offsetHeight - 12 : e.clientY + 18;
      t.style.transform = `translate(${x}px,${y}px)`;
      t.style.left = t.style.top = '0';
    });
    target.addEventListener('pointerleave', () => t.classList.remove('vis'));
  }

  function empty(el) {
    el.innerHTML = `<div class="empty-msg"><b>Nothing on this shelf</b>No games match those filters — try another console or clear the search.</div>`;
  }

  /* ---------- detail view ---------- */
  let dv, cur, curList, from, rx = -6, ry = -28, isOpen = false;

  function buildDV() {
    dv = document.createElement('div');
    dv.className = 'dv';
    dv.setAttribute('role', 'dialog');
    dv.setAttribute('aria-modal', 'true');
    dv.innerHTML = `<div class="dv-bg"></div>
      <button class="dv-close" aria-label="Close">✕</button>
      <div class="stage"><span class="hint">Drag to turn</span><div class="mover"></div><div class="floor-shadow"></div>
        <div class="views">
          <button data-v="front">Front</button><button data-v="spine">Spine</button><button data-v="back">Back</button>
          <button class="open-btn" data-v="open">Open case</button>
        </div></div>
      <div class="info"></div>`;
    document.body.append(dv);
    dv.querySelector('.dv-bg').addEventListener('click', close);
    dv.querySelector('.dv-close').addEventListener('click', close);
    dv.querySelectorAll('[data-v]').forEach(b => b.addEventListener('click', () => view(b.dataset.v)));

    const stage = dv.querySelector('.stage');
    let drag = null;
    stage.addEventListener('pointerdown', e => {
      if (e.target.closest('.views')) return;
      drag = { x: e.clientX, y: e.clientY, rx, ry, moved: false };
      stage.setPointerCapture(e.pointerId);
    });
    stage.addEventListener('pointermove', e => {
      if (!drag) return;
      const dx = e.clientX - drag.x, dy = e.clientY - drag.y;
      if (Math.abs(dx) + Math.abs(dy) > 4) drag.moved = true;
      ry = drag.ry + dx * 0.5;
      rx = Math.max(-60, Math.min(60, drag.rx - dy * 0.35));
      turn(false);
    });
    stage.addEventListener('pointerup', () => {
      if (drag && !drag.moved) view(isOpen ? 'close' : 'open');
      drag = null;
    });

    document.addEventListener('keydown', e => {
      if (!dv.classList.contains('open')) return;
      if (e.key === 'Escape') close();
      if (e.key === 'ArrowRight') step(1);
      if (e.key === 'ArrowLeft') step(-1);
      if (e.key === ' ') { e.preventDefault(); view(isOpen ? 'close' : 'open'); }
    });
  }

  function turn(anim) {
    const bx = dv.querySelector('.bx');
    if (!bx) return;
    bx.style.transition = anim ? 'transform .8s cubic-bezier(.2,.8,.2,1), translate .8s cubic-bezier(.2,.8,.2,1), scale .8s' : 'translate .8s, scale .8s';
    bx.style.transform = `rotateX(${rx}deg) rotateY(${ry}deg)`;
    dv.querySelectorAll('[data-v]').forEach(b => b.classList.remove('on'));
    const n = ((ry % 360) + 360) % 360;
    const v = n > 45 && n < 135 ? 'spine' : n >= 135 && n < 225 ? 'back' : (n < 45 || n > 315) ? 'front' : '';
    if (v) dv.querySelector(`[data-v="${v}"]`).classList.add('on');
  }

  function view(v) {
    const bx = dv.querySelector('.bx');
    if (v === 'open' || v === 'close') {
      isOpen = v === 'open';
      bx.classList.toggle('open', isOpen);
      if (isOpen) { rx = -8; ry = cur && dims().kind === 'box' ? -18 : 18; }
      const box = dims().kind === 'box';
      const btn = dv.querySelector('.open-btn');
      btn.textContent = isOpen ? (box ? 'Put it back' : 'Close case') : (box ? 'Take it out' : 'Open case');
      btn.dataset.v = isOpen ? 'close' : 'open';
    } else {
      if (isOpen) view('close');
      rx = v === 'front' ? -4 : -6;
      ry = { front: -12, spine: 90, back: 180 }[v];
      // turn the short way round
      const k = Math.round((dv._ry || 0) / 360) * 360;
      ry += k;
    }
    dv._ry = ry;
    turn(true);
  }

  function scaleFor(g) {
    const d = Case.dims(g, 1);
    const stage = dv.querySelector('.stage').getBoundingClientRect();
    const maxH = Math.min(stage.height * 0.72, 470), maxW = Math.min(stage.width * 0.62, 460);
    return Math.min(maxH / d.h, maxW / d.w);
  }
  function dims() { return Case.dims(cur, 1); }

  function info(g) {
    const c = CONSOLE_BY_ID[g.console];
    const st = STATUS[g.status];
    const L = g.launcher && LAUNCHERS[g.launcher];
    const i = curList.indexOf(g);
    return `
      <div class="eyebrow"><span class="mini" style="${miniVars(g.console)}"></span>${c.name}${g.big ? ' · Big box' : ''}</div>
      <h2>${esc(g.title)}</h2>
      <div class="sub">${g.year} · ${esc(g.publisher)}</div>
      <div class="badges">
        <span class="badge"><span class="dot" style="--c:${st.color}"></span>${st.name}</span>
        ${g.stars ? `<span class="badge stars" aria-label="${g.stars} of 5">${'★'.repeat(g.stars)}${'☆'.repeat(5 - g.stars)}</span>` : ''}
        ${g.fav ? '<span class="badge">♥ Favourite</span>' : ''}
      </div>
      ${L ? `<div class="launch" style="--lb:${L.bg};--lf:${L.fg}"><i>${L.glyph}</i><div><small>${g.launcher === 'none' ? 'PC disc' : 'Activates on'}</small><b>${L.name}</b></div></div>` : ''}
      <dl class="facts">
        <div><dt>Condition</dt><dd>${g.condition}</dd></div>
        <div><dt>Region</dt><dd>${g.region}</dd></div>
        <div><dt>Played</dt><dd>${g.hours ? g.hours + ' h' : '—'}</dd></div>
        <div><dt>Rated</dt><dd>${g.esrb}</dd></div>
        <div><dt>Released</dt><dd>${g.year}</dd></div>
        <div><dt>On the shelf since</dt><dd>${new Date(g.added).toLocaleDateString('en-AU', { month: 'short', year: 'numeric' })}</dd></div>
      </dl>
      ${g.note ? `<p class="note">${esc(g.note)}</p>` : ''}
      <div class="navbtns">
        <button data-step="-1" ${i <= 0 ? 'disabled' : ''}>← Prev</button>
        <button data-step="1" ${i >= curList.length - 1 ? 'disabled' : ''}>Next →</button>
        <span>${i + 1} / ${curList.length} · ← → keys · space opens</span>
      </div>`;
  }

  // how the case sits on the page before it flies out: spine out, lying down, or face out
  const FROM = { spine: 'rotateY(90deg)', down: 'rotateZ(-90deg) rotateY(90deg)', front: 'none' };

  function mount(g, fromEl, side) {
    cur = g;
    isOpen = false;
    const s = scaleFor(g);
    const mover = dv.querySelector('.mover');
    mover.innerHTML = '';
    const bx = Case.box(g, s);
    mover.append(bx);
    dv.querySelector('.info').innerHTML = info(g);
    dv.querySelectorAll('[data-step]').forEach(b => b.addEventListener('click', () => step(+b.dataset.step)));
    dv.querySelector('.open-btn').textContent = Case.dims(g, 1).kind === 'box' ? 'Take it out' : 'Open case';

    // fly in from where it was on the page
    rx = -6; ry = -28;
    if (fromEl) {
      const r = fromEl.getBoundingClientRect();
      const m = mover.getBoundingClientRect();
      const d = Case.dims(g, s);
      const k = side === 'spine' ? r.height / d.h : side === 'down' ? r.width / d.h : Math.min(r.width / d.w, r.height / d.h);
      mover.style.transition = 'none';
      mover.style.transform = `translate(${r.left + r.width / 2 - (m.left + m.width / 2)}px,${r.top + r.height / 2 - (m.top + m.height / 2)}px) scale(${k})`;
      bx.style.transition = 'none';
      bx.style.transform = FROM[side] || 'none';
      fromEl.style.visibility = 'hidden';
      from = fromEl;
      from.dataset.side = side || 'front';
      mover.getBoundingClientRect();
      requestAnimationFrame(() => {
        mover.style.transition = 'transform .75s cubic-bezier(.2,.85,.25,1)';
        mover.style.transform = 'none';
        dv._ry = ry;
        turn(true);
      });
    } else {
      mover.style.transform = 'none';
      dv._ry = ry;
      turn(true);
    }
  }

  function open(g, fromEl, side, lst) {
    if (!dv) buildDV();
    curList = lst || list();
    dv.classList.add('open');
    document.body.style.overflow = 'hidden';
    mount(g, fromEl, side);
  }

  function step(n) {
    const i = curList.indexOf(cur) + n;
    if (i < 0 || i >= curList.length) return;
    if (from) { from.style.visibility = ''; from = null; }
    mount(curList[i]);
    UI.onStep && UI.onStep(curList[i]);
  }

  function close() {
    const mover = dv.querySelector('.mover');
    const done = () => { dv.classList.remove('open'); if (from) from.style.visibility = ''; from = null; document.body.style.overflow = ''; mover.innerHTML = ''; };
    if (from && document.contains(from)) {
      const r = from.getBoundingClientRect();
      mover.style.transform = 'none';
      const m = mover.getBoundingClientRect();
      const d = Case.dims(cur, scaleFor(cur));
      const side = from.dataset.side;
      const k = side === 'spine' ? r.height / d.h : side === 'down' ? r.width / d.h : Math.min(r.width / d.w, r.height / d.h);
      const bx = dv.querySelector('.bx');
      bx.classList.remove('open');
      bx.style.transition = 'transform .5s cubic-bezier(.4,0,.2,1)';
      bx.style.transform = FROM[side] || 'none';
      mover.style.transition = 'transform .5s cubic-bezier(.4,0,.2,1)';
      mover.style.transform = `translate(${r.left + r.width / 2 - (m.left + m.width / 2)}px,${r.top + r.height / 2 - (m.top + m.height / 2)}px) scale(${k})`;
      dv.querySelector('.dv-bg').style.opacity = '';
      dv.classList.add('closing');
      setTimeout(() => { dv.classList.remove('closing'); done(); }, 480);
      dv.querySelector('.info').style.opacity = 0;
      setTimeout(() => dv.querySelector('.info').style.opacity = '', 520);
    } else done();
  }

  window.UI = { init, list, state, tip, open, empty, miniVars, changed, PROTOS };
})();
