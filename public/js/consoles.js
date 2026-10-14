/* consoles.js — every platform the shelf knows about, with its real case size in mm
   (front width × height, depth) so cases are drawn at their true relative size. */

window.FAMILIES = [
  { id: 'playstation', name: 'PlayStation', color: '#3b7be0' },
  { id: 'nintendo',    name: 'Nintendo',    color: '#e8303a' },
  { id: 'pc',          name: 'PC',          color: '#9aa4ad' },
  { id: 'xbox',        name: 'Xbox',        color: '#3aa64a' },
];

/* kind: keep = plastic keep case, jewel = CD jewel case, box = cardboard box.
   Sizes are the case's outside in mm (width × height, depth). A case with a cover
   scan keeps its height and takes its width from the scan (js/cases.js), so the
   scan always shows whole and fills the front.
   media: what is inside. spineArt: the spine takes its colour from the cover.
   hidden: a case style only, never a shelf of its own.
   stack: its loose cartridges stand on their side with their top label facing out, like books, where the
   title shows on a real SNES cartridge, except the one that faces out (js/app.js).
   The order here is the order on the site: PlayStation, Nintendo, PC, then Xbox, each newest first. */
window.CONSOLES = [
  { id: 'ps5',     name: 'PlayStation 5',     short: 'PS5',      fam: 'playstation', w: 135, h: 171, d: 12, kind: 'keep',  media: 'bd',      spineArt: true },
  { id: 'ps4',     name: 'PlayStation 4',     short: 'PS4',      fam: 'playstation', w: 135, h: 171, d: 12, kind: 'keep',  media: 'bd',      spineArt: true },
  { id: 'ps3',     name: 'PlayStation 3',     short: 'PS3',      fam: 'playstation', w: 135, h: 171, d: 12, kind: 'keep',  media: 'bd',      spineArt: true },
  { id: 'ps2',     name: 'PlayStation 2',     short: 'PS2',      fam: 'playstation', w: 135, h: 190, d: 14, kind: 'keep',  media: 'dvd',     spineArt: false },
  { id: 'ps1',     name: 'PlayStation',       short: 'PS1',      fam: 'playstation', w: 142, h: 125, d: 10, kind: 'jewel', media: 'cd',      spineArt: true },
  { id: 'switch2', name: 'Nintendo Switch 2', short: 'Switch 2', fam: 'nintendo',    w: 105, h: 170, d: 11, kind: 'keep',  media: 'switch2', spineArt: true },
  { id: 'switch',  name: 'Nintendo Switch',   short: 'Switch',   fam: 'nintendo',    w: 105, h: 170, d: 11, kind: 'keep',  media: 'switch',  spineArt: false },
  { id: 'wiiu',    name: 'Wii U',             short: 'Wii U',    fam: 'nintendo',    w: 135, h: 190, d: 14, kind: 'keep',  media: 'wiiu',    spineArt: false },
  { id: '3ds',     name: 'Nintendo 3DS',      short: '3DS',      fam: 'nintendo',    w: 136, h: 125, d: 15, kind: 'keep',  media: '3ds',     spineArt: false },
  { id: 'wii',     name: 'Wii',               short: 'Wii',      fam: 'nintendo',    w: 135, h: 190, d: 14, kind: 'keep',  media: 'wii',     spineArt: false },
  { id: 'ds',      name: 'Nintendo DS',       short: 'DS',       fam: 'nintendo',    w: 136, h: 125, d: 15, dClear: 17, kind: 'keep',  media: 'ds',      spineArt: false },
  { id: 'gba',     name: 'Game Boy Advance',  short: 'GBA',      fam: 'nintendo',    w: 128, h: 124, d: 32, kind: 'box',   media: 'gba',     spineArt: true,  stack: true },
  { id: 'gbc',     name: 'Game Boy Color',    short: 'GBC',      fam: 'nintendo',    w: 118, h: 124, d: 30, kind: 'box',   media: 'gbc',     spineArt: true,  stack: true },
  { id: 'snes',    name: 'Super Nintendo',    short: 'SNES',     fam: 'nintendo',    w: 180, h: 128, d: 38, kind: 'box',   media: 'snes',    spineArt: true,  stack: true },
  { id: 'gb',      name: 'Game Boy',          short: 'GB',       fam: 'nintendo',    w: 118, h: 124, d: 30, kind: 'box',   media: 'gb',      spineArt: true,  hidden: true },
  { id: 'pc',      name: 'PC',                short: 'PC',       fam: 'pc',          w: 135, h: 190, d: 14, kind: 'keep',  media: 'dvd',     spineArt: true },
  { id: 'xone',    name: 'Xbox One',          short: 'One',      fam: 'xbox',        w: 135, h: 171, d: 12, kind: 'keep',  media: 'bd',      spineArt: true },
  { id: 'x360',    name: 'Xbox 360',          short: '360',      fam: 'xbox',        w: 135, h: 190, d: 14, kind: 'keep',  media: 'dvd',     spineArt: true },
  { id: 'xbox',    name: 'Xbox',              short: 'Xbox',     fam: 'xbox',        w: 135, h: 190, d: 14, kind: 'keep',  media: 'dvd',     spineArt: false },
];
window.CONSOLE_BY_ID = Object.fromEntries(window.CONSOLES.map(c => [c.id, c]));

/* Big-box PC games are cardboard, much bigger than a DVD case. */
window.BIGBOX = { w: 190, h: 235, d: 50 };

/* The thick plastic PC cases of the early 2000s (EA's PC GAME and PC CD ones): a DVD case's height, twice its depth. */
window.PCBIGCASE = { w: 135, h: 190, d: 28 };

/* Where a PC copy activates — for the PC games still to come. */
window.LAUNCHERS = {
  steam:     { name: 'Steam',           bg: '#171a21', fg: '#66c0f4', glyph: '◉' },
  origin:    { name: 'Origin',          bg: '#f56c2d', fg: '#ffffff', glyph: '◎' },
  ea:        { name: 'EA app',          bg: '#ff4747', fg: '#ffffff', glyph: 'EA' },
  rockstar:  { name: 'Rockstar',        bg: '#fcaf17', fg: '#111111', glyph: 'R★' },
  battlenet: { name: 'Battle.net',      bg: '#148eff', fg: '#ffffff', glyph: '✦' },
  gog:       { name: 'GOG Galaxy',      bg: '#86328a', fg: '#ffffff', glyph: 'G' },
  ubisoft:   { name: 'Ubisoft Connect', bg: '#0070ff', fg: '#ffffff', glyph: 'U' },
  none:      { name: 'DRM-free disc',   bg: '#3a3a3a', fg: '#ffffff', glyph: '◌' },
};

/* The little marks on the PC's library filter: a disc for the boxed copies, then each launcher's logo.
   A launcher without one here shows its glyph on its colours instead. */
window.STORE_LOGOS = {
  disc: `<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="#c3cad5" fill-rule="evenodd" d="M12 1.5a10.5 10.5 0 1 0 0 21a10.5 10.5 0 1 0 0-21zM12 10.3a1.7 1.7 0 1 1 0 3.4a1.7 1.7 0 1 1 0-3.4z"/><path fill="#eef1f6" d="M12 1.5a10.5 10.5 0 0 1 9.1 5.25L15.5 10a4 4 0 0 0-3.5-2z"/><path fill="#d6c6f2" d="M12 22.5a10.5 10.5 0 0 1-9.1-5.25L8.5 14a4 4 0 0 0 3.5 2z"/><circle cx="12" cy="12" r="4" fill="none" stroke="rgba(0,0,0,.2)" stroke-width="1"/><circle cx="12" cy="12" r="10.1" fill="none" stroke="rgba(0,0,0,.25)" stroke-width=".8"/></svg>`,
  steam: `<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="11.5" fill="#1b2838"/><circle cx="15.2" cy="9" r="3.6" fill="none" stroke="#fff" stroke-width="1.6"/><circle cx="15.2" cy="9" r="1.6" fill="#fff"/><path d="M12.3 11.4L8.6 14.6" stroke="#fff" stroke-width="2.2" stroke-linecap="round"/><circle cx="7.6" cy="15.6" r="2.4" fill="#1b2838" stroke="#fff" stroke-width="1.6"/></svg>`,
  ea: `<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="11.5" fill="#ff4747"/><g fill="#fff"><path d="M4.6 8h9.2l-.9 1.6H4.6z"/><path d="M5.8 11.2h4.9l-.9 1.6h-4z"/><path d="M4.6 14.4h5.2l-.9 1.6H4.6z"/><path d="M15.4 8h1.7l3.9 8h-1.9l-.8-1.7h-3.9l.8-1.6h2.3l-1.3-2.8-2.9 6.1h-1.9z"/></g></svg>`,
  rockstar: `<svg viewBox="0 0 24 24" aria-hidden="true"><rect x=".5" y=".5" width="23" height="23" rx="5" fill="#fcaf17"/><text x="3.6" y="19.5" font-family="Arial Black, Arial, sans-serif" font-weight="900" font-size="16" fill="#111">R</text><polygon points="17.5,2.8 18.5,5.62 21.49,5.7 19.12,7.53 19.97,10.4 17.5,8.7 15.03,10.4 15.88,7.53 13.51,5.7 16.5,5.62" fill="#fff" stroke="#111" stroke-width=".9" stroke-linejoin="round"/></svg>`,
};

/* Where a copy was sold: the code printed on the box (AUS, EUR, HOL, UK, USA, JPN) when it is
   known, else just the standard it plays on. std groups them for the filter: a PAL copy wants
   a PAL console, and a 3DS only plays games from its own region. */
window.REGIONS = {
  'AUS':    { name: 'AUS',    long: 'Australia',                 std: 'PAL' },
  'EUR':    { name: 'EUR',    long: 'Europe',                    std: 'PAL' },
  'HOL':    { name: 'HOL',    long: 'Netherlands',               std: 'PAL' },
  'UK':     { name: 'UK',     long: 'United Kingdom',            std: 'PAL' },
  'PAL':    { name: 'PAL',    long: 'PAL — Europe or Australia', std: 'PAL' },
  'USA':    { name: 'USA',    long: 'United States',             std: 'NTSC-U' },
  'NTSC-U': { name: 'NTSC-U', long: 'NTSC-U — the Americas',     std: 'NTSC-U' },
  'JPN':    { name: 'JPN',    long: 'Japan',                     std: 'NTSC-J' },
  'NTSC-J': { name: 'NTSC-J', long: 'NTSC-J — Japan',            std: 'NTSC-J' },
};

window.FORMATS = {
  'boxed':          'Boxed',
  'cartridge-only': 'Cartridge only',
  'digital':        'Digital copy',
  'code-in-box':    'Code in a box',
};
