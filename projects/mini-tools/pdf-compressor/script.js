const dropzone = document.getElementById('dropzone');
const fileInput = document.getElementById('fileInput');
const formPanel = document.getElementById('formPanel');
const fileNameEl = document.getElementById('fileName');
const fileSizeEl = document.getElementById('fileSize');
const changeFileBtn = document.getElementById('changeFileBtn');
const levelSelect = document.getElementById('levelSelect');
const compressBtn = document.getElementById('compressBtn');
const errorMsg = document.getElementById('errorMsg');
const progress = document.getElementById('progress');
const progressLabel = document.getElementById('progressLabel');
const progressFill = document.getElementById('progressFill');
const result = document.getElementById('result');
const resultStats = document.getElementById('resultStats');
const downloadLink = document.getElementById('downloadLink');

const LEVELS = {
  light: { quality: 0.85, maxDimension: 2200 },
  recommended: { quality: 0.7, maxDimension: 1800 },
  strong: { quality: 0.5, maxDimension: 1400 },
};

let selectedFile = null;

function showError(html) {
  errorMsg.innerHTML = html;
  errorMsg.hidden = false;
}

function formatBytes(bytes) {
  if (bytes < 1024) return bytes + ' B';
  const units = ['KB', 'MB', 'GB'];
  let v = bytes / 1024, i = 0;
  while (v >= 1024 && i < units.length - 1) { v /= 1024; i++; }
  return v.toFixed(v < 10 ? 2 : 1) + ' ' + units[i];
}

function compressedName(name) {
  const lower = name.toLowerCase();
  const base = lower.endsWith('.pdf') ? name.slice(0, name.length - 4) : name;
  return base + '-compressed.pdf';
}

function setBusy(busy) {
  compressBtn.disabled = busy;
  compressBtn.textContent = busy ? 'Compressing…' : 'Compress PDF';
}

function resetState() {
  selectedFile = null;
  errorMsg.hidden = true;
  progress.hidden = true;
  progressFill.style.width = '0%';
  result.hidden = true;
  levelSelect.value = 'recommended';
  setBusy(false);
  if (downloadLink.href) {
    URL.revokeObjectURL(downloadLink.href);
    downloadLink.removeAttribute('href');
  }
}

function handleFile(file) {
  const looksLikePdf = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');
  if (!looksLikePdf) {
    resetState();
    formPanel.hidden = true;
    showError('Please choose a PDF file.');
    return;
  }
  resetState();
  selectedFile = file;
  fileNameEl.textContent = file.name;
  fileSizeEl.textContent = formatBytes(file.size);
  formPanel.hidden = false;
}

async function submitCompress() {
  errorMsg.hidden = true;
  result.hidden = true;

  setBusy(true);
  progress.hidden = false;
  progressLabel.textContent = 'Compressing…';
  progressFill.style.width = '30%';

  try {
    const buf = await selectedFile.arrayBuffer();
    const level = LEVELS[levelSelect.value] || LEVELS.recommended;
    const { bytes, imagesFound, imagesCompressed } = await PDFCompressor.compressPdf(buf, level);

    progressFill.style.width = '100%';
    const blob = new Blob([bytes], { type: 'application/pdf' });
    downloadLink.href = URL.createObjectURL(blob);
    downloadLink.download = compressedName(selectedFile.name);

    const originalSize = selectedFile.size;
    const newSize = bytes.length;
    if (newSize < originalSize) {
      const pct = Math.round((1 - newSize / originalSize) * 100);
      resultStats.textContent = `${formatBytes(originalSize)} → ${formatBytes(newSize)} (${pct}% smaller)`;
    } else if (imagesFound === 0) {
      resultStats.textContent = `No compressible images found — file structure was optimized, but size is unchanged.`;
    } else {
      resultStats.textContent = `Already well optimized — the recompressed images weren't smaller, so the original is kept as-is.`;
    }
    if (imagesFound > 0) {
      resultStats.textContent += ` · ${imagesCompressed}/${imagesFound} image${imagesFound === 1 ? '' : 's'} recompressed`;
    }

    progress.hidden = true;
    result.hidden = false;
  } catch (e) {
    progress.hidden = true;
    if (e && e.message === 'ALREADY_ENCRYPTED') {
      showError('This PDF is password-protected. Remove the password first with <a href="../pdf-password-remover/index.html">Unlock PDF</a>, then compress it here.');
    } else {
      showError('Something went wrong while compressing the PDF' + (e && e.message ? ': ' + e.message : '.'));
    }
  } finally {
    setBusy(false);
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
  formPanel.hidden = true;
});

compressBtn.addEventListener('click', submitCompress);
