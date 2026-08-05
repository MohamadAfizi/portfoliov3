<?php
function read_json_file(string $path): array {
    if (!file_exists($path)) return [];
    $json = file_get_contents($path);
    return json_decode($json, true) ?: [];
}

function write_json_file(string $path, array $data): bool {
    $dir = dirname($path);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $tmp = $path . '.tmp';
    $written = file_put_contents($tmp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    if ($written === false) return false;
    return rename($tmp, $path);
}
