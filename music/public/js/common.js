/* Shared by the shelf and the artist pages: talking to the API, and the drawer.
 *
 * Both pages used to call Discogs directly from the browser, which meant a
 * 25-requests-a-minute anonymous limit, no images, and nothing of Bruno's own
 * in the drawer. They now read this site's own database instead — so the
 * drawer shows what the admin says it should, including the notes and
 * corrections typed in there, and there is no rate limit to hit.
 */

const API_BASE = document.body.dataset.api || 'api/';
const DATA_VERSION = document.body.dataset.version || '';
const $ = id => document.getElementById(id);

function esc(value) {
  return String(value ?? '').replace(/[&<>"']/g, c => (
    { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
  ));
}

/* A stable "random" number per record, for the scatter on the floor. */
function hash(s) {
  let h = 0;
  for (const c of String(s)) h = (h * 31 + c.charCodeAt(0)) >>> 0;
  return h;
}

function formatSeconds(total) {
  const s = Number(total) || 0;
  return `${Math.floor(s / 60)}:${String(s % 60).padStart(2, '0')}`;
}

/* Only ever follow http(s) links, whatever the data says. */
function safeUrl(url) {
  return /^https?:\/\//i.test(url || '') ? url : '';
}

async function fetchJSON(path) {
  // The version makes each edit a new URL, so the browser's own copy of the
  // response (the API allows five minutes) can't hide it.
  const url = API_BASE + path + (path.includes('?') ? '&' : '?') + 'v=' + encodeURIComponent(DATA_VERSION);
  const response = await fetch(url, { headers: { Accept: 'application/json' } });

  if (!response.ok) {
    const body = await response.json().catch(() => ({}));
    throw new Error(body.error || `The collection server answered ${response.status}.`);
  }

  return response.json();
}

function coverFallbackSVG() {
  return `<div class="cover-fallback"><svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
    <circle cx="50" cy="50" r="48" fill="#F2EAD8"/>
    <circle cx="50" cy="50" r="16" fill="#C99A2E"/>
    <circle cx="50" cy="50" r="3" fill="#17140F"/>
  </svg></div>`;
}

/* ---------- The drawer ----------
 *
 * The server decides what goes in it (see /admin_fields), so this only has to
 * know how to draw each shape of value: a line of text, a list of lines, tags,
 * a tracklist, credits, links, a gallery.
 */

const drawerCache = new Map();
let currentItemId = null;

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
      // Still escaped — 'html' means "may run to several lines", not "trusted".
      html = `<span class="notes">${esc(value).replace(/\n/g, '<br>')}</span>`;
      break;
    default:
      html = esc(value);
  }

  // A listing's price gets its own class so the shop can draw it the way the
  // card does (bold, gold) rather than like any other fact.
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

const SECTION_LABELS = {
  gallery: 'Images',
  tracklist: 'Tracklist',
  credits: 'Credits',
  companies: 'Companies',
  identifiers: 'Identifiers',
  videos: 'Videos',
  links: 'Links',
};

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

async function openDrawer(id, fromHash, source) {
  currentItemId = id;

  // The drawer is addressable: /lady-gaga#item-412 opens straight into that
  // record, which is what makes a link to one sendable. replaceState would
  // swallow the back button, so pushState is used and popstate closes it.
  if (!fromHash && location.hash !== `#item-${id}`) {
    history.pushState({ item: id }, '', `#item-${id}`);
  }

  const cached = drawerCache.get(id);
  $('drawerBody').innerHTML = cached
    ? renderDrawerBody(cached)
    : '<div class="detail-state">Opening…</div>';
  $('drawerCover').innerHTML = cached && cached.cover
    ? `<img src="${esc(cached.cover)}" alt="">`
    : coverFallbackSVG();

  $('drawer').scrollTop = 0;
  // the record stands up on the left of the page while the drawer opens on the right
  if (typeof Spotlight !== 'undefined') Spotlight.open(id, source);
  $('overlay').classList.add('open');
  $('drawer').classList.add('open');

  if (cached) return;

  try {
    const item = await fetchJSON(`item?id=${encodeURIComponent(id)}`);
    drawerCache.set(id, item);
    // Someone can click another sleeve while this is in flight; only paint if
    // this is still the record on screen.
    if (currentItemId !== id) return;

    $('drawerBody').innerHTML = renderDrawerBody(item);
    if (item.cover) $('drawerCover').innerHTML = `<img src="${esc(item.cover)}" alt="">`;
  } catch (error) {
    if (currentItemId === id) $('drawerBody').innerHTML = renderDrawerBody(null, error.message);
  }
}

function closeDrawer(fromHash) {
  currentItemId = null;
  $('overlay').classList.remove('open');
  $('drawer').classList.remove('open');
  if (typeof Spotlight !== 'undefined') Spotlight.close();

  if (!fromHash && location.hash.startsWith('#item-')) {
    history.pushState({}, '', location.pathname + location.search);
  }
}

/** The id in the URL right now, or null. */
function hashItemId() {
  const match = location.hash.match(/^#item-(\d+)$/);
  return match ? Number(match[1]) : null;
}

function wireDrawer() {
  $('drawerBody').addEventListener('click', event => {
    const thumb = event.target.closest('[data-cover]');
    if (thumb) {
      $('drawerCover').innerHTML = `<img src="${esc(thumb.dataset.cover)}" alt="">`;
      if (typeof Spotlight !== 'undefined') Spotlight.cover(thumb.dataset.cover);
      return;
    }
    if (event.target.closest('[data-retry]') && currentItemId !== null) {
      const id = currentItemId;
      drawerCache.delete(id);
      openDrawer(id);
    }
  });

  $('overlay').addEventListener('click', () => closeDrawer());
  $('drawerClose').addEventListener('click', () => closeDrawer());
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape') closeDrawer();
  });

  // Back and forward move through the drawers that were opened, and a link
  // pasted with #item-… opens one on arrival.
  addEventListener('popstate', () => {
    const id = hashItemId();
    if (id === null) closeDrawer(true);
    else if (id !== currentItemId) openDrawer(id, true);
  });

  const initial = hashItemId();
  if (initial !== null) openDrawer(initial, true);
}

/* ---------- A small client-side cache ----------
 *
 * The data only changes when a sync runs or an item is edited, so a page revisit
 * shouldn't wait on the network to draw the shelf. A cached copy is used only
 * while the page's data version still matches the one it was saved under, so a
 * sync or an admin edit is picked up on the next visit without anyone having to
 * clear anything by hand.
 */

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
    /* storage full or unavailable — the page works without it */
  }
}

function clearCache(key) {
  try {
    localStorage.removeItem(key);
  } catch (error) {
    /* ignore */
  }
}
