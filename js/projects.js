// ==========================================================================
// Loading the project data and rendering every grid built from it.
//
// Each `[id^="toolsGrid-"]` element in the HTML declares a category; that
// category's `json/<category>.json` is fetched, rendered with the renderer
// registered for it, and then fed to the counts, chips, intro preview and
// search index. A category that fails to load leaves its grid with a note
// rather than taking the rest of the page down with it.
// ==========================================================================

import {
  TIMELINE_CATEGORIES, WEBSITE_STYLE_CATEGORIES, INTRO_PROJECT_GROUPS, PROJECT_TAB_OVERRIDES
} from './config.js';
import { state } from './state.js';
import { escapeHtml, slugify, flashElement } from './utils.js';
import { act, registerActions } from './actions.js';
import { renderToolCard, renderTimelineCard, renderCaseCard, renderMiniGameCard, carouselGoto, carouselNav } from './cards.js';
import {
  renderWebsiteCard, renderWebsiteDetail, setupWebsiteHoverGifs,
  initWebsiteFilters, filterWebsitesToCompany, websiteFilterCompany
} from './websites.js';
import { openFile, renderTabs } from './tabs.js';
import { renderExplorer, collapseFoldersExceptCurrent } from './explorer.js';
import { showActivePanel } from './panels.js';
import { openToolTab, tryOpenToolTabRoute, openCategorySiblings } from './tool-tabs.js';
import { wantsAllToolsInCategory } from './router.js';
import { buildSearchIndex } from './search.js';
import { revealAfterToolRoute } from './boot.js';
import { watchGameFrames } from './games.js';

const CATEGORY_RENDERERS = {
  websites: renderWebsiteCard,
  'web-systems': renderWebsiteCard,
  'edm-html-builder': renderCaseCard,
  'landing-pages': renderTimelineCard,
  'site-history': renderTimelineCard,
  'mini-games': renderMiniGameCard
};

function categoryOf(grid){
  return grid.dataset.category || grid.id.replace('toolsGrid-', '');
}

// ---- Fetching -------------------------------------------------------------

function loadCategory(category){
  return fetch(`json/${category}.json`)
    .then(res => {
      if(!res.ok) throw new Error(`${res.status} ${res.statusText}`);
      return res.json();
    })
    .then(items => [category, items])
    .catch(err => {
      console.error(`Could not load json/${category}.json`, err);
      return [category, []];
    });
}

// ---- Grid rendering -------------------------------------------------------

function renderGrid(grid, items){
  const category = categoryOf(grid);
  if(!items || !items.length){
    grid.innerHTML = `<div class="grid-empty-note">No entries yet — add one to json/${category}.json.</div>`;
    return;
  }

  const isTimeline = TIMELINE_CATEGORIES.has(category);
  const renderer = CATEGORY_RENDERERS[category] || renderToolCard;
  const sortedItems = isTimeline ? [...items].sort((a, b) => (b.year || 0) - (a.year || 0)) : items;

  grid.innerHTML = sortedItems.map((item, i) => {
    // Only the first card of a year prints the year marker on the rail.
    const prev = sortedItems[i - 1];
    const sameYearAsPrevious = isTimeline && !!prev && prev.year === item.year;
    return renderer(item, sameYearAsPrevious, category);
  }).join('');
}

/** Fills the `<n>` in headings like `<mini-tools count="12" />`. */
function updateToolCounts(byCategory){
  document.querySelectorAll('.tool-count').forEach(el => {
    const categories = el.dataset.countCategory.split(',');
    el.textContent = categories.reduce((sum, category) => sum + (byCategory[category] || []).length, 0);
  });
}

/** Fills the "a · b · +3 more" summary lines under folder headings. */
function updateCategoryMeta(byCategory){
  document.querySelectorAll('.meta[data-meta-category]').forEach(el => {
    const categories = el.dataset.metaCategory.split(',');
    const titles = categories.flatMap(category => (byCategory[category] || []).map(item => item.title));
    if(!titles.length) return;
    el.textContent = titles.length <= 2
      ? titles.join(' · ')
      : `${titles[0]} · ${titles[1]} · +${titles.length - 2} more`;
  });
}

// ---- Project chips --------------------------------------------------------

function projectChipHtml(p){
  const companySlug = WEBSITE_STYLE_CATEGORIES.includes(p.category) ? slugify(websiteFilterCompany(p.company)) : '';
  return `
    <button class="exp-project-chip"${act('goto-project', p.category, p.id, companySlug)}>
      ${p.icon ? `<span class="ic-emoji">${escapeHtml(p.icon)}</span>` : ''}<span>${escapeHtml(p.title)}</span>
    </button>`;
}

/** Hangs each job's related projects off that job's commit in experience.js. */
function renderExperienceProjects(allProjects){
  document.querySelectorAll('#panel-experience .commit[data-company]').forEach(commit => {
    const matches = allProjects.filter(p => p.company === commit.dataset.company);
    if(!matches.length) return;
    const details = commit.querySelector('.details');
    if(!details) return;
    details.insertAdjacentHTML('beforeend', `
      <div class="exp-projects">
        <div class="skill-label">Related projects</div>
        <div class="exp-projects-row">${matches.map(projectChipHtml).join('')}</div>
      </div>`);
  });
}

function renderFreelanceProjects(allProjects){
  const row = document.getElementById('freelanceProjectsRow');
  if(!row) return;
  row.innerHTML = allProjects.filter(p => p.company === 'Freelance').map(projectChipHtml).join('');
}

// ---- Intro panel preview --------------------------------------------------

/** "Featured one · Featured two · +7 more" for an intro card. */
function introGroupMetaText(items){
  if(!items.length) return '';
  const featured = items.filter(p => p.featured);
  const rest = items.filter(p => !p.featured);
  const highlights = [...featured, ...rest].slice(0, 2);
  const names = highlights.map(p => p.title).join(' · ');
  const remaining = items.length - highlights.length;
  return remaining > 0 ? `${names} · +${remaining} more` : names;
}

function renderIntroProjectPreview(byCategory){
  document.querySelectorAll('#panel-intro .commit[data-open]').forEach(commit => {
    const groups = INTRO_PROJECT_GROUPS[commit.dataset.open];
    const meta = commit.querySelector('.meta');
    if(!groups || !meta) return;
    const items = groups.flatMap(category => (byCategory[category] || []).filter(p => !p.linkTo));
    if(items.length) meta.textContent = introGroupMetaText(items);
  });
}

// ---- Navigation into a project -------------------------------------------

/** Opens the panel a project lives on, then scrolls to and flashes its card. */
export function goToProject(category, id, companySlug){
  openFile(PROJECT_TAB_OVERRIDES[category] || category);
  if(WEBSITE_STYLE_CATEGORIES.includes(category) && companySlug) filterWebsitesToCompany(category, companySlug);
  requestAnimationFrame(()=> flashElement(document.getElementById('project-' + id)));
}

// ---- Card chrome ----------------------------------------------------------

function toggleCardMinimize(id){
  const card = document.getElementById('project-' + id);
  if(card) card.classList.toggle('minimized');
}

function toggleCaseMediaHidden(id){
  const card = document.getElementById('project-' + id);
  if(card) card.classList.toggle('media-hidden');
}

// ---- Deep-linked tool tabs -----------------------------------------------

/**
 * A URL naming a tool (rather than a page) could not be resolved at startup,
 * because tool tabs only exist once their project JSON has been read. Now
 * that it has, open it — or give up on it and fall back to the default view.
 */
function resolvePendingToolRoute(){
  const id = state.pendingToolRouteId;
  state.pendingToolRouteId = null;
  if(!id || !tryOpenToolTabRoute(id)) return;

  if(wantsAllToolsInCategory()){
    openCategorySiblings(id);
    collapseFoldersExceptCurrent(id);
  }
  renderTabs();
  renderExplorer();
  showActivePanel();
}

// ---- Entry point ----------------------------------------------------------

export function initProjects(){
  registerActions({
    'goto-project':      (el, [category, id, companySlug]) => goToProject(category, id, companySlug),
    'open-tool-tab':     (el, [id]) => openToolTab(id),
    'card-minimize':     (el, [id]) => toggleCardMinimize(id),
    'case-media-hidden': (el, [id]) => toggleCaseMediaHidden(id),
    'carousel-goto':     (el, [id, index]) => carouselGoto(id, Number(index)),
    'carousel-nav':      (el, [id, dir]) => carouselNav(id, Number(dir))
  });

  const projectGrids = document.querySelectorAll('[id^="toolsGrid-"]');
  const categories = [...new Set(Array.from(projectGrids).map(categoryOf))];

  return Promise.all(categories.map(loadCategory)).then(results => {
    const byCategory = Object.fromEntries(results);

    projectGrids.forEach(grid => renderGrid(grid, byCategory[categoryOf(grid)]));
    updateToolCounts(byCategory);
    updateCategoryMeta(byCategory);
    setupWebsiteHoverGifs();
    watchGameFrames();
    renderExplorer();

    WEBSITE_STYLE_CATEGORIES.forEach(category => initWebsiteFilters(category, byCategory[category] || []));
    if(WEBSITE_STYLE_CATEGORIES.includes(state.activeId)) renderWebsiteDetail(state.activeId);

    const allProjects = Object.entries(byCategory)
      .flatMap(([category, items]) => (items || []).map(item => ({ ...item, category })));
    // Entries that only redirect elsewhere would be duplicate chips.
    const chipProjects = allProjects.filter(p => !p.linkTo);
    renderExperienceProjects(chipProjects);
    renderFreelanceProjects(chipProjects);
    renderIntroProjectPreview(byCategory);
    buildSearchIndex(allProjects);

    resolvePendingToolRoute();
    revealAfterToolRoute();
  });
}
