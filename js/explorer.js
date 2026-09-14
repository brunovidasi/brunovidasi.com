// ==========================================================================
// The file explorer: the folder tree, its "···" menu, and the right-click
// menu on a tree item.
//
// The tree is two levels deep at most. Depth 0 is the fixed `rootOrder`;
// depth 1 is a folder's `children`, except under a mini-tool category, whose
// children are the tool tabs discovered in the project JSON at runtime.
// ==========================================================================

import {
  files, folders, rootOrder, FOLDER_DEFAULT_FILE, MINI_TOOL_CATEGORY_IDS
} from './config.js';
import { state, TOOL_TAB_REGISTRY } from './state.js';
import { fileIconHtml, folderIconHtml } from './icons.js';
import { isMobileViewport, positionFloatingMenu } from './utils.js';
import { openFile, closeAllTabs, closeOtherTabs, closeTabContextMenu } from './tabs.js';
import { openToolTab, toolIdsForCategory } from './tool-tabs.js';
import { toggleDoc } from './panels.js';
import { urlForTabId } from './router.js';
import { toggleExplorer } from './shell.js';

// ---- Tree structure -------------------------------------------------------

export function folderChildren(key){
  return MINI_TOOL_CATEGORY_IDS.has(key) ? [key, ...toolIdsForCategory(key)] : folders[key].children;
}

/** The folder itself plus every folder above it, so the whole branch can be highlighted. */
export function ancestorFolderIds(folderId){
  const ids = new Set();
  let cur = folderId;
  while(cur && !ids.has(cur)){
    ids.add(cur);
    cur = files[cur] ? files[cur].folder : null;
  }
  return ids;
}

function isFolderNode(key, depth){
  return depth === 0 ? !!folders[key] : depth === 1 && MINI_TOOL_CATEGORY_IDS.has(key);
}

function leafDisplay(id){
  if(TOOL_TAB_REGISTRY[id]) return { label: id + '.js', iconHtml: fileIconHtml('js') };
  const file = files[id];
  return { label: file.label, iconHtml: fileIconHtml(file.icon) };
}

function openTreeItem(id){
  if(TOOL_TAB_REGISTRY[id]) openToolTab(id);
  else openFile(id);
}

// ---- Rendering ------------------------------------------------------------

function renderTreeNode(key, depth, container, highlightId, activeFolderIds){
  if(isFolderNode(key, depth)){
    const folder = folders[key];
    const defaultFile = FOLDER_DEFAULT_FILE[key] || (files[key] ? key : null);
    const openable = !!defaultFile;
    const selected = (openable && highlightId === defaultFile) || activeFolderIds.has(key);

    const head = document.createElement('div');
    head.className = 'tree-item' + (state.openFolders[key] ? ' folder-open' : '') + (selected ? ' active' : '');
    if(depth > 0) head.style.paddingLeft = (depth * 20) + 'px';
    head.innerHTML = `<span class="left">${folderIconHtml(!!state.openFolders[key])}${folder.label}</span><span class="caret">▸</span>`;
    head.onclick = ()=>{
      const nextOpen = !state.openFolders[key];
      state.openFolders[key] = nextOpen;
      // On desktop, expanding a folder also opens its readme; on mobile that
      // would close the drawer the visitor is still navigating.
      if(openable && nextOpen && !isMobileViewport()) openFile(defaultFile);
      else renderExplorer();
    };
    container.appendChild(head);

    const kids = document.createElement('div');
    kids.className = 'folder-children' + (state.openFolders[key] ? '' : ' collapsed');
    folderChildren(key).forEach(childId => renderTreeNode(childId, depth + 1, kids, highlightId, activeFolderIds));
    container.appendChild(kids);
    return;
  }

  const { label, iconHtml } = leafDisplay(key);
  const item = document.createElement('div');
  if(depth > 0) item.style.paddingLeft = (depth * 20) + 'px';
  item.className = 'tree-item' + (highlightId === key ? ' active' : '');
  item.innerHTML = `<span class="left">${iconHtml}${label}</span>`;
  item.onclick = ()=> openTreeItem(key);
  item.addEventListener('contextmenu', (e)=>{
    e.preventDefault();
    e.stopPropagation();
    openTreeContextMenu(e.clientX, e.clientY, key);
  });
  container.appendChild(item);
}

export function renderExplorer(){
  const tree = document.getElementById('fileTree');
  tree.innerHTML = '';

  const activeFile = state.activeId && files[state.activeId];
  // A tool opened from a project card highlights that card's page rather than
  // itself, since it has no row of its own outside the mini-tools folders.
  const highlightId = (activeFile && activeFile.isToolTab && activeFile.parentId && !MINI_TOOL_CATEGORY_IDS.has(activeFile.parentId))
    ? activeFile.parentId
    : state.activeId;
  const immediateFolderId = activeFile ? (activeFile.isToolTab ? activeFile.parentId : activeFile.folder) : null;
  const activeFolderIds = immediateFolderId ? ancestorFolderIds(immediateFolderId) : new Set();

  rootOrder.forEach(key => renderTreeNode(key, 0, tree, highlightId, activeFolderIds));
}

export function setAllFolders(open){
  Object.keys(folders).forEach(key => { state.openFolders[key] = open; });
  renderExplorer();
}

export function collapseFoldersExceptCurrent(id){
  const file = files[id];
  const parentId = file && (file.isToolTab ? file.parentId : file.folder);
  const keepOpen = parentId ? ancestorFolderIds(parentId) : new Set();
  Object.keys(folders).forEach(key => { state.openFolders[key] = keepOpen.has(key); });
}

// ---- Sidebar view tabs (EXPLORER / SEARCH) --------------------------------

export function switchSidebarView(view){
  document.querySelectorAll('.view-tab').forEach(btn => btn.classList.toggle('active', btn.dataset.view === view));
  document.getElementById('fileTree').hidden = view !== 'explorer';
  document.getElementById('searchPanel').hidden = view !== 'search';
  // The Quick Open button only makes sense alongside the search field.
  document.getElementById('quickOpenBtn').hidden = view !== 'search';
}

// ---- "···" actions menu ---------------------------------------------------

let explorerMenu = null;
let explorerMore = null;

export function closeExplorerMenu(){
  if(!explorerMenu) return;
  explorerMenu.classList.remove('show');
  explorerMore.classList.remove('active');
}

function openExplorerMenu(){
  closeTabContextMenu();
  closeTreeContextMenu();
  explorerMenu.classList.add('show');
  explorerMore.classList.add('active');
}

function initExplorerMenu(){
  explorerMore = document.getElementById('explorerMore');
  explorerMenu = document.getElementById('explorerMenu');
  const labelRow = document.querySelector('.explorer .label-row');

  explorerMore.addEventListener('click', (e)=>{
    e.stopPropagation();
    const opening = !explorerMenu.classList.contains('show');
    closeTabContextMenu();
    closeTreeContextMenu();
    if(opening) openExplorerMenu();
  });

  labelRow.addEventListener('contextmenu', (e)=>{
    e.preventDefault();
    e.stopPropagation();
    openExplorerMenu();
  });

  const menuActions = {
    expandAllFoldersBtn:   ()=> setAllFolders(true),
    collapseAllFoldersBtn: ()=> setAllFolders(false),
    closeAllTabsBtn:       closeAllTabs,
    closeOtherTabsBtn:     ()=> closeOtherTabs(),
    hideExplorerBtn:       toggleExplorer
  };
  Object.entries(menuActions).forEach(([id, run]) => {
    document.getElementById(id).addEventListener('click', ()=>{
      run();
      closeExplorerMenu();
    });
  });

  document.addEventListener('click', (e)=>{
    if(!explorerMenu.classList.contains('show')) return;
    if(explorerMenu.contains(e.target) || explorerMore.contains(e.target)) return;
    closeExplorerMenu();
  });
}

// ---- Tree item context menu ----------------------------------------------

let treeContextMenu = null;
let treeContextMenuId = null;

export function closeTreeContextMenu(){
  if(!treeContextMenu) return;
  treeContextMenu.classList.remove('show');
  treeContextMenuId = null;
}

export function openTreeContextMenu(x, y, id){
  treeContextMenuId = id;
  const tool = TOOL_TAB_REGISTRY[id];
  document.getElementById('ctxTreeQuickView').hidden = !tool;
  document.getElementById('ctxTreeGithub').hidden = !(tool && tool.github);
  document.getElementById('ctxTreeCodepen').hidden = !(tool && tool.codepen);
  document.getElementById('ctxTreeLinksDivider').hidden = !(tool && (tool.github || tool.codepen));
  closeExplorerMenu();
  closeTabContextMenu();
  treeContextMenu.classList.add('show');
  positionFloatingMenu(treeContextMenu, x, y);
}

/** Opens the tool's own page and expands its card in place, without leaving it. */
function quickViewTreeItem(id){
  const tool = TOOL_TAB_REGISTRY[id];
  if(!tool) return;
  if(tool.category) openFile(tool.category);
  const embed = document.getElementById('embed-' + id);
  if(embed && !embed.classList.contains('open')) toggleDoc(id);
  requestAnimationFrame(()=>{
    const card = document.getElementById('project-' + id);
    if(card) card.scrollIntoView({ block: 'center', behavior: 'smooth' });
  });
}

function initTreeContextMenu(){
  treeContextMenu = document.getElementById('treeContextMenu');

  const menuActions = {
    ctxTreeQuickView:  (id)=> quickViewTreeItem(id),
    ctxTreeOpen:       (id)=> openTreeItem(id),
    ctxTreeOpenNewTab: (id)=> window.open(urlForTabId(id), '_blank'),
    ctxTreeGithub:     (id)=> openToolLink(id, 'github'),
    ctxTreeCodepen:    (id)=> openToolLink(id, 'codepen')
  };
  Object.entries(menuActions).forEach(([elId, run]) => {
    document.getElementById(elId).addEventListener('click', ()=>{
      if(treeContextMenuId) run(treeContextMenuId);
      closeTreeContextMenu();
    });
  });

  document.addEventListener('click', (e)=>{
    if(!treeContextMenu.classList.contains('show')) return;
    if(treeContextMenu.contains(e.target)) return;
    closeTreeContextMenu();
  });
  document.getElementById('fileTree').addEventListener('scroll', closeTreeContextMenu);
  window.addEventListener('resize', closeTreeContextMenu);
}

function openToolLink(id, key){
  const tool = TOOL_TAB_REGISTRY[id];
  if(tool && tool[key]) window.open(tool[key], '_blank', 'noopener');
}

// ---- Wiring ---------------------------------------------------------------

export function initExplorer(){
  initExplorerMenu();
  initTreeContextMenu();
  document.querySelectorAll('.view-tab').forEach(btn => {
    btn.addEventListener('click', ()=> switchSidebarView(btn.dataset.view));
  });

  // Capture phase runs before any element's own contextmenu handler, so any
  // menu left open from a previous right-click is closed before a new one
  // (or the browser's native menu, e.g. on empty body) takes its place.
  document.addEventListener('contextmenu', ()=>{
    closeExplorerMenu();
    closeTabContextMenu();
    closeTreeContextMenu();
  }, true);
}
