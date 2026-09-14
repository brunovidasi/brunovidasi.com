// ==========================================================================
// The boot sequence and the things it hands over to: the hero tagline loop,
// the bio reveal, and the DEV toggle that skips all of it.
//
// Three ways in:
//   - normal      — type the boot lines, run the rain, reveal the app
//   - deep link   — no boot at all; the visitor asked for a specific page
//   - tool route  — hold the boot mask until the tool tab is ready
// ==========================================================================

import { bootLines, bioText, yearsExperience } from './config.js';
import { state } from './state.js';
import { typoFor } from './typing.js';
import { isDevMode, isLocalhost } from './router.js';

// ---- Boot text ------------------------------------------------------------

const BOOT_CHAR_MS = 18;
const BOOT_LINE_PAUSE_MS = 120;

function typeBootLines(){
  const bootEl = document.getElementById('bootText');
  let lineIndex = 0;

  function typeLine(){
    if(lineIndex >= bootLines.length){
      bootEl.innerHTML = bootEl.innerHTML.replace(/_\n$/, '<span class="boot-cursor">_</span>\n');
      setTimeout(showRain, 250);
      return;
    }
    const line = bootLines[lineIndex];
    let charIndex = 0;
    const interval = setInterval(()=>{
      bootEl.textContent = bootEl.textContent.replace(/▍$/, '') + line[charIndex];
      charIndex++;
      if(charIndex < line.length) return;
      clearInterval(interval);
      bootEl.textContent += '\n';
      lineIndex++;
      setTimeout(typeLine, BOOT_LINE_PAUSE_MS);
    }, BOOT_CHAR_MS);
  }

  typeLine();
}

/** The full-screen character rain that wipes the boot text away. */
function showRain(){
  const rain = document.getElementById('rain');
  const canvas = document.createElement('canvas');
  canvas.width = window.innerWidth;
  canvas.height = window.innerHeight;
  rain.appendChild(canvas);
  rain.classList.add('show');

  const ctx = canvas.getContext('2d');
  const CHARS = '01{}<>/;=()bruno';
  const CELL = 16;
  const TOTAL_FRAMES = 40;
  const drops = new Array(Math.floor(canvas.width / CELL)).fill(0);
  let frames = 0;

  const interval = setInterval(()=>{
    ctx.fillStyle = 'rgba(0,0,0,0.15)';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    ctx.fillStyle = '#8fd19e';
    ctx.font = '14px monospace';
    drops.forEach((y, i) => {
      const ch = CHARS[Math.floor(Math.random() * CHARS.length)];
      ctx.fillText(ch, i * CELL, y * CELL);
      drops[i] = (y * CELL > canvas.height && Math.random() > 0.975) ? 0 : y + 1;
    });

    frames++;
    if(frames <= TOTAL_FRAMES) return;
    clearInterval(interval);
    document.getElementById('boot').classList.add('hide');
    rain.classList.remove('show');
    revealApp();
    setTimeout(()=> document.getElementById('boot').remove(), 700);
  }, 45);
}

function revealApp(){
  document.getElementById('app').classList.add('show');
  startCyclingTagline();
}

// ---- Bio ------------------------------------------------------------------

let bioTypedOnce = false;

export function startBioTyping(){
  if(bioTypedOnce) return;
  bioTypedOnce = true;
  document.getElementById('bioTypedText').textContent = bioText;
  document.getElementById('bioAfter').classList.add('show');
}

// ---- Hero tagline ---------------------------------------------------------

const TYPE_SPEED = 65;
const DELETE_SPEED = 65;
const READ_PAUSE = 3800;
const NEXT_PAUSE = 500;
/** Characters to type before another typo is allowed. */
const MISTAKE_COOLDOWN = 15;

let taglines = [];
const taglinesPromise = fetch('json/taglines.json')
  .then(res => res.json())
  .then(items => { taglines = items.map(t => t.replace('{years}', yearsExperience)); })
  .catch(err => {
    console.error('Could not load json/taglines.json', err);
    taglines = ['I build things that work — and look good doing it.'];
  });

let order = [];
let orderPos = 0;
let taglineIndex = 0;
let charCount = 0;
let deleting = false;
let mistakeCooldown = 0;

/** Shuffles the tagline order, avoiding an immediate repeat across cycles. */
function shuffleTaglineOrder(avoidFirst){
  const next = [...Array(taglines.length).keys()];
  for(let i = next.length - 1; i > 0; i--){
    const j = Math.floor(Math.random() * (i + 1));
    [next[i], next[j]] = [next[j], next[i]];
  }
  if(next.length > 1 && next[0] === avoidFirst){
    [next[0], next[1]] = [next[1], next[0]];
  }
  return next;
}

function typeTaglineChar(el, current){
  charCount++;
  const char = current[charCount - 1];
  const canMistake = mistakeCooldown <= 0
    && charCount < current.length
    && /[a-zA-Z0-9]/.test(char)
    && Math.random() < 0.02;

  if(canMistake){
    mistakeCooldown = MISTAKE_COOLDOWN;
    el.textContent = current.slice(0, charCount - 1) + typoFor(char);
    setTimeout(()=>{
      el.textContent = current.slice(0, charCount - 1);
      setTimeout(()=>{
        el.textContent = current.slice(0, charCount);
        setTimeout(tickTagline, TYPE_SPEED);
      }, 90 + Math.random() * 80);
    }, 160 + Math.random() * 180);
    return;
  }

  el.textContent = current.slice(0, charCount);
  if(mistakeCooldown > 0) mistakeCooldown--;
  if(charCount >= current.length){
    deleting = true;
    setTimeout(tickTagline, READ_PAUSE);
    return;
  }
  setTimeout(tickTagline, TYPE_SPEED);
}

function deleteTaglineChar(el, current){
  charCount--;
  el.textContent = current.slice(0, charCount);
  if(charCount > 0){
    setTimeout(tickTagline, DELETE_SPEED);
    return;
  }
  deleting = false;
  orderPos++;
  if(orderPos >= order.length){
    order = shuffleTaglineOrder(taglineIndex);
    orderPos = 0;
  }
  taglineIndex = order[orderPos];
  setTimeout(tickTagline, NEXT_PAUSE);
}

function tickTagline(){
  const el = document.getElementById('heroVariable');
  const current = taglines[taglineIndex];
  if(deleting) deleteTaglineChar(el, current);
  else typeTaglineChar(el, current);
}

function startCyclingTagline(){
  taglinesPromise.then(()=>{
    order = shuffleTaglineOrder(-1);
    orderPos = 0;
    taglineIndex = order[0];
    charCount = 0;
    deleting = false;
    mistakeCooldown = 0;
    document.getElementById('heroVariable').textContent = '';
    tickTagline();
  });
}

// ---- Entry paths ----------------------------------------------------------

let awaitingToolReveal = false;

export function startBoot(){
  if(isDevMode() || state.enteredViaDeepLink){
    document.getElementById('boot').remove();
    revealApp();
    return;
  }
  if(state.pendingToolRouteId){
    // Tool deep-link: the tool tab can't be created yet (it needs the project
    // JSON, still loading), so keep the boot mask up instead of revealing
    // the default intro panel/explorer first and swapping to the tool after.
    // revealAfterToolRoute() fades it out once the tool tab is actually ready.
    awaitingToolReveal = true;
    return;
  }
  typeBootLines();
}

/** Called by projects.js once a deep-linked tool tab has been resolved. */
export function revealAfterToolRoute(){
  if(!awaitingToolReveal) return;
  awaitingToolReveal = false;
  const boot = document.getElementById('boot');
  if(boot){
    boot.classList.add('hide');
    boot.addEventListener('transitionend', ()=> boot.remove(), { once: true });
  }
  revealApp();
}

// ---- DEV toggle (localhost only) -----------------------------------------

export function initDevToggle(){
  const toggle = document.getElementById('devModeToggle');
  if(!isLocalhost){
    toggle.remove();
    return;
  }

  const paint = ()=>{
    toggle.classList.toggle('on', isDevMode());
    toggle.textContent = 'DEV: ' + (isDevMode() ? 'ON' : 'OFF');
  };
  paint();

  toggle.addEventListener('click', ()=>{
    const turningOn = !isDevMode();
    localStorage.setItem('devMode', turningOn ? 'true' : 'false');
    // DEV mode routes on the hash, so a path-routed URL has to go back to the
    // root first or the dev server would 404 on reload.
    if(turningOn && location.pathname !== '/'){
      location.href = '/' + location.search + (state.activeId ? '#' + state.activeId : '');
    } else {
      location.reload();
    }
  });
}
