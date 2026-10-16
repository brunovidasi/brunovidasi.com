<?php
/**
 * The 404's screen: a PS2 on an old TV, its towers rising out of red fog like
 * the console's own boot, asking for a disc it does not have, with the way
 * home as its menu. css/lost.css draws it.
 *
 * @var array  $error heading, message
 * @var string $brand the site's name, its accent words in a <span>
 */

// the towers: a 7 × 7 field with gaps and a clearing in the middle under the words, the same every time
mt_srand(404);
$towers = [];
for ($row = -3; $row <= 3; $row++) {
    for ($col = -3; $col <= 3; $col++) {
        if (mt_rand(0, 99) < 28 || (abs($row) <= 1 && abs($col) <= 1)) {
            continue;
        }
        $towers[] = ['x' => $col, 'y' => $row, 'h' => mt_rand(3, 20), 'd' => mt_rand(0, 40) / 10];
    }
}
$motes = [];
for ($i = 0; $i < 18; $i++) {
    $motes[] = ['x' => mt_rand(2, 98), 'd' => mt_rand(0, 120) / 10, 't' => mt_rand(90, 180) / 10, 's' => mt_rand(3, 8) / 10];
}
mt_srand();
?>
<div class="tv">
  <div class="tv-screen">
    <div class="ps2">
      <div class="ps2-sky" aria-hidden="true">
        <svg class="fog fog-far" viewBox="0 0 800 600" preserveAspectRatio="xMidYMid slice">
          <filter id="ps2-fog-far" x="0" y="0" width="100%" height="100%">
            <feTurbulence type="fractalNoise" baseFrequency=".0045 .009" numOctaves="4" seed="4"/>
            <feColorMatrix values="0 0 0 0 .55  0 0 0 0 .02  0 0 0 0 .06  1.9 0 0 0 -.62"/>
          </filter>
          <rect width="100%" height="100%" filter="url(#ps2-fog-far)"/>
        </svg>
        <div class="towers">
          <div class="field">
<?php foreach ($towers as $t): ?>
            <div class="tower" style="--x:<?= $t['x'] ?>;--y:<?= $t['y'] ?>;--h:<?= $t['h'] ?>;--d:<?= $t['d'] ?>s"><i></i><i></i><i></i><i></i><b></b></div>
<?php endforeach ?>
          </div>
        </div>
        <svg class="fog fog-near" viewBox="0 0 800 600" preserveAspectRatio="xMidYMid slice">
          <filter id="ps2-fog-near" x="0" y="0" width="100%" height="100%">
            <feTurbulence type="fractalNoise" baseFrequency=".008 .015" numOctaves="3" seed="40"/>
            <feColorMatrix values="0 0 0 0 1  0 0 0 0 .28  0 0 0 0 .3  2.2 0 0 0 -.95"/>
          </filter>
          <rect width="100%" height="100%" filter="url(#ps2-fog-near)"/>
        </svg>
<?php foreach ($motes as $m): ?>
        <span class="mote" style="--x:<?= $m['x'] ?>%;--d:<?= $m['d'] ?>s;--t:<?= $m['t'] ?>s;--s:<?= $m['s'] ?>"></span>
<?php endforeach ?>
      </div>

      <div class="ps2-ui">
        <header class="ps2-bar"><?= e($error['message']) ?></header>

        <div class="ps2-body">
          <p class="ps2-notice"><span class="disc" aria-hidden="true"></span>Please insert a <?= e(CONSOLES['ps2']) ?> format disc.</p>
          <h1><?= $error['heading'] ?></h1>
          <nav class="ps2-menu" aria-label="Where to go">
            <a href="<?= e(url('')) ?>" class="is-on">Back to home</a>
            <a href="#" data-back hidden>Previous page</a>
          </nav>
        </div>

        <footer class="ps2-keys" aria-hidden="true">
          <span><i class="key key-x"></i>Enter</span>
          <span><i class="key key-tri"></i>Back</span>
        </footer>
      </div>
    </div>
  </div>
  <div class="tv-chin" aria-hidden="true">
    <span class="brand tv-brand"><?= $brand ?></span>
    <span class="tv-grille"></span>
    <span class="tv-led"></span>
  </div>
</div>
<script>
  // the menu works like the console's: up and down move the glow, Enter picks, Escape or Backspace goes back
  (() => {
    const back = document.querySelector('.ps2-menu [data-back]');
    const goBack = () => (history.length > 1 ? history.back() : location.assign(document.querySelector('.ps2-menu a').href));
    if (history.length > 1) back.hidden = false;
    back.addEventListener('click', e => { e.preventDefault(); goBack(); });
    const items = () => [...document.querySelectorAll('.ps2-menu a:not([hidden])')];
    const pick = a => { items().forEach(i => i.classList.toggle('is-on', i === a)); };
    items().forEach(a => { a.addEventListener('mouseenter', () => pick(a)); a.addEventListener('focus', () => pick(a)); });
    document.addEventListener('keydown', e => {
      if (e.altKey || e.ctrlKey || e.metaKey) return;
      const list = items(), on = list.findIndex(a => a.classList.contains('is-on'));
      if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        e.preventDefault();
        const next = list[(on + (e.key === 'ArrowDown' ? 1 : list.length - 1)) % list.length];
        pick(next); next.focus();
      } else if (e.key === 'Enter' && document.activeElement === document.body) {
        list[Math.max(0, on)].click();
      } else if (e.key === 'Escape' || e.key === 'Backspace') {
        goBack();
      }
    });
  })();
</script>
