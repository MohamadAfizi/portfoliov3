<?php
declare(strict_types=1);

require_once __DIR__ . '/json-helpers.php';

function visitor_data_path(): string
{
    return __DIR__ . '/../data/visitors.json';
}

function visitor_ip(): string
{
    $candidates = [
        $_SERVER['HTTP_CF_CONNECTING_IP'] ?? '',
        trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''))[0]),
        $_SERVER['REMOTE_ADDR'] ?? '',
    ];

    foreach ($candidates as $candidate) {
        if (filter_var($candidate, FILTER_VALIDATE_IP)) {
            return $candidate;
        }
    }

    return '';
}

function anonymize_ip(string $ip): string
{
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $parts = explode('.', $ip);
        $parts[3] = '0';
        return implode('.', $parts);
    }

    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        $binary = inet_pton($ip);
        if ($binary !== false) {
            return inet_ntop(substr($binary, 0, 6) . str_repeat("\0", 10)) ?: '';
        }
    }

    return '';
}

function cached_location(array $visitors, string $anonymizedIp): array
{
    for ($index = count($visitors) - 1; $index >= 0; $index--) {
        $visitor = $visitors[$index] ?? null;
        if (is_array($visitor) && ($visitor['ip'] ?? '') === $anonymizedIp && !empty($visitor['location'])) {
            return is_array($visitor['location']) ? $visitor['location'] : [];
        }
    }

    return [];
}

function fetch_location(string $ip): array
{
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        return [];
    }

    $context = stream_context_create([
        'http' => [
            'timeout' => 1.5,
            'ignore_errors' => true,
            'header' => "User-Agent: PortfolioV3/1.0\r\nAccept: application/json\r\n",
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);

    $response = @file_get_contents('https://ipapi.co/' . rawurlencode($ip) . '/json/', false, $context);
    if ($response === false) {
        return [];
    }

    try {
        $data = json_decode($response, true, 32, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        return [];
    }

    if (!is_array($data) || isset($data['error'])) {
        return [];
    }

    return array_filter([
        'city' => $data['city'] ?? null,
        'region' => $data['region'] ?? null,
        'country' => $data['country_name'] ?? null,
        'country_code' => $data['country_code'] ?? null,
        'timezone' => $data['timezone'] ?? null,
    ], static fn (mixed $value): bool => $value !== null && $value !== '');
}

function track_visitor(): void
{
    if (PHP_SAPI === 'cli') {
        return;
    }

    $path = visitor_data_path();
    $ip = visitor_ip();
    $anonymizedIp = anonymize_ip($ip);
    $location = cached_location(read_json_file($path), $anonymizedIp);
    if ($location === []) {
        $location = fetch_location($ip);
    }

    $handle = @fopen($path, 'c+');
    if ($handle === false || !flock($handle, LOCK_EX)) {
        if (is_resource($handle)) {
            fclose($handle);
        }
        return;
    }

    try {
        rewind($handle);
        $raw = stream_get_contents($handle);
        $visitors = [];

        if (is_string($raw) && trim($raw) !== '') {
            $decoded = json_decode($raw, true);
            $visitors = is_array($decoded) ? $decoded : [];
        }

        $visitors[] = [
            'time' => gmdate('c'),
            'path' => (string) ($_SERVER['REQUEST_URI'] ?? '/'),
            'user_agent' => (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''),
            'referrer' => (string) ($_SERVER['HTTP_REFERER'] ?? ''),
            'ip' => $anonymizedIp,
            'location' => $location,
        ];

        $json = json_encode($visitors, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json !== false) {
            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, $json . PHP_EOL);
            fflush($handle);
        }
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}

function visitor_analytics(int $months = 7): array
{
    $timezone = new DateTimeZone('Asia/Kuala_Lumpur');
    $month = new DateTimeImmutable('first day of this month 00:00:00', $timezone);
    $buckets = [];

    for ($offset = $months - 1; $offset >= 0; $offset--) {
        $date = $month->modify('-' . $offset . ' months');
        $buckets[$date->format('Y-m')] = [
            'label' => $date->format('M Y'),
            'value' => 0,
        ];
    }

    foreach (read_json_file(visitor_data_path()) as $visitor) {
        if (!is_array($visitor) || empty($visitor['time'])) {
            continue;
        }

        try {
            $visitedAt = new DateTimeImmutable((string) $visitor['time']);
        } catch (Exception) {
            continue;
        }

        $key = $visitedAt->setTimezone($timezone)->format('Y-m');
        if (isset($buckets[$key])) {
            $buckets[$key]['value']++;
        }
    }

    return [
        'labels' => array_column($buckets, 'label'),
        'values' => array_column($buckets, 'value'),
    ];
}
