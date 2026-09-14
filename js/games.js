// ==========================================================================
// The mini-games panel: sizing, reloading and fullscreening the game frames.
// ==========================================================================

import { registerActions } from './actions.js';

/**
 * The games are old fixed-size pages with no viewport meta, so the iframe
 * cannot size itself. Measure the furthest bottom edge inside the document
 * instead — `scrollHeight` over-reports on several of them.
 */
function measureGameHeight(iframe){
  try {
    const doc = iframe.contentDocument;
    if(!doc || !doc.body) return 0;
    const bottoms = Array.from(doc.body.children)
      .map(el => el.getBoundingClientRect().bottom)
      .filter(v => v > 0);
    return bottoms.length ? Math.max(...bottoms) : doc.body.scrollHeight;
  } catch(e){
    // Cross-origin or not yet loaded — fall back to the shared default.
    return 0;
  }
}

/** Sizes every game frame to the tallest one, so the grid rows line up. */
export function fitGameFrame(){
  const wraps = Array.from(document.querySelectorAll('.game-frame-wrap'));
  const heights = wraps.map(wrap => measureGameHeight(wrap.querySelector('iframe')));
  const naturalHeight = Math.max(0, ...heights);
  if(!naturalHeight) return;
  const sharedHeight = Math.min(Math.max(Math.ceil(naturalHeight), 320), 640) + 'px';
  wraps.forEach(wrap => { wrap.style.height = sharedHeight; });
}

/** Re-measures once each freshly rendered game frame has loaded. */
export function watchGameFrames(){
  document.querySelectorAll('.game-frame-wrap iframe').forEach(iframe => {
    if(iframe.dataset.fitWired) return;
    iframe.dataset.fitWired = '1';
    iframe.addEventListener('load', fitGameFrame);
  });
}

function openGameFullscreen(id){
  const wrap = document.getElementById('gameFrame-' + id);
  if(!wrap) return;
  if(wrap.requestFullscreen) wrap.requestFullscreen();
  else if(wrap.webkitRequestFullscreen) wrap.webkitRequestFullscreen();
}

function reloadGameFrame(id){
  const wrap = document.getElementById('gameFrame-' + id);
  const iframe = wrap && wrap.querySelector('iframe');
  if(!iframe) return;
  iframe.src = iframe.src;
}

export function initGames(){
  registerActions({
    'game-fullscreen': (el, [id]) => openGameFullscreen(id),
    'game-reload':     (el, [id]) => reloadGameFrame(id)
  });
}
