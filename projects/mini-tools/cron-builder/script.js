const FIELD_ORDER = ['minute', 'hour', 'dom', 'month', 'dow'];

const FIELD_DEFS = {
  minute: { min: 0, max: 59, label: 'Minute', unit: 'minute(s)', color: '--f-minute' },
  hour: { min: 0, max: 23, label: 'Hour', unit: 'hour(s)', color: '--f-hour' },
  dom: { min: 1, max: 31, label: 'Day of month', unit: 'day(s)', color: '--f-dom' },
  month: { min: 1, max: 12, label: 'Month', unit: 'month(s)', color: '--f-month', names: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'] },
  dow: { min: 0, max: 6, label: 'Day of week', unit: 'weekday(s)', color: '--f-dow', names: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] },
};

const DEFAULT_STEP = { minute: 15, hour: 2, dom: 2, month: 2, dow: 1 };
const DEFAULT_RANGE = {
  minute: [0, 30], hour: [9, 17], dom: [1, 15], month: [1, 6], dow: [1, 5],
};

const MONTH_ALIASES = { jan: 1, feb: 2, mar: 3, apr: 4, may: 5, jun: 6, jul: 7, aug: 8, sep: 9, oct: 10, nov: 11, dec: 12 };
const DOW_ALIASES = { sun: 0, mon: 1, tue: 2, wed: 3, thu: 4, fri: 5, sat: 6 };

const state = {
  minute: { mode: 'every' },
  hour: { mode: 'every' },
  dom: { mode: 'every' },
  month: { mode: 'every' },
  dow: { mode: 'every' },
};

const fieldsEl = document.getElementById('fields');
const exprInput = document.getElementById('exprInput');
const exprError = document.getElementById('exprError');
const explainEl = document.getElementById('explain');
const runsListEl = document.getElementById('runsList');
const tzLabelEl = document.getElementById('tzLabel');

tzLabelEl.textContent = `(${Intl.DateTimeFormat().resolvedOptions().timeZone})`;

function fieldStateToCronPart(field) {
  const st = state[field];
  const def = FIELD_DEFS[field];
  switch (st.mode) {
    case 'every': return '*';
    case 'step': return `*/${st.step ?? DEFAULT_STEP[field]}`;
    case 'range': return `${st.from ?? def.min}-${st.to ?? def.max}`;
    case 'specific': {
      const vals = st.specific && st.specific.size ? [...st.specific].sort((a, b) => a - b) : [def.min];
      return vals.join(',');
    }
    case 'custom': return st.custom;
    default: return '*';
  }
}

function buildCronString() {
  return FIELD_ORDER.map(fieldStateToCronPart).join(' ');
}

function normalizeAliases(part, aliasMap) {
  return part.replace(/[a-zA-Z]{3}/g, (word) => {
    const v = aliasMap[word.toLowerCase()];
    return v !== undefined ? String(v) : word;
  });
}

function parseField(expr, min, max) {
  const set = new Set();
  const parts = expr.split(',');
  for (let part of parts) {
    part = part.trim();
    let m;
    if (part === '') return null;
    if (part === '*') {
      for (let i = min; i <= max; i++) set.add(i);
    } else if ((m = part.match(/^\*\/(\d+)$/))) {
      const step = parseInt(m[1], 10);
      if (step <= 0) return null;
      for (let i = min; i <= max; i += step) set.add(i);
    } else if ((m = part.match(/^(\d+)-(\d+)\/(\d+)$/))) {
      const a = +m[1], b = +m[2], s = +m[3];
      if (s <= 0 || a > b || a < min || b > max) return null;
      for (let i = a; i <= b; i += s) set.add(i);
    } else if ((m = part.match(/^(\d+)-(\d+)$/))) {
      const a = +m[1], b = +m[2];
      if (a > b || a < min || b > max) return null;
      for (let i = a; i <= b; i++) set.add(i);
    } else if ((m = part.match(/^(\d+)\/(\d+)$/))) {
      const a = +m[1], s = +m[2];
      if (s <= 0 || a < min || a > max) return null;
      for (let i = a; i <= max; i += s) set.add(i);
    } else if ((m = part.match(/^(\d+)$/))) {
      const v = +m[1];
      if (v < min || v > max) return null;
      set.add(v);
    } else {
      return null;
    }
  }
  return set;
}

function reconstructFieldState(part, field) {
  const def = FIELD_DEFS[field];
  let m;
  if (part === '*') return { mode: 'every' };
  if ((m = part.match(/^\*\/(\d+)$/))) return { mode: 'step', step: +m[1] };
  if ((m = part.match(/^(\d+)-(\d+)$/))) {
    const from = +m[1], to = +m[2];
    if (from >= def.min && to <= def.max && from <= to) return { mode: 'range', from, to };
    return { mode: 'custom', custom: part };
  }
  if (/^\d+(,\d+)*$/.test(part)) {
    const nums = part.split(',').map(Number);
    if (nums.every((n) => n >= def.min && n <= def.max)) {
      return { mode: 'specific', specific: new Set(nums) };
    }
  }
  return { mode: 'custom', custom: part };
}

function dayNameList(set, names) {
  return [...set].sort((a, b) => a - b).map((i) => names[i]).join(', ');
}

function ordinal(n) {
  const s = ['th', 'st', 'nd', 'rd'];
  const v = n % 100;
  return n + (s[(v - 20) % 10] || s[v] || s[0]);
}

function pad(n) {
  return String(n).padStart(2, '0');
}

function formatHM(h, m) {
  const period = h < 12 ? 'AM' : 'PM';
  const h12 = h % 12 === 0 ? 12 : h % 12;
  return `${h12}:${pad(m)} ${period}`;
}

function describe(parts, sets, restricted) {
  const [mm, hh, dom, mon, dow] = parts;
  const monthNames = FIELD_DEFS.month.names;
  const dowNames = FIELD_DEFS.dow.names;

  const mmSingle = /^\d+$/.test(mm);
  const hhSingle = /^\d+$/.test(hh);
  const domSingle = /^\d+$/.test(dom);
  const monSingle = /^\d+$/.test(mon);

  if (mm === '*' && hh === '*' && dom === '*' && mon === '*' && dow === '*') {
    return 'Runs every minute.';
  }

  const stepMatch = mm.match(/^\*\/(\d+)$/);
  if (stepMatch && hh === '*' && dom === '*' && mon === '*' && dow === '*') {
    return `Runs every ${stepMatch[1]} minutes.`;
  }

  if (mmSingle && hh === '*' && dom === '*' && mon === '*' && dow === '*') {
    return `Runs every hour, at minute ${mm} past the hour.`;
  }

  const hourStepMatch = hh.match(/^\*\/(\d+)$/);
  if (mmSingle && hourStepMatch && dom === '*' && mon === '*' && dow === '*') {
    return `Runs every ${hourStepMatch[1]} hours, at minute ${mm} past the hour.`;
  }

  if (mmSingle && hhSingle && dom === '*' && mon === '*') {
    const time = formatHM(+hh, +mm);
    if (dow === '*') return `Runs every day at ${time}.`;
    const dowSet = sets.dow;
    const weekdays = new Set([1, 2, 3, 4, 5]);
    const weekend = new Set([0, 6]);
    if (setsEqual(dowSet, weekdays)) return `Runs every weekday (Mon–Fri) at ${time}.`;
    if (setsEqual(dowSet, weekend)) return `Runs every weekend (Sat–Sun) at ${time}.`;
    return `Runs every ${dayNameList(dowSet, dowNames)} at ${time}.`;
  }

  if (mmSingle && hhSingle && dow === '*' && mon === '*') {
    const time = formatHM(+hh, +mm);
    if (domSingle) return `Runs on the ${ordinal(+dom)} of every month at ${time}.`;
    return `Runs on day(s) ${dom} of every month at ${time}.`;
  }

  if (mmSingle && hhSingle && dow === '*' && domSingle && monSingle) {
    const time = formatHM(+hh, +mm);
    return `Runs every year on ${monthNames[+mon - 1]} ${ordinal(+dom)} at ${time}.`;
  }

  const clause = (val, single, def, name) => {
    if (val === '*') return `every ${name}`;
    if (/^\*\/(\d+)$/.test(val)) return `every ${val.slice(2)} ${def.unit}`;
    return `${name} ${val}`;
  };

  const parts_ = [
    `at minute ${mm === '*' ? '*' : mm}`,
    `hour ${hh === '*' ? '*' : hh}`,
    `on day-of-month ${dom === '*' ? '*' : dom}`,
    `in month ${mon === '*' ? '*' : mon}`,
    `on day-of-week ${dow === '*' ? '*' : dow}`,
  ];
  return `Runs ${parts_.join(', ')}.`;
}

function setsEqual(a, b) {
  if (a.size !== b.size) return false;
  for (const v of a) if (!b.has(v)) return false;
  return true;
}

function computeNextRuns(sets, restricted, count = 5) {
  const now = new Date();
  let cursor = new Date(now.getFullYear(), now.getMonth(), now.getDate(), now.getHours(), now.getMinutes() + 1, 0, 0);
  const limitDate = new Date(cursor);
  limitDate.setFullYear(limitDate.getFullYear() + 5);
  const results = [];
  let guard = 0;

  while (results.length < count && cursor < limitDate && guard < 200000) {
    guard++;
    if (!sets.month.has(cursor.getMonth() + 1)) {
      cursor = new Date(cursor.getFullYear(), cursor.getMonth() + 1, 1, 0, 0, 0, 0);
      continue;
    }
    const domOk = sets.dom.has(cursor.getDate());
    const dowOk = sets.dow.has(cursor.getDay());
    let dayOk;
    if (restricted.dom && restricted.dow) dayOk = domOk || dowOk;
    else if (restricted.dom) dayOk = domOk;
    else if (restricted.dow) dayOk = dowOk;
    else dayOk = true;

    if (!dayOk) {
      cursor = new Date(cursor.getFullYear(), cursor.getMonth(), cursor.getDate() + 1, 0, 0, 0, 0);
      continue;
    }
    if (!sets.hour.has(cursor.getHours())) {
      cursor = new Date(cursor.getFullYear(), cursor.getMonth(), cursor.getDate(), cursor.getHours() + 1, 0, 0, 0);
      continue;
    }
    if (!sets.minute.has(cursor.getMinutes())) {
      cursor = new Date(cursor.getFullYear(), cursor.getMonth(), cursor.getDate(), cursor.getHours(), cursor.getMinutes() + 1, 0, 0);
      continue;
    }
    results.push(new Date(cursor));
    cursor = new Date(cursor.getFullYear(), cursor.getMonth(), cursor.getDate(), cursor.getHours(), cursor.getMinutes() + 1, 0, 0);
  }
  return results;
}

function relativeLabel(date) {
  const diffMs = date - new Date();
  const mins = Math.round(diffMs / 60000);
  const days = Math.floor(mins / 1440);
  const hours = Math.floor((mins % 1440) / 60);
  const remMins = mins % 60;
  if (days > 0) return `in ${days}d ${hours}h`;
  if (hours > 0) return `in ${hours}h ${remMins}m`;
  return `in ${Math.max(remMins, 0)}m`;
}

const dateFmt = new Intl.DateTimeFormat(undefined, { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });

function renderRuns(sets, restricted) {
  const runs = computeNextRuns(sets, restricted, 5);
  runsListEl.innerHTML = '';
  if (!runs.length) {
    runsListEl.innerHTML = '<li class="runs-empty">No upcoming runs found in the next 5 years — this schedule may never actually match (e.g. Feb 31).</li>';
    return;
  }
  runs.forEach((d, i) => {
    const li = document.createElement('li');
    li.className = 'run-row';
    li.innerHTML = `<span class="run-index">${i + 1}</span><span class="run-date">${dateFmt.format(d)} · ${formatHM(d.getHours(), d.getMinutes())}</span><span class="run-rel">${relativeLabel(d)}</span>`;
    runsListEl.appendChild(li);
  });
}

function flashToken(field) {
  const tok = document.querySelector(`.expr-display .tok[data-field="${field}"]`);
  if (!tok) return;
  tok.classList.add('flash');
  setTimeout(() => tok.classList.remove('flash'), 150);
}

function updateTokDisplay(parts) {
  FIELD_ORDER.forEach((field, i) => {
    const tok = document.querySelector(`.expr-display .tok[data-field="${field}"]`);
    if (tok) tok.textContent = parts[i];
  });
}

function recomputeAll(rawCron, { fromFields } = {}) {
  const trimmed = rawCron.trim();
  const rawParts = trimmed.split(/\s+/);
  if (trimmed === '' || rawParts.length !== 5) {
    exprError.textContent = 'Expected 5 space-separated fields: minute hour day-of-month month day-of-week.';
    exprInput.classList.add('invalid');
    return;
  }

  const normalizedParts = rawParts.map((p, i) => {
    const field = FIELD_ORDER[i];
    let np = p;
    if (field === 'month') np = normalizeAliases(np, MONTH_ALIASES);
    if (field === 'dow') np = normalizeAliases(np, DOW_ALIASES).replace(/\b7\b/g, '0');
    return np;
  });

  const sets = {};
  for (let i = 0; i < 5; i++) {
    const field = FIELD_ORDER[i];
    const def = FIELD_DEFS[field];
    const parsed = parseField(normalizedParts[i], def.min, field === 'dow' ? 7 : def.max);
    if (!parsed) {
      exprError.textContent = `Invalid value in "${def.label}" field: "${rawParts[i]}"`;
      exprInput.classList.add('invalid');
      return;
    }
    if (field === 'dow' && parsed.has(7)) { parsed.delete(7); parsed.add(0); }
    sets[field] = parsed;
  }

  exprError.textContent = '';
  exprInput.classList.remove('invalid');

  const restricted = { dom: rawParts[2] !== '*', dow: rawParts[4] !== '*' };

  updateTokDisplay(rawParts);
  explainEl.textContent = describe(rawParts, sets, restricted);
  renderRuns(sets, restricted);

  if (!fromFields) {
    FIELD_ORDER.forEach((field, i) => {
      state[field] = reconstructFieldState(normalizedParts[i], field);
      renderFieldCard(field);
    });
  }
}

function updateFromFields() {
  const cron = buildCronString();
  exprInput.value = cron;
  recomputeAll(cron, { fromFields: true });
}

function renderFieldCard(field) {
  const def = FIELD_DEFS[field];
  const st = state[field];
  let card = document.querySelector(`.field-card[data-field="${field}"]`);
  if (!card) {
    card = document.createElement('div');
    card.className = 'field-card';
    card.dataset.field = field;
    card.style.setProperty('--f-color', `var(${def.color})`);
    card.innerHTML = `
      <div class="field-head"><span class="dot"></span><span class="field-name">${def.label}</span></div>
      <div class="mode-row">
        <button class="mode-btn" data-mode="every">Every</button>
        <button class="mode-btn" data-mode="step">Step</button>
        <button class="mode-btn" data-mode="range">Range</button>
        <button class="mode-btn" data-mode="specific">Specific</button>
      </div>
      <div class="mode-body"></div>
    `;
    card.querySelectorAll('.mode-btn').forEach((btn) => {
      btn.addEventListener('click', () => {
        const mode = btn.dataset.mode;
        if (mode === 'step') state[field] = { mode, step: DEFAULT_STEP[field] };
        else if (mode === 'range') state[field] = { mode, from: DEFAULT_RANGE[field][0], to: DEFAULT_RANGE[field][1] };
        else if (mode === 'specific') state[field] = { mode, specific: new Set([def.min]) };
        else state[field] = { mode: 'every' };
        renderFieldCard(field);
        updateFromFields();
      });
    });
    card.addEventListener('mouseenter', () => {
      const tok = document.querySelector(`.expr-display .tok[data-field="${field}"]`);
      if (tok) tok.classList.add('flash');
    });
    card.addEventListener('mouseleave', () => {
      const tok = document.querySelector(`.expr-display .tok[data-field="${field}"]`);
      if (tok) tok.classList.remove('flash');
    });
    fieldsEl.appendChild(card);
  }

  card.querySelectorAll('.mode-btn').forEach((btn) => {
    btn.classList.toggle('active', btn.dataset.mode === st.mode);
  });

  const body = card.querySelector('.mode-body');
  body.innerHTML = '';

  if (st.mode === 'every') {
    body.innerHTML = `<div class="mode-hint">Matches every ${def.unit}.</div>`;
  } else if (st.mode === 'step') {
    body.innerHTML = `<label class="inline-label">Every <input type="number" class="num-input" min="1" max="${def.max}" value="${st.step ?? DEFAULT_STEP[field]}"> ${def.unit}</label>`;
    body.querySelector('input').addEventListener('input', (e) => {
      const v = Math.max(1, parseInt(e.target.value, 10) || 1);
      state[field].step = v;
      updateFromFields();
    });
  } else if (st.mode === 'range') {
    body.innerHTML = `<label class="inline-label">From <input type="number" class="num-input from" min="${def.min}" max="${def.max}" value="${st.from ?? def.min}"> to <input type="number" class="num-input to" min="${def.min}" max="${def.max}" value="${st.to ?? def.max}"></label>`;
    const fromInput = body.querySelector('.from');
    const toInput = body.querySelector('.to');
    const onChange = () => {
      let from = Math.min(Math.max(def.min, parseInt(fromInput.value, 10) || def.min), def.max);
      let to = Math.min(Math.max(def.min, parseInt(toInput.value, 10) || def.max), def.max);
      if (from > to) to = from;
      state[field].from = from;
      state[field].to = to;
      updateFromFields();
    };
    fromInput.addEventListener('input', onChange);
    toInput.addEventListener('input', onChange);
  } else if (st.mode === 'specific') {
    if (!st.specific) st.specific = new Set([def.min]);
    const grid = document.createElement('div');
    grid.className = 'chip-grid';
    for (let i = def.min; i <= def.max; i++) {
      const chip = document.createElement('button');
      chip.className = 'chip' + (st.specific.has(i) ? ' active' : '');
      chip.textContent = def.names ? def.names[field === 'month' ? i - 1 : i] : i;
      chip.addEventListener('click', () => {
        if (st.specific.has(i)) { if (st.specific.size > 1) st.specific.delete(i); }
        else st.specific.add(i);
        chip.classList.toggle('active', st.specific.has(i));
        updateFromFields();
      });
      grid.appendChild(chip);
    }
    body.appendChild(grid);
  } else if (st.mode === 'custom') {
    body.innerHTML = `<div class="mode-hint custom">Custom: <code>${st.custom}</code> — edit the expression above, or pick a mode to replace it.</div>`;
  }
}

document.getElementById('presets').addEventListener('click', (e) => {
  const btn = e.target.closest('.preset');
  if (!btn) return;
  document.querySelectorAll('.preset').forEach((b) => b.classList.remove('active'));
  btn.classList.add('active');
  exprInput.value = btn.dataset.cron;
  recomputeAll(btn.dataset.cron);
});

exprInput.addEventListener('input', () => {
  document.querySelectorAll('.preset').forEach((b) => b.classList.remove('active'));
  recomputeAll(exprInput.value);
});

const copyBtn = document.getElementById('copyBtn');
copyBtn.addEventListener('click', async () => {
  const text = exprInput.value;
  try {
    await navigator.clipboard.writeText(text);
  } catch (e) {
    exprInput.select();
    exprInput.setSelectionRange(0, text.length);
    document.execCommand('copy');
  }
  const original = copyBtn.textContent;
  copyBtn.textContent = 'Copied!';
  copyBtn.classList.add('copied');
  setTimeout(() => {
    copyBtn.textContent = original;
    copyBtn.classList.remove('copied');
  }, 1200);
});

document.getElementById('clearAllBtn').addEventListener('click', () => {
  document.querySelectorAll('.preset').forEach((b) => b.classList.remove('active'));
  FIELD_ORDER.forEach((field) => {
    state[field] = { mode: 'every' };
    renderFieldCard(field);
  });
  updateFromFields();
});

document.querySelectorAll('.expr-display .tok, .expr-legend .tok').forEach((tok) => {
  const field = tok.dataset.field;
  tok.style.setProperty('--f-color', `var(${FIELD_DEFS[field].color})`);
  tok.addEventListener('click', () => {
    const card = document.querySelector(`.field-card[data-field="${field}"]`);
    if (!card) return;
    card.scrollIntoView({ behavior: 'smooth', block: 'center' });
    card.classList.add('highlight');
    setTimeout(() => card.classList.remove('highlight'), 700);
  });
});

FIELD_ORDER.forEach(renderFieldCard);
exprInput.value = '0 3 * * *';
recomputeAll(exprInput.value);
