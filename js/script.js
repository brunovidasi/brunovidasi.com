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
  bio:               { label:'README.md',           icon:'info', folder:'about' },
  experience:        { label:'experience.js',       icon:'js',   folder:'about' },
  education:         { label:'education.md',        icon:'md',   folder:'about' },
  skills:            { label:'skills.json',         icon:'json', folder:'about' },
  websites:          { label:'websites.html',       icon:'html', folder:'projects' },
  'landing-pages':   { label:'landing-pages.html',  icon:'html', folder:'projects' },
  'mini-tools':      { label:'mini-tools.html',     icon:'html', folder:'projects' },
  'edm-tools':       { label:'eDM-tools.html',      icon:'html', folder:'projects' },
  'edm-work':        { label:'eDM-work.html',       icon:'html', folder:'projects' },
  'site-history':    { label:'site-history.php',    icon:'php',  folder:'projects' },
  freelance:         { label:'freelance.css',       icon:'css',  folder:null },
  contact:           { label:'contact.sh',          icon:'sh',   folder:null },
  documents:         { label:'documents.pdf',       icon:'pdf',  folder:null }
};
const folders = {
  about:    { label:'about/', children:['bio','experience','education','skills'] },
  projects: { label:'projects/', children:['websites','landing-pages','mini-tools','edm-work','edm-tools','site-history'] }
};
const rootOrder = ['intro','about','projects','freelance','documents','contact'];

// default-open tabs, as requested
let openTabs = ['intro','experience','education','skills','contact','documents'];
let activeId = 'intro';
let openFolders = { about:true, projects:true };

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

function updatePath(id){
  if(isDevMode()){
    const newHash = id ? '#' + id : '';
    if(location.hash !== newHash){
      history.replaceState(null, '', location.pathname + location.search + newHash);
    }
    return;
  }
  const newPath = id ? '/' + id : '/';
  if(location.pathname !== newPath){
    history.replaceState(null, '', newPath + location.search);
  }
}

const enteredViaDeepLink = applyPath(currentRouteId());

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

function showActivePanel(){
  document.querySelectorAll('.panel').forEach(p=>p.classList.remove('active'));
  const empty = document.getElementById('emptyState');
  if(activeId && openTabs.includes(activeId)){
    empty.classList.remove('show');
    const el = document.getElementById('panel-'+activeId);
    if(el) el.classList.add('active');
  } else {
    empty.classList.add('show');
  }
}

function setActive(id){
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
  if(id === 'bio') startBioTyping();
  document.getElementById('shell').classList.remove('mobile-nav-open');
  setMobileNavLock(false);
}

function closeTab(id){
  const idx = openTabs.indexOf(id);
  if(idx === -1) return;
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
  renderTabs();
  renderExplorer();
  showActivePanel();
  updatePath(null);
}

function handleRouteChange(){
  if(applyPath(currentRouteId())){
    renderTabs();
    renderExplorer();
    showActivePanel();
    if(activeId === 'bio') startBioTyping();
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
  const viewHtml = project.noView ? '' : `<button class="doc-btn" onclick="toggleDoc('${project.id}')">👁 View</button>`;
  const openWindowHtml = (project.noView || project.category !== 'site-history') ? '' : `<button class="doc-btn" onclick="openInNewWindow('${path}')">${ICON_LIVE_SVG}Open in New Tab</button>`;
  const embedHtml = project.noView ? '' : `
      <div class="doc-embed" id="embed-${project.id}">
        <iframe data-src="${path}" title="${title}"></iframe>
      </div>`;
  return `
    <div class="doc-card tool-card${fullWidthClass}">
      <div class="tool-card-top">
        <div class="doc-head">
          ${iconHtml}
          <div class="doc-name">${title}</div>
          ${yearHtml}
        </div>
        ${descHtml}
        <div class="doc-actions">
          ${viewHtml}
          ${openWindowHtml}
          ${liveHtml}
          ${githubHtml}
          ${codepenHtml}
        </div>
      </div>${embedHtml}
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
    <div class="case-card">
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
  const yearHtml = project.year ? `<span class="tool-year">${escapeHtml(String(project.year))}</span>` : '';
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
    ? `<button class="doc-btn" onclick="event.stopPropagation();openInNewWindow('${visitTargetUrl}')">${ICON_LIVE_SVG}${isArchived ? 'View on Wayback Machine' : 'Visit site'}</button>`
    : `<span class="doc-btn website-offline-btn">🕸️ Offline</span>`;
  const githubHtml = project.github ? `<a class="doc-btn" href="${github}" target="_blank" rel="noopener" onclick="event.stopPropagation()">${ICON_GITHUB_SVG}GitHub</a>` : '';
  const clickAttr = cardClickable ? ` onclick="openInNewWindow('${visitTargetUrl}')"` : '';

  return `
    <div class="website-card" data-status="${status}"${isArchived ? ' data-archived="true"' : ''}${cardClickable ? ' tabindex="0"' : ''}${clickAttr}>
      <div class="website-chrome">
        <span class="website-dot r"></span><span class="website-dot y"></span><span class="website-dot g"></span>
        <div class="website-urlbar"><span class="website-lock">${status === 'live' ? '🔒' : '⚠️'}</span>${url}</div>
        <span class="website-status website-status--${status}">${status === 'live' ? '🟢 Live' : '⚫ Offline'}</span>
      </div>
      <div class="website-thumb${thumbSrc ? '' : ' placeholder'}">
        <div class="website-thumb-scroll">
          ${thumbHtml}
        </div>
        ${overlayHtml}
      </div>
      <div class="website-body">
        <div class="website-body-inner">
          <div class="website-info">
            <div class="doc-head">
              <div class="doc-name">${title}</div>
              ${yearHtml}
            </div>
            ${roleHtml}
            ${descHtml}
            ${techHtml}
          </div>
          ${logoHtml ? `<div class="website-logo-wrap">${logoHtml}</div>` : ''}
        </div>
        <div class="doc-actions">
          ${visitHtml}
          ${githubHtml}
        </div>
      </div>
    </div>`;
}

function filterWebsites(status, btn){
  const grid = document.getElementById('toolsGrid-websites');
  if(!grid) return;
  grid.dataset.filter = status;
  btn.parentElement.querySelectorAll('.filter-pill').forEach(b => b.classList.toggle('active', b === btn));
}

const CATEGORY_RENDERERS = {
  websites: renderWebsiteCard,
  'edm-work': renderCaseCard,
  'landing-pages': renderTimelineCard,
  'site-history': renderTimelineCard
};

// categories rendered as a year-descending timeline (most recent first)
const TIMELINE_CATEGORIES = new Set(['landing-pages', 'site-history']);

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
});

// ---- swap a website thumbnail from its static screenshot to its live-motion GIF on hover ----
function setupWebsiteHoverGifs(){
  document.querySelectorAll('.website-thumb-img[data-gif-src]').forEach(img => {
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
  if(btn) btn.innerHTML = shouldOpen ? '🙈 Collapse all' : '👁 View all';
}

// ---- contact page interactions ----
function toggleDoc(id){
  const embed = document.getElementById('embed-'+id);
  const isOpen = embed.classList.toggle('open');
  if(isOpen) loadEmbedIframe(embed);
}
function openInNewWindow(path){
  window.open(path, '_blank', 'noopener');
}
function copyEmail(){
  navigator.clipboard.writeText('contact@brunovidasi.com').then(()=>{
    const fb = document.getElementById('copyFeedback');
    fb.style.display = 'inline';
    setTimeout(()=> fb.style.display = 'none', 1500);
  });
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
  const app = document.getElementById('app');
  app.classList.add('minimize-fx');
  setTimeout(()=> app.classList.remove('minimize-fx'), 350);
});
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
  if(li >= bootLines.length){ setTimeout(showRain, 250); return; }
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

// ---- bio.md: shows once, no typing animation ----
let bioTypedOnce = false;
const bioText = `Hi, I'm Bruno — a Brazilian-born, Sydney-based developer with ${yearsExperience}+ years of experience across full-stack and front-end development. I specialise in building reliable, well-structured systems — from custom PHP/Node back-ends to pixel-perfect front-ends.\n\nI'm 32, originally from Rio de Janeiro, Brazil, and I've called Sydney home since 2017. \nI'm an Australian citizen, fluent in English, Portuguese and Spanish.`;

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
    el.textContent = current.slice(0, tlChar);
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
    tlChar = 0; tlDeleting = false;
    document.getElementById('heroVariable').textContent = '';
    tickTagline();
  });
}

if(activeId === 'bio') startBioTyping();

// ---- kick off the boot sequence (skipped in DEV mode, and on direct tab-link visits) ----
if(isDevMode() || enteredViaDeepLink){
  document.getElementById('boot').remove();
  document.getElementById('app').classList.add('show');
  startCyclingTagline();
} else {
  typeBootLine();
}
