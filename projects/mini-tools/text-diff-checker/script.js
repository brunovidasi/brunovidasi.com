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
/* Helpers                                                           */
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

function tokenizeWords(line) {
  return line.match(/\s+|[^\s]+/g) || [];
}

function tokenizeChars(line) {
  return Array.from(line);
}

/* ---------------------------------------------------------------- */
/* Intra-line (word/char) diff -> HTML for each side                 */
/* ---------------------------------------------------------------- */

const TOKEN_GUARD = 250000; // a*b product cap before falling back to whole-line marks

function tokenDiffHtml(oldLine, newLine, granularity, opts) {
  const tokenize = granularity === 'char' ? tokenizeChars : tokenizeWords;
  const a = tokenize(oldLine);
  const b = tokenize(newLine);

  if (a.length * b.length > TOKEN_GUARD) {
    return {
      aHtml: `<mark class="tok-del">${escapeHtml(oldLine)}</mark>`,
      bHtml: `<mark class="tok-ins">${escapeHtml(newLine)}</mark>`,
    };
  }

  const tokEq = (x, y) => {
    if (opts.ignoreWhitespace && /^\s+$/.test(x) && /^\s+$/.test(y)) return true;
    let tx = x, ty = y;
    if (opts.ignoreCase) { tx = tx.toLowerCase(); ty = ty.toLowerCase(); }
    if (opts.ignoreWhitespace) { tx = tx.replace(/\s+/g, ''); ty = ty.replace(/\s+/g, ''); }
    return tx === ty;
  };

  const ops = diffArrays(a, b, tokEq);

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

/* ---------------------------------------------------------------- */
/* Line-level diff -> row plan                                       */
/* ---------------------------------------------------------------- */

function buildDiffRows(textA, textB, opts) {
  const rawLinesA = textA.split('\n');
  const rawLinesB = textB.split('\n');

  const normLine = (s) => {
    let t = s;
    if (opts.ignoreCase) t = t.toLowerCase();
    if (opts.ignoreWhitespace) t = t.replace(/\s+/g, '');
    return t;
  };

  const lineOps = diffArrays(rawLinesA, rawLinesB, (x, y) => normLine(x) === normLine(y));

  // Group into equal-runs and change-runs (a change-run may mix delete/insert ops).
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
        const { aHtml, bHtml } = tokenDiffHtml(oldLine, newLine, opts.granularity, opts);
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

/* ---------------------------------------------------------------- */
/* Rendering                                                         */
/* ---------------------------------------------------------------- */

const CONTEXT = 3;
let expanderSeq = 0;

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
        `<td class="c-num">${row.bNum}</td><td class="c-content">${escapeHtml(row.bText)}</td></tr>`;
    case 'replace':
      return `<tr class="row-repl">` +
        `<td class="c-num">${row.aNum}</td><td class="c-content">${row.aHtml}</td>` +
        `<td class="c-num">${row.bNum}</td><td class="c-content">${row.bHtml}</td></tr>`;
    default:
      return '';
  }
}

function renderDiff(rows, stats, collapse) {
  const table = document.getElementById('diffTable');
  const body = document.getElementById('diffBody');
  const emptyState = document.getElementById('diffEmptyState');

  if (rows.length === 0) {
    table.hidden = true;
    emptyState.hidden = false;
    emptyState.textContent = 'Paste some text on both sides to see the diff.';
    body.innerHTML = '';
    return;
  }

  const totalChanges = stats.added + stats.removed + stats.changed;
  if (totalChanges === 0) {
    table.hidden = true;
    emptyState.hidden = false;
    emptyState.textContent = `✓ Identical — no differences found across ${stats.equal} line${stats.equal === 1 ? '' : 's'}.`;
    body.innerHTML = '';
    return;
  }

  emptyState.hidden = true;
  table.hidden = false;

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
          const gid = `grp-${++expanderSeq}`;
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

  body.innerHTML = html;
}

function renderStats(stats) {
  const bar = document.getElementById('statsBar');
  const total = stats.equal + stats.added + stats.removed + stats.changed;
  const similarity = total === 0 ? 100 : Math.round((stats.equal / total) * 100);
  bar.innerHTML = `
    <span class="stat-pill">${similarity}% similar</span>
    <span class="stat-pill stat-equal">${stats.equal} unchanged</span>
    <span class="stat-pill stat-chg">${stats.changed} changed</span>
    <span class="stat-pill stat-ins">${stats.added} added</span>
    <span class="stat-pill stat-del">${stats.removed} removed</span>
  `;
}

/* ---------------------------------------------------------------- */
/* Wiring                                                             */
/* ---------------------------------------------------------------- */

const inputA = document.getElementById('inputA');
const inputB = document.getElementById('inputB');
const metaA = document.getElementById('metaA');
const metaB = document.getElementById('metaB');
const granularitySeg = document.getElementById('granularitySeg');
const ignoreCase = document.getElementById('ignoreCase');
const ignoreWhitespace = document.getElementById('ignoreWhitespace');
const collapseUnchanged = document.getElementById('collapseUnchanged');
const swapBtn = document.getElementById('swapBtn');
const sampleBtn = document.getElementById('sampleBtn');
const clearBtn = document.getElementById('clearBtn');
const fileA = document.getElementById('fileA');
const fileB = document.getElementById('fileB');
const copyDiffBtn = document.getElementById('copyDiffBtn');
const diffTable = document.getElementById('diffTable');

let granularity = 'word';
let lastRows = [];

function updateMeta(textarea, metaEl) {
  const text = textarea.value;
  const lines = text === '' ? 0 : text.split('\n').length;
  metaEl.textContent = `${lines} line${lines === 1 ? '' : 's'} · ${text.length} char${text.length === 1 ? '' : 's'}`;
}

function currentOpts() {
  return {
    granularity,
    ignoreCase: ignoreCase.checked,
    ignoreWhitespace: ignoreWhitespace.checked,
  };
}

function recompute() {
  updateMeta(inputA, metaA);
  updateMeta(inputB, metaB);

  if (inputA.value === '' && inputB.value === '') {
    lastRows = [];
    document.getElementById('statsBar').innerHTML = '';
    renderDiff([], { equal: 0, added: 0, removed: 0, changed: 0 }, collapseUnchanged.checked);
    return;
  }

  const { rows, stats } = buildDiffRows(inputA.value, inputB.value, currentOpts());
  lastRows = rows;
  renderStats(stats);
  renderDiff(rows, stats, collapseUnchanged.checked);
}

const debouncedRecompute = debounce(recompute, 180);

inputA.addEventListener('input', debouncedRecompute);
inputB.addEventListener('input', debouncedRecompute);

granularitySeg.addEventListener('click', (e) => {
  const btn = e.target.closest('.seg-btn');
  if (!btn) return;
  granularitySeg.querySelectorAll('.seg-btn').forEach((b) => b.classList.remove('active'));
  btn.classList.add('active');
  granularity = btn.dataset.val;
  recompute();
});

[ignoreCase, ignoreWhitespace, collapseUnchanged].forEach((el) => el.addEventListener('change', recompute));

swapBtn.addEventListener('click', () => {
  const tmp = inputA.value;
  inputA.value = inputB.value;
  inputB.value = tmp;
  recompute();
});

clearBtn.addEventListener('click', () => {
  inputA.value = '';
  inputB.value = '';
  recompute();
});

sampleBtn.addEventListener('click', () => {
  inputA.value = `Text Diff Checker

Compare two blocks of text and see exactly what changed.
It works entirely in your browser, so nothing is ever uploaded.
Supports word-level and character-level comparisons.

Made by Bruno Vieira.`;
  inputB.value = `Text Diff Checker

Compare two pieces of text and see precisely what changed.
It runs entirely in your browser, so nothing is ever uploaded to a server.
Supports word-level and character-level comparisons, plus file uploads.

Thanks for stopping by!

Made by Bruno Vieira.`;
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
loadFile(fileA, inputA);
loadFile(fileB, inputB);

diffTable.addEventListener('click', (e) => {
  const exp = e.target.closest('.row-expander');
  if (!exp) return;
  const gid = exp.dataset.target;
  diffTable.querySelectorAll(`tr[data-group="${gid}"]`).forEach((tr) => tr.classList.add('revealed'));
  exp.remove();
});

copyDiffBtn.addEventListener('click', async () => {
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
  const text = lines.join('\n');
  try {
    await navigator.clipboard.writeText(text);
    copyDiffBtn.textContent = 'Copied!';
    copyDiffBtn.classList.add('copied');
    setTimeout(() => {
      copyDiffBtn.textContent = 'Copy diff';
      copyDiffBtn.classList.remove('copied');
    }, 1400);
  } catch (err) {
    copyDiffBtn.textContent = 'Copy failed';
    setTimeout(() => { copyDiffBtn.textContent = 'Copy diff'; }, 1400);
  }
});

recompute();
