<?php
$has_image = !empty($item['image']) || ($is_show_no_photo && !empty($default_image));
$is_special = $item['_is_special'] ?? false;
$columns_config = $widget->getOption('dynamic_layout_columns', '1,3,2');
$columns_per_row = array_map('intval', explode(',', $columns_config));
$total_rows_pattern = array_sum($columns_per_row);
$cycle_position = $total_rows_pattern > 0 ? $index % $total_rows_pattern : 0;
$cumulative = 0;
$items_in_row = 1;
foreach ($columns_per_row as $row_items) {
    if ($cycle_position < ($cumulative + $row_items)) {
        $items_in_row = $row_items;
        break;
    }
    $cumulative += $row_items;
}
$col_size = $items_in_row > 0 ? floor(12 / $items_in_row) : 12;
$col_class = 'col-md-'.max(1, $col_size);
if ($mobile_columns > 1) {
    $col_class .= ' col-'.(12/$mobile_columns);
}
?>

<?php if ($index === 0): ?>
<?php endif; ?>

<div class="<?= $col_class ?> dynamic-card-<?= $index ?>">
    <div class="card h-100 d-flex flex-column wholecontent-card <?= $is_special ? 'special-card' : '' ?>">
        <?php 
        extract([
            'item' => $item,
            'article' => $article,
            'has_image' => $has_image,
            'is_show_item_parent' => $is_show_item_parent,
            'is_show_item_cat' => $is_show_item_cat,
            'teaser_len' => $teaser_len,
            'layout' => 'dynamic',
            'is_show_tags' => $is_show_tags,
            'max_tags_count' => $max_tags_count,
            'show_author' => $show_author,
            'show_date' => $show_date,
            'show_comments' => $show_comments,
            'show_views' => $show_views,
            'show_group' => $show_group
        ]);
        
        include __DIR__.'/../part/card_header.tpl.php';
        include __DIR__.'/../part/card_body.tpl.php';
        if ($show_author || $show_date || $show_comments || $show_views) {
            include __DIR__.'/../part/details.tpl.php';
        }
        ?>
    </div>
</div>