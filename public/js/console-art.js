/* console-art.js — a small picture of each console, drawn to read at chip size:
   the console as you'd recognise it on a shelf or a table. All share one 60×40
   frame and the same light from the top left. The PlayStations and Xboxes are
   traced from pictures of the real consoles: each one the model and the angle
   people know best, with its controller where that helps. The drawings they
   replaced are kept in legacy/console-art.js. */

(function () {
  const svg = body => `<svg class="cart-art" viewBox="0 0 60 40" aria-hidden="true">${body}</svg>`;
  const cross = (x, y, s, c) => `<path d="M${x - s} ${y - s / 3}h${s * 2 / 3}v-${s * 2 / 3}h${s * 2 / 3}v${s * 2 / 3}h${s * 2 / 3}v${s * 2 / 3}h-${s * 2 / 3}v${s * 2 / 3}h-${s * 2 / 3}v-${s * 2 / 3}h-${s * 2 / 3}z" fill="${c}"/>`;
  const shine = (x, y, w, h, r = 2) => `<rect x="${x}" y="${y}" width="${w}" height="${h}" rx="${r}" fill="#fff" opacity=".14"/>`;
  const f1 = v => +v.toFixed(2);
  // horizontal stripes across a triangle, every `step` units: the vents in the original Xbox's X
  function stripes(pts, from, to, step, w, c) {
    let out = '';
    for (let y = from; y <= to; y += step) {
      const xs = [];
      pts.forEach((p, i) => {
        const q = pts[(i + 1) % pts.length];
        if ((p[1] - y) * (q[1] - y) <= 0 && p[1] !== q[1]) xs.push(p[0] + (y - p[1]) / (q[1] - p[1]) * (q[0] - p[0]));
      });
      if (xs.length > 1) out += `<rect x="${f1(Math.min(...xs))}" y="${f1(y - w / 2)}" width="${f1(Math.max(...xs) - Math.min(...xs))}" height="${w}" fill="${c}"/>`;
    }
    return out;
  }
  // the four face buttons round (x, y)
  const face = (x, y, d, r, c) => [[0, -d], [d, 0], [0, d], [-d, 0]].map(([dx, dy], i) => `<circle cx="${x + dx}" cy="${y + dy}" r="${r}" fill="${Array.isArray(c) ? c[i] : c}"/>`).join('');

  // PlayStation pads, drawn in a 24 × 15 box and placed with a transform
  // the DualShock 3: its long grips, the d-pad as four arrows in a round well, the face buttons with
  // their coloured symbols, the two sticks low in the middle, the PS button between them
  const ds3 = `
    <path d="M3.2 2.2C5 1.2 8 1.2 9.6 1.7H14.4C16 1.2 19 1.2 20.8 2.2C22.6 3.2 23.3 5 23.7 7.5C24.1 10.2 24.2 12.6 23.2 13.8C22.2 14.9 20.6 14.6 19.7 13.4L17.4 10.6C16.6 9.8 15.6 9.6 14.8 10.2C13.4 11 10.6 11 9.2 10.2C8.4 9.6 7.4 9.8 6.6 10.6L4.3 13.4C3.4 14.6 1.8 14.9 .8 13.8C-.2 12.6-.1 10.2 .3 7.5C.7 5 1.4 3.2 3.2 2.2Z" fill="#1b1b1e"/>
    <path d="M3.4 2.6C5.2 1.7 8 1.7 9.6 2.1H14.4C16 1.7 18.8 1.7 20.6 2.6" stroke="#6a6c73" stroke-width=".35" fill="none"/>
    <path d="M1.3 7Q1.8 4 3.6 3" stroke="#fff" stroke-opacity=".18" stroke-width=".5" fill="none"/>
    <circle cx="5" cy="5.6" r="2.6" fill="#101012"/>
    <g fill="#34353a"><path d="M4.4 3.3H5.6V4.6L5 5.2L4.4 4.6Z"/><path d="M4.4 7.9H5.6V6.6L5 6L4.4 6.6Z"/><path d="M2.7 5V6.2H4L4.6 5.6L4 5Z"/><path d="M7.3 5V6.2H6L5.4 5.6L6 5Z"/></g>
    <circle cx="19" cy="5.6" r="2.6" fill="#101012"/>
    ${[[19, 3.9], [20.7, 5.6], [19, 7.3], [17.3, 5.6]].map(([x, y]) => `<circle cx="${x}" cy="${y}" r=".82" fill="#2c2d32"/>`).join('')}
    <path d="M18.6 4.25L19 3.5L19.4 4.25Z" fill="none" stroke="#4fc07a" stroke-width=".2"/>
    <circle cx="20.7" cy="5.6" r=".38" fill="none" stroke="#e0565c" stroke-width=".2"/>
    <path d="M18.65 6.95L19.35 7.65M19.35 6.95L18.65 7.65" stroke="#6f8fe0" stroke-width=".2"/>
    <rect x="16.95" y="5.25" width=".7" height=".7" fill="none" stroke="#d98cc6" stroke-width=".2"/>
    <circle cx="8.6" cy="8.5" r="2.15" fill="#0c0c0e"/><circle cx="8.6" cy="8.5" r="1.55" fill="#26272b"/><circle cx="8.6" cy="8.5" r="1.05" fill="#313237"/>
    <circle cx="15.4" cy="8.5" r="2.15" fill="#0c0c0e"/><circle cx="15.4" cy="8.5" r="1.55" fill="#26272b"/><circle cx="15.4" cy="8.5" r="1.05" fill="#313237"/>
    <circle cx="12" cy="7.5" r=".7" fill="#2c2d32" stroke="#45464c" stroke-width=".15"/>
    <rect x="9.9" y="5.3" width="1.4" height=".55" rx=".27" fill="#34353a"/><rect x="12.7" y="5.3" width="1.4" height=".55" rx=".27" fill="#34353a"/>
    <text x="12" y="3.9" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-weight="700" font-size="1" letter-spacing=".1" fill="#7a7c83">SONY</text>`;
  const ds4 = `
    <path d="M3.6 1.4 H20.4 Q23 1.6 23.5 5.2 Q24.4 10.6 22.9 12.7 Q21.2 14.1 19.3 12.4 L16.9 9.9 H7.1 L4.7 12.4 Q2.8 14.1 1.1 12.7 Q-.4 10.6 .5 5.2 Q1 1.6 3.6 1.4 Z" fill="#1c1c1f"/>
    <path d="M3.6 1.8 H20.4" stroke="#47494f" stroke-width=".4"/>
    <rect x="8" y="1.6" width="8" height="4.3" rx=".7" fill="#121214" stroke="#3a3b41" stroke-width=".3"/>
    ${cross(4.3, 5.3, 1.9, '#3a3b41')}
    ${face(19.7, 5.3, 1.55, .72, '#34353b')}
    <circle cx="8.5" cy="8.9" r="1.85" fill="#0e0e10"/><circle cx="8.5" cy="8.9" r="1.2" fill="#323338"/>
    <circle cx="15.5" cy="8.9" r="1.85" fill="#0e0e10"/><circle cx="15.5" cy="8.9" r="1.2" fill="#323338"/>
    <circle cx="12" cy="8.3" r=".5" fill="#3a3b41"/>`;
  const dualsense = `
    <path d="M4 1.6 Q12 .4 20 1.6 Q22.6 2.1 23.3 5.6 Q24.4 11.6 22.8 14 Q21 15.6 19.2 13.6 L16.6 10.6 H7.4 L4.8 13.6 Q3 15.6 1.2 14 Q-.4 11.6 .7 5.6 Q1.4 2.1 4 1.6 Z" fill="#f2f3f5" stroke="#b9bcc3" stroke-width=".3"/>
    <path d="M6.2 5.4 H17.8 L19.1 9.6 Q19.3 11 17.8 11.2 H6.2 Q4.7 11 4.9 9.6 Z" fill="#17181b"/>
    <rect x="7.9" y="1.4" width="8.2" height="4.6" rx=".9" fill="#fbfbfc" stroke="#c4c7cd" stroke-width=".3"/>
    ${cross(4.3, 5.6, 1.8, '#25262a')}
    ${face(19.7, 5.6, 1.5, .62, '#25262a')}
    <circle cx="8.8" cy="9" r="1.75" fill="#f2f3f5"/><circle cx="8.8" cy="9" r="1.2" fill="#25262a"/>
    <circle cx="15.2" cy="9" r="1.75" fill="#f2f3f5"/><circle cx="15.2" cy="9" r="1.2" fill="#25262a"/>
    <rect x="11.2" y="8.5" width="1.6" height=".7" rx=".35" fill="#f2f3f5"/>`;

  window.CONSOLE_ART = {
    // the grey box from the front and above: the round lid between the two raised ends,
    // the reset and power buttons on the left, the eject button on the right, the ports below
    ps1: svg(`
      <path d="M5 3 H55 V35 L53.6 37 H6.4 L5 35 Z" fill="#c9cbd0" stroke="#7d8087" stroke-width=".45"/>
      <path d="M19.3 3.9 H40.7 V35 H19.3 Z" fill="#bfc1c6" stroke="#8a8d94" stroke-width=".35"/>
      <path d="M5 35 H55 L53.6 37 H6.4 Z" fill="#a9acb2" stroke="#7d8087" stroke-width=".35"/>
      <rect x="20.6" y="35.5" width="6.4" height="1.3" fill="#3b3d42"/><rect x="33" y="35.5" width="6.4" height="1.3" fill="#3b3d42"/>
      ${shine(5.4, 3.4, 13.4, 1.6, .5)}${shine(41.2, 3.4, 13.4, 1.6, .5)}
      <circle cx="30" cy="19" r="14.3" fill="#c6c8cd" stroke="#6f7279" stroke-width=".5"/>
      <path d="M18 14 A13.2 13.2 0 0 1 42 14" stroke="#fff" stroke-opacity=".35" stroke-width=".6" fill="none"/>
      <text x="30" y="11.4" text-anchor="middle" font-family="Georgia, 'Times New Roman', serif" font-size="2.3" letter-spacing=".25" fill="#6c6f75">SONY</text>
      <path d="M29.4 25.3 V21.3 H30.8 Q32.1 21.3 32.1 22.4 Q32.1 23.5 30.8 23.5 H30" stroke="#d8262c" stroke-width=".75" fill="none"/>
      <path d="M27.2 25.1 L29.2 24.4" stroke="#f2c418" stroke-width=".7"/><path d="M29.6 25.4 L31.4 24.8" stroke="#2a8fd6" stroke-width=".7"/><path d="M31.4 24.2 L33.1 24.8" stroke="#47b549" stroke-width=".7"/>
      <circle cx="10.5" cy="22.4" r="1.6" fill="#9a9da3" stroke="#5c5f65" stroke-width=".35"/>
      <circle cx="11.7" cy="29.6" r="3.7" fill="#cfd1d5" stroke="#5c5f65" stroke-width=".4"/>
      <path d="M10.6 28.4 V30.8 M12.4 28.6 A1.3 1.3 0 1 1 11.6 28.4" stroke="#3fbf8f" stroke-width=".45" fill="none"/>
      <rect x="11" y="34" width=".9" height=".9" rx=".2" fill="#57d36e"/>
      <circle cx="48.2" cy="29.4" r="3.7" fill="#cfd1d5" stroke="#5c5f65" stroke-width=".4"/>
      <path d="M47 29.8 L48.2 28.3 L49.4 29.8 Z M47 30.4 H49.4" stroke="#3aaee0" stroke-width=".35" fill="#3aaee0"/>
      <path d="M40.7 26 L45 28" stroke="#6f7279" stroke-width=".35"/>`),

    // the first PS2, standing: its long black side with the PS2 logo, the ridged front
    // with its two buttons, and the stepped back with the blue ports
    ps2: svg(`<g transform="translate(30 0) scale(1.3 1) translate(-30 0)">
      <path d="M21 4.7 L31.3 2 V38.4 L21.1 36.6 Z" fill="#2c2d31"/>
      <path d="M21 4.7 L31.3 2" stroke="#4a4c53" stroke-width=".35"/>
      <path d="M35.9 6 H38.8 V38 H35.9 Z" fill="#1e1f22"/>
      ${Array.from({ length: 14 }, (_, i) => `<rect x="36.4" y="${f1(7.2 + i * 1.75)}" width="1.9" height=".7" fill="#2c2d31"/>`).join('')}
      <rect x="36" y="32.8" width="2.5" height="4.3" fill="#3b8ee8"/>
      <rect x="36.5" y="33.4" width=".9" height=".9" fill="#163a66"/><rect x="36.5" y="35" width=".7" height="1.6" fill="#163a66"/><rect x="37.4" y="35" width=".7" height="1.6" fill="#163a66"/>
      <path d="M31.3 2 L35.9 2.4 V38.3 L31.3 38.4 Z" fill="#121315"/>
      ${[31.8, 32.7, 33.6, 34.5, 35.3].map(x => `<path d="M${x} 2.4 V38.2" stroke="#2a2b30" stroke-width=".3"/>`).join('')}
      <path d="M31.4 13.2 H35.8 M31.4 26.4 H35.8" stroke="#2a2b30" stroke-width=".25"/>
      <circle cx="32.1" cy="4" r=".55" fill="#57c46d"/><circle cx="34.9" cy="4.2" r=".55" fill="#4f7dff"/>
      <circle cx="33.4" cy="12" r=".3" fill="#d8262c"/>
      <path d="M22.5 19.4 V17.1 H24.7 V18.25 H22.7" stroke="#a46cff" stroke-width=".42" fill="none"/>
      <path d="M27.5 17.1 H26.1 V19.4 H24.9" stroke="#8a7cff" stroke-width=".42" fill="none"/>
      <path d="M27.9 17.1 H29.6 V18.25 H28 V19.4 H29.8" stroke="#6d9cff" stroke-width=".42" fill="none"/>
      <rect x="23.6" y="20.2" width="4.6" height=".45" fill="#4a4c53"/></g>`),

    // the PS3 slim at an angle from the front: its curved matte top with the PS3 logo, the front with the
    // disc slot and the glossy strip with its two buttons, and a DualShock 3 in front of it
    ps3: svg(`
      <path d="M58 11 L56.6 15.4 L39.3 32.7 L39.9 28.8 Z" fill="#232326"/>
      <path d="M5.95 18.5 L39.9 28.8 L39.3 32.7 L7 22.6 Z" fill="#17171a"/>
      <path d="M21.8 25 L39.6 30.1" stroke="#050506" stroke-width=".7"/>
      <path d="M22.5 25.9 L39.4 30.9 L39.1 32.6 L22.5 27.8 Z" fill="#b8b9bd"/>
      <path d="M22.5 25.9 L39.4 30.9" stroke="#e8e9ec" stroke-width=".3"/>
      <ellipse cx="29.6" cy="27.9" rx=".8" ry=".45" fill="#f4f4f6"/><ellipse cx="37.6" cy="30.9" rx=".8" ry=".45" fill="#f4f4f6"/>
      <path d="M5.95 18.5 Q13 9 27.6 2.5 Q45 6 58 11 Q50 21 39.9 28.8 Q22 24.5 5.95 18.5 Z" fill="#38383c"/>
      <path d="M8 17.6 Q15 9.6 27.6 3.6 Q40 6.6 48 9.4 Q30 9 8 17.6 Z" fill="#fff" opacity=".05"/>
      <path d="M5.95 18.5 Q13 9 27.6 2.5 Q45 6 58 11" stroke="#5a5b61" stroke-width=".35" fill="none"/>
      <path d="M5.95 18.5 Q22 24.5 39.9 28.8 Q50 21 58 11" stroke="#4a4b50" stroke-width=".3" fill="none"/>
      <g transform="matrix(.5 .15 -.27 .27 35.7 7.5)" stroke="#232326" stroke-width="1.4" fill="none">
        <path d="M14.6 16.05 H19.2 Q20.3 16.05 20.3 17.15 V17.4 Q20.3 18.45 19.2 18.45 H16.1 Q15 18.45 15 19.55 V20.9"/>
        <path d="M26.9 16.05 H24.2 Q23.2 16.05 23.2 17.05 V19.4 Q23.2 20.4 22.2 20.4 H20"/>
        <path d="M27.9 16.05 H32.1 Q33.1 16.05 33.1 17.1 V17.2 Q33.1 18.25 32.1 18.25 H29.8 M32.1 18.25 Q33.1 18.25 33.1 19.3 V19.35 Q33.1 20.4 32.1 20.4 H27.9"/>
      </g>
      <g transform="translate(12.2 28) rotate(18) scale(.8) translate(-12 -7.6)">${ds3}</g>`),

    // the PS4 slim standing up, the groove down its front edge and the PS logo on its side,
    // with a DualShock 4 in front of it
    ps4: svg(`
      <path d="M6 2.9 L11.8 2 V37.7 L6 36.9 Z" fill="#202024"/>
      <path d="M8.9 2.45 L10.6 2.2 V37.55 L8.9 37.3 Z" fill="#050506"/>
      <path d="M11.8 2 L29 3.4 V36.4 L11.8 37.7 Z" fill="#17171a"/>
      <path d="M11.8 2 L29 3.4 L29 9 L11.8 16 Z" fill="#fff" opacity=".045"/>
      <path d="M6 2.9 L11.8 2 L29 3.4" stroke="#3b3c42" stroke-width=".35" fill="none"/>
      <path d="M20 21.8 V17.8 H21.4 Q22.7 17.8 22.7 18.9 Q22.7 20 21.4 20 H20.6" stroke="#2f3036" stroke-width=".7" fill="none"/>
      <path d="M17.6 21.9 Q18.8 21 19.8 21.4 M21 22.2 Q22.6 21.5 24 21.9" stroke="#2f3036" stroke-width=".6" fill="none"/>
      <rect x="7.1" y="5" width=".5" height="3.4" fill="#3b3c42"/><rect x="7.1" y="31" width=".5" height="3" fill="#3b3c42"/>
      <path d="M5.4 38.2 L26.5 37.1 L27.4 37.6 L6.4 38.9 Z" fill="#2a2a2e"/>
      <g transform="translate(30.6 23.6) scale(1.1)">${ds4}</g>`),

    // the PS5 standing up: the white panels either side of the black core, its blue light,
    // and a DualSense in front of it
    ps5: svg(`
      <path d="M40.4 3.6 Q41.4 2.6 42.9 2.5 L42.3 36 L40.9 36.3 Z" fill="#d6d8dd"/>
      <path d="M38.1 4.4 L40.7 3.5 L40.9 36.3 L38.7 36.4 Z" fill="#111215"/>
      <path d="M38.25 5.2 L38.85 36" stroke="#4a8dff" stroke-width=".4"/>
      ${[20, 22.3, 32.3].map(y => `<rect x="39.5" y="${y}" width=".5" height="1.4" rx=".25" fill="#e8e9ec"/>`).join('')}
      <path d="M29.7 9 Q29.7 7.6 30.8 6.9 L37.3 2.5 Q38.1 2 38.1 2.9 L38.7 36.4 L29.4 35.5 Z" fill="#f4f4f6"/>
      <path d="M30.8 6.9 L37.3 2.5" stroke="#fff" stroke-width=".5"/>
      <path d="M38.1 2.9 L38.7 36.4" stroke="#c4c6cc" stroke-width=".35"/>
      <path d="M29.4 35.5 L38.7 36.4 L42.3 36" stroke="#9fa2a9" stroke-width=".4" fill="none"/>
      <g transform="translate(14.8 27.1) scale(.72)">${dualsense}</g>`),

    // the original Xbox from the front and above: the big X across its top, ridged either
    // side, the green jewel, and the front with its tray and four controller ports
    xbox: svg(`
      <path d="M4 20.3 L10 3.2 L25.2 1.7 H33.1 L48.1 3.2 L56 20.3 Z" fill="#0f0f10" stroke="#38393d" stroke-width=".4" stroke-linejoin="round"/>
      ${stripes([[10.2, 4.6], [20.2, 9.4], [5.6, 19.6]], 5.2, 19.2, 1.2, .55, '#3b3c40')}
      ${stripes([[49.8, 4.6], [39.8, 9.4], [54.4, 19.6]], 5.2, 19.2, 1.2, .55, '#3b3c40')}
      <path d="M24.2 20.3 L29.8 15.6 L36.4 20.3" stroke="#2e2f33" stroke-width=".45" fill="none"/>
      <ellipse cx="29.8" cy="9.4" rx="6" ry="4.1" fill="#2c7a1d"/>
      <ellipse cx="29.8" cy="9.2" rx="5.5" ry="3.6" fill="#5cc23a"/>
      <ellipse cx="29.2" cy="8.2" rx="3.8" ry="1.6" fill="#fff" opacity=".22"/>
      <text x="29.8" y="10.2" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-weight="700" font-size="2.5" letter-spacing=".15" fill="#f4f8f0">XBOX</text>
      <path d="M4 20.3 H56 L55.4 35.8 Q55.3 37.3 53.8 37.3 H6.2 Q4.7 37.3 4.6 35.8 Z" fill="#2c2c2f" stroke="#141415" stroke-width=".35"/>
      <rect x="4.2" y="20.3" width="51.6" height="3" fill="#161617"/>
      <path d="M24.2 23.3 L29.8 18.6 L36.4 23.3 Z" fill="#1c1c1e"/><path d="M25.8 23.3 L29.8 19.9 L34.6 23.3" stroke="#3a3b3f" stroke-width=".35" fill="none"/>
      <rect x="24.2" y="23.3" width="12.2" height="14" fill="#232325"/>
      <rect x="24.9" y="24.9" width="5.1" height="2.6" fill="#141415"/><rect x="30.6" y="24.9" width="5.1" height="2.6" fill="#141415"/>
      <ellipse cx="30.3" cy="32" rx="1.3" ry="1.9" fill="#3d3e42"/><path d="M29.7 32.3 L30.3 31.3 L30.9 32.3 Z" fill="#9a9ca1"/>
      <ellipse cx="30.3" cy="35.6" rx=".45" ry=".6" fill="#3d3e42"/>
      <rect x="9.2" y="26" width="14.4" height="2.9" fill="#1a1a1b"/>
      <text x="11.2" y="28.2" font-family="Arial, Helvetica, sans-serif" font-weight="700" font-size="2" letter-spacing=".12" fill="#d8dadd">XBOX</text>
      <rect x="36.9" y="26" width="17.6" height="2.9" fill="#1a1a1b"/>
      ${[15, 20.8, 38.9, 44.6].map(x => `<circle cx="${x}" cy="31.4" r=".45" fill="#0a0a0b"/><circle cx="${x}" cy="34.6" r="2.1" fill="#0a0a0b"/>`).join('')}`),

    // the Xbox 360 S standing up: glossy black, its curved front with the
    // ring of light, and its side with the silver stripe and the vents
    x360: svg(`
      <path d="M26.4 2.3 L40.9 6 Q41.9 6.3 41.9 7.3 V34.8 Q41.9 35.7 40.9 35.8 L26.4 37.1 Z" fill="#121214"/>
      <path d="M26.9 3.3 L40.3 6.8 Q40.9 12 36.6 14.4 Q31.4 17.1 26.9 18.6 Z" fill="#fff" opacity=".06"/>
      <path d="M40.3 6.8 Q40.9 12 36.6 14.4 Q31.4 17.1 26.9 18.6" stroke="#4a4c52" stroke-width=".35" fill="none"/>
      ${Array.from({ length: 16 }, (_, i) => { const y = 19.6 + i * 1.02, x0 = Math.max(27.9, 26.5 + (25.3 - y) / 0.593); return x0 < 36.6 ? `<rect x="${f1(x0)}" y="${f1(y)}" width="${f1(36.6 - x0)}" height=".42" fill="#2b2c31"/>` : ''; }).join('')}
      <path d="M26.5 25.3 L41 16.7" stroke="#cfd4da" stroke-width=".5"/>
      <path d="M18 3.1 Q20.2 20.4 18.2 37.7 L26.4 37.1 Q24 20.4 26.4 2.3 Z" fill="#0b0b0d"/>
      <path d="M26.4 2.3 Q24 20.4 26.4 37.1" stroke="#2c2d32" stroke-width=".3" fill="none"/>
      <path d="M19.4 4.2 Q21 20.4 19.6 36.4" stroke="#fff" stroke-opacity=".05" stroke-width="1.1" fill="none"/>
      <text transform="translate(22.1 5.4) rotate(90)" font-family="Arial, Helvetica, sans-serif" font-weight="700" font-size="1.7" letter-spacing=".2" fill="#868b92">XBOX 360</text>
      <circle cx="21.6" cy="25.8" r="1.85" fill="#c8cdd3"/><circle cx="21.6" cy="25.8" r="1.35" fill="#1a1b1d"/>
      <circle cx="21.6" cy="25.8" r=".95" stroke="#9ee35a" stroke-width=".42" fill="none" stroke-dasharray="1.15 .34"/>
      <circle cx="21.6" cy="25.8" r=".32" fill="#9ee35a"/>
      <circle cx="24.4" cy="5.4" r=".3" fill="#868b92"/>`),

    // the Xbox One S: white, the disc slot across its left half, the power button and the
    // pairing button on the right, on its black base with the USB port
    xone: svg(`
      <path d="M6.3 11 H30 V13.3 H3 Z" fill="#f1f1f2"/>
      <path d="M30 11 H53.7 L57 13.3 H30 Z" fill="#d4d5d7"/>
      <rect x="3" y="13.3" width="27" height="4.5" fill="#e2e3e5"/>
      <rect x="4.15" y="17.8" width="25.85" height="2.25" fill="#424245"/>
      <rect x="3" y="20.05" width="27" height="4.45" fill="#e2e3e5"/>
      <rect x="30" y="13.3" width="27" height="11.2" fill="#c2c3c6"/>
      <circle cx="33.35" cy="18.9" r="1.15" fill="#eeeff0"/>
      <circle cx="53.6" cy="18.9" r="1.15" fill="#4a4a4d"/>
      <rect x="4.15" y="24.5" width="51.7" height="4.5" fill="#3e3e41"/>
      <rect x="6.35" y="25.6" width="3.4" height="2.25" fill="#202022"/>
      <circle cx="48" cy="26.75" r="1.15" fill="#646467"/>
      <rect x="50.25" y="25.6" width="3.35" height="2.25" rx="1.1" fill="#202022"/>`),

    // the American SNES with its controller, traced from a photo: the console from its front right
    // corner, the raised back with the cartridge slot and the logo, the purple Power and Reset and the
    // grey Eject between them, the ports on the front with a controller plugged in, and the pad
    // with its lilac X and Y and purple A and B. The faces are the photo's own shades, cut into
    // regions; the seams, logos, ports and buttons are drawn over them in the photo's pixels.
    snes: svg(`
      <path d="M58.3 12.6 58.1 12.3 36.8 6.5 33.4 9.4 32.8 9.4 30.1 11.6 28.4 13.5 28.1 14.7 21.7 20.5 21.4 23.2 21.9 25.4 23 25.8 23.7 27 24.5 27.4 24.8 28 23.3 30.1 21.9 31.5 20.4 32.6 19.4 32.9 18.6 33 17.9 32.7 17.4 32.1 17.3 31.3 17.5 30.6 19.2 27.8 19.1 26.7 19.9 25.4 20.1 23.7 19.9 22.5 19.3 21.5 18.5 20.8 17 19.9 15.4 19.8 10.4 21 9.6 19.7 8.9 19.4 8.3 19.5 7.2 20.3 6.5 21.7 4 22.3 3.2 22.7 2.5 23.5 1.6 25.3 1.5 26.7 1.6 27.4 2.2 28.7 3.2 29.6 4.6 30.3 6.2 30.5 7.7 30 9.4 28.8 14 27.7 15.6 28 16.7 28 18.6 27.2 18.6 27.8 17.2 30 16.8 31.2 16.9 32.2 17.2 32.8 17.7 33.3 18.5 33.6 19.9 33.4 21.4 32.7 23.8 30.4 25.3 28.4 25.7 28.4 26.2 28 28.3 28.5 28.6 28.3 29.2 28.4 30.9 29.1 32.5 29.5 32.9 29.8 34.7 30.2 35.1 30.5 35.7 30.6 37.3 31.2 38 31.3 39.5 31.9 41.2 32.3 41.8 32.7 42.4 32.7 44 33.4 44.4 33.4 58 17.5 58.5 15.3 Z" fill="#c9c8cc"/>
      <path d="M16.9 20.6 15.5 20.7 14.3 21.4 13.5 22.3 13.1 23.5 13 24.7 13.4 26 14.4 27.1 15.6 27.6 16.8 27.6 18.1 27 19.2 25.9 19.7 24.6 19.7 23.3 19.2 22.2 18.2 21.1 Z" fill="#94949d"/>
      <path d="M58.2 12.5 55.4 15.7 54.9 15.6 54 16.3 51.5 19.2 50.8 20.4 50.8 21.6 48.4 24.2 48.5 24.3 50.4 22.3 49.7 23.9 49.4 24.2 48.5 25.7 48.6 26.1 50.7 24 55.5 18.5 55.7 18.6 53.8 21.1 54.2 20.9 56.6 18.6 57.2 18.4 58 17.5 58.4 15.5 58.1 15.5 58.4 15.2 Z" fill="#b1b0b4"/>
      <path d="M57.1 18.4 56.6 18.6 54.2 21 53.7 21.2 54 20.7 55.3 19.1 55.2 18.9 53.4 21 53.4 21.2 53 21.4 53.1 21.5 52.8 21.7 52.8 21.8 52.5 22 52.5 22.2 51.9 22.6 48.7 26.1 48.5 25.7 49.4 24.2 49.7 23.9 50.3 22.5 50.1 22.6 45.2 27.8 45 28.3 45.1 30.5 44.9 32.8 Z" fill="#a9a8ac"/>
      <path d="M39.2 19.3 36.8 18.5 35.6 19.7 34.5 20.2 32.8 22 32.8 22.2 36.3 23.2 38.3 21.1 38.2 20.7 38.4 20.1 Z" fill="#a9a8ac"/>
      <path d="M38.5 20.2 39 20.7 44.6 22.3 45.2 22.2 46.3 20.9 45.9 20.6 40.1 18.9 39.6 19 Z" fill="#7860b1"/>
      <path d="M28.8 17.4 28.8 17.7 29 17.7 29.1 17.5 29.3 17.7 34.8 19.3 35.2 19.1 36.5 17.9 36.2 17.7 30.3 16 30 16 Z" fill="#7860b1"/>
      <path d="M14.4 23.9 14.1 24.4 14.2 24.9 14.6 25.2 15.1 25.1 15.4 24.8 15.3 24.1 15 23.9 Z" fill="#adabdd"/>
      <path d="M16.1 22.1 15.8 22.3 15.6 23 15.8 23.3 16.3 23.4 16.7 23.2 16.8 22.6 16.6 22.2 Z" fill="#adabdd"/>
      <g transform="matrix(.0459 0 0 .0459 .49 4.71)" fill="none" stroke-linecap="round">
        <path d="M457 398 L527 424 L660 448 L742 470 L885 512 L884 598 L700 556 L640 522 L604 518 L470 468 Z" fill="#bcbdc1" stroke="none"/>
        <path d="M457 398 L527 424 L660 448 L742 470 L885 512" stroke="#e2e3e6" stroke-width="2"/>
      </g>
      <path d="M23.5 25.8 23.6 26.9 23.8 27.2 24.5 27.4 24.7 27.9 25 27.3 25.2 27.2 25.8 27.5 26.3 26.8 26.7 26.7 26.9 27 26.6 27.2 26.4 28 28 28.5 28.4 28.4 30 26.9 30 26.7 29.3 26.7 28.4 27.5 28.3 27.4 29.8 25.5 25.6 24.1 25.2 24.2 Z" fill="#525457"/>
      <path d="M26.7 26.7 26.3 26.8 25.7 27.6 25.2 27.3 25 27.4 24.8 28 22.9 30.5 23.2 30.6 21.6 32.2 20.2 33 20 32.9 21.1 32.2 19.6 32.9 18.6 33 17.9 32.7 17.4 32.1 17.5 30.5 19.2 27.8 19 27.1 18.9 27 18.4 28.2 17.3 29.7 16.9 30.7 16.8 31.8 17.2 32.8 17.7 33.3 18.5 33.6 19.9 33.4 21 32.9 23.3 30.9 25.8 27.8 26.5 27.8 26.6 27.2 26.8 27.1 Z" fill="#363739"/>
      <path d="M5.9 24.8 5.2 25.1 5.3 26 4.3 26.7 4.5 27.3 5.4 27.2 5.7 28.1 6.2 28.2 6.8 27.9 6.8 27 7.6 26.7 7.7 26.4 7.5 25.8 6.4 25.9 6.3 25 Z" fill="#525457"/>
      <path d="M10.3 20.7 9.6 19.8 8.8 19.4 8.3 19.5 7.7 19.8 7 20.6 6.9 21.6 7.2 21.5 7.6 20.7 8.6 19.9 9.3 20.1 9.7 20.7 10 20.7 10.1 21.1 10.3 21.1 Z" fill="#363739"/>
      <path d="M16.6 24.8 16.2 25 16.3 25.2 16.1 25.7 16.4 26.1 16.8 26.2 17.2 25.9 17.4 25.4 17 24.9 Z" fill="#56428e"/>
      <path d="M18 23.1 17.8 23.2 17.5 24.1 17.8 24.4 18.3 24.4 18.7 24.2 18.8 23.6 18.5 23.2 Z" fill="#56428e"/>
      <path d="M36.6 17.9 35 19.4 35.5 19.5 36.6 18.5 Z" fill="#56428e"/>
      <path d="M46.4 21 44.9 22.3 45 22.6 45.5 22.6 46.5 21.4 Z" fill="#56428e"/>
      <path d="M29.9 25.7 29.7 25.7 28.4 27.2 28.4 27.4 29.3 26.7 30 26.7 Z" fill="#363739"/>
      <g transform="matrix(.0459 0 0 .0459 .49 4.71)" fill="none" stroke-linecap="round">
        <g stroke="#6e6e74" stroke-width="2.6">
          <path d="M776 120 C745 140 700 175 684 205 C676 220 675 232 675 240"/>
          <path d="M1108 210 C1080 235 1040 275 1022 305 C1016 318 1015 328 1015 335"/>
          <path d="M1246 282 L1212 322 L985 552 Q972 566 955 563 L890 541"/>
          <path d="M660 410 L665 540 M885 482 L881 590"/>
        </g>
        <g stroke="#a3a3a8" stroke-width="2">
          <path d="M738 147 L1075 237 M712 170 L1060 262 M800 162 L790 182 M1012 222 L992 248"/>
          <path d="M458 400 L527 424 M530 420 L660 446 M668 470 L742 492 M746 482 L880 522 M746 526 L880 562"/>
        </g>
        <text transform="translate(712 212) rotate(19)" textLength="138" lengthAdjust="spacingAndGlyphs" font-family="'Arial Narrow', Arial, sans-serif" font-weight="700" font-size="27" fill="#77777d">SUPER NINTENDO</text>
        <g transform="translate(1171 163) rotate(16)"><rect x="-46" y="-12" width="92" height="24" rx="12" stroke="#929297" stroke-width="2.4"/>
          <text y="6" text-anchor="middle" textLength="70" font-family="Georgia, serif" font-weight="700" font-size="16" fill="#929297">Nintendo</text></g>
        <g transform="translate(762 504) rotate(18)" stroke="none">
          <rect width="98" height="30" rx="12" fill="#6f7074"/><rect x="3" y="3" width="44" height="24" rx="9" fill="#9c9da1"/><rect x="51" y="3" width="44" height="24" rx="9" fill="#9c9da1"/>
          ${[10, 22, 34, 58, 70, 82].map(x => `<circle cx="${x + 2}" cy="15" r="4.6" fill="#45464a"/>`).join('')}
        </g>
        <circle cx="497" cy="401" r="3.4" fill="#8c8c91" stroke="none"/>
        <g stroke="none">
          <circle cx="313" cy="431" r="16" fill="#b9b2ec"/><circle cx="343" cy="392" r="16" fill="#b9b2ec"/>
          <circle cx="385" cy="415" r="16" fill="#5b3fae"/><circle cx="353" cy="455" r="16" fill="#5b3fae"/>
          <circle cx="309" cy="426" r="7" fill="#fff" opacity=".35"/><circle cx="339" cy="387" r="7" fill="#fff" opacity=".35"/>
          <circle cx="381" cy="410" r="6" fill="#fff" opacity=".18"/><circle cx="349" cy="450" r="6" fill="#fff" opacity=".18"/>
          <g transform="translate(120 469) rotate(-12)"><path d="M-12 -36 H12 V-12 H36 V12 H12 V36 H-12 V12 H-36 V-12 H-12 Z" fill="#4f5154"/><circle r="6" fill="#5d5f63"/></g>
          <rect x="-16" y="-6.5" width="32" height="13" rx="6.5" fill="#535558" transform="translate(201 471) rotate(-52)"/>
          <rect x="-16" y="-6.5" width="32" height="13" rx="6.5" fill="#535558" transform="translate(244 461) rotate(-52)"/>
          <text transform="translate(162 410) rotate(-12)" textLength="96" lengthAdjust="spacingAndGlyphs" font-family="'Arial Narrow', Arial, sans-serif" font-weight="700" font-size="19" fill="#7c7c82">SUPER NINTENDO</text>
        </g>
      </g>
`),

    // the original grey Game Boy (only used as a case style)
    gb: svg(`
      <rect x="20" y="3" width="20" height="34" rx="2.5" fill="#c7c7c0"/>
      <rect x="22.5" y="6" width="15" height="13" rx="1.4" fill="#5f6068"/>
      <rect x="24.5" y="8" width="11" height="9" fill="#9bbc0f"/>
      ${cross(25, 26, 2.6, '#2b2b30')}
      <circle cx="35.5" cy="24.5" r="1.4" fill="#9b1c4a"/><circle cx="32.5" cy="26.5" r="1.4" fill="#9b1c4a"/>`),

    // the clear Game Boy Color, traced from a photo: the frosted see-through shell with the board
    // showing under it, the dark lens with its power light and rainbow logo, the dark d-pad in its
    // ring, B and A, the Select and Start pills, the round speaker and the big round corner
    gbc: svg(`
      <path d="M21.6 2.5 H38.4 Q39.4 2.5 39.4 3.5 V33 Q39.4 37.5 34.9 37.5 H21.6 Q20.6 37.5 20.6 36.5 V3.5 Q20.6 2.5 21.6 2.5 Z" fill="#e9eef2" fill-opacity=".62" stroke="#f4f7f9" stroke-opacity=".85" stroke-width=".45"/>
      <path d="M21.4 4 V36" stroke="#fff" stroke-opacity=".45" stroke-width=".3"/>
      <rect x="22.2" y="27.4" width="4.4" height="5" rx=".3" fill="#4d7d5a" opacity=".55"/>
      ${[[22.7, 28.2], [24.2, 29.4], [22.9, 30.8], [25.1, 31.4]].map(([x, y]) => `<rect x="${x}" y="${y}" width=".9" height=".6" fill="#23262a" opacity=".6"/>`).join('')}
      <path d="M22.6 27.9 H26 M25.6 28.4 V32.4" stroke="#c9a54a" stroke-opacity=".55" stroke-width=".18"/>
      <path d="M21.6 4.3 Q21.6 3.9 22 3.9 H38 Q38.4 3.9 38.4 4.3 V18.2 Q38.4 19.8 36.8 19.8 H23.2 Q21.6 19.8 21.6 18.2 Z" fill="#2c2c32"/>
      <rect x="24.4" y="5.8" width="11.2" height="10.9" fill="#a3a79e"/>
      <path d="M24.4 5.8 H35.6 V7.4 Q30 7 24.4 9.6 Z" fill="#fff" opacity=".12"/>
      <circle cx="22.55" cy="9" r=".28" fill="#c33b3b"/>${[23.25, 23.85, 24.45].map(x => `<path d="M${x} 8.7 Q${x + .25} 9 ${x} 9.3" stroke="#c9ccd2" stroke-width=".12" fill="none"/>`).join('')}
      <text x="25.2" y="18.75" textLength="9.6" lengthAdjust="spacingAndGlyphs" font-family="Arial, Helvetica, sans-serif" font-weight="700" font-size="1.25" fill="#a9bed2">GAME BOY <tspan fill="#e0413d">C</tspan><tspan fill="#8a5bd6">O</tspan><tspan fill="#3fb24f">L</tspan><tspan fill="#f2b632">O</tspan><tspan fill="#3d7fe0">R</tspan></text>
      <rect x="26.8" y="21.7" width="5.9" height="1.2" rx=".6" fill="none" stroke="#c4cad0" stroke-opacity=".7" stroke-width=".15"/>
      <circle cx="24.4" cy="25.4" r="3" fill="#cfd5db" fill-opacity=".45" stroke="#b9c0c8" stroke-width=".2"/>
      ${cross(24.4, 25.4, 2.65, '#2e2f34')}
      <circle cx="24.4" cy="25.4" r=".45" fill="#3d3e44"/>
      <circle cx="32.8" cy="26.2" r="1.3" fill="#2e2f34" stroke="#c9cfd5" stroke-opacity=".6" stroke-width=".25"/>
      <circle cx="36.6" cy="25.6" r="1.3" fill="#2e2f34" stroke="#c9cfd5" stroke-opacity=".6" stroke-width=".25"/>
      <rect x="27.3" y="31.1" width="2.1" height=".95" rx=".47" fill="#2e2f34" stroke="#c9cfd5" stroke-opacity=".6" stroke-width=".25"/>
      <rect x="30.15" y="31.1" width="2.1" height=".95" rx=".47" fill="#2e2f34" stroke="#c9cfd5" stroke-opacity=".6" stroke-width=".25"/>
      <circle cx="35.9" cy="33.6" r="2.7" fill="#cfd5db" fill-opacity=".35" stroke="#b9c0c8" stroke-width=".2"/>
      ${[[0, 0], [-1, -.9], [1, -.9], [-1.4, .4], [1.4, .4], [-.6, 1.4], [.6, 1.4], [0, -1.8], [-1.9, -.7], [1.9, -.7]].map(([dx, dy]) => `<circle cx="${f1(35.9 + dx)}" cy="${f1(33.6 + dy)}" r=".22" fill="#5d636b"/>`).join('')}`),

    // the black Game Boy Advance from the front, traced from a photo: the wide body with the shoulder
    // notches and the swelling grips, the grey shoulder buttons in the notches, the big lens with the
    // grey screen and the logo under it, the embossed Nintendo above, the power light, the light grey
    // d-pad, Start and Select, B and A, and the slotted speaker
    gba: svg(`
      <path d="M6.9 9.4 C7.8 7.8 9.8 7.1 12.2 6.6 L15.6 6 L15.8 7.6 L7.4 9.8 Z M53.1 9.4 C52.2 7.8 50.2 7.1 47.8 6.6 L44.4 6 L44.2 7.6 L52.6 9.8 Z" fill="#9c9da1"/>
      <path d="M19 4.9 H41 C44 4.9 45.5 6.6 48 7.4 L52.5 8.4 C54.6 8.9 55.2 10.4 55.6 12.6 C56.4 16.5 57.2 21 57.2 25 C57.2 28 55.8 29.6 53 30.6 C46 33.4 38.5 35 30 35 C21.5 35 14 33.4 7 30.6 C4.2 29.6 2.8 28 2.8 25 C2.8 21 3.6 16.5 4.4 12.6 C4.8 10.4 5.4 8.9 7.5 8.4 L12 7.4 C14.5 6.6 16 4.9 19 4.9 Z" fill="#2f2f32" stroke="#1a1a1c" stroke-width=".35"/>
      <path d="M19 5.4 H41 C44 5.4 45.4 7 48 7.8 L52.4 8.8 C54 9.2 54.7 10.4 55 11.8 C52 9.8 46 9.2 30 9.2 C14 9.2 8 9.8 5 11.8 C5.3 10.4 6 9.2 7.6 8.8 L12 7.8 C14.6 7 16 5.4 19 5.4 Z" fill="#fff" opacity=".07"/>
      <rect x="26.4" y="5.5" width="6.6" height="1.25" rx=".62" fill="none" stroke="#4a4a4f" stroke-width=".2"/>
      <path d="M17.2 7.4 H42.8 Q45.1 7.4 45.1 9.7 V28.2 Q45.1 31.4 41.9 31.4 H18.1 Q14.9 31.4 14.9 28.2 V9.7 Q14.9 7.4 17.2 7.4 Z" fill="#151517" stroke="#3c3c40" stroke-width=".25"/>
      <rect x="17.8" y="9.4" width="24.4" height="16" rx=".3" fill="#a6a8a3"/>
      <path d="M17.8 9.4 H42.2 V11.6 Q30 11 17.8 14.2 Z" fill="#fff" opacity=".14"/>
      <text x="30" y="28.9" text-anchor="middle" textLength="15.6" lengthAdjust="spacingAndGlyphs" font-family="Arial, Helvetica, sans-serif" font-weight="700" font-size="1.45" fill="#e9e9ea">GAME BOY ADVANCE</text>
      <circle cx="47" cy="8.9" r=".5" fill="#121213" stroke="#4a4a4f" stroke-width=".15"/>
      <text x="47.8" y="9.25" font-family="Arial, Helvetica, sans-serif" font-size=".95" fill="#8d8d91">POWER</text>
      ${cross(9.4, 15.8, 4.15, '#1f1f21')}
      ${cross(9.4, 15.8, 3.75, '#cfd0d3')}
      <circle cx="9.4" cy="15.8" r=".55" fill="#b9babe"/>
      <rect x="7.6" y="22.3" width="5.3" height="1.75" rx=".87" fill="#29292c" stroke="#4a4a4f" stroke-width=".15"/>
      <rect x="7.6" y="25.5" width="5.3" height="1.75" rx=".87" fill="#29292c" stroke="#4a4a4f" stroke-width=".15"/>
      <circle cx="12.4" cy="23.2" r=".85" fill="#cfd0d3"/><circle cx="12.4" cy="26.4" r=".85" fill="#cfd0d3"/>
      <circle cx="47.5" cy="16.7" r="1.65" fill="#cfd0d3" stroke="#1d1d1f" stroke-width=".25"/>
      <circle cx="52.3" cy="14.9" r="1.7" fill="#cfd0d3" stroke="#1d1d1f" stroke-width=".25"/>
      ${[0, 1, 2, 3, 4, 5].map(i => `<rect x="46.3" y="${f1(21.6 + i * 1.18)}" width="6.3" height=".5" rx=".25" fill="#141415"/>`).join('')}`),

    // the black DS Lite, open, traced from a photo (in the photo's pixels, scaled into the frame). Each half
    // is drawn flat in millimetres to the real layout, with its 3" screen, and laid onto the photo's angle
    // by the corners of that screen: the lid with its speakers either side, the hinge, and the lower half
    // with the d-pad, X Y A B, Start and Select, and the volume slider and Game Boy slot along the front
    ds: svg(`<g transform="matrix(.0366 0 0 .0366 6.54 .26)" font-family="Arial, Helvetica, sans-serif">
      <path d="M458 36 L442 49 L272 348 L271 378 L251 396 L249 424 L35 704 L45 765 L62 782 L827 1042 L849 1041 L1065 723 L1067 635 L1083 624 L1246 300 L1246 269 L1230 254 L484 36 Z" fill="#111113" stroke="#4e4e55" stroke-width="5" stroke-linejoin="round"/>
      <g transform="matrix(6.113 1.815 -2.817 5.409 755.5 332.25)">
        <rect x="-65.5" y="-33.5" width="131" height="67" rx="5" fill="#26262a"/>
        <path d="M-60 -32.6 H60" stroke="#fff" stroke-opacity=".1" stroke-width=".6" stroke-linecap="round"/>
        <rect x="-36" y="-28" width="72" height="56" rx="1.2" fill="#1a1a1d" stroke="#3a3a3f" stroke-width=".35"/>
        <rect x="-31" y="-23.25" width="62" height="46.5" fill="#8e8f8c"/>
        <path d="M-31 -23.25 H31 V-14 Q0 -20 -31 -4 Z" fill="#fff" opacity=".08"/>
        ${[-50, 50].map(x => [[-3, -1.6], [0, -1.6], [3, -1.6], [-1.5, 1.6], [1.5, 1.6], [4.5, 1.6]].map(([dx, dy]) => `<circle cx="${x + dx - .75}" cy="${dy}" r=".75" fill="#0b0b0c"/>`).join('')).join('')}
        ${[[-59, -29], [57, -29], [-59, 27], [57, 27]].map(([x, y]) => `<rect x="${x}" y="${y}" width="2.4" height="2.4" rx=".3" fill="none" stroke="#3c3c41" stroke-width=".3"/>`).join('')}
      </g>
      <g transform="matrix(6.169 1.992 -3.151 4.527 541.75 693.25)">
        <rect x="-64.5" y="-38" width="129" height="76" rx="4" fill="#232326"/>
        <rect x="-66" y="-41.5" width="132" height="7" rx="3.5" fill="#1b1b1e"/>
        <rect x="-66" y="-41.5" width="18" height="7" rx="3.5" fill="#2c2c30"/><rect x="48" y="-41.5" width="18" height="7" rx="3.5" fill="#2c2c30"/>
        <path d="M-64 -40 H64" stroke="#fff" stroke-opacity=".12" stroke-width=".5"/>
        <rect x="-.6" y="-34" width="1.2" height="2.4" rx=".6" fill="#050506"/>
        <rect x="-35" y="-27" width="70" height="54" rx="1.2" fill="#18181b" stroke="#3a3a3f" stroke-width=".35"/>
        <rect x="-31" y="-23.25" width="62" height="46.5" fill="#8e8f8c"/>
        <path d="M-31 -23.25 H31 V-12 Q0 -18 -31 -2 Z" fill="#fff" opacity=".07"/>
        <path d="M-52.7 -13.5 H-47.3 V-7.9 H-41.7 V-2.5 H-47.3 V3.1 H-52.7 V-2.5 H-58.3 V-7.9 H-52.7 Z" fill="#141416" stroke="#45454b" stroke-width=".35" stroke-linejoin="round"/>
        <path d="M-50 -11.6 V-9.6 M-50 -1 V1 M-56.4 -5.2 H-54.4 M-45.6 -5.2 H-43.6" stroke="#d6d6d9" stroke-width=".45" stroke-linecap="round"/>
        ${[['X', 51, -17], ['Y', 44, -9], ['A', 58.5, -9], ['B', 51.5, -1]].map(([l, x, y]) => `<circle cx="${x}" cy="${y}" r="3.2" fill="#161618" stroke="#45454b" stroke-width=".35"/><text x="${x}" y="${y + 1.2}" text-anchor="middle" font-weight="700" font-size="3.3" fill="#d6d6d9">${l}</text>`).join('')}
        <circle cx="42.4" cy="17.5" r="1.5" fill="#161618" stroke="#45454b" stroke-width=".3"/><circle cx="42.8" cy="25.3" r="1.5" fill="#161618" stroke="#45454b" stroke-width=".3"/>
        <text x="45" y="18.6" font-size="2.3" fill="#c9c9cc">START</text><text x="45.4" y="26.4" font-size="2.3" fill="#c9c9cc">SELECT</text>
      </g>
      <path d="M240 812 L628 940 L628 975 L240 846 Z" fill="#0a0a0b" stroke="#2c2c30" stroke-width="3"/>
      <rect x="-28" y="-9" width="56" height="18" rx="2" fill="#0a0a0b" stroke="#3a3a3f" stroke-width="2.5" transform="translate(153 787) rotate(18)"/>
      <rect x="-6" y="-5" width="12" height="10" fill="#4a4a50" transform="translate(146 791) rotate(18)"/>
      <text x="150" y="768" text-anchor="middle" font-size="15" fill="#8a8a90" transform="rotate(18 150 768)">VOL</text>
      <circle cx="740" cy="985" r="9" fill="#0a0a0b" stroke="#3a3a3f" stroke-width="2.5"/>
    </g>`),

    // the black New Nintendo 3DS XL, open, at the DS Lite's angle (each half drawn flat in millimetres to the real
    // layout, 160 mm wide, and laid on the same corners as the DS Lite's, a little smaller to fit): the lid with the
    // wide top screen, the camera above it, the speakers either side and the 3D slider; the hinge; the lower half with
    // the circle pad over the d-pad, the C-stick nub over X Y A B, Start and Select, and Home under the lower screen
    '3ds': svg(`<g transform="matrix(.0366 0 0 .0366 6.54 .26)" font-family="Arial, Helvetica, sans-serif">
      ${[[0, 20, '#08080a'], [0, 0, null]].map(([dx, dy, side]) => `
      <g transform="translate(${dx} ${dy}) matrix(6.113 1.815 -2.817 5.409 755.5 332.25) scale(.82)">
        <rect x="-80" y="-44" width="160" height="88" rx="9" fill="${side || '#17181c'}" ${side ? '' : 'stroke="#4e4e55" stroke-width=".9"'}/>
        ${side ? '' : `
        <path d="M-72 -43 H72" stroke="#fff" stroke-opacity=".14" stroke-width=".8" stroke-linecap="round"/>
        <rect x="-56" y="-37" width="112" height="72" rx="2" fill="#0d0d10" stroke="#34343a" stroke-width=".4"/>
        <rect x="-49.7" y="-31" width="99.4" height="59.6" fill="#4a4d55"/>
        <path d="M-49.7 -31 H49.7 V-18 Q0 -26 -49.7 -6 Z" fill="#fff" opacity=".08"/>
        <circle cx="0" cy="-34" r="1.3" fill="#050506" stroke="#3a3a40" stroke-width=".3"/>
        ${[-66, 66].map(x => [-6, -2, 2, 6].map(dy => [-2, 2].map(dx => `<circle cx="${x + dx}" cy="${dy}" r=".8" fill="#050506"/>`).join('')).join('')).join('')}
        <rect x="67" y="-30" width="3" height="16" rx="1.5" fill="#0a0a0c" stroke="#3a3a40" stroke-width=".3"/><rect x="67.4" y="-25" width="2.2" height="4" rx=".8" fill="#55565c"/>`}
      </g>`).join('')}
      ${[[0, 26, '#08080a'], [0, 0, null]].map(([dx, dy, side]) => `
      <g transform="translate(${dx} ${dy}) matrix(6.169 1.992 -3.151 4.527 541.75 693.25) scale(.82)">
        <rect x="-80" y="-46" width="160" height="92" rx="9" fill="${side || '#1a1b1f'}" ${side ? '' : 'stroke="#4e4e55" stroke-width=".9"'}/>
        ${side ? '' : `
        <rect x="-78" y="-50" width="156" height="8" rx="4" fill="#121216"/>
        <rect x="-78" y="-50" width="22" height="8" rx="4" fill="#26272c"/><rect x="56" y="-50" width="22" height="8" rx="4" fill="#26272c"/>
        <path d="M-76 -48.5 H76" stroke="#fff" stroke-opacity=".12" stroke-width=".6"/>
        <rect x="-46.5" y="-38" width="93" height="71" rx="2" fill="#0d0d10" stroke="#34343a" stroke-width=".4"/>
        <rect x="-42.5" y="-34" width="85" height="63.7" fill="#4a4d55"/>
        <path d="M-42.5 -34 H42.5 V-22 Q0 -29 -42.5 -10 Z" fill="#fff" opacity=".07"/>
        <circle cx="-62" cy="-22" r="9.5" fill="#0c0c0e" stroke="#45454b" stroke-width=".45"/><circle cx="-62" cy="-22" r="6.5" fill="#26272c" stroke="#45454b" stroke-width=".35"/>
        <path d="M-64.5 -1 H-59.5 V4.5 H-54 V9.5 H-59.5 V15 H-64.5 V9.5 H-70 V4.5 H-64.5 Z" fill="#111114" stroke="#45454b" stroke-width=".4" stroke-linejoin="round"/>
        <circle cx="55" cy="-35" r="2.2" fill="#2e2f35" stroke="#45454b" stroke-width=".3"/>
        ${[['X', 62, -27], ['Y', 54.5, -19.5], ['A', 69.5, -19.5], ['B', 62, -12]].map(([l, x, y]) => `<circle cx="${x}" cy="${y}" r="3.4" fill="#111114" stroke="#45454b" stroke-width=".4"/><text x="${x}" y="${y + 1.2}" text-anchor="middle" font-weight="700" font-size="3.4" fill="#d6d6d9">${l}</text>`).join('')}
        <circle cx="57" cy="4" r="1.6" fill="#111114" stroke="#45454b" stroke-width=".3"/><circle cx="67" cy="4" r="1.6" fill="#111114" stroke="#45454b" stroke-width=".3"/>
        <text x="57" y="9" text-anchor="middle" font-size="2.2" fill="#c9c9cc">SELECT</text><text x="67" y="9" text-anchor="middle" font-size="2.2" fill="#c9c9cc">START</text>
        <rect x="-5" y="36" width="10" height="4.4" rx="2.2" fill="#111114" stroke="#45454b" stroke-width=".35"/>
        <path d="M-1.6 39.4 V37.6 L0 36.6 L1.6 37.6 V39.4 Z" fill="none" stroke="#c9c9cc" stroke-width=".35"/>`}
      </g>`).join('')}
    </g>`),

    // the white Wii standing in its grey stand on the clear disc, with a Wii Remote leaning on it and its
    // strap, traced from a photo (in the photo's pixels, scaled into the frame): the narrow front with
    // Power, Reset, the SD door, the disc slot, Eject and the Wii logo, the long side with its seam and
    // badges, and the remote from Power and the d-pad down to the player lights and its own Wii logo
    wii: svg(`<g transform="matrix(.0307 0 0 .0307 8.76 -.23)">
      <ellipse cx="620" cy="1052" rx="240" ry="42" fill="#e8eaed" fill-opacity=".22" stroke="#eef0f2" stroke-opacity=".7" stroke-width="4"/>
      <path d="M350 915 L960 640 Q966 638 966 646 L962 822 Q961 830 954 833 L352 1150 Z" fill="#8b8c90"/>
      <path d="M155 868 L350 915 L352 1150 L156 1100 Q150 1098 150 1092 V876 Q150 866 155 868 Z" fill="#a8a9ad"/>
      <path d="M155 868 L190 858 L350 900 L960 628 L960 640 L350 915 Z" fill="#c8c9cc"/>
      <path d="M405 62 L944 94 Q958 95 958 110 L950 640 L350 918 Z" fill="#f4f4f5"/>
      <path d="M450 96 L948 110" stroke="#8d8e92" stroke-width="3"/>
      <path d="M440 100 L400 900" stroke="#dcdde0" stroke-width="2"/>
      <rect x="465" y="125" width="30" height="30" rx="3" fill="none" stroke="#c4c5c8" stroke-width="2"/>
      <text x="480" y="146" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-weight="700" font-style="italic" font-size="17" fill="#b9babd">ATI</text>
      <rect x="866" y="152" width="72" height="26" rx="13" fill="none" stroke="#cfd0d3" stroke-width="3"/>
      <path d="M248 58 H405 L350 918 L196 893 Q192 892 192 886 L234 70 Q235 58 248 58 Z" fill="#e6e7e9" stroke="#c9cacd" stroke-width="2"/>
      <path d="M352 140 Q362 140 361 152 L316 836 Q315 848 304 848 Q294 848 295 836 L340 152 Q341 140 352 140 Z" fill="#111214" stroke="#b5b6b9" stroke-width="3"/>
      <rect x="240" y="142" width="43" height="30" rx="4" fill="#f4f4f5" stroke="#b5b6b9" stroke-width="2.5"/>
      <circle cx="244" cy="157" r="3" fill="#2a2b2e"/><path d="M262 150 V157 M256 153 A8 8 0 1 0 268 153" stroke="#6d6e72" stroke-width="2.4" fill="none"/>
      <rect x="232" y="219" width="46" height="26" rx="4" fill="#f4f4f5" stroke="#b5b6b9" stroke-width="2.5"/>
      <g font-family="Arial, Helvetica, sans-serif" font-size="11" fill="#77787c" text-anchor="middle"><text x="260" y="190">POWER</text><text x="255" y="261">RESET</text><text x="220" y="790">EJECT</text></g>
      <path d="M226 318 L272 322 L251 656 L203 652 Z" fill="#e0e1e3" stroke="#b5b6b9" stroke-width="2.5"/>
      <rect x="196" y="800" width="44" height="32" rx="4" fill="#f4f4f5" stroke="#b5b6b9" stroke-width="2.5"/>
      <path d="M210 820 L218 808 L226 820 Z M210 824 H226" stroke="#6d6e72" stroke-width="2" fill="#6d6e72"/>
      <text x="265" y="890" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-weight="700" font-size="40" fill="#8e8f93">Wii</text>
      <path d="M790 1086 Q770 1100 738 1110" stroke="#b5b6b9" stroke-width="7" fill="none"/>
      <path d="M700 1118 C640 1130 615 1180 650 1222" stroke="#c4c5c8" stroke-width="36" fill="none"/>
      <path d="M690 1196 L1206 1050 Q1240 1042 1240 1074 L1240 1092 Q1240 1104 1228 1108 L708 1250 Z" fill="#c4c5c8"/>
      <path d="M720 1212 L1214 1072 M724 1232 L1220 1092" stroke="#aeafb2" stroke-width="2.5"/>
      <rect x="-30" y="-24" width="60" height="48" rx="8" fill="#d6d7da" stroke="#a3a4a7" stroke-width="2.5" transform="translate(710 1118) rotate(-18)"/>
      <rect x="-40" y="-30" width="80" height="60" rx="8" fill="#d6d7da" stroke="#a3a4a7" stroke-width="2.5" transform="translate(712 1214) rotate(-16)"/>
      <g transform="matrix(.9884 -.1628 .2229 .9745 575 320)">
        <rect width="172" height="785" rx="30" fill="#f6f6f7" stroke="#c4c5c8" stroke-width="3"/>
        <circle cx="35" cy="40" r="14" fill="#f2f2f3" stroke="#a9aaad" stroke-width="2.5"/><path d="M35 33 V40 M29 36 A8 8 0 1 0 41 36" stroke="#d2323a" stroke-width="2.6" fill="none"/>
        <text x="35" y="70" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="11" fill="#77787c">POWER</text>
        <path d="M74 84 H98 V116 H130 V140 H98 V172 H74 V140 H42 V116 H74 Z" fill="#fbfbfc" stroke="#3a3b3e" stroke-width="3" stroke-linejoin="round"/>
        <path d="M86 92 V108 M86 148 V164 M50 128 H66 M106 128 H122" stroke="#b9babd" stroke-width="2.5"/>
        <circle cx="86" cy="245" r="30" fill="#c9cacd" stroke="#9fa0a3" stroke-width="3"/><circle cx="86" cy="245" r="22" fill="#d8d9dc"/>
        <text x="86" y="252" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="20" fill="#9a9b9e">A</text>
        <circle cx="34" cy="378" r="15" fill="#fbfbfc" stroke="#9fa0a3" stroke-width="2.5"/><path d="M27 378 H41" stroke="#7a7b7f" stroke-width="2.6"/>
        <circle cx="86" cy="376" r="15" fill="#fbfbfc" stroke="#9fa0a3" stroke-width="2.5"/><path d="M79 381 V375 L86 369 L93 375 V381 Z" fill="#4a90e2"/>
        <circle cx="138" cy="373" r="15" fill="#fbfbfc" stroke="#9fa0a3" stroke-width="2.5"/><path d="M131 373 H145 M138 366 V380" stroke="#7a7b7f" stroke-width="2.6"/>
        <text x="88" y="408" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="11" fill="#77787c">HOME</text>
        ${[[0, 3], [1, 4], [2, 3], [3, 4], [4, 3], [5, 2]].map(([r, n]) => Array.from({ length: n }, (_, i) => `<circle cx="${86 + (i - (n - 1) / 2) * 13}" cy="${448 + r * 13}" r="4" fill="#1c1d1f"/>`).join('')).join('')}
        <circle cx="86" cy="580" r="20" fill="#fbfbfc" stroke="#9fa0a3" stroke-width="2.5"/><text x="86" y="587" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="18" fill="#6d6e72">1</text>
        <circle cx="86" cy="648" r="20" fill="#fbfbfc" stroke="#9fa0a3" stroke-width="2.5"/><text x="86" y="655" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="18" fill="#6d6e72">2</text>
        ${[36, 69, 103, 136].map(x => `<rect x="${x - 5}" y="698" width="10" height="10" fill="#141416"/>`).join('')}
        <text x="86" y="760" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-weight="700" font-size="44" fill="#8e8f93">Wii</text>
      </g></g>`),

    // the black Wii U and its GamePad, traced from a photo (in the photo's pixels, scaled into the frame):
    // the console lying behind on the right, its near end hidden by the pad's side, with its disc slot, the flap under it, Eject, Power, Sync and
    // the Wii U logo, and the GamePad standing in front. The pad is drawn flat in millimetres to its real
    // shape (255 × 134, a 16:9 screen), squeezed as the camera sees it and turned to the photo's angle,
    // so the screen stays a true rectangle: the screen in its glass, the
    // speaker bar, the two sticks, the d-pad, X Y A B, + and -, and Home, TV and Power along the bottom
    wiiu: svg(`<g transform="matrix(.047 0 0 .047 -.05 5.47)" font-family="Arial, Helvetica, sans-serif">
      <path d="M758 203 L1212 298 Q1234 303 1229 318 L835 390 L680 325 L700 240 Q720 200 758 203 Z" fill="#202023" stroke="#55555c" stroke-width="2.5"/>
      <path d="M760 214 L1200 306" stroke="#fff" stroke-opacity=".12" stroke-width="5"/>
      <path d="M680 325 L835 390 Q852 400 856 430 L872 552 L680 473 Z" fill="#111113"/>
      <path d="M835 390 L1229 318 Q1238 330 1236 380 L1232 430 Q1230 444 1218 447 L872 552 L856 430 Q852 400 835 390 Z" fill="#29292d" stroke="#55555c" stroke-width="2.5"/>
      <path d="M925 428 L1193 350 Q1199 352 1196 359 L928 441 Q921 441 921 435 Z" fill="#050506" stroke="#4a4a50" stroke-width="2"/>
      <path d="M997 448 L1160 410 Q1166 409 1166 415 V448 Q1166 454 1160 456 L1000 500 Q994 501 994 495 V454 Q994 449 997 448 Z" fill="none" stroke="#45454b" stroke-width="2.5"/>
      <circle cx="970" cy="487" r="7" fill="#8a1f26" stroke="#4a4a50" stroke-width="2"/>
      <rect x="884" y="432" width="24" height="18" rx="6" fill="#2e2e33" stroke="#55555b" stroke-width="2"/><path d="M890 444 L896 436 L902 444 Z" fill="#9a9aa0"/>
      <circle cx="895" cy="514" r="15" fill="#2e2e33" stroke="#55555b" stroke-width="2"/><path d="M895 506 V514 M889 509 A8 8 0 1 0 901 509" stroke="#9a9aa0" stroke-width="2" fill="none"/>
      <text x="1200" y="424" text-anchor="middle" font-weight="700" font-size="24" fill="#8a8a90" transform="rotate(-14 1200 424)">Wii<tspan fill="#6f6f75">U</tspan></text>
      <g transform="translate(426 280) rotate(9) scale(2.71 3.4)"><rect x="-118" y="-61" width="245.5" height="128" rx="38" fill="#0f0f11" stroke="#55555c" stroke-width=".9"/>
        <path d="M118 -40 Q127 -20 127 0 Q127 25 118 48" stroke="#fff" stroke-opacity=".1" stroke-width="1.2" fill="none"/></g>
      <g transform="translate(371 284) rotate(9) scale(2.71 3.4)">
        <rect x="-118" y="-61" width="245.5" height="128" rx="38" fill="#18181a" stroke="#66666e" stroke-width=".9"/>
        <path d="M-84 -58.8 H90 stroke="#fff" stroke-opacity=".14" stroke-width="1.6" stroke-linecap="round"/>
        <rect x="-73" y="-43" width="146" height="86" rx="3" fill="#0e0e10" stroke="#2c2c30" stroke-width=".6"/>
        <rect x="-68.6" y="-38.6" width="137.2" height="77.2" fill="#020203"/>
        <path d="M-68.6 -38.6 H68.6 V-12 Q0 -28 -68.6 -6 Z" fill="#fff" opacity=".06"/>
        <rect x="-36" y="-60.5" width="72" height="9" rx="4.5" fill="#050506" stroke="#2c2c30" stroke-width=".5"/>
        ${[-104, 104].map(x => `<circle cx="${x}" cy="-37" r="13" fill="#0f0f11"/><circle cx="${x}" cy="-37" r="10.5" fill="#26262a" stroke="#3e3e43" stroke-width=".7"/><circle cx="${x}" cy="-37" r="6.5" fill="#1e1e21"/><circle cx="${x - 2.5}" cy="-39.5" r="3" fill="#fff" opacity=".1"/>`).join('')}
        <path d="M-98.5 -15 H-91.5 V-9.5 H-86 V-2.5 H-91.5 V3 H-98.5 V-2.5 H-104 V-9.5 H-98.5 Z" fill="#232326" stroke="#4a4a50" stroke-width=".6" stroke-linejoin="round"/>
        <rect x="-98" y="20" width="9" height="6" rx="1" fill="none" stroke="#5a5a60" stroke-width=".7"/>
        ${[['X', 96.5, -18.5], ['Y', 85, -7.4], ['A', 108, -7.4], ['B', 96.5, 3.7]].map(([l, x, y]) => `<circle cx="${x}" cy="${y}" r="5" fill="#232326" stroke="#4a4a50" stroke-width=".6"/><text x="${x}" y="${y + 1.9}" text-anchor="middle" font-size="5.2" fill="#9a9aa0">${l}</text>`).join('')}
        <circle cx="84" cy="20" r="3.8" fill="#232326" stroke="#4a4a50" stroke-width=".5"/><path d="M82 20 H86 M84 18 V22" stroke="#9a9aa0" stroke-width=".6"/>
        <circle cx="84" cy="33" r="3.8" fill="#232326" stroke="#4a4a50" stroke-width=".5"/><path d="M82 33 H86" stroke="#9a9aa0" stroke-width=".6"/>
        <ellipse cx="-84" cy="53" rx="1.6" ry="1" fill="#000"/><ellipse cx="84" cy="53" rx="1.6" ry="1" fill="#000"/>
        <text x="-71" y="55" font-weight="700" font-size="8" fill="#8a8a90">Wii<tspan fill="#6f6f75">U</tspan></text>
        <circle cx="-20" cy="53.5" r=".8" fill="#5a5a60"/>
        <circle cx="-4" cy="53.5" r="5.4" fill="#232326" stroke="#5a5a60" stroke-width=".6"/><path d="M-6.6 55 V53 L-4 50.6 L-1.4 53 V55 Z" fill="#9a9aa0"/>
        <circle cx="37" cy="53.5" r=".9" fill="#5a5a60"/>
        <rect x="47.6" y="50.4" width="6.2" height="6.2" rx="1" fill="#232326" stroke="#3d8fd8" stroke-width=".55"/><text x="50.7" y="55" text-anchor="middle" font-weight="700" font-size="3.4" fill="#4aa3f0">TV</text>
        <circle cx="62" cy="53.5" r="3.8" fill="#232326" stroke="#4a4a50" stroke-width=".5"/><path d="M62 51.6 V53.6 M60.4 52.2 A2.2 2.2 0 1 0 63.6 52.2" stroke="#e0404a" stroke-width=".6" fill="none"/>
      </g>
    </g>`),

    // the Switch with neon blue and red Joy-Con, from the front, drawn in millimetres to its real size
    // (239 × 102) with the layout measured from a photo: the minus, stick, arrow buttons and capture on the
    // left, the plus, X Y A B, stick and home on the right, and the tablet with its 6.2" screen between
    switch: svg(`<g transform="matrix(.226 0 0 .226 3 8.47)">
      <path d="M35.9 0 H17 Q0 0 0 17 V85 Q0 102 17 102 H35.9 Z" fill="#14b9ea"/>
      <path d="M203.1 0 H222 Q239 0 239 17 V85 Q239 102 222 102 H203.1 Z" fill="#ff4b4b"/>
      <path d="M17 1.4 H34.5 M222 1.4 H204.5" stroke="#fff" stroke-opacity=".22" stroke-width="1.2" stroke-linecap="round"/>
      <rect x="35.9" y="0" width="167.2" height="102" fill="#2a2b30"/>
      <rect x="38.4" y="2.5" width="162.2" height="97" rx="1.5" fill="#1b1c20"/>
      <rect x="51" y="12.5" width="137" height="77" fill="#3c4556"/>
      <path d="M51 12.5 H120 L51 62 Z" fill="#fff" opacity=".07"/>
      <path d="M35.9 0 V102 M203.1 0 V102" stroke="#0e0e10" stroke-width="1"/>
      <rect x="25" y="10.1" width="6" height="1.4" rx=".5" fill="#1d1e22"/>
      <circle cx="18" cy="26" r="7.4" fill="#16171a"/><circle cx="18" cy="26" r="5.6" fill="#26272c"/><circle cx="18" cy="26" r="3.6" fill="#1d1e22"/>
      ${[[18, 45.6, 0], [26.4, 54, 90], [18, 62.4, 180], [9.6, 54, 270]].map(([x, y, r]) => `<circle cx="${x}" cy="${y}" r="3.7" fill="#1d1e22"/><path d="M${x - 1.2} ${y + .7} L${x} ${y - 1} L${x + 1.2} ${y + .7} Z" fill="#8e9097" transform="rotate(${r} ${x} ${y})"/>`).join('')}
      <rect x="17" y="70" width="6" height="6" rx="1" fill="#1d1e22"/><circle cx="20" cy="73" r="1.7" fill="#2c2d33"/>
      <path d="M208.5 10.8 H214.5 M211.5 7.8 V13.8" stroke="#1d1e22" stroke-width="1.5"/>
      ${[['X', 221, 18.6], ['Y', 212.6, 27], ['A', 229.4, 27], ['B', 221, 35.4]].map(([l, x, y]) => `<circle cx="${x}" cy="${y}" r="3.7" fill="#1d1e22"/><text x="${x}" y="${y + 1.5}" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-weight="700" font-size="4.2" fill="#d9dade">${l}</text>`).join('')}
      <circle cx="221" cy="56" r="7.4" fill="#16171a"/><circle cx="221" cy="56" r="5.6" fill="#26272c"/><circle cx="221" cy="56" r="3.6" fill="#1d1e22"/>
      <circle cx="219" cy="74" r="3.9" fill="#8e9097"/><circle cx="219" cy="74" r="3" fill="#1d1e22"/><path d="M217.4 75.4 V73.8 L219 72.3 L220.6 73.8 V75.4 Z" fill="#d9dade"/>
    </g>`),

    // the Switch 2 from the front, drawn in millimetres to its real size (272 × 116) with the layout measured
    // from a photo: the dark Joy-Con 2 with the blue and red rings round their sticks and the coloured inner
    // edges, minus, arrows and capture on the left, plus, X Y A B, home and C on the right, and the 7.9" screen
    switch2: svg(`<g transform="matrix(.2059 0 0 .2059 2 8.05)">
      <path d="M37 0 H17 Q1.5 0 1 15 L0 94 Q0 116 21 116 H37 Z" fill="#26272b"/>
      <path d="M235 0 H255 Q270.5 0 271 15 L272 94 Q272 116 251 116 H235 Z" fill="#26272b"/>
      <rect x="37" y="0" width="198" height="116" fill="#18191c"/>
      <rect x="38.6" y="1.6" width="194.8" height="112.8" rx="1.5" fill="#101114"/>
      <rect x="48.5" y="8.8" width="175" height="98.4" fill="#2f3848"/>
      <path d="M48.5 8.8 H130 L48.5 70 Z" fill="#fff" opacity=".06"/>
      <rect x="35.9" y="4" width="1.3" height="108" rx=".65" fill="#1fa8e0"/><rect x="234.8" y="4" width="1.3" height="108" rx=".65" fill="#ff3c3c"/>
      <rect x="28.7" y="11.6" width="6" height="1.4" rx=".5" fill="#4a4b52"/>
      <circle cx="19.4" cy="28" r="8.8" fill="none" stroke="#2bd0e6" stroke-width="1.3"/>
      <circle cx="19.4" cy="28" r="7.6" fill="#17181b"/><circle cx="19.4" cy="28" r="5.6" fill="#2b2c31"/><circle cx="19.4" cy="28" r="3.6" fill="#222327"/>
      ${[[17.9, 47.9, 0], [26.5, 56.5, 90], [17.9, 65.1, 180], [9.3, 56.5, 270]].map(([x, y, r]) => `<circle cx="${x}" cy="${y}" r="3.9" fill="#323338"/><path d="M${x - 1.2} ${y + .7} L${x} ${y - 1} L${x + 1.2} ${y + .7} Z" fill="#8e9097" transform="rotate(${r} ${x} ${y})"/>`).join('')}
      <rect x="20.7" y="74" width="6" height="6" rx="1" fill="#323338"/>
      <path d="M238 14.4 H244 M241 11.4 V17.4" stroke="#4a4b52" stroke-width="1.5"/>
      ${[['X', 251.6, 21.6], ['Y', 243, 30.2], ['A', 260.2, 30.2], ['B', 251.6, 38.8]].map(([l, x, y]) => `<circle cx="${x}" cy="${y}" r="3.9" fill="#323338"/><text x="${x}" y="${y + 1.5}" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="4.2" fill="#9a9ba1">${l}</text>`).join('')}
      <circle cx="251.6" cy="60.3" r="8.8" fill="none" stroke="#ff4a2e" stroke-width="1.3"/>
      <circle cx="251.6" cy="60.3" r="7.6" fill="#17181b"/><circle cx="251.6" cy="60.3" r="5.6" fill="#2b2c31"/><circle cx="251.6" cy="60.3" r="3.6" fill="#222327"/>
      <circle cx="243.2" cy="81" r="3.4" fill="#323338"/><path d="M241.7 82.3 V80.8 L243.2 79.4 L244.7 80.8 V82.3 Z" fill="#9a9ba1"/>
      <rect x="240.2" y="90.7" width="6" height="6" rx="1" fill="#323338"/><text x="243.2" y="95.2" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-weight="700" font-size="4" fill="#9a9ba1">C</text>
    </g>`),

    // a monitor
    pc: svg(`
      <rect x="11" y="5" width="38" height="24" rx="1.6" fill="#1f2228"/>
      <rect x="13" y="7" width="34" height="20" fill="#2f6aa8"/>
      <path d="M13 7 L30 7 L13 20 Z" fill="#fff" opacity=".12"/>
      <path d="M27 29 L33 29 L35 34.5 L25 34.5 Z" fill="#2b2e35"/>
      <rect x="21" y="34.5" width="18" height="2" rx="1" fill="#1f2228"/>`),
  };
})();
