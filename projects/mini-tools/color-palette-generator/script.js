'use strict';

/* ---------------------------------------------------------------- */
/* color math                                                        */
/* ---------------------------------------------------------------- */

function clamp(n, min, max) {
  return Math.min(max, Math.max(min, n));
}

function isValidHex(hex) {
  return /^#?[0-9a-fA-F]{6}$/.test(hex);
}

function normalizeHex(hex) {
  hex = hex.trim();
  if (hex[0] !== '#') hex = '#' + hex;
  return hex.toUpperCase();
}

function hexToRgb(hex) {
  hex = normalizeHex(hex).slice(1);
  return {
    r: parseInt(hex.slice(0, 2), 16),
    g: parseInt(hex.slice(2, 4), 16),
    b: parseInt(hex.slice(4, 6), 16),
  };
}

function rgbToHex(r, g, b) {
  const c = (n) => clamp(Math.round(n), 0, 255).toString(16).padStart(2, '0');
  return ('#' + c(r) + c(g) + c(b)).toUpperCase();
}

function rgbToHsl(r, g, b) {
  r /= 255; g /= 255; b /= 255;
  const max = Math.max(r, g, b), min = Math.min(r, g, b);
  let h, s;
  const l = (max + min) / 2;
  if (max === min) {
    h = s = 0;
  } else {
    const d = max - min;
    s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
    switch (max) {
      case r: h = (g - b) / d + (g < b ? 6 : 0); break;
      case g: h = (b - r) / d + 2; break;
      default: h = (r - g) / d + 4;
    }
    h /= 6;
  }
  return { h: h * 360, s: s * 100, l: l * 100 };
}

function hslToRgb(h, s, l) {
  h = ((h % 360) + 360) % 360 / 360;
  s /= 100; l /= 100;
  if (s === 0) {
    const v = l * 255;
    return { r: v, g: v, b: v };
  }
  const q = l < 0.5 ? l * (1 + s) : l + s - l * s;
  const p = 2 * l - q;
  const hue2rgb = (p, q, t) => {
    if (t < 0) t += 1;
    if (t > 1) t -= 1;
    if (t < 1 / 6) return p + (q - p) * 6 * t;
    if (t < 1 / 2) return q;
    if (t < 2 / 3) return p + (q - p) * (2 / 3 - t) * 6;
    return p;
  };
  return {
    r: hue2rgb(p, q, h + 1 / 3) * 255,
    g: hue2rgb(p, q, h) * 255,
    b: hue2rgb(p, q, h - 1 / 3) * 255,
  };
}

function relativeLuminance({ r, g, b }) {
  const lin = (c) => {
    c /= 255;
    return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
  };
  return 0.2126 * lin(r) + 0.7152 * lin(g) + 0.0722 * lin(b);
}

function contrastRatio(hexA, hexB) {
  const lA = relativeLuminance(hexToRgb(hexA));
  const lB = relativeLuminance(hexToRgb(hexB));
  const lighter = Math.max(lA, lB);
  const darker = Math.min(lA, lB);
  return (lighter + 0.05) / (darker + 0.05);
}

/* ---------------------------------------------------------------- */
/* tonal scale                                                       */
/* ---------------------------------------------------------------- */

const SCALE_STEPS = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950];
const SCALE_LIGHTNESS = [97, 93, 85, 74, 62, 50, 42, 34, 26, 18, 11];

function generateScale(baseHex) {
  const { h, s, l: baseL } = rgbToHsl(hexToRgb(baseHex).r, hexToRgb(baseHex).g, hexToRgb(baseHex).b);

  let baseStepIndex = 0;
  let best = Infinity;
  SCALE_LIGHTNESS.forEach((lVal, i) => {
    const d = Math.abs(lVal - baseL);
    if (d < best) { best = d; baseStepIndex = i; }
  });

  return SCALE_STEPS.map((step, i) => {
    const isBase = i === baseStepIndex;
    const l = isBase ? baseL : SCALE_LIGHTNESS[i];
    const rgb = hslToRgb(h, s, l);
    return {
      step,
      hex: rgbToHex(rgb.r, rgb.g, rgb.b),
      isBase,
    };
  });
}

/* ---------------------------------------------------------------- */
/* image color extraction (k-means)                                  */
/* ---------------------------------------------------------------- */

function extractDominantColors(img, k, seedOffset) {
  const maxDim = 110;
  const scale = Math.min(1, maxDim / Math.max(img.naturalWidth, img.naturalHeight));
  const w = Math.max(1, Math.round(img.naturalWidth * scale));
  const h = Math.max(1, Math.round(img.naturalHeight * scale));

  const canvas = document.createElement('canvas');
  canvas.width = w;
  canvas.height = h;
  const ctx = canvas.getContext('2d');
  ctx.drawImage(img, 0, 0, w, h);
  const data = ctx.getImageData(0, 0, w, h).data;

  const pixels = [];
  for (let i = 0; i < data.length; i += 4) {
    if (data[i + 3] < 125) continue;
    pixels.push([data[i], data[i + 1], data[i + 2]]);
  }
  if (pixels.length === 0) return [];

  const dist2 = (a, b) => {
    const dr = a[0] - b[0], dg = a[1] - b[1], db = a[2] - b[2];
    return dr * dr + dg * dg + db * db;
  };

  let seed = (seedOffset || 0) * 104729 + 12345;
  const rand = () => {
    seed = (seed * 1103515245 + 12345) & 0x7fffffff;
    return seed / 0x7fffffff;
  };

  const centroids = [pixels[Math.floor(rand() * pixels.length)]];
  while (centroids.length < k && centroids.length < pixels.length) {
    let bestPixel = null, bestD = -1;
    const sampleSize = Math.min(pixels.length, 400);
    for (let n = 0; n < sampleSize; n++) {
      const p = pixels[Math.floor(rand() * pixels.length)];
      let minD = Infinity;
      for (const c of centroids) minD = Math.min(minD, dist2(p, c));
      if (minD > bestD) { bestD = minD; bestPixel = p; }
    }
    centroids.push(bestPixel);
  }

  const assignments = new Array(pixels.length).fill(0);
  const iterations = 8;
  for (let iter = 0; iter < iterations; iter++) {
    for (let pi = 0; pi < pixels.length; pi++) {
      let best = 0, bestD = Infinity;
      for (let ci = 0; ci < centroids.length; ci++) {
        const d = dist2(pixels[pi], centroids[ci]);
        if (d < bestD) { bestD = d; best = ci; }
      }
      assignments[pi] = best;
    }
    const sums = centroids.map(() => [0, 0, 0, 0]);
    for (let pi = 0; pi < pixels.length; pi++) {
      const s = sums[assignments[pi]];
      s[0] += pixels[pi][0]; s[1] += pixels[pi][1]; s[2] += pixels[pi][2]; s[3]++;
    }
    for (let ci = 0; ci < centroids.length; ci++) {
      if (sums[ci][3] === 0) {
        centroids[ci] = pixels[Math.floor(rand() * pixels.length)];
      } else {
        centroids[ci] = [sums[ci][0] / sums[ci][3], sums[ci][1] / sums[ci][3], sums[ci][2] / sums[ci][3]];
      }
    }
  }

  const counts = centroids.map(() => 0);
  assignments.forEach((c) => counts[c]++);

  return centroids
    .map((c, i) => ({ hex: rgbToHex(c[0], c[1], c[2]), count: counts[i] }))
    .filter((c) => c.count > 0)
    .sort((a, b) => b.count - a.count)
    .map((c) => ({ hex: c.hex, pct: Math.round((c.count / pixels.length) * 100) }));
}

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
/* state + DOM                                                       */
/* ---------------------------------------------------------------- */

const state = {
  baseHex: '#3D6B8A',
  scale: [],
  fg: null,
  bg: null,
};

const els = {
  sourceTabs: document.getElementById('sourceTabs'),
  colorSource: document.getElementById('colorSource'),
  imageSource: document.getElementById('imageSource'),
  colorSwatch: document.getElementById('colorSwatch'),
  hexInput: document.getElementById('hexInput'),
  randomColorBtn: document.getElementById('randomColorBtn'),
  colorPresets: document.getElementById('colorPresets'),
  dropzone: document.getElementById('dropzone'),
  imageInput: document.getElementById('imageInput'),
  imagePreviewRow: document.getElementById('imagePreviewRow'),
  imagePreview: document.getElementById('imagePreview'),
  extractedList: document.getElementById('extractedList'),
  reextractBtn: document.getElementById('reextractBtn'),
  scaleGrid: document.getElementById('scaleGrid'),
  copyCssBtn: document.getElementById('copyCssBtn'),
  copyJsonBtn: document.getElementById('copyJsonBtn'),
  swapBtn: document.getElementById('swapBtn'),
  contrastPreview: document.getElementById('contrastPreview'),
  contrastResults: document.getElementById('contrastResults'),
};

const fgDD = setupColorDropdown('fg');
const bgDD = setupColorDropdown('bg');

let currentImage = null;
let extractSeed = 0;

/* ---------------------------------------------------------------- */
/* rendering                                                         */
/* ---------------------------------------------------------------- */

function setBaseColor(hex) {
  hex = normalizeHex(hex);
  if (!isValidHex(hex)) return;
  state.baseHex = hex;
  els.colorSwatch.value = hex;
  els.hexInput.value = hex;
  els.hexInput.classList.remove('invalid');
  render();
}

function render() {
  state.scale = generateScale(state.baseHex);
  renderScale();
  renderContrastDropdowns();
  renderContrast();
}

function renderScale() {
  els.scaleGrid.innerHTML = '';
  state.scale.forEach(({ step, hex, isBase }) => {
    const card = document.createElement('div');
    card.className = 'scale-card';

    const swatch = document.createElement('div');
    swatch.className = 'scale-swatch' + (isBase ? ' is-base' : '');
    swatch.style.background = hex;
    swatch.title = 'Click to copy ' + hex;
    swatch.addEventListener('click', () => { copyText(hex); flashCopied(card); });

    const meta = document.createElement('div');
    meta.className = 'scale-meta';

    const stepEl = document.createElement('div');
    stepEl.className = 'scale-step';
    stepEl.textContent = step;

    const hexEl = document.createElement('div');
    hexEl.className = 'scale-hex';
    hexEl.textContent = hex;
    hexEl.title = 'Click to copy';
    hexEl.addEventListener('click', () => { copyText(hex); flashCopied(card); });

    const badges = document.createElement('div');
    badges.className = 'scale-badges';
    const onWhite = contrastRatio(hex, '#FFFFFF');
    const onBlack = contrastRatio(hex, '#000000');
    badges.appendChild(makeMiniBadge('W', onWhite));
    badges.appendChild(makeMiniBadge('B', onBlack));

    meta.appendChild(stepEl);
    meta.appendChild(hexEl);
    meta.appendChild(badges);
    card.appendChild(swatch);
    card.appendChild(meta);
    els.scaleGrid.appendChild(card);
  });
}

function makeMiniBadge(label, ratio) {
  const span = document.createElement('span');
  const pass = ratio >= 4.5;
  span.className = 'wcag-badge ' + (pass ? 'pass' : 'fail');
  span.textContent = label + ' ' + ratio.toFixed(1);
  span.title = label === 'W' ? 'Contrast vs white text' : 'Contrast vs black text';
  return span;
}

function buildColorOptions() {
  const options = state.scale.map((s) => ({ value: s.hex, label: s.step + ' — ' + s.hex }));
  options.push({ value: '#FFFFFF', label: 'White — #FFFFFF' });
  options.push({ value: '#000000', label: 'Black — #000000' });
  return options;
}

function renderContrastDropdowns() {
  const options = buildColorOptions();
  const validValues = options.map((o) => o.value);
  const darkest = state.scale[state.scale.length - 1].hex;
  const lightest = state.scale[0].hex;

  if (!validValues.includes(state.fg)) state.fg = darkest;
  if (!validValues.includes(state.bg)) state.bg = lightest;

  renderDropdownMenu(fgDD, options, state.fg, (hex) => {
    state.fg = hex;
    renderContrastDropdowns();
    renderContrast();
  });
  renderDropdownMenu(bgDD, options, state.bg, (hex) => {
    state.bg = hex;
    renderContrastDropdowns();
    renderContrast();
  });
}

function renderContrast() {
  const fg = state.fg;
  const bg = state.bg;
  els.contrastPreview.style.background = bg;
  els.contrastPreview.style.color = fg;

  const ratio = contrastRatio(fg, bg);
  els.contrastResults.innerHTML = '';

  const card = document.createElement('div');
  card.className = 'result-card';

  const ratioEl = document.createElement('div');
  ratioEl.className = 'result-ratio';
  ratioEl.textContent = ratio.toFixed(2) + ':1';
  card.appendChild(ratioEl);

  const rows = document.createElement('div');
  rows.className = 'result-rows';
  [
    ['Normal text · AA', 4.5],
    ['Normal text · AAA', 7],
    ['Large text · AA', 3],
    ['Large text · AAA', 4.5],
  ].forEach(([label, threshold]) => {
    const row = document.createElement('div');
    row.className = 'result-row';
    const pass = ratio >= threshold;
    row.innerHTML = '<span>' + label + '</span><span class="status ' + (pass ? 'pass' : 'fail') + '">' + (pass ? 'PASS' : 'FAIL') + '</span>';
    rows.appendChild(row);
  });
  card.appendChild(rows);
  els.contrastResults.appendChild(card);
}

/* ---------------------------------------------------------------- */
/* custom color dropdowns                                            */
/* ---------------------------------------------------------------- */

function setupColorDropdown(prefix) {
  const dd = {
    trigger: document.getElementById(prefix + 'Trigger'),
    menu: document.getElementById(prefix + 'Menu'),
    swatch: document.getElementById(prefix + 'Swatch'),
    label: document.getElementById(prefix + 'Label'),
  };
  dd.trigger.addEventListener('click', (e) => {
    e.stopPropagation();
    const wasOpen = !dd.menu.hidden;
    closeAllDropdowns();
    if (!wasOpen) {
      dd.menu.hidden = false;
      dd.trigger.classList.add('open');
    }
  });
  return dd;
}

function closeAllDropdowns() {
  [fgDD, bgDD].forEach((dd) => {
    dd.menu.hidden = true;
    dd.trigger.classList.remove('open');
  });
}

function renderDropdownMenu(dd, options, selectedHex, onSelect) {
  dd.swatch.style.background = selectedHex;
  const match = options.find((o) => o.value === selectedHex);
  dd.label.textContent = match ? match.label : selectedHex;

  dd.menu.innerHTML = '';
  options.forEach((opt) => {
    const item = document.createElement('div');
    item.className = 'color-dd-option' + (opt.value === selectedHex ? ' active' : '');

    const sw = document.createElement('span');
    sw.className = 'dd-swatch';
    sw.style.background = opt.value;

    const lbl = document.createElement('span');
    lbl.textContent = opt.label;

    item.appendChild(sw);
    item.appendChild(lbl);
    item.addEventListener('click', (e) => {
      e.stopPropagation();
      closeAllDropdowns();
      onSelect(opt.value);
    });
    dd.menu.appendChild(item);
  });
}

document.addEventListener('click', () => closeAllDropdowns());
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') closeAllDropdowns();
});

/* ---------------------------------------------------------------- */
/* image extraction UI                                               */
/* ---------------------------------------------------------------- */

function handleImageFile(file) {
  if (!file || !file.type.startsWith('image/')) return;
  const reader = new FileReader();
  reader.onload = (e) => {
    const img = new Image();
    img.onload = () => {
      currentImage = img;
      els.imagePreview.src = e.target.result;
      els.imagePreviewRow.hidden = false;
      extractSeed = 0;
      runExtraction();
    };
    img.src = e.target.result;
  };
  reader.readAsDataURL(file);
}

function runExtraction() {
  if (!currentImage) return;
  els.extractedList.innerHTML = '<span class="dz-text">Extracting…</span>';
  setTimeout(() => {
    const colors = extractDominantColors(currentImage, 6, extractSeed);
    els.extractedList.innerHTML = '';
    colors.forEach(({ hex, pct }) => {
      const sw = document.createElement('button');
      sw.className = 'extracted-swatch';
      sw.style.background = hex;
      sw.title = hex + ' (' + pct + '% of image)';
      sw.addEventListener('click', () => setBaseColor(hex));
      els.extractedList.appendChild(sw);
    });
  }, 10);
}

/* ---------------------------------------------------------------- */
/* wiring                                                            */
/* ---------------------------------------------------------------- */

els.sourceTabs.addEventListener('click', (e) => {
  const btn = e.target.closest('.tab');
  if (!btn) return;
  document.querySelectorAll('.tab').forEach((t) => t.classList.remove('active'));
  btn.classList.add('active');
  const source = btn.dataset.source;
  els.colorSource.hidden = source !== 'color';
  els.imageSource.hidden = source !== 'image';
});

els.colorSwatch.addEventListener('input', () => setBaseColor(els.colorSwatch.value));

els.hexInput.addEventListener('input', () => {
  const val = els.hexInput.value;
  if (isValidHex(val)) {
    els.hexInput.classList.remove('invalid');
    state.baseHex = normalizeHex(val);
    els.colorSwatch.value = state.baseHex;
    render();
  } else {
    els.hexInput.classList.add('invalid');
  }
});

els.randomColorBtn.addEventListener('click', () => {
  const h = Math.floor(Math.random() * 360);
  const s = 45 + Math.random() * 30;
  const l = 38 + Math.random() * 18;
  const rgb = hslToRgb(h, s, l);
  setBaseColor(rgbToHex(rgb.r, rgb.g, rgb.b));
});

els.colorPresets.addEventListener('click', (e) => {
  const btn = e.target.closest('.preset-swatch');
  if (!btn) return;
  setBaseColor(btn.dataset.hex);
});

els.dropzone.addEventListener('click', (e) => {
  if (e.target !== els.imageInput) {
    e.preventDefault();
    els.imageInput.click();
  }
});

els.imageInput.addEventListener('change', () => {
  if (els.imageInput.files[0]) handleImageFile(els.imageInput.files[0]);
});

['dragenter', 'dragover'].forEach((evt) => {
  els.dropzone.addEventListener(evt, (e) => {
    e.preventDefault();
    els.dropzone.classList.add('dragover');
  });
});

['dragleave', 'drop'].forEach((evt) => {
  els.dropzone.addEventListener(evt, (e) => {
    e.preventDefault();
    els.dropzone.classList.remove('dragover');
  });
});

els.dropzone.addEventListener('drop', (e) => {
  const file = e.dataTransfer.files && e.dataTransfer.files[0];
  if (file) handleImageFile(file);
});

els.reextractBtn.addEventListener('click', () => {
  extractSeed++;
  runExtraction();
});

els.copyCssBtn.addEventListener('click', () => {
  const lines = state.scale.map((s) => '  --color-' + s.step + ': ' + s.hex + ';');
  copyText(':root {\n' + lines.join('\n') + '\n}');
  flashCopied(els.copyCssBtn);
});

els.copyJsonBtn.addEventListener('click', () => {
  const obj = {};
  state.scale.forEach((s) => { obj[s.step] = s.hex; });
  copyText(JSON.stringify(obj, null, 2));
  flashCopied(els.copyJsonBtn);
});

els.swapBtn.addEventListener('click', () => {
  const tmp = state.fg;
  state.fg = state.bg;
  state.bg = tmp;
  renderContrastDropdowns();
  renderContrast();
});

render();
