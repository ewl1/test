<?php
require_once __DIR__ . '/include/bootstrap.php';
$posts = get_posts($pdo);
include __DIR__ . '/theme/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Naujausi postai</h1>
    <?php if (is_logged_in() && has_permission($pdo, 'posts.create')): ?>
        <a href="admin/post-edit.php" class="btn btn-primary">Naujas postas</a>
    <?php endif; ?>
</div>

<?php foreach ($posts as $post): ?>
<div class="card mb-3">
    <div class="card-body">
        <h3><a href="post.php?id=<?= (int)$post['id'] ?>"><?= e($post['title']) ?></a></h3>
        <div class="text-muted small mb-2">Autorius: <?= e($post['username'] ?? '—') ?> · <?= e(format_dt($post['created_at'])) ?></div>
        <div><?= nl2br($post['content']) ?></div>
    </div>
</div>
<?php endforeach; ?>

<?php include __DIR__ . '/theme/footer.php'; ?>
