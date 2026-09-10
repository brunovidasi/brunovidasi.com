pdfjsLib.GlobalWorkerOptions.workerSrc = 'vendor/pdf.worker.min.js';

/* ============ mode switch ============ */

const modeSeg = document.getElementById('modeSeg');
const modePanels = {
  pdf2word: document.getElementById('pdf2word'),
  word2pdf: document.getElementById('word2pdf'),
};

modeSeg.addEventListener('click', (e) => {
  const btn = e.target.closest('.seg-btn');
  if (!btn) return;
  modeSeg.querySelectorAll('.seg-btn').forEach((b) => b.classList.toggle('active', b === btn));
  const mode = btn.dataset.mode;
  Object.entries(modePanels).forEach(([key, el]) => {
    el.hidden = key !== mode;
  });
});

/* ============ shared helpers ============ */

function baseName(name) {
  const i = name.lastIndexOf('.');
  return i > 0 ? name.slice(0, i) : name;
}

function setupDropzone(dropzone, input, onFile) {
  dropzone.addEventListener('click', () => input.click());
  input.addEventListener('change', () => {
    if (input.files[0]) onFile(input.files[0]);
    input.value = '';
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
    if (f) onFile(f);
  });
}

/* ============ PDF -> WORD ============ */

(function initPdfToWord() {
  const dropzone = document.getElementById('dropzonePdf');
  const fileInput = document.getElementById('fileInputPdf');
  const panel = document.getElementById('panelPdf');
  const fileNameEl = document.getElementById('fileNamePdf');
  const changeFileBtn = document.getElementById('changeFileBtnPdf');
  const errorMsg = document.getElementById('errorMsgPdf');
  const progress = document.getElementById('progressPdf');
  const progressLabel = document.getElementById('progressLabelPdf');
  const progressFill = document.getElementById('progressFillPdf');
  const result = document.getElementById('resultPdf');
  const resultText = document.getElementById('resultTextPdf');
  const downloadLink = document.getElementById('downloadLinkPdf');

  let selectedFile = null;

  function showError(msg) {
    errorMsg.textContent = msg;
    errorMsg.hidden = false;
  }

  function resetState() {
    selectedFile = null;
    errorMsg.hidden = true;
    progress.hidden = true;
    progressFill.style.width = '0%';
    result.hidden = true;
    if (downloadLink.href) {
      URL.revokeObjectURL(downloadLink.href);
      downloadLink.removeAttribute('href');
    }
  }

  // Groups a page's raw text items into lines, then flags paragraph breaks
  // using vertical gaps between lines. Reading order and rough paragraph
  // structure survive; column layout, tables, and images do not.
  function linesFromItems(items) {
    const lines = [];
    let current = null;

    for (const item of items) {
      const text = item.str;
      const x = item.transform[4];
      const y = item.transform[5];
      const height = item.height || Math.hypot(item.transform[2], item.transform[3]) || 10;

      if (!current) {
        current = { y, height, parts: [] };
        lines.push(current);
      } else if (Math.abs(y - current.y) > Math.max(2, height * 0.4)) {
        current = { y, height, parts: [] };
        lines.push(current);
      }

      if (text !== '') {
        const prev = current.parts[current.parts.length - 1];
        if (prev && prev.text !== '') {
          const gap = x - (prev.x + prev.width);
          const needsSpace = gap > height * 0.15 && !/\s$/.test(prev.text) && !/^\s/.test(text);
          if (needsSpace) current.parts.push({ text: ' ', x: prev.x + prev.width, width: 0 });
        }
        current.parts.push({ text, x, width: item.width || 0 });
      }

      if (item.hasEOL) current = null;
    }

    return lines
      .map((l) => ({ y: l.y, height: l.height, text: l.parts.map((p) => p.text).join('') }))
      .filter((l) => l.text.trim() !== '');
  }

  async function convert(file) {
    progress.hidden = false;
    result.hidden = true;
    errorMsg.hidden = true;

    const buf = await file.arrayBuffer();
    const pdfDoc = await pdfjsLib.getDocument({ data: buf }).promise;
    const numPages = pdfDoc.numPages;
    const paragraphs = [];
    let totalChars = 0;

    for (let i = 1; i <= numPages; i++) {
      progressLabel.textContent = `Extracting page ${i} of ${numPages}…`;
      progressFill.style.width = Math.round(((i - 1) / numPages) * 100) + '%';

      const page = await pdfDoc.getPage(i);
      const textContent = await page.getTextContent();
      const lines = linesFromItems(textContent.items);

      let prevBottom = null;
      lines.forEach((line, idx) => {
        totalChars += line.text.length;
        const gapBefore = prevBottom === null ? 0 : prevBottom - (line.y + line.height);
        const isNewParagraph = idx === 0 || gapBefore > line.height * 0.6;
        if (isNewParagraph && idx !== 0) {
          paragraphs.push(new docx.Paragraph({ text: '' }));
        }
        paragraphs.push(new docx.Paragraph({
          pageBreakBefore: idx === 0 && i > 1,
          children: [new docx.TextRun(line.text)],
        }));
        prevBottom = line.y;
      });

      if (lines.length === 0) {
        paragraphs.push(new docx.Paragraph({
          pageBreakBefore: i > 1,
          children: [new docx.TextRun({ text: '[This page has no extractable text]', italics: true })],
        }));
      }
    }

    progressFill.style.width = '100%';

    const doc = new docx.Document({ sections: [{ properties: {}, children: paragraphs }] });
    const blob = await docx.Packer.toBlob(doc);

    downloadLink.href = URL.createObjectURL(blob);
    downloadLink.download = baseName(file.name) + '.docx';
    resultText.textContent = totalChars < 20
      ? `Converted ${numPages} page${numPages === 1 ? '' : 's'} — very little text was found, so this PDF may be scanned images rather than real text.`
      : `Converted ${numPages} page${numPages === 1 ? '' : 's'} to an editable Word document.`;
    progress.hidden = true;
    result.hidden = false;
  }

  function handleFile(file) {
    const looksLikePdf = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');
    if (!looksLikePdf) {
      resetState();
      panel.hidden = true;
      showError('Please choose a PDF file.');
      return;
    }
    resetState();
    selectedFile = file;
    fileNameEl.textContent = file.name;
    panel.hidden = false;
    convert(file).catch((e) => {
      progress.hidden = true;
      showError("Couldn't convert this file" + (e && e.message ? ': ' + e.message : '.'));
    });
  }

  setupDropzone(dropzone, fileInput, handleFile);
  changeFileBtn.addEventListener('click', () => {
    resetState();
    panel.hidden = true;
  });
})();

/* ============ WORD -> PDF ============ */

(function initWordToPdf() {
  const dropzone = document.getElementById('dropzoneDocx');
  const fileInput = document.getElementById('fileInputDocx');
  const panel = document.getElementById('panelDocx');
  const fileNameEl = document.getElementById('fileNameDocx');
  const changeFileBtn = document.getElementById('changeFileBtnDocx');
  const errorMsg = document.getElementById('errorMsgDocx');
  const progress = document.getElementById('progressDocx');
  const progressLabel = document.getElementById('progressLabelDocx');
  const progressFill = document.getElementById('progressFillDocx');
  const result = document.getElementById('resultDocx');
  const resultText = document.getElementById('resultTextDocx');
  const downloadLink = document.getElementById('downloadLinkDocx');
  const renderTarget = document.getElementById('renderTarget');

  function showError(msg) {
    errorMsg.textContent = msg;
    errorMsg.hidden = false;
  }

  function resetState() {
    errorMsg.hidden = true;
    progress.hidden = true;
    progressFill.style.width = '0%';
    result.hidden = true;
    renderTarget.innerHTML = '';
    if (downloadLink.href) {
      URL.revokeObjectURL(downloadLink.href);
      downloadLink.removeAttribute('href');
    }
  }

  async function convert(file) {
    progress.hidden = false;
    result.hidden = true;
    errorMsg.hidden = true;
    progressLabel.textContent = 'Reading document…';
    progressFill.style.width = '15%';

    const buf = await file.arrayBuffer();
    const { value: html } = await mammoth.convertToHtml({ arrayBuffer: buf });

    renderTarget.innerHTML = html;
    progressLabel.textContent = 'Rendering pages…';
    progressFill.style.width = '45%';

    const jsPdfDoc = new jspdf.jsPDF({ unit: 'pt', format: 'a4', orientation: 'portrait' });
    const pageWidthPt = jsPdfDoc.internal.pageSize.getWidth();
    const marginPt = 36;
    const contentWidthPt = pageWidthPt - marginPt * 2;
    const windowWidthPx = renderTarget.offsetWidth;

    await new Promise((resolve, reject) => {
      jsPdfDoc.html(renderTarget, {
        x: marginPt,
        y: marginPt,
        width: contentWidthPt,
        windowWidth: windowWidthPx,
        html2canvas: { scale: contentWidthPt / windowWidthPx, useCORS: true, backgroundColor: '#ffffff' },
        callback: () => resolve(),
      }).catch(reject);
    });

    progressFill.style.width = '100%';
    const blob = jsPdfDoc.output('blob');
    downloadLink.href = URL.createObjectURL(blob);
    downloadLink.download = baseName(file.name) + '.pdf';
    resultText.textContent = `Converted "${file.name}" to PDF.`;
    progress.hidden = true;
    result.hidden = false;
    renderTarget.innerHTML = '';
  }

  function handleFile(file) {
    const looksLikeDocx = file.name.toLowerCase().endsWith('.docx');
    if (!looksLikeDocx) {
      resetState();
      panel.hidden = true;
      showError('Please choose a .docx file (older .doc files are not supported).');
      return;
    }
    resetState();
    fileNameEl.textContent = file.name;
    panel.hidden = false;
    convert(file).catch((e) => {
      progress.hidden = true;
      renderTarget.innerHTML = '';
      showError("Couldn't convert this file" + (e && e.message ? ': ' + e.message : '.'));
    });
  }

  setupDropzone(dropzone, fileInput, handleFile);
  changeFileBtn.addEventListener('click', () => {
    resetState();
    panel.hidden = true;
  });
})();
