// ==========================================================================
// The bottom panel: TERMINAL and PROBLEMS, plus its drag-to-resize edge.
// ==========================================================================

import { isMobileViewport } from './utils.js';
import { repositionActiveToolTabFrame } from './tool-tabs.js';
import { bootTerminal } from './terminal.js';

const panelEl = () => document.getElementById('bottomPanel');
const handleEl = () => document.getElementById('bottomPanelResizeHandle');
const hitEl = () => document.getElementById('bottomPanelResizeHit');

export function isBottomPanelOpen(){
  return !panelEl().hidden;
}

function activeBottomPanelTab(){
  const active = document.querySelector('.bottom-panel-tab.active');
  return active ? active.dataset.panel : 'terminal';
}

function switchBottomPanelTab(tab){
  document.querySelectorAll('.bottom-panel-tab').forEach(btn => {
    btn.classList.toggle('active', btn.dataset.panel === tab);
  });
  document.getElementById('bottomPanelTerminal').hidden = tab !== 'terminal';
  document.getElementById('bottomPanelProblems').hidden = tab !== 'problems';
  if(tab === 'terminal'){
    bootTerminal();
    document.getElementById('terminalInput').focus();
  }
}

export function openBottomPanel(tab){
  panelEl().hidden = false;
  handleEl().hidden = false;
  switchBottomPanelTab(tab);
  repositionActiveToolTabFrame();
  syncBottomPanelResizeHit();
}

export function closeBottomPanel(){
  panelEl().hidden = true;
  handleEl().hidden = true;
  repositionActiveToolTabFrame();
  syncBottomPanelResizeHit();
}

export function toggleBottomPanel(tab){
  if(isBottomPanelOpen() && activeBottomPanelTab() === tab) closeBottomPanel();
  else openBottomPanel(tab);
}

// ---- Resize ---------------------------------------------------------------

// Keeps the invisible resize hit-target (a body-level sibling that can
// out-stack an open tool tab's iframe — see initBottomPanelResize) aligned
// with the visible handle's actual on-screen box.
function syncBottomPanelResizeHit(){
  const hit = hitEl();
  const handle = handleEl();
  if(!hit) return;
  if(handle.hidden || isMobileViewport()){
    hit.hidden = true;
    return;
  }
  const r = handle.getBoundingClientRect();
  hit.hidden = false;
  hit.style.left = r.left + 'px';
  hit.style.top = r.top + 'px';
  hit.style.width = r.width + 'px';
  hit.style.height = r.height + 'px';
}

function initBottomPanelResize(){
  const bottomPanel = panelEl();
  const handle = handleEl();
  const hit = hitEl();
  const overlay = document.getElementById('bottomPanelResizeOverlay');
  if(!bottomPanel || !handle || !hit || !overlay) return;

  const MIN_HEIGHT = 120;
  const MAX_HEIGHT = 560;
  const DEFAULT_HEIGHT = 220;
  /** Two mousedowns closer together than this count as a double-click. */
  const DOUBLE_CLICK_MS = 350;

  function applyHeight(height){
    bottomPanel.style.setProperty('--bottom-panel-height', height + 'px');
    repositionActiveToolTabFrame();
    syncBottomPanelResizeHit();
  }

  let dragging = false;
  let startY = 0;
  let startHeight = 0;
  let pendingHeight = null;
  let rafId = null;

  function flushHeight(){
    rafId = null;
    if(pendingHeight !== null) applyHeight(pendingHeight);
  }

  // The handle lives inside #app, which establishes its own stacking context
  // (position:relative + z-index:1), so nothing inside it can out-stack a
  // fixed, body-level sibling like an open tool tab's .tool-tab-frame
  // (z-index: 40) — which the handle's -6px margin makes it visually
  // overlap. That silently swallows the drag's mousedown whenever a
  // mini-tool/tool tab is open underneath, so `hit` (a real body-level
  // sibling kept aligned to the handle's box, see syncBottomPanelResizeHit)
  // takes the mousedown/hover instead; `handle` stays purely visual.
  hit.addEventListener('mouseenter', ()=> handle.classList.add('hit-hover'));
  hit.addEventListener('mouseleave', ()=> handle.classList.remove('hit-hover'));

  // Native dblclick can't be used to detect a double-click here: the overlay
  // it activates on mousedown covers the handle, so the matching mouseup
  // lands on the overlay instead of the handle and the browser never sees a
  // same-target click/dblclick pair. Time consecutive mousedowns ourselves.
  let lastDownTime = 0;
  hit.addEventListener('mousedown', (e)=>{
    if(isMobileViewport()) return;
    const now = Date.now();
    if(now - lastDownTime < DOUBLE_CLICK_MS){
      lastDownTime = 0;
      applyHeight(DEFAULT_HEIGHT);
      e.preventDefault();
      return;
    }
    lastDownTime = now;
    dragging = true;
    startY = e.clientY;
    startHeight = bottomPanel.getBoundingClientRect().height;
    handle.classList.add('dragging');
    overlay.classList.add('active');
    document.body.style.cursor = 'row-resize';
    document.body.style.userSelect = 'none';
    e.preventDefault();
  });

  // Same reasoning as the explorer's resize overlay: without it, dragging
  // across an open iframe (Terminal sits right above tool tabs) stops
  // delivering mousemove/mouseup and the drag stalls.
  overlay.addEventListener('mousemove', (e)=>{
    if(!dragging) return;
    pendingHeight = Math.min(MAX_HEIGHT, Math.max(MIN_HEIGHT, startHeight - (e.clientY - startY)));
    if(rafId === null) rafId = requestAnimationFrame(flushHeight);
  });

  function endDrag(){
    if(!dragging) return;
    dragging = false;
    if(rafId !== null){ cancelAnimationFrame(rafId); rafId = null; }
    if(pendingHeight !== null){ applyHeight(pendingHeight); pendingHeight = null; }
    handle.classList.remove('dragging');
    overlay.classList.remove('active');
    document.body.style.cursor = '';
    document.body.style.userSelect = '';
    syncBottomPanelResizeHit();
  }
  overlay.addEventListener('mouseup', endDrag);
  // A fast drag released near the bottom edge of the window can put the
  // cursor outside the viewport, where the overlay itself is no longer the
  // event target — fall back to window's mouseup so the drag never gets
  // stuck "on" with the resize cursor and overlay left intercepting clicks.
  window.addEventListener('mouseup', endDrag);

  window.addEventListener('resize', syncBottomPanelResizeHit);
  if(window.ResizeObserver){
    const editorArea = document.getElementById('editorArea');
    if(editorArea) new ResizeObserver(syncBottomPanelResizeHit).observe(editorArea);
  }
  syncBottomPanelResizeHit();
}

// ---- Wiring ---------------------------------------------------------------

export function initBottomPanel(){
  document.querySelectorAll('.bottom-panel-tab').forEach(btn => {
    btn.addEventListener('click', ()=> switchBottomPanelTab(btn.dataset.panel));
  });
  document.getElementById('bottomPanelCloseBtn').addEventListener('click', closeBottomPanel);
  document.getElementById('terminalBtn').addEventListener('click', ()=> toggleBottomPanel('terminal'));
  initBottomPanelResize();
}
