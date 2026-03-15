<?php
$hasSlug = $GLOBALS['pdo']->query("SHOW COLUMNS FROM infusion_news LIKE 'slug'")->fetchAll();
$select = $hasSlug ? "title, summary, created_at, slug" : "title, summary, created_at";
$stmt = $GLOBALS['pdo']->query("SELECT $select FROM infusion_news ORDER BY id DESC LIMIT 5");
foreach ($stmt->fetchAll() as $row): ?>
    <div class="mb-2">
        <div class="fw-semibold"><?= e($row['title']) ?></div>
        <div class="small text-secondary"><?= e($row['summary']) ?></div>
    </div>
<?php endforeach; ?>
