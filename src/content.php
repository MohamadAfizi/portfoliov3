<?php
declare(strict_types=1);

$requestMethod = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
$isJsonRequest = str_contains($contentType, 'application/json');
$editorJsonResponseSent = false;
$editorJsonBufferBaseLevel = ob_get_level();

if ($requestMethod === 'POST' && $isJsonRequest) {
    // PHP notices must never turn an API response into an HTML document.
    ini_set('display_errors', '0');
    ini_set('html_errors', '0');
    ini_set('log_errors', '1');
    ob_start();

    register_shutdown_function(static function (): void {
        global $editorJsonResponseSent, $editorJsonBufferBaseLevel;

        if ($editorJsonResponseSent) {
            return;
        }

        $error = error_get_last();
        $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
        if (!is_array($error) || !in_array($error['type'] ?? null, $fatalTypes, true)) {
            return;
        }

        while (ob_get_level() > $editorJsonBufferBaseLevel) {
            ob_end_clean();
        }

        http_response_code(500);
        header('Content-Type: application/json; charset=UTF-8');
        echo '{"ok":false,"persisted":false,"logStored":false,"errors":["The server stopped while saving. No success was reported; refresh before trying again."]}';
    });
}

function editor_encode_json(mixed $value, bool $pretty = false): string
{
    $flags = JSON_UNESCAPED_SLASHES
        | JSON_UNESCAPED_UNICODE
        | JSON_INVALID_UTF8_SUBSTITUTE
        | JSON_THROW_ON_ERROR;
    if ($pretty) {
        $flags |= JSON_PRETTY_PRINT;
    }

    return json_encode($value, $flags);
}

function editor_send_json(array $payload, int $statusCode = 200): never
{
    global $editorJsonResponseSent, $editorJsonBufferBaseLevel;

    $editorJsonResponseSent = true;
    while (ob_get_level() > $editorJsonBufferBaseLevel) {
        ob_end_clean();
    }

    http_response_code($statusCode);
    header('Content-Type: application/json; charset=UTF-8');

    try {
        echo editor_encode_json($payload);
    } catch (Throwable $exception) {
        error_log('Content editor response encoding failed: ' . $exception->getMessage());
        http_response_code(500);
        echo '{"ok":false,"persisted":false,"logStored":false,"errors":["The server could not encode the save response. Refresh before trying again."]}';
    }
    exit;
}

require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/content-editor-auth.php';

$contentPath = __DIR__ . '/data/content.json';
$logsPath = __DIR__ . '/data/logs.json';
$allowedSections = ['site', 'tech_stack', 'projects', 'milestones', 'industry_experiences'];
$currentContent = load_content(true);

content_editor_start_session();
content_editor_send_security_headers();

$authCredentials = content_editor_credentials($currentContent);
$authAction = $requestMethod === 'POST' ? (string) ($_POST['auth_action'] ?? '') : '';
$loginError = '';

if ($authAction === 'logout') {
    if (!content_editor_is_authenticated($authCredentials)
        || !content_editor_csrf_is_valid($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Invalid logout request.';
        exit;
    }

    content_editor_logout();
    header('Location: content.php', true, 303);
    exit;
}

if ($authAction === 'login') {
    $waitSeconds = content_editor_login_wait_seconds();
    if (!content_editor_csrf_is_valid($_POST['csrf_token'] ?? null)) {
        $loginError = 'This login request expired. Refresh the page and try again.';
    } elseif (!content_editor_auth_is_configured($authCredentials)) {
        $loginError = 'Content editor login is not configured.';
    } elseif ($waitSeconds > 0) {
        $loginError = "Too many attempts. Try again in {$waitSeconds} seconds.";
    } elseif (content_editor_login(
        $authCredentials,
        trim((string) ($_POST['username'] ?? '')),
        (string) ($_POST['password'] ?? '')
    )) {
        header('Location: content.php', true, 303);
        exit;
    } else {
        content_editor_record_failed_login();
        $loginError = content_editor_login_wait_seconds() > 0
            ? 'Too many attempts. Try again in 60 seconds.'
            : 'Incorrect username or password.';
    }
}

$isAuthenticated = content_editor_is_authenticated($authCredentials);

if (!$isAuthenticated && $requestMethod === 'POST' && $isJsonRequest) {
    editor_send_json([
        'ok' => false,
        'persisted' => false,
        'logStored' => false,
        'errors' => ['Your editor session expired. Refresh the page and sign in again.'],
    ], 401);
}

if (!$isAuthenticated) {
    $authConfigured = content_editor_auth_is_configured($authCredentials);
    if (!$authConfigured) {
        http_response_code(503);
    } elseif ($loginError !== '') {
        http_response_code(401);
    }
    $loginCsrfToken = content_editor_csrf_token();
    ?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Content Editor Login</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&amp;family=JetBrains+Mono:wght@500;600;700&amp;display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/content-editor.css?v=<?= filemtime(__DIR__ . '/assets/content-editor.css') ?>">
</head>
<body class="login-page">
<main class="login-shell">
  <section class="login-intro" aria-labelledby="login-title">
    <span class="login-kicker">PRIVATE WORKSPACE / V3</span>
    <img class="login-avatar" src="media/images/dp.png" alt="" aria-hidden="true">
    <div>
      <p class="login-index">01 / AUTHENTICATION</p>
      <h1 id="login-title">Content control.</h1>
      <p>Sign in to manage portfolio copy, projects, milestones, and industry experience.</p>
    </div>
    <span class="login-path">src/content.php</span>
  </section>

  <section class="login-panel">
    <div class="login-panel-heading">
      <span>AUTHORIZED ACCESS</span>
      <span class="login-status-dot" aria-hidden="true"></span>
    </div>
    <form class="login-form" method="post">
      <input type="hidden" name="auth_action" value="login">
      <input type="hidden" name="csrf_token" value="<?= e($loginCsrfToken) ?>">
      <div class="field">
        <label for="loginUsername">Username</label>
        <input id="loginUsername" name="username" type="text" autocomplete="username" required autofocus>
      </div>
      <div class="field">
        <label for="loginPassword">Password</label>
        <input id="loginPassword" name="password" type="password" autocomplete="current-password" required>
      </div>
      <?php if ($loginError !== ''): ?>
        <p class="login-error" role="alert"><?= e($loginError) ?></p>
      <?php elseif (!$authConfigured): ?>
        <p class="login-error" role="alert">Add content_editor_auth credentials to src/data/content.json.</p>
      <?php else: ?>
        <p class="login-note">Your session stays active for up to 8 hours of inactivity.</p>
      <?php endif; ?>
      <button class="button button-primary login-submit" type="submit"<?= $authConfigured ? '' : ' disabled' ?>>Enter editor</button>
    </form>
  </section>
</main>
</body>
</html>
    <?php
    exit;
}

if ($requestMethod === 'POST') {
    if (!$isJsonRequest) {
        http_response_code(415);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Content editor saves require JSON.';
        exit;
    }

    if (!content_editor_csrf_is_valid($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
        editor_send_json([
            'ok' => false,
            'persisted' => false,
            'logStored' => false,
            'errors' => ['The security token expired. Refresh the page and try again.'],
        ], 403);
    }
}

$currentLogs = read_json_file($logsPath);

function editor_string_length(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
}

function editor_array(mixed $value): array
{
    return is_array($value) ? $value : [];
}

function normalize_editor_content_shape(array $content): array
{
    foreach (['tech_stack', 'projects', 'milestones', 'industry_experiences'] as $key) {
        if (!isset($content[$key]) || !is_array($content[$key])) {
            $content[$key] = [];
        }
    }
    foreach (['site', 'navigation', 'ui', 'github'] as $key) {
        if (!isset($content[$key]) || !is_array($content[$key])) {
            $content[$key] = [];
        }
    }

    return $content;
}

function normalize_editor_tags(mixed $value): array
{
    $tags = [];
    foreach (editor_array($value) as $tag) {
        $tag = trim((string) $tag);
        if ($tag !== '' && !in_array($tag, $tags, true)) {
            $tags[] = $tag;
        }
    }
    return $tags;
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
        $content['tech_stack'] = normalize_editor_tags($input['tech_stack'] ?? null);
    }

    if ($section === 'projects' || $section === 'milestones') {
        $content[$section] = array_values(editor_array($input[$section] ?? null));
    }

    if ($section === 'industry_experiences') {
        $industry = editor_array($input['industry_experiences'] ?? null);
        $roles = editor_array($industry['roles'] ?? null);
        foreach ($roles as &$role) {
            if (is_array($role)) {
                $role['skills'] = normalize_editor_tags($role['skills'] ?? null);
            }
        }
        unset($role);
        $industry['roles'] = array_values($roles);
        $content['industry_experiences'] = $industry;
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
        if (isset($role['skills']) && !is_array($role['skills'])) {
            $errors[] = 'Industry role #' . ($index + 1) . ' skills must be a list.';
        }
    }
    return $errors;
}

function editor_section_snapshot(array $content, string $section): mixed
{
    return match ($section) {
        'site' => [
            'site' => editor_array($content['site'] ?? null),
            'navigation' => editor_array($content['navigation'] ?? null),
            'ui' => editor_array($content['ui'] ?? null),
            'profile_summary' => (string) ($content['profile_summary'] ?? ''),
        ],
        'tech_stack', 'projects', 'milestones' => editor_array($content[$section] ?? null),
        'industry_experiences' => editor_array($content['industry_experiences'] ?? null),
        default => null,
    };
}

function editor_snapshot_count(mixed $snapshot, string $section): int
{
    if (!is_array($snapshot)) {
        return 0;
    }
    if (in_array($section, ['tech_stack', 'projects', 'milestones'], true)) {
        return count($snapshot);
    }
    if ($section === 'industry_experiences') {
        return count(editor_array($snapshot['keyAchievements'] ?? null))
            + count(editor_array($snapshot['roles'] ?? null));
    }
    return 0;
}

function editor_log_action(mixed $before, mixed $after, string $section, bool $success): string
{
    if (!$success) {
        return 'save_failed';
    }
    if ($before === $after) {
        return 'no_change';
    }

    $beforeCount = editor_snapshot_count($before, $section);
    $afterCount = editor_snapshot_count($after, $section);
    if ($afterCount > $beforeCount) {
        return 'create';
    }
    if ($afterCount < $beforeCount) {
        return 'delete';
    }
    return 'update_or_reorder';
}

function editor_log_subject(mixed $before, mixed $after, string $section): string
{
    if (!in_array($section, ['projects', 'milestones'], true)) {
        return '';
    }

    $beforeItems = is_array($before) ? $before : [];
    $afterItems = is_array($after) ? $after : [];
    $candidate = count($afterItems) > count($beforeItems) ? ($afterItems[0] ?? null) : ($beforeItems[0] ?? null);
    return is_array($candidate) ? (string) ($candidate['title'] ?? '') : '';
}

function editor_log_scalar(mixed $value): string
{
    if (is_string($value)) {
        return '"' . $value . '"';
    }
    if ($value === null) {
        return 'null';
    }
    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }
    if (is_array($value)) {
        try {
            return editor_encode_json($value);
        } catch (JsonException) {
            return '[unavailable value]';
        }
    }
    return (string) $value;
}

function editor_log_label_for_path(string $path): string
{
    $segment = strtolower((string) preg_replace('/.*(?:\.|\\[)([a-z_]+)(?:\\]|$)/i', '$1', $path));
    return match ($segment) {
        'techstack', 'tech_stack' => 'technology',
        'skills' => 'skill',
        'actions' => 'action',
        'keyachievements' => 'achievement',
        'roles' => 'role',
        'positions' => 'position',
        'projects' => 'project',
        'milestones' => 'milestone',
        default => $segment !== '' ? str_replace('_', ' ', $segment) : 'value',
    };
}

function editor_log_is_list_array(array $value): bool
{
    if (function_exists('array_is_list')) {
        return array_is_list($value);
    }
    return array_keys($value) === range(0, count($value) - 1);
}

function editor_log_list_difference(array $source, array $comparison): array
{
    $remaining = array_values($comparison);
    $difference = [];

    foreach ($source as $value) {
        $matchedIndex = null;
        foreach ($remaining as $index => $candidate) {
            if ($value === $candidate) {
                $matchedIndex = $index;
                break;
            }
        }
        if ($matchedIndex === null) {
            $difference[] = $value;
            continue;
        }
        array_splice($remaining, $matchedIndex, 1);
    }
    return $difference;
}

function editor_log_list_value(array $before, array $after, string $label): array
{
    $lines = [];
    foreach (editor_log_list_difference($after, $before) as $value) {
        $lines[] = 'Added ' . $label . ': ' . editor_log_scalar($value);
    }
    foreach (editor_log_list_difference($before, $after) as $value) {
        $lines[] = 'Deleted ' . $label . ': ' . editor_log_scalar($value);
    }
    if (!$lines && $before !== $after) {
        $lines[] = ucfirst($label) . ' order changed.';
    }
    return $lines;
}

function editor_log_object_value(mixed $before, mixed $after, string $path = ''): array
{
    if ($before === $after) {
        return [];
    }
    if (!is_array($before) || !is_array($after)) {
        return [($path !== '' ? $path : 'value') . ': ' . editor_log_scalar($before) . ' -> ' . editor_log_scalar($after)];
    }
    if (editor_log_is_list_array($before) && editor_log_is_list_array($after)) {
        return editor_log_list_value($before, $after, editor_log_label_for_path($path));
    }

    $lines = [];
    foreach (array_unique(array_merge(array_keys($before), array_keys($after))) as $key) {
        $nextPath = $path === '' ? (string) $key : $path . '.' . $key;
        $lines = array_merge(
            $lines,
            editor_log_object_value($before[$key] ?? null, $after[$key] ?? null, $nextPath)
        );
    }
    return $lines;
}

function editor_log_value(
    mixed $before,
    mixed $after,
    string $section,
    bool $success,
    array $attemptedActions,
    array $errors
): string {
    if (!$success) {
        $attempted = $attemptedActions ? implode(', ', $attemptedActions) : 'save';
        return 'Attempted: ' . $attempted . PHP_EOL . 'Error: ' . implode(' ', $errors);
    }

    $lines = [];
    if (in_array($section, ['tech_stack', 'projects', 'milestones'], true)) {
        $label = match ($section) {
            'tech_stack' => 'technology',
            'projects' => 'project',
            default => 'milestone',
        };
        $lines = editor_log_list_value(editor_array($before), editor_array($after), $label);
    } elseif ($section === 'industry_experiences') {
        $beforeIndustry = editor_array($before);
        $afterIndustry = editor_array($after);
        $lines = array_merge(
            editor_log_list_value(
                editor_array($beforeIndustry['keyAchievements'] ?? null),
                editor_array($afterIndustry['keyAchievements'] ?? null),
                'achievement'
            ),
            editor_log_list_value(
                editor_array($beforeIndustry['roles'] ?? null),
                editor_array($afterIndustry['roles'] ?? null),
                'role'
            )
        );
    } else {
        $lines = editor_log_object_value($before, $after);
    }

    return $lines ? implode(PHP_EOL, $lines) : 'No content values changed.';
}

function editor_write_stream(mixed $handle, string $contents): bool
{
    if (!@rewind($handle) || !@ftruncate($handle, 0)) {
        return false;
    }

    $length = strlen($contents);
    $offset = 0;
    while ($offset < $length) {
        $written = @fwrite($handle, substr($contents, $offset));
        if ($written === false || $written === 0) {
            return false;
        }
        $offset += $written;
    }

    return @fflush($handle);
}

function editor_decode_logs(string $raw): array
{
    if (trim($raw) === '') {
        return [];
    }

    $decoded = json_decode(
        $raw,
        true,
        512,
        JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR
    );
    if (!is_array($decoded)) {
        throw new JsonException('The audit log root must be an array.');
    }

    return $decoded;
}

function append_editor_log(string $path, array $entry, ?string &$failureReason = null): bool
{
    $failureReason = null;
    $handle = @fopen($path, 'c+');
    if ($handle === false) {
        $failureReason = 'The audit log is not writable.';
        return false;
    }

    $locked = false;
    try {
        $locked = @flock($handle, LOCK_EX);
        if (!$locked) {
            $failureReason = 'The audit log could not be locked.';
            return false;
        }

        @rewind($handle);
        $raw = stream_get_contents($handle);
        if (!is_string($raw)) {
            $failureReason = 'The audit log could not be read.';
            return false;
        }

        try {
            $logs = editor_decode_logs($raw);
        } catch (JsonException) {
            $failureReason = 'The audit log contains invalid JSON. Repair logs.json before saving.';
            return false;
        }

        $logs[] = $entry;
        $encoded = editor_encode_json($logs, true) . PHP_EOL;
        if (!editor_write_stream($handle, $encoded)) {
            $failureReason = 'The audit log could not be written safely.';
            return false;
        }

        return true;
    } catch (Throwable $exception) {
        error_log('Content editor audit write failed: ' . $exception->getMessage());
        $failureReason = 'The audit log could not be stored.';
        return false;
    } finally {
        if ($locked) {
            @flock($handle, LOCK_UN);
        }
        @fclose($handle);
    }
}

function persist_editor_save(
    string $contentPath,
    string $logsPath,
    array $expectedContent,
    array $nextContent,
    array $logEntry,
    ?string &$failureReason = null
): bool {
    $failureReason = null;
    $contentHandle = @fopen($contentPath, 'c+');
    if ($contentHandle === false) {
        $failureReason = 'The content file is not writable.';
        return false;
    }

    $logsHandle = @fopen($logsPath, 'c+');
    if ($logsHandle === false) {
        @fclose($contentHandle);
        $failureReason = 'The audit log is not writable. The content change was not saved.';
        return false;
    }

    $contentLocked = false;
    $logsLocked = false;
    $originalContent = null;
    $originalLogs = null;
    $contentTouched = false;
    $logsTouched = false;

    try {
        $contentLocked = @flock($contentHandle, LOCK_EX);
        if (!$contentLocked) {
            $failureReason = 'The content file could not be locked.';
            return false;
        }

        $logsLocked = @flock($logsHandle, LOCK_EX);
        if (!$logsLocked) {
            $failureReason = 'The audit log could not be locked. The content change was not saved.';
            return false;
        }

        @rewind($contentHandle);
        @rewind($logsHandle);
        $originalContent = stream_get_contents($contentHandle);
        $originalLogs = stream_get_contents($logsHandle);
        if (!is_string($originalContent) || !is_string($originalLogs)) {
            $failureReason = 'The editor data files could not be read safely.';
            return false;
        }

        try {
            $diskContent = json_decode(
                $originalContent,
                true,
                512,
                JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR
            );
        } catch (JsonException) {
            $failureReason = 'The content file contains invalid JSON. No change was saved.';
            return false;
        }
        if (!is_array($diskContent)) {
            $failureReason = 'The content file root must be an object. No change was saved.';
            return false;
        }
        if (normalize_editor_content_shape($diskContent) !== $expectedContent) {
            $failureReason = 'The content changed in another tab or process. Refresh before saving again.';
            return false;
        }

        try {
            $logs = editor_decode_logs($originalLogs);
        } catch (JsonException) {
            $failureReason = 'The audit log contains invalid JSON. The content change was not saved.';
            return false;
        }

        $logs[] = $logEntry;
        $encodedContent = editor_encode_json($nextContent, true) . PHP_EOL;
        $encodedLogs = editor_encode_json($logs, true) . PHP_EOL;

        $contentTouched = true;
        if (!editor_write_stream($contentHandle, $encodedContent)) {
            $restored = editor_write_stream($contentHandle, $originalContent);
            $failureReason = $restored
                ? 'The content file could not be written safely. The change was rolled back.'
                : 'The content write failed and rollback could not be confirmed. Refresh before editing again.';
            return false;
        }

        $logsTouched = true;
        if (!editor_write_stream($logsHandle, $encodedLogs)) {
            $contentRestored = editor_write_stream($contentHandle, $originalContent);
            $logsRestored = editor_write_stream($logsHandle, $originalLogs);
            $failureReason = $contentRestored && $logsRestored
                ? 'The audit log could not be written, so the content change was rolled back.'
                : 'The audit log failed and rollback could not be confirmed. Refresh before editing again.';
            return false;
        }

        return true;
    } catch (Throwable $exception) {
        error_log('Content editor transaction failed: ' . $exception->getMessage());
        $contentRestored = !$contentTouched
            || (is_string($originalContent) && editor_write_stream($contentHandle, $originalContent));
        $logsRestored = !$logsTouched
            || (is_string($originalLogs) && editor_write_stream($logsHandle, $originalLogs));
        $failureReason = $contentRestored && $logsRestored
            ? 'The save failed safely and was rolled back.'
            : 'The save failed and rollback could not be confirmed. Refresh before editing again.';
        return false;
    } finally {
        if ($logsLocked) {
            @flock($logsHandle, LOCK_UN);
        }
        if ($contentLocked) {
            @flock($contentHandle, LOCK_UN);
        }
        @fclose($logsHandle);
        @fclose($contentHandle);
    }
}

if ($requestMethod === 'POST') {
    $errors = [];
    $payload = null;
    $nextContent = $currentContent;
    $rawRequest = (string) file_get_contents('php://input');

    try {
        $payload = json_decode($rawRequest, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        $errors[] = 'Request body must be valid JSON.';
    }

    $section = is_array($payload) ? (string) ($payload['section'] ?? '') : '';
    $submittedOperations = [];
    if (is_array($payload) && is_array($payload['operations'] ?? null)) {
        foreach (['add', 'edit', 'delete', 'reorder'] as $operation) {
            if (in_array($operation, $payload['operations'], true)) {
                $submittedOperations[] = $operation;
            }
        }
    }
    if (!is_array($payload) || $section === '') {
        $errors[] = 'A section name is required.';
    }

    if (!$errors) {
        $nextContent = normalize_editor_payload($payload, $currentContent, $section);
        $errors = validate_editor_section($nextContent, $section, $allowedSections);
    }

    $beforeSnapshot = editor_section_snapshot($currentContent, $section);
    $afterSnapshot = in_array($section, $allowedSections, true)
        ? editor_section_snapshot($nextContent, $section)
        : ['raw_request' => $rawRequest];

    try {
        $logId = bin2hex(random_bytes(8));
    } catch (Throwable) {
        $logId = uniqid('log_', true);
    }

    $timestamp = date(DATE_ATOM);
    $detectedAction = editor_log_action($beforeSnapshot, $afterSnapshot, $section, !$errors);
    $logAction = $errors
        ? 'save_failed'
        : ($submittedOperations ? implode(' + ', $submittedOperations) : $detectedAction);
    $logEntry = [
        'id' => $logId,
        'timestamp' => $timestamp,
        'status' => $errors ? 'failed' : 'success',
        'section' => $section !== '' ? $section : 'unknown',
        'action' => $logAction,
        'attempted_actions' => $submittedOperations,
        'value' => editor_log_value(
            $beforeSnapshot,
            $afterSnapshot,
            $section,
            !$errors,
            $submittedOperations,
            $errors
        ),
        'errors' => $errors,
        'item_count_before' => editor_snapshot_count($beforeSnapshot, $section),
        'item_count_after' => editor_snapshot_count($afterSnapshot, $section),
    ];

    $persisted = false;
    $storageFailed = false;
    $logFailureReason = null;
    if (!$errors) {
        $persisted = persist_editor_save(
            $contentPath,
            $logsPath,
            $currentContent,
            $nextContent,
            $logEntry,
            $logFailureReason
        );
        $logStored = $persisted;

        if (!$persisted) {
            $storageFailed = true;
            $errors[] = $logFailureReason ?: 'The editor files could not be saved safely.';
            $logEntry['status'] = 'failed';
            $logEntry['action'] = 'save_failed';
            $logEntry['value'] = editor_log_value(
                $beforeSnapshot,
                $afterSnapshot,
                $section,
                false,
                $submittedOperations,
                $errors
            );
            $logEntry['errors'] = $errors;
            $logStored = append_editor_log($logsPath, $logEntry, $logFailureReason);
        }
    } else {
        $logStored = append_editor_log($logsPath, $logEntry, $logFailureReason);
    }

    if (!$logStored) {
        try {
            error_log('Content editor log fallback: ' . editor_encode_json([
                'log_error' => $logFailureReason,
                'entry' => $logEntry,
            ]));
        } catch (Throwable $exception) {
            error_log('Content editor log fallback encoding failed: ' . $exception->getMessage());
        }
    }

    $statusCode = $storageFailed ? 500 : ($errors ? 422 : 200);
    editor_send_json([
        'ok' => !$errors,
        'persisted' => $persisted,
        'errors' => $errors,
        'savedAt' => $persisted ? $timestamp : null,
        'log' => $logEntry,
        'logStored' => $logStored,
        'logError' => $logStored ? null : $logFailureReason,
    ], $statusCode);
}

$content = $currentContent;
$editorSafeContent = $content;
unset($editorSafeContent[CONTENT_EDITOR_AUTH_KEY]);
$editorData = encode_json_for_html($editorSafeContent);
$editorLogs = encode_json_for_html($currentLogs);
$logCount = count($currentLogs);
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
<meta name="csrf-token" content="<?= e(content_editor_csrf_token()) ?>">
<title>Portvolio v3 Content Editor</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&amp;family=JetBrains+Mono:wght@500;600;700&amp;display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="assets/content-editor.css?v=<?= filemtime(__DIR__ . '/assets/content-editor.css') ?>">
</head>
<body>
<header class="command-bar">
  <div class="brand-lockup">
    <img class="brand-avatar" src="media/images/dp.png" alt="" aria-hidden="true">
    <div>
      <h1>Portvolio v3 Content Editor</h1>
    </div>
  </div>

  <nav class="jump-nav" aria-label="Editor sections">
    <a class="jump-tech" href="#tech-stack">Tech Stack <span id="navTechCount"><?= $techCount ?></span></a>
    <a class="jump-projects" href="#projects">Projects <span id="navProjectsCount"><?= $projectCount ?></span></a>
    <a class="jump-milestones" href="#milestones">Milestones <span id="navMilestonesCount"><?= $milestoneCount ?></span></a>
    <a class="jump-experience" href="#experience">Experience <span id="navExperienceCount"><?= $achievementCount + $roleCount ?></span></a>
  </nav>

  <div class="editor-account">
    <div class="save-overview">
      <span class="save-dot" id="saveDot" aria-hidden="true"></span>
      <div>
        <strong id="saveOverview">All changes saved</strong>
        <span>src/data/content.json</span>
      </div>
    </div>
    <form class="logout-form" method="post">
      <input type="hidden" name="auth_action" value="logout">
      <input type="hidden" name="csrf_token" value="<?= e(content_editor_csrf_token()) ?>">
      <button class="button button-quiet logout-button" type="submit">Log out</button>
    </form>
  </div>
</header>

<main class="editor-shell">
  <section class="editor-section" id="site" data-section-card="site">
    <header class="section-heading">
      <div class="section-identity">
        <span class="section-number">01</span>
        <div><h2>Site &amp; Profile</h2><p>Identity, search details, profile copy, and public navigation</p></div>
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
          <div class="field field-span-2"><label for="siteTitle">Browser title</label><input id="siteTitle" data-section="site" data-path="site.title" value="<?= e($content['site']['title'] ?? '') ?>"></div>
          <div class="field"><label for="siteName">Display name</label><input id="siteName" data-section="site" data-path="site.name" value="<?= e($content['site']['name'] ?? '') ?>"></div>
          <div class="field"><label for="siteRole">Role</label><input id="siteRole" data-section="site" data-path="site.role" value="<?= e($content['site']['role'] ?? '') ?>"></div>
          <div class="field"><label for="siteLocation">Location</label><input id="siteLocation" data-section="site" data-path="site.location" value="<?= e($content['site']['location'] ?? '') ?>"></div>
          <div class="field"><label for="siteEmail">Email</label><input id="siteEmail" type="email" data-section="site" data-path="site.email" value="<?= e($content['site']['email'] ?? '') ?>"></div>
          <div class="field"><label for="siteFavicon">Favicon path</label><input id="siteFavicon" data-section="site" data-path="site.favicon" value="<?= e($content['site']['favicon'] ?? '') ?>"></div>
          <div class="field field-span-2"><label for="siteDescription">Search description</label><textarea id="siteDescription" class="textarea-small" data-section="site" data-path="site.description"><?= e($content['site']['description'] ?? '') ?></textarea></div>
          <div class="field field-span-2"><label for="siteFooter">Footer</label><textarea id="siteFooter" class="textarea-small" data-section="site" data-path="site.footer"><?= e($content['site']['footer'] ?? '') ?></textarea></div>
          <div class="field field-span-all"><label for="profileSummary">Profile summary</label><textarea id="profileSummary" class="textarea-feature" data-section="site" data-path="profile_summary"><?= e($content['profile_summary'] ?? '') ?></textarea></div>
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
    <div class="section-content">
      <div class="group-tabs industry-tabs" role="tablist" aria-label="Industry experience sections">
        <button class="group-tab is-active" type="button" role="tab" aria-selected="true" aria-controls="achievementsPanel" data-experience-tab="achievements" data-tone="0">
          <span>Key Achievements</span><strong id="achievementTabCount"><?= $achievementCount ?></strong>
        </button>
        <button class="group-tab" type="button" role="tab" aria-selected="false" aria-controls="rolesPanel" data-experience-tab="roles" data-tone="3">
          <span>Career Roles</span><strong id="roleTabCount"><?= $roleCount ?></strong>
        </button>
      </div>
      <div class="settings-panel experience-panel" id="achievementsPanel" data-experience-panel="achievements" role="tabpanel">
        <div class="panel-heading panel-heading-actions">
          <div><h3>Key achievements</h3><span><span id="achievementsCount"><?= $achievementCount ?></span> measurable career outcomes</span></div>
          <button class="button" type="button" id="addAchievementButton">+ Add achievement</button>
        </div>
        <div class="record-grid" id="achievementsList"></div>
      </div>
      <div class="settings-panel experience-panel" id="rolesPanel" data-experience-panel="roles" role="tabpanel" hidden>
        <div class="panel-heading panel-heading-actions">
          <div><h3>Career roles</h3><span><span id="rolesCount"><?= $roleCount ?></span> timeline records</span></div>
          <button class="button" type="button" id="addRoleButton">+ Add role</button>
        </div>
        <div class="record-grid role-grid" id="rolesList"></div>
      </div>
    </div>
  </section>

  <section class="editor-section" id="change-logs">
    <header class="section-heading">
      <div class="section-identity">
        <span class="section-number">06</span>
        <div><h2>Change Logs</h2><p><span id="logsCount"><?= $logCount ?></span> entries</p></div>
      </div>
      <div class="section-actions">
        <span class="log-file-label">src/data/logs.json</span>
      </div>
    </header>
    <div class="section-content log-table-wrap">
      <table class="log-table">
        <thead>
          <tr>
            <th scope="col">Time</th>
            <th scope="col" class="log-col-status">Status</th>
            <th scope="col" class="log-col-section">Section</th>
            <th scope="col">Value</th>
          </tr>
        </thead>
        <tbody id="logsTableBody"></tbody>
      </table>
      <div class="log-pagination" aria-label="Log pagination">
        <button class="button button-quiet" type="button" id="logsPrevButton">Previous</button>
        <span id="logsPageLabel">Page 1 of 1</span>
        <button class="button button-quiet" type="button" id="logsNextButton">Next</button>
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

<script>
window.CONTENT_EDITOR_DATA = <?= $editorData ?>;
window.CONTENT_EDITOR_LOGS = <?= $editorLogs ?>;
</script>
<script src="assets/content-editor.js?v=<?= filemtime(__DIR__ . '/assets/content-editor.js') ?>"></script>
</body>
</html>
