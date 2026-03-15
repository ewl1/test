<?php
require_permission('forum.admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $subject = trim($_POST['subject'] ?? '');
    $replies = (int)($_POST['replies'] ?? 0);
    if ($subject !== '') {
        $stmt = $GLOBALS['pdo']->prepare("INSERT INTO infusion_forum_threads (subject, replies) VALUES (:s,:r)");
        $stmt->execute([':s' => $subject, ':r' => $replies]);
        echo '<div class="alert alert-success">Tema pridėta.</div>';
    }
}
?>
<div class="card">
    <div class="card-header">Forumo infusion administravimas</div>
    <div class="card-body">
        <form method="post" class="row g-3">
            <?= csrf_field() ?>
            <div class="col-md-8"><label class="form-label">Tema</label><input class="form-control" name="subject"></div>
            <div class="col-md-4"><label class="form-label">Atsakymai</label><input class="form-control" type="number" name="replies" value="0"></div>
            <div class="col-12"><button class="btn btn-primary">Pridėti</button></div>
        </form>
        <hr>
        <?php foreach ($GLOBALS['pdo']->query("SELECT subject, replies FROM infusion_forum_threads ORDER BY id DESC LIMIT 20")->fetchAll() as $row): ?>
            <div class="d-flex justify-content-between border-bottom py-2">
                <span><?= e($row['subject']) ?></span>
                <span class="badge text-bg-light border"><?= (int)$row['replies'] ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</div>
