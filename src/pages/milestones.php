<?php
require_once __DIR__ . '/../partials/header.php';

$content = $GLOBALS['content'] ?? [];
$page = $content['pages']['milestones'] ?? null;
if ($page) {
    foreach ($page['sections'] as $section) {
        $type = $section['type'] ?? '';
        $sectionFile = __DIR__ . '/../sections/' . $type . '.php';
        if (file_exists($sectionFile)) {
            include $sectionFile;
        }
    }
}

require_once __DIR__ . '/../partials/footer.php';
