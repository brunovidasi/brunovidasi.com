// ==========================================================================
// The editor tab bar: opening, closing, reordering and the right-click menu.
// ==========================================================================

import { files } from './config.js';
import { state, resetWebsiteDetailIds } from './state.js';
import { tabIconHtml } from './icons.js';
import { positionFloatingMenu } from './utils.js';
import { updatePath, urlForTabId } from './router.js';
import { renderExplorer } from './explorer.js';
import { showActivePanel } from './panels.js';
import { removeToolTabFrame } from './tool-tabs.js';
import { closeMobileNav, toggleExplorer, isSidebarHidden } from './shell.js';

/**
 * Repaints everything that depends on which tabs exist and which is active,
 * then writes the URL. Every open/close/switch ends here.
 */
function syncWorkspace(){
  renderTabs();
  renderExplorer();
  showActivePanel();
  updatePath(state.activeId);
}

// ---- Opening & closing ----------------------------------------------------

export function setActive(id){
  resetWebsiteDetailIds();
  state.activeId = id;
  syncWorkspace();
}

export function openFile(id){
  if(files[id].folder) state.openFolders[files[id].folder] = true;
  if(!state.openTabs.includes(id)) state.openTabs.push(id);
  setActive(id);
  closeMobileNav();
}

export function closeTab(id){
  const idx = state.openTabs.indexOf(id);
  if(idx === -1) return;
  resetWebsiteDetailIds();
  state.openTabs.splice(idx, 1);
  removeToolTabFrame(id);
  if(state.activeId === id){
    // Focus shifts left, matching an editor closing the tab you were on.
    state.activeId = state.openTabs.length ? state.openTabs[Math.max(0, idx - 1)] : null;
  }
  syncWorkspace();
}

export function closeAllTabs(){
  state.openTabs.forEach(removeToolTabFrame);
  state.openTabs = [];
  state.activeId = null;
  resetWebsiteDetailIds();
  syncWorkspace();
}

export function closeOtherTabs(keepId){
  keepId = keepId || state.activeId;
  if(!keepId) return;
  state.openTabs.filter(id => id !== keepId).forEach(removeToolTabFrame);
  state.openTabs = [keepId];
  state.activeId = keepId;
  resetWebsiteDetailIds();
  syncWorkspace();
}

export function closeTabsToTheRight(id){
  const idx = state.openTabs.indexOf(id);
  if(idx === -1) return;
  const toClose = state.openTabs.slice(idx + 1);
  if(!toClose.length) return;
  toClose.forEach(removeToolTabFrame);
  state.openTabs = state.openTabs.slice(0, idx + 1);
  resetWebsiteDetailIds();
  if(!state.openTabs.includes(state.activeId)) state.activeId = id;
  syncWorkspace();
}

export function cycleTabs(direction){
  if(!state.openTabs.length) return;
  const idx = state.openTabs.indexOf(state.activeId);
  const nextIdx = idx === -1 ? 0 : (idx + direction + state.openTabs.length) % state.openTabs.length;
  setActive(state.openTabs[nextIdx]);
}

export function jumpToTabIndex(index){
  if(index < 0 || index >= state.openTabs.length) return;
  setActive(state.openTabs[index]);
}

// ---- Drag to reorder ------------------------------------------------------

let draggedTabId = null;

/** True when the pointer is on the left half of a tab, i.e. drop before it. */
function isBeforeMidpoint(tab, clientX){
  const rect = tab.getBoundingClientRect();
  return (clientX - rect.left) < rect.width / 2;
}

function clearDropIndicators(){
  document.querySelectorAll('.tab').forEach(t => {
    t.classList.remove('dragging', 'drag-over-before', 'drag-over-after');
  });
}

function wireTabDragAndDrop(tab, id){
  tab.addEventListener('dragstart', (e)=>{
    draggedTabId = id;
    tab.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', id);
  });
  tab.addEventListener('dragend', ()=>{
    draggedTabId = null;
    clearDropIndicators();
  });
  tab.addEventListener('dragover', (e)=>{
    if(!draggedTabId || draggedTabId === id) return;
    e.preventDefault();
    const before = isBeforeMidpoint(tab, e.clientX);
    tab.classList.toggle('drag-over-before', before);
    tab.classList.toggle('drag-over-after', !before);
  });
  tab.addEventListener('dragleave', ()=>{
    tab.classList.remove('drag-over-before', 'drag-over-after');
  });
  tab.addEventListener('drop', (e)=>{
    e.preventDefault();
    tab.classList.remove('drag-over-before', 'drag-over-after');
    if(!draggedTabId || draggedTabId === id) return;
    const fromIdx = state.openTabs.indexOf(draggedTabId);
    if(fromIdx === -1) return;
    state.openTabs.splice(fromIdx, 1);
    const before = isBeforeMidpoint(tab, e.clientX);
    const toIdx = state.openTabs.indexOf(id);
    state.openTabs.splice(before ? toIdx : toIdx + 1, 0, draggedTabId);
    renderTabs();
  });
}

// ---- Rendering ------------------------------------------------------------

export function renderTabs(){
  const bar = document.getElementById('tabBar');
  bar.innerHTML = '';

  if(state.openTabs.length === 0){
    // With no tabs there is nothing to look at, so never leave the visitor
    // facing a hidden explorer and an empty editor.
    document.getElementById('shell').classList.remove('sidebar-hidden');
    const note = document.createElement('div');
    note.className = 'tabs-empty-note';
    note.textContent = 'No tabs open';
    bar.appendChild(note);
    return;
  }

  state.openTabs.forEach(id => {
    const tab = document.createElement('div');
    tab.className = 'tab' + (state.activeId === id ? ' active' : '');
    tab.draggable = true;
    tab.innerHTML = tabIconHtml(files[id]) + files[id].label + '<span class="close-x">✕</span>';
    tab.addEventListener('click', (e)=>{
      if(e.target.classList.contains('close-x')) closeTab(id);
      else setActive(id);
    });
    tab.addEventListener('contextmenu', (e)=>{
      e.preventDefault();
      openTabContextMenu(e.clientX, e.clientY, id);
    });
    wireTabDragAndDrop(tab, id);
    bar.appendChild(tab);
  });

  const activeTab = bar.querySelector('.tab.active');
  if(activeTab) activeTab.scrollIntoView({ block: 'nearest', inline: 'nearest' });
}

// ---- Context menu ---------------------------------------------------------

let tabContextMenu = null;
let tabContextMenuId = null;

export function closeTabContextMenu(){
  if(!tabContextMenu) return;
  tabContextMenu.classList.remove('show');
  tabContextMenuId = null;
}

export function openTabContextMenu(x, y, id){
  tabContextMenuId = id;
  const idx = state.openTabs.indexOf(id);
  document.getElementById('ctxCloseOthers').toggleAttribute('disabled', state.openTabs.length < 2);
  document.getElementById('ctxCloseRight').toggleAttribute('disabled', idx === -1 || idx >= state.openTabs.length - 1);
  document.getElementById('ctxFullscreen').textContent = isSidebarHidden() ? 'Show Explorer' : 'Fullscreen';
  tabContextMenu.classList.add('show');
  positionFloatingMenu(tabContextMenu, x, y);
}

function initTabContextMenu(){
  tabContextMenu = document.getElementById('tabContextMenu');

  // Entries that act on the right-clicked tab.
  const perTabActions = {
    ctxCloseTab:        closeTab,
    ctxCloseOthers:     closeOtherTabs,
    ctxCloseRight:      closeTabsToTheRight,
    ctxOpenNewWindow:   (id)=> window.open(urlForTabId(id), '_blank')
  };
  Object.entries(perTabActions).forEach(([elId, run]) => {
    document.getElementById(elId).addEventListener('click', ()=>{
      if(tabContextMenuId) run(tabContextMenuId);
      closeTabContextMenu();
    });
  });

  // Entries that act on the workspace as a whole.
  const globalActions = { ctxCloseAll: closeAllTabs, ctxFullscreen: toggleExplorer };
  Object.entries(globalActions).forEach(([elId, run]) => {
    document.getElementById(elId).addEventListener('click', ()=>{
      run();
      closeTabContextMenu();
    });
  });

  document.addEventListener('click', (e)=>{
    if(!tabContextMenu.classList.contains('show')) return;
    if(tabContextMenu.contains(e.target)) return;
    closeTabContextMenu();
  });
  document.getElementById('tabBar').addEventListener('scroll', closeTabContextMenu);
  window.addEventListener('resize', closeTabContextMenu);

  // A click inside a tool tab's iframe never bubbles to this document, so the
  // outside-click handler above can't see it; catch it via the focus shift instead.
  window.addEventListener('blur', ()=>{
    setTimeout(()=>{
      if(document.activeElement && document.activeElement.tagName === 'IFRAME') closeTabContextMenu();
    }, 0);
  });
}

export function initTabs(){
  initTabContextMenu();
}
