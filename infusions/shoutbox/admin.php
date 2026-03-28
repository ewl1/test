<?php
require_permission('shoutbox.moderate');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (($_POST['action'] ?? '') === 'delete') {
        shoutbox_delete_message((int)($_POST['id'] ?? 0));
        echo '<div class="alert alert-success">' . e(__('shoutbox.admin.deleted')) . '</div>';
    }
}

$messages = shoutbox_get_messages(200);
?>
<div class="card">
    <div class="card-header"><?= e(__('shoutbox.admin.title')) ?></div>
    <div class="card-body">
        <div class="alert alert-info"><?= e(__('shoutbox.admin.description')) ?></div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>ID</th><th><?= e(__('shoutbox.admin.user')) ?></th><th><?= e(__('shoutbox.message')) ?></th><th><?= e(__('shoutbox.admin.date')) ?></th><th></th></tr></thead>
                <tbody>
                <?php foreach ($messages as $message): ?>
                    <tr>
                        <td><?= (int)$message['id'] ?></td>
                        <td><?= e($message['username'] ?? __('member.guest')) ?></td>
                        <td class="min-width-320"><?= shoutbox_escape_and_format($message['message']) ?></td>
                        <td><?= e($message['created_at']) ?></td>
                        <td>
                            <form method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$message['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger" data-confirm-message="<?= e(__('shoutbox.admin.delete_confirm')) ?>"><?= e(__('shoutbox.admin.delete')) ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$messages): ?>
                    <tr><td colspan="5" class="text-secondary"><?= e(__('shoutbox.admin.empty')) ?></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
