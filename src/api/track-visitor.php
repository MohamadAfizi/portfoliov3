<?php
declare(strict_types=1);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit;
}

require_once __DIR__ . '/../lib/visitor-tracker.php';

track_visitor();

http_response_code(204);
header('Cache-Control: no-store');
