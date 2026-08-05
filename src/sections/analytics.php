<div class="chart-container">
  <h3 class="analytics-title">
    <span id="analyticsSliderTitle"><?= e($content['ui']['visitor_analytics_title'] ?? '') ?></span>
  </h3>
  <div class="analytics-stage">
    <canvas id="visitorAnalyticsChart" class="analytics-slide"></canvas>
    <div id="githubContributionGraph" class="analytics-slide github-contributions" hidden aria-live="polite">
      <p class="github-status"><?= e($content['github']['loading_text'] ?? '') ?></p>
    </div>
  </div>
</div>
