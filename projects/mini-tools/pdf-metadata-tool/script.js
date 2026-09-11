'use strict';

/* ===================================================================
   Standard PDF Document Info fields
   =================================================================== */

const STANDARD_INFO_KEYS = ['Title', 'Author', 'Subject', 'Keywords', 'Creator', 'Producer', 'CreationDate', 'ModDate'];
const STANDARD_LABELS = {
  Title: 'Title', Author: 'Author', Subject: 'Subject', Keywords: 'Keywords',
  Creator: 'Creator (application)', Producer: 'Producer', CreationDate: 'Created', ModDate: 'Modified'
};
const DATE_KEYS = new Set(['CreationDate', 'ModDate']);
const GROUP_ORDER = ['info', 'advanced'];
const GROUP_LABELS = { info: 'Document Info', advanced: 'Advanced' };

/* ===================================================================
   Small shared helpers
   =================================================================== */

let uidCounter = 0;
function nextUid() { return 'f' + (uidCounter++); }

function pad2(n) { return String(n).padStart(2, '0'); }

function formatDateUTC(d) {
  return `${d.getUTCFullYear()}-${pad2(d.getUTCMonth() + 1)}-${pad2(d.getUTCDate())} ${pad2(d.getUTCHours())}:${pad2(d.getUTCMinutes())}:${pad2(d.getUTCSeconds())} UTC`;
}

function parseFriendlyDateUTC(str) {
  const m = String(str).trim().match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2}):(\d{2})/);
  if (!m) return null;
  const [, y, mo, da, h, mi, s] = m.map(Number);
  const d = new Date(Date.UTC(y, mo - 1, da, h, mi, s));
  return isNaN(d) ? null : d;
}

function formatBytes(bytes) {
  if (bytes < 1024) return bytes + ' B';
  const units = ['KB', 'MB', 'GB'];
  let v = bytes / 1024, i = 0;
  while (v >= 1024 && i < units.length - 1) { v /= 1024; i++; }
  return v.toFixed(v < 10 ? 2 : 1) + ' ' + units[i];
}

function escHtml(s) {
  return String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

function isSensitiveKey(key) {
  return key === 'Author' || /author|user|owner|\bname\b|email/i.test(key);
}

/* ===================================================================
   PDF metadata reading (pdf-lib)
   =================================================================== */

function extractFieldModel(doc) {
  const { PDFName } = PDFLib;
  const model = [];
  const infoDict = doc.getInfoDict();
  const seen = new Set();

  function pushField(rawKey, valueObj) {
    let displayText = '';
    try { displayText = typeof valueObj.decodeText === 'function' ? valueObj.decodeText() : String(valueObj); }
    catch (err) { displayText = ''; }
    let isDate = false;
    if (DATE_KEYS.has(rawKey) || /^D:/.test(displayText)) {
      try {
        const d = typeof valueObj.decodeDate === 'function' ? valueObj.decodeDate() : null;
        if (d instanceof Date && !isNaN(d)) { isDate = true; displayText = formatDateUTC(d); }
      } catch (err) { /* not a valid PDF date — treat as plain text */ }
    }
    model.push({
      key: nextUid(), group: 'info', infoKey: rawKey, label: STANDARD_LABELS[rawKey] || rawKey,
      sensitive: isSensitiveKey(rawKey), deleted: false, isDate, editValue: displayText
    });
  }

  for (const stdKey of STANDARD_INFO_KEYS) {
    const v = infoDict.lookup(PDFName.of(stdKey));
    if (v) { pushField(stdKey, v); seen.add(stdKey); }
  }
  for (const nameObj of infoDict.keys()) {
    const rawKey = nameObj.asString().replace(/^\//, '');
    if (seen.has(rawKey)) continue;
    seen.add(rawKey);
    const v = infoDict.lookup(nameObj);
    if (v) pushField(rawKey, v);
  }

  const metaRef = doc.catalog.get(PDFName.of('Metadata'));
  if (metaRef) {
    let preview = '(present — could not preview content)';
    try {
      const stream = doc.catalog.lookup(PDFName.of('Metadata'));
      const bytes = PDFLib.decodePDFRawStream(stream).decode();
      const text = new TextDecoder('utf-8', { fatal: false }).decode(bytes);
      if (text) preview = text.length > 500 ? text.slice(0, 500) + '…' : text;
    } catch (err) { /* keep fallback preview text */ }
    model.push({ key: nextUid(), group: 'advanced', label: 'XMP Metadata', sensitive: true, deleted: false, isXmp: true, displayValueStatic: preview });
  }

  return model;
}

async function applyEdits(item) {
  const { PDFName, PDFString, PDFHexString } = PDFLib;
  const doc = item.pdfDoc;
  const info = doc.getInfoDict();
  for (const f of item.fieldModel) {
    if (f.isXmp) {
      if (f.deleted) removeMetadataStream(doc);
      continue;
    }
    const nameKey = PDFName.of(f.infoKey);
    if (f.deleted) { info.delete(nameKey); continue; }
    if (f.isDate) {
      const d = parseFriendlyDateUTC(f.editValue);
      info.set(nameKey, d ? PDFString.fromDate(d) : PDFHexString.fromText(String(f.editValue)));
    } else {
      info.set(nameKey, PDFHexString.fromText(String(f.editValue)));
    }
  }
  item.workingBytes = await doc.save({ useObjectStreams: true });
}

// Deleting a dict *key* only unlinks the reference — pdf-lib's writer still serializes every
// indirect object it ever registered, reachable or not. A truly separate object (the XMP stream,
// or the whole Info dict once detached from the trailer) must also be evicted from the context,
// otherwise the "removed" metadata survives byte-for-byte as an orphaned, still-recoverable object.
function removeMetadataStream(doc) {
  const { PDFName } = PDFLib;
  const metaRef = doc.catalog.get(PDFName.of('Metadata'));
  doc.catalog.delete(PDFName.of('Metadata'));
  if (metaRef) doc.context.delete(metaRef);
}

function removeInfoDict(doc) {
  const oldInfoRef = doc.context.trailerInfo.Info;
  doc.context.trailerInfo.Info = undefined;
  if (oldInfoRef) doc.context.delete(oldInfoRef);
}

async function getStrippedBytes(item) {
  const { PDFDocument } = PDFLib;
  const doc = await PDFDocument.load(item.workingBytes, { updateMetadata: false, ignoreEncryption: true });
  removeInfoDict(doc);
  removeMetadataStream(doc);
  return doc.save({ useObjectStreams: true });
}

function visibleFields(item) { return item.fieldModel.filter(f => !f.deleted); }

function getMissingStandardKeys(item) {
  const present = new Set(visibleFields(item).filter(f => !f.isXmp).map(f => f.infoKey));
  return STANDARD_INFO_KEYS.filter(k => !present.has(k));
}

function addFieldToModel(item, infoKey) {
  if (!infoKey) return;
  const exists = visibleFields(item).some(f => !f.isXmp && f.infoKey.toLowerCase() === infoKey.toLowerCase());
  if (exists) return;
  const isDate = DATE_KEYS.has(infoKey);
  item.fieldModel.push({
    key: nextUid(), group: 'info', infoKey, label: STANDARD_LABELS[infoKey] || infoKey,
    sensitive: isSensitiveKey(infoKey), deleted: false, isDate,
    editValue: isDate ? formatDateUTC(new Date()) : ''
  });
}

function deleteFieldFromModel(item, key) {
  const f = item.fieldModel.find(x => x.key === key);
  if (f) f.deleted = true;
}

/* ===================================================================
   UI
   =================================================================== */

const dropzone = document.getElementById('dropzone');
const fileInput = document.getElementById('fileInput');
const grid = document.getElementById('grid');
const emptyState = document.getElementById('emptyState');
const downloadAllBtn = document.getElementById('downloadAllBtn');
const clearBtn = document.getElementById('clearBtn');
const modalOverlay = document.getElementById('modalOverlay');
const modalName = document.getElementById('modalName');
const modalBody = document.getElementById('modalBody');
const modalClose = document.getElementById('modalClose');
const modalCancel = document.getElementById('modalCancel');
const modalSaveBtn = document.getElementById('modalSave');

let items = [];
let idCounter = 0;
let currentModalItem = null;

function triggerDownload(url, filename) {
  const a = document.createElement('a');
  a.href = url;
  a.download = filename;
  document.body.appendChild(a);
  a.click();
  a.remove();
}

function updateWorkingUrl(item) {
  if (item.workingUrl) URL.revokeObjectURL(item.workingUrl);
  const blob = new Blob([item.workingBytes], { type: 'application/pdf' });
  item.workingUrl = URL.createObjectURL(blob);
  item.workingSize = blob.size;
}

function cardHTML(item) {
  if (item.error) {
    return `
      <div class="card card-error" data-id="${item.id}">
        <button class="card-remove" data-action="remove" data-id="${item.id}" title="Remove">✕</button>
        <div class="thumb pdf-thumb">📄</div>
        <div class="card-info">
          <div class="card-name" title="${escHtml(item.name)}">${escHtml(item.name)}</div>
          <div class="card-error-msg">${escHtml(item.error)}</div>
        </div>
      </div>`;
  }
  if (!item.pdfDoc) {
    return `
      <div class="card card-loading" data-id="${item.id}">
        <div class="thumb pdf-thumb">📄</div>
        <div class="card-info">
          <div class="card-name">${escHtml(item.name)}</div>
          <div class="card-meta">Reading…</div>
        </div>
      </div>`;
  }
  if (item.encrypted) {
    return `
      <div class="card" data-id="${item.id}">
        <button class="card-remove" data-action="remove" data-id="${item.id}" title="Remove">✕</button>
        <div class="thumb pdf-thumb">🔒</div>
        <div class="card-info">
          <div class="card-name" title="${escHtml(item.name)}">${escHtml(item.name)}</div>
          <div class="card-meta">${formatBytes(item.size)}${item.pageCount ? ' · ' + item.pageCount + ' page' + (item.pageCount === 1 ? '' : 's') : ''}</div>
          <div class="card-badges"><span class="badge encrypted">🔒 encrypted</span></div>
          <div class="card-encrypted-msg">Password-protected — metadata can't be read or edited while it's locked. Unlock it first with <a href="../unlock-pdf/index.html" target="_blank" rel="noopener">Unlock PDF</a>, then drop the result back in here.</div>
        </div>
      </div>`;
  }
  const fields = visibleFields(item);
  const badgeCount = fields.length;
  const sensitiveCount = fields.filter(f => f.sensitive).length;
  const sensitiveBadge = sensitiveCount > 0 ? `<span class="badge sensitive">🔒 ${sensitiveCount} sensitive</span>` : '';
  const modifiedInfo = item.modified
    ? `<div class="stripped-info">Current: ${formatBytes(item.workingSize)} <a href="${item.workingUrl}" download="edited-${escHtml(item.name)}" class="dl-link">Download ↓</a></div>`
    : '';
  return `
    <div class="card" data-id="${item.id}">
      <button class="card-remove" data-action="remove" data-id="${item.id}" title="Remove">✕</button>
      <div class="thumb pdf-thumb">📄</div>
      <div class="card-info">
        <div class="card-name" title="${escHtml(item.name)}">${escHtml(item.name)}</div>
        <div class="card-meta">${formatBytes(item.size)}${item.pageCount ? ' · ' + item.pageCount + ' page' + (item.pageCount === 1 ? '' : 's') : ''}</div>
        <div class="card-badges">
          <span class="badge">${badgeCount} field${badgeCount === 1 ? '' : 's'}</span>
          ${sensitiveBadge}
        </div>
        ${modifiedInfo}
        <div class="card-actions">
          <button class="small secondary" data-action="view" data-id="${item.id}">View &amp; edit</button>
          <button class="small" data-action="strip" data-id="${item.id}">Strip &amp; download</button>
        </div>
      </div>
    </div>`;
}

function renderAll() {
  emptyState.hidden = items.length > 0;
  grid.innerHTML = items.map(cardHTML).join('');
  downloadAllBtn.disabled = items.length === 0;
  clearBtn.disabled = items.length === 0;
}

function fieldRowHTML(f) {
  const delBtn = `<button class="field-delete" type="button" data-action="delete-field" data-key="${f.key}" title="Remove field">✕</button>`;
  const lockIcon = f.sensitive ? ' <span class="lock" title="Privacy-sensitive">🔒</span>' : '';
  let valueHtml;
  if (f.isXmp) {
    valueHtml = `<div class="xmp-preview">${escHtml(f.displayValueStatic)}</div>`;
  } else if (f.isDate) {
    valueHtml = `<input class="field-input" type="text" data-key="${f.key}" placeholder="YYYY-MM-DD HH:MM:SS UTC" value="${escHtml(f.editValue)}">`;
  } else {
    valueHtml = `<input class="field-input" type="text" data-key="${f.key}" value="${escHtml(f.editValue)}">`;
  }
  return `<tr class="${f.sensitive ? 'sensitive-row' : ''}"><td class="meta-label">${escHtml(f.label)}${lockIcon}</td><td class="meta-value">${valueHtml}</td><td class="meta-del">${delBtn}</td></tr>`;
}

function addFieldRowHTML(item) {
  const missing = getMissingStandardKeys(item);
  const opts = missing.map(k => `<option value="${escHtml(k)}">${escHtml(STANDARD_LABELS[k] || k)}</option>`).join('')
    + `<option value="__custom__">Custom field…</option>`;
  return `<div class="add-field-row">
    <select id="addFieldSelect">${opts}</select>
    <input type="text" id="addFieldCustomKey" class="field-input" placeholder="Custom key name" hidden>
    <button type="button" class="secondary small" data-action="add-field">+ Add field</button>
  </div>`;
}

function renderMetadataTable(item) {
  const fields = visibleFields(item);
  let html = '';
  if (!fields.length) {
    html += `<p class="modal-empty">No metadata found — this file is already clean.</p>`;
  } else {
    const byGroup = {};
    for (const f of fields) (byGroup[f.group] = byGroup[f.group] || []).push(f);
    for (const g of GROUP_ORDER) {
      const list = byGroup[g];
      if (!list || !list.length) continue;
      html += `<div class="meta-group"><div class="meta-group-title">${escHtml(GROUP_LABELS[g] || g)}</div><table class="meta-table">`;
      for (const f of list) html += fieldRowHTML(f);
      html += `</table></div>`;
    }
  }
  html += addFieldRowHTML(item);
  return html;
}

function refreshModalBody(item) {
  modalBody.innerHTML = renderMetadataTable(item);
}

function openModal(item) {
  currentModalItem = item;
  modalName.textContent = item.name;
  refreshModalBody(item);
  modalOverlay.classList.add('open');
}

function closeModal() {
  if (currentModalItem) {
    currentModalItem.fieldModel = extractFieldModel(currentModalItem.pdfDoc);
  }
  currentModalItem = null;
  modalOverlay.classList.remove('open');
  renderAll();
}

async function stripAndDownload(item) {
  if (!item.pdfDoc || item.error || item.encrypted) return;
  try {
    const bytes = await getStrippedBytes(item);
    item.workingBytes = bytes;
    item.pdfDoc = await PDFLib.PDFDocument.load(bytes, { updateMetadata: false, ignoreEncryption: true });
    item.fieldModel = extractFieldModel(item.pdfDoc);
    item.modified = true;
    updateWorkingUrl(item);
    renderAll();
    triggerDownload(item.workingUrl, 'cleaned-' + item.name);
  } catch (err) {
    item.error = 'Could not strip metadata: ' + err.message;
    renderAll();
  }
}

function removeItem(item) {
  if (item.workingUrl) URL.revokeObjectURL(item.workingUrl);
  items = items.filter(i => i !== item);
  renderAll();
}

function clearAll() {
  items.forEach(item => { if (item.workingUrl) URL.revokeObjectURL(item.workingUrl); });
  items = [];
  renderAll();
}

async function downloadAllCleaned() {
  const valid = items.filter(i => i.pdfDoc && !i.error && !i.encrypted);
  if (!valid.length) return;
  const originalLabel = downloadAllBtn.textContent;
  downloadAllBtn.disabled = true;
  downloadAllBtn.textContent = 'Zipping…';
  try {
    const zip = new JSZip();
    for (const item of valid) {
      try {
        const bytes = await getStrippedBytes(item);
        zip.file('cleaned-' + item.name, bytes);
      } catch (err) { /* skip this file, continue with the rest */ }
    }
    const content = await zip.generateAsync({ type: 'blob' });
    const url = URL.createObjectURL(content);
    triggerDownload(url, 'cleaned-pdfs.zip');
    setTimeout(() => URL.revokeObjectURL(url), 10000);
  } finally {
    downloadAllBtn.disabled = items.length === 0;
    downloadAllBtn.textContent = originalLabel;
  }
}

async function addFiles(fileList) {
  const files = Array.from(fileList).filter(f => f.type === 'application/pdf' || /\.pdf$/i.test(f.name));
  for (const file of files) {
    const item = {
      id: 'f' + (idCounter++), file, name: file.name, size: file.size,
      pdfDoc: null, fieldModel: [], pageCount: null, encrypted: false, error: null,
      modified: false, workingBytes: null, workingUrl: null, workingSize: null
    };
    items.push(item);
    renderAll();
    try {
      const buf = await file.arrayBuffer();
      const bytes = new Uint8Array(buf);
      const doc = await PDFLib.PDFDocument.load(bytes, { updateMetadata: false, ignoreEncryption: true });
      item.pdfDoc = doc;
      item.workingBytes = bytes;
      item.encrypted = !!doc.isEncrypted;
      try { item.pageCount = doc.getPageCount(); } catch (err) { item.pageCount = null; }
      if (!item.encrypted) item.fieldModel = extractFieldModel(doc);
    } catch (err) {
      item.error = (err && err.message) ? err.message : 'Could not read this PDF.';
    }
    renderAll();
  }
}

/* ---- event wiring ---- */

dropzone.addEventListener('click', () => fileInput.click());
fileInput.addEventListener('change', () => { addFiles(fileInput.files); fileInput.value = ''; });

['dragenter', 'dragover'].forEach(evt => dropzone.addEventListener(evt, e => { e.preventDefault(); dropzone.classList.add('drag'); }));
['dragleave', 'dragend'].forEach(evt => dropzone.addEventListener(evt, e => { e.preventDefault(); dropzone.classList.remove('drag'); }));
dropzone.addEventListener('drop', e => {
  e.preventDefault();
  dropzone.classList.remove('drag');
  if (e.dataTransfer && e.dataTransfer.files.length) addFiles(e.dataTransfer.files);
});

grid.addEventListener('click', e => {
  const btn = e.target.closest('[data-action]');
  if (!btn) return;
  const item = items.find(i => i.id === btn.dataset.id);
  if (!item) return;
  if (btn.dataset.action === 'view') openModal(item);
  else if (btn.dataset.action === 'strip') stripAndDownload(item);
  else if (btn.dataset.action === 'remove') removeItem(item);
});

function handleModalFieldChange(e) {
  const el = e.target.closest('[data-key]');
  if (!el || !currentModalItem) return;
  const f = currentModalItem.fieldModel.find(x => x.key === el.dataset.key);
  if (!f) return;
  f.editValue = el.value;
}
modalBody.addEventListener('input', handleModalFieldChange);
modalBody.addEventListener('change', e => {
  if (e.target.id === 'addFieldSelect') {
    const customInput = document.getElementById('addFieldCustomKey');
    if (customInput) customInput.hidden = e.target.value !== '__custom__';
    return;
  }
  handleModalFieldChange(e);
});

modalBody.addEventListener('click', e => {
  const btn = e.target.closest('[data-action]');
  if (!btn || !currentModalItem) return;
  if (btn.dataset.action === 'delete-field') {
    deleteFieldFromModel(currentModalItem, btn.dataset.key);
    refreshModalBody(currentModalItem);
  } else if (btn.dataset.action === 'add-field') {
    const sel = document.getElementById('addFieldSelect');
    if (!sel || !sel.value) return;
    if (sel.value === '__custom__') {
      const customInput = document.getElementById('addFieldCustomKey');
      const key = customInput ? customInput.value.trim() : '';
      if (key) addFieldToModel(currentModalItem, key);
    } else {
      addFieldToModel(currentModalItem, sel.value);
    }
    refreshModalBody(currentModalItem);
  }
});

modalSaveBtn.addEventListener('click', async () => {
  if (!currentModalItem) return;
  const item = currentModalItem;
  modalSaveBtn.disabled = true;
  modalSaveBtn.textContent = 'Saving…';
  try {
    await applyEdits(item);
    item.modified = true;
    item.fieldModel = extractFieldModel(item.pdfDoc);
    updateWorkingUrl(item);
    refreshModalBody(item);
    renderAll();
  } catch (err) {
    modalBody.insertAdjacentHTML('afterbegin', `<p class="modal-error">Could not save changes: ${escHtml(err.message)}</p>`);
  } finally {
    modalSaveBtn.disabled = false;
    modalSaveBtn.textContent = 'Save changes';
  }
});

downloadAllBtn.addEventListener('click', downloadAllCleaned);
clearBtn.addEventListener('click', clearAll);
modalClose.addEventListener('click', closeModal);
modalCancel.addEventListener('click', closeModal);
modalOverlay.addEventListener('click', e => { if (e.target === modalOverlay) closeModal(); });
document.addEventListener('keydown', e => { if (e.key === 'Escape' && modalOverlay.classList.contains('open')) closeModal(); });

renderAll();
