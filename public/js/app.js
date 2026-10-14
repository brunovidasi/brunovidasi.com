/* app.js — the game shelf. Loads data/games.json and shows it on a bookcase, in a
   grid or in a list. Going between the shelf and the grid carries every case across:
   it comes off the shelf and turns to show its cover on the way, as the record site
   carries its records between the floor and the grid. A game picked from anywhere
   lifts off the page and flies to the middle, where it can be turned round and
   opened; closing flies it back into its place.

   The collections (js/sims.js, js/gta.js) are filters at the top of the same page:
   each adds itself to window.COLLECTIONS before this script, and picking one (/sims,
   /gta in the address) shows only its games, grouped by game rather than by console,
   with its own header, its own look (a class on <html>) and one more view, its checklist:
     name        what the top bar calls it ("The Sims")
     title, icon the page's title and favicon while it is picked
     data        more JSON files to load besides data/games.json
     games(db, …data)   the games on this page
     group(g)    { key, label, name, order } — the game a copy belongs to
     groupNoun   what a group is called ("game")
     hero(games) the header's HTML
     checklist(el, games)   draws the checklist view into el
     drawer(g, games)       optional: { title, html } of a section for a game's drawer, or null
                            (shown wherever the game is opened, the whole shelf too) */

(function () {
  const esc = Case.esc;
  const $ = (s, el = document) => el.querySelector(s);
  const $$ = (s, el = document) => [...el.querySelectorAll(s)];
  const clamp = (v, a, b) => Math.max(a, Math.min(b, v));
  const lerp = (a, b, t) => a + (b - a) * t;
  const easeInOut = t => (t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2);
  const reduced = () => matchMedia('(prefers-reduced-motion: reduce)').matches;
  const ORDER = Object.fromEntries(CONSOLES.map((c, i) => [c.id, i]));
  const collator = new Intl.Collator(undefined, { numeric: true, sensitivity: 'base' });
  // the collections picked at the top (js/sims.js, js/gta.js); CFG is the one showing, null for the whole shelf
  const COLLECTIONS = window.COLLECTIONS || {};
  let CFG = null, COL = '';

  /* ---------- remembered choices (this browser only) ---------- */
  const store = {
    get(k, d) { try { const v = localStorage.getItem('games.' + k); return v === null ? d : JSON.parse(v); } catch (e) { return d; } },
    set(k, v) { try { localStorage.setItem('games.' + k, JSON.stringify(v)); } catch (e) { /* storage blocked */ } },
  };

  /* ---------- groups: the shelf stands its games by console, or in a collection by game ---------- */
  const byConsole = g => {
    const c = CONSOLE_BY_ID[g.console];
    return { key: g.console, label: c.short, name: c.name, order: ORDER[g.console], art: CONSOLE_ART[g.console] };
  };
  let GROUP = 'console', groupOf = byConsole;

  /* ---------- sorting: the menu and the list's headers set the same sort ---------- */
  // where a game files by title: without a leading article (The Sims under S), or where its sortAs says
  // (LEGO Rock Band with the Rock Band games). Games filed under the same name go in the order they came out
  // (or by their shelfDate)
  const fileAs = g => (g.sortAs || g.title).replace(/^(the|a|an)\s+/i, '');
  const byTitle = (a, b) => collator.compare(fileAs(a), fileAs(b))
    || ((a.sortAs || b.sortAs) && (a.shelfDate || a.released || '9999').localeCompare(b.shelfDate || b.released || '9999'))
    || collator.compare(a.title, b.title);
  const minutesPlayed = g => (g.steam?.playtime?.total || 0) + (g.psn?.playtime || 0) + (g.nintendo?.playtime || 0) + (g.xbox?.playtime || 0) + (g.ea?.playtime || 0);
  const SORT_BY = {
    title:     { label: 'Title',     value: g => g.title, cmp: byTitle },
    console:   { label: 'Console',   value: g => String(ORDER[g.console]).padStart(2, '0'), plain: true },
    released:  { label: 'Released',  value: g => g.released || '', plain: true },
    region:    { label: 'Region',    value: g => g.region },
    publisher: { label: 'Publisher', value: g => g.publisher || '' },
    genre:     { label: 'Genre',     value: g => g.genre || '' },
    // minutes played, all told, by what Steam, PlayStation Network, Nintendo, Xbox and the EA app say: unplayed or untracked go last
    played:    { label: 'Played',    value: g => { const m = minutesPlayed(g); return m ? String(m).padStart(8, '0') : ''; }, plain: true },
    // a collection's games, each in the order its copies came out
    // (a game can file under another date than its own, g.shelfDate: The Sims Deluxe Edition beside The Sims)
    group:     { label: 'Game',      value: g => String(groupOf(g).order).padStart(3, '0') + (g.shelfDate || g.released || '9999'), plain: true },
  };
  const MENU = {
    console: { key: GROUP, dir: 'asc', label: 'By console' },
    title: { key: 'title', dir: 'asc', label: 'Title A–Z' },
    newest: { key: 'released', dir: 'desc', label: 'Newest release first', short: 'Newest first' },
    oldest: { key: 'released', dir: 'asc', label: 'Oldest release first', short: 'Oldest first' },
    played: { key: 'played', dir: 'desc', label: 'Most played first', short: 'Most played' },
  };
  const validSort = s => s && SORT_BY[s.key] && (s.key !== 'group' || CFG) && (s.dir === 'asc' || s.dir === 'desc') ? { key: s.key, dir: s.dir } : { key: GROUP, dir: 'asc' };
  function sortList(list, { key, dir }) {
    const { value, plain } = SORT_BY[key];
    const sign = dir === 'desc' ? -1 : 1;
    const cmp = (x, y) => plain ? (x < y ? -1 : x > y ? 1 : 0) : collator.compare(x, y);
    const tie = (a, b) => ORDER[a.console] - ORDER[b.console] || byTitle(a, b);
    if (SORT_BY[key].cmp) return [...list].sort((a, b) => sign * SORT_BY[key].cmp(a, b) || ORDER[a.console] - ORDER[b.console]);
    return [...list].sort((a, b) => {
      const x = value(a), y = value(b);
      if (!x || !y) return (!x - !y) || tie(a, b); // nothing in the column goes last either way
      return sign * cmp(x, y) || tie(a, b);
    });
  }

  // the views Settings has turned on (window.SITE, from index.php), and the one a first visit opens on
  const SITE = window.SITE || {};
  const SITE_VIEWS = (SITE.views || ['shelf', 'rows', 'grid', 'showcase', 'list']).filter(v => v !== 'checklist');
  const DEFAULT_VIEW = SITE_VIEWS.includes(SITE.defaultView) ? SITE.defaultView : SITE_VIEWS[0] || 'shelf';
  let VIEWS = [...SITE_VIEWS];
  const flat = v => v === 'checklist'; // views with no cases to carry across
  const state = {
    view: [...VIEWS, 'checklist'].includes(store.get('view')) ? store.get('view') : DEFAULT_VIEW, // checked again once a collection is picked
    mode: 'mixed', // one cover per console faces out, the rest stand as spines
    sort: store.get('sort'), // checked once a collection is picked
    sel: new Set(), q: '', lib: '',
  };
  let ALL = [], games = [], DB = [];
  const byId = new Map(), LISTS = {}; // LISTS: each collection's games

  /* ---------- filtering ---------- */
  const fold = s => String(s || '').normalize('NFD').replace(/\p{M}/gu, '').toLowerCase();
  // where a console's digital copies come from, when it isn't a PC launcher: on their licence and in their details
  const ESHOP = { name: 'Nintendo eShop', bg: '#e60012', fg: '#ffffff', glyph: 'N' };
  const STORE = { switch: ESHOP, switch2: ESHOP };
  // short names people search by, added to a game's search text when its title has the long one
  const NICKNAMES = [[/grand theft auto/i, 'GTA']];
  const nicknames = g => NICKNAMES.filter(([re]) => re.test(g.title) || re.test(g.listedAs || '')).map(([, nick]) => nick);
  function base() {
    const words = fold(state.q).split(/\s+/).filter(Boolean);
    return ALL.filter(g => !g.bundle &&
      (!words.length || words.every(w => g._hay.includes(w))));
  }
  // how many games: a bundle is the packs in it (not a game of its own), and a copy on two consoles is one
  const tally = xs => xs.reduce((n, g) => n + (g.twinOf || g.bundle ? 0 : g.packs ? g.packs.length : 1), 0);
  // with only the PC picked, its games can be narrowed to the boxed ones or to one launcher's library
  const pcOnly = () => state.sel.size === 1 && state.sel.has('pc');
  // the EA app's are the copies its library lists (data/ea.json): bought in it, or a disc whose code was redeemed in it;
  // Rockstar's, the copies on its launcher and the ones it says were played on PC (data/rockstar.json)
  const inLib = (g, k) => k === 'disc' ? g.format !== 'digital' : k === 'ea' ? !!g.ea && g.ea.owned !== false : k === 'rockstar' ? g.launcher === k || !!g.rockstar : g.launcher === k;
  const list = () => sortList(base().filter(g =>
    (!state.sel.size || state.sel.has(g.console)) &&
    (!state.lib || !pcOnly() || inLib(g, state.lib))), state.sort);

  /* ---------- the header and controls ---------- */
  // the header: on the shelf only room above the consoles, its figures small in the top bar beside its name;
  // a collection's own header, with its figures in it
  function hero() {
    const figures = $('#figures');
    if (figures) {
      const consoles = CONSOLES.filter(c => !c.hidden && DB.some(g => g.console === c.id));
      const years = DB.map(g => g.released).filter(Boolean).map(r => +r.slice(0, 4));
      figures.innerHTML = `<span><b>${consoles.length}</b> Consoles</span><span><b>${tally(DB)}</b> Games</span>`;
      figures.hidden = !!CFG;
    }
    $('#hero').innerHTML = CFG ? CFG.hero(ALL) : `<header class="hero hero-figures"><h1 class="sr-only">${esc(SITE.pages?.shelf?.title || document.title)}</h1></header>`;
  }

  // the bar holds every console on the shelf; a collection hides those it has no games on (sync)
  function shell() {
    const consoles = CONSOLES.filter(c => !c.hidden && DB.some(g => g.console === c.id));
    $('#shell').innerHTML = `
      <div id="hero"></div>
      <div class="consoles-bar"><div class="consoles" role="toolbar" aria-label="Filter by console">
          <button class="chip all" data-all><span class="lab"><b>All</b><i data-n="all"></i></span></button>
          <div class="cscroll">${FAMILIES.filter(f => consoles.some(c => c.fam === f.id)).map(f => `<div class="fam" style="--fc:${f.color}">
            <button class="famname" data-fam="${f.id}">${f.name}</button>
            <div class="row">${consoles.filter(c => c.fam === f.id).map(c => `
              <button class="chip" data-c="${c.id}" title="${c.name}" aria-pressed="false">
                ${CONSOLE_ART[c.id] || ''}
                <span class="lab"><b>${c.short}</b><i data-n="${c.id}"></i></span>
              </button>`).join('')}</div></div>`).join('')}</div>
      </div></div>
      <div class="filters"><div class="filters-in">
          <div class="search" id="searchBox">
            <button type="button" class="search-open" aria-label="Search" aria-expanded="false" aria-controls="q">
              <svg viewBox="0 0 20 20" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="8.5" cy="8.5" r="5.5"/><path d="M13 13l4.5 4.5"/></svg>
            </button>
            <input id="q" type="search" placeholder="Search title, publisher, year…" aria-label="Search games" autocomplete="off">
            <button type="button" class="search-close" aria-label="Clear search">✕</button>
          </div>
          <select class="sort" id="sort" aria-label="Sort">
            ${Object.entries(MENU).map(([k, m]) => `<option value="${k}"${m.short ? ` data-short="${m.short}"` : ''}>${m.label}</option>`).join('')}
            <option value="custom" disabled hidden></option>
          </select>
          <div class="seg" id="view" role="group" aria-label="View">
            <button type="button" data-view="shelf">Shelf</button>
            <button type="button" data-view="rows">Rows</button>
            <button type="button" data-view="grid">Grid</button>
            <button type="button" data-view="showcase">Showcase</button>
            <button type="button" data-view="list">List</button>
            <button type="button" data-view="checklist">Checklist</button>
          </div>
      </div></div>`;
    // how many games are showing: on the line under the bar, at its right, beside the pills
    const count = document.createElement('span');
    count.className = 'count';
    count.id = 'count';
    ($('.tools') || $('#shell')).append(count);
    dropdown($('#sort'));
    searchBox();

    // a click picks just that console (or family); with shift, ctrl or cmd held it's added to or taken from what's picked
    const multi = e => e.shiftKey || e.metaKey || e.ctrlKey;
    $$('.chip[data-c]').forEach(b => b.addEventListener('click', e => {
      const id = b.dataset.c;
      if (multi(e)) state.sel.has(id) ? state.sel.delete(id) : state.sel.add(id);
      else state.sel = (state.sel.size === 1 && state.sel.has(id)) ? new Set() : new Set([id]);
      changed();
    }));
    $$('.famname').forEach(b => b.addEventListener('click', e => {
      const ids = $$('.chip[data-c]', b.parentElement).filter(c => !c.hidden).map(c => c.dataset.c);
      if (multi(e)) {
        const all = ids.every(i => state.sel.has(i));
        ids.forEach(i => all ? state.sel.delete(i) : state.sel.add(i));
      } else {
        const same = ids.length === state.sel.size && ids.every(i => state.sel.has(i));
        state.sel = same ? new Set() : new Set(ids);
      }
      changed();
    }));
    $('[data-all]').addEventListener('click', () => { state.sel = new Set(); changed(); });
    // the consoles scroll sideways beside "All", and fade out at an edge that has more past it
    const cs = $('.cscroll');
    const edges = () => {
      cs.classList.toggle('more-l', cs.scrollLeft > 2);
      cs.classList.toggle('more-r', cs.scrollLeft + cs.clientWidth < cs.scrollWidth - 2);
    };
    cs.addEventListener('scroll', edges, { passive: true });
    // with a mouse the consoles can be dragged sideways too; a drag doesn't pick the console it ends on
    let csDrag = null, csDragged = 0;
    cs.addEventListener('pointerdown', e => { if (e.pointerType === 'mouse' && e.button === 0) csDrag = { x: e.clientX, left: cs.scrollLeft, moved: false }; });
    addEventListener('pointermove', e => {
      if (!csDrag) return;
      const dx = e.clientX - csDrag.x;
      if (!csDrag.moved && Math.abs(dx) > 4) { csDrag.moved = true; cs.classList.add('dragging'); }
      if (csDrag.moved) cs.scrollLeft = csDrag.left - dx;
    });
    addEventListener('pointerup', () => {
      if (!csDrag) return;
      if (csDrag.moved) csDragged = performance.now();
      csDrag = null;
      cs.classList.remove('dragging');
    });
    cs.addEventListener('click', e => { if (performance.now() - csDragged < 100) { e.preventDefault(); e.stopImmediatePropagation(); } }, true);
    addEventListener('resize', edges);
    cs.edges = edges;
    edges();
    let t;
    $('#q').addEventListener('input', e => { clearTimeout(t); t = setTimeout(() => { state.q = e.target.value; changed(); }, 120); });
    $('#sort').addEventListener('change', e => { if (MENU[e.target.value]) setSort(MENU[e.target.value]); });
    $$('#view button').forEach(b => b.addEventListener('click', () => {
      if (state.view === b.dataset.view) return;
      // the shelf, the rows and the grid are the same cases laid out three ways, so they are carried across
      const before = Morph.capture();
      state.view = b.dataset.view;
      store.set('view', state.view);
      sync();
      render(!before);
      Morph.play(before);
    }));
    // the PC's libraries: its boxed copies, then each launcher, a logo on each (js/consoles.js)
    const libIcon = k => STORE_LOGOS[k] || `<i class="lib-glyph" style="--lb:${LAUNCHERS[k].bg};--lf:${LAUNCHERS[k].fg}">${LAUNCHERS[k].glyph}</i>`;
    $('#lib').innerHTML = `<button type="button" data-lib="">All</button>` +
      ['disc', ...Object.keys(LAUNCHERS).filter(k => k !== 'none')].map(k =>
        `<button type="button" data-lib="${k}">${libIcon(k)}${k === 'disc' ? 'Physical' : esc(LAUNCHERS[k].name)} <em data-ln="${k}"></em></button>`).join('');
    $$('#lib button').forEach(b => b.addEventListener('click', () => { state.lib = b.dataset.lib; changed(); }));
  }

  // scroll the consoles sideways to the first picked one, from its family's name if that fits too
  function showPicked() {
    const cs = $('.cscroll'), chip = cs && $('.chip.on[data-c]', cs);
    if (!chip) return;
    const box = cs.getBoundingClientRect(), c = chip.getBoundingClientRect(), f = chip.closest('.fam').getBoundingClientRect();
    if (c.left >= box.left && c.right <= box.right) return;
    const from = c.right - f.left <= box.width ? f.left : c.left;
    cs.scrollLeft += from - box.left - 2;
  }

  /* The search: a field in the bar; on a phone a magnifying glass that opens the field across the bar,
     as on the record site. .searching on the bar means it is open; .has-query on the box means something is typed. */
  function searchBox() {
    const box = $('#searchBox'), input = $('#q'), bar = box.closest('.filters'), opener = $('.search-open', box);
    const typed = () => box.classList.toggle('has-query', input.value.trim() !== '');
    const setOpen = on => { bar.classList.toggle('searching', on); opener.setAttribute('aria-expanded', String(on)); };
    const clear = () => {
      if (input.value !== '') { input.value = ''; input.dispatchEvent(new Event('input', { bubbles: true })); }
      typed();
    };
    opener.addEventListener('click', () => { setOpen(true); input.focus(); });
    $('.search-close', box).addEventListener('click', () => { clear(); input.blur(); setOpen(false); });
    input.addEventListener('input', typed);
    input.addEventListener('keydown', e => {
      if (e.key === 'Escape') { clear(); input.blur(); setOpen(false); }
      else if (e.key === 'Enter') input.blur(); // puts a phone's keyboard away, keeps the search
    });
    // left empty it folds back into a glass; with a search typed it stays open
    input.addEventListener('blur', () => { if (input.value.trim() === '') setOpen(false); });
  }

  function setSort(sort) {
    state.sort = validSort(sort);
    store.set('sort', state.sort);
    changed();
  }

  function sync() {
    const b = base();
    const counts = {};
    b.forEach(g => counts[g.console] = (counts[g.console] || 0) + (g.packs ? g.packs.length : 1));
    // a collection shows only the consoles it has games on, and its own view and words
    const has = new Set(ALL.map(g => g.console));
    $$('.chip[data-c]').forEach(c => { c.hidden = !has.has(c.dataset.c); });
    $$('.fam').forEach(f => { f.hidden = !$$('.chip[data-c]', f).some(c => !c.hidden); });
    $$('#view [data-view]').forEach(b => { b.hidden = !VIEWS.includes(b.dataset.view); });
    $('#sort option[value="console"]').textContent = MENU.console.label;
    $$('[data-n]').forEach(i => {
      const k = i.dataset.n, n = k === 'all' ? tally(b) : (counts[k] || 0);
      i.textContent = n + (n === 1 ? ' game' : ' games');
      if (k !== 'all') i.closest('.chip').classList.toggle('empty', !n);
    });
    $$('.chip[data-c]').forEach(c => { const on = state.sel.has(c.dataset.c); c.classList.toggle('on', on); c.setAttribute('aria-pressed', on); });
    $('[data-all]').classList.toggle('on', !state.sel.size);
    $$('.famname').forEach(f => {
      const ids = $$('.chip[data-c]', f.parentElement).filter(c => !c.hidden).map(c => c.dataset.c);
      f.classList.toggle('on', ids.every(i => state.sel.has(i)));
    });
    $$('#view button').forEach(x => x.classList.toggle('on', x.dataset.view === state.view));
    document.body.dataset.view = state.view;
    // a library with no PC games on this page isn't offered
    $('#libWrap').hidden = !pcOnly();
    const pcs = ALL.filter(g => g.console === 'pc');
    $$('#lib [data-ln]').forEach(i => {
      const n = pcs.filter(g => inLib(g, i.dataset.ln)).length;
      i.textContent = n;
      i.parentElement.hidden = !n;
    });
    $$('#lib button').forEach(x => x.classList.toggle('on', x.dataset.lib === state.lib));
    // the sort menu names the sort, or a line of its own for one set from a column
    const [choice] = Object.entries(MENU).find(([, m]) => m.key === state.sort.key && m.dir === state.sort.dir) || [];
    const custom = $('#sort option[value="custom"]');
    custom.hidden = !!choice;
    custom.textContent = `Sorted by ${SORT_BY[state.sort.key].label} ${state.sort.dir === 'asc' ? '↑' : '↓'}`;
    $('#sort').value = choice || 'custom';
    dropdownSync($('#sort'));
    $('#count').textContent = `${tally(games)} of ${tally(ALL)} games`;
  }

  function changed() {
    games = list();
    sync();
    render(true);
    if (!Detail.isOpen()) setPath(here(), true);
  }

  /* ---------- tooltip ---------- */
  const tipEl = document.createElement('div');
  tipEl.id = 'tip';
  document.body.append(tipEl);
  const host = $('#case');
  host.addEventListener('pointerover', e => {
    const item = e.target.closest('[data-id]');
    if (!item || e.pointerType === 'touch' || !host.contains(item) || state.view !== 'shelf') return;
    const g = byId.get(item.dataset.id);
    tipEl.innerHTML = `<b>${esc(g.title)}</b><span>${CONSOLE_BY_ID[g.console].short}${g.platinum ? ' Platinum' : ''} · ${Case.year(g)} · ${esc(REGIONS[g.region]?.name || g.region)}${g.format !== 'boxed' ? ' · ' + FORMATS[g.format] : ''}</span>`;
    tipEl.classList.add('vis');
  });
  host.addEventListener('pointerout', e => { if (!e.relatedTarget || !e.relatedTarget.closest || !e.relatedTarget.closest('[data-id]')) tipEl.classList.remove('vis'); });
  addEventListener('pointermove', e => {
    if (!tipEl.classList.contains('vis')) return;
    const x = Math.min(e.clientX + 14, innerWidth - tipEl.offsetWidth - 8);
    const y = e.clientY + 18 + tipEl.offsetHeight > innerHeight ? e.clientY - tipEl.offsetHeight - 12 : e.clientY + 18;
    tipEl.style.transform = `translate(${x}px,${y}px)`;
  });

  // a click anywhere on a game, on the shelf, in the grid or in the list, picks it up
  host.addEventListener('click', e => {
    const head = e.target.closest('.lsort');
    if (head) {
      const key = head.dataset.key;
      setSort({ key, dir: state.sort.key === key && state.sort.dir === 'asc' ? 'desc' : 'asc' });
      $(`.lsort[data-key="${key}"]`)?.focus({ preventScroll: true });
      return;
    }
    const item = e.target.closest('[data-id]');
    if (!item) return;
    // in the showcase a game to the side comes to the middle first, and a drag isn't a click
    if (state.view === 'showcase') {
      if (SC.dragged) return;
      const i = SC.items.findIndex(it => it.el === item);
      if (i >= 0 && i !== Math.round(SC.cur)) { scGo(i); return; }
    }
    Detail.open(byId.get(item.dataset.id), faceOf(item));
  });
  host.addEventListener('keydown', e => {
    if (e.key !== 'Enter' && e.key !== ' ') return;
    const item = e.target.closest('.lrow[data-id]');
    if (item) { e.preventDefault(); Detail.open(byId.get(item.dataset.id), faceOf(item)); }
  });
  // a case on a collection's checklist opens here too, in place
  document.addEventListener('click', e => {
    const item = state.view === 'checklist' && e.target.closest('#checklist [data-id]');
    if (!item || e.metaKey || e.ctrlKey || e.shiftKey || !byId.has(item.dataset.id)) return;
    e.preventDefault();
    Detail.open(byId.get(item.dataset.id), faceOf(item));
  });

  // the part of a shelf item, grid cell or list row that is the game itself
  const faceOf = item => item.querySelector('.lcase > *, .gt > *') || item.firstElementChild;
  // where a game stands on the page: a case turned on the shelf by the box it stands in, not as turned
  const rectOf = el => (footed(el) ? el.parentElement : el).getBoundingClientRect();
  // a case facing out on the shelf: turned, and seen from its foot (css/site.css, .item.face)
  const footed = el => !!el && el.classList.contains('bx') && !!el.parentElement && el.parentElement.matches('.item.face');
  // how far it is turned as it shows right now (less while the pointer is over it), in degrees
  const liveRy = el => { const m = new DOMMatrix(getComputedStyle(el).transform); return Math.atan2(-m.m13, m.m11) * 180 / Math.PI; };
  // how a case flying to or from the shelf is seen at scale s (its own size 1): from its foot, with the shelf's
  // perspective, so it leaves and lands looking just as it stands there; anywhere else from its middle with persp
  const footView = (el, s, persp) => footed(el)
    ? { perspective: (parseFloat(getComputedStyle(el.parentElement).perspective) / s).toFixed(1) + 'px', perspectiveOrigin: '50% 100%' }
    : { perspective: persp, perspectiveOrigin: '50% 50%' };
  // how far a game leaning on the shelf is tipped over, in degrees (0 standing straight): as it is drawn
  // right now (live, so a lean eased back on hover counts), or where it comes to rest
  const tiltOf = (el, live = true) => {
    const item = el && el.closest('.item.lean');
    if (!item) return 0;
    if (!live) return -parseFloat(item.style.getPropertyValue('--lean')) || 0;
    const m = new DOMMatrix(getComputedStyle(item).transform);
    return Math.atan2(m.b, m.a) * 180 / Math.PI;
  };

  function render(animate) {
    tipEl.classList.remove('vis');
    Morph.stop();
    host.classList.toggle('bookcase', state.view === 'shelf');
    host.classList.toggle('gridview', state.view === 'grid');
    host.classList.toggle('listview', state.view === 'list');
    host.classList.toggle('rowsview', state.view === 'rows');
    host.classList.toggle('showcaseview', state.view === 'showcase');
    cancelAnimationFrame(SC.raf);
    SC.raf = 0;
    if (state.view === 'checklist') { CFG.checklist($('#checklist'), ALL); Detail.rehome(); return; }
    if (!games.length) {
      host.innerHTML = `<div class="empty-msg"><b>Nothing on this shelf</b>No games match those filters. Try another console or clear the search.</div>`;
      return;
    }
    if (state.view === 'list') renderList(animate); else if (state.view === 'grid') renderGrid(animate); else if (state.view === 'rows') renderRows(animate); else if (state.view === 'showcase') renderShowcase(animate); else renderShelf(animate);
    Detail.rehome();
  }

  /* ---------- the bookcase ---------- */
  const scale = () => innerWidth < 640 ? 1 : innerWidth < 1000 ? 1.25 : 1.5;
  // spines on the shelf are drawn wider than real ones, so their titles can be read
  const spineK = () => innerWidth < 640 ? 1.55 : innerWidth < 1000 ? 1.45 : 1.45;
  // a loose cartridge beside the cases: a big one (the SNES) at its real size, a small card larger so it can be seen
  const cartK = g => Case.cartSize(g, 1)[0] > 100 ? 1 : 1.6;
  // a loose cartridge stands facing out, or, on a console whose cartridges stand sideways (the SNES), on its
  // side with its top label out like a book's spine, all but the one that faces out ("All covers" faces them all out).
  // One with nothing on its top edge (a PAL cartridge, an unofficial one) faces out, so it can be told from the rest,
  // unless it is marked faceOut false (it is then given a title on its top, js/cases.js)
  // how far a case facing out on the shelf is turned to show its spine, in degrees
  const FACE_RY = 16;
  const looseKind = (g, lead) => CONSOLE_BY_ID[g.console].stack && Case.showsTop(g) && state.mode !== 'covers' && !lead.has(g.id) ? 'stack' : 'loose';
  function shelfItem(g, kind, animate, n) {
    const btn = document.createElement('button');
    btn.className = `item ${kind}` + (animate ? ' enter' : '');
    btn.dataset.id = g.id;
    btn.style.animationDelay = Math.min(n * 6, 900) + 'ms';
    btn.setAttribute('aria-label', `${g.title}, ${CONSOLE_BY_ID[g.console].name}, ${Case.year(g)}`);
    return btn;
  }

  // In "one cover per console" (or game), one per console faces out: a favourite (or one marked to) if any, else the newest with a cover.
  function leaders() {
    const out = new Set(), by = {};
    games.forEach(g => (by[groupOf(g).key] = by[groupOf(g).key] || []).push(g));
    for (const gs of Object.values(by)) {
      // a console whose games are all loose cartridges (the SNES) faces one of those out
      let pool = gs.filter(g => g.format === 'boxed');
      if (!pool.length) pool = gs.filter(g => g.format === 'cartridge-only' && CONSOLE_BY_ID[g.console].stack);
      // faceOut, where a game has it, says whether it faces out over its being a favourite
      const favs = pool.filter(g => g.faceOut ?? g.fav);
      if (favs.length) { favs.forEach(g => out.add(g.id)); continue; }
      const pick = [...pool].sort((a, b) => (!!b.cover - !!a.cover) || (b.released || '').localeCompare(a.released || ''))[0];
      if (pick) out.add(pick.id);
    }
    return out;
  }

  // a number from a game's id, the same every time, so the shelf is messy the same way on every visit
  const hash = str => { let h = 2166136261; for (const ch of str) h = Math.imul(h ^ ch.charCodeAt(0), 16777619); return h >>> 0; };

  /* The last one or two games on a shelf (on about two shelves in three, which keep room for it, and on any
     with room to spare), or before a bookend where the shelf has room, lean over onto the one before, as on a real shelf. A leaning game tips over on its bottom left corner until its top
     rests against its neighbour: on its side, or on its top corner if the leaning one is the taller. A second
     one leans the same way on the first. Returns each one that leans, with its angle and how far it stands
     out from where it would stand upright. */
  function leanOf(row, room, atEnd) {
    const out = [];
    // where a run of games ends: the end of the shelf first, then each bookend
    const stops = [...(atEnd ? [row.length] : []), ...row.map((it, i) => it.end ? i : -1).filter(i => i > 0)];
    for (const stop of stops) {
      const tail = [];
      for (let i = stop - 1; i >= 0 && (row[i].kind === 'spine' || row[i].kind === 'stack'); i--) tail.unshift(row[i]);
      if (!tail.length) continue;
      const h = hash(tail[tail.length - 1].g.id);
      if (stop !== row.length && h % 2) continue;
      for (let n = Math.min(tail.length, h % 3 === 0 ? 2 : 1); n >= 1; n--) {
        const lean = tail.slice(-n), nb = row[stop - n - 1];
        if (!nb || nb.end) continue;
        let done = false;
        for (let deg = 9 + (h >>> 3) % 7; deg >= 6 && !done; deg -= 2) {
          const t = deg * Math.PI / 180, first = lean[0];
          const off = first.bh * Math.cos(t) <= nb.bh ? first.bh * Math.sin(t) : nb.bh * Math.tan(t);
          // each after the first stands against the one before it, at the same angle
          const rest = lean.slice(0, -1).map(it => it.bw / Math.cos(t) - it.bw);
          const need = off + rest.reduce((x, y) => x + y, 0) + 4;
          if (need > room) continue;
          room -= need;
          lean.forEach((it, i) => out.push({ it, deg, off: i ? rest[i - 1] : off }));
          done = true;
        }
        if (done) break;
      }
    }
    return out;
  }

  function renderShelf(animate) {
    host.innerHTML = '';
    const s = scale(), sk = spineK();
    const inner = host.clientWidth - (innerWidth < 820 ? 20 + 12 : 44 + 28);
    const lead = state.mode === 'mixed' ? leaders() : new Set();
    const kindOf = g => g.format === 'cartridge-only' ? looseKind(g, lead) : g.format === 'digital' ? 'spine' : (state.mode === 'covers' || lead.has(g.id)) ? 'face' : 'spine';
    // a thin spine is drawn wider, up to as wide as a widened DVD case's; a thick one (a cardboard box) stays as it is
    const widen = d => Math.max(d, Math.min(d * sk, 15 * s * sk));
    // and so is a SNES cartridge standing on its side
    const thick = g => widen(Case.topSize(g, s * cartK(g), true)[0]);
    // how big it stands (bw × bh) and the room it takes on the shelf (w)
    function sizeOf(g, kind) {
      if (kind === 'loose') { const [cw, ch] = Case.cartSize(g, s * cartK(g)); return { bw: cw, bh: ch + 12, w: cw + 16 }; }
      if (kind === 'stack') { const [tw, th] = Case.topSize(g, s * cartK(g), true, thick(g)); return { bw: tw, bh: th, w: tw + 2 }; }
      const L = Case.layout(g, s);
      return kind === 'face' ? { bw: L.w, bh: L.h, w: L.w + 12 } : { bw: widen(L.d), bh: L.h, w: widen(L.d) };
    }

    // left to right, a new shelf when one is full (a shelf whose last games will lean keeps room for them)
    const reserve = 190 * s * 0.2;
    const shelfOf = g => ({ items: [], x: 0, lean: !g || hash(g.id) % 3 !== 0 });
    const rows = [shelfOf(games[0])];
    let last = null;
    const grouped = state.sort.key === GROUP;
    games.forEach(g => {
      const kind = kindOf(g), size = sizeOf(g, kind);
      const newGroup = grouped && groupOf(g).key !== last;
      const w = size.w + 2 + (newGroup && last ? 30 : 0);
      let row = rows[rows.length - 1];
      if (row.x + w > inner - (row.lean ? reserve : 0) && row.items.length) { row = shelfOf(g); rows.push(row); }
      if (newGroup && last && row.items.length) row.items.push({ end: true });
      row.items.push({ g, kind, ...size, label: grouped && (newGroup || !row.items.some(r => r.g)) });
      row.x += w;
      last = groupOf(g).key;
    });

    let n = 0;
    const labels = [];
    rows.forEach(({ items, x, lean }) => {
      const shelf = document.createElement('div');
      const bay = document.createElement('div');
      bay.className = 'bay';
      // each shelf only a little taller than the tallest game on it
      bay.style.height = (Math.max(...items.filter(it => it.g).map(it => it.bh)) + 26) + 'px';
      const board = document.createElement('div');
      board.className = 'board';
      const plates = document.createElement('div');
      plates.className = 'plates';
      board.append(plates);
      const leans = new Map(leanOf(items, inner - x, lean || inner - x > reserve).map(l => [l.it, l]));
      items.forEach(it => {
        if (it.end) { const b = document.createElement('div'); b.className = 'bookend'; bay.append(b); return; }
        const { g, kind } = it;
        const btn = shelfItem(g, kind, animate, n++);
        // one facing out is a solid case, turned a little so its spine and depth show (css/site.css)
        if (kind === 'face') { btn.dataset.ry = FACE_RY; btn.style.setProperty('--ry', FACE_RY); }
        btn.append(kind === 'face' ? Case.slab(g, s, true) : kind === 'loose' ? Case.cart(g, s * cartK(g)) : kind === 'stack' ? Case.top(g, s * cartK(g), true, thick(g)) : Case.spine(g, s));
        if (kind === 'spine') {
          const sp = btn.firstElementChild;
          sp.style.setProperty('--d', it.bw.toFixed(1) + 'px');
        }
        const l = leans.get(it);
        if (l) {
          btn.classList.add('lean');
          btn.style.setProperty('--lean', l.deg);
          btn.style.marginLeft = (l.off + 2).toFixed(1) + 'px';
        }
        bay.append(btn);
        if (it.label) labels.push([btn, g, plates]);
      });
      shelf.append(bay, board);
      host.append(shelf);
    });

    // a brass label under where each console starts, nudged so none overlap
    let lastPlates = null, edge = 0;
    labels.forEach(([btn, g, plates]) => {
      if (plates !== lastPlates) { edge = -Infinity; lastPlates = plates; }
      const p = document.createElement('div');
      p.className = 'plate';
      // in the colour of the console's family, as in the filter: red Nintendo, blue PlayStation, green Xbox
      const fam = CONSOLE_BY_ID[groupOf(g).key] && FAMILIES.find(f => f.id === CONSOLE_BY_ID[groupOf(g).key].fam);
      if (fam) p.style.setProperty('--pc', fam.color);
      p.textContent = groupOf(g).label;
      p.title = groupOf(g).name;
      plates.append(p);
      const left = Math.max(btn.offsetLeft - 14, edge + 4);
      p.style.left = left + 'px';
      edge = left + p.offsetWidth;
    });
  }

  /* ---------- the list ---------- */
  const COLS = ['title', 'console', 'released', 'region', 'publisher', 'genre'];
  function renderList(animate) {
    const wrap = document.createElement('div');
    wrap.className = 'list' + (animate ? ' enter' : '');
    const head = document.createElement('div');
    head.className = 'lrow lhead';
    head.innerHTML = '<span></span>' + COLS.map(key => {
      const on = state.sort.key === key;
      return `<span><button type="button" class="lsort${on ? ` on ${state.sort.dir}` : ''}" data-key="${key}" aria-label="Sort by ${SORT_BY[key].label}${on ? `, sorted ${state.sort.dir === 'asc' ? 'ascending' : 'descending'}` : ''}">${SORT_BY[key].label}<i aria-hidden="true"></i></button></span>`;
    }).join('');
    wrap.append(head);

    games.forEach((g, i) => {
      const c = CONSOLE_BY_ID[g.console];
      const row = document.createElement('div');
      row.className = 'lrow';
      row.dataset.id = g.id;
      row.style.setProperty('--i', Math.min(i, 24));
      row.tabIndex = 0;
      row.setAttribute('role', 'button');
      row.setAttribute('aria-label', `${g.title}, ${c.name}. Open details`);
      const thumb = document.createElement('span');
      thumb.className = 'lcase';
      if (g.format === 'cartridge-only') {
        const [cw, ch] = Case.cartSize(g, 1);
        thumb.append(Case.cart(g, Math.min(44 / ch, 46 / cw)));
      } else {
        const L = Case.layout(g, 1);
        const face = Case.front(g, Math.min(48 / L.h, 58 / L.w));
        if (g.format === 'digital') face.classList.add('ghosted');
        thumb.append(face);
      }
      row.append(thumb);
      row.insertAdjacentHTML('beforeend', `
        <span class="ltitle"><b title="${esc(g.title)}">${esc(g.title)}</b><small>${c.short}${g.platinum ? ' Platinum' : ''} · ${Case.year(g)}${g.format !== 'boxed' ? ` · ${FORMATS[g.format]}` : ''}</small>${g.format !== 'boxed' ? `<i class="kc">${FORMATS[g.format]}</i>` : ''}${g.platinum ? '<i class="kc plat-tag">Platinum</i>' : ''}</span>
        <span class="lcon"><i class="kc" style="--fc:${FAMILIES.find(f => f.id === c.fam).color}">${c.short}</i></span>
        <span class="lyear" title="${esc(Case.date(g.released, true))}">${Case.year(g)}</span>
        <span class="lregion" title="${esc(REGIONS[g.region]?.long || g.region)}">${esc(g.region)}</span>
        <span class="lpub" title="${esc(g.publisher || '')}">${esc(g.publisher || '—')}</span>
        <span class="lgenre" title="${esc(g.genre || '')}">${esc(g.genre || '—')}</span>`);
      wrap.append(row);
    });
    host.replaceChildren(wrap);
  }

  // its front as it stands face out: a box built from its parts (g.model) is that model, not a picture of its front
  const faceArt = (g, k) => g.model ? Case.slab(g, k, true) : Case.front(g, k);

  /* ---------- a game standing face out with its name under it: in the grid and in the rows ----------
     It stands at scale s, no wider than room and no taller than the stage. */
  function cellOf(g, s, room, stage, animate, delay) {
    const c = CONSOLE_BY_ID[g.console];
    const cell = document.createElement('button');
    cell.type = 'button';
    cell.className = 'cell' + (animate ? ' enter' : '');
    cell.dataset.id = g.id;
    cell.style.setProperty('--d', delay + 'ms');
    cell.setAttribute('aria-label', `${g.title}, ${c.name}, ${Case.year(g)}`);
    const tile = document.createElement('span');
    tile.className = 'gt';
    let w;
    if (g.format === 'cartridge-only') {
      const [w1, h1] = Case.cartSize(g, 1);
      const k = Math.min(s * cartK(g) * 1.25, room / w1, (stage * 0.8) / h1);
      tile.append(Case.cart(g, k));
      w = w1 * k;
    } else {
      const L = Case.layout(g, 1);
      const k = Math.min(s, room / L.w, stage / L.h);
      const face = faceArt(g, k);
      if (g.format === 'digital') face.classList.add('ghosted');
      tile.append(face);
      w = L.w * k;
    }
    cell.style.setProperty('--tw', w.toFixed(1) + 'px');
    const st = document.createElement('span');
    st.className = 'stage';
    st.append(tile);
    cell.append(st);
    cell.insertAdjacentHTML('beforeend', `<span class="cap"><b>${esc(g.title)}</b><small>${c.short}${g.platinum ? ' Platinum' : ''} · ${Case.year(g)} · ${esc(g.region)}${g.format !== 'boxed' ? ` · ${FORMATS[g.format]}` : ''}</small></span>`);
    return cell;
  }
  const groupHead = (gr, n) => `<div class="ghead">${gr.art ? `<span class="spot-art">${gr.art}</span>` : ''}<b>${esc(gr.name)}</b><i>${n} ${n === 1 ? 'copy' : CFG ? 'copies' : 'games'}</i></div>`;

  /* ---------- the grid: every game standing face out on one baseline ---------- */
  // A DVD case stands the full height of the stage and the rest keep their real size beside it,
  // so a DS case is shorter and a Switch case narrower. Loose cards stand twice their size, a SNES cartridge a little over its own.
  const gridSize = () => innerWidth < 640 ? { cell: 100, stage: 136, gap: 12 } : innerWidth < 1000 ? { cell: 132, stage: 172, gap: 20 } : { cell: 148, stage: 196, gap: 26 };
  function renderGrid(animate) {
    const G = gridSize();
    const width = host.clientWidth;
    const cols = Math.max(1, Math.floor((width + G.gap) / (G.cell + G.gap)));
    const room = (width - G.gap * (cols - 1)) / cols - 6;
    const s = G.stage / 190;
    const wrap = document.createElement('div');
    wrap.className = 'grid';
    wrap.style.cssText = `--cell:${G.cell}px;--stage:${G.stage}px;--gap:${G.gap}px`;
    const grouped = state.sort.key === GROUP;
    const counts = {};
    games.forEach(g => counts[groupOf(g).key] = (counts[groupOf(g).key] || 0) + 1);
    let last = null, n = 0;
    games.forEach(g => {
      const gr = groupOf(g);
      if (grouped && gr.key !== last) {
        wrap.insertAdjacentHTML('beforeend', groupHead(gr, counts[gr.key]));
      }
      last = gr.key;
      wrap.append(cellOf(g, s, room, G.stage, animate, Math.min(n++ * 12, 700)));
    });
    host.replaceChildren(wrap);
  }

  /* ---------- the rows: each console (or game) on a row of its own that scrolls sideways ----------
     The cases stand as on the shelf, their spines out (with "Show" as on the shelf), but taller,
     and each spine much wider than a real one, so it is easy to tap on a phone. A swipe moves
     along the row. Sorted any way but by console, the games stand on one long row in that order. */
  const rowSize = () => innerWidth < 640 ? { stage: 230, spine: 46 } : innerWidth < 1000 ? { stage: 250, spine: 44 } : { stage: 270, spine: 42 };
  function renderRows(animate) {
    const R = rowSize();
    const s = R.stage / 190;
    const lead = state.mode === 'mixed' ? leaders() : new Set();
    const kindOf = g => g.format === 'cartridge-only' ? looseKind(g, lead) : g.format === 'digital' ? 'spine' : (state.mode === 'covers' || lead.has(g.id)) ? 'face' : 'spine';
    const wrap = document.createElement('div');
    wrap.className = 'rows';
    const grouped = state.sort.key === GROUP;
    const runs = [];
    games.forEach(g => {
      const gr = grouped ? groupOf(g) : null, last = runs[runs.length - 1];
      if (last && (!grouped || last.gr.key === gr.key)) last.games.push(g);
      else runs.push({ gr, games: [g] });
    });
    let n = 0;
    runs.forEach(({ gr, games: gs }) => {
      const sec = document.createElement('section');
      sec.className = 'rgrp';
      if (gr) sec.innerHTML = groupHead(gr, gs.length);
      const track = document.createElement('div');
      track.className = 'rtrack';
      gs.forEach((g, i) => {
        const kind = kindOf(g);
        const btn = document.createElement('button');
        btn.type = 'button';
        // only the first cases on each row rise in; the rest are off to the side
        btn.className = `item ${kind}` + (animate && i < 14 ? ' enter' : '');
        btn.dataset.id = g.id;
        btn.style.animationDelay = Math.min(n++ * 8, 500) + 'ms';
        btn.setAttribute('aria-label', `${g.title}, ${CONSOLE_BY_ID[g.console].name}, ${Case.year(g)}`);
        const tile = document.createElement('span');
        tile.className = 'gt';
        if (kind === 'loose') tile.append(Case.cart(g, s * cartK(g)));
        else if (kind === 'stack') tile.append(Case.top(g, s * cartK(g), true, R.spine * 1.2));
        else if (kind === 'face') tile.append(faceArt(g, Math.min(s, (R.stage * 1.1) / Case.layout(g, 1).w)));
        else {
          const sp = Case.spine(g, s);
          sp.style.setProperty('--d', R.spine + 'px');
          tile.append(sp);
        }
        btn.append(tile);
        track.append(btn);
      });
      // the row and its arrows together, so the arrows stand as tall as the row's cases
      const bar = document.createElement('div');
      bar.className = 'rbar';
      bar.append(track);
      sec.append(bar);
      // with a mouse, an arrow either end moves along the row by most of its width
      const arrows = [-1, 1].map(dir => {
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'rnav ' + (dir < 0 ? 'prev' : 'next');
        b.tabIndex = -1;
        b.setAttribute('aria-label', dir < 0 ? 'Scroll the row left' : 'Scroll the row right');
        // a drawn chevron: the font's ‹ › sit off the middle of the circle
        b.innerHTML = `<span><svg viewBox="0 0 12 12" width="12" height="12" aria-hidden="true"><path d="${dir < 0 ? 'M7.5 2.5 4 6l3.5 3.5' : 'M4.5 2.5 8 6 4.5 9.5'}" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>`;
        b.addEventListener('click', () => track.scrollBy({ left: dir * track.clientWidth * 0.8, behavior: reduced() ? 'auto' : 'smooth' }));
        bar.append(b);
        return b;
      });
      const ends = () => {
        arrows[0].classList.toggle('more', track.scrollLeft > 4);
        arrows[1].classList.toggle('more', track.scrollLeft + track.clientWidth < track.scrollWidth - 4);
        track.classList.toggle('more-l', track.scrollLeft > 4);
        track.classList.toggle('more-r', track.scrollLeft + track.clientWidth < track.scrollWidth - 4);
      };
      track.addEventListener('scroll', ends, { passive: true });
      requestAnimationFrame(ends);
      wrap.append(sec);
    });
    host.replaceChildren(wrap);
  }

  /* ---------- the showcase: one game at a time on a glossy stage, the rest fanned out either side ----------
     As in prototypes/3-showcase.html. A drag, a sideways scroll, the arrows or the arrow keys move along, and
     the strip under it jumps through the whole collection, a block per console (or game). The game in the
     middle is picked up like any other; one to the side comes to the middle first. */
  const SC = { items: [], cur: 0, target: 0, raf: 0, shown: -1, id: null, drag: null, dragged: false, wheel: 0 };
  const scSize = () => innerWidth < 640 ? 1.25 : innerWidth < 1000 ? 1.6 : 1.9;
  function renderShowcase(animate) {
    const wrap = document.createElement('div');
    wrap.className = 'showcase' + (animate ? ' enter' : '');
    wrap.innerHTML = `<div class="sc-stage"><div class="sc-floor"></div>
        <button type="button" class="sc-arrow l" data-go="-1" aria-label="Previous game">←</button>
        <button type="button" class="sc-arrow r" data-go="1" aria-label="Next game">→</button></div>
      <div class="sc-cap"></div>
      <div class="sc-scrub"><div class="sc-track"><i class="sc-thumb"></i></div>
        <small><span>Drag, scroll sideways or use ← → · click the middle one to pick it up</span><span class="sc-pos"></span></small></div>`;
    host.replaceChildren(wrap);
    const stage = $('.sc-stage', wrap), track = $('.sc-track', wrap);
    const maxH = stage.clientHeight * 0.66;
    Object.assign(SC, { wrap, stage, track, thumb: $('.sc-thumb', wrap), cap: $('.sc-cap', wrap), shown: -1 });
    SC.items = games.map(g => {
      const el = document.createElement('button');
      el.type = 'button';
      el.className = 'sc-it';
      el.dataset.id = g.id;
      el.tabIndex = -1;
      el.setAttribute('aria-label', `${g.title}, ${CONSOLE_BY_ID[g.console].name}, ${Case.year(g)}`);
      const tile = document.createElement('span');
      tile.className = 'gt';
      let w;
      if (g.format === 'cartridge-only') {
        const [w1, h1] = Case.cartSize(g, 1);
        const k = Math.min(scSize() * cartK(g), (maxH * 0.6) / h1, (innerWidth * 0.4) / w1);
        tile.append(Case.cart(g, k));
        w = w1 * k;
      } else {
        // a solid case, front, spine and edges, so turned to the side it shows its spine and depth;
        // a digital copy, which has no case, stays a flat cover in a dashed outline
        const L = Case.layout(g, 1);
        const k = Math.min(scSize(), maxH / L.h, (innerWidth * 0.5) / L.w);
        let face;
        if (g.format === 'digital') { face = Case.front(g, k); face.classList.add('ghosted'); }
        else face = Case.slab(g, k, true);
        tile.append(face);
        // its reflection in the floor: a mirrored cover (a 3D case can't take the browser's own reflection)
        const refl = document.createElement('span');
        refl.className = 'sc-refl';
        refl.style.setProperty('--sd', (L.d * k).toFixed(1) + 'px');
        const mirror = Case.front(g, k);
        refl.append(mirror);
        tile.append(refl);
        w = L.w * k;
      }
      el.style.marginLeft = (-w / 2).toFixed(1) + 'px';
      el.append(tile);
      stage.append(el);
      return { el, g };
    });
    // the strip: a block for each console (or game) when sorted that way, else one
    const grouped = state.sort.key === GROUP;
    const blocks = [];
    games.forEach((g, i) => {
      const gr = groupOf(g), last = blocks[blocks.length - 1];
      if (grouped && last && last.gr.key === gr.key) last.n++; else if (grouped || !last) blocks.push({ gr, n: 1, start: i });
      else last.n++;
    });
    blocks.forEach(b => {
      const el = document.createElement('div');
      el.className = 'sc-seg';
      el.style.flex = b.n;
      const fam = grouped && CONSOLE_BY_ID[b.gr.key] && FAMILIES.find(f => f.id === CONSOLE_BY_ID[b.gr.key].fam);
      el.style.setProperty('--fc', fam ? fam.color : 'var(--gold)');
      if (grouped) { el.dataset.key = b.gr.key; el.title = b.gr.name; el.textContent = b.n / games.length > 0.035 ? b.gr.label : ''; }
      track.insertBefore(el, SC.thumb);
    });
    const at = SC.id ? games.findIndex(g => g.id === SC.id) : -1;
    SC.cur = SC.target = Math.max(0, at);
    scLayout();

    // moving along: a drag, a sideways scroll (a scroll down still goes down the page), the arrows, the strip
    stage.addEventListener('pointerdown', e => {
      if (e.target.closest('.sc-arrow')) return;
      SC.drag = { x: e.clientX, c: SC.cur };
      SC.dragged = false;
    });
    stage.addEventListener('wheel', e => {
      if (Math.abs(e.deltaX) <= Math.abs(e.deltaY)) return;
      e.preventDefault();
      SC.wheel += e.deltaX;
      if (Math.abs(SC.wheel) > 40) { scGo(Math.round(SC.target) + Math.sign(SC.wheel)); SC.wheel = 0; }
    }, { passive: false });
    wrap.addEventListener('click', e => {
      const b = e.target.closest('[data-go]');
      if (b) scGo(Math.round(SC.target) + +b.dataset.go);
      if (e.target.closest('.sc-go')) { const it = SC.items[Math.round(SC.cur)]; if (it) Detail.open(it.g, faceOf(it.el)); }
    });
    let scrubbing = false;
    const scrubTo = e => { const r = track.getBoundingClientRect(); scGo(Math.floor((e.clientX - r.left) / r.width * games.length)); };
    track.addEventListener('pointerdown', e => { scrubbing = true; track.setPointerCapture(e.pointerId); scrubTo(e); });
    track.addEventListener('pointermove', e => { if (scrubbing) scrubTo(e); });
    track.addEventListener('pointerup', () => { scrubbing = false; });
  }
  addEventListener('pointermove', e => {
    if (!SC.drag) return;
    const dx = e.clientX - SC.drag.x;
    if (Math.abs(dx) > 5) SC.dragged = true;
    SC.target = SC.cur = clamp(SC.drag.c - dx / 90, 0, Math.max(0, games.length - 1));
    scLayout();
  });
  addEventListener('pointerup', () => {
    if (!SC.drag) return;
    SC.drag = null;
    scGo(Math.round(SC.cur));
    setTimeout(() => { SC.dragged = false; }, 0);
  });
  document.addEventListener('keydown', e => {
    if (state.view !== 'showcase' || Detail.isOpen() || e.target.closest?.('input, select, textarea, .dd')) return;
    if (e.key === 'ArrowRight') { e.preventDefault(); scGo(Math.round(SC.target) + 1); }
    if (e.key === 'ArrowLeft') { e.preventDefault(); scGo(Math.round(SC.target) - 1); }
    if (e.key === 'Enter' && e.target === document.body) { const it = SC.items[Math.round(SC.cur)]; if (it) Detail.open(it.g, faceOf(it.el)); }
  });

  // each game stands where it is from the middle: the middle one facing out, the rest fanned out and turned in
  function scLayout() {
    const sp = Math.min(innerWidth * 0.085, 110), gap = Math.min(innerWidth * 0.2, 230);
    SC.items.forEach((it, i) => {
      const o = i - SC.cur, a = Math.abs(o);
      if (a > 10) { it.el.style.display = 'none'; return; }
      it.el.style.display = '';
      const sg = Math.sign(o), k = Math.min(a, 1);
      // the one in the middle rests a little turned, so its spine shows; easing to none as it moves aside
      const ry = -sg * k * 58 - (1 - k) * 14;
      it.el.style.transform = scPose(o * sp + sg * k * gap, 0, -k * 220 - a * 18, ry, 1);
      it.el.style.zIndex = 100 - Math.round(a * 4);
      // further from the middle, darker: an overlay's opacity rather than a filter, so a step repaints nothing
      it.el.style.setProperty('--dim', (Math.min(a, 6) * 0.1).toFixed(3));
      it.el.style.setProperty('--k', Math.round(a));
      it.el.dataset.ry = ry.toFixed(1); // how it is turned, for a flight from it (js: Detail, Morph)
      it.el.classList.toggle('center', a < 0.5);
    });
    const c = Math.round(SC.cur);
    if (c !== SC.shown && games[c]) scCaption(c);
    SC.thumb.style.left = games.length ? ((c + 0.5) / games.length * 100) + '%' : '0';
  }
  // the same list of functions every time, so a transition between two poses goes smoothly through each
  const scPose = (x, y, z, ry, k) => `translate3d(${x.toFixed(1)}px,${y.toFixed(1)}px,${z.toFixed(1)}px) rotateY(${ry.toFixed(1)}deg) scale(${k.toFixed(3)})`;

  /* Coming from the shelf, the rows or the grid: each game on screen there travels from where it stood to its
     place on the stage, turning from its spine (or as it stood) to face out on the way, in one arc. It is the
     stage's own case that moves, so nothing is swapped when it lands. The rest come in quietly. */
  function scEnter(snap) {
    const sr = SC.stage.getBoundingClientRect();
    const moving = SC.items.filter(it => it.el.style.display !== 'none');
    moving.forEach(it => {
      const end = it.el.style.transform, from = snap.spots.get(it.g.id);
      const a = Math.abs(SC.items.indexOf(it) - SC.cur);
      if (!from) {
        it.el.animate([{ opacity: 0 }, { opacity: 1 }], { duration: 500, delay: 350 + a * 30, easing: 'ease-out', fill: 'backwards' });
        return;
      }
      // where it stands untransformed: its bottom middle, the point it turns and scales about
      const bx = sr.left + it.el.offsetLeft + it.el.offsetWidth / 2, by = sr.top + it.el.offsetTop + it.el.offsetHeight;
      const h = it.el.offsetHeight;
      const turned = from.kind === 'spine' || from.kind === 'side';
      const fh = from.kind === 'side' ? from.h * (it.el.offsetHeight / it.el.offsetWidth) : from.h; // a cartridge on its side is as tall as the cartridge is wide
      const k0 = Math.max(0.05, fh / h), dx = from.cx - bx, dy = from.cy + fh / 2 - by;
      const m = new DOMMatrix(getComputedStyle(it.el).transform);
      const x1 = m.m41, z1 = m.m43, ry1 = +it.el.dataset.ry;
      const lift = 40 + Math.min(120, Math.hypot(dx - x1, dy) * 0.12);
      const duration = 820 + Math.min(300, Math.hypot(dx - x1, dy) * 0.25);
      it.el.animate([
        { transform: scPose(dx, dy, 0, turned ? 90 : from.ry || 0, k0) },
        { transform: scPose((dx + x1) / 2, dy / 2 - lift, z1 / 2, turned ? 45 + ry1 / 2 : ry1 / 2, (k0 + 1) / 2), offset: 0.5 },
        { transform: end },
      ], { duration, delay: Math.min(a, 10) * 28, easing: 'cubic-bezier(.35,0,.25,1)', fill: 'backwards' });
    });
  }

  function scCaption(i) {
    SC.shown = i;
    const g = games[i], c = CONSOLE_BY_ID[g.console];
    SC.id = g.id;
    SC.wrap.style.setProperty('--tint', (g.colors && g.colors[1]) || '#2a2a30');
    SC.cap.innerHTML = `<div class="sc-fade">
      <p class="sc-eyebrow"><span class="spot-art">${CONSOLE_ART[g.console] || ''}</span>${esc(c.name)} · ${esc(REGIONS[g.region]?.name || g.region)}</p>
      <h2>${esc(g.title)}</h2>
      <p class="sc-meta">${[Case.year(g), g.publisher, g.format !== 'boxed' && FORMATS[g.format], g.platinum && 'Platinum'].filter(Boolean).map(esc).join(' · ')}</p>
      <button type="button" class="sc-go">Take a closer look</button></div>`;
    $$('.sc-seg', SC.track).forEach(s => s.classList.toggle('on', s.dataset.key === groupOf(g).key));
    $('.sc-pos', SC.wrap).textContent = `${i + 1} / ${games.length}`;
  }
  function scTick() {
    SC.cur += (SC.target - SC.cur) * 0.14;
    if (Math.abs(SC.target - SC.cur) < 0.002) SC.cur = SC.target;
    scLayout();
    SC.raf = SC.cur !== SC.target ? requestAnimationFrame(scTick) : 0;
  }
  function scGo(i) {
    SC.target = clamp(i, 0, Math.max(0, games.length - 1));
    if (reduced()) { SC.cur = SC.target; scLayout(); return; }
    if (!SC.raf) SC.raf = requestAnimationFrame(scTick);
  }
  // straight to a game, as when one closes after stepping to it in the spotlight, so it flies home to the middle
  function scJump(g) {
    const i = games.indexOf(g);
    if (i < 0 || !SC.items.length) return;
    cancelAnimationFrame(SC.raf);
    SC.raf = 0;
    SC.cur = SC.target = i;
    scLayout();
  }

  /* ---------- shelf ⇄ grid: every case on screen is carried across ----------
     As the record site carries its records between the floor and the grid, each
     game on screen travels in an arc from where one view had it to where the other
     stands it. Coming off the shelf a case turns from its spine to its cover on the
     way; going back it turns to its spine again. */
  const Morph = (() => {
    const MAX = 72; // cases that travel; any more simply fade in
    let run = null;
    const onScreen = r => r.width > 0 && r.bottom > -40 && r.top < innerHeight + 40 && r.right > -40 && r.left < innerWidth + 40;
    // how tall it shows: in the showcase one to the side stands further back, so smaller than it is drawn
    const hOf = face => face.closest('.sc-it') ? face.getBoundingClientRect().height : face.offsetHeight;
    // how it is turned where it stands (one to the side in the showcase turns in towards the middle)
    const ryOf = item => footed(item.firstElementChild) ? liveRy(item.firstElementChild) : +(item.dataset.ry || 0);
    const kindOf = el => el.classList.contains('sp') ? 'spine' : el.classList.contains('cart') ? 'cart' : el.classList.contains('cside') ? 'side' : 'front';
    // a spine's width and the size of its lettering, as drawn (the rows draw them wider than real ones)
    const spineOf = el => el.classList.contains('sp') ? { sw: el.offsetWidth, sf: parseFloat(getComputedStyle(el).fontSize) } : {};
    // a cartridge facing out or standing on its side: either way it flies as the one solid cartridge
    const cartish = k => k === 'cart' || k === 'side';
    // how big a cartridge is drawn, in px per mm: on its side its height is the cartridge's width
    const unitOf = (face, kind, g) => hOf(face) / Case.cartSize(g, 1)[kind === 'side' ? 0 : 1];
    // how it is turned: on its side with the top label out, leaning on the shelf, or straight
    // (on its side it is pushed back by half its height, so its top label sits where the shelf showed it, not nearer)
    const cartTurn = (kind, view, back) => kind === 'side' ? `translateZ(${-back}px) rotateY(-90deg) rotateZ(90deg)` : `translateZ(0px) rotateY(0deg) rotateZ(${view === 'shelf' ? -6 : 0}deg)`;
    // the part of the page that waits while its case is in the air (in the list, the row's thumbnail)
    const spotOf = (item, face) => face.closest('.gt, .lcase') || item;

    /** Where every game on screen is now. Called just before the view is drawn again. */
    function capture() {
      if (flat(state.view) || reduced()) return null;
      const spots = new Map();
      for (const item of host.querySelectorAll('[data-id]')) {
        const face = faceOf(item), kind = kindOf(face);
        const r = rectOf(face);
        if (onScreen(r)) spots.set(item.dataset.id, { cx: r.left + r.width / 2, cy: r.top + r.height / 2, h: hOf(face), ry: ryOf(item), foot: footed(face) && footView(face, 1).perspective, z: +item.style.zIndex || 0, tilt: tiltOf(face), kind, u: cartish(kind) ? unitOf(face, kind, byId.get(item.dataset.id)) : 0, ...spineOf(face) });
      }
      return { view: state.view, spots };
    }

    function stop() {
      if (!run) return;
      clearTimeout(run.timer);
      run.layer.remove();
      run.hidden.forEach(el => { el.style.visibility = ''; });
      host.querySelector('.grid, .list')?.classList.remove('morphing');
      run = null;
    }

    /** Sets the games of the view just drawn travelling from where `snap` had them. */
    function play(snap) {
      if (!snap || snap.view === state.view || flat(state.view) || reduced()) return;
      stop();
      if (state.view === 'showcase') { scEnter(snap); return; }
      const layer = document.createElement('div');
      layer.className = 'morph';
      document.body.append(layer);
      const hidden = [];

      // every read first, so the page is laid out once rather than once per case
      const arrivals = [];
      for (const item of host.querySelectorAll('[data-id]')) {
        const face = faceOf(item);
        const r = rectOf(face);
        if (onScreen(r)) arrivals.push({ item, face, r, from: snap.spots.get(item.dataset.id), kind: kindOf(face) });
      }
      arrivals.sort((a, b) => (a.r.top - b.r.top) || (a.r.left - b.r.left));
      const flights = arrivals.filter(a => a.from && cartish(a.from.kind) === cartish(a.kind)).slice(0, MAX);
      const flying = new Set(flights);

      let longest = 0;
      flights.forEach((a, i) => {
        const g = byId.get(a.item.dataset.id);
        const cart = cartish(a.kind);
        // it flies at the size it lands, so it is crisp when it gets there
        const s = cart ? unitOf(a.face, a.kind, g) : hOf(a.face) / Case.layout(g, 1).h;
        const fly = cart ? Case.cart(g, s) : Case.slab(g, s);
        if (g.format === 'digital') $('.cv', fly)?.classList.add('ghosted');
        const [W, H] = cart ? Case.cartSize(g, s) : [Case.layout(g, s).w, hOf(a.face)];
        const cx = a.r.left + a.r.width / 2, cy = a.r.top + a.r.height / 2;
        const wrap = document.createElement('div');
        wrap.className = 'mf';
        // (leaving the showcase, the one in the middle stays over the ones beside it)
        wrap.style.cssText = `left:${(cx - W / 2 + scrollX).toFixed(1)}px;top:${(cy - H / 2 + scrollY).toFixed(1)}px;width:${W.toFixed(1)}px;height:${H.toFixed(1)}px;z-index:${a.from.z || 0}`;
        wrap.append(fly);
        layer.append(wrap);

        const dx = a.from.cx - cx, dy = a.from.cy - cy, k = cart ? a.from.u / s : a.from.h / H;
        const dist = Math.hypot(dx, dy);
        const lift = 30 + Math.min(90, dist * 0.12);
        const duration = 760 + Math.min(340, dist * 0.3);
        const delay = Math.min(i, 48) * 14;
        longest = Math.max(longest, delay + duration);
        // a game leaning on the shelf tips over or straightens up on the way
        const z0 = a.from.tilt || 0, z1 = tiltOf(a.face, false);
        const at = (x, y, sc, z) => `translate(${x.toFixed(1)}px,${y.toFixed(1)}px) scale(${sc.toFixed(3)}) rotate(${z.toFixed(2)}deg)`;
        const anim = wrap.animate([
          { transform: at(dx, dy, k, z0) },
          { transform: at(dx / 2, dy / 2 - lift, ((k + 1) / 2) * 1.06, (z0 + z1) / 2), offset: 0.5 },
          { transform: at(0, 0, 1, z1) },
        ], { duration, delay, easing: 'cubic-bezier(.35,0,.25,1)', fill: 'both' });
        // a case turns between its spine and its cover; a cartridge turns from its side to its front, or straightens up or leans back
        const turn = cart
          ? [cartTurn(a.from.kind, snap.view, H / 2), cartTurn(a.kind, state.view, H / 2)]
          : [`rotateY(${a.from.kind === 'spine' ? 90 : a.from.ry}deg)`, `rotateY(${a.kind === 'spine' ? 90 : ryOf(a.item)}deg)`];
        fly.animate(turn.map(transform => ({ transform })), { duration: duration * 0.8, delay: delay + duration * 0.1, easing: 'cubic-bezier(.45,.05,.25,1)', fill: 'both' });
        // to or from a case turned on the shelf: seen from its foot there, as it stands, and from its middle anywhere else
        if (a.from.foot || footed(a.face)) {
          const mid = { perspective: '900px', perspectiveOrigin: '50% 50%' };
          const v0 = a.from.foot ? { perspective: (parseFloat(a.from.foot) / k).toFixed(1) + 'px', perspectiveOrigin: '50% 100%' } : mid;
          wrap.animate([v0, footView(a.face, 1, mid.perspective)], { duration: duration * 0.8, delay: delay + duration * 0.1, easing: 'cubic-bezier(.45,.05,.25,1)', fill: 'both' });
        }
        // a spine as wide as it was where it took off, widening or narrowing to the one it lands on
        if (!cart) {
          const d1 = parseFloat(fly.style.getPropertyValue('--sd')), f1 = parseFloat(fly.style.getPropertyValue('--sfs'));
          const to = spineOf(a.face);
          const ends = [a.from.sw ? [a.from.sw / k, a.from.sf / k] : [d1, f1], to.sw ? [to.sw, to.sf] : [d1, f1]];
          if (Math.abs(ends[0][0] - ends[1][0]) > 0.5) fly.animate(ends.map(([d, f]) => ({ '--sd': d + 'px', '--sfs': f + 'px' })), { duration, delay, easing: 'cubic-bezier(.45,.05,.25,1)', fill: 'both' });
        }

        // the game on the page waits under it until it lands
        const spot = spotOf(a.item, a.face);
        spot.style.visibility = 'hidden';
        hidden.push(spot);
        anim.onfinish = () => { spot.style.visibility = ''; wrap.remove(); };
      });

      // the rest had no place in the other view: they come in quietly
      arrivals.filter(a => !flying.has(a)).forEach(a => {
        spotOf(a.item, a.face).animate([{ opacity: 0 }, { opacity: 1 }], { duration: 450, delay: 350, easing: 'ease-out', fill: 'backwards' });
      });
      // the grid's captions and the list's rows come in once the cases are nearly home
      host.querySelector('.grid, .list')?.classList.add('morphing');
      run = { layer, hidden, timer: setTimeout(stop, longest + 300) };
    }

    return { capture, play, stop };
  })();

  /* ---------- the spotlight: a game lifts off the page and comes to the stage ----------
     Its place on the shelf (or in the grid or list) stays empty while it is out. It
     stands on the left with its name above, where it can be turned round and opened,
     and everything known about it fills a drawer on the right (on a phone held
     upright, a sheet along the bottom). Closing flies it back into its place. */
  const Detail = (() => {
    const overlay = document.createElement('div');
    overlay.className = 'overlay';
    const ui = document.createElement('div');
    ui.className = 'spot-ui';
    ui.setAttribute('role', 'dialog');
    ui.setAttribute('aria-modal', 'true');
    ui.tabIndex = -1;
    ui.innerHTML = `<div class="spot-nav"><button type="button" data-step="-1" aria-label="Previous game">←</button><span class="spot-count"></span><button type="button" data-step="1" aria-label="Next game">→</button></div>
      <aside class="spot-drawer" aria-label="Details"><button class="spot-close" type="button" aria-label="Close">✕</button><div class="dw-body"></div></aside>`;
    const drawer = $('.spot-drawer', ui), body = $('.dw-body', ui);
    document.body.append(overlay, ui);
    overlay.addEventListener('click', () => close());
    $('.spot-close', ui).addEventListener('click', () => close());
    ui.addEventListener('click', e => {
      const s = e.target.closest('[data-step]');
      if (s) step(+s.dataset.step);
      const more = e.target.closest('[data-more]');
      const go = e.target.closest('[data-go]');
      if (go) jump(byId.get(go.dataset.go));
      if (more) { more.previousElementSibling.classList.toggle('all'); more.textContent = more.previousElementSibling.classList.contains('all') ? 'Show less' : 'Read more'; }
    });

    const REST = { box: [-6, -24], flat: [-4, -14] };
    const NAV = 64; // the arrows along the bottom
    let cur = null; // the game in the middle
    const leaving = new Set(); // games on their way back

    const isOpen = () => !!cur && !cur.closing;
    const shape = g => g.format === 'cartridge-only' ? 'cart' : g.format === 'digital' ? 'card' : 'box';
    // how it stood where it was picked from: a spine turned side on, a cartridge on its side with its top label out, or facing out
    const fromPose = el => footed(el) ? [0, liveRy(el), 0] : el && el.classList.contains('sp') ? [0, 90, 0] : el && el.classList.contains('cside') ? [0, -90, 90]
      : [0, el && el.closest('[data-ry]') ? +el.closest('[data-ry]').dataset.ry : 0, 0];
    const rot = ([x, y, z = 0]) => `rotateX(${x}deg) rotateY(${y}deg) rotateZ(${z}deg)`;

    /* What stands in the spotlight: the 3D case, a cartridge, or a digital copy's card. */
    function build(g, k) {
      const turn = document.createElement('div');
      turn.className = 'turn';
      const sh = shape(g);
      let obj;
      if (sh === 'box') obj = Case.box(g, k);
      else {
        obj = document.createElement('div');
        obj.className = 'flat';
        obj.append(sh === 'cart' ? Case.cart(g, k) : Case.front(g, k, false));
        if (sh === 'card') {
          obj.classList.add('card');
          obj.insertAdjacentHTML('beforeend', '<span class="stamp">Digital copy</span>' + receipt(g, k));
        }
      }
      turn.append(obj);
      return { turn, obj, sh };
    }
    /* A digital copy has no case to turn round, so its back is the licence it came as: a store receipt
       with the shop it activates in and what was bought. (Purchase details can join it once a store's API is wired in.) */
    function receipt(g, k) {
      const c = CONSOLE_BY_ID[g.console];
      const L = (g.launcher && g.launcher !== 'none' && LAUNCHERS[g.launcher]) || STORE[g.console] || { name: 'Digital store', bg: '#3a3a3a', fg: '#ffffff', glyph: '↓' };
      const pack = g.pack && g.pack !== 'Base game' ? [g.pack, g.packCode].filter(Boolean).join(' · ') : '';
      const rows = [
        ['Platform', c.name + (g.mac ? ' / Mac' : '')],
        ['Region', REGIONS[g.region]?.name || g.region],
        ['Released', Case.date(g.released, true)],
        ['Publisher', g.publisher],
        ['Edition', g.edition],
        ['Played', g.steam ? played(g.steam.playtime.total) : g.nintendo?.playtime != null ? played(g.nintendo.playtime) : g.xbox?.playtime != null ? played(g.xbox.playtime) : g.ea?.playtime != null && played(g.ea.playtime)],
        ['Owned since', g.ea?.since && Case.date(g.ea.since.slice(0, 10), true)],
        ['App ID', g.steam?.appid],
      ].filter(r => r[1]);
      // a barcode of uneven stripes, only for the look of it: the same for the same game
      let seed = [...String(g.id || g.title)].reduce((h, ch) => (h * 31 + ch.charCodeAt(0)) % 2147483647, 7) || 7;
      const rnd = () => (seed = (seed * 16807) % 2147483647) / 2147483647;
      const bars = [];
      for (let x = 0; x < 98;) { const e = Math.min(x + 0.6 + rnd() * 2.4, 100); bars.push(`#2a2723 ${x.toFixed(1)}% ${e.toFixed(1)}%`, `#0000 ${e.toFixed(1)}%`); x = e + 0.8 + rnd() * 2; }
      const { w } = Case.layout(g, k);
      return `<div class="receipt" style="--rf:${(w * 0.052).toFixed(2)}px" aria-hidden="true">
        <div class="rc-shop" style="--lb:${L.bg};--lf:${L.fg}"><i>${L.glyph}</i><span>${esc(L.name)}</span></div>
        <p class="rc-kind">Digital licence</p>
        <p class="rc-title">${esc(g.title)}</p>
        ${pack ? `<p class="rc-pack">${esc(pack)}</p>` : ''}
        <dl>${rows.map(([a, b]) => `<dt>${a}</dt><dd>${esc(b)}</dd>`).join('')}</dl>
        <i class="rc-bar" style="--bar:linear-gradient(90deg,${bars.join(',')})"></i>
        <p class="rc-foot">Licence held in ${esc(L.name)} account</p>
      </div>`;
    }
    /* ---------- the drawer: everything known about the game ----------
       What the copy is, then what Steam, PlayStation Network, Nintendo, Xbox, the EA app or Rockstar says of a game
       tied to it (data/steam.json, data/psn.json, data/nintendo.json, data/xbox.json, data/ea.json, data/rockstar.json):
       how long it was played and when, its achievements or trophies, and for Steam what its store page says. */
    const played = m => !m ? 'Not yet' : m < 60 ? `${m} min` : `${+(m / 60).toFixed(m < 600 ? 1 : 0)} h`;
    const day = t => Case.date(t.slice(0, 10), true);
    const num = n => Number(n).toLocaleString('en');
    const TROPHY = `<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="#c9a24a" d="M7 3h10v2h3v3a4 4 0 0 1-4.3 4A5 5 0 0 1 13 14.9V18h3v2H8v-2h3v-3.1A5 5 0 0 1 8.3 12 4 4 0 0 1 4 8V5h3zm0 4H6v1a2 2 0 0 0 1.2 1.8A6 6 0 0 1 7 8zm10 0v1a6 6 0 0 1-.2 1.8A2 2 0 0 0 18 8V7z"/></svg>`;
    const SWITCH = `<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="4" fill="#e60012"/><rect x="7.5" y="7" width="9" height="10" rx="1" fill="#fff"/><circle cx="4.9" cy="9.5" r="1.2" fill="#fff"/><circle cx="19.1" cy="14.5" r="1.2" fill="#fff"/></svg>`;
    const XBOX = `<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="11.5" fill="#107c10"/><path fill="#fff" d="M6.2 6.7C4.8 8.2 4 10 4 12a8 8 0 0 0 2.5 5.8c-.6-1.9 1.4-5.4 3.6-7.9-1.4-1.5-2.7-2.6-3.9-3.2zm11.6 0c-1.2.6-2.5 1.7-3.9 3.2 2.2 2.5 4.2 6 3.6 7.9A8 8 0 0 0 20 12c0-2-.8-3.8-2.2-5.3zM12 4c-1.5 0-2.9.4-4 1.1 1.3.2 2.7 1 4 2.1 1.3-1.1 2.7-1.9 4-2.1A8 8 0 0 0 12 4zm0 7.4c-2.1 2.2-4.4 5.7-3.9 7.5A8 8 0 0 0 12 20a8 8 0 0 0 3.9-1.1c.5-1.8-1.8-5.3-3.9-7.5z"/></svg>`;

    // a label and its value (already escaped), left out when there is no value
    const fact = (label, v) => v ? `<dt>${esc(label)}</dt><dd>${v}</dd>` : '';
    const facts = rows => rows.join('') ? `<dl class="dw-facts">${rows.join('')}</dl>` : '';
    const section = (title, html, icon = '') => html ? `<section class="dw-sec"><h4>${icon}<span>${esc(title)}</span></h4>${html}</section>` : '';
    const tags = xs => xs.length ? `<div class="dw-tags">${xs.map(x => `<span>${esc(x)}</span>`).join('')}</div>` : '';
    const when = t => t ? esc(day(t)) : '';
    const link = (href, text) => `<a class="dw-link" href="${esc(href)}" target="_blank" rel="noopener">${esc(text)} ↗</a>`;
    // how far through its achievements or trophies: the count over a bar
    const meter = (label, got, of, unit = '') => of ? `<div class="dw-meter"><p><span>${esc(label)}</span><b>${num(got)}${unit} <small>of ${num(of)}${unit}</small></b></p><i style="--p:${Math.min(100, got / of * 100).toFixed(1)}%"></i></div>` : '';
    // the trophies earned of each grade
    const grades = gs => gs?.some(([, , d]) => d) ? `<div class="dw-grades">${gs.filter(([, , d]) => d).map(([k, e, d]) => `<span class="tg-${k}"><i></i>${e}<small>/${d} ${k}</small></span>`).join('')}</div>` : '';
    // the achievements unlocked last, newest first
    const unlocks = xs => xs?.length ? `<p class="dw-sub">Latest unlocked</p><ul class="dw-unlocks">${xs.map(u => `<li><b>${esc(u.name)}</b>${u.description ? `<span>${esc(u.description)}</span>` : ''}${u.date || u.gamerscore ? `<small>${[u.date && day(u.date), u.gamerscore && `${u.gamerscore} G`].filter(Boolean).map(esc).join(' · ')}</small>` : ''}</li>`).join('')}</ul>` : '';

    // three figures across the top: how long it was played everywhere, how much of it was unlocked, and when it was last played
    function glance(g) {
      const m = minutesPlayed(g);
      const last = [g.steam?.lastPlayed, g.psn?.lastPlayed, g.nintendo?.lastPlayed, g.xbox?.lastPlayed, g.ea?.lastPlayed, g.rockstar?.lastPlayed].filter(Boolean).sort().at(-1);
      const done = [
        g.steam && g.steam.achievements.unlocked != null && [g.steam.achievements.unlocked, g.steam.achievements.total],
        g.psn?.trophies && [g.psn.trophies.earned, g.psn.trophies.total],
        g.xbox?.achievements && [g.xbox.achievements.earned, g.xbox.achievements.total],
        g.ea?.achievements && [g.ea.achievements.earned, g.ea.achievements.total],
      ].filter(x => x && x[1]);
      const got = done.reduce((s, x) => s + x[0], 0), of = done.reduce((s, x) => s + x[1], 0);
      const cells = [
        m ? ['Played', played(m)] : null,
        of ? [g.psn?.trophies && done.length === 1 ? 'Trophies' : 'Achievements', `${Math.round(got / of * 100)}%`] : null,
        last ? ['Last played', Case.date(last.slice(0, 10))] : null,
      ].filter(Boolean);
      return cells.length ? `<div class="dw-glance">${cells.map(([k, v]) => `<div><b>${esc(v)}</b><span>${esc(k)}</span></div>`).join('')}</div>` : '';
    }

    function copyHtml(g) {
      const c = CONSOLE_BY_ID[g.console];
      const L = g.launcher && g.launcher !== 'none' && LAUNCHERS[g.launcher];
      const pack = g.pack && g.pack !== 'Base game' ? [g.pack, g.packCode].filter(Boolean).join(' · ') : '';
      const format = g.format === 'digital' && STORE[g.console] ? `${FORMATS.digital} · ${STORE[g.console].name}` : FORMATS[g.format];
      return facts([
        fact('Platform', esc(c.name + (g.mac ? ' / Mac' : ''))),
        fact('Region', esc(REGIONS[g.region]?.long || g.region)),
        fact('Released', g.released && esc(Case.date(g.released, true))),
        fact('Publisher', esc(g.publisher)),
        fact('Developer', esc(g.developer)),
        fact('Genre', esc(g.genre)),
        fact('Series', esc(g.series)),
        fact('Edition', esc(g.edition)),
        fact('Pack', esc(pack)),
        fact('Format', esc(format)),
        fact('Activates on', L && esc(L.name)),
        fact('Also digital', g.alsoDigital && esc(g.launcher === 'ea' ? 'In the EA app' : `On ${LAUNCHERS[g.launcher]?.name || 'a launcher'}`)),
      ]);
    }

    function playedHtml(g) {
      let html = '';
      if (g.steam) {
        const s = g.steam, a = s.achievements, p = s.playtime;
        const on = [['Windows', p.windows], ['Mac', p.mac], ['Linux', p.linux], ['Steam Deck', p.deck]].filter(x => x[1]);
        html += section('Steam', facts([
          fact('Played', esc(played(p.total))),
          fact('Where', on.length > 1 && esc(on.map(([k, m]) => `${played(m)} on ${k}`).join(', '))),
          fact('Past two weeks', p.lastTwoWeeks && esc(played(p.lastTwoWeeks))),
          fact('Last played', when(s.lastPlayed)),
          fact('Achievements', a.total && a.unlocked == null && esc(num(a.total))),
        ]) + (a.unlocked != null ? meter('Achievements', a.unlocked, a.total) : ''), STORE_LOGOS.steam);
      }
      if (g.psn) {
        const p = g.psn, t = p.trophies;
        html += section('PlayStation Network', facts([
          fact('Played', p.playtime != null && esc(played(p.playtime))),
          fact('Sessions', p.playCount > 1 && esc(num(p.playCount))),
          fact('First played', when(p.firstPlayed)),
          fact('Last played', when(p.lastPlayed)),
        ]) + (t ? meter('Trophies', t.earned, t.total) + grades(t.grades) : ''), TROPHY);
      }
      if (g.nintendo) {
        const p = g.nintendo;
        html += section('Nintendo', facts([
          fact('Played', esc(played(p.playtime))),
          fact('Days played', p.playDays && esc(num(p.playDays))),
          fact('First played', when(p.firstPlayed)),
          fact('Last played', when(p.lastPlayed)),
        ]), SWITCH);
      }
      if (g.xbox) {
        const x = g.xbox, a = x.achievements;
        html += section('Xbox', facts([
          fact('Played', x.playtime != null && esc(played(x.playtime))),
          fact('Last played', when(x.lastPlayed)),
        ]) + (a ? meter('Achievements', a.earned, a.total) + meter('Gamerscore', a.gamerscore, a.gamerscoreTotal, ' G') + unlocks(a.recent) : ''), XBOX);
      }
      if (g.ea) {
        const e = g.ea, a = e.achievements;
        // an add-on has no play time of its own: it is played in its game, whose figures it shows
        html += section('EA app', facts([
          fact('Played in', e.via && esc(e.via.listedAs || e.via.title)),
          fact('Played', e.playtime != null && esc(e.playtime ? played(e.playtime) : 'Not yet')),
          fact('Last played', when(e.lastPlayed)),
          fact('Owned since', when(e.since)),
        ]) + (a ? meter('Achievements', a.earned, a.total) + unlocks(a.recent) : ''), STORE_LOGOS.ea);
      }
      if (g.rockstar) {
        const r = g.rockstar;
        // Rockstar keeps no play time anyone can read: only when it was last played, and the achievements
        html += section('Rockstar Games Launcher', facts([
          fact('Last played', r.lastPlayed ? when(r.lastPlayed) : 'Not yet'),
          fact('Achievements', r.achievements && esc(`${num(r.achievements)} unlocked`)),
        ]), STORE_LOGOS.rockstar);
      }
      return html;
    }

    // what its Steam store page says: what it is, pictures of it, its score, and the rest
    function storeHtml(g) {
      const st = g.steam?.store;
      if (!st) return '';
      const about = st.about || st.description;
      const shots = (st.screenshots || []).slice(0, 12);
      const ages = st.ageRatings ? [st.ageRatings.pegi && `PEGI ${st.ageRatings.pegi}`, st.ageRatings.esrb && `ESRB ${st.ageRatings.esrb.toUpperCase()}`].filter(Boolean).join(' · ') : '';
      const price = st.free ? 'Free to play' : st.price ? `${st.price.currency} ${st.price.final.toFixed(2)}${st.price.discount ? ` (${st.price.discount}% off)` : ''}` : '';
      const os = st.platforms ? Object.entries(st.platforms).filter(([, v]) => v).map(([k]) => ({ windows: 'Windows', mac: 'Mac', linux: 'Linux' }[k] || k)).join(', ') : '';
      const langs = st.languages || [];
      return section('About', about ? `<p class="dw-about">${esc(about)}</p>${about.length > 320 ? '<button type="button" class="dw-more" data-more>Read more</button>' : ''}` : '')
        + (shots.length ? `<div class="dw-shots">${shots.map(u => `<a href="${esc(u)}" target="_blank" rel="noopener" aria-label="Screenshot"><img src="${esc(u.replace('.1920x1080', '.600x338'))}" alt="" loading="lazy"></a>`).join('')}</div>` : '')
        + section('On the Steam store', facts([
          fact('Metacritic', st.metacritic?.score && `<span class="dw-score" style="--mc:${st.metacritic.score >= 75 ? '#6c3' : st.metacritic.score >= 50 ? '#fc3' : '#f44'}">${esc(st.metacritic.score)}</span>`),
          fact('Reviews', st.recommendations && esc(num(st.recommendations))),
          fact('On Steam since', st.released && esc(Case.date(st.released, true))),
          fact('Developer', esc((st.developers || []).join(', '))),
          fact('Publisher', esc((st.publishers || []).join(', '))),
          fact('Genres', esc((st.genres || []).join(', '))),
          fact('Systems', esc(os)),
          fact('Controller', st.controllerSupport && esc(st.controllerSupport === 'full' ? 'Full support' : 'Partial support')),
          fact('Rated', esc(ages)),
          fact('Languages', langs.length && esc(langs.length > 6 ? `${langs.slice(0, 6).join(', ')} and ${langs.length - 6} more` : langs.join(', '))),
          fact('Add-ons', st.dlc?.length && esc(`${st.dlc.length} DLC`)),
          fact('Price now', esc(price)),
        ]) + tags(st.categories || []));
    }

    function linksHtml(g) {
      const c = CONSOLE_BY_ID[g.console];
      const q = encodeURIComponent(`${g.title} ${c.name} ${REGIONS[g.region]?.name || g.region} box art`);
      return section('Links', [
        g.steam?.storeUrl && link(g.steam.storeUrl, 'Steam store page'),
        g.steam?.store?.metacritic?.url && link(g.steam.store.metacritic.url, 'Metacritic'),
        !g.cover && link(`https://www.google.com/search?tbm=isch&q=${q}`, 'Find the cover on Google Images'),
      ].filter(Boolean).join(''));
    }

    // what a collection says of it besides: for a Sims game on PC, the rest of its series' packs (js/sims.js);
    // and for any other game sold with packs, the game and its packs on the same console
    const related = g => Object.entries(COLLECTIONS).map(([k, c]) => {
      const r = c.drawer && LISTS[k] && c.drawer(g, LISTS[k]);
      return r ? section(r.title, r.html) : '';
    }).join('') || packsHtml(g);

    // a game and its packs: the copies titled as the game, and the ones with a pack (data/games.json) titled
    // "Game: Pack" or filed under it (sortAs) — Spore with Galactic Adventures, Age of Empires III with The WarChiefs
    function packsHtml(g) {
      if (g.series) return '';
      const root = g.pack ? g.sortAs || g.title.split(': ')[0] : g.title;
      const fam = DB.filter(x => x.console === g.console && !x.series
        && (x.pack ? x.sortAs === root || x.title.startsWith(root + ': ') : x.title === root));
      if (!fam.some(x => x.pack) || fam.length < 2) return '';
      const name = x => x.title.startsWith(root + ': ') ? x.title.slice(root.length + 2) : x.title;
      return section(`${root} on ${CONSOLE_BY_ID[g.console].short}`, Collection.packList(g, fam, name));
    }

    // the other copies of the same game: on other consoles, and on the same one in another format (Moving Out boxed and
    // as a code in a box). Copies match by title (Grand Theft Auto IV on PS3, Xbox 360 and PC), or are filed with
    // one by sameAs (Canis Canem Edit with Bully: Scholarship Edition); sameAs false keeps one apart (Just Dance on the
    // Switch isn't the Wii's). A copy that plays on more than one console (alsoOn) is listed once, the game open in bold
    const gameKey = g => g.sameAs === false ? '' : (g.sameAs || g.title).normalize('NFD').toLowerCase().replace(/&/g, 'and').replace(/[^a-z0-9]+/g, ' ').trim();
    function elsewhereHtml(g) {
      const own = byId.get(g.twinOf) || g, key = gameKey(own);
      if (!key) return '';
      const on = x => [x.console, ...(x.alsoOn || [])];
      const fam = DB.filter(x => !x.twinOf && gameKey(x) === key);
      if (fam.length < 2) return '';
      fam.sort((a, b) => ORDER[a.console] - ORDER[b.console] || (a.released || '').localeCompare(b.released || ''));
      // what tells the copies apart: the format, region or launcher only where they differ
      const differs = f => new Set(fam.map(f)).size > 1;
      const how = x => x.format === 'digital' && x.launcher && x.launcher !== 'none' ? LAUNCHERS[x.launcher]?.name : FORMATS[x.format];
      const showHow = differs(how), showRegion = differs(x => x.region);
      const item = x => {
        const where = on(x).map(c => CONSOLE_BY_ID[c].name).join(' / ') + (x.mac ? ' / Mac' : '');
        const what = [x.title !== own.title && x.title, x.edition, showHow && how(x), showRegion && x.region, Case.year(x)].filter(Boolean).join(' · ');
        return `<li${x === own ? ' class="on" aria-current="true"' : ''}><button type="button" data-go="${esc(x === own ? g.id : x.id)}">${esc(where)}</button><small>${esc(what)}</small></li>`;
      };
      const others = fam.some(x => on(x).some(c => !on(own).includes(c))), same = fam.some(x => x !== own && x.console === own.console);
      return section(others && same ? 'Other copies and platforms' : others ? 'On other platforms' : 'Other copies', `<div class="dw-packs"><ul>${fam.map(item).join('')}</ul></div>`);
    }

    // a bundle and the packs it came with (contains): the bundle lists them, each pack names its bundle
    function bundleHtml(g) {
      // the packs in a bundle aren't links: each would only open the bundle again
      const item = x => `<li>${x.bundle === g ? `<span>${esc(x.title)}</span>` : `<button type="button" data-go="${esc(x.id)}">${esc(x.title)}</button>`}<small>${esc([x.pack, x.packCode].filter(Boolean).join(' · '))}</small></li>`;
      const list = xs => `<div class="dw-packs"><ul>${xs.map(item).join('')}</ul></div>`;
      if (g.packs) return section('In this bundle', list(g.packs));
      return g.bundle ? section('Came in', list([g.bundle])) : '';
    }

    function details(g) {
      const marks = [g.fav && '♥ Favourite', g.platinum && 'Platinum', g.steelbook && 'SteelBook', g.kinect && 'Kinect', g.psn?.trophies?.platinum && 'Platinum trophy'].filter(Boolean);
      return glance(g) + tags(marks)
        + (g.note ? `<p class="dw-note">${esc(g.note)}</p>` : '')
        + section('The copy', copyHtml(g))
        + elsewhereHtml(g)
        + bundleHtml(g)
        + related(g)
        + playedHtml(g) + storeHtml(g) + linksHtml(g);
    }

    function sizeOf(g, k) {
      if (shape(g) === 'cart') return Case.cartSize(g, k);
      const L = Case.layout(g, k);
      return [L.w, L.h];
    }

    /* Where the game stands: the screen left of the drawer, or above it on a phone held upright, where the drawer
       is a sheet along the bottom. The arrows sit along the foot of it. */
    function stage() {
      const vw = innerWidth, vh = innerHeight;
      const wide = vw >= 760 && vw >= vh * 0.9;
      const S = wide ? { x: 0, y: 0, w: vw - Math.round(clamp(vw * 0.36, 340, 480)), h: vh } : { x: 0, y: 0, w: vw, h: Math.round(vh * 0.5) };
      // on a short stage the arrows stand either side of the game instead of under it
      S.edge = !wide || vh < 560;
      S.nav = S.edge ? 0 : NAV;
      ui.classList.toggle('sheet', !wide);
      ui.classList.toggle('edge', S.edge);
      ui.style.setProperty('--stage-w', S.w + 'px');
      ui.style.setProperty('--stage-h', S.h + 'px');
      return S;
    }

    /* The game in the middle of the stage, as big as the text above and the buttons below leave room for. */
    function layoutFor(g) {
      const S = stage();
      const [w1, h1] = sizeOf(g, 1);
      const padX = S.edge ? 58 : clamp(S.w * 0.06, 16, 72), margin = clamp(S.h * 0.04, 12, 40);
      const cap = shape(g) === 'cart' ? 7 : 3.2;
      const text = S.w < 640 ? 130 : 170; // a first guess at the text; place() measures it
      const k = Math.max(0.15, Math.min((S.w - padX * 2) / (w1 * 1.15), Math.max(S.h - margin * 2 - S.nav - text, 110) / h1, (S.h * 0.66) / h1, cap));
      return { k, w: w1 * k, h: h1 * k, cx: S.x + S.w / 2, cy: S.y + S.h / 2, vw: innerWidth, vh: innerHeight, S, padX, margin };
    }

    function infoAbove(g) {
      const c = CONSOLE_BY_ID[g.console];
      const pack = g.pack && g.pack !== 'Base game' ? g.pack : '';
      return `<p class="spot-eyebrow" style="--n:0"><span class="spot-art">${CONSOLE_ART[g.console] || ''}</span>${esc(c.name)}${g.mac ? ' / Mac' : ''} · ${esc(REGIONS[g.region]?.name || g.region)}</p><h2 style="--n:1">${esc(g.title)}</h2>
        <p class="spot-who" style="--n:2">${[g.released && Case.date(g.released, true), g.publisher, pack].filter(Boolean).map(esc).join(' · ')}</p>`;
    }
    function infoBelow(g) {
      const n = 3;
      return shape(g) === 'box' ? `<div class="views" style="--n:${n}">
          <button type="button" data-v="front">Front</button><button type="button" data-v="spine">Spine</button><button type="button" data-v="back">Back</button>
          <button type="button" class="open-btn" data-v="open">${Case.layout(g, 1).kind === 'box' ? 'Take it out' : 'Open case'}</button></div>`
        // a digital copy has only its cover and, on the back, its licence
        : shape(g) === 'card' ? `<div class="views" style="--n:${n}"><button type="button" data-v="front">Cover</button><button type="button" data-v="back">Licence</button></div>` : '';
    }

    /* ---------- turning it round ---------- */
    function pose(animate) {
      if (!cur) return;
      cur.turn.style.transition = animate ? 'transform .8s cubic-bezier(.2,.8,.2,1)' : 'none';
      cur.turn.style.transform = rot([cur.rx, cur.ry]);
      const n = ((cur.ry % 360) + 360) % 360;
      const v = n > 45 && n < 135 ? 'spine' : n >= 135 && n < 225 ? 'back' : (n < 45 || n > 315) ? 'front' : '';
      $$('[data-v]', cur.layer).forEach(b => b.classList.toggle('on', b.dataset.v === v));
    }
    function view(v) {
      if (!cur || cur.sh === 'cart') return;
      if (cur.sh === 'card' && !['front', 'back'].includes(v)) return;
      const isBox = Case.layout(cur.g, 1).kind === 'box';
      if (v === 'open' || v === 'close') {
        cur.open = v === 'open';
        cur.obj.classList.toggle('open', cur.open);
        if (cur.open) { cur.rx = -8; cur.ry = isBox ? -18 : 16; }
        $$('.open-btn', cur.layer).forEach(b => {
          b.textContent = cur.open ? (isBox ? 'Put it back' : 'Close case') : (isBox ? 'Take it out' : 'Open case');
          b.dataset.v = cur.open ? 'close' : 'open';
        });
      } else {
        if (cur.open) view('close');
        cur.rx = -5;
        cur.ry = { front: -14, spine: 90, back: 180 }[v] + Math.round(cur.ry / 360) * 360;
      }
      pose(true);
    }
    // drag to turn, a tap opens the case
    function grab(el) {
      let drag = null;
      el.addEventListener('pointerdown', e => {
        if (!cur || cur.closing || cur.piece !== el) return;
        drag = { x: e.clientX, y: e.clientY, rx: cur.rx, ry: cur.ry, moved: false };
        el.setPointerCapture(e.pointerId);
        cur.flight?.finish?.();
      });
      el.addEventListener('pointermove', e => {
        if (!drag || !cur) return;
        const dx = e.clientX - drag.x, dy = e.clientY - drag.y;
        if (Math.abs(dx) + Math.abs(dy) > 4) drag.moved = true;
        cur.ry = drag.ry + dx * 0.5;
        cur.rx = clamp(drag.rx - dy * 0.35, -60, 60);
        pose(false);
      });
      el.addEventListener('pointerup', () => {
        if (drag && !drag.moved && cur && cur.sh === 'box') view(cur.open ? 'close' : 'open');
        // a tap turns a digital copy over
        else if (drag && !drag.moved && cur && cur.sh === 'card') { const n = ((cur.ry % 360) + 360) % 360; view(n > 90 && n < 270 ? 'front' : 'back'); }
        drag = null;
      });
    }

    /* ---------- where a game is on the page ---------- */
    function poseOf(el) {
      const r = rectOf(el), z = tiltOf(el);
      // a leaning one's box is widened by its tilt: its own size is what it is drawn at
      const w = z ? el.offsetWidth : r.width, h = z ? el.offsetHeight : r.height;
      return { cx: r.left + r.width / 2, cy: r.top + r.height / 2, w, h, z, seen: r.bottom > 0 && r.top < innerHeight && r.right > 0 && r.left < innerWidth && r.width > 0 };
    }
    // the element that shows this game now: on the shelf, in the grid or in the list
    function homeOf(g) {
      const item = (state.view === 'checklist' ? $('#checklist') : host).querySelector(`[data-id="${CSS.escape(g.id)}"]`);
      return item ? faceOf(item) : null;
    }
    // what a flight scales against: a spine is matched by height, anything else by its size
    const scaleFrom = (el, p, w, h) => el.closest('.sc-it') ? p.h / h : el.classList.contains('sp') ? p.h / h : el.classList.contains('cside') ? p.h / w : Math.min(p.w / w, p.h / h);
    // (z: tipped over that far, as a game leaning on the shelf)
    const piecePose = (L, cx, cy, s, z = 0) => `translate(${(cx - L.w / 2).toFixed(1)}px,${(cy - L.h / 2).toFixed(1)}px) scale(${s.toFixed(4)}) rotate(${z.toFixed(2)}deg)`;
    // a place just off the screen on the way to (x, y)
    const offscreen = (L, x, y) => [clamp(x, L.w / 2, L.vw - L.w / 2), y < L.vh / 2 ? -L.h * 0.75 : L.vh + L.h * 0.75];

    // a case picked from a spine drawn wider than the real one (the rows) starts as wide and narrows to its real
    // depth on the way out, and widens again on the way home, instead of jumping (s: its scale at the spine's end)
    function depth(a, el, s, duration, fill, out) {
      if (a.sh !== 'box' || !el || !el.classList.contains('sp')) return;
      const real = [parseFloat(a.obj.style.getPropertyValue('--sd')), parseFloat(a.obj.style.getPropertyValue('--sfs'))];
      const spine = [el.offsetWidth / s, parseFloat(getComputedStyle(el).fontSize) / s];
      if (Math.abs(spine[0] - real[0]) < 0.5) return;
      const keys = (out ? [spine, real] : [real, spine]).map(([d, f]) => ({ '--sd': d + 'px', '--sfs': f + 'px' }));
      a.obj.animate(keys, { duration, easing: 'cubic-bezier(.4,.1,.2,1)', fill });
    }

    // A digital copy has no case to turn edge on, so it folds down into the dashed outline it stands as on the
    // shelf, like a download going back into the library, and unfolds out of it coming out (out). sw: that
    // outline's width on the screen, s: the scale it is drawn at there.
    const flatOff = (a, el) => a.sh === 'card' && !!el && el.classList.contains('sp');
    function fold(a, sw, s, duration, out) {
      const o = a.obj;
      a.folding?.cancel();
      const open = { '--fold': 0, '--fx': '0px', '--px': '1px' };
      const shut = { '--fold': 1, '--fx': (Math.max(0, a.L.w - sw / s) / 2).toFixed(1) + 'px', '--px': (1 / s).toFixed(3) + 'px' };
      const ease = 'cubic-bezier(.6,0,.3,1)';
      const keys = out ? [{ ...shut, easing: ease }, { ...open, offset: 0.55 }, open] : [open, { ...open, offset: 0.45, easing: ease }, shut];
      o.classList.add('folding');
      a.folding = o.animate(keys, { duration, fill: out ? 'none' : 'forwards' });
      if (out) a.folding.finished.then(() => { if (!a.closing) o.classList.remove('folding'); }, () => {});
    }

    function hide(a, el) {
      if (el && el.closest('.sc-it')) el = el.closest('.gt'); // in the showcase its reflection goes with it
      if (footed(el)) el = el.parentElement; // on the shelf its shadow on the board goes with it
      if (el && !a.hidden.includes(el)) { el.style.visibility = 'hidden'; a.hidden.push(el); }
    }
    function unhide(a) { a.hidden.forEach(el => { el.style.visibility = ''; }); a.hidden = []; }

    /* ---------- opening ---------- */
    function open(g, source, fromHash) {
      if (g?.bundle) { g = g.bundle; source = null; fromHash = false; } // a pack opens the bundle it came in
      if (!g) return;
      if (cur) finish(cur);
      tipEl.classList.remove('vis');
      if (!fromHash) setPath(g.id, true);
      overlay.classList.add('open');
      ui.classList.add('open');
      show(g, source && source.isConnected ? source : homeOf(g), 0);
      setTimeout(() => ui.focus({ preventScroll: true }), 60);
    }

    // Stands a game in the middle. It lifts off its place when that is on screen; otherwise it
    // comes in from the side it is on (side: 1 from the right, -1 from the left, 0 from its place).
    function show(g, src, side, still) {
      // already on its way back: it comes straight out again, from where it has got to
      let back = null;
      leaving.forEach(x => {
        if (x.g !== g) return;
        if (side) back = nowAt(x);
        if (back) back.turn = getComputedStyle(x.turn).transform;
        finish(x);
      });
      const L = layoutFor(g);
      const a = cur = { g, source: src || null, L, rx: 0, ry: 0, open: false, closing: false, hidden: [], raf: 0, timer: 0 };
      const layer = document.createElement('div');
      layer.className = 'spot';
      layer.innerHTML = `<div class="spot-floor"></div><div class="spot-info above">${infoAbove(g)}</div><div class="spot-info below">${infoBelow(g)}</div>`;
      const piece = document.createElement('div');
      piece.className = 'spot-piece';
      const { turn, obj, sh } = build(g, L.k);
      piece.append(turn);
      layer.append(piece);
      if (still) layer.classList.add('still');
      else if (side) layer.classList.add('step');
      ui.before(layer);
      Object.assign(a, { layer, piece, turn, obj, sh });
      layer.addEventListener('click', e => { const v = e.target.closest('[data-v]'); if (v) view(v.dataset.v); });
      grab(piece);
      place(a);
      count(g);
      fill(g, side && !still);
      ui.setAttribute('aria-label', g.title);
      document.body.style.setProperty('--spot-x', L.cx.toFixed(0) + 'px');
      document.body.style.setProperty('--spot-y', L.cy.toFixed(0) + 'px');

      const restPose = REST[sh === 'box' ? 'box' : 'flat'];
      [a.rx, a.ry] = restPose;
      const rest = piecePose(L, L.cx, L.cy, 1);
      piece.style.transform = rest;
      pose(false);

      const from = a.source ? poseOf(a.source) : null;
      hide(a, a.source); // its place stays empty while it is out
      if (still || reduced()) return;
      if (from && from.seen && !side) {
        // off the page: it lifts from its place and flies over, turning and growing as it goes
        const s0 = scaleFrom(a.source, from, L.w, L.h);
        const dist = Math.hypot(from.cx - L.cx, from.cy - L.cy);
        const lift = 50 + Math.min(110, dist * 0.12);
        const duration = 820 + Math.min(320, dist * 0.25);
        a.flight = piece.animate([
          { transform: piecePose(L, from.cx, from.cy, s0, from.z) },
          { transform: piecePose(L, (from.cx + L.cx) / 2, (from.cy + L.cy) / 2 - lift, (s0 + 1) / 2, from.z * 0.4), offset: 0.45 },
          { transform: rest },
        ], { duration, easing: 'cubic-bezier(.3,.05,.2,1)', fill: 'backwards' });
        const flat = flatOff(a, a.source);
        turn.animate([{ transform: rot(flat ? [0, 0, 0] : fromPose(a.source)) }, { transform: rot(restPose) }], { duration: duration * 0.9, easing: 'cubic-bezier(.4,.1,.2,1)', fill: 'backwards' });
        if (flat) fold(a, from.w, s0, duration, true);
        // off the shelf facing out, it starts seen from its foot as it stood there
        if (footed(a.source)) piece.animate([footView(a.source, s0), { perspective: getComputedStyle(piece).perspective, perspectiveOrigin: '50% 50%' }], { duration: duration * 0.9, easing: 'cubic-bezier(.4,.1,.2,1)', fill: 'backwards' });
        depth(a, a.source, s0, duration * 0.9, 'backwards', true);
      } else {
        // its place is off the screen (or it has none): it comes in from that side
        const start = back ? [back.cx, back.cy]
          : side ? [L.cx + side * (L.vw / 2 + L.w * 0.8), L.cy]
          : from ? offscreen(L, from.cx, from.cy)
          : [L.cx, L.vh + L.h * 0.75];
        const s0 = back ? back.s : from && !side ? Math.max(0.3, scaleFrom(a.source, from, L.w, L.h)) : 0.9;
        a.flight = piece.animate([
          { transform: piecePose(L, start[0], start[1], s0, back ? back.z : 0) },
          { transform: rest },
        ], { duration: side ? 620 : 820, easing: 'cubic-bezier(.2,.75,.25,1)', fill: 'backwards' });
        const turnFrom = back && back.turn !== 'none' ? back.turn : rot(from && !side ? fromPose(a.source) : [restPose[0], restPose[1] + (side || -1) * 40]);
        turn.animate([{ transform: turnFrom }, { transform: rot(restPose) }],
          { duration: side ? 620 : 820, easing: 'cubic-bezier(.2,.75,.25,1)', fill: 'backwards' });
      }
    }

    // the text above and the buttons below, kept on the stage: the game shrinks if they need the room
    function place(a) {
      const { L, layer } = a, S = L.S;
      const above = $('.spot-info.above', layer), below = $('.spot-info.below', layer);
      const width = Math.min(Math.max(L.w * 1.6, 360), S.w - (S.edge ? 32 : L.padX * 2));
      for (const info of [above, below]) {
        info.style.left = (S.x + (S.w - width) / 2).toFixed(1) + 'px';
        info.style.width = width.toFixed(1) + 'px';
      }
      const ah = above.offsetHeight, bh = below.offsetHeight, gapA = 22, gapB = bh ? 26 : 0;
      const room = S.h - L.margin * 2 - S.nav - ah - bh - gapA - gapB;
      if (L.h > room && room > 50) {
        const f = room / L.h;
        Object.assign(L, { k: L.k * f, w: L.w * f, h: L.h * f });
        const { turn, obj, sh } = build(a.g, L.k);
        a.piece.replaceChildren(turn);
        Object.assign(a, { turn, obj, sh });
      }
      const top = S.y + L.margin + Math.max(0, (S.h - S.nav - L.margin * 2 - (ah + gapA + L.h + gapB + bh)) / 2);
      L.cy = top + ah + gapA + L.h / 2;
      above.style.top = top.toFixed(1) + 'px';
      below.style.top = (L.cy + L.h / 2 + gapB).toFixed(1) + 'px';
      a.piece.style.width = L.w.toFixed(1) + 'px';
      a.piece.style.height = L.h.toFixed(1) + 'px';
      const floor = $('.spot-floor', layer);
      floor.style.left = (L.cx - L.w * 0.7).toFixed(1) + 'px';
      floor.style.width = (L.w * 1.4).toFixed(1) + 'px';
      floor.style.top = (L.cy + L.h / 2 - 12).toFixed(1) + 'px';
    }

    // the drawer takes the game's details, back at the top; stepping (fade), they come in afresh
    function fill(g, fade) {
      if (body._g === g) return; // drawn again for a resize: it keeps its place
      body._g = g;
      body.innerHTML = details(g);
      drawer.scrollTop = body.scrollTop = 0;
      drawer.setAttribute('aria-label', `Details of ${g.title}`);
      if (fade && !reduced()) body.animate([{ opacity: 0, transform: 'translateY(12px)' }, { opacity: 1, transform: 'none' }], { duration: 380, easing: 'cubic-bezier(.2,.8,.25,1)' });
    }

    function count(g) {
      const i = games.indexOf(g);
      $('.spot-count', ui).textContent = i < 0 ? '' : `${i + 1} of ${games.length}`;
      $('[data-step="-1"]', ui).disabled = i <= 0;
      $('[data-step="1"]', ui).disabled = i < 0 || i >= games.length - 1;
    }

    /* ---------- closing: back to its place ---------- */
    // a book taken out of its sleeve (The Sims Medieval Collector's Edition) goes back in, quickly, before it
    // flies anywhere; then fn. False when there is nothing to put back
    function putBack(fn) {
      const a = cur;
      if (!a || !a.open || !a.obj.classList.contains('m-book') || reduced()) return false;
      if (a.settling) return true;
      a.settling = true;
      a.obj.classList.add('quick');
      view('close');
      setTimeout(() => { a.settling = false; a.obj.classList.remove('quick'); if (cur === a) fn(); }, 800);
      return true;
    }
    function close(fromHash) {
      const a = cur;
      if (!a || a.closing) return;
      if (putBack(() => close(fromHash))) return;
      overlay.classList.remove('open');
      ui.classList.remove('open');
      body._g = null;
      if (!fromHash) setPath(here(), false, true);
      if (state.view === 'showcase') scJump(a.g); // the showcase turns to it, so it flies home to the middle
      const back = homeOf(a.g);
      if (back && state.view === 'rows') back.closest('[data-id]').scrollIntoView({ block: 'nearest', inline: 'center' });
      leave(a, 0);
      setTimeout(() => back?.closest('[data-id]')?.focus({ preventScroll: true }), 30);
    }

    // Sends a game back: into its place if that is on screen, else off the screen towards it;
    // stepping through, always out of the side it is leaving by (side -1 left, 1 right).
    function leave(a, side) {
      a.closing = true;
      if (cur === a) cur = null;
      leaving.add(a);
      clearTimeout(a.timer);
      if (reduced()) { finish(a); return; }
      a.layer.classList.add('leaving');
      a.obj.classList.remove('open');
      const L = a.L;
      // where it is right now, which is not its resting place if it was still flying in
      const from = nowAt(a) || { cx: L.cx, cy: L.cy, s: 1, z: 0 };
      // and how it is turned: still turning in, it turns on from there instead of jumping to its rest
      const turning = a.turn.getAnimations().some(x => x.playState === 'running');
      const turnNow = turning && getComputedStyle(a.turn).transform;
      a.piece.getAnimations().forEach(x => x.cancel());
      a.turn.getAnimations().forEach(x => x.cancel());
      a.obj.getAnimations().forEach(x => { if (x.effect?.getKeyframes?.()[0]?.['--sd'] !== undefined) x.cancel(); });
      a.piece.style.transform = piecePose(L, from.cx, from.cy, from.s, from.z);
      if (turnNow && turnNow !== 'none') {
        a.turn.style.transition = 'none';
        a.turn.style.transform = turnNow;
        void a.turn.offsetWidth; // so the turn below starts from here
      }

      // the page may have been drawn again since it came out
      const home = homeOf(a.g);
      if (home !== a.source) { unhide(a); a.source = home; hide(a, home); }
      const to = home ? poseOf(home) : null;

      if (to && to.seen && !side) {
        const dist = Math.hypot(to.cx - from.cx, to.cy - from.cy);
        const lift = 40 + Math.min(100, dist * 0.1);
        const duration = 760 + Math.min(300, dist * 0.22);
        a.turn.style.transition = `transform ${duration * 0.85}ms cubic-bezier(.4,0,.2,1)`;
        const flat = flatOff(a, home);
        a.turn.style.transform = rot(flat ? [0, Math.round(a.ry / 360) * 360, 0] : fromPose(home));
        if (flat) fold(a, to.w, scaleFrom(home, to, L.w, L.h), duration, false);
        // and going back to stand facing out on the shelf, it ends seen from its foot, as it stands there
        if (footed(home)) {
          const ease = `${(duration * 0.85).toFixed(0)}ms cubic-bezier(.4,0,.2,1)`;
          a.piece.style.transition = `perspective ${ease}, perspective-origin ${ease}`;
          Object.assign(a.piece.style, footView(home, scaleFrom(home, to, L.w, L.h)));
        }
        depth(a, home, scaleFrom(home, to, L.w, L.h), duration * 0.85, 'forwards', false);
        const t0 = performance.now();
        let goal = to;
        // every frame aims at where its place is now, since the page can scroll meanwhile
        const frame = now => {
          if (a.done) return;
          const t = clamp((now - t0) / duration, 0, 1), u = easeInOut(t);
          if (home.isConnected) goal = poseOf(home);
          a.piece.style.transform = piecePose(L, lerp(from.cx, goal.cx, u), lerp(from.cy, goal.cy, u) - lift * Math.sin(Math.PI * u), lerp(from.s, scaleFrom(home, goal, L.w, L.h), u), lerp(from.z, goal.z, u));
          if (t < 1) a.raf = requestAnimationFrame(frame); else finish(a);
        };
        a.raf = requestAnimationFrame(frame);
        a.timer = setTimeout(() => finish(a), duration + 400);
        return;
      }

      const end = side ? [L.cx + side * (L.vw / 2 + L.w * 0.8), from.cy]
        : to ? offscreen(L, to.cx, to.cy)
        : [from.cx, L.vh + L.h * 0.75];
      const s1 = to && !side ? Math.max(0.3, scaleFrom(home, to, L.w, L.h)) : 0.9;
      // stepping through, it is already on its way the moment the next one is asked for, never hanging about in the middle
      a.turn.style.transition = side ? 'transform .5s cubic-bezier(.3,.5,.4,1)' : 'transform .6s cubic-bezier(.4,0,.2,1)';
      a.turn.style.transform = rot(to && !side ? fromPose(home) : [a.rx, a.ry + (side || 1) * 40]);
      a.flight = a.piece.animate([
        { transform: piecePose(L, from.cx, from.cy, from.s, from.z) },
        { transform: piecePose(L, end[0], end[1], s1) },
      ], side ? { duration: 500, easing: 'cubic-bezier(.3,.5,.4,1)', fill: 'forwards' } : { duration: 650, easing: 'cubic-bezier(.5,0,.75,.4)', fill: 'forwards' });
      a.flight.finished.then(() => finish(a), () => {});
      a.timer = setTimeout(() => finish(a), 1000);
    }

    // where a game is on the screen while it is still flying in or out, else null
    function nowAt(a) {
      if (!a.flight || a.flight.playState !== 'running') return null;
      const L = a.L, m = new DOMMatrix(getComputedStyle(a.piece).transform);
      return { cx: m.e + L.w / 2, cy: m.f + L.h / 2, s: Math.hypot(m.a, m.b), z: Math.atan2(m.b, m.a) * 180 / Math.PI };
    }

    function finish(a) {
      if (!a || a.done) return;
      a.done = true;
      clearTimeout(a.timer);
      cancelAnimationFrame(a.raf);
      a.layer?.remove();
      unhide(a);
      leaving.delete(a);
      if (cur === a) cur = null;
    }

    // the next or previous game: this one goes out one side as that one comes in from the other
    function step(n) {
      if (!cur || cur.closing) return;
      if (putBack(() => step(n))) return;
      const g = games[games.indexOf(cur.g) + n];
      if (!g || games.indexOf(cur.g) < 0) return;
      setPath(g.id, false);
      // held down, the games go by quickly: the oldest on their way out, all but off the screen by now, go at once
      [...leaving].slice(0, -3).forEach(finish);
      leave(cur, -n);
      show(g, homeOf(g), n);
    }

    // straight to another game picked in the drawer: it comes in from the side it stands on in the list
    function jump(g) {
      g = g?.bundle || g; // a pack that came in a bundle opens the bundle it stands in
      if (!g || !cur || cur.closing || g === cur.g) return;
      if (putBack(() => jump(g))) return;
      const i = games.indexOf(cur.g), j = games.indexOf(g);
      const n = i >= 0 && j >= 0 && j < i ? -1 : 1;
      setPath(g.id, false);
      leave(cur, -n);
      show(g, homeOf(g), n);
    }

    // the page was drawn again: the game in the middle belongs to its new place
    function rehome() {
      if (!cur) return;
      unhide(cur);
      cur.source = homeOf(cur.g);
      hide(cur, cur.source);
      count(cur.g);
    }

    let rt = 0;
    addEventListener('resize', () => {
      clearTimeout(rt);
      rt = setTimeout(() => {
        if (!cur || cur.closing) return;
        const g = cur.g;
        finish(cur);
        show(g, homeOf(g), 0, true);
      }, 200);
    });
    document.addEventListener('keydown', e => {
      if (!isOpen() || e.target.matches('input, select, textarea')) return;
      if (e.key === 'Escape') close();
      if (e.key === 'ArrowRight') step(1);
      if (e.key === 'ArrowLeft') step(-1);
    });

    return { open, close, isOpen, rehome, current: () => cur && cur.g };
  })();

  /* ---------- the address: /ps2 for a console, /ps2-final-fantasy-x for a game, /sims/ps2 in a collection ---------- */
  // Every change is a step in the browser's history, so Back and Forward go through them: opening a game,
  // picking a console or a collection, jumping to a checklist's section. Going from game to game in the
  // middle doesn't add steps, and closing a game opened here goes Back to where it was opened.
  const BASE = SITE.base || '/';
  const pathOf = (col, id) => BASE + [col, id].filter(Boolean).map(encodeURIComponent).join('/');
  // the collection in the address, and the console, game or checklist section after it
  function readPath() {
    const path = location.pathname.startsWith(BASE) ? location.pathname.slice(BASE.length) : '';
    const parts = path.split('/').filter(Boolean).map(p => { try { return decodeURIComponent(p); } catch (e) { return p; } });
    const col = COLLECTIONS[parts[0]] ? parts.shift() : '';
    return { col, id: parts[0] || '' };
  }
  // the old addresses, ?sims#ps2-final-fantasy-x, #wiiu, go to the new ones
  function upgradeOldPath() {
    const q = new URLSearchParams(location.search), h = location.hash.slice(1);
    const col = Object.keys(COLLECTIONS).find(k => q.has(k));
    if (!col && !h) return;
    try { history.replaceState(null, '', pathOf(col || readPath().col, decodeURIComponent(h) || readPath().id)); } catch (e) { /* some viewers refuse it */ }
  }
  // what the address says with nothing open: the one console picked, if one is
  const here = () => state.sel.size === 1 ? [...state.sel][0] : '';
  // a game or a checklist section in the collection showing (js/collection.js links its cases with it)
  Collection.href = id => pathOf(COL, id);
  let quiet = false; // going Back or Forward: the address is already right
  // push adds a step, else this one is rewritten; a game opened by a step of its own is marked, so closing
  // it can go Back (one the address opened isn't)
  function setPath(id, push, closing) {
    if (quiet) return;
    try {
      const url = pathOf(COL, id);
      const st = { sel: [...state.sel], game: byId.has(id) && (push || !!history.state?.game) };
      if (closing && history.state && history.state.game) history.back();
      else if (push && url !== location.pathname) history.pushState(st, '', url);
      else history.replaceState(st, '', url);
    } catch (e) { /* some viewers refuse it */ }
  }
  // a checklist section: the collection's checklist, scrolled to it
  function showSection(id) {
    if (!VIEWS.includes('checklist') || !id) return false;
    const was = state.view;
    if (was !== 'checklist') { state.view = 'checklist'; sync(); render(false); }
    const sec = document.getElementById(id);
    if (!sec || !sec.closest('#checklist')) {
      if (was !== 'checklist') { state.view = was; sync(); render(false); } // not one of its sections
      return false;
    }
    sec.scrollIntoView();
    return true;
  }
  document.addEventListener('click', e => {
    const a = e.target.closest('a[data-section]');
    if (!a || e.metaKey || e.ctrlKey || e.shiftKey || e.button) return;
    e.preventDefault();
    if (showSection(a.dataset.section)) setPath(a.dataset.section, true);
  });
  const sameSel = ids => ids.length === state.sel.size && ids.every(id => state.sel.has(id));
  // Back or Forward: the page shows what that step's address says
  addEventListener('popstate', e => {
    if (!DB.length) return;
    const { col, id } = readPath();
    const g = byId.get(id);
    if (col === COL && g) { if (Detail.current()?.id !== id) Detail.open(g, null, true); return; }
    if (Detail.isOpen()) Detail.close(true);
    // the consoles picked then, or the one in the address
    const sel = e.state?.sel || (CONSOLE_BY_ID[id] ? [id] : []);
    const go = () => { quiet = true; try { show(); } finally { quiet = false; } };
    const show = () => {
      const moved = col !== COL;
      if (moved) setCollection(col);
      const ids = sel.filter(c => ALL.some(x => x.console === c));
      if (moved || !sameSel(ids)) {
        state.sel = new Set(ids);
        changed();
        showPicked();
      }
      if (moved) scrollTo(0, 0);
      if (g) {
        // a game in the collection gone back to
        if (!games.includes(g)) { state.sel = new Set(); changed(); }
        setTimeout(() => Detail.open(g, null, true), moved ? 300 : 0);
      } else if (id && !CONSOLE_BY_ID[id]) showSection(id);
    };
    if (col !== COL && document.startViewTransition && !reduced()) document.startViewTransition(go); else go();
  });

  /* ---------- the collections: the whole shelf, or one series in its own look ---------- */
  // /sims or /gta in the address picks one; the top bar's links pick one in place
  const SHELF = { title: document.title, icon: $('link[rel="icon"]')?.getAttribute('href'), about: $('meta[name="description"]')?.content };
  function setCollection(col) {
    const root = document.documentElement;
    if (COL) root.classList.remove('collection', COL);
    COL = COLLECTIONS[col] ? col : '';
    CFG = COLLECTIONS[COL] || null;
    if (COL) root.classList.add('collection', COL);
    ALL = CFG ? LISTS[COL] : DB;
    GROUP = CFG ? 'group' : 'console';
    groupOf = CFG ? CFG.group : byConsole;
    MENU.console.key = GROUP;
    MENU.console.label = CFG ? `By ${CFG.groupNoun}` : 'By console';
    VIEWS = [...SITE_VIEWS, ...(CFG && CFG.checklist ? ['checklist'] : [])];
    if (!VIEWS.includes(state.view)) state.view = DEFAULT_VIEW;
    // sorted by console on the shelf is sorted by game in a collection, and back
    const s = state.sort || {};
    state.sort = validSort(s.key === 'console' || s.key === 'group' ? { key: GROUP, dir: s.dir } : s);
    // the consoles picked stay picked where the collection has games on them
    state.sel = new Set([...state.sel].filter(id => ALL.some(g => g.console === id)));
    if (state.lib && !ALL.some(g => g.console === 'pc' && inLib(g, state.lib))) state.lib = '';
    document.title = CFG ? `${CFG.title} · ${SHELF.title}` : SHELF.title;
    $('link[rel="icon"]')?.setAttribute('href', CFG ? CFG.icon : SHELF.icon);
    $('meta[name="description"]')?.setAttribute('content', CFG ? CFG.about : SHELF.about);
    $$('.topnav [data-col]').forEach(a => a.dataset.col === COL ? a.setAttribute('aria-current', 'page') : a.removeAttribute('aria-current'));
    hero();
  }
  // a click on the top bar's links changes the collection where it stands, fading from one look to the other
  $$('.topnav [data-col], .brand').forEach(a => a.addEventListener('click', e => {
    if (e.metaKey || e.ctrlKey || e.shiftKey || e.button || !DB.length) return; // a new tab or window
    e.preventDefault();
    const col = a.dataset.col || '';
    if (col === COL) return;
    const go = () => {
      setCollection(col);
      changed(); // a step in the history: /sims
      showPicked();
      scrollTo(0, 0);
    };
    if (document.startViewTransition && !reduced()) document.startViewTransition(go); else go();
  }));

  /* A copy's PSN play history and trophies. A copy with more than one game in it (a trilogy) is tied to
     each one's, and gets them added up. */
  function psnOf(g, psn) {
    const ts = [g.psnTitleId || []].flat().map(id => psn.titles[id]).filter(Boolean);
    const ss = [g.psnTrophySet || []].flat().map(id => psn.trophySets[id]).filter(Boolean);
    if (!ts.length && !ss.length) return null;
    const sum = (xs, f) => xs.reduce((a, x) => a + (f(x) || 0), 0);
    const times = k => ts.map(t => t[k]).filter(Boolean).sort();
    return {
      playtime: ts.length ? sum(ts, t => t.playtime) : null,
      playCount: ts.length ? sum(ts, t => t.playCount) : null,
      firstPlayed: times('firstPlayed')[0] || null,
      lastPlayed: times('lastPlayed').at(-1) || null,
      trophies: ss.length ? {
        total: sum(ss, s => s.total), earned: sum(ss, s => s.earnedTotal), platinum: ss.some(s => s.earned.platinum),
        grades: ['platinum', 'gold', 'silver', 'bronze'].map(k => [k, sum(ss, s => s.earned[k]), sum(ss, s => s.defined[k])]),
      } : null,
    };
  }

  /* A copy's Nintendo play history. A copy with more than one game in it is tied to each one's, added up. */
  function nintendoOf(g, nin) {
    const ts = [g.nintendoTitleId || []].flat().map(id => nin.titles[id]).filter(Boolean);
    if (!ts.length) return null;
    const times = k => ts.map(t => t[k]).filter(Boolean).sort();
    return {
      playtime: ts.reduce((a, t) => a + (t.playtime || 0), 0),
      playDays: ts.reduce((a, t) => a + (t.playDays || 0), 0),
      firstPlayed: times('firstPlayed')[0] || null,
      lastPlayed: times('lastPlayed').at(-1) || null,
    };
  }

  /* A copy's Xbox history and achievements. A copy with more than one game in it is tied to each one's, added up. */
  function xboxOf(g, xbl) {
    const ts = [g.xboxTitleId || []].flat().map(id => xbl.titles[id]).filter(Boolean);
    if (!ts.length) return null;
    const sum = (xs, f) => xs.reduce((a, x) => a + (f(x) || 0), 0);
    const as = ts.map(t => t.achievements).filter(Boolean);
    const timed = ts.filter(t => t.playtime != null);
    return {
      playtime: timed.length ? sum(timed, t => t.playtime) : null,
      lastPlayed: ts.map(t => t.lastPlayed).filter(Boolean).sort().at(-1) || null,
      achievements: as.length ? {
        total: sum(as, a => a.total), earned: sum(as, a => a.earned),
        gamerscore: sum(as, a => a.gamerscore), gamerscoreTotal: sum(as, a => a.gamerscoreTotal),
        recent: as.flatMap(a => a.unlocked).sort((x, y) => (y.date || '').localeCompare(x.date || '')).slice(0, 5),
      } : null,
    };
  }

  /* A copy's EA app library entry: its play time, when it was last played and first owned, and its achievements
     (an add-on has only when it was first owned, until it borrows the rest from its base game, below). */
  function eaOf(g, lib) {
    const e = g.eaSlug && lib.games[g.eaSlug];
    if (!e) return null;
    const a = e.achievements;
    return {
      game: e.kind === 'game',
      playtime: e.playtime,
      lastPlayed: e.lastPlayed,
      since: e.offers.map(o => o.since).filter(Boolean).sort()[0] || null,
      achievements: a && { total: a.total, earned: a.earned, recent: a.unlocked.slice(0, 5) },
    };
  }

  /* A PC copy's Rockstar Games entry: when it was last played on PC and how many achievements were unlocked. */
  function rockstarOf(g, lib) {
    const r = g.rockstarTitle && lib.games[g.rockstarTitle];
    if (!r) return null;
    return { lastPlayed: r.platforms.pc || r.lastPlayed, achievements: r.achievements?.earned || 0 };
  }

  /* ---------- start ---------- */
  // the data comes from the site's API (data/games.json is api/games, data/sims-wants.json api/sims_wants…),
  // with the data's version on it so a browser never keeps a stale copy; without one, from the files themselves
  const src = u => SITE.api ? SITE.api + u.replace(/^data\//, '').replace(/\.json$/, '').replace(/-/g, '_') + '?v=' + (SITE.version || '') : BASE + u;
  // the covers and logos it names are under the site's root, wherever in it the address is (/sims/wiiu)
  const rooted = (k, v) => typeof v === 'string' && /^(covers|logos|assets)\//.test(v) ? BASE + v : v;
  const json = u => fetch(src(u)).then(r => { if (!r.ok) throw new Error(u + ' ' + r.status); return r.text(); }).then(t => JSON.parse(t, rooted));
  const EXTRA = [...new Set(Object.values(COLLECTIONS).flatMap(c => c.data || []))];
  // what Steam says about the games tied to it (steamAppId), written by tools/steam-sync.mjs; the shelf does without it
  // and what PlayStation Network says about the ones tied to it (psnTitleId, psnTrophySet), from tools/psn-sync.mjs,
  // and Nintendo about the Switch ones (nintendoTitleId), from tools/nintendo-sync.mjs, and Xbox Live about the Xbox ones (xboxTitleId), from tools/xbox-sync.mjs,
  // and the EA app about the PC ones in its library (eaSlug), from tools/ea-sync.mjs, and Rockstar about the PC ones played (rockstarTitle), from tools/rockstar-sync.mjs
  const ONLINE = ['data/steam.json', 'data/psn.json', 'data/nintendo.json', 'data/xbox.json', 'data/ea.json', 'data/rockstar.json'].map(u => json(u).catch(() => null));
  Promise.all([...['data/games.json', ...EXTRA].map(json), ...ONLINE])
    .then(([db, ...more]) => {
      const [steam, psn, nintendo, xbox, ea, rockstar] = more.splice(-6);
      const file = Object.fromEntries(EXTRA.map((u, i) => [u, more[i]]));
      // games marked hidden stay in the list, but off every shelf and count
      db.games = db.games.filter(g => !g.hidden);
      DB = db.games;
      DB.forEach(g => {
        byId.set(g.id, g);
        if (g.steamAppId && steam?.games[g.steamAppId]) g.steam = steam.games[g.steamAppId];
        if (psn) g.psn = psnOf(g, psn);
        if (nintendo) g.nintendo = nintendoOf(g, nintendo);
        if (xbox) g.xbox = xboxOf(g, xbox);
        if (ea) g.ea = eaOf(g, ea);
        if (rockstar) g.rockstar = rockstarOf(g, rockstar);
        g._hay = fold([g.title, g.listedAs, g.publisher, g.developer, g.genre, g.released, g.region, REGIONS[g.region]?.std !== g.region && REGIONS[g.region]?.long, ...[g.console, ...(g.alsoOn || [])].flatMap(c => [CONSOLE_BY_ID[c].name, CONSOLE_BY_ID[c].short]), g.note, g.edition, g.platinum && 'Platinum', g.steelbook && 'SteelBook tin', g.series, g.pack, g.packCode, g.mac && 'Mac', g.steam && 'Steam', g.format === 'digital' && STORE[g.console] && 'eShop', ...(g.steam?.store?.genres || []), g.psn?.trophies?.platinum && 'Platinum trophy', g.xbox && 'Xbox Live', inLib(g, 'ea') && 'EA app', g.rockstar && 'Rockstar Games Launcher', ...nicknames(g)].join(' | '));
      });
      // an add-on is played in its game, so every pack of a series (and its other base game copies) shows the play time,
      // last played and achievements of the series' base game in the EA app (or the collection that holds it: The Sims,
      // The Sims 2); it keeps its own owned since, and stays out of the EA app library unless it is in it itself
      const eaBase = new Map();
      DB.filter(g => /^The Sims/.test(g.series) && g.ea?.game).sort((a, b) => (b.pack === 'Base game') - (a.pack === 'Base game'))
        .forEach(g => eaBase.has(g.series) || eaBase.set(g.series, g));
      DB.filter(g => g.pack && eaBase.has(g.series) && !g.ea?.game).forEach(g => {
        const b = eaBase.get(g.series);
        g.ea = { ...b.ea, game: false, since: g.ea?.since || null, owned: !!g.ea, via: b };
      });
      // a bundle holds the copies it came with (contains): they stand inside it, not on the shelf of their own,
      // but the checklist and the counts still have them
      DB.filter(g => g.contains).forEach(g => {
        g.packs = g.contains.map(id => byId.get(id)).filter(Boolean);
        g.packs.forEach(p => { p.bundle = g; });
        g._hay += ' | ' + fold(g.packs.map(p => p.title).join(' | ')); // a search for a pack finds its bundle
      });
      // a copy that plays on more than one console (alsoOn: an Xbox One & Xbox 360 release) stands under each of them,
      // in its own case; it counts once
      DB.filter(g => g.alsoOn).forEach(g => g.alsoOn.forEach(c => {
        const twin = { ...g, id: g.id.replace(/^[^-]+/, c), console: c, caseStyle: g.caseStyle || g.console, twinOf: g.id };
        byId.set(twin.id, twin);
        DB.push(twin);
      }));
      for (const [k, c] of Object.entries(COLLECTIONS)) LISTS[k] = c.games(db, ...(c.data || []).map(u => file[u]));
      shell();
      upgradeOldPath();
      let { col, id: h } = readPath();
      // a game that isn't in the collection in the address opens on the whole shelf
      if (col && byId.has(h) && !LISTS[col].includes(byId.get(h))) col = '';
      setCollection(col);
      if (CONSOLE_BY_ID[h]) state.sel = new Set([h]);
      games = list();
      sync();
      showPicked();
      render(true);
      // a collection's link to one of its checklist's sections (/gta/want-list) opens the checklist there;
      // anything else the address has that isn't a console or a game goes
      const section = h && !byId.has(h) && !CONSOLE_BY_ID[h] && showSection(h);
      setPath(byId.has(h) || section ? h : here(), false);
      if (byId.has(h)) {
        const g = byId.get(h);
        if (!games.includes(g)) { state.sel = new Set(); games = list(); sync(); render(false); }
        setTimeout(() => Detail.open(g, null, true), 300);
      }
      let lw = innerWidth, rt;
      addEventListener('resize', () => { clearTimeout(rt); rt = setTimeout(() => { if (innerWidth !== lw) { lw = innerWidth; render(false); } }, 150); });
    })
    .catch(err => {
      console.error(err);
      host.innerHTML = `<div class="empty-msg"><b>The shelf couldn't load</b>The games didn't load. Try again in a moment.</div>`;
    });
})();
