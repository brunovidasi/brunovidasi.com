// Adds a password to a PDF, entirely client-side.
//
// pdf-lib can read and write PDFs but has no encryption support at all — so this
// hand-implements the write side of the modern PDF standard security handler
// (AES-256, revision 6 — the "hardened hash" scheme from ISO 32000-2, used by
// Acrobat 9+ and supported by essentially every PDF reader in current use):
// generate a random file key, derive /O, /U, /OE, /UE and /Perms from the
// password per spec, AES-256-encrypt every stream and string in the document
// against pdf-lib's low-level object model, attach the resulting /Encrypt
// dictionary, and let pdf-lib re-save the file.

(function (global) {
  // ---------------------------------------------------------------- byte utils

  function concatBytes(...parts) {
    let len = 0;
    for (const p of parts) len += p.length;
    const out = new Uint8Array(len);
    let off = 0;
    for (const p of parts) { out.set(p, off); off += p.length; }
    return out;
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

  function randomBytes(n) {
    const b = new Uint8Array(n);
    crypto.getRandomValues(b);
    return b;
  }

  function int32LE(n) {
    const v = n >>> 0;
    return new Uint8Array([v & 0xff, (v >>> 8) & 0xff, (v >>> 16) & 0xff, (v >>> 24) & 0xff]);
  }

  // ------------------------------------------------------- AES via SubtleCrypto

  async function aesCbcEncrypt(keyBytes, ivBytes, data) {
    const key = await crypto.subtle.importKey('raw', keyBytes, 'AES-CBC', false, ['encrypt']);
    return new Uint8Array(await crypto.subtle.encrypt({ name: 'AES-CBC', iv: ivBytes }, key, data));
  }

  // Raw (unpadded) CBC encrypt: SubtleCrypto always PKCS7-pads on encrypt, but CBC
  // is causal — an appended padding block never changes the ciphertext of the
  // blocks before it — so the first data.length bytes of its output are exactly
  // the padding-free encryption the PDF spec's key-derivation steps need. For a
  // single block with a zero IV this is also exactly AES-ECB, used for /Perms.
  async function aesCbcEncryptRaw(keyBytes, ivBytes, data) {
    const key = await crypto.subtle.importKey('raw', keyBytes, 'AES-CBC', false, ['encrypt']);
    const full = new Uint8Array(await crypto.subtle.encrypt({ name: 'AES-CBC', iv: ivBytes }, key, data));
    return full.slice(0, data.length);
  }

  async function sha256(b) { return new Uint8Array(await crypto.subtle.digest('SHA-256', b)); }
  async function sha384(b) { return new Uint8Array(await crypto.subtle.digest('SHA-384', b)); }
  async function sha512(b) { return new Uint8Array(await crypto.subtle.digest('SHA-512', b)); }

  // --------------------------------------- AES-256 "hardened hash" (Algorithm 2.B)

  async function hardenedHash(passwordBytes, saltBytes, udataBytes) {
    let K = await sha256(concatBytes(passwordBytes, saltBytes, udataBytes));
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

  // Computes /U + /UE (Algorithm 8) and /O + /OE (Algorithm 9) for a freshly
  // generated file key. The owner and user passwords are the same value here —
  // this tool only exposes a single "password to open" field — but the two
  // entries are still independent per spec (the owner hash also folds in U).
  async function computeUAndUE(password, fileKey) {
    const valSalt = randomBytes(8);
    const keySalt = randomBytes(8);
    const hash = await hardenedHash(password, valSalt, new Uint8Array(0));
    const U = concatBytes(hash, valSalt, keySalt);
    const interKey = await hardenedHash(password, keySalt, new Uint8Array(0));
    const UE = await aesCbcEncryptRaw(interKey, new Uint8Array(16), fileKey);
    return { U, UE };
  }

  async function computeOAndOE(password, fileKey, U) {
    const valSalt = randomBytes(8);
    const keySalt = randomBytes(8);
    const hash = await hardenedHash(password, valSalt, U);
    const O = concatBytes(hash, valSalt, keySalt);
    const interKey = await hardenedHash(password, keySalt, U);
    const OE = await aesCbcEncryptRaw(interKey, new Uint8Array(16), fileKey);
    return { O, OE };
  }

  // Algorithm 3.10 — a 16-byte integrity check the file key itself encrypts,
  // letting a reader confirm it derived the *actual* file key, not just a
  // hash collision, before trusting the permission bits.
  async function computePerms(fileKey, P, encryptMetadata) {
    const plain = new Uint8Array(16);
    plain.set(int32LE(P), 0);
    plain.set([0xff, 0xff, 0xff, 0xff], 4);
    plain[8] = encryptMetadata ? 0x54 /* 'T' */ : 0x46 /* 'F' */;
    plain.set([0x61, 0x64, 0x62], 9); // "adb"
    plain.set(randomBytes(4), 12);
    return await aesCbcEncryptRaw(fileKey, new Uint8Array(16), plain);
  }

  // ------------------------------------------------------ per-object encryption

  async function encryptForObject(data, fileKey) {
    if (data.length === 0) return data;
    const iv = randomBytes(16);
    const ct = await aesCbcEncrypt(fileKey, iv, data);
    return concatBytes(iv, ct);
  }

  // --------------------------------------------------------------- main entry

  // permissions: { print, copy, edit } — each true (default) or false.
  async function addPasswordToPdf(originalBytes, password, permissions) {
    const PDFLib = global.PDFLib;
    if (!PDFLib) throw new Error('pdf-lib not loaded');
    const { PDFDocument, PDFName, PDFDict, PDFArray, PDFRawStream, PDFString, PDFHexString, PDFNumber, PDFBool } = PDFLib;

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
    const passwordBytes = new TextEncoder().encode(password).slice(0, 127);
    const fileKey = randomBytes(32);

    const { U, UE } = await computeUAndUE(passwordBytes, fileKey);
    const { O, OE } = await computeOAndOE(passwordBytes, fileKey, U);

    let P = -4; // all permission bits set (bits 1-2 reserved, always 0)
    const clear = (bit) => { P &= ~(1 << (bit - 1)); };
    if (!permissions.print) { clear(3); clear(12); }
    if (!permissions.copy) { clear(5); clear(10); }
    if (!permissions.edit) { clear(4); clear(6); clear(9); clear(11); }

    const perms = await computePerms(fileKey, P, true);

    // Every stream/string in the file gets AES-256-encrypted in place first —
    // once the /Encrypt dictionary itself exists below, its own plaintext O/U/
    // OE/UE/Perms values must NOT be swept up by this walk.
    async function encryptValueInPlace(container, key, value) {
      if (value instanceof PDFString || value instanceof PDFHexString) {
        const encrypted = await encryptForObject(value.asBytes(), fileKey);
        container.set(key, PDFHexString.of(bytesToHex(encrypted)));
      } else if (value instanceof PDFDict) {
        await walkDict(value);
      } else if (value instanceof PDFArray) {
        await walkArray(value);
      }
    }
    async function walkDict(dict) {
      for (const [key, value] of dict.entries()) await encryptValueInPlace(dict, key, value);
    }
    async function walkArray(arr) {
      for (let i = 0; i < arr.size(); i++) {
        await encryptValueInPlace({ set: (_, v) => arr.set(i, v) }, i, arr.get(i));
      }
    }

    const isXRefStream = (dict) => dict.lookupMaybe(PDFName.of('Type'), PDFName) === PDFName.of('XRef');

    for (const [, obj] of context.enumerateIndirectObjects()) {
      if (obj instanceof PDFRawStream) {
        if (isXRefStream(obj.dict)) continue; // never encrypted, per spec
        obj.contents = await encryptForObject(obj.contents, fileKey);
        await walkDict(obj.dict);
      } else if (obj instanceof PDFDict) {
        await walkDict(obj);
      } else if (obj instanceof PDFArray) {
        await walkArray(obj);
      }
    }

    const cfDict = context.obj({
      StdCF: context.obj({ AuthEvent: 'DocOpen', CFM: 'AESV3', Length: 32 }),
    });
    const encryptDict = context.obj({
      Filter: 'Standard',
      V: 5,
      R: 6,
      Length: 256,
      CF: cfDict,
      StmF: 'StdCF',
      StrF: 'StdCF',
      O: PDFHexString.of(bytesToHex(O)),
      U: PDFHexString.of(bytesToHex(U)),
      OE: PDFHexString.of(bytesToHex(OE)),
      UE: PDFHexString.of(bytesToHex(UE)),
      P,
      Perms: PDFHexString.of(bytesToHex(perms)),
      EncryptMetadata: true,
    });
    const encryptRef = context.register(encryptDict);
    context.trailerInfo.Encrypt = encryptRef;
    context.trailerInfo.ID = context.obj([
      PDFHexString.of(bytesToHex(randomBytes(16))),
      PDFHexString.of(bytesToHex(randomBytes(16))),
    ]);

    return await pdfDoc.save({ useObjectStreams: false, addDefaultPage: false, updateFieldAppearances: false });
  }

  global.PDFPasswordProtector = { addPasswordToPdf };
})(window);
