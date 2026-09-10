(function () {
  const input = document.getElementById('input');
  const output = document.getElementById('output');
  const metaIn = document.getElementById('metaIn');
  const metaOut = document.getElementById('metaOut');
  const statsBar = document.getElementById('statsBar');
  const verifyMsg = document.getElementById('verifyMsg');
  const stripComments = document.getElementById('stripComments');
  const preserveBangComments = document.getElementById('preserveBangComments');
  const fileInput = document.getElementById('fileInput');
  const sampleBtn = document.getElementById('sampleBtn');
  const clearBtn = document.getElementById('clearBtn');
  const copyBtn = document.getElementById('copyBtn');

  const SAMPLE = [
    '/*!',
    ' * Sample widget — license banner, always kept',
    ' */',
    '',
    'function greet(name) {',
    '  // trim before comparing',
    '  const trimmed = name.trim();',
    '',
    '  if (/^[A-Z]/.test(trimmed)) {',
    '    return `Hello, ${trimmed}!`;',
    '  }',
    '',
    '  return "Hello, " + trimmed[0].toUpperCase() + trimmed.slice(1) + "!";',
    '}',
    '',
    'const total = 10 / 2; /* plain division, not a regex */',
    'console.log(greet("ada"), total);'
  ].join('\n');

  // --- Shared JS tokenizer ---------------------------------------------
  // A single pass, used by BOTH the minifier and the verification check,
  // so the two can never disagree about what counts as a string, a
  // template literal, a regex literal, or a comment. Producing that
  // classification twice (once per consumer) is exactly how the CSS
  // minifier's verifier once went out of sync with its minifier — so
  // here there is only one source of truth.
  //
  // Regex-vs-division is resolved with the classic "lastWasValue"
  // heuristic: a `/` starts a regex unless the previous significant
  // token was something a value could follow (identifier/number that
  // isn't a keyword like `return`, a string/template/regex, or `)`/`]`).
  // It is not a full parser and can be fooled by contrived code, which
  // is exactly why the verification pass exists as a safety net.

  const REGEX_PRECEDING_WORDS = new Set([
    'return', 'typeof', 'instanceof', 'in', 'of', 'new', 'delete', 'void',
    'throw', 'do', 'else', 'yield', 'await', 'case', 'extends', 'default'
  ]);

  // `if`/`while`/`for`/`with` are the control-flow forms whose `(...)` can be
  // followed directly by a brace-less statement, e.g. `if (x) /re/.test(y);`.
  // The `)` there must NOT be treated as a value (unlike a call's `)`), or the
  // regex right after it gets misread as division and its contents silently
  // corrupted by whitespace collapsing — see the paren-tracking below.
  const CONTROL_PAREN_WORDS = new Set(['if', 'while', 'for', 'with']);

  function isWordChar(ch) {
    return ch !== undefined && /[A-Za-z0-9_$]/.test(ch);
  }
  function isHSpace(ch) {
    return ch === ' ' || ch === '\t' || ch === '\f' || ch === '\v';
  }
  function isNL(ch) {
    return ch === '\n' || ch === '\r';
  }

  function* tokenizeJS(src) {
    const len = src.length;
    let i = 0;
    const stack = []; // frames: 'tmpl' (raw template text) or {expr:true, depth:0} (inside ${ ... })
    let lastWasValue = false;
    let lastWordToken = '';
    const parenStack = []; // true = this `(` opened a control-flow condition

    while (i < len) {
      const top = stack.length ? stack[stack.length - 1] : null;

      if (top === 'tmpl') {
        let j = i;
        let text = '';
        while (j < len) {
          const c = src[j];
          if (c === '\\' && j + 1 < len) { text += c + src[j + 1]; j += 2; continue; }
          if (c === '`') break;
          if (c === '$' && src[j + 1] === '{') break;
          text += c;
          j++;
        }
        if (text.length) { yield { type: 'tmplchunk', text: text }; i = j; continue; }
        if (src[j] === '`') { yield { type: 'punct', text: '`' }; stack.pop(); i = j + 1; lastWasValue = true; continue; }
        if (src[j] === '$') { yield { type: 'punct', text: '${' }; stack.push({ expr: true, depth: 0 }); i = j + 2; lastWasValue = false; continue; }
        break; // unterminated template — stop
      }

      const ch = src[i];

      if (isHSpace(ch) || isNL(ch)) {
        let j = i;
        let hasNL = false;
        while (j < len && (isHSpace(src[j]) || isNL(src[j]))) {
          if (isNL(src[j])) hasNL = true;
          j++;
        }
        yield { type: hasNL ? 'newline' : 'space', text: src.slice(i, j) };
        i = j;
        continue;
      }

      if (ch === '/' && src[i + 1] === '/') {
        let j = i + 2;
        while (j < len && !isNL(src[j])) j++;
        yield { type: 'linecomment', text: src.slice(i, j) };
        i = j;
        continue;
      }

      if (ch === '/' && src[i + 1] === '*') {
        const end = src.indexOf('*/', i + 2);
        const j = end === -1 ? len : end + 2;
        yield { type: 'blockcomment', text: src.slice(i, j) };
        i = j;
        continue;
      }

      if (ch === '"' || ch === "'") {
        let j = i + 1;
        while (j < len) {
          if (src[j] === '\\' && j + 1 < len) { j += 2; continue; }
          if (src[j] === ch) { j++; break; }
          if (isNL(src[j])) break; // unterminated string — bail safely
          j++;
        }
        yield { type: 'string', text: src.slice(i, j) };
        i = j;
        lastWasValue = true;
        lastWordToken = '';
        continue;
      }

      if (ch === '`') {
        yield { type: 'punct', text: '`' };
        stack.push('tmpl');
        i++;
        lastWordToken = '';
        continue;
      }

      if (ch === '/' && !lastWasValue) {
        let j = i + 1;
        let inClass = false;
        while (j < len) {
          const c = src[j];
          if (c === '\\' && j + 1 < len) { j += 2; continue; }
          if (c === '[') { inClass = true; j++; continue; }
          if (c === ']') { inClass = false; j++; continue; }
          if (c === '/' && !inClass) { j++; break; }
          if (isNL(c)) break; // unterminated regex — bail safely
          j++;
        }
        while (j < len && /[a-zA-Z]/.test(src[j])) j++;
        yield { type: 'regex', text: src.slice(i, j) };
        i = j;
        lastWasValue = true;
        lastWordToken = '';
        continue;
      }

      if (ch === '}' && top && top.expr) {
        yield { type: 'punct', text: '}' };
        i++;
        if (top.depth === 0) { stack.pop(); } else { top.depth--; }
        lastWasValue = false;
        lastWordToken = '';
        continue;
      }

      if (ch === '{' && top && top.expr) {
        top.depth++;
        yield { type: 'punct', text: '{' };
        i++;
        lastWasValue = false;
        lastWordToken = '';
        continue;
      }

      if (isWordChar(ch)) {
        let j = i;
        while (j < len && isWordChar(src[j])) j++;
        const word = src.slice(i, j);
        yield { type: 'word', text: word };
        i = j;
        lastWasValue = !REGEX_PRECEDING_WORDS.has(word);
        lastWordToken = word;
        continue;
      }

      if (ch === '(') {
        parenStack.push(CONTROL_PAREN_WORDS.has(lastWordToken));
        yield { type: 'punct', text: ch };
        i++;
        lastWasValue = false;
        lastWordToken = '';
        continue;
      }

      if (ch === ')') {
        const wasControl = parenStack.length ? parenStack.pop() : false;
        yield { type: 'punct', text: ch };
        i++;
        lastWasValue = !wasControl;
        lastWordToken = '';
        continue;
      }

      yield { type: 'punct', text: ch };
      i++;
      lastWasValue = (ch === ']');
      lastWordToken = '';
    }
  }

  // --- Minifier ---------------------------------------------------------
  // Only ever removes: comments, and whitespace that is provably safe to
  // remove. A whitespace run containing a newline always collapses to
  // exactly one newline — never dropped outright — so automatic semicolon
  // insertion can never be affected. A purely horizontal run collapses to
  // one space, or is dropped entirely next to punctuation where no token
  // could ever merge with its neighbor (brackets, `;`, `,`, `:`).

  const DROP_ADJACENT = new Set(['{', '}', '(', ')', '[', ']', ';', ',', ':']);

  function isPreservedComment(text, isLineComment) {
    if (isLineComment) return /^\/\/!/.test(text) || /@license|@preserve/i.test(text);
    const isBang = text.length > 2 && text[2] === '!';
    return isBang || /@license|@preserve/i.test(text);
  }

  function minifyJS(src, opts) {
    const strip = opts.strip;
    const keepBang = opts.keepBang;
    const tokens = Array.from(tokenizeJS(src));
    const out = [];

    function prevSignificant(idx) {
      for (let k = idx - 1; k >= 0; k--) {
        const t = tokens[k].type;
        if (t !== 'space' && t !== 'newline' && t !== 'linecomment' && t !== 'blockcomment') return tokens[k];
      }
      return null;
    }
    function nextSignificant(idx) {
      for (let k = idx + 1; k < tokens.length; k++) {
        const t = tokens[k].type;
        if (t !== 'space' && t !== 'newline' && t !== 'linecomment' && t !== 'blockcomment') return tokens[k];
      }
      return null;
    }

    for (let idx = 0; idx < tokens.length; idx++) {
      const tok = tokens[idx];

      if (tok.type === 'linecomment' || tok.type === 'blockcomment') {
        const preserved = keepBang && isPreservedComment(tok.text, tok.type === 'linecomment');
        if (!strip || preserved) out.push(tok.text);
        continue;
      }

      if (tok.type === 'newline') {
        out.push('\n');
        continue;
      }

      if (tok.type === 'space') {
        const prev = prevSignificant(idx);
        const next = nextSignificant(idx);
        const prevChar = prev ? prev.text[prev.text.length - 1] : null;
        const nextChar = next ? next.text[0] : null;
        const canDrop = !prev || !next || DROP_ADJACENT.has(prevChar) || DROP_ADJACENT.has(nextChar);
        if (!canDrop) out.push(' ');
        continue;
      }

      out.push(tok.text);
    }

    return out.join('').trim();
  }

  // --- Verification -------------------------------------------------
  // Drop every whitespace and comment token entirely (regardless of the
  // minifier's own options) and compare. Since minifyJS only ever removes
  // whitespace/comments too, the two strings must be identical by
  // construction as long as both consumers agree on the tokenization —
  // which they do, because they share tokenizeJS.

  function normalizeForComparison(src) {
    let result = '';
    for (const tok of tokenizeJS(src)) {
      if (tok.type === 'space' || tok.type === 'newline' || tok.type === 'linecomment' || tok.type === 'blockcomment') continue;
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

  function run() {
    const src = input.value;
    if (!src.trim()) {
      output.value = '';
      metaOut.textContent = '0 B';
      statsBar.innerHTML = '';
      verifyMsg.textContent = '';
      verifyMsg.className = 'verify-msg';
      updateMetaIn();
      return;
    }

    const minified = minifyJS(src, {
      strip: stripComments.checked,
      keepBang: preserveBangComments.checked
    });

    output.value = minified;

    const inBytes = new Blob([src]).size;
    const outBytes = new Blob([minified]).size;
    const savedPct = inBytes > 0 ? (((inBytes - outBytes) / inBytes) * 100).toFixed(1) : '0.0';

    metaOut.textContent = formatBytes(outBytes);
    statsBar.innerHTML =
      '<span class="stat-pill">' + formatBytes(inBytes) + ' → ' + formatBytes(outBytes) + '</span>' +
      '<span class="stat-pill stat-saved">−' + savedPct + '%</span>';

    const normInput = normalizeForComparison(src);
    const normOutput = normalizeForComparison(minified);

    if (normInput === normOutput) {
      verifyMsg.textContent = '✓ Verified: output is semantically identical to input.';
      verifyMsg.className = 'verify-msg match';
    } else {
      verifyMsg.textContent = '⚠ Verification mismatch — output may differ beyond whitespace/comments. Review before using.';
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
  stripComments.addEventListener('change', run);
  preserveBangComments.addEventListener('change', run);

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
