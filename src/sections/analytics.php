<div class="chart-container">
  <h3 class="analytics-title">
    <span id="analyticsSliderTitle"><?= e($content['ui']['visitor_analytics_title'] ?? '') ?></span>
  </h3>
  <div class="analytics-stage">
    <canvas id="visitorAnalyticsChart" class="analytics-slide"></canvas>
    <!-- Future second analytics chart goes here. Keep the visitor chart as the primary live view for now. -->
  </div>
</div>
