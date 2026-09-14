// ==========================================================================
// The "typed by a human" effect used by section headings, the minimized-window
// note and the hero tagline.
//
// What makes it read as human rather than as a teleprinter is the occasional
// deliberate typo: a neighbouring key is struck, noticed, backspaced and
// corrected, with pauses long enough to look like a person doing it.
// ==========================================================================

/** Keys physically adjacent on a QWERTY board, used to pick believable typos. */
const QWERTY_NEIGHBOURS = {
  a: 'sq', b: 'vn', c: 'xv', d: 'sf', e: 'wr', f: 'dg', g: 'fh', h: 'gj', i: 'uo', j: 'hk',
  k: 'jl', l: 'k', m: 'n', n: 'bm', o: 'ip', p: 'o', q: 'wa', r: 'et', s: 'ad', t: 'ry',
  u: 'yi', v: 'cb', w: 'qe', x: 'zc', y: 'tu', z: 'x'
};

/** A plausible mis-strike for `correct`, matching its case. */
export function typoFor(correct){
  const lower = correct.toLowerCase();
  const neighbours = QWERTY_NEIGHBOURS[lower];
  let typo = neighbours
    ? neighbours[Math.floor(Math.random() * neighbours.length)]
    : String(Math.floor(Math.random() * 10));
  if(correct !== lower) typo = typo.toUpperCase();
  return typo;
}

/** Per-character delay: spaces and punctuation take a beat longer, as they do in life. */
export function typingDelay(char, speed){
  let delay = 40 + Math.random() * 70;
  if(char === ' ') delay += 60;
  if('="/<>'.includes(char)) delay += 30;
  return delay * speed;
}

/**
 * Clones `container`'s markup, empties every text node, and returns the clone
 * alongside a flat play-list of what to type back in — so the effect can run
 * over rich markup (nested spans, entities) without rebuilding it as a string.
 *
 * `.tool-count` elements are revealed whole rather than typed: they hold a
 * number that is filled in asynchronously once the project JSON loads.
 */
function buildTypingPlan(container){
  const frag = document.createDocumentFragment();
  Array.from(container.childNodes).forEach(node => frag.appendChild(node.cloneNode(true)));

  const chars = [];
  function walk(node){
    if(node.nodeType === Node.TEXT_NODE){
      const full = node.textContent;
      node.textContent = '';
      for(const ch of full) chars.push({ type: 'char', node, char: ch });
      return;
    }
    if(node.nodeType === Node.ELEMENT_NODE){
      if(node.classList && node.classList.contains('tool-count')){
        node.style.visibility = 'hidden';
        chars.push({ type: 'reveal', el: node });
        return;
      }
      Array.from(node.childNodes).forEach(walk);
    }
  }
  Array.from(frag.childNodes).forEach(walk);
  return { frag, chars };
}

/**
 * Types out `container`'s existing content in place. Runs once per element —
 * the `data-typed` marker makes repeat panel visits cheap (and stops a second
 * caret being appended). Clear that attribute to re-run it deliberately.
 */
export function humanTypeSect(container, speed, onComplete){
  if(!container || container.dataset.typed) return;
  container.dataset.typed = '1';
  speed = speed || 1;

  const { frag, chars } = buildTypingPlan(container);
  container.innerHTML = '';
  container.appendChild(frag);

  const cursor = document.createElement('span');
  cursor.className = 'cursor-caret sect-caret';
  cursor.textContent = '|';
  const lastEntry = chars[chars.length - 1];
  const lastNode = lastEntry && (lastEntry.node || lastEntry.el);
  const cursorHost = (lastNode && lastNode.parentNode) || container;
  cursorHost.appendChild(cursor);

  let i = 0;
  let mistakeCooldown = 0;

  function typeNext(){
    if(i >= chars.length){
      if(onComplete) onComplete();
      return;
    }
    const entry = chars[i];

    if(entry.type === 'reveal'){
      entry.el.style.visibility = '';
      i++;
      setTimeout(typeNext, (90 + Math.random() * 60) * speed);
      return;
    }

    const { node, char } = entry;
    const canMistake = mistakeCooldown <= 0 && i < chars.length - 1 && /[a-zA-Z0-9]/.test(char) && Math.random() < 0.07;

    if(canMistake){
      mistakeCooldown = 6;
      node.textContent += typoFor(char);
      setTimeout(()=>{
        node.textContent = node.textContent.slice(0, -1);
        setTimeout(()=>{
          node.textContent += char;
          i++;
          setTimeout(typeNext, typingDelay(char, speed));
        }, (90 + Math.random() * 80) * speed);
      }, (160 + Math.random() * 180) * speed);
      return;
    }

    node.textContent += char;
    i++;
    if(mistakeCooldown > 0) mistakeCooldown--;
    setTimeout(typeNext, typingDelay(char, speed));
  }

  typeNext();
}
