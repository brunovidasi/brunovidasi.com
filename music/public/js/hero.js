/* The header's numbers count up when the page opens. The markup already has
 * the real figures, so without the animation they are simply there. */

(() => {
  const numbers = document.querySelectorAll('.hero-stat b[data-n]');
  const still = document.querySelector('.hero-still, .hero-settled') || prefersReducedMotion();
  if (!numbers.length || still) return;

  const started = performance.now();
  const duration = 900;

  numbers.forEach(el => {
    const to = Number(el.dataset.n);
    if (to < 2) return;
    el.textContent = '0';

    const tick = now => {
      const t = Math.min(1, (now - started) / duration);
      el.textContent = Math.round(to * (1 - Math.pow(1 - t, 3)));
      if (t < 1) requestAnimationFrame(tick);
    };
    requestAnimationFrame(tick);
  });
})();

/* On a phone the artist pills fold into a "Collections" menu, of ordinary
 * links. It closes on a tap anywhere else, or Escape. */

(() => {
  const menu = document.querySelector('.hero-menu');
  if (!menu) return;

  const button = menu.querySelector('.dd-button');
  const list = menu.querySelector('.dd-menu');

  const setOpen = open => {
    list.hidden = !open;
    menu.classList.toggle('open', open);
    button.setAttribute('aria-expanded', String(open));
  };

  button.addEventListener('click', () => setOpen(list.hidden));
  document.addEventListener('pointerdown', e => { if (!menu.contains(e.target)) setOpen(false); });
  menu.addEventListener('keydown', e => {
    if (e.key !== 'Escape') return;
    setOpen(false);
    button.focus();
  });
})();
