<div class="text-container">
  <h1 class="text__glitch"><?= e($site['name'] ?? '') ?></h1>
</div>
<h2>
  <span class="fas fa-code mr-1"></span>
  <?= e($site['role'] ?? '') ?> | <?= e($site['location'] ?? '') ?> | <?= e($site['email'] ?? '') ?>
</h2>
<p id="profile-summary" class="profile-summary"><?= e($content['profile_summary'] ?? '') ?></p>
<div class="skill-tags" id="skill-tags"></div>
