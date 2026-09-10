// Shared QR Code tables and Galois Field / Reed-Solomon math, used by both
// the encoder and the decoder. Implements ISO/IEC 18004 GF(256) arithmetic
// (primitive polynomial 0x11D) plus the standard error-correction block
// table and alignment-pattern position table.
(function (root) {
  'use strict';

  const RS_BLOCK_TABLE = [
    [1, 26, 19], [1, 26, 16], [1, 26, 13], [1, 26, 9],
    [1, 44, 34], [1, 44, 28], [1, 44, 22], [1, 44, 16],
    [1, 70, 55], [1, 70, 44], [2, 35, 17], [2, 35, 13],
    [1, 100, 80], [2, 50, 32], [2, 50, 24], [4, 25, 9],
    [1, 134, 108], [2, 67, 43], [2, 33, 15, 2, 34, 16], [2, 33, 11, 2, 34, 12],
    [2, 86, 68], [4, 43, 27], [4, 43, 19], [4, 43, 15],
    [2, 98, 78], [4, 49, 31], [2, 32, 14, 4, 33, 15], [4, 39, 13, 1, 40, 14],
    [2, 121, 97], [2, 60, 38, 2, 61, 39], [4, 40, 18, 2, 41, 19], [4, 40, 14, 2, 41, 15],
    [2, 146, 116], [3, 58, 36, 2, 59, 37], [4, 36, 16, 4, 37, 17], [4, 36, 12, 4, 37, 13],
    [2, 86, 68, 2, 87, 69], [4, 69, 43, 1, 70, 44], [6, 43, 19, 2, 44, 20], [6, 43, 15, 2, 44, 16],
    [4, 101, 81], [1, 80, 50, 4, 81, 51], [4, 50, 22, 4, 51, 23], [3, 36, 12, 8, 37, 13],
    [2, 116, 92, 2, 117, 93], [6, 58, 36, 2, 59, 37], [4, 46, 20, 6, 47, 21], [7, 42, 14, 4, 43, 15],
    [4, 133, 107], [8, 59, 37, 1, 60, 38], [8, 44, 20, 4, 45, 21], [12, 33, 11, 4, 34, 12],
    [3, 145, 115, 1, 146, 116], [4, 64, 40, 5, 65, 41], [11, 36, 16, 5, 37, 17], [11, 36, 12, 5, 37, 13],
    [5, 109, 87, 1, 110, 88], [5, 65, 41, 5, 66, 42], [5, 54, 24, 7, 55, 25], [11, 36, 12, 7, 37, 13],
    [5, 122, 98, 1, 123, 99], [7, 73, 45, 3, 74, 46], [15, 43, 19, 2, 44, 20], [3, 45, 15, 13, 46, 16],
    [1, 135, 107, 5, 136, 108], [10, 74, 46, 1, 75, 47], [1, 50, 22, 15, 51, 23], [2, 42, 14, 17, 43, 15],
    [5, 150, 120, 1, 151, 121], [9, 69, 43, 4, 70, 44], [17, 50, 22, 1, 51, 23], [2, 42, 14, 19, 43, 15],
    [3, 141, 113, 4, 142, 114], [3, 70, 44, 11, 71, 45], [17, 47, 21, 4, 48, 22], [9, 39, 13, 16, 40, 14],
    [3, 135, 107, 5, 136, 108], [3, 67, 41, 13, 68, 42], [15, 54, 24, 5, 55, 25], [15, 43, 15, 10, 44, 16],
    [4, 144, 116, 4, 145, 117], [17, 68, 42], [17, 50, 22, 6, 51, 23], [19, 46, 16, 6, 47, 17],
    [2, 139, 111, 7, 140, 112], [17, 74, 46], [7, 54, 24, 16, 55, 25], [34, 37, 13],
    [4, 151, 121, 5, 152, 122], [4, 75, 47, 14, 76, 48], [11, 54, 24, 14, 55, 25], [16, 45, 15, 14, 46, 16],
    [6, 147, 117, 4, 148, 118], [6, 73, 45, 14, 74, 46], [11, 54, 24, 16, 55, 25], [30, 46, 16, 2, 47, 17],
    [8, 132, 106, 4, 133, 107], [8, 75, 47, 13, 76, 48], [7, 54, 24, 22, 55, 25], [22, 45, 15, 13, 46, 16],
    [10, 142, 114, 2, 143, 115], [19, 74, 46, 4, 75, 47], [28, 50, 22, 6, 51, 23], [33, 46, 16, 4, 47, 17],
    [8, 152, 122, 4, 153, 123], [22, 73, 45, 3, 74, 46], [8, 53, 23, 26, 54, 24], [12, 45, 15, 28, 46, 16],
    [3, 147, 117, 10, 148, 118], [3, 73, 45, 23, 74, 46], [4, 54, 24, 31, 55, 25], [11, 45, 15, 31, 46, 16],
    [7, 146, 116, 7, 147, 117], [21, 73, 45, 7, 74, 46], [1, 53, 23, 37, 54, 24], [19, 45, 15, 26, 46, 16],
    [5, 145, 115, 10, 146, 116], [19, 75, 47, 10, 76, 48], [15, 54, 24, 25, 55, 25], [23, 45, 15, 25, 46, 16],
    [13, 145, 115, 3, 146, 116], [2, 74, 46, 29, 75, 47], [42, 54, 24, 1, 55, 25], [23, 45, 15, 28, 46, 16],
    [17, 145, 115], [10, 74, 46, 23, 75, 47], [10, 54, 24, 35, 55, 25], [19, 45, 15, 35, 46, 16],
    [17, 145, 115, 1, 146, 116], [14, 74, 46, 21, 75, 47], [29, 54, 24, 19, 55, 25], [11, 45, 15, 46, 46, 16],
    [13, 145, 115, 6, 146, 116], [14, 74, 46, 23, 75, 47], [44, 54, 24, 7, 55, 25], [59, 46, 16, 1, 47, 17],
    [12, 151, 121, 7, 152, 122], [12, 75, 47, 26, 76, 48], [39, 54, 24, 14, 55, 25], [22, 45, 15, 41, 46, 16],
    [6, 151, 121, 14, 152, 122], [6, 75, 47, 34, 76, 48], [46, 54, 24, 10, 55, 25], [2, 45, 15, 64, 46, 16],
    [17, 152, 122, 4, 153, 123], [29, 74, 46, 14, 75, 47], [49, 54, 24, 10, 55, 25], [24, 45, 15, 46, 46, 16],
    [4, 152, 122, 18, 153, 123], [13, 74, 46, 32, 75, 47], [48, 54, 24, 14, 55, 25], [42, 45, 15, 32, 46, 16],
    [20, 147, 117, 4, 148, 118], [40, 75, 47, 7, 76, 48], [43, 54, 24, 22, 55, 25], [10, 45, 15, 67, 46, 16],
    [19, 148, 118, 6, 149, 119], [18, 75, 47, 31, 76, 48], [34, 54, 24, 34, 55, 25], [20, 45, 15, 61, 46, 16],
  ];

  const ALIGNMENT_POSITIONS = [
    [],
    [6, 18], [6, 22], [6, 26], [6, 30], [6, 34],
    [6, 22, 38], [6, 24, 42], [6, 26, 46], [6, 28, 50], [6, 30, 54], [6, 32, 58], [6, 34, 62],
    [6, 26, 46, 66], [6, 26, 48, 70], [6, 26, 50, 74], [6, 30, 54, 78], [6, 30, 56, 82], [6, 30, 58, 86], [6, 34, 62, 90],
    [6, 28, 50, 72, 94], [6, 26, 50, 74, 98], [6, 30, 54, 78, 102], [6, 28, 54, 80, 106], [6, 32, 58, 84, 110], [6, 30, 58, 86, 114], [6, 34, 62, 90, 118],
    [6, 26, 50, 74, 98, 122], [6, 30, 54, 78, 102, 126], [6, 26, 52, 78, 104, 130], [6, 30, 56, 82, 108, 134], [6, 34, 60, 86, 112, 138], [6, 30, 58, 86, 114, 142], [6, 34, 62, 90, 118, 146],
    [6, 30, 54, 78, 102, 126, 150], [6, 24, 50, 76, 102, 128, 154], [6, 28, 54, 80, 106, 132, 158], [6, 32, 58, 84, 110, 136, 162], [6, 26, 54, 82, 110, 138, 166], [6, 30, 58, 86, 114, 142, 170],
  ];

  // EC level order used to index RS_BLOCK_TABLE rows: L, M, Q, H
  const EC_LEVELS = ['L', 'M', 'Q', 'H'];
  const EC_LEVEL_BITS = { L: 1, M: 0, Q: 3, H: 2 }; // format-info 2-bit indicator

  // ---- GF(256) arithmetic, primitive polynomial x^8+x^4+x^3+x^2+1 (0x11D) ----
  const GF_EXP = new Array(512);
  const GF_LOG = new Array(256);
  (function buildGF() {
    for (let i = 0; i < 8; i++) GF_EXP[i] = 1 << i;
    for (let i = 8; i < 256; i++) {
      GF_EXP[i] = GF_EXP[i - 4] ^ GF_EXP[i - 5] ^ GF_EXP[i - 6] ^ GF_EXP[i - 8];
    }
    for (let i = 0; i < 255; i++) GF_LOG[GF_EXP[i]] = i;
    for (let i = 255; i < 512; i++) GF_EXP[i] = GF_EXP[i - 255];
  })();

  function gfMul(a, b) {
    if (a === 0 || b === 0) return 0;
    return GF_EXP[GF_LOG[a] + GF_LOG[b]];
  }

  function gfDiv(a, b) {
    if (a === 0) return 0;
    return GF_EXP[(GF_LOG[a] - GF_LOG[b] + 255) % 255];
  }

  function gfPow(a, n) {
    if (a === 0) return n === 0 ? 1 : 0;
    const e = ((GF_LOG[a] * n) % 255 + 255) % 255;
    return GF_EXP[e];
  }

  // Build the RS generator polynomial of given degree (coefficients, highest degree first)
  function rsGeneratorPoly(degree) {
    let poly = [1];
    for (let i = 0; i < degree; i++) {
      const next = new Array(poly.length + 1).fill(0);
      for (let j = 0; j < poly.length; j++) {
        next[j] ^= gfMul(poly[j], 1);
        next[j + 1] ^= gfMul(poly[j], GF_EXP[i]);
      }
      poly = next;
    }
    return poly;
  }

  // Compute RS error-correction codewords for a list of data codeword bytes.
  function rsEncode(dataCodewords, ecCount) {
    const generator = rsGeneratorPoly(ecCount);
    const result = new Array(dataCodewords.length + ecCount).fill(0);
    for (let i = 0; i < dataCodewords.length; i++) result[i] = dataCodewords[i];
    for (let i = 0; i < dataCodewords.length; i++) {
      const coef = result[i];
      if (coef === 0) continue;
      for (let j = 0; j < generator.length; j++) {
        result[i + j] ^= gfMul(generator[j], coef);
      }
    }
    return result.slice(dataCodewords.length);
  }

  // Reed-Solomon decode: correct up to floor(ecCount/2) byte errors in-place.
  // `codewords` = data+ec codewords for one block. Returns corrected data
  // codewords (length = codewords.length - ecCount), or null if uncorrectable.
  function rsDecode(codewords, ecCount) {
    const n = codewords.length;
    const poly = codewords.slice();

    // Syndromes
    const syndromes = new Array(ecCount).fill(0);
    let hasError = false;
    for (let i = 0; i < ecCount; i++) {
      let s = 0;
      for (let j = 0; j < n; j++) {
        s = gfMul(s, GF_EXP[i]) ^ poly[j];
      }
      syndromes[i] = s;
      if (s !== 0) hasError = true;
    }
    if (!hasError) return poly.slice(0, n - ecCount);

    // Berlekamp-Massey to find error locator polynomial
    let errLoc = [1];
    let oldLoc = [1];
    for (let i = 0; i < ecCount; i++) {
      oldLoc.push(0);
      let delta = syndromes[i];
      for (let j = 1; j < errLoc.length; j++) {
        const sv = i - j >= 0 ? syndromes[i - j] : 0;
        delta ^= gfMul(errLoc[errLoc.length - 1 - j], sv);
      }
      if (delta !== 0) {
        if (oldLoc.length > errLoc.length) {
          const newLoc = polyScale(oldLoc, delta);
          oldLoc = polyScale(errLoc, gfDiv(1, delta));
          errLoc = newLoc;
        }
        errLoc = polyXor(errLoc, polyScale(oldLoc, delta));
      }
    }
    // strip leading zeros
    let errStart = 0;
    while (errStart < errLoc.length && errLoc[errStart] === 0) errStart++;
    errLoc = errLoc.slice(errStart);
    const numErrors = errLoc.length - 1;
    if (numErrors <= 0 || numErrors > ecCount / 2) return null;

    // Chien search: find roots of errLoc (evaluated highest-degree-first via
    // Horner, at x = alpha^i). QR's per-block codewords are a *shortened*
    // RS code, so a root can land anywhere in the full GF(256) exponent
    // range (0..254), not just 0..n-1 — only the resulting position needs
    // to fall inside the actual codeword. errLoc's roots are at X_k^-1, and
    // X_k = alpha^(n-1-pos), so alpha^i = X_k^-1 = alpha^(pos-(n-1)),
    // giving pos = (i + n - 1) mod 255.
    const errPositions = [];
    for (let i = 0; i < 255; i++) {
      const x = GF_EXP[i];
      let y = errLoc[0];
      for (let m = 1; m < errLoc.length; m++) {
        y = gfMul(y, x) ^ errLoc[m];
      }
      if (y === 0) {
        const pos = (i + n - 1) % 255;
        if (pos < n) errPositions.push(pos);
      }
    }
    if (errPositions.length !== numErrors) return null; // uncorrectable

    // lambda[j] = coefficient of x^j in the error locator (low-degree-first)
    const lambda = new Array(errLoc.length);
    for (let j = 0; j < errLoc.length; j++) lambda[j] = errLoc[errLoc.length - 1 - j];

    // omega(x) = [S(x) * Lambda(x)] mod x^ecCount, low-degree-first,
    // where S(x) = syndromes[0] + syndromes[1] x + ...
    const omega = new Array(ecCount).fill(0);
    for (let i = 0; i < ecCount; i++) {
      for (let j = 0; j < lambda.length && i + j < ecCount; j++) {
        omega[i + j] ^= gfMul(syndromes[i], lambda[j]);
      }
    }

    // Forney algorithm: compute error magnitudes and correct
    const corrected = poly.slice();
    for (const pos of errPositions) {
      const i = n - 1 - pos;
      const xk = GF_EXP[i];
      const xkInv = gfDiv(1, xk);

      let omegaAtXkInv = 0;
      for (let k = 0; k < omega.length; k++) omegaAtXkInv ^= gfMul(omega[k], gfPow(xkInv, k));

      let lambdaPrimeAtXkInv = 0;
      for (let j = 1; j < lambda.length; j += 2) lambdaPrimeAtXkInv ^= gfMul(lambda[j], gfPow(xkInv, j - 1));

      if (lambdaPrimeAtXkInv === 0) return null;
      const magnitude = gfMul(xk, gfDiv(omegaAtXkInv, lambdaPrimeAtXkInv));
      corrected[pos] ^= magnitude;
    }

    // verify
    for (let i = 0; i < ecCount; i++) {
      let s = 0;
      for (let j = 0; j < n; j++) s = gfMul(s, GF_EXP[i]) ^ corrected[j];
      if (s !== 0) return null;
    }
    return corrected.slice(0, n - ecCount);
  }

  function polyScale(poly, scalar) {
    return poly.map((c) => gfMul(c, scalar));
  }

  function polyXor(a, b) {
    const len = Math.max(a.length, b.length);
    const result = new Array(len).fill(0);
    for (let i = 0; i < a.length; i++) result[i + len - a.length] ^= a[i];
    for (let i = 0; i < b.length; i++) result[i + len - b.length] ^= b[i];
    return result;
  }

  function getRsBlockInfo(version, level) {
    const levelIdx = EC_LEVELS.indexOf(level);
    const row = RS_BLOCK_TABLE[(version - 1) * 4 + levelIdx];
    const groups = [];
    for (let i = 0; i < row.length; i += 3) {
      groups.push({ count: row[i], totalCodewords: row[i + 1], dataCodewords: row[i + 2] });
    }
    const ecCodewordsPerBlock = groups[0].totalCodewords - groups[0].dataCodewords;
    const totalDataCodewords = groups.reduce((sum, g) => sum + g.count * g.dataCodewords, 0);
    const totalBlocks = groups.reduce((sum, g) => sum + g.count, 0);
    return { groups, ecCodewordsPerBlock, totalDataCodewords, totalBlocks };
  }

  // ---- BCH format / version info (ISO/IEC 18004 Annex C/D) ----
  const G15 = 0x537; // 10100110111
  const G18 = 0x1F25; // 1111100100101
  const G15_MASK = 0x5412;

  function bchDigitCount(v) {
    let d = 0;
    while (v !== 0) { d++; v >>>= 1; }
    return d;
  }

  function bchTypeInfo(data) {
    let d = data << 10;
    const g15Digits = bchDigitCount(G15);
    while (bchDigitCount(d) - g15Digits >= 0) {
      d ^= G15 << (bchDigitCount(d) - g15Digits);
    }
    return ((data << 10) | d) ^ G15_MASK;
  }

  function bchTypeNumber(data) {
    let d = data << 12;
    const g18Digits = bchDigitCount(G18);
    while (bchDigitCount(d) - g18Digits >= 0) {
      d ^= G18 << (bchDigitCount(d) - g18Digits);
    }
    return (data << 12) | d;
  }

  function formatInfoBits(level, maskPattern) {
    const data = (EC_LEVEL_BITS[level] << 3) | maskPattern;
    return bchTypeInfo(data);
  }

  // precompute all 32 valid format codewords for fuzzy matching on decode
  const ALL_FORMAT_INFOS = [];
  for (let d = 0; d < 32; d++) ALL_FORMAT_INFOS.push(bchTypeInfo(d));

  function versionInfoBits(version) {
    return bchTypeNumber(version);
  }

  const ALL_VERSION_INFOS = [];
  for (let v = 7; v <= 40; v++) ALL_VERSION_INFOS.push({ version: v, bits: bchTypeNumber(v) });

  function popcount(n) {
    let c = 0;
    while (n) { c += n & 1; n >>>= 1; }
    return c;
  }

  const ALPHANUMERIC_CHARS = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ $%*+-./:';

  function charCountBits(mode, version) {
    const table = {
      numeric: [10, 12, 14],
      alphanumeric: [9, 11, 13],
      byte: [8, 16, 16],
      kanji: [8, 10, 12],
    };
    const bounds = table[mode];
    if (version <= 9) return bounds[0];
    if (version <= 26) return bounds[1];
    return bounds[2];
  }

  const MODE_INDICATORS = { numeric: 1, alphanumeric: 2, byte: 4, kanji: 8, eci: 7, terminator: 0 };
  const MODE_BY_INDICATOR = { 1: 'numeric', 2: 'alphanumeric', 4: 'byte', 8: 'kanji', 7: 'eci', 0: 'terminator' };

  // ---- Module placement, shared by encoder and decoder so their bit
  // ordering can never drift apart ----

  function moduleCount(version) {
    return 17 + version * 4;
  }

  // True for any module whose value is fixed by the QR structure itself
  // (finder/separator, timing, alignment, format/version info, dark
  // module) rather than by the masked data/EC codeword stream.
  function isFunctionModule(size, version, row, col) {
    if (row < 9 && col < 9) return true;
    if (row < 9 && col >= size - 8) return true;
    if (row >= size - 8 && col < 9) return true;
    if (row === 6 || col === 6) return true;
    const positions = ALIGNMENT_POSITIONS[version - 1];
    for (const r of positions) {
      for (const c of positions) {
        if ((r <= 8 && c <= 8) || (r <= 8 && c >= size - 9) || (r >= size - 9 && c <= 8)) continue;
        if (Math.abs(row - r) <= 2 && Math.abs(col - c) <= 2) return true;
      }
    }
    if (version >= 7) {
      if (row < 6 && col >= size - 11 && col <= size - 9) return true;
      if (col < 6 && row >= size - 11 && row <= size - 9) return true;
    }
    // (row>=size-8 on the col===8 branch also covers the dark module at (size-8, 8))
    if ((row === 8 && (col < 9 || col >= size - 8)) || (col === 8 && (row < 9 || row >= size - 8))) return true;
    return false;
  }

  function applyMask(pattern, row, col) {
    switch (pattern) {
      case 0: return (row + col) % 2 === 0;
      case 1: return row % 2 === 0;
      case 2: return col % 3 === 0;
      case 3: return (row + col) % 3 === 0;
      case 4: return (Math.floor(row / 2) + Math.floor(col / 3)) % 2 === 0;
      case 5: return ((row * col) % 2) + ((row * col) % 3) === 0;
      case 6: return (((row * col) % 2) + ((row * col) % 3)) % 2 === 0;
      case 7: return (((row + col) % 2) + ((row * col) % 3)) % 2 === 0;
      default: return false;
    }
  }

  // The standard QR zigzag bit-reading/writing path: two-column strips from
  // the right edge, alternating scan direction, skipping the timing column
  // and every function module. Returns [row, col] pairs in traversal order;
  // order[i] is where codeword-bit i lives, for encoding AND decoding alike.
  const zigzagOrderCache = new Map();
  function getZigzagOrder(version) {
    if (zigzagOrderCache.has(version)) return zigzagOrderCache.get(version);
    const size = moduleCount(version);
    const order = [];
    let col = size - 1;
    let dir = -1;
    while (col > 0) {
      if (col === 6) col--;
      for (let i = 0; i < size; i++) {
        const row = dir === -1 ? size - 1 - i : i;
        for (const c of [col, col - 1]) {
          if (isFunctionModule(size, version, row, c)) continue;
          order.push([row, c]);
        }
      }
      dir *= -1;
      col -= 2;
    }
    zigzagOrderCache.set(version, order);
    return order;
  }

  const QR = {
    RS_BLOCK_TABLE,
    ALIGNMENT_POSITIONS,
    EC_LEVELS,
    EC_LEVEL_BITS,
    ALPHANUMERIC_CHARS,
    MODE_INDICATORS,
    MODE_BY_INDICATOR,
    gfMul,
    gfDiv,
    gfPow,
    GF_EXP,
    GF_LOG,
    rsEncode,
    rsDecode,
    getRsBlockInfo,
    formatInfoBits,
    versionInfoBits,
    ALL_FORMAT_INFOS,
    ALL_VERSION_INFOS,
    popcount,
    charCountBits,
    moduleCount,
    isFunctionModule,
    applyMask,
    getZigzagOrder,
  };

  if (typeof module !== 'undefined' && module.exports) {
    module.exports = QR;
  } else {
    root.QR = QR;
  }
})(typeof window !== 'undefined' ? window : globalThis);
