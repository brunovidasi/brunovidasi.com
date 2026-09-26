<?php
/** @var string $title */
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($title) ?> — Bruno's Music Collection</title>
<meta name="robots" content="noindex, nofollow">
<?php require __DIR__ . '/favicons.php'; ?>
<link rel="stylesheet" href="<?= e(url('css/admin.css')) ?>">
</head>
<body>

<div class="auth">
  <div class="card">
