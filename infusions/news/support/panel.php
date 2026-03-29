<?php

function news_render_panel($limit = 5)
{
    foreach (news_recent_items($limit) as $row): ?>
        <div class="mb-2">
            <div class="fw-semibold"><?= e($row['title']) ?></div>
            <div class="small text-secondary"><?= e($row['summary']) ?></div>
        </div>
    <?php endforeach;
}
