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
// plain <select>. Used for the currency pickers, which have a list too long
// to scan by eye.
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
const curRateLineEl = document.getElementById('curRateLine');
const curQuickAmountsEl = document.getElementById('curQuickAmounts');

function orderedCurrencyCodes(codes) {
  return [
    ...CURRENCY_PRIORITY.filter(c => codes.includes(c)),
    ...codes.filter(c => !CURRENCY_PRIORITY.includes(c)).sort(),
  ];
}

function currencyLabel(code) {
  return `${code} — ${CURRENCY_META[code] || code}`;
}
function currencySearchText(code) {
  return `${code} ${CURRENCY_META[code] || ''}`;
}

const curFromCombo = new SearchCombo('curFromCombo', 'curFromUnit', 'curFromList', {
  labelFor: currencyLabel, searchTextFor: currencySearchText, onChange: computeCurrency,
});
const curToCombo = new SearchCombo('curToCombo', 'curToUnit', 'curToList', {
  labelFor: currencyLabel, searchTextFor: currencySearchText, onChange: computeCurrency,
});

function populateCurrencySelects() {
  const codes = orderedCurrencyCodes(Object.keys(curState.rates));
  curFromCombo.setOptions(codes);
  curToCombo.setOptions(codes);
  curFromCombo.value = codes.includes(curState.from) ? curState.from : 'USD';
  curToCombo.value = codes.includes(curState.to) ? curState.to : 'EUR';
}

function computeCurrency() {
  if (!curState.rates) return;
  const from = curFromCombo.value;
  const to = curToCombo.value;
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
document.getElementById('curSwap').addEventListener('click', () => {
  const f = curFromCombo.value;
  curFromCombo.value = curToCombo.value;
  curToCombo.value = f;
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
