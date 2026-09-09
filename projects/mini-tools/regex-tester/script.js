(() => {
  const patternInput = document.getElementById('patternInput');
  const testInput = document.getElementById('testInput');
  const tsHighlight = document.getElementById('tsHighlight');
  const flagsBar = document.getElementById('flags');
  const patternError = document.getElementById('patternError');
  const matchBadge = document.getElementById('matchBadge');
  const matchesList = document.getElementById('matchesList');
  const groupsSection = document.getElementById('groupsSection');
  const groupsList = document.getElementById('groupsList');
  const libraryList = document.getElementById('libraryList');
  const cheatGroups = document.getElementById('cheatGroups');
  const copyPatternBtn = document.getElementById('copyPatternBtn');

  const COLORS = ['c1', 'c2', 'c3', 'c4', 'c5'];
  const colorVar = i => `var(--${COLORS[i % COLORS.length]})`;

  let activeFlags = new Set(['g']);

  // ---------------- common patterns library ----------------

  const LIBRARY = [
    { name: 'Email address', pattern: '[\\w.+-]+@[\\w-]+\\.[a-zA-Z]{2,}', flags: 'g',
      sample: 'Contact us at hello@example.com or support@my-site.co.uk.' },
    { name: 'URL', pattern: 'https?:\\/\\/[^\\s/$.?#].[^\\s]*', flags: 'g',
      sample: 'Visit https://example.com/path?q=1 or http://sub.example.org.' },
    { name: 'IPv4 address', pattern: '\\b(?:\\d{1,3}\\.){3}\\d{1,3}\\b', flags: 'g',
      sample: 'Server is at 192.168.0.1, gateway 10.0.0.1.' },
    { name: 'Hex color', pattern: '#(?:[0-9a-fA-F]{3}){1,2}\\b', flags: 'g',
      sample: 'Brand colors: #3d6b8a, #fff and #b06a4c.' },
    { name: 'Date (YYYY-MM-DD)', pattern: '(?<year>\\d{4})-(?<month>\\d{2})-(?<day>\\d{2})', flags: 'g',
      sample: 'Founded 2019-03-14, next release 2026-09-09.' },
    { name: 'Time (24h HH:MM)', pattern: '(?<hour>[01]\\d|2[0-3]):(?<minute>[0-5]\\d)', flags: 'g',
      sample: 'Doors open at 09:30, show starts 21:00.' },
    { name: 'US phone number', pattern: '\\(?\\d{3}\\)?[-.\\s]?\\d{3}[-.\\s]?\\d{4}', flags: 'g',
      sample: 'Call (415) 555-0134 or 415.555.0199.' },
    { name: 'HTML tag', pattern: '<\\/?[a-zA-Z][\\w-]*(?:\\s+[^<>]*)?>', flags: 'g',
      sample: '<div class="a"><span>hi</span></div>' },
    { name: 'Whitespace (leading/trailing)', pattern: '^\\s+|\\s+$', flags: 'gm',
      sample: '   trim me   \n   this line too   ' },
    { name: 'Integer', pattern: '-?\\d+\\b', flags: 'g',
      sample: 'Values: 42, -7, 1000 and 3.14.' },
    { name: 'Decimal number', pattern: '-?\\d+(?:\\.\\d+)?', flags: 'g',
      sample: 'Values: 42, -7.5, 1000 and 3.14159.' },
    { name: 'Username (3-16, alnum/_)', pattern: '^[a-zA-Z0-9_]{3,16}$', flags: '',
      sample: 'bruno_v99' },
    { name: 'Slug (kebab-case)', pattern: '^[a-z0-9]+(?:-[a-z0-9]+)*$', flags: '',
      sample: 'my-cool-blog-post' },
    { name: 'Duplicate words', pattern: '\\b(\\w+)\\s+\\1\\b', flags: 'gi',
      sample: 'This is is a test test of duplicate words.' },
  ];

  function renderLibrary() {
    libraryList.innerHTML = '';
    LIBRARY.forEach(item => {
      const btn = document.createElement('button');
      btn.className = 'lib-item';
      btn.type = 'button';
      btn.title = item.pattern;
      btn.innerHTML = `<div class="lib-name">${escapeHtml(item.name)}</div><div class="lib-pattern">/${escapeHtml(item.pattern)}/${item.flags}</div>`;
      btn.addEventListener('click', () => {
        patternInput.value = item.pattern;
        activeFlags = new Set(item.flags.split(''));
        syncFlagButtons();
        if (!testInput.value.trim()) testInput.value = item.sample;
        [...libraryList.children].forEach(c => c.classList.remove('active'));
        btn.classList.add('active');
        recompute();
      });
      libraryList.appendChild(btn);
    });
  }

  // ---------------- cheat sheet ----------------

  const CHEATSHEET = [
    { cat: 'Anchors', rows: [
      ['^', 'Start of string (or line, with m)'],
      ['$', 'End of string (or line, with m)'],
      ['\\b', 'Word boundary'],
      ['\\B', 'Not a word boundary'],
    ]},
    { cat: 'Character classes', rows: [
      ['.', 'Any character except newline'],
      ['\\d', 'Digit — [0-9]'],
      ['\\D', 'Non-digit'],
      ['\\w', 'Word character — [A-Za-z0-9_]'],
      ['\\W', 'Non-word character'],
      ['\\s', 'Whitespace'],
      ['\\S', 'Non-whitespace'],
      ['[abc]', 'Any of a, b, c'],
      ['[^abc]', 'None of a, b, c'],
      ['[a-z]', 'Range a to z'],
    ]},
    { cat: 'Quantifiers', rows: [
      ['*', '0 or more'],
      ['+', '1 or more'],
      ['?', '0 or 1'],
      ['{n}', 'Exactly n'],
      ['{n,}', 'n or more'],
      ['{n,m}', 'Between n and m'],
      ['*?', 'Lazy 0 or more'],
      ['+?', 'Lazy 1 or more'],
    ]},
    { cat: 'Groups & references', rows: [
      ['(...)', 'Capturing group'],
      ['(?:...)', 'Non-capturing group'],
      ['(?<name>...)', 'Named capturing group'],
      ['\\1', 'Backreference to group 1'],
      ['\\k<name>', 'Backreference to named group'],
      ['|', 'Alternation (or)'],
    ]},
    { cat: 'Lookaround', rows: [
      ['(?=...)', 'Lookahead'],
      ['(?!...)', 'Negative lookahead'],
      ['(?<=...)', 'Lookbehind'],
      ['(?<!...)', 'Negative lookbehind'],
    ]},
    { cat: 'Flags', rows: [
      ['g', 'Global — find all matches'],
      ['i', 'Case-insensitive'],
      ['m', 'Multiline — ^ $ per line'],
      ['s', 'Dot matches newline'],
      ['u', 'Unicode mode'],
      ['y', 'Sticky — match at lastIndex'],
    ]},
  ];

  function renderCheatsheet() {
    cheatGroups.innerHTML = '';
    CHEATSHEET.forEach(group => {
      const wrap = document.createElement('div');
      const title = document.createElement('div');
      title.className = 'cheat-cat-title';
      title.textContent = group.cat;
      wrap.appendChild(title);
      const rows = document.createElement('div');
      rows.className = 'cheat-rows';
      group.rows.forEach(([token, desc]) => {
        const btn = document.createElement('button');
        btn.className = 'cheat-row';
        btn.type = 'button';
        btn.innerHTML = `<span class="cheat-token">${escapeHtml(token)}</span><span class="cheat-desc">${escapeHtml(desc)}</span>`;
        btn.addEventListener('click', () => insertAtCaret(token));
        rows.appendChild(btn);
      });
      wrap.appendChild(rows);
      cheatGroups.appendChild(wrap);
    });
  }

  function insertAtCaret(token) {
    const el = patternInput;
    const start = el.selectionStart ?? el.value.length;
    const end = el.selectionEnd ?? el.value.length;
    el.value = el.value.slice(0, start) + token + el.value.slice(end);
    el.focus();
    const pos = start + token.length;
    el.setSelectionRange(pos, pos);
    recompute();
  }

  // ---------------- flags ----------------

  function syncFlagButtons() {
    [...flagsBar.children].forEach(btn => {
      btn.classList.toggle('active', activeFlags.has(btn.dataset.flag));
    });
  }

  flagsBar.addEventListener('click', e => {
    const btn = e.target.closest('.flag-btn');
    if (!btn) return;
    const f = btn.dataset.flag;
    if (activeFlags.has(f)) activeFlags.delete(f);
    else activeFlags.add(f);
    syncFlagButtons();
    recompute();
  });

  // ---------------- helpers ----------------

  function escapeHtml(str) {
    return str.replace(/[&<>"']/g, c => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[c]));
  }

  function buildRegex(pattern, flags) {
    let f = flags;
    if (!f.includes('d')) f += 'd';
    return new RegExp(pattern, f);
  }

  function collectMatches(pattern, flags, text) {
    const re = buildRegex(pattern, flags);
    const matches = [];
    if (flags.includes('g') || flags.includes('y')) {
      let m;
      let guard = 0;
      while ((m = re.exec(text)) !== null) {
        matches.push(m);
        guard++;
        if (guard > 5000) break;
        if (m[0] === '') re.lastIndex++;
        if (!flags.includes('g')) break;
      }
    } else {
      const m = re.exec(text);
      if (m) matches.push(m);
    }
    return matches;
  }

  function namedGroupNames(pattern) {
    const names = [];
    const re = /\(\?<([a-zA-Z_$][\w$]*)>/g;
    let m;
    while ((m = re.exec(pattern)) !== null) names.push(m[1]);
    return names;
  }

  // build a nested-interval tree for one match (whole match + all its groups)
  function buildIntervalTree(match) {
    const intervals = [];
    const [ms, me] = match.indices[0];
    intervals.push({ start: ms, end: me, type: 'match' });
    if (match.indices.length > 1) {
      for (let i = 1; i < match.indices.length; i++) {
        const span = match.indices[i];
        if (!span) continue;
        intervals.push({ start: span[0], end: span[1], type: 'group', index: i });
      }
    }
    if (match.indices.groups) {
      for (const name in match.indices.groups) {
        const span = match.indices.groups[name];
        if (!span) continue;
        intervals.push({ start: span[0], end: span[1], type: 'named', name });
      }
    }
    intervals.sort((a, b) => a.start - b.start || b.end - a.end);
    const roots = [];
    const stack = [];
    for (const iv of intervals) {
      const node = { iv, children: [] };
      while (stack.length && stack[stack.length - 1].iv.end <= iv.start) stack.pop();
      if (stack.length) stack[stack.length - 1].children.push(node);
      else roots.push(node);
      stack.push(node);
    }
    return roots;
  }

  function renderNode(node, text, groupColorFor) {
    const { iv, children } = node;
    let html = '';
    let cursor = iv.start;
    for (const child of children) {
      html += escapeHtml(text.slice(cursor, child.iv.start));
      html += renderNode(child, text, groupColorFor);
      cursor = child.iv.end;
    }
    html += escapeHtml(text.slice(cursor, iv.end));

    if (iv.type === 'match') {
      return `<mark class="hlm hlm-${iv.matchColorIdx % 5}">${html}</mark>`;
    }
    const label = iv.type === 'named' ? iv.name : `group ${iv.index}`;
    const color = groupColorFor(label);
    return `<span class="hlg" style="--g-color:${color}" title="${escapeHtml(label)}">${html}</span>`;
  }

  // ---------------- core compute ----------------

  function recompute() {
    const pattern = patternInput.value;
    const flags = [...activeFlags].join('');
    patternError.textContent = '';
    patternInput.classList.remove('invalid');

    const text = testInput.value;

    if (!pattern) {
      tsHighlight.innerHTML = escapeHtml(text);
      matchBadge.textContent = '0 matches';
      matchBadge.classList.add('zero');
      matchesList.innerHTML = '<div class="match-empty">Enter a pattern above to see matches here.</div>';
      groupsSection.hidden = true;
      return;
    }

    let matches;
    try {
      matches = collectMatches(pattern, flags, text);
    } catch (err) {
      patternError.textContent = err.message;
      patternInput.classList.add('invalid');
      tsHighlight.innerHTML = escapeHtml(text);
      matchBadge.textContent = 'error';
      matchBadge.classList.add('zero');
      matchesList.innerHTML = '<div class="match-empty">Fix the pattern error above.</div>';
      groupsSection.hidden = true;
      return;
    }

    // color assignment per named group, stable across matches
    const names = namedGroupNames(pattern);
    const groupColorFor = (() => {
      const map = new Map();
      names.forEach((n, i) => map.set(n, colorVar(i)));
      return label => {
        if (!map.has(label)) map.set(label, colorVar(map.size));
        return map.get(label);
      };
    })();

    // build highlighted overlay
    let html = '';
    let cursor = 0;
    matches.forEach((m, i) => {
      const tree = buildIntervalTree(m);
      html += escapeHtml(text.slice(cursor, m.indices[0][0]));
      tree.forEach(node => {
        node.iv.matchColorIdx = i;
        html += renderNode(node, text, groupColorFor);
      });
      cursor = m.indices[0][1];
    });
    html += escapeHtml(text.slice(cursor));
    tsHighlight.innerHTML = html || '&nbsp;';

    // badge
    matchBadge.textContent = `${matches.length} match${matches.length === 1 ? '' : 'es'}`;
    matchBadge.classList.toggle('zero', matches.length === 0);

    // matches list
    if (matches.length === 0) {
      matchesList.innerHTML = '<div class="match-empty">No matches in the test string.</div>';
    } else {
      matchesList.innerHTML = '';
      matches.forEach((m, i) => {
        const row = document.createElement('div');
        row.className = 'match-row';
        row.style.setProperty('--m-color', colorVar(i));
        const [s, e] = m.indices[0];
        let groupsHtml = '';
        for (let g = 1; g < m.length; g++) {
          if (m[g] === undefined) continue;
          groupsHtml += `<span class="grp"><b>$${g}</b>: "${escapeHtml(m[g])}"</span>`;
        }
        if (m.groups) {
          for (const name in m.groups) {
            if (m.groups[name] === undefined) continue;
            groupsHtml += `<span class="grp"><b>${escapeHtml(name)}</b>: "${escapeHtml(m.groups[name])}"</span>`;
          }
        }
        row.innerHTML = `
          <div class="match-row-head">
            <span class="match-index">#${i + 1}</span>
            <span class="match-text">${escapeHtml(m[0]) || '(empty match)'}</span>
            <span class="match-pos">${s}–${e}</span>
          </div>
          ${groupsHtml ? `<div class="match-groups">${groupsHtml}</div>` : ''}
        `;
        matchesList.appendChild(row);
      });
    }

    // named groups explained
    if (names.length === 0) {
      groupsSection.hidden = true;
    } else {
      groupsSection.hidden = false;
      groupsList.innerHTML = '';
      names.forEach((name, i) => {
        const values = matches
          .map(m => m.groups && m.groups[name])
          .filter(v => v !== undefined);
        const row = document.createElement('div');
        row.className = 'group-row';
        row.style.setProperty('--g-color', colorVar(i));
        const valuesHtml = values.length
          ? values.map(v => `"${escapeHtml(v)}"`).join(', ')
          : '<span class="none">did not capture in any match</span>';
        row.innerHTML = `<span class="group-name">${escapeHtml(name)}</span><span class="group-values">${valuesHtml}</span>`;
        groupsList.appendChild(row);
      });
    }
  }

  // ---------------- scroll sync + copy ----------------

  testInput.addEventListener('scroll', () => {
    tsHighlight.scrollTop = testInput.scrollTop;
    tsHighlight.scrollLeft = testInput.scrollLeft;
  });

  patternInput.addEventListener('input', recompute);
  testInput.addEventListener('input', recompute);

  copyPatternBtn.addEventListener('click', async () => {
    const flags = [...activeFlags].join('');
    const text = `/${patternInput.value}/${flags}`;
    try {
      await navigator.clipboard.writeText(text);
      copyPatternBtn.textContent = 'Copied!';
      copyPatternBtn.classList.add('copied');
      setTimeout(() => {
        copyPatternBtn.textContent = 'Copy';
        copyPatternBtn.classList.remove('copied');
      }, 1200);
    } catch {
      /* clipboard unavailable — ignore */
    }
  });

  // ---------------- init ----------------

  renderLibrary();
  renderCheatsheet();
  syncFlagButtons();
  patternInput.value = '(?<year>\\d{4})-(?<month>\\d{2})-(?<day>\\d{2})';
  testInput.value = 'Log entries: 2024-01-05 build failed. Retried on 2024-01-06 and it passed. Released 2024-02-14.';
  recompute();
})();
