<?php
$initialCategory = array_key_first($portfolioCategories);
$initialDescription = $initialCategory !== null
    ? ($portfolioCategories[$initialCategory]['description'] ?? '')
    : '';

$pageData = [
    'ui' => $content['ui'],
    'github' => $content['github'],
    'techStack' => array_values($content['tech_stack']),
    'categories' => $portfolioCategories,
    'analytics' => [
        'visitors' => $visitorAnalytics,
    ],
];
?>
<nav class="nav-links" aria-label="Portfolio categories">
<?php foreach ($portfolioCategories as $key => $category): ?>
  <a href="#<?= e($key) ?>" data-category="<?= e($key) ?>"><?= e($category['label']) ?></a>
<?php endforeach; ?>
</nav>

<div id="categoryDescription" class="category-description active"><?= e($initialDescription) ?></div>
<div id="groupingFilter" class="grouping-filter"></div>

<div class="cards-wrapper">
  <div class="cards-grid">
<?php for ($index = 0; $index < 6; $index++): ?>
    <div class="card">
      <h3></h3>
      <p></p>
      <div class="card-techstack"></div>
      <div class="card-links"></div>
    </div>
<?php endfor; ?>
  </div>

  <div class="cards-pagination" aria-label="Cards pagination">
    <button id="cardsPrev" class="page-btn" type="button" aria-label="Previous page">&lt;</button>
    <span id="cardsPageInfo" class="page-info" aria-live="polite"></span>
    <button id="cardsNext" class="page-btn" type="button" aria-label="Next page">&gt;</button>
  </div>
</div>

<div id="industryTimeline" class="industry-timeline" hidden></div>

<script id="portfolio-data" type="application/json"><?= encode_json_for_html($pageData) ?></script>
    </div>
  </section>
</div>
