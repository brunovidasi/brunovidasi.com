<?php
/**
 * The rest of a public page after its controls: the format chips, the records,
 * the footer, the drawer and the scripts.
 *
 * @var array $page loading (text), tip (bool), scripts
 */
?>
<div class="filters">
  <div class="chips" id="formats" role="group" aria-label="Filter by format"></div>
  <span class="meta" id="countMeta"></span>
</div>

<main>
  <div id="content">
    <div class="state"><p><?= e($page['loading'] ?? 'Loading…') ?></p></div>
  </div>
</main>

<footer>
  <span>&copy; 2026 | Data from Discogs API | <a href="https://brunovida.si/contact" target="_blank" rel="noopener">Contact Bruno</a></span>
  <span>Made with &#10084;&#65039; by <a href="https://brunovida.si" target="_blank" rel="noopener">brunovida.si</a></span>
</footer>

<?php if ($page['tip'] ?? true): ?>
<div id="tip"></div>
<?php endif; ?>
<div class="overlay" id="overlay"></div>
<aside class="drawer" id="drawer">
  <button class="drawer-close" id="drawerClose" aria-label="Close">✕</button>
  <div class="drawer-cover" id="drawerCover"></div>
  <div class="drawer-body" id="drawerBody"></div>
</aside>

<?php foreach ($page['scripts'] as $script): ?>
<script src="<?= e(asset_url("js/$script.js")) ?>"></script>
<?php endforeach; ?>

</body>
</html>
