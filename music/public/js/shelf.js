/* What the public pages' own scripts share: their stored preferences, the
 * format chips, the toggle buttons and loading their records. */

const CACHE_TTL = 1000 * 60 * 60 * 6;

/** Preferences kept in localStorage; `fix` corrects stored values that are no longer valid. prefs.save() writes them back. */
function storedPrefs(key, defaults, fix = prefs => prefs) {
  let prefs;
  try {
    prefs = fix({ ...defaults, ...JSON.parse(localStorage.getItem(key) || '{}') });
  } catch (error) {
    prefs = defaults;
  }

  Object.defineProperty(prefs, 'save', {
    value() {
      try { localStorage.setItem(key, JSON.stringify(prefs)); } catch (error) { /* storage unavailable */ }
    },
  });

  return prefs;
}

/** The page's data from the browser's cache while it is fresh and `usable`, else from the API (and cached). */
async function loadData(cacheKey, path, { force = false, usable = Boolean, keep = data => data } = {}) {
  if (!force) {
    const cached = loadCache(cacheKey, CACHE_TTL);
    if (cached && usable(cached)) return cached;
  }

  const data = await fetchJSON(path);
  saveCache(cacheKey, keep(data));
  return keep(data);
}

/** body.view-floor, view-grid or view-list, for the stylesheet. */
function markView(views, view) {
  views.forEach(v => document.body.classList.toggle('view-' + v, view === v));
}

/**
 * The format chips over the records, each with its count. A format the records
 * no longer have falls back to All.
 */
function renderFormatChips(items, prefs, allCount = items.length) {
  const counts = {};
  items.forEach(it => { counts[it.kind] = (counts[it.kind] || 0) + 1; });
  if (prefs.fmt !== 'all' && !counts[prefs.fmt]) prefs.fmt = 'all';

  $('formats').innerHTML = [['all', 'All'], ...Object.entries(KIND_LABEL)]
    .filter(([k]) => k === 'all' || counts[k])
    .map(([k, label]) => `<button type="button" data-k="${k}" class="${prefs.fmt === k ? 'on' : ''}">${label} <i>${k === 'all' ? allCount : counts[k]}</i></button>`)
    .join('');
}

function bindFormatChips(prefs, renderChips, render) {
  $('formats').addEventListener('click', e => {
    const b = e.target.closest('button');
    if (!b) return;
    prefs.fmt = b.dataset.k;
    prefs.save();
    renderChips();
    render();
  });
}

/** Lights the button of a toggle group (#viewToggle, #orderToggle) whose data-[key] is `value`. */
function markToggle(id, key, value) {
  document.querySelectorAll(`#${id} button`).forEach(b => b.classList.toggle('on', b.dataset[key] === value));
}

/** Calls onPick with the data-[key] of whichever button of a toggle group is clicked. */
function onToggle(id, key, onPick) {
  $(id).addEventListener('click', e => {
    const b = e.target.closest('button');
    if (b) onPick(b.dataset[key]);
  });
}

/** A toggle group bound to prefs[key]: picking one saves it and draws the page again. */
function bindToggle(id, key, prefs, render) {
  onToggle(id, key, value => {
    prefs[key] = value;
    prefs.save();
    markToggle(id, key, value);
    render();
  });
}

/** What a list's column header does when clicked: sort by it, or flip it. */
function sortByColumn(prefs, render) {
  return key => {
    prefs.sort = nextSort(prefs.sort, key);
    prefs.save();
    render();
  };
}

/** The Messiness slider over the pile. */
function bindMessSlider(prefs) {
  $('mess').value = prefs.mess;
  $('mess').addEventListener('input', e => {
    prefs.mess = Number(e.target.value);
    document.querySelectorAll('.floor').forEach(floor => floor.style.setProperty('--mess', prefs.mess));
    prefs.save();
  });
}
