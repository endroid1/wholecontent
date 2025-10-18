<?php
$options = $widget->getOptions();
$layout = $options['layout'] ?? 'basic';
$column_count = isset($options['column_count']) ? (int)$options['column_count'] : 4;
$masonry_columns = isset($options['masonry_columns']) ? (int)$options['masonry_columns'] : 3;
$basic_fullwidth = $options['basic_fullwidth'] ?? false;
$special_positions = $options['special_positions'] ?? 'none';
$custom_positions = $options['custom_positions'] ?? '';
$show_author = $options['show_author'] ?? false;
$show_date = $options['show_date'] ?? false;
$show_group = $options['show_group'] ?? false;
$show_views = $options['show_views'] ?? false;
$show_comments = $options['show_comments'] ?? false;
$is_show_item_parent = $options['show_item_parent'] ?? false;
$is_show_item_cat = $options['show_item_cat'] ?? false;
$teaser_len = isset($options['teaser_len']) ? (int)$options['teaser_len'] : 0;
$is_show_no_photo = $options['is_show_no_photo'] ?? false;
$default_image = $options['default_image'] ?? null;
$sorting = $options['sorting'] ?? 'sort_by_date';
$lazy_loading = $options['lazy_loading'] ?? false;
$is_show_tags = $options['show_tags'] ?? false;
$max_tags_count = isset($options['max_tags_count']) ? (int)$options['max_tags_count'] : 3;
$mobile_columns = isset($options['mobile_columns']) ? (int)$options['mobile_columns'] : 1;
$css_class = $options['css_class'] ?? '';
$show_ads = $options['show_ads'] ?? false;
$ads_positions = $options['ads_positions'] ?? '';
$ads_html = $options['ads_html'] ?? '';
$infinite_scroll = $options['infinite_scroll'] ?? false;
$items_per_page = $options['items_per_page'] ?? 6;
$show_special = $options['show_special'] ?? false; // ДОБАВИТЬ ЭТУ СТРОКУ!

// ДЛЯ ОТЛАДКИ - удалить после проверки
if(cmsUser::isAdmin()) {
    echo "<!-- WholeContent Debug Info -->";
    echo "<!-- Infinite Scroll: " . ($infinite_scroll ? 'ENABLED' : 'DISABLED') . " -->";
    echo "<!-- Items count: " . (isset($all_items) ? count($all_items) : 0) . " -->"; 
    echo "<!-- Items per page: " . $items_per_page . " -->";
    echo "<!-- Widget ID: " . $widget->id . " -->";
    echo "<!-- AJAX Path: " . href_to('content', 'wholecontent_load_more') . " -->";
    echo "<!-- Show Special: " . ($show_special ? 'YES' : 'NO') . " -->";
}

$this->addCSS('templates/modern/controllers/content/widgets/wholecontent/conf/base.css', false, 100);
$this->addCSS('templates/modern/controllers/content/widgets/wholecontent/conf/layouts/' . $layout . '.css', false, 101);

// Добавляем CSS для бесконечной прокрутки
if ($infinite_scroll) {
    $this->addCSS('templates/modern/controllers/content/widgets/wholecontent/conf/infinite-scroll.css', false, 102);
}

include __DIR__.'/conf/helpers.tpl.php';
include __DIR__.'/conf/sorting.tpl.php';
$layout_file = __DIR__.'/layout/' . $layout . '.tpl.php';
$layout_file = __DIR__.'/layout/' . preg_replace('/[^a-z0-9_]/i', '', $layout) . '.tpl.php';
if (!is_file($layout_file) || !file_exists($layout_file)) {
    $layout_file = __DIR__.'/layout/column.tpl.php';
}
?>

    <div class="wholecontent-container wholecontent-layout-<?= $layout ?> <?= htmlspecialchars($css_class) ?>" 
     data-mobile-cols="<?= htmlspecialchars($mobile_columns) ?>"
     data-columns="<?= $layout === 'masonry' ? $masonry_columns : $column_count ?>"
     data-layout="<?= $layout ?>"
     data-sorting="<?= $sorting ?>"
     data-show-ads="<?= $show_ads ? '1' : '0' ?>"
     data-ads-positions="<?= htmlspecialchars($ads_positions) ?>"
     data-show-special="<?= $show_special ? '1' : '0' ?>"
     data-special-positions="<?= htmlspecialchars($special_positions) ?>"
     data-custom-positions="<?= htmlspecialchars($custom_positions) ?>">
     
    <div class="row gx-2 gy-3" id="wholecontent-<?= $widget->id ?>">
<?php 
$all_items = $sortItems($articles, $sorting);
if (!empty($all_items)): 
    $item_count = count($all_items);
?>
            <?php foreach ($all_items as $index => $item): ?>
                <?php
                // Проверяем позицию рекламы
                if ($show_ads && $widget->isAdPosition($index, $ads_positions)):
                    $ad_col_class = $widget->getAdColumnClass($layout, $column_count, $mobile_columns);
                ?>
                    <div class="wholecontent-item wholecontent-ad-container <?= $ad_col_class ?>">
                        <?php include __DIR__.'/part/ads_block.tpl.php'; ?>
                    </div>
                <?php endif; ?>

        <?php
        $article = $item['_article'];
        unset($item['_article']);
        $has_image = !empty($item['image']) || ($is_show_no_photo && !empty($default_image));
        
        $is_special = false;
        if ($show_special && $special_positions !== 'none') {
            $is_special = $widget->isSpecialPosition($index, $special_positions, $custom_positions);
        }
        $item['_is_special'] = $is_special;
        
        $item['_schema'] = [
            'context' => 'https://schema.org',
            'type' => 'Article',
            'headline' => htmlspecialchars($item['title'] ?? ''),
            'datePublished' => !empty($item['date_pub']) ? date('c', strtotime($item['date_pub'])) : '',
            'author' => [
                'type' => 'Person',
                'name' => htmlspecialchars($item['user']['nickname'] ?? '')
            ]
                ];
                ?>
                
                <div class="wholecontent-item">
                    <?php include $layout_file; ?>
                </div>
            <?php endforeach; ?>
<?php else: ?>
    <div class="col-12">
        <div class="alert alert-info"><?= LANG_WD_CONTENT_WHOLECONTENT_NO_ITEMS ?></div>
    </div>
<?php endif; ?>
    </div>
</div>

<?php
// Добавляем JavaScript для бесконечной прокрутки
if ($infinite_scroll && !empty($all_items) && count($all_items) >= $items_per_page) {
    $this->addJS('templates/modern/controllers/content/widgets/wholecontent/conf/infinite-scroll.js', false, 200);
    
    $script = "
    <script>
    $(document).ready(function() {
        console.log('Initializing infinite scroll for widget: {$widget->id}');
        $('#wholecontent-{$widget->id}').wholecontentInfiniteScroll({
            widgetId: {$widget->id},
            loadingText: '".LANG_LOADING."',
            errorText: '".LANG_ERROR."',
            noMoreText: '".LANG_WD_CONTENT_WHOLECONTENT_NO_MORE_ITEMS."',
            path: '".href_to('content', 'wholecontent_load_more')."',
            currentCount: {$item_count}
        });
    });
    </script>
    ";
    
    echo $script;
} elseif ($infinite_scroll && cmsUser::isAdmin()) {
    echo "<!-- Infinite scroll disabled: all_items=".(!empty($all_items)?'yes':'no').", count=".(!empty($all_items)?count($all_items):0).", items_per_page={$items_per_page} -->";
}
?>