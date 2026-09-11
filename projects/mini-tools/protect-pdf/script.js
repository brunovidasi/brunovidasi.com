pdfjsLib.GlobalWorkerOptions.workerSrc = 'vendor/pdf.worker.min.js';

const dropzone = document.getElementById('dropzone');
const fileInput = document.getElementById('fileInput');
const formPanel = document.getElementById('formPanel');
const fileNameEl = document.getElementById('fileName');
const changeFileBtn = document.getElementById('changeFileBtn');
const pwInput = document.getElementById('pwInput');
const pwConfirmInput = document.getElementById('pwConfirmInput');
const permPrint = document.getElementById('permPrint');
const permCopy = document.getElementById('permCopy');
const permEdit = document.getElementById('permEdit');
const protectBtn = document.getElementById('protectBtn');
const errorMsg = document.getElementById('errorMsg');
const progress = document.getElementById('progress');
const progressLabel = document.getElementById('progressLabel');
const progressFill = document.getElementById('progressFill');
const result = document.getElementById('result');
const resultText = document.getElementById('resultText');
const downloadLink = document.getElementById('downloadLink');

let selectedFile = null;

function showError(html) {
  errorMsg.innerHTML = html;
  errorMsg.hidden = false;
}

function protectedName(name) {
  const lower = name.toLowerCase();
  const base = lower.endsWith('.pdf') ? name.slice(0, name.length - 4) : name;
  return base + '-protected.pdf';
}

function setBusy(busy) {
  protectBtn.disabled = busy;
  protectBtn.textContent = busy ? 'Encrypting…' : 'Add password';
}

function resetState() {
  selectedFile = null;
  errorMsg.hidden = true;
  progress.hidden = true;
  progressFill.style.width = '0%';
  result.hidden = true;
  pwInput.value = '';
  pwConfirmInput.value = '';
  permPrint.checked = true;
  permCopy.checked = true;
  permEdit.checked = true;
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
  formPanel.hidden = false;
  pwInput.focus();
}

async function submitProtect() {
  const pw = pwInput.value;
  const confirmPw = pwConfirmInput.value;
  errorMsg.hidden = true;
  result.hidden = true;

  if (!pw) {
    showError('Choose a password.');
    return;
  }
  if (pw !== confirmPw) {
    showError("Passwords don't match.");
    return;
  }

  setBusy(true);
  progress.hidden = false;
  progressLabel.textContent = 'Encrypting…';
  progressFill.style.width = '40%';

  try {
    const buf = await selectedFile.arrayBuffer();
    const outBytes = await PDFPasswordProtector.addPasswordToPdf(buf, pw, {
      print: permPrint.checked,
      copy: permCopy.checked,
      edit: permEdit.checked,
    });

    progressLabel.textContent = 'Verifying…';
    progressFill.style.width = '80%';

    // Sanity check: confirm the result genuinely requires the password before
    // ever offering it for download.
    try {
      await pdfjsLib.getDocument({ data: outBytes.slice(0), password: '' }).promise;
      throw new Error('Encryption did not take effect — the file opened without a password.');
    } catch (verifyErr) {
      if (!(verifyErr && verifyErr.name === 'PasswordException')) throw verifyErr;
    }

    progressFill.style.width = '100%';
    const blob = new Blob([outBytes], { type: 'application/pdf' });
    downloadLink.href = URL.createObjectURL(blob);
    downloadLink.download = protectedName(selectedFile.name);
    resultText.textContent = 'Password added — the file now requires it to open.';
    progress.hidden = true;
    result.hidden = false;
  } catch (e) {
    progress.hidden = true;
    if (e && e.message === 'ALREADY_ENCRYPTED') {
      showError('This PDF already has a password. Remove it first with <a href="../unlock-pdf/index.html">Unlock PDF</a>, then add a new one here.');
    } else {
      showError('Something went wrong while encrypting the PDF' + (e && e.message ? ': ' + e.message : '.'));
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

protectBtn.addEventListener('click', submitProtect);
pwConfirmInput.addEventListener('keydown', (e) => {
  if (e.key === 'Enter') submitProtect();
});
