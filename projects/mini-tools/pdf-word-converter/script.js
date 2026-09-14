pdfjsLib.GlobalWorkerOptions.workerSrc = 'vendor/pdf.worker.min.js';

/* ============ mode switch ============ */

const modeSeg = document.getElementById('modeSeg');
const modePanels = {
  pdf2word: document.getElementById('pdf2word'),
  word2pdf: document.getElementById('word2pdf'),
};

function switchMode(mode) {
  modeSeg.querySelectorAll('.seg-btn').forEach((b) => b.classList.toggle('active', b.dataset.mode === mode));
  Object.entries(modePanels).forEach(([key, el]) => {
    el.hidden = key !== mode;
  });
}

modeSeg.addEventListener('click', (e) => {
  const btn = e.target.closest('.seg-btn');
  if (!btn) return;
  switchMode(btn.dataset.mode);
});

// Lets each mode hand a misplaced file (a .docx dropped on the PDF side, or
// vice versa) off to the other mode instead of just rejecting it.
const converterHandlers = {};

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
  // structure survive. Each line also keeps its dominant font size/name (for
  // heading/bold detection) and its individual text fragments with x
  // positions (for table-column detection) — see findColumnGaps().
  function linesFromItems(items, fontNameById) {
    const lines = [];
    let current = null;
    const realFontName = (item) => (fontNameById && fontNameById.get(item.fontName)) || item.fontName || '';

    for (const item of items) {
      const text = item.str;
      const x = item.transform[4];
      const y = item.transform[5];
      const height = item.height || Math.hypot(item.transform[2], item.transform[3]) || 10;

      if (!current) {
        current = { y, height, fontName: realFontName(item), parts: [] };
        lines.push(current);
      } else if (Math.abs(y - current.y) > Math.max(2, height * 0.4)) {
        current = { y, height, fontName: realFontName(item), parts: [] };
        lines.push(current);
      }
      if (height > current.height) {
        current.height = height;
        current.fontName = realFontName(item) || current.fontName;
      }

      if (text !== '') {
        const prev = current.parts[current.parts.length - 1];
        if (prev && prev.text !== '') {
          const gapBefore = x - (prev.x + prev.width);
          const needsSpace = gapBefore > height * 0.15 && !/\s$/.test(prev.text) && !/^\s/.test(text);
          if (needsSpace) prev.text += ' ';
        }
        current.parts.push({ text, x, width: item.width || 0 });
      }

      if (item.hasEOL) current = null;
    }

    return lines
      .map((l) => ({ y: l.y, height: l.height, fontName: l.fontName, parts: l.parts, text: l.parts.map((p) => p.text).join('') }))
      .filter((l) => l.text.trim() !== '');
  }

  function isBoldFont(fontName) {
    return /bold/i.test(fontName || '');
  }

  function isItalicFont(fontName) {
    return /italic|oblique/i.test(fontName || '');
  }

  function headingLevelFor(height, bodySize) {
    const ratio = height / bodySize;
    if (ratio >= 1.8) return docx.HeadingLevel.HEADING_1;
    if (ratio >= 1.35) return docx.HeadingLevel.HEADING_2;
    if (ratio >= 1.12) return docx.HeadingLevel.HEADING_3;
    return null;
  }

  // The font size that covers the most characters in the document is a
  // reliable stand-in for "body text size" — headings are usually larger
  // but short, so a plain per-line-count mode would be thrown off by them.
  function computeBodySize(allLines) {
    const charsBySize = new Map();
    for (const line of allLines) {
      const size = Math.round(line.height);
      charsBySize.set(size, (charsBySize.get(size) || 0) + line.text.length);
    }
    let bodySize = 12;
    let bestCount = -1;
    for (const [size, count] of charsBySize) {
      if (count > bestCount) {
        bestCount = count;
        bodySize = size;
      }
    }
    return bodySize;
  }

  // Splits a page into groups of consecutive lines with no paragraph-break
  // gap between them — each group is later tested to see if it's actually
  // a table (see detectTable).
  function splitIntoParagraphGroups(lines) {
    const groups = [];
    let current = [];
    lines.forEach((line, idx) => {
      if (idx > 0 && line.isNewParagraph) {
        if (current.length) groups.push(current);
        current = [];
      }
      current.push(line);
    });
    if (current.length) groups.push(current);
    return groups;
  }

  // Finds x-ranges that have no text ("ink") in ANY line of the group. A
  // real table column boundary is blank all the way down every row even
  // when the gap itself is narrow (just cell padding), which is what
  // distinguishes it from a coincidental gap in a couple of lines of prose.
  function findColumnGaps(lines) {
    // Whitespace-only text items (the gaps pdf.js reports between real
    // words) sometimes carry a wildly inflated width — e.g. a single space
    // between two table cells can be reported as wide as the entire gap
    // between them. Since they're blank by definition, they must never
    // count as "ink", or a real column gap would look filled in.
    const inkParts = (line) => line.parts.filter((p) => !/^\s*$/.test(p.text));

    let minX = Infinity;
    let maxX = -Infinity;
    lines.forEach((line) => inkParts(line).forEach((p) => {
      minX = Math.min(minX, p.x);
      maxX = Math.max(maxX, p.x + Math.max(p.width, 1));
    }));
    if (!Number.isFinite(minX) || maxX - minX < 20) return [];

    const width = Math.ceil(maxX - minX);
    const inkCountAtX = new Array(width + 1).fill(0);
    lines.forEach((line) => {
      const covered = new Array(width + 1).fill(false);
      inkParts(line).forEach((p) => {
        const start = Math.max(0, Math.floor(p.x - minX));
        const end = Math.min(width, Math.ceil(p.x + Math.max(p.width, 1) - minX));
        for (let x = start; x <= end; x++) covered[x] = true;
      });
      for (let x = 0; x <= width; x++) if (covered[x]) inkCountAtX[x]++;
    });

    const strayTolerance = Math.max(0, Math.floor(lines.length * 0.1));
    const gaps = [];
    let gapStart = null;
    for (let x = 0; x <= width; x++) {
      const isGap = inkCountAtX[x] <= strayTolerance;
      if (isGap && gapStart === null) gapStart = x;
      if ((!isGap || x === width) && gapStart !== null) {
        const gapEnd = isGap ? x + 1 : x;
        if (gapEnd - gapStart >= 3) gaps.push([minX + gapStart, minX + gapEnd]);
        gapStart = null;
      }
    }

    return gaps
      .filter(([a, b]) => a > minX + 2 && b < maxX - 2)
      .map(([a, b]) => (a + b) / 2);
  }

  function splitLineAtGaps(line, gapCenters) {
    const cells = new Array(gapCenters.length + 1).fill('');
    line.parts.forEach((part) => {
      const mid = part.x + (part.width || 0) / 2;
      const idx = gapCenters.findIndex((g) => mid < g);
      cells[idx === -1 ? gapCenters.length : idx] += part.text;
    });
    return cells.map((c) => c.trim());
  }

  // A group of lines is treated as a table only if splitting it at the
  // shared column gaps actually produces multiple non-empty cells for most
  // of its rows — a single stray gap in a couple of lines of prose won't
  // pass this.
  function detectTable(lines) {
    if (lines.length < 2) return null;
    // Every row of a real table shares roughly the same font size — this
    // keeps an unrelated heading (usually larger) from being swept in
    // either as a bogus leading row or, while a table candidate is being
    // extended row by row, as a bogus trailing one.
    const refHeight = lines[1].height;
    if (!lines.every((l) => Math.abs(l.height - refHeight) <= refHeight * 0.3)) {
      return null;
    }
    const gapSource = lines.length >= 3 ? lines.slice(1) : lines;
    const gapCenters = findColumnGaps(gapSource);
    if (gapCenters.length === 0) return null;

    const rows = lines.map((line) => splitLineAtGaps(line, gapCenters));
    const nonEmptyRows = rows.filter((cells) => cells.some((c) => c !== ''));
    const multiCellRows = rows.filter((cells) => cells.filter((c) => c !== '').length >= 2);
    if (nonEmptyRows.length === 0 || multiCellRows.length < Math.max(2, Math.ceil(nonEmptyRows.length * 0.6))) {
      return null;
    }

    let minX = Infinity;
    let maxX = -Infinity;
    lines.forEach((line) => line.parts.filter((p) => !/^\s*$/.test(p.text)).forEach((p) => {
      minX = Math.min(minX, p.x);
      maxX = Math.max(maxX, p.x + (p.width || 0));
    }));
    const boundaries = [minX, ...gapCenters, maxX];
    const columnWidthsPt = boundaries.slice(1).map((b, i) => Math.max(b - boundaries[i], 40));

    return { rows, columnWidthsPt };
  }

  // Scans a page's lines (independently of paragraph-break gaps, since a
  // bordered table's row spacing often looks just like a paragraph break)
  // for maximal runs that qualify as a table via detectTable().
  function findTableRegions(lines) {
    const regions = [];
    let i = 0;
    while (i < lines.length - 1) {
      // Require the first rows alone to already look tabular before trying
      // to extend — otherwise a heading line sitting just above a real
      // table can get swept in as a bogus first row, since a lenient
      // multi-row match can tolerate one bad row among several good ones.
      // Try 2 rows first, then 3 (in case row 1 is a differently-styled
      // header that only agrees with the data once there are 2 data rows
      // to find shared gaps from).
      let bestEnd = i + 2;
      let bestTable = detectTable(lines.slice(i, bestEnd));
      if (!bestTable && i + 3 <= lines.length) {
        bestEnd = i + 3;
        bestTable = detectTable(lines.slice(i, bestEnd));
      }
      if (!bestTable) {
        i += 1;
        continue;
      }
      for (let j = bestEnd + 1; j <= lines.length; j++) {
        const candidate = detectTable(lines.slice(i, j));
        if (!candidate) break;
        bestEnd = j;
        bestTable = candidate;
      }
      regions.push({ start: i, end: bestEnd, table: bestTable });
      i = bestEnd;
    }
    return regions;
  }

  function buildTable(rows, columnWidthsPt) {
    const tableRows = rows.map((cells) => new docx.TableRow({
      children: cells.map((text, idx) => new docx.TableCell({
        width: { size: Math.round((columnWidthsPt[idx] || 60) * 20), type: docx.WidthType.DXA },
        children: [new docx.Paragraph({ children: [new docx.TextRun(text)] })],
      })),
    }));
    return new docx.Table({ rows: tableRows });
  }

  async function convert(file) {
    progress.hidden = false;
    result.hidden = true;
    errorMsg.hidden = true;

    const buf = await file.arrayBuffer();
    const pdfDoc = await pdfjsLib.getDocument({ data: buf }).promise;
    const numPages = pdfDoc.numPages;

    progressLabel.textContent = 'Extracting text…';
    const pages = [];
    const allLines = [];

    for (let i = 1; i <= numPages; i++) {
      progressFill.style.width = Math.round(((i - 1) / numPages) * 50) + '%';

      const page = await pdfDoc.getPage(i);
      const textContent = await page.getTextContent();

      // getTextContent() alone doesn't resolve font objects — their real
      // (embedded) names, which is where "Bold"/"Italic" shows up, only
      // become available after the content stream has been processed once.
      await page.getOperatorList();
      const fontNameById = new Map();
      for (const fontId of Object.keys(textContent.styles)) {
        try {
          const font = page.commonObjs.get(fontId);
          if (font && font.name) fontNameById.set(fontId, font.name);
        } catch (e) {
          // leave unresolved fonts out of the map; isBoldFont just won't match them
        }
      }

      const lines = linesFromItems(textContent.items, fontNameById);

      let prevBottom = null;
      lines.forEach((line, idx) => {
        const gapBefore = prevBottom === null ? 0 : prevBottom - (line.y + line.height);
        line.isNewParagraph = idx > 0 && gapBefore > line.height * 0.6;
        prevBottom = line.y;
      });

      pages.push(lines);
      allLines.push(...lines);
    }

    const totalChars = allLines.reduce((sum, l) => sum + l.text.length, 0);
    const bodySize = computeBodySize(allLines);
    const nodes = [];

    pages.forEach((lines, pageIdx) => {
      progressLabel.textContent = `Building page ${pageIdx + 1} of ${numPages}…`;
      progressFill.style.width = Math.round(50 + (pageIdx / numPages) * 50) + '%';

      if (lines.length === 0) {
        nodes.push(new docx.Paragraph({
          pageBreakBefore: pageIdx > 0,
          children: [new docx.TextRun({ text: '[This page has no extractable text]', italics: true })],
        }));
        return;
      }

      // Table regions are found first, independently of paragraph-break
      // gaps (a bordered table's row spacing often looks just like a
      // paragraph break); whatever's left over is grouped into paragraphs.
      const regions = findTableRegions(lines);
      const segments = [];
      let cursor = 0;
      regions.forEach((region) => {
        if (region.start > cursor) segments.push({ type: 'lines', lines: lines.slice(cursor, region.start) });
        segments.push({ type: 'table', table: region.table });
        cursor = region.end;
      });
      if (cursor < lines.length) segments.push({ type: 'lines', lines: lines.slice(cursor) });

      let isFirstNodeOnPage = true;

      segments.forEach((segment) => {
        if (segment.type === 'table') {
          if (isFirstNodeOnPage && pageIdx > 0) {
            nodes.push(new docx.Paragraph({ pageBreakBefore: true, text: '' }));
          } else if (!isFirstNodeOnPage) {
            nodes.push(new docx.Paragraph({ text: '' }));
          }
          nodes.push(buildTable(segment.table.rows, segment.table.columnWidthsPt));
          isFirstNodeOnPage = false;
          return;
        }

        splitIntoParagraphGroups(segment.lines).forEach((group, groupIdx) => {
          group.forEach((line, lineIdx) => {
            const isFirstLineOfNode = isFirstNodeOnPage;
            if (!isFirstLineOfNode && lineIdx === 0 && groupIdx > 0) {
              nodes.push(new docx.Paragraph({ text: '' }));
            }
            const heading = headingLevelFor(line.height, bodySize);
            const bold = !heading && isBoldFont(line.fontName);
            const italics = isItalicFont(line.fontName);
            nodes.push(new docx.Paragraph({
              pageBreakBefore: isFirstLineOfNode && pageIdx > 0,
              heading: heading || undefined,
              children: [new docx.TextRun({ text: line.text, bold: bold || undefined, italics: italics || undefined })],
            }));
            isFirstNodeOnPage = false;
          });
        });
      });
    });

    progressFill.style.width = '100%';

    const doc = new docx.Document({ sections: [{ properties: {}, children: nodes }] });
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
      if (file.name.toLowerCase().endsWith('.docx') && converterHandlers.docx) {
        switchMode('word2pdf');
        converterHandlers.docx(file);
        return;
      }
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

  converterHandlers.pdf = handleFile;
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

  // Word paragraph alignment/indent are direct formatting that mammoth's HTML
  // writer discards. We smuggle them through as an invisible marker at the
  // start of each paragraph's text, then translate that marker into an
  // inline style on the paragraph's actual DOM element once it's parsed —
  // this works regardless of which tag (p, h1, li, td>p…) mammoth picked.
  const FMT_MARK = '\uE000';
  const FMT_MARKER_RE = new RegExp('^' + FMT_MARK + 'FMT:([^' + FMT_MARK + ']*)' + FMT_MARK);

  function docxAlignToCss(alignment) {
    switch (alignment) {
      case 'center': return 'center';
      case 'right': case 'end': return 'right';
      case 'both': return 'justify';
      default: return null;
    }
  }

  function twipsToPx(twips) {
    const n = parseInt(twips, 10);
    return Number.isFinite(n) ? Math.round(n / 20 * (96 / 72)) : null;
  }

  function buildParagraphFormattingTransform() {
    return mammoth.transforms.paragraph((paragraph) => {
      const align = docxAlignToCss(paragraph.alignment);
      const marginLeft = paragraph.indent && twipsToPx(paragraph.indent.start);
      const textIndent = paragraph.indent && twipsToPx(paragraph.indent.firstLine);
      if (!align && !marginLeft && !textIndent) return paragraph;

      const params = [];
      if (align) params.push('align=' + align);
      if (marginLeft) params.push('marginLeft=' + marginLeft);
      if (textIndent) params.push('textIndent=' + textIndent);

      const marker = { type: 'text', value: FMT_MARK + 'FMT:' + params.join('&') + FMT_MARK };
      return Object.assign({}, paragraph, { children: [marker].concat(paragraph.children) });
    });
  }

  function applyParagraphFormatting(root) {
    const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
    const textNodes = [];
    let node;
    while ((node = walker.nextNode())) textNodes.push(node);

    for (const textNode of textNodes) {
      const match = FMT_MARKER_RE.exec(textNode.data);
      if (!match) continue;
      textNode.data = textNode.data.slice(match[0].length);
      const el = textNode.parentElement;
      if (!el) continue;
      for (const pair of match[1].split('&')) {
        const [key, value] = pair.split('=');
        if (key === 'align') el.style.textAlign = value;
        else if (key === 'marginLeft') el.style.marginLeft = value + 'px';
        else if (key === 'textIndent') el.style.textIndent = value + 'px';
      }
    }
  }

  async function convert(file) {
    progress.hidden = false;
    result.hidden = true;
    errorMsg.hidden = true;
    progressLabel.textContent = 'Reading document…';
    progressFill.style.width = '15%';

    const buf = await file.arrayBuffer();
    const { value: html } = await mammoth.convertToHtml(
      { arrayBuffer: buf },
      { transformDocument: buildParagraphFormattingTransform() }
    );

    renderTarget.innerHTML = html;
    applyParagraphFormatting(renderTarget);
    progressLabel.textContent = 'Rendering pages…';
    progressFill.style.width = '45%';

    const jsPdfDoc = new jspdf.jsPDF({ unit: 'pt', format: 'a4', orientation: 'portrait' });
    const pageWidthPt = jsPdfDoc.internal.pageSize.getWidth();
    const pageHeightPt = jsPdfDoc.internal.pageSize.getHeight();
    const marginPt = 36;
    const contentWidthPt = pageWidthPt - marginPt * 2;
    const usableHeightPt = pageHeightPt - marginPt * 2;
    const windowWidthPx = renderTarget.offsetWidth;
    const scale = contentWidthPt / windowWidthPx;

    // jsPDF's own multi-page html() pagination is unreliable — with content
    // taller than one page it has been observed to emit a blank first page
    // and duplicate content onto the second. Render once to a single tall
    // canvas instead and slice it into pages ourselves.
    const fullCanvas = await html2canvas(renderTarget, {
      scale,
      useCORS: true,
      backgroundColor: '#ffffff',
      windowWidth: windowWidthPx,
    });

    const pageHeightPx = Math.floor(usableHeightPt);
    const totalPages = Math.max(1, Math.ceil(fullCanvas.height / pageHeightPx));

    for (let i = 0; i < totalPages; i++) {
      progressLabel.textContent = `Rendering page ${i + 1} of ${totalPages}…`;
      progressFill.style.width = (45 + Math.round((i / totalPages) * 50)) + '%';

      const sliceHeight = Math.min(pageHeightPx, fullCanvas.height - i * pageHeightPx);
      const sliceCanvas = document.createElement('canvas');
      sliceCanvas.width = fullCanvas.width;
      sliceCanvas.height = sliceHeight;
      sliceCanvas.getContext('2d').drawImage(
        fullCanvas, 0, i * pageHeightPx, fullCanvas.width, sliceHeight,
        0, 0, fullCanvas.width, sliceHeight
      );

      if (i > 0) jsPdfDoc.addPage();
      jsPdfDoc.addImage(sliceCanvas.toDataURL('image/png'), 'PNG', marginPt, marginPt, contentWidthPt, sliceHeight);
    }

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
      const looksLikePdf = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');
      if (looksLikePdf && converterHandlers.pdf) {
        switchMode('pdf2word');
        converterHandlers.pdf(file);
        return;
      }
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

  converterHandlers.docx = handleFile;
  setupDropzone(dropzone, fileInput, handleFile);
  changeFileBtn.addEventListener('click', () => {
    resetState();
    panel.hidden = true;
  });
})();
