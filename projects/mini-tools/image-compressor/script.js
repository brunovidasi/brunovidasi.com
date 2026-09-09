let items = [];
let idCounter = 0;

const formatSelect = document.getElementById('format');
const qualityInput = document.getElementById('quality');
const qualityVal = document.getElementById('qualityVal');
const resizeToggle = document.getElementById('resizeToggle');
const resizeField = document.getElementById('resizeField');
const maxDimInput = document.getElementById('maxDim');
const limitSizeToggle = document.getElementById('limitSizeToggle');
const limitSizeField = document.getElementById('limitSizeField');
const maxSizeInput = document.getElementById('maxSizeKb');
const dropzone = document.getElementById('dropzone');
const fileInput = document.getElementById('fileInput');
const grid = document.getElementById('grid');
const emptyState = document.getElementById('emptyState');
const summary = document.getElementById('summary');
const sumCount = document.getElementById('sumCount');
const sumOriginal = document.getElementById('sumOriginal');
const sumCompressed = document.getElementById('sumCompressed');
const sumSaved = document.getElementById('sumSaved');
const clearBtn = document.getElementById('clearBtn');
const downloadAllBtn = document.getElementById('downloadAllBtn');

const modalOverlay = document.getElementById('modalOverlay');
const modalName = document.getElementById('modalName');
const modalClose = document.getElementById('modalClose');
const compareBefore = document.getElementById('compareBefore');
const compareAfter = document.getElementById('compareAfter');
const compareBox = document.getElementById('compare');

if (typeof JSZip === 'undefined') {
  downloadAllBtn.style.display = 'none';
}

function formatBytes(bytes) {
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
  return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
}

function extForType(type) {
  if (type === 'image/jpeg') return 'jpg';
  if (type === 'image/webp') return 'webp';
  if (type === 'image/png') return 'png';
  return 'jpg';
}

function swapExt(name, ext) {
  const dot = name.lastIndexOf('.');
  const base = dot > 0 ? name.slice(0, dot) : name;
  return base + '.' + ext;
}

// Cheap way to tell if a PNG/WebP/GIF actually uses transparency, so "auto"
// format can pick JPEG for opaque photos and WebP only when alpha is needed.
function hasAlpha(item) {
  if (item._alphaChecked !== undefined) return item._alphaChecked;
  const sw = 32;
  const sh = Math.max(1, Math.round(32 * (item.height / item.width)));
  const c = document.createElement('canvas');
  c.width = sw;
  c.height = sh;
  const ctx = c.getContext('2d');
  ctx.drawImage(item.bitmap, 0, 0, sw, sh);
  let data;
  try {
    data = ctx.getImageData(0, 0, sw, sh).data;
  } catch (e) {
    item._alphaChecked = true;
    return true;
  }
  for (let i = 3; i < data.length; i += 4) {
    if (data[i] < 255) {
      item._alphaChecked = true;
      return true;
    }
  }
  item._alphaChecked = false;
  return false;
}

function outputMimeFor(item) {
  const choice = formatSelect.value;
  if (choice !== 'auto') return choice;
  const srcType = item.file.type;
  if (srcType === 'image/jpeg') return 'image/jpeg';
  if (srcType === 'image/png' || srcType === 'image/webp' || srcType === 'image/gif') {
    return hasAlpha(item) ? 'image/webp' : 'image/jpeg';
  }
  return 'image/jpeg';
}

function encodeAt(item, w, h, mime, quality) {
  return new Promise((resolve) => {
    const canvas = document.createElement('canvas');
    canvas.width = w;
    canvas.height = h;
    const ctx = canvas.getContext('2d');
    if (mime === 'image/jpeg') {
      // JPEG has no alpha channel — paint white behind it first, otherwise
      // transparent pixels turn black.
      ctx.fillStyle = '#fff';
      ctx.fillRect(0, 0, w, h);
    }
    ctx.drawImage(item.bitmap, 0, 0, w, h);
    canvas.toBlob((blob) => {
      resolve({ blob, w, h, quality });
    }, mime, mime === 'image/png' ? undefined : quality);
  });
}

// Binary-searches the JPEG/WebP quality value that gets as close as possible
// to (without exceeding) targetBytes, at a fixed w/h.
function searchQualityForTarget(item, w, h, mime, targetBytes) {
  let lo = 0.05;
  let hi = 0.95;
  let best = null;
  let last = null;
  const step = (i) => {
    if (i >= 7) return Promise.resolve(best || last);
    const mid = (lo + hi) / 2;
    return encodeAt(item, w, h, mime, mid).then((res) => {
      last = res;
      if (res.blob && res.blob.size <= targetBytes) {
        best = res;
        lo = mid;
      } else {
        hi = mid;
      }
      return step(i + 1);
    });
  };
  return step(0);
}

function updateQualityLabel() {
  qualityVal.textContent = limitSizeToggle.checked ? 'auto' : qualityInput.value + '%';
}

function setBusy(item, busy) {
  item.el.querySelector('.spinner').style.display = busy ? 'flex' : 'none';
}

function compressItem(item) {
  // Settings can change again before this finishes (slider drag, toggling
  // options); _gen lets a stale run recognize itself and bail instead of
  // clobbering a newer result.
  const myGen = ++item._gen;
  setBusy(item, true);
  const mime = outputMimeFor(item);
  const quality = Number(qualityInput.value) / 100;
  const limitOn = limitSizeToggle.checked;
  const targetBytes = Math.max(1, Number(maxSizeInput.value) || 500) * 1024;

  let w = item.width;
  let h = item.height;
  if (resizeToggle.checked) {
    const maxDim = Number(maxDimInput.value) || item.width;
    const longest = Math.max(w, h);
    if (longest > maxDim) {
      const scale = maxDim / longest;
      w = Math.round(w * scale);
      h = Math.round(h * scale);
    }
  }

  let work;
  if (!limitOn) {
    work = encodeAt(item, w, h, mime, quality).then((res) => ({ res, metTarget: true }));
  } else if (mime === 'image/png') {
    // PNG is lossless — quality doesn't apply, so the only lever left is
    // shrinking the canvas until it fits.
    const shrinkPng = (cw, ch, round) =>
      encodeAt(item, cw, ch, mime, undefined).then((res) => {
        if (res.blob.size <= targetBytes || round >= 10 || Math.min(cw, ch) <= 60) {
          return { res, metTarget: res.blob.size <= targetBytes };
        }
        return shrinkPng(Math.round(cw * 0.85), Math.round(ch * 0.85), round + 1);
      });
    work = shrinkPng(w, h, 0);
  } else {
    // Check the cheapest possible encode (quality 0.05) before running a full
    // binary search at this size — most of the time the target is reachable
    // at full resolution, so this skips a lot of wasted toBlob() calls.
    const searchThenShrink = (cw, ch, round) =>
      encodeAt(item, cw, ch, mime, 0.05).then((floorRes) => {
        if (!(floorRes.blob.size <= targetBytes) && round < 6 && Math.min(cw, ch) > 60) {
          return searchThenShrink(Math.round(cw * 0.85), Math.round(ch * 0.85), round + 1);
        }
        if (floorRes.blob.size > targetBytes) {
          return { res: floorRes, metTarget: false };
        }
        return searchQualityForTarget(item, cw, ch, mime, targetBytes).then((res) => ({ res, metTarget: true }));
      });
    work = searchThenShrink(w, h, 0);
  }

  work.then((outcome) => {
    if (item._gen !== myGen) return;
    const res = outcome.res;
    if (!res || !res.blob) {
      setBusy(item, false);
      return;
    }

    // Never ship something heavier than what came in — if re-encoding lost
    // this race, just keep the original file.
    let finalBlob = res.blob;
    let finalType = mime;
    let finalW = res.w;
    let finalH = res.h;
    let keptOriginal = false;
    if (finalBlob.size >= item.file.size) {
      finalBlob = item.file;
      finalType = item.file.type || mime;
      finalW = item.width;
      finalH = item.height;
      keptOriginal = true;
    }

    if (item.compressedUrl) URL.revokeObjectURL(item.compressedUrl);
    item.compressedBlob = finalBlob;
    item.compressedType = finalType;
    item.compressedUrl = URL.createObjectURL(finalBlob);
    item.compressedWidth = finalW;
    item.compressedHeight = finalH;
    item.keptOriginal = keptOriginal;
    item.targetMissed = limitOn && !keptOriginal && !outcome.metTarget;
    item.usedQuality = (!keptOriginal && mime !== 'image/png' && res.quality != null)
      ? Math.round(res.quality * 100)
      : null;
    renderCard(item);
    updateSummary();
    setBusy(item, false);
  });
}

function renderCard(item) {
  const savedPct = item.compressedBlob
    ? Math.round((1 - item.compressedBlob.size / item.file.size) * 100)
    : null;

  item.el.querySelector('.thumb-wrap img').src = item.previewUrl;
  const badge = item.el.querySelector('.badge');
  if (savedPct === null) {
    badge.style.display = 'none';
  } else {
    badge.style.display = 'block';
    badge.textContent = savedPct === 0 ? '0%' : (savedPct > 0 ? '-' : '+') + Math.abs(savedPct) + '%';
    badge.className = 'badge' + (savedPct >= 0 ? '' : ' bad');
  }

  item.el.querySelector('.card-name').textContent = item.file.name;
  item.el.querySelector('.from').textContent = formatBytes(item.file.size);
  let toText = item.compressedBlob ? formatBytes(item.compressedBlob.size) : '…';
  if (item.usedQuality != null) toText += ' (q' + item.usedQuality + '%)';
  item.el.querySelector('.to').textContent = toText;

  const note = item.el.querySelector('.card-note');
  if (item.keptOriginal) {
    note.textContent = 'Already optimized — kept original file';
    note.className = 'card-note';
  } else if (item.targetMissed) {
    note.textContent = "Couldn't reach target size — this is as small as it gets";
    note.className = 'card-note warn';
  } else {
    note.textContent = '';
    note.className = 'card-note';
  }

  item.el.querySelector('.dl-btn').disabled = !item.compressedBlob;
}

function updateSummary() {
  const done = items.filter((i) => i.compressedBlob);
  if (done.length === 0) {
    summary.style.display = 'none';
    return;
  }
  summary.style.display = 'flex';
  const origTotal = done.reduce((s, i) => s + i.file.size, 0);
  const compTotal = done.reduce((s, i) => s + i.compressedBlob.size, 0);
  sumCount.textContent = items.length;
  sumOriginal.textContent = formatBytes(origTotal);
  sumCompressed.textContent = formatBytes(compTotal);
  sumSaved.textContent = (origTotal > 0 ? Math.round((1 - compTotal / origTotal) * 100) : 0) + '%';
}

function recompressAll() {
  items.forEach(compressItem);
}

let recompressTimer;
function recompressDebounced() {
  clearTimeout(recompressTimer);
  recompressTimer = setTimeout(recompressAll, 200);
}

function openModal(item) {
  if (!item.compressedBlob) return;
  modalName.textContent = item.file.name;
  compareBefore.src = item.previewUrl;
  compareAfter.src = item.compressedUrl;
  compareBox.style.setProperty('--pos', '50%');
  modalOverlay.classList.add('open');
}

// Drag the divider straight on the image instead of a separate range input —
// pointer events cover mouse and touch in one listener, and capturing lets
// the drag keep tracking even if the cursor slips off the image mid-drag.
let comparing = false;

function setComparePos(clientX) {
  const rect = compareBox.getBoundingClientRect();
  const pct = Math.min(100, Math.max(0, ((clientX - rect.left) / rect.width) * 100));
  compareBox.style.setProperty('--pos', pct + '%');
}

compareBox.addEventListener('pointerdown', (e) => {
  comparing = true;
  compareBox.setPointerCapture(e.pointerId);
  setComparePos(e.clientX);
});
compareBox.addEventListener('pointermove', (e) => {
  if (comparing) setComparePos(e.clientX);
});
compareBox.addEventListener('pointerup', () => { comparing = false; });
compareBox.addEventListener('pointercancel', () => { comparing = false; });

modalClose.addEventListener('click', () => modalOverlay.classList.remove('open'));
modalOverlay.addEventListener('click', (e) => {
  if (e.target === modalOverlay) modalOverlay.classList.remove('open');
});

function removeItem(item) {
  items = items.filter((i) => i !== item);
  item.el.remove();
  URL.revokeObjectURL(item.previewUrl);
  if (item.compressedUrl) URL.revokeObjectURL(item.compressedUrl);
  updateEmptyState();
  updateSummary();
}

function updateEmptyState() {
  emptyState.style.display = items.length === 0 ? 'block' : 'none';
}

function addFiles(fileList) {
  const files = Array.from(fileList).filter((f) => f.type.indexOf('image/') === 0);
  if (files.length === 0) return;

  files.forEach((file) => {
    const item = { id: ++idCounter, file, _gen: 0 };
    const card = document.createElement('div');
    card.className = 'card';
    card.innerHTML =
      '<div class="thumb-wrap">' +
        '<img alt="">' +
        '<div class="badge" style="display:none;"></div>' +
        '<div class="spinner">compressing…</div>' +
      '</div>' +
      '<div class="card-body">' +
        '<div class="card-name"></div>' +
        '<div class="card-sizes"><span class="from"></span><span class="to"></span></div>' +
        '<div class="card-note"></div>' +
        '<div class="card-actions">' +
          '<button class="secondary small dl-btn" disabled>Download</button>' +
          '<button class="secondary small rm-btn">Remove</button>' +
        '</div>' +
      '</div>';
    item.el = card;
    grid.appendChild(card);

    card.querySelector('.thumb-wrap').addEventListener('click', () => openModal(item));
    card.querySelector('.dl-btn').addEventListener('click', () => {
      if (!item.compressedBlob) return;
      const a = document.createElement('a');
      a.href = item.compressedUrl;
      a.download = swapExt(item.file.name, extForType(item.compressedType));
      a.click();
    });
    card.querySelector('.rm-btn').addEventListener('click', () => removeItem(item));

    items.push(item);
    updateEmptyState();

    item.previewUrl = URL.createObjectURL(file);

    const img = new Image();
    img.onload = () => {
      item.width = img.naturalWidth;
      item.height = img.naturalHeight;
      item.bitmap = img;
      renderCard(item);
      compressItem(item);
    };
    img.src = item.previewUrl;
  });
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

qualityInput.addEventListener('input', () => {
  updateQualityLabel();
  recompressDebounced();
});
formatSelect.addEventListener('change', recompressAll);
resizeToggle.addEventListener('change', () => {
  maxDimInput.disabled = !resizeToggle.checked;
  resizeField.classList.toggle('off', !resizeToggle.checked);
  recompressAll();
});
maxDimInput.addEventListener('input', recompressDebounced);

limitSizeToggle.addEventListener('change', () => {
  maxSizeInput.disabled = !limitSizeToggle.checked;
  limitSizeField.classList.toggle('off', !limitSizeToggle.checked);
  qualityInput.disabled = limitSizeToggle.checked;
  updateQualityLabel();
  recompressAll();
});
maxSizeInput.addEventListener('input', recompressDebounced);

clearBtn.addEventListener('click', () => {
  items.slice().forEach(removeItem);
});

downloadAllBtn.addEventListener('click', () => {
  const done = items.filter((i) => i.compressedBlob);
  if (done.length === 0) return;
  downloadAllBtn.disabled = true;
  downloadAllBtn.textContent = 'Zipping…';
  const zip = new JSZip();
  done.forEach((item) => {
    zip.file(swapExt(item.file.name, extForType(item.compressedType)), item.compressedBlob);
  });
  zip.generateAsync({ type: 'blob' }).then((blob) => {
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'compressed-images.zip';
    a.click();
    downloadAllBtn.disabled = false;
    downloadAllBtn.textContent = 'Download all (.zip)';
  });
});

updateQualityLabel();
updateEmptyState();
