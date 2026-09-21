/* The header's numbers count up to themselves when the page opens. The markup
 * already carries the real figures (includes/hero.php), so without JavaScript,
 * with reduced motion asked for, or with the header's animation switched off in
 * the admin, they are simply there. */

(() => {
  const numbers = document.querySelectorAll('.hero-stat b[data-n]');
  const still = document.querySelector('.hero-still') || matchMedia('(prefers-reduced-motion: reduce)').matches;
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
