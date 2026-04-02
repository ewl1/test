<?php

function shoutbox_admin_can_manage_settings()
{
    return has_permission($GLOBALS['pdo'], (int)(current_user()['id'] ?? 0), 'settings.manage')
        || has_permission($GLOBALS['pdo'], (int)(current_user()['id'] ?? 0), 'admin.access');
}

function shoutbox_admin_active_tab(): string
{
    $tab = strtolower((string)($_GET['tab'] ?? 'messages'));
    $allowed = ['messages', 'assistant', 'settings'];
    return in_array($tab, $allowed, true) ? $tab : 'messages';
}

function shoutbox_admin_url(string $tab = 'messages'): string
{
    return 'infusion-admin.php?folder=shoutbox&tab=' . urlencode($tab);
}

function shoutbox_admin_assistant_manager(): \App\Shoutbox\ShoutboxManager
{
    static $manager = null;
    if ($manager === null) {
        $manager = new \App\Shoutbox\ShoutboxManager();
    }
    return $manager;
}

function shoutbox_handle_admin_request()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    verify_csrf();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'save_settings') {
        if (!shoutbox_admin_can_manage_settings()) {
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
        redirect(shoutbox_admin_url('settings'));
    }

    if ($action === 'delete') {
        shoutbox_delete_message((int)($_POST['id'] ?? 0));
        flash('success', __('shoutbox.admin.deleted'));
        redirect(shoutbox_admin_url('messages'));
    }

    if ($action === 'assistant_create') {
        $keyword = trim((string)($_POST['keyword'] ?? ''));
        $response = trim((string)($_POST['response'] ?? ''));
        $threshold = max(1, min(5, (int)($_POST['levenshtein_threshold'] ?? 2)));
        $useLevenshtein = !empty($_POST['use_levenshtein']);
        $isActive = !empty($_POST['is_active']);

        if ($keyword === '') {
            flash('danger', __('shoutbox.admin.assistant.keyword_required'));
            redirect(shoutbox_admin_url('assistant'));
        }

        if ($response === '') {
            flash('danger', __('shoutbox.admin.assistant.response_required'));
            redirect(shoutbox_admin_url('assistant'));
        }

        $id = shoutbox_admin_assistant_manager()->createTrigger($keyword, $response, $useLevenshtein, $threshold, $isActive);
        audit_log(current_user()['id'] ?? null, 'shoutbox_assistant_create', 'shoutbox_bot_triggers', $id, [
            'keyword' => $keyword,
            'use_levenshtein' => $useLevenshtein,
            'threshold' => $threshold,
            'is_active' => $isActive,
        ]);
        flash('success', __('shoutbox.admin.assistant.created'));
        redirect(shoutbox_admin_url('assistant'));
    }

    if ($action === 'assistant_toggle') {
        $id = (int)($_POST['id'] ?? 0);
        $active = !empty($_POST['is_active']);
        shoutbox_admin_assistant_manager()->toggleActive($id, $active);
        audit_log(current_user()['id'] ?? null, 'shoutbox_assistant_toggle', 'shoutbox_bot_triggers', $id, [
            'is_active' => $active,
        ]);
        flash('success', __('shoutbox.admin.assistant.toggled'));
        redirect(shoutbox_admin_url('assistant'));
    }

    if ($action === 'assistant_delete') {
        $id = (int)($_POST['id'] ?? 0);
        shoutbox_admin_assistant_manager()->deleteTrigger($id);
        audit_log(current_user()['id'] ?? null, 'shoutbox_assistant_delete', 'shoutbox_bot_triggers', $id);
        flash('success', __('shoutbox.admin.assistant.deleted'));
        redirect(shoutbox_admin_url('assistant'));
    }
}

function shoutbox_render_admin_tabs(string $activeTab): void
{
    $tabs = [
        'messages' => __('shoutbox.admin.tab.messages'),
        'assistant' => __('shoutbox.admin.tab.assistant'),
    ];

    if (shoutbox_admin_can_manage_settings()) {
        $tabs['settings'] = __('shoutbox.admin.tab.settings');
    }
    ?>
    <ul class="nav nav-tabs mb-4" role="tablist">
        <?php foreach ($tabs as $key => $label): ?>
            <li class="nav-item" role="presentation">
                <a class="nav-link <?= $activeTab === $key ? 'active' : '' ?>" href="<?= e(shoutbox_admin_url($key)) ?>">
                    <?= e($label) ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php
}

function shoutbox_render_admin_settings(): void
{
    ?>
    <div class="card mb-4" id="shoutbox-settings">
        <div class="card-header"><?= e(__('shoutbox.admin.settings.title')) ?></div>
        <div class="card-body">
            <div class="alert alert-info"><?= e(__('shoutbox.admin.settings.description')) ?></div>
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
    <?php
}

function shoutbox_render_admin_messages(array $messages): void
{
    ?>
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
    <?php
}

function shoutbox_render_admin_assistant(array $triggers): void
{
    ?>
    <div class="card mb-4">
        <div class="card-header"><?= e(__('shoutbox.admin.assistant.title')) ?></div>
        <div class="card-body">
            <div class="alert alert-info"><?= e(__('shoutbox.admin.assistant.description')) ?></div>
            <form method="post" class="row g-3 mb-4">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="assistant_create">
                <div class="col-md-4">
                    <label class="form-label"><?= e(__('shoutbox.admin.assistant.keyword')) ?></label>
                    <input class="form-control" type="text" name="keyword" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label"><?= e(__('shoutbox.admin.assistant.response')) ?></label>
                    <input class="form-control" type="text" name="response" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?= e(__('shoutbox.admin.assistant.threshold')) ?></label>
                    <input class="form-control" type="number" min="1" max="5" name="levenshtein_threshold" value="2">
                </div>
                <div class="col-md-4">
                    <div class="form-check mt-md-4 pt-md-2">
                        <input class="form-check-input" type="checkbox" name="use_levenshtein" id="shoutbox-assistant-levenshtein" value="1">
                        <label class="form-check-label" for="shoutbox-assistant-levenshtein"><?= e(__('shoutbox.admin.assistant.levenshtein')) ?></label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check mt-md-4 pt-md-2">
                        <input class="form-check-input" type="checkbox" name="is_active" id="shoutbox-assistant-active" value="1" checked>
                        <label class="form-check-label" for="shoutbox-assistant-active"><?= e(__('shoutbox.admin.assistant.active')) ?></label>
                    </div>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-primary admin-action-button mt-md-4" type="submit"><?= e(__('shoutbox.admin.assistant.add')) ?></button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table align-middle mb-0 admin-table-strong">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th><?= e(__('shoutbox.admin.assistant.keyword')) ?></th>
                        <th><?= e(__('shoutbox.admin.assistant.response')) ?></th>
                        <th><?= e(__('shoutbox.admin.assistant.threshold')) ?></th>
                        <th><?= e(__('shoutbox.admin.assistant.active')) ?></th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($triggers as $trigger): ?>
                        <tr>
                            <td class="admin-strong-cell"><?= (int)$trigger['id'] ?></td>
                            <td class="admin-strong-cell"><?= e($trigger['keyword']) ?></td>
                            <td class="admin-message-cell"><?= e($trigger['response']) ?></td>
                            <td class="admin-table-note">
                                <?= !empty($trigger['use_levenshtein']) ? (int)$trigger['levenshtein_threshold'] : '—' ?>
                            </td>
                            <td class="admin-table-note"><?= !empty($trigger['is_active']) ? __('general.yes') : __('general.no') ?></td>
                            <td class="text-nowrap">
                                <form method="post" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="assistant_toggle">
                                    <input type="hidden" name="id" value="<?= (int)$trigger['id'] ?>">
                                    <input type="hidden" name="is_active" value="<?= !empty($trigger['is_active']) ? '0' : '1' ?>">
                                    <button class="btn btn-sm btn-outline-secondary" type="submit">
                                        <?= e(!empty($trigger['is_active']) ? __('shoutbox.admin.assistant.toggle_off') : __('shoutbox.admin.assistant.toggle_on')) ?>
                                    </button>
                                </form>
                                <form method="post" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="assistant_delete">
                                    <input type="hidden" name="id" value="<?= (int)$trigger['id'] ?>">
                                    <button class="btn btn-sm btn-danger admin-danger-button" type="submit" data-confirm-message="<?= e(__('shoutbox.admin.assistant.delete_confirm')) ?>">
                                        <?= e(__('shoutbox.admin.delete')) ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$triggers): ?>
                        <tr><td colspan="6" class="text-secondary"><?= e(__('shoutbox.admin.assistant.empty')) ?></td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php
}

function shoutbox_render_admin_page()
{
    $messages = shoutbox_get_messages(200);
    $success = flash('success');
    $error = flash('danger');
    $activeTab = shoutbox_admin_active_tab();
    $triggers = shoutbox_admin_assistant_manager()->allTriggers();
    ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= e($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
    <?php endif; ?>

    <?php shoutbox_render_admin_tabs($activeTab); ?>

    <?php if ($activeTab === 'settings' && shoutbox_admin_can_manage_settings()): ?>
        <?php shoutbox_render_admin_settings(); ?>
    <?php elseif ($activeTab === 'assistant'): ?>
        <?php shoutbox_render_admin_assistant($triggers); ?>
    <?php else: ?>
        <?php shoutbox_render_admin_messages($messages); ?>
    <?php endif; ?>
    <?php
}
