<?php
require_permission('news.admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $title = trim($_POST['title'] ?? '');
    $summary = trim($_POST['summary'] ?? '');
    if ($title !== '') {
        $hasSlug = $GLOBALS['pdo']->query("SHOW COLUMNS FROM infusion_news LIKE 'slug'")->fetchAll();
        if ($hasSlug) {
            $stmt = $GLOBALS['pdo']->prepare("INSERT INTO infusion_news (title, summary, slug) VALUES (:t,:s,:slug)");
            $stmt->execute([
                ':t' => $title,
                ':s' => $summary,
                ':slug' => mb_strtolower(str_replace(' ', '-', $title))
            ]);
        } else {
            $stmt = $GLOBALS['pdo']->prepare("INSERT INTO infusion_news (title, summary) VALUES (:t,:s)");
            $stmt->execute([':t' => $title, ':s' => $summary]);
        }
        echo '<div class="alert alert-success">Naujiena pridėta.</div>';
    }
}
?>
<div class="card">
    <div class="card-header">Naujienų infusion administravimas</div>
    <div class="card-body">
        <form method="post" class="row g-3">
            <?= csrf_field() ?>
            <div class="col-md-6"><label class="form-label">Pavadinimas</label><input class="form-control" name="title"></div>
            <div class="col-md-6"><label class="form-label">Santrauka</label><input class="form-control" name="summary"></div>
            <div class="col-12"><button class="btn btn-primary">Pridėti</button></div>
        </form>
        <hr>
        <?php foreach ($GLOBALS['pdo']->query("SELECT title, summary, created_at FROM infusion_news ORDER BY id DESC LIMIT 20")->fetchAll() as $row): ?>
            <div class="border-bottom py-2">
                <div class="fw-semibold"><?= e($row['title']) ?></div>
                <div class="small text-secondary"><?= e($row['summary']) ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
