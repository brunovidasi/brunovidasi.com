// Região fiscal -> dígito verificador de região (9º dígito) e estados correspondentes
const REGIONS = [
  { digit: 0, label: "RS", states: ["Rio Grande do Sul (RS)"] },
  { digit: 1, label: "DF/GO/MT/MS/TO", states: ["Distrito Federal (DF)", "Goiás (GO)", "Mato Grosso (MT)", "Mato Grosso do Sul (MS)", "Tocantins (TO)"] },
  { digit: 2, label: "AC/AM/AP/PA/RO/RR", states: ["Acre (AC)", "Amazonas (AM)", "Amapá (AP)", "Pará (PA)", "Rondônia (RO)", "Roraima (RR)"] },
  { digit: 3, label: "CE/MA/PI", states: ["Ceará (CE)", "Maranhão (MA)", "Piauí (PI)"] },
  { digit: 4, label: "AL/PB/PE/RN", states: ["Alagoas (AL)", "Paraíba (PB)", "Pernambuco (PE)", "Rio Grande do Norte (RN)"] },
  { digit: 5, label: "BA/SE", states: ["Bahia (BA)", "Sergipe (SE)"] },
  { digit: 6, label: "MG", states: ["Minas Gerais (MG)"] },
  { digit: 7, label: "ES/RJ", states: ["Espírito Santo (ES)", "Rio de Janeiro (RJ)"] },
  { digit: 8, label: "SP", states: ["São Paulo (SP)"] },
  { digit: 9, label: "PR/SC", states: ["Paraná (PR)", "Santa Catarina (SC)"] },
];

const stateSelect = document.getElementById('state');

// Build dropdown: "Random (any state)" then each individual state, mapped to its region digit
function populateStates() {
  const randomOpt = document.createElement('option');
  randomOpt.value = 'random';
  randomOpt.textContent = 'Aleatório (qualquer estado)';
  stateSelect.appendChild(randomOpt);

  REGIONS.forEach(region => {
    region.states.forEach(stateName => {
      const opt = document.createElement('option');
      opt.value = region.digit;
      opt.textContent = stateName;
      opt.dataset.regionLabel = region.label;
      stateSelect.appendChild(opt);
    });
  });
}
populateStates();

function randDigit() {
  return Math.floor(Math.random() * 10);
}

function calcCheckDigit(digits) {
  // digits: array of numbers, weight starts at (length+1) down to 2
  let sum = 0;
  let weight = digits.length + 1;
  for (let i = 0; i < digits.length; i++) {
    sum += digits[i] * weight;
    weight--;
  }
  const rest = sum % 11;
  return rest < 2 ? 0 : 11 - rest;
}

function generateCPF(regionDigit) {
  // first 8 digits random
  const base = [];
  for (let i = 0; i < 8; i++) base.push(randDigit());
  // 9th digit = region digit (random if not specified)
  const d9 = regionDigit === null ? randDigit() : regionDigit;
  const first9 = [...base, d9];

  const d10 = calcCheckDigit(first9);
  const d11 = calcCheckDigit([...first9, d10]);

  return [...first9, d10, d11].join('');
}

function formatCPF(cpf) {
  return cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
}

function getRegionLabelForDigit(digit) {
  const region = REGIONS.find(r => r.digit === digit);
  return region ? region.label : '';
}

function showToast(msg) {
  const toast = document.getElementById('toast');
  toast.textContent = msg;
  toast.classList.add('show');
  setTimeout(() => toast.classList.remove('show'), 1500);
}

function copyText(text) {
  navigator.clipboard.writeText(text).then(() => showToast('Copiado!'))
    .catch(() => showToast('Erro ao copiar'));
}

let lastGenerated = [];

document.getElementById('generateBtn').addEventListener('click', () => {
  const stateVal = stateSelect.value;
  const regionDigit = stateVal === 'random' ? null : parseInt(stateVal, 10);
  const count = parseInt(document.getElementById('count').value, 10);
  const format = document.getElementById('format').value;

  lastGenerated = [];
  for (let i = 0; i < count; i++) {
    const raw = generateCPF(regionDigit);
    lastGenerated.push(format === 'formatted' ? formatCPF(raw) : raw);
  }

  const singleCard = document.getElementById('singleResultCard');
  const listCard = document.getElementById('listCard');

  if (count === 1) {
    listCard.style.display = 'none';
    singleCard.style.display = 'block';
    document.getElementById('cpfResult').textContent = lastGenerated[0];

    const d9 = regionDigit === null ? parseInt(lastGenerated[0].replace(/\D/g,'')[8], 10) : regionDigit;
    document.getElementById('cpfMeta').textContent = 'Região fiscal: ' + getRegionLabelForDigit(d9);
  } else {
    singleCard.style.display = 'none';
    listCard.style.display = 'block';
    const container = document.getElementById('listContainer');
    container.innerHTML = '';
    lastGenerated.forEach(cpf => {
      const item = document.createElement('div');
      item.className = 'list-item';
      const span = document.createElement('span');
      span.textContent = cpf;
      const btn = document.createElement('button');
      btn.className = 'secondary';
      btn.textContent = 'Copiar';
      btn.addEventListener('click', () => copyText(cpf));
      item.appendChild(span);
      item.appendChild(btn);
      container.appendChild(item);
    });
  }
});

document.getElementById('copyBtn').addEventListener('click', () => {
  if (lastGenerated.length) copyText(lastGenerated[0]);
});

document.getElementById('copyAllBtn').addEventListener('click', () => {
  if (lastGenerated.length) copyText(lastGenerated.join('\n'));
});
