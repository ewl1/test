<?php
require_permission('shoutbox.moderate');

$canManageShoutboxSettings = has_permission($GLOBALS['pdo'], (int)(current_user()['id'] ?? 0), 'settings.manage')
    || has_permission($GLOBALS['pdo'], (int)(current_user()['id'] ?? 0), 'admin.access');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'save_settings') {
        if (!$canManageShoutboxSettings) {
            abort_http(403, __('permissions.denied_specific', ['permission' => 'settings.manage']));
        }

        $shoutboxOrder = strtolower((string)($_POST['shoutbox_order'] ?? 'desc'));
        save_setting('shoutbox_order', $shoutboxOrder === 'asc' ? 'asc' : 'desc');

        $shoutboxPerPage = max(5, min(100, (int)($_POST['shoutbox_messages_per_page'] ?? 20)));
        save_setting('shoutbox_messages_per_page', (string)$shoutboxPerPage);

        $shoutboxPanelMessages = max(3, min(20, (int)($_POST['shoutbox_panel_messages'] ?? 5)));
        save_setting('shoutbox_panel_messages', (string)$shoutboxPanelMessages);

        audit_log(current_user()['id'] ?? null, 'shoutbox_settings_update', 'settings', null, [
            'order' => shoutbox_normalize_order($shoutboxOrder),
            'messages_per_page' => $shoutboxPerPage,
            'panel_messages' => $shoutboxPanelMessages,
        ]);

        flash('success', __('shoutbox.admin.settings.saved'));
        redirect('infusion-admin.php?folder=shoutbox');
    }

    if ($action === 'delete') {
        shoutbox_delete_message((int)($_POST['id'] ?? 0));
        flash('success', __('shoutbox.admin.deleted'));
        redirect('infusion-admin.php?folder=shoutbox');
    }
}

$messages = shoutbox_get_messages(200);
$success = flash('success');
?>
<?php if ($success): ?>
    <div class="alert alert-success"><?= e($success) ?></div>
<?php endif; ?>

<?php if ($canManageShoutboxSettings): ?>
    <div class="card mb-4" id="shoutbox-settings">
        <div class="card-header"><?= e(__('shoutbox.admin.settings.title')) ?></div>
        <div class="card-body">
            <div class="alert alert-info"><?= e(__('shoutbox.admin.settings.description')) ?></div>
            <div class="d-flex flex-wrap gap-2 mb-3">
                <a class="btn btn-sm btn-outline-secondary admin-action-button" href="smileys.php">Šypsenėlių valdymas</a>
            </div>
            <form method="post" class="row g-3">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save_settings">
                <div class="col-md-6">
                    <label class="form-label"><?= e(__('shoutbox.admin.settings.order')) ?></label>
                    <select class="form-select" name="shoutbox_order">
                        <option value="desc" <?= setting('shoutbox_order', 'desc') === 'desc' ? 'selected' : '' ?>><?= e(__('shoutbox.admin.settings.order.desc')) ?></option>
                        <option value="asc" <?= setting('shoutbox_order', 'desc') === 'asc' ? 'selected' : '' ?>><?= e(__('shoutbox.admin.settings.order.asc')) ?></option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?= e(__('shoutbox.admin.settings.per_page')) ?></label>
                    <input class="form-control" type="number" min="5" max="100" name="shoutbox_messages_per_page" value="<?= e(setting('shoutbox_messages_per_page', '20')) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?= e(__('shoutbox.admin.settings.panel_limit')) ?></label>
                    <input class="form-control" type="number" min="3" max="20" name="shoutbox_panel_messages" value="<?= e(setting('shoutbox_panel_messages', '5')) ?>">
                </div>
                <div class="col-12">
                    <button class="btn btn-primary admin-action-button" type="submit"><?= e(__('shoutbox.admin.settings.save')) ?></button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header"><?= e(__('shoutbox.admin.messages.title')) ?></div>
    <div class="card-body">
        <div class="alert alert-info"><?= e(__('shoutbox.admin.description')) ?></div>
        <div class="table-responsive">
            <table class="table align-middle mb-0 admin-table-strong">
                <thead><tr><th>ID</th><th><?= e(__('shoutbox.admin.user')) ?></th><th><?= e(__('shoutbox.message')) ?></th><th><?= e(__('shoutbox.admin.date')) ?></th><th></th></tr></thead>
                <tbody>
                <?php foreach ($messages as $message): ?>
                    <tr>
                        <td class="admin-strong-cell"><?= (int)$message['id'] ?></td>
                        <td class="admin-strong-cell"><?= e($message['username'] ?? __('member.guest')) ?></td>
                        <td class="min-width-320 admin-message-cell"><?= shoutbox_escape_and_format($message['message']) ?></td>
                        <td class="admin-table-note"><?= e($message['created_at']) ?></td>
                        <td>
                            <form method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$message['id'] ?>">
                                <button class="btn btn-sm btn-danger admin-danger-button" data-confirm-message="<?= e(__('shoutbox.admin.delete_confirm')) ?>"><?= e(__('shoutbox.admin.delete')) ?></button>
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
