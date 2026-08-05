<?php
require_once __DIR__ . '/../partials/header.php';

// Page-specific loader
$content = $GLOBALS['content'] ?? [];
$page = $content['pages']['project'] ?? null;
if ($page) {
    foreach ($page['sections'] as $section) {
        $type = $section['type'] ?? '';
        $id = $section['id'] ?? '';
        $data = $section['data'] ?? [];
        $sectionFile = __DIR__ . '/../sections/' . $type . '.php';
        if (file_exists($sectionFile)) {
            include $sectionFile;
        }
    }
}

require_once __DIR__ . '/../partials/footer.php';
