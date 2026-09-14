// ==========================================================================
// Entry point.
//
// Every other module exports functions and an `init`, and does no DOM work
// until called from here. That keeps the start-up order explicit in one place
// and lets the modules import each other freely — including in cycles, which
// the workspace modules genuinely need — without import order mattering.
// (The one deliberate exception is boot.js, which starts the taglines fetch
// as its module body runs so the request is in flight as early as possible.)
// ==========================================================================

import { yearsExperience } from './config.js';
import { initActions, registerActions } from './actions.js';
import { initRoute, initRouter, wantsFullscreenView } from './router.js';
import { initShell, enterFullscreenView } from './shell.js';
import { initExplorer, renderExplorer } from './explorer.js';
import { initTabs, renderTabs, openFile } from './tabs.js';
import { initToolTabs } from './tool-tabs.js';
import { initPanels, showActivePanel } from './panels.js';
import { initGames } from './games.js';
import { initWebsites } from './websites.js';
import { initProjects } from './projects.js';
import { initContact } from './contact.js';
import { initBottomPanel } from './bottom-panel.js';
import { initTerminal } from './terminal.js';
import { initSearch } from './search.js';
import { initPalette } from './palette.js';
import { initHelpModal } from './help-modal.js';
import { initKeyboard } from './keyboard.js';
import { startBoot, initDevToggle } from './boot.js';

/** Fills the figures the copy quotes, so neither goes stale. */
function fillDynamicFigures(){
  const year = new Date().getFullYear();
  document.querySelectorAll('.years-exp').forEach(el => { el.textContent = yearsExperience; });
  document.querySelectorAll('.current-year').forEach(el => { el.textContent = year; });
}

function main(){
  fillDynamicFigures();

  // Resolve the entry URL before anything renders, so the first paint is
  // already the page that was asked for.
  initRoute();

  // Delegated handlers and the actions the markup refers to by name.
  initActions();
  registerActions({ 'open-file': (el, [id]) => openFile(id) });

  initShell();
  initExplorer();
  initTabs();
  initToolTabs();
  initPanels();
  initGames();
  initWebsites();
  initContact();
  initBottomPanel();
  initTerminal();
  initSearch();
  initPalette();
  initHelpModal();
  initKeyboard();
  initRouter();
  initDevToggle();

  // First paint.
  renderExplorer();
  renderTabs();
  showActivePanel();
  if(wantsFullscreenView()) enterFullscreenView();

  // Kick the project JSON off before the boot animation, not after, so the
  // network request overlaps it instead of queueing behind it.
  initProjects();
  startBoot();
}

main();
