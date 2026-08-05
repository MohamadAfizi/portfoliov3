<?php
// Projects list renderer
$data = $data ?? [];
$items = $data['items'] ?? [];
?>
<section class="projects" id="<?= htmlspecialchars($id) ?>">
    <div class="projects-grid">
    <?php foreach ($items as $p): ?>
        <article class="project">
            <h3><?= htmlspecialchars($p['title'] ?? '') ?></h3>
            <p><?= htmlspecialchars($p['summary'] ?? '') ?></p>
        </article>
    <?php endforeach; ?>
    </div>
</section>
