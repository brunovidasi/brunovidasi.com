'use strict';

/* =========================================================================
   Shared helpers
   ========================================================================= */

function lsGet(key, fallback) {
  try {
    const raw = localStorage.getItem(key);
    return raw === null ? fallback : JSON.parse(raw);
  } catch (e) {
    return fallback;
  }
}

function lsSet(key, value) {
  try {
    localStorage.setItem(key, JSON.stringify(value));
  } catch (e) { /* ignore */ }
}

function formatNumber(n) {
  if (!isFinite(n)) return '—';
  if (n === 0) return '0';
  const abs = Math.abs(n);
  if (abs >= 1e9 || abs < 1e-6) return n.toExponential(4);
  const decimals = abs >= 1000 ? 2 : abs >= 1 ? 4 : 6;
  const rounded = parseFloat(n.toFixed(decimals));
  return rounded.toLocaleString('en-US', { maximumFractionDigits: decimals });
}

// A typeable dropdown: a text input filters a floating list of options as you
// type (matching whatever `searchTextFor` returns), with arrow-key navigation
// and Enter/click to select. Exposes `.value` so callers can treat it like a
// plain <select>. Used for the unit pickers, which have lists too long to
// scan by eye.
class SearchCombo {
  constructor(rootId, inputId, listId, { labelFor, searchTextFor, onChange }) {
    this.root = document.getElementById(rootId);
    this.input = document.getElementById(inputId);
    this.list = document.getElementById(listId);
    this.labelFor = labelFor;
    this.searchTextFor = searchTextFor || labelFor;
    this.onChange = onChange || null;
    this.options = [];
    this.filtered = [];
    this.activeIndex = -1;
    this._value = '';

    this.input.addEventListener('input', () => {
      this.activeIndex = -1;
      this.renderList(this.input.value);
    });
    this.input.addEventListener('focus', () => {
      this.input.select();
      this.renderList('');
    });
    this.input.addEventListener('keydown', (e) => this.handleKeydown(e));
    this.input.addEventListener('blur', () => {
      // Let a mousedown on an option register before we close/reset the field.
      setTimeout(() => this.close(), 150);
    });
    this.list.addEventListener('mousedown', (e) => {
      const item = e.target.closest('.search-combo-item');
      if (!item) return;
      e.preventDefault();
      this.select(item.dataset.value);
    });
  }

  setOptions(values) {
    this.options = values;
  }

  get value() { return this._value; }
  set value(v) {
    this._value = v;
    this.input.value = this.labelFor(v);
  }

  renderList(query) {
    const q = query.trim().toLowerCase();
    this.filtered = !q
      ? this.options
      : this.options.filter(v => this.searchTextFor(v).toLowerCase().includes(q));

    this.list.innerHTML = this.filtered.length
      ? this.filtered.map(v => `<div class="search-combo-item${v === this._value ? ' selected' : ''}" data-value="${v}">${this.labelFor(v)}</div>`).join('')
      : '<div class="search-combo-empty">No match</div>';
    this.list.hidden = false;
    this.root.classList.add('open');
  }

  close() {
    this.list.hidden = true;
    this.root.classList.remove('open');
    this.input.value = this.labelFor(this._value);
  }

  handleKeydown(e) {
    if (e.key === 'Escape') {
      this.close();
      this.input.blur();
      return;
    }
    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
      e.preventDefault();
      if (this.list.hidden) { this.renderList(''); return; }
      const delta = e.key === 'ArrowDown' ? 1 : -1;
      this.activeIndex = Math.max(0, Math.min(this.activeIndex + delta, this.filtered.length - 1));
      const items = this.list.querySelectorAll('.search-combo-item');
      items.forEach((el, i) => el.classList.toggle('active', i === this.activeIndex));
      if (items[this.activeIndex]) items[this.activeIndex].scrollIntoView({ block: 'nearest' });
      return;
    }
    if (e.key === 'Enter') {
      e.preventDefault();
      const v = this.filtered[this.activeIndex] ?? (this.filtered.length === 1 ? this.filtered[0] : null);
      if (v != null) this.select(v);
    }
  }

  select(v) {
    this.value = v;
    this.close();
    this.input.blur();
    if (this.onChange) this.onChange(v);
  }
}

/* =========================================================================
   UNITS
   ========================================================================= */

const UNIT_CATEGORIES = {
  length: {
    label: 'Length', icon: '📏',
    units: {
      mm: { label: 'Millimeters', symbol: 'mm', factor: 0.001 },
      cm: { label: 'Centimeters', symbol: 'cm', factor: 0.01 },
      m:  { label: 'Meters', symbol: 'm', factor: 1 },
      km: { label: 'Kilometers', symbol: 'km', factor: 1000 },
      in: { label: 'Inches', symbol: 'in', factor: 0.0254 },
      ft: { label: 'Feet', symbol: 'ft', factor: 0.3048 },
      yd: { label: 'Yards', symbol: 'yd', factor: 0.9144 },
      mi: { label: 'Miles', symbol: 'mi', factor: 1609.344 },
      nmi:{ label: 'Nautical miles', symbol: 'nmi', factor: 1852 },
    },
    default: ['m', 'ft'],
  },
  weight: {
    label: 'Weight', icon: '⚖️',
    units: {
      mg: { label: 'Milligrams', symbol: 'mg', factor: 0.000001 },
      g:  { label: 'Grams', symbol: 'g', factor: 0.001 },
      kg: { label: 'Kilograms', symbol: 'kg', factor: 1 },
      t:  { label: 'Metric tons', symbol: 't', factor: 1000 },
      oz: { label: 'Ounces', symbol: 'oz', factor: 0.0283495231 },
      lb: { label: 'Pounds', symbol: 'lb', factor: 0.45359237 },
      st: { label: 'Stone', symbol: 'st', factor: 6.35029318 },
    },
    default: ['kg', 'lb'],
  },
  temperature: {
    label: 'Temperature', icon: '🌡️', special: true,
    units: {
      c: { label: 'Celsius', symbol: '°C' },
      f: { label: 'Fahrenheit', symbol: '°F' },
      k: { label: 'Kelvin', symbol: 'K' },
    },
    default: ['c', 'f'],
  },
  volume: {
    label: 'Volume', icon: '🧪',
    units: {
      ml:   { label: 'Milliliters', symbol: 'ml', factor: 0.001 },
      l:    { label: 'Liters', symbol: 'L', factor: 1 },
      m3:   { label: 'Cubic meters', symbol: 'm³', factor: 1000 },
      tsp:  { label: 'Teaspoons (US)', symbol: 'tsp', factor: 0.00492892 },
      tbsp: { label: 'Tablespoons (US)', symbol: 'tbsp', factor: 0.0147868 },
      flOz: { label: 'Fluid ounces (US)', symbol: 'fl oz', factor: 0.0295735 },
      cup:  { label: 'Cups (US)', symbol: 'cup', factor: 0.24 },
      pint: { label: 'Pints (US)', symbol: 'pt', factor: 0.473176 },
      quart:{ label: 'Quarts (US)', symbol: 'qt', factor: 0.946353 },
      gal:  { label: 'Gallons (US)', symbol: 'gal', factor: 3.78541 },
    },
    default: ['l', 'gal'],
  },
  area: {
    label: 'Area', icon: '▦',
    units: {
      mm2:  { label: 'Sq millimeters', symbol: 'mm²', factor: 0.000001 },
      cm2:  { label: 'Sq centimeters', symbol: 'cm²', factor: 0.0001 },
      m2:   { label: 'Sq meters', symbol: 'm²', factor: 1 },
      ha:   { label: 'Hectares', symbol: 'ha', factor: 10000 },
      km2:  { label: 'Sq kilometers', symbol: 'km²', factor: 1000000 },
      in2:  { label: 'Sq inches', symbol: 'in²', factor: 0.00064516 },
      ft2:  { label: 'Sq feet', symbol: 'ft²', factor: 0.09290304 },
      yd2:  { label: 'Sq yards', symbol: 'yd²', factor: 0.83612736 },
      acre: { label: 'Acres', symbol: 'ac', factor: 4046.8564224 },
      mi2:  { label: 'Sq miles', symbol: 'mi²', factor: 2589988.110336 },
    },
    default: ['m2', 'ft2'],
  },
  speed: {
    label: 'Speed', icon: '💨',
    units: {
      mps:  { label: 'Meters/second', symbol: 'm/s', factor: 1 },
      kph:  { label: 'Kilometers/hour', symbol: 'km/h', factor: 0.277778 },
      mph:  { label: 'Miles/hour', symbol: 'mph', factor: 0.44704 },
      knot: { label: 'Knots', symbol: 'kn', factor: 0.514444 },
      fps:  { label: 'Feet/second', symbol: 'ft/s', factor: 0.3048 },
    },
    default: ['kph', 'mph'],
  },
  data: {
    label: 'Data', icon: '💾',
    units: {
      bit: { label: 'Bits', symbol: 'bit', factor: 0.125 },
      byte:{ label: 'Bytes', symbol: 'B', factor: 1 },
      kb:  { label: 'Kilobytes', symbol: 'KB', factor: 1024 },
      mb:  { label: 'Megabytes', symbol: 'MB', factor: 1024 ** 2 },
      gb:  { label: 'Gigabytes', symbol: 'GB', factor: 1024 ** 3 },
      tb:  { label: 'Terabytes', symbol: 'TB', factor: 1024 ** 4 },
      pb:  { label: 'Petabytes', symbol: 'PB', factor: 1024 ** 5 },
    },
    default: ['mb', 'gb'],
  },
  time: {
    label: 'Time', icon: '⏱️',
    units: {
      ms:   { label: 'Milliseconds', symbol: 'ms', factor: 0.001 },
      s:    { label: 'Seconds', symbol: 's', factor: 1 },
      min:  { label: 'Minutes', symbol: 'min', factor: 60 },
      hr:   { label: 'Hours', symbol: 'hr', factor: 3600 },
      day:  { label: 'Days', symbol: 'd', factor: 86400 },
      week: { label: 'Weeks', symbol: 'wk', factor: 604800 },
      month:{ label: 'Months (avg)', symbol: 'mo', factor: 2629800 },
      year: { label: 'Years', symbol: 'yr', factor: 31557600 },
    },
    default: ['hr', 'min'],
  },
};

const TEMP_TO_CELSIUS = {
  c: v => v,
  f: v => (v - 32) * 5 / 9,
  k: v => v - 273.15,
};
const TEMP_FROM_CELSIUS = {
  c: v => v,
  f: v => v * 9 / 5 + 32,
  k: v => v + 273.15,
};

function convertUnit(catKey, value, fromUnit, toUnit) {
  const cat = UNIT_CATEGORIES[catKey];
  if (cat.special) {
    return TEMP_FROM_CELSIUS[toUnit](TEMP_TO_CELSIUS[fromUnit](value));
  }
  const baseValue = value * cat.units[fromUnit].factor;
  return baseValue / cat.units[toUnit].factor;
}

const unitState = {
  category: lsGet('conv.unit.category', 'length'),
  from: lsGet('conv.unit.from', null),
  to: lsGet('conv.unit.to', null),
};
if (!UNIT_CATEGORIES[unitState.category]) unitState.category = 'length';

const catRow = document.getElementById('unitCategories');
Object.keys(UNIT_CATEGORIES).forEach(key => {
  const cat = UNIT_CATEGORIES[key];
  const btn = document.createElement('button');
  btn.className = 'category-btn';
  btn.dataset.cat = key;
  btn.textContent = `${cat.icon} ${cat.label}`;
  btn.addEventListener('click', () => selectUnitCategory(key));
  catRow.appendChild(btn);
});

const unitFromValueEl = document.getElementById('unitFromValue');
const unitToValueEl = document.getElementById('unitToValue');
const unitRateLineEl = document.getElementById('unitRateLine');
const unitAllGridEl = document.getElementById('unitAllGrid');

function unitLabel(u) {
  const unit = UNIT_CATEGORIES[unitState.category].units[u];
  return unit ? unit.label : u;
}

const unitFromCombo = new SearchCombo('unitFromCombo', 'unitFromUnit', 'unitFromList', {
  labelFor: unitLabel,
  onChange: computeUnit,
});
const unitToCombo = new SearchCombo('unitToCombo', 'unitToUnit', 'unitToList', {
  labelFor: unitLabel,
  onChange: computeUnit,
});

function selectUnitCategory(key) {
  unitState.category = key;
  lsSet('conv.unit.category', key);
  document.querySelectorAll('.category-btn').forEach(b => b.classList.toggle('active', b.dataset.cat === key));

  const cat = UNIT_CATEGORIES[key];
  const unitKeys = Object.keys(cat.units);
  const savedFrom = unitState.from && cat.units[unitState.from] ? unitState.from : cat.default[0];
  const savedTo = unitState.to && cat.units[unitState.to] ? unitState.to : cat.default[1];

  unitFromCombo.setOptions(unitKeys);
  unitToCombo.setOptions(unitKeys);
  unitFromCombo.value = savedFrom;
  unitToCombo.value = savedTo;

  computeUnit();
}

function computeUnit() {
  const cat = UNIT_CATEGORIES[unitState.category];
  const from = unitFromCombo.value;
  const to = unitToCombo.value;
  unitState.from = from;
  unitState.to = to;
  lsSet('conv.unit.from', from);
  lsSet('conv.unit.to', to);

  const raw = parseFloat(unitFromValueEl.value);
  if (isNaN(raw)) {
    unitToValueEl.value = '';
    unitRateLineEl.textContent = '';
    unitAllGridEl.innerHTML = '';
    return;
  }

  const result = convertUnit(unitState.category, raw, from, to);
  unitToValueEl.value = formatNumber(result);

  const oneUnitResult = convertUnit(unitState.category, 1, from, to);
  unitRateLineEl.textContent = `1 ${cat.units[from].symbol} = ${formatNumber(oneUnitResult)} ${cat.units[to].symbol}`;

  unitAllGridEl.innerHTML = Object.keys(cat.units).map(u => {
    const val = convertUnit(unitState.category, raw, from, u);
    const isCurrent = u === to;
    return `<div class="all-units-row${isCurrent ? ' current' : ''}">
      <input class="val" type="text" inputmode="decimal" autocomplete="off" spellcheck="false" data-unit="${u}" value="${formatNumber(val)}">
      <span class="unit">${cat.units[u].symbol}</span>
    </div>`;
  }).join('');
}

// Typing into any "all units" cell recomputes every other field (including
// the From/To boxes above) from that cell's unit, without re-rendering the
// grid — a full re-render would blow away the input the user is mid-typing in.
function syncFromGridInput(sourceUnit, sourceEl) {
  const raw = parseFloat(sourceEl.value);
  if (isNaN(raw)) return;

  const fromU = unitFromCombo.value;
  const toU = unitToCombo.value;
  unitFromValueEl.value = formatNumber(convertUnit(unitState.category, raw, sourceUnit, fromU));
  unitToValueEl.value = formatNumber(convertUnit(unitState.category, raw, sourceUnit, toU));

  unitAllGridEl.querySelectorAll('input.val').forEach(inp => {
    if (inp === sourceEl) return;
    const u = inp.dataset.unit;
    inp.value = formatNumber(convertUnit(unitState.category, raw, sourceUnit, u));
  });
}

unitAllGridEl.addEventListener('input', (e) => {
  const inp = e.target.closest('input.val');
  if (!inp) return;
  syncFromGridInput(inp.dataset.unit, inp);
});

document.getElementById('unitSwap').addEventListener('click', () => {
  const f = unitFromCombo.value;
  unitFromCombo.value = unitToCombo.value;
  unitToCombo.value = f;
  computeUnit();
});
unitFromValueEl.addEventListener('input', computeUnit);

selectUnitCategory(unitState.category);
