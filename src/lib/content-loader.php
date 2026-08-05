<?php
declare(strict_types=1);

require_once __DIR__ . '/json-helpers.php';

function load_content(): array
{
    $content = read_json_file(__DIR__ . '/../data/content.json');

    foreach (['tech_stack', 'projects', 'milestones', 'industry_experiences'] as $key) {
        if (!isset($content[$key]) || !is_array($content[$key])) {
            $content[$key] = [];
        }
    }

    $content['site'] = isset($content['site']) && is_array($content['site']) ? $content['site'] : [];
    $content['navigation'] = isset($content['navigation']) && is_array($content['navigation'])
        ? $content['navigation']
        : [];
    $content['ui'] = isset($content['ui']) && is_array($content['ui']) ? $content['ui'] : [];
    $content['github'] = isset($content['github']) && is_array($content['github']) ? $content['github'] : [];

    return $content;
}

function category_from_content(array $content, string $key, string $fallbackLabel): array
{
    $navigation = $content['navigation'][$key] ?? [];
    $items = $content[$key] ?? [];
    $groups = [];

    foreach ($items as $item) {
        $group = is_array($item) ? trim((string) ($item['group'] ?? '')) : '';
        if ($group !== '' && !in_array($group, $groups, true)) {
            $groups[] = $group;
        }
    }

    return [
        'label' => (string) ($navigation['label'] ?? $fallbackLabel),
        'description' => (string) ($navigation['description'] ?? ''),
        'groups' => $groups,
        'items' => is_array($items) ? array_values($items) : [],
    ];
}
