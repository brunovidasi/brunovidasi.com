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

/* =========================================================================
   Tabs
   ========================================================================= */

document.getElementById('tabs').addEventListener('click', (e) => {
  const btn = e.target.closest('.tab');
  if (!btn) return;
  document.querySelectorAll('.tab').forEach(t => t.classList.toggle('active', t === btn));
  document.querySelectorAll('.panel').forEach(p => p.classList.toggle('active', p.id === 'panel-' + btn.dataset.tab));
});

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
const unitFromUnitEl = document.getElementById('unitFromUnit');
const unitToUnitEl = document.getElementById('unitToUnit');
const unitRateLineEl = document.getElementById('unitRateLine');
const unitAllGridEl = document.getElementById('unitAllGrid');

function selectUnitCategory(key) {
  unitState.category = key;
  lsSet('conv.unit.category', key);
  document.querySelectorAll('.category-btn').forEach(b => b.classList.toggle('active', b.dataset.cat === key));

  const cat = UNIT_CATEGORIES[key];
  const unitKeys = Object.keys(cat.units);
  const savedFrom = unitState.from && cat.units[unitState.from] ? unitState.from : cat.default[0];
  const savedTo = unitState.to && cat.units[unitState.to] ? unitState.to : cat.default[1];

  unitFromUnitEl.innerHTML = unitKeys.map(u => `<option value="${u}">${cat.units[u].label}</option>`).join('');
  unitToUnitEl.innerHTML = unitKeys.map(u => `<option value="${u}">${cat.units[u].label}</option>`).join('');
  unitFromUnitEl.value = savedFrom;
  unitToUnitEl.value = savedTo;

  computeUnit();
}

function computeUnit() {
  const cat = UNIT_CATEGORIES[unitState.category];
  const from = unitFromUnitEl.value;
  const to = unitToUnitEl.value;
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

  const fromU = unitFromUnitEl.value;
  const toU = unitToUnitEl.value;
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
  const f = unitFromUnitEl.value;
  unitFromUnitEl.value = unitToUnitEl.value;
  unitToUnitEl.value = f;
  computeUnit();
});
unitFromValueEl.addEventListener('input', computeUnit);
unitFromUnitEl.addEventListener('change', computeUnit);
unitToUnitEl.addEventListener('change', computeUnit);

selectUnitCategory(unitState.category);

/* =========================================================================
   CURRENCY
   ========================================================================= */

const CURRENCY_META = {
  USD: 'US Dollar', EUR: 'Euro', GBP: 'British Pound', JPY: 'Japanese Yen',
  CHF: 'Swiss Franc', CAD: 'Canadian Dollar', AUD: 'Australian Dollar',
  NZD: 'New Zealand Dollar', CNY: 'Chinese Yuan', HKD: 'Hong Kong Dollar',
  SGD: 'Singapore Dollar', INR: 'Indian Rupee', BRL: 'Brazilian Real',
  MXN: 'Mexican Peso', ZAR: 'South African Rand', SEK: 'Swedish Krona',
  NOK: 'Norwegian Krone', DKK: 'Danish Krone', PLN: 'Polish Zloty',
  TRY: 'Turkish Lira', RUB: 'Russian Ruble', KRW: 'South Korean Won',
  THB: 'Thai Baht', IDR: 'Indonesian Rupiah', PHP: 'Philippine Peso',
  MYR: 'Malaysian Ringgit', VND: 'Vietnamese Dong', AED: 'UAE Dirham',
  SAR: 'Saudi Riyal', ILS: 'Israeli Shekel', EGP: 'Egyptian Pound',
  ARS: 'Argentine Peso', CLP: 'Chilean Peso', COP: 'Colombian Peso',
  PKR: 'Pakistani Rupee', BDT: 'Bangladeshi Taka', NGN: 'Nigerian Naira',
  UAH: 'Ukrainian Hryvnia', CZK: 'Czech Koruna', HUF: 'Hungarian Forint',
  RON: 'Romanian Leu', ISK: 'Icelandic Krona',
};

const CURRENCY_PRIORITY = ['USD', 'EUR', 'GBP', 'JPY', 'CHF', 'CAD', 'AUD', 'CNY', 'INR', 'BRL'];

// Approximate offline fallback (relative to USD). Used only if the live fetch fails.
const STATIC_RATES = {
  USD: 1, EUR: 0.92, GBP: 0.79, JPY: 149, CHF: 0.88, CAD: 1.36, AUD: 1.52,
  NZD: 1.66, CNY: 7.24, HKD: 7.82, SGD: 1.34, INR: 83.5, BRL: 5.4,
  MXN: 18.3, ZAR: 18.9, SEK: 10.4, NOK: 10.6, DKK: 6.86, PLN: 4.0,
  TRY: 32.5, RUB: 92, KRW: 1370, THB: 35.8, IDR: 15700, PHP: 56.5,
  MYR: 4.7, VND: 24500, AED: 3.67, SAR: 3.75, ILS: 3.7, EGP: 48.5,
  ARS: 920, CLP: 970, COP: 3950, PKR: 278, BDT: 117, NGN: 1550,
  UAH: 41, CZK: 23.2, HUF: 365, RON: 4.58, ISK: 138,
};

const curState = {
  from: lsGet('conv.cur.from', 'USD'),
  to: lsGet('conv.cur.to', 'EUR'),
  rates: null,
  offline: false,
  updatedAt: null,
};

const curFromValueEl = document.getElementById('curFromValue');
const curToValueEl = document.getElementById('curToValue');
const curFromUnitEl = document.getElementById('curFromUnit');
const curToUnitEl = document.getElementById('curToUnit');
const curRateLineEl = document.getElementById('curRateLine');
const curQuickAmountsEl = document.getElementById('curQuickAmounts');

function currencyOptionsHtml(codes) {
  const ordered = [
    ...CURRENCY_PRIORITY.filter(c => codes.includes(c)),
    ...codes.filter(c => !CURRENCY_PRIORITY.includes(c)).sort(),
  ];
  return ordered.map(c => `<option value="${c}">${c} — ${CURRENCY_META[c] || c}</option>`).join('');
}

function populateCurrencySelects() {
  const codes = Object.keys(curState.rates);
  curFromUnitEl.innerHTML = currencyOptionsHtml(codes);
  curToUnitEl.innerHTML = currencyOptionsHtml(codes);
  curFromUnitEl.value = codes.includes(curState.from) ? curState.from : 'USD';
  curToUnitEl.value = codes.includes(curState.to) ? curState.to : 'EUR';
}

function computeCurrency() {
  if (!curState.rates) return;
  const from = curFromUnitEl.value;
  const to = curToUnitEl.value;
  curState.from = from;
  curState.to = to;
  lsSet('conv.cur.from', from);
  lsSet('conv.cur.to', to);

  const raw = parseFloat(curFromValueEl.value);
  if (isNaN(raw)) {
    curToValueEl.value = '';
    return;
  }

  const amountUsd = raw / curState.rates[from];
  const result = amountUsd * curState.rates[to];
  curToValueEl.value = formatNumber(result);

  const oneRate = curState.rates[to] / curState.rates[from];
  const staleNote = curState.offline
    ? ` <span class="stale">· offline, approximate rates${curState.updatedAt ? ' (' + curState.updatedAt + ')' : ''}</span>`
    : ` · live rates as of ${curState.updatedAt || 'just now'}`;
  curRateLineEl.innerHTML = `1 ${from} = ${formatNumber(oneRate)} ${to}${staleNote}`;
}

async function loadCurrencyRates() {
  try {
    const res = await fetch('https://open.er-api.com/v6/latest/USD');
    if (!res.ok) throw new Error('bad response');
    const json = await res.json();
    if (json.result !== 'success' || !json.rates) throw new Error('bad payload');
    const rates = {};
    Object.keys(CURRENCY_META).forEach(code => {
      if (typeof json.rates[code] === 'number') rates[code] = json.rates[code];
    });
    rates.USD = 1;
    curState.rates = rates;
    curState.offline = false;
    curState.updatedAt = json.time_last_update_utc || null;
  } catch (e) {
    curState.rates = STATIC_RATES;
    curState.offline = true;
    curState.updatedAt = null;
  }

  populateCurrencySelects();
  computeCurrency();
}

curFromValueEl.addEventListener('input', computeCurrency);
curFromUnitEl.addEventListener('change', computeCurrency);
curToUnitEl.addEventListener('change', computeCurrency);
document.getElementById('curSwap').addEventListener('click', () => {
  const f = curFromUnitEl.value;
  curFromUnitEl.value = curToUnitEl.value;
  curToUnitEl.value = f;
  computeCurrency();
});

[1, 10, 50, 100, 500, 1000].forEach(amount => {
  const chip = document.createElement('button');
  chip.className = 'chip';
  chip.textContent = amount.toLocaleString('en-US');
  chip.addEventListener('click', () => {
    curFromValueEl.value = amount;
    computeCurrency();
  });
  curQuickAmountsEl.appendChild(chip);
});

loadCurrencyRates();

/* =========================================================================
   TIMEZONE
   ========================================================================= */

const COMMON_ZONES = [
  'UTC', 'America/New_York', 'America/Chicago', 'America/Denver', 'America/Los_Angeles',
  'America/Sao_Paulo', 'America/Mexico_City', 'America/Toronto', 'America/Bogota',
  'Europe/London', 'Europe/Paris', 'Europe/Berlin', 'Europe/Madrid', 'Europe/Rome',
  'Europe/Moscow', 'Europe/Istanbul', 'Africa/Cairo', 'Africa/Johannesburg', 'Africa/Lagos',
  'Asia/Dubai', 'Asia/Karachi', 'Asia/Kolkata', 'Asia/Dhaka', 'Asia/Bangkok',
  'Asia/Jakarta', 'Asia/Singapore', 'Asia/Shanghai', 'Asia/Hong_Kong', 'Asia/Tokyo',
  'Asia/Seoul', 'Australia/Sydney', 'Australia/Perth', 'Pacific/Auckland',
];

const ALL_ZONES = (typeof Intl.supportedValuesOf === 'function')
  ? Intl.supportedValuesOf('timeZone')
  : COMMON_ZONES;

// The browser's supported-zones list can use a different id than the common
// alias (e.g. "Asia/Calcutta" instead of "Asia/Kolkata"); resolve to whatever
// id the runtime actually recognizes so aliases still match ALL_ZONES.
function canonicalZone(zone) {
  try {
    return new Intl.DateTimeFormat('en-US', { timeZone: zone }).resolvedOptions().timeZone;
  } catch (e) {
    return null;
  }
}

function zoneLabel(zone) {
  if (zone === 'UTC') return 'UTC';
  const parts = zone.split('/');
  return parts.slice(1).join(' – ').replace(/_/g, ' ') || zone;
}

function zoneRegion(zone) {
  return zone.split('/')[0].replace(/_/g, ' ');
}

const detectedZone = (() => {
  try { return Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC'; }
  catch (e) { return 'UTC'; }
})();

const tzState = {
  fromZone: canonicalZone(lsGet('conv.tz.fromZone', detectedZone)),
  zones: lsGet('conv.tz.zones', null),
};
if (!tzState.fromZone || !ALL_ZONES.includes(tzState.fromZone)) tzState.fromZone = detectedZone;
if (!Array.isArray(tzState.zones)) {
  tzState.zones = ['UTC', 'America/New_York', 'Europe/London', 'Asia/Tokyo', 'Asia/Kolkata']
    .map(canonicalZone)
    .filter(z => z && z !== tzState.fromZone);
} else {
  tzState.zones = tzState.zones.map(canonicalZone);
}
tzState.zones = tzState.zones.filter(z => z && ALL_ZONES.includes(z));

const tzDateTimeEl = document.getElementById('tzDateTime');
const tzFromZoneEl = document.getElementById('tzFromZone');
const tzAddZoneEl = document.getElementById('tzAddZone');
const tzListEl = document.getElementById('tzList');

function buildZoneOptionsHtml() {
  const sorted = [...ALL_ZONES].sort((a, b) => zoneLabel(a).localeCompare(zoneLabel(b)));
  return sorted.map(z => `<option value="${z}">${zoneRegion(z)} — ${zoneLabel(z)}</option>`).join('');
}

tzFromZoneEl.innerHTML = buildZoneOptionsHtml();
tzAddZoneEl.innerHTML = buildZoneOptionsHtml();
tzFromZoneEl.value = tzState.fromZone;

function getZoneOffsetMinutes(zone, date) {
  const dtf = new Intl.DateTimeFormat('en-US', {
    timeZone: zone, hour12: false,
    year: 'numeric', month: '2-digit', day: '2-digit',
    hour: '2-digit', minute: '2-digit', second: '2-digit',
  });
  const parts = {};
  dtf.formatToParts(date).forEach(p => { parts[p.type] = p.value; });
  const hour = parts.hour === '24' ? '00' : parts.hour;
  const asUtc = Date.UTC(+parts.year, +parts.month - 1, +parts.day, +hour, +parts.minute, +parts.second);
  return (asUtc - date.getTime()) / 60000;
}

function zonedInputToUtc(dateTimeLocalStr, zone) {
  const guessUtc = new Date(dateTimeLocalStr + ':00Z');
  const offsetMin = getZoneOffsetMinutes(zone, guessUtc);
  return new Date(guessUtc.getTime() - offsetMin * 60000);
}

function toDateTimeLocalValue(date, zone) {
  const dtf = new Intl.DateTimeFormat('en-US', {
    timeZone: zone, hour12: false,
    year: 'numeric', month: '2-digit', day: '2-digit',
    hour: '2-digit', minute: '2-digit',
  });
  const parts = {};
  dtf.formatToParts(date).forEach(p => { parts[p.type] = p.value; });
  const hour = parts.hour === '24' ? '00' : parts.hour;
  return `${parts.year}-${parts.month}-${parts.day}T${hour}:${parts.minute}`;
}

function zoneOffsetLabel(zone, utcDate) {
  const dtf = new Intl.DateTimeFormat('en-US', { timeZone: zone, timeZoneName: 'shortOffset' });
  const part = dtf.formatToParts(utcDate).find(p => p.type === 'timeZoneName');
  return part ? part.value.replace('GMT', 'UTC') : '';
}

function formatZoneTime(zone, utcDate) {
  const timeFmt = new Intl.DateTimeFormat('en-US', { timeZone: zone, hour: 'numeric', minute: '2-digit', hour12: true });
  const dateFmt = new Intl.DateTimeFormat('en-US', { timeZone: zone, weekday: 'short', month: 'short', day: 'numeric' });
  return { time: timeFmt.format(utcDate), date: dateFmt.format(utcDate) };
}

function zoneDayNumber(zone, utcDate) {
  const dtf = new Intl.DateTimeFormat('en-US', { timeZone: zone, year: 'numeric', month: '2-digit', day: '2-digit' });
  const parts = {};
  dtf.formatToParts(utcDate).forEach(p => { parts[p.type] = p.value; });
  return Date.UTC(+parts.year, +parts.month - 1, +parts.day);
}

function renderTzList() {
  if (!tzDateTimeEl.value) return;
  const utcInstant = zonedInputToUtc(tzDateTimeEl.value, tzState.fromZone);
  const originDay = zoneDayNumber(tzState.fromZone, utcInstant);

  const rowsHtml = [tzState.fromZone, ...tzState.zones].map((zone, idx) => {
    const isOrigin = idx === 0;
    const { time, date } = formatZoneTime(zone, utcInstant);
    const offset = zoneOffsetLabel(zone, utcInstant);
    const dayDiff = Math.round((zoneDayNumber(zone, utcInstant) - originDay) / 86400000);
    const dayTag = isOrigin ? '' : dayDiff === 0 ? 'same day' : dayDiff > 0 ? `+${dayDiff}d` : `${dayDiff}d`;

    return `<div class="tz-row${isOrigin ? ' origin' : ''}" data-zone="${zone}">
      <div class="tz-row-main">
        <div class="tz-row-name">${zoneRegion(zone)} — ${zoneLabel(zone)}${isOrigin ? '<span class="badge">from</span>' : ''}</div>
        <div class="tz-row-meta">${offset}${dayTag ? ' · ' + dayTag : ''}</div>
      </div>
      <div class="tz-row-right">
        <div class="tz-row-time">${time}</div>
        <div class="tz-row-date">${date}</div>
      </div>
      ${isOrigin ? '' : '<button class="tz-remove" title="Remove" data-remove-zone="' + zone + '">✕</button>'}
    </div>`;
  }).join('');

  tzListEl.innerHTML = rowsHtml;
}

function persistTzState() {
  lsSet('conv.tz.fromZone', tzState.fromZone);
  lsSet('conv.tz.zones', tzState.zones);
}

tzListEl.addEventListener('click', (e) => {
  const btn = e.target.closest('[data-remove-zone]');
  if (!btn) return;
  const zone = btn.dataset.removeZone;
  tzState.zones = tzState.zones.filter(z => z !== zone);
  persistTzState();
  renderTzList();
});

document.getElementById('tzAddBtn').addEventListener('click', () => {
  const zone = tzAddZoneEl.value;
  if (!zone || zone === tzState.fromZone || tzState.zones.includes(zone)) return;
  tzState.zones.push(zone);
  persistTzState();
  renderTzList();
});

tzFromZoneEl.addEventListener('change', () => {
  const prevZone = tzState.fromZone;
  const utcInstant = tzDateTimeEl.value ? zonedInputToUtc(tzDateTimeEl.value, prevZone) : new Date();
  tzState.fromZone = tzFromZoneEl.value;
  tzDateTimeEl.value = toDateTimeLocalValue(utcInstant, tzState.fromZone);
  persistTzState();
  renderTzList();
});

tzDateTimeEl.addEventListener('input', renderTzList);

document.getElementById('tzNowBtn').addEventListener('click', () => {
  tzState.fromZone = detectedZone;
  tzFromZoneEl.value = detectedZone;
  tzDateTimeEl.value = toDateTimeLocalValue(new Date(), detectedZone);
  persistTzState();
  renderTzList();
});

tzDateTimeEl.value = toDateTimeLocalValue(new Date(), tzState.fromZone);
renderTzList();
