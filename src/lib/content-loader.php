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

function project_technology_analytics(array $projects, int $limit = 8): array
{
    $counts = [];

    foreach ($projects as $project) {
        if (!is_array($project)) {
            continue;
        }

        foreach (($project['techStack'] ?? []) as $technology) {
            $technology = trim((string) $technology);
            if ($technology !== '') {
                $counts[$technology] = ($counts[$technology] ?? 0) + 1;
            }
        }
    }

    arsort($counts);
    $counts = array_slice($counts, 0, $limit, true);

    return [
        'labels' => array_keys($counts),
        'values' => array_values($counts),
    ];
}
