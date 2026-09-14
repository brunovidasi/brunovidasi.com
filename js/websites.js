// ==========================================================================
// The websites / web-systems panels.
//
// These two categories share a list-and-detail view: a grid of browser-chrome
// cards that opens one project full-width, with the open project reflected in
// the URL as `?p=<id>` so a detail view is linkable.
// ==========================================================================

import { WEBSITE_CATEGORY_LABELS, WEBSITE_CATEGORY_TAGS } from './config.js';
import { state, websiteDetailIds, websiteItemsByCategory, websiteSectKeys, websiteDetailWasOpen } from './state.js';
import { escapeHtml, slugify } from './utils.js';
import { humanTypeSect } from './typing.js';
import { ICON_GITHUB_SVG, ICON_LIVE_SVG, ICON_EYE_SVG, ICON_PDF_SVG } from './icons.js';
import { act, registerActions } from './actions.js';
import { updatePath, pushWebsiteDetailPath } from './router.js';
import { showActivePanel } from './panels.js';
import { openFile } from './tabs.js';

// ---- Filtering ------------------------------------------------------------

/** Anything without a client is shown as freelance work rather than "Personal". */
export function websiteFilterCompany(company){
  return (!company || company === 'Personal') ? 'Freelance' : company;
}

function applyWebsiteFilters(category){
  const grid = document.getElementById('toolsGrid-' + category);
  if(!grid) return;
  const status = grid.dataset.filter || 'all';
  const company = grid.dataset.companyFilter || 'all';

  // A card left minimized would be re-shown at the wrong height by the filter.
  grid.querySelectorAll('.website-card.minimized').forEach(card => card.classList.remove('minimized'));

  let visibleCount = 0;
  grid.querySelectorAll('.website-card').forEach(card => {
    const statusMatch = status === 'all' || card.dataset.status === status;
    const companyMatch = company === 'all' || card.dataset.company === company;
    const visible = statusMatch && companyMatch;
    card.style.display = visible ? '' : 'none';
    if(visible) visibleCount++;
  });

  let emptyNote = grid.querySelector('.website-filter-empty');
  if(visibleCount === 0){
    if(!emptyNote){
      emptyNote = document.createElement('div');
      emptyNote.className = 'website-filter-empty grid-empty-note';
      emptyNote.textContent = `No ${WEBSITE_CATEGORY_LABELS[category] || category} match these filters.`;
      grid.appendChild(emptyNote);
    }
  } else if(emptyNote){
    emptyNote.remove();
  }
}

function filterWebsites(category, status, btn){
  const grid = document.getElementById('toolsGrid-' + category);
  if(!grid) return;
  grid.dataset.filter = status;
  btn.parentElement.querySelectorAll('.filter-pill').forEach(b => b.classList.toggle('active', b === btn));
  applyWebsiteFilters(category);
}

function filterWebsitesByCompany(category, company){
  const grid = document.getElementById('toolsGrid-' + category);
  if(!grid) return;
  grid.dataset.companyFilter = company;
  applyWebsiteFilters(category);
  const select = document.getElementById('webCompanySelect-' + category);
  if(!select) return;
  if(select.value !== company) select.value = company;
  select.classList.toggle('active', company !== 'all');
}

export function renderWebsiteCompanyFilters(category, items){
  const select = document.getElementById('webCompanySelect-' + category);
  if(!select) return;
  const companies = [...new Set(items.map(p => websiteFilterCompany(p.company)))].sort();
  select.innerHTML = ['<option value="all">🏢 All Companies</option>']
    .concat(companies.map(c => `<option value="${slugify(c)}">${escapeHtml(c)}</option>`))
    .join('');
}

/** Used when arriving from a company's chip elsewhere on the site. */
export function filterWebsitesToCompany(category, companySlug){
  const allStatusBtn = document.querySelector('#webStatusFilters-' + category + ' .filter-pill');
  if(allStatusBtn) filterWebsites(category, 'all', allStatusBtn);
  filterWebsitesByCompany(category, companySlug);
}

export function initWebsiteFilters(category, items){
  websiteItemsByCategory[category] = items;
  renderWebsiteCompanyFilters(category, items);
  applyWebsiteFilters(category);
}

// ---- Detail view open/close ----------------------------------------------

export function openWebsiteDetail(category, id){
  websiteDetailIds[category] = id;
  showActivePanel();
  pushWebsiteDetailPath(category, id);
  scrollEditorToTop();
}

function closeWebsiteDetail(category){
  websiteDetailIds[category] = null;
  showActivePanel();
  updatePath(category);
  scrollEditorToTop();
}

function scrollEditorToTop(){
  const editor = document.getElementById('editorArea');
  if(editor) editor.scrollTop = 0;
}

function toggleWebsiteDetailFocus(id){
  const card = document.getElementById('websiteDetail-' + id);
  if(card) card.classList.toggle('focus-media');
}

// ---- Shared card markup ---------------------------------------------------

/**
 * The pieces the grid card and the detail view render identically. Kept in one
 * place so a change to, say, the offline badge cannot drift between the two.
 */
function websiteViewModel(project, category){
  const title = escapeHtml(project.title);
  const status = project.status === 'live' ? 'live' : 'offline';
  // Web systems are internal tools with no public URL, so they show no status.
  const showStatus = category !== 'web-systems';
  const isArchived = status === 'offline' && !!project.archiveUrl;
  // Raw, not escaped: it is only ever passed through act(), which escapes.
  const visitTargetUrl = status === 'live' ? (project.live || '') : (project.archiveUrl || '');
  const techList = Array.isArray(project.tech) ? project.tech : [];
  const thumbSrc = project.screenshot || project.screenshotGif;
  const enableHoverGif = !!(project.screenshot && project.screenshotGif);

  return {
    title,
    status,
    showStatus,
    isArchived,
    visitTargetUrl,
    hasVisitLink: !!visitTargetUrl,
    thumbSrc,
    containThumb: project.thumbFit === 'contain',
    urlHtml: escapeHtml(project.url),
    yearHtml: project.year ? `<span class="website-year">${escapeHtml(String(project.year))}</span> ` : '',
    roleHtml: project.role ? `<div class="website-role">${escapeHtml(project.role)}</div>` : '',
    logoHtml: project.logo ? `<img class="website-logo" src="${escapeHtml(project.logo)}" alt="${title} logo">` : '',
    techHtml: techList.length
      ? `<div class="website-tech">${techList.map(t => `<span class="kw-pill">${escapeHtml(t)}</span>`).join('')}</div>`
      : '',
    thumbHtml: thumbSrc
      ? `<img class="website-thumb-img" src="${escapeHtml(thumbSrc)}" alt="${title} screenshot" loading="lazy"${enableHoverGif ? ` data-static-src="${escapeHtml(project.screenshot)}" data-gif-src="${escapeHtml(project.screenshotGif)}"` : ''}>`
      : `<span class="website-thumb-icon">${project.icon ? escapeHtml(project.icon) : '🌐'}</span>`,
    overlayHtml: showStatus && status === 'offline' && !isArchived
      ? '<div class="website-offline-overlay">🕸️ No longer live</div>'
      : '',
    visitHtml: visitTargetUrl
      ? `<button class="doc-btn"${act('open-window', visitTargetUrl)}>${ICON_LIVE_SVG}${isArchived ? 'View on Wayback Machine' : 'Visit site'}</button>`
      : (showStatus ? '<span class="doc-btn website-offline-btn">🕸️ Offline</span>' : ''),
    githubHtml: project.github
      ? `<a class="doc-btn" href="${escapeHtml(project.github)}" target="_blank" rel="noopener">${ICON_GITHUB_SVG}GitHub</a>`
      : '',
    pdfHtml: project.pdf
      ? `<a class="doc-btn" href="${escapeHtml(project.pdf)}" target="_blank" rel="noopener">${ICON_PDF_SVG}Read thesis</a>`
      : '',
    lockHtml: showStatus ? `<span class="website-lock">${status === 'live' ? '🔒' : '⚠️'}</span>` : '',
    statusBadgeHtml: showStatus
      ? `<span class="website-status website-status--${status}">${status === 'live' ? '🟢 Live' : '⚫ Offline'}</span>`
      : ''
  };
}

export function renderWebsiteCard(project, _sameYearAsPrevious, category){
  category = category || 'websites';
  const vm = websiteViewModel(project, category);
  const descHtml = project.description ? `<div class="tool-desc">${escapeHtml(project.description)}</div>` : '';
  const companySlug = slugify(websiteFilterCompany(project.company));
  // A few entries are really a case study living in another panel.
  const detailAttrs = project.linkTo
    ? act('goto-project', project.linkTo.category, project.linkTo.id)
    : act('website-detail', category, project.id);

  return `
    <div class="website-card" id="project-${project.id}" data-status="${vm.status}" data-company="${companySlug}"${vm.isArchived ? ' data-archived="true"' : ''}>
      <div class="website-chrome">
        <span class="website-dot r"${act('card-minimize', project.id)} title="Minimize"></span><span class="website-dot y"${act('card-minimize', project.id)} title="Minimize"></span><span class="website-dot g"${detailAttrs} title="View project"></span>
        <div class="website-urlbar">${vm.lockHtml}${vm.urlHtml}</div>
        ${vm.statusBadgeHtml}
      </div>
      <div class="website-thumb${vm.thumbSrc ? '' : ' placeholder'}${vm.containThumb ? ' thumb-contain' : ''}" tabindex="0" role="button" aria-label="View ${vm.title} project details"${detailAttrs}>
        <div class="website-thumb-scroll">
          ${vm.thumbHtml}
        </div>
        ${vm.overlayHtml}
      </div>
      <div class="website-body">
        <div class="website-body-inner">
          <div class="website-info">
            <div class="doc-head">
              <div class="doc-name">${vm.yearHtml}${vm.title}</div>
            </div>
            ${vm.roleHtml}
            ${descHtml}
            ${vm.techHtml}
          </div>
          ${vm.logoHtml ? `<div class="website-logo-wrap">${vm.logoHtml}</div>` : ''}
        </div>
        <div class="doc-actions">
          <button class="doc-btn"${detailAttrs}>${ICON_EYE_SVG}View project</button>
          ${vm.visitHtml}
          ${vm.githubHtml}
          ${vm.pdfHtml}
        </div>
      </div>
    </div>`;
}

function renderWebsiteDetailHtml(project, category){
  const vm = websiteViewModel(project, category);
  const descHtml = project.description ? `<div class="website-detail-desc">${escapeHtml(project.description)}</div>` : '';
  const extendedDescHtml = project.extendedDescription
    ? `<div class="website-detail-desc website-detail-desc--extended">${escapeHtml(project.extendedDescription)}</div>`
    : '';
  const greenDotHtml = vm.hasVisitLink
    ? `<span class="website-dot g"${act('open-window', vm.visitTargetUrl)} title="Visit site"></span>`
    : '<span class="website-dot g disabled" title="No live link"></span>';

  return `
    <div class="website-detail-inner">
    ${backButtonHtml(category)}
    <div class="website-card website-detail-card" id="websiteDetail-${project.id}" data-status="${vm.status}"${vm.isArchived ? ' data-archived="true"' : ''}>
      <div class="website-detail-split">
        <div class="website-detail-media">
          <div class="website-chrome">
            <span class="website-dot r"${act('close-website-detail', category)} title="Back"></span><span class="website-dot y"${act('website-detail-focus', project.id)} title="Toggle screenshot size"></span>${greenDotHtml}
            <div class="website-urlbar">${vm.lockHtml}${vm.urlHtml}</div>
            ${vm.statusBadgeHtml}
          </div>
          <div class="website-thumb website-detail-thumb${vm.thumbSrc ? '' : ' placeholder'}${vm.containThumb ? ' thumb-contain' : ''}">
            ${vm.thumbHtml}
            ${vm.overlayHtml}
          </div>
        </div>
        <div class="website-detail-info-col">
          <div class="website-detail-head-row">
            <div class="website-info">
              <div class="doc-head">
                <div class="doc-name">${vm.yearHtml}${vm.title}</div>
              </div>
              ${vm.roleHtml}
            </div>
            ${vm.logoHtml ? `<div class="website-logo-wrap">${vm.logoHtml}</div>` : ''}
          </div>
          ${descHtml}
          ${vm.techHtml}
          ${extendedDescHtml}
          <div class="doc-actions">
            ${vm.visitHtml}
            ${vm.githubHtml}
            ${vm.pdfHtml}
          </div>
        </div>
      </div>
    </div>
    </div>`;
}

function backButtonHtml(category){
  const label = WEBSITE_CATEGORY_LABELS[category] || category;
  return `<button class="website-detail-back"${act('close-website-detail', category)}>← Back to all ${label}</button>`;
}

// ---- Panel heading --------------------------------------------------------

/** The `<websites count="12" />` heading, which becomes `<website name="..." />` in detail view. */
function websitesSectHtml(category){
  const detailId = websiteDetailIds[category];
  const items = websiteItemsByCategory[category] || [];
  const tags = WEBSITE_CATEGORY_TAGS[category] || { plural: category, singular: category };
  const project = detailId && items.find(p => p.id === detailId);

  if(project){
    const yearAttr = project.year
      ? ` <span class="attr">year</span>=<span class="str">"${escapeHtml(String(project.year))}"</span>`
      : '';
    return `<span class="brk">&lt;</span>${tags.singular} <span class="attr">name</span>=<span class="str">"${escapeHtml(project.title)}"</span>${yearAttr}<span class="brk"> /&gt;</span>`;
  }

  const typeAttr = category === 'websites' ? 'live-preview' : 'internal-system';
  return `<span class="brk">&lt;</span>${tags.plural} <span class="attr">count</span>=<span class="str">"<span class="tool-count" data-count-category="${category}">${items.length}</span>"</span> <span class="attr">type</span>=<span class="str">"${typeAttr}"</span><span class="brk"> /&gt;</span>`;
}

function updateWebsitesSect(category){
  const sect = document.querySelector('#panel-' + category + ' h2.sect');
  if(!sect) return;
  const detailId = websiteDetailIds[category];
  const items = websiteItemsByCategory[category] || [];
  const project = detailId && items.find(p => p.id === detailId);
  const key = project ? project.id : '';
  // Re-type only when the heading's subject actually changed.
  if(key === websiteSectKeys[category]) return;
  websiteSectKeys[category] = key;
  sect.dataset.typed = '';
  sect.innerHTML = websitesSectHtml(category);
  humanTypeSect(sect);
}

// ---- List / detail switch -------------------------------------------------

export function renderWebsiteDetail(category){
  updateWebsitesSect(category);
  const panel = document.getElementById('panel-' + category);
  if(!panel) return;
  const listView = panel.querySelector('.website-list-view');
  const detailView = panel.querySelector('.website-detail');
  if(!listView || !detailView) return;

  const detailId = websiteDetailIds[category];
  const wasOpen = websiteDetailWasOpen[category];
  websiteDetailWasOpen[category] = !!detailId;

  if(!detailId){
    listView.style.display = '';
    // Only animate the list back in when returning from a detail view, not on
    // every panel visit.
    if(wasOpen){
      listView.classList.remove('pane-enter');
      void listView.offsetWidth;
      listView.classList.add('pane-enter');
      listView.addEventListener('animationend', ()=> listView.classList.remove('pane-enter'), { once: true });
    }
    detailView.classList.remove('active');
    detailView.innerHTML = '';
    return;
  }

  listView.style.display = 'none';
  detailView.classList.add('active');
  const items = websiteItemsByCategory[category] || [];
  const project = items.find(p => p.id === detailId);

  if(!project){
    // A stale `?p=` id; show a way back rather than an empty panel. Before the
    // JSON has loaded there is nothing to say yet, so render nothing.
    detailView.innerHTML = items.length
      ? `<div class="website-detail-inner">${backButtonHtml(category)}<div class="website-detail-missing">Project not found.</div></div>`
      : '';
    return;
  }

  detailView.innerHTML = renderWebsiteDetailHtml(project, category);
  setupWebsiteHoverGifs(detailView);
}

/** Swaps a card's still screenshot for its animated version on hover. */
export function setupWebsiteHoverGifs(root){
  (root || document).querySelectorAll('.website-thumb-img[data-gif-src]').forEach(img => {
    const thumb = img.closest('.website-thumb');
    if(!thumb) return;
    thumb.addEventListener('mouseenter', ()=> { img.src = img.dataset.gifSrc; });
    thumb.addEventListener('mouseleave', ()=> { img.src = img.dataset.staticSrc; });
  });
}

// ---- Wiring ---------------------------------------------------------------

export function initWebsites(){
  registerActions({
    'filter-websites':      (el, [category, status]) => filterWebsites(category, status, el),
    'website-detail':       (el, [category, id]) => showWebsiteDetail(category, id),
    'close-website-detail': (el, [category]) => closeWebsiteDetail(category),
    'website-detail-focus': (el, [id]) => toggleWebsiteDetailFocus(id)
  });
  registerActions({
    'filter-websites-company': (el, [category]) => filterWebsitesByCompany(category, el.value)
  });
}

/**
 * Detail links appear on the category's own panel and on other panels (e.g.
 * skills.json), so open the owning tab first when we are not already on it.
 */
function showWebsiteDetail(category, id){
  if(state.activeId !== category) openFile(category);
  openWebsiteDetail(category, id);
}
