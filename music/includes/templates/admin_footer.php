<?php
/** @var string[] $scripts */
?>
</main>

<?php foreach ($scripts as $script): ?>
<script src="<?= e(url("js/$script.js")) ?>"></script>
<?php endforeach; ?>

</body>
</html>
