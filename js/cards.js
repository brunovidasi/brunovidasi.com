// ==========================================================================
// Project card markup.
//
// Every renderer here takes a project from `json/<category>.json` and returns
// an HTML string; projects.js picks which one a category uses. The website and
// web-system cards live in websites.js, which owns their list/detail view.
// ==========================================================================

import { TOOL_TAB_REGISTRY } from './state.js';
import { escapeHtml } from './utils.js';
import { act } from './actions.js';
import {
  ICON_GITHUB_SVG, ICON_CODEPEN_SVG, ICON_LIVE_SVG, ICON_EYE_SVG, ICON_FULLSCREEN_SVG,
  GAME_DESKTOP_LOCK_HTML
} from './icons.js';

/** Categories whose cards can also be opened as a full tool tab. */
const OPENABLE_IN_TAB = ['site-history', 'mini-tools'];

// ---- Default tool card ----------------------------------------------------

export function renderToolCard(project, sameYearAsPrevious, category){
  const title = escapeHtml(project.title);
  const iconHtml = project.icon ? `<span class="ic-emoji">${escapeHtml(project.icon)}</span>` : '';
  const yearHtml = project.year ? `<span class="tool-year">${escapeHtml(String(project.year))}</span>` : '';
  const descHtml = project.description ? `<div class="tool-desc">${escapeHtml(project.description)}</div>` : '';

  const liveHtml = project.live ? `<a class="doc-btn" href="${escapeHtml(project.live)}" target="_blank" rel="noopener">${ICON_LIVE_SVG}Live Site</a>` : '';
  const githubHtml = project.github ? `<a class="doc-btn" href="${escapeHtml(project.github)}" target="_blank" rel="noopener">${ICON_GITHUB_SVG}GitHub</a>` : '';
  const codepenHtml = project.codepen ? `<a class="doc-btn" href="${escapeHtml(project.codepen)}" target="_blank" rel="noopener">${ICON_CODEPEN_SVG}CodePen</a>` : '';

  // `noView` marks a card with nothing to embed — a link-out only.
  const viewLabel = project.category === 'mini-tools' ? 'Quick View' : 'View';
  const viewHtml = project.noView ? '' : `<button class="doc-btn" data-view-label="${viewLabel}"${act('toggle-doc', project.id)}>${ICON_EYE_SVG}${viewLabel}</button>`;
  const nameAttrs = project.noView ? '' : act('toggle-doc', project.id);
  const nameClass = project.noView ? 'doc-name' : 'doc-name doc-name-clickable';
  const embedHtml = project.noView ? '' : `
      <div class="doc-embed" id="embed-${project.id}">
        <iframe data-src="${escapeHtml(project.path)}" title="${title}"></iframe>
      </div>`;

  // Self-hosted pages with an embed can also open as their own editor tab;
  // registering them here is what puts them in the explorer and Quick Open.
  const showOpenTab = !project.noView && !project.live && OPENABLE_IN_TAB.includes(project.category);
  if(showOpenTab){
    TOOL_TAB_REGISTRY[project.id] = {
      path: project.path,
      title: project.title,
      icon: project.glyph || project.icon,
      fileIcon: project.fileIcon,
      category: category || project.category,
      github: project.github || null,
      codepen: project.codepen || null
    };
  }
  const openTabHtml = showOpenTab ? `<button class="doc-btn"${act('open-tool-tab', project.id)}>${ICON_LIVE_SVG}Open</button>` : '';

  // Some cards carry extra prototypes, only one of which is open at a time.
  const prototypes = Array.isArray(project.prototypes) ? project.prototypes : [];
  const prototypeGroup = `${project.id}-prototypes`;
  const prototypeBtnsHtml = prototypes
    .map(p => `<button class="doc-btn"${act('toggle-exclusive-doc', p.id, prototypeGroup)}>${ICON_EYE_SVG}${escapeHtml(p.label)}</button>`)
    .join('');
  const prototypeEmbedsHtml = prototypes.map(p => `
      <div class="doc-embed" id="embed-${p.id}" data-group="${prototypeGroup}">
        <iframe data-src="${escapeHtml(p.path)}" title="${escapeHtml(p.label)}"></iframe>
      </div>`).join('');

  return `
    <div class="doc-card tool-card${project.featured ? ' tool-card-full' : ''}" id="project-${project.id}">
      <div class="tool-card-top">
        <div class="doc-head">
          ${iconHtml}
          <div class="${nameClass}"${nameAttrs}>${title}</div>
          ${yearHtml}
        </div>
        ${descHtml}
        <div class="doc-actions">
          ${viewHtml}
          ${openTabHtml}
          ${liveHtml}
          ${codepenHtml}
          ${prototypeBtnsHtml}
          ${githubHtml}
        </div>
      </div>${embedHtml}${prototypeEmbedsHtml}
    </div>`;
}

// ---- Timeline card (a tool card under a year marker) ---------------------

export function renderTimelineCard(project, sameYearAsPrevious, category){
  const yearHtml = (project.year && !sameYearAsPrevious)
    ? `<div class="year">${escapeHtml(String(project.year))}</div>`
    : '';
  // The year is hoisted out of the card and onto the timeline rail.
  const { year, ...rest } = project;
  return `
    <div class="commit timeline-commit">
      <div class="commit-body">
        ${yearHtml}
        ${renderToolCard({ ...rest, featured: true }, sameYearAsPrevious, category)}
      </div>
    </div>`;
}

// ---- Case study card ------------------------------------------------------

const CASE_SECTIONS = ['challenge', 'technique', 'outcome'];

export function renderCaseCard(project){
  const title = escapeHtml(project.title);
  const badge = project.badge ? `<span class="case-badge">${escapeHtml(project.badge)}</span>` : '';
  const iconHtml = project.icon ? `<span class="ic-emoji">${escapeHtml(project.icon)}</span>` : '';
  const yearHtml = project.year ? `<span class="website-year">${escapeHtml(String(project.year))}</span>` : '';
  const roleHtml = project.role ? `<div class="website-role">${escapeHtml(project.role)}</div>` : '';
  const descHtml = project.description ? `<div class="case-section">${escapeHtml(project.description)}</div>` : '';

  const techList = Array.isArray(project.tech) ? project.tech : [];
  const techHtml = techList.length
    ? `<div class="website-tech">${techList.map(t => `<span class="kw-pill">${escapeHtml(t)}</span>`).join('')}</div>`
    : '';

  const sections = CASE_SECTIONS
    .filter(key => project[key])
    .map(key => `<div class="case-section"><b>${key.charAt(0).toUpperCase() + key.slice(1)}:</b> ${escapeHtml(project[key])}</div>`)
    .join('');

  const githubHtml = project.github
    ? `<a class="doc-btn" href="${escapeHtml(project.github)}" target="_blank" rel="noopener">${ICON_GITHUB_SVG}GitHub</a>`
    : '';
  const greenDotHtml = project.github
    ? `<span class="website-dot g"${act('open-window', project.github)} title="View source on GitHub"></span>`
    : '<span class="website-dot g disabled" title="No source link"></span>';

  return `
    <div class="case-card" id="project-${project.id}">
      <div class="website-chrome">
        <span class="website-dot r"${act('card-minimize', project.id)} title="Minimize"></span><span class="website-dot y"${act('case-media-hidden', project.id)} title="Hide screenshot"></span>${greenDotHtml}
        <div class="website-urlbar">${project.icon ? `<span class="website-lock">${escapeHtml(project.icon)}</span>` : ''}${title}</div>
      </div>
      ${caseMediaHtml(project, title)}
      <div class="case-body">
        <div class="case-head">
          ${iconHtml}
          <div class="case-title">${title}</div>
          ${yearHtml}
          ${badge}
        </div>
        ${roleHtml}
        ${descHtml}
        ${techHtml}
        ${sections}
        ${githubHtml ? `<div class="case-actions">${githubHtml}</div>` : ''}
      </div>
    </div>`;
}

/** One image, a carousel for several, or a placeholder for none. */
function caseMediaHtml(project, title){
  const mediaList = Array.isArray(project.media)
    ? project.media.filter(Boolean)
    : (project.media ? [project.media] : []);

  if(mediaList.length === 0){
    return '<div class="case-media empty">🖼️ Screenshot / GIF coming soon</div>';
  }
  if(mediaList.length === 1){
    return `<div class="case-media"><div class="case-media-scroll"><img src="${escapeHtml(mediaList[0])}" alt="${title}" loading="lazy"></div></div>`;
  }

  const imgs = mediaList.map(src => `<img src="${escapeHtml(src)}" alt="${title}" loading="lazy">`).join('');
  const dots = mediaList
    .map((_, i) => `<span class="dot${i === 0 ? ' active' : ''}"${act('carousel-goto', project.id, i)}></span>`)
    .join('');
  return `
      <div class="case-media">
        <div class="case-carousel" id="carousel-${project.id}">
          <div class="case-carousel-track" id="carousel-track-${project.id}">${imgs}</div>
          <button class="carousel-btn prev"${act('carousel-nav', project.id, -1)} aria-label="Previous image">‹</button>
          <button class="carousel-btn next"${act('carousel-nav', project.id, 1)} aria-label="Next image">›</button>
          <div class="carousel-dots">${dots}</div>
        </div>
      </div>`;
}

// ---- Mini-game card -------------------------------------------------------

export function renderMiniGameCard(project, sameYearAsPrevious, category){
  const title = escapeHtml(project.title);
  const descHtml = project.description ? `<div class="tool-desc">${escapeHtml(project.description)}</div>` : '';

  TOOL_TAB_REGISTRY[project.id] = {
    path: project.path,
    title: project.title,
    icon: project.glyph || project.icon,
    category: category || project.category
  };

  // Most games exist twice over: the original Java build and a 2026 HTML5 port.
  const githubHtml = project.github
    ? `<a class="doc-btn" href="${escapeHtml(project.github)}" target="_blank" rel="noopener">${ICON_GITHUB_SVG}${escapeHtml(project.githubLabel || 'GitHub (2013 Java)')}</a>`
    : '';
  const githubHtml2026 = project.github2026
    ? `<a class="doc-btn" href="${escapeHtml(project.github2026)}" target="_blank" rel="noopener">${ICON_GITHUB_SVG}${escapeHtml(project.githubLabel2026 || 'GitHub (2026 HTML5)')}</a>`
    : '';
  const campaignHtml = project.campaign
    ? `<button class="doc-btn"${act('open-window', project.campaign)}>${ICON_LIVE_SVG}${escapeHtml(project.campaignLabel || 'View Original Campaign')}</button>`
    : '';

  return `
    <div class="website-card game-card" id="project-${project.id}">
      <div class="website-chrome">
        <span class="website-dot r"${act('game-reload', project.id)} title="Reload"></span><span class="website-dot y"${act('card-minimize', project.id)} title="Minimize"></span><span class="website-dot g"${act('game-fullscreen', project.id)} title="Fullscreen"></span>
        <div class="website-urlbar"><span class="website-lock">🎮</span> ${title}</div>
        <button class="game-fullscreen-btn"${act('game-fullscreen', project.id)} title="Fullscreen">${ICON_FULLSCREEN_SVG}</button>
      </div>
      <div class="game-frame-wrap" id="gameFrame-${project.id}">
        <iframe src="${escapeHtml(project.path)}" title="${title}" scrolling="no"></iframe>
        ${GAME_DESKTOP_LOCK_HTML}
      </div>
      <div class="website-body">
        <div class="website-body-inner">
          <div class="website-info">
            <div class="doc-head">
              <div class="doc-name">${title}</div>
            </div>
            ${descHtml}
          </div>
        </div>
        <div class="doc-actions">
          <button class="doc-btn"${act('open-tool-tab', project.id)}>${ICON_LIVE_SVG}Open</button>
          ${campaignHtml}
          ${githubHtml}
          ${githubHtml2026}
        </div>
      </div>
    </div>`;
}

// ---- Carousel -------------------------------------------------------------

const carouselIndex = {};

export function carouselGoto(id, index){
  const track = document.getElementById('carousel-track-' + id);
  if(!track) return;
  const count = track.children.length;
  index = ((index % count) + count) % count;
  carouselIndex[id] = index;
  track.style.transform = `translateX(-${index * 100}%)`;
  document.querySelectorAll(`#carousel-${id} .dot`).forEach((d, i) => d.classList.toggle('active', i === index));
}

export function carouselNav(id, dir){
  carouselGoto(id, (carouselIndex[id] || 0) + dir);
}
