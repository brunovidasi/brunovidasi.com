/* ---------- preset path generators ---------- */

function starPath(cx, cy, outerR, innerR, points) {
  let d = '';
  for (let i = 0; i < points * 2; i++) {
    const r = i % 2 === 0 ? outerR : innerR;
    const angle = (Math.PI / points) * i - Math.PI / 2;
    const x = cx + r * Math.cos(angle);
    const y = cy + r * Math.sin(angle);
    d += (i === 0 ? 'M' : 'L') + x.toFixed(2) + ',' + y.toFixed(2) + ' ';
  }
  return d + 'Z';
}

function wavePath(width, height, amplitude, cycles) {
  const step = width / cycles;
  let d = `M0,${height / 2} `;
  for (let i = 0; i < cycles; i++) {
    const x0 = i * step;
    const cpY1 = height / 2 - amplitude;
    const cpY2 = height / 2 + amplitude;
    d += `C${x0 + step / 4},${cpY1} ${x0 + step / 4},${cpY1} ${x0 + step / 2},${height / 2} `;
    d += `C${x0 + step * 0.75},${cpY2} ${x0 + step * 0.75},${cpY2} ${x0 + step},${height / 2} `;
  }
  return d.trim();
}

function spiralPath(cx, cy, turns, maxR) {
  const steps = turns * 40;
  let d = '';
  for (let i = 0; i <= steps; i++) {
    const t = i / steps;
    const angle = t * turns * Math.PI * 2;
    const r = maxR * t;
    const x = cx + r * Math.cos(angle);
    const y = cy + r * Math.sin(angle);
    d += (i === 0 ? 'M' : 'L') + x.toFixed(2) + ',' + y.toFixed(2) + ' ';
  }
  return d.trim();
}

function infinityPath(cx, cy, a) {
  const steps = 100;
  let d = '';
  for (let i = 0; i <= steps; i++) {
    const t = (i / steps) * Math.PI * 2;
    const denom = 1 + Math.sin(t) * Math.sin(t);
    const x = cx + (a * Math.cos(t)) / denom;
    const y = cy + (a * Math.sin(t) * Math.cos(t)) / denom;
    d += (i === 0 ? 'M' : 'L') + x.toFixed(2) + ',' + y.toFixed(2) + ' ';
  }
  return d.trim() + 'Z';
}

function catmullRomToBezier(points, closed) {
  const n = points.length;
  const at = (i) => points[((i % n) + n) % n];
  let d = `M${points[0][0]},${points[0][1]} `;
  const count = closed ? n : n - 1;
  for (let i = 0; i < count; i++) {
    const p0 = at(i - 1);
    const p1 = at(i);
    const p2 = at(i + 1);
    const p3 = at(i + 2);
    const c1x = p1[0] + (p2[0] - p0[0]) / 6;
    const c1y = p1[1] + (p2[1] - p0[1]) / 6;
    const c2x = p2[0] - (p3[0] - p1[0]) / 6;
    const c2y = p2[1] - (p3[1] - p1[1]) / 6;
    d += `C${c1x.toFixed(2)},${c1y.toFixed(2)} ${c2x.toFixed(2)},${c2y.toFixed(2)} ${p2[0]},${p2[1]} `;
  }
  return d.trim() + (closed ? ' Z' : '');
}

function blobPath(cx, cy, radii) {
  const n = radii.length;
  const points = radii.map((r, i) => {
    const angle = (Math.PI * 2 * i) / n - Math.PI / 2;
    return [+(cx + r * Math.cos(angle)).toFixed(2), +(cy + r * Math.sin(angle)).toFixed(2)];
  });
  return catmullRomToBezier(points, true);
}

const PRESET_PATHS = {
  Heart: 'M100,180 C40,130 0,90 0,55 C0,25 25,0 55,0 C75,0 90,10 100,25 C110,10 125,0 145,0 C175,0 200,25 200,55 C200,90 160,130 100,180 Z',
  Star: starPath(100, 105, 90, 35, 5),
  Check: 'M10,105 L75,165 L190,20',
  Arrow: 'M10,80 L160,80 M110,30 L165,80 L110,130',
  Wave: wavePath(240, 100, 35, 3),
  Blob: blobPath(100, 100, [90, 70, 100, 65, 95, 75, 88, 68]),
  Zigzag: 'M10,10 L60,90 L110,10 L160,90 L210,10',
  Spiral: spiralPath(100, 100, 3.5, 90),
  Infinity: infinityPath(100, 100, 80),
};

/* ---------- easing presets ---------- */

const EASING_PRESETS = [
  { name: 'Linear', v: [0, 0, 1, 1] },
  { name: 'Ease', v: [0.25, 0.1, 0.25, 1] },
  { name: 'Ease in', v: [0.42, 0, 1, 1] },
  { name: 'Ease out', v: [0, 0, 0.58, 1] },
  { name: 'Ease in-out', v: [0.42, 0, 0.58, 1] },
  { name: 'Back', v: [0.34, 1.56, 0.64, 1] },
  { name: 'Anticipate', v: [0.36, 0, 0.66, -0.56] },
];

/* ---------- state ---------- */

const state = {
  d: '',
  animType: 'draw',
  duration: 1500,
  delay: 0,
  loop: true,
  playing: true,
  easing: [0.25, 0.1, 0.25, 1],
  strokeColor: '#3d6b8a',
  strokeWidth: 3,
  fillColor: '#3d6b8a',
  dotColor: '#ab5142',
  dotSize: 4,
};

let pathLength = 0;
let currentAnim = null;
let currentDotAnim = null;

/* ---------- DOM refs ---------- */

const el = (id) => document.getElementById(id);
const presetsEl = el('presets');
const pathInput = el('pathInput');
const inputError = el('inputError');
const stageEl = el('stage');
const stageSvg = el('stageSvg');
const previewPath = el('previewPath');
const previewDot = el('previewDot');
const drawHint = el('drawHint');
const drawActions = el('drawActions');
const restartBtn = el('restartBtn');
const playPauseBtn = el('playPauseBtn');
const loopToggle = el('loopToggle');
const statsEl = el('stats');
const durationRange = el('durationRange');
const durationVal = el('durationVal');
const delayRange = el('delayRange');
const delayVal = el('delayVal');
const easingPresetsEl = el('easingPresets');
const curveEditor = el('curveEditor');
const ex1 = el('ex1'), ey1 = el('ey1'), ex2 = el('ex2'), ey2 = el('ey2');
const strokeColorInput = el('strokeColor');
const strokeWidthRange = el('strokeWidthRange');
const strokeWidthVal = el('strokeWidthVal');
const fillColorInput = el('fillColor');
const dotColorInput = el('dotColor');
const dotSizeRange = el('dotSizeRange');
const dotSizeVal = el('dotSizeVal');
const fillStyleBlock = el('fillStyleBlock');
const dotStyleBlock = el('dotStyleBlock');
const codeOutput = el('codeOutput');
const copyCodeBtn = el('copyCodeBtn');

/* ---------- path loading ---------- */

function extractPathData(raw) {
  const text = raw.trim();
  if (!text) return null;
  if (text[0] !== '<') return text;
  try {
    const doc = new DOMParser().parseFromString(text, 'image/svg+xml');
    if (doc.querySelector('parsererror')) return null;
    const pathEl = doc.querySelector('path[d]');
    if (pathEl) return pathEl.getAttribute('d');
    const poly = doc.querySelector('polyline, polygon');
    if (poly) {
      const pts = poly.getAttribute('points').trim().split(/\s+|,/).map(Number);
      let d = `M${pts[0]},${pts[1]} `;
      for (let i = 2; i < pts.length; i += 2) d += `L${pts[i]},${pts[i + 1]} `;
      if (poly.tagName === 'polygon') d += 'Z';
      return d.trim();
    }
    const rect = doc.querySelector('rect');
    if (rect) {
      const x = +rect.getAttribute('x') || 0, y = +rect.getAttribute('y') || 0;
      const w = +rect.getAttribute('width'), h = +rect.getAttribute('height');
      return `M${x},${y} L${x + w},${y} L${x + w},${y + h} L${x},${y + h} Z`;
    }
    const circle = doc.querySelector('circle');
    if (circle) {
      const cx = +circle.getAttribute('cx') || 0, cy = +circle.getAttribute('cy') || 0, r = +circle.getAttribute('r');
      return `M${cx - r},${cy} A${r},${r} 0 1,0 ${cx + r},${cy} A${r},${r} 0 1,0 ${cx - r},${cy} Z`;
    }
    return null;
  } catch (e) {
    return null;
  }
}

function setPath(d, { silent } = {}) {
  const test = document.createElementNS('http://www.w3.org/2000/svg', 'path');
  test.setAttribute('d', d);
  let bbox;
  try {
    stageSvg.appendChild(test);
    bbox = test.getBBox();
  } catch (e) {
    bbox = null;
  } finally {
    test.remove();
  }
  if (!bbox || (bbox.width === 0 && bbox.height === 0)) {
    if (!silent) inputError.textContent = 'Could not read that as an SVG path.';
    return false;
  }
  inputError.textContent = '';
  state.d = d;
  previewPath.setAttribute('d', d);
  const pad = Math.max(bbox.width, bbox.height) * 0.14 || 10;
  stageSvg.setAttribute('viewBox', `${bbox.x - pad} ${bbox.y - pad} ${bbox.width + pad * 2} ${bbox.height + pad * 2}`);
  pathLength = previewPath.getTotalLength();
  const dotR = Math.max(pad * 0.35, 1.5);
  previewDot.setAttribute('r', dotR.toFixed(2));
  statsEl.textContent = `length ${pathLength.toFixed(0)} · bbox ${bbox.width.toFixed(0)}×${bbox.height.toFixed(0)}`;
  applyStyles();
  updateAnimation();
  updateExportCode();
  return true;
}

/* ---------- presets UI ---------- */

Object.keys(PRESET_PATHS).forEach((name) => {
  const btn = document.createElement('button');
  btn.className = 'preset';
  btn.textContent = name;
  btn.addEventListener('click', () => {
    document.querySelectorAll('.preset').forEach((b) => b.classList.remove('active'));
    btn.classList.add('active');
    pathInput.value = PRESET_PATHS[name];
    setPath(PRESET_PATHS[name]);
  });
  presetsEl.appendChild(btn);
});

el('loadBtn').addEventListener('click', () => {
  document.querySelectorAll('.preset').forEach((b) => b.classList.remove('active'));
  const d = extractPathData(pathInput.value);
  if (!d) {
    inputError.textContent = 'Paste a path "d" value or an <svg> containing a <path>.';
    return;
  }
  setPath(d);
});

/* ---------- freehand drawing ---------- */

let drawing = false;
let drawPoints = [];

function svgPoint(evt) {
  const pt = stageSvg.createSVGPoint();
  pt.x = evt.clientX;
  pt.y = evt.clientY;
  const ctm = stageSvg.getScreenCTM();
  return ctm ? pt.matrixTransform(ctm.inverse()) : { x: 0, y: 0 };
}

function pointsToPath(pts) {
  if (pts.length < 2) return null;
  let d = `M${pts[0].x.toFixed(2)},${pts[0].y.toFixed(2)} `;
  for (let i = 1; i < pts.length - 1; i++) {
    const mx = (pts[i].x + pts[i + 1].x) / 2;
    const my = (pts[i].y + pts[i + 1].y) / 2;
    d += `Q${pts[i].x.toFixed(2)},${pts[i].y.toFixed(2)} ${mx.toFixed(2)},${my.toFixed(2)} `;
  }
  const last = pts[pts.length - 1];
  d += `L${last.x.toFixed(2)},${last.y.toFixed(2)}`;
  return d;
}

function enterDrawMode() {
  drawing = false;
  drawPoints = [];
  stageEl.classList.add('drawing');
  drawHint.classList.add('show');
  drawActions.hidden = true;
  stageSvg.setAttribute('viewBox', '0 0 300 300');
  previewPath.setAttribute('d', '');
  el('drawToggleBtn').textContent = 'Cancel drawing';
  el('drawToggleBtn').dataset.mode = 'active';
  pauseAnim();
}

function exitDrawMode() {
  stageEl.classList.remove('drawing');
  drawHint.classList.remove('show');
  drawActions.hidden = true;
  el('drawToggleBtn').textContent = '✏️ Draw your own';
  el('drawToggleBtn').dataset.mode = '';
}

el('drawToggleBtn').addEventListener('click', () => {
  if (el('drawToggleBtn').dataset.mode === 'active') {
    exitDrawMode();
    if (state.d) setPath(state.d);
  } else {
    enterDrawMode();
  }
});

stageSvg.addEventListener('pointerdown', (e) => {
  if (el('drawToggleBtn').dataset.mode !== 'active') return;
  drawing = true;
  drawPoints = [svgPoint(e)];
  stageSvg.setPointerCapture(e.pointerId);
});

stageSvg.addEventListener('pointermove', (e) => {
  if (!drawing) return;
  const p = svgPoint(e);
  const last = drawPoints[drawPoints.length - 1];
  if (Math.hypot(p.x - last.x, p.y - last.y) < 2) return;
  drawPoints.push(p);
  const d = pointsToPath(drawPoints);
  if (d) previewPath.setAttribute('d', d);
});

stageSvg.addEventListener('pointerup', () => {
  if (!drawing) return;
  drawing = false;
  if (drawPoints.length > 1) drawActions.hidden = false;
});

el('useDrawingBtn').addEventListener('click', () => {
  const d = pointsToPath(drawPoints);
  if (!d) return;
  exitDrawMode();
  pathInput.value = d;
  setPath(d);
});

el('clearDrawingBtn').addEventListener('click', () => {
  drawPoints = [];
  previewPath.setAttribute('d', '');
  drawActions.hidden = true;
});

el('cancelDrawingBtn').addEventListener('click', () => {
  exitDrawMode();
  if (state.d) setPath(state.d);
});

/* ---------- animation type ---------- */

document.querySelectorAll('#animTypeSeg .seg-btn').forEach((btn) => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('#animTypeSeg .seg-btn').forEach((b) => b.classList.remove('active'));
    btn.classList.add('active');
    state.animType = btn.dataset.type;
    fillStyleBlock.hidden = state.animType !== 'drawFill';
    dotStyleBlock.hidden = state.animType !== 'dot';
    applyStyles();
    updateAnimation();
    updateExportCode();
  });
});

/* ---------- duration / delay / loop ---------- */

durationRange.addEventListener('input', () => {
  state.duration = +durationRange.value;
  durationVal.textContent = `${state.duration}ms`;
  updateAnimation();
  updateExportCode();
});

delayRange.addEventListener('input', () => {
  state.delay = +delayRange.value;
  delayVal.textContent = `${state.delay}ms`;
  updateAnimation();
  updateExportCode();
});

loopToggle.addEventListener('change', () => {
  state.loop = loopToggle.checked;
  updateAnimation();
  updateExportCode();
});

/* ---------- easing ---------- */

EASING_PRESETS.forEach((preset) => {
  const chip = document.createElement('button');
  chip.className = 'easing-chip';
  chip.textContent = preset.name;
  chip.addEventListener('click', () => {
    state.easing = preset.v.slice();
    syncEasingUI();
    updateAnimation();
    updateExportCode();
  });
  easingPresetsEl.appendChild(chip);
});

function markActiveEasingChip() {
  const chips = easingPresetsEl.querySelectorAll('.easing-chip');
  chips.forEach((chip, i) => {
    const match = EASING_PRESETS[i].v.every((v, j) => Math.abs(v - state.easing[j]) < 0.001);
    chip.classList.toggle('active', match);
  });
}

const CURVE_MIN = -0.6, CURVE_MAX = 1.6, CURVE_RANGE = CURVE_MAX - CURVE_MIN;
const valToPx = (v) => 120 - ((v - CURVE_MIN) / CURVE_RANGE) * 120;
const pxToVal = (px) => CURVE_MIN + ((120 - px) / 120) * CURVE_RANGE;
const xValToPx = (v) => v * 120;
const pxToXVal = (px) => Math.min(1, Math.max(0, px / 120));

function drawCurveEditor() {
  const [x1, y1, x2, y2] = state.easing;
  const p0 = [0, valToPx(0)];
  const p3 = [120, valToPx(1)];
  const p1 = [xValToPx(x1), valToPx(y1)];
  const p2 = [xValToPx(x2), valToPx(y2)];
  curveEditor.innerHTML = `
    <line x1="0" y1="${valToPx(0)}" x2="120" y2="${valToPx(0)}" stroke="var(--line)" stroke-width="1"/>
    <line x1="0" y1="${valToPx(1)}" x2="120" y2="${valToPx(1)}" stroke="var(--line)" stroke-width="1"/>
    <line x1="${p0[0]}" y1="${p0[1]}" x2="${p1[0]}" y2="${p1[1]}" stroke="var(--line-strong)" stroke-width="1"/>
    <line x1="${p3[0]}" y1="${p3[1]}" x2="${p2[0]}" y2="${p2[1]}" stroke="var(--line-strong)" stroke-width="1"/>
    <path d="M${p0[0]},${p0[1]} C${p1[0]},${p1[1]} ${p2[0]},${p2[1]} ${p3[0]},${p3[1]}" fill="none" stroke="var(--accent)" stroke-width="2"/>
    <circle class="handle" data-handle="1" cx="${p1[0]}" cy="${p1[1]}" r="5" fill="var(--err)" stroke="var(--surface)" stroke-width="1.5" style="cursor:grab"/>
    <circle class="handle" data-handle="2" cx="${p2[0]}" cy="${p2[1]}" r="5" fill="var(--err)" stroke="var(--surface)" stroke-width="1.5" style="cursor:grab"/>
  `;
}

function syncEasingUI() {
  const [x1, y1, x2, y2] = state.easing;
  ex1.value = x1.toFixed(2);
  ey1.value = y1.toFixed(2);
  ex2.value = x2.toFixed(2);
  ey2.value = y2.toFixed(2);
  drawCurveEditor();
  markActiveEasingChip();
}

[ex1, ey1, ex2, ey2].forEach((input, i) => {
  input.addEventListener('input', () => {
    const v = parseFloat(input.value);
    if (Number.isNaN(v)) return;
    state.easing[i] = i % 2 === 0 ? Math.min(1, Math.max(0, v)) : v;
    drawCurveEditor();
    markActiveEasingChip();
    updateAnimation();
    updateExportCode();
  });
});

let draggingHandle = null;
curveEditor.addEventListener('pointerdown', (e) => {
  const target = e.target.closest('.handle');
  if (!target) return;
  draggingHandle = +target.dataset.handle;
  curveEditor.setPointerCapture(e.pointerId);
});
curveEditor.addEventListener('pointermove', (e) => {
  if (!draggingHandle) return;
  const rect = curveEditor.getBoundingClientRect();
  const px = ((e.clientX - rect.left) / rect.width) * 120;
  const py = ((e.clientY - rect.top) / rect.height) * 120;
  const x = pxToXVal(px);
  const y = pxToVal(py);
  if (draggingHandle === 1) { state.easing[0] = x; state.easing[1] = y; }
  else { state.easing[2] = x; state.easing[3] = y; }
  syncEasingUI();
  updateAnimation();
  updateExportCode();
});
curveEditor.addEventListener('pointerup', () => { draggingHandle = null; });
curveEditor.addEventListener('pointerleave', () => { draggingHandle = null; });

/* ---------- style controls ---------- */

function applyStyles() {
  previewPath.setAttribute('stroke', state.strokeColor);
  previewPath.setAttribute('stroke-width', state.strokeWidth);
  previewDot.setAttribute('fill', state.dotColor);
  if (state.animType === 'dot') {
    previewPath.setAttribute('fill', 'none');
    previewPath.style.strokeDasharray = '';
    previewPath.style.strokeDashoffset = '';
    previewPath.style.fillOpacity = '';
    previewPath.style.opacity = '0.55';
    previewPath.setAttribute('stroke-dasharray', '4 4');
    previewDot.style.opacity = '1';
  } else {
    previewPath.style.opacity = '1';
    previewPath.removeAttribute('stroke-dasharray');
    previewDot.style.opacity = '0';
    if (state.animType === 'drawFill') {
      previewPath.setAttribute('fill', state.fillColor);
    } else {
      previewPath.setAttribute('fill', 'none');
    }
  }
}

strokeColorInput.addEventListener('input', () => {
  state.strokeColor = strokeColorInput.value;
  applyStyles();
  updateExportCode();
});

strokeWidthRange.addEventListener('input', () => {
  state.strokeWidth = +strokeWidthRange.value;
  strokeWidthVal.textContent = `${state.strokeWidth}px`;
  applyStyles();
  updateExportCode();
});

fillColorInput.addEventListener('input', () => {
  state.fillColor = fillColorInput.value;
  applyStyles();
  updateExportCode();
});

dotColorInput.addEventListener('input', () => {
  state.dotColor = dotColorInput.value;
  applyStyles();
  updateExportCode();
});

dotSizeRange.addEventListener('input', () => {
  state.dotSize = +dotSizeRange.value;
  dotSizeVal.textContent = `${state.dotSize}px`;
  updateExportCode();
});

/* ---------- animation (Web Animations API) ---------- */

function bezierEasing() {
  const [x1, y1, x2, y2] = state.easing;
  return `cubic-bezier(${x1},${y1},${x2},${y2})`;
}

function updateAnimation() {
  if (!state.d || !pathLength) return;
  if (currentAnim) currentAnim.cancel();
  if (currentDotAnim) currentDotAnim.cancel();

  const timing = {
    duration: state.duration,
    delay: state.delay,
    easing: bezierEasing(),
    iterations: state.loop ? Infinity : 1,
    fill: 'forwards',
  };

  if (state.animType === 'draw') {
    currentAnim = previewPath.animate(
      [
        { strokeDasharray: `${pathLength}`, strokeDashoffset: `${pathLength}` },
        { strokeDasharray: `${pathLength}`, strokeDashoffset: '0' },
      ],
      timing
    );
  } else if (state.animType === 'drawFill') {
    currentAnim = previewPath.animate(
      [
        { strokeDasharray: `${pathLength}`, strokeDashoffset: `${pathLength}`, fillOpacity: 0, offset: 0 },
        { strokeDasharray: `${pathLength}`, strokeDashoffset: '0', fillOpacity: 0, offset: 0.7 },
        { strokeDasharray: `${pathLength}`, strokeDashoffset: '0', fillOpacity: 1, offset: 1 },
      ],
      timing
    );
  } else if (state.animType === 'dot') {
    if (CSS.supports('offset-path', `path("M0,0")`)) {
      previewDot.style.offsetPath = `path("${state.d}")`;
      previewDot.style.offsetRotate = '0deg';
      currentDotAnim = previewDot.animate(
        [{ offsetDistance: '0%' }, { offsetDistance: '100%' }],
        timing
      );
    }
  }
  if (!state.playing) pauseAnim(); else playAnim();
}

function pauseAnim() {
  state.playing = false;
  if (currentAnim) currentAnim.pause();
  if (currentDotAnim) currentDotAnim.pause();
  playPauseBtn.textContent = '▶';
}

function playAnim() {
  state.playing = true;
  if (currentAnim) currentAnim.play();
  if (currentDotAnim) currentDotAnim.play();
  playPauseBtn.textContent = '⏸';
}

playPauseBtn.addEventListener('click', () => {
  if (state.playing) pauseAnim(); else playAnim();
});

restartBtn.addEventListener('click', () => {
  if (currentAnim) currentAnim.currentTime = 0;
  if (currentDotAnim) currentDotAnim.currentTime = 0;
  playAnim();
});

/* ---------- export code ---------- */

function generateCSS() {
  const len = pathLength.toFixed(1);
  const ease = bezierEasing();
  const iter = state.loop ? 'infinite' : '1';
  if (state.animType === 'draw') {
    return `.svg-path {
  fill: none;
  stroke: ${state.strokeColor};
  stroke-width: ${state.strokeWidth};
  stroke-dasharray: ${len};
  stroke-dashoffset: ${len};
  animation: draw-in ${state.duration}ms ${ease} ${state.delay}ms ${iter} forwards;
}

@keyframes draw-in {
  to { stroke-dashoffset: 0; }
}`;
  }
  if (state.animType === 'drawFill') {
    return `.svg-path {
  stroke: ${state.strokeColor};
  stroke-width: ${state.strokeWidth};
  fill: ${state.fillColor};
  fill-opacity: 0;
  stroke-dasharray: ${len};
  stroke-dashoffset: ${len};
  animation: draw-fill ${state.duration}ms ${ease} ${state.delay}ms ${iter} forwards;
}

@keyframes draw-fill {
  70% { stroke-dashoffset: 0; fill-opacity: 0; }
  100% { stroke-dashoffset: 0; fill-opacity: 1; }
}`;
  }
  return `.path-guide {
  fill: none;
  stroke: ${state.strokeColor};
  stroke-width: ${state.strokeWidth};
}

.dot {
  width: ${state.dotSize}px;
  height: ${state.dotSize}px;
  border-radius: 50%;
  background: ${state.dotColor};
  offset-path: path("${state.d}");
  offset-rotate: 0deg;
  animation: move-dot ${state.duration}ms ${ease} ${state.delay}ms ${iter};
}

@keyframes move-dot {
  from { offset-distance: 0%; }
  to { offset-distance: 100%; }
}`;
}

function generateSMIL() {
  const len = pathLength.toFixed(1);
  const [x1, y1, x2, y2] = state.easing;
  const spline = `${x1} ${y1} ${x2} ${y2}`;
  const repeat = state.loop ? 'indefinite' : '1';
  const durS = (state.duration / 1000).toFixed(3);
  const delayS = (state.delay / 1000).toFixed(3);

  if (state.animType === 'draw') {
    return `<path d="${state.d}" fill="none" stroke="${state.strokeColor}" stroke-width="${state.strokeWidth}"
      stroke-dasharray="${len}" stroke-dashoffset="${len}">
  <animate attributeName="stroke-dashoffset" from="${len}" to="0"
    dur="${durS}s" begin="${delayS}s" fill="freeze"
    calcMode="spline" keyTimes="0;1" keySplines="${spline}"
    repeatCount="${repeat}" />
</path>`;
  }
  if (state.animType === 'drawFill') {
    const strokeDur = (state.duration * 0.7 / 1000).toFixed(3);
    const fillDur = (state.duration * 0.3 / 1000).toFixed(3);
    const fillBegin = ((state.delay + state.duration * 0.7) / 1000).toFixed(3);
    return `<path d="${state.d}" stroke="${state.strokeColor}" stroke-width="${state.strokeWidth}"
      fill="${state.fillColor}" fill-opacity="0"
      stroke-dasharray="${len}" stroke-dashoffset="${len}">
  <animate attributeName="stroke-dashoffset" from="${len}" to="0"
    dur="${strokeDur}s" begin="${delayS}s" fill="freeze"
    calcMode="spline" keyTimes="0;1" keySplines="${spline}" repeatCount="${repeat}" />
  <animate attributeName="fill-opacity" from="0" to="1"
    dur="${fillDur}s" begin="${fillBegin}s" fill="freeze" repeatCount="${repeat}" />
</path>`;
  }
  return `<path id="motionPath" d="${state.d}" fill="none" stroke="${state.strokeColor}" stroke-width="${state.strokeWidth}" />
<circle r="${(state.dotSize / 2).toFixed(1)}" fill="${state.dotColor}">
  <animateMotion dur="${durS}s" begin="${delayS}s" repeatCount="${repeat}"
    calcMode="spline" keyTimes="0;1" keySplines="${spline}">
    <mpath href="#motionPath" />
  </animateMotion>
</circle>`;
}

let activeTab = 'css';

function updateExportCode() {
  if (!state.d) { codeOutput.textContent = ''; return; }
  const code = activeTab === 'css' ? generateCSS() : generateSMIL();
  codeOutput.textContent = code;
}

document.querySelectorAll('#exportTabs .tab-btn').forEach((btn) => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('#exportTabs .tab-btn').forEach((b) => b.classList.remove('active'));
    btn.classList.add('active');
    activeTab = btn.dataset.tab;
    updateExportCode();
  });
});

copyCodeBtn.addEventListener('click', async () => {
  const text = codeOutput.textContent;
  if (!text) return;
  try {
    await navigator.clipboard.writeText(text);
  } catch (e) {
    const range = document.createRange();
    range.selectNode(codeOutput);
    window.getSelection().removeAllRanges();
    window.getSelection().addRange(range);
    document.execCommand('copy');
  }
  const original = copyCodeBtn.textContent;
  copyCodeBtn.textContent = 'Copied!';
  copyCodeBtn.classList.add('copied');
  setTimeout(() => {
    copyCodeBtn.textContent = original;
    copyCodeBtn.classList.remove('copied');
  }, 1200);
});

/* ---------- init ---------- */

syncEasingUI();
durationVal.textContent = `${state.duration}ms`;
delayVal.textContent = `${state.delay}ms`;
strokeWidthVal.textContent = `${state.strokeWidth}px`;
dotSizeVal.textContent = `${state.dotSize}px`;

const starBtn = [...presetsEl.querySelectorAll('.preset')].find((b) => b.textContent === 'Star');
if (starBtn) starBtn.classList.add('active');
pathInput.value = PRESET_PATHS.Star;
setPath(PRESET_PATHS.Star);
