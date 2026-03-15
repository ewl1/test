<?php
$stmt = $GLOBALS['pdo']->query("SELECT subject, replies FROM infusion_forum_threads ORDER BY id DESC LIMIT 5");
foreach ($stmt->fetchAll() as $row): ?>
    <div class="d-flex justify-content-between border-bottom py-2">
        <span><?= e($row['subject']) ?></span>
        <span class="badge text-bg-light border"><?= (int)$row['replies'] ?></span>
    </div>
<?php endforeach; ?>
