/* Two small things in the controls band, shared by every page.
 *
 * The search box. On a desktop it is just the input; on a phone (css/floor.css)
 * it is a magnifying glass that opens the input across the bar, and this is what
 * opens and closes it, through two classes:
 *
 *   .searching   the input is open and the rest of the bar has stepped aside
 *   .has-query   something is typed, so the glass says a filter is on
 *
 * The page's own script still reads #search and listens for its `input` events.
 *
 * The format chips. Each page draws them afresh whenever one is chosen, which
 * throws away how far the row has been slid sideways on a phone; this puts it
 * back and brings the chosen one into view.
 */

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
    // Clearing goes through the page's own listener, so the records come back too.
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

  // Left empty, it goes back to being a glass; with a search typed it stays open to show it.
  input.addEventListener('blur', () => { if (input.value.trim() === '') setOpen(false); });

  // A browser can bring a typed search back (the back button); show it as open.
  const restore = () => {
    sync();
    if (input.value !== '') setOpen(true);
  };
  restore();
  addEventListener('pageshow', restore);
})();

(() => {
  const chips = document.getElementById('formats');
  if (!chips) return;

  // Listens before the page's own handler, which redraws the chips
  chips.addEventListener('click', () => {
    const left = chips.scrollLeft;
    requestAnimationFrame(() => {
      chips.scrollLeft = left;
      chips.querySelector('.on')?.scrollIntoView({ block: 'nearest', inline: 'nearest' });
    });
  });
})();
