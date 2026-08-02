<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mohamad Afizi's Portfolio Website</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css?family=Montserrat:900" rel="stylesheet">
<link rel="stylesheet" href="assets/styles.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r134/three.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vanta@latest/dist/vanta.waves.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/baffle@0.3.6/dist/baffle.min.js"></script>
<link rel="icon" type="image/png" href="../../shared/media/images/dp.png">
</head>
<body>
<div id="vanta-bg"></div>
<!-- <button id="pageDocBtn" class="page-doc-btn">Resume</button> -->
<div class="main-content-z">
  <section class="nav" id="home">
    <div class="intro">
      <div class="text-container">
        <h1 class="text__glitch">MOHAMAD AFIZI</h1>
      </div>
      <h2><span class="fas fa-code mr-1"></span> Full Stack Developer | Kuala Lumpur, Malaysia | fizzyjamal@gmail.com</h2>

      <?php
        $contentJsonPath = __DIR__ . '/data/content.json';
        $cmsRaw = file_get_contents($contentJsonPath);
        $cmsData = is_string($cmsRaw) ? json_decode($cmsRaw, true) : null;
        $profileSummary = is_array($cmsData) && isset($cmsData['profile_summary']) ? (string)$cmsData['profile_summary'] : '';
      ?>
      <p id="profile-summary" style="font-size: 14px;">
        <?php echo htmlspecialchars($profileSummary, ENT_QUOTES, 'UTF-8'); ?>
      </p>

      <div class="skill-tags" id="skill-tags">
        <!-- Hardcoded skill tags -->
      </div>

      <!-- Chart.js Canvas -->
      <div class="chart-container">
        <h3 style="text-align: center; margin-bottom: 15px;">
          <span id="analyticsSliderTitle" style="color: #fff;">Visitor Analytics</span>
        </h3>
        <canvas id="visitorAnalyticsChart"></canvas>
      </div>

      <nav class="nav-links">
        <a href="#projects" data-category="projects">projects</a>
        <a href="#milestones" data-category="milestones">milestones</a>
        <a href="#industry_experiences" data-category="industry_experiences">industry experiences</a>
      </nav>

      <div id="categoryDescription" class="category-description active">
        Explore the projects I've worked on, from solo ventures to collaborative efforts.
      </div>

      <div id="groupingFilter" class="grouping-filter"></div>

      <div class="cards-wrapper">
        <div class="cards-grid">
          <div class="card"><h3>Card 1</h3><p></p><div class="card-techstack"></div><div class="card-links"></div></div>
          <div class="card"><h3>Card 2</h3><p></p><div class="card-techstack"></div><div class="card-links"></div></div>
          <div class="card"><h3>Card 3</h3><p></p><div class="card-techstack"></div><div class="card-links"></div></div>
          <div class="card"><h3>Card 4</h3><p></p><div class="card-techstack"></div><div class="card-links"></div></div>
          <div class="card"><h3>Card 5</h3><p></p><div class="card-techstack"></div><div class="card-links"></div></div>
          <div class="card"><h3>Card 6</h3><p></p><div class="card-techstack"></div><div class="card-links"></div></div>
        </div>

        <div class="cards-pagination" aria-label="Cards pagination">
          <button id="cardsPrev" class="page-btn" aria-label="Previous page">&lt;</button>
          <span id="cardsPageInfo" class="page-info" aria-hidden="true"></span>
          <button id="cardsNext" class="page-btn" aria-label="Next page">&gt;</button>
        </div>
      </div>

    </div>
  </section>
</div>

<!-- Modal Structure -->
<div id="detailsModal" class="modal">
  <div class="modal-content">
    <span id="closeModalBtn" class="modal-close">&times;</span>
    <h2 id="modalTitle" style="color:#6defF8; font-size: 1.5rem; font-weight: bold; margin-top: 0; margin-bottom: 1rem;"></h2>
    <div id="modalBody" style="color: #cbd5e0; line-height: 1.6;"></div>
  </div>
</div>

<script src="assets/app.js"></script>

<!-- Fixed Footer -->
<div class="site-footer">
  <footer>
    <span>Build by Mohamad Afizi. Self-hosted. Self-made. 2026 | <a href="contentmanagement.php"><i class="fas fa-wrench"></i></a></span>
  </footer>
</div>
</body>
</html>