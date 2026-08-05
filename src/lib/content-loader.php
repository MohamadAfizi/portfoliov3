<?php
require_once __DIR__ . '/json-helpers.php';

function load_content(): array {
    $path = __DIR__ . '/../data/content.json';
    return read_json_file($path);
}
