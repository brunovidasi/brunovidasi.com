/* What every public page needs first: reading this site's API, the drawer that
 * opens on a record, and a few small helpers. The drawer shows only what the
 * server sends, which is what /admin_fields says it should. */

const API_BASE = document.body.dataset.api || 'api/';
const DATA_VERSION = document.body.dataset.version || '';
const $ = id => document.getElementById(id);

function esc(value) {
  return String(value ?? '').replace(/[&<>"']/g, c => (
    { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
  ));
}

/** A stable "random" number per record, for the scatter on the floor. */
function hash(s) {
  let h = 0;
  for (const c of String(s)) h = (h * 31 + c.charCodeAt(0)) >>> 0;
  return h;
}

/** Only ever follow http(s) links, whatever the data says. */
function safeUrl(url) {
  return /^https?:\/\//i.test(url || '') ? url : '';
}

const clamp = (value, min, max) => Math.max(min, Math.min(max, value));
const lerp = (a, b, t) => a + (b - a) * t;
const easeInOutCubic = t => (t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2);
const prefersReducedMotion = () => matchMedia('(prefers-reduced-motion: reduce)').matches;

async function fetchJSON(path) {
  // The data version makes each change a new URL, so a cached response can't hide it.
  const url = API_BASE + path + (path.includes('?') ? '&' : '?') + 'v=' + encodeURIComponent(DATA_VERSION);
  const response = await fetch(url, { headers: { Accept: 'application/json' } });

  if (!response.ok) {
    const body = await response.json().catch(() => ({}));
    throw new Error(body.error || `The collection server answered ${response.status}.`);
  }

  return response.json();
}

/* ---------- The drawer ---------- */

const drawerCache = new Map();
let currentItemId = null;

const SECTION_LABELS = {
  gallery: 'Images',
  tracklist: 'Tracklist',
  credits: 'Credits',
  companies: 'Companies',
  identifiers: 'Identifiers',
  videos: 'Videos',
  links: 'Links',
};

function factHtml(fact) {
  const value = fact.value;
  let html;

  switch (fact.type) {
    case 'lines':
      html = (Array.isArray(value) ? value : [value]).map(esc).join('<br>');
      break;
    case 'tags':
      html = `<span class="tags">${(value || []).map(v => `<span class="tag">${esc(v)}</span>`).join('')}</span>`;
      break;
    case 'html':
      // Still escaped: 'html' means "may run to several lines", not "trusted".
      html = `<span class="notes">${esc(value).replace(/\n/g, '<br>')}</span>`;
      break;
    default:
      html = esc(value);
  }

  const classes = [fact.mine && 'mine', fact.type === 'price' && 'price'].filter(Boolean).join(' ');

  return `<dt>${esc(fact.label)}</dt><dd${classes ? ` class="${classes}"` : ''}>${html}</dd>`;
}

function trackHtml(track) {
  if (track.type_ === 'heading') return `<li class="track-heading">${esc(track.title)}</li>`;

  const artists = (track.artists || []).map(a => a.name).join(', ');
  const credits = (track.extraartists || [])
    .map(c => `${esc(c.role)} ${esc(c.name)}`)
    .join(' · ');
  const subs = (track.sub_tracks || []).length
    ? `<ol class="tracklist sub">${track.sub_tracks.map(trackHtml).join('')}</ol>`
    : '';

  return `<li class="track">
    <span class="pos">${esc(track.position)}</span>
    <div class="track-main">${esc(track.title)}
      ${artists ? `<div class="track-artist">${esc(artists)}</div>` : ''}
      ${credits ? `<div class="track-credits">${credits}</div>` : ''}
      ${subs}
    </div>
    <span class="dur">${esc(track.duration)}</span>
  </li>`;
}

function sectionHtml(key, label, value) {
  let body = '';

  if (key === 'tracklist') {
    body = `<ol class="tracklist">${value.map(trackHtml).join('')}</ol>`;
  } else if (key === 'gallery') {
    if (value.length < 2) return '';
    body = `<div class="gallery">${value.map(image => `
      <button class="thumb" data-cover="${esc(image.full)}" aria-label="Show ${esc(image.type)} image">
        <img src="${esc(image.thumb)}" alt="" loading="lazy">
      </button>`).join('')}</div>`;
  } else if (key === 'links' || key === 'videos') {
    body = value
      .filter(link => safeUrl(link.url))
      .map(link => `<div class="credit"><a href="${esc(link.url)}" target="_blank" rel="noopener">${esc(link.label)}</a></div>`)
      .join('');
  } else if (Array.isArray(value)) {
    body = value.map(row => `<div class="credit"><span class="role">${esc(row.role)}</span> ${esc(row.text)}</div>`).join('');
  } else {
    body = `<p class="notes">${esc(value)}</p>`;
  }

  return body ? `<section class="detail"><h4>${esc(label)}</h4>${body}</section>` : '';
}

function renderDrawerBody(item, error) {
  if (error) {
    return `<div class="detail-state">Couldn't load this record. ${esc(error)}
      <button class="ghost" data-retry>Try again</button></div>`;
  }

  const mine = item.mine.length
    ? `<dl class="facts mine-facts">${item.mine.map(factHtml).join('')}</dl>`
    : '';
  const facts = item.facts.length
    ? `<dl class="facts">${item.facts.map(factHtml).join('')}</dl>`
    : '';
  const sections = Object.entries(item.sections)
    .map(([key, value]) => sectionHtml(key, SECTION_LABELS[key] || key, value))
    .join('');

  return `
    <h2>${esc(item.title)}</h2>
    <div class="artist">${esc(item.artist)}${item.year ? ' · ' + item.year : ''}
      <i class="kc ${esc(item.kind)}">${esc(item.kindLabel)}</i></div>
    ${mine}
    ${facts}
    ${item.pending ? '<div class="detail-state">The full Discogs detail for this one hasn\'t been fetched yet.</div>' : ''}
    ${sections}`;
}

/* ---------- The drawer's pictures ----------
   The cover and the rest of the gallery side by side in a strip that snaps
   one at a time, so a finger swipes through them. The gallery's thumbnails
   jump the strip to theirs; a count in the corner says where it is. */

let coverUrls = [];
let coverAt = 0;

function coverSlides(item) {
  const gallery = ((item && item.sections && item.sections.gallery) || []).map(image => image.full);
  return [...new Set([item && item.cover, ...gallery].filter(Boolean))];
}

function paintCover(urls) {
  coverUrls = urls;
  $('drawerCover').innerHTML = urls.length
    ? `<div class="cover-track">${urls.map((url, n) => `<img src="${esc(url)}" alt="" draggable="false"${n ? ' loading="lazy"' : ''}>`).join('')}</div>`
      + (urls.length > 1 ? `<span class="cover-count" aria-hidden="true"></span>` : '')
    : `<div class="cover-fallback"><svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
    <circle cx="50" cy="50" r="48" fill="#F2EAD8"/>
    <circle cx="50" cy="50" r="16" fill="#C99A2E"/>
    <circle cx="50" cy="50" r="3" fill="#17140F"/>
  </svg></div>`;
  markSlide(0);
}

/** Which picture is showing: the count, and its thumbnail lit. */
function markSlide(n) {
  coverAt = n;
  const count = $('drawerCover').querySelector('.cover-count');
  if (count) count.textContent = `${n + 1} / ${coverUrls.length}`;
  $('drawerBody').querySelectorAll('.thumb').forEach(thumb => {
    thumb.classList.toggle('on', thumb.dataset.cover === coverUrls[n]);
  });
}

/** A thumbnail was picked: the strip slides to its picture, and the spotlight shows it too. */
function showSlide(url) {
  if (typeof Spotlight !== 'undefined') Spotlight.cover(url);
  let n = coverUrls.indexOf(url);
  if (n < 0) {
    paintCover([...coverUrls, url]);
    n = coverUrls.length - 1;
  }
  markSlide(n);
  const track = $('drawerCover').querySelector('.cover-track');
  if (!track) return;
  track.scrollTo({ left: n * track.clientWidth, behavior: prefersReducedMotion() ? 'auto' : 'smooth' });
}

/* ---------- Opening and closing ---------- */

async function openDrawer(id, fromHash, source) {
  currentItemId = id;

  // #item-412 opens straight into a record, so a link to one can be sent.
  // pushState rather than replaceState, so the back button closes it.
  if (!fromHash && location.hash !== `#item-${id}`) {
    history.pushState({ item: id }, '', `#item-${id}`);
  }

  const cached = drawerCache.get(id);
  $('drawerBody').innerHTML = cached
    ? renderDrawerBody(cached)
    : '<div class="detail-state">Opening…</div>';
  paintCover(cached ? coverSlides(cached) : []);

  $('drawer').scrollTop = 0;
  if (typeof Spotlight !== 'undefined') Spotlight.open(id, source);
  $('overlay').classList.add('open');
  $('drawer').classList.add('open');

  if (cached) return;

  try {
    const item = await fetchJSON(`item?id=${encodeURIComponent(id)}`);
    drawerCache.set(id, item);
    // Another record may have been opened while this one loaded.
    if (currentItemId !== id) return;

    $('drawerBody').innerHTML = renderDrawerBody(item);
    const slides = coverSlides(item);
    if (slides.length) paintCover(slides);
  } catch (error) {
    if (currentItemId === id) $('drawerBody').innerHTML = renderDrawerBody(null, error.message);
  }
}

function closeDrawer(fromHash) {
  const id = currentItemId;
  currentItemId = null;
  if (typeof releaseTile === 'function') releaseTile();
  $('overlay').classList.remove('open');
  $('drawer').classList.remove('open');
  if (typeof Spotlight !== 'undefined') Spotlight.close();

  // Undo the entry openDrawer pushed, so Back doesn't reopen what was just closed.
  // A drawer opened from a pasted link has no entry of its own: just drop the hash.
  if (!fromHash && location.hash.startsWith('#item-')) {
    if (history.state && history.state.item === id) history.back();
    else history.replaceState(null, '', location.pathname + location.search);
  }
}

/** The record id in the URL's #item-…, or null. */
function hashItemId() {
  const match = location.hash.match(/^#item-(\d+)$/);
  return match ? Number(match[1]) : null;
}

function wireDrawer() {
  $('drawerBody').addEventListener('click', event => {
    const thumb = event.target.closest('[data-cover]');
    if (thumb) {
      showSlide(thumb.dataset.cover);
      return;
    }
    if (event.target.closest('[data-retry]') && currentItemId !== null) {
      const id = currentItemId;
      drawerCache.delete(id);
      openDrawer(id);
    }
  });

  // Scroll doesn't bubble, and the strip is drawn afresh for every record, so this listens in the capture phase.
  $('drawerCover').addEventListener('scroll', event => {
    const track = event.target;
    if (!track.classList || !track.classList.contains('cover-track')) return;
    const n = Math.round(track.scrollLeft / Math.max(1, track.clientWidth));
    if (n === coverAt || coverUrls[n] === undefined) return;
    markSlide(n);
    if (typeof Spotlight !== 'undefined') Spotlight.cover(coverUrls[n]);
  }, true);

  $('overlay').addEventListener('click', () => closeDrawer());
  $('drawerClose').addEventListener('click', () => closeDrawer());
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape') closeDrawer();
  });

  // Back and forward move between the drawers opened, and a pasted #item-… link opens one.
  addEventListener('popstate', () => {
    const id = hashItemId();
    if (id === null) { if (currentItemId !== null) closeDrawer(true); }
    else if (id !== currentItemId) openDrawer(id, true);
  });

  const initial = hashItemId();
  if (initial !== null) openDrawer(initial, true);
}

/* ---------- The browser's copy of the data ----------
   Used only while the page's data version matches the one it was saved
   under, so a sync or an edit in the admin shows on the next visit. */

function loadCache(key, ttlMs) {
  try {
    const raw = localStorage.getItem(key);
    if (!raw) return null;
    const parsed = JSON.parse(raw);
    return parsed.v !== DATA_VERSION || Date.now() - parsed.at > ttlMs ? null : parsed.data;
  } catch (error) {
    return null;
  }
}

function saveCache(key, data) {
  try {
    localStorage.setItem(key, JSON.stringify({ at: Date.now(), v: DATA_VERSION, data }));
  } catch (error) {
    /* storage full or unavailable: the page works without it */
  }
}

function clearCache(key) {
  try {
    localStorage.removeItem(key);
  } catch (error) {
    /* ignore */
  }
}
