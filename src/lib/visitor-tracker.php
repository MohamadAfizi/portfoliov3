<?php
function track_visitor(): void {
    $file = __DIR__ . '/../data/visitors.json';
    $entry = [
        'time' => gmdate('c'),
        'path' => $_SERVER['REQUEST_URI'] ?? null,
        'ua' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
    ];
    $dir = dirname($file);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $fp = fopen($file, 'c+');
    if (!$fp) return;
    flock($fp, LOCK_EX);
    $stat = fstat($fp);
    $contents = '';
    if ($stat['size'] > 0) {
        rewind($fp);
        $contents = stream_get_contents($fp);
    }
    $list = [];
    if ($contents) {
        $list = json_decode($contents, true) ?: [];
    }
    $list[] = $entry;
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($list, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
}
