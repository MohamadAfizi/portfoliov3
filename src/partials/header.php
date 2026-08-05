<?php
// Load content.json into $GLOBALS['content'] if not already loaded
if (!isset($GLOBALS['content'])) {
    $contentFile = __DIR__ . '/../data/content.json';
    if (file_exists($contentFile)) {
        $json = file_get_contents($contentFile);
        $GLOBALS['content'] = json_decode($json, true) ?: [];
    } else {
        $GLOBALS['content'] = [];
    }
}

// Simple visitor tracker include (non-blocking write)
if (file_exists(__DIR__ . '/../lib/visitor-tracker.php')) {
    include_once __DIR__ . '/../lib/visitor-tracker.php';
    if (function_exists('track_visitor')) {
        track_visitor();
    }
}

?><!doctype html>
<html lang="en">
<head>
    <?php if (file_exists(__DIR__ . '/meta.php')) include __DIR__ . '/meta.php'; ?>
    <link rel="stylesheet" href="/src/assets/styles.css">
    <script defer src="/src/assets/app.js"></script>
</head>
<body>
<?php if (file_exists(__DIR__ . '/nav.php')) include __DIR__ . '/nav.php'; ?>
