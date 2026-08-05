<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';

$content = load_content();
$visitorAnalytics = visitor_analytics();
$projectAnalytics = project_technology_analytics($content['projects']);
$portfolioCategories = [];

include __DIR__ . '/partials/header.php';
include __DIR__ . '/sections/hero.php';
include __DIR__ . '/sections/analytics.php';
include __DIR__ . '/sections/projects.php';
include __DIR__ . '/sections/milestones.php';
include __DIR__ . '/sections/industry-experiences.php';
include __DIR__ . '/sections/portfolio.php';
include __DIR__ . '/partials/modal.php';
include __DIR__ . '/partials/footer.php';
