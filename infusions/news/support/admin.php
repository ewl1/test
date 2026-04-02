<?php

function news_handle_admin_request()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    verify_csrf();

    $action = trim((string)($_POST['action'] ?? 'create_news'));
    if ($action === 'save_settings') {
        $editorMode = trim((string)($_POST['editor_mode'] ?? 'bbcode'));
        if (!in_array($editorMode, ['bbcode', 'tinymce', 'mixed'], true)) {
            $editorMode = 'bbcode';
        }

        news_save_setting('editor_mode', $editorMode);
        flash('success', __('news.admin.settings_saved'));
        redirect('infusion-admin.php?folder=news&tab=settings');
    }

    if (news_create_item($_POST['title'] ?? '', $_POST['summary'] ?? '')) {
        flash('success', __('news.admin.created'));
    } else {
        flash('error', __('news.admin.title_required'));
    }

    redirect('infusion-admin.php?folder=news&tab=content');
}

function news_render_admin_page()
{
    if (news_editor_mode() === 'tinymce' || news_editor_mode() === 'mixed') {
        editor_register_tinymce_assets();
    }
    register_page_script('infusions/news/assets/js/news.js');

    $success = flash('success');
    $error = flash('error');
    $items = news_recent_items(20);
    $editorMode = news_editor_mode();
    $tab = trim((string)($_GET['tab'] ?? 'content'));
    if (!in_array($tab, ['content', 'settings'], true)) {
        $tab = 'content';
    }
    ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= e($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header"><?= e(__('news.admin.title')) ?></div>
        <div class="card-body">
            <ul class="nav nav-tabs mb-4">
                <li class="nav-item">
                    <a class="nav-link <?= $tab === 'content' ? 'active' : '' ?>" href="infusion-admin.php?folder=news&tab=content">Naujienos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $tab === 'settings' ? 'active' : '' ?>" href="infusion-admin.php?folder=news&tab=settings">Nustatymai</a>
                </li>
            </ul>

            <?php if ($tab === 'settings'): ?>
                <form method="post" class="row g-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save_settings">
                    <div class="col-md-6">
                        <label class="form-label"><?= e(__('news.field.editor_mode')) ?></label>
                        <select class="form-select" name="editor_mode">
                            <option value="bbcode" <?= $editorMode === 'bbcode' ? 'selected' : '' ?>><?= e(__('news.editor_mode.bbcode')) ?></option>
                            <option value="tinymce" <?= $editorMode === 'tinymce' ? 'selected' : '' ?>><?= e(__('news.editor_mode.tinymce')) ?></option>
                            <option value="mixed" <?= $editorMode === 'mixed' ? 'selected' : '' ?>><?= e(__('news.editor_mode.mixed')) ?></option>
                        </select>
                        <div class="form-text"><?= e(__('news.field.editor_mode_help')) ?></div>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-outline-primary" type="submit"><?= e(__('news.action.save_settings')) ?></button>
                    </div>
                </form>
            <?php else: ?>
                <form method="post" class="row g-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="create_news">
                    <div class="col-md-6">
                        <label class="form-label"><?= e(__('news.field.title')) ?></label>
                        <input class="form-control" name="title">
                    </div>
                    <div class="col-12">
                        <label class="form-label"><?= e(__('news.field.summary')) ?></label>
                        <?php if ($editorMode === 'bbcode' || $editorMode === 'mixed'): ?>
                            <div class="news-editor-toolbar mb-2">
                                <?php foreach (news_bbcode_buttons() as $button): ?>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-secondary"
                                        data-news-editor-target="news-summary"
                                        data-news-insert-text="<?= e($button['insert']) ?>"
                                        data-news-insert-html="<?= e($button['html']) ?>"
                                    ><?= e($button['label']) ?></button>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <textarea
                            class="form-control"
                            id="news-summary"
                            name="summary"
                            rows="10"
                            data-news-editor-mode="<?= e($editorMode) ?>"
                            data-news-tinymce-config="<?= e(editor_tinymce_config_json()) ?>"
                        ></textarea>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary"><?= e(__('news.action.create')) ?></button>
                    </div>
                </form>
                <hr>
                <?php foreach ($items as $row): ?>
                    <div class="border-bottom py-2">
                        <div class="fw-semibold"><?= e($row['title']) ?></div>
                        <div class="small text-secondary"><?= e(news_summary_excerpt($row['summary'] ?? '')) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
