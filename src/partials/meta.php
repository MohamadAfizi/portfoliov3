<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($site['title'] ?? 'Portfolio') ?></title>
<?php if (!empty($site['description'])): ?>
<meta name="description" content="<?= e($site['description']) ?>">
<?php endif; ?>
<?php if (!empty($site['favicon'])): ?>
<link rel="icon" type="image/png" href="<?= e($site['favicon']) ?>">
<?php endif; ?>
