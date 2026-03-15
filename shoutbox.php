<?php
require_once __DIR__ . '/include/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_logged_in()) {
    verify_csrf();
    [$ok, $message] = create_shout($pdo, $_POST['message'] ?? '');
    flash($ok ? 'success' : 'error', $message);
    redirect('shoutbox.php');
}

$shouts = get_shouts($pdo);
include __DIR__ . '/theme/header.php';
?>
<h1>Šaukykla</h1>

<?php if (is_logged_in()): ?>
<form method="post" class="card card-body mb-3">
    <?= csrf_input() ?>
    <div class="mb-3"><textarea class="form-control" name="message" rows="3" placeholder="Rašyk žinutę..."></textarea></div>
    <button class="btn btn-primary">Skelbti</button>
</form>
<?php endif; ?>

<div class="list-group">
<?php foreach ($shouts as $shout): ?>
    <div class="list-group-item">
        <div class="d-flex">
            <img src="<?= e(user_avatar_url($shout)) ?>" alt="" width="48" height="48" class="rounded me-3">
            <div class="flex-grow-1">
                <div class="fw-bold"><?= e($shout['username'] ?? 'Svečias') ?></div>
                <div class="small text-muted mb-2"><?= e(format_dt($shout['created_at'])) ?></div>
                <div><?= bbcode_to_html($shout['message']) ?></div>
                <?php if (is_logged_in() && has_permission($pdo, 'shoutbox.moderate')): ?>
                    <div class="mt-2">
                        <a class="btn btn-sm btn-outline-primary" href="admin/shout-edit.php?id=<?= (int)$shout['id'] ?>">Redaguoti</a>
                        <a class="btn btn-sm btn-outline-danger confirm-delete" href="admin/shout-delete.php?id=<?= (int)$shout['id'] ?>">Trinti</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>

<?php include __DIR__ . '/theme/footer.php'; ?>
