<?php
$default_image = $default_image ?? null;
$is_show_no_photo = $is_show_no_photo ?? false;
$lazy_loading = $lazy_loading ?? true;
$image_preset = $image_preset ?? 'content_list';
$sorting = $sorting ?? 'sort_by_date';
$column_count = $column_count ?? 4;
$basic_fullwidth = $basic_fullwidth ?? false;
$is_show_tags = $is_show_tags ?? false;
$max_tags_count = $max_tags_count ?? 3;
$show_special = $show_special ?? false;
$show_ads = $show_ads ?? false;
$ads_positions = $ads_positions ?? '';
$ads_html = $ads_html ?? '';
$canViewHits = function($ctype_name) {
    $user = cmsUser::getInstance();
    $model = cmsCore::getModel('content');
    $ctype = $model->getContentTypeByName($ctype_name);
    if (!$ctype) {
        return false;
    }
    if (empty($ctype['options']['hits_on'])) {
        return false;
    }
    $hits_groups_view = $ctype['options']['hits_groups_view'] ?? [];
    if (empty($hits_groups_view)) {
        return true;
    }
    return $user->isInGroups($hits_groups_view);
};

$isAdPosition = function($index) use ($show_ads, $ads_positions) {
    if (!$show_ads || empty($ads_positions)) {
        return false;
    }
    
    $positions = array_map('intval', explode(',', $ads_positions));
    $position = $index + 1;
    
    return in_array($position, $positions, true);
};

$renderImage = function($item, $article, $class = 'card-img-top') use ($image_preset, $default_image, $is_show_no_photo, $lazy_loading) {
	 if (empty($item['id'])) {
        return '';
    }
    static $image_cache = [];
    
    $cache_key = md5(serialize([
        $item['id'] ?? 0,
        $item['image'] ?? '',
        $class,
        $image_preset
    ]));
    
    if (isset($image_cache[$cache_key])) {
        return $image_cache[$cache_key];
    }

    $image_field = !empty($item['image']) ? $item['image'] : ($is_show_no_photo ? $default_image : null);
    
    if (!$image_field) {
        $image_cache[$cache_key] = '';
        return '';
    }
    
    $image_html = html_image($image_field, $image_preset, $item['title'] ?? '', [
        'class' => 'img-fluid w-100 h-100 object-fit-cover ' . $class,
        'loading' => $lazy_loading ? 'lazy' : 'eager'
    ]);
    
    $result = !empty($item['url']) 
        ? '<a href="'.html($item['url'], false).'" class="d-block h-100">'.$image_html.'</a>' 
        : $image_html;
    
    $image_cache[$cache_key] = $result;
    return $result;
};