<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';

$content = load_content();
$visitorAnalytics = visitor_analytics();
$portfolioCategories = [];
$site = $content['site'];
?><!DOCTYPE html>
<html lang="<?= e($site['language'] ?? 'en') ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($site['title'] ?? 'Portfolio') ?></title>
<?php if (!empty($site['description'])): ?>
<meta name="description" content="<?= e($site['description']) ?>">
<?php endif; ?>
<?php if (!empty($site['favicon'])): ?>
<link rel="icon" type="image/png" href="<?= e($site['favicon']) ?>">
<?php endif; ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700;800&amp;display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/styles.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r134/three.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vanta@latest/dist/vanta.waves.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/baffle@0.3.6/dist/baffle.min.js"></script>
</head>
<body>
<div id="vanta-bg"></div>
<div class="main-content-z">
  <section class="nav" id="home">
    <div class="intro">
<?php
include __DIR__ . '/sections/hero.php';
include __DIR__ . '/sections/analytics.php';
include __DIR__ . '/sections/projects.php';
include __DIR__ . '/sections/milestones.php';
include __DIR__ . '/sections/industry-experiences.php';
include __DIR__ . '/sections/portfolio.php';
?>

<div id="detailsModal" class="modal" aria-hidden="true">
  <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <button id="closeModalBtn" class="modal-close" type="button" aria-label="Close details">&times;</button>
    <h2 id="modalTitle" class="modal-title"></h2>
    <div id="modalBody" class="modal-body"></div>
  </div>
</div>

<script src="assets/app.js"></script>
<div class="site-footer">
  <footer>
    <span><?= e($site['footer'] ?? '') ?></span>
  </footer>
</div>
</body>
</html>
