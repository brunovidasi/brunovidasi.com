// ==========================================================================
// Panels: showing the active one, and the widgets that live inside them
// (collapsible document embeds, the commit list, the intro code block).
// ==========================================================================

import { files, WEBSITE_STYLE_CATEGORIES } from './config.js';
import { state } from './state.js';
import { humanTypeSect } from './typing.js';
import { ICON_EYE_SVG, ICON_EYE_OFF_SVG } from './icons.js';
import { actSelector, registerActions } from './actions.js';
import { updateDocumentTitle } from './router.js';
import { updateToolTabFrames } from './tool-tabs.js';
import { renderWebsiteDetail } from './websites.js';
import { fitGameFrame } from './games.js';
import { startBioTyping } from './boot.js';
import { startEmailReveal } from './contact.js';

/**
 * Reveals the panel for the active tab and runs its one-off entrance effects.
 * Tool tabs have no panel of their own — their iframe is shown instead.
 */
export function showActivePanel(){
  document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
  const empty = document.getElementById('emptyState');
  const isToolTab = state.activeId && files[state.activeId] && files[state.activeId].isToolTab;

  updateToolTabFrames(isToolTab ? state.activeId : null);
  updateDocumentTitle();

  if(!state.activeId || !state.openTabs.includes(state.activeId)){
    empty.classList.add('show');
    return;
  }

  empty.classList.remove('show');
  if(isToolTab) return;

  const panel = document.getElementById('panel-' + state.activeId);
  if(!panel) return;
  panel.classList.add('active');

  const sect = panel.querySelector('h2.sect');
  if(sect) humanTypeSect(sect);
  if(state.activeId === 'about') startBioTyping();
  if(state.activeId === 'contact') startEmailReveal();
  if(WEBSITE_STYLE_CATEGORIES.includes(state.activeId)) renderWebsiteDetail(state.activeId);
  if(state.activeId === 'mini-games') fitGameFrame();
}

// ---- Document embeds ------------------------------------------------------

/** Embeds are lazy: the iframe only gets its src the first time it is opened. */
function loadEmbedIframe(embed){
  const iframe = embed.querySelector('iframe[data-src]');
  if(!iframe) return;
  iframe.src = iframe.dataset.src;
  iframe.removeAttribute('data-src');
}

/** Swaps a View button between its open and closed icon + label. */
function setViewButtonState(btn, isOpen){
  if(!btn) return;
  const icon = btn.querySelector('svg.btn-icon');
  if(icon) icon.outerHTML = isOpen ? ICON_EYE_OFF_SVG : ICON_EYE_SVG;
  const viewLabel = btn.dataset.viewLabel;
  if(!viewLabel) return;
  const textNode = Array.from(btn.childNodes).find(n => n.nodeType === Node.TEXT_NODE && n.textContent.trim());
  if(textNode) textNode.textContent = isOpen ? 'Hide' : viewLabel;
}

export function toggleDoc(id){
  const embed = document.getElementById('embed-' + id);
  if(!embed) return;
  const isOpen = embed.classList.toggle('open');
  if(isOpen) loadEmbedIframe(embed);

  document.querySelectorAll(`.doc-btn${actSelector('toggle-doc', id)}`)
    .forEach(btn => setViewButtonState(btn, isOpen));

  // Within an exclusive group, opening one closes the others, so every
  // sibling's button needs re-syncing rather than just this one's.
  const group = embed.dataset.group;
  if(!group) return;
  document.querySelectorAll(`.doc-embed[data-group="${group}"]`).forEach(e => {
    const groupId = e.id.replace(/^embed-/, '');
    const groupBtn = document.querySelector(`.doc-btn${actSelector('toggle-exclusive-doc', groupId)}`);
    setViewButtonState(groupBtn, e.classList.contains('open'));
  });
}

function toggleExclusiveDoc(id, group){
  document.querySelectorAll(`.doc-embed[data-group="${group}"]`).forEach(e => {
    if(e.id !== 'embed-' + id) e.classList.remove('open');
  });
  toggleDoc(id);
}

function toggleAllDocs(category, btn){
  const grid = document.querySelector(`[data-category="${category}"]`);
  if(!grid) return;
  const embeds = grid.querySelectorAll('.doc-embed');
  if(!embeds.length) return;
  const shouldOpen = !Array.from(embeds).every(e => e.classList.contains('open'));
  embeds.forEach(e => {
    e.classList.toggle('open', shouldOpen);
    if(shouldOpen) loadEmbedIframe(e);
  });
  if(btn) btn.innerHTML = shouldOpen ? `${ICON_EYE_OFF_SVG}Collapse all` : `${ICON_EYE_SVG}View all`;
}

export function openInNewWindow(path){
  window.open(path, '_blank', 'noopener');
}

// ---- Experience / education commits ---------------------------------------

/** Turns each commit's message row into a toggle for its details block. */
function initCommitToggles(){
  document.querySelectorAll('.commit').forEach(commit => {
    const details = commit.querySelector('.details');
    if(!details) return;
    details.classList.add('more');
    commit.classList.add('open');

    const msg = commit.querySelector('.msg');
    msg.classList.add('toggle-title');
    msg.insertAdjacentHTML('beforeend', ' <span class="chev">▾</span>');
    msg.addEventListener('click', ()=> commit.classList.toggle('open'));

    const logo = commit.querySelector('.commit-logo');
    if(logo) logo.addEventListener('click', ()=> commit.classList.toggle('open'));
  });
}

function toggleAllJobDetails(){
  const commits = document.querySelectorAll('#panel-experience .commit');
  const btn = document.getElementById('toggleJobDetailsBtn');
  if(!commits.length || !btn) return;
  const hide = btn.dataset.hidden !== 'true';
  commits.forEach(c => c.classList.toggle('open', !hide));
  btn.dataset.hidden = String(hide);
  btn.innerHTML = hide
    ? `${ICON_EYE_SVG}Show job details`
    : `${ICON_EYE_OFF_SVG}Hide job details`;
}

// ---- intro.js code block --------------------------------------------------

let introCodeCommented = false;

/** Cmd+/ on the intro panel, as in an editor. Real comments are left alone. */
export function toggleIntroCodeComment(){
  const lines = document.querySelectorAll('#panel-intro .code-line');
  if(!lines.length) return;
  introCodeCommented = !introCodeCommented;
  lines.forEach(line => {
    if(line.querySelector('.com')) return;
    line.classList.toggle('line-commented', introCodeCommented);
  });
}

// ---- Wiring ---------------------------------------------------------------

export function initPanels(){
  initCommitToggles();
  registerActions({
    'toggle-doc':            (el, [id]) => toggleDoc(id),
    'toggle-exclusive-doc':  (el, [id, group]) => toggleExclusiveDoc(id, group),
    'toggle-all-docs':       (el, [category]) => toggleAllDocs(category, el),
    'toggle-job-details':    () => toggleAllJobDetails(),
    'open-window':           (el, [path]) => openInNewWindow(path)
  });
}
