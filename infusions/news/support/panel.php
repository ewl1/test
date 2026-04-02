<?php

function news_render_panel($limit = 5)
{
    foreach (news_recent_items($limit) as $row): ?>
        <div class="mb-2">
            <div class="fw-semibold">
                <a class="text-decoration-none" href="<?= e(!empty($row['slug']) ? public_path('news.php?slug=' . rawurlencode((string)$row['slug'])) : public_path('news.php?id=' . (int)$row['id'])) ?>">
                    <?= e($row['title']) ?>
                </a>
            </div>
            <div class="small text-secondary"><?= e(news_summary_excerpt($row['summary'] ?? '', 120)) ?></div>
        </div>
    <?php endforeach;
}
