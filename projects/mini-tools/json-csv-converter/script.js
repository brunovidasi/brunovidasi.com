'use strict';

/* ---------------------------------------------------------------- */
/* Myers diff — shortest edit script between two arbitrary arrays    */
/* ---------------------------------------------------------------- */

function diffArrays(a, b, eq) {
  eq = eq || ((x, y) => x === y);
  const N = a.length, M = b.length;
  const MAX = N + M || 1;
  const size = 2 * MAX + 1;
  const trace = [];
  let v = new Array(size).fill(0);
  let foundD = -1;

  outer:
  for (let d = 0; d <= MAX; d++) {
    trace.push(v.slice());
    for (let k = -d; k <= d; k += 2) {
      const idx = k + MAX;
      let x;
      if (k === -d || (k !== d && v[idx - 1] < v[idx + 1])) {
        x = v[idx + 1];
      } else {
        x = v[idx - 1] + 1;
      }
      let y = x - k;
      while (x < N && y < M && eq(a[x], b[y])) { x++; y++; }
      v[idx] = x;
      if (x >= N && y >= M) { foundD = d; break outer; }
    }
  }

  const ops = [];
  let x = N, y = M;
  for (let d = foundD; d >= 0; d--) {
    const vPrev = trace[d];
    const k = x - y;
    const idx = k + MAX;
    let prevK;
    if (k === -d || (k !== d && vPrev[idx - 1] < vPrev[idx + 1])) {
      prevK = k + 1;
    } else {
      prevK = k - 1;
    }
    const prevX = vPrev[prevK + MAX];
    const prevY = prevX - prevK;
    while (x > prevX && y > prevY) {
      ops.push({ type: 'equal', a: x - 1, b: y - 1 });
      x--; y--;
    }
    if (d > 0) {
      if (x === prevX) {
        ops.push({ type: 'insert', a: -1, b: y - 1 });
        y--;
      } else {
        ops.push({ type: 'delete', a: x - 1, b: -1 });
        x--;
      }
    }
  }
  ops.reverse();
  return ops;
}

/* ---------------------------------------------------------------- */
/* Generic helpers                                                    */
/* ---------------------------------------------------------------- */

function escapeHtml(s) {
  return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function stripMarksToText(html) {
  return html.replace(/<[^>]+>/g, '').replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&amp;/g, '&');
}

function debounce(fn, ms) {
  let t = null;
  return (...args) => {
    clearTimeout(t);
    t = setTimeout(() => fn(...args), ms);
  };
}

function download(filename, text) {
  const blob = new Blob([text], { type: 'text/plain' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = filename;
  a.click();
  URL.revokeObjectURL(url);
}

async function copyText(text, btn) {
  try {
    await navigator.clipboard.writeText(text);
    const original = btn.textContent;
    btn.textContent = 'Copied!';
    btn.classList.add('copied');
    setTimeout(() => { btn.textContent = original; btn.classList.remove('copied'); }, 1400);
  } catch (err) {
    const original = btn.textContent;
    btn.textContent = 'Copy failed';
    setTimeout(() => { btn.textContent = original; }, 1400);
  }
}

/* ---------------------------------------------------------------- */
/* CSV parsing / serialization                                       */
/* ---------------------------------------------------------------- */

function resolveDelimiter(val) {
  return val === '\\t' ? '\t' : val;
}

function detectDelimiter(text) {
  const firstLine = text.split(/\r\n|\r|\n/, 1)[0] || '';
  const candidates = [',', ';', '\t'];
  let best = ',', bestCount = -1;
  for (const d of candidates) {
    const count = firstLine.split(d).length - 1;
    if (count > bestCount) { bestCount = count; best = d; }
  }
  return best;
}

function parseCSV(text, delimiter) {
  const rows = [];
  let row = [];
  let field = '';
  let inQuotes = false;
  let touched = false;
  const n = text.length;
  let i = 0;
  while (i < n) {
    const c = text[i];
    if (inQuotes) {
      if (c === '"') {
        if (text[i + 1] === '"') { field += '"'; i += 2; continue; }
        inQuotes = false; i++; continue;
      }
      field += c; i++; continue;
    }
    if (c === '"') { inQuotes = true; touched = true; i++; continue; }
    if (c === delimiter) { row.push(field); field = ''; touched = true; i++; continue; }
    if (c === '\r') { i++; continue; }
    if (c === '\n') { row.push(field); rows.push(row); row = []; field = ''; touched = false; i++; continue; }
    field += c; touched = true; i++;
  }
  if (touched || field !== '' || row.length > 0) { row.push(field); rows.push(row); }
  return rows;
}

function csvField(value, delimiter) {
  const s = String(value);
  if (s.includes('"') || s.includes('\n') || s.includes('\r') || s.includes(delimiter)) {
    return '"' + s.replace(/"/g, '""') + '"';
  }
  return s;
}

function rowsToCSV(rows, delimiter) {
  return rows.map((r) => r.map((v) => csvField(v, delimiter)).join(delimiter)).join('\n');
}

/* ---------------------------------------------------------------- */
/* JSON helpers                                                       */
/* ---------------------------------------------------------------- */

function parseJsonWithLocation(text) {
  try {
    return { ok: true, value: JSON.parse(text) };
  } catch (err) {
    const msg = err.message;
    let line = null, col = null;
    const mPos = msg.match(/position (\d+)/);
    if (mPos) {
      const pos = Number(mPos[1]);
      const upto = text.slice(0, pos);
      const lines = upto.split('\n');
      line = lines.length;
      col = lines[lines.length - 1].length + 1;
    }
    const mLc = msg.match(/line (\d+) column (\d+)/);
    if (mLc) { line = Number(mLc[1]); col = Number(mLc[2]); }
    return { ok: false, error: { message: msg, line, col } };
  }
}

function isPlainObject(v) {
  return v !== null && typeof v === 'object' && !Array.isArray(v);
}

function sortKeysDeep(value) {
  if (Array.isArray(value)) return value.map(sortKeysDeep);
  if (isPlainObject(value)) {
    const sorted = {};
    for (const k of Object.keys(value).sort()) sorted[k] = sortKeysDeep(value[k]);
    return sorted;
  }
  return value;
}

function stringifyJson(value, indentOption) {
  if (indentOption === 'min') return JSON.stringify(value);
  const indent = indentOption === 'tab' ? '\t' : Number(indentOption);
  return JSON.stringify(value, null, indent);
}

function detectFormat(text) {
  const t = text.trim();
  if (!t) return null;
  if (/^[\[{]/.test(t)) return 'json';
  const parsed = parseJsonWithLocation(t);
  if (parsed.ok) return 'json';
  return 'csv';
}

/* ---------------------------------------------------------------- */
/* JSON <-> rows (CSV) conversion                                     */
/* ---------------------------------------------------------------- */

function flattenValue(v) {
  if (v === null || v === undefined) return '';
  if (typeof v === 'object') return JSON.stringify(v);
  return String(v);
}

function jsonToRows(data) {
  if (Array.isArray(data)) {
    if (data.length === 0) return { header: [], rows: [], hasRealHeader: false };
    if (data.every(isPlainObject)) {
      const header = [];
      const seen = new Set();
      for (const obj of data) {
        for (const k of Object.keys(obj)) {
          if (!seen.has(k)) { seen.add(k); header.push(k); }
        }
      }
      const rows = data.map((obj) => header.map((k) => flattenValue(obj[k])));
      return { header, rows, hasRealHeader: true };
    }
    if (data.every((v) => Array.isArray(v))) {
      const maxLen = Math.max(...data.map((r) => r.length));
      const header = Array.from({ length: maxLen }, (_, i) => `column_${i + 1}`);
      const rows = data.map((r) => header.map((_, i) => flattenValue(r[i])));
      return { header, rows, hasRealHeader: false };
    }
    const header = ['value'];
    const rows = data.map((v) => [flattenValue(v)]);
    return { header, rows, hasRealHeader: false };
  }
  if (isPlainObject(data)) {
    const header = Object.keys(data);
    const rows = [header.map((k) => flattenValue(data[k]))];
    return { header, rows, hasRealHeader: true };
  }
  return { header: ['value'], rows: [[flattenValue(data)]], hasRealHeader: false };
}

function coerceCsvValue(s) {
  if (s === '') return '';
  if (s === 'true') return true;
  if (s === 'false') return false;
  if (s === 'null') return null;
  if (/^-?\d+(\.\d+)?([eE][-+]?\d+)?$/.test(s)) return Number(s);
  return s;
}

function rowsToJson(rows, opts) {
  if (rows.length === 0) return [];
  let header, dataRows;
  if (opts.headerRow) {
    header = rows[0];
    dataRows = rows.slice(1);
  } else {
    const maxLen = Math.max(...rows.map((r) => r.length));
    header = Array.from({ length: maxLen }, (_, i) => `column_${i + 1}`);
    dataRows = rows;
  }
  return dataRows.map((r) => {
    const obj = {};
    header.forEach((h, i) => {
      const raw = r[i] !== undefined ? r[i] : '';
      obj[h || `column_${i + 1}`] = opts.typeCoerce ? coerceCsvValue(raw) : raw;
    });
    return obj;
  });
}

/* ================================================================ */
/* FORMAT & CONVERT MODE                                              */
/* ================================================================ */

(function initConvertMode() {
  const inputTypeSeg = document.getElementById('inputTypeSeg');
  const outputTypeSeg = document.getElementById('outputTypeSeg');
  const indentSelect = document.getElementById('indentSelect');
  const sortKeysChk = document.getElementById('sortKeys');
  const delimiterSelect = document.getElementById('delimiterSelect');
  const headerRowChk = document.getElementById('headerRow');
  const typeCoerceChk = document.getElementById('typeCoerce');
  const outDelimiterSelect = document.getElementById('outDelimiterSelect');
  const jsonOptsGroup = document.getElementById('jsonOptsGroup');
  const csvInOptsGroup = document.getElementById('csvInOptsGroup');
  const csvOutOptsGroup = document.getElementById('csvOutOptsGroup');
  const inputArea = document.getElementById('inputArea');
  const outputArea = document.getElementById('outputArea');
  const metaIn = document.getElementById('metaIn');
  const metaOut = document.getElementById('metaOut');
  const detectedBadge = document.getElementById('detectedBadge');
  const statusPill = document.getElementById('statusPill');
  const sampleBtn = document.getElementById('sampleBtn');
  const clearBtn = document.getElementById('clearBtn');
  const fileIn = document.getElementById('fileIn');
  const downloadBtn = document.getElementById('downloadBtn');
  const copyBtn = document.getElementById('copyBtn');

  let inputType = 'auto';
  let outputType = 'json';
  let lastOutput = '';

  function meta(text) {
    const lines = text === '' ? 0 : text.split('\n').length;
    return `${lines} line${lines === 1 ? '' : 's'} · ${text.length} char${text.length === 1 ? '' : 's'}`;
  }

  function setStatus(kind, text) {
    statusPill.className = `status-pill status-${kind}`;
    statusPill.textContent = text;
  }

  function syncOptionVisibility(effectiveType) {
    jsonOptsGroup.style.display = outputType === 'json' ? '' : 'none';
    csvOutOptsGroup.style.display = outputType === 'csv' ? '' : 'none';
    csvInOptsGroup.style.display = effectiveType === 'csv' ? '' : 'none';
  }

  function recompute() {
    const text = inputArea.value;
    metaIn.textContent = meta(text);

    const effectiveType = inputType === 'auto' ? detectFormat(text) : (text.trim() ? inputType : null);
    detectedBadge.textContent = effectiveType ? effectiveType.toUpperCase() : '—';
    syncOptionVisibility(effectiveType);

    if (!effectiveType) {
      outputArea.value = '';
      metaOut.textContent = meta('');
      setStatus('idle', 'Paste some input to get started.');
      lastOutput = '';
      return;
    }

    const delimVal = delimiterSelect.value === 'auto' ? detectDelimiter(text) : resolveDelimiter(delimiterSelect.value);
    const outDelim = resolveDelimiter(outDelimiterSelect.value);

    let value; // parsed JS value, when input is JSON
    let rows;  // array of arrays, when input is CSV

    if (effectiveType === 'json') {
      const parsed = parseJsonWithLocation(text);
      if (!parsed.ok) {
        const loc = parsed.error.line != null ? ` at line ${parsed.error.line}, column ${parsed.error.col}` : '';
        setStatus('err', `Invalid JSON${loc}: ${parsed.error.message}`);
        outputArea.value = '';
        metaOut.textContent = meta('');
        lastOutput = '';
        return;
      }
      value = parsed.value;
      if (sortKeysChk.checked) value = sortKeysDeep(value);
    } else {
      rows = parseCSV(text, delimVal);
      if (rows.length === 0) {
        setStatus('err', 'No CSV rows found.');
        outputArea.value = '';
        metaOut.textContent = meta('');
        lastOutput = '';
        return;
      }
      const colCount = rows[0].length;
      const mismatched = rows.some((r) => r.length !== colCount);
      if (mismatched) {
        setStatus('err', `CSV has inconsistent column counts (expected ${colCount} per row).`);
      }
    }

    let output;
    if (outputType === 'json') {
      const data = effectiveType === 'json'
        ? value
        : rowsToJson(rows, { headerRow: headerRowChk.checked, typeCoerce: typeCoerceChk.checked });
      output = stringifyJson(data, indentSelect.value);
    } else {
      let outRows, header, hasRealHeader;
      if (effectiveType === 'json') {
        ({ header, rows: outRows, hasRealHeader } = jsonToRows(value));
      } else {
        outRows = rows;
        header = null;
        hasRealHeader = false;
      }
      const finalRows = header && hasRealHeader ? [header, ...outRows] : outRows;
      output = rowsToCSV(finalRows, outDelim);
    }

    outputArea.value = output;
    metaOut.textContent = meta(output);
    lastOutput = output;

    if (effectiveType === 'json') {
      const count = Array.isArray(value) ? value.length : (isPlainObject(value) ? Object.keys(value).length : 1);
      const noun = Array.isArray(value) ? `item${count === 1 ? '' : 's'}` : (isPlainObject(value) ? `key${count === 1 ? '' : 's'}` : 'value');
      setStatus('ok', `Valid JSON — ${count} ${noun}.`);
    } else if (!statusPill.classList.contains('status-err')) {
      const rowCount = headerRowChk.checked ? rows.length - 1 : rows.length;
      setStatus('ok', `Valid CSV — ${Math.max(rowCount, 0)} data row${rowCount === 1 ? '' : 's'}, ${rows[0].length} column${rows[0].length === 1 ? '' : 's'}.`);
    }
  }

  const debouncedRecompute = debounce(recompute, 160);
  inputArea.addEventListener('input', debouncedRecompute);
  [indentSelect, sortKeysChk, delimiterSelect, headerRowChk, typeCoerceChk, outDelimiterSelect].forEach((el) => {
    el.addEventListener('change', recompute);
  });

  inputTypeSeg.addEventListener('click', (e) => {
    const btn = e.target.closest('.seg-btn');
    if (!btn) return;
    inputTypeSeg.querySelectorAll('.seg-btn').forEach((b) => b.classList.remove('active'));
    btn.classList.add('active');
    inputType = btn.dataset.val;
    recompute();
  });

  outputTypeSeg.addEventListener('click', (e) => {
    const btn = e.target.closest('.seg-btn');
    if (!btn) return;
    outputTypeSeg.querySelectorAll('.seg-btn').forEach((b) => b.classList.remove('active'));
    btn.classList.add('active');
    outputType = btn.dataset.val;
    recompute();
  });

  clearBtn.addEventListener('click', () => {
    inputArea.value = '';
    recompute();
  });

  sampleBtn.addEventListener('click', () => {
    inputArea.value = `[
  { "id": 1, "name": "Ada Lovelace", "role": "Mathematician", "active": true },
  { "id": 2, "name": "Alan Turing", "role": "Computer Scientist", "active": true },
  { "id": 3, "name": "Grace Hopper", "role": "Rear Admiral", "active": false }
]`;
    recompute();
  });

  fileIn.addEventListener('change', () => {
    const file = fileIn.files && fileIn.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = () => {
      inputArea.value = String(reader.result);
      if (/\.csv$/i.test(file.name)) {
        inputTypeSeg.querySelectorAll('.seg-btn').forEach((b) => b.classList.toggle('active', b.dataset.val === 'csv'));
        inputType = 'csv';
      } else if (/\.json$/i.test(file.name)) {
        inputTypeSeg.querySelectorAll('.seg-btn').forEach((b) => b.classList.toggle('active', b.dataset.val === 'json'));
        inputType = 'json';
      }
      recompute();
    };
    reader.readAsText(file);
    fileIn.value = '';
  });

  downloadBtn.addEventListener('click', () => {
    if (!lastOutput) return;
    download(outputType === 'json' ? 'output.json' : 'output.csv', lastOutput);
  });

  copyBtn.addEventListener('click', () => {
    if (!lastOutput) return;
    copyText(lastOutput, copyBtn);
  });

  recompute();
})();

/* ================================================================ */
/* COMPARE VERSIONS MODE                                              */
/* ================================================================ */

(function initCompareMode() {
  const cmpTypeSeg = document.getElementById('cmpTypeSeg');
  const cmpSortKeys = document.getElementById('cmpSortKeys');
  const cmpCollapse = document.getElementById('cmpCollapse');
  const cmpSwapBtn = document.getElementById('cmpSwapBtn');
  const cmpSampleBtn = document.getElementById('cmpSampleBtn');
  const cmpClearBtn = document.getElementById('cmpClearBtn');
  const cmpInputA = document.getElementById('cmpInputA');
  const cmpInputB = document.getElementById('cmpInputB');
  const cmpMetaA = document.getElementById('cmpMetaA');
  const cmpMetaB = document.getElementById('cmpMetaB');
  const cmpFileA = document.getElementById('cmpFileA');
  const cmpFileB = document.getElementById('cmpFileB');
  const cmpCopyBtn = document.getElementById('cmpCopyBtn');
  const cmpStatsBar = document.getElementById('cmpStatsBar');
  const cmpDiffTable = document.getElementById('cmpDiffTable');
  const cmpDiffBody = document.getElementById('cmpDiffBody');
  const cmpEmptyState = document.getElementById('cmpEmptyState');

  let cmpType = 'auto';
  let lastRows = [];
  let expanderSeq = 0;
  const CONTEXT = 3;

  function meta(text) {
    const lines = text === '' ? 0 : text.split('\n').length;
    return `${lines} line${lines === 1 ? '' : 's'} · ${text.length} char${text.length === 1 ? '' : 's'}`;
  }

  function normalize(text) {
    const t = text.trim();
    if (!t) return { ok: true, text: '' };
    const effectiveType = cmpType === 'auto' ? detectFormat(t) : cmpType;
    if (effectiveType === 'json') {
      const parsed = parseJsonWithLocation(t);
      if (!parsed.ok) {
        const loc = parsed.error.line != null ? ` at line ${parsed.error.line}, column ${parsed.error.col}` : '';
        return { ok: false, error: `Invalid JSON${loc}: ${parsed.error.message}` };
      }
      const value = cmpSortKeys.checked ? sortKeysDeep(parsed.value) : parsed.value;
      return { ok: true, text: JSON.stringify(value, null, 2) };
    }
    const delim = detectDelimiter(t);
    const rows = parseCSV(t, delim);
    return { ok: true, text: rowsToCSV(rows, ',') };
  }

  function tokenizeWords(line) {
    return line.match(/\s+|[^\s]+/g) || [];
  }

  const TOKEN_GUARD = 250000;

  function tokenDiffHtml(oldLine, newLine) {
    const a = tokenizeWords(oldLine);
    const b = tokenizeWords(newLine);
    if (a.length * b.length > TOKEN_GUARD) {
      return {
        aHtml: `<mark class="tok-del">${escapeHtml(oldLine)}</mark>`,
        bHtml: `<mark class="tok-ins">${escapeHtml(newLine)}</mark>`,
      };
    }
    const ops = diffArrays(a, b);
    function buildSide(side) {
      const keep = side === 'a' ? ['equal', 'delete'] : ['equal', 'insert'];
      const markClass = side === 'a' ? 'tok-del' : 'tok-ins';
      const arr = side === 'a' ? a : b;
      const key = side === 'a' ? 'a' : 'b';
      let html = '', run = '', runType = null;
      const flush = () => {
        if (!run) return;
        html += runType === 'equal' ? escapeHtml(run) : `<mark class="${markClass}">${escapeHtml(run)}</mark>`;
        run = '';
      };
      for (const op of ops) {
        if (!keep.includes(op.type)) continue;
        if (op.type !== runType) { flush(); runType = op.type; }
        run += arr[op[key]];
      }
      flush();
      return html;
    }
    return { aHtml: buildSide('a'), bHtml: buildSide('b') };
  }

  function buildDiffRows(textA, textB) {
    const rawLinesA = textA.split('\n');
    const rawLinesB = textB.split('\n');
    const lineOps = diffArrays(rawLinesA, rawLinesB);

    const segments = [];
    let i = 0;
    while (i < lineOps.length) {
      if (lineOps[i].type === 'equal') {
        let j = i;
        const items = [];
        while (j < lineOps.length && lineOps[j].type === 'equal') { items.push(lineOps[j]); j++; }
        segments.push({ type: 'equal', items });
        i = j;
      } else {
        let j = i;
        const dels = [], inss = [];
        while (j < lineOps.length && lineOps[j].type !== 'equal') {
          if (lineOps[j].type === 'delete') dels.push(lineOps[j].a);
          else inss.push(lineOps[j].b);
          j++;
        }
        segments.push({ type: 'change', dels, inss });
        i = j;
      }
    }

    let numA = 1, numB = 1;
    const rows = [];
    const stats = { equal: 0, added: 0, removed: 0, changed: 0 };

    for (const seg of segments) {
      if (seg.type === 'equal') {
        for (const op of seg.items) {
          rows.push({ kind: 'equal', aNum: numA++, aText: rawLinesA[op.a], bNum: numB++, bText: rawLinesB[op.b] });
          stats.equal++;
        }
      } else {
        const { dels, inss } = seg;
        const pairCount = Math.min(dels.length, inss.length);
        for (let k = 0; k < pairCount; k++) {
          const oldLine = rawLinesA[dels[k]];
          const newLine = rawLinesB[inss[k]];
          const { aHtml, bHtml } = tokenDiffHtml(oldLine, newLine);
          rows.push({ kind: 'replace', aNum: numA++, aHtml, bNum: numB++, bHtml });
          stats.changed++;
        }
        for (let k = pairCount; k < dels.length; k++) {
          rows.push({ kind: 'delete', aNum: numA++, aText: rawLinesA[dels[k]] });
          stats.removed++;
        }
        for (let k = pairCount; k < inss.length; k++) {
          rows.push({ kind: 'insert', bNum: numB++, bText: rawLinesB[inss[k]] });
          stats.added++;
        }
      }
    }
    return { rows, stats };
  }

  function planEqualRun(rows, startIdx, count, isFirst, isLast, collapse) {
    if (!collapse || count <= CONTEXT * 2 + 1) {
      return rows.slice(startIdx, startIdx + count).map((r) => ({ show: true, row: r }));
    }
    const slice = rows.slice(startIdx, startIdx + count);
    if (isFirst) {
      const hidden = slice.slice(0, count - CONTEXT);
      const visible = slice.slice(count - CONTEXT);
      return [{ show: false, group: hidden }, ...visible.map((r) => ({ show: true, row: r }))];
    }
    if (isLast) {
      const visible = slice.slice(0, CONTEXT);
      const hidden = slice.slice(CONTEXT);
      return [...visible.map((r) => ({ show: true, row: r })), { show: false, group: hidden }];
    }
    const head = slice.slice(0, CONTEXT);
    const hidden = slice.slice(CONTEXT, count - CONTEXT);
    const tail = slice.slice(count - CONTEXT);
    return [
      ...head.map((r) => ({ show: true, row: r })),
      { show: false, group: hidden },
      ...tail.map((r) => ({ show: true, row: r })),
    ];
  }

  function rowHtml(row) {
    switch (row.kind) {
      case 'equal':
        return `<tr class="row-equal">` +
          `<td class="c-num">${row.aNum}</td><td class="c-content">${escapeHtml(row.aText)}</td>` +
          `<td class="c-num">${row.bNum}</td><td class="c-content">${escapeHtml(row.bText)}</td></tr>`;
      case 'delete':
        return `<tr class="row-del">` +
          `<td class="c-num">${row.aNum}</td><td class="c-content">${escapeHtml(row.aText)}</td>` +
          `<td class="c-num"></td><td class="c-content c-empty"></td></tr>`;
      case 'insert':
        return `<tr class="row-ins">` +
          `<td class="c-num"></td><td class="c-content c-empty"></td>` +
          `<td class="c-num">${row.bNum}</td><td class="c-content">${row.bText ? escapeHtml(row.bText) : ''}</td></tr>`;
      case 'replace':
        return `<tr class="row-repl">` +
          `<td class="c-num">${row.aNum}</td><td class="c-content">${row.aHtml}</td>` +
          `<td class="c-num">${row.bNum}</td><td class="c-content">${row.bHtml}</td></tr>`;
      default:
        return '';
    }
  }

  function renderDiff(rows, stats, collapse) {
    if (rows.length === 0) {
      cmpDiffTable.hidden = true;
      cmpEmptyState.hidden = false;
      cmpEmptyState.textContent = 'Paste JSON or CSV on both sides to see the diff.';
      cmpDiffBody.innerHTML = '';
      return;
    }
    const totalChanges = stats.added + stats.removed + stats.changed;
    if (totalChanges === 0) {
      cmpDiffTable.hidden = true;
      cmpEmptyState.hidden = false;
      cmpEmptyState.textContent = `✓ Identical after normalizing — no differences found across ${stats.equal} line${stats.equal === 1 ? '' : 's'}.`;
      cmpDiffBody.innerHTML = '';
      return;
    }
    cmpEmptyState.hidden = true;
    cmpDiffTable.hidden = false;

    let html = '';
    let i = 0;
    while (i < rows.length) {
      if (rows[i].kind === 'equal') {
        let j = i;
        while (j < rows.length && rows[j].kind === 'equal') j++;
        const count = j - i;
        const isFirst = i === 0;
        const isLast = j === rows.length;
        const plan = planEqualRun(rows, i, count, isFirst, isLast, collapse);
        for (const entry of plan) {
          if (entry.show) {
            html += rowHtml(entry.row);
          } else if (entry.group.length) {
            const gid = `cmpgrp-${++expanderSeq}`;
            const n = entry.group.length;
            html += `<tr class="row-expander" data-target="${gid}"><td colspan="4">⋯ ${n} unchanged line${n === 1 ? '' : 's'} — click to expand ⋯</td></tr>`;
            for (const r of entry.group) {
              html += rowHtml(r).replace('<tr class="', `<tr data-group="${gid}" class="grp-row `);
            }
          }
        }
        i = j;
      } else {
        html += rowHtml(rows[i]);
        i++;
      }
    }
    cmpDiffBody.innerHTML = html;
  }

  function renderStats(stats) {
    const total = stats.equal + stats.added + stats.removed + stats.changed;
    const similarity = total === 0 ? 100 : Math.round((stats.equal / total) * 100);
    cmpStatsBar.innerHTML = `
      <span class="stat-pill">${similarity}% similar</span>
      <span class="stat-pill stat-equal">${stats.equal} unchanged</span>
      <span class="stat-pill stat-chg">${stats.changed} changed</span>
      <span class="stat-pill stat-ins">${stats.added} added</span>
      <span class="stat-pill stat-del">${stats.removed} removed</span>
    `;
  }

  function recompute() {
    cmpMetaA.textContent = meta(cmpInputA.value);
    cmpMetaB.textContent = meta(cmpInputB.value);

    if (cmpInputA.value === '' && cmpInputB.value === '') {
      lastRows = [];
      cmpStatsBar.innerHTML = '';
      renderDiff([], { equal: 0, added: 0, removed: 0, changed: 0 }, cmpCollapse.checked);
      return;
    }

    const normA = normalize(cmpInputA.value);
    const normB = normalize(cmpInputB.value);

    if (!normA.ok || !normB.ok) {
      cmpStatsBar.innerHTML = `<span class="stat-pill" style="color:var(--err);background:var(--err-bg)">${!normA.ok ? 'A: ' + normA.error : 'B: ' + normB.error}</span>`;
      cmpDiffTable.hidden = true;
      cmpEmptyState.hidden = false;
      cmpEmptyState.textContent = 'Fix the parse error above to see the diff.';
      cmpDiffBody.innerHTML = '';
      lastRows = [];
      return;
    }

    const { rows, stats } = buildDiffRows(normA.text, normB.text);
    lastRows = rows;
    renderStats(stats);
    renderDiff(rows, stats, cmpCollapse.checked);
  }

  const debouncedRecompute = debounce(recompute, 180);
  cmpInputA.addEventListener('input', debouncedRecompute);
  cmpInputB.addEventListener('input', debouncedRecompute);
  [cmpSortKeys, cmpCollapse].forEach((el) => el.addEventListener('change', recompute));

  cmpTypeSeg.addEventListener('click', (e) => {
    const btn = e.target.closest('.seg-btn');
    if (!btn) return;
    cmpTypeSeg.querySelectorAll('.seg-btn').forEach((b) => b.classList.remove('active'));
    btn.classList.add('active');
    cmpType = btn.dataset.val;
    recompute();
  });

  cmpSwapBtn.addEventListener('click', () => {
    const tmp = cmpInputA.value;
    cmpInputA.value = cmpInputB.value;
    cmpInputB.value = tmp;
    recompute();
  });

  cmpClearBtn.addEventListener('click', () => {
    cmpInputA.value = '';
    cmpInputB.value = '';
    recompute();
  });

  cmpSampleBtn.addEventListener('click', () => {
    cmpInputA.value = `{
  "name": "Ada Lovelace",
  "role": "Mathematician",
  "active": true,
  "born": 1815
}`;
    cmpInputB.value = `{
  "name": "Ada Lovelace",
  "role": "Computer Scientist",
  "active": true,
  "born": 1815,
  "notableWork": "Analytical Engine notes"
}`;
    recompute();
  });

  function loadFile(input, target) {
    input.addEventListener('change', () => {
      const file = input.files && input.files[0];
      if (!file) return;
      const reader = new FileReader();
      reader.onload = () => {
        target.value = String(reader.result);
        recompute();
      };
      reader.readAsText(file);
      input.value = '';
    });
  }
  loadFile(cmpFileA, cmpInputA);
  loadFile(cmpFileB, cmpInputB);

  cmpDiffTable.addEventListener('click', (e) => {
    const exp = e.target.closest('.row-expander');
    if (!exp) return;
    const gid = exp.dataset.target;
    cmpDiffTable.querySelectorAll(`tr[data-group="${gid}"]`).forEach((tr) => tr.classList.add('revealed'));
    exp.remove();
  });

  cmpCopyBtn.addEventListener('click', () => {
    const lines = [];
    for (const row of lastRows) {
      if (row.kind === 'equal') lines.push(`  ${row.aText}`);
      else if (row.kind === 'delete') lines.push(`- ${row.aText}`);
      else if (row.kind === 'insert') lines.push(`+ ${row.bText}`);
      else if (row.kind === 'replace') {
        lines.push(`- ${stripMarksToText(row.aHtml)}`);
        lines.push(`+ ${stripMarksToText(row.bHtml)}`);
      }
    }
    copyText(lines.join('\n'), cmpCopyBtn);
  });

  recompute();
})();

/* ================================================================ */
/* MODE SWITCH                                                       */
/* ================================================================ */

(function initModeSwitch() {
  const modeSeg = document.getElementById('modeSeg');
  const convertMode = document.getElementById('convertMode');
  const compareMode = document.getElementById('compareMode');

  modeSeg.addEventListener('click', (e) => {
    const btn = e.target.closest('.seg-btn');
    if (!btn) return;
    modeSeg.querySelectorAll('.seg-btn').forEach((b) => b.classList.remove('active'));
    btn.classList.add('active');
    const mode = btn.dataset.mode;
    convertMode.hidden = mode !== 'convert';
    compareMode.hidden = mode !== 'compare';
  });
})();
