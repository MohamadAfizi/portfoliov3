<?php
// Minimal hero section renderer
$data = $data ?? [];
$title = $data['title'] ?? ($GLOBALS['content']['globals']['title'] ?? '');
$subtitle = $data['subtitle'] ?? '';
?>
<section class="hero" id="<?= htmlspecialchars($id) ?>">
    <h1><?= htmlspecialchars($title) ?></h1>
    <?php if ($subtitle): ?>
        <p><?= htmlspecialchars($subtitle) ?></p>
    <?php endif; ?>
</section>
