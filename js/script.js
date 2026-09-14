const CAREER_START_YEAR = 2012;
const yearsExperience = new Date().getFullYear() - CAREER_START_YEAR;
document.querySelectorAll('.years-exp').forEach(el=> el.textContent = yearsExperience);
document.querySelectorAll('.current-year').forEach(el=> el.textContent = new Date().getFullYear());

const ICON_GLYPHS = {
  js:   { char: '', color: '#cbcb41' },
  md:   { char: '', color: '#ffb454' },
  json: { char: '', color: '#cbcb41' },
  info: { char: '', color: '#519aba' },
  html: { char: '', color: '#ffb454' },
  php:  { char: '', color: '#a074c4' },
  sh:   { char: '', color: '#8dc149' },
  eml:  { char: '', color: '#6d8086' },
  pdf:  { char: '', color: '#cc3e44' },
  css:  { char: '', color: '#519aba' }
};
const FOLDER_COLOR = '#ffb454';
const FOLDER_CLOSED_SVG = '<svg viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M2 4.5V6H5.58579C5.71839 6 5.84557 5.94732 5.93934 5.85355L7.29289 4.5L5.93934 3.14645C5.84557 3.05268 5.71839 3 5.58579 3H3.5C2.67157 3 2 3.67157 2 4.5ZM1 4.5C1 3.11929 2.11929 2 3.5 2H5.58579C5.98361 2 6.36514 2.15804 6.64645 2.43934L8.20711 4H12.5C13.8807 4 15 5.11929 15 6.5V11.5C15 12.8807 13.8807 14 12.5 14H3.5C2.11929 14 1 12.8807 1 11.5V4.5ZM2 7V11.5C2 12.3284 2.67157 13 3.5 13H12.5C13.3284 13 14 12.3284 14 11.5V6.5C14 5.67157 13.3284 5 12.5 5H8.20711L6.64645 6.56066C6.36514 6.84197 5.98361 7 5.58579 7H2Z"/></svg>';
const FOLDER_OPEN_SVG = '<svg viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M2 4.5V9.10022L2.92389 7.5C3.45979 6.5718 4.45017 6 5.52196 6L11.9146 6C11.7087 5.4174 11.1531 5 10.5 5H7C6.86739 5 6.74021 4.94732 6.64645 4.85355L4.93934 3.14645C4.84557 3.05268 4.71839 3 4.58579 3H3.5C2.67157 3 2 3.67157 2 4.5ZM7.06895 13.9953C7.04641 13.9984 7.02339 14 7 14H3.5C2.11929 14 1 12.8807 1 11.5V4.5C1 3.11929 2.11929 2 3.5 2H4.58579C4.98361 2 5.36514 2.15804 5.64645 2.43934L7.20711 4H10.5C11.724 4 12.7426 4.87965 12.958 6.04127C14.605 6.34148 15.5443 8.22106 14.6616 9.75L13.0766 12.4953C12.5407 13.4235 11.5503 13.9953 10.4785 13.9953H7.06895ZM5.52196 7C4.80743 7 4.14718 7.3812 3.78991 8L2.20492 10.7453C1.62757 11.7453 2.34926 12.9953 3.50396 12.9953L10.4785 12.9953C11.193 12.9953 11.8533 12.6141 12.2105 11.9953L13.7955 9.25C14.3729 8.25 13.6512 7 12.4965 7L5.52196 7Z"/></svg>';

function fileIconHtml(type){
  const g = ICON_GLYPHS[type];
  if(!g) return '';
  return '<span class="file-icon glyph" style="color:' + g.color + '">' + g.char + '</span>';
}

function tabIconHtml(file){
  if(file.fileIconType) return fileIconHtml(file.fileIconType);
  if(file.toolIcon) return '<span class="file-icon" style="font-family:var(--mono,monospace);font-size:13px;color:#519aba">' + file.toolIcon + '</span>';
  return fileIconHtml(file.icon);
}

function folderIconHtml(open){
  return '<span class="file-icon" style="color:' + FOLDER_COLOR + '">' + (open ? FOLDER_OPEN_SVG : FOLDER_CLOSED_SVG) + '</span>';
}

const files = {
  intro:            { label:'intro.js',            icon:'js',   folder:null },
  about:             { label:'README.md',           icon:'info', folder:'about' },
  experience:        { label:'experience.js',       icon:'js',   folder:'about' },
  education:         { label:'education.md',        icon:'md',   folder:'about' },
  skills:            { label:'skills.json',         icon:'json', folder:'about' },
  websites:          { label:'websites.html',       icon:'html', folder:'projects' },
  'web-systems':     { label:'web-systems.php',     icon:'php',  folder:'projects' },
  'landing-pages':   { label:'landing-pages.html',  icon:'html', folder:'projects' },
  'edm-tools':       { label:'eDM-tools.html',      icon:'html', folder:'projects' },
  'mini-games':      { label:'mini-games.html',     icon:'html', folder:'projects' },
  'site-history':    { label:'site-history.html',   icon:'html', folder:'projects' },
  'mini-tools-readme':      { label:'mini-tools.md',        icon:'md',   folder:'mini-tools' },
  'mini-tools-dev':         { label:'dev-utilities.md',      icon:'md',   folder:'mini-tools' },
  'mini-tools-media':       { label:'media-tools.md',        icon:'md',   folder:'mini-tools' },
  'mini-tools-converters':  { label:'converters.md',         icon:'md',   folder:'mini-tools' },
  'mini-tools-generators':  { label:'generators.md',         icon:'md',   folder:'mini-tools' },
  'mini-tools-pdf':         { label:'pdf-tools.md',          icon:'md',   folder:'mini-tools' },
  freelance:         { label:'freelance.css',       icon:'css',  folder:null },
  contact:           { label:'contact.eml',          icon:'eml',  folder:null },
  documents:         { label:'documents.pdf',       icon:'pdf',  folder:null }
};
const folders = {
  about:      { label:'about/', children:['about','experience','education','skills'] },
  projects:   { label:'projects/', children:['websites','web-systems','landing-pages','edm-tools','mini-games','site-history'] },
  'mini-tools': { label:'mini-tools/', children:['mini-tools-readme','mini-tools-dev','mini-tools-media','mini-tools-converters','mini-tools-generators','mini-tools-pdf'] },
  'mini-tools-dev':         { label:'dev-utilities/' },
  'mini-tools-media':       { label:'media-tools/' },
  'mini-tools-converters':  { label:'converters/' },
  'mini-tools-generators':  { label:'generators/' },
  'mini-tools-pdf':         { label:'pdf-tools/' }
};
const MINI_TOOL_CATEGORY_IDS = new Set(['mini-tools-dev','mini-tools-media','mini-tools-converters','mini-tools-generators','mini-tools-pdf']);

const FOLDER_DEFAULT_FILE = { about: 'about', 'mini-tools': 'mini-tools-readme', projects: 'websites' };

const TOOL_TAB_REGISTRY = {};
const rootOrder = ['intro','about','projects','mini-tools','freelance','documents','contact'];

const DEFAULT_OPEN_TABS = ['intro'];
let openTabs = DEFAULT_OPEN_TABS.slice();
let activeId = 'intro';
let openFolders = { about:true };

const WEBSITE_STYLE_CATEGORIES = ['websites', 'web-systems'];
const WEBSITE_CATEGORY_LABELS = { websites: 'websites', 'web-systems': 'web systems' };
const WEBSITE_CATEGORY_TAGS = { websites: { plural: 'websites', singular: 'website' }, 'web-systems': { plural: 'web-systems', singular: 'web-system' } };

let websiteDetailIds = { websites: null, 'web-systems': null };
let websiteItemsByCategory = { websites: [], 'web-systems': [] };
let websiteSectKeys = { websites: '', 'web-systems': '' };
let websiteDetailWasOpen = { websites: false, 'web-systems': false };

function resetWebsiteDetailIds(){
  WEBSITE_STYLE_CATEGORIES.forEach(c => websiteDetailIds[c] = null);
}

let bioTypedOnce = false;
const bioText = `Hi, I'm Bruno — a Brazilian-born, Sydney-based developer with ${yearsExperience}+ years of experience across full-stack and front-end development. I specialise in building reliable, well-structured systems — from custom PHP/Node back-ends to pixel-perfect front-ends.\n\nI'm 32, originally from Rio de Janeiro, Brazil, and I've called Sydney home since 2017. \nI'm an Australian citizen, fluent in English, Portuguese and Spanish.\n\nRather than a typical portfolio, this website is built like a developer tool — a file explorer, tabs, even a boot-up sequence — so browsing it feels less like reading a resume and more like poking around a codebase, getting a real sense of how I think and build.`;

const SCRAMBLE_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!#$%&*+=?';
let emailScrambleTimer = null;
const emailRowEl = document.getElementById('emailRow');
const emailTextEl = document.getElementById('emailText');
let emailRevealedOnce = false;

const isLocalhost = ['localhost', '127.0.0.1', ''].includes(location.hostname);
function isDevMode(){
  return isLocalhost && localStorage.getItem('devMode') === 'true';
}

function wantsFullscreenView(){
  return new URLSearchParams(location.search).get('fs') === '1';
}

function wantsAllToolsInCategory(){
  return new URLSearchParams(location.search).get('all') === '1';
}

// Every route otherwise shares the same static <title>, so same-origin tabs
// (e.g. several mini-tools opened via the app page's "Open" links) are
// indistinguishable in Safari's tab switcher. Give each active tab/tool its
// own title so they can actually be told apart.
const BASE_DOCUMENT_TITLE = document.title;
function updateDocumentTitle(){
  const label = activeId && files[activeId] && files[activeId].label;
  document.title = label ? `Bruno Vieira • ${label}` : BASE_DOCUMENT_TITLE;
}

function currentRouteId(){
  return isDevMode()
    ? decodeURIComponent(location.hash.replace(/^#/, ''))
    : decodeURIComponent(location.pathname.replace(/^\/+|\/+$/g, ''));
}

const ROUTE_ALIASES = { 'mini-tools': 'mini-tools-readme' };

function applyPath(id){
  id = ROUTE_ALIASES[id] || id;
  if(!id || !files[id]) return false;
  if(files[id].folder) openFolders[files[id].folder] = true;
  if(!openTabs.includes(id)) openTabs.push(id);
  activeId = id;
  return true;
}

function readWebsiteDetailFromUrl(){
  return new URLSearchParams(location.search).get('p');
}

function stripWebsiteDetailParam(search){
  if(!search || !search.includes('p=')) return search || '';
  const params = new URLSearchParams(search);
  params.delete('p');
  const str = params.toString();
  return str ? '?' + str : '';
}

function updatePath(id){
  const search = stripWebsiteDetailParam(location.search);
  if(isDevMode()){
    const newHash = id ? '#' + id : '';
    if(location.hash !== newHash || location.search !== search){
      history.pushState(null, '', location.pathname + search + newHash);
    }
    return;
  }
  const newPath = id ? '/' + id : '/';
  if(location.pathname !== newPath || location.search !== search){
    history.pushState(null, '', newPath + search);
  }
}

const initialRouteId = currentRouteId();
const enteredViaDeepLink = applyPath(initialRouteId);
if(WEBSITE_STYLE_CATEGORIES.includes(activeId)) websiteDetailIds[activeId] = readWebsiteDetailFromUrl();
let pendingToolRouteId = enteredViaDeepLink ? null : (initialRouteId || null);

function toolIdsForCategory(categoryId){
  return Object.keys(TOOL_TAB_REGISTRY).filter(id => TOOL_TAB_REGISTRY[id].category === categoryId);
}

function folderChildren(key){
  return MINI_TOOL_CATEGORY_IDS.has(key) ? [key, ...toolIdsForCategory(key)] : folders[key].children;
}

function ancestorFolderIds(folderId){
  const ids = new Set();
  let cur = folderId;
  while(cur && !ids.has(cur)){
    ids.add(cur);
    cur = files[cur] ? files[cur].folder : null;
  }
  return ids;
}

function leafDisplay(id){
  if(TOOL_TAB_REGISTRY[id]) return { label: id + '.js', iconHtml: fileIconHtml('js') };
  const f = files[id];
  return { label: f.label, iconHtml: fileIconHtml(f.icon) };
}

function openTreeItem(id){
  if(TOOL_TAB_REGISTRY[id]) openToolTab(id); else openFile(id);
}

function isFolderNode(key, depth){
  return depth===0 ? !!folders[key] : depth===1 && MINI_TOOL_CATEGORY_IDS.has(key);
}

function renderTreeNode(key, depth, container, highlightId, activeFolderIds){
  if(isFolderNode(key, depth)){
    const folder = folders[key];
    const defaultFile = FOLDER_DEFAULT_FILE[key] || (files[key] ? key : null);
    const openable = !!defaultFile;
    const selected = (openable && highlightId===defaultFile) || activeFolderIds.has(key);
    const head = document.createElement('div');
    head.className = 'tree-item' + (openFolders[key] ? ' folder-open' : '') + (selected ? ' active' : '');
    if(depth>0) head.style.paddingLeft = (depth*20)+'px';
    head.innerHTML = '<span class="left">' + folderIconHtml(!!openFolders[key]) + folder.label + '</span><span class="caret">▸</span>';
    head.onclick = ()=>{
      const nextOpen = !openFolders[key];
      openFolders[key] = nextOpen;
      if(openable && nextOpen && !window.matchMedia('(max-width: 720px)').matches) openFile(defaultFile); else renderExplorer();
    };
    container.appendChild(head);

    const kids = document.createElement('div');
    kids.className = 'folder-children' + (openFolders[key] ? '' : ' collapsed');
    folderChildren(key).forEach(childId => renderTreeNode(childId, depth+1, kids, highlightId, activeFolderIds));
    container.appendChild(kids);
  } else {
    const { label, iconHtml } = leafDisplay(key);
    const item = document.createElement('div');
    if(depth>0) item.style.paddingLeft = (depth*20)+'px';
    item.className = 'tree-item' + (highlightId===key ? ' active' : '');
    item.innerHTML = '<span class="left">' + iconHtml + label + '</span>';
    item.onclick = ()=> openTreeItem(key);
    item.addEventListener('contextmenu', (e)=>{
      e.preventDefault();
      e.stopPropagation();
      openTreeContextMenu(e.clientX, e.clientY, key);
    });
    container.appendChild(item);
  }
}

function renderExplorer(){
  const tree = document.getElementById('fileTree');
  tree.innerHTML = '';
  const activeFile = activeId && files[activeId];
  const highlightId = (activeFile && activeFile.isToolTab && activeFile.parentId && !MINI_TOOL_CATEGORY_IDS.has(activeFile.parentId))
    ? activeFile.parentId
    : activeId;
  const immediateFolderId = activeFile ? (activeFile.isToolTab ? activeFile.parentId : activeFile.folder) : null;
  const activeFolderIds = immediateFolderId ? ancestorFolderIds(immediateFolderId) : new Set();
  rootOrder.forEach(key => renderTreeNode(key, 0, tree, highlightId, activeFolderIds));
}

function setAllFolders(open){
  Object.keys(folders).forEach(key=> openFolders[key] = open);
  renderExplorer();
}

function collapseFoldersExceptCurrent(id){
  const file = files[id];
  const parentId = file && (file.isToolTab ? file.parentId : file.folder);
  const keepOpen = parentId ? ancestorFolderIds(parentId) : new Set();
  Object.keys(folders).forEach(key => { openFolders[key] = keepOpen.has(key); });
}

let draggedTabId = null;

function renderTabs(){
  const bar = document.getElementById('tabBar');
  bar.innerHTML = '';
  if(openTabs.length === 0){
    document.getElementById('shell').classList.remove('sidebar-hidden');
    const note = document.createElement('div');
    note.className = 'tabs-empty-note';
    note.textContent = 'No tabs open';
    bar.appendChild(note);
    return;
  }
  openTabs.forEach(id=>{
    const tab = document.createElement('div');
    tab.className = 'tab' + (activeId===id ? ' active' : '');
    tab.draggable = true;
    tab.innerHTML = tabIconHtml(files[id]) + files[id].label + '<span class="close-x">✕</span>';
    tab.addEventListener('click', (e)=>{
      if(e.target.classList.contains('close-x')){
        closeTab(id);
      } else {
        setActive(id);
      }
    });
    tab.addEventListener('dragstart', (e)=>{
      draggedTabId = id;
      tab.classList.add('dragging');
      e.dataTransfer.effectAllowed = 'move';
      e.dataTransfer.setData('text/plain', id);
    });
    tab.addEventListener('dragend', ()=>{
      draggedTabId = null;
      document.querySelectorAll('.tab').forEach(t=>{
        t.classList.remove('dragging','drag-over-before','drag-over-after');
      });
    });
    tab.addEventListener('dragover', (e)=>{
      if(!draggedTabId || draggedTabId === id) return;
      e.preventDefault();
      const rect = tab.getBoundingClientRect();
      const before = (e.clientX - rect.left) < rect.width / 2;
      tab.classList.toggle('drag-over-before', before);
      tab.classList.toggle('drag-over-after', !before);
    });
    tab.addEventListener('dragleave', ()=>{
      tab.classList.remove('drag-over-before','drag-over-after');
    });
    tab.addEventListener('contextmenu', (e)=>{
      e.preventDefault();
      openTabContextMenu(e.clientX, e.clientY, id);
    });
    tab.addEventListener('drop', (e)=>{
      e.preventDefault();
      tab.classList.remove('drag-over-before','drag-over-after');
      if(!draggedTabId || draggedTabId === id) return;
      const fromIdx = openTabs.indexOf(draggedTabId);
      if(fromIdx === -1) return;
      openTabs.splice(fromIdx, 1);
      const rect = tab.getBoundingClientRect();
      const before = (e.clientX - rect.left) < rect.width / 2;
      const toIdx = openTabs.indexOf(id);
      openTabs.splice(before ? toIdx : toIdx + 1, 0, draggedTabId);
      renderTabs();
    });
    bar.appendChild(tab);
  });

  const activeTab = bar.querySelector('.tab.active');
  if(activeTab) activeTab.scrollIntoView({ block: 'nearest', inline: 'nearest' });
}

const QWERTY_NEIGHBOURS = {
  a:'sq', b:'vn', c:'xv', d:'sf', e:'wr', f:'dg', g:'fh', h:'gj', i:'uo', j:'hk',
  k:'jl', l:'k', m:'n', n:'bm', o:'ip', p:'o', q:'wa', r:'et', s:'ad', t:'ry',
  u:'yi', v:'cb', w:'qe', x:'zc', y:'tu', z:'x'
};

function typoFor(correct){
  const lower = correct.toLowerCase();
  const neighbours = QWERTY_NEIGHBOURS[lower];
  let typo = neighbours ? neighbours[Math.floor(Math.random() * neighbours.length)] : String(Math.floor(Math.random() * 10));
  if(correct !== lower) typo = typo.toUpperCase();
  return typo;
}

function typingDelay(char, speed){
  let delay = 40 + Math.random() * 70;
  if(char === ' ') delay += 60;
  if('="/<>'.includes(char)) delay += 30;
  return delay * speed;
}

function buildTypingPlan(container){
  const frag = document.createDocumentFragment();
  Array.from(container.childNodes).forEach(node => frag.appendChild(node.cloneNode(true)));

  const chars = [];
  function walk(node){
    if(node.nodeType === Node.TEXT_NODE){
      const full = node.textContent;
      node.textContent = '';
      for(const ch of full) chars.push({ type:'char', node, char: ch });
      return;
    }
    if(node.nodeType === Node.ELEMENT_NODE){
      if(node.classList && node.classList.contains('tool-count')){
        node.style.visibility = 'hidden';
        chars.push({ type:'reveal', el: node });
        return;
      }
      Array.from(node.childNodes).forEach(walk);
    }
  }
  Array.from(frag.childNodes).forEach(walk);
  return { frag, chars };
}

function humanTypeSect(container, speed, onComplete){
  if(!container || container.dataset.typed) return;
  container.dataset.typed = '1';
  speed = speed || 1;

  const { frag, chars } = buildTypingPlan(container);
  container.innerHTML = '';
  container.appendChild(frag);
  const cursor = document.createElement('span');
  cursor.className = 'cursor-caret sect-caret';
  cursor.textContent = '|';
  const lastEntry = chars[chars.length - 1];
  const cursorHost = (lastEntry && (lastEntry.node || lastEntry.el) && (lastEntry.node || lastEntry.el).parentNode) || container;
  cursorHost.appendChild(cursor);

  let i = 0;
  let mistakeCooldown = 0;

  function typeNext(){
    if(i >= chars.length){
      if(onComplete) onComplete();
      return;
    }
    const entry = chars[i];

    if(entry.type === 'reveal'){
      entry.el.style.visibility = '';
      i++;
      setTimeout(typeNext, (90 + Math.random() * 60) * speed);
      return;
    }

    const { node, char } = entry;
    const canMistake = mistakeCooldown <= 0 && i < chars.length - 1 && /[a-zA-Z0-9]/.test(char) && Math.random() < 0.07;

    if(canMistake){
      mistakeCooldown = 6;
      node.textContent += typoFor(char);
      setTimeout(()=>{
        node.textContent = node.textContent.slice(0, -1);
        setTimeout(()=>{
          node.textContent += char;
          i++;
          setTimeout(typeNext, typingDelay(char, speed));
        }, (90 + Math.random() * 80) * speed);
      }, (160 + Math.random() * 180) * speed);
      return;
    }

    node.textContent += char;
    i++;
    if(mistakeCooldown > 0) mistakeCooldown--;
    setTimeout(typeNext, typingDelay(char, speed));
  }

  typeNext();
}

function showActivePanel(){
  document.querySelectorAll('.panel').forEach(p=>p.classList.remove('active'));
  const empty = document.getElementById('emptyState');
  const isToolTab = activeId && files[activeId] && files[activeId].isToolTab;
  updateToolTabFrames(isToolTab ? activeId : null);
  updateDocumentTitle();
  if(activeId && openTabs.includes(activeId)){
    empty.classList.remove('show');
    if(isToolTab) return;
    const el = document.getElementById('panel-'+activeId);
    if(el){
      el.classList.add('active');
      const sect = el.querySelector('h2.sect');
      if(sect) humanTypeSect(sect);
      if(activeId === 'about') startBioTyping();
      if(activeId === 'contact') startEmailReveal();
      if(WEBSITE_STYLE_CATEGORIES.includes(activeId)) renderWebsiteDetail(activeId);
      if(activeId === 'mini-games') fitGameFrame();
    }
  } else {
    empty.classList.add('show');
  }
}

function setActive(id){
  resetWebsiteDetailIds();
  activeId = id;
  renderTabs();
  renderExplorer();
  showActivePanel();
  updatePath(id);
}

function openFile(id){
  if(files[id].folder) openFolders[files[id].folder] = true;
  if(!openTabs.includes(id)) openTabs.push(id);
  setActive(id);
  document.getElementById('shell').classList.remove('mobile-nav-open');
  setMobileNavLock(false);
}

function closeTab(id){
  const idx = openTabs.indexOf(id);
  if(idx === -1) return;
  resetWebsiteDetailIds();
  openTabs.splice(idx,1);
  removeToolTabFrame(id);
  if(activeId === id){
    if(openTabs.length){
      activeId = openTabs[Math.max(0, idx-1)];
    } else {
      activeId = null;
    }
  }
  renderTabs();
  renderExplorer();
  showActivePanel();
  updatePath(activeId);
}

function closeAllTabs(){
  openTabs.forEach(removeToolTabFrame);
  openTabs = [];
  activeId = null;
  resetWebsiteDetailIds();
  renderTabs();
  renderExplorer();
  showActivePanel();
  updatePath(null);
}

function closeOtherTabs(keepId){
  keepId = keepId || activeId;
  if(!keepId) return;
  openTabs.filter(id => id !== keepId).forEach(removeToolTabFrame);
  openTabs = [keepId];
  activeId = keepId;
  resetWebsiteDetailIds();
  renderTabs();
  renderExplorer();
  showActivePanel();
  updatePath(activeId);
}

function closeTabsToTheRight(id){
  const idx = openTabs.indexOf(id);
  if(idx === -1) return;
  const toClose = openTabs.slice(idx + 1);
  if(!toClose.length) return;
  toClose.forEach(removeToolTabFrame);
  openTabs = openTabs.slice(0, idx + 1);
  resetWebsiteDetailIds();
  if(!openTabs.includes(activeId)) activeId = id;
  renderTabs();
  renderExplorer();
  showActivePanel();
  updatePath(activeId);
}

function cycleTabs(direction){
  if(!openTabs.length) return;
  const idx = openTabs.indexOf(activeId);
  const nextIdx = idx === -1 ? 0 : (idx + direction + openTabs.length) % openTabs.length;
  setActive(openTabs[nextIdx]);
}

function jumpToTabIndex(index){
  if(index < 0 || index >= openTabs.length) return;
  setActive(openTabs[index]);
}

function urlForTabId(id){
  const path = isDevMode() ? (location.pathname + '#' + id) : ('/' + id);
  return location.origin + path;
}

function openToolTab(id){
  if(!tryOpenToolTabRoute(id)) return;
  setActive(id);
  document.getElementById('shell').classList.remove('mobile-nav-open');
  setMobileNavLock(false);
}

function registerToolFile(id){
  if(files[id]) return;
  const info = TOOL_TAB_REGISTRY[id];
  files[id] = { label: info.title, toolIcon: info.icon, fileIconType: info.fileIcon, folder: null, isToolTab: true, toolPath: info.path, parentId: info.category || null };
}

function tryOpenToolTabRoute(id){
  if(!TOOL_TAB_REGISTRY[id]) return false;
  registerToolFile(id);
  const parentId = files[id].parentId;
  if(parentId){
    openFolders[parentId] = true;
    const parent = files[parentId];
    if(parent && parent.folder) openFolders[parent.folder] = true;
  }
  if(!openTabs.includes(id)) openTabs.push(id);
  activeId = id;
  return true;
}

function openCategorySiblings(id){
  const parentId = files[id] && files[id].parentId;
  if(!parentId || !MINI_TOOL_CATEGORY_IDS.has(parentId)) return;
  toolIdsForCategory(parentId).forEach(siblingId => {
    registerToolFile(siblingId);
    if(!openTabs.includes(siblingId)) openTabs.push(siblingId);
  });
}

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
  frame.style.transformOrigin = `${130 - rect.left}px ${(window.innerHeight - 34) - rect.top}px`;
}

function repositionActiveToolTabFrame(){
  const active = document.querySelector('.tool-tab-frame.active');
  if(active) positionToolTabFrame(active);
}
window.addEventListener('resize', repositionActiveToolTabFrame);

// Mobile Safari can suspend a backgrounded tab and, on restore, repaint a
// position:fixed tool-tab-frame with a stale/wrong compositor layer (a
// different tool's iframe) even though the underlying .active class was never
// actually wrong — re-adding the same class to the same element is a no-op
// and doesn't force WebKit to recomposite. So instead of just re-asserting
// state, force every tool-tab-frame through a real display:none -> reflow ->
// restore cycle whenever the tab becomes visible again, to make Safari
// actually repaint from scratch rather than trust whatever pixels it kept
// around from before the suspend.
function resyncActiveToolTabFrame(){
  const isToolTab = activeId && files[activeId] && files[activeId].isToolTab;
  const frames = document.querySelectorAll('.tool-tab-frame');
  frames.forEach(f => { f.style.display = 'none'; });
  void document.body.offsetHeight;
  updateToolTabFrames(isToolTab ? activeId : null);
  frames.forEach(f => { f.style.display = ''; });
}
window.addEventListener('pageshow', resyncActiveToolTabFrame);
document.addEventListener('visibilitychange', () => { if(document.visibilityState === 'visible') resyncActiveToolTabFrame(); });

function ensureToolTabFrame(id){
  let frame = document.getElementById('toolTabFrame-' + id);
  if(!frame){
    const info = files[id];
    frame = document.createElement('div');
    frame.className = 'tool-tab-frame';
    frame.id = 'toolTabFrame-' + id;
    frame.innerHTML = `<div class="tool-tab-mask"><span class="tool-tab-mask-label">loading&hellip;</span></div><iframe src="${escapeHtml(info.toolPath)}" title="${escapeHtml(info.label)}"></iframe>`;
    document.body.appendChild(frame);
    playToolCompileAnimation(frame, id);
    const mask = frame.querySelector('.tool-tab-mask');
    const maskLabel = mask.querySelector('.tool-tab-mask-label');
    // Only reveal the "loading..." label if the load is actually slow enough
    // to notice, so fast loads keep the plain fade with no extra text/delay.
    const maskLabelTimer = setTimeout(() => maskLabel.classList.add('show'), 500);
    frame.querySelector('iframe').addEventListener('load', () => {
      clearTimeout(maskLabelTimer);
      requestAnimationFrame(() => {
        mask.classList.add('hide');
        mask.addEventListener('transitionend', () => mask.remove(), { once: true });
      });
    }, { once: true });
  }
  return frame;
}

function playToolCompileAnimation(frame, id){
  const bar = document.createElement('div');
  bar.className = 'tool-compile-bar';
  const line = document.createElement('span');
  bar.appendChild(line);
  frame.appendChild(bar);

  requestAnimationFrame(() => bar.classList.add('show'));

  const text = `$ compiling ${id}.module ... ok`;
  let i = 0;
  function typeNext(){
    if(!document.body.contains(bar)) return;
    if(i >= text.length){
      line.insertAdjacentHTML('beforeend', '<span class="boot-cursor">_</span>');
      setTimeout(() => {
        bar.classList.remove('show');
        bar.addEventListener('transitionend', () => bar.remove(), { once: true });
      }, 2000);
      return;
    }
    line.textContent += text[i];
    setTimeout(typeNext, typingDelay(text[i], 0.3));
    i++;
  }
  typeNext();
}

function removeToolTabFrame(id){
  const frame = document.getElementById('toolTabFrame-' + id);
  if(frame) frame.remove();
  if(files[id] && files[id].isToolTab) delete files[id];
}

function updateToolTabFrames(activeToolId){
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

function handleRouteChange(){
  const id = currentRouteId();
  if(!id){
    activeId = 'intro';
    openTabs = DEFAULT_OPEN_TABS.slice();
    resetWebsiteDetailIds();
    renderTabs();
    renderExplorer();
    showActivePanel();
    return;
  }
  if(applyPath(id) || tryOpenToolTabRoute(id)){
    resetWebsiteDetailIds();
    if(WEBSITE_STYLE_CATEGORIES.includes(activeId)) websiteDetailIds[activeId] = readWebsiteDetailFromUrl();
    renderTabs();
    renderExplorer();
    showActivePanel();
  }
}
window.addEventListener('popstate', handleRouteChange);
window.addEventListener('hashchange', handleRouteChange);

document.querySelectorAll('[data-open]').forEach(el=>{
  el.addEventListener('click', ()=> openFile(el.dataset.open));
});

document.querySelectorAll('.commit').forEach(c=>{
  const details = c.querySelector('.details');
  if(!details) return;
  details.classList.add('more');
  c.classList.add('open');
  const msg = c.querySelector('.msg');
  msg.classList.add('toggle-title');
  msg.insertAdjacentHTML('beforeend', ' <span class="chev">▾</span>');
  msg.addEventListener('click', ()=> c.classList.toggle('open'));
  const logo = c.querySelector('.commit-logo');
  if(logo) logo.addEventListener('click', ()=> c.classList.toggle('open'));
});

function toggleAllJobDetails(){
  const commits = document.querySelectorAll('#panel-experience .commit');
  const btn = document.getElementById('toggleJobDetailsBtn');
  if(!commits.length || !btn) return;
  const hide = btn.dataset.hidden !== 'true';
  commits.forEach(c=> c.classList.toggle('open', !hide));
  btn.dataset.hidden = String(hide);
  btn.innerHTML = hide
    ? '<svg class="btn-icon" viewBox="0 0 24 24"><use href="img/icons/sprite.svg#icon-eye"></use></svg>Show job details'
    : '<svg class="btn-icon" viewBox="0 0 24 24"><use href="img/icons/sprite.svg#icon-eye-off"></use></svg>Hide job details';
}

renderExplorer();
renderTabs();
showActivePanel();
if(wantsFullscreenView()){
  document.getElementById('shell').classList.add('sidebar-hidden');
  hintGreenDot();
}

const ICON_GITHUB_SVG = '<svg class="btn-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/></svg>';
const ICON_CODEPEN_SVG = '<svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" stroke-linecap="round"><path d="M12 2.5 22 9v6l-10 6.5L2 15V9z"/><path d="M12 2.5v6.2M12 22v-6.2M2 9l10 6.2M22 9 12 15.2M2 15l10-6.2M22 15 12 8.8"/></svg>';
const ICON_LIVE_SVG = '<svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><path d="M15 3h6v6"/><path d="M10 14 21 3"/></svg>';
const ICON_EYE_SVG = '<svg class="btn-icon" viewBox="0 0 24 24"><use href="img/icons/sprite.svg#icon-eye"></use></svg>';
const ICON_EYE_OFF_SVG = '<svg class="btn-icon" viewBox="0 0 24 24"><use href="img/icons/sprite.svg#icon-eye-off"></use></svg>';
const ICON_PDF_SVG = '<svg class="btn-icon icon-pdf" viewBox="0 0 24 24"><use href="img/icons/sprite.svg#icon-pdf"></use></svg>';
const ICON_FULLSCREEN_SVG = '<svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3H5a2 2 0 0 0-2 2v3M16 3h3a2 2 0 0 1 2 2v3M21 16v3a2 2 0 0 1-2 2h-3M8 21H5a2 2 0 0 1-2-2v-3"/></svg>';

function escapeHtml(str){
  if(str == null) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function renderToolCard(project, sameYearAsPrevious, category){
  const title = escapeHtml(project.title);
  const description = escapeHtml(project.description);
  const path = escapeHtml(project.path);
  const github = escapeHtml(project.github);
  const codepen = escapeHtml(project.codepen);
  const live = escapeHtml(project.live);
  const iconHtml = project.icon ? `<span class="ic-emoji">${escapeHtml(project.icon)}</span>` : '';
  const yearHtml = project.year ? `<span class="tool-year">${escapeHtml(String(project.year))}</span>` : '';
  const descHtml = project.description ? `<div class="tool-desc">${description}</div>` : '';
  const liveHtml = project.live ? `<a class="doc-btn" href="${live}" target="_blank" rel="noopener">${ICON_LIVE_SVG}Live Site</a>` : '';
  const githubHtml = project.github ? `<a class="doc-btn" href="${github}" target="_blank" rel="noopener">${ICON_GITHUB_SVG}GitHub</a>` : '';
  const codepenHtml = project.codepen ? `<a class="doc-btn" href="${codepen}" target="_blank" rel="noopener">${ICON_CODEPEN_SVG}CodePen</a>` : '';
  const fullWidthClass = project.featured ? ' tool-card-full' : '';
  const viewLabel = project.category === 'mini-tools' ? 'Quick View' : 'View';
  const viewHtml = project.noView ? '' : `<button class="doc-btn" data-view-label="${viewLabel}" onclick="toggleDoc('${project.id}')">${ICON_EYE_SVG}${viewLabel}</button>`;
  const nameClickAttr = project.noView ? '' : ` onclick="toggleDoc('${project.id}')"`;
  const nameClass = project.noView ? 'doc-name' : 'doc-name doc-name-clickable';
  const showOpenTab = !project.noView && !project.live && ['site-history', 'mini-tools'].includes(project.category);
  if(showOpenTab) TOOL_TAB_REGISTRY[project.id] = { path: project.path, title: project.title, icon: project.glyph || project.icon, fileIcon: project.fileIcon, category: category || project.category, github: project.github || null, codepen: project.codepen || null };
  const openTabHtml = showOpenTab ? `<button class="doc-btn" onclick="openToolTab('${project.id}')">${ICON_LIVE_SVG}Open</button>` : '';
  const embedHtml = project.noView ? '' : `
      <div class="doc-embed" id="embed-${project.id}">
        <iframe data-src="${path}" title="${title}"></iframe>
      </div>`;
  const prototypes = Array.isArray(project.prototypes) ? project.prototypes : [];
  const prototypeGroup = `${project.id}-prototypes`;
  const prototypeBtnsHtml = prototypes.map(p => `<button class="doc-btn" onclick="toggleExclusiveDoc('${p.id}','${prototypeGroup}')">${ICON_EYE_SVG}${escapeHtml(p.label)}</button>`).join('');
  const prototypeEmbedsHtml = prototypes.map(p => `
      <div class="doc-embed" id="embed-${p.id}" data-group="${prototypeGroup}">
        <iframe data-src="${escapeHtml(p.path)}" title="${escapeHtml(p.label)}"></iframe>
      </div>`).join('');
  return `
    <div class="doc-card tool-card${fullWidthClass}" id="project-${project.id}">
      <div class="tool-card-top">
        <div class="doc-head">
          ${iconHtml}
          <div class="${nameClass}"${nameClickAttr}>${title}</div>
          ${yearHtml}
        </div>
        ${descHtml}
        <div class="doc-actions">
          ${viewHtml}
          ${openTabHtml}
          ${liveHtml}
          ${codepenHtml}
          ${prototypeBtnsHtml}
          ${githubHtml}
        </div>
      </div>${embedHtml}${prototypeEmbedsHtml}
    </div>`;
}

function renderTimelineCard(project, sameYearAsPrevious, category){
  const yearHtml = (project.year && !sameYearAsPrevious) ? `<div class="year">${escapeHtml(String(project.year))}</div>` : '';
  const { year, ...rest } = project;
  const cardHtml = renderToolCard({ ...rest, featured: true }, sameYearAsPrevious, category);
  return `
    <div class="commit timeline-commit">
      <div class="commit-body">
        ${yearHtml}
        ${cardHtml}
      </div>
    </div>`;
}

const carouselIndex = {};
function carouselGoto(id, index){
  const track = document.getElementById('carousel-track-' + id);
  if(!track) return;
  const count = track.children.length;
  index = ((index % count) + count) % count;
  carouselIndex[id] = index;
  track.style.transform = `translateX(-${index * 100}%)`;
  document.querySelectorAll(`#carousel-${id} .dot`).forEach((d,i)=> d.classList.toggle('active', i===index));
}
function carouselNav(id, dir){
  carouselGoto(id, (carouselIndex[id] || 0) + dir);
}

function renderCaseCard(project){
  const title = escapeHtml(project.title);
  const badge = project.badge ? `<span class="case-badge">${escapeHtml(project.badge)}</span>` : '';
  const iconHtml = project.icon ? `<span class="ic-emoji">${escapeHtml(project.icon)}</span>` : '';
  const yearHtml = project.year ? `<span class="website-year">${escapeHtml(String(project.year))}</span>` : '';
  const roleHtml = project.role ? `<div class="website-role">${escapeHtml(project.role)}</div>` : '';
  const techList = Array.isArray(project.tech) ? project.tech : [];
  const techHtml = techList.length ? `<div class="website-tech">${techList.map(t => `<span class="kw-pill">${escapeHtml(t)}</span>`).join('')}</div>` : '';
  const mediaList = Array.isArray(project.media) ? project.media.filter(Boolean) : (project.media ? [project.media] : []);
  let mediaHtml;
  if(mediaList.length === 0){
    mediaHtml = `<div class="case-media empty">🖼️ Screenshot / GIF coming soon</div>`;
  } else if(mediaList.length === 1){
    mediaHtml = `<div class="case-media"><div class="case-media-scroll"><img src="${escapeHtml(mediaList[0])}" alt="${title}" loading="lazy"></div></div>`;
  } else {
    const imgs = mediaList.map(src => `<img src="${escapeHtml(src)}" alt="${title}" loading="lazy">`).join('');
    const dots = mediaList.map((_, i) => `<span class="dot${i === 0 ? ' active' : ''}" onclick="carouselGoto('${project.id}', ${i})"></span>`).join('');
    mediaHtml = `
      <div class="case-media">
        <div class="case-carousel" id="carousel-${project.id}">
          <div class="case-carousel-track" id="carousel-track-${project.id}">${imgs}</div>
          <button class="carousel-btn prev" onclick="carouselNav('${project.id}', -1)" aria-label="Previous image">‹</button>
          <button class="carousel-btn next" onclick="carouselNav('${project.id}', 1)" aria-label="Next image">›</button>
          <div class="carousel-dots">${dots}</div>
        </div>
      </div>`;
  }
  const descHtml = project.description ? `<div class="case-section">${escapeHtml(project.description)}</div>` : '';
  const sections = ['challenge','technique','outcome']
    .filter(key => project[key])
    .map(key => `<div class="case-section"><b>${key.charAt(0).toUpperCase() + key.slice(1)}:</b> ${escapeHtml(project[key])}</div>`)
    .join('');
  const githubHtml = project.github ? `<a class="doc-btn" href="${escapeHtml(project.github)}" target="_blank" rel="noopener">${ICON_GITHUB_SVG}GitHub</a>` : '';
  const githubUrl = project.github ? escapeHtml(project.github) : '';
  const greenDotHtml = githubUrl
    ? `<span class="website-dot g" onclick="openInNewWindow('${githubUrl}')" title="View source on GitHub"></span>`
    : `<span class="website-dot g disabled" title="No source link"></span>`;
  return `
    <div class="case-card" id="project-${project.id}">
      <div class="website-chrome">
        <span class="website-dot r" onclick="toggleCardMinimize('${project.id}')" title="Minimize"></span><span class="website-dot y" onclick="toggleCaseMediaHidden('${project.id}')" title="Hide screenshot"></span>${greenDotHtml}
        <div class="website-urlbar">${project.icon ? `<span class="website-lock">${escapeHtml(project.icon)}</span>` : ''}${title}</div>
      </div>
      ${mediaHtml}
      <div class="case-body">
        <div class="case-head">
          ${iconHtml}
          <div class="case-title">${title}</div>
          ${yearHtml}
          ${badge}
        </div>
        ${roleHtml}
        ${descHtml}
        ${techHtml}
        ${sections}
        ${githubHtml ? `<div class="case-actions">${githubHtml}</div>` : ''}
      </div>
    </div>`;
}

function renderWebsiteCard(project, _sameYearAsPrevious, category){
  category = category || 'websites';
  const title = escapeHtml(project.title);
  const url = escapeHtml(project.url);
  const live = escapeHtml(project.live);
  const github = escapeHtml(project.github);
  const status = project.status === 'live' ? 'live' : 'offline';
  const iconHtml = project.icon ? escapeHtml(project.icon) : '🌐';
  const yearHtml = project.year ? `<span class="website-year">${escapeHtml(String(project.year))}</span> ` : '';
  const roleHtml = project.role ? `<div class="website-role">${escapeHtml(project.role)}</div>` : '';
  const logoHtml = project.logo ? `<img class="website-logo" src="${escapeHtml(project.logo)}" alt="${title} logo">` : '';
  const descHtml = project.description ? `<div class="tool-desc">${escapeHtml(project.description)}</div>` : '';
  const techList = Array.isArray(project.tech) ? project.tech : [];
  const techHtml = techList.length ? `<div class="website-tech">${techList.map(t => `<span class="kw-pill">${escapeHtml(t)}</span>`).join('')}</div>` : '';
  const thumbSrc = project.screenshot || project.screenshotGif;
  const enableHoverGif = !!(project.screenshot && project.screenshotGif);
  const containThumb = project.thumbFit === 'contain';
  const thumbHtml = thumbSrc
    ? `<img class="website-thumb-img" src="${escapeHtml(thumbSrc)}" alt="${title} screenshot" loading="lazy"${enableHoverGif ? ` data-static-src="${escapeHtml(project.screenshot)}" data-gif-src="${escapeHtml(project.screenshotGif)}"` : ''}>`
    : `<span class="website-thumb-icon">${iconHtml}</span>`;
  const linkTo = project.linkTo;
  const detailAction = linkTo
    ? `goToProject('${escapeHtml(linkTo.category)}','${escapeHtml(linkTo.id)}','')`
    : `openWebsiteDetail('${category}','${project.id}')`;
  const showStatus = category !== 'web-systems';
  const archiveUrl = escapeHtml(project.archiveUrl || '');
  const isArchived = status === 'offline' && !!project.archiveUrl;
  const visitTargetUrl = status === 'live' ? live : archiveUrl;
  const cardClickable = !!visitTargetUrl;
  const overlayHtml = showStatus && status === 'offline' && !isArchived ? `<div class="website-offline-overlay">🕸️ No longer live</div>` : '';
  const visitHtml = cardClickable
    ? `<button class="doc-btn" onclick="openInNewWindow('${visitTargetUrl}')">${ICON_LIVE_SVG}${isArchived ? 'View on Wayback Machine' : 'Visit site'}</button>`
    : (showStatus ? `<span class="doc-btn website-offline-btn">🕸️ Offline</span>` : '');
  const githubHtml = project.github ? `<a class="doc-btn" href="${github}" target="_blank" rel="noopener">${ICON_GITHUB_SVG}GitHub</a>` : '';
  const pdfHtml = project.pdf ? `<a class="doc-btn" href="${escapeHtml(project.pdf)}" target="_blank" rel="noopener">${ICON_PDF_SVG}Read thesis</a>` : '';
  const companySlug = slugify(websiteFilterCompany(project.company));
  const lockHtml = showStatus ? `<span class="website-lock">${status === 'live' ? '🔒' : '⚠️'}</span>` : '';
  const statusBadgeHtml = showStatus ? `<span class="website-status website-status--${status}">${status === 'live' ? '🟢 Live' : '⚫ Offline'}</span>` : '';

  return `
    <div class="website-card" id="project-${project.id}" data-status="${status}" data-company="${companySlug}"${isArchived ? ' data-archived="true"' : ''}>
      <div class="website-chrome">
        <span class="website-dot r" onclick="toggleCardMinimize('${project.id}')" title="Minimize"></span><span class="website-dot y" onclick="toggleCardMinimize('${project.id}')" title="Minimize"></span><span class="website-dot g" onclick="${detailAction}" title="View project"></span>
        <div class="website-urlbar">${lockHtml}${url}</div>
        ${statusBadgeHtml}
      </div>
      <div class="website-thumb${thumbSrc ? '' : ' placeholder'}${containThumb ? ' thumb-contain' : ''}" tabindex="0" role="button" aria-label="View ${title} project details" onclick="${detailAction}" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();${detailAction};}">
        <div class="website-thumb-scroll">
          ${thumbHtml}
        </div>
        ${overlayHtml}
      </div>
      <div class="website-body">
        <div class="website-body-inner">
          <div class="website-info">
            <div class="doc-head">
              <div class="doc-name">${yearHtml}${title}</div>
            </div>
            ${roleHtml}
            ${descHtml}
            ${techHtml}
          </div>
          ${logoHtml ? `<div class="website-logo-wrap">${logoHtml}</div>` : ''}
        </div>
        <div class="doc-actions">
          <button class="doc-btn" onclick="${detailAction}">${ICON_EYE_SVG}View project</button>
          ${visitHtml}
          ${githubHtml}
          ${pdfHtml}
        </div>
      </div>
    </div>`;
}


function renderMiniGameCard(project, sameYearAsPrevious, category){
  const title = escapeHtml(project.title);
  const path = escapeHtml(project.path);
  const github = escapeHtml(project.github);
  TOOL_TAB_REGISTRY[project.id] = { path: project.path, title: project.title, icon: project.glyph || project.icon, category: category || project.category };
  const descHtml = project.description ? `<div class="tool-desc">${escapeHtml(project.description)}</div>` : '';
  const githubLabel = escapeHtml(project.githubLabel || 'GitHub (2013 Java)');
  const githubHtml = project.github ? `<a class="doc-btn" href="${github}" target="_blank" rel="noopener">${ICON_GITHUB_SVG}${githubLabel}</a>` : '';
  const githubLabel2026 = escapeHtml(project.githubLabel2026 || 'GitHub (2026 HTML5)');
  const githubHtml2026 = project.github2026 ? `<a class="doc-btn" href="${escapeHtml(project.github2026)}" target="_blank" rel="noopener">${ICON_GITHUB_SVG}${githubLabel2026}</a>` : '';
  const campaignHtml = project.campaign ? `<button class="doc-btn" onclick="openInNewWindow('${escapeHtml(project.campaign)}')">${ICON_LIVE_SVG}${escapeHtml(project.campaignLabel || 'View Original Campaign')}</button>` : '';

  return `
    <div class="website-card game-card" id="project-${project.id}">
      <div class="website-chrome">
        <span class="website-dot r" onclick="reloadGameFrame('${project.id}')" title="Reload"></span><span class="website-dot y" onclick="toggleCardMinimize('${project.id}')" title="Minimize"></span><span class="website-dot g" onclick="openGameFullscreen('${project.id}')" title="Fullscreen"></span>
        <div class="website-urlbar"><span class="website-lock">🎮</span> ${title}</div>
        <button class="game-fullscreen-btn" onclick="openGameFullscreen('${project.id}')" title="Fullscreen">${ICON_FULLSCREEN_SVG}</button>
      </div>
      <div class="game-frame-wrap" id="gameFrame-${project.id}">
        <iframe src="${path}" title="${title}" scrolling="no" onload="fitGameFrame()"></iframe>
      </div>
      <div class="website-body">
        <div class="website-body-inner">
          <div class="website-info">
            <div class="doc-head">
              <div class="doc-name">${title}</div>
            </div>
            ${descHtml}
          </div>
        </div>
        <div class="doc-actions">
          <button class="doc-btn" onclick="openToolTab('${project.id}')">${ICON_LIVE_SVG}Open</button>
          ${campaignHtml}
          ${githubHtml}
          ${githubHtml2026}
        </div>
      </div>
    </div>`;
}

function measureGameHeight(iframe){
  try{
    const doc = iframe.contentDocument;
    if(!doc || !doc.body) return 0;
    const bottoms = Array.from(doc.body.children)
      .map(el => el.getBoundingClientRect().bottom)
      .filter(v => v > 0);
    return bottoms.length ? Math.max(...bottoms) : doc.body.scrollHeight;
  } catch(e){
    return 0;
  }
}

function fitGameFrame(){
  const wraps = Array.from(document.querySelectorAll('.game-frame-wrap'));
  const heights = wraps.map(wrap => measureGameHeight(wrap.querySelector('iframe')));
  const naturalHeight = Math.max(0, ...heights);
  if(!naturalHeight) return;
  const sharedHeight = Math.min(Math.max(Math.ceil(naturalHeight), 320), 640) + 'px';
  wraps.forEach(wrap => { wrap.style.height = sharedHeight; });
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

function toggleCardMinimize(id){
  const card = document.getElementById('project-' + id);
  if(card) card.classList.toggle('minimized');
}

function toggleWebsiteDetailFocus(id){
  const card = document.getElementById('websiteDetail-' + id);
  if(card) card.classList.toggle('focus-media');
}

function toggleCaseMediaHidden(id){
  const card = document.getElementById('project-' + id);
  if(card) card.classList.toggle('media-hidden');
}

function slugify(str){
  return String(str).toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '') || 'personal';
}

function websiteFilterCompany(company){
  return (!company || company === 'Personal') ? 'Freelance' : company;
}

function applyWebsiteFilters(category){
  const grid = document.getElementById('toolsGrid-' + category);
  if(!grid) return;
  const status = grid.dataset.filter || 'all';
  const company = grid.dataset.companyFilter || 'all';
  grid.querySelectorAll('.website-card.minimized').forEach(card => card.classList.remove('minimized'));
  let visibleCount = 0;
  grid.querySelectorAll('.website-card').forEach(card => {
    const statusMatch = status === 'all' || card.dataset.status === status;
    const companyMatch = company === 'all' || card.dataset.company === company;
    const visible = statusMatch && companyMatch;
    card.style.display = visible ? '' : 'none';
    if(visible) visibleCount++;
  });
  let emptyNote = grid.querySelector('.website-filter-empty');
  if(visibleCount === 0){
    if(!emptyNote){
      emptyNote = document.createElement('div');
      emptyNote.className = 'website-filter-empty grid-empty-note';
      emptyNote.textContent = `No ${WEBSITE_CATEGORY_LABELS[category] || category} match these filters.`;
      grid.appendChild(emptyNote);
    }
  } else if(emptyNote){
    emptyNote.remove();
  }
}

function filterWebsites(category, status, btn){
  const grid = document.getElementById('toolsGrid-' + category);
  if(!grid) return;
  grid.dataset.filter = status;
  btn.parentElement.querySelectorAll('.filter-pill').forEach(b => b.classList.toggle('active', b === btn));
  applyWebsiteFilters(category);
}

function filterWebsitesByCompany(category, company){
  const grid = document.getElementById('toolsGrid-' + category);
  if(!grid) return;
  grid.dataset.companyFilter = company;
  applyWebsiteFilters(category);
  const select = document.getElementById('webCompanySelect-' + category);
  if(select && select.value !== company) select.value = company;
  if(select) select.classList.toggle('active', company !== 'all');
}

function renderWebsiteCompanyFilters(category, items){
  const select = document.getElementById('webCompanySelect-' + category);
  if(!select) return;
  const companies = [...new Set(items.map(p => websiteFilterCompany(p.company)))].sort();
  const options = [`<option value="all">🏢 All Companies</option>`]
    .concat(companies.map(c => `<option value="${slugify(c)}">${escapeHtml(c)}</option>`));
  select.innerHTML = options.join('');
}

function filterWebsitesToCompany(category, companySlug){
  const allStatusBtn = document.querySelector('#webStatusFilters-' + category + ' .filter-pill');
  if(allStatusBtn) filterWebsites(category, 'all', allStatusBtn);
  filterWebsitesByCompany(category, companySlug);
}

function openWebsiteDetail(category, id){
  websiteDetailIds[category] = id;
  showActivePanel();
  const params = new URLSearchParams(location.search);
  params.set('p', id);
  const search = '?' + params.toString();
  const path = isDevMode() ? location.pathname : '/' + category;
  const hash = isDevMode() ? (location.hash || '#' + category) : '';
  history.pushState(null, '', path + search + hash);
  const editor = document.getElementById('editorArea');
  if(editor) editor.scrollTop = 0;
}

function closeWebsiteDetail(category){
  websiteDetailIds[category] = null;
  showActivePanel();
  updatePath(category);
  const editor = document.getElementById('editorArea');
  if(editor) editor.scrollTop = 0;
}

function renderWebsiteDetailHtml(project, category){
  const title = escapeHtml(project.title);
  const url = escapeHtml(project.url);
  const live = escapeHtml(project.live);
  const github = escapeHtml(project.github);
  const status = project.status === 'live' ? 'live' : 'offline';
  const iconHtml = project.icon ? escapeHtml(project.icon) : '🌐';
  const yearHtml = project.year ? `<span class="website-year">${escapeHtml(String(project.year))}</span> ` : '';
  const roleHtml = project.role ? `<div class="website-role">${escapeHtml(project.role)}</div>` : '';
  const logoHtml = project.logo ? `<img class="website-logo" src="${escapeHtml(project.logo)}" alt="${title} logo">` : '';
  const descHtml = project.description ? `<div class="website-detail-desc">${escapeHtml(project.description)}</div>` : '';
  const extendedDescHtml = project.extendedDescription ? `<div class="website-detail-desc website-detail-desc--extended">${escapeHtml(project.extendedDescription)}</div>` : '';
  const techList = Array.isArray(project.tech) ? project.tech : [];
  const techHtml = techList.length ? `<div class="website-tech">${techList.map(t => `<span class="kw-pill">${escapeHtml(t)}</span>`).join('')}</div>` : '';
  const thumbSrc = project.screenshot || project.screenshotGif;
  const enableHoverGif = !!(project.screenshot && project.screenshotGif);
  const containThumb = project.thumbFit === 'contain';
  const thumbHtml = thumbSrc
    ? `<img class="website-thumb-img" src="${escapeHtml(thumbSrc)}" alt="${title} screenshot" loading="lazy"${enableHoverGif ? ` data-static-src="${escapeHtml(project.screenshot)}" data-gif-src="${escapeHtml(project.screenshotGif)}"` : ''}>`
    : `<span class="website-thumb-icon">${iconHtml}</span>`;
  const showStatus = category !== 'web-systems';
  const archiveUrl = escapeHtml(project.archiveUrl || '');
  const isArchived = status === 'offline' && !!project.archiveUrl;
  const visitTargetUrl = status === 'live' ? live : archiveUrl;
  const cardClickable = !!visitTargetUrl;
  const overlayHtml = showStatus && status === 'offline' && !isArchived ? `<div class="website-offline-overlay">🕸️ No longer live</div>` : '';
  const visitHtml = cardClickable
    ? `<button class="doc-btn" onclick="openInNewWindow('${visitTargetUrl}')">${ICON_LIVE_SVG}${isArchived ? 'View on Wayback Machine' : 'Visit site'}</button>`
    : (showStatus ? `<span class="doc-btn website-offline-btn">🕸️ Offline</span>` : '');
  const githubHtml = project.github ? `<a class="doc-btn" href="${github}" target="_blank" rel="noopener">${ICON_GITHUB_SVG}GitHub</a>` : '';
  const pdfHtml = project.pdf ? `<a class="doc-btn" href="${escapeHtml(project.pdf)}" target="_blank" rel="noopener">${ICON_PDF_SVG}Read thesis</a>` : '';
  const lockHtml = showStatus ? `<span class="website-lock">${status === 'live' ? '🔒' : '⚠️'}</span>` : '';
  const statusBadgeHtml = showStatus ? `<span class="website-status website-status--${status}">${status === 'live' ? '🟢 Live' : '⚫ Offline'}</span>` : '';
  const greenDotHtml = cardClickable
    ? `<span class="website-dot g" onclick="openInNewWindow('${visitTargetUrl}')" title="Visit site"></span>`
    : `<span class="website-dot g disabled" title="No live link"></span>`;

  return `
    <div class="website-detail-inner">
    <button class="website-detail-back" onclick="closeWebsiteDetail('${category}')">← Back to all ${WEBSITE_CATEGORY_LABELS[category] || category}</button>
    <div class="website-card website-detail-card" id="websiteDetail-${project.id}" data-status="${status}"${isArchived ? ' data-archived="true"' : ''}>
      <div class="website-detail-split">
        <div class="website-detail-media">
          <div class="website-chrome">
            <span class="website-dot r" onclick="closeWebsiteDetail('${category}')" title="Back"></span><span class="website-dot y" onclick="toggleWebsiteDetailFocus('${project.id}')" title="Toggle screenshot size"></span>${greenDotHtml}
            <div class="website-urlbar">${lockHtml}${url}</div>
            ${statusBadgeHtml}
          </div>
          <div class="website-thumb website-detail-thumb${thumbSrc ? '' : ' placeholder'}${containThumb ? ' thumb-contain' : ''}">
            ${thumbHtml}
            ${overlayHtml}
          </div>
        </div>
        <div class="website-detail-info-col">
          <div class="website-detail-head-row">
            <div class="website-info">
              <div class="doc-head">
                <div class="doc-name">${yearHtml}${title}</div>
              </div>
              ${roleHtml}
            </div>
            ${logoHtml ? `<div class="website-logo-wrap">${logoHtml}</div>` : ''}
          </div>
          ${descHtml}
          ${techHtml}
          ${extendedDescHtml}
          <div class="doc-actions">
            ${visitHtml}
            ${githubHtml}
            ${pdfHtml}
          </div>
        </div>
      </div>
    </div>
    </div>`;
}

function websitesSectHtml(category){
  const detailId = websiteDetailIds[category];
  const items = websiteItemsByCategory[category] || [];
  const tags = WEBSITE_CATEGORY_TAGS[category] || { plural: category, singular: category };
  const project = detailId && items.find(p => p.id === detailId);
  if(project){
    const title = escapeHtml(project.title);
    const yearAttr = project.year ? ` <span class="attr">year</span>=<span class="str">"${escapeHtml(String(project.year))}"</span>` : '';
    return `<span class="brk">&lt;</span>${tags.singular} <span class="attr">name</span>=<span class="str">"${title}"</span>${yearAttr}<span class="brk"> /&gt;</span>`;
  }
  const typeAttr = category === 'websites' ? 'live-preview' : 'internal-system';
  return `<span class="brk">&lt;</span>${tags.plural} <span class="attr">count</span>=<span class="str">"<span class="tool-count" data-count-category="${category}">${items.length}</span>"</span> <span class="attr">type</span>=<span class="str">"${typeAttr}"</span><span class="brk"> /&gt;</span>`;
}

function updateWebsitesSect(category){
  const sect = document.querySelector('#panel-' + category + ' h2.sect');
  if(!sect) return;
  const detailId = websiteDetailIds[category];
  const items = websiteItemsByCategory[category] || [];
  const project = detailId && items.find(p => p.id === detailId);
  const key = project ? project.id : '';
  if(key === websiteSectKeys[category]) return;
  websiteSectKeys[category] = key;
  sect.dataset.typed = '';
  sect.innerHTML = websitesSectHtml(category);
  humanTypeSect(sect);
}

function renderWebsiteDetail(category){
  updateWebsitesSect(category);
  const panel = document.getElementById('panel-' + category);
  if(!panel) return;
  const listView = panel.querySelector('.website-list-view');
  const detailView = panel.querySelector('.website-detail');
  if(!listView || !detailView) return;
  const detailId = websiteDetailIds[category];
  const wasOpen = websiteDetailWasOpen[category];
  websiteDetailWasOpen[category] = !!detailId;
  if(!detailId){
    listView.style.display = '';
    if(wasOpen){
      listView.classList.remove('pane-enter');
      void listView.offsetWidth;
      listView.classList.add('pane-enter');
      listView.addEventListener('animationend', () => listView.classList.remove('pane-enter'), { once: true });
    }
    detailView.classList.remove('active');
    detailView.innerHTML = '';
    return;
  }
  listView.style.display = 'none';
  detailView.classList.add('active');
  const items = websiteItemsByCategory[category] || [];
  const project = items.find(p => p.id === detailId);
  if(!project){
    detailView.innerHTML = items.length
      ? `<div class="website-detail-inner"><button class="website-detail-back" onclick="closeWebsiteDetail('${category}')">← Back to all ${WEBSITE_CATEGORY_LABELS[category] || category}</button><div class="website-detail-missing">Project not found.</div></div>`
      : '';
    return;
  }
  detailView.innerHTML = renderWebsiteDetailHtml(project, category);
  setupWebsiteHoverGifs(detailView);
}

const CATEGORY_RENDERERS = {
  websites: renderWebsiteCard,
  'web-systems': renderWebsiteCard,
  'edm-html-builder': renderCaseCard,
  'landing-pages': renderTimelineCard,
  'site-history': renderTimelineCard,
  'mini-games': renderMiniGameCard
};

const TIMELINE_CATEGORIES = new Set(['landing-pages', 'site-history', 'websites', 'web-systems']);

const projectGrids = document.querySelectorAll('[id^="toolsGrid-"]');
const projectCategories = [...new Set(Array.from(projectGrids).map(g => g.dataset.category || g.id.replace('toolsGrid-', '')))];

Promise.all(projectCategories.map(category =>
  fetch(`json/${category}.json`)
    .then(res => {
      if(!res.ok) throw new Error(`${res.status} ${res.statusText}`);
      return res.json();
    })
    .then(items => [category, items])
    .catch(err => {
      console.error(`Could not load json/${category}.json`, err);
      return [category, []];
    })
)).then(results => {
  const byCategory = Object.fromEntries(results);
  projectGrids.forEach(grid=>{
    const category = grid.dataset.category || grid.id.replace('toolsGrid-', '');
    const items = byCategory[category];
    if(!items || !items.length){
      grid.innerHTML = `<div class="grid-empty-note">No entries yet — add one to json/${category}.json.</div>`;
      return;
    }
    const renderer = CATEGORY_RENDERERS[category] || renderToolCard;
    const sortedItems = TIMELINE_CATEGORIES.has(category)
      ? [...items].sort((a, b) => (b.year || 0) - (a.year || 0))
      : items;
    grid.innerHTML = sortedItems.map((item, i) => {
      const prev = sortedItems[i - 1];
      const sameYearAsPrevious = TIMELINE_CATEGORIES.has(category) && !!prev && prev.year === item.year;
      return renderer(item, sameYearAsPrevious, category);
    }).join('');
  });
  document.querySelectorAll('.tool-count').forEach(el=>{
    const categories = el.dataset.countCategory.split(',');
    el.textContent = categories.reduce((sum, category) => sum + (byCategory[category] || []).length, 0);
  });
  document.querySelectorAll('.meta[data-meta-category]').forEach(el=>{
    const categories = el.dataset.metaCategory.split(',');
    const titles = categories.flatMap(category => (byCategory[category] || []).map(item => item.title));
    if(!titles.length) return;
    el.textContent = titles.length <= 2
      ? titles.join(' · ')
      : `${titles[0]} · ${titles[1]} · +${titles.length - 2} more`;
  });
  setupWebsiteHoverGifs();
  renderExplorer();
  WEBSITE_STYLE_CATEGORIES.forEach(category => {
    websiteItemsByCategory[category] = byCategory[category] || [];
    renderWebsiteCompanyFilters(category, websiteItemsByCategory[category]);
    applyWebsiteFilters(category);
  });
  if(WEBSITE_STYLE_CATEGORIES.includes(activeId)) renderWebsiteDetail(activeId);
  const allProjects = Object.entries(byCategory).flatMap(([category, items]) =>
    (items || []).map(item => ({ ...item, category })));
  const chipProjects = allProjects.filter(p => !p.linkTo);
  renderExperienceProjects(chipProjects);
  renderFreelanceProjects(chipProjects);
  renderIntroProjectPreview(byCategory);
  buildSearchIndex(allProjects);
  if(pendingToolRouteId && tryOpenToolTabRoute(pendingToolRouteId)){
    if(wantsAllToolsInCategory()){
      openCategorySiblings(pendingToolRouteId);
      collapseFoldersExceptCurrent(pendingToolRouteId);
    }
    pendingToolRouteId = null;
    renderTabs();
    renderExplorer();
    showActivePanel();
  } else {
    pendingToolRouteId = null;
  }
  revealAfterToolRoute();
});

function projectChipHtml(p){
  const companySlug = WEBSITE_STYLE_CATEGORIES.includes(p.category) ? slugify(websiteFilterCompany(p.company)) : '';
  return `
    <button class="exp-project-chip" onclick="goToProject('${p.category}','${p.id}','${companySlug}')">
      ${p.icon ? `<span class="ic-emoji">${escapeHtml(p.icon)}</span>` : ''}<span>${escapeHtml(p.title)}</span>
    </button>`;
}

function renderExperienceProjects(allProjects){
  document.querySelectorAll('#panel-experience .commit[data-company]').forEach(commit => {
    const matches = allProjects.filter(p => p.company === commit.dataset.company);
    if(!matches.length) return;
    const details = commit.querySelector('.details');
    if(!details) return;
    details.insertAdjacentHTML('beforeend', `
      <div class="exp-projects">
        <div class="skill-label">Related projects</div>
        <div class="exp-projects-row">${matches.map(projectChipHtml).join('')}</div>
      </div>`);
  });
}

function renderFreelanceProjects(allProjects){
  const row = document.getElementById('freelanceProjectsRow');
  if(!row) return;
  const matches = allProjects.filter(p => p.company === 'Freelance');
  row.innerHTML = matches.map(projectChipHtml).join('');
}

const INTRO_PROJECT_GROUPS = {
  websites: ['websites'],
  'web-systems': ['web-systems'],
  'landing-pages': ['landing-pages'],
  'mini-tools-readme': ['mini-tools-dev', 'mini-tools-media', 'mini-tools-converters', 'mini-tools-generators', 'mini-tools-pdf', 'mini-tools-personal'],
  'edm-tools': ['edm-html-builder', 'edm-kinetic-modules', 'edm-tools'],
  'mini-games': ['mini-games']
};
function introGroupMetaText(items){
  if(!items.length) return '';
  const featured = items.filter(p => p.featured);
  const rest = items.filter(p => !p.featured);
  const highlights = [...featured, ...rest].slice(0, 2);
  const names = highlights.map(p => p.title).join(' · ');
  const remaining = items.length - highlights.length;
  return remaining > 0 ? `${names} · +${remaining} more` : names;
}
function renderIntroProjectPreview(byCategory){
  document.querySelectorAll('#panel-intro .commit[data-open]').forEach(commit => {
    const groups = INTRO_PROJECT_GROUPS[commit.dataset.open];
    const meta = commit.querySelector('.meta');
    if(!groups || !meta) return;
    const items = groups.flatMap(category => (byCategory[category] || []).filter(p => !p.linkTo));
    if(items.length) meta.textContent = introGroupMetaText(items);
  });
}

const PROJECT_TAB_OVERRIDES = {
  'edm-kinetic-modules': 'edm-tools',
  'edm-html-builder': 'edm-tools',
  'mini-tools-personal': 'mini-tools-readme'
};
function goToProject(category, id, companySlug){
  openFile(PROJECT_TAB_OVERRIDES[category] || category);
  if(WEBSITE_STYLE_CATEGORIES.includes(category) && companySlug) filterWebsitesToCompany(category, companySlug);
  requestAnimationFrame(() => {
    const el = document.getElementById('project-' + id);
    if(!el) return;
    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    el.classList.add('project-flash');
    setTimeout(() => el.classList.remove('project-flash'), 1600);
  });
}

function setupWebsiteHoverGifs(root){
  (root || document).querySelectorAll('.website-thumb-img[data-gif-src]').forEach(img => {
    const thumb = img.closest('.website-thumb');
    if(!thumb) return;
    thumb.addEventListener('mouseenter', () => { img.src = img.dataset.gifSrc; });
    thumb.addEventListener('mouseleave', () => { img.src = img.dataset.staticSrc; });
  });
}

function loadEmbedIframe(embed){
  const iframe = embed.querySelector('iframe[data-src]');
  if(!iframe) return;
  iframe.src = iframe.dataset.src;
  iframe.removeAttribute('data-src');
}

function toggleAllDocs(category, btn){
  const grid = document.querySelector(`[data-category="${category}"]`);
  if(!grid) return;
  const embeds = grid.querySelectorAll('.doc-embed');
  if(!embeds.length) return;
  const allOpen = Array.from(embeds).every(e => e.classList.contains('open'));
  const shouldOpen = !allOpen;
  embeds.forEach(e => {
    e.classList.toggle('open', shouldOpen);
    if(shouldOpen) loadEmbedIframe(e);
  });
  if(btn) btn.innerHTML = shouldOpen ? `${ICON_EYE_OFF_SVG}Collapse all` : `${ICON_EYE_SVG}View all`;
}

function setViewButtonState(btn, isOpen){
  if(!btn) return;
  const icon = btn.querySelector('svg.btn-icon');
  if(icon) icon.outerHTML = isOpen ? ICON_EYE_OFF_SVG : ICON_EYE_SVG;
  const viewLabel = btn.dataset.viewLabel;
  if(!viewLabel) return;
  const textNode = Array.from(btn.childNodes).find(n => n.nodeType === Node.TEXT_NODE && n.textContent.trim());
  if(textNode) textNode.textContent = isOpen ? 'Hide' : viewLabel;
}

function toggleDoc(id){
  const embed = document.getElementById('embed-'+id);
  if(!embed) return;
  const isOpen = embed.classList.toggle('open');
  if(isOpen) loadEmbedIframe(embed);
  document.querySelectorAll(`.doc-btn[onclick="toggleDoc('${id}')"]`).forEach(btn => setViewButtonState(btn, isOpen));
  const group = embed.dataset.group;
  if(group){
    document.querySelectorAll(`.doc-embed[data-group="${group}"]`).forEach(e => {
      const groupId = e.id.replace(/^embed-/, '');
      const groupBtn = document.querySelector(`.doc-btn[onclick^="toggleExclusiveDoc('${groupId}',"]`);
      setViewButtonState(groupBtn, e.classList.contains('open'));
    });
  }
}
function toggleExclusiveDoc(id, group){
  document.querySelectorAll(`.doc-embed[data-group="${group}"]`).forEach(e => {
    if(e.id !== 'embed-'+id) e.classList.remove('open');
  });
  toggleDoc(id);
}
function openInNewWindow(path){
  window.open(path, '_blank', 'noopener');
}
function copyEmail(){
  navigator.clipboard.writeText('contact@brunovidasi.com').then(()=>{
    const btn = document.getElementById('emailCopyBtn');
    const label = btn.querySelector('.copy-btn-label');
    btn.classList.add('copied');
    label.textContent = 'Copied!';
    clearTimeout(btn._copyResetTimer);
    btn._copyResetTimer = setTimeout(()=>{
      btn.classList.remove('copied');
      label.textContent = 'Copy';
    }, 1500);
  });
}

function scrambleReveal(el, stepMs = 28){
  const target = el.dataset.email || el.textContent;
  clearInterval(emailScrambleTimer);

  let step = 0;
  const totalSteps = target.length + 8;

  emailScrambleTimer = setInterval(()=>{
    step++;
    const revealCount = Math.max(0, step - 8);
    let out = '';
    for(let i = 0; i < target.length; i++){
      out += i < revealCount ? target[i] : SCRAMBLE_CHARS[Math.floor(Math.random() * SCRAMBLE_CHARS.length)];
    }
    el.textContent = out;
    if(step >= totalSteps){
      clearInterval(emailScrambleTimer);
      el.textContent = target;
    }
  }, stepMs);
}

if(emailRowEl && emailTextEl){
  emailRowEl.addEventListener('mouseenter', ()=> scrambleReveal(emailTextEl));
}

function startEmailReveal(){
  if(emailRevealedOnce || !emailTextEl) return;
  emailRevealedOnce = true;
  scrambleReveal(emailTextEl);
}

document.getElementById('contactForm').addEventListener('submit', function(e){
  e.preventDefault();

  const form = e.target;
  const btn = document.getElementById('contactSubmitBtn');
  const fb = document.getElementById('contactFormFeedback');
  const success = document.getElementById('contactFormSuccess');
  const originalBtnText = btn.textContent;

  btn.disabled = true;
  btn.textContent = 'Sending…';

  fetch('https://api.web3forms.com/submit', {
    method: 'POST',
    headers: { 'Accept': 'application/json' },
    body: new FormData(form)
  })
    .then(res => res.json())
    .then(json => {
      if (json.success) {
        form.reset();
        form.hidden = true;
        success.hidden = false;
      } else {
        fb.textContent = 'Something went wrong. Please try again or email me directly.';
        fb.className = 'form-fb err';
        fb.style.display = 'inline';
        setTimeout(()=> fb.style.display = 'none', 4000);
      }
    })
    .catch(() => {
      fb.textContent = 'Something went wrong. Please try again or email me directly.';
      fb.className = 'form-fb err';
      fb.style.display = 'inline';
      setTimeout(()=> fb.style.display = 'none', 4000);
    })
    .finally(() => {
      btn.disabled = false;
      btn.textContent = originalBtnText;
    });
});

document.getElementById('contactFormReset').addEventListener('click', function(e){
  e.preventDefault();
  document.getElementById('contactFormSuccess').hidden = true;
  document.getElementById('contactForm').hidden = false;
});

document.getElementById('dotRed').addEventListener('click', ()=>{
  location.href = '/' + location.search;
});
document.getElementById('dotYellow').addEventListener('click', ()=>{
  document.getElementById('app').classList.add('minimized');
  document.getElementById('bgRain').classList.add('show');
  document.querySelector('.bg-overlay').classList.add('show');
  document.getElementById('dockRestore').style.display = 'flex';
  humanTypeSect(document.getElementById('bgOverlayText'), 0.55, ()=>{
    document.getElementById('bgOverlaySign').classList.add('show');
  });
});
function restoreApp(){
  if(!document.getElementById('app').classList.contains('minimized')) return;
  document.getElementById('app').classList.remove('minimized');
  document.getElementById('bgRain').classList.remove('show');
  document.querySelector('.bg-overlay').classList.remove('show');
  document.getElementById('dockRestore').style.display = 'none';
}
document.getElementById('dockRestore').addEventListener('click', restoreApp);

(function(){
  var canvas = document.getElementById('bgRain');
  var ctx = canvas.getContext('2d');
  function resize(){ canvas.width = window.innerWidth; canvas.height = window.innerHeight; }
  resize();
  window.addEventListener('resize', resize);
  var chars = "01";
  var fontSize = 15;
  var cols, drops;
  function setup(){
    cols = Math.floor(canvas.width / fontSize);
    drops = new Array(cols).fill(0).map(function(){ return Math.random() * -50; });
  }
  setup();
  window.addEventListener('resize', setup);
  function draw(){
    ctx.fillStyle = 'rgba(11,14,12,0.14)';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    ctx.fillStyle = '#8fd19e';
    ctx.font = fontSize + 'px monospace';
    for(var i = 0; i < drops.length; i++){
      var ch = chars[Math.floor(Math.random() * chars.length)];
      ctx.fillText(ch, i * fontSize, drops[i] * fontSize);
      drops[i] = (drops[i] * fontSize > canvas.height && Math.random() > 0.975) ? 0 : drops[i] + 1;
    }
  }
  setInterval(draw, 55);
})();
let lockedScrollY = 0;
function setMobileNavLock(locked){
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
function toggleExplorer(){
  const shell = document.getElementById('shell');
  if(window.matchMedia('(max-width: 720px)').matches){
    shell.classList.toggle('mobile-nav-open');
    setMobileNavLock(shell.classList.contains('mobile-nav-open'));
  } else {
    shell.classList.toggle('sidebar-hidden');
  }
}
function hintGreenDot(){
  const dotGreen = document.getElementById('dotGreen');
  dotGreen.classList.add('pulse-hint');
  setTimeout(()=> dotGreen.classList.remove('pulse-hint'), 8000);
}
document.getElementById('dotGreen').addEventListener('click', ()=>{
  document.getElementById('dotGreen').classList.remove('pulse-hint');
  toggleExplorer();
});
document.getElementById('titlebarLogoBtn').addEventListener('click', ()=>{
  openFile('intro');
  document.getElementById('shell').classList.remove('sidebar-hidden');
});
document.getElementById('footerBrunoLink').addEventListener('click', ()=>{
  document.getElementById('shell').classList.remove('sidebar-hidden');
});

(function initExplorerResize(){
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
    if(window.matchMedia('(max-width: 720px)').matches) return;
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

  handle.addEventListener('dblclick', ()=>{
    applyWidth(DEFAULT_WIDTH);
  });
})();

document.getElementById('mobileMenuBtn').addEventListener('click', toggleExplorer);
document.getElementById('explorerClose').addEventListener('click', ()=>{
  document.getElementById('shell').classList.remove('mobile-nav-open');
  setMobileNavLock(false);
});

const explorerMore = document.getElementById('explorerMore');
const explorerMenu = document.getElementById('explorerMenu');
const explorerLabelRow = document.querySelector('.explorer .label-row');
function closeExplorerMenu(){
  explorerMenu.classList.remove('show');
  explorerMore.classList.remove('active');
}
explorerMore.addEventListener('click', (e)=>{
  e.stopPropagation();
  const opening = !explorerMenu.classList.contains('show');
  closeTabContextMenu();
  closeTreeContextMenu();
  if(opening){
    explorerMenu.classList.add('show');
    explorerMore.classList.add('active');
  }
});
explorerLabelRow.addEventListener('contextmenu', (e)=>{
  e.preventDefault();
  e.stopPropagation();
  closeTabContextMenu();
  closeTreeContextMenu();
  explorerMenu.classList.add('show');
  explorerMore.classList.add('active');
});
document.getElementById('expandAllFoldersBtn').addEventListener('click', ()=>{
  setAllFolders(true);
  closeExplorerMenu();
});
document.getElementById('collapseAllFoldersBtn').addEventListener('click', ()=>{
  setAllFolders(false);
  closeExplorerMenu();
});
document.getElementById('closeAllTabsBtn').addEventListener('click', ()=>{
  closeAllTabs();
  closeExplorerMenu();
});
document.getElementById('closeOtherTabsBtn').addEventListener('click', ()=>{
  closeOtherTabs();
  closeExplorerMenu();
});
document.getElementById('hideExplorerBtn').addEventListener('click', ()=>{
  toggleExplorer();
  closeExplorerMenu();
});
document.addEventListener('click', (e)=>{
  if(!explorerMenu.classList.contains('show')) return;
  if(explorerMenu.contains(e.target) || explorerMore.contains(e.target)) return;
  closeExplorerMenu();
});
// Capture phase runs before any element's own contextmenu handler, so any
// menu left open from a previous right-click is closed before a new one
// (or the browser's native menu, e.g. on empty body) takes its place.
document.addEventListener('contextmenu', ()=>{
  closeExplorerMenu();
  closeTabContextMenu();
  closeTreeContextMenu();
}, true);

const tabContextMenu = document.getElementById('tabContextMenu');
let tabContextMenuId = null;

function closeTabContextMenu(){
  tabContextMenu.classList.remove('show');
  tabContextMenuId = null;
}

function isSidebarHidden(){
  const shell = document.getElementById('shell');
  return window.matchMedia('(max-width: 720px)').matches
    ? !shell.classList.contains('mobile-nav-open')
    : shell.classList.contains('sidebar-hidden');
}

function openTabContextMenu(x, y, id){
  tabContextMenuId = id;
  const idx = openTabs.indexOf(id);
  document.getElementById('ctxCloseOthers').toggleAttribute('disabled', openTabs.length < 2);
  document.getElementById('ctxCloseRight').toggleAttribute('disabled', idx === -1 || idx >= openTabs.length - 1);
  document.getElementById('ctxFullscreen').textContent = isSidebarHidden() ? 'Show Explorer' : 'Fullscreen';
  tabContextMenu.classList.add('show');
  const menuRect = tabContextMenu.getBoundingClientRect();
  const maxX = window.innerWidth - menuRect.width - 4;
  const maxY = window.innerHeight - menuRect.height - 4;
  tabContextMenu.style.left = Math.max(4, Math.min(x, maxX)) + 'px';
  tabContextMenu.style.top = Math.max(4, Math.min(y, maxY)) + 'px';
}

document.getElementById('ctxCloseTab').addEventListener('click', ()=>{
  if(tabContextMenuId) closeTab(tabContextMenuId);
  closeTabContextMenu();
});
document.getElementById('ctxCloseOthers').addEventListener('click', ()=>{
  if(tabContextMenuId) closeOtherTabs(tabContextMenuId);
  closeTabContextMenu();
});
document.getElementById('ctxCloseRight').addEventListener('click', ()=>{
  if(tabContextMenuId) closeTabsToTheRight(tabContextMenuId);
  closeTabContextMenu();
});
document.getElementById('ctxCloseAll').addEventListener('click', ()=>{
  closeAllTabs();
  closeTabContextMenu();
});
document.getElementById('ctxFullscreen').addEventListener('click', ()=>{
  toggleExplorer();
  closeTabContextMenu();
});
document.getElementById('ctxOpenNewWindow').addEventListener('click', ()=>{
  if(tabContextMenuId) window.open(urlForTabId(tabContextMenuId), '_blank');
  closeTabContextMenu();
});
document.addEventListener('click', (e)=>{
  if(!tabContextMenu.classList.contains('show')) return;
  if(tabContextMenu.contains(e.target)) return;
  closeTabContextMenu();
});

const treeContextMenu = document.getElementById('treeContextMenu');
let treeContextMenuId = null;

function closeTreeContextMenu(){
  treeContextMenu.classList.remove('show');
  treeContextMenuId = null;
}

function openTreeContextMenu(x, y, id){
  treeContextMenuId = id;
  const tool = TOOL_TAB_REGISTRY[id];
  document.getElementById('ctxTreeQuickView').hidden = !tool;
  document.getElementById('ctxTreeGithub').hidden = !(tool && tool.github);
  document.getElementById('ctxTreeCodepen').hidden = !(tool && tool.codepen);
  document.getElementById('ctxTreeLinksDivider').hidden = !(tool && (tool.github || tool.codepen));
  closeExplorerMenu();
  closeTabContextMenu();
  treeContextMenu.classList.add('show');
  const menuRect = treeContextMenu.getBoundingClientRect();
  const maxX = window.innerWidth - menuRect.width - 4;
  const maxY = window.innerHeight - menuRect.height - 4;
  treeContextMenu.style.left = Math.max(4, Math.min(x, maxX)) + 'px';
  treeContextMenu.style.top = Math.max(4, Math.min(y, maxY)) + 'px';
}

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

document.getElementById('ctxTreeQuickView').addEventListener('click', ()=>{
  if(treeContextMenuId) quickViewTreeItem(treeContextMenuId);
  closeTreeContextMenu();
});
document.getElementById('ctxTreeOpen').addEventListener('click', ()=>{
  if(treeContextMenuId) openTreeItem(treeContextMenuId);
  closeTreeContextMenu();
});
document.getElementById('ctxTreeOpenNewTab').addEventListener('click', ()=>{
  if(treeContextMenuId) window.open(urlForTabId(treeContextMenuId), '_blank');
  closeTreeContextMenu();
});
document.getElementById('ctxTreeGithub').addEventListener('click', ()=>{
  const tool = treeContextMenuId && TOOL_TAB_REGISTRY[treeContextMenuId];
  if(tool && tool.github) window.open(tool.github, '_blank', 'noopener');
  closeTreeContextMenu();
});
document.getElementById('ctxTreeCodepen').addEventListener('click', ()=>{
  const tool = treeContextMenuId && TOOL_TAB_REGISTRY[treeContextMenuId];
  if(tool && tool.codepen) window.open(tool.codepen, '_blank', 'noopener');
  closeTreeContextMenu();
});
document.addEventListener('click', (e)=>{
  if(!treeContextMenu.classList.contains('show')) return;
  if(treeContextMenu.contains(e.target)) return;
  closeTreeContextMenu();
});
document.getElementById('fileTree').addEventListener('scroll', closeTreeContextMenu);
window.addEventListener('resize', closeTreeContextMenu);

function toggleIntroCodeComment(){
  const lines = document.querySelectorAll('#panel-intro .code-line');
  if(!lines.length) return;
  introCodeCommented = !introCodeCommented;
  lines.forEach(line => {
    if(line.querySelector('.com')) return;
    line.classList.toggle('line-commented', introCodeCommented);
  });
}
let introCodeCommented = false;

document.addEventListener('keydown', (e)=>{
  const mod = e.metaKey || e.ctrlKey;

  if(e.key === '?' && !mod && !isTypingTarget(e.target)){
    e.preventDefault();
    openShortcutsModal();
    return;
  }
  // VS Code's integrated-terminal toggle is Ctrl+` on every platform, Cmd included.
  if(e.ctrlKey && !e.shiftKey && !e.altKey && e.key === '`'){
    e.preventDefault();
    toggleBottomPanel('terminal');
    return;
  }
  if(mod && !e.shiftKey && e.key.toLowerCase() === 'p'){
    e.preventDefault();
    openQuickOpen();
    return;
  }
  if(mod && e.shiftKey && e.key.toLowerCase() === 'p'){
    e.preventDefault();
    openCommandPalette();
    return;
  }
  if(mod && e.shiftKey && e.key.toLowerCase() === 'f'){
    e.preventDefault();
    openWorkspaceSearch();
    return;
  }
  if(mod && e.shiftKey && e.key.toLowerCase() === 'e'){
    e.preventDefault();
    focusExplorer();
    return;
  }
  if(mod && e.shiftKey && e.key.toLowerCase() === 'm'){
    e.preventDefault();
    toggleBottomPanel('problems');
    return;
  }
  if(mod && !e.shiftKey && e.key.toLowerCase() === 'b'){
    e.preventDefault();
    toggleExplorer();
    return;
  }
  if(mod && !e.shiftKey && e.key.toLowerCase() === 'w'){
    e.preventDefault();
    if(activeId) closeTab(activeId);
    return;
  }
  if(mod && e.key === 'Tab'){
    e.preventDefault();
    cycleTabs(e.shiftKey ? -1 : 1);
    return;
  }
  if(mod && !e.shiftKey && /^[1-9]$/.test(e.key)){
    e.preventDefault();
    jumpToTabIndex(Number(e.key) - 1);
    return;
  }
  if(mod && !e.shiftKey && e.key === '/'){
    if(activeId === 'intro'){
      e.preventDefault();
      toggleIntroCodeComment();
    }
    return;
  }
  if(e.key === 'Escape'){
    if(isQuickOpenOpen()) closeQuickOpen();
    else if(isCommandPaletteOpen()) closeCommandPalette();
    else if(isShortcutsModalOpen()) closeShortcutsModal();
    else closeTabContextMenu();
  }
});
document.getElementById('tabBar').addEventListener('scroll', closeTabContextMenu);
window.addEventListener('resize', closeTabContextMenu);
// A click inside a tool tab's iframe never bubbles to this document, so the
// outside-click handler above can't see it; catch it via the focus shift instead.
window.addEventListener('blur', ()=>{
  setTimeout(()=>{
    if(document.activeElement && document.activeElement.tagName === 'IFRAME') closeTabContextMenu();
  }, 0);
});

// ==== Bottom Panel: Terminal (Ctrl+`) + Problems (Ctrl/Cmd+Shift+M) ====

const bottomPanel = document.getElementById('bottomPanel');
const bottomPanelTabs = document.querySelectorAll('.bottom-panel-tab');
const bottomPanelTerminal = document.getElementById('bottomPanelTerminal');
const bottomPanelProblems = document.getElementById('bottomPanelProblems');
const terminalLog = document.getElementById('terminalLog');
const terminalInput = document.getElementById('terminalInput');
const terminalPromptEl = document.querySelector('.terminal-prompt');

function isBottomPanelOpen(){
  return !bottomPanel.hidden;
}

function activeBottomPanelTab(){
  const active = document.querySelector('.bottom-panel-tab.active');
  return active ? active.dataset.panel : 'terminal';
}

function switchBottomPanelTab(tab){
  bottomPanelTabs.forEach(btn => btn.classList.toggle('active', btn.dataset.panel === tab));
  bottomPanelTerminal.hidden = tab !== 'terminal';
  bottomPanelProblems.hidden = tab !== 'problems';
  if(tab === 'terminal'){
    bootTerminal();
    terminalInput.focus();
  }
}

const bottomPanelResizeHandle = document.getElementById('bottomPanelResizeHandle');

function openBottomPanel(tab){
  bottomPanel.hidden = false;
  bottomPanelResizeHandle.hidden = false;
  switchBottomPanelTab(tab);
  repositionActiveToolTabFrame();
}

function closeBottomPanel(){
  bottomPanel.hidden = true;
  bottomPanelResizeHandle.hidden = true;
  repositionActiveToolTabFrame();
}

function toggleBottomPanel(tab){
  if(isBottomPanelOpen() && activeBottomPanelTab() === tab) closeBottomPanel();
  else openBottomPanel(tab);
}

bottomPanelTabs.forEach(btn => btn.addEventListener('click', ()=> switchBottomPanelTab(btn.dataset.panel)));
document.getElementById('bottomPanelCloseBtn').addEventListener('click', closeBottomPanel);
document.getElementById('terminalBtn').addEventListener('click', ()=> toggleBottomPanel('terminal'));

(function initBottomPanelResize(){
  const handle = bottomPanelResizeHandle;
  const overlay = document.getElementById('bottomPanelResizeOverlay');
  if(!bottomPanel || !handle || !overlay) return;

  const MIN_HEIGHT = 120;
  const MAX_HEIGHT = 560;
  const DEFAULT_HEIGHT = 220;

  function applyHeight(height){
    bottomPanel.style.setProperty('--bottom-panel-height', height + 'px');
    repositionActiveToolTabFrame();
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

  // Native dblclick can't be used to detect a double-click here: the overlay
  // it activates on mousedown covers the handle, so the matching mouseup
  // lands on the overlay instead of the handle and the browser never sees a
  // same-target click/dblclick pair. Time consecutive mousedowns ourselves.
  let lastDownTime = 0;
  handle.addEventListener('mousedown', (e)=>{
    if(window.matchMedia('(max-width: 720px)').matches) return;
    const now = Date.now();
    if(now - lastDownTime < 350){
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
  }
  overlay.addEventListener('mouseup', endDrag);
  // A fast drag released near the bottom edge of the window can put the
  // cursor outside the viewport, where the overlay itself is no longer the
  // event target — fall back to window's mouseup so the drag never gets
  // stuck "on" with the resize cursor and overlay left intercepting clicks.
  window.addEventListener('mouseup', endDrag);
})();

function printTerminalLine(html){
  const line = document.createElement('div');
  line.className = 't-line';
  line.innerHTML = html;
  terminalLog.appendChild(line);
  terminalLog.scrollTop = terminalLog.scrollHeight;
}

function bootTerminal(){
  if(terminalLog.dataset.booted) return;
  terminalLog.dataset.booted = '1';
  printTerminalLine(`<span class="t-out">Bruno's portfolio shell — type 'help' to see what's here.</span>`);
}

const TERMINAL_SOCIAL_LINKS = {
  github: 'https://github.com/brunovidasi',
  linkedin: 'https://www.linkedin.com/in/brunovidasi/?locale=en_US',
  codepen: 'https://codepen.io/brunovidasi'
};

// Resolves a typed name to a real, currently-navigable id — driven entirely by
// the live files/folders/TOOL_TAB_REGISTRY data (same source the Explorer and
// Quick Open use), so it stays correct as pages/tools are added or renamed
// instead of needing its own hardcoded route table.
function resolveNavTarget(name){
  if(!name) return null;
  if(TOOL_TAB_REGISTRY[name]) return name;
  if(files[name]) return name;
  if(folders[name] && FOLDER_DEFAULT_FILE[name]) return FOLDER_DEFAULT_FILE[name];

  const lower = name.toLowerCase();
  const byFile = Object.keys(files).find(id => !files[id].isToolTab && files[id].label.replace(/\.[^.]+$/, '').toLowerCase() === lower);
  if(byFile) return byFile;

  const byFolder = Object.keys(folders).find(id => folders[id].label.replace(/\/$/, '').toLowerCase() === lower);
  if(byFolder && FOLDER_DEFAULT_FILE[byFolder]) return FOLDER_DEFAULT_FILE[byFolder];

  const byTool = Object.keys(TOOL_TAB_REGISTRY).find(id => (TOOL_TAB_REGISTRY[id].title || '').toLowerCase() === lower);
  if(byTool) return byTool;

  return null;
}

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

// Tracks which folder the terminal is "inside" so ls/cd can resolve names
// relative to it (root = empty path). Only two levels deep exist (e.g.
// mini-tools -> mini-tools-dev), so a plain array of folder ids is enough.
let terminalCwdPath = [];

function terminalCwdFolderId(){
  return terminalCwdPath.length ? terminalCwdPath[terminalCwdPath.length - 1] : null;
}

function terminalDirChildren(){
  const cur = terminalCwdFolderId();
  return cur ? folderChildren(cur) : rootOrder;
}

function terminalPromptPath(){
  if(!terminalCwdPath.length) return '~';
  return '~/' + terminalCwdPath.map(id => folders[id].label.replace(/\/$/, '')).join('/');
}

function updateTerminalPromptEl(){
  if(terminalPromptEl) terminalPromptEl.textContent = `visitor@bruno-dev:${terminalPromptPath()}$`;
}

function folderPathTo(folderId){
  return MINI_TOOL_CATEGORY_IDS.has(folderId) ? ['mini-tools', folderId] : [folderId];
}

function folderDefaultFile(folderId){
  return FOLDER_DEFAULT_FILE[folderId] || (files[folderId] ? folderId : null);
}

function findInChildren(children, name){
  const lower = name.toLowerCase();
  return children.find(id => {
    if(id === name) return true;
    if(folders[id]) return folders[id].label.replace(/\/$/, '').toLowerCase() === lower;
    if(files[id]) return files[id].label.toLowerCase() === lower || files[id].label.replace(/\.[^.]+$/, '').toLowerCase() === lower;
    if(TOOL_TAB_REGISTRY[id]) return (TOOL_TAB_REGISTRY[id].title || '').toLowerCase() === lower;
    return false;
  });
}

function resolveFolderId(name, scopeChildren){
  const inScope = scopeChildren.find(id => folders[id] && (id === name || folders[id].label.replace(/\/$/, '').toLowerCase() === name.toLowerCase()));
  if(inScope) return inScope;
  return Object.keys(folders).find(id => id === name || folders[id].label.replace(/\/$/, '').toLowerCase() === name.toLowerCase()) || null;
}

function dirLabels(children){
  return children.map(id => files[id] ? files[id].label : (TOOL_TAB_REGISTRY[id] ? TOOL_TAB_REGISTRY[id].title + '.js' : rootLabel(id)));
}

function runTerminalCommand(raw){
  const input = raw.trim();
  printTerminalLine(`<span class="t-prompt">visitor@bruno-dev:${escapeHtml(terminalPromptPath())}$</span> <span class="t-out">${escapeHtml(input)}</span>`);
  if(!input) return;
  const [cmd, ...rest] = input.split(/\s+/);
  const arg = rest.join(' ');
  const lower = cmd.toLowerCase();
  switch(lower){
    case 'help':
      printTerminalLine(`<span class="t-out">Commands: help, ls [dir], cd/cat/open &lt;page&gt;, cd .., shortcuts, whoami, about, resume, contact, github, linkedin, codepen, echo &lt;text&gt;, date, clear, exit</span>`);
      break;
    case 'ls': {
      if(!arg){
        const labels = terminalCwdFolderId() ? dirLabels(terminalDirChildren()) : rootOrder.map(rootLabel);
        printTerminalLine(`<span class="t-out">${escapeHtml(labels.join('  '))}</span>`);
        break;
      }
      const folderId = resolveFolderId(arg, terminalDirChildren());
      if(!folderId){
        printTerminalLine(`<span class="t-err">ls: ${escapeHtml(arg)}: No such directory</span>`);
        break;
      }
      printTerminalLine(`<span class="t-out">${escapeHtml(dirLabels(folderChildren(folderId)).join('  '))}</span>`);
      break;
    }
    case 'cd': {
      if(!arg || arg === '~' || arg === '/'){
        terminalCwdPath = [];
        printTerminalLine(`<span class="t-out">→ ${escapeHtml(files.intro.label)}</span>`);
        openFile('intro');
        break;
      }
      if(arg === '..'){
        if(!terminalCwdPath.length){
          printTerminalLine(`<span class="t-err">cd: already at top-level directory</span>`);
          break;
        }
        terminalCwdPath.pop();
        printTerminalLine(`<span class="t-out">→ ${escapeHtml(terminalPromptPath())}</span>`);
        break;
      }
      const scopeChildren = terminalDirChildren();
      const folderId = resolveFolderId(arg, scopeChildren);
      if(folderId){
        terminalCwdPath = folderPathTo(folderId);
        const defaultFile = folderDefaultFile(folderId);
        printTerminalLine(`<span class="t-out">→ ${escapeHtml(defaultFile ? openNavTarget(defaultFile) : terminalPromptPath())}</span>`);
        break;
      }
      const target = findInChildren(scopeChildren, arg) || resolveNavTarget(arg);
      if(target && (files[target] || TOOL_TAB_REGISTRY[target])){
        printTerminalLine(`<span class="t-out">→ ${escapeHtml(openNavTarget(target))}</span>`);
      } else {
        printTerminalLine(`<span class="t-err">cd: ${escapeHtml(arg)}: No such file or directory</span>`);
      }
      break;
    }
    case 'cat':
    case 'open': {
      if(!arg){
        printTerminalLine(`<span class="t-out">→ ${escapeHtml(files.intro.label)}</span>`);
        openFile('intro');
        break;
      }
      const target = findInChildren(terminalDirChildren(), arg) || resolveNavTarget(arg);
      if(target && (files[target] || TOOL_TAB_REGISTRY[target])){
        printTerminalLine(`<span class="t-out">→ ${escapeHtml(openNavTarget(target))}</span>`);
      } else {
        printTerminalLine(`<span class="t-err">${lower}: ${escapeHtml(arg)}: No such file or directory</span>`);
      }
      break;
    }
    case 'whoami':
      printTerminalLine(`<span class="t-out">you: a curious visitor who found the hidden terminal. nice work 👀</span>`);
      break;
    case 'about':
      printTerminalLine(`<span class="t-out">Sydney-based developer, ${yearsExperience}+ years experience. Run 'cat about' for the full page.</span>`);
      break;
    case 'resume':
    case 'cv':
      printTerminalLine(`<span class="t-out">opening documents.pdf...</span>`);
      openFile('documents');
      break;
    case 'contact':
      printTerminalLine(`<span class="t-out">opening contact.eml...</span>`);
      openFile('contact');
      break;
    case 'shortcuts':
      printTerminalLine(`<span class="t-out">opening keyboard shortcuts...</span>`);
      openShortcutsModal('shortcuts');
      break;
    case 'github':
    case 'linkedin':
    case 'codepen':
      printTerminalLine(`<span class="t-out">${TERMINAL_SOCIAL_LINKS[lower]}</span>`);
      window.open(TERMINAL_SOCIAL_LINKS[lower], '_blank', 'noopener');
      break;
    case 'echo':
      printTerminalLine(`<span class="t-out">${escapeHtml(arg)}</span>`);
      break;
    case 'date':
      printTerminalLine(`<span class="t-out">${escapeHtml(new Date().toString())}</span>`);
      break;
    case 'sudo':
      printTerminalLine(`<span class="t-err">Permission denied: nice try 😏</span>`);
      break;
    case 'coffee':
      printTerminalLine(`<span class="t-out">☕ brewing... still faster than IE6.</span>`);
      break;
    case 'clear':
      terminalLog.innerHTML = '';
      break;
    case 'exit':
    case 'close':
      closeBottomPanel();
      break;
    default:
      printTerminalLine(`<span class="t-err">command not found: ${escapeHtml(cmd)}</span>`);
  }
  updateTerminalPromptEl();
}

terminalInput.addEventListener('keydown', (e)=>{
  if(e.key === 'Enter'){
    const value = terminalInput.value;
    terminalInput.value = '';
    runTerminalCommand(value);
  }
});

// ==== Quick Open (Ctrl/Cmd+P) + workspace Search (Ctrl/Cmd+Shift+F) ====
// One shared index built once the project JSON has loaded: QUICK_OPEN_INDEX
// (coarse, title-only, fuzzy-matched) and CONTENT_INDEX (fine-grained text
// blocks, substring-matched). Both resolve to the same navigateToHit(): open
// the target's panel, then scroll/flash the specific element if there is one.

const PROJECT_PANEL_OVERRIDES = { 'edm-html-builder': 'edm-tools', 'edm-kinetic-modules': 'edm-tools' };
const PROJECT_TEXT_FIELDS = ['title', 'description', 'role', 'extendedDescription', 'company', 'challenge', 'technique', 'outcome'];

let QUICK_OPEN_INDEX = [];
let CONTENT_INDEX = [];

function panelIdForCategory(category){
  return PROJECT_PANEL_OVERRIDES[category] || category;
}

function addContentBlock(panelId, rawText, el){
  const text = (rawText || '').replace(/\s+/g, ' ').trim();
  if(!text) return;
  CONTENT_INDEX.push({ panelId, text, textLower: text.toLowerCase(), el: el || null });
}

// Adjacent elements (e.g. back-to-back .stack-pill spans) have no whitespace
// between them in the source HTML, so plain .textContent runs them together
// ("HTML5CSS3jQuery"). Insert a separating space after each element's text.
function extractText(el){
  let out = '';
  el.childNodes.forEach(node => {
    if(node.nodeType === Node.TEXT_NODE) out += node.textContent;
    else if(node.nodeType === Node.ELEMENT_NODE) out += extractText(node) + ' ';
  });
  return out;
}

function scrapeStaticContent(){
  const experiencePanel = document.getElementById('panel-experience');
  if(experiencePanel) experiencePanel.querySelectorAll(':scope > .commit').forEach(el => addContentBlock('experience', extractText(el), el));

  const educationPanel = document.getElementById('panel-education');
  if(educationPanel) educationPanel.querySelectorAll(':scope > .commit').forEach(el => addContentBlock('education', extractText(el), el));

  const skillsPanel = document.getElementById('panel-skills');
  if(skillsPanel) skillsPanel.querySelectorAll('.cv-skill-line').forEach(el => addContentBlock('skills', extractText(el), el));

  const freelancePanel = document.getElementById('panel-freelance');
  if(freelancePanel) freelancePanel.querySelectorAll('.bio-text, .bio-list li').forEach(el => addContentBlock('freelance', extractText(el), el));

  const documentsPanel = document.getElementById('panel-documents');
  if(documentsPanel) documentsPanel.querySelectorAll('.doc-card').forEach(el => addContentBlock('documents', extractText(el), el));

  addContentBlock('about', bioText, document.getElementById('bioTypedText'));
}

function buildSearchIndex(allProjects){
  CONTENT_INDEX = [];
  QUICK_OPEN_INDEX = [];

  Object.keys(files).forEach(id => {
    const f = files[id];
    if(f.isToolTab) return;
    QUICK_OPEN_INDEX.push({
      title: f.label,
      meta: f.folder && folders[f.folder] ? folders[f.folder].label : '',
      iconHtml: fileIconHtml(f.icon),
      panelId: id,
      el: null
    });
  });

  scrapeStaticContent();

  allProjects.forEach(project => {
    const panelId = panelIdForCategory(project.category);
    if(!files[panelId]) return;
    const el = document.getElementById('project-' + project.id);
    const text = PROJECT_TEXT_FIELDS.map(k => project[k]).filter(Boolean)
      .concat(Array.isArray(project.tech) ? project.tech : [])
      .join(' — ');
    addContentBlock(panelId, text, el);

    QUICK_OPEN_INDEX.push({
      title: project.title,
      meta: files[panelId].label,
      iconHtml: project.icon ? `<span class="file-icon" style="font-family:var(--mono,monospace);font-size:13px;color:#519aba">${escapeHtml(project.icon)}</span>` : fileIconHtml('js'),
      panelId,
      el
    });
  });
}

function navigateToHit(hit){
  restoreApp();
  openFile(hit.panelId);
  if(!hit.el) return;
  setTimeout(()=>{
    hit.el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    hit.el.classList.remove('search-flash');
    void hit.el.offsetWidth;
    hit.el.classList.add('search-flash');
    setTimeout(()=> hit.el.classList.remove('search-flash'), 1600);
  }, 60);
}

// ---- Quick Open ----

function fuzzyMatch(query, text){
  const q = query.toLowerCase();
  const t = text.toLowerCase();
  let qi = 0, streak = 0, score = 0;
  const indices = [];
  for(let ti = 0; ti < t.length && qi < q.length; ti++){
    if(t[ti] === q[qi]){
      indices.push(ti);
      streak++;
      score += 2 + streak * 2 + (ti === 0 || t[ti - 1] === ' ' || t[ti - 1] === '-' ? 3 : 0);
      qi++;
    } else {
      streak = 0;
    }
  }
  if(qi < q.length) return null;
  return { score: score - t.length * 0.01, indices };
}

function highlightIndices(text, indices){
  if(!indices.length) return escapeHtml(text);
  const idxSet = new Set(indices);
  let out = '';
  for(let i = 0; i < text.length; i++){
    const ch = escapeHtml(text[i]);
    out += idxSet.has(i) ? `<span class="quick-open-match">${ch}</span>` : ch;
  }
  return out;
}

const quickOpenBackdrop = document.getElementById('quickOpenBackdrop');
const quickOpenInput = document.getElementById('quickOpenInput');
const quickOpenResultsEl = document.getElementById('quickOpenResults');
let quickOpenRows = [];
let quickOpenSelected = 0;

function isQuickOpenOpen(){
  return quickOpenBackdrop.classList.contains('show');
}

function openQuickOpen(){
  quickOpenBackdrop.classList.add('show');
  quickOpenInput.value = '';
  renderQuickOpenResults('');
  quickOpenInput.focus();
}

function closeQuickOpen(){
  quickOpenBackdrop.classList.remove('show');
}

function renderQuickOpenResults(query){
  const q = query.trim();
  let matches;
  if(!q){
    matches = QUICK_OPEN_INDEX.slice(0, 50).map(item => ({ item, indices: [] }));
  } else {
    matches = QUICK_OPEN_INDEX
      .map(item => {
        const m = fuzzyMatch(q, item.title);
        return m ? { item, indices: m.indices, score: m.score } : null;
      })
      .filter(Boolean)
      .sort((a, b) => b.score - a.score)
      .slice(0, 50);
  }
  quickOpenRows = matches;
  quickOpenSelected = 0;
  if(!matches.length){
    quickOpenResultsEl.innerHTML = '<div class="quick-open-empty">No matching files found</div>';
    return;
  }
  quickOpenResultsEl.innerHTML = matches.map((m, i) => `
    <div class="quick-open-row${i === 0 ? ' selected' : ''}" data-index="${i}">
      ${m.item.iconHtml}
      <span class="quick-open-row-title">${highlightIndices(m.item.title, m.indices)}</span>
      <span class="quick-open-row-meta">${escapeHtml(m.item.meta)}</span>
    </div>`).join('');
}

function updateQuickOpenSelection(newIndex){
  const rows = quickOpenResultsEl.querySelectorAll('.quick-open-row');
  if(!rows.length) return;
  quickOpenSelected = Math.max(0, Math.min(newIndex, rows.length - 1));
  rows.forEach((r, i) => r.classList.toggle('selected', i === quickOpenSelected));
  rows[quickOpenSelected].scrollIntoView({ block: 'nearest' });
}

function activateQuickOpenRow(index){
  const row = quickOpenRows[index];
  if(!row) return;
  closeQuickOpen();
  navigateToHit(row.item);
}

quickOpenInput.addEventListener('input', ()=> renderQuickOpenResults(quickOpenInput.value));
quickOpenInput.addEventListener('keydown', (e)=>{
  if(e.key === 'ArrowDown'){ e.preventDefault(); updateQuickOpenSelection(quickOpenSelected + 1); }
  else if(e.key === 'ArrowUp'){ e.preventDefault(); updateQuickOpenSelection(quickOpenSelected - 1); }
  else if(e.key === 'Enter'){ e.preventDefault(); activateQuickOpenRow(quickOpenSelected); }
  else if(e.key === 'Escape'){ e.preventDefault(); closeQuickOpen(); }
});
quickOpenResultsEl.addEventListener('mousemove', (e)=>{
  const row = e.target.closest('.quick-open-row');
  if(row) updateQuickOpenSelection(Number(row.dataset.index));
});
quickOpenResultsEl.addEventListener('click', (e)=>{
  const row = e.target.closest('.quick-open-row');
  if(row) activateQuickOpenRow(Number(row.dataset.index));
});
quickOpenBackdrop.addEventListener('mousedown', (e)=>{
  if(e.target === quickOpenBackdrop) closeQuickOpen();
});
document.getElementById('quickOpenBtn').addEventListener('click', openQuickOpen);

// ---- Command Palette (Ctrl/Cmd+Shift+P) ----

const COMMAND_LIST = [
  { label: 'Go to File...', meta: '⌘P', run: openQuickOpen },
  { label: 'Find in Workspace', meta: '⇧⌘F', run: openWorkspaceSearch },
  { label: 'View: Show Explorer', meta: '⇧⌘E', run: focusExplorer },
  { label: 'View: Toggle Sidebar', meta: '⌘B', run: toggleExplorer },
  { label: 'View: Toggle Terminal', meta: '^`', run: ()=> toggleBottomPanel('terminal') },
  { label: 'View: Toggle Problems', meta: '⇧⌘M', run: ()=> toggleBottomPanel('problems') },
  { label: 'Tab: Close Editor', meta: '⌘W', run: ()=> { if(activeId) closeTab(activeId); } },
  { label: 'Tab: Close Others', run: ()=> closeOtherTabs() },
  { label: 'Tab: Close All Editors', run: closeAllTabs },
  { label: 'Help: How to Use This Site', meta: '?', run: ()=> openShortcutsModal('guide') },
  { label: 'Help: Keyboard Shortcuts', run: ()=> openShortcutsModal('shortcuts') },
  { label: 'Bruno: Open GitHub', run: ()=> window.open('https://github.com/brunovidasi', '_blank', 'noopener') },
  { label: 'Bruno: Open LinkedIn', run: ()=> window.open('https://www.linkedin.com/in/brunovidasi/?locale=en_US', '_blank', 'noopener') },
  { label: 'Bruno: Open CodePen', run: ()=> window.open('https://codepen.io/brunovidasi', '_blank', 'noopener') }
];

const cmdPaletteBackdrop = document.getElementById('cmdPaletteBackdrop');
const cmdPaletteInput = document.getElementById('cmdPaletteInput');
const cmdPaletteResultsEl = document.getElementById('cmdPaletteResults');
let cmdPaletteRows = [];
let cmdPaletteSelected = 0;

function isCommandPaletteOpen(){
  return cmdPaletteBackdrop.classList.contains('show');
}

function openCommandPalette(){
  cmdPaletteBackdrop.classList.add('show');
  cmdPaletteInput.value = '';
  renderCommandPaletteResults('');
  cmdPaletteInput.focus();
}

function closeCommandPalette(){
  cmdPaletteBackdrop.classList.remove('show');
}

function renderCommandPaletteResults(query){
  const q = query.trim();
  let matches;
  if(!q){
    matches = COMMAND_LIST.map(item => ({ item, indices: [] }));
  } else {
    matches = COMMAND_LIST
      .map(item => {
        const m = fuzzyMatch(q, item.label);
        return m ? { item, indices: m.indices, score: m.score } : null;
      })
      .filter(Boolean)
      .sort((a, b) => b.score - a.score);
  }
  cmdPaletteRows = matches;
  cmdPaletteSelected = 0;
  if(!matches.length){
    cmdPaletteResultsEl.innerHTML = '<div class="quick-open-empty">No matching commands</div>';
    return;
  }
  cmdPaletteResultsEl.innerHTML = matches.map((m, i) => `
    <div class="quick-open-row${i === 0 ? ' selected' : ''}" data-index="${i}">
      <span class="quick-open-row-title">${highlightIndices(m.item.label, m.indices)}</span>
      ${m.item.meta ? `<span class="quick-open-row-meta">${escapeHtml(m.item.meta)}</span>` : ''}
    </div>`).join('');
}

function updateCommandPaletteSelection(newIndex){
  const rows = cmdPaletteResultsEl.querySelectorAll('.quick-open-row');
  if(!rows.length) return;
  cmdPaletteSelected = Math.max(0, Math.min(newIndex, rows.length - 1));
  rows.forEach((r, i) => r.classList.toggle('selected', i === cmdPaletteSelected));
  rows[cmdPaletteSelected].scrollIntoView({ block: 'nearest' });
}

function activateCommandPaletteRow(index){
  const row = cmdPaletteRows[index];
  if(!row) return;
  closeCommandPalette();
  row.item.run();
}

cmdPaletteInput.addEventListener('input', ()=> renderCommandPaletteResults(cmdPaletteInput.value));
cmdPaletteInput.addEventListener('keydown', (e)=>{
  if(e.key === 'ArrowDown'){ e.preventDefault(); updateCommandPaletteSelection(cmdPaletteSelected + 1); }
  else if(e.key === 'ArrowUp'){ e.preventDefault(); updateCommandPaletteSelection(cmdPaletteSelected - 1); }
  else if(e.key === 'Enter'){ e.preventDefault(); activateCommandPaletteRow(cmdPaletteSelected); }
  else if(e.key === 'Escape'){ e.preventDefault(); closeCommandPalette(); }
});
cmdPaletteResultsEl.addEventListener('mousemove', (e)=>{
  const row = e.target.closest('.quick-open-row');
  if(row) updateCommandPaletteSelection(Number(row.dataset.index));
});
cmdPaletteResultsEl.addEventListener('click', (e)=>{
  const row = e.target.closest('.quick-open-row');
  if(row) activateCommandPaletteRow(Number(row.dataset.index));
});
cmdPaletteBackdrop.addEventListener('mousedown', (e)=>{
  if(e.target === cmdPaletteBackdrop) closeCommandPalette();
});

// ---- "How to Use This Site" help modal: Guide + Shortcuts tabs (footer link / "?") ----

const IS_MAC = /Mac|iPhone|iPad|iPod/.test(navigator.platform || navigator.userAgent);
const MOD_KEY = IS_MAC ? '⌘' : 'Ctrl';
const ALT_KEY = IS_MAC ? '⌥' : 'Alt';

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

const shortcutsGuideEl = document.getElementById('shortcutsGuide');

function renderGuide(){
  shortcutsGuideEl.innerHTML = GUIDE_GROUPS.map(group => `
    <div class="shortcuts-group-title">${escapeHtml(group.title)}</div>
    ${group.items.map(item => `
      <div class="guide-row">
        <span class="guide-icon${item.dot ? ' ' + item.dot : ''}"></span>
        <span>${item.html}</span>
      </div>`).join('')}
  `).join('');
}
renderGuide();

const shortcutsTabButtons = document.querySelectorAll('.shortcuts-tabs .modal-tab');

function switchShortcutsTab(tab){
  shortcutsTabButtons.forEach(btn => btn.classList.toggle('active', btn.dataset.shortcutsTab === tab));
  shortcutsGuideEl.hidden = tab !== 'guide';
  shortcutsBodyEl.hidden = tab !== 'shortcuts';
}

shortcutsTabButtons.forEach(btn => btn.addEventListener('click', ()=> switchShortcutsTab(btn.dataset.shortcutsTab)));

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

const shortcutsBackdrop = document.getElementById('shortcutsBackdrop');
const shortcutsBodyEl = document.getElementById('shortcutsBody');

function renderShortcutsModal(){
  const groupsHtml = SHORTCUT_GROUPS.map(group => `
    <div class="shortcuts-group-title">${escapeHtml(group.title)}</div>
    ${group.items.map(item => `
      <div class="shortcut-row">
        <span class="shortcut-desc">${escapeHtml(item.desc)}</span>
        <span class="keycap-row">${item.keys.map(k => `<kbd>${escapeHtml(k)}</kbd>`).join('<span class="keycap-plus">+</span>')}${item.through ? `<span class="keycap-plus">–</span><kbd>${escapeHtml(item.through)}</kbd>` : ''}</span>
      </div>`).join('')}
  `).join('');
  shortcutsBodyEl.innerHTML = groupsHtml + `<div class="shortcuts-footnote">${escapeHtml(SHORTCUTS_FOOTNOTE)}</div>`;
}
renderShortcutsModal();

function isShortcutsModalOpen(){
  return shortcutsBackdrop.classList.contains('show');
}

function openShortcutsModal(tab){
  switchShortcutsTab(tab || 'guide');
  shortcutsBackdrop.classList.add('show');
}

function closeShortcutsModal(){
  shortcutsBackdrop.classList.remove('show');
}

document.getElementById('helpBtn').addEventListener('click', ()=> openShortcutsModal());
document.getElementById('shortcutsCloseBtn').addEventListener('click', closeShortcutsModal);
shortcutsBackdrop.addEventListener('mousedown', (e)=>{
  if(e.target === shortcutsBackdrop) closeShortcutsModal();
});

function isTypingTarget(el){
  return !!el && (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA' || el.isContentEditable);
}

// ---- Sidebar workspace search ----

const viewTabButtons = document.querySelectorAll('.view-tab');
const fileTreeEl = document.getElementById('fileTree');
const searchPanelEl = document.getElementById('searchPanel');
const searchPanelInput = document.getElementById('searchPanelInput');
const searchSummaryEl = document.getElementById('searchSummary');
const searchResultsEl = document.getElementById('searchResults');

function switchSidebarView(view){
  viewTabButtons.forEach(btn => btn.classList.toggle('active', btn.dataset.view === view));
  fileTreeEl.hidden = view !== 'explorer';
  searchPanelEl.hidden = view !== 'search';
  document.getElementById('quickOpenBtn').hidden = view !== 'search';
}

viewTabButtons.forEach(btn => btn.addEventListener('click', ()=> switchSidebarView(btn.dataset.view)));

function openWorkspaceSearch(){
  const shell = document.getElementById('shell');
  if(window.matchMedia('(max-width: 720px)').matches){
    if(!shell.classList.contains('mobile-nav-open')){
      shell.classList.add('mobile-nav-open');
      setMobileNavLock(true);
    }
  } else {
    shell.classList.remove('sidebar-hidden');
  }
  switchSidebarView('search');
  searchPanelInput.focus();
}

function focusExplorer(){
  restoreApp();
  const shell = document.getElementById('shell');
  if(window.matchMedia('(max-width: 720px)').matches){
    if(!shell.classList.contains('mobile-nav-open')){
      shell.classList.add('mobile-nav-open');
      setMobileNavLock(true);
    }
  } else {
    shell.classList.remove('sidebar-hidden');
  }
  switchSidebarView('explorer');
}

function truncateSnippet(text, matchIndex, matchLen){
  const RADIUS = 34;
  const start = Math.max(0, matchIndex - RADIUS);
  const end = Math.min(text.length, matchIndex + matchLen + RADIUS);
  const prefixed = start > 0;
  const snippet = (prefixed ? '…' : '') + text.slice(start, end) + (end < text.length ? '…' : '');
  return { snippet, offset: matchIndex - start + (prefixed ? 1 : 0) };
}

function highlightSubstring(text, offset, len){
  return escapeHtml(text.slice(0, offset)) +
    '<span class="search-match-text">' + escapeHtml(text.slice(offset, offset + len)) + '</span>' +
    escapeHtml(text.slice(offset + len));
}

function renderSearchResults(query){
  const q = query.trim().toLowerCase();
  if(!q){
    searchSummaryEl.textContent = '';
    searchResultsEl.innerHTML = '';
    return;
  }
  const hits = CONTENT_INDEX
    .map(entry => ({ entry, matchAt: entry.textLower.indexOf(q) }))
    .filter(h => h.matchAt !== -1);

  if(!hits.length){
    searchSummaryEl.textContent = 'No results found';
    searchResultsEl.innerHTML = '';
    return;
  }

  const order = [];
  const groups = new Map();
  hits.forEach(h => {
    const panelId = h.entry.panelId;
    if(!groups.has(panelId)){ groups.set(panelId, []); order.push(panelId); }
    groups.get(panelId).push(h);
  });

  searchSummaryEl.textContent = `${hits.length} result${hits.length === 1 ? '' : 's'} in ${order.length} file${order.length === 1 ? '' : 's'}`;

  const hitRegistry = [];
  let html = '';
  order.forEach(panelId => {
    const f = files[panelId];
    if(!f) return;
    const groupHits = groups.get(panelId);
    hitRegistry.push({ panelId, el: null });
    html += `<div class="search-result-group">
      <div class="search-result-head" data-hit="${hitRegistry.length - 1}">
        ${fileIconHtml(f.icon)}<span class="search-result-label">${escapeHtml(f.label)}</span>
        <span class="search-result-count">${groupHits.length}</span>
      </div>`;
    groupHits.slice(0, 6).forEach(h => {
      const { snippet, offset } = truncateSnippet(h.entry.text, h.matchAt, q.length);
      hitRegistry.push({ panelId, el: h.entry.el });
      html += `<div class="search-result-snippet" data-hit="${hitRegistry.length - 1}">${highlightSubstring(snippet, offset, q.length)}</div>`;
    });
    html += `</div>`;
  });

  searchResultsEl.innerHTML = html;
  searchResultsEl.querySelectorAll('[data-hit]').forEach(node => {
    node.addEventListener('click', ()=>{
      const hit = hitRegistry[Number(node.dataset.hit)];
      if(hit) navigateToHit(hit);
    });
  });
}

let searchDebounceTimer = null;
searchPanelInput.addEventListener('input', ()=>{
  clearTimeout(searchDebounceTimer);
  searchDebounceTimer = setTimeout(()=> renderSearchResults(searchPanelInput.value), 120);
});

const devModeToggle = document.getElementById('devModeToggle');
if(isLocalhost){
  devModeToggle.classList.toggle('on', isDevMode());
  devModeToggle.textContent = 'DEV: ' + (isDevMode() ? 'ON' : 'OFF');
  devModeToggle.addEventListener('click', ()=>{
    const turningOn = !isDevMode();
    localStorage.setItem('devMode', turningOn ? 'true' : 'false');
    if(turningOn && location.pathname !== '/'){
      location.href = '/' + location.search + (activeId ? '#' + activeId : '');
    } else {
      location.reload();
    }
  });
} else {
  devModeToggle.remove();
}

const bootLines = [
  "$ initializing brunovida.si...",
  "$ mounting /experience ... ok",
  `$ loading ${yearsExperience}+ years of experience ... ok`,
  "$ compiling creativity.module ... ok",
  "$ launching interface_"
];
const bootEl = document.getElementById('bootText');
let li = 0;
function typeBootLine(){
  if(li >= bootLines.length){
    bootEl.innerHTML = bootEl.innerHTML.replace(/_\n$/, '<span class="boot-cursor">_</span>\n');
    setTimeout(showRain, 250);
    return;
  }
  let line = bootLines[li];
  let ci = 0;
  const iv = setInterval(()=>{
    bootEl.textContent = bootEl.textContent.replace(/▍$/,'') + line[ci];
    ci++;
    if(ci >= line.length){
      clearInterval(iv);
      bootEl.textContent += "\n";
      li++;
      setTimeout(typeBootLine, 120);
    }
  }, 18);
}

function showRain(){
  const rain = document.getElementById('rain');
  const canvas = document.createElement('canvas');
  canvas.width = window.innerWidth; canvas.height = window.innerHeight;
  rain.appendChild(canvas);
  rain.classList.add('show');
  const ctx = canvas.getContext('2d');
  const cols = Math.floor(canvas.width/16);
  const drops = new Array(cols).fill(0);
  const chars = "01{}<>/;=()bruno";
  let frames = 0;
  const rainIv = setInterval(()=>{
    ctx.fillStyle = 'rgba(0,0,0,0.15)';
    ctx.fillRect(0,0,canvas.width,canvas.height);
    ctx.fillStyle = '#8fd19e';
    ctx.font = '14px monospace';
    drops.forEach((y,i)=>{
      const ch = chars[Math.floor(Math.random()*chars.length)];
      ctx.fillText(ch, i*16, y*16);
      drops[i] = (y*16 > canvas.height && Math.random() > 0.975) ? 0 : y+1;
    });
    frames++;
    if(frames > 40){
      clearInterval(rainIv);
      document.getElementById('boot').classList.add('hide');
      rain.classList.remove('show');
      const app = document.getElementById('app');
      app.classList.add('show');
      startCyclingTagline();
      setTimeout(()=>document.getElementById('boot').remove(), 700);
    }
  }, 45);
}

function startBioTyping(){
  if(bioTypedOnce) return;
  bioTypedOnce = true;
  const el = document.getElementById('bioTypedText');
  const after = document.getElementById('bioAfter');
  el.textContent = bioText;
  after.classList.add('show');
}

let taglines = [];
const taglinesPromise = fetch('json/taglines.json')
  .then(res => res.json())
  .then(items => { taglines = items.map(t => t.replace('{years}', yearsExperience)); })
  .catch(err => {
    console.error('Could not load json/taglines.json', err);
    taglines = ["I build things that work — and look good doing it."];
  });
let tlOrder = [];
let tlPos = 0;
let tlIndex = 0;
let tlChar = 0;
let tlDeleting = false;
let tlMistakeCooldown = 0;
const TYPE_SPEED = 65;
const DELETE_SPEED = 65;
const READ_PAUSE = 3800;
const NEXT_PAUSE = 500;

function shuffleTaglineOrder(avoidFirst){
  const order = [...Array(taglines.length).keys()];
  for(let i = order.length - 1; i > 0; i--){
    const j = Math.floor(Math.random() * (i + 1));
    [order[i], order[j]] = [order[j], order[i]];
  }
  if(order.length > 1 && order[0] === avoidFirst){
    [order[0], order[1]] = [order[1], order[0]];
  }
  return order;
}

function tickTagline(){
  const el = document.getElementById('heroVariable');
  const current = taglines[tlIndex];

  if(!tlDeleting){
    tlChar++;
    const char = current[tlChar - 1];
    const canMistake = tlMistakeCooldown <= 0 && tlChar < current.length && /[a-zA-Z0-9]/.test(char) && Math.random() < 0.02;

    if(canMistake){
      tlMistakeCooldown = 15;
      el.textContent = current.slice(0, tlChar - 1) + typoFor(char);
      setTimeout(()=>{
        el.textContent = current.slice(0, tlChar - 1);
        setTimeout(()=>{
          el.textContent = current.slice(0, tlChar);
          setTimeout(tickTagline, TYPE_SPEED);
        }, 90 + Math.random() * 80);
      }, 160 + Math.random() * 180);
      return;
    }

    el.textContent = current.slice(0, tlChar);
    if(tlMistakeCooldown > 0) tlMistakeCooldown--;
    if(tlChar >= current.length){
      tlDeleting = true;
      setTimeout(tickTagline, READ_PAUSE);
      return;
    }
    setTimeout(tickTagline, TYPE_SPEED);
  } else {
    tlChar--;
    el.textContent = current.slice(0, tlChar);
    if(tlChar <= 0){
      tlDeleting = false;
      tlPos++;
      if(tlPos >= tlOrder.length){
        tlOrder = shuffleTaglineOrder(tlIndex);
        tlPos = 0;
      }
      tlIndex = tlOrder[tlPos];
      setTimeout(tickTagline, NEXT_PAUSE);
      return;
    }
    setTimeout(tickTagline, DELETE_SPEED);
  }
}

function startCyclingTagline(){
  taglinesPromise.then(()=>{
    tlOrder = shuffleTaglineOrder(-1);
    tlPos = 0;
    tlIndex = tlOrder[0];
    tlChar = 0; tlDeleting = false; tlMistakeCooldown = 0;
    document.getElementById('heroVariable').textContent = '';
    tickTagline();
  });
}

if(activeId === 'about') startBioTyping();

let awaitingToolReveal = false;

if(isDevMode() || enteredViaDeepLink){
  document.getElementById('boot').remove();
  document.getElementById('app').classList.add('show');
  startCyclingTagline();
} else if(pendingToolRouteId){
  // Tool deep-link: the tool tab can't be created yet (it needs the project
  // JSON, still loading below), so keep the boot mask up instead of revealing
  // the default intro panel/explorer first and swapping to the tool after.
  // revealAfterToolRoute() fades it out once the tool tab is actually ready.
  awaitingToolReveal = true;
} else {
  typeBootLine();
}

function revealAfterToolRoute(){
  if(!awaitingToolReveal) return;
  awaitingToolReveal = false;
  const boot = document.getElementById('boot');
  if(boot){
    boot.classList.add('hide');
    boot.addEventListener('transitionend', () => boot.remove(), { once: true });
  }
  document.getElementById('app').classList.add('show');
  startCyclingTagline();
}
