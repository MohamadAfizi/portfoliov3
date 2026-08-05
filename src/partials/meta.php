<?php
$globals = $GLOBALS['content']['globals'] ?? [];
$title = $globals['title'] ?? 'Portfolio';
$description = $globals['description'] ?? '';
?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($title) ?></title>
<?php if ($description): ?>
<meta name="description" content="<?= htmlspecialchars($description) ?>">
<?php endif; ?>
