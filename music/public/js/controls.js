/* The search box on a phone: a magnifying glass that opens the input across
 * the controls bar. .searching on the bar means it is open; .has-query on the
 * box means something is typed. */

(() => {
  const box = document.getElementById('searchBox');
  const input = document.getElementById('search');
  const bar = box?.closest('.controls');
  if (!box || !input || !bar) return;

  const opener = box.querySelector('.search-open');

  const sync = () => box.classList.toggle('has-query', input.value.trim() !== '');

  const setOpen = open => {
    bar.classList.toggle('searching', open);
    opener.setAttribute('aria-expanded', String(open));
  };

  const close = () => {
    // Cleared through the page's own listener, so the records come back too.
    if (input.value !== '') {
      input.value = '';
      input.dispatchEvent(new Event('input', { bubbles: true }));
    }
    input.blur();
    setOpen(false);
  };

  opener.addEventListener('click', () => {
    setOpen(true);
    input.focus();
  });

  box.querySelector('.search-close').addEventListener('click', close);
  input.addEventListener('input', sync);

  input.addEventListener('keydown', e => {
    if (e.key === 'Escape') close();
    else if (e.key === 'Enter') input.blur(); // puts the keyboard away, keeps the filter
  });

  // Left empty it folds back into a glass; with a search typed it stays open.
  input.addEventListener('blur', () => { if (input.value.trim() === '') setOpen(false); });

  // The back button can bring a typed search back: show it open.
  const restore = () => {
    sync();
    if (input.value !== '') setOpen(true);
  };
  restore();
  addEventListener('pageshow', restore);
})();

/* The format chips are drawn afresh on every pick, which loses how far the row
 * was slid sideways on a phone: this puts it back, with the chosen one in view. */
(() => {
  const chips = document.getElementById('formats');
  if (!chips) return;

  // Registered before the page's own handler, which redraws the chips.
  chips.addEventListener('click', () => {
    const left = chips.scrollLeft;
    requestAnimationFrame(() => {
      chips.scrollLeft = left;
      chips.querySelector('.on')?.scrollIntoView({ block: 'nearest', inline: 'nearest' });
    });
  });
})();
