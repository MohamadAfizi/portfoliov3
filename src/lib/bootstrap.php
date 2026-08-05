<?php
declare(strict_types=1);

require_once __DIR__ . '/content-loader.php';
require_once __DIR__ . '/visitor-tracker.php';

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
