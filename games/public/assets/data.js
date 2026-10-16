/* data.js — mock collection for the prototypes.
   Sizes are the real case/box sizes in millimetres (front width × height, depth),
   so every view can draw the cases at their true relative scale. */

window.FAMILIES = [
  { id: 'playstation', name: 'PlayStation', color: '#2f6fd8' },
  { id: 'xbox',        name: 'Xbox',        color: '#2ea043' },
  { id: 'nintendo',    name: 'Nintendo',    color: '#e60012' },
  { id: 'pc',          name: 'PC',          color: '#9aa4ad' },
];

/* kind: keep = plastic keep case, jewel = CD jewel case, box = cardboard box.
   media: what is inside when you open it. */
window.CONSOLES = [
  { id: 'ps1',     name: 'PlayStation',          short: 'PS1',      fam: 'playstation', w: 142, h: 125, d: 10, kind: 'jewel', media: 'cd',     year: 1995 },
  { id: 'ps2',     name: 'PlayStation 2',        short: 'PS2',      fam: 'playstation', w: 135, h: 190, d: 14, kind: 'keep',  media: 'dvd',    year: 2000 },
  { id: 'ps3',     name: 'PlayStation 3',        short: 'PS3',      fam: 'playstation', w: 135, h: 170, d: 12, kind: 'keep',  media: 'bd',     year: 2006 },
  { id: 'ps4',     name: 'PlayStation 4',        short: 'PS4',      fam: 'playstation', w: 135, h: 170, d: 12, kind: 'keep',  media: 'bd',     year: 2013 },
  { id: 'ps5',     name: 'PlayStation 5',        short: 'PS5',      fam: 'playstation', w: 135, h: 170, d: 12, kind: 'keep',  media: 'bd',     year: 2020 },
  { id: 'xbox',    name: 'Xbox',                 short: 'Xbox',     fam: 'xbox',        w: 135, h: 190, d: 14, kind: 'keep',  media: 'dvd',    year: 2001 },
  { id: 'x360',    name: 'Xbox 360',             short: '360',      fam: 'xbox',        w: 135, h: 190, d: 14, kind: 'keep',  media: 'dvd',    year: 2005 },
  { id: 'xone',    name: 'Xbox One',             short: 'One',      fam: 'xbox',        w: 135, h: 170, d: 12, kind: 'keep',  media: 'bd',     year: 2013 },
  { id: 'snes',    name: 'Super Nintendo',       short: 'SNES',     fam: 'nintendo',    w: 180, h: 128, d: 38, kind: 'box',   media: 'snes',   year: 1991 },
  { id: 'gbc',     name: 'Game Boy Color',       short: 'GBC',      fam: 'nintendo',    w: 118, h: 124, d: 30, kind: 'box',   media: 'gbc',    year: 1998 },
  { id: 'gba',     name: 'Game Boy Advance',     short: 'GBA',      fam: 'nintendo',    w: 128, h: 124, d: 32, kind: 'box',   media: 'gba',    year: 2001 },
  { id: 'ds',      name: 'Nintendo DS',          short: 'DS',       fam: 'nintendo',    w: 125, h: 137, d: 14, kind: 'keep',  media: 'ds',     year: 2004 },
  { id: '3ds',     name: 'Nintendo 3DS',         short: '3DS',      fam: 'nintendo',    w: 125, h: 137, d: 14, kind: 'keep',  media: '3ds',    year: 2011 },
  { id: 'n3ds',    name: 'New Nintendo 3DS',     short: 'New 3DS',  fam: 'nintendo',    w: 125, h: 137, d: 14, kind: 'keep',  media: '3ds',    year: 2015 },
  { id: 'wii',     name: 'Wii',                  short: 'Wii',      fam: 'nintendo',    w: 135, h: 190, d: 14, kind: 'keep',  media: 'dvd',    year: 2006 },
  { id: 'wiiu',    name: 'Wii U',                short: 'Wii U',    fam: 'nintendo',    w: 135, h: 190, d: 14, kind: 'keep',  media: 'wiiu',   year: 2012 },
  { id: 'switch',  name: 'Nintendo Switch',      short: 'Switch',   fam: 'nintendo',    w: 105, h: 170, d: 11, kind: 'keep',  media: 'switch', year: 2017 },
  { id: 'switch2', name: 'Nintendo Switch 2',    short: 'Switch 2', fam: 'nintendo',    w: 105, h: 170, d: 11, kind: 'keep',  media: 'switch', year: 2025 },
  { id: 'pc',      name: 'PC',                   short: 'PC',       fam: 'pc',          w: 135, h: 190, d: 14, kind: 'keep',  media: 'dvd',    year: 1981 },
];

/* Where a PC copy activates. */
window.LAUNCHERS = {
  steam:    { name: 'Steam',        bg: '#171a21', fg: '#66c0f4', glyph: '◉' },
  origin:   { name: 'Origin',       bg: '#f56c2d', fg: '#ffffff', glyph: '◎' },
  ea:       { name: 'EA app',       bg: '#ff4747', fg: '#ffffff', glyph: 'EA' },
  rockstar: { name: 'Rockstar',     bg: '#fcaf17', fg: '#111111', glyph: 'R★' },
  battlenet:{ name: 'Battle.net',   bg: '#148eff', fg: '#ffffff', glyph: '✦' },
  gog:      { name: 'GOG Galaxy',   bg: '#86328a', fg: '#ffffff', glyph: 'G' },
  ubisoft:  { name: 'Ubisoft Connect', bg: '#0070ff', fg: '#ffffff', glyph: 'U' },
  none:     { name: 'DRM-free disc', bg: '#3a3a3a', fg: '#ffffff', glyph: '◌' },
};

/* Big-box PC games are cardboard, much bigger than a DVD case. */
window.BIGBOX = { w: 190, h: 235, d: 50 };

(function () {
  // console, title, year, publisher, [bg1, bg2, accent], motif, rating, status, condition, hours, stars, extra
  const rows = [
    ['ps1', 'Final Fantasy VII', 1997, 'Square', ['#0d1b2a', '#1b4d3e', '#7fd1ae'], 'moon', 'T', 'completed', 'CIB', 82, 5, { fav: 1, note: 'Black label, all three discs. The one that started it.' }],
    ['ps1', 'Metal Gear Solid', 1998, 'Konami', ['#111417', '#3d4a52', '#c9d3d9'], 'grid', 'M', 'completed', 'CIB', 14, 5, {}],
    ['ps1', 'Crash Bandicoot', 1996, 'Sony', ['#ff8a1c', '#ffcf3d', '#7a2e0b'], 'burst', 'T', 'completed', 'Disc only', 9, 4, {}],
    ['ps1', 'Spyro the Dragon', 1998, 'Sony', ['#3a1d6e', '#a64bd6', '#ffd34d'], 'peaks', 'E', 'completed', 'CIB', 12, 4, {}],
    ['ps1', 'Resident Evil 2', 1998, 'Capcom', ['#1a0505', '#5c0f12', '#e33b3b'], 'city', 'M', 'completed', 'CIB', 11, 5, {}],
    ['ps1', 'Castlevania: Symphony of the Night', 1997, 'Konami', ['#0b0716', '#3a1847', '#d8b46a'], 'moon', 'T', 'completed', 'CIB', 22, 5, { fav: 1 }],
    ['ps1', 'Tony Hawk\'s Pro Skater 2', 2000, 'Activision', ['#0e2a5b', '#1f78c1', '#ffd400'], 'stripes', 'T', 'completed', 'CIB', 30, 4, {}],

    ['ps2', 'Shadow of the Colossus', 2005, 'Sony', ['#c9c2ad', '#6e6957', '#2b2a24'], 'peaks', 'T', 'completed', 'CIB', 13, 5, { fav: 1, note: 'Greatest hits? No — original print with the cardboard sleeve.' }],
    ['ps2', 'Grand Theft Auto: San Andreas', 2004, 'Rockstar', ['#f2e6c8', '#e0a43a', '#1a1a1a'], 'city', 'M', 'completed', 'CIB', 120, 5, {}],
    ['ps2', 'Kingdom Hearts', 2002, 'Square', ['#091a3a', '#2c7be5', '#ffe08a'], 'orb', 'E', 'completed', 'CIB', 45, 4, {}],
    ['ps2', 'God of War', 2005, 'Sony', ['#2a0b06', '#8a1c12', '#f2b23a'], 'burst', 'M', 'completed', 'CIB', 11, 4, {}],
    ['ps2', 'Metal Gear Solid 3: Snake Eater', 2004, 'Konami', ['#1d2513', '#4d5a2a', '#d6c38a'], 'wave', 'M', 'completed', 'CIB', 24, 5, {}],
    ['ps2', 'Okami', 2006, 'Capcom', ['#f4ecd8', '#e5d3b0', '#c4302b'], 'sun', 'T', 'playing', 'CIB', 31, 5, {}],
    ['ps2', 'Jak and Daxter', 2001, 'Sony', ['#1e5a8a', '#58b2e8', '#f2b705'], 'peaks', 'E', 'completed', 'Disc only', 15, 4, {}],
    ['ps2', 'Burnout 3: Takedown', 2004, 'EA', ['#120a00', '#ff6a00', '#ffd27a'], 'stripes', 'T', 'completed', 'CIB', 40, 4, {}],

    ['ps3', 'The Last of Us', 2013, 'Sony', ['#1f2a1c', '#5d6b45', '#e8d9b0'], 'peaks', 'M', 'completed', 'CIB', 17, 5, { fav: 1 }],
    ['ps3', 'Uncharted 2: Among Thieves', 2009, 'Sony', ['#3a2a14', '#c7883b', '#f5e1b5'], 'sun', 'T', 'completed', 'CIB', 12, 5, {}],
    ['ps3', 'Demon\'s Souls', 2009, 'Atlus', ['#0a0a0a', '#2b2b2b', '#c9b27a'], 'moon', 'M', 'completed', 'CIB', 48, 5, {}],
    ['ps3', 'Red Dead Redemption', 2010, 'Rockstar', ['#5c1b0d', '#c75b24', '#1a0d07'], 'sun', 'M', 'completed', 'CIB', 35, 5, {}],
    ['ps3', 'Journey Collector\'s Edition', 2012, 'Sony', ['#c45a1e', '#f2b260', '#fff2d6'], 'peaks', 'E10', 'completed', 'Sealed', 3, 5, {}],

    ['ps4', 'Bloodborne', 2015, 'Sony', ['#0c0c10', '#2c2a3a', '#b8a77a'], 'moon', 'M', 'completed', 'CIB', 64, 5, { fav: 1, note: 'Platinum. Took a whole winter.' }],
    ['ps4', 'God of War', 2018, 'Sony', ['#1c2a33', '#7a9aa8', '#e8eef0'], 'peaks', 'M', 'completed', 'CIB', 30, 5, {}],
    ['ps4', 'Persona 5', 2017, 'Atlus', ['#0a0a0a', '#e3101c', '#ffffff'], 'stripes', 'M', 'completed', 'CIB', 105, 5, {}],
    ['ps4', 'Horizon Zero Dawn', 2017, 'Sony', ['#13304a', '#e46c2b', '#ffe0b0'], 'sun', 'T', 'completed', 'CIB', 40, 4, {}],
    ['ps4', 'Marvel\'s Spider-Man', 2018, 'Sony', ['#0b1530', '#b3121e', '#ffffff'], 'city', 'T', 'completed', 'CIB', 26, 4, {}],

    ['ps5', 'Astro Bot', 2024, 'Sony', ['#0c2b6b', '#2e86ff', '#e8f4ff'], 'orb', 'E10', 'completed', 'CIB', 16, 5, { fav: 1 }],
    ['ps5', 'Elden Ring', 2022, 'Bandai Namco', ['#1a1408', '#6b5524', '#ffd77a'], 'burst', 'M', 'playing', 'CIB', 140, 5, {}],
    ['ps5', 'Demon\'s Souls', 2020, 'Sony', ['#141414', '#5a4a3a', '#d9c39a'], 'moon', 'M', 'backlog', 'Sealed', 0, 0, {}],
    ['ps5', 'Final Fantasy XVI', 2023, 'Square Enix', ['#0b0b12', '#33305a', '#e04a2a'], 'rings', 'M', 'completed', 'CIB', 55, 4, {}],
    ['ps5', 'Ratchet & Clank: Rift Apart', 2021, 'Sony', ['#2a0b4a', '#d4387a', '#ffd23f'], 'grid', 'E10', 'completed', 'CIB', 14, 4, {}],

    ['xbox', 'Halo: Combat Evolved', 2001, 'Microsoft', ['#0d1a14', '#2d5a3d', '#b6e3c0'], 'orb', 'M', 'completed', 'CIB', 20, 5, { fav: 1 }],
    ['xbox', 'Fable', 2004, 'Microsoft', ['#2b1d0e', '#7d5a2a', '#ffe7a8'], 'peaks', 'M', 'completed', 'CIB', 25, 4, {}],
    ['xbox', 'Jade Empire', 2005, 'Microsoft', ['#0b2a22', '#1e7a5f', '#e3c46b'], 'sun', 'M', 'backlog', 'CIB', 2, 0, {}],
    ['xbox', 'Ninja Gaiden Black', 2005, 'Tecmo', ['#0a0a0a', '#3a0a0a', '#e3b23c'], 'diag', 'M', 'abandoned', 'CIB', 6, 3, {}],

    ['x360', 'Halo 3', 2007, 'Microsoft', ['#141c22', '#6c8899', '#ffcc66'], 'sun', 'M', 'completed', 'CIB', 40, 5, {}],
    ['x360', 'Gears of War', 2006, 'Microsoft', ['#0d0d0d', '#5a1010', '#c7c7c7'], 'rings', 'M', 'completed', 'CIB', 12, 4, {}],
    ['x360', 'Mass Effect 2', 2010, 'EA', ['#06121e', '#1e4a6e', '#ff9a3c'], 'orb', 'M', 'completed', 'CIB', 48, 5, { fav: 1 }],
    ['x360', 'Alan Wake', 2010, 'Microsoft', ['#060b14', '#1c3a5a', '#f2e4b0'], 'moon', 'T', 'completed', 'CIB', 13, 4, {}],
    ['x360', 'Forza Horizon', 2012, 'Microsoft', ['#3b1a4a', '#ff7a3c', '#ffe7a0'], 'sun', 'T', 'completed', 'CIB', 33, 4, {}],

    ['xone', 'Forza Horizon 4', 2018, 'Microsoft', ['#11332a', '#e85a2a', '#fff0d0'], 'peaks', 'E', 'completed', 'CIB', 60, 5, {}],
    ['xone', 'Halo 5: Guardians', 2015, 'Microsoft', ['#0a1a24', '#2b6b8a', '#bfe9ff'], 'rings', 'T', 'completed', 'CIB', 10, 3, {}],
    ['xone', 'Cuphead', 2017, 'Studio MDHR', ['#f0e2bf', '#d9b77a', '#c0392b'], 'dots', 'E', 'abandoned', 'CIB', 9, 4, {}],
    ['xone', 'Sunset Overdrive', 2014, 'Microsoft', ['#ff3c7a', '#ffb200', '#00e5ff'], 'burst', 'M', 'completed', 'Disc only', 15, 4, {}],

    ['snes', 'Super Metroid', 1994, 'Nintendo', ['#0a0a12', '#3a2a5a', '#ff7a2a'], 'orb', 'E', 'completed', 'CIB', 8, 5, { fav: 1, note: 'Box has a small crush on the top flap.' }],
    ['snes', 'Chrono Trigger', 1995, 'Square', ['#f2ead0', '#e2c98a', '#2a4a8a'], 'rings', 'E', 'completed', 'CIB', 30, 5, {}],
    ['snes', 'The Legend of Zelda: A Link to the Past', 1992, 'Nintendo', ['#1a1a1a', '#c8a03a', '#ffe48a'], 'burst', 'E', 'completed', 'CIB', 18, 5, {}],
    ['snes', 'Super Mario World', 1991, 'Nintendo', ['#3a9ad9', '#7cd36a', '#ffde3a'], 'peaks', 'E', 'completed', 'Cart only', 12, 5, {}],
    ['snes', 'EarthBound', 1995, 'Nintendo', ['#e64a2a', '#ff9a5a', '#ffffff'], 'dots', 'E', 'completed', 'Big box', 26, 5, { note: 'Big box with the Player\'s Guide.' }],
    ['snes', 'Donkey Kong Country', 1994, 'Nintendo', ['#1a3a0a', '#6aa82a', '#f2c23a'], 'wave', 'E', 'completed', 'CIB', 10, 4, {}],

    ['gbc', 'Pokémon Gold', 2000, 'Nintendo', ['#2a1a00', '#c99a2e', '#fff1b0'], 'sun', 'E', 'completed', 'CIB', 70, 5, {}],
    ['gbc', 'Pokémon Crystal', 2001, 'Nintendo', ['#0a2a3a', '#5ad1e8', '#e8fbff'], 'rings', 'E', 'completed', 'Cart only', 60, 5, {}],
    ['gbc', 'Link\'s Awakening DX', 1998, 'Nintendo', ['#0a3a5a', '#4ab0c8', '#ffe28a'], 'wave', 'E', 'completed', 'CIB', 14, 5, {}],
    ['gbc', 'Wario Land 3', 2000, 'Nintendo', ['#3a1a5a', '#a05ad8', '#ffe03a'], 'burst', 'E', 'backlog', 'CIB', 1, 0, {}],

    ['gba', 'Pokémon Emerald', 2005, 'Nintendo', ['#0a2a14', '#2aa85a', '#c9ffd8'], 'orb', 'E', 'completed', 'CIB', 85, 5, {}],
    ['gba', 'Metroid Fusion', 2002, 'Nintendo', ['#0a0a1a', '#2a4aa8', '#ff5a3a'], 'grid', 'E', 'completed', 'CIB', 7, 5, {}],
    ['gba', 'Advance Wars', 2001, 'Nintendo', ['#1a3a8a', '#e83a2a', '#ffffff'], 'stripes', 'E', 'completed', 'Cart only', 25, 4, {}],
    ['gba', 'Golden Sun', 2001, 'Nintendo', ['#3a1a00', '#e88a1a', '#fff2b0'], 'sun', 'E', 'completed', 'CIB', 32, 4, {}],
    ['gba', 'Fire Emblem', 2003, 'Nintendo', ['#1a0a0a', '#8a1a1a', '#e8c86a'], 'diag', 'E', 'completed', 'CIB', 30, 5, {}],

    ['ds', 'Pokémon HeartGold', 2010, 'Nintendo', ['#2a1a00', '#e8b42a', '#fff4c8'], 'sun', 'E', 'completed', 'CIB', 90, 5, { note: 'With the Pokéwalker, still has a battery.' }],
    ['ds', 'Mario Kart DS', 2005, 'Nintendo', ['#c81a1a', '#ff8a3a', '#ffffff'], 'stripes', 'E', 'completed', 'CIB', 40, 4, {}],
    ['ds', 'Phoenix Wright: Ace Attorney', 2005, 'Capcom', ['#0a1a4a', '#2a5ab8', '#ffe03a'], 'diag', 'T', 'completed', 'CIB', 20, 5, {}],
    ['ds', 'The World Ends with You', 2008, 'Square Enix', ['#0a0a0a', '#e82a8a', '#3affd8'], 'city', 'T', 'completed', 'CIB', 28, 5, { fav: 1 }],
    ['ds', 'Chrono Trigger', 2008, 'Square Enix', ['#f2e8c8', '#c89a4a', '#1a3a8a'], 'rings', 'E10', 'backlog', 'Sealed', 0, 0, {}],

    ['3ds', 'A Link Between Worlds', 2013, 'Nintendo', ['#1a2a4a', '#8a5ad8', '#ffe08a'], 'burst', 'E', 'completed', 'CIB', 16, 5, {}],
    ['3ds', 'Fire Emblem Awakening', 2013, 'Nintendo', ['#0a1a3a', '#2a6ad8', '#ffd83a'], 'diag', 'T', 'completed', 'CIB', 45, 5, {}],
    ['3ds', 'Pokémon X', 2013, 'Nintendo', ['#0a1a3a', '#2a8ae8', '#e8f4ff'], 'rings', 'E', 'completed', 'CIB', 50, 4, {}],
    ['3ds', 'Animal Crossing: New Leaf', 2013, 'Nintendo', ['#5ab8e8', '#8ae86a', '#fff8d0'], 'dots', 'E', 'playing', 'CIB', 210, 5, {}],

    ['n3ds', 'Xenoblade Chronicles 3D', 2015, 'Nintendo', ['#0a1a2a', '#3a7ab8', '#e8f0ff'], 'peaks', 'T', 'backlog', 'CIB', 4, 0, {}],
    ['n3ds', 'Fire Emblem Warriors', 2017, 'Nintendo', ['#1a0a2a', '#d83a3a', '#ffd88a'], 'burst', 'T', 'abandoned', 'CIB', 5, 3, {}],
    ['n3ds', 'Minecraft: New 3DS Edition', 2017, 'Mojang', ['#3a8ad8', '#6ab83a', '#8a5a2a'], 'grid', 'E10', 'backlog', 'Sealed', 0, 0, {}],

    ['wii', 'Super Mario Galaxy', 2007, 'Nintendo', ['#0a0a2a', '#3a2a8a', '#ffd83a'], 'moon', 'E', 'completed', 'CIB', 30, 5, { fav: 1 }],
    ['wii', 'Wii Sports', 2006, 'Nintendo', ['#e8f0f8', '#b8cde0', '#3a8ad8'], 'rings', 'E', 'completed', 'CIB', 60, 4, {}],
    ['wii', 'Xenoblade Chronicles', 2012, 'Nintendo', ['#0a2a3a', '#2a8aa8', '#ff6a3a'], 'peaks', 'T', 'completed', 'CIB', 95, 5, {}],
    ['wii', 'Metroid Prime Trilogy', 2009, 'Nintendo', ['#0a0a0a', '#c8a03a', '#ff7a2a'], 'orb', 'T', 'completed', 'CIB', 50, 5, { note: 'Collector\'s tin + art book.' }],
    ['wii', 'The Legend of Zelda: Twilight Princess', 2006, 'Nintendo', ['#0a1a0a', '#3a4a2a', '#f2d06a'], 'moon', 'T', 'completed', 'CIB', 42, 4, {}],

    ['wiiu', 'Super Mario 3D World', 2013, 'Nintendo', ['#2a8ae8', '#ffd83a', '#e82a2a'], 'stripes', 'E', 'completed', 'CIB', 20, 5, {}],
    ['wiiu', 'Splatoon', 2015, 'Nintendo', ['#1a0a2a', '#ff3a9a', '#c8ff3a'], 'dots', 'E10', 'completed', 'CIB', 70, 4, {}],
    ['wiiu', 'Xenoblade Chronicles X', 2015, 'Nintendo', ['#0a1a2a', '#5a8ab8', '#ffffff'], 'grid', 'T', 'backlog', 'CIB', 6, 0, {}],

    ['switch', 'The Legend of Zelda: Breath of the Wild', 2017, 'Nintendo', ['#3a6a8a', '#8ac8b8', '#f2f0d8'], 'peaks', 'E10', 'completed', 'CIB', 160, 5, { fav: 1 }],
    ['switch', 'Super Mario Odyssey', 2017, 'Nintendo', ['#e82a2a', '#ff8a3a', '#ffffff'], 'sun', 'E10', 'completed', 'CIB', 35, 5, {}],
    ['switch', 'Animal Crossing: New Horizons', 2020, 'Nintendo', ['#5ac8e8', '#a8e86a', '#fff6d0'], 'wave', 'E', 'completed', 'CIB', 300, 5, {}],
    ['switch', 'Hollow Knight', 2018, 'Team Cherry', ['#0a0a12', '#2a3a5a', '#e8f0ff'], 'moon', 'E10', 'playing', 'CIB', 48, 5, {}],
    ['switch', 'Metroid Dread', 2021, 'Nintendo', ['#0a0a14', '#d83a1a', '#ffd83a'], 'diag', 'T', 'completed', 'CIB', 11, 5, {}],
    ['switch', 'Splatoon 3', 2022, 'Nintendo', ['#1a0a2a', '#e8ff3a', '#3a5aff'], 'dots', 'E10', 'playing', 'CIB', 85, 4, {}],

    ['switch2', 'Mario Kart World', 2025, 'Nintendo', ['#e82a2a', '#ffb23a', '#3ac8ff'], 'stripes', 'E', 'playing', 'CIB', 40, 5, { fav: 1 }],
    ['switch2', 'Donkey Kong Bananza', 2025, 'Nintendo', ['#5a2a0a', '#f2c23a', '#ff6a1a'], 'burst', 'E10', 'completed', 'CIB', 25, 5, {}],
    ['switch2', 'Kirby Air Riders', 2025, 'Nintendo', ['#ff8ac8', '#5ac8ff', '#ffffff'], 'rings', 'E', 'backlog', 'Sealed', 0, 0, {}],
    ['switch2', 'Pokémon Legends: Z-A', 2025, 'Nintendo', ['#0a1a1a', '#2ad8a8', '#e8fff8'], 'city', 'E10', 'playing', 'CIB', 22, 4, {}],

    ['pc', 'Half-Life 2', 2004, 'Valve', ['#1a1408', '#e8862a', '#fff0d8'], 'diag', 'M', 'completed', 'Big box', 14, 5, { launcher: 'steam', big: 1, fav: 1, note: 'Collector\'s big box. The first game ever to need Steam.' }],
    ['pc', 'Portal 2', 2011, 'Valve', ['#e8ecf0', '#9ab0c0', '#3a8ae8'], 'rings', 'E10', 'completed', 'CIB', 10, 5, { launcher: 'steam' }],
    ['pc', 'Mass Effect 3', 2012, 'EA', ['#0a0a12', '#3a2a2a', '#ff4a2a'], 'orb', 'M', 'completed', 'CIB', 40, 4, { launcher: 'origin' }],
    ['pc', 'Battlefield 3', 2011, 'EA', ['#1a1a14', '#8a7a4a', '#ff9a2a'], 'city', 'M', 'abandoned', 'CIB', 30, 3, { launcher: 'origin' }],
    ['pc', 'Dragon Age: Inquisition', 2014, 'EA', ['#0a0a0a', '#5a1a14', '#d8b46a'], 'burst', 'M', 'backlog', 'CIB', 8, 0, { launcher: 'origin' }],
    ['pc', 'The Sims 4', 2014, 'EA', ['#0a3a1a', '#3ad85a', '#ffffff'], 'orb', 'T', 'playing', 'CIB', 120, 4, { launcher: 'ea' }],
    ['pc', 'Need for Speed Unbound', 2022, 'EA', ['#0a0a0a', '#ff3a8a', '#3affd8'], 'stripes', 'T', 'backlog', 'Sealed', 0, 0, { launcher: 'ea' }],
    ['pc', 'Grand Theft Auto V', 2015, 'Rockstar', ['#f2e8c8', '#3a8a5a', '#1a1a1a'], 'city', 'M', 'completed', 'CIB', 95, 5, { launcher: 'rockstar' }],
    ['pc', 'Red Dead Redemption 2', 2019, 'Rockstar', ['#5a0a0a', '#c8341a', '#f2e0c0'], 'sun', 'M', 'completed', 'CIB', 110, 5, { launcher: 'rockstar', fav: 1 }],
    ['pc', 'Max Payne 3', 2012, 'Rockstar', ['#0a0a0a', '#3a3a3a', '#e8e8e8'], 'diag', 'M', 'completed', 'CIB', 12, 4, { launcher: 'rockstar' }],
    ['pc', 'The Witcher 3: Wild Hunt', 2015, 'CD Projekt', ['#0a0e14', '#4a5a6a', '#c8a85a'], 'moon', 'M', 'completed', 'CIB', 180, 5, { launcher: 'gog' }],
    ['pc', 'StarCraft II: Wings of Liberty', 2010, 'Blizzard', ['#050a14', '#1a3a6a', '#5ad8ff'], 'grid', 'T', 'completed', 'Big box', 35, 4, { launcher: 'battlenet', big: 1 }],
    ['pc', 'Assassin\'s Creed II', 2009, 'Ubisoft', ['#e8e4dc', '#9a8a7a', '#8a1a1a'], 'peaks', 'M', 'completed', 'CIB', 22, 4, { launcher: 'ubisoft' }],
    ['pc', 'Diablo II', 2000, 'Blizzard', ['#0a0505', '#5a0a0a', '#ff6a1a'], 'burst', 'M', 'completed', 'Big box', 90, 5, { launcher: 'none', big: 1 }],
  ];

  const titleFonts = ['serif', 'sans', 'heavy', 'serif', 'sans'];
  let added = new Date('2026-09-30').getTime();

  window.GAMES = rows.map((r, i) => {
    const [c, t, y, pub, col, motif, esrb, status, cond, hours, stars, extra] = r;
    added -= (3 + (i * 7919) % 40) * 86400000;
    return Object.assign({
      id: i + 1,
      console: c,
      title: t,
      year: y,
      publisher: pub,
      colors: col,
      motif,
      esrb,
      status,
      condition: cond,
      hours,
      stars,
      region: 'NTSC-U',
      font: titleFonts[(t.length + i) % titleFonts.length],
      added: new Date(added).toISOString().slice(0, 10),
    }, extra || {});
  });

  window.CONSOLE_BY_ID = Object.fromEntries(window.CONSOLES.map(c => [c.id, c]));
  window.STATUS = {
    completed: { name: 'Completed', color: '#5ac88a' },
    playing:   { name: 'Playing',   color: '#5ab0ff' },
    backlog:   { name: 'Backlog',   color: '#c99a2e' },
    abandoned: { name: 'On hold',   color: '#9a8a7a' },
  };
})();
