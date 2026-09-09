(() => {
  'use strict';

  const LOWER = 'abcdefghijklmnopqrstuvwxyz';
  const UPPER = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
  const DIGITS = '0123456789';
  const SYMBOLS = '!@#$%^&*()-_=+[]{};:,.<>?/';
  const AMBIGUOUS = 'l1IO0oB8S5Z2';

  const modeTabs = document.getElementById('modeTabs');
  const passwordView = document.getElementById('passwordView');
  const passphraseView = document.getElementById('passphraseView');

  const pwLength = document.getElementById('pwLength');
  const pwLengthValue = document.getElementById('pwLengthValue');
  const optLower = document.getElementById('optLower');
  const optUpper = document.getElementById('optUpper');
  const optDigits = document.getElementById('optDigits');
  const optSymbols = document.getElementById('optSymbols');
  const optAmbiguous = document.getElementById('optAmbiguous');

  const ppWords = document.getElementById('ppWords');
  const ppWordsValue = document.getElementById('ppWordsValue');
  const ppCapitalize = document.getElementById('ppCapitalize');
  const ppNumber = document.getElementById('ppNumber');
  const ppSeparator = document.getElementById('ppSeparator');

  const outputText = document.getElementById('outputText');
  const generateBtn = document.getElementById('generateBtn');
  const copyBtn = document.getElementById('copyBtn');

  const entropyBits = document.getElementById('entropyBits');
  const strengthFill = document.getElementById('strengthFill');
  const strengthLabel = document.getElementById('strengthLabel');
  const detailPool = document.getElementById('detailPool');
  const detailCombos = document.getElementById('detailCombos');
  const detailCrackTime = document.getElementById('detailCrackTime');
  const historyWrap = document.getElementById('historyWrap');
  const historyList = document.getElementById('historyList');

  let mode = 'password';
  let lastEntropy = 0;
  const history = [];
  const MAX_HISTORY = 5;

  function randomInt(maxExclusive) {
    if (maxExclusive <= 0) return 0;
    const array = new Uint32Array(1);
    const limit = Math.floor(0x100000000 / maxExclusive) * maxExclusive;
    let value;
    do {
      crypto.getRandomValues(array);
      value = array[0];
    } while (value >= limit);
    return value % maxExclusive;
  }

  function pickPool() {
    let pool = '';
    if (optLower.checked) pool += LOWER;
    if (optUpper.checked) pool += UPPER;
    if (optDigits.checked) pool += DIGITS;
    if (optSymbols.checked) pool += SYMBOLS;
    if (optAmbiguous.checked) {
      pool = pool.split('').filter((c) => !AMBIGUOUS.includes(c)).join('');
    }
    return pool;
  }

  function generatePassword() {
    const length = parseInt(pwLength.value, 10);
    const pool = pickPool();
    if (!pool) {
      return { value: '', bits: 0, poolSize: 0, combos: 'choose at least one character set' };
    }

    const required = [];
    if (optLower.checked) required.push(LOWER);
    if (optUpper.checked) required.push(UPPER);
    if (optDigits.checked) required.push(DIGITS);
    if (optSymbols.checked) required.push(SYMBOLS);

    let chars;
    let tries = 0;
    do {
      chars = [];
      for (let i = 0; i < length; i++) {
        chars.push(pool[randomInt(pool.length)]);
      }
      tries++;
    } while (
      tries < 20 &&
      length >= required.length &&
      !required.every((set) => chars.some((c) => set.includes(c)))
    );

    const bits = length * Math.log2(pool.length);
    return { value: chars.join(''), bits, poolSize: pool.length };
  }

  function generatePassphrase() {
    const count = parseInt(ppWords.value, 10);
    const sep = ppSeparator.value;
    const words = [];
    for (let i = 0; i < count; i++) {
      let word = WORDLIST[randomInt(WORDLIST.length)];
      if (ppCapitalize.checked) {
        word = word.charAt(0).toUpperCase() + word.slice(1);
      }
      words.push(word);
    }

    let bits = count * Math.log2(WORDLIST.length);
    let extraLabel = '';

    if (ppNumber.checked) {
      const digit = randomInt(10);
      const pos = randomInt(words.length);
      words[pos] = words[pos] + digit;
      bits += Math.log2(10);
      extraLabel = ' + digit';
    }

    const value = words.join(sep);
    return { value, bits, poolSize: WORDLIST.length, extraLabel };
  }

  function formatCombos(bits) {
    if (bits <= 0) return '—';
    const exponent = bits * Math.log10(2);
    const exp10 = Math.floor(exponent);
    const mantissa = Math.pow(10, exponent - exp10);
    return `${mantissa.toFixed(2)} × 10^${exp10}`;
  }

  function formatCrackTime(bits) {
    if (bits <= 0) return '—';
    const guessesPerSec = 1e12;
    const combinations = Math.pow(2, bits);
    const seconds = combinations / guessesPerSec / 2;

    const units = [
      ['second', 60],
      ['minute', 60],
      ['hour', 24],
      ['day', 365],
      ['year', Infinity],
    ];

    if (seconds < 1) return 'instantly';

    let value = seconds;
    let unitName = 'second';
    for (const [name, size] of units) {
      unitName = name;
      if (value < size) break;
      value /= size;
    }

    if (unitName === 'year' && value > 1e12) {
      const exp = Math.floor(Math.log10(value));
      const mant = value / Math.pow(10, exp);
      return `${mant.toFixed(1)} × 10^${exp} years`;
    }

    return `${value < 10 ? value.toFixed(1) : Math.round(value)} ${unitName}${value >= 2 ? 's' : ''}`;
  }

  function strengthFromBits(bits) {
    if (bits <= 0) return { label: 'None', pct: 0, color: 'var(--err)' };
    if (bits < 28) return { label: 'Very weak', pct: 12, color: 'var(--err)' };
    if (bits < 36) return { label: 'Weak', pct: 30, color: 'var(--err)' };
    if (bits < 50) return { label: 'Fair', pct: 50, color: 'var(--warn)' };
    if (bits < 70) return { label: 'Strong', pct: 72, color: 'var(--ok)' };
    if (bits < 100) return { label: 'Very strong', pct: 90, color: 'var(--ok)' };
    return { label: 'Excellent', pct: 100, color: 'var(--ok)' };
  }

  function updateEntropyUI(result) {
    const bits = Math.round(result.bits * 10) / 10;
    lastEntropy = result.bits;
    entropyBits.textContent = result.bits > 0 ? bits.toFixed(1) : '0';

    const strength = strengthFromBits(result.bits);
    strengthFill.style.width = strength.pct + '%';
    strengthFill.style.background = strength.color;
    strengthLabel.textContent = strength.label;
    strengthLabel.style.color = strength.color;

    if (mode === 'password') {
      detailPool.textContent = result.poolSize
        ? `${result.poolSize} characters, ${pwLength.value} positions`
        : '—';
    } else {
      detailPool.textContent = `${result.poolSize} words${result.extraLabel || ''}, ${ppWords.value} positions`;
    }
    detailCombos.textContent = formatCombos(result.bits);
    detailCrackTime.textContent = formatCrackTime(result.bits);
  }

  function addToHistory(value, bits) {
    history.unshift({ value, bits });
    if (history.length > MAX_HISTORY) history.pop();
    renderHistory();
  }

  function renderHistory() {
    if (!history.length) {
      historyWrap.hidden = true;
      return;
    }
    historyWrap.hidden = false;
    historyList.innerHTML = '';
    history.forEach((item) => {
      const row = document.createElement('div');
      row.className = 'history-item';
      const value = document.createElement('span');
      value.className = 'history-value';
      value.textContent = item.value;
      const bits = document.createElement('span');
      bits.className = 'history-bits';
      bits.textContent = `${(Math.round(item.bits * 10) / 10).toFixed(1)} bits`;
      row.appendChild(value);
      row.appendChild(bits);
      row.title = 'Click to copy';
      row.style.cursor = 'pointer';
      row.addEventListener('click', () => copyText(item.value));
      historyList.appendChild(row);
    });
  }

  function generate() {
    const result = mode === 'password' ? generatePassword() : generatePassphrase();
    if (!result.value) {
      outputText.textContent = result.combos || 'Choose at least one option';
      outputText.classList.add('placeholder');
      updateEntropyUI({ bits: 0, poolSize: 0 });
      return;
    }
    outputText.classList.remove('placeholder');
    outputText.textContent = result.value;
    updateEntropyUI(result);
    addToHistory(result.value, result.bits);
  }

  async function copyText(text) {
    try {
      await navigator.clipboard.writeText(text);
      return true;
    } catch (err) {
      return false;
    }
  }

  function flashCopied(btn) {
    const original = btn.textContent;
    btn.textContent = 'Copied!';
    btn.classList.add('copied');
    setTimeout(() => {
      btn.textContent = original;
      btn.classList.remove('copied');
    }, 1200);
  }

  modeTabs.addEventListener('click', (e) => {
    const btn = e.target.closest('.tab');
    if (!btn) return;
    mode = btn.dataset.mode;
    modeTabs.querySelectorAll('.tab').forEach((t) => t.classList.toggle('active', t === btn));
    passwordView.hidden = mode !== 'password';
    passphraseView.hidden = mode !== 'passphrase';
    generate();
  });

  pwLength.addEventListener('input', () => {
    pwLengthValue.textContent = pwLength.value;
    generate();
  });

  ppWords.addEventListener('input', () => {
    ppWordsValue.textContent = ppWords.value;
    generate();
  });

  [optLower, optUpper, optDigits, optSymbols, optAmbiguous, ppCapitalize, ppNumber].forEach((el) => {
    el.addEventListener('change', () => {
      if ([optLower, optUpper, optDigits, optSymbols].every((c) => !c.checked)) {
        optLower.checked = true;
      }
      generate();
    });
  });

  ppSeparator.addEventListener('change', generate);

  generateBtn.addEventListener('click', generate);

  copyBtn.addEventListener('click', async () => {
    const text = outputText.textContent;
    if (!text || outputText.classList.contains('placeholder')) return;
    const ok = await copyText(text);
    if (ok) flashCopied(copyBtn);
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && !e.target.matches('select, input[type="text"]')) {
      generate();
    }
  });

  generate();
})();
