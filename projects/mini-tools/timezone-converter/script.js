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

// A typeable dropdown: a text input filters a floating list of options as you
// type (matching whatever `searchTextFor` returns), with arrow-key navigation
// and Enter/click to select. Exposes `.value` so callers can treat it like a
// plain <select>. Used for the timezone pickers, which have a list too long
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
const tzListEl = document.getElementById('tzList');

function zoneFullLabel(zone) {
  return `${zoneRegion(zone)} — ${zoneLabel(zone)}`;
}
function zoneSearchText(zone) {
  return `${zone} ${zoneFullLabel(zone)}`;
}

const SORTED_ZONES = [...ALL_ZONES].sort((a, b) => zoneFullLabel(a).localeCompare(zoneFullLabel(b)));

const tzFromCombo = new SearchCombo('tzFromCombo', 'tzFromZone', 'tzFromList', {
  labelFor: zoneFullLabel, searchTextFor: zoneSearchText, onChange: handleFromZoneChange,
});
const tzAddCombo = new SearchCombo('tzAddCombo', 'tzAddZone', 'tzAddList', {
  labelFor: zoneFullLabel, searchTextFor: zoneSearchText,
});

tzFromCombo.setOptions(SORTED_ZONES);
tzAddCombo.setOptions(SORTED_ZONES);
tzFromCombo.value = tzState.fromZone;
tzAddCombo.value = SORTED_ZONES[0];

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
  const zone = tzAddCombo.value;
  if (!zone || zone === tzState.fromZone || tzState.zones.includes(zone)) return;
  tzState.zones.push(zone);
  persistTzState();
  renderTzList();
});

function handleFromZoneChange(newZone) {
  const prevZone = tzState.fromZone;
  const utcInstant = tzDateTimeEl.value ? zonedInputToUtc(tzDateTimeEl.value, prevZone) : new Date();
  tzState.fromZone = newZone;
  tzDateTimeEl.value = toDateTimeLocalValue(utcInstant, tzState.fromZone);
  persistTzState();
  renderTzList();
}

tzDateTimeEl.addEventListener('input', renderTzList);

document.getElementById('tzNowBtn').addEventListener('click', () => {
  tzState.fromZone = detectedZone;
  tzFromCombo.value = detectedZone;
  tzDateTimeEl.value = toDateTimeLocalValue(new Date(), detectedZone);
  persistTzState();
  renderTzList();
});

tzDateTimeEl.value = toDateTimeLocalValue(new Date(), tzState.fromZone);
renderTzList();
