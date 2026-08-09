<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';

$contentPath = __DIR__ . '/data/content.json';
$backupPath = __DIR__ . '/data/content.backup.json';
$allowedSections = ['site', 'tech_stack', 'projects', 'milestones', 'industry_experiences'];
$currentContent = load_content();

function editor_string_length(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
}

function editor_array(mixed $value): array
{
    return is_array($value) ? $value : [];
}

function normalize_editor_payload(array $input, array $baseContent, string $section): array
{
    $content = $baseContent;

    if ($section === 'site') {
        $content['site'] = editor_array($input['site'] ?? null);
        $content['navigation'] = editor_array($input['navigation'] ?? null);
        $content['ui'] = editor_array($input['ui'] ?? null);
        $content['profile_summary'] = (string) ($input['profile_summary'] ?? '');
    }

    if ($section === 'tech_stack') {
        $tags = [];
        foreach (editor_array($input['tech_stack'] ?? null) as $tag) {
            $tag = trim((string) $tag);
            if ($tag !== '' && !in_array($tag, $tags, true)) {
                $tags[] = $tag;
            }
        }
        $content['tech_stack'] = $tags;
    }

    if ($section === 'projects' || $section === 'milestones') {
        $content[$section] = array_values(editor_array($input[$section] ?? null));
    }

    if ($section === 'industry_experiences') {
        $content['industry_experiences'] = editor_array($input['industry_experiences'] ?? null);
    }

    return $content;
}

function validate_portfolio_items(array $items, string $label, bool $limitDescription = false): array
{
    $errors = [];

    foreach ($items as $index => $item) {
        $itemNumber = $index + 1;
        if (!is_array($item)) {
            $errors[] = "$label #$itemNumber must be an object.";
            continue;
        }

        if (isset($item['actions']) && !is_array($item['actions'])) {
            $errors[] = "$label #$itemNumber actions must be a list.";
        }
        if (isset($item['techStack']) && !is_array($item['techStack'])) {
            $errors[] = "$label #$itemNumber tech stack must be a list.";
        }
        if ($limitDescription && editor_string_length((string) ($item['description'] ?? '')) > 150) {
            $errors[] = "$label #$itemNumber description must be 150 characters or fewer.";
        }
    }

    return $errors;
}

function validate_editor_section(array $content, string $section, array $allowedSections): array
{
    if (!in_array($section, $allowedSections, true)) {
        return ['Unknown editor section.'];
    }

    if ($section === 'site') {
        $errors = [];
        foreach (['site', 'navigation', 'ui'] as $key) {
            if (!is_array($content[$key] ?? null)) {
                $errors[] = "$key must be an object.";
            }
        }
        return $errors;
    }

    if ($section === 'tech_stack') {
        return is_array($content['tech_stack'] ?? null) ? [] : ['Tech stack must be a list.'];
    }

    if ($section === 'projects') {
        return validate_portfolio_items(editor_array($content['projects'] ?? null), 'Project', true);
    }

    if ($section === 'milestones') {
        return validate_portfolio_items(editor_array($content['milestones'] ?? null), 'Milestone');
    }

    $industry = editor_array($content['industry_experiences'] ?? null);
    $errors = [];
    foreach (['keyAchievements', 'roles'] as $key) {
        if (!is_array($industry[$key] ?? null)) {
            $errors[] = "Industry $key must be a list.";
        }
    }
    foreach (editor_array($industry['roles'] ?? null) as $index => $role) {
        if (!is_array($role)) {
            $errors[] = 'Industry role #' . ($index + 1) . ' must be an object.';
            continue;
        }
        if (isset($role['positions']) && !is_array($role['positions'])) {
            $errors[] = 'Industry role #' . ($index + 1) . ' positions must be a list.';
        }
    }
    return $errors;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=UTF-8');
    $errors = [];
    $payload = null;

    try {
        $payload = json_decode((string) file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        $errors[] = 'Request body must be valid JSON.';
    }

    $section = is_array($payload) ? (string) ($payload['section'] ?? '') : '';
    if (!is_array($payload) || $section === '') {
        $errors[] = 'A section name is required.';
    }

    if (!$errors) {
        $nextContent = normalize_editor_payload($payload, $currentContent, $section);
        $errors = validate_editor_section($nextContent, $section, $allowedSections);
    }

    if (!$errors) {
        try {
            $encoded = json_encode(
                $nextContent,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            ) . PHP_EOL;
            $currentJson = file_get_contents($contentPath);

            if ($currentJson === false || file_put_contents($backupPath, $currentJson, LOCK_EX) === false) {
                throw new RuntimeException('Could not create the content backup.');
            }
            if (file_put_contents($contentPath, $encoded, LOCK_EX) === false) {
                throw new RuntimeException('Could not write the content file.');
            }
        } catch (Throwable $exception) {
            error_log($exception->getMessage());
            $errors[] = 'The content file could not be saved safely. No success was reported.';
        }
    }

    http_response_code($errors ? 422 : 200);
    echo json_encode([
        'ok' => !$errors,
        'errors' => $errors,
        'savedAt' => $errors ? null : date(DATE_ATOM),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

$content = $currentContent;
$editorData = encode_json_for_html($content);
$projectCount = count(editor_array($content['projects'] ?? null));
$milestoneCount = count(editor_array($content['milestones'] ?? null));
$techCount = count(editor_array($content['tech_stack'] ?? null));
$industry = editor_array($content['industry_experiences'] ?? null);
$achievementCount = count(editor_array($industry['keyAchievements'] ?? null));
$roleCount = count(editor_array($industry['roles'] ?? null));
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Portfolio Content Editor</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&amp;family=JetBrains+Mono:wght@500;600;700&amp;display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/content-editor.css">
</head>
<body>
<header class="command-bar">
  <div class="brand-lockup">
    <span class="brand-mark" aria-hidden="true">CE</span>
    <div>
      <h1>Content Editor</h1>
      <p>Portfolio control surface</p>
    </div>
  </div>

  <nav class="jump-nav" aria-label="Editor sections">
    <a href="#site">Site</a>
    <a href="#tech-stack">Tech <span id="navTechCount"><?= $techCount ?></span></a>
    <a href="#projects">Projects <span id="navProjectsCount"><?= $projectCount ?></span></a>
    <a href="#milestones">Milestones <span id="navMilestonesCount"><?= $milestoneCount ?></span></a>
    <a href="#experience">Experience <span id="navExperienceCount"><?= $achievementCount + $roleCount ?></span></a>
  </nav>

  <div class="save-overview">
    <span class="save-dot" id="saveDot" aria-hidden="true"></span>
    <div>
      <strong id="saveOverview">All changes saved</strong>
      <span>src/data/content.json</span>
    </div>
  </div>
</header>

<main class="editor-shell">
  <section class="editor-section" id="site" data-section-card="site">
    <header class="section-heading">
      <div class="section-identity">
        <span class="section-number">01</span>
        <div><h2>Site &amp; Profile</h2><p>Identity, terminal copy, navigation, and interface labels</p></div>
      </div>
      <div class="section-actions">
        <span class="section-status" data-status="site">No unsaved changes</span>
        <button class="button button-primary" type="button" data-save-section="site" disabled>Save Site &amp; Profile</button>
      </div>
    </header>
    <div class="section-content settings-stack">
      <div class="settings-panel">
        <div class="panel-heading"><h3>Public identity</h3><span>Browser and profile details</span></div>
        <div class="field-grid field-grid-4">
          <div class="field"><label for="siteLanguage">Language</label><input id="siteLanguage" data-section="site" data-path="site.language" value="<?= e($content['site']['language'] ?? '') ?>"></div>
          <div class="field field-span-2"><label for="siteTitle">Browser title</label><input id="siteTitle" data-section="site" data-path="site.title" value="<?= e($content['site']['title'] ?? '') ?>"></div>
          <div class="field"><label for="siteName">Display name</label><input id="siteName" data-section="site" data-path="site.name" value="<?= e($content['site']['name'] ?? '') ?>"></div>
          <div class="field"><label for="siteRole">Role</label><input id="siteRole" data-section="site" data-path="site.role" value="<?= e($content['site']['role'] ?? '') ?>"></div>
          <div class="field"><label for="siteLocation">Location</label><input id="siteLocation" data-section="site" data-path="site.location" value="<?= e($content['site']['location'] ?? '') ?>"></div>
          <div class="field"><label for="siteEmail">Email</label><input id="siteEmail" type="email" data-section="site" data-path="site.email" value="<?= e($content['site']['email'] ?? '') ?>"></div>
          <div class="field"><label for="siteFavicon">Favicon path</label><input id="siteFavicon" data-section="site" data-path="site.favicon" value="<?= e($content['site']['favicon'] ?? '') ?>"></div>
          <div class="field field-span-2"><label for="siteDescription">Meta description</label><textarea id="siteDescription" class="textarea-small" data-section="site" data-path="site.description"><?= e($content['site']['description'] ?? '') ?></textarea></div>
          <div class="field field-span-2"><label for="siteFooter">Footer</label><textarea id="siteFooter" class="textarea-small" data-section="site" data-path="site.footer"><?= e($content['site']['footer'] ?? '') ?></textarea></div>
          <div class="field field-span-all"><label for="profileSummary">Profile summary</label><textarea id="profileSummary" class="textarea-feature" data-section="site" data-path="profile_summary"><?= e($content['profile_summary'] ?? '') ?></textarea></div>
        </div>
      </div>

      <div class="settings-panel">
        <div class="panel-heading"><h3>Terminal presentation</h3><span>Hero command-line copy</span></div>
        <div class="field-grid field-grid-3">
          <div class="field"><label for="terminalPrompt">Terminal prompt</label><input id="terminalPrompt" data-section="site" data-path="site.terminal_prompt" value="<?= e($content['site']['terminal_prompt'] ?? '') ?>"></div>
          <div class="field"><label for="heroCommand">Hero command</label><input id="heroCommand" data-section="site" data-path="site.hero_command" value="<?= e($content['site']['hero_command'] ?? '') ?>"></div>
          <div class="field"><label for="summaryCommand">Summary command</label><input id="summaryCommand" data-section="site" data-path="site.summary_command" value="<?= e($content['site']['summary_command'] ?? '') ?>"></div>
        </div>
      </div>

      <div class="settings-panel">
        <div class="panel-heading"><h3>Public navigation</h3><span>Labels and descriptions shown on the portfolio</span></div>
        <div class="navigation-grid">
          <?php foreach (['projects' => 'Projects', 'milestones' => 'Milestones', 'industry_experiences' => 'Industry experiences'] as $key => $label): ?>
            <article class="navigation-item">
              <strong><?= e($label) ?></strong>
              <div class="field"><label for="nav-<?= e($key) ?>-label">Navigation label</label><input id="nav-<?= e($key) ?>-label" data-section="site" data-path="navigation.<?= e($key) ?>.label" value="<?= e($content['navigation'][$key]['label'] ?? '') ?>"></div>
              <div class="field"><label for="nav-<?= e($key) ?>-description">Description</label><textarea id="nav-<?= e($key) ?>-description" class="textarea-medium" data-section="site" data-path="navigation.<?= e($key) ?>.description"><?= e($content['navigation'][$key]['description'] ?? '') ?></textarea></div>
            </article>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="settings-panel">
        <div class="panel-heading"><h3>Interface labels</h3><span>Analytics and pagination copy</span></div>
        <div class="field-grid field-grid-3">
          <div class="field"><label for="analyticsTitle">Analytics title</label><input id="analyticsTitle" data-section="site" data-path="ui.visitor_analytics_title" value="<?= e($content['ui']['visitor_analytics_title'] ?? '') ?>"></div>
          <div class="field"><label for="totalVisitsLabel">Total visits label</label><input id="totalVisitsLabel" data-section="site" data-path="ui.total_visits_label" value="<?= e($content['ui']['total_visits_label'] ?? '') ?>"></div>
          <div class="field"><label for="pageLabel">Page label</label><input id="pageLabel" data-section="site" data-path="ui.page_label" value="<?= e($content['ui']['page_label'] ?? '') ?>"></div>
        </div>
      </div>
    </div>
  </section>

  <section class="editor-section" id="tech-stack" data-section-card="tech_stack">
    <header class="section-heading">
      <div class="section-identity">
        <span class="section-number">02</span>
        <div><h2>Tech Stack</h2><p><span id="techCount"><?= $techCount ?></span> editable skills, displayed as compact tags</p></div>
      </div>
      <div class="section-actions">
        <span class="section-status" data-status="tech_stack">No unsaved changes</span>
        <button class="button" type="button" id="addTechButton">+ Add technology</button>
        <button class="button button-primary" type="button" data-save-section="tech_stack" disabled>Save Tech Stack</button>
      </div>
    </header>
    <div class="section-content"><div class="master-token-list" id="techStackList"></div></div>
  </section>

  <section class="editor-section" id="projects" data-section-card="projects">
    <header class="section-heading">
      <div class="section-identity">
        <span class="section-number">03</span>
        <div><h2>Projects</h2><p><span id="projectsCount"><?= $projectCount ?></span> projects, grouped with every field visible</p></div>
      </div>
      <div class="section-actions">
        <span class="section-status" data-status="projects">No unsaved changes</span>
        <button class="button" type="button" data-open-add-dialog="projects">+ Add project</button>
        <button class="button button-primary" type="button" data-save-section="projects" disabled>Save Projects</button>
      </div>
    </header>
    <div class="section-content grouped-records" id="projectsList"></div>
  </section>

  <section class="editor-section" id="milestones" data-section-card="milestones">
    <header class="section-heading">
      <div class="section-identity">
        <span class="section-number">04</span>
        <div><h2>Milestones</h2><p><span id="milestonesCount"><?= $milestoneCount ?></span> learning and certification milestones</p></div>
      </div>
      <div class="section-actions">
        <span class="section-status" data-status="milestones">No unsaved changes</span>
        <button class="button" type="button" data-open-add-dialog="milestones">+ Add milestone</button>
        <button class="button button-primary" type="button" data-save-section="milestones" disabled>Save Milestones</button>
      </div>
    </header>
    <div class="section-content grouped-records" id="milestonesList"></div>
  </section>

  <section class="editor-section" id="experience" data-section-card="industry_experiences">
    <header class="section-heading">
      <div class="section-identity">
        <span class="section-number">05</span>
        <div><h2>Industry Experience</h2><p><span id="experienceCount"><?= $achievementCount + $roleCount ?></span> achievements and career records</p></div>
      </div>
      <div class="section-actions">
        <span class="section-status" data-status="industry_experiences">No unsaved changes</span>
        <button class="button button-primary" type="button" data-save-section="industry_experiences" disabled>Save Industry Experience</button>
      </div>
    </header>
    <div class="section-content settings-stack">
      <div class="settings-panel">
        <div class="panel-heading panel-heading-actions">
          <div><h3>Key achievements</h3><span><span id="achievementsCount"><?= $achievementCount ?></span> measurable career outcomes</span></div>
          <button class="button" type="button" id="addAchievementButton">+ Add achievement</button>
        </div>
        <div class="record-grid" id="achievementsList"></div>
      </div>
      <div class="settings-panel">
        <div class="panel-heading panel-heading-actions">
          <div><h3>Career roles</h3><span><span id="rolesCount"><?= $roleCount ?></span> timeline records and nested positions</span></div>
          <button class="button" type="button" id="addRoleButton">+ Add role</button>
        </div>
        <div class="record-grid role-grid" id="rolesList"></div>
      </div>
    </div>
  </section>
</main>

<dialog class="group-dialog" id="addEntryDialog">
  <form id="addEntryForm">
    <header>
      <div><span class="eyebrow">Create record</span><h2 id="addEntryTitle">Add project</h2></div>
      <button class="icon-button" type="button" id="closeEntryDialog" aria-label="Close dialog">x</button>
    </header>
    <div class="dialog-content">
      <p>Choose where this record belongs. The new item will be placed first.</p>
      <div class="field"><label for="entryGroupSelect">Group</label><select id="entryGroupSelect"></select></div>
      <div class="field" id="newGroupField" hidden><label for="entryNewGroup">New group name</label><input id="entryNewGroup" autocomplete="off" placeholder="Enter a group name"></div>
      <p class="dialog-error" id="entryDialogError" role="alert"></p>
    </div>
    <footer>
      <button class="button" type="button" id="cancelEntryDialog">Cancel</button>
      <button class="button button-primary" type="submit" id="confirmEntryAdd">Add record</button>
    </footer>
  </form>
</dialog>

<div class="toast-region" id="toastRegion" aria-live="polite" aria-atomic="true"></div>

<script>window.CONTENT_EDITOR_DATA = <?= $editorData ?>;</script>
<script src="assets/content-editor.js"></script>
</body>
</html>
