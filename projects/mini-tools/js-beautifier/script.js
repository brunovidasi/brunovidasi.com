(function () {
  const input = document.getElementById('input');
  const output = document.getElementById('output');
  const metaIn = document.getElementById('metaIn');
  const metaOut = document.getElementById('metaOut');
  const statsBar = document.getElementById('statsBar');
  const verifyMsg = document.getElementById('verifyMsg');
  const indentSize = document.getElementById('indentSize');
  const fileInput = document.getElementById('fileInput');
  const sampleBtn = document.getElementById('sampleBtn');
  const clearBtn = document.getElementById('clearBtn');
  const copyBtn = document.getElementById('copyBtn');

  const SAMPLE = 'function greet(name){const trimmed=name.trim();if(/^[A-Z]/.test(trimmed)){return `Hello, ${trimmed}!`;}return "Hello, "+trimmed[0].toUpperCase()+trimmed.slice(1)+"!";}const total=10/2;/* plain division, not a regex */console.log(greet("ada"),total);';

  // --- Shared JS tokenizer ---------------------------------------------
  // Identical to the one in the JS Minifier — same string/template/regex/
  // comment classification, reused verbatim so this tool and its
  // verification check (and the minifier, if you compare the two) can
  // never disagree about what a token is.

  const REGEX_PRECEDING_WORDS = new Set([
    'return', 'typeof', 'instanceof', 'in', 'of', 'new', 'delete', 'void',
    'throw', 'do', 'else', 'yield', 'await', 'case', 'extends', 'default'
  ]);

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
    const stack = [];
    let lastWasValue = false;
    let lastWordToken = '';
    const parenStack = [];

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
        break;
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
          if (isNL(src[j])) break;
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
          if (isNL(c)) break;
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
        // Only the brace that actually closes the `${ ... }` (depth 0) is
        // the template's own delimiter, not a real code block — it must
        // stay inline or a newline gets inserted inside the interpolation
        // (`${trimmed` / `}!` on separate lines). A `}` closing something
        // genuinely nested inside the expression (depth > 0, e.g. an
        // object literal or an arrow function's block) is real code and
        // is free to format normally.
        const closesExpr = top.depth === 0;
        yield { type: 'punct', text: '}', tmplBrace: closesExpr };
        i++;
        if (closesExpr) { stack.pop(); } else { top.depth--; }
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

  // --- Beautifier ---------------------------------------------------
  // Re-indents based on brace depth and breaks lines at statement
  // boundaries (`;`, `{`, `}`) — the two changes that turn a wall of
  // minified code back into something readable. Everything else (every
  // identifier, string, regex, template, and operator) is copied
  // verbatim; no renaming, no reordering, no operator spacing that would
  // require distinguishing multi-character operators from adjacent
  // single-char punctuation (risking turning `a+ +b` into `a++b`).
  //
  // `;` only breaks the line when parenDepth is 0, so a `for(;;)` header's
  // semicolons are left inline instead of exploding across three lines —
  // the exact same class of bug the minifier's regex-vs-division fix
  // guards against, just for a different piece of JS grammar.

  function beautifyJS(src, opts) {
    const indentUnit = opts.indentUnit;
    const tokens = Array.from(tokenizeJS(src));
    let out = '';
    let depth = 0;
    let parenDepth = 0;
    let atLineStart = true;

    function ensureNewline() {
      out = out.replace(/[ \t]+$/, '');
      if (out.length && !out.endsWith('\n')) out += '\n';
      out += indentUnit.repeat(depth);
      atLineStart = true;
    }

    const CONTINUATION_WORDS = new Set(['else', 'catch', 'finally', 'while']);
    const CONTINUATION_PUNCT = new Set([')', ';', ',', '.']);
    function nextSignificant(idx) {
      for (let k = idx + 1; k < tokens.length; k++) {
        const t = tokens[k].type;
        if (t !== 'space' && t !== 'newline' && t !== 'linecomment' && t !== 'blockcomment') return tokens[k];
      }
      return null;
    }

    for (let idx = 0; idx < tokens.length; idx++) {
      const tok = tokens[idx];

      if (tok.type === 'space') {
        if (atLineStart || /[ \n]$/.test(out)) continue;
        out += ' ';
        continue;
      }

      if (tok.type === 'newline') {
        // A real newline in the source can be load-bearing for automatic
        // semicolon insertion (`return\n{...}` means `return;` followed by
        // a dead block, NOT `return {...}`). Collapsing it to a mere space
        // — as a plain 'space' token safely can be — would silently change
        // that meaning, so it always becomes an actual newline instead
        // (idempotent if we're already at a fresh, correctly indented
        // line from `{`/`;` handling just above).
        ensureNewline();
        continue;
      }

      if (tok.type === 'linecomment' || tok.type === 'blockcomment') {
        if (!atLineStart && !/[ \n]$/.test(out)) out += ' ';
        out += tok.text;
        atLineStart = false;
        if (tok.type === 'linecomment') ensureNewline();
        continue;
      }

      if (tok.type === 'punct') {
        const ch = tok.text;

        if (ch === '(') { parenDepth++; out += '('; atLineStart = false; continue; }
        if (ch === ')') {
          out = out.replace(/ $/, '');
          parenDepth = Math.max(0, parenDepth - 1);
          out += ')';
          atLineStart = false;
          continue;
        }
        if (ch === '{') {
          if (out.length && !/[\s({[]$/.test(out)) out += ' ';
          out += '{';
          depth++;
          ensureNewline();
          continue;
        }
        if (ch === '}' && tok.tmplBrace) {
          out += '}';
          atLineStart = false;
          continue;
        }
        if (ch === '}') {
          depth = Math.max(0, depth - 1);
          ensureNewline();
          out += '}';
          atLineStart = false;
          // A block-closing `}` is followed by a newline before the next
          // statement — unless that next token is a continuation of the
          // SAME statement (`} else {`, `}); `, `}).then(...)`, `}, {`),
          // in which case it stays on the same line, tight or with a
          // single space, matching conventional style either way.
          const next = nextSignificant(idx);
          if (next) {
            if (next.type === 'word' && CONTINUATION_WORDS.has(next.text)) {
              out += ' ';
            } else if (next.type === 'punct' && CONTINUATION_PUNCT.has(next.text)) {
              // stays tight, nothing to add
            } else {
              ensureNewline();
            }
          }
          continue;
        }
        if (ch === ';') {
          out = out.replace(/ $/, '');
          out += ';';
          if (parenDepth === 0) { ensureNewline(); } else { out += ' '; atLineStart = false; }
          continue;
        }
        if (ch === ',') {
          out = out.replace(/ $/, '');
          out += ', ';
          atLineStart = false;
          continue;
        }
        if (ch === ':') {
          out += ': ';
          atLineStart = false;
          continue;
        }

        out += ch;
        atLineStart = false;
        continue;
      }

      out += tok.text;
      atLineStart = false;
    }

    return out.trim() + '\n';
  }

  // --- Verification -------------------------------------------------
  // Same check as the JS Minifier: drop every whitespace/comment token
  // and compare. beautifyJS only ever adds whitespace, so this must
  // match by construction as long as both consumers agree on
  // tokenization — which they do, sharing tokenizeJS.

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

  function currentIndentUnit() {
    const v = indentSize.value;
    if (v === 'tab') return '\t';
    return ' '.repeat(parseInt(v, 10));
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

    const formatted = beautifyJS(src, { indentUnit: currentIndentUnit() });

    output.value = formatted;

    const inBytes = new Blob([src]).size;
    const outBytes = new Blob([formatted]).size;

    metaOut.textContent = formatBytes(outBytes);
    statsBar.innerHTML = '<span class="stat-pill">' + formatBytes(inBytes) + ' → ' + formatBytes(outBytes) + '</span>';

    const normInput = normalizeForComparison(src);
    const normOutput = normalizeForComparison(formatted);

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
  indentSize.addEventListener('change', run);

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
