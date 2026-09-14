// ==========================================================================
// The "How to Use This Site" modal — a Guide tab explaining the interface and
// a Shortcuts tab listing the keyboard commands.
//
// Modifier symbols are picked per platform so a Mac visitor never reads
// "Ctrl+P" for a shortcut that is actually ⌘P.
// ==========================================================================

import { escapeHtml } from './utils.js';

export const IS_MAC = /Mac|iPhone|iPad|iPod/.test(navigator.platform || navigator.userAgent);
export const MOD_KEY = IS_MAC ? '⌘' : 'Ctrl';

// ---- Guide tab ------------------------------------------------------------

const GUIDE_GROUPS = [
  {
    title: 'Title Bar',
    items: [
      { dot: 'r', html: '<b>Red dot</b> — reloads the site from the start (boot sequence included).' },
      { dot: 'y', html: '<b>Yellow dot</b> — minimizes the window; click the dock icon at the bottom of the screen to bring it back.' },
      { dot: 'g', html: '<b>Green dot</b> — toggles the sidebar.' },
      { html: 'The <b>bruno-vieira</b> logo jumps back to intro.js.' }
    ]
  },
  {
    title: 'Explorer (sidebar)',
    items: [
      { html: '<b>EXPLORER</b> / <b>SEARCH</b> tabs switch between the file tree and site-wide search.' },
      { html: 'Click a <b>file</b> to open it in a tab; click a <b>folder</b> to expand or collapse it.' },
      { html: 'The <b>···</b> button opens a menu to expand/collapse all folders, close tabs, or hide the explorer.' },
      { html: 'Drag the sidebar\'s right edge to <b>resize</b> it.' }
    ]
  },
  {
    title: 'Tabs',
    items: [
      { html: 'Click a tab to switch to it; click its <b>✕</b> to close it.' },
      { html: '<b>Drag</b> a tab left or right to reorder it.' },
      { html: '<b>Right-click</b> a tab for more: close others, close to the right, close all, fullscreen, or open in a new window.' }
    ]
  },
  {
    title: 'Terminal & Problems',
    items: [
      { html: 'Click <b>Terminal</b> in the status bar (or see Shortcuts) to open the small working shell at the bottom of the editor.' },
      { html: "Once it's open, try typing <b>help</b> — real commands like <b>ls</b> and <b>cd &lt;page&gt;</b> actually navigate the site." }
    ]
  },
  {
    title: 'Status Bar & Power Tools',
    items: [
      { html: '<b>Guide</b> reopens this manual any time.' },
      { html: 'The Command Palette (see Shortcuts) lists every action the site supports, searchable by name.' }
    ]
  }
];

function renderGuide(){
  document.getElementById('shortcutsGuide').innerHTML = GUIDE_GROUPS.map(group => `
    <div class="shortcuts-group-title">${escapeHtml(group.title)}</div>
    ${group.items.map(item => `
      <div class="guide-row">
        <span class="guide-icon${item.dot ? ' ' + item.dot : ''}"></span>
        <span>${item.html}</span>
      </div>`).join('')}
  `).join('');
}

// ---- Shortcuts tab --------------------------------------------------------

const SHORTCUT_GROUPS = [
  {
    title: 'Go To',
    items: [
      { keys: [MOD_KEY, 'P'], desc: 'Go to File...' },
      { keys: [MOD_KEY, 'Shift', 'P'], desc: 'Command Palette' },
      { keys: [MOD_KEY, 'Shift', 'F'], desc: 'Find in Workspace' },
      { keys: ['?'], desc: 'Show this help' }
    ]
  },
  {
    title: 'View',
    items: [
      { keys: [MOD_KEY, 'B'], desc: 'Toggle Sidebar' },
      { keys: [MOD_KEY, 'Shift', 'E'], desc: 'Show Explorer' },
      // Ctrl on every platform, Cmd included — matching VS Code itself.
      { keys: ['Ctrl', '`'], desc: 'Toggle Terminal' },
      { keys: [MOD_KEY, 'Shift', 'M'], desc: 'Toggle Problems' }
    ]
  },
  {
    title: 'Tabs',
    items: [
      { keys: [MOD_KEY, 'Tab'], desc: 'Next Tab' },
      { keys: [MOD_KEY, 'Shift', 'Tab'], desc: 'Previous Tab' },
      { keys: [MOD_KEY, '1'], through: '9', desc: 'Jump to Tab 1–9' },
      { keys: [MOD_KEY, 'W'], desc: 'Close Tab' }
    ]
  },
  {
    title: 'Editor',
    items: [
      { keys: [MOD_KEY, '/'], desc: 'Toggle Comment (intro.js)' },
      { keys: ['Esc'], desc: 'Close Dialog / Menu' }
    ]
  }
];

const SHORTCUTS_FOOTNOTE = `Right-click a tab for more actions (close others, close to the right, fullscreen). Some browsers reserve ${MOD_KEY}+Tab, ${MOD_KEY}+W and ${MOD_KEY}+1–9 for their own tab switching, so those may not always reach the page.`;

function keycapRowHtml(item){
  const keys = item.keys.map(k => `<kbd>${escapeHtml(k)}</kbd>`).join('<span class="keycap-plus">+</span>');
  const range = item.through ? `<span class="keycap-plus">–</span><kbd>${escapeHtml(item.through)}</kbd>` : '';
  return keys + range;
}

function renderShortcuts(){
  const groupsHtml = SHORTCUT_GROUPS.map(group => `
    <div class="shortcuts-group-title">${escapeHtml(group.title)}</div>
    ${group.items.map(item => `
      <div class="shortcut-row">
        <span class="shortcut-desc">${escapeHtml(item.desc)}</span>
        <span class="keycap-row">${keycapRowHtml(item)}</span>
      </div>`).join('')}
  `).join('');
  document.getElementById('shortcutsBody').innerHTML =
    groupsHtml + `<div class="shortcuts-footnote">${escapeHtml(SHORTCUTS_FOOTNOTE)}</div>`;
}

// ---- Open / close ---------------------------------------------------------

function switchShortcutsTab(tab){
  document.querySelectorAll('.shortcuts-tabs .modal-tab').forEach(btn => {
    btn.classList.toggle('active', btn.dataset.shortcutsTab === tab);
  });
  document.getElementById('shortcutsGuide').hidden = tab !== 'guide';
  document.getElementById('shortcutsBody').hidden = tab !== 'shortcuts';
}

export function isShortcutsModalOpen(){
  return document.getElementById('shortcutsBackdrop').classList.contains('show');
}

export function openShortcutsModal(tab){
  switchShortcutsTab(tab || 'guide');
  document.getElementById('shortcutsBackdrop').classList.add('show');
}

export function closeShortcutsModal(){
  document.getElementById('shortcutsBackdrop').classList.remove('show');
}

// ---- Wiring ---------------------------------------------------------------

export function initHelpModal(){
  renderGuide();
  renderShortcuts();

  document.querySelectorAll('.shortcuts-tabs .modal-tab').forEach(btn => {
    btn.addEventListener('click', ()=> switchShortcutsTab(btn.dataset.shortcutsTab));
  });
  document.getElementById('helpBtn').addEventListener('click', ()=> openShortcutsModal());
  document.getElementById('shortcutsCloseBtn').addEventListener('click', closeShortcutsModal);

  const backdrop = document.getElementById('shortcutsBackdrop');
  backdrop.addEventListener('mousedown', (e)=>{
    if(e.target === backdrop) closeShortcutsModal();
  });
}
