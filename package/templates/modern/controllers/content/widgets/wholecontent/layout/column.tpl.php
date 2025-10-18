<?php
$is_special = $item['_is_special'] ?? false;
$columns = max(1, min($column_count, 6));
$col_class = 'col-12'; 
$has_image = !empty($item['image']) || ($is_show_no_photo && !empty($default_image));

if ($mobile_columns > 1) {
    $col_class .= ' col-sm-'.(12/$mobile_columns);
}
$col_class .= ' col-md-6 col-lg-'.(12/$columns);
?>
<div class="<?= trim($col_class) ?>">
    <div class="card h-100 d-flex flex-column wholecontent-card <?= $is_special ? 'special-card' : '' ?>">
        <?php 
        extract([
            'item' => $item,
            'article' => $article,
            'has_image' => $has_image,
            'is_show_item_parent' => $is_show_item_parent,
            'is_show_item_cat' => $is_show_item_cat,
            'teaser_len' => $teaser_len,
			'layout' => 'column',
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