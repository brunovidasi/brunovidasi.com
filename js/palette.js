// ==========================================================================
// Quick Open (Ctrl/Cmd+P) and the Command Palette (Ctrl/Cmd+Shift+P).
//
// The two are the same widget with different contents, so both are built from
// one picker: a filtered list over a modal input, driven by arrow keys and
// Enter, with the mouse tracking the same selection.
// ==========================================================================

import { state } from './state.js';
import { escapeHtml, fuzzyMatch, highlightIndices } from './utils.js';
import { QUICK_OPEN_INDEX, navigateToHit, openWorkspaceSearch, focusExplorer } from './search.js';
import { closeTab, closeOtherTabs, closeAllTabs } from './tabs.js';
import { toggleExplorer } from './shell.js';
import { toggleBottomPanel } from './bottom-panel.js';
import { openShortcutsModal } from './help-modal.js';
import { SOCIAL_LINKS } from './config.js';

/** Quick Open caps its list; the command list is short enough to show whole. */
const MAX_QUICK_OPEN_ROWS = 50;

/**
 * Builds a searchable modal list.
 *
 * @param getMatches  (query) -> [{ item, indices }], already sorted
 * @param renderRow   (match) -> the row's inner HTML
 * @param onPick      (item) -> void, called after the modal closes
 */
function createPicker({ backdropId, inputId, resultsId, getMatches, renderRow, onPick, emptyMessage }){
  const backdrop = document.getElementById(backdropId);
  const input = document.getElementById(inputId);
  const resultsEl = document.getElementById(resultsId);
  let rows = [];
  let selected = 0;

  function isOpen(){
    return backdrop.classList.contains('show');
  }

  function open(){
    backdrop.classList.add('show');
    input.value = '';
    render('');
    input.focus();
  }

  function close(){
    backdrop.classList.remove('show');
  }

  function render(query){
    const matches = getMatches(query.trim());
    rows = matches;
    selected = 0;
    if(!matches.length){
      resultsEl.innerHTML = `<div class="quick-open-empty">${emptyMessage}</div>`;
      return;
    }
    resultsEl.innerHTML = matches
      .map((m, i) => `<div class="quick-open-row${i === 0 ? ' selected' : ''}" data-index="${i}">${renderRow(m)}</div>`)
      .join('');
  }

  function select(newIndex){
    const rowEls = resultsEl.querySelectorAll('.quick-open-row');
    if(!rowEls.length) return;
    selected = Math.max(0, Math.min(newIndex, rowEls.length - 1));
    rowEls.forEach((r, i) => r.classList.toggle('selected', i === selected));
    rowEls[selected].scrollIntoView({ block: 'nearest' });
  }

  function activate(index){
    const row = rows[index];
    if(!row) return;
    close();
    onPick(row.item);
  }

  input.addEventListener('input', ()=> render(input.value));
  input.addEventListener('keydown', (e)=>{
    if(e.key === 'ArrowDown'){ e.preventDefault(); select(selected + 1); }
    else if(e.key === 'ArrowUp'){ e.preventDefault(); select(selected - 1); }
    else if(e.key === 'Enter'){ e.preventDefault(); activate(selected); }
    else if(e.key === 'Escape'){ e.preventDefault(); close(); }
  });
  resultsEl.addEventListener('mousemove', (e)=>{
    const row = e.target.closest('.quick-open-row');
    if(row) select(Number(row.dataset.index));
  });
  resultsEl.addEventListener('click', (e)=>{
    const row = e.target.closest('.quick-open-row');
    if(row) activate(Number(row.dataset.index));
  });
  backdrop.addEventListener('mousedown', (e)=>{
    if(e.target === backdrop) close();
  });

  return { open, close, isOpen };
}

/** Ranks `items` against `query` on `field`, best first. An empty query keeps source order. */
function rankByFuzzy(items, query, field, limit){
  if(!query){
    const all = items.map(item => ({ item, indices: [] }));
    return limit ? all.slice(0, limit) : all;
  }
  const matches = items
    .map(item => {
      const m = fuzzyMatch(query, item[field]);
      if(m) return { item, indices: m.indices, score: m.score };
      // Aliases ("resume" for documents.pdf) match as substrings so they don't add fuzzy noise.
      const q = query.toLowerCase();
      const alias = (item.aliases || []).find(a => a.toLowerCase().includes(q));
      if(!alias) return null;
      const start = alias.toLowerCase().indexOf(q);
      const aliasIndices = Array.from({ length: q.length }, (_, i) => start + i);
      return { item, indices: [], score: fuzzyMatch(query, alias).score, alias, aliasIndices };
    })
    .filter(Boolean)
    .sort((a, b) => b.score - a.score);
  return limit ? matches.slice(0, limit) : matches;
}

// ---- Quick Open -----------------------------------------------------------

let quickOpen = null;

export function openQuickOpen(){ if(quickOpen) quickOpen.open(); }
export function closeQuickOpen(){ if(quickOpen) quickOpen.close(); }
export function isQuickOpenOpen(){ return !!quickOpen && quickOpen.isOpen(); }

// ---- Command Palette ------------------------------------------------------

let commandPalette = null;

export function openCommandPalette(){ if(commandPalette) commandPalette.open(); }
export function closeCommandPalette(){ if(commandPalette) commandPalette.close(); }
export function isCommandPaletteOpen(){ return !!commandPalette && commandPalette.isOpen(); }

/** Every action the site exposes, searchable by name. `meta` is the shortcut hint. */
const COMMAND_LIST = [
  { label: 'Go to File...', meta: '⌘P', run: openQuickOpen },
  { label: 'Find in Workspace', meta: '⇧⌘F', run: openWorkspaceSearch },
  { label: 'View: Show Explorer', meta: '⇧⌘E', run: focusExplorer },
  { label: 'View: Toggle Sidebar', meta: '⌘B', run: toggleExplorer },
  { label: 'View: Toggle Terminal', meta: '^`', run: ()=> toggleBottomPanel('terminal') },
  { label: 'View: Toggle Problems', meta: '⇧⌘M', run: ()=> toggleBottomPanel('problems') },
  { label: 'Tab: Close Editor', meta: '⌘W', run: ()=> { if(state.activeId) closeTab(state.activeId); } },
  { label: 'Tab: Close Others', run: ()=> closeOtherTabs() },
  { label: 'Tab: Close All Editors', run: closeAllTabs },
  { label: 'Help: How to Use This Site', meta: '?', run: ()=> openShortcutsModal('guide') },
  { label: 'Help: Keyboard Shortcuts', run: ()=> openShortcutsModal('shortcuts') },
  { label: 'Bruno: Open GitHub', run: ()=> window.open(SOCIAL_LINKS.github, '_blank', 'noopener') },
  { label: 'Bruno: Open LinkedIn', run: ()=> window.open(SOCIAL_LINKS.linkedin, '_blank', 'noopener') },
  { label: 'Bruno: Open CodePen', run: ()=> window.open(SOCIAL_LINKS.codepen, '_blank', 'noopener') }
];

// ---- Wiring ---------------------------------------------------------------

export function initPalette(){
  quickOpen = createPicker({
    backdropId: 'quickOpenBackdrop',
    inputId: 'quickOpenInput',
    resultsId: 'quickOpenResults',
    emptyMessage: 'No matching files found',
    getMatches: (query)=> rankByFuzzy(QUICK_OPEN_INDEX, query, 'title', MAX_QUICK_OPEN_ROWS),
    renderRow: (m)=> `
      ${m.item.iconHtml}
      <span class="quick-open-row-title">${highlightIndices(m.item.title, m.indices)}</span>
      <span class="quick-open-row-meta">${m.alias ? highlightIndices(m.alias, m.aliasIndices) : escapeHtml(m.item.meta)}</span>`,
    onPick: navigateToHit
  });

  commandPalette = createPicker({
    backdropId: 'cmdPaletteBackdrop',
    inputId: 'cmdPaletteInput',
    resultsId: 'cmdPaletteResults',
    emptyMessage: 'No matching commands',
    getMatches: (query)=> rankByFuzzy(COMMAND_LIST, query, 'label'),
    renderRow: (m)=> `
      <span class="quick-open-row-title">${highlightIndices(m.item.label, m.indices)}</span>
      ${m.item.meta ? `<span class="quick-open-row-meta">${escapeHtml(m.item.meta)}</span>` : ''}`,
    onPick: (item)=> item.run()
  });

  document.getElementById('quickOpenBtn').addEventListener('click', openQuickOpen);
}
