'use strict';

/* ---------------------------------------------------------------- */
/* clipboard                                                         */
/* ---------------------------------------------------------------- */

function copyText(text) {
  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(text).catch(() => fallbackCopy(text));
  } else {
    fallbackCopy(text);
  }
}

function fallbackCopy(text) {
  const ta = document.createElement('textarea');
  ta.value = text;
  ta.style.position = 'fixed';
  ta.style.opacity = '0';
  document.body.appendChild(ta);
  ta.select();
  try { document.execCommand('copy'); } catch (e) { /* no-op */ }
  document.body.removeChild(ta);
}

function flashCopied(el) {
  el.classList.add('copied');
  setTimeout(() => el.classList.remove('copied'), 900);
}

/* ---------------------------------------------------------------- */
/* color helpers                                                     */
/* ---------------------------------------------------------------- */

function hexToRgb(hex) {
  hex = hex.replace('#', '');
  return {
    r: parseInt(hex.slice(0, 2), 16),
    g: parseInt(hex.slice(2, 4), 16),
    b: parseInt(hex.slice(4, 6), 16),
  };
}

function rgba(hex, alphaPct) {
  const { r, g, b } = hexToRgb(hex);
  const a = clamp01(alphaPct / 100);
  return 'rgba(' + r + ', ' + g + ', ' + b + ', ' + round2(a) + ')';
}

function clamp01(n) {
  return Math.min(1, Math.max(0, n));
}

function round2(n) {
  return Math.round(n * 100) / 100;
}

function isValidHex(hex) {
  return /^#?[0-9a-fA-F]{6}$/.test(hex);
}

function normalizeHex(hex) {
  hex = hex.trim();
  if (hex[0] !== '#') hex = '#' + hex;
  return hex.toUpperCase();
}

function createColorField(initialHex, onChange) {
  const wrap = document.createElement('div');
  wrap.className = 'color-field';

  const colorInput = document.createElement('input');
  colorInput.type = 'color';
  colorInput.className = 'color-swatch-sm';
  colorInput.value = initialHex;

  const hexInput = document.createElement('input');
  hexInput.type = 'text';
  hexInput.className = 'color-hex-input';
  hexInput.spellcheck = false;
  hexInput.autocomplete = 'off';
  hexInput.maxLength = 7;
  hexInput.value = initialHex;

  colorInput.addEventListener('input', () => {
    const hex = colorInput.value.toUpperCase();
    hexInput.value = hex;
    hexInput.classList.remove('invalid');
    onChange(hex);
  });

  hexInput.addEventListener('input', () => {
    const val = hexInput.value;
    if (!isValidHex(val)) {
      hexInput.classList.add('invalid');
      return;
    }
    const hex = normalizeHex(val);
    hexInput.classList.remove('invalid');
    colorInput.value = hex;
    onChange(hex);
  });

  wrap.appendChild(colorInput);
  wrap.appendChild(hexInput);
  return wrap;
}

/* ---------------------------------------------------------------- */
/* tool tabs                                                         */
/* ---------------------------------------------------------------- */

const toolPanels = {
  clip: document.getElementById('panel-clip'),
  gradient: document.getElementById('panel-gradient'),
  shadow: document.getElementById('panel-shadow'),
};

document.getElementById('toolTabs').addEventListener('click', (e) => {
  const btn = e.target.closest('.tool-tab');
  if (!btn) return;
  document.querySelectorAll('.tool-tab').forEach((t) => t.classList.remove('active'));
  btn.classList.add('active');
  const tool = btn.dataset.tool;
  Object.keys(toolPanels).forEach((key) => {
    toolPanels[key].hidden = key !== tool;
  });
});

/* ================================================================ */
/* CLIP-PATH                                                         */
/* ================================================================ */

const clipState = {
  shape: 'inset',
  inset: { top: 10, right: 10, bottom: 10, left: 10, round: 0 },
  circle: { radius: 40, x: 50, y: 50 },
  ellipse: { rx: 40, ry: 30, x: 50, y: 50 },
  polygonPoints: [[50, 0], [0, 100], [100, 100]],
};

const POLYGON_PRESETS = [
  { name: 'Triangle', points: [[50, 0], [0, 100], [100, 100]] },
  { name: 'Right triangle', points: [[0, 0], [0, 100], [100, 100]] },
  { name: 'Trapezoid', points: [[20, 0], [80, 0], [100, 100], [0, 100]] },
  { name: 'Parallelogram', points: [[25, 0], [100, 0], [75, 100], [0, 100]] },
  { name: 'Rhombus', points: [[50, 0], [100, 50], [50, 100], [0, 50]] },
  { name: 'Pentagon', points: [[50, 0], [100, 38], [82, 100], [18, 100], [0, 38]] },
  { name: 'Hexagon', points: [[25, 0], [75, 0], [100, 50], [75, 100], [25, 100], [0, 50]] },
  { name: 'Star', points: [[50, 0], [61, 35], [98, 35], [68, 57], [79, 91], [50, 70], [21, 91], [32, 57], [2, 35], [39, 35]] },
  { name: 'Arrow', points: [[0, 20], [60, 20], [60, 0], [100, 50], [60, 100], [60, 80], [0, 80]] },
  { name: 'Message', points: [[0, 0], [100, 0], [100, 75], [25, 75], [15, 100], [15, 75], [0, 75]] },
  { name: 'Cross', points: [[35, 0], [65, 0], [65, 35], [100, 35], [100, 65], [65, 65], [65, 100], [35, 100], [35, 65], [0, 65], [0, 35], [35, 35]] },
  { name: 'Frame', points: [[0, 15], [15, 0], [85, 0], [100, 15], [100, 85], [85, 100], [15, 100], [0, 85]] },
  { name: 'Chevron', points: [[0, 0], [75, 0], [100, 50], [75, 100], [0, 100], [25, 50]] },
];

const clipEls = {
  shapeSeg: document.getElementById('clipShapeSeg'),
  insetControls: document.getElementById('insetControls'),
  circleControls: document.getElementById('circleControls'),
  ellipseControls: document.getElementById('ellipseControls'),
  polygonControls: document.getElementById('polygonControls'),
  polygonPresets: document.getElementById('polygonPresets'),
  polygonPoints: document.getElementById('polygonPoints'),
  polygonError: document.getElementById('polygonError'),
  preview: document.getElementById('clipPreview'),
  code: document.getElementById('clipCode'),
  copyBtn: document.getElementById('clipCopyBtn'),
};

function buildClipPath() {
  if (clipState.shape === 'inset') {
    const s = clipState.inset;
    const round = s.round > 0 ? ' round ' + s.round + 'px' : '';
    return 'inset(' + s.top + '% ' + s.right + '% ' + s.bottom + '% ' + s.left + '%' + round + ')';
  }
  if (clipState.shape === 'circle') {
    const s = clipState.circle;
    return 'circle(' + s.radius + '% at ' + s.x + '% ' + s.y + '%)';
  }
  if (clipState.shape === 'ellipse') {
    const s = clipState.ellipse;
    return 'ellipse(' + s.rx + '% ' + s.ry + '% at ' + s.x + '% ' + s.y + '%)';
  }
  const pts = clipState.polygonPoints.map((p) => p[0] + '% ' + p[1] + '%').join(', ');
  return 'polygon(' + pts + ')';
}

function pointsToText(points) {
  return points.map((p) => p[0] + '% ' + p[1] + '%').join(',\n');
}

function parsePointsText(text) {
  const pairs = text.split(',').map((s) => s.trim()).filter(Boolean);
  const points = [];
  for (const pair of pairs) {
    const m = pair.match(/^(-?\d+(?:\.\d+)?)%?\s+(-?\d+(?:\.\d+)?)%?$/);
    if (!m) return null;
    points.push([parseFloat(m[1]), parseFloat(m[2])]);
  }
  if (points.length < 3) return null;
  return points;
}

function renderClip() {
  clipEls.insetControls.hidden = clipState.shape !== 'inset';
  clipEls.circleControls.hidden = clipState.shape !== 'circle';
  clipEls.ellipseControls.hidden = clipState.shape !== 'ellipse';
  clipEls.polygonControls.hidden = clipState.shape !== 'polygon';

  const value = buildClipPath();
  clipEls.preview.style.clipPath = value;
  clipEls.preview.style.webkitClipPath = value;
  clipEls.code.textContent = '.element {\n  clip-path: ' + value + ';\n}';
}

document.getElementById('insetTop').addEventListener('input', (e) => {
  clipState.inset.top = +e.target.value;
  document.getElementById('insetTopVal').textContent = e.target.value + '%';
  renderClip();
});
document.getElementById('insetRight').addEventListener('input', (e) => {
  clipState.inset.right = +e.target.value;
  document.getElementById('insetRightVal').textContent = e.target.value + '%';
  renderClip();
});
document.getElementById('insetBottom').addEventListener('input', (e) => {
  clipState.inset.bottom = +e.target.value;
  document.getElementById('insetBottomVal').textContent = e.target.value + '%';
  renderClip();
});
document.getElementById('insetLeft').addEventListener('input', (e) => {
  clipState.inset.left = +e.target.value;
  document.getElementById('insetLeftVal').textContent = e.target.value + '%';
  renderClip();
});
document.getElementById('insetRound').addEventListener('input', (e) => {
  clipState.inset.round = +e.target.value;
  document.getElementById('insetRoundVal').textContent = e.target.value + 'px';
  renderClip();
});

document.getElementById('circleRadius').addEventListener('input', (e) => {
  clipState.circle.radius = +e.target.value;
  document.getElementById('circleRadiusVal').textContent = e.target.value + '%';
  renderClip();
});
document.getElementById('circleX').addEventListener('input', (e) => {
  clipState.circle.x = +e.target.value;
  document.getElementById('circleXVal').textContent = e.target.value + '%';
  renderClip();
});
document.getElementById('circleY').addEventListener('input', (e) => {
  clipState.circle.y = +e.target.value;
  document.getElementById('circleYVal').textContent = e.target.value + '%';
  renderClip();
});

document.getElementById('ellipseRx').addEventListener('input', (e) => {
  clipState.ellipse.rx = +e.target.value;
  document.getElementById('ellipseRxVal').textContent = e.target.value + '%';
  renderClip();
});
document.getElementById('ellipseRy').addEventListener('input', (e) => {
  clipState.ellipse.ry = +e.target.value;
  document.getElementById('ellipseRyVal').textContent = e.target.value + '%';
  renderClip();
});
document.getElementById('ellipseX').addEventListener('input', (e) => {
  clipState.ellipse.x = +e.target.value;
  document.getElementById('ellipseXVal').textContent = e.target.value + '%';
  renderClip();
});
document.getElementById('ellipseY').addEventListener('input', (e) => {
  clipState.ellipse.y = +e.target.value;
  document.getElementById('ellipseYVal').textContent = e.target.value + '%';
  renderClip();
});

clipEls.shapeSeg.addEventListener('click', (e) => {
  const btn = e.target.closest('.seg-btn');
  if (!btn) return;
  clipEls.shapeSeg.querySelectorAll('.seg-btn').forEach((b) => b.classList.remove('active'));
  btn.classList.add('active');
  clipState.shape = btn.dataset.shape;
  renderClip();
});

function buildPresetSvg(points) {
  const pts = points.map((p) => p[0] + ',' + p[1]).join(' ');
  return '<svg viewBox="0 0 100 100"><polygon points="' + pts + '"></polygon></svg>';
}

POLYGON_PRESETS.forEach((preset, i) => {
  const btn = document.createElement('button');
  btn.className = 'preset-btn';
  btn.title = preset.name;
  btn.innerHTML = buildPresetSvg(preset.points);
  btn.addEventListener('click', () => {
    clipEls.polygonPresets.querySelectorAll('.preset-btn').forEach((b) => b.classList.remove('active'));
    btn.classList.add('active');
    clipState.polygonPoints = preset.points.map((p) => p.slice());
    clipEls.polygonPoints.value = pointsToText(clipState.polygonPoints);
    clipEls.polygonError.textContent = '';
    renderClip();
  });
  if (i === 0) btn.classList.add('active');
  clipEls.polygonPresets.appendChild(btn);
});

clipEls.polygonPoints.value = pointsToText(clipState.polygonPoints);

clipEls.polygonPoints.addEventListener('input', () => {
  const parsed = parsePointsText(clipEls.polygonPoints.value);
  if (!parsed) {
    clipEls.polygonError.textContent = 'Need at least 3 points, each as "x% y%" separated by commas.';
    return;
  }
  clipEls.polygonError.textContent = '';
  clipState.polygonPoints = parsed;
  clipEls.polygonPresets.querySelectorAll('.preset-btn').forEach((b) => b.classList.remove('active'));
  renderClip();
});

clipEls.copyBtn.addEventListener('click', () => {
  copyText(clipEls.code.textContent);
  flashCopied(clipEls.copyBtn);
});

renderClip();

/* ================================================================ */
/* GRADIENT                                                          */
/* ================================================================ */

const gradientState = {
  type: 'linear',
  linear: { angle: 135 },
  radial: { shape: 'circle', x: 50, y: 50, size: 'farthest-corner' },
  conic: { angle: 0, x: 50, y: 50 },
  repeating: false,
  stops: [
    { color: '#3D6B8A', pos: 0 },
    { color: '#AB5142', pos: 100 },
  ],
};

const gradientEls = {
  typeSeg: document.getElementById('gradientTypeSeg'),
  linearControls: document.getElementById('linearControls'),
  radialControls: document.getElementById('radialControls'),
  conicControls: document.getElementById('conicControls'),
  radialShapeSeg: document.getElementById('radialShapeSeg'),
  radialSize: document.getElementById('radialSize'),
  repeatCheck: document.getElementById('gradientRepeat'),
  stopsList: document.getElementById('stopsList'),
  addStopBtn: document.getElementById('addStopBtn'),
  preview: document.getElementById('gradientPreview'),
  code: document.getElementById('gradientCode'),
  copyBtn: document.getElementById('gradientCopyBtn'),
};

function buildGradientValue() {
  const prefix = gradientState.repeating ? 'repeating-' : '';
  const stopsCss = gradientState.stops
    .slice()
    .map((s) => s.color + ' ' + s.pos + '%')
    .join(', ');

  if (gradientState.type === 'linear') {
    return prefix + 'linear-gradient(' + gradientState.linear.angle + 'deg, ' + stopsCss + ')';
  }
  if (gradientState.type === 'radial') {
    const r = gradientState.radial;
    return prefix + 'radial-gradient(' + r.shape + ' ' + r.size + ' at ' + r.x + '% ' + r.y + '%, ' + stopsCss + ')';
  }
  const c = gradientState.conic;
  return prefix + 'conic-gradient(from ' + c.angle + 'deg at ' + c.x + '% ' + c.y + '%, ' + stopsCss + ')';
}

function renderGradient() {
  gradientEls.linearControls.hidden = gradientState.type !== 'linear';
  gradientEls.radialControls.hidden = gradientState.type !== 'radial';
  gradientEls.conicControls.hidden = gradientState.type !== 'conic';

  const value = buildGradientValue();
  gradientEls.preview.style.background = value;
  gradientEls.code.textContent = '.element {\n  background: ' + value + ';\n}';
}

function renderStops() {
  gradientEls.stopsList.innerHTML = '';
  gradientState.stops.forEach((stop, i) => {
    const row = document.createElement('div');
    row.className = 'stop-row';

    const colorField = createColorField(stop.color, (hex) => {
      stop.color = hex;
      renderGradient();
    });

    const sliderWrap = document.createElement('div');
    sliderWrap.className = 'stop-slider-wrap';

    const range = document.createElement('input');
    range.type = 'range';
    range.min = '0';
    range.max = '100';
    range.step = '1';
    range.value = String(stop.pos);

    const posVal = document.createElement('span');
    posVal.className = 'stop-pos-val';
    posVal.textContent = stop.pos + '%';

    range.addEventListener('input', () => {
      stop.pos = +range.value;
      posVal.textContent = stop.pos + '%';
      renderGradient();
    });

    sliderWrap.appendChild(range);
    sliderWrap.appendChild(posVal);

    const removeBtn = document.createElement('button');
    removeBtn.className = 'stop-remove';
    removeBtn.textContent = '✕';
    removeBtn.title = 'Remove stop';
    removeBtn.disabled = gradientState.stops.length <= 2;
    removeBtn.addEventListener('click', () => {
      if (gradientState.stops.length <= 2) return;
      gradientState.stops.splice(i, 1);
      renderStops();
      renderGradient();
    });

    row.appendChild(colorField);
    row.appendChild(sliderWrap);
    row.appendChild(removeBtn);
    gradientEls.stopsList.appendChild(row);
  });
}

gradientEls.typeSeg.addEventListener('click', (e) => {
  const btn = e.target.closest('.seg-btn');
  if (!btn) return;
  gradientEls.typeSeg.querySelectorAll('.seg-btn').forEach((b) => b.classList.remove('active'));
  btn.classList.add('active');
  gradientState.type = btn.dataset.type;
  renderGradient();
});

gradientEls.radialShapeSeg.addEventListener('click', (e) => {
  const btn = e.target.closest('.seg-btn');
  if (!btn) return;
  gradientEls.radialShapeSeg.querySelectorAll('.seg-btn').forEach((b) => b.classList.remove('active'));
  btn.classList.add('active');
  gradientState.radial.shape = btn.dataset.rshape;
  renderGradient();
});

document.getElementById('linearAngle').addEventListener('input', (e) => {
  gradientState.linear.angle = +e.target.value;
  document.getElementById('linearAngleVal').textContent = e.target.value + 'deg';
  renderGradient();
});

document.getElementById('radialX').addEventListener('input', (e) => {
  gradientState.radial.x = +e.target.value;
  document.getElementById('radialXVal').textContent = e.target.value + '%';
  renderGradient();
});
document.getElementById('radialY').addEventListener('input', (e) => {
  gradientState.radial.y = +e.target.value;
  document.getElementById('radialYVal').textContent = e.target.value + '%';
  renderGradient();
});
gradientEls.radialSize.addEventListener('change', () => {
  gradientState.radial.size = gradientEls.radialSize.value;
  renderGradient();
});

document.getElementById('conicAngle').addEventListener('input', (e) => {
  gradientState.conic.angle = +e.target.value;
  document.getElementById('conicAngleVal').textContent = e.target.value + 'deg';
  renderGradient();
});
document.getElementById('conicX').addEventListener('input', (e) => {
  gradientState.conic.x = +e.target.value;
  document.getElementById('conicXVal').textContent = e.target.value + '%';
  renderGradient();
});
document.getElementById('conicY').addEventListener('input', (e) => {
  gradientState.conic.y = +e.target.value;
  document.getElementById('conicYVal').textContent = e.target.value + '%';
  renderGradient();
});

gradientEls.repeatCheck.addEventListener('change', () => {
  gradientState.repeating = gradientEls.repeatCheck.checked;
  renderGradient();
});

gradientEls.addStopBtn.addEventListener('click', () => {
  if (gradientState.stops.length >= 8) return;
  const last = gradientState.stops[gradientState.stops.length - 1];
  const pos = Math.min(100, last.pos + 10);
  gradientState.stops.push({ color: '#C98A2E', pos });
  renderStops();
  renderGradient();
});

gradientEls.copyBtn.addEventListener('click', () => {
  copyText(gradientEls.code.textContent);
  flashCopied(gradientEls.copyBtn);
});

renderStops();
renderGradient();

/* ================================================================ */
/* BOX-SHADOW                                                        */
/* ================================================================ */

let shadowIdSeq = 0;

const shadowState = {
  layers: [
    { id: shadowIdSeq++, inset: false, x: 0, y: 4, blur: 12, spread: 0, color: '#000000', alpha: 25 },
  ],
};

const shadowEls = {
  list: document.getElementById('shadowList'),
  addBtn: document.getElementById('addShadowBtn'),
  preview: document.getElementById('shadowPreview'),
  code: document.getElementById('shadowCode'),
  copyBtn: document.getElementById('shadowCopyBtn'),
};

function buildShadowValue() {
  return shadowState.layers
    .map((l) => (l.inset ? 'inset ' : '') + l.x + 'px ' + l.y + 'px ' + l.blur + 'px ' + l.spread + 'px ' + rgba(l.color, l.alpha))
    .join(',\n  ');
}

function renderShadowPreview() {
  const value = buildShadowValue();
  shadowEls.preview.style.boxShadow = value;
  shadowEls.code.textContent = '.element {\n  box-shadow: ' + value + ';\n}';
}

function renderShadowList() {
  shadowEls.list.innerHTML = '';
  shadowState.layers.forEach((layer, i) => {
    const item = document.createElement('div');
    item.className = 'shadow-layer';

    const head = document.createElement('div');
    head.className = 'shadow-layer-head';

    const title = document.createElement('span');
    title.className = 'shadow-layer-title';
    title.textContent = 'Layer ' + (i + 1);

    const actions = document.createElement('div');
    actions.className = 'shadow-layer-actions';

    const insetLabel = document.createElement('label');
    insetLabel.className = 'check-label';
    const insetCheck = document.createElement('input');
    insetCheck.type = 'checkbox';
    insetCheck.checked = layer.inset;
    insetCheck.addEventListener('change', () => {
      layer.inset = insetCheck.checked;
      renderShadowPreview();
    });
    insetLabel.appendChild(insetCheck);
    insetLabel.appendChild(document.createTextNode('Inset'));

    const removeBtn = document.createElement('button');
    removeBtn.className = 'stop-remove';
    removeBtn.textContent = '✕';
    removeBtn.title = 'Remove layer';
    removeBtn.disabled = shadowState.layers.length <= 1;
    removeBtn.addEventListener('click', () => {
      if (shadowState.layers.length <= 1) return;
      shadowState.layers.splice(i, 1);
      renderShadowList();
      renderShadowPreview();
    });

    actions.appendChild(insetLabel);
    actions.appendChild(removeBtn);
    head.appendChild(title);
    head.appendChild(actions);

    const grid = document.createElement('div');
    grid.className = 'shadow-grid';
    grid.appendChild(makeShadowRange('Offset X', -50, 50, layer.x, 'px', (v) => { layer.x = v; renderShadowPreview(); }));
    grid.appendChild(makeShadowRange('Offset Y', -50, 50, layer.y, 'px', (v) => { layer.y = v; renderShadowPreview(); }));
    grid.appendChild(makeShadowRange('Blur', 0, 100, layer.blur, 'px', (v) => { layer.blur = v; renderShadowPreview(); }));
    grid.appendChild(makeShadowRange('Spread', -50, 50, layer.spread, 'px', (v) => { layer.spread = v; renderShadowPreview(); }));

    const colorRow = document.createElement('div');
    colorRow.className = 'shadow-color-row';

    const colorField = createColorField(layer.color, (hex) => {
      layer.color = hex;
      renderShadowPreview();
    });

    const alphaWrap = makeShadowRange('Opacity', 0, 100, layer.alpha, '%', (v) => { layer.alpha = v; renderShadowPreview(); });

    colorRow.appendChild(colorField);
    colorRow.appendChild(alphaWrap);

    item.appendChild(head);
    item.appendChild(grid);
    item.appendChild(colorRow);
    shadowEls.list.appendChild(item);
  });
}

function makeShadowRange(label, min, max, value, unit, onChange) {
  const wrap = document.createElement('label');
  wrap.className = 'range-label';

  const labelText = document.createElement('span');
  labelText.textContent = label + ' ';
  const valSpan = document.createElement('span');
  valSpan.className = 'range-val';
  valSpan.textContent = value + unit;
  labelText.appendChild(valSpan);

  const range = document.createElement('input');
  range.type = 'range';
  range.min = String(min);
  range.max = String(max);
  range.step = '1';
  range.value = String(value);
  range.addEventListener('input', () => {
    valSpan.textContent = range.value + unit;
    onChange(+range.value);
  });

  wrap.appendChild(labelText);
  wrap.appendChild(range);
  return wrap;
}

shadowEls.addBtn.addEventListener('click', () => {
  if (shadowState.layers.length >= 6) return;
  shadowState.layers.push({ id: shadowIdSeq++, inset: false, x: 0, y: 4, blur: 12, spread: 0, color: '#000000', alpha: 25 });
  renderShadowList();
  renderShadowPreview();
});

shadowEls.copyBtn.addEventListener('click', () => {
  copyText(shadowEls.code.textContent);
  flashCopied(shadowEls.copyBtn);
});

renderShadowList();
renderShadowPreview();
