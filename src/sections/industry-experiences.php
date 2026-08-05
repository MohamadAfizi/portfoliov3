<?php
$industryNavigation = $content['navigation']['industry_experiences'] ?? [];
$industryContent = $content['industry_experiences'] ?? [];

$portfolioCategories['industry_experiences'] = [
    'label' => (string) ($industryNavigation['label'] ?? 'industry experiences'),
    'description' => (string) ($industryNavigation['description'] ?? ''),
    'groups' => [],
    'items' => isset($industryContent['roles']) && is_array($industryContent['roles'])
        ? array_values($industryContent['roles'])
        : [],
    'keyAchievements' => isset($industryContent['keyAchievements']) && is_array($industryContent['keyAchievements'])
        ? array_values($industryContent['keyAchievements'])
        : [],
];
