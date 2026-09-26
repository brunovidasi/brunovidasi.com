/* A dropdown in the site's own clothes over a real <select>, which stays in
 * the page, hidden, as the thing code reads and writes. Code that changes the
 * select calls dropdownSync(select) so the button follows. */

const DROPDOWNS = new Map();

/** Turn a <select> into a button and a menu. Safe to call twice on one select. */
function dropdown(select) {
  if (DROPDOWNS.has(select)) return DROPDOWNS.get(select);

  const root = document.createElement('div');
  root.className = 'dd';

  const button = document.createElement('button');
  button.type = 'button';
  button.className = 'dd-button';
  button.setAttribute('aria-haspopup', 'listbox');
  button.setAttribute('aria-expanded', 'false');
  if (select.getAttribute('aria-label')) button.setAttribute('aria-label', select.getAttribute('aria-label'));

  const menu = document.createElement('ul');
  menu.className = 'dd-menu';
  menu.setAttribute('role', 'listbox');
  menu.tabIndex = -1;
  menu.hidden = true;
  if (select.getAttribute('aria-label')) menu.setAttribute('aria-label', select.getAttribute('aria-label'));

  select.before(root);
  root.append(button, menu, select);
  select.tabIndex = -1;
  select.setAttribute('aria-hidden', 'true');

  let active = -1;
  let typed = '';
  let typedAt = 0;

  const choices = () => [...menu.children];
  const isOpen = () => !menu.hidden;

  /** Draw the menu and the button from whatever the select holds right now. */
  function sync() {
    const selected = select.selectedOptions[0];
    if (selected?.dataset.short) {
      // A data-short is what the button says on a phone; the menu always has the full wording.
      const full = document.createElement('span');
      const short = document.createElement('span');
      full.className = 'dd-full';
      short.className = 'dd-short';
      full.textContent = selected.textContent;
      short.textContent = selected.dataset.short;
      button.replaceChildren(full, short);
    } else {
      button.textContent = selected ? selected.textContent : '';
    }

    menu.replaceChildren(...[...select.options]
      .filter(option => !option.hidden)
      .map(option => {
        const li = document.createElement('li');
        li.setAttribute('role', 'option');
        li.dataset.value = option.value;
        li.textContent = option.textContent;
        li.setAttribute('aria-selected', String(option.selected));
        if (option.disabled) li.setAttribute('aria-disabled', 'true');
        return li;
      }));
  }

  function highlight(index) {
    const items = choices();
    if (!items.length) return;
    active = Math.max(0, Math.min(items.length - 1, index));
    items.forEach((li, i) => li.classList.toggle('active', i === active));
    items[active].scrollIntoView({ block: 'nearest' });
    button.setAttribute('aria-activedescendant', items[active].id);
  }

  function open() {
    if (isOpen()) return;
    sync();
    choices().forEach((li, i) => { li.id = `${select.id || 'dd'}-opt-${i}`; });
    menu.hidden = false;
    root.classList.add('open');
    button.setAttribute('aria-expanded', 'true');

    // Left-aligned under the button, unless that runs off the right of the window.
    menu.style.left = '0';
    menu.style.right = 'auto';
    if (menu.getBoundingClientRect().right > document.documentElement.clientWidth - 8) {
      menu.style.left = 'auto';
      menu.style.right = '0';
    }

    highlight(choices().findIndex(li => li.getAttribute('aria-selected') === 'true'));
  }

  function close(returnFocus) {
    if (!isOpen()) return;
    menu.hidden = true;
    root.classList.remove('open');
    button.setAttribute('aria-expanded', 'false');
    button.removeAttribute('aria-activedescendant');
    if (returnFocus) button.focus();
  }

  function choose(li) {
    if (!li || li.hasAttribute('aria-disabled')) return;
    const changed = select.value !== li.dataset.value;
    select.value = li.dataset.value;
    sync();
    close(true);
    if (changed) select.dispatchEvent(new Event('change', { bubbles: true }));
  }

  button.addEventListener('click', () => (isOpen() ? close(false) : open()));

  button.addEventListener('keydown', e => {
    const step = { ArrowDown: 1, ArrowUp: -1 }[e.key];

    if (!isOpen()) {
      if (step || e.key === 'Enter' || e.key === ' ') { e.preventDefault(); open(); }
      return;
    }

    if (step) { e.preventDefault(); highlight(active + step); }
    else if (e.key === 'Home') { e.preventDefault(); highlight(0); }
    else if (e.key === 'End') { e.preventDefault(); highlight(choices().length - 1); }
    else if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); choose(choices()[active]); }
    else if (e.key === 'Escape') { e.preventDefault(); close(true); }
    else if (e.key === 'Tab') close(false);
    else if (e.key.length === 1 && !e.ctrlKey && !e.metaKey) {
      // Typing the start of a choice jumps to it.
      const now = Date.now();
      typed = (now - typedAt > 700 ? '' : typed) + e.key.toLowerCase();
      typedAt = now;
      const at = choices().findIndex(li => li.textContent.toLowerCase().startsWith(typed));
      if (at >= 0) highlight(at);
    }
  });

  menu.addEventListener('mousemove', e => {
    const li = e.target.closest('li');
    if (li) highlight(choices().indexOf(li));
  });

  // On mousedown, so the button doesn't lose focus and close the menu first.
  menu.addEventListener('mousedown', e => e.preventDefault());
  menu.addEventListener('click', e => choose(e.target.closest('li')));

  document.addEventListener('pointerdown', e => { if (!root.contains(e.target)) close(false); });
  button.addEventListener('blur', () => { if (!root.contains(document.activeElement)) close(false); });

  sync();
  const instance = { sync, close };
  DROPDOWNS.set(select, instance);
  return instance;
}

/** Show the select's current value and choices; call after changing them from code. */
function dropdownSync(select) {
  DROPDOWNS.get(select)?.sync();
}
