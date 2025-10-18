<?php
$is_special = $item['_is_special'] ?? false;
$has_image = !empty($item['image']) || ($is_show_no_photo && !empty($default_image));


$masonry_columns = isset($options['masonry_columns']) ? (int)$options['masonry_columns'] : 3;

$size_class = 'masonry-standard';
if ($is_special) {
    $size_class = 'masonry-wide';
} elseif ($index % 5 === 0) {
    $size_class = 'masonry-tall';
} elseif ($index % 7 === 0) {
    $size_class = 'masonry-wide';
}
?>
<div class="masonry-item <?= $size_class ?>" data-columns="<?= $masonry_columns ?>">
    <div class="card h-100 wholecontent-card <?= $is_special ? 'special-card' : '' ?>">
        <?php 
        extract([
            'item' => $item,
            'article' => $article,
            'has_image' => $has_image,
            'is_show_item_parent' => $is_show_item_parent,
            'is_show_item_cat' => $is_show_item_cat,
            'teaser_len' => $teaser_len,
            'layout' => 'masonry',
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