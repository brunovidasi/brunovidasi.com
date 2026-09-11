const pageSizeSelect = document.getElementById('pageSize');
const clearBtn = document.getElementById('clearBtn');
const dropzone = document.getElementById('dropzone');
const fileInput = document.getElementById('fileInput');
const errorMsg = document.getElementById('errorMsg');
const emptyState = document.getElementById('emptyState');
const grid = document.getElementById('grid');
const summary = document.getElementById('summary');
const sumCount = document.getElementById('sumCount');
const progress = document.getElementById('progress');
const progressFill = document.getElementById('progressFill');
const downloadLink = document.getElementById('downloadLink');
const convertBtn = document.getElementById('convertBtn');

let items = [];
let idCounter = 0;

function showError(msg) {
  errorMsg.textContent = msg;
  errorMsg.hidden = false;
}

function clearDownload() {
  downloadLink.hidden = true;
  if (downloadLink.href) {
    URL.revokeObjectURL(downloadLink.href);
    downloadLink.removeAttribute('href');
  }
}

function updateEmptyState() {
  emptyState.style.display = items.length === 0 ? 'block' : 'none';
  summary.hidden = items.length === 0;
  sumCount.textContent = items.length;
  if (items.length === 0) clearDownload();
}

function moveItem(idx, dir) {
  const j = idx + dir;
  if (j < 0 || j >= items.length) return;
  const tmp = items[idx];
  items[idx] = items[j];
  items[j] = tmp;
  clearDownload();
  renderGrid();
}

function removeItem(id) {
  const item = items.find((i) => i.id === id);
  if (item) URL.revokeObjectURL(item.previewUrl);
  items = items.filter((i) => i.id !== id);
  clearDownload();
  renderGrid();
}

function renderGrid() {
  grid.innerHTML = '';
  items.forEach((item, idx) => {
    const card = document.createElement('div');
    card.className = 'card';
    card.innerHTML =
      '<div class="thumb-wrap">' +
        '<img alt="" src="' + item.previewUrl + '">' +
        '<div class="badge">' + (idx + 1) + '</div>' +
      '</div>' +
      '<div class="card-body">' +
        '<div class="card-name"></div>' +
        '<div class="card-size"></div>' +
        '<div class="card-actions">' +
          '<button class="secondary small up-btn" title="Move up">↑</button>' +
          '<button class="secondary small down-btn" title="Move down">↓</button>' +
          '<button class="secondary small rm-btn">Remove</button>' +
        '</div>' +
      '</div>';
    card.querySelector('.card-name').textContent = item.file.name;
    card.querySelector('.card-size').textContent = item.width ? (item.width + '×' + item.height) : '…';
    const upBtn = card.querySelector('.up-btn');
    const downBtn = card.querySelector('.down-btn');
    upBtn.disabled = idx === 0;
    downBtn.disabled = idx === items.length - 1;
    upBtn.addEventListener('click', () => moveItem(idx, -1));
    downBtn.addEventListener('click', () => moveItem(idx, 1));
    card.querySelector('.rm-btn').addEventListener('click', () => removeItem(item.id));
    grid.appendChild(card);
  });
  updateEmptyState();
}

function addFiles(fileList) {
  const files = Array.from(fileList).filter((f) => f.type.indexOf('image/') === 0);
  if (files.length === 0) return;
  errorMsg.hidden = true;
  clearDownload();

  files.forEach((file) => {
    const item = { id: ++idCounter, file };
    item.previewUrl = URL.createObjectURL(file);
    items.push(item);
    renderGrid();

    const img = new Image();
    img.onload = () => {
      item.width = img.naturalWidth;
      item.height = img.naturalHeight;
      item.bitmap = img;
      renderGrid();
    };
    img.onerror = () => {
      showError(`Couldn't read "${file.name}" as an image — it was skipped.`);
      removeItem(item.id);
    };
    img.src = item.previewUrl;
  });
}

// pdf-lib only embeds JPEG and PNG directly — anything else (WebP, GIF, BMP…)
// gets redrawn on a canvas and re-encoded as PNG first.
function toPngBytes(item) {
  return new Promise((resolve, reject) => {
    const canvas = document.createElement('canvas');
    canvas.width = item.width;
    canvas.height = item.height;
    const ctx = canvas.getContext('2d');
    ctx.drawImage(item.bitmap, 0, 0, item.width, item.height);
    canvas.toBlob((blob) => {
      if (!blob) { reject(new Error('canvas encoding failed')); return; }
      blob.arrayBuffer().then(resolve, reject);
    }, 'image/png');
  });
}

const A4_WIDTH = 595.28;
const A4_HEIGHT = 841.89;
const A4_MARGIN = 24;

function pageLayoutFor(item, sizeMode) {
  const imgW = item.width;
  const imgH = item.height;

  if (sizeMode === 'a4') {
    const landscape = imgW > imgH;
    const pageW = landscape ? A4_HEIGHT : A4_WIDTH;
    const pageH = landscape ? A4_WIDTH : A4_HEIGHT;
    const maxW = pageW - A4_MARGIN * 2;
    const maxH = pageH - A4_MARGIN * 2;
    const scale = Math.min(maxW / imgW, maxH / imgH);
    const drawW = imgW * scale;
    const drawH = imgH * scale;
    return { pageW, pageH, drawW, drawH, x: (pageW - drawW) / 2, y: (pageH - drawH) / 2 };
  }

  // "Fit to image": the page is exactly the image's own proportions, assuming
  // a 96dpi screen pixel so page sizes stay in a sane physical range.
  const pageW = imgW * 0.75;
  const pageH = imgH * 0.75;
  return { pageW, pageH, drawW: pageW, drawH: pageH, x: 0, y: 0 };
}

async function convert() {
  if (!items.length) return;
  errorMsg.hidden = true;
  clearDownload();
  convertBtn.disabled = true;
  convertBtn.textContent = 'Converting…';
  progress.hidden = false;
  progressFill.style.width = '0%';

  try {
    const pdfDoc = await PDFLib.PDFDocument.create();
    const sizeMode = pageSizeSelect.value;

    for (let i = 0; i < items.length; i++) {
      const item = items[i];
      const bytes = await item.file.arrayBuffer();
      const type = item.file.type;

      let embedded;
      if (type === 'image/jpeg') {
        embedded = await pdfDoc.embedJpg(bytes);
      } else if (type === 'image/png') {
        embedded = await pdfDoc.embedPng(bytes);
      } else {
        embedded = await pdfDoc.embedPng(await toPngBytes(item));
      }

      const layout = pageLayoutFor(item, sizeMode);
      const page = pdfDoc.addPage([layout.pageW, layout.pageH]);
      page.drawImage(embedded, { x: layout.x, y: layout.y, width: layout.drawW, height: layout.drawH });

      progressFill.style.width = Math.round(((i + 1) / items.length) * 100) + '%';
    }

    const pdfBytes = await pdfDoc.save();
    const blob = new Blob([pdfBytes], { type: 'application/pdf' });
    downloadLink.href = URL.createObjectURL(blob);
    downloadLink.download = 'images.pdf';
    downloadLink.textContent = `Download PDF (${items.length} page${items.length === 1 ? '' : 's'})`;
    downloadLink.hidden = false;
  } catch (e) {
    showError("Couldn't build the PDF" + (e && e.message ? ': ' + e.message : '.'));
  } finally {
    progress.hidden = true;
    convertBtn.disabled = false;
    convertBtn.textContent = 'Convert to PDF';
  }
}

dropzone.addEventListener('click', () => fileInput.click());
fileInput.addEventListener('change', () => {
  addFiles(fileInput.files);
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
  if (e.dataTransfer && e.dataTransfer.files) addFiles(e.dataTransfer.files);
});

clearBtn.addEventListener('click', () => {
  items.forEach((i) => URL.revokeObjectURL(i.previewUrl));
  items = [];
  errorMsg.hidden = true;
  renderGrid();
});

pageSizeSelect.addEventListener('change', clearDownload);
convertBtn.addEventListener('click', convert);

updateEmptyState();
