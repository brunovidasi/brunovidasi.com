// Removes a PDF's password while keeping its original vector/text content intact,
// instead of flattening pages to images.
//
// pdf-lib can *write* PDFs but has no built-in decryption, and pdf.js can decrypt
// for rendering but never exposes the raw file key or lets you re-serialize. So
// this hand-implements the standard PDF security handler (PDF32000-1:2008 §7.6,
// plus the AES-256 "hardened hash" extension from ISO 32000-2 used by modern
// Acrobat) against pdf-lib's low-level object model: derive the file's real
// encryption key from the password, decrypt every stream/string in place, expand
// any (now-decryptable) compressed object streams, drop the /Encrypt entry, and
// let pdf-lib re-save a normal, unencrypted PDF.
//
// Throws on anything unsupported — the caller is expected to fall back to the
// image-based renderer when that happens.

(function (global) {
  const PASSWORD_PAD = new Uint8Array([
    0x28,0xbf,0x4e,0x5e,0x4e,0x75,0x8a,0x41,0x64,0x00,0x4e,0x56,0xff,0xfa,0x01,0x08,
    0x2e,0x2e,0x00,0xb6,0xd0,0x68,0x3e,0x80,0x2f,0x0c,0xa9,0xfe,0x64,0x53,0x69,0x7a,
  ]);

  // ---------------------------------------------------------------- byte utils

  function concatBytes(...parts) {
    let len = 0;
    for (const p of parts) len += p.length;
    const out = new Uint8Array(len);
    let off = 0;
    for (const p of parts) { out.set(p, off); off += p.length; }
    return out;
  }

  function bytesEqual(a, b) {
    if (a.length !== b.length) return false;
    for (let i = 0; i < a.length; i++) if (a[i] !== b[i]) return false;
    return true;
  }

  function bytesToHex(bytes) {
    let hex = '';
    for (let i = 0; i < bytes.length; i++) hex += bytes[i].toString(16).padStart(2, '0');
    return hex;
  }

  function repeatBytes(unit, times) {
    const out = new Uint8Array(unit.length * times);
    for (let i = 0; i < times; i++) out.set(unit, i * unit.length);
    return out;
  }

  function int32LE(n) {
    const v = n >>> 0;
    return new Uint8Array([v & 0xff, (v >>> 8) & 0xff, (v >>> 16) & 0xff, (v >>> 24) & 0xff]);
  }

  function padPassword(pwBytes) {
    const out = new Uint8Array(32);
    const n = Math.min(pwBytes.length, 32);
    out.set(pwBytes.subarray(0, n));
    out.set(PASSWORD_PAD.subarray(0, 32 - n), n);
    return out;
  }

  // -------------------------------------------------------------------- MD5

  function md5(bytes) {
    const s = [
      7,12,17,22, 7,12,17,22, 7,12,17,22, 7,12,17,22,
      5, 9,14,20, 5, 9,14,20, 5, 9,14,20, 5, 9,14,20,
      4,11,16,23, 4,11,16,23, 4,11,16,23, 4,11,16,23,
      6,10,15,21, 6,10,15,21, 6,10,15,21, 6,10,15,21,
    ];
    const K = new Int32Array([
      -680876936,-389564586,606105819,-1044525330,-176418897,1200080426,-1473231341,-45705983,
      1770035416,-1958414417,-42063,-1990404162,1804603682,-40341101,-1502002290,1236535329,
      -165796510,-1069501632,643717713,-373897302,-701558691,38016083,-660478335,-405537848,
      568446438,-1019803690,-187363961,1163531501,-1444681467,-51403784,1735328473,-1926607734,
      -378558,-2022574463,1839030562,-35309556,-1530992060,1272893353,-155497632,-1094730640,
      681279174,-358537222,-722521979,76029189,-640364487,-421815835,530742520,-995338651,
      -198630844,1126891415,-1416354905,-57434055,1700485571,-1894986606,-1051523,-2054922799,
      1873313359,-30611744,-1560198380,1309151649,-145523070,-1120210379,718787259,-343485551,
    ]);
    const rotl = (x, c) => (x << c) | (x >>> (32 - c));

    const bitLenLow = (bytes.length * 8) >>> 0;
    const total = (Math.floor((bytes.length + 8) / 64) + 1) * 64;
    const buf = new Uint8Array(total);
    buf.set(bytes);
    buf[bytes.length] = 0x80;
    const dv = new DataView(buf.buffer);
    dv.setUint32(total - 8, bitLenLow, true);
    dv.setUint32(total - 4, 0, true); // input is always well under 2^32 bits for our use case

    let a0 = 0x67452301, b0 = 0xefcdab89, c0 = 0x98badcfe, d0 = 0x10325476;
    const M = new Int32Array(16);
    for (let chunk = 0; chunk < total; chunk += 64) {
      for (let i = 0; i < 16; i++) M[i] = dv.getInt32(chunk + i * 4, true);
      let A = a0, B = b0, C = c0, D = d0;
      for (let i = 0; i < 64; i++) {
        let F, g;
        if (i < 16) { F = (B & C) | (~B & D); g = i; }
        else if (i < 32) { F = (D & B) | (~D & C); g = (5 * i + 1) % 16; }
        else if (i < 48) { F = B ^ C ^ D; g = (3 * i + 5) % 16; }
        else { F = C ^ (B | ~D); g = (7 * i) % 16; }
        F = (F + A + K[i] + M[g]) | 0;
        A = D; D = C; C = B;
        B = (B + rotl(F, s[i])) | 0;
      }
      a0 = (a0 + A) | 0; b0 = (b0 + B) | 0; c0 = (c0 + C) | 0; d0 = (d0 + D) | 0;
    }
    const out = new Uint8Array(16);
    const outDv = new DataView(out.buffer);
    outDv.setInt32(0, a0, true);
    outDv.setInt32(4, b0, true);
    outDv.setInt32(8, c0, true);
    outDv.setInt32(12, d0, true);
    return out;
  }

  // -------------------------------------------------------------------- RC4

  function rc4(keyBytes, data) {
    const S = new Uint8Array(256);
    for (let i = 0; i < 256; i++) S[i] = i;
    let j = 0;
    for (let i = 0; i < 256; i++) {
      j = (j + S[i] + keyBytes[i % keyBytes.length]) & 0xff;
      const t = S[i]; S[i] = S[j]; S[j] = t;
    }
    const out = new Uint8Array(data.length);
    let i = 0; j = 0;
    for (let k = 0; k < data.length; k++) {
      i = (i + 1) & 0xff;
      j = (j + S[i]) & 0xff;
      const t = S[i]; S[i] = S[j]; S[j] = t;
      out[k] = data[k] ^ S[(S[i] + S[j]) & 0xff];
    }
    return out;
  }

  // ------------------------------------------------------- AES via SubtleCrypto

  async function aesCbcDecrypt(keyBytes, ivBytes, data) {
    const key = await crypto.subtle.importKey('raw', keyBytes, 'AES-CBC', false, ['decrypt']);
    return new Uint8Array(await crypto.subtle.decrypt({ name: 'AES-CBC', iv: ivBytes }, key, data));
  }

  // Raw (unpadded) CBC encrypt: SubtleCrypto always PKCS7-pads on encrypt, but CBC
  // is causal — an appended padding block never changes the ciphertext of the
  // blocks before it — so the first data.length bytes of its output are exactly
  // the padding-free encryption we need.
  async function aesCbcEncryptRaw(keyBytes, ivBytes, data) {
    const key = await crypto.subtle.importKey('raw', keyBytes, 'AES-CBC', false, ['encrypt']);
    const full = new Uint8Array(await crypto.subtle.encrypt({ name: 'AES-CBC', iv: ivBytes }, key, data));
    return full.slice(0, data.length);
  }

  // Raw (unpadded) CBC decrypt: SubtleCrypto always validates+strips PKCS7 padding
  // on decrypt and throws if the last block isn't validly padded, which raw PDF
  // key material never is. Fix: manufacture a ciphertext block that we know will
  // decrypt to a full valid padding block (by CBC-encrypting a 0x10-filled block
  // using the real ciphertext's last block as the IV), append it, decrypt the
  // whole thing normally, then trust the padding strip to remove exactly that.
  async function aesCbcDecryptRaw(keyBytes, ivBytes, data) {
    const lastBlock = data.slice(data.length - 16);
    const padBlock = new Uint8Array(16).fill(16);
    const extraBlock = await aesCbcEncryptRaw(keyBytes, lastBlock, padBlock);
    const withPad = concatBytes(data, extraBlock);
    const decKey = await crypto.subtle.importKey('raw', keyBytes, 'AES-CBC', false, ['decrypt']);
    const plain = new Uint8Array(await crypto.subtle.decrypt({ name: 'AES-CBC', iv: ivBytes }, decKey, withPad));
    return plain.slice(0, data.length);
  }

  async function sha256(b) { return new Uint8Array(await crypto.subtle.digest('SHA-256', b)); }
  async function sha384(b) { return new Uint8Array(await crypto.subtle.digest('SHA-384', b)); }
  async function sha512(b) { return new Uint8Array(await crypto.subtle.digest('SHA-512', b)); }

  // ------------------------------------------------- legacy key derivation (R2-4)

  function computeFileKeyLegacy({ passwordBytes, O, P, id0, R, keyLenBytes, encryptMetadata }) {
    const padded = padPassword(passwordBytes);
    let input = concatBytes(padded, O, int32LE(P), id0);
    if (R >= 4 && encryptMetadata === false) {
      input = concatBytes(input, new Uint8Array([0xff, 0xff, 0xff, 0xff]));
    }
    let hash = md5(input);
    if (R >= 3) {
      for (let i = 0; i < 50; i++) hash = md5(hash.subarray(0, keyLenBytes));
    }
    return hash.subarray(0, keyLenBytes);
  }

  function computeUCheckLegacy({ fileKey, id0, R }) {
    if (R === 2) return rc4(fileKey, PASSWORD_PAD);
    let enc = rc4(fileKey, md5(concatBytes(PASSWORD_PAD, id0)));
    for (let i = 1; i <= 19; i++) {
      const roundKey = fileKey.map((b) => b ^ i);
      enc = rc4(roundKey, enc);
    }
    return enc; // only first 16 bytes are meaningful for R>=3
  }

  function recoverUserPasswordFromOwner({ ownerPasswordBytes, O, R, keyLenBytes }) {
    let hash = md5(padPassword(ownerPasswordBytes));
    if (R >= 3) {
      for (let i = 0; i < 50; i++) hash = md5(hash.subarray(0, keyLenBytes));
    }
    const rc4Key = hash.subarray(0, keyLenBytes);
    if (R === 2) return rc4(rc4Key, O);
    let tmp = O;
    for (let i = 19; i >= 0; i--) {
      const roundKey = rc4Key.map((b) => b ^ i);
      tmp = rc4(roundKey, tmp);
    }
    return tmp;
  }

  function deriveLegacyFileKey({ password, O, U, P, id0, R, keyLenBytes, encryptMetadata }) {
    const pwBytes = new TextEncoder().encode(password);
    const compareLen = R === 2 ? 32 : 16;

    let fileKey = computeFileKeyLegacy({ passwordBytes: pwBytes, O, P, id0, R, keyLenBytes, encryptMetadata });
    let check = computeUCheckLegacy({ fileKey, id0, R });
    if (bytesEqual(check.subarray(0, compareLen), U.subarray(0, compareLen))) return fileKey;

    const recoveredUserPw = recoverUserPasswordFromOwner({ ownerPasswordBytes: pwBytes, O, R, keyLenBytes });
    fileKey = computeFileKeyLegacy({ passwordBytes: recoveredUserPw, O, P, id0, R, keyLenBytes, encryptMetadata });
    check = computeUCheckLegacy({ fileKey, id0, R });
    if (bytesEqual(check.subarray(0, compareLen), U.subarray(0, compareLen))) return fileKey;

    return null;
  }

  // --------------------------------------------- AES-256 key derivation (R5/R6)

  async function hardenedHash(passwordBytes, saltBytes, udataBytes, R) {
    let K = await sha256(concatBytes(passwordBytes, saltBytes, udataBytes));
    if (R < 6) return K;
    let round = 0;
    while (true) {
      const unit = concatBytes(passwordBytes, K, udataBytes);
      const K1 = repeatBytes(unit, 64);
      const E = await aesCbcEncryptRaw(K.subarray(0, 16), K.subarray(16, 32), K1);
      let sum = 0;
      for (let i = 0; i < 16; i++) sum += E[i];
      const mod = sum % 3;
      K = mod === 0 ? await sha256(E) : mod === 1 ? await sha384(E) : await sha512(E);
      round++;
      if (round >= 64 && E[E.length - 1] <= round - 32) break;
    }
    return K.subarray(0, 32);
  }

  async function deriveAes256FileKey({ password, O, U, OE, UE, R }) {
    const pwBytes = new TextEncoder().encode(password).slice(0, 127);
    const uHash = U.subarray(0, 32), uValSalt = U.subarray(32, 40), uKeySalt = U.subarray(40, 48);
    const oHash = O.subarray(0, 32), oValSalt = O.subarray(32, 40), oKeySalt = O.subarray(40, 48);
    const empty = new Uint8Array(0);

    let check = await hardenedHash(pwBytes, uValSalt, empty, R);
    if (bytesEqual(check, uHash)) {
      const interKey = await hardenedHash(pwBytes, uKeySalt, empty, R);
      return await aesCbcDecryptRaw(interKey, new Uint8Array(16), UE);
    }

    check = await hardenedHash(pwBytes, oValSalt, U, R);
    if (bytesEqual(check, oHash)) {
      const interKey = await hardenedHash(pwBytes, oKeySalt, U, R);
      return await aesCbcDecryptRaw(interKey, new Uint8Array(16), OE);
    }

    return null;
  }

  // ------------------------------------------------------ per-object decryption

  function objectKey(fileKey, ref, isAES) {
    const extra = isAES ? new Uint8Array([0x73, 0x41, 0x6c, 0x54]) : new Uint8Array(0); // "sAlT"
    const objBytes = new Uint8Array([
      ref.objectNumber & 0xff, (ref.objectNumber >> 8) & 0xff, (ref.objectNumber >> 16) & 0xff,
      ref.generationNumber & 0xff, (ref.generationNumber >> 8) & 0xff,
    ]);
    const hash = md5(concatBytes(fileKey, objBytes, extra));
    return hash.subarray(0, Math.min(fileKey.length + 5, 16));
  }

  async function decryptForObject(data, ref, cipher, fileKey) {
    if (cipher === 'Identity' || data.length === 0) return data;
    if (cipher === 'RC4') {
      return rc4(objectKey(fileKey, ref, false), data);
    }
    if (cipher === 'AESV2' || cipher === 'AESV3') {
      if (data.length < 16) return new Uint8Array(0);
      const key = cipher === 'AESV2' ? objectKey(fileKey, ref, true) : fileKey;
      const iv = data.subarray(0, 16);
      const ct = data.subarray(16);
      if (ct.length === 0) return new Uint8Array(0);
      return await aesCbcDecrypt(key, iv, ct);
    }
    throw new Error('Unsupported crypt filter: ' + cipher);
  }

  // pdf-lib only assigns an object-stream container into its context via the
  // code path that also eagerly decompresses it — patching that call into a
  // no-op (see below) skips the crash, but also means the container is never
  // stored anywhere, under any ref. To recover it, independently scan the raw
  // bytes for "/Type /ObjStm" object headers, in file order, and correlate
  // them positionally with the (same-order) sequence of patched calls.
  function findObjStmHeadersInOrder(bytes) {
    const text = new TextDecoder('latin1').decode(bytes); // 1 byte = 1 code point, exact
    const found = [];
    const typeRe = /\/Type\s*\/ObjStm\b/g;
    let m;
    while ((m = typeRe.exec(text))) {
      const windowStart = Math.max(0, m.index - 300);
      const before = text.slice(windowStart, m.index);
      const objMatches = [...before.matchAll(/(\d+)\s+(\d+)\s+obj\b/g)];
      if (objMatches.length) {
        const last = objMatches[objMatches.length - 1];
        found.push({ num: parseInt(last[1], 10), gen: parseInt(last[2], 10), pos: windowStart + last.index });
      }
    }
    found.sort((a, b) => a.pos - b.pos);
    return found;
  }

  // --------------------------------------------------------------- main entry

  async function removePasswordKeepingContent(originalBytes, password) {
    const PDFLib = global.PDFLib;
    if (!PDFLib) throw new Error('pdf-lib not loaded');
    const { PDFDocument, PDFName, PDFDict, PDFArray, PDFRawStream, PDFString, PDFHexString, PDFNumber, PDFBool, PDFRef } = PDFLib;

    // Pass 1: parse the structure without letting pdf-lib eagerly decompress
    // (still-encrypted) object streams, which would throw on invalid deflate data.
    const pendingObjStms = [];
    const originalForStream = PDFLib.PDFObjectStreamParser.forStream;
    PDFLib.PDFObjectStreamParser.forStream = (rawStream) => {
      pendingObjStms.push(rawStream);
      return { parseIntoContext: async () => {} };
    };
    let pdfDoc;
    try {
      pdfDoc = await PDFDocument.load(originalBytes.slice(0), {
        ignoreEncryption: true,
        updateMetadata: false,
        throwOnInvalidObject: false,
      });
    } finally {
      PDFLib.PDFObjectStreamParser.forStream = originalForStream;
    }

    const objStmHeaders = findObjStmHeadersInOrder(new Uint8Array(originalBytes));
    if (objStmHeaders.length !== pendingObjStms.length) {
      throw new Error('Could not reliably locate this file\'s compressed object streams');
    }
    const objStmEntries = pendingObjStms.map((rawStream, i) => ({
      ref: PDFRef.of(objStmHeaders[i].num, objStmHeaders[i].gen),
      rawStream,
    }));

    const context = pdfDoc.context;
    const encryptRef = context.trailerInfo.Encrypt;
    if (!encryptRef) throw new Error('No /Encrypt dictionary found');
    const encryptDict = context.lookup(encryptRef, PDFDict);

    const idArr = context.lookup(context.trailerInfo.ID, PDFArray);
    if (!idArr || idArr.size() === 0) throw new Error('No /ID entry — cannot derive the encryption key');
    const id0Obj = idArr.lookupMaybe(0, PDFString, PDFHexString);
    if (!id0Obj) throw new Error('Malformed /ID entry');
    const id0 = id0Obj.asBytes();

    const V = encryptDict.lookupMaybe(PDFName.of('V'), PDFNumber)?.asNumber() ?? 0;
    const R = encryptDict.lookup(PDFName.of('R'), PDFNumber).asNumber();
    const P = encryptDict.lookup(PDFName.of('P'), PDFNumber).asNumber();
    const getStr = (key) => {
      const v = encryptDict.lookupMaybe(PDFName.of(key), PDFString, PDFHexString);
      return v ? v.asBytes() : undefined;
    };
    const O = getStr('O'), U = getStr('U'), OE = getStr('OE'), UE = getStr('UE');
    const encryptMetadata = encryptDict.lookupMaybe(PDFName.of('EncryptMetadata'), PDFBool)?.asBoolean() ?? true;
    const lengthBits = encryptDict.lookupMaybe(PDFName.of('Length'), PDFNumber)?.asNumber() ?? 40;

    // Work out which cipher applies to streams vs. strings.
    let stmCipher, strCipher, keyLenBytes;
    if (V <= 3) {
      stmCipher = strCipher = 'RC4';
      keyLenBytes = Math.max(5, Math.floor(lengthBits / 8));
    } else {
      const cfDict = encryptDict.lookupMaybe(PDFName.of('CF'), PDFDict);
      const resolveFilter = (nameKey) => {
        const fname = encryptDict.lookupMaybe(PDFName.of(nameKey), PDFName);
        if (!fname || fname === PDFName.of('Identity')) return { cipher: 'Identity', keyLenBytes: 0 };
        const cf = cfDict?.lookupMaybe(fname, PDFDict);
        const cfm = cf?.lookupMaybe(PDFName.of('CFM'), PDFName);
        if (!cfm || cfm === PDFName.of('None')) return { cipher: 'Identity', keyLenBytes: 0 };
        if (cfm === PDFName.of('AESV2')) return { cipher: 'AESV2', keyLenBytes: 16 };
        if (cfm === PDFName.of('AESV3')) return { cipher: 'AESV3', keyLenBytes: 32 };
        if (cfm === PDFName.of('V2')) return { cipher: 'RC4', keyLenBytes: Math.max(5, Math.floor(lengthBits / 8)) };
        throw new Error('Unsupported crypt filter method');
      };
      const stm = resolveFilter('StmF');
      const str = resolveFilter('StrF');
      stmCipher = stm.cipher; strCipher = str.cipher;
      keyLenBytes = V === 5 ? 32 : (stm.keyLenBytes || str.keyLenBytes || 16);
    }

    // Derive the actual file encryption key from the password.
    let fileKey;
    if (V === 5) {
      if (!U || !O || !UE || !OE) throw new Error('Malformed AES-256 encryption dictionary');
      fileKey = await deriveAes256FileKey({ password, O, U, OE, UE, R });
    } else {
      if (!U || !O) throw new Error('Malformed encryption dictionary');
      fileKey = deriveLegacyFileKey({ password, O, U, P, id0, R, keyLenBytes, encryptMetadata });
    }
    if (!fileKey) throw new Error('Password did not validate against this file');

    // Decrypt every stream/string, skipping the Encrypt dict itself and (per spec)
    // cross-reference streams, which are never encrypted.
    const isMetadataStream = (dict) => dict.lookupMaybe(PDFName.of('Type'), PDFName) === PDFName.of('Metadata');
    const isXRefStream = (dict) => dict.lookupMaybe(PDFName.of('Type'), PDFName) === PDFName.of('XRef');

    async function decryptValueInPlace(container, key, value, ref) {
      if (value instanceof PDFString || value instanceof PDFHexString) {
        const bytes = value.asBytes();
        const decrypted = await decryptForObject(bytes, ref, strCipher, fileKey);
        container.set(key, PDFHexString.of(bytesToHex(decrypted)));
      } else if (value instanceof PDFDict) {
        await walkDict(value, ref);
      } else if (value instanceof PDFArray) {
        await walkArray(value, ref);
      }
    }

    async function walkDict(dict, ref) {
      for (const [key, value] of dict.entries()) {
        await decryptValueInPlace(dict, key, value, ref);
      }
    }
    async function walkArray(arr, ref) {
      for (let i = 0; i < arr.size(); i++) {
        const value = arr.get(i);
        await decryptValueInPlace({ set: (_, v) => arr.set(i, v) }, i, value, ref);
      }
    }

    // Decrypt and expand every compressed object stream first (using each
    // container's own ref for its key — an ObjStm is encrypted exactly like any
    // other stream), so the full object graph exists before we walk it below.
    // Objects that come out of an expansion aren't separately re-encrypted per
    // spec, so track their refs to skip them in the main pass.
    const objStmChildRefs = new Set();
    for (const { ref, rawStream } of objStmEntries) {
      rawStream.contents = await decryptForObject(rawStream.contents, ref, stmCipher, fileKey);
      const before = new Set(context.indirectObjects.keys());
      await originalForStream(rawStream, () => false).parseIntoContext();
      for (const r of context.indirectObjects.keys()) {
        if (!before.has(r)) objStmChildRefs.add(r);
      }
    }

    for (const [ref, obj] of context.enumerateIndirectObjects()) {
      if (ref === encryptRef || objStmChildRefs.has(ref)) continue;

      if (obj instanceof PDFRawStream) {
        if (isXRefStream(obj.dict)) continue; // never encrypted
        if (!(encryptMetadata === false && isMetadataStream(obj.dict))) {
          obj.contents = await decryptForObject(obj.contents, ref, stmCipher, fileKey);
        }
        await walkDict(obj.dict, ref);
      } else if (obj instanceof PDFDict) {
        await walkDict(obj, ref);
      } else if (obj instanceof PDFArray) {
        await walkArray(obj, ref);
      } else if (obj instanceof PDFString || obj instanceof PDFHexString) {
        const decrypted = await decryptForObject(obj.asBytes(), ref, strCipher, fileKey);
        context.assign(ref, PDFHexString.of(bytesToHex(decrypted)));
      }
    }

    context.trailerInfo.Encrypt = undefined;

    return await pdfDoc.save({ useObjectStreams: false, addDefaultPage: false, updateFieldAppearances: false });
  }

  global.PDFPasswordRemover = { removePasswordKeepingContent };
})(window);
