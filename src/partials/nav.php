<?php
$globals = $GLOBALS['content']['globals'] ?? [];
$menu = $globals['menu'] ?? [];
?>
<nav>
    <a href="/">Home</a>
    <?php foreach ($menu as $item): ?>
        <a href="<?= htmlspecialchars($item['href'] ?? '#') ?>"><?= htmlspecialchars($item['label'] ?? '') ?></a>
    <?php endforeach; ?>
</nav>
