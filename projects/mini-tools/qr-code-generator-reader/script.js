(() => {
  'use strict';

  // ---- tabs ----
  const modeTabs = document.getElementById('modeTabs');
  const generateView = document.getElementById('generateView');
  const scanView = document.getElementById('scanView');

  modeTabs.addEventListener('click', (e) => {
    const btn = e.target.closest('.tab');
    if (!btn) return;
    const mode = btn.dataset.mode;
    modeTabs.querySelectorAll('.tab').forEach((t) => t.classList.toggle('active', t === btn));
    generateView.hidden = mode !== 'generate';
    scanView.hidden = mode !== 'scan';
    if (mode !== 'scan') stopCamera();
  });

  // ==================== GENERATE ====================

  const genText = document.getElementById('genText');
  const ecLevel = document.getElementById('ecLevel');
  const moduleSize = document.getElementById('moduleSize');
  const moduleSizeValue = document.getElementById('moduleSizeValue');
  const margin = document.getElementById('margin');
  const marginValue = document.getElementById('marginValue');
  const darkColor = document.getElementById('darkColor');
  const lightColor = document.getElementById('lightColor');
  const qrCanvas = document.getElementById('qrCanvas');
  const qrMeta = document.getElementById('qrMeta');
  const qrError = document.getElementById('qrError');
  const downloadPngBtn = document.getElementById('downloadPng');
  const downloadSvgBtn = document.getElementById('downloadSvg');
  const copyPngBtn = document.getElementById('copyPng');

  let level = 'M';
  let currentResult = null;

  function regenerate() {
    const text = genText.value;
    qrError.hidden = true;
    if (!text) {
      qrCanvas.hidden = true;
      qrMeta.textContent = '—';
      currentResult = null;
      updateDownloadButtons();
      return;
    }
    try {
      const result = QREncoder.generateMatrix(text, level);
      currentResult = result;
      QREncoder.renderToCanvas(qrCanvas, result.matrix, {
        moduleSize: parseInt(moduleSize.value, 10),
        margin: parseInt(margin.value, 10),
        dark: darkColor.value,
        light: lightColor.value,
      });
      qrCanvas.hidden = false;
      qrMeta.textContent = `v${result.version} · ${result.level} · ${result.matrix.length}×${result.matrix.length}`;
    } catch (err) {
      currentResult = null;
      qrCanvas.hidden = true;
      qrMeta.textContent = '—';
      qrError.textContent = err.message;
      qrError.hidden = false;
    }
    updateDownloadButtons();
  }

  function updateDownloadButtons() {
    const has = !!currentResult;
    downloadPngBtn.disabled = !has;
    downloadSvgBtn.disabled = !has;
    copyPngBtn.disabled = !has;
  }

  ecLevel.addEventListener('click', (e) => {
    const btn = e.target.closest('button');
    if (!btn) return;
    level = btn.dataset.value;
    ecLevel.querySelectorAll('button').forEach((b) => b.classList.toggle('active', b === btn));
    regenerate();
  });

  moduleSize.addEventListener('input', () => {
    moduleSizeValue.textContent = moduleSize.value + 'px';
    regenerate();
  });
  margin.addEventListener('input', () => {
    marginValue.textContent = margin.value;
    regenerate();
  });
  darkColor.addEventListener('input', regenerate);
  lightColor.addEventListener('input', regenerate);
  genText.addEventListener('input', regenerate);

  function triggerDownload(url, filename) {
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
  }

  downloadPngBtn.addEventListener('click', () => {
    if (!currentResult) return;
    qrCanvas.toBlob((blob) => {
      const url = URL.createObjectURL(blob);
      triggerDownload(url, 'qrcode.png');
      setTimeout(() => URL.revokeObjectURL(url), 1000);
    });
  });

  downloadSvgBtn.addEventListener('click', () => {
    if (!currentResult) return;
    const svg = QREncoder.renderToSvg(currentResult.matrix, {
      margin: parseInt(margin.value, 10),
      dark: darkColor.value,
      light: lightColor.value,
    });
    const blob = new Blob([svg], { type: 'image/svg+xml' });
    const url = URL.createObjectURL(blob);
    triggerDownload(url, 'qrcode.svg');
    setTimeout(() => URL.revokeObjectURL(url), 1000);
  });

  copyPngBtn.addEventListener('click', async () => {
    if (!currentResult) return;
    try {
      qrCanvas.toBlob(async (blob) => {
        await navigator.clipboard.write([new ClipboardItem({ [blob.type]: blob })]);
        flashCopied(copyPngBtn);
      });
    } catch (err) {
      // clipboard image write unsupported; silently ignore
    }
  });

  function flashCopied(btn) {
    const original = btn.textContent;
    btn.textContent = 'Copied!';
    btn.classList.add('copied');
    setTimeout(() => {
      btn.textContent = original;
      btn.classList.remove('copied');
    }, 1200);
  }

  regenerate();

  // ==================== SCAN ====================

  const fileInput = document.getElementById('fileInput');
  const cameraBtn = document.getElementById('cameraBtn');
  const stopCameraBtn = document.getElementById('stopCameraBtn');
  const scanPreview = document.getElementById('scanPreview');
  const video = document.getElementById('video');
  const scanCanvas = document.getElementById('scanCanvas');
  const scanStatus = document.getElementById('scanStatus');
  const scanResultPanel = document.getElementById('scanResultPanel');
  const scanMeta = document.getElementById('scanMeta');
  const scanOutputText = document.getElementById('scanOutputText');
  const copyScanTextBtn = document.getElementById('copyScanText');
  const openScanLink = document.getElementById('openScanLink');

  let cameraStream = null;
  let scanRAF = null;

  function setScanStatus(text, kind) {
    scanStatus.textContent = text;
    scanStatus.className = 'scan-status' + (kind ? ' ' + kind : '');
  }

  function showScanResult(result) {
    scanResultPanel.hidden = false;
    scanMeta.textContent = `v${result.version} · ${result.level}`;
    scanOutputText.textContent = result.text;
    let isUrl = false;
    try {
      const u = new URL(result.text);
      isUrl = u.protocol === 'http:' || u.protocol === 'https:';
    } catch (e) { /* not a URL */ }
    openScanLink.hidden = !isUrl;
    if (isUrl) openScanLink.href = result.text;
    setScanStatus('QR code decoded.', 'ok');
  }

  function hideScanResult() {
    scanResultPanel.hidden = true;
  }

  function decodeFromImageElement(img) {
    hideScanResult();
    const canvas = document.createElement('canvas');
    canvas.width = img.naturalWidth || img.width;
    canvas.height = img.naturalHeight || img.height;
    const ctx = canvas.getContext('2d');
    ctx.drawImage(img, 0, 0);
    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
    setScanStatus('Scanning image…');
    const result = QRDecoder.decodeImageData(imageData);
    if (result) {
      showScanResult(result);
    } else {
      setScanStatus('No QR code found in that image. Try a clearer or larger photo.', 'error');
    }
  }

  fileInput.addEventListener('change', () => {
    const file = fileInput.files[0];
    if (!file) return;
    stopCamera();
    scanPreview.hidden = true;
    const url = URL.createObjectURL(file);
    const img = new Image();
    img.onload = () => {
      decodeFromImageElement(img);
      URL.revokeObjectURL(url);
    };
    img.onerror = () => {
      setScanStatus('Could not load that image.', 'error');
      URL.revokeObjectURL(url);
    };
    img.src = url;
  });

  async function startCamera() {
    hideScanResult();
    try {
      cameraStream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: 'environment', width: { ideal: 1280 }, height: { ideal: 960 } },
      });
    } catch (err) {
      setScanStatus('Could not access the camera: ' + err.message, 'error');
      return;
    }
    video.srcObject = cameraStream;
    await video.play();
    scanPreview.hidden = false;
    cameraBtn.hidden = true;
    stopCameraBtn.hidden = false;
    setScanStatus('Point your camera at a QR code…');
    scanLoop();
  }

  function stopCamera() {
    if (scanRAF) cancelAnimationFrame(scanRAF);
    scanRAF = null;
    if (cameraStream) {
      cameraStream.getTracks().forEach((t) => t.stop());
      cameraStream = null;
    }
    video.srcObject = null;
    scanPreview.hidden = true;
    cameraBtn.hidden = false;
    stopCameraBtn.hidden = true;
  }

  let lastScanAt = 0;
  function scanLoop(timestamp) {
    scanRAF = requestAnimationFrame(scanLoop);
    if (!video.videoWidth) return;
    if (timestamp && timestamp - lastScanAt < 350) return; // throttle: ~3 scans/sec
    lastScanAt = timestamp || 0;

    scanCanvas.width = video.videoWidth;
    scanCanvas.height = video.videoHeight;
    const ctx = scanCanvas.getContext('2d');
    ctx.drawImage(video, 0, 0);
    const imageData = ctx.getImageData(0, 0, scanCanvas.width, scanCanvas.height);
    const result = QRDecoder.decodeImageData(imageData);
    if (result) {
      showScanResult(result);
      stopCamera();
    }
  }

  cameraBtn.addEventListener('click', startCamera);
  stopCameraBtn.addEventListener('click', stopCamera);

  copyScanTextBtn.addEventListener('click', async () => {
    if (!scanOutputText.textContent) return;
    try {
      await navigator.clipboard.writeText(scanOutputText.textContent);
      flashCopied(copyScanTextBtn);
    } catch (err) { /* clipboard unsupported */ }
  });
})();
