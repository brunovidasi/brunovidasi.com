// ==========================================================================
// Global keyboard shortcuts, mirroring the editor this site is dressed as.
//
// The table is ordered and first-match-wins: the matched entry's `run` fires
// and the key is swallowed. A key no entry claims is left alone, so browser
// and page shortcuts that are not shadowed here still work.
// ==========================================================================

import { state } from './state.js';
import { isTypingTarget } from './utils.js';
import { closeTab, cycleTabs, jumpToTabIndex, closeTabContextMenu } from './tabs.js';
import { toggleExplorer } from './shell.js';
import { toggleBottomPanel } from './bottom-panel.js';
import { toggleIntroCodeComment } from './panels.js';
import { openWorkspaceSearch, focusExplorer } from './search.js';
import {
  openQuickOpen, closeQuickOpen, isQuickOpenOpen,
  openCommandPalette, closeCommandPalette, isCommandPaletteOpen
} from './palette.js';
import { openShortcutsModal, closeShortcutsModal, isShortcutsModalOpen } from './help-modal.js';

/** Cmd on Mac, Ctrl elsewhere — whichever the visitor's browser reports. */
const mod = (e)=> e.metaKey || e.ctrlKey;
const key = (e, k)=> e.key.toLowerCase() === k;

const SHORTCUTS = [
  { when: (e)=> e.key === '?' && !mod(e) && !isTypingTarget(e.target),
    run: ()=> openShortcutsModal() },

  // VS Code's integrated-terminal toggle is Ctrl+` on every platform, Cmd included.
  { when: (e)=> e.ctrlKey && !e.shiftKey && !e.altKey && e.key === '`',
    run: ()=> toggleBottomPanel('terminal') },

  { when: (e)=> mod(e) && !e.shiftKey && key(e, 'p'), run: openQuickOpen },
  { when: (e)=> mod(e) && e.shiftKey && key(e, 'p'),  run: openCommandPalette },
  { when: (e)=> mod(e) && e.shiftKey && key(e, 'f'),  run: openWorkspaceSearch },
  { when: (e)=> mod(e) && e.shiftKey && key(e, 'e'),  run: focusExplorer },
  { when: (e)=> mod(e) && e.shiftKey && key(e, 'm'),  run: ()=> toggleBottomPanel('problems') },
  { when: (e)=> mod(e) && !e.shiftKey && key(e, 'b'), run: toggleExplorer },

  { when: (e)=> mod(e) && !e.shiftKey && key(e, 'w'),
    run: ()=> { if(state.activeId) closeTab(state.activeId); } },
  { when: (e)=> mod(e) && e.key === 'Tab',
    run: (e)=> cycleTabs(e.shiftKey ? -1 : 1) },
  { when: (e)=> mod(e) && !e.shiftKey && /^[1-9]$/.test(e.key),
    run: (e)=> jumpToTabIndex(Number(e.key) - 1) },

  // Toggle Comment only means something on the one panel that shows code.
  { when: (e)=> mod(e) && !e.shiftKey && e.key === '/' && state.activeId === 'intro',
    run: toggleIntroCodeComment }
];

/** Escape closes whatever is on top, innermost first. */
function closeTopmostOverlay(){
  if(isQuickOpenOpen()) closeQuickOpen();
  else if(isCommandPaletteOpen()) closeCommandPalette();
  else if(isShortcutsModalOpen()) closeShortcutsModal();
  else closeTabContextMenu();
}

export function initKeyboard(){
  document.addEventListener('keydown', (e)=>{
    if(e.key === 'Escape'){
      closeTopmostOverlay();
      return;
    }
    const shortcut = SHORTCUTS.find(s => s.when(e));
    if(!shortcut) return;
    e.preventDefault();
    shortcut.run(e);
  });
}
