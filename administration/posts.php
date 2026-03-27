<?php define('IN_ADMIN', true); ?>
<?php
require_once __DIR__ . '/_guard.php';
require_permission($pdo, 'posts.edit');
$posts = $pdo->query("SELECT p.*, u.username FROM posts p LEFT JOIN users u ON u.id = p.user_id ORDER BY p.id DESC")->fetchAll();
include dirname(__DIR__) . '/themes/default/header.php';
?>
<div class="d-flex justify-content-between mb-3">
    <h1>Postai</h1>
    <a class="btn btn-primary" href="post-edit.php">Naujas</a>
</div>
<table class="table table-striped">
    <tr><th>ID</th><th>Pavadinimas</th><th>Statusas</th><th>Autorius</th><th></th></tr>
    <?php foreach ($posts as $post): ?>
    <tr>
        <td><?= (int)$post['id'] ?></td>
        <td><?= e($post['title']) ?></td>
        <td><?= e($post['status']) ?></td>
        <td><?= e($post['username'] ?? '—') ?></td>
        <td>
            <a class="btn btn-sm btn-outline-primary" href="post-edit.php?id=<?= (int)$post['id'] ?>">Redaguoti</a>
            <a class="btn btn-sm btn-outline-danger confirm-delete" href="post-delete.php?id=<?= (int)$post['id'] ?>">Trinti</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php include dirname(__DIR__) . '/themes/default/footer.php'; ?>
