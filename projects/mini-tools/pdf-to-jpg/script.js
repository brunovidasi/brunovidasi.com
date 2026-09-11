pdfjsLib.GlobalWorkerOptions.workerSrc = 'vendor/pdf.worker.min.js';

const dropzone = document.getElementById('dropzone');
const fileInput = document.getElementById('fileInput');
const filePanel = document.getElementById('filePanel');
const fileNameEl = document.getElementById('fileName');
const changeFileBtn = document.getElementById('changeFileBtn');
const qualityInput = document.getElementById('quality');
const qualityVal = document.getElementById('qualityVal');
const resolutionSelect = document.getElementById('resolution');
const errorMsg = document.getElementById('errorMsg');
const progress = document.getElementById('progress');
const progressLabel = document.getElementById('progressLabel');
const progressFill = document.getElementById('progressFill');
const summary = document.getElementById('summary');
const sumCount = document.getElementById('sumCount');
const downloadAllBtn = document.getElementById('downloadAllBtn');
const grid = document.getElementById('grid');

if (typeof JSZip === 'undefined') {
  downloadAllBtn.style.display = 'none';
}

let pdfDoc = null;
let baseFileName = 'document';
let pages = [];
let renderGen = 0;

function showError(msg) {
  errorMsg.textContent = msg;
  errorMsg.hidden = false;
}

function formatBytes(bytes) {
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
  return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
}

function baseName(name) {
  const i = name.lastIndexOf('.');
  return i > 0 ? name.slice(0, i) : name;
}

function pad(n, len) {
  return String(n).padStart(len, '0');
}

function updateQualityLabel() {
  qualityVal.textContent = qualityInput.value + '%';
}

function resetState() {
  pages.forEach((p) => { if (p.url) URL.revokeObjectURL(p.url); });
  pages = [];
  grid.innerHTML = '';
  pdfDoc = null;
  renderGen++;
  errorMsg.hidden = true;
  progress.hidden = true;
  progressFill.style.width = '0%';
  summary.hidden = true;
  downloadAllBtn.hidden = true;
  downloadAllBtn.disabled = false;
  downloadAllBtn.textContent = 'Download all (.zip)';
}

function addCard(entry, digits) {
  const card = document.createElement('div');
  card.className = 'card';
  card.innerHTML =
    '<div class="thumb-wrap"><img alt=""></div>' +
    '<div class="card-body">' +
      '<div class="card-name">Page ' + entry.num + '</div>' +
      '<div class="card-sizes"><span class="to">…</span></div>' +
      '<div class="card-actions">' +
        '<button class="secondary small dl-btn" disabled>Download</button>' +
      '</div>' +
    '</div>';
  entry.el = card;
  entry.digits = digits;
  card.querySelector('.dl-btn').addEventListener('click', () => {
    if (!entry.url) return;
    const a = document.createElement('a');
    a.href = entry.url;
    a.download = baseFileName + '-page-' + pad(entry.num, digits) + '.jpg';
    a.click();
  });
  grid.appendChild(card);
}

function updateCard(entry) {
  if (!entry.el || !entry.blob) return;
  entry.el.querySelector('.thumb-wrap img').src = entry.url;
  entry.el.querySelector('.to').textContent = formatBytes(entry.blob.size);
  entry.el.querySelector('.dl-btn').disabled = false;
}

function encodePage(entry) {
  return new Promise((resolve) => {
    const quality = Number(qualityInput.value) / 100;
    entry.canvas.toBlob((blob) => {
      if (entry.url) URL.revokeObjectURL(entry.url);
      entry.blob = blob;
      entry.url = URL.createObjectURL(blob);
      updateCard(entry);
      resolve();
    }, 'image/jpeg', quality);
  });
}

async function renderAllPages() {
  const myGen = ++renderGen;
  const scale = Number(resolutionSelect.value);
  const numPages = pdfDoc.numPages;
  const digits = String(numPages).length;

  pages.forEach((p) => { if (p.url) URL.revokeObjectURL(p.url); });
  pages = [];
  grid.innerHTML = '';
  errorMsg.hidden = true;
  progress.hidden = false;
  summary.hidden = true;

  for (let i = 1; i <= numPages; i++) {
    progressLabel.textContent = `Rendering page ${i} of ${numPages}…`;
    progressFill.style.width = Math.round(((i - 1) / numPages) * 100) + '%';

    const page = await pdfDoc.getPage(i);
    const viewport = page.getViewport({ scale });
    const canvas = document.createElement('canvas');
    canvas.width = Math.max(1, Math.round(viewport.width));
    canvas.height = Math.max(1, Math.round(viewport.height));
    const ctx = canvas.getContext('2d');
    // JPEG has no alpha channel — paint white behind the page first, otherwise
    // a transparent PDF background turns black.
    ctx.fillStyle = '#fff';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    await page.render({ canvasContext: ctx, viewport }).promise;

    if (myGen !== renderGen) return;

    const entry = { num: i, canvas };
    pages.push(entry);
    addCard(entry, digits);
    await encodePage(entry);
    if (myGen !== renderGen) return;
  }

  progressFill.style.width = '100%';
  progress.hidden = true;
  sumCount.textContent = numPages;
  summary.hidden = false;
  downloadAllBtn.hidden = numPages < 2;
}

function reencodeAll() {
  pages.forEach(encodePage);
}

let reencodeTimer;
function reencodeDebounced() {
  clearTimeout(reencodeTimer);
  reencodeTimer = setTimeout(reencodeAll, 150);
}

async function handleFile(file) {
  const looksLikePdf = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');
  if (!looksLikePdf) {
    resetState();
    filePanel.hidden = true;
    showError('Please choose a PDF file.');
    return;
  }
  resetState();
  baseFileName = baseName(file.name);
  fileNameEl.textContent = file.name;
  filePanel.hidden = false;

  try {
    const buf = await file.arrayBuffer();
    pdfDoc = await pdfjsLib.getDocument({ data: buf }).promise;
    await renderAllPages();
  } catch (e) {
    progress.hidden = true;
    showError("Couldn't read this PDF" + (e && e.message ? ': ' + e.message : '.'));
  }
}

dropzone.addEventListener('click', () => fileInput.click());
fileInput.addEventListener('change', () => {
  if (fileInput.files[0]) handleFile(fileInput.files[0]);
  fileInput.value = '';
});

['dragenter', 'dragover'].forEach((evt) => {
  dropzone.addEventListener(evt, (e) => {
    e.preventDefault();
    dropzone.classList.add('drag');
  });
});
['dragleave', 'drop'].forEach((evt) => {
  dropzone.addEventListener(evt, (e) => {
    e.preventDefault();
    dropzone.classList.remove('drag');
  });
});
dropzone.addEventListener('drop', (e) => {
  const f = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0];
  if (f) handleFile(f);
});

changeFileBtn.addEventListener('click', () => {
  resetState();
  filePanel.hidden = true;
});

qualityInput.addEventListener('input', () => {
  updateQualityLabel();
  if (pages.length) reencodeDebounced();
});
resolutionSelect.addEventListener('change', () => {
  if (pdfDoc) renderAllPages();
});

downloadAllBtn.addEventListener('click', () => {
  if (!pages.length) return;
  downloadAllBtn.disabled = true;
  downloadAllBtn.textContent = 'Zipping…';
  const zip = new JSZip();
  pages.forEach((p) => {
    zip.file(baseFileName + '-page-' + pad(p.num, p.digits) + '.jpg', p.blob);
  });
  zip.generateAsync({ type: 'blob' }).then((blob) => {
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = baseFileName + '-jpg-pages.zip';
    a.click();
    URL.revokeObjectURL(a.href);
    downloadAllBtn.disabled = false;
    downloadAllBtn.textContent = 'Download all (.zip)';
  });
});

updateQualityLabel();
