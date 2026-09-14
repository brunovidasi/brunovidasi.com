// ==========================================================================
// Delegated action dispatch.
//
// Replaces the inline `onclick="doThing('id')"` handlers this page used to
// carry. Markup declares what it does — `data-act="toggle-doc" data-arg="x"`
// — and modules register the implementation. Two document-level listeners
// then serve every button, including cards rendered long after load.
//
// This is what lets the rest of the code be real ES modules: an inline
// attribute can only call a global, whereas an action name is just a string.
// ==========================================================================

import { escapeHtml } from './utils.js';

/** action name -> (element, args) => void */
const registry = new Map();

/** Elements the browser already activates on Enter/Space; don't double-fire. */
const NATIVELY_ACTIVATABLE = new Set(['BUTTON', 'A', 'INPUT', 'SELECT', 'TEXTAREA']);

export function registerActions(map){
  Object.entries(map).forEach(([name, fn]) => registry.set(name, fn));
}

/**
 * Builds the attributes for a delegated action, for use inside a template
 * string: `<button${act('toggle-doc', id)}>`. Note the leading space.
 * Empty or missing arguments are omitted and arrive at the handler as
 * undefined, which the handlers treat the same as the old empty-string args.
 */
export function act(name, ...args){
  let out = ` data-act="${escapeHtml(name)}"`;
  args.forEach((arg, i) => {
    if(arg === undefined || arg === null || arg === '') return;
    out += ` data-arg${i === 0 ? '' : i + 1}="${escapeHtml(String(arg))}"`;
  });
  return out;
}

/** Selector matching the elements `act(name, arg)` would have produced. */
export function actSelector(name, arg){
  return arg === undefined
    ? `[data-act="${name}"]`
    : `[data-act="${name}"][data-arg="${arg}"]`;
}

function argsOf(el){
  return [el.dataset.arg, el.dataset.arg2, el.dataset.arg3];
}

function dispatch(el, event){
  const fn = registry.get(el.dataset.act);
  if(!fn) return;
  // Anchors used as buttons (href="#", or a real path kept for crawlers and
  // middle-click) must not navigate — the old handlers all did `return false`.
  if(el.tagName === 'A') event.preventDefault();
  fn(el, argsOf(el));
}

export function initActions(){
  document.addEventListener('click', (e)=>{
    const target = e.target.closest('[data-act], [data-open]');
    if(!target) return;
    if(target.dataset.act) dispatch(target, e);
    else {
      if(target.tagName === 'A') e.preventDefault();
      const open = registry.get('open-file');
      if(open) open(target, [target.dataset.open]);
    }
  });

  // Keyboard activation for the elements that only look like buttons
  // (`tabindex="0" role="button"`); native controls are skipped so they are
  // not fired twice.
  document.addEventListener('keydown', (e)=>{
    if(e.key !== 'Enter' && e.key !== ' ') return;
    const target = e.target.closest && e.target.closest('[data-act]');
    if(!target || NATIVELY_ACTIVATABLE.has(target.tagName)) return;
    e.preventDefault();
    dispatch(target, e);
  });

  document.addEventListener('change', (e)=>{
    const target = e.target.closest('[data-act-change]');
    if(!target) return;
    const fn = registry.get(target.dataset.actChange);
    if(fn) fn(target, argsOf(target));
  });
}
