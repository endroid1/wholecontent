<?php
$show_category_block = ($is_show_item_parent || $is_show_item_cat) && 
                      (!empty($item['cat_slug']) || !empty($article['ctype']['title']));
?>

<div class="card-body flex-grow-1">
    <?php if ($show_category_block): ?>
    <div class="d-flex align-items-center small mb-2 text-muted">
        <?php if ($is_show_item_parent): ?>
            <a href="<?= href_to($article['ctype']['name']) ?>" class="text-decoration-none">
                <?= html($article['ctype']['title'] ?? '') ?>
            </a>
            <?php if ($is_show_item_cat && !empty($item['cat_slug']) && !empty($item['cat_name'])): ?>
            <span class="mx-1">/</span>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php if ($is_show_item_cat && !empty($item['cat_slug']) && !empty($item['cat_name'])): ?>
            <a href="<?= href_to($article['ctype']['name'], $item['cat_slug']) ?>" class="text-decoration-none">
                <?= html($item['cat_name']) ?>
            </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <h3 class="card-title">
        <?php if (!empty($item['url'])): ?>
            <a href="<?= html($item['url'], false) ?>" class="text-decoration-none">
                <?= html($item['title'] ?? '', false) ?>
            </a>
        <?php else: ?>
            <?= html($item['title'] ?? '', false) ?>
        <?php endif; ?>
        
        <?php if (!empty($item['is_private'])): ?>
            <span class="badge bg-secondary ms-1" title="<?= LANG_PRIVACY_PRIVATE ?>">
                <?php html_svg_icon('solid', 'lock'); ?>
            </span>
        <?php endif; ?>
    </h3>
    
    <?php if (!empty($article['teaser_field']) && !empty($item[$article['teaser_field']])): ?>
        <div class="card-text mt-2 flex-grow-1">
            <?php if (empty($item['is_private'])): ?>
                <?= !empty($teaser_len) ? string_short($item[$article['teaser_field']], $teaser_len) : $item[$article['teaser_field']] ?>
            <?php else: ?>
                <div class="alert alert-warning small p-2 mb-0"><?= LANG_PRIVACY_PRIVATE_HINT ?></div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>