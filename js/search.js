// ==========================================================================
// Workspace search.
//
// One index is built once the project JSON has loaded, in two shapes:
//   QUICK_OPEN_INDEX — coarse, title-only, fuzzy-matched (Ctrl/Cmd+P)
//   CONTENT_INDEX    — fine-grained text blocks, substring-matched (sidebar)
// Both resolve to the same navigateToHit(): open the target's panel, then
// scroll to and flash the specific element if the hit named one.
// ==========================================================================

import { files, folders, bioText, FOLDER_DEFAULT_FILE, PROJECT_PANEL_OVERRIDES, PROJECT_TEXT_FIELDS } from './config.js';
import { escapeHtml, truncateSnippet, highlightSubstring, flashElement } from './utils.js';
import { fileIconHtml, toolIconHtml, folderIconHtml } from './icons.js';
import { openFile } from './tabs.js';
import { restoreApp, revealSidebar } from './shell.js';
import { switchSidebarView } from './explorer.js';

/** Max snippets shown per file before the group is truncated. */
const MAX_SNIPPETS_PER_FILE = 6;
const SEARCH_DEBOUNCE_MS = 120;

export let QUICK_OPEN_INDEX = [];
let CONTENT_INDEX = [];

// ---- Building the index ---------------------------------------------------

function panelIdForCategory(category){
  return PROJECT_PANEL_OVERRIDES[category] || category;
}

function addContentBlock(panelId, rawText, el){
  const text = (rawText || '').replace(/\s+/g, ' ').trim();
  if(!text) return;
  CONTENT_INDEX.push({ panelId, text, textLower: text.toLowerCase(), el: el || null });
}

// Adjacent elements (e.g. back-to-back .stack-pill spans) have no whitespace
// between them in the source HTML, so plain .textContent runs them together
// ("HTML5CSS3jQuery"). Insert a separating space after each element's text.
function extractText(el){
  let out = '';
  el.childNodes.forEach(node => {
    if(node.nodeType === Node.TEXT_NODE) out += node.textContent;
    else if(node.nodeType === Node.ELEMENT_NODE) out += extractText(node) + ' ';
  });
  return out;
}

/** Panel id -> the selector picking out its individually-linkable blocks. */
const STATIC_CONTENT_SOURCES = {
  experience: ':scope > .commit',
  education: ':scope > .commit',
  skills: '.cv-skill-line',
  freelance: '.bio-text, .bio-list li',
  documents: '.doc-card'
};

function scrapeStaticContent(){
  Object.entries(STATIC_CONTENT_SOURCES).forEach(([panelId, selector]) => {
    const panel = document.getElementById('panel-' + panelId);
    if(!panel) return;
    panel.querySelectorAll(selector).forEach(el => addContentBlock(panelId, extractText(el), el));
  });
  // The bio is typed in from JS, so index the source string rather than the DOM.
  addContentBlock('about', bioText, document.getElementById('bioTypedText'));
}

export function buildSearchIndex(allProjects){
  CONTENT_INDEX = [];
  QUICK_OPEN_INDEX = [];

  Object.keys(files).forEach(id => {
    const file = files[id];
    // Tool tabs come and go with the tabs that own them; indexing them would
    // leave stale entries behind.
    if(file.isToolTab) return;
    QUICK_OPEN_INDEX.push({
      title: file.label,
      aliases: file.aliases || [],
      meta: file.folder && folders[file.folder] ? folders[file.folder].label : '',
      iconHtml: fileIconHtml(file.icon),
      panelId: id,
      el: null
    });
  });

  // Folders open the file they default to (or the category panel they share an id with).
  Object.keys(folders).forEach(id => {
    const panelId = FOLDER_DEFAULT_FILE[id] || id;
    if(!files[panelId]) return;
    const parentId = Object.keys(folders).find(p => (folders[p].children || []).includes(id));
    QUICK_OPEN_INDEX.push({
      title: folders[id].label,
      meta: parentId ? folders[parentId].label : '',
      iconHtml: folderIconHtml(false),
      panelId,
      el: null
    });
  });

  scrapeStaticContent();

  allProjects.forEach(project => {
    const panelId = panelIdForCategory(project.category);
    if(!files[panelId]) return;
    const el = document.getElementById('project-' + project.id);

    const text = PROJECT_TEXT_FIELDS.map(k => project[k]).filter(Boolean)
      .concat(Array.isArray(project.tech) ? project.tech : [])
      .join(' — ');
    addContentBlock(panelId, text, el);

    QUICK_OPEN_INDEX.push({
      title: project.title,
      meta: files[panelId].label,
      iconHtml: project.icon ? toolIconHtml(escapeHtml(project.icon)) : fileIconHtml('js'),
      panelId,
      el
    });
  });
}

// ---- Navigating to a hit --------------------------------------------------

export function navigateToHit(hit){
  restoreApp();
  openFile(hit.panelId);
  if(!hit.el) return;
  // The panel has to finish becoming visible before it can be scrolled.
  setTimeout(()=> flashElement(hit.el, 'search-flash'), 60);
}

// ---- Sidebar search panel -------------------------------------------------

export function openWorkspaceSearch(){
  revealSidebar();
  switchSidebarView('search');
  document.getElementById('searchPanelInput').focus();
}

export function focusExplorer(){
  restoreApp();
  revealSidebar();
  switchSidebarView('explorer');
}

function renderSearchResults(query){
  const summaryEl = document.getElementById('searchSummary');
  const resultsEl = document.getElementById('searchResults');
  const q = query.trim().toLowerCase();

  if(!q){
    summaryEl.textContent = '';
    resultsEl.innerHTML = '';
    return;
  }

  const hits = CONTENT_INDEX
    .map(entry => ({ entry, matchAt: entry.textLower.indexOf(q) }))
    .filter(h => h.matchAt !== -1);

  // Files matched by name: their label, the folders they sit in, or an alias.
  const titleMatches = new Map();
  Object.keys(files).forEach(id => {
    const file = files[id];
    if(file.isToolTab) return;
    const names = [file.label];
    if(file.folder && folders[file.folder]) names.push(folders[file.folder].label);
    if(folders[id]) names.push(folders[id].label);
    names.push(...(file.aliases || []));
    for(const name of names){
      const matchAt = name.toLowerCase().indexOf(q);
      if(matchAt !== -1){
        titleMatches.set(id, { name, matchAt, isLabel: name === file.label });
        break;
      }
    }
  });

  if(!hits.length && !titleMatches.size){
    summaryEl.textContent = 'No results found';
    resultsEl.innerHTML = '';
    return;
  }

  // Group by file: name matches first, then first-seen content order.
  const order = [];
  const groups = new Map();
  titleMatches.forEach((_, panelId) => {
    groups.set(panelId, []);
    order.push(panelId);
  });
  hits.forEach(h => {
    const panelId = h.entry.panelId;
    if(!groups.has(panelId)){
      groups.set(panelId, []);
      order.push(panelId);
    }
    groups.get(panelId).push(h);
  });

  const plural = (n, word) => `${n} ${word}${n === 1 ? '' : 's'}`;
  summaryEl.textContent = hits.length
    ? `${plural(hits.length, 'result')} in ${plural(order.length, 'file')}`
    : `${plural(order.length, 'file')} matched by name`;

  // Rows carry an index into this registry rather than the hit itself, since
  // they are built as one HTML string.
  const hitRegistry = [];
  let html = '';
  order.forEach(panelId => {
    const file = files[panelId];
    if(!file) return;
    const groupHits = groups.get(panelId);
    const nameMatch = titleMatches.get(panelId);
    const labelHtml = nameMatch && nameMatch.isLabel
      ? highlightSubstring(file.label, nameMatch.matchAt, q.length)
      : escapeHtml(file.label);
    // A folder or alias match is shown beside the label so the hit makes sense.
    const hintHtml = nameMatch && !nameMatch.isLabel
      ? `<span class="search-result-hint">${highlightSubstring(nameMatch.name, nameMatch.matchAt, q.length)}</span>`
      : '';

    hitRegistry.push({ panelId, el: null });
    html += `<div class="search-result-group">
      <div class="search-result-head" data-hit="${hitRegistry.length - 1}">
        ${fileIconHtml(file.icon)}<span class="search-result-label">${labelHtml}</span>${hintHtml}
        ${groupHits.length ? `<span class="search-result-count">${groupHits.length}</span>` : ''}
      </div>`;

    groupHits.slice(0, MAX_SNIPPETS_PER_FILE).forEach(h => {
      const { snippet, offset } = truncateSnippet(h.entry.text, h.matchAt, q.length);
      hitRegistry.push({ panelId, el: h.entry.el });
      html += `<div class="search-result-snippet" data-hit="${hitRegistry.length - 1}">${highlightSubstring(snippet, offset, q.length)}</div>`;
    });
    html += '</div>';
  });

  resultsEl.innerHTML = html;
  resultsEl.querySelectorAll('[data-hit]').forEach(node => {
    node.addEventListener('click', ()=>{
      const hit = hitRegistry[Number(node.dataset.hit)];
      if(hit) navigateToHit(hit);
    });
  });
}

export function initSearch(){
  const input = document.getElementById('searchPanelInput');
  let debounceTimer = null;
  input.addEventListener('input', ()=>{
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(()=> renderSearchResults(input.value), SEARCH_DEBOUNCE_MS);
  });
}
