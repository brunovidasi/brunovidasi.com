/* admin.js — what the admin's pages do in the browser: the menu on a phone, the filters that apply as
   they change, the confirmations, the colour pickers, the synced fields that stop syncing once changed,
   Find cover, the dashboard's Sync buttons, and the tables' sorting and column picker. Every page works without it, but slower. */

(function () {
  const $ = (s, el = document) => el.querySelector(s);
  const $$ = (s, el = document) => [...el.querySelectorAll(s)];
  const csrf = $('meta[name="csrf-token"]')?.content || '';
  let dirty = false; // a game's edit page has changes not saved yet
  const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

  // a POST to the admin's own endpoints, answered in JSON
  async function post(url, data) {
    const body = new FormData();
    Object.entries(data).forEach(([k, v]) => body.append(k, v));
    body.append('csrf_token', csrf);
    const r = await fetch(url, { method: 'POST', body, credentials: 'same-origin', headers: { Accept: 'application/json' } });
    let json = null;
    try { json = await r.json(); } catch (e) { /* not JSON: a PHP error page */ }
    if (!json) throw new Error(`The server answered ${r.status} without saying why. The PHP error log has it.`);
    if (!r.ok && json.error) throw new Error(json.error);
    return json;
  }

  /* ---------- the menu on a phone ---------- */
  const toggle = $('.menu-toggle');
  toggle?.addEventListener('click', () => {
    const open = toggle.getAttribute('aria-expanded') !== 'true';
    toggle.setAttribute('aria-expanded', open);
    document.body.classList.toggle('nav-open', open);
  });

  /* ---------- filters that apply as they change ---------- */
  $$('form[data-autosubmit]').forEach(form => {
    let t;
    form.addEventListener('change', e => { if (e.target.matches('select')) form.requestSubmit(); });
    $$('input[type="search"]', form).forEach(input => input.addEventListener('input', () => {
      clearTimeout(t);
      t = setTimeout(() => form.requestSubmit(), 450);
    }));
  });
  // the search box keeps its place after the page comes back
  const q = $('form[data-autosubmit] input[type="search"]');
  if (q && q.value) { q.focus(); q.setSelectionRange(q.value.length, q.value.length); }

  /* ---------- confirmations ---------- */
  document.addEventListener('click', e => {
    const b = e.target.closest('[data-confirm]');
    if (b && !confirm(b.dataset.confirm)) e.preventDefault();
  });

  /* ---------- colours: the picker and the text box agree ---------- */
  $$('input[type="color"][data-color-for]').forEach(picker => {
    const text = document.getElementById(picker.dataset.colorFor);
    if (!text) return;
    picker.addEventListener('input', () => { text.value = picker.value; text.dispatchEvent(new Event('input', { bubbles: true })); });
    text.addEventListener('input', () => { if (/^#[0-9a-f]{6}$/i.test(text.value)) picker.value = text.value; });
  });

  /* ---------- copy a value ---------- */
  $$('[data-copy]').forEach(b => b.addEventListener('click', async () => {
    const input = document.getElementById(b.dataset.copy);
    input.select();
    try { await navigator.clipboard.writeText(input.value); b.textContent = 'Copied'; } catch (e) { document.execCommand('copy'); }
    setTimeout(() => { b.textContent = 'Copy'; }, 1600);
  }));

  /* ---------- the "Send to my shelf" bookmark's sign-in, brought here in the address's #fragment ---------- */
  const sent = location.hash.match(/^#(psn|nintendo)=(.+)$/);
  if (sent) {
    // out of the address at once, so it stays in no history or bookmark
    history.replaceState(null, '', location.pathname + location.search + '#' + sent[1]);
    const section = document.getElementById(sent[1]);
    const input = section && $(sent[1] === 'psn' ? 'input[name="npsso"]' : 'input[name="link"]', section);
    if (input) {
      input.value = decodeURIComponent(sent[2]);
      const details = input.closest('details');
      if (details) details.open = true;
      section.scrollIntoView({ block: 'center' });
      const form = input.closest('form');
      form.requestSubmit($('button[value="connect"]', form));
    }
  }

  /* ---------- a game's edit page ---------- */
  const form = $('#gameForm');
  if (form) {
    // a synced field changed by hand stops being synced, unless it is ticked again
    $$('.field-sync').forEach(box => {
      const field = box.closest('.field') || box.closest('.cover-card');
      const keep = $('input[type="checkbox"]', box);
      const inputs = box.dataset.field === 'cover'
        ? $$('input[name^="cover_"], input[name="colors[]"], textarea[name="cover_more"]', field)
        : $$('input, select, textarea', field).filter(i => !box.contains(i));
      const changed = () => {
        if (box.classList.contains('changed')) return;
        box.classList.add('changed');
        if (keep && keep.checked) {
          keep.checked = false;
          const note = document.createElement('span');
          note.className = 'note';
          note.textContent = 'Changed by hand: it stays as you leave it. Tick to let syncs update it again.';
          box.append(note);
        }
      };
      inputs.forEach(i => i.addEventListener(i.type === 'file' ? 'change' : 'input', changed));
    });
    // "Use it": the synced value back in its box
    $$('[data-use]').forEach(b => b.addEventListener('click', () => {
      const input = document.getElementById(b.dataset.use);
      input.value = b.dataset.value;
      input.dispatchEvent(new Event('input', { bubbles: true }));
    }));
    // a synced number changed by hand is yours; set back to the synced value, it is the platform's again
    $$('.synced-field').forEach(f => {
      const input = $('input', f), mode = $('select.mode', f);
      input.addEventListener('input', () => {
        if (!mode.value && input.value !== input.dataset.synced) { mode.value = 'keep'; f.classList.add('overridden'); }
      });
      mode.addEventListener('change', () => {
        f.classList.toggle('overridden', !!mode.value);
        if (!mode.value) input.value = input.dataset.synced;
      });
    });
    // an unsaved change isn't lost by leaving the page
    form.addEventListener('input', () => { dirty = true; });
    form.addEventListener('submit', () => { dirty = false; });
    addEventListener('beforeunload', e => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
  }

  /* ---------- Find cover ---------- */
  const coverCard = $('.cover-card');
  const findBtn = $('[data-find-cover]');
  if (coverCard && findBtn) {
    const out = $('[data-results]', coverCard);
    findBtn.addEventListener('click', async () => {
      findBtn.disabled = true;
      findBtn.textContent = 'Looking…';
      out.hidden = false;
      out.innerHTML = '<p class="muted small">Asking libretro, TheGamesDB, the wikis… The first time on a console takes longer: its list is fetched.</p>';
      try {
        const res = await post(coverCard.dataset.find, { action: 'find', id: coverCard.dataset.id });
        out.innerHTML = res.covers.length
          ? `<div class="cover-grid">${res.covers.map((c, i) => `
              <figure>
                <img src="${esc(c.url)}" alt="" loading="lazy" referrerpolicy="no-referrer">
                <figcaption><b>${esc(c.source)}</b>${c.offRegion ? ' <span class="pill warn">other region</span>' : ''}<span>${esc(c.ref)}</span></figcaption>
                <button type="button" class="small" data-pick="${i}">Use this</button>
              </figure>`).join('')}</div>`
          : '<p class="muted small">Nothing found. Set "Cover match" (under Cover) to steer it, or upload one.</p>';
        if (res.errors?.length) out.insertAdjacentHTML('beforeend', `<p class="error-text small">Couldn't ask everyone: ${esc(res.errors.join('; '))}</p>`);
        $$('[data-pick]', out).forEach(b => b.addEventListener('click', async () => {
          const c = res.covers[+b.dataset.pick];
          if (dirty && !confirm('Save the cover now? Your other changes on this page are lost unless you save them first.')) return;
          b.disabled = true;
          b.textContent = 'Saving…';
          try {
            await post(coverCard.dataset.find, { action: 'apply', id: coverCard.dataset.id, url: c.url, source: c.source, ref: c.ref });
            dirty = false;
            location.reload();
          } catch (err) {
            b.disabled = false;
            b.textContent = 'Use this';
            alert(err.message);
          }
        }));
      } catch (err) {
        out.innerHTML = `<p class="error-text small">${esc(err.message)}</p>`;
      }
      findBtn.disabled = false;
      findBtn.textContent = 'Find cover again';
    });
  }

  /* ---------- the dashboard's syncs ---------- */
  const board = $('#syncBoard');
  if (board) {
    const endpoint = board.dataset.endpoint;
    async function sync(tile) {
      const button = $('[data-sync]', tile), out = $('[data-out]', tile);
      const platform = tile.dataset.platform;
      button.disabled = true;
      tile.classList.add('busy');
      tile.classList.remove('failed', 'done');
      out.innerHTML = '<span class="spinner" aria-hidden="true"></span> Syncing… this takes up to a couple of minutes.';
      try {
        const res = await post(endpoint, { platform });
        if (res.status === 'error') throw Object.assign(new Error(res.message), { res });
        tile.classList.add('done');
        out.innerHTML = `${esc(res.message)}${res.run_url ? ` <a href="${esc(res.run_url)}">What changed</a>` : ''}`;
        if (res.last) $('[data-last]', tile).textContent = res.last;
      } catch (err) {
        tile.classList.add('failed');
        out.innerHTML = `<span class="error-text">${esc(err.message)}</span>${err.res?.run_url ? ` <a href="${esc(err.res.run_url)}">Log</a>` : ''}`;
      }
      tile.classList.remove('busy');
      button.disabled = false;
    }
    $$('[data-sync]', board).forEach(b => b.addEventListener('click', () => sync(b.closest('.sync-tile'))));
    $('[data-sync-all]', board)?.addEventListener('click', async e => {
      const all = e.currentTarget;
      all.disabled = true;
      // one after the other: the platforms don't mind, and nor does a small host
      for (const tile of $$('.sync-tile[data-connected="1"]', board)) await sync(tile);
      all.disabled = false;
    });
  }
  /* ---------- tables: sort by any column, and pick the columns ---------- */
  // A table with data-sort sorts by a click on a column's heading (again for the other way). A cell's
  // data-sort, when it has one, is what it sorts by (an ISO date, minutes, a console's place); empty
  // cells go last either way. The choice is kept for the visit, so it holds as the filters change.
  const store = {
    get(k) { try { return JSON.parse(sessionStorage.getItem(k)); } catch (e) { return null; } },
    set(k, v) { try { sessionStorage.setItem(k, JSON.stringify(v)); } catch (e) { /* private window */ } },
    getLocal(k) { try { return JSON.parse(localStorage.getItem(k)); } catch (e) { return null; } },
    setLocal(k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) { /* private window */ } },
  };
  const collator = new Intl.Collator(undefined, { numeric: true, sensitivity: 'base' });

  $$('table[data-sort]').forEach((table, n) => {
    const key = `sort:${location.pathname}:${table.dataset.sort || n}`;
    const heads = $$('thead th', table);
    const body = table.tBodies[0];
    const value = (row, i) => {
      const cell = row.cells[i];
      if (!cell) return '';
      return (cell.dataset.sort ?? cell.textContent).trim();
    };
    function sortBy(i, dir) {
      const rows = [...body.rows];
      rows.sort((a, b) => {
        const x = value(a, i), y = value(b, i);
        if (x === '' || y === '') return (x === '') - (y === '');
        const nx = Number(x), ny = Number(y);
        const c = !isNaN(nx) && !isNaN(ny) ? nx - ny : collator.compare(x, y);
        return dir === 'desc' ? -c : c;
      });
      body.append(...rows);
      heads.forEach((th, j) => th.setAttribute('aria-sort', j === i ? (dir === 'desc' ? 'descending' : 'ascending') : 'none'));
    }
    heads.forEach((th, i) => {
      if (th.textContent.trim() === '' || th.hasAttribute('data-nosort')) return;
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'sort';
      button.innerHTML = `${th.innerHTML}<span class="arrows" aria-hidden="true"></span>`;
      th.replaceChildren(button);
      th.setAttribute('aria-sort', 'none');
      button.addEventListener('click', () => {
        // numbers and dates read best biggest first; words A to Z
        const first = th.dataset.first || 'asc';
        const now = th.getAttribute('aria-sort');
        const dir = now === 'none' ? first : now === 'ascending' ? 'desc' : 'asc';
        sortBy(i, dir);
        store.set(key, { i, dir });
      });
    });
    const saved = store.get(key);
    if (saved && heads[saved.i]?.querySelector('.sort')) sortBy(saved.i, saved.dir);
  });

  // A table with data-columns gets a Columns menu in the element its value names. A heading with
  // data-col names its column; data-off hides it until it is picked. The choice is kept for good.
  $$('table[data-columns]').forEach(table => {
    const host = document.getElementById(table.dataset.columns);
    if (!host) return;
    const key = `columns:${location.pathname}`;
    const heads = $$('thead th', table);
    const saved = store.getLocal(key) || {};
    const cols = heads.map((th, i) => ({ th, i, name: th.dataset.col })).filter(c => c.name);
    const show = (c, on) => {
      c.th.hidden = !on;
      for (const row of table.tBodies[0].rows) if (row.cells[c.i]) row.cells[c.i].hidden = !on;
    };
    const menu = document.createElement('details');
    menu.className = 'col-picker';
    menu.innerHTML = '<summary>Columns</summary><div class="col-list"></div>';
    cols.forEach(c => {
      const on = c.name in saved ? saved[c.name] : !c.th.hasAttribute('data-off');
      show(c, on);
      const label = document.createElement('label');
      label.className = 'check';
      label.innerHTML = `<input type="checkbox"${on ? ' checked' : ''}> <span>${esc(c.th.textContent.trim())}</span>`;
      label.firstChild.addEventListener('change', e => {
        show(c, e.target.checked);
        saved[c.name] = e.target.checked;
        store.setLocal(key, saved);
      });
      $('.col-list', menu).append(label);
    });
    host.append(menu);
    document.addEventListener('click', e => { if (!menu.contains(e.target)) menu.open = false; });
  });
})();
