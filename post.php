<?php
require_once __DIR__ . '/includes/bootstrap.php';

$id = (int)($_GET['id'] ?? 0);
$post = get_post($pdo, $id);
if (!$post) {
    abort_http(404, 'Įrašas nerastas.');
}

include __DIR__ . '/themes/default/header.php';
?>
<div class="card">
    <div class="card-body">
        <h1 class="mb-3"><?= e($post['title']) ?></h1>
        <div class="text-muted small mb-3">
            Autorius:
            <?php if (!empty($post['user_id'])): ?>
                <a class="text-decoration-none" href="<?= user_profile_url((int)$post['user_id']) ?>"><?= e($post['username'] ?? 'Narys') ?></a>
            <?php else: ?>
                <?= e($post['username'] ?? '-') ?>
            <?php endif; ?>
            · <?= e(format_dt($post['created_at'])) ?>
        </div>
        <div><?= nl2br(e($post['content'])) ?></div>
    </div>
</div>
<?php include __DIR__ . '/themes/default/footer.php'; ?>
