// Shrinks a PDF by recompressing its embedded JPEG images, entirely client-side.
//
// pdf-lib can read and write PDFs but has no re-encoding support for image data — it
// just carries DCTDecode (JPEG) streams through byte-for-byte. This walks every
// indirect object, finds Image XObjects stored as JPEG, decodes each one with the
// browser's own JPEG decoder (createImageBitmap), redraws it on canvas at a lower
// quality and (optionally) a lower resolution, and swaps the re-encoded bytes back
// into the same stream — then lets pdf-lib re-save the file with compact object
// streams. Images that don't shrink, or that the browser can't decode, are left
// untouched rather than risking a larger or broken file.

(function (global) {
  function loadImageBitmapFromJpeg(bytes) {
    const blob = new Blob([bytes], { type: 'image/jpeg' });
    return createImageBitmap(blob);
  }

  function canvasToJpegBytes(canvas, quality) {
    return new Promise((resolve, reject) => {
      canvas.toBlob((blob) => {
        if (!blob) { reject(new Error('canvas.toBlob failed')); return; }
        blob.arrayBuffer().then((buf) => resolve(new Uint8Array(buf)), reject);
      }, 'image/jpeg', quality);
    });
  }

  // True only for a lone /DCTDecode filter (or a single-entry array of it) — the
  // one shape this tool knows how to safely decode and re-encode.
  function isDctDecodeOnly(filterValue, PDFName, PDFArray) {
    if (!filterValue) return false;
    if (filterValue instanceof PDFName) return filterValue === PDFName.of('DCTDecode');
    if (filterValue instanceof PDFArray) {
      if (filterValue.size() !== 1) return false;
      const f = filterValue.get(0);
      return f instanceof PDFName && f === PDFName.of('DCTDecode');
    }
    return false;
  }

  // options: { quality: 0..1 JPEG quality, maxDimension: cap on the longer image
  // side in pixels (falsy = no cap) }.
  async function compressPdf(originalBytes, options) {
    const PDFLib = global.PDFLib;
    if (!PDFLib) throw new Error('pdf-lib not loaded');
    const { PDFDocument, PDFName, PDFArray, PDFRawStream } = PDFLib;
    const { quality, maxDimension } = options || {};

    let pdfDoc;
    try {
      pdfDoc = await PDFDocument.load(originalBytes.slice(0), { updateMetadata: false });
    } catch (e) {
      // pdf-lib's EncryptedPDFError doesn't survive `instanceof` reliably (its
      // ES5-targeted build subclasses the native Error type, a known TS gotcha
      // where the prototype chain doesn't carry through) — match on its fixed
      // message text instead.
      if (e && /is encrypted/.test(e.message || '')) throw new Error('ALREADY_ENCRYPTED');
      throw e;
    }

    const context = pdfDoc.context;
    const subtypeKey = PDFName.of('Subtype');
    const imageName = PDFName.of('Image');
    const filterKey = PDFName.of('Filter');
    const smaskKey = PDFName.of('SMask');
    const widthKey = PDFName.of('Width');
    const heightKey = PDFName.of('Height');
    const bpcKey = PDFName.of('BitsPerComponent');
    const colorSpaceKey = PDFName.of('ColorSpace');
    const decodeParmsKey = PDFName.of('DecodeParms');
    const decodeKey = PDFName.of('Decode');

    let imagesFound = 0;
    let imagesCompressed = 0;
    let originalImageBytesTotal = 0;
    let newImageBytesTotal = 0;

    for (const [, obj] of context.enumerateIndirectObjects()) {
      if (!(obj instanceof PDFRawStream)) continue;
      const dict = obj.dict;
      const subtype = dict.lookup(subtypeKey);
      if (!(subtype instanceof PDFName) || subtype !== imageName) continue;
      const filter = dict.lookup(filterKey);
      if (!isDctDecodeOnly(filter, PDFName, PDFArray)) continue;

      imagesFound++;
      const originalImageBytes = obj.contents;

      let bitmap;
      try {
        bitmap = await loadImageBitmapFromJpeg(originalImageBytes);
      } catch (e) {
        continue; // not decodable by the browser's JPEG decoder — leave untouched
      }

      const hasSMask = !!dict.lookup(smaskKey);
      let targetW = bitmap.width;
      let targetH = bitmap.height;
      // A soft mask (alpha) is pixel-aligned to the base image by most readers only
      // loosely — resizing just the base image risks a visible mismatch, so only
      // images without one get downscaled.
      if (!hasSMask && maxDimension && Math.max(targetW, targetH) > maxDimension) {
        const scale = maxDimension / Math.max(targetW, targetH);
        targetW = Math.max(1, Math.round(targetW * scale));
        targetH = Math.max(1, Math.round(targetH * scale));
      }

      const canvas = document.createElement('canvas');
      canvas.width = targetW;
      canvas.height = targetH;
      const ctx = canvas.getContext('2d');
      ctx.drawImage(bitmap, 0, 0, targetW, targetH);
      bitmap.close();

      let newBytes;
      try {
        newBytes = await canvasToJpegBytes(canvas, quality);
      } catch (e) {
        continue;
      }

      if (newBytes.length >= originalImageBytes.length) continue; // no win — keep original

      obj.contents = newBytes;
      dict.set(filterKey, PDFName.of('DCTDecode'));
      dict.delete(decodeParmsKey);
      dict.delete(decodeKey);
      dict.set(widthKey, context.obj(targetW));
      dict.set(heightKey, context.obj(targetH));
      dict.set(bpcKey, context.obj(8));
      dict.set(colorSpaceKey, PDFName.of('DeviceRGB'));

      imagesCompressed++;
      originalImageBytesTotal += originalImageBytes.length;
      newImageBytesTotal += newBytes.length;
    }

    const outBytes = await pdfDoc.save({ useObjectStreams: true });
    return {
      bytes: outBytes,
      imagesFound,
      imagesCompressed,
      originalImageBytesTotal,
      newImageBytesTotal,
    };
  }

  global.PDFCompressor = { compressPdf };
})(window);
