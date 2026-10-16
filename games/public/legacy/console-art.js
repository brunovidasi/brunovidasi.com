/* console-art.js — a small picture of each console, drawn to read at chip size:
   the console as you'd recognise it on a shelf or a table. All share one 60×40
   frame and the same light from the top left. */

(function () {
  const svg = body => `<svg class="cart-art" viewBox="0 0 60 40" aria-hidden="true">${body}</svg>`;
  const cross = (x, y, s, c) => `<path d="M${x - s} ${y - s / 3}h${s * 2 / 3}v-${s * 2 / 3}h${s * 2 / 3}v${s * 2 / 3}h${s * 2 / 3}v${s * 2 / 3}h-${s * 2 / 3}v${s * 2 / 3}h-${s * 2 / 3}v-${s * 2 / 3}h-${s * 2 / 3}z" fill="${c}"/>`;
  const shine = (x, y, w, h, r = 2) => `<rect x="${x}" y="${y}" width="${w}" height="${h}" rx="${r}" fill="#fff" opacity=".14"/>`;

  window.CONSOLE_ART = {
    // grey box from above: the round lid, the two buttons, the ports at the front
    ps1: svg(`
      <rect x="6" y="7" width="48" height="27" rx="3" fill="#c3c6cc"/>
      <rect x="6" y="28" width="48" height="6" rx="2.5" fill="#a9adb4"/>
      ${shine(7, 8, 46, 4)}
      <circle cx="21" cy="18" r="9" fill="#b4b8be" stroke="#9a9ea6" stroke-width="1"/>
      <circle cx="21" cy="18" r="3.2" fill="#a2a6ad"/>
      <circle cx="40" cy="13" r="2.4" fill="#8f939b"/><circle cx="46.5" cy="13" r="1.7" fill="#8f939b"/>
      <rect x="37.5" y="19" width="11" height="2.2" rx="1.1" fill="#8f939b"/>
      <rect x="11" y="30" width="8" height="2.4" rx="1" fill="#4c4f55"/><rect x="41" y="30" width="8" height="2.4" rx="1" fill="#4c4f55"/>
      <g transform="translate(43.5 24.6) rotate(45)"><rect x="-1.6" y="-1.6" width="1.5" height="1.5" fill="#e8262b"/><rect x=".1" y="-1.6" width="1.5" height="1.5" fill="#f7d117"/><rect x="-1.6" y=".1" width="1.5" height="1.5" fill="#2a8fd6"/><rect x=".1" y=".1" width="1.5" height="1.5" fill="#47b549"/></g>`),

    // the black slab, its ridged left end and the blue logo light
    ps2: svg(`
      <path d="M6 17 L48 17 L54 11 L12 11 Z" fill="#30323a"/>
      <path d="M48 17 L54 11 L54 25 L48 31 Z" fill="#0b0c0e"/>
      <rect x="6" y="17" width="42" height="14" fill="#17181c"/>
      ${[9, 11, 13, 15, 17].map(x => `<rect x="${x}" y="19" width=".9" height="10" fill="#2f3138"/>`).join('')}
      <rect x="21" y="21" width="23" height="1.3" fill="#34363e"/>
      <rect x="21" y="25.5" width="23" height=".7" fill="#26282e"/>
      <circle cx="44" cy="28.4" r="1.1" fill="#3a7bff"/>`),

    // the curved top of the first PS3, the silver band and the slot
    ps3: svg(`
      <path d="M6 31 L6 21 Q6 10 17 10 L43 10 Q54 10 54 21 L54 31 Z" fill="#131317"/>
      <path d="M9 21 Q9 13 17.5 12.6 L42.5 12.6 Q51 13 51 21" fill="none" stroke="#3d3f47" stroke-width="1"/>
      <rect x="6" y="27.5" width="48" height="3.5" fill="#8c9199"/>
      <rect x="6" y="27.5" width="48" height=".8" fill="#c3c8cf"/>
      <rect x="14" y="21.5" width="24" height="1.2" fill="#2c2e35"/>
      <circle cx="46" cy="22" r=".9" fill="#5bd36b"/>`),

    // the leaning slab with the light bar across it
    ps4: svg(`
      <path d="M9 16 L49 16 L54 11 L14 11 Z" fill="#2a2d36"/>
      <path d="M9 16 L49 16 L46 31 L12 31 Z" fill="#15171c"/>
      <path d="M49 16 L54 11 L51 26 L46 31 Z" fill="#0c0d10"/>
      <path d="M10.6 22.5 L47.6 22.5" stroke="#3d7bff" stroke-width="1.1"/>
      <path d="M14 11.6 L53 11.6" stroke="#4b4f5b" stroke-width=".6"/>`),

    // lying flat, seen at an angle like the PS4: the black core with its blue light between
    // the two white panels, which curve round at the ends, on its stand
    ps5: svg(`
      <path d="M6 18 C8 13.5 11.5 11.5 17 11.5 L56 11.5 C55 14.5 52 18 47.5 18 Z" fill="#f3f3f5"/>
      <path d="M56 11.5 C55 14.5 52 18 47.5 18 L47.5 29 C52 29 55 25.5 56 22.5 Z" fill="#c8cad0"/>
      <path d="M47.5 19.6 C51.4 19.6 54.4 16.4 56 13.6 L56 17.6 C54.4 20.6 51.4 23.8 47.5 23.8 Z" fill="#0d0e11"/>
      <rect x="6" y="18" width="41.5" height="1.6" fill="#d9dadf"/>
      <rect x="6" y="19.6" width="41.5" height="4.2" fill="#16171c"/>
      <path d="M6.6 20 H47.5 M6.6 23.4 H47.5" stroke="#4a8dff" stroke-width=".55"/>
      <path d="M6 23.8 H47.5 V29 L13 29 C9 29 6 27.4 6 23.8 Z" fill="#e8e9ec"/>
      <rect x="14" y="12.2" width="34" height="1.3" rx=".65" fill="#fff" opacity=".6"/>
      <rect x="22" y="29" width="13" height="2.4" rx="1" fill="#1d1e24"/>`),

    // the big black box with the green jewel on top
    xbox: svg(`
      <rect x="6" y="8" width="48" height="25" rx="4" fill="#1d1e21"/>
      <rect x="6" y="28" width="48" height="5" rx="2.5" fill="#0f1012"/>
      ${shine(7, 9, 46, 4, 3)}
      <circle cx="30" cy="18" r="7.4" fill="#245f24"/><circle cx="30" cy="18" r="5.4" fill="#63c93a"/>
      <path d="M27.3 15.3 L32.7 20.7 M32.7 15.3 L27.3 20.7" stroke="#123512" stroke-width="1.7" stroke-linecap="round"/>
      <rect x="14" y="29.5" width="6" height="2" rx="1" fill="#2d6e2a"/><rect x="40" y="29.5" width="6" height="2" rx="1" fill="#2d6e2a"/>`),

    // white with curved-in ends, the ring of light on the right
    x360: svg(`
      <path d="M6 12 Q9.5 20 6 28 L54 28 Q50.5 20 54 12 Z" fill="#eceef1"/>
      <path d="M6 12 L54 12 L52.5 14 L7.5 14 Z" fill="#d5d9de"/>
      <rect x="13" y="19.5" width="23" height="1.3" fill="#b4b9c0"/>
      <circle cx="45.5" cy="20.2" r="3.6" fill="#cfd3d9"/><circle cx="45.5" cy="20.2" r="2" fill="#e6e9ec"/>
      <path d="M45.5 16.6 A3.6 3.6 0 0 1 49.1 20.2" stroke="#4fd34f" stroke-width="1.3" fill="none"/>`),

    // the long black box: glossy on the left, vented on the right
    xone: svg(`
      <rect x="4" y="13" width="52" height="16" rx="1.5" fill="#121316"/>
      <rect x="4" y="13" width="32" height="16" rx="1.5" fill="#1e2026"/>
      ${Array.from({ length: 10 }, (_, i) => `<rect x="${37.5 + i * 1.7}" y="14" width=".7" height="14" fill="#25272d"/>`).join('')}
      <rect x="4" y="13" width="52" height="1" fill="#3a3d45"/>
      <circle cx="31" cy="21" r="1.3" fill="#e8e8e8"/>`),

    // the boxy American SNES with its purple switches
    snes: svg(`
      <rect x="6" y="12" width="48" height="21" rx="3" fill="#cac8d2"/>
      <rect x="6" y="28" width="48" height="5" rx="2.5" fill="#b3b1bd"/>
      <rect x="13" y="13" width="34" height="11" rx="2" fill="#a29fb0"/>
      ${[16, 19, 41, 44].map(x => `<rect x="${x}" y="14" width=".9" height="9" fill="#8f8c9e"/>`).join('')}
      <rect x="21" y="14.6" width="18" height="2.8" rx="1" fill="#4f4d59"/>
      <rect x="14.5" y="19.3" width="5.5" height="2.5" rx="1" fill="#6b4fb4"/><rect x="40" y="19.3" width="5.5" height="2.5" rx="1" fill="#6b4fb4"/>
      <rect x="27" y="19.2" width="6" height="2.8" rx="1" fill="#8a8796"/>
      <rect x="12" y="29.4" width="6" height="2.2" rx="1" fill="#55535e"/><rect x="42" y="29.4" width="6" height="2.2" rx="1" fill="#55535e"/>`),

    // the original grey Game Boy (only used as a case style)
    gb: svg(`
      <rect x="20" y="3" width="20" height="34" rx="2.5" fill="#c7c7c0"/>
      <rect x="22.5" y="6" width="15" height="13" rx="1.4" fill="#5f6068"/>
      <rect x="24.5" y="8" width="11" height="9" fill="#9bbc0f"/>
      ${cross(25, 26, 2.6, '#2b2b30')}
      <circle cx="35.5" cy="24.5" r="1.4" fill="#9b1c4a"/><circle cx="32.5" cy="26.5" r="1.4" fill="#9b1c4a"/>`),

    // purple Game Boy Color
    gbc: svg(`
      <rect x="20" y="3" width="20" height="34" rx="3" fill="#7657c6"/>
      ${shine(20.5, 3.5, 19, 4, 2.5)}
      <rect x="22.5" y="6" width="15" height="13" rx="1.4" fill="#32323b"/>
      <rect x="24.5" y="8" width="11" height="9" fill="#a8cfb7"/>
      ${cross(25, 26, 2.6, '#2a2245')}
      <circle cx="35.5" cy="24.5" r="1.4" fill="#2a2245"/><circle cx="32.5" cy="26.5" r="1.4" fill="#2a2245"/>
      ${[33, 34.6, 36.2].map(x => `<rect x="${x}" y="31" width=".7" height="3.5" rx=".3" fill="#5a3fa6"/>`).join('')}`),

    // indigo Game Boy Advance, held sideways
    gba: svg(`
      <path d="M9 11 Q9 8.5 12 8.5 L48 8.5 Q51 8.5 51 11 L54 26 Q54.5 31.5 49 31.5 L11 31.5 Q5.5 31.5 6 26 Z" fill="#4c45b3"/>
      ${shine(10, 9, 40, 3)}
      <rect x="19.5" y="11" width="21" height="15.5" rx="1.5" fill="#26262f"/>
      <rect x="21.5" y="13" width="17" height="11.5" fill="#a6bccf"/>
      ${cross(12.5, 19.5, 2.6, '#25205e')}
      <circle cx="47" cy="17.5" r="1.6" fill="#25205e"/><circle cx="44" cy="20.5" r="1.6" fill="#25205e"/>`),

    // a DS Lite, open
    ds: svg(`
      <rect x="16" y="2.5" width="28" height="16.5" rx="2" fill="#e9eaee"/>
      <rect x="21.5" y="4.6" width="17" height="12.4" fill="#2a2d36"/>
      <rect x="17" y="19" width="26" height="2" fill="#c3c5cc"/>
      <rect x="16" y="21" width="28" height="16.5" rx="2" fill="#f3f4f7"/>
      <rect x="21.5" y="23.2" width="17" height="12.4" fill="#2a2d36"/>
      ${cross(18.8, 27.5, 1.8, '#4a4d56')}
      <circle cx="41.4" cy="26" r=".8" fill="#4a4d56"/><circle cx="40" cy="27.5" r=".8" fill="#4a4d56"/><circle cx="42.8" cy="27.5" r=".8" fill="#4a4d56"/><circle cx="41.4" cy="29" r=".8" fill="#4a4d56"/>`),

    // a red 3DS, open: the wider top screen and the circle pad
    '3ds': svg(`
      <rect x="14.5" y="2.5" width="31" height="16.5" rx="2" fill="#a91f29"/>
      <rect x="17.5" y="4.6" width="25" height="12.4" fill="#22252d"/>
      <rect x="15.5" y="19" width="29" height="2" fill="#7c151c"/>
      <rect x="14.5" y="21" width="31" height="16.5" rx="2" fill="#c42a33"/>
      ${shine(15, 21.5, 30, 3)}
      <rect x="23" y="23.2" width="14" height="11" fill="#22252d"/>
      <circle cx="18.6" cy="25.6" r="2" fill="#5a5d66"/>${cross(18.6, 31.5, 1.6, '#3b0d11')}
      <circle cx="41.4" cy="26" r=".8" fill="#3b0d11"/><circle cx="40" cy="27.5" r=".8" fill="#3b0d11"/><circle cx="42.8" cy="27.5" r=".8" fill="#3b0d11"/><circle cx="41.4" cy="29" r=".8" fill="#3b0d11"/>`),

    // the slim white Wii on its stand, and its remote
    wii: svg(`
      <rect x="17" y="3.5" width="10" height="30.5" rx="1.6" fill="#f4f5f7" stroke="#c8ccd2" stroke-width=".7"/>
      <rect x="21.4" y="7" width="1.3" height="20" rx=".6" fill="#7fd0ff"/>
      <rect x="14" y="34" width="16" height="2.6" rx="1.3" fill="#d6d9de"/>
      <rect x="35" y="8" width="6.4" height="26" rx="2" fill="#f4f5f7" stroke="#c8ccd2" stroke-width=".7"/>
      ${cross(38.2, 12.2, 1.6, '#aab0b8')}
      <circle cx="38.2" cy="17.5" r="1.1" fill="#c8ccd2"/><circle cx="38.2" cy="27" r=".7" fill="#7fd0ff"/>`),

    // the Wii U GamePad
    wiiu: svg(`
      <path d="M4.5 12 Q4.5 8.5 8.5 8.5 L51.5 8.5 Q55.5 8.5 55.5 12 L55.5 28.5 Q55.5 32 51.5 32 L8.5 32 Q4.5 32 4.5 28.5 Z" fill="#1c1d21"/>
      ${shine(5, 9, 50, 3)}
      <rect x="17" y="11.5" width="26" height="17" rx="1" fill="#2f5f86"/>
      <circle cx="10.5" cy="14.8" r="2.7" fill="#3a3b41" stroke="#55565d" stroke-width=".6"/><circle cx="49.5" cy="14.8" r="2.7" fill="#3a3b41" stroke="#55565d" stroke-width=".6"/>
      ${cross(10.5, 24, 2.2, '#4a4b52')}
      <circle cx="49.5" cy="22.5" r=".9" fill="#55565d"/><circle cx="48" cy="24" r=".9" fill="#55565d"/><circle cx="51" cy="24" r=".9" fill="#55565d"/><circle cx="49.5" cy="25.5" r=".9" fill="#55565d"/>`),

    // neon blue and red Joy-Con either side of the screen
    switch: svg(`
      <path d="M14 9.5 L10.5 9.5 Q5.5 9.5 5.5 15 L5.5 26 Q5.5 31.5 10.5 31.5 L14 31.5 Z" fill="#00b4e0"/>
      <path d="M46 9.5 L49.5 9.5 Q54.5 9.5 54.5 15 L54.5 26 Q54.5 31.5 49.5 31.5 L46 31.5 Z" fill="#ff4f4f"/>
      <rect x="14" y="9.5" width="32" height="22" fill="#26272c"/>
      <rect x="16" y="11.5" width="28" height="18" fill="#3c4556"/>
      <circle cx="9.8" cy="15.5" r="1.9" fill="#1e3640"/><circle cx="50.2" cy="25" r="1.9" fill="#4a1d1d"/>
      ${cross(9.8, 24.5, 1.7, '#14313b')}
      <circle cx="50.2" cy="14" r=".7" fill="#4a1d1d"/><circle cx="49" cy="15.3" r=".7" fill="#4a1d1d"/><circle cx="51.4" cy="15.3" r=".7" fill="#4a1d1d"/><circle cx="50.2" cy="16.6" r=".7" fill="#4a1d1d"/>`),

    // the bigger Switch 2: dark Joy-Con with blue and red on their inner edges
    switch2: svg(`
      <path d="M13 8.5 L9.5 8.5 Q4.5 8.5 4.5 14 L4.5 27 Q4.5 32.5 9.5 32.5 L13 32.5 Z" fill="#26272b"/>
      <path d="M47 8.5 L50.5 8.5 Q55.5 8.5 55.5 14 L55.5 27 Q55.5 32.5 50.5 32.5 L47 32.5 Z" fill="#26272b"/>
      <rect x="12.2" y="9.5" width=".9" height="22" fill="#1fa8e0"/><rect x="46.9" y="9.5" width=".9" height="22" fill="#ff3c3c"/>
      <rect x="13" y="8.5" width="34" height="24" fill="#18191c"/>
      <rect x="15" y="10.5" width="30" height="20" fill="#2f3848"/>
      <circle cx="8.8" cy="15" r="2" fill="#3c3d43"/><circle cx="51.2" cy="26" r="2" fill="#3c3d43"/>
      ${cross(8.8, 25, 1.7, '#45464c')}
      <circle cx="51.2" cy="14" r=".7" fill="#55565d"/><circle cx="50" cy="15.3" r=".7" fill="#55565d"/><circle cx="52.4" cy="15.3" r=".7" fill="#55565d"/><circle cx="51.2" cy="16.6" r=".7" fill="#55565d"/>`),

    // a monitor
    pc: svg(`
      <rect x="11" y="5" width="38" height="24" rx="1.6" fill="#1f2228"/>
      <rect x="13" y="7" width="34" height="20" fill="#2f6aa8"/>
      <path d="M13 7 L30 7 L13 20 Z" fill="#fff" opacity=".12"/>
      <path d="M27 29 L33 29 L35 34.5 L25 34.5 Z" fill="#2b2e35"/>
      <rect x="21" y="34.5" width="18" height="2" rx="1" fill="#1f2228"/>`),
  };
})();
