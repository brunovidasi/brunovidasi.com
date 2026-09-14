// ==========================================================================
// The window chrome: traffic-light buttons, minimize/restore, the sidebar's
// open/hidden/mobile-drawer states, and the drag-to-resize edge.
// ==========================================================================

import { isMobileViewport } from './utils.js';
import { humanTypeSect } from './typing.js';
import { openFile } from './tabs.js';

const shellEl = () => document.getElementById('shell');
const appEl = () => document.getElementById('app');

// ---- Mobile nav scroll lock ----------------------------------------------

let lockedScrollY = 0;

/**
 * Pins the page behind the mobile drawer. Fixing `body` alone would scroll it
 * to the top, so the current offset is held in `top` and restored on release.
 */
export function setMobileNavLock(locked){
  const body = document.body;
  if(locked){
    lockedScrollY = window.scrollY;
    body.style.top = `-${lockedScrollY}px`;
    body.classList.add('mobile-nav-locked');
  } else {
    body.classList.remove('mobile-nav-locked');
    body.style.top = '';
    window.scrollTo(0, lockedScrollY);
  }
}

export function closeMobileNav(){
  shellEl().classList.remove('mobile-nav-open');
  setMobileNavLock(false);
}

// ---- Sidebar --------------------------------------------------------------

export function isSidebarHidden(){
  const shell = shellEl();
  return isMobileViewport()
    ? !shell.classList.contains('mobile-nav-open')
    : shell.classList.contains('sidebar-hidden');
}

export function toggleExplorer(){
  const shell = shellEl();
  if(isMobileViewport()){
    shell.classList.toggle('mobile-nav-open');
    setMobileNavLock(shell.classList.contains('mobile-nav-open'));
  } else {
    shell.classList.toggle('sidebar-hidden');
  }
}

/** Makes the sidebar visible whichever form it currently takes. */
export function revealSidebar(){
  const shell = shellEl();
  if(isMobileViewport()){
    if(!shell.classList.contains('mobile-nav-open')){
      shell.classList.add('mobile-nav-open');
      setMobileNavLock(true);
    }
  } else {
    shell.classList.remove('sidebar-hidden');
  }
}

export function showSidebar(){
  shellEl().classList.remove('sidebar-hidden');
}

/** Pulses the green dot so a `?fs=1` visitor can find the way back. */
function hintGreenDot(){
  const dotGreen = document.getElementById('dotGreen');
  dotGreen.classList.add('pulse-hint');
  setTimeout(()=> dotGreen.classList.remove('pulse-hint'), 8000);
}

export function enterFullscreenView(){
  shellEl().classList.add('sidebar-hidden');
  hintGreenDot();
}

// ---- Minimize / restore ---------------------------------------------------

function minimizeApp(){
  appEl().classList.add('minimized');
  document.getElementById('bgRain').classList.add('show');
  document.querySelector('.bg-overlay').classList.add('show');
  document.getElementById('dockRestore').style.display = 'flex';
  humanTypeSect(document.getElementById('bgOverlayText'), 0.55, ()=>{
    document.getElementById('bgOverlaySign').classList.add('show');
  });
}

export function restoreApp(){
  const app = appEl();
  if(!app.classList.contains('minimized')) return;
  app.classList.remove('minimized');
  document.getElementById('bgRain').classList.remove('show');
  document.querySelector('.bg-overlay').classList.remove('show');
  document.getElementById('dockRestore').style.display = 'none';
}

// ---- Background "digital rain" (behind the minimized window) --------------

function initBackgroundRain(){
  const canvas = document.getElementById('bgRain');
  const ctx = canvas.getContext('2d');
  const CHARS = '01';
  const FONT_SIZE = 15;
  let drops = [];

  function resize(){
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
  }

  function seedDrops(){
    const cols = Math.floor(canvas.width / FONT_SIZE);
    // Negative starting offsets stagger the columns so they don't fall in step.
    drops = new Array(cols).fill(0).map(()=> Math.random() * -50);
  }

  function draw(){
    ctx.fillStyle = 'rgba(11,14,12,0.14)';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    ctx.fillStyle = '#8fd19e';
    ctx.font = FONT_SIZE + 'px monospace';
    for(let i = 0; i < drops.length; i++){
      const ch = CHARS[Math.floor(Math.random() * CHARS.length)];
      ctx.fillText(ch, i * FONT_SIZE, drops[i] * FONT_SIZE);
      drops[i] = (drops[i] * FONT_SIZE > canvas.height && Math.random() > 0.975) ? 0 : drops[i] + 1;
    }
  }

  resize();
  seedDrops();
  window.addEventListener('resize', resize);
  window.addEventListener('resize', seedDrops);
  setInterval(draw, 55);
}

// ---- Sidebar resize -------------------------------------------------------

function initExplorerResize(){
  const explorer = document.querySelector('.explorer');
  const handle = document.getElementById('explorerResizeHandle');
  const overlay = document.getElementById('explorerResizeOverlay');
  if(!explorer || !handle || !overlay) return;

  const MIN_WIDTH = 170;
  const MAX_WIDTH = 480;
  const DEFAULT_WIDTH = 230;

  function applyWidth(width){
    explorer.style.setProperty('--explorer-width', width + 'px');
  }

  let dragging = false;
  let startX = 0;
  let startWidth = 0;
  let pendingWidth = null;
  let rafId = null;

  function flushWidth(){
    rafId = null;
    if(pendingWidth !== null) applyWidth(pendingWidth);
  }

  handle.addEventListener('mousedown', (e)=>{
    if(isMobileViewport()) return;
    dragging = true;
    startX = e.clientX;
    startWidth = explorer.getBoundingClientRect().width;
    explorer.classList.add('no-resize-transition');
    handle.classList.add('dragging');
    overlay.classList.add('active');
    document.body.style.cursor = 'col-resize';
    document.body.style.userSelect = 'none';
    e.preventDefault();
  });

  // The overlay (rather than the iframes/panels underneath) receives the
  // drag's mousemove/mouseup — without it, crossing into an open iframe
  // (mini-tools, résumé, games) stops delivering mouse events to the page
  // and the drag stalls until the cursor returns over non-iframe content.
  overlay.addEventListener('mousemove', (e)=>{
    if(!dragging) return;
    pendingWidth = Math.min(MAX_WIDTH, Math.max(MIN_WIDTH, startWidth + (e.clientX - startX)));
    if(rafId === null) rafId = requestAnimationFrame(flushWidth);
  });

  overlay.addEventListener('mouseup', ()=>{
    if(!dragging) return;
    dragging = false;
    if(rafId !== null){ cancelAnimationFrame(rafId); rafId = null; }
    if(pendingWidth !== null){ applyWidth(pendingWidth); pendingWidth = null; }
    explorer.classList.remove('no-resize-transition');
    handle.classList.remove('dragging');
    overlay.classList.remove('active');
    document.body.style.cursor = '';
    document.body.style.userSelect = '';
  });

  handle.addEventListener('dblclick', ()=> applyWidth(DEFAULT_WIDTH));
}

// ---- Wiring ---------------------------------------------------------------

export function initShell(){
  document.getElementById('dotRed').addEventListener('click', ()=>{
    location.href = '/' + location.search;
  });
  document.getElementById('dotYellow').addEventListener('click', minimizeApp);
  document.getElementById('dotGreen').addEventListener('click', (e)=>{
    e.currentTarget.classList.remove('pulse-hint');
    toggleExplorer();
  });
  document.getElementById('dockRestore').addEventListener('click', restoreApp);

  document.getElementById('titlebarLogoBtn').addEventListener('click', ()=>{
    openFile('intro');
    showSidebar();
  });
  document.getElementById('footerBrunoLink').addEventListener('click', showSidebar);

  document.getElementById('mobileMenuBtn').addEventListener('click', toggleExplorer);
  document.getElementById('explorerClose').addEventListener('click', closeMobileNav);

  initBackgroundRain();
  initExplorerResize();
}
