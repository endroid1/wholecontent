<?php
$is_special = $item['_is_special'] ?? false;
$show_tags = ($is_show_tags && !empty($item['tags']));
$show_categories = ($is_show_item_parent || $is_show_item_cat) && (!empty($item['cat_slug']) || !empty($article['ctype']['title']));
?>

<?php if ($has_image): ?>
<div class="<?= $is_special ? 'ratio ratio-21x9' : 'ratio ratio-16x9' ?> card-img-container">
    <?= $renderImage($item, $article, 'img-fluid object-fit-cover') ?>
    
    <?php if ($show_tags): ?>
    <div class="card-tags-container">
        <?php 
        $tags = is_array($item['tags']) ? $item['tags'] : explode(',', $item['tags']);
        $tags_display = array_slice(array_filter(array_map('trim', $tags)), 0, $max_tags_count);
        foreach ($tags_display as $tag): 
            $tag_url = href_to('tags', urlencode($tag));
        ?>
            <a href="<?= $tag_url ?>" class="card-tag">
                <span class="card-tag-badge">
                    <?php html_svg_icon('solid', 'tag'); ?>
                    <?= html($tag) ?>
                </span>
            </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>