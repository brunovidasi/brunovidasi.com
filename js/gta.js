/* gta.js — the Grand Theft Auto collection (?gta), in the look of css/gta.css. data/gta.json lists every game in the series,
   era by era, with the copies on the shelf (ids from data/games.json) and the
   platforms it came out on that aren't here yet. The copies stand on the shelf
   (js/app.js) grouped by game. In the checklist view each game is a row: its copies
   on a stretch of shelf, and each missing version an outline at its case's real
   size. The missing versions are the want list at the bottom. */

(function () {
  const { card, meter, wantCard, esc, $ } = Collection;

  // platforms the shelf has no console for: their case sizes in mm
  const OTHER = {
    psp: { name: 'PSP', short: 'PSP', w: 105, h: 178 },
    xsx: { name: 'Xbox Series X|S', short: 'Series X|S', w: 135, h: 171 },
  };
  const platform = id => CONSOLE_BY_ID[id] || OTHER[id];

  /** An outline where a version still to find would stand, at that case's real size. */
  function missing(title, id) {
    const p = platform(id);
    const s = Collection.stageH() / 190;
    const a = document.createElement('a');
    a.className = 'cell missing';
    a.href = `https://www.google.com/search?q=${encodeURIComponent(`${title} ${p.name}`)}`;
    a.target = '_blank';
    a.rel = 'noopener';
    a.setAttribute('aria-label', `${title} on ${p.name}: still to find. Look for it`);
    a.innerHTML = `<span class="stage"><span class="gt"><span class="ghostbox" style="width:${(p.w * s).toFixed(1)}px;height:${(p.h * s).toFixed(1)}px"><b>${esc(p.short)}</b><i>Still to find</i></span></span></span>
      <span class="cap"><b>${esc(p.name)}</b><small>Look for it ↗</small></span>`;
    return a;
  }

  let data, byId, all, wants;
  const gameOf = new Map(); // a copy's id → its game in data/gta.json

  function hero(copies) {
    const platforms = new Set(copies.map(g => g.console));
    const years = all.map(g => +g.released.slice(0, 4));
    return `
      <header class="hero">
        <div>
          <div class="eyebrow">Vice City</div>
          <h1>Grand Theft <em>Auto</em></h1>
          <p>Every GTA I have, from the top-down original in ${Math.min(...years)} to The Definitive Edition, on every platform I have it on. A dashed outline is a version still to find.</p>
        </div>
        <div class="stats">
          <div><b>${all.length}</b><span>Games</span></div>
          <div><b>${copies.length}</b><span>Copies</span></div>
          <div><b>${platforms.size}</b><span>Platforms</span></div>
          <div><b>${wants.length}</b><span>Still to find</span></div>
        </div>
      </header>`;
  }

  function checklist(root, copies) {
    root.innerHTML = `
      <nav class="series-nav" aria-label="Jump to"><div class="in">
        ${data.eras.map(e => `<a href="#${e.id}">${esc(e.name)} <em>${e.games.length}</em></a>`).join('')}
        <a class="want" href="#want-list">Want list <em>${wants.length}</em></a>
      </div></nav>`;

    data.eras.forEach(e => {
      const have = e.games.reduce((n, g) => n + g.copies.length, 0), want = e.games.reduce((n, g) => n + g.missing.length, 0);
      const sec = document.createElement('section');
      sec.className = 'series';
      sec.id = e.id;
      sec.innerHTML = `<div class="shead"><div><h2>${esc(e.name)}</h2><p>${esc(e.blurb)}</p></div>${meter(have, want, 'Every version')}</div>`;
      e.games.forEach(g => {
        const row = document.createElement('div');
        row.className = 'title-row';
        row.innerHTML = `<div class="tr-head"><span class="yr">${esc(g.released.slice(0, 4))}</span><h3>${esc(g.title)}</h3>
          <small>${g.copies.length} of ${g.copies.length + g.missing.length} ${g.copies.length + g.missing.length === 1 ? 'version' : 'versions'}</small></div>`;
        const rack = document.createElement('div');
        rack.className = 'rack';
        g.copies.map(id => byId.get(id)).filter(Boolean).forEach(c => rack.append(card(c, platform(c.console).name, false)));
        g.missing.forEach(p => rack.append(missing(g.title, p)));
        row.append(rack);
        sec.append(row);
      });
      root.append(sec);
    });

    // the want list: every version still to find, by platform
    const order = [...new Set(wants.map(w => w.p))].sort((a, b) => (platform(a).name).localeCompare(platform(b).name));
    const sec = document.createElement('section');
    sec.className = 'series wantlist';
    sec.id = 'want-list';
    sec.innerHTML = `<div class="shead"><div><h2>The <em>want list</em></h2><p>The versions on my list that aren't on the shelf yet, by platform.</p></div>${meter(copies.length, wants.length)}</div>
      <div class="packs">${order.map(p => {
        const ws = wants.filter(w => w.p === p);
        return `<div class="pgrp"><h3>${esc(platform(p).name)} <em>${ws.length}</em></h3><div class="wants">${ws.map(({ g }) =>
          wantCard({ title: g.title, released: g.released, platform: platform(p).name, note: g.note }, platform(p).short)).join('')}</div></div>`;
      }).join('')}</div>`;
    root.append(sec);
  }

  (window.COLLECTIONS = window.COLLECTIONS || {}).gta = {
    name: 'GTA',
    title: 'Grand Theft Auto',
    icon: 'favicon-gta.svg',
    about: 'Bruno\'s Grand Theft Auto collection: every game on every platform I have it on, and the versions still to find.',
    data: ['data/gta.json'],
    games(db, d) {
      data = d;
      byId = new Map(db.games.map(g => [g.id, g]));
      all = data.eras.flatMap(e => e.games);
      wants = all.flatMap(g => g.missing.map(p => ({ g, p })));
      // a copy with two games in it (Grand Theft Auto & GTA London) is listed under both, and stands on the
      // shelf once, with the first
      all.forEach((g, i) => g.copies.forEach(id => gameOf.has(id) || gameOf.set(id, { ...g, order: i })));
      return [...new Set(all.flatMap(g => g.copies))].map(id => byId.get(id)).filter(Boolean);
    },
    group(c) {
      const g = gameOf.get(c.id);
      return { key: 'gta-' + g.order, label: g.title.replace(/^Grand Theft Auto:? ?/, 'GTA ').replace(/ – .*/, '').trim(), name: g.title, order: g.order };
    },
    groupNoun: 'game',
    hero,
    checklist,
  };
})();
