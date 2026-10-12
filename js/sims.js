/* sims.js — the Sims collection (?sims). Every game in data/games.json with a "series"
   stands on the shelf (js/app.js), grouped by game: The Sims, The Sims 2…, in the
   look of css/sims.css, with a plumbob over the case the pointer is on. Its
   checklist view sorts them by game and then by pack, with how much of each is
   there, and the want list (data/sims-wants.json) of what is still missing. */

(function () {
  const esc = Case.esc;

  // the games in the series, in the order they came out
  const SERIES = [
    { id: 'the-sims',   name: 'The Sims',          blurb: 'The first one, from 2000, and its seven expansions.' },
    { id: 'the-sims-2', name: 'The Sims 2',        blurb: 'The base game, its expansions and its stuff packs.' },
    { id: 'stories',    name: 'The Sims Stories',  blurb: 'Made for laptops, all three of them.' },
    { id: 'the-sims-3', name: 'The Sims 3',        blurb: 'On PC and Mac, on disc and registered in the EA app.' },
    { id: 'medieval',   name: 'The Sims Medieval', blurb: 'The Sims with quests, and its one expansion.' },
    { id: 'the-sims-4', name: 'The Sims 4',        blurb: 'On PC and Mac. Most packs live in the EA app.' },
    { id: 'simcity',    name: 'SimCity',           blurb: 'Where Maxis started.' },
    { id: 'mysims',     name: 'MySims',            blurb: 'The little round Sims.' },
  ].map(s => ({ ...s, key: s.name }));
  const PACKS = ['Base game', 'Collection', 'Expansion Pack', 'Game Pack', 'Stuff Pack', 'Kit', 'Free Pack', 'World', 'Extra'];
  const PACK_PLURAL = { 'Base game': 'Base games', 'Collection': 'Collections', 'Expansion Pack': 'Expansion packs', 'Game Pack': 'Game packs', 'Stuff Pack': 'Stuff packs', 'Kit': 'Kits', 'Free Pack': 'Free packs', 'World': 'Worlds', 'Extra': 'Extras' };
  const { group, meter, wantCard, byRelease, $ } = Collection;
  const PACK_TAG = { 'Expansion Pack': 'EP', 'Game Pack': 'GP', 'Stuff Pack': 'SP', 'Kit': 'Kit', 'Free Pack': 'Free' };

  // inside its own section "The Sims 2: Pets" reads as "Pets"
  const short = g => {
    const t = g.title;
    for (const p of [g.series + ': ', 'The Sims: ', 'SimCity: ']) if (t.startsWith(p) && t.length > p.length) return t.slice(p.length);
    return t;
  };

  let wants = [];
  const countOf = games => ({
    inApp: games.filter(g => g.format === 'digital' || g.alsoDigital).length,
    onDisc: games.filter(g => g.format === 'boxed' || g.format === 'cartridge-only').length,
  });

  function hero(games) {
    const { inApp, onDisc } = countOf(games);
    return `
      <header class="hero">
        <div>
          <div class="eyebrow">Sul sul!</div>
          <h1>The Sims <em>Collection</em><img class="bob" src="assets/plumbob.svg" alt="" width="30" height="60"></h1>
          <p>Every Sims game and pack I have, from the first one in 2000 to The Sims 4, with SimCity and MySims beside them. A solid case is on disc; an outline is in the EA app.</p>
        </div>
        <div class="stats">
          <div><b>${games.length}</b><span>In the collection</span></div>
          <div><b>${onDisc}</b><span>On disc</span></div>
          <div><b>${inApp}</b><span>In the EA app</span></div>
          <div><b>${wants.length}</b><span>Still to find</span></div>
        </div>
      </header>`;
  }

  function checklist(root, games) {
    const pc = games.filter(g => g.console === 'pc');
    const consoles = games.filter(g => g.console !== 'pc');
    const sections = SERIES.map(s => ({ ...s, games: pc.filter(g => g.series === s.key).sort(byRelease), wants: wants.filter(w => w.series === s.key && !/DS|N64/.test(w.platform)) }))
      .filter(s => s.games.length || s.wants.length);
    const consoleWants = wants.filter(w => /DS|N64/.test(w.platform));

    root.innerHTML = `
      <nav class="series-nav" aria-label="Jump to"><div class="in">
        ${sections.map(s => `<a href="#${s.id}">${esc(s.name)} <em>${s.games.length}</em></a>`).join('')}
        ${consoles.length ? `<a href="#consoles">On consoles <em>${consoles.length}</em></a>` : ''}
        <a class="want" href="#want-list">Want list <em>${wants.length}</em></a>
      </div></nav>`;

    sections.forEach(s => {
      const sec = document.createElement('section');
      sec.className = 'series';
      sec.id = s.id;
      sec.innerHTML = `<div class="shead"><div><h2>${esc(s.name)}</h2><p>${esc(s.blurb)}</p></div>${meter(s.games.length, s.wants.length)}</div><div class="packs"></div>`;
      const packs = $('.packs', sec);
      PACKS.forEach(p => {
        const gs = s.games.filter(g => (g.pack || 'Base game') === p);
        if (!gs.length) return;
        packs.append(group(PACK_PLURAL[p], gs, short));
      });
      root.append(sec);
    });

    if (consoles.length) {
      const sec = document.createElement('section');
      sec.className = 'series';
      sec.id = 'consoles';
      sec.innerHTML = `<div class="shead"><div><h2>On consoles</h2><p>The Sims, SimCity and MySims on PlayStation, Xbox and Nintendo, from the shelf.</p></div>${meter(consoles.length, consoleWants.length)}</div><div class="packs"></div>`;
      const packs = $('.packs', sec);
      SERIES.forEach(s => {
        const gs = consoles.filter(g => g.series === s.key).sort((a, b) => byRelease(a, b));
        if (!gs.length) return;
        packs.append(group(s.name, gs, short));
      });
      root.append(sec);
    }

    const sec = document.createElement('section');
    sec.className = 'series wantlist';
    sec.id = 'want-list';
    const groups = [...SERIES.map(s => [s.name, wants.filter(w => w.series === s.key && !/DS|N64/.test(w.platform))]), ['On consoles', consoleWants]].filter(([, ws]) => ws.length);
    sec.innerHTML = `<div class="shead"><div><h2>The <em>want list</em></h2><p>What is on my list but not on the shelf yet, oldest first.</p></div>${meter(games.length, wants.length)}</div>
      <div class="packs">${groups.map(([name, ws]) => {
        const sorted = [...ws].sort((a, b) => PACKS.indexOf(a.pack) - PACKS.indexOf(b.pack) || byRelease(a, b));
        return `<div class="pgrp"><h3>${esc(name)} <em>${ws.length}</em></h3><div class="wants">${sorted.map(w => wantCard(w, w.packCode || PACK_TAG[w.pack])).join('')}</div></div>`;
      }).join('')}</div>`;
    root.append(sec);

  }

  // in the drawer of a PC game: every game and pack of its series on PC, sorted as the checklist
  // sorts them; what is still to find comes last
  function drawer(g, all) {
    if (g.console !== 'pc' || !SERIES.some(s => s.key === g.series)) return null;
    const pc = all.filter(x => x.console === 'pc' && x.series === g.series);
    const ws = wants.filter(w => w.series === g.series && !/DS|N64/.test(w.platform)).sort((a, b) => PACKS.indexOf(a.pack) - PACKS.indexOf(b.pack) || byRelease(a, b));
    return pc.length + ws.length < 2 ? null : { title: `${g.series} on PC`, html: Collection.packList(g, pc, short, ws) };
  }

  (window.COLLECTIONS = window.COLLECTIONS || {}).sims = {
    name: 'The Sims',
    title: 'The Sims Collection',
    icon: 'favicon-sims.svg',
    about: 'Bruno\'s The Sims collection: every game and pack, on disc and in the EA app, and the ones still to find.',
    data: ['data/sims-wants.json'],
    games(db, w) { wants = w.wants; return db.games.filter(g => g.series); },
    group(g) {
      const i = SERIES.findIndex(s => s.key === g.series);
      return { key: SERIES[i].id, label: SERIES[i].name, name: SERIES[i].name, order: i };
    },
    groupNoun: 'game',
    hero,
    checklist,
    drawer,
  };
})();
