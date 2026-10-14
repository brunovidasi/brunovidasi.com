/* collection.js — what the collections' checklists (js/sims.js, js/gta.js) share:
   a game's case standing on a stretch of shelf with its name and tags under it, the
   meter of how much of a set is there, and a want-list card. A case opens in the
   spotlight in place (js/app.js). */

(function () {
  const esc = Case.esc;
  const $ = (s, el = document) => el.querySelector(s);
  const byRelease = (a, b) => (a.shelfDate || a.released || '9999').localeCompare(b.shelfDate || b.released || '9999') || a.title.localeCompare(b.title);

  // a DVD case stands the height of the stage, and the rest keep their real size beside it
  const stageH = () => innerWidth < 640 ? 130 : 168;

  /** A game's case and caption, linking to it in the collection. `name` is what the caption calls it.
      A console copy is tagged with its console; `platformTag` false leaves that off, where the
      caption already names the platform. */
  function card(g, name = g.title, platformTag = true) {
    const c = CONSOLE_BY_ID[g.console];
    const a = document.createElement('a');
    a.className = 'cell';
    a.href = '#' + encodeURIComponent(g.id);
    a.dataset.id = g.id; // the shelf opens it in place (js/app.js)
    a.setAttribute('aria-label', `${g.title}, ${c.name}${g.mac ? ' and Mac' : ''}, ${Case.year(g)}`);
    const stage = document.createElement('span');
    stage.className = 'stage';
    const tile = document.createElement('span');
    tile.className = 'gt';
    const s = stageH() / 190;
    if (g.format === 'cartridge-only') tile.append(Case.cart(g, s * 2));
    else {
      // a box built from its parts (g.model) stands as that model, not a picture of its front
      const k = Math.min(s, (stageH() * 0.95) / Case.layout(g, 1).w);
      const face = g.model ? Case.slab(g, k, true) : Case.front(g, k);
      if (g.format === 'digital') face.classList.add('ghosted');
      tile.append(face);
    }
    stage.append(tile);
    a.append(stage);
    const tags = [];
    if (g.packCode) tags.push(`<span class="tag code">${esc(g.packCode)}</span>`);
    if (g.console !== 'pc' && platformTag) tags.push(`<span class="tag">${esc(c.short)}</span>`);
    if (g.format === 'boxed') tags.push('<span class="tag disc">Disc</span>');
    if (g.format === 'cartridge-only') tags.push('<span class="tag disc">Cart</span>');
    if (g.format === 'code-in-box') tags.push('<span class="tag dl">Code</span>');
    if (g.format === 'digital' || g.alsoDigital) {
      const L = LAUNCHERS[g.launcher];
      tags.push(`<span class="tag dl"${L ? ` style="--lb:${L.bg};--lf:${L.fg}"` : ''}>${esc(L && g.launcher !== 'none' ? L.name : 'Digital')}</span>`);
    }
    if (g.mac) tags.push('<span class="tag mac">Mac</span>');
    a.insertAdjacentHTML('beforeend', `<span class="cap"><b title="${esc(g.title)}">${esc(name)}</b><small>${Case.year(g)}${g.edition ? ' · ' + esc(g.edition) : ''}${g.bundle ? ' · in ' + esc(g.bundle.title) : ''}</small><span class="tags">${tags.join('')}</span></span>`);
    return a;
  }

  /** A titled stretch of shelf holding these games. */
  function group(title, gs, name) {
    const grp = document.createElement('div');
    grp.className = 'pgrp';
    grp.innerHTML = `<h3>${esc(title)} <em>${gs.length}</em></h3>`;
    const rack = document.createElement('div');
    rack.className = 'rack';
    gs.forEach(g => rack.append(card(g, name ? name(g) : undefined)));
    grp.append(rack);
    return grp;
  }

  /** How much of a set is there. */
  function meter(have, want, done = 'Complete') {
    const total = have + want, p = total ? Math.round(have / total * 100) : 100;
    return `<div class="meter" aria-label="${have} of ${total} on the list">
      <div class="nums"><b>${have} <span>of ${total}</span></b><span>${want ? `${want} to find` : done}</span></div>
      <div class="track"><i style="--p:${p}%"></i></div></div>`;
  }

  /** One thing on a want list: its title, what it is, and a search for it. */
  function wantCard(w, tag) {
    const q = encodeURIComponent(`${w.title} ${w.platform === 'PC / Mac' ? 'PC' : w.platform}`);
    return `<div class="want">
      <span class="ghostcase" aria-hidden="true">${esc(tag || w.platform)}</span>
      <span><b>${esc(w.title)}</b>
        <small>${[w.pack, Case.date(w.released), w.platform].filter(x => x && x !== 'Unknown').map(esc).join(' · ')}</small>
        ${w.note ? `<small class="note">${esc(w.note)}</small>` : ''}
        <a href="https://www.google.com/search?q=${q}" target="_blank" rel="noopener">Look for it ↗</a></span>
    </div>`;
  }

  /** In a game's drawer: the base game and its packs, a list for each kind of pack as on a checklist,
      the game open in bold. Each opens in its place (js/app.js, data-go); `wants`, still to find, come last, faded.
      `name` is what the list calls each one. */
  const PACKS = ['Base game', 'Expansion Pack', 'Game Pack', 'Stuff Pack', 'Parts Pack', 'Kit', 'Free Pack', 'World', 'Extra', 'Collection'];
  const plural = p => p.replace(/ Pack$/, ' pack') + 's'; // Expansion Pack → Expansion packs
  function packList(g, gs, name, wants = []) {
    const kind = x => x.pack || 'Base game';
    const kinds = [...PACKS, ...new Set(gs.map(kind).filter(k => !PACKS.includes(k)))];
    const code = x => x.packCode ? `<small>${esc(x.packCode)}</small>` : '';
    const item = x => `<li${x === g ? ' class="on" aria-current="true"' : ''}><button type="button" data-go="${esc(x.id)}">${esc(name(x))}</button>${code(x)}</li>`;
    const list = (title, lis, cls = '') => `<div${cls ? ` class="${cls}"` : ''}><p class="dw-sub">${esc(title)} <em>${lis.length}</em></p><ul>${lis.join('')}</ul></div>`;
    return `<div class="dw-packs">${kinds.map(k => [k, gs.filter(x => kind(x) === k).sort(byRelease)]).filter(([, xs]) => xs.length)
      .map(([k, xs]) => list(plural(k), xs.map(item))).join('')}`
      + (wants.length ? list('Still to find', wants.map(w => `<li><span>${esc(name(w))}</span>${code(w)}</li>`), 'find') : '')
      + '</div>';
  }

  window.Collection = { card, group, meter, wantCard, packList, stageH, byRelease, esc, $ };
})();
