// ---------------- experience counter ----------------
const CAREER_START_YEAR = 2012;
const yearsExperience = new Date().getFullYear() - CAREER_START_YEAR;
document.querySelectorAll('.years-exp').forEach(el=> el.textContent = yearsExperience);
document.querySelectorAll('.current-year').forEach(el=> el.textContent = new Date().getFullYear());

// ---------------- explorer icons (VS Code "Seti" file-icon-theme glyphs) ----------------
const ICON_GLYPHS = {
  js:   { char: '', color: '#cbcb41' },
  md:   { char: '', color: '#ffb454' },
  json: { char: '', color: '#cbcb41' },
  info: { char: '', color: '#519aba' },
  html: { char: '', color: '#ffb454' },
  php:  { char: '', color: '#a074c4' },
  sh:   { char: '', color: '#8dc149' },
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

function folderIconHtml(open){
  return '<span class="file-icon" style="color:' + FOLDER_COLOR + '">' + (open ? FOLDER_OPEN_SVG : FOLDER_CLOSED_SVG) + '</span>';
}

// ---------------- file/folder model ----------------
const files = {
  intro:            { label:'intro.js',            icon:'js',   folder:null },
  about:             { label:'README.md',           icon:'info', folder:'about' },
  experience:        { label:'experience.js',       icon:'js',   folder:'about' },
  education:         { label:'education.md',        icon:'md',   folder:'about' },
  skills:            { label:'skills.json',         icon:'json', folder:'about' },
  websites:          { label:'websites.html',       icon:'html', folder:'projects' },
  'landing-pages':   { label:'landing-pages.html',  icon:'html', folder:'projects' },
  'mini-tools':      { label:'mini-tools.html',     icon:'html', folder:'projects' },
  'edm-tools':       { label:'eDM-tools.html',      icon:'html', folder:'projects' },
  'mini-games':      { label:'mini-games.html',     icon:'html', folder:'projects' },
  'edm-work':        { label:'eDM-work.html',       icon:'html', folder:'projects' },
  'site-history':    { label:'site-history.php',    icon:'php',  folder:'projects' },
  freelance:         { label:'freelance.css',       icon:'css',  folder:null },
  contact:           { label:'contact.sh',          icon:'sh',   folder:null },
  documents:         { label:'documents.pdf',       icon:'pdf',  folder:null }
};
const folders = {
  about:    { label:'about/', children:['about','experience','education','skills'] },
  projects: { label:'projects/', children:['websites','landing-pages','mini-tools','edm-work','edm-tools','mini-games','site-history'] }
};
const rootOrder = ['intro','about','projects','freelance','documents','contact'];

// default-open tabs, as requested
const DEFAULT_OPEN_TABS = ['intro', 'about', 'experience','education','skills','contact'];
let openTabs = DEFAULT_OPEN_TABS.slice();
let activeId = 'intro';
let openFolders = { about:true, projects:true };

// ---- website detail page state (declared early — same TDZ reason as bioTypedOnce
// below: a direct /websites/<id> visit resolves this via applyPath() during the
// initial render, before code further down the file has run) ----
let websiteDetailId = null;
let websiteItems = [];
let websitesSectKey = ''; // tracks what the websites panel's <h2 class="sect"> currently shows, so it only retypes when that actually changes
let websiteDetailWasOpen = false; // tracks the previous render, so the list only replays its enter transition on an actual detail->list switch

// ---- bio.md: shows once, no typing animation (declared early so the initial
// render — which may land straight on the about tab via deep link — can use it) ----
let bioTypedOnce = false;
const bioText = `Hi, I'm Bruno — a Brazilian-born, Sydney-based developer with ${yearsExperience}+ years of experience across full-stack and front-end development. I specialise in building reliable, well-structured systems — from custom PHP/Node back-ends to pixel-perfect front-ends.\n\nI'm 32, originally from Rio de Janeiro, Brazil, and I've called Sydney home since 2017. \nI'm an Australian citizen, fluent in English, Portuguese and Spanish.`;

// ---- terminal-style "decrypt" reveal for the email address (declared early — same
// TDZ reason as bioTypedOnce above: a direct /contact visit calls startEmailReveal()
// during the initial render, before code further down the file has run) ----
const SCRAMBLE_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!#$%&*+=?';
let emailScrambleTimer = null;
const emailRowEl = document.getElementById('emailRow');
const emailTextEl = document.getElementById('emailText');
let emailRevealedOnce = false;

// ---------------- dev mode (localhost only: skips boot animation, uses #hash deep-links
// instead of clean paths so local static servers without .htaccess rewrite support still work) ----------------
const isLocalhost = ['localhost', '127.0.0.1', ''].includes(location.hostname);
function isDevMode(){
  return isLocalhost && localStorage.getItem('devMode') === 'true';
}

// ---------------- URL deep-linking (e.g. brunovida.si/experience, or brunovida.si/#experience in dev mode) ----------------
function currentRouteId(){
  return isDevMode()
    ? decodeURIComponent(location.hash.replace(/^#/, ''))
    : decodeURIComponent(location.pathname.replace(/^\/+|\/+$/g, ''));
}

function applyPath(id){
  if(!id || !files[id]) return false;
  if(files[id].folder) openFolders[files[id].folder] = true;
  if(!openTabs.includes(id)) openTabs.push(id);
  activeId = id;
  return true;
}

// ---- website detail id travels as a ?p= query param (not a path segment) so a direct
// visit to a project page never breaks this document's relative asset paths, which
// resolve against whatever the pathname's directory depth happens to be ----
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

const enteredViaDeepLink = applyPath(currentRouteId());
if(activeId === 'websites') websiteDetailId = readWebsiteDetailFromUrl();

function renderExplorer(){
  const tree = document.getElementById('fileTree');
  tree.innerHTML = '';
  rootOrder.forEach(key=>{
    if(folders[key]){
      const f = folders[key];
      const head = document.createElement('div');
      head.className = 'tree-item' + (openFolders[key] ? ' folder-open' : '');
      head.innerHTML = '<span class="left">' + folderIconHtml(!!openFolders[key]) + f.label + '</span><span class="caret">▸</span>';
      head.onclick = ()=>{ openFolders[key] = !openFolders[key]; renderExplorer(); };
      tree.appendChild(head);

      const kids = document.createElement('div');
      kids.className = 'folder-children' + (openFolders[key] ? '' : ' collapsed');
      f.children.forEach(fileId=>{
        const item = document.createElement('div');
        item.style.paddingLeft = '20px';
        item.className = 'tree-item' + (activeId===fileId ? ' active' : '');
        item.innerHTML = '<span class="left">' + fileIconHtml(files[fileId].icon) + files[fileId].label + '</span>';
        item.onclick = ()=> openFile(fileId);
        kids.appendChild(item);
      });
      tree.appendChild(kids);
    } else {
      const item = document.createElement('div');
      item.className = 'tree-item' + (activeId===key ? ' active' : '');
      item.innerHTML = '<span class="left">' + fileIconHtml(files[key].icon) + files[key].label + '</span>';
      item.onclick = ()=> openFile(key);
      tree.appendChild(item);
    }
  });
}

let draggedTabId = null;

function renderTabs(){
  const bar = document.getElementById('tabBar');
  bar.innerHTML = '';
  if(openTabs.length === 0){
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
    tab.innerHTML = fileIconHtml(files[id].icon) + files[id].label + '<span class="close-x">✕</span>';
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

// ---- human-style typing effect for panel title tags (e.g. <work-experience role="..." years="..." />) ----
// Types the tag out char-by-char like a real person: occasional typo, a brief pause,
// a backspace, then the correct character. Once fully typed it never backspaces again —
// only the cursor keeps blinking (via the existing .cursor-caret animation).
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

// Clones the container's children so the typing animation can rebuild the same
// markup (and thus keep syntax-highlight colors) while revealing it char-by-char.
// .tool-count holds async, live data (fetched separately) rather than static text —
// its content is left untouched (so the async update code can still safely set it
// whenever it resolves) but hidden until the typing sequence reaches its position,
// then revealed in one go, so the count doesn't pop in ahead of the text before it.
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
  if(activeId && openTabs.includes(activeId)){
    empty.classList.remove('show');
    const el = document.getElementById('panel-'+activeId);
    if(el){
      el.classList.add('active');
      const sect = el.querySelector('h2.sect');
      if(sect) humanTypeSect(sect);
      if(activeId === 'about') startBioTyping();
      if(activeId === 'contact') startEmailReveal();
      if(activeId === 'websites') renderWebsiteDetail();
      if(activeId === 'mini-games') fitGameFrame();
    }
  } else {
    empty.classList.add('show');
  }
}

function setActive(id){
  websiteDetailId = null;
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
  websiteDetailId = null;
  openTabs.splice(idx,1);
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
  openTabs = [];
  activeId = null;
  websiteDetailId = null;
  renderTabs();
  renderExplorer();
  showActivePanel();
  updatePath(null);
}

function handleRouteChange(){
  const id = currentRouteId();
  if(!id){
    // back/forward landed on root "/" -- restore the default intro tab state,
    // since applyPath('') is a no-op and would otherwise leave the previous
    // tab showing on screen
    activeId = 'intro';
    openTabs = DEFAULT_OPEN_TABS.slice();
    websiteDetailId = null;
    renderTabs();
    renderExplorer();
    showActivePanel();
    return;
  }
  if(applyPath(id)){
    websiteDetailId = activeId === 'websites' ? readWebsiteDetailFromUrl() : null;
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

// ---- title-click toggles the job description (details) ----
document.querySelectorAll('.commit').forEach(c=>{
  const details = c.querySelector('.details');
  if(!details) return;
  details.classList.add('more');
  const msg = c.querySelector('.msg');
  msg.classList.add('toggle-title');
  msg.insertAdjacentHTML('beforeend', ' <span class="chev">▾</span>');
  msg.addEventListener('click', ()=> c.classList.toggle('open'));
});

renderExplorer();
renderTabs();
showActivePanel();

// ---- projects: data-driven tool cards (see json/<category>.json) ----
const ICON_GITHUB_SVG = '<svg class="btn-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/></svg>';
const ICON_CODEPEN_SVG = '<svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" stroke-linecap="round"><path d="M12 2.5 22 9v6l-10 6.5L2 15V9z"/><path d="M12 2.5v6.2M12 22v-6.2M2 9l10 6.2M22 9 12 15.2M2 15l10-6.2M22 15 12 8.8"/></svg>';
const ICON_LIVE_SVG = '<svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><path d="M15 3h6v6"/><path d="M10 14 21 3"/></svg>';
const ICON_EYE_SVG = '<svg class="btn-icon" viewBox="0 0 24 24"><use href="img/icons/sprite.svg#icon-eye"></use></svg>';
const ICON_EYE_OFF_SVG = '<svg class="btn-icon" viewBox="0 0 24 24"><use href="img/icons/sprite.svg#icon-eye-off"></use></svg>';

function escapeHtml(str){
  if(str == null) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function renderToolCard(project){
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
  const viewHtml = project.noView ? '' : `<button class="doc-btn" onclick="toggleDoc('${project.id}')">${ICON_EYE_SVG}View</button>`;
  const nameClickAttr = project.noView ? '' : ` onclick="toggleDoc('${project.id}')"`;
  const nameClass = project.noView ? 'doc-name' : 'doc-name doc-name-clickable';
  const openWindowHtml = (project.noView || project.category !== 'site-history') ? '' : `<button class="doc-btn" onclick="openInNewWindow('${path}')">${ICON_LIVE_SVG}Open in New Tab</button>`;
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
          ${openWindowHtml}
          ${liveHtml}
          ${codepenHtml}
          ${prototypeBtnsHtml}
          ${githubHtml}
        </div>
      </div>${embedHtml}${prototypeEmbedsHtml}
    </div>`;
}

function renderTimelineCard(project, sameYearAsPrevious){
  const yearHtml = (project.year && !sameYearAsPrevious) ? `<div class="year">${escapeHtml(String(project.year))}</div>` : '';
  const { year, ...rest } = project;
  const cardHtml = renderToolCard({ ...rest, featured: true });
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
  const mediaList = Array.isArray(project.media) ? project.media.filter(Boolean) : (project.media ? [project.media] : []);
  let mediaHtml;
  if(mediaList.length === 0){
    mediaHtml = `<div class="case-media empty">🖼️ Screenshot / GIF coming soon</div>`;
  } else if(mediaList.length === 1){
    mediaHtml = `<div class="case-media"><img src="${escapeHtml(mediaList[0])}" alt="${title}" loading="lazy"></div>`;
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
  return `
    <div class="case-card" id="project-${project.id}">
      ${mediaHtml}
      <div class="case-body">
        <div class="case-head">
          ${iconHtml}
          <div class="case-title">${title}</div>
          ${badge}
        </div>
        ${descHtml}
        ${sections}
        ${githubHtml ? `<div class="case-actions">${githubHtml}</div>` : ''}
      </div>
    </div>`;
}

function renderWebsiteCard(project){
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
  const thumbHtml = thumbSrc
    ? `<img class="website-thumb-img" src="${escapeHtml(thumbSrc)}" alt="${title} screenshot" loading="lazy"${enableHoverGif ? ` data-static-src="${escapeHtml(project.screenshot)}" data-gif-src="${escapeHtml(project.screenshotGif)}"` : ''}>`
    : `<span class="website-thumb-icon">${iconHtml}</span>`;
  const archiveUrl = escapeHtml(project.archiveUrl || '');
  const isArchived = status === 'offline' && !!project.archiveUrl;
  const visitTargetUrl = status === 'live' ? live : archiveUrl;
  const cardClickable = !!visitTargetUrl;
  const overlayHtml = status === 'offline' && !isArchived ? `<div class="website-offline-overlay">🕸️ No longer live</div>` : '';
  const visitHtml = cardClickable
    ? `<button class="doc-btn" onclick="openInNewWindow('${visitTargetUrl}')">${ICON_LIVE_SVG}${isArchived ? 'View on Wayback Machine' : 'Visit site'}</button>`
    : `<span class="doc-btn website-offline-btn">🕸️ Offline</span>`;
  const githubHtml = project.github ? `<a class="doc-btn" href="${github}" target="_blank" rel="noopener">${ICON_GITHUB_SVG}GitHub</a>` : '';
  const companySlug = slugify(websiteFilterCompany(project.company));

  return `
    <div class="website-card" id="project-${project.id}" data-status="${status}" data-company="${companySlug}"${isArchived ? ' data-archived="true"' : ''}>
      <div class="website-chrome">
        <span class="website-dot r"></span><span class="website-dot y"></span><span class="website-dot g"></span>
        <div class="website-urlbar"><span class="website-lock">${status === 'live' ? '🔒' : '⚠️'}</span>${url}</div>
        <span class="website-status website-status--${status}">${status === 'live' ? '🟢 Live' : '⚫ Offline'}</span>
      </div>
      <div class="website-thumb${thumbSrc ? '' : ' placeholder'}" tabindex="0" role="button" aria-label="View ${title} project details" onclick="openWebsiteDetail('${project.id}')" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();openWebsiteDetail('${project.id}');}">
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
          <button class="doc-btn" onclick="openWebsiteDetail('${project.id}')">${ICON_EYE_SVG}View project</button>
          ${visitHtml}
          ${githubHtml}
        </div>
      </div>
    </div>`;
}

// ---- mini-games: same "browser window" chrome as a website-card, but the body is a
// live, playable iframe instead of a screenshot — no lazy-loading, it's the whole point ----
const ICON_FULLSCREEN_SVG = '<svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3H5a2 2 0 0 0-2 2v3M16 3h3a2 2 0 0 1 2 2v3M21 16v3a2 2 0 0 1-2 2h-3M8 21H5a2 2 0 0 1-2-2v-3"/></svg>';

function renderMiniGameCard(project){
  const title = escapeHtml(project.title);
  const path = escapeHtml(project.path);
  const github = escapeHtml(project.github);
  const descHtml = project.description ? `<div class="tool-desc">${escapeHtml(project.description)}</div>` : '';
  const githubHtml = project.github ? `<a class="doc-btn" href="${github}" target="_blank" rel="noopener">${ICON_GITHUB_SVG}GitHub (2013 project)</a>` : '';

  return `
    <div class="website-card game-card" id="project-${project.id}">
      <div class="website-chrome">
        <span class="website-dot r"></span><span class="website-dot y"></span><span class="website-dot g"></span>
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
          <button class="doc-btn" onclick="openInNewWindow('${path}')">${ICON_LIVE_SVG}Open in New Tab</button>
          ${githubHtml}
        </div>
      </div>
    </div>`;
}

// ---- size every game's box to the tallest game's content height (same-origin iframes),
// so all the game boxes line up instead of each floating at its own natural height ----
function measureGameHeight(iframe){
  try{
    const doc = iframe.contentDocument;
    if(!doc || !doc.body) return 0;
    // body/html usually carry min-height:100vh (so a game looks right opened standalone),
    // which makes their own scrollHeight just echo the iframe's current (fallback) height —
    // measure how far the actual content reaches instead, via its top-level children
    const bottoms = Array.from(doc.body.children)
      .map(el => el.getBoundingClientRect().bottom)
      .filter(v => v > 0);
    return bottoms.length ? Math.max(...bottoms) : doc.body.scrollHeight;
  } catch(e){
    // cross-origin or otherwise unreadable
    return 0;
  }
}

function fitGameFrame(){
  const wraps = Array.from(document.querySelectorAll('.game-frame-wrap'));
  const heights = wraps.map(wrap => measureGameHeight(wrap.querySelector('iframe')));
  const naturalHeight = Math.max(0, ...heights);
  // a wrap still hidden behind an inactive tab measures 0 — skip and retry once its
  // panel becomes active (see showActivePanel), rather than locking in a bogus height
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

function slugify(str){
  return String(str).toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '') || 'personal';
}

// ---- personal projects (no company set) are grouped into "Freelance" for company
// filtering — there's no separate "Personal" option in the dropdown ----
function websiteFilterCompany(company){
  return (!company || company === 'Personal') ? 'Freelance' : company;
}

function applyWebsiteFilters(){
  const grid = document.getElementById('toolsGrid-websites');
  if(!grid) return;
  const status = grid.dataset.filter || 'all';
  const company = grid.dataset.companyFilter || 'all';
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
      emptyNote.textContent = 'No websites match these filters.';
      grid.appendChild(emptyNote);
    }
  } else if(emptyNote){
    emptyNote.remove();
  }
}

function filterWebsites(status, btn){
  const grid = document.getElementById('toolsGrid-websites');
  if(!grid) return;
  grid.dataset.filter = status;
  btn.parentElement.querySelectorAll('.filter-pill').forEach(b => b.classList.toggle('active', b === btn));
  applyWebsiteFilters();
}

function filterWebsitesByCompany(company){
  const grid = document.getElementById('toolsGrid-websites');
  if(!grid) return;
  grid.dataset.companyFilter = company;
  applyWebsiteFilters();
  const select = document.getElementById('websiteCompanySelect');
  if(select && select.value !== company) select.value = company;
  if(select) select.classList.toggle('active', company !== 'all');
}

function renderWebsiteCompanyFilters(items){
  const select = document.getElementById('websiteCompanySelect');
  if(!select) return;
  const companies = [...new Set(items.map(p => websiteFilterCompany(p.company)))].sort();
  const options = [`<option value="all">🏢 All Companies</option>`]
    .concat(companies.map(c => `<option value="${slugify(c)}">${escapeHtml(c)}</option>`));
  select.innerHTML = options.join('');
}

// ---- jump here from an experience/freelance project chip: pre-filter to that
// project's own company, resetting any lingering Live/Offline filter so the
// target card can't be hidden by a stale status filter ----
function filterWebsitesToCompany(companySlug){
  const allStatusBtn = document.querySelector('#websiteStatusFilters .filter-pill');
  if(allStatusBtn) filterWebsites('all', allStatusBtn);
  filterWebsitesByCompany(companySlug);
}

// ---- website detail page: opened from a card's screenshot, own /websites?p=<id> URL
// (query param rather than a path segment — see stripWebsiteDetailParam above) ----
function openWebsiteDetail(id){
  websiteDetailId = id;
  showActivePanel();
  const params = new URLSearchParams(location.search);
  params.set('p', id);
  const search = '?' + params.toString();
  const path = isDevMode() ? location.pathname : '/websites';
  const hash = isDevMode() ? (location.hash || '#websites') : '';
  history.pushState(null, '', path + search + hash);
  const editor = document.getElementById('editorArea');
  if(editor) editor.scrollTop = 0;
}

function closeWebsiteDetail(){
  websiteDetailId = null;
  showActivePanel();
  updatePath('websites');
  const editor = document.getElementById('editorArea');
  if(editor) editor.scrollTop = 0;
}

function renderWebsiteDetailHtml(project){
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
  const thumbHtml = thumbSrc
    ? `<img class="website-thumb-img" src="${escapeHtml(thumbSrc)}" alt="${title} screenshot" loading="lazy"${enableHoverGif ? ` data-static-src="${escapeHtml(project.screenshot)}" data-gif-src="${escapeHtml(project.screenshotGif)}"` : ''}>`
    : `<span class="website-thumb-icon">${iconHtml}</span>`;
  const archiveUrl = escapeHtml(project.archiveUrl || '');
  const isArchived = status === 'offline' && !!project.archiveUrl;
  const visitTargetUrl = status === 'live' ? live : archiveUrl;
  const cardClickable = !!visitTargetUrl;
  const overlayHtml = status === 'offline' && !isArchived ? `<div class="website-offline-overlay">🕸️ No longer live</div>` : '';
  const visitHtml = cardClickable
    ? `<button class="doc-btn" onclick="openInNewWindow('${visitTargetUrl}')">${ICON_LIVE_SVG}${isArchived ? 'View on Wayback Machine' : 'Visit site'}</button>`
    : `<span class="doc-btn website-offline-btn">🕸️ Offline</span>`;
  const githubHtml = project.github ? `<a class="doc-btn" href="${github}" target="_blank" rel="noopener">${ICON_GITHUB_SVG}GitHub</a>` : '';

  return `
    <div class="website-detail-inner">
    <button class="website-detail-back" onclick="closeWebsiteDetail()">← Back to all websites</button>
    <div class="website-card website-detail-card" data-status="${status}"${isArchived ? ' data-archived="true"' : ''}>
      <div class="website-detail-split">
        <div class="website-detail-media">
          <div class="website-chrome">
            <span class="website-dot r"></span><span class="website-dot y"></span><span class="website-dot g"></span>
            <div class="website-urlbar"><span class="website-lock">${status === 'live' ? '🔒' : '⚠️'}</span>${url}</div>
            <span class="website-status website-status--${status}">${status === 'live' ? '🟢 Live' : '⚫ Offline'}</span>
          </div>
          <div class="website-thumb website-detail-thumb${thumbSrc ? '' : ' placeholder'}">
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
          ${extendedDescHtml}
          ${techHtml}
          <div class="doc-actions">
            ${visitHtml}
            ${githubHtml}
          </div>
        </div>
      </div>
    </div>
    </div>`;
}

// ---- the websites panel's top <h2 class="sect"> switches between the tab-level
// "<websites count=... />" tag and a per-project "<website name=... year=... />" tag,
// retyping (like every other section header) only when that content actually changes ----
function websitesSectHtml(){
  const project = websiteDetailId && websiteItems.find(p => p.id === websiteDetailId);
  if(project){
    const title = escapeHtml(project.title);
    const yearAttr = project.year ? ` <span class="attr">year</span>=<span class="str">"${escapeHtml(String(project.year))}"</span>` : '';
    return `<span class="brk">&lt;</span>website <span class="attr">name</span>=<span class="str">"${title}"</span>${yearAttr}<span class="brk"> /&gt;</span>`;
  }
  return `<span class="brk">&lt;</span>websites <span class="attr">count</span>=<span class="str">"<span class="tool-count" data-count-category="websites">${websiteItems.length}</span>"</span> <span class="attr">type</span>=<span class="str">"live-preview"</span><span class="brk"> /&gt;</span>`;
}

function updateWebsitesSect(){
  const sect = document.querySelector('#panel-websites h2.sect');
  if(!sect) return;
  const project = websiteDetailId && websiteItems.find(p => p.id === websiteDetailId);
  const key = project ? project.id : '';
  if(key === websitesSectKey) return;
  websitesSectKey = key;
  sect.dataset.typed = '';
  sect.innerHTML = websitesSectHtml();
  humanTypeSect(sect);
}

function renderWebsiteDetail(){
  updateWebsitesSect();
  const listView = document.getElementById('websiteListView');
  const detailView = document.getElementById('websiteDetailView');
  if(!listView || !detailView) return;
  const wasOpen = websiteDetailWasOpen;
  websiteDetailWasOpen = !!websiteDetailId;
  if(!websiteDetailId){
    listView.style.display = '';
    if(wasOpen){
      // a lingering animation class would replay the moment this panel's ancestor
      // is re-shown from display:none by an unrelated tab switch — remove it once
      // the transition finishes so it only ever plays for a genuine detail->list close
      listView.classList.remove('pane-enter');
      void listView.offsetWidth; // restart the CSS animation
      listView.classList.add('pane-enter');
      listView.addEventListener('animationend', () => listView.classList.remove('pane-enter'), { once: true });
    }
    detailView.classList.remove('active');
    detailView.innerHTML = '';
    return;
  }
  listView.style.display = 'none';
  detailView.classList.add('active');
  const project = websiteItems.find(p => p.id === websiteDetailId);
  if(!project){
    detailView.innerHTML = websiteItems.length
      ? `<div class="website-detail-inner"><button class="website-detail-back" onclick="closeWebsiteDetail()">← Back to all websites</button><div class="website-detail-missing">Project not found.</div></div>`
      : '';
    return;
  }
  detailView.innerHTML = renderWebsiteDetailHtml(project);
  setupWebsiteHoverGifs(detailView);
}

const CATEGORY_RENDERERS = {
  websites: renderWebsiteCard,
  'edm-work': renderCaseCard,
  'landing-pages': renderTimelineCard,
  'site-history': renderTimelineCard,
  'mini-games': renderMiniGameCard
};

// categories rendered as a year-descending timeline (most recent first)
const TIMELINE_CATEGORIES = new Set(['landing-pages', 'site-history', 'websites']);

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
      return renderer(item, sameYearAsPrevious);
    }).join('');
  });
  document.querySelectorAll('.tool-count').forEach(el=>{
    const category = el.dataset.countCategory;
    el.textContent = (byCategory[category] || []).length;
  });
  setupWebsiteHoverGifs();
  websiteItems = byCategory.websites || [];
  renderWebsiteCompanyFilters(websiteItems);
  applyWebsiteFilters();
  if(activeId === 'websites') renderWebsiteDetail();
  const allProjects = Object.entries(byCategory).flatMap(([category, items]) =>
    (items || []).map(item => ({ ...item, category })));
  renderExperienceProjects(allProjects);
  renderFreelanceProjects(allProjects);
});

// ---- little clickable box that jumps to a project's card in its own tab ----
function projectChipHtml(p){
  const companySlug = p.category === 'websites' ? slugify(websiteFilterCompany(p.company)) : '';
  return `
    <button class="exp-project-chip" onclick="goToProject('${p.category}','${p.id}','${companySlug}')">
      ${p.icon ? `<span class="ic-emoji">${escapeHtml(p.icon)}</span>` : ''}<span>${escapeHtml(p.title)}</span>
    </button>`;
}

// ---- experience: mini boxes on each role linking to that company's related projects ----
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

// ---- freelance tab: mini boxes for every project done outside full-time roles ----
function renderFreelanceProjects(allProjects){
  const row = document.getElementById('freelanceProjectsRow');
  if(!row) return;
  const matches = allProjects.filter(p => p.company === 'Freelance');
  row.innerHTML = matches.map(projectChipHtml).join('');
}

// ---- jump from an experience mini box to the matching project card ----
// (edm-kinetic-modules cards live nested inside the edm-work tab, not their own tab)
const PROJECT_TAB_OVERRIDES = { 'edm-kinetic-modules': 'edm-work' };
function goToProject(category, id, companySlug){
  openFile(PROJECT_TAB_OVERRIDES[category] || category);
  if(category === 'websites' && companySlug) filterWebsitesToCompany(companySlug);
  requestAnimationFrame(() => {
    const el = document.getElementById('project-' + id);
    if(!el) return;
    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    el.classList.add('project-flash');
    setTimeout(() => el.classList.remove('project-flash'), 1600);
  });
}

// ---- swap a website thumbnail from its static screenshot to its live-motion GIF on hover ----
function setupWebsiteHoverGifs(root){
  (root || document).querySelectorAll('.website-thumb-img[data-gif-src]').forEach(img => {
    const thumb = img.closest('.website-thumb');
    if(!thumb) return;
    thumb.addEventListener('mouseenter', () => { img.src = img.dataset.gifSrc; });
    thumb.addEventListener('mouseleave', () => { img.src = img.dataset.staticSrc; });
  });
}

// ---- lazy-load an embed's iframe the first time it's opened ----
function loadEmbedIframe(embed){
  const iframe = embed.querySelector('iframe[data-src]');
  if(!iframe) return;
  iframe.src = iframe.dataset.src;
  iframe.removeAttribute('data-src');
}

// ---- "View all" toggle for a tools grid: opens/closes every doc-embed at once ----
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

// ---- swap a "View" button's icon (and, when its label is literally "View"/"Hide", its text) to reflect open state ----
function setViewButtonState(btn, isOpen){
  if(!btn) return;
  const icon = btn.querySelector('svg.btn-icon');
  if(icon) icon.outerHTML = isOpen ? ICON_EYE_OFF_SVG : ICON_EYE_SVG;
  const textNode = Array.from(btn.childNodes).find(n => n.nodeType === Node.TEXT_NODE && n.textContent.trim());
  if(textNode && (textNode.textContent.trim() === 'View' || textNode.textContent.trim() === 'Hide')){
    textNode.textContent = isOpen ? 'Hide' : 'View';
  }
}

// ---- contact page interactions ----
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
// ---- like toggleDoc, but closes sibling embeds sharing the same group first (e.g. prototype variants) ----
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

// ---- traffic light buttons ----
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
document.getElementById('dockRestore').addEventListener('click', ()=>{
  document.getElementById('app').classList.remove('minimized');
  document.getElementById('bgRain').classList.remove('show');
  document.querySelector('.bg-overlay').classList.remove('show');
  document.getElementById('dockRestore').style.display = 'none';
});

// ---- background matrix rain (revealed behind the app when minimized) ----
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
document.getElementById('dotGreen').addEventListener('click', toggleExplorer);

// ---- mobile explorer overlay ----
document.getElementById('mobileMenuBtn').addEventListener('click', toggleExplorer);
document.getElementById('explorerClose').addEventListener('click', ()=>{
  document.getElementById('shell').classList.remove('mobile-nav-open');
  setMobileNavLock(false);
});

// ---- explorer "..." menu ----
const explorerMore = document.getElementById('explorerMore');
const explorerMenu = document.getElementById('explorerMenu');
explorerMore.addEventListener('click', (e)=>{
  e.stopPropagation();
  explorerMenu.classList.toggle('show');
  explorerMore.classList.toggle('active');
});
document.getElementById('closeAllTabsBtn').addEventListener('click', ()=>{
  closeAllTabs();
  explorerMenu.classList.remove('show');
  explorerMore.classList.remove('active');
});
document.addEventListener('click', (e)=>{
  if(!explorerMenu.classList.contains('show')) return;
  if(explorerMenu.contains(e.target) || explorerMore.contains(e.target)) return;
  explorerMenu.classList.remove('show');
  explorerMore.classList.remove('active');
});

// ---- dev mode toggle button (isLocalhost/isDevMode defined earlier, near the routing code) ----
const devModeToggle = document.getElementById('devModeToggle');
if(isLocalhost){
  devModeToggle.classList.toggle('on', isDevMode());
  devModeToggle.textContent = 'DEV: ' + (isDevMode() ? 'ON' : 'OFF');
  devModeToggle.addEventListener('click', ()=>{
    const turningOn = !isDevMode();
    localStorage.setItem('devMode', turningOn ? 'true' : 'false');
    // Switching into hash mode while on a clean deep-link path (e.g. /experience)
    // would 404 on reload against a local server with no rewrite support, so
    // route back through '/' with the id carried over as a hash instead.
    if(turningOn && location.pathname !== '/'){
      location.href = '/' + location.search + (activeId ? '#' + activeId : '');
    } else {
      location.reload();
    }
  });
} else {
  devModeToggle.remove();
}

// ---- boot sequence ----
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

// ---- cycling typewriter tagline (slower + balanced typing/deleting + longer read pause) ----
let taglines = [];
const taglinesPromise = fetch('json/taglines.json')
  .then(res => res.json())
  .then(items => { taglines = items.map(t => t.replace('{years}', yearsExperience)); })
  .catch(err => {
    console.error('Could not load json/taglines.json', err);
    taglines = ["I build things that work — and look good doing it."];
  });
let tlOrder = [];  // shuffled indices into `taglines`, consumed one at a time
let tlPos = 0;
let tlIndex = 0;
let tlChar = 0;
let tlDeleting = false;
let tlMistakeCooldown = 0;  // chars to wait before another typo can occur (see typoFor)
const TYPE_SPEED = 65;     // ms per character while typing
const DELETE_SPEED = 65;   // same speed while deleting
const READ_PAUSE = 3800;   // pause once fully typed, so it can be read
const NEXT_PAUSE = 500;    // pause once fully deleted, before next phrase

// Fisher-Yates shuffle of [0, count) indices; keeps the new run's first
// phrase from matching the last one shown, so back-to-back repeats can't happen.
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

// ---- kick off the boot sequence (skipped in DEV mode, and on direct tab-link visits) ----
if(isDevMode() || enteredViaDeepLink){
  document.getElementById('boot').remove();
  document.getElementById('app').classList.add('show');
  startCyclingTagline();
} else {
  typeBootLine();
}
