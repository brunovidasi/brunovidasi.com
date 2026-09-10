// QR Code decoder: canvas/image -> module matrix -> text.
// Two layers: decodeMatrix() is the pure ISO/IEC 18004 inverse of the
// encoder (format/version info, unmasking, Reed-Solomon correction,
// segment parsing) operating on a clean boolean module grid; everything
// above that (binarization, finder-pattern detection, perspective
// sampling) turns a real photo/camera frame into that grid.
(function (root) {
  'use strict';

  const QR = typeof module !== 'undefined' && module.exports ? require('./qr-common.js') : root.QR;

  // ---- Layer 1: matrix -> text ----

  function extractFormatCopyA(matrix) {
    const get = (r, c) => matrix[r][c] ? 1 : 0;
    let bits = 0;
    for (let i = 0; i <= 5; i++) bits |= get(i, 8) << i;
    bits |= get(7, 8) << 6;
    bits |= get(8, 8) << 7;
    bits |= get(8, 7) << 8;
    for (let i = 9; i < 15; i++) bits |= get(8, 14 - i) << i;
    return bits;
  }

  function extractFormatCopyB(matrix, size) {
    const get = (r, c) => matrix[r][c] ? 1 : 0;
    let bits = 0;
    for (let i = 0; i <= 7; i++) bits |= get(8, size - 1 - i) << i;
    for (let i = 8; i < 15; i++) bits |= get(size - 15 + i, 8) << i;
    return bits;
  }

  function bestFormatMatch(rawBits) {
    let bestIdx = -1;
    let bestDist = 99;
    for (let d = 0; d < 32; d++) {
      const dist = QR.popcount(QR.ALL_FORMAT_INFOS[d] ^ rawBits);
      if (dist < bestDist) {
        bestDist = dist;
        bestIdx = d;
      }
    }
    return { data: bestIdx, distance: bestDist };
  }

  function readFormatInfo(matrix, size) {
    const a = bestFormatMatch(extractFormatCopyA(matrix));
    const b = bestFormatMatch(extractFormatCopyB(matrix, size));
    const best = a.distance <= b.distance ? a : b;
    if (best.distance > 3) return null; // format info uncorrectable (max 3-bit distance guaranteed)
    const levelBits = best.data >> 3;
    const mask = best.data & 7;
    const level = QR.EC_LEVELS.find((l) => QR.EC_LEVEL_BITS[l] === levelBits);
    return { level, mask };
  }

  function readCodewords(matrix, version, mask) {
    const order = QR.getZigzagOrder(version);
    const bits = order.map(([row, col]) => {
      const raw = matrix[row][col] ? 1 : 0;
      return QR.applyMask(mask, row, col) ? raw ^ 1 : raw;
    });
    const info = QR.getRsBlockInfo(version, 'L'); // block layout doesn't depend on level's byte-count here
    const totalCodewords = order.length >> 3;
    const bytes = [];
    for (let i = 0; i < totalCodewords; i++) {
      let b = 0;
      for (let j = 0; j < 8; j++) b = (b << 1) | (bits[i * 8 + j] || 0);
      bytes.push(b);
    }
    return bytes;
  }

  function deinterleaveAndCorrect(codewords, version, level) {
    const info = QR.getRsBlockInfo(version, level);
    const blocksMeta = [];
    for (const group of info.groups) {
      for (let b = 0; b < group.count; b++) blocksMeta.push({ dataLen: group.dataCodewords });
    }
    const ecLen = info.ecCodewordsPerBlock;
    const maxDataLen = Math.max(...blocksMeta.map((b) => b.dataLen));

    const blockData = blocksMeta.map(() => []);
    const blockEc = blocksMeta.map(() => []);
    let idx = 0;
    for (let i = 0; i < maxDataLen; i++) {
      for (let b = 0; b < blocksMeta.length; b++) {
        if (i < blocksMeta[b].dataLen) blockData[b].push(codewords[idx++]);
      }
    }
    for (let i = 0; i < ecLen; i++) {
      for (let b = 0; b < blocksMeta.length; b++) blockEc[b].push(codewords[idx++]);
    }

    const correctedBlocks = [];
    for (let b = 0; b < blocksMeta.length; b++) {
      const full = blockData[b].concat(blockEc[b]);
      const corrected = QR.rsDecode(full, ecLen);
      if (!corrected) return null;
      correctedBlocks.push(corrected);
    }
    return correctedBlocks.reduce((acc, block) => acc.concat(block), []);
  }

  class BitReader {
    constructor(bytes) {
      this.bytes = bytes;
      this.pos = 0; // bit position
    }
    hasBits(n) {
      return this.pos + n <= this.bytes.length * 8;
    }
    read(n) {
      let v = 0;
      for (let i = 0; i < n; i++) {
        const byteIdx = this.pos >> 3;
        const bitIdx = 7 - (this.pos & 7);
        const bit = byteIdx < this.bytes.length ? (this.bytes[byteIdx] >>> bitIdx) & 1 : 0;
        v = (v << 1) | bit;
        this.pos++;
      }
      return v;
    }
  }

  function decodeSegments(dataCodewords, version) {
    const reader = new BitReader(dataCodewords);
    let text = '';
    const byteChunks = [];

    function flushBytes() {
      if (byteChunks.length) {
        text += new TextDecoder('utf-8', { fatal: false }).decode(new Uint8Array(byteChunks));
        byteChunks.length = 0;
      }
    }

    while (reader.hasBits(4)) {
      const modeBits = reader.read(4);
      const mode = QR.MODE_BY_INDICATOR[modeBits];
      if (mode === undefined || mode === 'terminator') break;

      if (mode === 'eci') {
        reader.read(8); // skip ECI designator (single-byte form); not otherwise interpreted
        continue;
      }

      if (!reader.hasBits(QR.charCountBits(mode, version))) break;
      const count = reader.read(QR.charCountBits(mode, version));

      if (mode === 'numeric') {
        flushBytes();
        let remaining = count;
        while (remaining > 0) {
          const chunkLen = Math.min(3, remaining);
          const bits = chunkLen === 3 ? 10 : chunkLen === 2 ? 7 : 4;
          if (!reader.hasBits(bits)) return text;
          const value = reader.read(bits);
          text += String(value).padStart(chunkLen, '0');
          remaining -= chunkLen;
        }
      } else if (mode === 'alphanumeric') {
        flushBytes();
        let remaining = count;
        while (remaining >= 2) {
          if (!reader.hasBits(11)) return text;
          const value = reader.read(11);
          text += QR.ALPHANUMERIC_CHARS[Math.floor(value / 45)] + QR.ALPHANUMERIC_CHARS[value % 45];
          remaining -= 2;
        }
        if (remaining === 1) {
          if (!reader.hasBits(6)) return text;
          text += QR.ALPHANUMERIC_CHARS[reader.read(6)];
        }
      } else if (mode === 'byte') {
        for (let i = 0; i < count; i++) {
          if (!reader.hasBits(8)) { flushBytes(); return text; }
          byteChunks.push(reader.read(8));
        }
      } else if (mode === 'kanji') {
        flushBytes();
        for (let i = 0; i < count; i++) {
          if (!reader.hasBits(13)) return text;
          reader.read(13); // Shift-JIS Kanji mode is not decoded to characters, only skipped
        }
      }
    }
    flushBytes();
    return text;
  }

  // Decode a clean boolean module matrix (no quiet zone, matrix[row][col]
  // truthy = dark) into { text, version, level, mask } or null on failure.
  function decodeMatrix(matrix) {
    const size = matrix.length;
    if ((size - 17) % 4 !== 0) return null;
    const version = (size - 17) / 4;
    if (version < 1 || version > 40) return null;

    const format = readFormatInfo(matrix, size);
    if (!format) return null;

    const codewords = readCodewords(matrix, version, format.mask);
    const dataCodewords = deinterleaveAndCorrect(codewords, version, format.level);
    if (!dataCodewords) return null;

    const text = decodeSegments(dataCodewords, version);
    return { text, version, level: format.level, mask: format.mask };
  }

  // ---- Layer 2: raw image -> module matrix ----
  // Binarizes with a local-mean adaptive threshold (robust to uneven camera
  // lighting), finds the three finder patterns via run-length ratio scans
  // (the standard 1:1:3:1:1 signature), builds a projective transform from
  // their centers (Heckbert's unit-square-to-quadrilateral construction),
  // and samples the module grid through it.

  function toGrayscale(imageData) {
    const { data, width, height } = imageData;
    const gray = new Uint8ClampedArray(width * height);
    for (let i = 0, p = 0; i < gray.length; i++, p += 4) {
      gray[i] = (data[p] * 0.299 + data[p + 1] * 0.587 + data[p + 2] * 0.114) | 0;
    }
    return gray;
  }

  // returns a Uint8Array where 1 = dark module, using a local-mean adaptive threshold
  function binarize(gray, width, height) {
    const integral = new Float64Array((width + 1) * (height + 1));
    for (let y = 0; y < height; y++) {
      let rowSum = 0;
      for (let x = 0; x < width; x++) {
        rowSum += gray[y * width + x];
        integral[(y + 1) * (width + 1) + (x + 1)] = integral[y * (width + 1) + (x + 1)] + rowSum;
      }
    }
    // Bradley/Roth adaptive threshold: block size must be a healthy
    // fraction of the image so it can't fit entirely inside one large
    // uniform region (e.g. a finder pattern's solid core) — a too-small
    // window there would compare each pixel to itself and never fire.
    const half = Math.max(8, Math.floor(Math.min(width, height) / 8));
    const percent = 0.85;
    const out = new Uint8Array(width * height);
    for (let y = 0; y < height; y++) {
      const y0 = Math.max(0, y - half);
      const y1 = Math.min(height - 1, y + half);
      for (let x = 0; x < width; x++) {
        const x0 = Math.max(0, x - half);
        const x1 = Math.min(width - 1, x + half);
        const area = (x1 - x0 + 1) * (y1 - y0 + 1);
        const sum = integral[(y1 + 1) * (width + 1) + (x1 + 1)]
          - integral[(y0) * (width + 1) + (x1 + 1)]
          - integral[(y1 + 1) * (width + 1) + (x0)]
          + integral[(y0) * (width + 1) + (x0)];
        const mean = sum / area;
        out[y * width + x] = gray[y * width + x] < mean * percent ? 1 : 0;
      }
    }
    return out;
  }

  function runLengths(getPixel, length) {
    const runs = [];
    let color = getPixel(0);
    let count = 1;
    for (let i = 1; i < length; i++) {
      const c = getPixel(i);
      if (c === color) {
        count++;
      } else {
        runs.push({ color, length: count, start: i - count });
        color = c;
        count = 1;
      }
    }
    runs.push({ color, length: count, start: length - count });
    return runs;
  }

  function findRatioCandidates(runs) {
    const candidates = [];
    for (let i = 0; i + 4 < runs.length; i++) {
      const w = [runs[i].length, runs[i + 1].length, runs[i + 2].length, runs[i + 3].length, runs[i + 4].length];
      if (runs[i].color !== 1 || runs[i + 2].color !== 1 || runs[i + 4].color !== 1) continue;
      if (runs[i + 1].color !== 0 || runs[i + 3].color !== 0) continue;
      const unit = (w[0] + w[1] + w[3] + w[4]) / 4;
      if (unit < 1) continue;
      const ok = Math.abs(w[0] - unit) < unit * 0.75
        && Math.abs(w[1] - unit) < unit * 0.75
        && Math.abs(w[3] - unit) < unit * 0.75
        && Math.abs(w[4] - unit) < unit * 0.75
        && w[2] > unit * 1.5 && w[2] < unit * 5.5;
      if (!ok) continue;
      const center = runs[i].start + w[0] + w[1] + w[2] / 2;
      const moduleSize = (w[0] + w[1] + w[2] + w[3] + w[4]) / 7;
      candidates.push({ center, moduleSize });
    }
    return candidates;
  }

  function findFinderCandidates(binary, width, height) {
    const raw = [];
    for (let y = 0; y < height; y++) {
      const runs = runLengths((x) => binary[y * width + x], width);
      for (const c of findRatioCandidates(runs)) raw.push({ x: c.center, y, moduleSize: c.moduleSize });
    }
    if (!raw.length) return [];

    // cluster nearby hits (many rows will cross each real finder pattern):
    // assign each point to its nearest existing centroid within a fixed
    // radius (not a running average, which can drift and chain-merge
    // unrelated hits), rejecting points whose module size doesn't match.
    const clusters = [];
    for (const pt of raw) {
      let best = null;
      let bestDist = Infinity;
      for (const cl of clusters) {
        const cx = cl.x / cl.n;
        const cy = cl.y / cl.n;
        const cms = cl.moduleSize / cl.n;
        if (Math.abs(cms - pt.moduleSize) > cms * 0.6) continue;
        const dist = Math.hypot(cx - pt.x, cy - pt.y);
        if (dist < cms * 2.5 && dist < bestDist) {
          bestDist = dist;
          best = cl;
        }
      }
      if (!best) {
        best = { x: 0, y: 0, moduleSize: 0, n: 0 };
        clusters.push(best);
      }
      best.x += pt.x;
      best.y += pt.y;
      best.moduleSize += pt.moduleSize;
      best.n++;
    }
    const centers = clusters
      .filter((cl) => cl.n >= 3)
      .map((cl) => ({ x: cl.x / cl.n, y: cl.y / cl.n, moduleSize: cl.moduleSize / cl.n, hits: cl.n }))
      .sort((a, b) => b.hits - a.hits)
      .slice(0, 20);

    // verify each with a vertical scan through its estimated center
    const verified = [];
    for (const c of centers) {
      const cx = Math.round(c.x);
      if (cx < 0 || cx >= width) continue;
      const runs = runLengths((y) => binary[y * width + cx], height);
      const vCandidates = findRatioCandidates(runs);
      const match = vCandidates.find((v) => Math.abs(v.center - c.y) < c.moduleSize * 3);
      if (match) verified.push({ x: c.x, y: match.center, moduleSize: (c.moduleSize + match.moduleSize) / 2 });
    }
    return verified;
  }

  function pickBestTriple(points) {
    if (points.length < 3) return null;
    if (points.length === 3) return orderTriple(points[0], points[1], points[2]);
    let best = null;
    let bestScore = Infinity;
    for (let i = 0; i < points.length; i++) {
      for (let j = i + 1; j < points.length; j++) {
        for (let k = j + 1; k < points.length; k++) {
          const ordered = orderTriple(points[i], points[j], points[k]);
          if (!ordered) continue;
          const { topLeft, topRight, bottomLeft } = ordered;
          const dTR = Math.hypot(topRight.x - topLeft.x, topRight.y - topLeft.y);
          const dBL = Math.hypot(bottomLeft.x - topLeft.x, bottomLeft.y - topLeft.y);
          const score = Math.abs(dTR - dBL) / Math.max(dTR, dBL);
          if (score < bestScore) {
            bestScore = score;
            best = ordered;
          }
        }
      }
    }
    return bestScore < 0.6 ? best : null;
  }

  // Identify which of 3 finder centers is top-left (largest angle, ~90deg)
  // and orient the other two via cross-product sign.
  function orderTriple(p0, p1, p2) {
    const pts = [p0, p1, p2];
    function dist2(a, b) { return (a.x - b.x) ** 2 + (a.y - b.y) ** 2; }
    const d01 = dist2(pts[0], pts[1]);
    const d02 = dist2(pts[0], pts[2]);
    const d12 = dist2(pts[1], pts[2]);
    // the vertex opposite the longest side is the right-angle vertex (top-left)
    let topLeft, a, b;
    if (d12 >= d01 && d12 >= d02) { topLeft = pts[0]; a = pts[1]; b = pts[2]; }
    else if (d02 >= d01 && d02 >= d12) { topLeft = pts[1]; a = pts[0]; b = pts[2]; }
    else { topLeft = pts[2]; a = pts[0]; b = pts[1]; }
    const cross = (a.x - topLeft.x) * (b.y - topLeft.y) - (a.y - topLeft.y) * (b.x - topLeft.x);
    const [topRight, bottomLeft] = cross < 0 ? [b, a] : [a, b];
    return { topLeft, topRight, bottomLeft };
  }

  function computeProjectiveTransform(x0, y0, x1, y1, x2, y2, x3, y3) {
    // maps unit square (0,0),(1,0),(1,1),(0,1) -> quad (x0,y0),(x1,y1),(x2,y2),(x3,y3)
    const dx1 = x1 - x2, dx2 = x3 - x2, dx3 = x0 - x1 + x2 - x3;
    const dy1 = y1 - y2, dy2 = y3 - y2, dy3 = y0 - y1 + y2 - y3;
    let a, b, c, d, e, f, g, h;
    if (dx3 === 0 && dy3 === 0) {
      a = x1 - x0; b = x2 - x1; c = x0;
      d = y1 - y0; e = y2 - y1; f = y0;
      g = 0; h = 0;
    } else {
      const denom = dx1 * dy2 - dx2 * dy1;
      const a13 = (dx3 * dy2 - dx2 * dy3) / denom;
      const a23 = (dx1 * dy3 - dx3 * dy1) / denom;
      a = x1 - x0 + a13 * x1; b = x3 - x0 + a23 * x3; c = x0;
      d = y1 - y0 + a13 * y1; e = y3 - y0 + a23 * y3; f = y0;
      g = a13; h = a23;
    }
    return (u, v) => {
      const denom = g * u + h * v + 1;
      return [(a * u + b * v + c) / denom, (d * u + e * v + f) / denom];
    };
  }

  function sampleMatrix(binary, width, height, transform, size) {
    const matrix = [];
    for (let row = 0; row < size; row++) {
      const line = [];
      for (let col = 0; col < size; col++) {
        const u = (col + 0.5 - 3.5) / (size - 7);
        const v = (row + 0.5 - 3.5) / (size - 7);
        const [px, py] = transform(u, v);
        const x = Math.round(px);
        const y = Math.round(py);
        line.push(x >= 0 && x < width && y >= 0 && y < height ? binary[y * width + x] : 0);
      }
      matrix.push(line);
    }
    return matrix;
  }

  // Full pipeline: ImageData (or {data,width,height} shaped alike) -> result.
  // Returns { text, version, level, mask } on success, or null if no QR
  // code could be found/decoded.
  function decodeImageData(imageData) {
    const { width, height } = imageData;
    const gray = toGrayscale(imageData);
    const binary = binarize(gray, width, height);
    const candidates = findFinderCandidates(binary, width, height);
    const triple = pickBestTriple(candidates);
    if (!triple) return null;
    const { topLeft, topRight, bottomLeft } = triple;
    const moduleSize = (topLeft.moduleSize + topRight.moduleSize + bottomLeft.moduleSize) / 3;
    if (moduleSize <= 0) return null;

    const dTR = Math.hypot(topRight.x - topLeft.x, topRight.y - topLeft.y);
    const estModules = dTR / moduleSize;
    const estVersion = Math.round((estModules + 7 - 17) / 4);

    const bottomRight = {
      x: topRight.x + bottomLeft.x - topLeft.x,
      y: topRight.y + bottomLeft.y - topLeft.y,
    };
    const transform = computeProjectiveTransform(
      topLeft.x, topLeft.y,
      topRight.x, topRight.y,
      bottomRight.x, bottomRight.y,
      bottomLeft.x, bottomLeft.y,
    );

    const versionsToTry = [];
    for (let dv = 0; dv <= 3; dv++) {
      for (const v of dv === 0 ? [estVersion] : [estVersion - dv, estVersion + dv]) {
        if (v >= 1 && v <= 40 && !versionsToTry.includes(v)) versionsToTry.push(v);
      }
    }

    for (const version of versionsToTry) {
      const size = 17 + version * 4;
      const matrix = sampleMatrix(binary, width, height, transform, size);
      const result = decodeMatrix(matrix);
      if (result && result.text !== undefined) return result;
    }
    return null;
  }

  const Decoder = {
    decodeMatrix,
    readFormatInfo,
    readCodewords,
    deinterleaveAndCorrect,
    decodeSegments,
    BitReader,
    decodeImageData,
    toGrayscale,
    binarize,
    findFinderCandidates,
    computeProjectiveTransform,
  };

  if (typeof module !== 'undefined' && module.exports) {
    module.exports = Decoder;
  } else {
    root.QRDecoder = Decoder;
  }
})(typeof window !== 'undefined' ? window : globalThis);
