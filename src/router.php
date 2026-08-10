<?php
declare(strict_types=1);

$requestPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
$requestPath = is_string($requestPath) ? rawurldecode($requestPath) : '/';
$requestPath = str_replace('\\', '/', $requestPath);
$requestPath = (string) preg_replace('#/+#', '/', $requestPath);

if (preg_match('#(?:^|/)data(?:/|$)#i', $requestPath) === 1) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    echo 'Not found.';
    return true;
}

return false;
