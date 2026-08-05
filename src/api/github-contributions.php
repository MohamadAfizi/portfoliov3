<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/content-loader.php';

function github_json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: private, max-age=300');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    header('Allow: GET');
    github_json_response(['ok' => false, 'error' => 'method_not_allowed'], 405);
}

$content = load_content();
$github = $content['github'] ?? [];
$username = trim((string) ($github['username'] ?? ''));
$profileUrl = trim((string) ($github['profile_url'] ?? ''));
$token = trim((string) (getenv('GITHUB_TOKEN') ?: ''));

if ($username === '' || !preg_match('/^[a-zA-Z0-9-]{1,39}$/', $username)) {
    github_json_response(['ok' => false, 'error' => 'invalid_github_username'], 500);
}

if ($token === '') {
    github_json_response(['ok' => false, 'error' => 'github_token_not_configured'], 503);
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
    ]);
}

$cacheKey = 'github_contributions_' . strtolower($username);
$cached = $_SESSION[$cacheKey] ?? null;
if (
    is_array($cached)
    && isset($cached['stored_at'], $cached['payload'])
    && is_int($cached['stored_at'])
    && (time() - $cached['stored_at']) < 900
    && is_array($cached['payload'])
) {
    github_json_response($cached['payload']);
}

$query = <<<'GRAPHQL'
query PortfolioContributions($username: String!) {
  user(login: $username) {
    contributionsCollection {
      contributionCalendar {
        totalContributions
        weeks {
          contributionDays {
            date
            contributionCount
            contributionLevel
          }
        }
      }
    }
  }
}
GRAPHQL;

$requestBody = json_encode([
    'query' => $query,
    'variables' => ['username' => $username],
], JSON_UNESCAPED_SLASHES);

if ($requestBody === false) {
    github_json_response(['ok' => false, 'error' => 'request_encoding_failed'], 500);
}

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'timeout' => 5,
        'ignore_errors' => true,
        'header' => implode("\r\n", [
            'Accept: application/vnd.github+json',
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'User-Agent: MohamadAfizi-Portfolio-V3',
        ]) . "\r\n",
        'content' => $requestBody,
    ],
    'ssl' => [
        'verify_peer' => true,
        'verify_peer_name' => true,
    ],
]);

$response = @file_get_contents('https://api.github.com/graphql', false, $context);
if ($response === false) {
    github_json_response(['ok' => false, 'error' => 'github_unreachable'], 502);
}

try {
    $decoded = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException) {
    github_json_response(['ok' => false, 'error' => 'invalid_github_response'], 502);
}

$calendar = $decoded['data']['user']['contributionsCollection']['contributionCalendar'] ?? null;
if (!is_array($calendar) || isset($decoded['errors'])) {
    github_json_response(['ok' => false, 'error' => 'github_api_error'], 502);
}

$weeks = [];
foreach (($calendar['weeks'] ?? []) as $week) {
    if (!is_array($week)) {
        continue;
    }

    $days = [];
    foreach (($week['contributionDays'] ?? []) as $day) {
        if (!is_array($day)) {
            continue;
        }

        $date = (string) ($day['date'] ?? '');
        $count = (int) ($day['contributionCount'] ?? 0);
        $level = (string) ($day['contributionLevel'] ?? 'NONE');

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            continue;
        }

        if (!in_array($level, ['NONE', 'FIRST_QUARTILE', 'SECOND_QUARTILE', 'THIRD_QUARTILE', 'FOURTH_QUARTILE'], true)) {
            $level = 'NONE';
        }

        $days[] = [
            'date' => $date,
            'count' => max(0, $count),
            'level' => $level,
        ];
    }

    if ($days !== []) {
        $weeks[] = ['days' => $days];
    }
}

$payload = [
    'ok' => true,
    'data' => [
        'username' => $username,
        'profileUrl' => $profileUrl !== '' ? $profileUrl : 'https://github.com/' . rawurlencode($username),
        'totalContributions' => max(0, (int) ($calendar['totalContributions'] ?? 0)),
        'weeks' => $weeks,
    ],
];

$_SESSION[$cacheKey] = [
    'stored_at' => time(),
    'payload' => $payload,
];

github_json_response($payload);
