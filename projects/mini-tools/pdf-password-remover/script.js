pdfjsLib.GlobalWorkerOptions.workerSrc = 'vendor/pdf.worker.min.js';

const RENDER_SCALE = 2; // ~144 DPI — good screen/print balance without huge files
const MAX_DIM = 3000; // cap canvas size so very large pages don't blow up memory

const dropzone = document.getElementById('dropzone');
const fileInput = document.getElementById('fileInput');
const passwordPanel = document.getElementById('passwordPanel');
const fileNameEl = document.getElementById('fileName');
const changeFileBtn = document.getElementById('changeFileBtn');
const pwRow = document.getElementById('pwRow');
const pwInput = document.getElementById('pwInput');
const unlockBtn = document.getElementById('unlockBtn');
const errorMsg = document.getElementById('errorMsg');
const progress = document.getElementById('progress');
const progressLabel = document.getElementById('progressLabel');
const progressFill = document.getElementById('progressFill');
const result = document.getElementById('result');
const resultText = document.getElementById('resultText');
const downloadLink = document.getElementById('downloadLink');
const keepVectorToggle = document.getElementById('keepVectorToggle');

let selectedFile = null;
let originalBuffer = null;

function showError(msg) {
  errorMsg.textContent = msg;
  errorMsg.hidden = false;
}

function unlockedName(name) {
  const lower = name.toLowerCase();
  const base = lower.endsWith('.pdf') ? name.slice(0, name.length - 4) : name;
  return base + '-unlocked.pdf';
}

function setUnlockBusy(busy) {
  unlockBtn.disabled = busy;
  unlockBtn.textContent = busy ? 'Checking…' : 'Unlock';
}

function resetState() {
  selectedFile = null;
  originalBuffer = null;
  errorMsg.hidden = true;
  progress.hidden = true;
  progressFill.style.width = '0%';
  result.hidden = true;
  pwRow.hidden = true;
  pwInput.value = '';
  setUnlockBusy(false);
  if (downloadLink.href) {
    URL.revokeObjectURL(downloadLink.href);
    downloadLink.removeAttribute('href');
  }
}

function finishWithBlob(blob, message) {
  downloadLink.href = URL.createObjectURL(blob);
  downloadLink.download = unlockedName(selectedFile.name);
  resultText.textContent = message;
  progress.hidden = true;
  result.hidden = false;
}

async function renderToUnlockedPdf(pdfDoc, fallbackNote) {
  pwRow.hidden = true;
  errorMsg.hidden = true;
  result.hidden = true;
  progress.hidden = false;

  const numPages = pdfDoc.numPages;
  let outDoc = null;

  try {
    for (let i = 1; i <= numPages; i++) {
      progressLabel.textContent = `Rendering page ${i} of ${numPages}…`;
      progressFill.style.width = Math.round(((i - 1) / numPages) * 100) + '%';

      const page = await pdfDoc.getPage(i);
      const unscaled = page.getViewport({ scale: 1 });
      let scale = RENDER_SCALE;
      if (unscaled.width * scale > MAX_DIM || unscaled.height * scale > MAX_DIM) {
        scale = MAX_DIM / Math.max(unscaled.width, unscaled.height);
      }
      const viewport = page.getViewport({ scale });

      const canvas = document.createElement('canvas');
      canvas.width = Math.ceil(viewport.width);
      canvas.height = Math.ceil(viewport.height);
      const ctx = canvas.getContext('2d');
      ctx.fillStyle = '#fff';
      ctx.fillRect(0, 0, canvas.width, canvas.height);
      await page.render({ canvasContext: ctx, viewport }).promise;
      const imgData = canvas.toDataURL('image/jpeg', 0.92);
      canvas.width = 0;
      canvas.height = 0;

      const w = unscaled.width;
      const h = unscaled.height;
      const orientation = w >= h ? 'l' : 'p';
      if (!outDoc) {
        outDoc = new jspdf.jsPDF({ unit: 'pt', orientation, format: [w, h], compress: true });
      } else {
        outDoc.addPage([w, h], orientation);
      }
      outDoc.addImage(imgData, 'JPEG', 0, 0, w, h);
    }

    progressFill.style.width = '100%';
    const blob = outDoc.output('blob');
    const prefix = fallbackNote ? fallbackNote + ' ' : '';
    finishWithBlob(blob, `${prefix}Password removed — ${numPages} page${numPages === 1 ? '' : 's'} rebuilt as images.`);
  } catch (e) {
    progress.hidden = true;
    showError('Something went wrong while rebuilding the PDF' + (e && e.message ? ': ' + e.message : '.'));
  }
}

async function unlockDocument(pdfDoc, password) {
  pwRow.hidden = true;
  errorMsg.hidden = true;
  result.hidden = true;

  if (keepVectorToggle.checked) {
    progress.hidden = false;
    progressLabel.textContent = 'Decrypting the original PDF…';
    progressFill.style.width = '50%';
    try {
      const outBytes = await PDFPasswordRemover.removePasswordKeepingContent(originalBuffer.slice(0), password);
      progressFill.style.width = '100%';
      const blob = new Blob([outBytes], { type: 'application/pdf' });
      finishWithBlob(blob, `Password removed — the original PDF's ${pdfDoc.numPages} page${pdfDoc.numPages === 1 ? '' : 's'} are kept intact.`);
      return;
    } catch (e) {
      console.warn('Vector-preserving removal failed, falling back to image rendering:', e);
      await renderToUnlockedPdf(pdfDoc, "Couldn't preserve this file's original structure, so it was flattened to images instead.");
      return;
    }
  }

  await renderToUnlockedPdf(pdfDoc);
}

function attemptLoad(password, isProbe) {
  const data = originalBuffer.slice(0);
  return pdfjsLib.getDocument({ data, password }).promise
    .then((pdfDoc) => {
      if (isProbe) {
        // The silent no-password probe succeeded — this file was never encrypted,
        // so there's nothing to remove. Skip straight to offering the original.
        pwRow.hidden = true;
        errorMsg.hidden = true;
        const blob = new Blob([originalBuffer], { type: 'application/pdf' });
        finishWithBlob(blob, "This PDF isn't password-protected — nothing to remove.");
        downloadLink.download = selectedFile.name;
        return;
      }
      return unlockDocument(pdfDoc, password);
    })
    .catch((err) => {
      if (err && err.name === 'PasswordException') {
        pwRow.hidden = false;
        if (err.code === pdfjsLib.PasswordResponses.INCORRECT_PASSWORD) {
          showError('Incorrect password — try again.');
        }
        pwInput.focus();
        pwInput.select();
      } else {
        showError("Couldn't read this file — it may be corrupted or not a valid PDF.");
      }
    });
}

function submitPassword() {
  const pw = pwInput.value;
  if (!pw) {
    showError("Enter the PDF's password.");
    return;
  }
  errorMsg.hidden = true;
  setUnlockBusy(true);
  attemptLoad(pw).then(() => setUnlockBusy(false));
}

function handleFile(file) {
  const looksLikePdf = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');
  if (!looksLikePdf) {
    resetState();
    passwordPanel.hidden = true;
    showError('Please choose a PDF file.');
    return;
  }
  resetState();
  selectedFile = file;
  fileNameEl.textContent = file.name;
  passwordPanel.hidden = false;

  file.arrayBuffer()
    .then((buf) => {
      originalBuffer = buf;
      attemptLoad('', true); // silent first try — only reveals the password field if one is actually needed
    })
    .catch(() => showError('Could not read this file.'));
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
  passwordPanel.hidden = true;
});

unlockBtn.addEventListener('click', submitPassword);
pwInput.addEventListener('keydown', (e) => {
  if (e.key === 'Enter') submitPassword();
});
