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
  const dotStyleCtrl = document.getElementById('dotStyle');
  const cornerStyleCtrl = document.getElementById('cornerStyle');
  const fillModeCtrl = document.getElementById('fillMode');
  const gradientTypeCtrl = document.getElementById('gradientType');
  const gradientTypeBlock = document.getElementById('gradientTypeBlock');
  const gradientAngleBlock = document.getElementById('gradientAngleBlock');
  const gradientAngle = document.getElementById('gradientAngle');
  const gradientAngleValue = document.getElementById('gradientAngleValue');
  const darkColor = document.getElementById('darkColor');
  const darkColorHex = document.getElementById('darkColorHex');
  const darkColorLabel = document.getElementById('darkColorLabel');
  const darkColor2Field = document.getElementById('darkColor2Field');
  const darkColor2 = document.getElementById('darkColor2');
  const darkColor2Hex = document.getElementById('darkColor2Hex');
  const lightColorField = document.getElementById('lightColorField');
  const lightColor = document.getElementById('lightColor');
  const lightColorHex = document.getElementById('lightColorHex');
  const transparentBg = document.getElementById('transparentBg');
  const contrastWarning = document.getElementById('contrastWarning');
  const canvasWrap = document.getElementById('canvasWrap');
  const qrCanvas = document.getElementById('qrCanvas');
  const qrMeta = document.getElementById('qrMeta');
  const qrError = document.getElementById('qrError');
  const downloadPngBtn = document.getElementById('downloadPng');
  const downloadSvgBtn = document.getElementById('downloadSvg');
  const copyPngBtn = document.getElementById('copyPng');

  let level = 'M';
  let dotStyle = 'square';
  let cornerStyle = 'square';
  let fillMode = 'solid';
  let gradientType = 'linear';
  let currentResult = null;

  // ---- color swatch <-> hex text input syncing ----
  function normalizeHex(value) {
    let v = value.trim();
    if (!v.startsWith('#')) v = '#' + v;
    if (/^#[0-9a-fA-F]{3}$/.test(v)) {
      v = '#' + v[1] + v[1] + v[2] + v[2] + v[3] + v[3];
    }
    return /^#[0-9a-fA-F]{6}$/.test(v) ? v.toLowerCase() : null;
  }

  function linkColorInputs(swatch, hexInput) {
    swatch.addEventListener('input', () => {
      hexInput.value = swatch.value;
      hexInput.classList.remove('invalid');
      regenerate();
    });
    hexInput.addEventListener('input', () => {
      const normalized = normalizeHex(hexInput.value);
      if (normalized) {
        hexInput.classList.remove('invalid');
        swatch.value = normalized;
        regenerate();
      } else {
        hexInput.classList.add('invalid');
      }
    });
    hexInput.addEventListener('blur', () => {
      const normalized = normalizeHex(hexInput.value);
      hexInput.value = normalized || swatch.value;
      hexInput.classList.remove('invalid');
    });
  }

  linkColorInputs(darkColor, darkColorHex);
  linkColorInputs(darkColor2, darkColor2Hex);
  linkColorInputs(lightColor, lightColorHex);

  function luminance(hex) {
    const r = parseInt(hex.slice(1, 3), 16) / 255;
    const g = parseInt(hex.slice(3, 5), 16) / 255;
    const b = parseInt(hex.slice(5, 7), 16) / 255;
    return 0.2126 * r + 0.7152 * g + 0.0722 * b;
  }

  function updateContrastWarning() {
    const bgLum = transparentBg.checked ? 1 : luminance(lightColor.value); // assume a light backdrop when transparent
    const darkLums = fillMode === 'gradient' ? [luminance(darkColor.value), luminance(darkColor2.value)] : [luminance(darkColor.value)];
    const worstDiff = Math.min(...darkLums.map((l) => Math.abs(bgLum - l)));
    if (worstDiff < 0.28) {
      contrastWarning.textContent = '⚠ Low contrast between dot and background colors — this code may not scan reliably. Try a bigger difference in lightness.';
      contrastWarning.hidden = false;
    } else {
      contrastWarning.hidden = true;
    }
  }

  function currentStyleOptions() {
    const opts = {
      moduleSize: parseInt(moduleSize.value, 10),
      margin: parseInt(margin.value, 10),
      dotStyle,
      cornerStyle,
      dark: darkColor.value,
      light: lightColor.value,
      transparentBackground: transparentBg.checked,
    };
    if (fillMode === 'gradient') {
      opts.gradient = {
        color1: darkColor.value,
        color2: darkColor2.value,
        type: gradientType,
        angle: parseInt(gradientAngle.value, 10),
      };
    }
    return opts;
  }

  function regenerate() {
    const text = genText.value;
    qrError.hidden = true;
    updateContrastWarning();
    canvasWrap.classList.toggle('transparent-preview', transparentBg.checked);
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
      QREncoder.renderToCanvas(qrCanvas, result.matrix, currentStyleOptions());
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

  function bindSegControl(el, onChange) {
    el.addEventListener('click', (e) => {
      const btn = e.target.closest('button');
      if (!btn) return;
      el.querySelectorAll('button').forEach((b) => b.classList.toggle('active', b === btn));
      onChange(btn.dataset.value);
      regenerate();
    });
  }

  bindSegControl(ecLevel, (v) => { level = v; });
  bindSegControl(dotStyleCtrl, (v) => { dotStyle = v; });
  bindSegControl(cornerStyleCtrl, (v) => { cornerStyle = v; });
  bindSegControl(fillModeCtrl, (v) => {
    fillMode = v;
    const isGradient = v === 'gradient';
    gradientTypeBlock.hidden = !isGradient;
    gradientAngleBlock.hidden = !isGradient || gradientType !== 'linear';
    darkColor2Field.hidden = !isGradient;
    darkColorLabel.textContent = isGradient ? 'Dot color 1' : 'Dot color';
  });
  bindSegControl(gradientTypeCtrl, (v) => {
    gradientType = v;
    gradientAngleBlock.hidden = fillMode !== 'gradient' || v !== 'linear';
  });

  transparentBg.addEventListener('change', () => {
    lightColor.disabled = transparentBg.checked;
    lightColorHex.disabled = transparentBg.checked;
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
  gradientAngle.addEventListener('input', () => {
    gradientAngleValue.textContent = gradientAngle.value + '°';
    regenerate();
  });
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
    const svg = QREncoder.renderToSvg(currentResult.matrix, currentStyleOptions());
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
