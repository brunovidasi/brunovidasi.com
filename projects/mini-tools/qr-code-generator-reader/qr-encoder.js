// QR Code encoder: text -> module matrix -> canvas/SVG rendering.
// Implements ISO/IEC 18004 encoding (numeric/alphanumeric/byte modes,
// automatic version selection, all 8 mask patterns scored by the standard
// penalty rules) using the shared GF(256)/Reed-Solomon math in qr-common.js.
(function (root) {
  'use strict';

  const QR = typeof module !== 'undefined' && module.exports ? require('./qr-common.js') : root.QR;

  class BitBuffer {
    constructor() {
      this.bits = [];
    }
    put(value, length) {
      for (let i = length - 1; i >= 0; i--) {
        this.bits.push((value >>> i) & 1);
      }
    }
    get length() {
      return this.bits.length;
    }
    toBytes() {
      const bytes = [];
      for (let i = 0; i < this.bits.length; i += 8) {
        let b = 0;
        for (let j = 0; j < 8; j++) {
          b = (b << 1) | (this.bits[i + j] || 0);
        }
        bytes.push(b);
      }
      return bytes;
    }
  }

  function detectMode(text) {
    if (/^[0-9]*$/.test(text)) return 'numeric';
    if (new RegExp('^[' + QR.ALPHANUMERIC_CHARS.replace(/[$*+\-.\/]/g, '\\$&') + ']*$').test(text)) {
      return 'alphanumeric';
    }
    return 'byte';
  }

  function utf8Bytes(str) {
    return Array.from(new TextEncoder().encode(str));
  }

  function dataBitLength(mode, text, byteData) {
    if (mode === 'numeric') {
      const n = text.length;
      return Math.floor(n / 3) * 10 + (n % 3 === 1 ? 4 : n % 3 === 2 ? 7 : 0);
    }
    if (mode === 'alphanumeric') {
      const n = text.length;
      return Math.floor(n / 2) * 11 + (n % 2 === 1 ? 6 : 0);
    }
    return byteData.length * 8;
  }

  function encodeSegment(buffer, mode, text, version) {
    buffer.put(QR.MODE_INDICATORS[mode], 4);
    if (mode === 'byte') {
      const bytes = utf8Bytes(text);
      buffer.put(bytes.length, QR.charCountBits('byte', version));
      bytes.forEach((b) => buffer.put(b, 8));
      return;
    }
    if (mode === 'numeric') {
      buffer.put(text.length, QR.charCountBits('numeric', version));
      for (let i = 0; i < text.length; i += 3) {
        const chunk = text.slice(i, i + 3);
        const bits = chunk.length === 3 ? 10 : chunk.length === 2 ? 7 : 4;
        buffer.put(parseInt(chunk, 10), bits);
      }
      return;
    }
    if (mode === 'alphanumeric') {
      buffer.put(text.length, QR.charCountBits('alphanumeric', version));
      for (let i = 0; i < text.length; i += 2) {
        if (i + 1 < text.length) {
          const v = QR.ALPHANUMERIC_CHARS.indexOf(text[i]) * 45 + QR.ALPHANUMERIC_CHARS.indexOf(text[i + 1]);
          buffer.put(v, 11);
        } else {
          buffer.put(QR.ALPHANUMERIC_CHARS.indexOf(text[i]), 6);
        }
      }
      return;
    }
  }

  // Pick the smallest version (1-40) at the given EC level that fits `text`.
  function chooseVersion(text, mode, level, byteData) {
    for (let version = 1; version <= 40; version++) {
      const info = QR.getRsBlockInfo(version, level);
      const capacityBits = info.totalDataCodewords * 8;
      const headerBits = 4 + QR.charCountBits(mode, version);
      const bodyBits = dataBitLength(mode, text, byteData);
      if (headerBits + bodyBits <= capacityBits) return version;
    }
    return null;
  }

  function buildCodewords(text, mode, version, level) {
    const buffer = new BitBuffer();
    const byteData = mode === 'byte' ? utf8Bytes(text) : null;
    encodeSegment(buffer, mode, text, version);

    const info = QR.getRsBlockInfo(version, level);
    const capacityBits = info.totalDataCodewords * 8;

    // terminator (up to 4 zero bits)
    const termLen = Math.min(4, capacityBits - buffer.length);
    if (termLen > 0) buffer.put(0, termLen);
    // pad to byte boundary
    while (buffer.length % 8 !== 0) buffer.put(0, 1);
    // pad bytes 0xEC/0x11 alternating until full
    const padBytes = [0xec, 0x11];
    let padIdx = 0;
    while (buffer.length < capacityBits) {
      buffer.put(padBytes[padIdx % 2], 8);
      padIdx++;
    }

    const dataCodewords = buffer.toBytes();

    // split into blocks per group, compute EC codewords, interleave
    const blocks = [];
    let offset = 0;
    for (const group of info.groups) {
      for (let b = 0; b < group.count; b++) {
        const data = dataCodewords.slice(offset, offset + group.dataCodewords);
        offset += group.dataCodewords;
        const ec = QR.rsEncode(data, info.ecCodewordsPerBlock);
        blocks.push({ data, ec });
      }
    }

    const maxDataLen = Math.max(...blocks.map((b) => b.data.length));
    const interleavedData = [];
    for (let i = 0; i < maxDataLen; i++) {
      for (const block of blocks) {
        if (i < block.data.length) interleavedData.push(block.data[i]);
      }
    }
    const interleavedEc = [];
    for (let i = 0; i < info.ecCodewordsPerBlock; i++) {
      for (const block of blocks) interleavedEc.push(block.ec[i]);
    }

    return interleavedData.concat(interleavedEc);
  }

  // ---- Matrix construction ----

  function drawFinderPattern(matrix, row, col) {
    for (let r = -1; r <= 7; r++) {
      for (let c = -1; c <= 7; c++) {
        const rr = row + r;
        const cc = col + c;
        if (rr < 0 || cc < 0 || rr >= matrix.length || cc >= matrix.length) continue;
        const inBorder = r === -1 || r === 7 || c === -1 || c === 7;
        const inRing = r >= 0 && r <= 6 && c >= 0 && c <= 6 && (r === 0 || r === 6 || c === 0 || c === 6);
        const inCore = r >= 2 && r <= 4 && c >= 2 && c <= 4;
        matrix[rr][cc] = inBorder ? 0 : inRing || inCore ? 1 : 0;
      }
    }
  }

  function drawAlignmentPattern(matrix, row, col) {
    for (let r = -2; r <= 2; r++) {
      for (let c = -2; c <= 2; c++) {
        const rr = row + r;
        const cc = col + c;
        const onRing = Math.max(Math.abs(r), Math.abs(c));
        matrix[rr][cc] = onRing === 1 ? 0 : 1;
      }
    }
  }

  function buildMatrix(version, level, maskPattern, codewords) {
    const size = 17 + version * 4;
    const matrix = Array.from({ length: size }, () => new Array(size).fill(null));

    drawFinderPattern(matrix, 0, 0);
    drawFinderPattern(matrix, 0, size - 7);
    drawFinderPattern(matrix, size - 7, 0);

    const positions = QR.ALIGNMENT_POSITIONS[version - 1];
    for (const r of positions) {
      for (const c of positions) {
        if ((r <= 8 && c <= 8) || (r <= 8 && c >= size - 9) || (r >= size - 9 && c <= 8)) continue;
        drawAlignmentPattern(matrix, r, c);
      }
    }

    for (let i = 8; i < size - 8; i++) {
      matrix[6][i] = i % 2 === 0 ? 1 : 0;
      matrix[i][6] = i % 2 === 0 ? 1 : 0;
    }

    matrix[size - 8][8] = 1; // dark module

    // reserve format info areas (filled in later) and version info areas
    for (let i = 0; i <= 8; i++) {
      if (matrix[8][i] === null) matrix[8][i] = 0;
      if (matrix[i][8] === null) matrix[i][8] = 0;
    }
    for (let i = 0; i < 8; i++) {
      matrix[8][size - 1 - i] = 0;
    }
    for (let i = 0; i < 7; i++) {
      matrix[size - 1 - i][8] = 0; // rows size-1..size-7; leaves the dark module at size-8 untouched
    }
    if (version >= 7) {
      for (let r = 0; r < 6; r++) {
        for (let c = 0; c < 3; c++) {
          matrix[r][size - 11 + c] = 0;
          matrix[size - 11 + c][r] = 0;
        }
      }
    }

    // place data bits along the shared zigzag order, skipping function modules
    const bits = [];
    for (const byte of codewords) {
      for (let i = 7; i >= 0; i--) bits.push((byte >>> i) & 1);
    }
    const order = QR.getZigzagOrder(version);
    for (let i = 0; i < order.length; i++) {
      const [row, c] = order[i];
      const bit = i < bits.length ? bits[i] : 0;
      matrix[row][c] = QR.applyMask(maskPattern, row, c) ? bit ^ 1 : bit;
    }

    // format info: two redundant copies, per ISO/IEC 18004 Figure 25
    const formatBits = QR.formatInfoBits(level, maskPattern);
    const getBit = (i) => (formatBits >>> i) & 1;
    // copy A, around the top-left finder
    for (let i = 0; i <= 5; i++) matrix[i][8] = getBit(i);
    matrix[7][8] = getBit(6);
    matrix[8][8] = getBit(7);
    matrix[8][7] = getBit(8);
    for (let i = 9; i < 15; i++) matrix[8][14 - i] = getBit(i);
    // copy B, split between the top-right and bottom-left finders
    for (let i = 0; i <= 7; i++) matrix[8][size - 1 - i] = getBit(i);
    for (let i = 8; i < 15; i++) matrix[size - 15 + i][8] = getBit(i);

    // version info (v7+)
    if (version >= 7) {
      const versionBits = QR.versionInfoBits(version);
      for (let i = 0; i < 18; i++) {
        const bit = (versionBits >>> i) & 1;
        const r = Math.floor(i / 3);
        const c = i % 3;
        matrix[r][size - 11 + c] = bit;
        matrix[size - 11 + c][r] = bit;
      }
    }

    return matrix;
  }

  function evaluatePenalty(matrix) {
    const size = matrix.length;
    let penalty = 0;

    // rule 1: runs of 5+ same-color modules in a row/column
    function runPenalty(getVal) {
      let p = 0;
      for (let i = 0; i < size; i++) {
        let runColor = getVal(i, 0);
        let runLen = 1;
        for (let j = 1; j < size; j++) {
          const v = getVal(i, j);
          if (v === runColor) {
            runLen++;
          } else {
            if (runLen >= 5) p += 3 + (runLen - 5);
            runColor = v;
            runLen = 1;
          }
        }
        if (runLen >= 5) p += 3 + (runLen - 5);
      }
      return p;
    }
    penalty += runPenalty((i, j) => matrix[i][j]);
    penalty += runPenalty((i, j) => matrix[j][i]);

    // rule 2: 2x2 blocks of same color
    for (let r = 0; r < size - 1; r++) {
      for (let c = 0; c < size - 1; c++) {
        const v = matrix[r][c];
        if (v === matrix[r][c + 1] && v === matrix[r + 1][c] && v === matrix[r + 1][c + 1]) {
          penalty += 3;
        }
      }
    }

    // rule 3: finder-like patterns 1:1:3:1:1 with 4 light either side
    const pattern1 = [1, 0, 1, 1, 1, 0, 1, 0, 0, 0, 0];
    const pattern2 = [0, 0, 0, 0, 1, 0, 1, 1, 1, 0, 1];
    function matchesPattern(arr, pat) {
      return pat.every((v, idx) => arr[idx] === v);
    }
    for (let r = 0; r < size; r++) {
      for (let c = 0; c <= size - 11; c++) {
        const seg = matrix[r].slice(c, c + 11);
        if (matchesPattern(seg, pattern1) || matchesPattern(seg, pattern2)) penalty += 40;
      }
    }
    for (let c = 0; c < size; c++) {
      for (let r = 0; r <= size - 11; r++) {
        const seg = [];
        for (let k = 0; k < 11; k++) seg.push(matrix[r + k][c]);
        if (matchesPattern(seg, pattern1) || matchesPattern(seg, pattern2)) penalty += 40;
      }
    }

    // rule 4: overall dark module ratio
    let dark = 0;
    for (let r = 0; r < size; r++) for (let c = 0; c < size; c++) if (matrix[r][c]) dark++;
    const percent = (dark * 100) / (size * size);
    const prevMultipleOf5 = Math.floor(percent / 5) * 5;
    const nextMultipleOf5 = prevMultipleOf5 + 5;
    penalty += Math.min(Math.abs(prevMultipleOf5 - 50) / 5, Math.abs(nextMultipleOf5 - 50) / 5) * 10;

    return penalty;
  }

  function generateMatrix(text, level, options) {
    options = options || {};
    let mode = options.mode || detectMode(text);
    const byteData = mode === 'byte' ? utf8Bytes(text) : null;
    let version = options.version || chooseVersion(text, mode, level, byteData);
    if (!version) throw new Error('Text is too long to fit in a QR code at this error-correction level.');

    const codewords = buildCodewords(text, mode, version, level);

    let bestMatrix = null;
    let bestPenalty = Infinity;
    let bestMask = 0;
    for (let mask = 0; mask < 8; mask++) {
      const matrix = buildMatrix(version, level, mask, codewords);
      const penalty = evaluatePenalty(matrix);
      if (penalty < bestPenalty) {
        bestPenalty = penalty;
        bestMatrix = matrix;
        bestMask = mask;
      }
    }

    return { matrix: bestMatrix, version, level, mask: bestMask, mode };
  }

  // ---- Rendering ----
  //
  // Styling (dot/corner shape, gradients) is purely cosmetic: every style
  // below keeps each module's full nominal dark/light area intact (a
  // "rounded" module still covers its whole cell, just with clipped
  // corners) or, for "dots", shrinks it by a bounded amount (to ~82%)
  // that real-world QR scanners tolerate fine — the encoded structure
  // (finder ratios, module grid) never changes, only how each cell is
  // painted.

  // True for any module inside one of the 3 finder-pattern 7x7 blocks —
  // those are drawn as one combined eye shape, not per-module, so the
  // per-module loop skips them.
  function isEyeModule(size, row, col) {
    if (row < 7 && col < 7) return true;
    if (row < 7 && col >= size - 7) return true;
    if (row >= size - 7 && col < 7) return true;
    return false;
  }

  function pathRoundedSquare(cx, cy, half, radius) {
    const r = Math.min(radius, half);
    const x0 = cx - half, y0 = cy - half, x1 = cx + half, y1 = cy + half;
    return `M${x0 + r},${y0} L${x1 - r},${y0} Q${x1},${y0} ${x1},${y0 + r} L${x1},${y1 - r} Q${x1},${y1} ${x1 - r},${y1} L${x0 + r},${y1} Q${x0},${y1} ${x0},${y1 - r} L${x0},${y0 + r} Q${x0},${y0} ${x0 + r},${y0} Z`;
  }

  // A circle expressed as two arcs, so it can be combined with another
  // subpath under an evenodd fill rule (SVG has no single-element "circle
  // with a hole" otherwise).
  function pathCircle(cx, cy, r) {
    return `M${cx + r},${cy} A${r},${r} 0 1,0 ${cx - r},${cy} A${r},${r} 0 1,0 ${cx + r},${cy} Z`;
  }

  function shapePath(cx, cy, half, style) {
    return style === 'dots' || style === 'circle' ? pathCircle(cx, cy, half) : pathRoundedSquare(cx, cy, half, half * (STYLE_RADIUS[style] || 0));
  }

  // These add a subpath to the context's CURRENT path (rather than a
  // Path2D object) so multiple shapes can be combined under one
  // ctx.fill('evenodd') call — Path2D works in browsers, but this form
  // also runs unchanged under node-canvas, which the test suite uses.
  function addRoundedSquareToCtx(ctx, cx, cy, half, radius) {
    const r = Math.min(radius, half);
    const x0 = cx - half, y0 = cy - half, x1 = cx + half, y1 = cy + half;
    ctx.moveTo(x0 + r, y0);
    ctx.arcTo(x1, y0, x1, y1, r);
    ctx.arcTo(x1, y1, x0, y1, r);
    ctx.arcTo(x0, y1, x0, y0, r);
    ctx.arcTo(x0, y0, x1, y0, r);
    ctx.closePath();
  }

  function addShapeToCtx(ctx, cx, cy, half, style) {
    if (style === 'dots' || style === 'circle') {
      ctx.moveTo(cx + half, cy);
      ctx.arc(cx, cy, half, 0, Math.PI * 2);
    } else {
      addRoundedSquareToCtx(ctx, cx, cy, half, half * (STYLE_RADIUS[style] || 0));
    }
  }

  // radius/shape factor per style: 0 = sharp square, ~.35 = rounded, 1 = circle
  const STYLE_RADIUS = { square: 0, rounded: 0.35, dots: 1, circle: 1 };

  function drawModuleCanvas(ctx, cx, cy, half, style) {
    ctx.beginPath();
    if (style === 'dots' || style === 'circle') {
      ctx.arc(cx, cy, half * 0.82, 0, Math.PI * 2);
    } else {
      addRoundedSquareToCtx(ctx, cx, cy, half, half * (STYLE_RADIUS[style] || 0) * 2);
    }
    ctx.fill();
  }

  // One finder pattern rendered as a dark 7×7 ring (an evenodd "donut" with
  // a 5×5 hole, so whatever is behind — the page background, transparent
  // canvas, or an opaque fill — shows through the hole correctly either
  // way) plus a solid dark 3×3 core. Structurally identical to the
  // per-module finder pattern, just drawn as one clean shape.
  function drawEyeCanvas(ctx, originX, originY, moduleSizePx, style, darkFill) {
    const cx = originX + moduleSizePx * 3.5;
    const cy = originY + moduleSizePx * 3.5;
    ctx.fillStyle = darkFill;

    ctx.beginPath();
    addShapeToCtx(ctx, cx, cy, 3.5 * moduleSizePx, style);
    addShapeToCtx(ctx, cx, cy, 2.5 * moduleSizePx, style);
    ctx.fill('evenodd');

    ctx.beginPath();
    addShapeToCtx(ctx, cx, cy, 1.5 * moduleSizePx, style);
    ctx.fill();
  }

  function buildCanvasGradient(ctx, gradient, widthPx, heightPx) {
    if (!gradient) return null;
    const { color1, color2, type = 'linear', angle = 90 } = gradient;
    if (type === 'radial') {
      const cx = widthPx / 2, cy = heightPx / 2;
      const r = Math.hypot(widthPx, heightPx) / 2;
      const g = ctx.createRadialGradient(cx, cy, 0, cx, cy, r);
      g.addColorStop(0, color1);
      g.addColorStop(1, color2);
      return g;
    }
    const rad = (angle * Math.PI) / 180;
    const dx = Math.cos(rad), dy = Math.sin(rad);
    const cx = widthPx / 2, cy = heightPx / 2;
    const half = (Math.abs(dx) * widthPx + Math.abs(dy) * heightPx) / 2;
    const g = ctx.createLinearGradient(cx - dx * half, cy - dy * half, cx + dx * half, cy + dy * half);
    g.addColorStop(0, color1);
    g.addColorStop(1, color2);
    return g;
  }

  function renderToCanvas(canvas, matrix, options) {
    options = options || {};
    const moduleSize = options.moduleSize || 8;
    const margin = options.margin != null ? options.margin : 4;
    const dark = options.dark || '#000000';
    const light = options.light || '#ffffff';
    const dotStyle = options.dotStyle || 'square';
    const cornerStyle = options.cornerStyle || dotStyle;
    const transparentBackground = !!options.transparentBackground;
    const size = matrix.length;
    const total = size + margin * 2;
    canvas.width = total * moduleSize;
    canvas.height = total * moduleSize;
    const ctx = canvas.getContext('2d');

    ctx.clearRect(0, 0, canvas.width, canvas.height);
    if (!transparentBackground) {
      ctx.fillStyle = light;
      ctx.fillRect(0, 0, canvas.width, canvas.height);
    }

    const darkFill = buildCanvasGradient(ctx, options.gradient, canvas.width, canvas.height) || dark;

    ctx.fillStyle = darkFill;
    const half = moduleSize / 2;
    for (let r = 0; r < size; r++) {
      for (let c = 0; c < size; c++) {
        if (!matrix[r][c] || isEyeModule(size, r, c)) continue;
        const cx = (c + margin) * moduleSize + half;
        const cy = (r + margin) * moduleSize + half;
        drawModuleCanvas(ctx, cx, cy, half, dotStyle);
      }
    }

    const eyeOrigins = [
      [margin, margin],
      [margin, margin + size - 7],
      [margin + size - 7, margin],
    ];
    for (const [er, ec] of eyeOrigins) {
      drawEyeCanvas(ctx, ec * moduleSize, er * moduleSize, moduleSize, cornerStyle, darkFill);
    }
  }

  function renderToSvg(matrix, options) {
    options = options || {};
    const margin = options.margin != null ? options.margin : 4;
    const dark = options.dark || '#000000';
    const light = options.light || '#ffffff';
    const dotStyle = options.dotStyle || 'square';
    const cornerStyle = options.cornerStyle || dotStyle;
    const transparentBackground = !!options.transparentBackground;
    const size = matrix.length;
    const total = size + margin * 2;

    let defs = '';
    let fillRef = `fill="${dark}"`;
    if (options.gradient) {
      const { color1, color2, type = 'linear', angle = 90 } = options.gradient;
      const id = 'qrGrad';
      if (type === 'radial') {
        defs = `<radialGradient id="${id}" cx="50%" cy="50%" r="70%"><stop offset="0%" stop-color="${color1}"/><stop offset="100%" stop-color="${color2}"/></radialGradient>`;
      } else {
        const rad = (angle * Math.PI) / 180;
        const dx = Math.cos(rad) * 50, dy = Math.sin(rad) * 50;
        defs = `<linearGradient id="${id}" x1="${50 - dx}%" y1="${50 - dy}%" x2="${50 + dx}%" y2="${50 + dy}%"><stop offset="0%" stop-color="${color1}"/><stop offset="100%" stop-color="${color2}"/></linearGradient>`;
      }
      fillRef = `fill="url(#${id})"`;
    }

    let shapes = '';
    for (let r = 0; r < size; r++) {
      for (let c = 0; c < size; c++) {
        if (!matrix[r][c] || isEyeModule(size, r, c)) continue;
        const cx = c + margin + 0.5, cy = r + margin + 0.5;
        if (dotStyle === 'dots' || dotStyle === 'circle') {
          shapes += `<circle cx="${cx}" cy="${cy}" r="0.41"/>`;
        } else if (dotStyle === 'rounded') {
          shapes += `<path d="${pathRoundedSquare(cx, cy, 0.5, 0.5 * STYLE_RADIUS.rounded * 2)}"/>`;
        } else {
          shapes += `<rect x="${c + margin}" y="${r + margin}" width="1" height="1"/>`;
        }
      }
    }

    // each eye: an evenodd "donut" (7-wide outer minus 5-wide hole) plus a
    // solid 3-wide core — the hole naturally reveals the background (or
    // transparency) behind it, no blend-mode tricks needed
    const eyeOrigins = [[margin, margin], [margin, margin + size - 7], [margin + size - 7, margin]];
    let eyeShapes = '';
    for (const [er, ec] of eyeOrigins) {
      const cx = ec + 3.5, cy = er + 3.5;
      const ringPath = shapePath(cx, cy, 3.5, cornerStyle) + ' ' + shapePath(cx, cy, 2.5, cornerStyle);
      eyeShapes += `<path fill-rule="evenodd" d="${ringPath}"/>`;
      eyeShapes += `<path d="${shapePath(cx, cy, 1.5, cornerStyle)}"/>`;
    }

    const bg = transparentBackground ? '' : `<rect width="${total}" height="${total}" fill="${light}"/>`;
    const defsBlock = defs ? `<defs>${defs}</defs>` : '';
    return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${total} ${total}">` +
      defsBlock + bg +
      `<g ${fillRef}>${shapes}${eyeShapes}</g>` +
      `</svg>`;
  }

  const Encoder = { generateMatrix, renderToCanvas, renderToSvg, detectMode, chooseVersion, buildCodewords, BitBuffer };

  if (typeof module !== 'undefined' && module.exports) {
    module.exports = Encoder;
  } else {
    root.QREncoder = Encoder;
  }
})(typeof window !== 'undefined' ? window : globalThis);
