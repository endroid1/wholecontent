<?php
$has_group_content = $show_group && !empty($item['parent_id']);
$show_footer = $show_author || $show_date || $show_comments || $show_views || $has_group_content;
if (!$show_footer) return;
$can_view_hits = $canViewHits($article['ctype']['name']);
?>

<div class="card-footer bg-transparent small text-muted d-flex flex-wrap align-items-center justify-content-between py-2 mt-auto">
    <div class="d-flex flex-wrap align-items-center">
        <?php if ($show_author && !empty($item['user']['id'])): ?>
            <span class="d-inline-flex align-items-center mr-3 me-3">
                <?php html_svg_icon('regular', 'user-circle'); ?>
                <a href="<?= href_to('users', $item['user']['id']) ?>" class="text-decoration-none ml-1 ms-1">
                    <?= html($item['user']['nickname'] ?? '', false) ?>
                </a>
            </span>
        <?php endif; ?>

        <?php if ($show_date && !empty($item['date_pub'])): ?>
            <span class="text-nowrap mr-3 me-3">
                <?= html(string_date_age_max($item['date_pub'], true), false) ?>
            </span>
        <?php endif; ?>

        <?php if ($show_group && !empty($item['parent_id'])): ?>
            <span class="text-nowrap mr-3 me-3">
                <?= LANG_WROTE_IN_GROUP ?> 
                <a href="<?= href_to($item['parent_url'] ?? '#') ?>" class="text-decoration-none ml-1 ms-1">
                    <?= html($item['parent_title'] ?? '', false) ?>
                </a>
            </span>
        <?php endif; ?>

    </div>
	
 <div class="ml-md-auto ms-md-auto">
 
<?php if ($show_views && isset($item['hits_count']) && $can_view_hits): ?>
    <span class="text-nowrap mr-3 me-3">
        <?php html_svg_icon('solid', 'eye'); ?>
        <span class="ml-1 ms-1"><?= (int)$item['hits_count'] ?></span>
    </span>
<?php endif; ?>
 
 
 
    <?php if ($show_comments && !empty($article['ctype']['is_comments'])): ?>
            <?php if (!empty($item['url'])): ?>
                <?php html_svg_icon('regular', 'comments'); ?>
                <a href="<?= html($item['url'], false) . '#comments' ?>" title="<?= LANG_COMMENTS ?>" class="text-decoration-none ml-1 ms-1">
                    <span class="ml-1 ms-1"><?= (int)($item['comments'] ?? 0) ?></span>
                </a>
            <?php else: ?>
                <span class="text-nowrap">
                    <?php html_svg_icon('regular', 'comments'); ?>
                    <span class="ml-1 ms-1"><?= (int)($item['comments'] ?? 0) ?></span>
                </span>
            <?php endif; ?>
        
    <?php endif; ?>
	</div>
</div>