// ==========================================================================
// Small, dependency-free helpers shared across modules.
// ==========================================================================

import { MOBILE_BREAKPOINT } from './config.js';

export function escapeHtml(str){
  if(str == null) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

export function slugify(str){
  return String(str).toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '') || 'personal';
}

export function isMobileViewport(){
  return window.matchMedia(MOBILE_BREAKPOINT).matches;
}

export function isTypingTarget(el){
  return !!el && (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA' || el.isContentEditable);
}

// ---- Fuzzy matching (Quick Open, Command Palette) -------------------------

/**
 * Subsequence match that rewards consecutive hits and word-start hits, and
 * lightly penalises long haystacks so shorter titles win on equal evidence.
 * Returns null when `query` is not a subsequence of `text`.
 */
export function fuzzyMatch(query, text){
  const q = query.toLowerCase();
  const t = text.toLowerCase();
  let qi = 0, streak = 0, score = 0;
  const indices = [];
  for(let ti = 0; ti < t.length && qi < q.length; ti++){
    if(t[ti] === q[qi]){
      indices.push(ti);
      streak++;
      score += 2 + streak * 2 + (ti === 0 || t[ti - 1] === ' ' || t[ti - 1] === '-' ? 3 : 0);
      qi++;
    } else {
      streak = 0;
    }
  }
  if(qi < q.length) return null;
  return { score: score - t.length * 0.01, indices };
}

export function highlightIndices(text, indices){
  if(!indices.length) return escapeHtml(text);
  const idxSet = new Set(indices);
  let out = '';
  for(let i = 0; i < text.length; i++){
    const ch = escapeHtml(text[i]);
    out += idxSet.has(i) ? `<span class="quick-open-match">${ch}</span>` : ch;
  }
  return out;
}

// ---- Substring matching (workspace search) -------------------------------

/** Clips `text` to a window of context either side of a match. */
export function truncateSnippet(text, matchIndex, matchLen){
  const RADIUS = 34;
  const start = Math.max(0, matchIndex - RADIUS);
  const end = Math.min(text.length, matchIndex + matchLen + RADIUS);
  const prefixed = start > 0;
  const snippet = (prefixed ? '…' : '') + text.slice(start, end) + (end < text.length ? '…' : '');
  return { snippet, offset: matchIndex - start + (prefixed ? 1 : 0) };
}

export function highlightSubstring(text, offset, len){
  return escapeHtml(text.slice(0, offset)) +
    '<span class="search-match-text">' + escapeHtml(text.slice(offset, offset + len)) + '</span>' +
    escapeHtml(text.slice(offset + len));
}

// ---- DOM -----------------------------------------------------------------

/** Scrolls an element into view and pulses the given flash class on it. */
export function flashElement(el, className = 'project-flash', duration = 1600){
  if(!el) return;
  el.scrollIntoView({ behavior: 'smooth', block: 'center' });
  el.classList.remove(className);
  void el.offsetWidth;
  el.classList.add(className);
  setTimeout(()=> el.classList.remove(className), duration);
}

/** Clamps a floating menu to stay inside the viewport, then positions it. */
export function positionFloatingMenu(menu, x, y){
  const rect = menu.getBoundingClientRect();
  const maxX = window.innerWidth - rect.width - 4;
  const maxY = window.innerHeight - rect.height - 4;
  menu.style.left = Math.max(4, Math.min(x, maxX)) + 'px';
  menu.style.top = Math.max(4, Math.min(y, maxY)) + 'px';
}
