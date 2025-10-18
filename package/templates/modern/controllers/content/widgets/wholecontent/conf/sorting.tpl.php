<?php
$sortItems = function($articles, $sorting_type) {
    $all_items = [];
    
    foreach ($articles as $article) {
        if (empty($article['items'])) continue;
        foreach ($article['items'] as $item) {
            $item['_article'] = $article;
            $all_items[] = $item;
        }
    }

    if (empty($sorting_type) || empty($all_items)) {
        return $all_items;
    }

    switch ($sorting_type) {
        case 'sort_by_title':
            $sort_func = fn($a, $b) => strcmp(
                mb_strtolower($a['title'] ?? ''), 
                mb_strtolower($b['title'] ?? '')
            );
            break;
        case 'sort_by_types':
            $sort_func = fn($a, $b) => strcmp(
                $a['_article']['ctype']['title'] ?? '', 
                $b['_article']['ctype']['title'] ?? ''
            );
            break;
        case 'sort_rand':
            shuffle($all_items);
            return $all_items;
        default:
            $sort_func = fn($a, $b) => strtotime($b['date_pub'] ?? '') <=> strtotime($a['date_pub'] ?? '');
    }

    usort($all_items, $sort_func);
    return $all_items;
};