'use strict';

/* ===================================================================
   Byte-level format constants & lookup tables
   =================================================================== */

const TYPE_SIZES = { 1: 1, 2: 1, 3: 2, 4: 4, 5: 8, 6: 1, 7: 1, 8: 2, 9: 4, 10: 8, 11: 4, 12: 8 };

const ORIENTATION_MAP = {
  1: 'Normal', 2: 'Mirrored horizontal', 3: 'Rotated 180°', 4: 'Mirrored vertical',
  5: 'Mirrored horizontal, rotated 270° CW', 6: 'Rotated 90° CW',
  7: 'Mirrored horizontal, rotated 90° CW', 8: 'Rotated 270° CW'
};
const METERING_MAP = { 0: 'Unknown', 1: 'Average', 2: 'Center-weighted', 3: 'Spot', 4: 'Multi-spot', 5: 'Pattern', 6: 'Partial', 255: 'Other' };
const SCENE_MAP = { 0: 'Standard', 1: 'Landscape', 2: 'Portrait', 3: 'Night' };
const EXPOSURE_PROGRAM_MAP = { 0: 'Not defined', 1: 'Manual', 2: 'Program AE', 3: 'Aperture priority', 4: 'Shutter priority', 5: 'Creative', 6: 'Action', 7: 'Portrait', 8: 'Landscape' };

const GROUP_ORDER = ['gps', 'camera', 'exposure', 'datetime', 'software', 'image', 'other'];
const GROUP_LABELS = { gps: 'GPS Location', camera: 'Camera & Lens', exposure: 'Exposure & Shooting', datetime: 'Date & Time', software: 'Software & Authoring', image: 'Image', other: 'Other' };

// dict entries: { label, group, sensitive?, fmt? (display formatter key), edit? ({kind, options?}) }
const IFD0_TAGS = {
  0x010F: { label: 'Make', group: 'camera', edit: { kind: 'text' } },
  0x0110: { label: 'Model', group: 'camera', edit: { kind: 'text' } },
  0x0112: { label: 'Orientation', group: 'image', fmt: 'orientation', edit: { kind: 'select', options: ORIENTATION_MAP } },
  0x011A: { label: 'X Resolution', group: 'image', fmt: 'rational' },
  0x011B: { label: 'Y Resolution', group: 'image', fmt: 'rational' },
  0x0128: { label: 'Resolution Unit', group: 'image', fmt: 'resUnit' },
  0x0131: { label: 'Software', group: 'software', edit: { kind: 'text' } },
  0x0132: { label: 'Modify Date', group: 'datetime', fmt: 'exifDate', edit: { kind: 'date' } },
  0x013B: { label: 'Artist', group: 'software', sensitive: true, edit: { kind: 'text' } },
  0x8298: { label: 'Copyright', group: 'software', edit: { kind: 'text' } }
};

const EXIF_TAGS = {
  0x829A: { label: 'Exposure Time', group: 'exposure', fmt: 'exposureTime', edit: { kind: 'exposureTime' } },
  0x829D: { label: 'F Number', group: 'exposure', fmt: 'fNumber', edit: { kind: 'decimal' } },
  0x8822: { label: 'Exposure Program', group: 'exposure', fmt: 'exposureProgram' },
  0x8827: { label: 'ISO Speed', group: 'exposure', edit: { kind: 'int' } },
  0x9003: { label: 'Date Taken', group: 'datetime', fmt: 'exifDate', edit: { kind: 'date' } },
  0x9004: { label: 'Date Digitized', group: 'datetime', fmt: 'exifDate', edit: { kind: 'date' } },
  0x9201: { label: 'Shutter Speed', group: 'exposure', fmt: 'shutterApex' },
  0x9202: { label: 'Aperture Value', group: 'exposure', fmt: 'apertureApex' },
  0x9204: { label: 'Exposure Bias', group: 'exposure', fmt: 'signedRational' },
  0x9205: { label: 'Max Aperture', group: 'exposure', fmt: 'apertureApex' },
  0x9207: { label: 'Metering Mode', group: 'exposure', fmt: 'meteringMode' },
  0x9209: { label: 'Flash', group: 'exposure', fmt: 'flash' },
  0x920A: { label: 'Focal Length', group: 'exposure', fmt: 'focalLength', edit: { kind: 'decimal' } },
  0x9286: { label: 'User Comment', group: 'software', fmt: 'userComment', edit: { kind: 'text' } },
  0xA002: { label: 'Image Width', group: 'image' },
  0xA003: { label: 'Image Height', group: 'image' },
  0xA403: { label: 'White Balance', group: 'exposure', fmt: 'whiteBalance' },
  0xA405: { label: 'Focal Length (35mm equiv.)', group: 'exposure', fmt: 'mmSuffix' },
  0xA406: { label: 'Scene Capture Type', group: 'exposure', fmt: 'sceneCaptureType' },
  0xA430: { label: 'Camera Owner Name', group: 'software', sensitive: true, edit: { kind: 'text' } },
  0xA431: { label: 'Body Serial Number', group: 'camera', sensitive: true, edit: { kind: 'text' } },
  0xA432: { label: 'Lens Specification', group: 'camera', fmt: 'lensSpec' },
  0xA433: { label: 'Lens Make', group: 'camera', edit: { kind: 'text' } },
  0xA434: { label: 'Lens Model', group: 'camera', edit: { kind: 'text' } },
  0xA435: { label: 'Lens Serial Number', group: 'camera', sensitive: true, edit: { kind: 'text' } }
};

const PNG_TEXT_LABELS = { Title: 'Title', Author: 'Author', Description: 'Description', Copyright: 'Copyright', 'Creation Time': 'Creation Time', Software: 'Software', Disclaimer: 'Disclaimer', Warning: 'Warning', Source: 'Source', Comment: 'Comment' };

const ADDABLE_JPEG_TAGS = [
  { key: 'ifd0:010F', ifd: 'ifd0', tag: 0x010F, label: 'Make', group: 'camera', editKind: 'text' },
  { key: 'ifd0:0110', ifd: 'ifd0', tag: 0x0110, label: 'Model', group: 'camera', editKind: 'text' },
  { key: 'ifd0:0131', ifd: 'ifd0', tag: 0x0131, label: 'Software', group: 'software', editKind: 'text' },
  { key: 'ifd0:013B', ifd: 'ifd0', tag: 0x013B, label: 'Artist', group: 'software', editKind: 'text', sensitive: true },
  { key: 'ifd0:8298', ifd: 'ifd0', tag: 0x8298, label: 'Copyright', group: 'software', editKind: 'text' },
  { key: 'exif:9003', ifd: 'exif', tag: 0x9003, label: 'Date Taken', group: 'datetime', editKind: 'date' },
  { key: 'exif:829A', ifd: 'exif', tag: 0x829A, label: 'Exposure Time', group: 'exposure', editKind: 'exposureTime' },
  { key: 'exif:829D', ifd: 'exif', tag: 0x829D, label: 'F Number', group: 'exposure', editKind: 'decimal' },
  { key: 'exif:8827', ifd: 'exif', tag: 0x8827, label: 'ISO Speed', group: 'exposure', editKind: 'int' },
  { key: 'exif:920A', ifd: 'exif', tag: 0x920A, label: 'Focal Length', group: 'exposure', editKind: 'decimal' },
  { key: 'exif:A434', ifd: 'exif', tag: 0xA434, label: 'Lens Model', group: 'camera', editKind: 'text' },
  { key: 'gps:coord', gpsKind: 'coord', label: 'GPS Coordinates', group: 'gps', editKind: 'gpsCoord' }
];
const ADDABLE_PNG_TEXT = ['Author', 'Description', 'Copyright', 'Software', 'Title', 'Comment'];

/* ===================================================================
   Small shared helpers
   =================================================================== */

let uidCounter = 0;
function nextUid() { return 'f' + (uidCounter++); }

function numFrom(v) { return typeof v === 'number' ? v : (Array.isArray(v) ? numFrom(v[0]) : 0); }
function numFromRational(r) { return r && r.den ? r.num / r.den : 0; }
function trimNum(v) { return Number.isInteger(v) ? String(v) : v.toFixed(2).replace(/0+$/, '').replace(/\.$/, ''); }
function pad2(n) { return String(Math.trunc(n)).padStart(2, '0'); }
function gcd(a, b) { a = Math.abs(a); b = Math.abs(b); while (b) { [a, b] = [b, a % b]; } return a || 1; }

function decimalToRational(x, scale) {
  scale = scale || 1000;
  if (!isFinite(x)) return { num: 0, den: 1 };
  const num0 = Math.round(x * scale);
  const g = gcd(num0, scale);
  return { num: num0 / g, den: scale / g };
}

function decimalToDMSRational(absDeg) {
  const deg = Math.floor(absDeg);
  const minFloat = (absDeg - deg) * 60;
  const min = Math.floor(minFloat);
  const secFloat = (minFloat - min) * 60;
  const sec = Math.round(secFloat * 100);
  return [{ num: deg, den: 1 }, { num: min, den: 1 }, { num: sec, den: 100 }];
}

function parseExposureTimeInput(str) {
  str = String(str).trim();
  if (!str) return null;
  if (str.includes('/')) {
    const [n, d] = str.split('/').map(s => parseFloat(s));
    if (!n || !d) return null;
    return { num: Math.round(n), den: Math.round(d) };
  }
  const v = parseFloat(str);
  if (!isFinite(v) || v <= 0) return null;
  if (v < 1) return { num: 1, den: Math.round(1 / v) };
  return decimalToRational(v);
}

function formatRationalDecimal(r) {
  if (!r || !r.den) return '0';
  const v = r.num / r.den;
  return Number.isInteger(v) ? String(v) : v.toFixed(3).replace(/0+$/, '').replace(/\.$/, '');
}

function formatBytes(bytes) {
  if (bytes < 1024) return bytes + ' B';
  const units = ['KB', 'MB', 'GB'];
  let v = bytes / 1024, i = 0;
  while (v >= 1024 && i < units.length - 1) { v /= 1024; i++; }
  return v.toFixed(v < 10 ? 2 : 1) + ' ' + units[i];
}

function escHtml(s) {
  return String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

/* ===================================================================
   EXIF/TIFF value formatters (for read-only display)
   =================================================================== */

function formatOrientation(e) { const n = numFrom(e.value); return ORIENTATION_MAP[n] || `Unknown (${n})`; }
function formatExposureTime(e) {
  const r = e.value; if (!r || !r.den) return null;
  const v = r.num / r.den;
  return v >= 1 ? `${trimNum(v)} s` : `1/${Math.round(r.den / r.num)} s`;
}
function formatFNumber(e) { const r = e.value; if (!r || !r.den) return null; return `f/${(r.num / r.den).toFixed(1)}`; }
function formatFocalLength(e) { const r = e.value; if (!r || !r.den) return null; return `${trimNum(r.num / r.den)} mm`; }
function formatMmSuffix(e) { return `${numFrom(e.value)} mm`; }
function formatShutterApex(e) {
  const r = e.value; if (!r || !r.den) return null;
  const seconds = Math.pow(2, -(r.num / r.den));
  if (!isFinite(seconds) || seconds <= 0) return null;
  return seconds >= 1 ? `${trimNum(seconds)} s` : `1/${Math.round(1 / seconds)} s`;
}
function formatApertureApex(e) {
  const r = e.value; if (!r || !r.den) return null;
  const fnum = Math.pow(2, (r.num / r.den) / 2);
  return isFinite(fnum) ? `f/${fnum.toFixed(1)}` : null;
}
function formatSignedRational(e) { const r = e.value; if (!r || !r.den) return '0 EV'; const v = r.num / r.den; return `${v > 0 ? '+' : ''}${trimNum(v)} EV`; }
function formatMeteringMode(e) { return METERING_MAP[numFrom(e.value)] || 'Unknown'; }
function formatFlash(e) { return (numFrom(e.value) & 0x1) ? 'Flash fired' : 'Flash did not fire'; }
function formatWhiteBalance(e) { return numFrom(e.value) === 0 ? 'Auto' : 'Manual'; }
function formatSceneCaptureType(e) { return SCENE_MAP[numFrom(e.value)] || 'Standard'; }
function formatExposureProgram(e) { return EXPOSURE_PROGRAM_MAP[numFrom(e.value)] || 'Unknown'; }
function formatResUnit(e) { const n = numFrom(e.value); return n === 2 ? 'inches' : n === 3 ? 'cm' : 'none'; }
function formatExifDate(e) { return typeof e.value === 'string' ? e.value.replace(':', '-').replace(':', '-') : String(e.value); }
function formatLensSpec(e) {
  const v = e.value; if (!Array.isArray(v) || v.length < 4) return null;
  const minF = formatRationalDecimal(v[0]), maxF = formatRationalDecimal(v[1]);
  const minA = v[2] && v[2].den ? (v[2].num / v[2].den).toFixed(1) : null;
  const maxA = v[3] && v[3].den ? (v[3].num / v[3].den).toFixed(1) : null;
  let s = minF === maxF ? `${minF}mm` : `${minF}-${maxF}mm`;
  if (minA && maxA) s += minA === maxA ? ` f/${minA}` : ` f/${minA}-${maxA}`;
  return s;
}
function formatUserComment(e) {
  const bytes = e.value;
  if (!Array.isArray(bytes) || bytes.length <= 8) return null;
  const prefix = String.fromCharCode(...bytes.slice(0, 5));
  const rest = bytes.slice(8).filter(b => b !== 0);
  if (!rest.length) return null;
  try {
    if (prefix === 'ASCII') return rest.map(b => String.fromCharCode(b)).join('');
    return new TextDecoder('utf-8').decode(new Uint8Array(rest));
  } catch (err) { return null; }
}

const FORMATTERS = {
  orientation: formatOrientation, exposureTime: formatExposureTime, fNumber: formatFNumber,
  focalLength: formatFocalLength, mmSuffix: formatMmSuffix, shutterApex: formatShutterApex,
  apertureApex: formatApertureApex, signedRational: formatSignedRational, meteringMode: formatMeteringMode,
  flash: formatFlash, whiteBalance: formatWhiteBalance, sceneCaptureType: formatSceneCaptureType,
  exposureProgram: formatExposureProgram, resUnit: formatResUnit, exifDate: formatExifDate,
  lensSpec: formatLensSpec, userComment: formatUserComment, rational: e => formatRationalDecimal(e.value)
};

function bytesToPrintableOrHex(bytes) {
  if (!Array.isArray(bytes) || !bytes.length) return '';
  const printable = bytes.every(b => (b >= 32 && b < 127) || b === 0);
  if (printable) {
    const s = bytes.map(b => String.fromCharCode(b)).join('').replace(/\0+$/, '');
    if (s) return s;
  }
  return `<${bytes.length} bytes>`;
}

function genericFormat(entry) {
  const { type, value } = entry;
  if (type === 2) return value;
  if (type === 5 || type === 10) return Array.isArray(value) ? value.map(formatRationalDecimal).join(', ') : formatRationalDecimal(value);
  if (type === 7) return bytesToPrintableOrHex(value);
  if (Array.isArray(value)) return value.join(', ');
  return String(value);
}

function formatEntryValue(entry, fmtKey) {
  if (fmtKey && FORMATTERS[fmtKey]) {
    try { const r = FORMATTERS[fmtKey](entry); if (r !== null && r !== undefined) return r; } catch (err) { /* fall through */ }
  }
  return genericFormat(entry);
}

/* ===================================================================
   TIFF / EXIF IFD reading
   =================================================================== */

function readIFDValue(view, offset, type, count, le) {
  switch (type) {
    case 2: {
      const bytes = [];
      for (let i = 0; i < count; i++) bytes.push(view.getUint8(offset + i));
      while (bytes.length && bytes[bytes.length - 1] === 0) bytes.pop();
      return new TextDecoder('utf-8', { fatal: false }).decode(new Uint8Array(bytes));
    }
    case 5: case 10: {
      const vals = [];
      for (let i = 0; i < count; i++) {
        const o = offset + i * 8;
        const num = type === 10 ? view.getInt32(o, le) : view.getUint32(o, le);
        const den = type === 10 ? view.getInt32(o + 4, le) : view.getUint32(o + 4, le);
        vals.push({ num, den });
      }
      return count === 1 ? vals[0] : vals;
    }
    case 3: case 8: {
      const vals = [];
      for (let i = 0; i < count; i++) vals.push(type === 8 ? view.getInt16(offset + i * 2, le) : view.getUint16(offset + i * 2, le));
      return count === 1 ? vals[0] : vals;
    }
    case 4: case 9: {
      const vals = [];
      for (let i = 0; i < count; i++) vals.push(type === 9 ? view.getInt32(offset + i * 4, le) : view.getUint32(offset + i * 4, le));
      return count === 1 ? vals[0] : vals;
    }
    case 11: { const vals = []; for (let i = 0; i < count; i++) vals.push(view.getFloat32(offset + i * 4, le)); return count === 1 ? vals[0] : vals; }
    case 12: { const vals = []; for (let i = 0; i < count; i++) vals.push(view.getFloat64(offset + i * 8, le)); return count === 1 ? vals[0] : vals; }
    default: { const vals = []; for (let i = 0; i < count; i++) vals.push(view.getUint8(offset + i)); return vals; }
  }
}

function readIFD(view, tiffStart, ifdOffset, le) {
  const entries = [];
  const map = {};
  if (ifdOffset < 0 || ifdOffset + 2 > view.byteLength) return { entries, map };
  const numEntries = Math.min(view.getUint16(ifdOffset, le), 400);
  for (let i = 0; i < numEntries; i++) {
    const entryOffset = ifdOffset + 2 + i * 12;
    if (entryOffset + 12 > view.byteLength) break;
    try {
      const tag = view.getUint16(entryOffset, le);
      const type = view.getUint16(entryOffset + 2, le);
      const count = view.getUint32(entryOffset + 4, le);
      const size = (TYPE_SIZES[type] || 1) * count;
      let valueOffset = entryOffset + 8;
      if (size > 4) valueOffset = tiffStart + view.getUint32(entryOffset + 8, le);
      if (valueOffset < 0 || valueOffset + size > view.byteLength || count > 50000) continue;
      const value = readIFDValue(view, valueOffset, type, count, le);
      const entry = { tag, type, count, value };
      entries.push(entry);
      map[tag] = entry;
    } catch (err) { /* skip malformed entry */ }
  }
  return { entries, map };
}

function dmsToDecimal(arr) {
  if (!Array.isArray(arr) || arr.length < 3) return NaN;
  const [d, m, s] = arr.map(numFromRational);
  return d + m / 60 + s / 3600;
}

/* ===================================================================
   TIFF / EXIF IFD writing
   =================================================================== */

function writeIFDValue(view, offset, type, count, value, le) {
  const arr = Array.isArray(value) ? value : [value];
  switch (type) {
    case 2: {
      const bytes = new TextEncoder().encode(typeof value === 'string' ? value : '');
      for (let i = 0; i < bytes.length; i++) view.setUint8(offset + i, bytes[i]);
      break;
    }
    case 5: case 10: {
      for (let i = 0; i < count; i++) {
        const r = arr[i] || { num: 0, den: 1 };
        const o = offset + i * 8;
        if (type === 10) { view.setInt32(o, r.num, le); view.setInt32(o + 4, r.den, le); }
        else { view.setUint32(o, r.num >>> 0, le); view.setUint32(o + 4, r.den >>> 0, le); }
      }
      break;
    }
    case 3: case 8: {
      for (let i = 0; i < count; i++) { const v = arr[i] || 0; if (type === 8) view.setInt16(offset + i * 2, v, le); else view.setUint16(offset + i * 2, v, le); }
      break;
    }
    case 4: case 9: {
      for (let i = 0; i < count; i++) { const v = arr[i] || 0; if (type === 9) view.setInt32(offset + i * 4, v, le); else view.setUint32(offset + i * 4, v >>> 0, le); }
      break;
    }
    case 11: { for (let i = 0; i < count; i++) view.setFloat32(offset + i * 4, arr[i] || 0, le); break; }
    case 12: { for (let i = 0; i < count; i++) view.setFloat64(offset + i * 8, arr[i] || 0, le); break; }
    default: { for (let i = 0; i < count; i++) view.setUint8(offset + i, arr[i] || 0); break; }
  }
}

// Serializes one IFD (table + overflow data) as a standalone byte block.
// `ifdAbsoluteStart` is this block's own offset relative to the TIFF header start —
// TIFF value offsets are always relative to the header, not to the IFD itself.
function serializeIFD(entries, ifdAbsoluteStart, le) {
  entries = entries.slice().sort((a, b) => a.tag - b.tag);
  const n = entries.length;
  const tableSize = 2 + n * 12 + 4;
  const sizes = entries.map(e => (TYPE_SIZES[e.type] || 1) * e.count);
  const padded = sizes.map(sz => sz + (sz % 2));
  let offset = tableSize;
  const overflowOffsets = [];
  for (let i = 0; i < n; i++) {
    if (sizes[i] > 4) { overflowOffsets[i] = offset; offset += padded[i]; }
    else overflowOffsets[i] = -1;
  }
  const buf = new Uint8Array(offset);
  const view = new DataView(buf.buffer);
  view.setUint16(0, n, le);
  for (let i = 0; i < n; i++) {
    const e = entries[i];
    const entryOff = 2 + i * 12;
    view.setUint16(entryOff, e.tag, le);
    view.setUint16(entryOff + 2, e.type, le);
    view.setUint32(entryOff + 4, e.count, le);
    if (overflowOffsets[i] === -1) {
      writeIFDValue(view, entryOff + 8, e.type, e.count, e.value, le);
    } else {
      view.setUint32(entryOff + 8, ifdAbsoluteStart + overflowOffsets[i], le);
      writeIFDValue(view, overflowOffsets[i], e.type, e.count, e.value, le);
    }
  }
  view.setUint32(2 + n * 12, 0, le); // next-IFD offset
  return buf;
}

function sizeOfIFD(entries) {
  let overflow = 0;
  for (const e of entries) { const sz = (TYPE_SIZES[e.type] || 1) * e.count; if (sz > 4) overflow += sz + (sz % 2); }
  return 2 + entries.length * 12 + 4 + overflow;
}

// Builds a complete little-endian TIFF blob (header + IFD0 [+ Exif sub-IFD] [+ GPS sub-IFD]) from scratch.
function buildTiffBytes(groups) {
  const le = true;
  const hasExif = groups.exifEntries.length > 0;
  const hasGps = groups.gpsEntries.length > 0;
  const ifd0Full = groups.ifd0Entries.concat(
    hasExif ? [{ tag: 0x8769, type: 4, count: 1, value: 0 }] : [],
    hasGps ? [{ tag: 0x8825, type: 4, count: 1, value: 0 }] : []
  );
  if (!ifd0Full.length) return null;
  const S0 = sizeOfIFD(ifd0Full);
  const S1 = hasExif ? sizeOfIFD(groups.exifEntries) : 0;
  const ifd0Start = 8;
  const exifStart = ifd0Start + S0;
  const gpsStart = exifStart + S1;
  for (const e of ifd0Full) {
    if (e.tag === 0x8769) e.value = exifStart;
    if (e.tag === 0x8825) e.value = gpsStart;
  }
  const S2 = hasGps ? sizeOfIFD(groups.gpsEntries) : 0;
  const totalSize = gpsStart + S2;
  const out = new Uint8Array(totalSize);
  out[0] = 0x49; out[1] = 0x49; // "II"
  const view = new DataView(out.buffer);
  view.setUint16(2, 42, le);
  view.setUint32(4, ifd0Start, le);
  out.set(serializeIFD(ifd0Full, ifd0Start, le), ifd0Start);
  if (hasExif) out.set(serializeIFD(groups.exifEntries, exifStart, le), exifStart);
  if (hasGps) out.set(serializeIFD(groups.gpsEntries, gpsStart, le), gpsStart);
  return out;
}

/* ===================================================================
   Field model: read/write EXIF (JPEG) & metadata chunks (PNG)
   =================================================================== */

function computeDisplay(f) {
  if (f.editKind) return null;
  if (f.displayValueStatic !== undefined) return f.displayValueStatic;
  return formatEntryValue({ type: f.rawType, value: f.rawValue }, f.fmtKey);
}

function initialEditValue(rawType, rawValue, kind) {
  const entry = { type: rawType, value: rawValue };
  switch (kind) {
    case 'text': return typeof rawValue === 'string' ? rawValue : genericFormat(entry);
    case 'date': { const s = typeof rawValue === 'string' ? rawValue : ''; return s.replace(':', '-').replace(':', '-'); }
    case 'decimal': { const r = rawValue; return r && r.den ? +(r.num / r.den).toFixed(3) : 0; }
    case 'exposureTime': { const r = rawValue; if (!r || !r.den) return ''; const v = r.num / r.den; return v >= 1 ? String(+v.toFixed(2)) : `1/${Math.round(r.den / r.num)}`; }
    case 'int': return numFrom(rawValue);
    case 'select': return numFrom(rawValue);
    default: return null;
  }
}

function makeExifModelEntry(entry, dict, ifd, editable) {
  const def = dict[entry.tag];
  const label = def ? def.label : `Tag 0x${entry.tag.toString(16).padStart(4, '0').toUpperCase()}`;
  const group = def ? def.group : 'other';
  const sensitive = !!(def && def.sensitive);
  const edit = editable && def && def.edit;
  return {
    key: nextUid(), label, group, sensitive, deleted: false,
    ifd, tag: entry.tag, rawType: entry.type, rawCount: entry.count, rawValue: entry.value,
    fmtKey: def && def.fmt,
    editKind: edit ? edit.kind : null,
    selectOptions: edit && edit.options,
    editValue: edit ? initialEditValue(entry.type, entry.value, edit.kind) : null
  };
}

function makeGpsModelEntries(gpsMap, editable) {
  const out = [];
  const val = t => gpsMap[t] && gpsMap[t].value;
  const latRef = val(1), lat = val(2), lonRef = val(3), lon = val(4);
  if (lat && lon) {
    const latDec = dmsToDecimal(lat) * (String(latRef).toUpperCase().startsWith('S') ? -1 : 1);
    const lonDec = dmsToDecimal(lon) * (String(lonRef).toUpperCase().startsWith('W') ? -1 : 1);
    if (isFinite(latDec) && isFinite(lonDec)) {
      out.push({
        key: nextUid(), label: 'GPS Coordinates', group: 'gps', sensitive: true, deleted: false,
        gpsKind: 'coord', editKind: editable ? 'gpsCoord' : null,
        editValue: { lat: +latDec.toFixed(6), lon: +lonDec.toFixed(6) },
        displayValueStatic: `${latDec.toFixed(6)}, ${lonDec.toFixed(6)}`
      });
    }
  }
  const altRef = val(5), alt = val(6);
  if (alt) {
    const altVal = numFromRational(alt) * (numFrom(altRef) === 1 ? -1 : 1);
    out.push({
      key: nextUid(), label: 'GPS Altitude', group: 'gps', sensitive: true, deleted: false,
      gpsKind: 'altitude', editKind: editable ? 'decimal' : null,
      editValue: +altVal.toFixed(2), displayValueStatic: `${altVal.toFixed(2)} m`
    });
  }
  const dateStamp = val(0x1D), timeStamp = val(7);
  if (dateStamp || timeStamp) {
    let s = dateStamp ? String(dateStamp).replace(/:/g, '-') : '';
    if (Array.isArray(timeStamp)) { const [h, m, sec] = timeStamp.map(numFromRational); s += (s ? ' ' : '') + `${pad2(h)}:${pad2(m)}:${pad2(Math.round(sec))} UTC`; }
    out.push({
      key: nextUid(), label: 'GPS Timestamp', group: 'gps', sensitive: true, deleted: false,
      gpsKind: 'timestamp', editKind: null,
      rawTimestampEntries: [gpsMap[0x1D], gpsMap[7]].filter(Boolean),
      displayValueStatic: s
    });
  }
  return out;
}

function gpsLinkUrl(lat, lon) {
  return `https://www.openstreetmap.org/?mlat=${lat.toFixed(6)}&mlon=${lon.toFixed(6)}#map=16/${lat.toFixed(6)}/${lon.toFixed(6)}`;
}

function buildJpegStyleFieldsFromTiff(view, tiffStart, range, editable) {
  const model = [];
  if (tiffStart + 8 > view.byteLength) return model;
  const bom = view.getUint16(tiffStart);
  let le;
  if (bom === 0x4949) le = true; else if (bom === 0x4D4D) le = false; else return model;
  if (view.getUint16(tiffStart + 2, le) !== 42) return model;
  const ifd0Offset = view.getUint32(tiffStart + 4, le);
  try {
    const ifd0 = readIFD(view, tiffStart, tiffStart + ifd0Offset, le);
    for (const entry of ifd0.entries) {
      if (entry.tag === 0x8769 || entry.tag === 0x8825) continue;
      const m = makeExifModelEntry(entry, IFD0_TAGS, 'ifd0', editable);
      if (range) m.pngChunkRange = range;
      model.push(m);
    }
    if (ifd0.map[0x8769]) {
      const exifOffset = tiffStart + numFrom(ifd0.map[0x8769].value);
      const exifIfd = readIFD(view, tiffStart, exifOffset, le);
      for (const entry of exifIfd.entries) {
        const m = makeExifModelEntry(entry, EXIF_TAGS, 'exif', editable);
        if (range) m.pngChunkRange = range;
        model.push(m);
      }
    }
    if (ifd0.map[0x8825]) {
      const gpsOffset = tiffStart + numFrom(ifd0.map[0x8825].value);
      const gpsIfd = readIFD(view, tiffStart, gpsOffset, le);
      const gpsFields = makeGpsModelEntries(gpsIfd.map, editable);
      for (const f of gpsFields) if (range) f.pngChunkRange = range;
      model.push(...gpsFields);
    }
  } catch (err) { /* return whatever we managed to parse */ }
  return model;
}

function encodeEditedValue(f) {
  switch (f.editKind) {
    case 'text': {
      if (f.tag === 0x9286) { // UserComment: 8-byte charset prefix + text, type UNDEFINED
        const asciiPrefix = [0x41, 0x53, 0x43, 0x49, 0x49, 0, 0, 0];
        const textBytes = Array.from(new TextEncoder().encode(String(f.editValue)));
        return { type: 7, count: asciiPrefix.length + textBytes.length, value: asciiPrefix.concat(textBytes) };
      }
      const str = String(f.editValue);
      return { type: 2, count: new TextEncoder().encode(str).length + 1, value: str };
    }
    case 'date': {
      const str = String(f.editValue).trim().replace(/-/g, ':');
      return { type: 2, count: new TextEncoder().encode(str).length + 1, value: str };
    }
    case 'decimal': return { type: 5, count: 1, value: decimalToRational(parseFloat(f.editValue) || 0) };
    case 'exposureTime': return { type: 5, count: 1, value: parseExposureTimeInput(f.editValue) || { num: 0, den: 1 } };
    case 'int': return { type: f.rawType || 3, count: 1, value: Math.round(parseFloat(f.editValue) || 0) };
    case 'select': return { type: f.rawType || 3, count: 1, value: Math.round(parseFloat(f.editValue) || 1) };
    default: return { type: f.rawType, count: f.rawCount, value: f.rawValue };
  }
}

function buildGpsCoordRawEntries(lat, lon) {
  const latRef = lat >= 0 ? 'N' : 'S';
  const lonRef = lon >= 0 ? 'E' : 'W';
  return [
    { tag: 1, type: 2, count: 2, value: latRef },
    { tag: 2, type: 5, count: 3, value: decimalToDMSRational(Math.abs(lat)) },
    { tag: 3, type: 2, count: 2, value: lonRef },
    { tag: 4, type: 5, count: 3, value: decimalToDMSRational(Math.abs(lon)) }
  ];
}
function buildGpsAltitudeRawEntries(alt) {
  return [
    { tag: 5, type: 1, count: 1, value: [alt < 0 ? 1 : 0] },
    { tag: 6, type: 5, count: 1, value: decimalToRational(Math.abs(alt), 100) }
  ];
}

function collectJpegRawGroups(fieldModel) {
  const ifd0Entries = [], exifEntries = [], gpsEntries = [];
  for (const f of fieldModel) {
    if (f.deleted || f.jpegAux) continue;
    if (f.group === 'gps') {
      if (f.gpsKind === 'coord') gpsEntries.push(...buildGpsCoordRawEntries(f.editValue.lat, f.editValue.lon));
      else if (f.gpsKind === 'altitude') gpsEntries.push(...buildGpsAltitudeRawEntries(f.editValue));
      else if (f.gpsKind === 'timestamp') for (const e of f.rawTimestampEntries) gpsEntries.push(e);
      continue;
    }
    const raw = f.editKind ? encodeEditedValue(f) : { type: f.rawType, count: f.rawCount, value: f.rawValue };
    const target = f.ifd === 'exif' ? exifEntries : ifd0Entries;
    target.push({ tag: f.tag, type: raw.type, count: raw.count, value: raw.value });
  }
  if (gpsEntries.length && !gpsEntries.some(e => e.tag === 0)) gpsEntries.unshift({ tag: 0, type: 1, count: 4, value: [2, 3, 0, 0] });
  return { ifd0Entries, exifEntries, gpsEntries };
}

/* ===================================================================
   JPEG: segment walking, metadata extraction, stripping & editing
   =================================================================== */

function matchAscii(view, offset, str) {
  if (offset + str.length > view.byteLength) return false;
  for (let i = 0; i < str.length; i++) if (view.getUint8(offset + i) !== str.charCodeAt(i)) return false;
  return true;
}

function classifyJpegSegment(view, marker, start, end) {
  const seg = { marker, start, end, kind: 'other' };
  const dataStart = start + 4;
  if (marker === 0xE1) {
    if (matchAscii(view, dataStart, 'Exif\0\0')) { seg.kind = 'exif'; seg.tiffStart = dataStart + 6; }
    else if (matchAscii(view, dataStart, 'http://ns.adobe.com')) seg.kind = 'xmp';
  } else if (marker === 0xED && matchAscii(view, dataStart, 'Photoshop 3.0')) {
    seg.kind = 'iptc';
  } else if (marker === 0xE2) {
    seg.kind = 'icc';
  } else if (marker === 0xE0) {
    seg.kind = 'jfif';
  } else if (marker === 0xFE) {
    seg.kind = 'comment';
  } else if (marker >= 0xE3 && marker <= 0xEF) {
    seg.kind = 'app';
  }
  return seg;
}

function parseJPEGSegments(buffer) {
  const view = new DataView(buffer);
  if (view.byteLength < 4 || view.getUint16(0) !== 0xFFD8) throw new Error('Not a valid JPEG (missing SOI marker).');
  const segments = [];
  let offset = 2;
  while (offset + 1 < view.byteLength) {
    if (view.getUint8(offset) !== 0xFF) break;
    let marker = view.getUint8(offset + 1);
    while (marker === 0xFF && offset + 2 < view.byteLength) { offset++; marker = view.getUint8(offset + 1); }
    if (marker === 0x01 || (marker >= 0xD0 && marker <= 0xD7)) { offset += 2; continue; }
    if (marker === 0xD9) { offset += 2; break; }
    if (marker === 0xDA) { segments.push({ marker, start: offset, end: view.byteLength, kind: 'scan' }); offset = view.byteLength; break; }
    if (offset + 4 > view.byteLength) break;
    const segLen = view.getUint16(offset + 2);
    const start = offset, end = offset + 2 + segLen;
    if (end > view.byteLength) break;
    segments.push(classifyJpegSegment(view, marker, start, end));
    offset = end;
  }
  if (!segments.length || segments[segments.length - 1].kind !== 'scan') {
    if (offset < view.byteLength) segments.push({ marker: null, start: offset, end: view.byteLength, kind: 'raw' });
  }
  return segments;
}

function extractJpegFieldModel(buffer) {
  const view = new DataView(buffer);
  const segments = parseJPEGSegments(buffer);
  const exifSeg = segments.find(s => s.kind === 'exif');
  const model = exifSeg ? buildJpegStyleFieldsFromTiff(view, exifSeg.tiffStart, null, true) : [];
  if (segments.some(s => s.kind === 'xmp')) model.push({ key: nextUid(), label: 'XMP Metadata', group: 'other', sensitive: false, deleted: false, editKind: null, displayValueStatic: 'Present (Adobe XMP block found)', jpegAux: 'xmp' });
  if (segments.some(s => s.kind === 'iptc')) model.push({ key: nextUid(), label: 'IPTC Metadata', group: 'other', sensitive: false, deleted: false, editKind: null, displayValueStatic: 'Present (Photoshop IPTC block found)', jpegAux: 'iptc' });
  segments.filter(s => s.kind === 'comment').forEach(s => {
    const text = new TextDecoder('utf-8', { fatal: false }).decode(new Uint8Array(buffer, s.start + 4, s.end - s.start - 4));
    if (text) model.push({ key: nextUid(), label: 'JPEG Comment', group: 'software', sensitive: false, deleted: false, editKind: null, displayValueStatic: text, jpegAux: 'comment' });
  });
  return model;
}

function stripJPEG(buffer, options) {
  const segments = parseJPEGSegments(buffer);
  const strippableKinds = new Set(['exif', 'xmp', 'iptc', 'comment', 'app']);
  const parts = [buffer.slice(0, 2)];
  for (const seg of segments) {
    const strip = strippableKinds.has(seg.kind) || (seg.kind === 'icc' && !options.keepIcc);
    if (!strip) parts.push(buffer.slice(seg.start, seg.end));
  }
  return new Blob(parts, { type: 'image/jpeg' });
}

function buildApp1ExifSegmentBytes(tiffBytes) {
  const prefix = new TextEncoder().encode('Exif\0\0');
  const payloadLen = prefix.length + tiffBytes.length;
  const segLen = payloadLen + 2;
  if (segLen > 0xFFFF) throw new Error('Metadata is too large for a single JPEG segment (max ~64KB).');
  const out = new Uint8Array(4 + payloadLen);
  out[0] = 0xFF; out[1] = 0xE1;
  out[2] = (segLen >> 8) & 0xFF; out[3] = segLen & 0xFF;
  out.set(prefix, 4);
  out.set(tiffBytes, 4 + prefix.length);
  return out;
}

function rebuildJpegSegments(buffer, { tiffBytes, removeKinds }) {
  const segments = parseJPEGSegments(buffer);
  const parts = [buffer.slice(0, 2)];
  let insertedExif = false;
  for (const seg of segments) {
    if (seg.kind === 'exif') {
      if (tiffBytes) { parts.push(buildApp1ExifSegmentBytes(tiffBytes)); insertedExif = true; }
      continue;
    }
    if (removeKinds.has(seg.kind)) continue;
    parts.push(buffer.slice(seg.start, seg.end));
  }
  if (tiffBytes && !insertedExif) parts.splice(1, 0, buildApp1ExifSegmentBytes(tiffBytes));
  return new Blob(parts, { type: 'image/jpeg' });
}

async function applyJpegEdits(item) {
  const { ifd0Entries, exifEntries, gpsEntries } = collectJpegRawGroups(item.fieldModel);
  const hasAny = ifd0Entries.length || exifEntries.length || gpsEntries.length;
  const tiffBytes = hasAny ? buildTiffBytes({ ifd0Entries, exifEntries, gpsEntries }) : null;
  const removeKinds = new Set(item.fieldModel.filter(f => f.jpegAux && f.deleted).map(f => f.jpegAux));
  const blob = rebuildJpegSegments(item.workingBuffer, { tiffBytes, removeKinds });
  item.workingBuffer = await blob.arrayBuffer();
}

/* ===================================================================
   PNG: chunk walking, metadata extraction, stripping & editing
   =================================================================== */

const PNG_SIGNATURE = [0x89, 0x50, 0x4E, 0x47, 0x0D, 0x0A, 0x1A, 0x0A];
const PNG_METADATA_CHUNKS = new Set(['tEXt', 'zTXt', 'iTXt', 'eXIf', 'tIME']);

let crcTable = null;
function getCrcTable() {
  if (crcTable) return crcTable;
  const table = new Uint32Array(256);
  for (let n = 0; n < 256; n++) {
    let c = n;
    for (let k = 0; k < 8; k++) c = (c & 1) ? (0xEDB88320 ^ (c >>> 1)) : (c >>> 1);
    table[n] = c >>> 0;
  }
  crcTable = table;
  return table;
}
function crc32(bytes) {
  const table = getCrcTable();
  let c = 0xFFFFFFFF;
  for (let i = 0; i < bytes.length; i++) c = table[(c ^ bytes[i]) & 0xFF] ^ (c >>> 8);
  return (c ^ 0xFFFFFFFF) >>> 0;
}

function buildPngChunk(type, dataBytes) {
  const typeBytes = new TextEncoder().encode(type);
  const len = dataBytes.length;
  const out = new Uint8Array(4 + 4 + len + 4);
  const view = new DataView(out.buffer);
  view.setUint32(0, len, false);
  out.set(typeBytes, 4);
  out.set(dataBytes, 8);
  view.setUint32(8 + len, crc32(out.subarray(4, 8 + len)), false);
  return out;
}

function buildITxtChunkBytes(keyword, text) {
  const enc = new TextEncoder();
  const keywordBytes = enc.encode(keyword);
  const textBytes = enc.encode(text);
  const data = new Uint8Array(keywordBytes.length + 5 + textBytes.length);
  let o = 0;
  data.set(keywordBytes, o); o += keywordBytes.length;
  data[o++] = 0; // null after keyword
  data[o++] = 0; // compression flag (uncompressed)
  data[o++] = 0; // compression method
  data[o++] = 0; // empty language tag + null
  data[o++] = 0; // empty translated keyword + null
  data.set(textBytes, o);
  return buildPngChunk('iTXt', data);
}

function buildTimeChunkBytes(d) {
  const data = new Uint8Array(7);
  const view = new DataView(data.buffer);
  view.setUint16(0, d.year, false);
  data[2] = d.month; data[3] = d.day; data[4] = d.hour; data[5] = d.min; data[6] = d.sec;
  return buildPngChunk('tIME', data);
}

function parsePngDateInput(str) {
  const m = String(str).trim().match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2}):(\d{2})/);
  if (!m) return { year: 1970, month: 1, day: 1, hour: 0, min: 0, sec: 0 };
  return { year: +m[1], month: +m[2], day: +m[3], hour: +m[4], min: +m[5], sec: +m[6] };
}

function buildPngFieldBytes(f) {
  if (f.pngKind === 'text') return buildITxtChunkBytes(f.pngKeyword, String(f.editValue));
  if (f.pngKind === 'time') return buildTimeChunkBytes(parsePngDateInput(f.editValue));
  return null;
}

function parsePNGChunks(buffer) {
  const view = new DataView(buffer);
  for (let i = 0; i < 8; i++) if (view.getUint8(i) !== PNG_SIGNATURE[i]) throw new Error('Not a valid PNG (bad signature).');
  const chunks = [];
  let offset = 8;
  while (offset + 8 <= view.byteLength) {
    const len = view.getUint32(offset);
    const type = String.fromCharCode(view.getUint8(offset + 4), view.getUint8(offset + 5), view.getUint8(offset + 6), view.getUint8(offset + 7));
    const dataStart = offset + 8, dataEnd = dataStart + len, chunkEnd = dataEnd + 4;
    if (chunkEnd > view.byteLength) break;
    chunks.push({ type, start: offset, end: chunkEnd, dataStart, dataEnd });
    offset = chunkEnd;
    if (type === 'IEND') break;
  }
  return chunks;
}

function latin1Decode(view, start, end) {
  let s = '';
  for (let i = start; i < end; i++) s += String.fromCharCode(view.getUint8(i));
  return s;
}
function findNull(view, start, end) {
  for (let i = start; i < end; i++) if (view.getUint8(i) === 0) return i;
  return -1;
}

function makePngTextModelEntry(keyword, text, range, compressed) {
  const label = PNG_TEXT_LABELS[keyword] || keyword || 'Text';
  const sensitive = /author|artist|owner|location|gps/i.test(keyword || '');
  if (compressed) {
    return { key: nextUid(), label, group: 'software', sensitive, deleted: false, pngKind: 'text', pngKeyword: keyword, pngChunkRange: range, editKind: null, displayValueStatic: '(compressed — value hidden, will still be removed)' };
  }
  return { key: nextUid(), label, group: 'software', sensitive, deleted: false, pngKind: 'text', pngKeyword: keyword, pngChunkRange: range, editKind: 'text', editValue: text };
}

function extractPngFieldModel(buffer) {
  const view = new DataView(buffer);
  const chunks = parsePNGChunks(buffer);
  const model = [];
  for (const c of chunks) {
    try {
      const range = { start: c.start, end: c.end };
      if (c.type === 'tEXt') {
        const nullIdx = findNull(view, c.dataStart, c.dataEnd);
        if (nullIdx === -1) continue;
        const keyword = latin1Decode(view, c.dataStart, nullIdx);
        const text = latin1Decode(view, nullIdx + 1, c.dataEnd);
        model.push(makePngTextModelEntry(keyword, text, range, false));
      } else if (c.type === 'zTXt') {
        const nullIdx = findNull(view, c.dataStart, c.dataEnd);
        if (nullIdx === -1) continue;
        const keyword = latin1Decode(view, c.dataStart, nullIdx);
        model.push(makePngTextModelEntry(keyword, null, range, true));
      } else if (c.type === 'iTXt') {
        let i = c.dataStart;
        const n1 = findNull(view, i, c.dataEnd); if (n1 === -1) continue;
        const keyword = latin1Decode(view, i, n1);
        i = n1 + 1;
        const compressionFlag = view.getUint8(i);
        i += 2;
        const n2 = findNull(view, i, c.dataEnd); if (n2 === -1) continue;
        i = n2 + 1;
        const n3 = findNull(view, i, c.dataEnd); if (n3 === -1) continue;
        i = n3 + 1;
        if (compressionFlag === 0) {
          const text = new TextDecoder('utf-8', { fatal: false }).decode(new Uint8Array(buffer, i, c.dataEnd - i));
          model.push(makePngTextModelEntry(keyword, text, range, false));
        } else {
          model.push(makePngTextModelEntry(keyword, null, range, true));
        }
      } else if (c.type === 'tIME' && c.dataEnd - c.dataStart >= 7) {
        const year = view.getUint16(c.dataStart);
        const month = view.getUint8(c.dataStart + 2), day = view.getUint8(c.dataStart + 3);
        const hour = view.getUint8(c.dataStart + 4), min = view.getUint8(c.dataStart + 5), sec = view.getUint8(c.dataStart + 6);
        const display = `${year}-${pad2(month)}-${pad2(day)} ${pad2(hour)}:${pad2(min)}:${pad2(sec)}`;
        model.push({ key: nextUid(), label: 'Last Modified', group: 'datetime', sensitive: false, deleted: false, pngKind: 'time', pngChunkRange: range, editKind: 'date', editValue: display });
      } else if (c.type === 'eXIf') {
        model.push(...buildJpegStyleFieldsFromTiff(view, c.dataStart, range, false));
      }
    } catch (err) { /* skip malformed chunk */ }
  }
  return model;
}

function stripPNG(buffer, options) {
  const chunks = parsePNGChunks(buffer);
  const parts = [buffer.slice(0, 8)];
  for (const c of chunks) {
    const strip = PNG_METADATA_CHUNKS.has(c.type) || (c.type === 'iCCP' && !options.keepIcc);
    if (!strip) parts.push(buffer.slice(c.start, c.end));
  }
  return new Blob(parts, { type: 'image/png' });
}

function editPngMetadata(buffer, fieldModel) {
  const chunks = parsePNGChunks(buffer);
  const parts = [buffer.slice(0, 8)];
  const ihdrIndex = chunks.findIndex(c => c.type === 'IHDR');
  const newFields = fieldModel.filter(f => !f.pngChunkRange && !f.deleted && f.pngKind);
  for (let i = 0; i < chunks.length; i++) {
    const c = chunks[i];
    const related = fieldModel.filter(f => f.pngChunkRange && f.pngChunkRange.start === c.start && f.pngChunkRange.end === c.end);
    if (related.length) {
      const allDeleted = related.every(f => f.deleted);
      const editableOne = related.find(f => f.editKind && !f.deleted);
      if (allDeleted) { /* omit entirely */ }
      else if (editableOne) parts.push(buildPngFieldBytes(editableOne));
      else parts.push(buffer.slice(c.start, c.end));
    } else {
      parts.push(buffer.slice(c.start, c.end));
    }
    if (i === ihdrIndex) for (const nf of newFields) parts.push(buildPngFieldBytes(nf));
  }
  return new Blob(parts, { type: 'image/png' });
}

async function applyPngEdits(item) {
  const blob = editPngMetadata(item.workingBuffer, item.fieldModel);
  item.workingBuffer = await blob.arrayBuffer();
}

/* ===================================================================
   Format detection & dispatch
   =================================================================== */

function detectFormat(buffer) {
  const view = new DataView(buffer);
  if (buffer.byteLength >= 2 && view.getUint16(0) === 0xFFD8) return 'jpeg';
  if (buffer.byteLength >= 8 && view.getUint8(0) === 0x89 && view.getUint8(1) === 0x50 && view.getUint8(2) === 0x4E && view.getUint8(3) === 0x47) return 'png';
  return null;
}
function extractFieldModel(format, buffer) { return format === 'jpeg' ? extractJpegFieldModel(buffer) : extractPngFieldModel(buffer); }
function stripMetadata(format, buffer, options) { return format === 'jpeg' ? stripJPEG(buffer, options) : stripPNG(buffer, options); }
async function applyEdits(item) { return item.format === 'jpeg' ? applyJpegEdits(item) : applyPngEdits(item); }

function visibleFields(item) { return item.fieldModel.filter(f => !f.deleted); }

function getAddableOptions(item) {
  const live = visibleFields(item);
  if (item.format === 'jpeg') {
    return ADDABLE_JPEG_TAGS.filter(o => o.gpsKind ? !live.some(f => f.gpsKind === o.gpsKind) : !live.some(f => f.ifd === o.ifd && f.tag === o.tag));
  }
  return ADDABLE_PNG_TEXT.filter(kw => !live.some(f => f.pngKind === 'text' && f.pngKeyword === kw));
}

function defaultTypeFor(kind) { return { text: 2, date: 2, decimal: 5, exposureTime: 5, int: 3, select: 3 }[kind] || 2; }
function defaultEditValue(kind) { return { text: '', date: '', decimal: 0, exposureTime: '', int: 0, select: 1 }[kind]; }

function addFieldToModel(item, key) {
  const opt = getAddableOptions(item).find(o => (o.key || o) === key);
  if (!opt) return;
  if (item.format === 'jpeg') {
    if (opt.gpsKind === 'coord') {
      item.fieldModel.push({ key: nextUid(), label: opt.label, group: 'gps', sensitive: true, deleted: false, gpsKind: 'coord', editKind: 'gpsCoord', editValue: { lat: 0, lon: 0 } });
    } else {
      item.fieldModel.push({
        key: nextUid(), label: opt.label, group: opt.group, sensitive: !!opt.sensitive, deleted: false,
        ifd: opt.ifd, tag: opt.tag, rawType: defaultTypeFor(opt.editKind), rawCount: 1, rawValue: null,
        editKind: opt.editKind, editValue: defaultEditValue(opt.editKind)
      });
    }
  } else {
    item.fieldModel.push({
      key: nextUid(), label: PNG_TEXT_LABELS[opt] || opt, group: 'software', sensitive: /author/i.test(opt), deleted: false,
      pngKind: 'text', pngKeyword: opt, pngChunkRange: null, editKind: 'text', editValue: ''
    });
  }
}

function deleteFieldFromModel(item, key) {
  const f = item.fieldModel.find(x => x.key === key);
  if (!f) return;
  if (f.pngChunkRange) {
    for (const g of item.fieldModel) if (g.pngChunkRange && g.pngChunkRange.start === f.pngChunkRange.start && g.pngChunkRange.end === f.pngChunkRange.end) g.deleted = true;
  } else {
    f.deleted = true;
  }
}

/* ===================================================================
   UI
   =================================================================== */

const dropzone = document.getElementById('dropzone');
const fileInput = document.getElementById('fileInput');
const grid = document.getElementById('grid');
const emptyState = document.getElementById('emptyState');
const keepIccCheckbox = document.getElementById('keepIcc');
const downloadAllBtn = document.getElementById('downloadAllBtn');
const clearBtn = document.getElementById('clearBtn');
const modalOverlay = document.getElementById('modalOverlay');
const modalName = document.getElementById('modalName');
const modalBody = document.getElementById('modalBody');
const modalClose = document.getElementById('modalClose');
const modalCancel = document.getElementById('modalCancel');
const modalSaveBtn = document.getElementById('modalSave');

let images = [];
let idCounter = 0;
let currentModalItem = null;

function triggerDownload(url, filename) {
  const a = document.createElement('a');
  a.href = url;
  a.download = filename;
  document.body.appendChild(a);
  a.click();
  a.remove();
}

function updateWorkingUrl(item) {
  if (item.workingUrl) URL.revokeObjectURL(item.workingUrl);
  const blob = new Blob([item.workingBuffer], { type: item.format === 'jpeg' ? 'image/jpeg' : 'image/png' });
  item.workingUrl = URL.createObjectURL(blob);
  item.workingSize = blob.size;
}

function cardHTML(item) {
  if (item.error) {
    return `
      <div class="card card-error" data-id="${item.id}">
        <button class="card-remove" data-action="remove" data-id="${item.id}" title="Remove">✕</button>
        <img class="thumb" src="${item.url}" alt="">
        <div class="card-info">
          <div class="card-name" title="${escHtml(item.name)}">${escHtml(item.name)}</div>
          <div class="card-error-msg">${escHtml(item.error)}</div>
        </div>
      </div>`;
  }
  if (!item.format) {
    return `
      <div class="card card-loading" data-id="${item.id}">
        <img class="thumb" src="${item.url}" alt="">
        <div class="card-info">
          <div class="card-name">${escHtml(item.name)}</div>
          <div class="card-meta">Reading…</div>
        </div>
      </div>`;
  }
  const fields = visibleFields(item);
  const badgeCount = fields.length;
  const sensitiveCount = fields.filter(f => f.sensitive).length;
  const sensitiveBadge = sensitiveCount > 0 ? `<span class="badge sensitive">🔒 ${sensitiveCount} sensitive</span>` : '';
  const modifiedInfo = item.modified
    ? `<div class="stripped-info">Current: ${formatBytes(item.workingSize)} <a href="${item.workingUrl}" download="edited-${escHtml(item.name)}" class="dl-link">Download ↓</a></div>`
    : '';
  return `
    <div class="card" data-id="${item.id}">
      <button class="card-remove" data-action="remove" data-id="${item.id}" title="Remove">✕</button>
      <img class="thumb" src="${item.url}" alt="">
      <div class="card-info">
        <div class="card-name" title="${escHtml(item.name)}">${escHtml(item.name)}</div>
        <div class="card-meta">${item.format.toUpperCase()} · ${formatBytes(item.size)}</div>
        <div class="card-badges">
          <span class="badge">${badgeCount} field${badgeCount === 1 ? '' : 's'}</span>
          ${sensitiveBadge}
        </div>
        ${modifiedInfo}
        <div class="card-actions">
          <button class="small secondary" data-action="view" data-id="${item.id}">View &amp; edit</button>
          <button class="small" data-action="strip" data-id="${item.id}">Strip &amp; download</button>
        </div>
      </div>
    </div>`;
}

function renderAll() {
  emptyState.hidden = images.length > 0;
  grid.innerHTML = images.map(cardHTML).join('');
  downloadAllBtn.disabled = images.length === 0;
  clearBtn.disabled = images.length === 0;
}

function fieldRowHTML(f) {
  const delBtn = `<button class="field-delete" type="button" data-action="delete-field" data-key="${f.key}" title="Remove field">✕</button>`;
  const lockIcon = f.sensitive ? ' <span class="lock" title="Privacy-sensitive">🔒</span>' : '';
  let valueHtml;
  if (f.editKind === 'gpsCoord') {
    const lat = f.editValue.lat, lon = f.editValue.lon;
    valueHtml = `
      <div class="gps-coord-inputs">
        <input class="field-input" type="number" step="any" data-key="${f.key}" data-sub="lat" value="${lat}">
        <input class="field-input" type="number" step="any" data-key="${f.key}" data-sub="lon" value="${lon}">
      </div>
      <a href="${gpsLinkUrl(lat, lon)}" target="_blank" rel="noopener" class="gps-link" id="gpslink-${f.key}">Open in OpenStreetMap ↗</a>`;
  } else if (f.editKind === 'select') {
    const opts = Object.entries(f.selectOptions || {}).map(([v, label]) => `<option value="${v}" ${Number(v) === Number(f.editValue) ? 'selected' : ''}>${escHtml(label)}</option>`).join('');
    valueHtml = `<select class="field-input" data-key="${f.key}">${opts}</select>`;
  } else if (f.editKind === 'text') {
    valueHtml = `<input class="field-input" type="text" data-key="${f.key}" value="${escHtml(String(f.editValue))}">`;
  } else if (f.editKind === 'date') {
    valueHtml = `<input class="field-input" type="text" data-key="${f.key}" placeholder="YYYY-MM-DD HH:MM:SS" value="${escHtml(String(f.editValue))}">`;
  } else if (f.editKind === 'decimal') {
    valueHtml = `<input class="field-input" type="number" step="any" data-key="${f.key}" value="${f.editValue}">`;
  } else if (f.editKind === 'exposureTime') {
    valueHtml = `<input class="field-input" type="text" data-key="${f.key}" placeholder="e.g. 1/250 or 2" value="${escHtml(String(f.editValue))}">`;
  } else if (f.editKind === 'int') {
    valueHtml = `<input class="field-input" type="number" step="1" data-key="${f.key}" value="${f.editValue}">`;
  } else {
    valueHtml = escHtml(computeDisplay(f) ?? '');
  }
  return `<tr class="${f.sensitive ? 'sensitive-row' : ''}"><td class="meta-label">${escHtml(f.label)}${lockIcon}</td><td class="meta-value">${valueHtml}</td><td class="meta-del">${delBtn}</td></tr>`;
}

function addFieldRowHTML(item) {
  const options = getAddableOptions(item);
  if (!options.length) return '';
  const opts = options.map(o => `<option value="${escHtml(o.key || o)}">${escHtml(o.label || o)}</option>`).join('');
  return `<div class="add-field-row">
    <select id="addFieldSelect">${opts}</select>
    <button type="button" class="secondary small" data-action="add-field">+ Add field</button>
  </div>`;
}

function renderMetadataTable(item) {
  const fields = visibleFields(item);
  let html = '';
  if (!fields.length) {
    html += `<p class="modal-empty">No metadata found — this file is already clean.</p>`;
  } else {
    const byGroup = {};
    for (const f of fields) (byGroup[f.group] = byGroup[f.group] || []).push(f);
    for (const g of GROUP_ORDER) {
      const list = byGroup[g];
      if (!list || !list.length) continue;
      html += `<div class="meta-group"><div class="meta-group-title">${escHtml(GROUP_LABELS[g] || g)}</div><table class="meta-table">`;
      for (const f of list) html += fieldRowHTML(f);
      html += `</table></div>`;
    }
  }
  html += addFieldRowHTML(item);
  return html;
}

function refreshModalBody(item) {
  modalBody.innerHTML = renderMetadataTable(item);
}

function openModal(item) {
  currentModalItem = item;
  modalName.textContent = item.name;
  refreshModalBody(item);
  modalOverlay.classList.add('open');
}

function closeModal() {
  if (currentModalItem) {
    currentModalItem.fieldModel = extractFieldModel(currentModalItem.format, currentModalItem.workingBuffer);
  }
  currentModalItem = null;
  modalOverlay.classList.remove('open');
  renderAll();
}

async function stripAndDownload(item) {
  if (!item.format || item.error) return;
  try {
    const blob = stripMetadata(item.format, item.workingBuffer, { keepIcc: keepIccCheckbox.checked });
    item.workingBuffer = await blob.arrayBuffer();
    item.modified = true;
    item.fieldModel = extractFieldModel(item.format, item.workingBuffer);
    updateWorkingUrl(item);
    renderAll();
    triggerDownload(item.workingUrl, 'cleaned-' + item.name);
  } catch (err) {
    item.error = 'Could not strip metadata: ' + err.message;
    renderAll();
  }
}

function removeImage(item) {
  URL.revokeObjectURL(item.url);
  if (item.workingUrl) URL.revokeObjectURL(item.workingUrl);
  images = images.filter(i => i !== item);
  renderAll();
}

function clearAll() {
  images.forEach(item => {
    URL.revokeObjectURL(item.url);
    if (item.workingUrl) URL.revokeObjectURL(item.workingUrl);
  });
  images = [];
  renderAll();
}

async function downloadAllCleaned() {
  const valid = images.filter(i => i.format && !i.error);
  if (!valid.length) return;
  const originalLabel = downloadAllBtn.textContent;
  downloadAllBtn.disabled = true;
  downloadAllBtn.textContent = 'Zipping…';
  try {
    const zip = new JSZip();
    const keepIcc = keepIccCheckbox.checked;
    for (const item of valid) {
      try {
        const blob = stripMetadata(item.format, item.workingBuffer, { keepIcc });
        zip.file('cleaned-' + item.name, blob);
      } catch (err) { /* skip this file, continue with the rest */ }
    }
    const content = await zip.generateAsync({ type: 'blob' });
    const url = URL.createObjectURL(content);
    triggerDownload(url, 'cleaned-photos.zip');
    setTimeout(() => URL.revokeObjectURL(url), 10000);
  } finally {
    downloadAllBtn.disabled = images.length === 0;
    downloadAllBtn.textContent = originalLabel;
  }
}

async function addFiles(fileList) {
  const files = Array.from(fileList).filter(f => f.type === 'image/jpeg' || f.type === 'image/png' || /\.(jpe?g|png)$/i.test(f.name));
  for (const file of files) {
    const item = {
      id: 'img' + (idCounter++), file, name: file.name, size: file.size, url: URL.createObjectURL(file),
      format: null, fieldModel: [], error: null, workingBuffer: null,
      modified: false, workingUrl: null, workingSize: null
    };
    images.push(item);
    renderAll();
    try {
      const buffer = await file.arrayBuffer();
      const format = detectFormat(buffer);
      if (!format) throw new Error('Unrecognized file — not a valid JPEG or PNG.');
      item.format = format;
      item.workingBuffer = buffer;
      item.fieldModel = extractFieldModel(format, buffer);
    } catch (err) {
      item.error = err.message || 'Could not read this file.';
    }
    renderAll();
  }
}

/* ---- event wiring ---- */

dropzone.addEventListener('click', () => fileInput.click());
fileInput.addEventListener('change', () => { addFiles(fileInput.files); fileInput.value = ''; });

['dragenter', 'dragover'].forEach(evt => dropzone.addEventListener(evt, e => { e.preventDefault(); dropzone.classList.add('drag'); }));
['dragleave', 'dragend'].forEach(evt => dropzone.addEventListener(evt, e => { e.preventDefault(); dropzone.classList.remove('drag'); }));
dropzone.addEventListener('drop', e => {
  e.preventDefault();
  dropzone.classList.remove('drag');
  if (e.dataTransfer && e.dataTransfer.files.length) addFiles(e.dataTransfer.files);
});

grid.addEventListener('click', e => {
  const btn = e.target.closest('[data-action]');
  if (!btn) return;
  const item = images.find(i => i.id === btn.dataset.id);
  if (!item) return;
  if (btn.dataset.action === 'view') openModal(item);
  else if (btn.dataset.action === 'strip') stripAndDownload(item);
  else if (btn.dataset.action === 'remove') removeImage(item);
});

function handleModalFieldChange(e) {
  const el = e.target.closest('[data-key]');
  if (!el || !currentModalItem) return;
  const f = currentModalItem.fieldModel.find(x => x.key === el.dataset.key);
  if (!f) return;
  if (el.dataset.sub) {
    f.editValue[el.dataset.sub] = parseFloat(el.value) || 0;
    const link = document.getElementById(`gpslink-${f.key}`);
    if (link) link.href = gpsLinkUrl(f.editValue.lat, f.editValue.lon);
  } else {
    f.editValue = el.value;
  }
}
modalBody.addEventListener('input', handleModalFieldChange);
modalBody.addEventListener('change', handleModalFieldChange);

modalBody.addEventListener('click', e => {
  const btn = e.target.closest('[data-action]');
  if (!btn || !currentModalItem) return;
  if (btn.dataset.action === 'delete-field') {
    deleteFieldFromModel(currentModalItem, btn.dataset.key);
    refreshModalBody(currentModalItem);
  } else if (btn.dataset.action === 'add-field') {
    const sel = document.getElementById('addFieldSelect');
    if (sel && sel.value) { addFieldToModel(currentModalItem, sel.value); refreshModalBody(currentModalItem); }
  }
});

modalSaveBtn.addEventListener('click', async () => {
  if (!currentModalItem) return;
  const item = currentModalItem;
  modalSaveBtn.disabled = true;
  modalSaveBtn.textContent = 'Saving…';
  try {
    await applyEdits(item);
    item.modified = true;
    item.fieldModel = extractFieldModel(item.format, item.workingBuffer);
    updateWorkingUrl(item);
    refreshModalBody(item);
    renderAll();
  } catch (err) {
    modalBody.insertAdjacentHTML('afterbegin', `<p class="modal-error">Could not save changes: ${escHtml(err.message)}</p>`);
  } finally {
    modalSaveBtn.disabled = false;
    modalSaveBtn.textContent = 'Save changes';
  }
});

downloadAllBtn.addEventListener('click', downloadAllCleaned);
clearBtn.addEventListener('click', clearAll);
modalClose.addEventListener('click', closeModal);
modalCancel.addEventListener('click', closeModal);
modalOverlay.addEventListener('click', e => { if (e.target === modalOverlay) closeModal(); });
document.addEventListener('keydown', e => { if (e.key === 'Escape' && modalOverlay.classList.contains('open')) closeModal(); });

renderAll();
