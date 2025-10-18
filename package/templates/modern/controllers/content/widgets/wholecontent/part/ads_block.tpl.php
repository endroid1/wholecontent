<?php
if (empty($ads_html)) return;
?>

<div class="wholecontent-ad-container <?= $col_class ?? 'col-12' ?>">
    <div class="wholecontent-ad-block h-100">
        <?= $ads_html ?>
    </div>
</div>

<style>
.wholecontent-ad-block {
    min-height: 200px;
    transition: all 0.3s ease;
}

.wholecontent-ad-block:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.1);
}

/* Адаптивность */
@media (max-width: 767px) {
    .wholecontent-ad-block {
        min-height: 150px;
        margin: 10px 0;
    }
}
</style>