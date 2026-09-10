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
    '/* Card component */',
    '.card {',
    '  display : flex;',
    '  flex-direction:   column;',
    '  padding: 16px  24px;',
    '  border: 1px solid #ddd7c8;;',
    '  border-radius: 12px;',
    '}',
    '',
    '.card > .card-title {',
    '  font-weight: 700;',
    '  content: "  keep this spacing  ";',
    '}',
    '',
    '/*! preserved: license banner */',
    '.card:hover { box-shadow: 0 2px 8px rgba(0,0,0,.1); }'
  ].join('\n');

  // --- Tokenizer-based minifier -------------------------------------
  // Walks the CSS character by character, tracking whether we're inside
  // a string, a comment, or plain code. This avoids regex-based
  // minifiers' classic failure mode: accidentally "minifying" inside
  // string literals (e.g. content: " / "; or url("a;b.css")) or breaking
  // on nested/odd comment placement. Every character that is kept is
  // copied verbatim - we never rewrite values, reorder rules, or alter
  // anything other than removing safe whitespace and (optionally)
  // comments.

  function minifyCSS(css, opts) {
    const strip = opts.strip;
    const keepBang = opts.keepBang;
    let out = [];
    let i = 0;
    const len = css.length;

    let inString = null;     // ' or " when inside a string literal
    let lastMeaningful = ''; // last non-removed char written to out

    function isWhitespace(ch) {
      return ch === ' ' || ch === '\t' || ch === '\n' || ch === '\r' || ch === '\f';
    }

    while (i < len) {
      const ch = css[i];

      if (inString) {
        out.push(ch);
        if (ch === '\\' && i + 1 < len) {
          out.push(css[i + 1]);
          i += 2;
          continue;
        }
        if (ch === inString) {
          inString = null;
        }
        i++;
        continue;
      }

      if (ch === '"' || ch === "'") {
        inString = ch;
        out.push(ch);
        i++;
        continue;
      }

      if (ch === '/' && css[i + 1] === '*') {
        const end = css.indexOf('*/', i + 2);
        const commentBody = end === -1 ? css.slice(i) : css.slice(i, end + 2);
        const isBang = commentBody.length > 2 && commentBody[2] === '!';
        const looksLikeConditional = /\[if|<!--|-->|mso|outlook/i.test(commentBody);

        const keepThis = !strip || (keepBang && (isBang || looksLikeConditional));

        if (keepThis) {
          out.push(commentBody);
          lastMeaningful = commentBody.slice(-1);
        }
        i = end === -1 ? len : end + 2;
        continue;
      }

      // Whitespace: collapse runs to a single space, but drop entirely
      // around characters where it is provably safe (after/before
      // { } ; : , and at start/end of stripped regions).
      if (isWhitespace(ch)) {
        let j = i;
        while (j < len && isWhitespace(css[j])) j++;
        const next = css[j];

        const prevChar = lastMeaningful;
        const dropBefore = prevChar === '' || '{};:,>+~('.indexOf(prevChar) !== -1;
        const dropAfter = next === undefined || '{};,>+~)'.indexOf(next) !== -1 || next === ':' || next === '!';

        if (!(dropBefore || dropAfter)) {
          out.push(' ');
          lastMeaningful = ' ';
        }
        i = j;
        continue;
      }

      // Trailing semicolon before a closing brace is safe to drop
      if (ch === ';') {
        let j = i + 1;
        while (j < len && isWhitespace(css[j])) j++;
        if (css[j] === '}') {
          i++;
          continue;
        }
        out.push(ch);
        lastMeaningful = ch;
        i++;
        continue;
      }

      out.push(ch);
      lastMeaningful = ch;
      i++;
    }

    return out.join('').trim();
  }

  // --- Semantic verification -----------------------------------------
  // Strip all whitespace and comments from BOTH the original and the
  // minified result, then compare. If they don't match, something
  // other than whitespace/comments changed, which should never happen.

  function normalizeForComparison(css) {
    let result = '';
    let i = 0;
    const len = css.length;
    let inString = null;
    while (i < len) {
      const ch = css[i];
      if (inString) {
        result += ch;
        if (ch === '\\' && i + 1 < len) { result += css[i + 1]; i += 2; continue; }
        if (ch === inString) inString = null;
        i++;
        continue;
      }
      if (ch === '"' || ch === "'") { inString = ch; result += ch; i++; continue; }
      if (ch === '/' && css[i + 1] === '*') {
        const end = css.indexOf('*/', i + 2);
        i = end === -1 ? len : end + 2;
        continue;
      }
      if (ch === ' ' || ch === '\t' || ch === '\n' || ch === '\r' || ch === '\f') { i++; continue; }
      // A semicolon immediately before a closing brace (ignoring
      // whitespace/comments in between) is a no-op the minifier drops,
      // so it must not count as a meaningful difference here either.
      if (ch === ';') {
        let j = i + 1;
        while (j < len) {
          if (css[j] === ' ' || css[j] === '\t' || css[j] === '\n' || css[j] === '\r' || css[j] === '\f') { j++; continue; }
          if (css[j] === '/' && css[j + 1] === '*') {
            const end = css.indexOf('*/', j + 2);
            j = end === -1 ? len : end + 2;
            continue;
          }
          break;
        }
        if (css[j] === '}') {
          i++;
          continue;
        }
      }
      result += ch;
      i++;
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

    const minified = minifyCSS(css, {
      strip: stripComments.checked,
      keepBang: preserveBangComments.checked
    });

    output.value = minified;

    const inBytes = new Blob([css]).size;
    const outBytes = new Blob([minified]).size;
    const savedPct = inBytes > 0 ? (((inBytes - outBytes) / inBytes) * 100).toFixed(1) : '0.0';

    metaOut.textContent = formatBytes(outBytes);
    statsBar.innerHTML =
      '<span class="stat-pill">' + formatBytes(inBytes) + ' → ' + formatBytes(outBytes) + '</span>' +
      '<span class="stat-pill stat-saved">−' + savedPct + '%</span>';

    // Comments are stripped from both sides regardless of the strip
    // option, so this check only ever validates whitespace handling
    // plus whichever comments the strip option actually removed.
    const normInput = normalizeForComparison(css);
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
