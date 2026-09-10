(function () {
  const input = document.getElementById('input');
  const output = document.getElementById('output');
  const metaIn = document.getElementById('metaIn');
  const metaOut = document.getElementById('metaOut');
  const statsBar = document.getElementById('statsBar');
  const verifyMsg = document.getElementById('verifyMsg');
  const indentSize = document.getElementById('indentSize');
  const oneSelectorPerLine = document.getElementById('oneSelectorPerLine');
  const fileInput = document.getElementById('fileInput');
  const sampleBtn = document.getElementById('sampleBtn');
  const clearBtn = document.getElementById('clearBtn');
  const copyBtn = document.getElementById('copyBtn');

  const SAMPLE = '/*! Card component */.card,.card--compact{display:flex;flex-direction:column;padding:16px 24px;border:1px solid #ddd7c8;border-radius:12px}.card>.card-title{font-weight:700;content:"  keep this spacing  "}.card:hover{box-shadow:0 2px 8px rgba(0,0,0,.1)}@media (max-width:600px){.card{padding:8px}}';

  // --- Tokenizer ---------------------------------------------------------
  // Same string/comment-aware character walk as the CSS Minifier, just
  // exposed as a generator so this tool and its verification check share
  // one classification of what is a string, a comment, or plain text.

  function* cssTokenize(src) {
    let i = 0;
    const len = src.length;
    while (i < len) {
      const ch = src[i];
      if (ch === ' ' || ch === '\t' || ch === '\n' || ch === '\r' || ch === '\f') {
        let j = i;
        while (j < len && (src[j] === ' ' || src[j] === '\t' || src[j] === '\n' || src[j] === '\r' || src[j] === '\f')) j++;
        yield { type: 'space', text: src.slice(i, j) };
        i = j;
        continue;
      }
      if (ch === '"' || ch === "'") {
        let j = i + 1;
        while (j < len) {
          if (src[j] === '\\' && j + 1 < len) { j += 2; continue; }
          if (src[j] === ch) { j++; break; }
          j++;
        }
        yield { type: 'string', text: src.slice(i, j) };
        i = j;
        continue;
      }
      if (ch === '/' && src[i + 1] === '*') {
        const end = src.indexOf('*/', i + 2);
        const j = end === -1 ? len : end + 2;
        yield { type: 'comment', text: src.slice(i, j) };
        i = j;
        continue;
      }
      yield { type: 'char', text: ch };
      i++;
    }
  }

  // Re-scan a small, already-assembled chunk of segment text (selector or
  // declaration) to find top-level ':' / ',' — "top-level" meaning not
  // inside a string, a comment, or parens/brackets (so `:not(:hover)` and
  // `rgba(0, 0, 0, .5)` are never mistaken for declaration/selector
  // punctuation).

  function forEachTopLevelChar(text, cb) {
    let i = 0;
    const len = text.length;
    let depth = 0;
    while (i < len) {
      const ch = text[i];
      if (ch === '"' || ch === "'") {
        let j = i + 1;
        while (j < len) { if (text[j] === '\\' && j + 1 < len) { j += 2; continue; } if (text[j] === ch) { j++; break; } j++; }
        i = j;
        continue;
      }
      if (ch === '/' && text[i + 1] === '*') {
        const end = text.indexOf('*/', i + 2);
        i = end === -1 ? len : end + 2;
        continue;
      }
      if (ch === '(' || ch === '[') { depth++; i++; continue; }
      if (ch === ')' || ch === ']') { depth = Math.max(0, depth - 1); i++; continue; }
      if (depth === 0) cb(ch, i);
      i++;
    }
  }

  function findFirstTopLevelColon(text) {
    let idx = -1;
    forEachTopLevelChar(text, function (ch, i) { if (ch === ':' && idx === -1) idx = i; });
    return idx;
  }

  function splitTopLevelCommas(text) {
    const positions = [];
    forEachTopLevelChar(text, function (ch, i) { if (ch === ',') positions.push(i); });
    if (!positions.length) return [text];
    const parts = [];
    let start = 0;
    for (const p of positions) { parts.push(text.slice(start, p)); start = p + 1; }
    parts.push(text.slice(start));
    return parts;
  }

  // --- Formatter -----------------------------------------------------
  // Buffers characters into a "segment" — a selector/prelude, a single
  // declaration, or a standalone comment — until it hits a top-level `{`,
  // `;`, or `}` (top-level meaning outside any parens/brackets, so a
  // for-real never-happens-in-CSS edge case aside, structural characters
  // inside `url(...)` or `:not(...)` never trigger early). Once a segment
  // is complete its *kind* is known from what terminated it, which is
  // exactly the ambiguity that trips up naive regex formatters: the same
  // `:` character means something different in `a:hover{` (leave alone)
  // than in `color:red;` (needs a space). Content itself is never
  // rewritten — only whitespace is added, so nothing here can change
  // what the CSS means.

  function formatCSS(src, opts) {
    const indentUnit = opts.indentUnit;
    const splitSelectors = opts.oneSelectorPerLine;
    const tokens = Array.from(cssTokenize(src));
    let out = '';
    let blockDepth = 0;
    let nestDepth = 0;
    let segment = [];

    function indent(d) { return indentUnit.repeat(d); }
    function segmentText() { return segment.map(function (t) { return t.text; }).join(''); }
    // Collapse whitespace *tokens* to a single space each, leaving every
    // string/comment/char token's own text untouched — unlike a blind
    // regex over the joined text, this can never reach inside a string
    // literal's content (e.g. `content: "  two  spaces  "`).
    function collapseSegment() {
      let result = '';
      for (const t of segment) result += t.type === 'space' ? ' ' : t.text;
      return result.trim();
    }

    function finalizeDeclarationLike() {
      let text = collapseSegment();
      if (!text) return;
      const colonIdx = findFirstTopLevelColon(text);
      if (colonIdx !== -1) {
        const prop = text.slice(0, colonIdx).trim();
        const value = text.slice(colonIdx + 1).trim();
        text = prop + ': ' + value;
      }
      out += text + ';\n' + indent(blockDepth);
    }

    function finalizeSelectorLike() {
      let text = collapseSegment();
      if (splitSelectors) {
        const parts = splitTopLevelCommas(text).map(function (s) { return s.trim(); }).filter(Boolean);
        if (parts.length > 1) text = parts.join(',\n' + indent(blockDepth));
      }
      out += text + ' {\n';
      blockDepth++;
      out += indent(blockDepth);
    }

    for (let idx = 0; idx < tokens.length; idx++) {
      const tok = tokens[idx];

      if (tok.type === 'char' && (tok.text === '(' || tok.text === '[')) { nestDepth++; segment.push(tok); continue; }
      if (tok.type === 'char' && (tok.text === ')' || tok.text === ']')) { nestDepth = Math.max(0, nestDepth - 1); segment.push(tok); continue; }

      if (tok.type === 'comment' && nestDepth === 0) {
        const hasRealContent = segment.some(function (t) { return t.type !== 'space'; });
        if (!hasRealContent) {
          out = out.replace(/[ \t]*$/, '');
          out += (out && !out.endsWith('\n') ? '\n' : '') + indent(blockDepth) + tok.text + '\n' + indent(blockDepth);
          segment = [];
          continue;
        }
        segment.push(tok);
        continue;
      }

      if (tok.type === 'char' && nestDepth === 0 && tok.text === '{') {
        finalizeSelectorLike();
        segment = [];
        continue;
      }

      if (tok.type === 'char' && nestDepth === 0 && tok.text === ';') {
        finalizeDeclarationLike();
        segment = [];
        continue;
      }

      if (tok.type === 'char' && nestDepth === 0 && tok.text === '}') {
        finalizeDeclarationLike();
        blockDepth = Math.max(0, blockDepth - 1);
        out = out.replace(/[ \t]*$/, '');
        out += indent(blockDepth) + '}\n';
        if (blockDepth === 0) out += '\n';
        out += indent(blockDepth);
        segment = [];
        continue;
      }

      segment.push(tok);
    }

    const leftover = segmentText().trim();
    if (leftover) out += leftover;

    return out.replace(/[ \t]+\n/g, '\n').replace(/\n{3,}/g, '\n\n').trim() + '\n';
  }

  // --- Verification ---------------------------------------------------
  // Same rule as the CSS Minifier's check: strip all whitespace and
  // comments, and treat a `;` immediately before `}` as insignificant
  // (the formatter can add one there for consistent style), then compare.

  function normalizeForComparison(css) {
    const tokens = Array.from(cssTokenize(css)).filter(function (t) {
      return t.type !== 'space' && t.type !== 'comment';
    });
    let result = '';
    for (let i = 0; i < tokens.length; i++) {
      const tok = tokens[i];
      // A `;` immediately before a `}` (once whitespace/comments are gone)
      // is a no-op the formatter can insert or remove freely — checked at
      // the token level, never on the joined string, so a string literal
      // that happens to contain the literal text ";}" is never mistaken
      // for this structural pattern.
      if (tok.type === 'char' && tok.text === ';' && tokens[i + 1] && tokens[i + 1].type === 'char' && tokens[i + 1].text === '}') {
        continue;
      }
      result += tok.text;
    }
    return result;
  }

  function formatBytes(n) {
    return n.toLocaleString() + ' B';
  }

  function updateMetaIn() {
    metaIn.textContent = formatBytes(new Blob([input.value]).size);
  }

  function currentIndentUnit() {
    const v = indentSize.value;
    if (v === 'tab') return '\t';
    return ' '.repeat(parseInt(v, 10));
  }

  function run() {
    const css = input.value;
    if (!css.trim()) {
      output.value = '';
      metaOut.textContent = '0 B';
      statsBar.innerHTML = '';
      verifyMsg.textContent = '';
      verifyMsg.className = 'verify-msg';
      updateMetaIn();
      return;
    }

    const formatted = formatCSS(css, {
      indentUnit: currentIndentUnit(),
      oneSelectorPerLine: oneSelectorPerLine.checked
    });

    output.value = formatted;

    const inBytes = new Blob([css]).size;
    const outBytes = new Blob([formatted]).size;

    metaOut.textContent = formatBytes(outBytes);
    statsBar.innerHTML = '<span class="stat-pill">' + formatBytes(inBytes) + ' → ' + formatBytes(outBytes) + '</span>';

    const normInput = normalizeForComparison(css);
    const normOutput = normalizeForComparison(formatted);

    if (normInput === normOutput) {
      verifyMsg.textContent = '✓ Verified: output is semantically identical to input.';
      verifyMsg.className = 'verify-msg match';
    } else {
      verifyMsg.textContent = '⚠ Verification mismatch — output may differ beyond whitespace. Review before using.';
      verifyMsg.className = 'verify-msg warn';
    }

    updateMetaIn();
  }

  let debounceTimer = null;
  function scheduleRun() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(run, 150);
  }

  input.addEventListener('input', scheduleRun);
  indentSize.addEventListener('change', run);
  oneSelectorPerLine.addEventListener('change', run);

  clearBtn.addEventListener('click', function () {
    input.value = '';
    output.value = '';
    metaIn.textContent = '0 B';
    metaOut.textContent = '0 B';
    statsBar.innerHTML = '';
    verifyMsg.textContent = '';
    verifyMsg.className = 'verify-msg';
    input.focus();
  });

  sampleBtn.addEventListener('click', function () {
    input.value = SAMPLE;
    run();
  });

  copyBtn.addEventListener('click', function () {
    if (!output.value) return;
    navigator.clipboard.writeText(output.value).then(function () {
      copyBtn.textContent = 'Copied!';
      copyBtn.classList.add('copied');
      setTimeout(function () {
        copyBtn.textContent = 'Copy';
        copyBtn.classList.remove('copied');
      }, 1200);
    });
  });

  fileInput.addEventListener('change', function () {
    const file = fileInput.files && fileInput.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function () {
      input.value = String(reader.result);
      run();
    };
    reader.readAsText(file);
    fileInput.value = '';
  });

  updateMetaIn();
})();
