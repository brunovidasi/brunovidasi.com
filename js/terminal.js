// ==========================================================================
// The built-in shell.
//
// Commands resolve against the same `files` / `folders` / TOOL_TAB_REGISTRY
// data the explorer and Quick Open read, so `ls` and `cd` stay correct as
// pages and tools are added or renamed — there is no route table to keep in
// step. `cd <page>` really does navigate the site.
// ==========================================================================

import {
  files, folders, rootOrder, FOLDER_DEFAULT_FILE, MINI_TOOL_CATEGORY_IDS,
  SOCIAL_LINKS, yearsExperience
} from './config.js';
import { TOOL_TAB_REGISTRY } from './state.js';
import { escapeHtml } from './utils.js';
import { openFile } from './tabs.js';
import { openToolTab } from './tool-tabs.js';
import { folderChildren } from './explorer.js';
import { openShortcutsModal } from './help-modal.js';
import { closeBottomPanel } from './bottom-panel.js';

const PROMPT_USER = 'visitor@bruno-dev';

// ---- Output ---------------------------------------------------------------

function printTerminalLine(html){
  const log = document.getElementById('terminalLog');
  const line = document.createElement('div');
  line.className = 't-line';
  line.innerHTML = html;
  log.appendChild(line);
  log.scrollTop = log.scrollHeight;
}

const out = (text)=> printTerminalLine(`<span class="t-out">${text}</span>`);
const err = (text)=> printTerminalLine(`<span class="t-err">${text}</span>`);

export function bootTerminal(){
  const log = document.getElementById('terminalLog');
  if(log.dataset.booted) return;
  log.dataset.booted = '1';
  out("Bruno's portfolio shell — type 'help' to see what's here.");
}

// ---- Name resolution ------------------------------------------------------

/** Strips a file extension and lowercases, so `cd about` matches `README.md`. */
function baseName(label){
  return label.replace(/\.[^.]+$/, '').toLowerCase();
}

function folderName(id){
  return folders[id].label.replace(/\/$/, '').toLowerCase();
}

/** Resolves a typed name to a real, currently-navigable id. */
function resolveNavTarget(name){
  if(!name) return null;
  if(TOOL_TAB_REGISTRY[name]) return name;
  if(files[name]) return name;
  if(folders[name] && FOLDER_DEFAULT_FILE[name]) return FOLDER_DEFAULT_FILE[name];

  const lower = name.toLowerCase();
  const byFile = Object.keys(files).find(id => !files[id].isToolTab && baseName(files[id].label) === lower);
  if(byFile) return byFile;

  const byFolder = Object.keys(folders).find(id => folderName(id) === lower);
  if(byFolder && FOLDER_DEFAULT_FILE[byFolder]) return FOLDER_DEFAULT_FILE[byFolder];

  const byTool = Object.keys(TOOL_TAB_REGISTRY).find(id => (TOOL_TAB_REGISTRY[id].title || '').toLowerCase() === lower);
  return byTool || null;
}

/** Opens a resolved id and returns the label to echo back. */
function openNavTarget(id){
  if(TOOL_TAB_REGISTRY[id]){
    const title = TOOL_TAB_REGISTRY[id].title;
    openToolTab(id);
    return files[id] ? files[id].label : title;
  }
  openFile(id);
  return files[id].label;
}

function rootLabel(id){
  return folders[id] ? folders[id].label : (files[id] ? files[id].label : id);
}

function findInChildren(children, name){
  const lower = name.toLowerCase();
  return children.find(id => {
    if(id === name) return true;
    if(folders[id]) return folderName(id) === lower;
    if(files[id]) return files[id].label.toLowerCase() === lower || baseName(files[id].label) === lower;
    if(TOOL_TAB_REGISTRY[id]) return (TOOL_TAB_REGISTRY[id].title || '').toLowerCase() === lower;
    return false;
  });
}

/** Prefers a folder in the current directory, then falls back to any folder. */
function resolveFolderId(name, scopeChildren){
  const lower = name.toLowerCase();
  const matches = (id)=> folders[id] && (id === name || folderName(id) === lower);
  return scopeChildren.find(matches) || Object.keys(folders).find(matches) || null;
}

// ---- Working directory ----------------------------------------------------

// Tracks which folder the terminal is "inside" so ls/cd can resolve names
// relative to it (root = empty path). Only two levels deep exist (e.g.
// mini-tools -> mini-tools-dev), so a plain array of folder ids is enough.
let cwdPath = [];

function cwdFolderId(){
  return cwdPath.length ? cwdPath[cwdPath.length - 1] : null;
}

function dirChildren(){
  const cur = cwdFolderId();
  return cur ? folderChildren(cur) : rootOrder;
}

function promptPath(){
  if(!cwdPath.length) return '~';
  return '~/' + cwdPath.map(id => folders[id].label.replace(/\/$/, '')).join('/');
}

function updateTerminalPromptEl(){
  const el = document.querySelector('.terminal-prompt');
  if(el) el.textContent = `${PROMPT_USER}:${promptPath()}$`;
}

function folderPathTo(folderId){
  return MINI_TOOL_CATEGORY_IDS.has(folderId) ? ['mini-tools', folderId] : [folderId];
}

function folderDefaultFile(folderId){
  return FOLDER_DEFAULT_FILE[folderId] || (files[folderId] ? folderId : null);
}

function dirLabels(children){
  return children.map(id => files[id]
    ? files[id].label
    : (TOOL_TAB_REGISTRY[id] ? TOOL_TAB_REGISTRY[id].title + '.js' : rootLabel(id)));
}

// ---- Commands -------------------------------------------------------------

const HELP_TEXT = 'Commands: help, ls [dir], cd/cat/open &lt;page&gt;, cd .., shortcuts, whoami, about, resume, contact, github, linkedin, codepen, echo &lt;text&gt;, date, clear, exit';

function cmdLs(arg){
  if(!arg){
    const labels = cwdFolderId() ? dirLabels(dirChildren()) : rootOrder.map(rootLabel);
    out(escapeHtml(labels.join('  ')));
    return;
  }
  const folderId = resolveFolderId(arg, dirChildren());
  if(!folderId){
    err(`ls: ${escapeHtml(arg)}: No such directory`);
    return;
  }
  out(escapeHtml(dirLabels(folderChildren(folderId)).join('  ')));
}

function cmdCd(arg){
  if(!arg || arg === '~' || arg === '/'){
    cwdPath = [];
    out(`→ ${escapeHtml(files.intro.label)}`);
    openFile('intro');
    return;
  }
  if(arg === '..'){
    if(!cwdPath.length){
      err('cd: already at top-level directory');
      return;
    }
    cwdPath.pop();
    out(`→ ${escapeHtml(promptPath())}`);
    return;
  }

  const scopeChildren = dirChildren();
  const folderId = resolveFolderId(arg, scopeChildren);
  if(folderId){
    cwdPath = folderPathTo(folderId);
    const defaultFile = folderDefaultFile(folderId);
    out(`→ ${escapeHtml(defaultFile ? openNavTarget(defaultFile) : promptPath())}`);
    return;
  }

  const target = findInChildren(scopeChildren, arg) || resolveNavTarget(arg);
  if(target && (files[target] || TOOL_TAB_REGISTRY[target])) out(`→ ${escapeHtml(openNavTarget(target))}`);
  else err(`cd: ${escapeHtml(arg)}: No such file or directory`);
}

function cmdOpen(arg, commandName){
  if(!arg){
    out(`→ ${escapeHtml(files.intro.label)}`);
    openFile('intro');
    return;
  }
  const target = findInChildren(dirChildren(), arg) || resolveNavTarget(arg);
  if(target && (files[target] || TOOL_TAB_REGISTRY[target])) out(`→ ${escapeHtml(openNavTarget(target))}`);
  else err(`${commandName}: ${escapeHtml(arg)}: No such file or directory`);
}

function cmdOpenPage(id, label){
  out(`opening ${label}...`);
  openFile(id);
}

function cmdSocial(name){
  out(SOCIAL_LINKS[name]);
  window.open(SOCIAL_LINKS[name], '_blank', 'noopener');
}

export function runTerminalCommand(raw){
  const input = raw.trim();
  printTerminalLine(`<span class="t-prompt">${PROMPT_USER}:${escapeHtml(promptPath())}$</span> <span class="t-out">${escapeHtml(input)}</span>`);
  if(!input) return;

  const [cmd, ...rest] = input.split(/\s+/);
  const arg = rest.join(' ');
  const name = cmd.toLowerCase();

  switch(name){
    case 'help':     out(HELP_TEXT); break;
    case 'ls':       cmdLs(arg); break;
    case 'cd':       cmdCd(arg); break;
    case 'cat':
    case 'open':     cmdOpen(arg, name); break;
    case 'whoami':   out('you: a curious visitor who found the hidden terminal. nice work 👀'); break;
    case 'about':    out(`Sydney-based developer, ${yearsExperience}+ years experience. Run 'cat about' for the full page.`); break;
    case 'resume':
    case 'cv':       cmdOpenPage('documents', 'documents.pdf'); break;
    case 'contact':  cmdOpenPage('contact', 'contact.eml'); break;
    case 'shortcuts':
      out('opening keyboard shortcuts...');
      openShortcutsModal('shortcuts');
      break;
    case 'github':
    case 'linkedin':
    case 'codepen':  cmdSocial(name); break;
    case 'echo':     out(escapeHtml(arg)); break;
    case 'date':     out(escapeHtml(new Date().toString())); break;
    case 'sudo':     err('Permission denied: nice try 😏'); break;
    case 'coffee':   out('☕ brewing... still faster than IE6.'); break;
    case 'clear':    document.getElementById('terminalLog').innerHTML = ''; break;
    case 'exit':
    case 'close':    closeBottomPanel(); break;
    default:         err(`command not found: ${escapeHtml(cmd)}`);
  }

  updateTerminalPromptEl();
}

export function initTerminal(){
  const input = document.getElementById('terminalInput');
  input.addEventListener('keydown', (e)=>{
    if(e.key !== 'Enter') return;
    const value = input.value;
    input.value = '';
    runTerminalCommand(value);
  });
}
