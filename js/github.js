// ==========================================================================
// .gitconfig panel: my GitHub profile, repos, languages and contributions.
//
// The data comes from json/github.json, which the deploy workflow generates
// with a token (see scripts/fetch-github.mjs). If that file is missing — a
// local checkout, or a failed fetch in CI — it falls back to the public REST
// API, which needs no token but has no pinned repos or contribution calendar.
// ==========================================================================

import { escapeHtml } from './utils.js';
import { ICON_GITHUB_SVG, ICON_LIVE_SVG } from './icons.js';

const LOGIN = 'brunovidasi';
const RECENT_REPO_COUNT = 6;

// ---- Loading --------------------------------------------------------------

function getJson(url){
  return fetch(url).then(res => {
    if(!res.ok) throw new Error(`${res.status} ${res.statusText}`);
    return res.json();
  });
}

/** Reshapes the REST profile + repos into the snapshot's format. */
async function loadFromRestApi(){
  const [user, repos] = await Promise.all([
    getJson(`https://api.github.com/users/${LOGIN}`),
    getJson(`https://api.github.com/users/${LOGIN}/repos?sort=pushed&per_page=100`)
  ]);
  const own = repos.filter(r => !r.fork);
  return {
    profile: {
      login: user.login,
      name: user.name,
      avatarUrl: user.avatar_url,
      bio: user.bio,
      location: user.location,
      url: user.html_url,
      followers: user.followers,
      publicRepos: own.length,
      totalStars: own.reduce((n, r) => n + r.stargazers_count, 0)
    },
    pinned: [],
    repos: own.map(r => ({
      name: r.name,
      description: r.description,
      url: r.html_url,
      homepageUrl: r.homepage,
      stargazerCount: r.stargazers_count,
      forkCount: r.forks_count,
      pushedAt: r.pushed_at,
      language: r.language,
      languageColor: null
    })),
    languages: [],
    contributions: null
  };
}

function loadGithubData(){
  return getJson('json/github.json').catch(() => loadFromRestApi());
}

// ---- Formatting -----------------------------------------------------------

function timeAgo(iso){
  const days = Math.floor((Date.now() - new Date(iso)) / 86400000);
  if(days < 1) return 'today';
  if(days === 1) return 'yesterday';
  if(days < 30) return `${days} days ago`;
  const months = Math.floor(days / 30);
  if(months < 12) return `${months} month${months > 1 ? 's' : ''} ago`;
  const years = Math.floor(days / 365);
  return `${years} year${years > 1 ? 's' : ''} ago`;
}

/** Repo homepages are free text on GitHub, so some lack a scheme. */
function normaliseUrl(url){
  if(!url) return null;
  return /^https?:\/\//i.test(url) ? url : `https://${url}`;
}

// ---- Rendering ------------------------------------------------------------

function renderProfile(p){
  const meta = [p.bio, p.location].filter(Boolean).map(escapeHtml).join(' · ');
  return `
    <div class="gh-profile">
      <img class="gh-avatar" src="${escapeHtml(p.avatarUrl)}" alt="" width="72" height="72">
      <div class="gh-profile-text">
        <a class="gh-name" href="${escapeHtml(p.url)}" target="_blank" rel="noopener">${escapeHtml(p.name || p.login)} <span class="gh-login">@${escapeHtml(p.login)}</span></a>
        ${meta ? `<div class="gh-meta">${meta}</div>` : ''}
        <a class="doc-btn gh-profile-btn" href="${escapeHtml(p.url)}" target="_blank" rel="noopener">${ICON_GITHUB_SVG}View profile</a>
      </div>
    </div>`;
}

function renderStats(data){
  const p = data.profile;
  const stats = [
    ['publicRepos', p.publicRepos],
    ['stars', p.totalStars],
    ['followers', p.followers]
  ];
  if(data.contributions) stats.push(['contributions', data.contributions.total]);
  return `
    <div class="gh-stats">
      ${stats.map(([key, value]) => `
        <div class="gh-stat">
          <span class="gh-stat-key">${key}:</span>
          <span class="gh-stat-value">${Number(value).toLocaleString('en-AU')}</span>
        </div>`).join('')}
    </div>`;
}

/** Quartile buckets of the year's non-zero days, as GitHub itself does. */
function contributionLevels(counts){
  const sorted = counts.filter(Boolean).sort((a, b) => a - b);
  if(!sorted.length) return () => 0;
  const q = f => sorted[Math.floor((sorted.length - 1) * f)];
  const cuts = [q(.25), q(.5), q(.75)];
  return n => n === 0 ? 0 : 1 + cuts.filter(c => n > c).length;
}

function renderContributions(contributions){
  if(!contributions || !contributions.days.length) return '';
  const days = contributions.days;
  const levelOf = contributionLevels(days.map(([, n]) => n));
  // Pad the first week so each column runs Sunday to Saturday.
  const leading = new Date(days[0][0] + 'T00:00:00Z').getUTCDay();
  const cells = Array(leading).fill('<span class="gh-day gh-day-pad"></span>').concat(days.map(([date, n]) =>
    `<span class="gh-day" data-level="${levelOf(n)}" title="${n} contribution${n === 1 ? '' : 's'} on ${date}"></span>`
  ));
  return `
    <div class="skill-label mt-26">${contributions.total.toLocaleString('en-AU')} contributions in the last year</div>
    <div class="gh-calendar-wrap">
      <div class="gh-calendar">${cells.join('')}</div>
    </div>
    <div class="gh-legend">Less
      ${[0, 1, 2, 3, 4].map(l => `<span class="gh-day" data-level="${l}"></span>`).join('')}
    More</div>`;
}

function renderLanguages(languages){
  if(!languages.length) return '';
  return `
    <div class="skill-label mt-26">Top languages</div>
    <div class="gh-lang-bar">
      ${languages.map(l => `<span style="flex:${l.percent};background:${escapeHtml(l.color || 'var(--muted)')}" title="${escapeHtml(l.name)} ${l.percent}%"></span>`).join('')}
    </div>
    <div class="gh-lang-list">
      ${languages.map(l => `
        <span class="gh-lang"><span class="gh-lang-dot" style="background:${escapeHtml(l.color || 'var(--muted)')}"></span>${escapeHtml(l.name)} <span class="gh-lang-pct">${l.percent}%</span></span>`).join('')}
    </div>`;
}

function renderRepoCard(repo){
  const homepage = normaliseUrl(repo.homepageUrl);
  const langDot = repo.languageColor ? `<span class="gh-lang-dot" style="background:${escapeHtml(repo.languageColor)}"></span>` : '';
  const facts = [
    repo.language ? `<span class="gh-lang">${langDot}${escapeHtml(repo.language)}</span>` : '',
    repo.stargazerCount ? `<span>★ ${repo.stargazerCount}</span>` : '',
    repo.forkCount ? `<span>⑂ ${repo.forkCount}</span>` : '',
    `<span>updated ${timeAgo(repo.pushedAt)}</span>`
  ].filter(Boolean).join('');
  return `
    <div class="doc-card tool-card gh-repo">
      <div class="doc-head">
        <a class="doc-name gh-repo-name" href="${escapeHtml(repo.url)}" target="_blank" rel="noopener">${escapeHtml(repo.name)}</a>
      </div>
      <div class="tool-desc">${escapeHtml(repo.description || 'No description.')}</div>
      <div class="gh-repo-facts">${facts}</div>
      <div class="doc-actions">
        <a class="doc-btn" href="${escapeHtml(repo.url)}" target="_blank" rel="noopener">${ICON_GITHUB_SVG}Code</a>
        ${homepage ? `<a class="doc-btn" href="${escapeHtml(homepage)}" target="_blank" rel="noopener">${ICON_LIVE_SVG}Live Site</a>` : ''}
      </div>
    </div>`;
}

/** One-line rows for the repos not already shown as cards. */
function renderRepoList(repos){
  if(!repos.length) return '';
  const rows = repos.map(repo => {
    const langDot = repo.languageColor ? `<span class="gh-lang-dot" style="background:${escapeHtml(repo.languageColor)}"></span>` : '';
    return `
      <a class="gh-row" href="${escapeHtml(repo.url)}" target="_blank" rel="noopener">
        <span class="gh-row-name">${escapeHtml(repo.name)}</span>
        <span class="gh-row-desc">${escapeHtml(repo.description || '')}</span>
        <span class="gh-row-facts">
          ${repo.language ? `<span class="gh-lang">${langDot}${escapeHtml(repo.language)}</span>` : ''}
          ${repo.stargazerCount ? `<span>★ ${repo.stargazerCount}</span>` : ''}
          <span class="gh-row-date">${timeAgo(repo.pushedAt)}</span>
        </span>
      </a>`;
  }).join('');
  return `
    <div class="skill-label mt-26">All repositories <span class="gh-count">(${repos.length} more)</span></div>
    <div class="gh-list">${rows}</div>`;
}

function renderRepos(label, repos){
  if(!repos.length) return '';
  return `
    <div class="skill-label mt-26">${label}</div>
    <div class="tools-grid">${repos.map(renderRepoCard).join('')}</div>`;
}

function render(container, data){
  // Pinned repos are hand-picked, so they lead; recent ones fill in after,
  // without repeating any that are already pinned.
  const pinnedNames = new Set(data.pinned.map(r => r.name));
  const unpinned = data.repos.filter(r => !pinnedNames.has(r.name));
  const recent = unpinned.slice(0, RECENT_REPO_COUNT);
  const rest = unpinned.slice(RECENT_REPO_COUNT);
  const updated = data.generatedAt ? `<div class="gh-updated">// synced ${timeAgo(data.generatedAt)}</div>` : '';

  container.innerHTML = `
    ${renderProfile(data.profile)}
    ${renderStats(data)}
    ${renderContributions(data.contributions)}
    ${renderLanguages(data.languages)}
    ${renderRepos('Pinned', data.pinned)}
    ${renderRepos('Recently updated', recent)}
    ${renderRepoList(rest)}
    ${updated}`;
  keepCalendarOnLatestWeeks(container);
}

/**
 * On a screen too narrow for the whole year, scroll the calendar to its
 * recent end. Observed rather than done once, because the panel is usually
 * hidden (zero width) when this renders.
 */
function keepCalendarOnLatestWeeks(container){
  const wrap = container.querySelector('.gh-calendar-wrap');
  if(!wrap || typeof ResizeObserver === 'undefined') return;
  new ResizeObserver(() => { wrap.scrollLeft = wrap.scrollWidth; }).observe(wrap);
}

// ---- Wiring ---------------------------------------------------------------

export function initGithub(){
  const container = document.getElementById('githubContent');
  if(!container) return;
  loadGithubData()
    .then(data => render(container, data))
    .catch(err => {
      console.error('Could not load GitHub data', err);
      container.innerHTML = `<div class="grid-empty-note">Couldn't reach GitHub right now. <a href="https://github.com/${LOGIN}" target="_blank" rel="noopener">See my profile on github.com →</a></div>`;
    });
}
