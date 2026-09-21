/* lib.js — shared by prototypes 6–11.
   Loads your whole Discogs collection (vinyl AND CDs/DVDs), turns each record's formats into
   a list of physical discs, and renders sleeves whose discs slide out on hover. */
const L = (() => {
  const CACHE = 'proto_all_v2', TTL = 12 * 3600e3, USER = 'brunovidasi';
  const S = { recs: [], fmt: 'all', q: '', onChange: null };
  let uid = 0;

  const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const hash = s => { let h = 0; for (const c of String(s)) h = (h * 31 + c.charCodeAt(0)) >>> 0; return h; };
  const byArtist = (a, b) => a.artist.localeCompare(b.artist) || a.year - b.year;

  /* ---------- data ---------- */
  const COLORS = [['red', '#c8322b'], ['pink', '#f06aa8'], ['blue', '#2f6fdd'], ['aqua', '#3cc6d4'], ['turquoise', '#2bc0b4'], ['green', '#2f9e58'],
    ['yellow', '#f0c828'], ['orange', '#ee7d1e'], ['purple', '#7a3fb5'], ['violet', '#7a3fb5'], ['gold', '#d4a836'], ['silver', '#b8bcc2'],
    ['grey', '#8a8d92'], ['gray', '#8a8d92'], ['smoky', '#6c6f74'], ['white', '#f1f1ec'], ['brown', '#7a4b2a'], ['clear', '#dfe8ec'], ['black', '#131313']];
  function vinylColor(text) {
    const t = (text || '').toLowerCase();
    for (const [w, c] of COLORS) if (t.includes(w)) return { c, tr: /transl|transp|clear|smoky/.test(t) };
    return { c: null, tr: false };
  }

  function norm(r) {
    const b = r.basic_information, fm = b.formats || [], discs = [];
    for (const f of fm) {
      const d = f.descriptions || [], n = Math.min(3, Number(f.qty) || 1);
      for (let i = 0; i < n; i++) {
        if (f.name === 'Vinyl') discs.push({ t: 'v', ...vinylColor(f.text), pic: d.includes('Picture Disc'), sz: d.includes('7"') ? 7 : d.includes('10"') ? 10 : 12 });
        else if (f.name === 'CD') discs.push({ t: 'cd' });
        else if (f.name === 'DVD' || f.name === 'Blu-ray') discs.push({ t: 'dvd' });
      }
    }
    if (!discs.length) discs.push({ t: 'cd' });
    const first = fm[0] || {};
    return {
      id: r.instance_id || r.id,
      title: (b.title || '').trim(),
      artist: (b.artists || []).map(a => a.name.replace(/\s\(\d+\)$/, '')).join(', ') || 'Unknown',
      year: b.year || 0,
      cover: b.cover_image || b.thumb || '',
      thumb: b.thumb || b.cover_image || '',
      label: ((b.labels || [])[0] || {}).name || '',
      added: r.date_added || '',
      genres: b.genres || [], styles: b.styles || [],
      discs: discs.slice(0, 3),
      box: fm.some(f => f.name === 'Box Set'),
      kind: discs.some(d => d.t === 'v') ? 'vinyl' : discs.some(d => d.t === 'cd') ? 'cd' : 'dvd',
      fmt: [first.name, (first.descriptions || [])[0], (first.text || '').trim()].filter(Boolean).join(' · '),
    };
  }

  async function fetchAll(onProg) {
    const out = []; let page = 1, pages = 1, complete = true;
    do {
      const res = await fetch(`https://api.discogs.com/users/${USER}/collection/folders/0/releases?page=${page}&per_page=100`);
      if (!res.ok) { if (!out.length) throw new Error(res.status); complete = false; break; }
      const j = await res.json();
      out.push(...j.releases); pages = j.pagination.pages;
      if (onProg) onProg(page, pages);
      page++;
    } while (page <= pages);
    return { list: out.map(norm), complete };
  }

  const SAMPLE = [['Pink Floyd', 'The Dark Side of the Moon', 1973], ['Miles Davis', 'Kind of Blue', 1959], ['Fleetwood Mac', 'Rumours', 1977], ['Daft Punk', 'Random Access Memories', 2013],
    ['Radiohead', 'OK Computer', 1997], ['Nirvana', 'Nevermind', 1991], ['Björk', 'Homogenic', 1997], ['The Beatles', 'Abbey Road', 1969], ['Lady Gaga', 'The Fame', 2008],
    ['Led Zeppelin', 'IV', 1971], ['Beyoncé', 'Dangerously in Love', 2003], ['Amy Winehouse', 'Back to Black', 2006]];
  const sample = () => SAMPLE.map(([artist, title, year], i) => ({ id: -i - 1, artist, title, year, cover: '', thumb: '', label: '', added: new Date(2024, i, 5).toISOString(), genres: [], styles: [],
    discs: [i % 3 === 0 ? { t: 'v', c: ['#c8322b', '#2f6fdd', '#f06aa8', null][i % 4], tr: i % 2 === 0, sz: 12 } : { t: 'cd' }], box: false, kind: i % 3 === 0 ? 'vinyl' : 'cd', fmt: 'sample' }));

  async function load(onProg) {
    try { const c = JSON.parse(localStorage.getItem(CACHE)); if (c && Date.now() - c.t < TTL && c.d.length) return { recs: c.d, source: 'your collection (cached)' }; } catch (e) { /* no cache */ }
    try {
      const { list, complete } = await fetchAll(onProg);
      if (complete) try { localStorage.setItem(CACHE, JSON.stringify({ t: Date.now(), d: list })); } catch (e) { /* too big */ }
      return { recs: list, source: complete ? 'Discogs' : 'Discogs (partial — rate limited)' };
    } catch (e) { return { recs: sample(), source: 'sample data (Discogs unreachable)' }; }
  }

  /* ---------- filtering + shared controls ---------- */
  const isCD = r => r.kind !== 'vinyl';
  function visible() {
    const q = S.q.toLowerCase();
    return S.recs.filter(r => (S.fmt === 'all' || (S.fmt === 'vinyl') === !isCD(r)) && (!q || (r.artist + ' ' + r.title).toLowerCase().includes(q)));
  }
  function bar(host, onChange, extra = '') {
    S.onChange = onChange;
    const n = { all: S.recs.length, vinyl: S.recs.filter(r => !isCD(r)).length }; n.cd = n.all - n.vinyl;
    host.className = 'bar';
    host.innerHTML = `<div class="chips fmt">${[['all', 'All'], ['vinyl', 'Vinyl'], ['cd', 'CD / DVD']].map(([v, l]) => `<button data-f="${v}" class="${S.fmt === v ? 'on' : ''}">${l}<i>${n[v]}</i></button>`).join('')}</div>
      <input type="search" placeholder="Search…" aria-label="Search" value="${esc(S.q)}">${extra}<span class="cnt"></span>`;
    host.querySelector('.fmt').addEventListener('click', e => {
      const b = e.target.closest('button'); if (!b) return;
      S.fmt = b.dataset.f; host.querySelectorAll('.fmt button').forEach(x => x.classList.toggle('on', x === b)); onChange();
    });
    host.querySelector('input').addEventListener('input', e => { S.q = e.target.value.trim(); onChange(); });
  }
  const setCount = n => { const c = document.querySelector('.bar .cnt'); if (c) c.textContent = `${n} shown`; };

  /* ---------- rendering ---------- */
  function nav(current) {
    const items = [['6-bento.html', '6 Bento'], ['7-floor.html', '7 Floor'], ['8-stacks.html', '8 Stacks'], ['9-stagger.html', '9 Stagger'], ['10-fisheye.html', '10 Fisheye'], ['11-hover-lab.html', '11 Hover lab'], ['12-header.html', '12 Header']];
    const n = document.createElement('nav'); n.className = 'protonav';
    n.innerHTML = items.map(([h, l]) => `<a href="${h}"${h === current ? ' aria-current="page"' : ''}>${l}</a>`).join('') + '<a class="dim" href="1-crate.html">← first set</a>';
    document.body.prepend(n);
  }

  /* A sleeve. Discs are built lazily on first hover, so 500 tiles stay cheap. */
  function tile(r, cls = '', hi = false) {   // hi: use the big cover image (for large tiles)
    const t = document.createElement('div');
    t.className = 'tile ' + cls; t.tabIndex = 0; t._r = r;
    t.setAttribute('aria-label', `${r.title} — ${r.artist}`);
    const h = hash(r.artist + r.title) % 360, src = hi ? (r.cover || r.thumb) : (r.thumb || r.cover);
    t.innerHTML = `<div class="sleeve" style="--fb:linear-gradient(135deg,hsl(${h} 40% 34%),hsl(${(h + 40) % 360} 45% 16%))">${src ? `<img loading="lazy" decoding="async" alt="" src="${esc(src)}">` : ''}</div>`;
    return t;
  }

  function discEl(r, k, { ring = false } = {}) {
    const d = r.discs[k], el = document.createElement('i'), sp = document.createElement('span'), art = r.cover || r.thumb;
    el.className = `d ${d.t === 'v' ? 'v' : d.t}${d.tr ? ' tr' : ''}${d.pic ? ' pic' : ''}${d.plain ? ' plain' : ''}`;
    el.style.setProperty('--k', k);
    el.style.setProperty('--dd', d.t === 'v' ? 0.96 : 0.86);
    el.style.zIndex = String(3 - k);
    if (d.c) el.style.setProperty('--vc', d.c);
    if (art) el.style.setProperty('--art', `url("${art.replace(/"/g, '%22')}")`);
    sp.className = 'sp';
    sp.innerHTML = '<b class="lbl"></b>';
    if (ring) {
      const id = 'ring' + (++uid), s = `${r.artist} · ${r.title} · ${r.year || ''} · `.toUpperCase(), txt = s.repeat(Math.ceil(64 / s.length));
      sp.insertAdjacentHTML('beforeend', `<svg class="ring" viewBox="0 0 100 100"><path id="${id}" d="M50,50 m-42,0 a42,42 0 1,1 84,0 a42,42 0 1,1 -84,0" fill="none"/><text><textPath href="#${id}" textLength="262" lengthAdjust="spacing">${esc(txt)}</textPath></text></svg>`);
    }
    el.append(sp);
    return el;
  }

  const tipEl = document.createElement('div'); tipEl.id = 'tip'; document.body.append(tipEl);
  addEventListener('pointermove', e => { tipEl.style.left = Math.min(e.clientX + 14, innerWidth - 270) + 'px'; tipEl.style.top = (e.clientY + 18) + 'px'; });

  /* Build a tile's discs on first use, pick the side with room, and slide them out. */
  function reveal(t, { ring = false, upgrade = false } = {}) {
    const r = t._r;
    if (!t._discs) { const w = document.createElement('div'); w.className = 'discs'; r.discs.forEach((_, k) => w.append(discEl(r, k, { ring }))); t.prepend(w); t._discs = w; }
    if (upgrade && r.cover) { const im = t.querySelector('img'); if (im && im.src !== r.cover) im.src = r.cover; }
    const rc = t.getBoundingClientRect();
    t.classList.toggle('left', rc.right + rc.width * 0.45 > document.documentElement.clientWidth - 6);
    void t.offsetWidth;            // flush styles so the slide-out transition runs
    t.classList.add('hot');
  }
  const conceal = t => t.classList.remove('hot');
  const showTip = r => { tipEl.innerHTML = `<b>${esc(r.title)}</b>${esc(r.artist)}${r.year ? ' · ' + r.year : ''}<br><span>${esc(r.fmt)}</span>`; tipEl.style.opacity = 1; };

  /* Hover behaviour for every .tile inside host. */
  function wire(host, { delay = () => 0, ring = false, tip = true, upgrade = false } = {}) {
    const on = t => {
      t._on = true; clearTimeout(t._to);
      const ms = delay(t), go = () => { if (t._on) reveal(t, { ring, upgrade }); };
      ms ? (t._to = setTimeout(go, ms)) : go();
      if (tip) showTip(t._r);
    };
    const off = t => { t._on = false; clearTimeout(t._to); conceal(t); if (tip) tipEl.style.opacity = 0; };
    host.addEventListener('pointerover', e => { const t = e.target.closest('.tile'); if (t && host.contains(t) && !t._on) on(t); });
    host.addEventListener('pointerout', e => { const t = e.target.closest('.tile'); if (t && !t.contains(e.relatedTarget)) off(t); });
    host.addEventListener('focusin', e => { const t = e.target.closest('.tile'); if (t && !t._on) on(t); });
    host.addEventListener('focusout', e => { const t = e.target.closest('.tile'); if (t) off(t); });
  }

  return { S, esc, hash, byArtist, load, visible, bar, setCount, nav, tile, discEl, wire, reveal, conceal, tipEl, init: recs => { S.recs = recs; } };
})();
