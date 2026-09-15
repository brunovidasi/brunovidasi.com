// ==========================================================================
// Tool tabs: mini-tools and games opened as full editor tabs.
//
// Unlike the static panels, these are real pages in an <iframe>. The frame is
// a fixed, body-level element kept aligned to the editor area rather than a
// child of it, so the page behind it can stay scroll-locked while the tool
// scrolls on its own.
// ==========================================================================

import { files, MINI_TOOL_CATEGORY_IDS } from './config.js';
import { state, TOOL_TAB_REGISTRY } from './state.js';
import { escapeHtml } from './utils.js';
import { typingDelay } from './typing.js';
import { setActive } from './tabs.js';
import { closeMobileNav } from './shell.js';

export function toolIdsForCategory(categoryId){
  return Object.keys(TOOL_TAB_REGISTRY).filter(id => TOOL_TAB_REGISTRY[id].category === categoryId);
}

// ---- Registry -------------------------------------------------------------

/** Adds a discovered tool to `files` so the tab bar and explorer can show it. */
export function registerToolFile(id){
  if(files[id]) return;
  const info = TOOL_TAB_REGISTRY[id];
  files[id] = {
    label: info.title,
    toolIcon: info.icon,
    fileIconType: info.fileIcon,
    folder: null,
    isToolTab: true,
    toolPath: info.path,
    parentId: info.category || null
  };
}

/** Opens a tool by route without touching the URL. Returns false if unknown. */
export function tryOpenToolTabRoute(id){
  if(!TOOL_TAB_REGISTRY[id]) return false;
  registerToolFile(id);
  const parentId = files[id].parentId;
  if(parentId){
    state.openFolders[parentId] = true;
    const parent = files[parentId];
    if(parent && parent.folder) state.openFolders[parent.folder] = true;
  }
  if(!state.openTabs.includes(id)) state.openTabs.push(id);
  state.activeId = id;
  return true;
}

export function openToolTab(id){
  if(!tryOpenToolTabRoute(id)) return;
  setActive(id);
  closeMobileNav();
}

/** `?all=1` — opens every tool alongside the deep-linked one. */
export function openCategorySiblings(id){
  const parentId = files[id] && files[id].parentId;
  if(!parentId || !MINI_TOOL_CATEGORY_IDS.has(parentId)) return;
  toolIdsForCategory(parentId).forEach(siblingId => {
    registerToolFile(siblingId);
    if(!state.openTabs.includes(siblingId)) state.openTabs.push(siblingId);
  });
}

// ---- Frame geometry -------------------------------------------------------

let toolTabResizeObserver = null;
let toolTabScrollLockY = 0;
let toolTabLocked = false;

function positionToolTabFrame(frame){
  const editorArea = document.getElementById('editorArea');
  if(!editorArea) return;
  const rect = editorArea.getBoundingClientRect();
  frame.style.top = rect.top + 'px';
  frame.style.left = rect.left + 'px';
  frame.style.width = rect.width + 'px';
  frame.style.height = rect.height + 'px';
  // Grow the open animation out of the tab's own corner, not the frame's.
  frame.style.transformOrigin = `${130 - rect.left}px ${(window.innerHeight - 34) - rect.top}px`;
}

export function repositionActiveToolTabFrame(){
  const active = document.querySelector('.tool-tab-frame.active');
  if(active) positionToolTabFrame(active);
}

// ---- Frame lifecycle ------------------------------------------------------

// Mobile Safari discards backgrounded tabs and, on return, reloads the page
// from session history — which also restores each child iframe's last URL,
// matched to the new iframes by their position in the document. Tool frames
// are created on demand, so after a restore the positions no longer line up
// and a frame would load a *different* tool than its tab says. Three guards:
//  1. a stable, unique `name`, so WebKit matches history by name, not index;
//  2. navigate via location.replace() after insertion, which overrides any
//     restored URL and adds no child entries to the back/forward list;
//  3. on load, if the frame still shows another tool's page, correct it.

function absoluteUrl(path){
  return new URL(path, location.href);
}

export function loadFrameWithoutHistory(iframe, path){
  try {
    iframe.contentWindow.location.replace(absoluteUrl(path).href);
  } catch(e){
    iframe.src = path;
  }
}

function isBlankFrame(iframe){
  try { return iframe.contentWindow.location.href === 'about:blank'; }
  catch(e){ return false; }
}

/** Pathname of another registered tool's page, used to spot a mixed-up restore. */
function otherToolPathnames(id){
  return new Set(Object.keys(TOOL_TAB_REGISTRY)
    .filter(otherId => otherId !== id && TOOL_TAB_REGISTRY[otherId].path)
    .map(otherId => decodeURIComponent(absoluteUrl(TOOL_TAB_REGISTRY[otherId].path).pathname)));
}

function wireToolFrameSelfHeal(iframe, id, path){
  const expected = decodeURIComponent(absoluteUrl(path).pathname);
  let healed = false;
  iframe.addEventListener('load', ()=>{
    if(healed || isBlankFrame(iframe)) return;
    let loaded;
    try { loaded = decodeURIComponent(iframe.contentWindow.location.pathname); }
    catch(e){ return; }
    if(loaded === expected || !otherToolPathnames(id).has(loaded)) return;
    console.warn(`Tool frame "${id}" restored with ${loaded}; reloading ${expected}`);
    healed = true;
    loadFrameWithoutHistory(iframe, path);
  });
}

/** Types out a fake compile line over the frame while the tool loads. */
function playToolCompileAnimation(frame, id){
  const bar = document.createElement('div');
  bar.className = 'tool-compile-bar';
  const line = document.createElement('span');
  bar.appendChild(line);
  frame.appendChild(bar);

  requestAnimationFrame(()=> bar.classList.add('show'));

  const text = `$ compiling ${id}.module ... ok`;
  let i = 0;
  function typeNext(){
    if(!document.body.contains(bar)) return;
    if(i >= text.length){
      line.insertAdjacentHTML('beforeend', '<span class="boot-cursor">_</span>');
      setTimeout(()=>{
        bar.classList.remove('show');
        bar.addEventListener('transitionend', ()=> bar.remove(), { once: true });
      }, 2000);
      return;
    }
    line.textContent += text[i];
    setTimeout(typeNext, typingDelay(text[i], 0.3));
    i++;
  }
  typeNext();
}

/** Fades the loading mask out once the tool's iframe reports it is ready. */
function wireToolTabMask(frame){
  const mask = frame.querySelector('.tool-tab-mask');
  const maskLabel = mask.querySelector('.tool-tab-mask-label');
  const maskDots = maskLabel.querySelector('.tool-tab-mask-dots');
  let maskDotsInterval = null;

  // Only reveal the "loading..." label if the load is actually slow enough
  // to notice, so fast loads keep the plain fade with no extra text/delay.
  const maskLabelTimer = setTimeout(()=>{
    maskLabel.classList.add('show');
    let dots = 0;
    maskDotsInterval = setInterval(()=>{
      dots = (dots + 1) % 4;
      maskDots.textContent = '.'.repeat(dots);
    }, 450);
  }, 500);

  const iframe = frame.querySelector('iframe');
  iframe.addEventListener('load', function onLoad(){
    // The initial about:blank document fires its own load before the tool's.
    if(isBlankFrame(iframe)) return;
    iframe.removeEventListener('load', onLoad);
    clearTimeout(maskLabelTimer);
    clearInterval(maskDotsInterval);
    requestAnimationFrame(()=>{
      mask.classList.add('hide');
      mask.addEventListener('transitionend', ()=> mask.remove(), { once: true });
    });
  });
}

function ensureToolTabFrame(id){
  let frame = document.getElementById('toolTabFrame-' + id);
  if(frame) return frame;

  const info = files[id];
  frame = document.createElement('div');
  frame.className = 'tool-tab-frame';
  frame.id = 'toolTabFrame-' + id;
  frame.innerHTML =
    '<div class="tool-tab-mask"><span class="tool-tab-mask-label">loading<span class="tool-tab-mask-dots"></span></span></div>' +
    `<iframe name="tool-${escapeHtml(id)}" title="${escapeHtml(info.label)}"></iframe>`;
  document.body.appendChild(frame);
  playToolCompileAnimation(frame, id);
  wireToolTabMask(frame);
  const iframe = frame.querySelector('iframe');
  wireToolFrameSelfHeal(iframe, id, info.toolPath);
  loadFrameWithoutHistory(iframe, info.toolPath);
  return frame;
}

export function removeToolTabFrame(id){
  const frame = document.getElementById('toolTabFrame-' + id);
  if(frame) frame.remove();
  if(files[id] && files[id].isToolTab) delete files[id];
}

/**
 * Shows the frame for `activeToolId` and hides the rest. While any tool is
 * open the page behind it is scroll-locked, so the tool owns the scrollbar.
 */
export function updateToolTabFrames(activeToolId){
  document.querySelectorAll('.tool-tab-frame').forEach(f => f.classList.remove('active'));

  if(activeToolId){
    const frame = ensureToolTabFrame(activeToolId);
    frame.classList.add('active');
    positionToolTabFrame(frame);
    if(!toolTabResizeObserver && window.ResizeObserver){
      toolTabResizeObserver = new ResizeObserver(repositionActiveToolTabFrame);
      toolTabResizeObserver.observe(document.getElementById('editorArea'));
    }
    if(!toolTabLocked){
      toolTabScrollLockY = window.scrollY;
      document.body.style.top = `-${toolTabScrollLockY}px`;
      document.body.classList.add('tool-tab-lock');
      toolTabLocked = true;
    }
  } else if(toolTabLocked){
    document.body.classList.remove('tool-tab-lock');
    document.body.style.top = '';
    window.scrollTo(0, toolTabScrollLockY);
    toolTabLocked = false;
  }
}

export function initToolTabs(){
  window.addEventListener('resize', repositionActiveToolTabFrame);
}
