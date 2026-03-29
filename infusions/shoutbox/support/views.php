<?php

function shoutbox_render_editor($context = 'page', $textareaId = 'shoutbox-message', $redirectPath = 'shoutbox.php', $compact = false)
{
    $success = flash(shoutbox_flash_key($context, 'success'));
    $error = flash(shoutbox_flash_key($context, 'error'));

    if ($success): ?>
        <div class="alert alert-success"><?= e($success) ?></div>
    <?php endif;
    if ($error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
    <?php endif;

    if (!current_user()): ?>
        <div class="alert alert-info mb-0"><?= e(__('shoutbox.post.login')) ?> <a href="<?= public_path('login.php') ?>"><?= e(__('nav.login')) ?></a>.</div>
        <?php
        return;
    endif;
    ?>

    <form method="post" class="shoutbox-editor-form <?= $compact ? 'shoutbox-editor-form-compact' : 'shoutbox-editor-form-page' ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="shoutbox_action" value="post">
        <input type="hidden" name="shoutbox_context" value="<?= e($context) ?>">
        <input type="hidden" name="redirect_to" value="<?= e($redirectPath) ?>">

        <div class="mb-2 d-flex flex-wrap gap-2 shoutbox-toolbar">
            <?php foreach (shoutbox_bbcode_buttons() as $button): ?>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-shoutbox-editor-target="<?= e($textareaId) ?>" data-shoutbox-insert-text="<?= e($button['insert']) ?>"><?= e($button['label']) ?></button>
            <?php endforeach; ?>
        </div>
        <div class="mb-2 d-flex flex-wrap gap-2 shoutbox-smiley-toolbar">
            <?php foreach (shoutbox_smileys() as $smiley):
                $code = (string)($smiley['code'] ?? '');
                if ($code === '') {
                    continue;
                }
            ?>
                <button type="button" class="btn btn-sm btn-outline-warning" data-shoutbox-editor-target="<?= e($textareaId) ?>" data-shoutbox-smiley-code="<?= e($code) ?>" title="<?= e($smiley['title'] ?? $code) ?>"><?= site_smiley_button_html($smiley, 'shoutbox-smiley') ?></button>
            <?php endforeach; ?>
        </div>

        <div class="mb-3 shoutbox-editor-field">
            <label class="form-label"><?= e($compact ? __('shoutbox.comment') : __('shoutbox.message')) ?></label>
            <textarea class="form-control shoutbox-editor-textarea" id="<?= e($textareaId) ?>" name="message" rows="<?= $compact ? 3 : 4 ?>" maxlength="500" placeholder="<?= e($compact ? __('shoutbox.comment.placeholder') : __('shoutbox.message.placeholder')) ?>"></textarea>
            <div class="form-text"><?= e(__('shoutbox.allowed_bbcode')) ?></div>
        </div>
        <button class="btn btn-primary"><?= e($compact ? __('shoutbox.comment.send') : __('shoutbox.send')) ?></button>
    </form>

    <?php
}

function render_shoutbox_page()
{
    $perPage = shoutbox_messages_per_page();
    $page = max(1, (int)($_GET['page'] ?? 1));
    $total = shoutbox_count_messages();
    $pager = paginate($total, $perPage, $page);

    if (($pager['pages'] ?? 0) > 0 && $page > (int)$pager['pages']) {
        $page = (int)$pager['pages'];
        $pager = paginate($total, $perPage, $page);
    }

    $messages = shoutbox_get_messages($perPage, (int)$pager['offset']);
    include THEMES . setting('current_theme', CURRENT_THEME) . '/header.php';
    ?>
    <div class="row justify-content-center shoutbox-page">
        <div class="col-lg-8">
            <div class="card mb-3 shoutbox-composer-card">
                <div class="card-body">
                    <h1 class="h4 mb-3"><?= e(__('shoutbox.title')) ?></h1>
                    <?php shoutbox_render_editor('page', 'shoutbox-message', 'shoutbox.php', false); ?>
                </div>
            </div>

            <div class="card shoutbox-messages-card">
                <div class="card-body">
                    <?php if (!$messages): ?>
                        <p class="text-secondary mb-0"><?= e(__('shoutbox.empty')) ?></p>
                    <?php endif; ?>

                    <?php foreach ($messages as $message): ?>
                        <article class="shoutbox-message-item border-bottom py-3" id="shoutbox-message-<?= (int)$message['id'] ?>">
                            <div class="d-flex justify-content-between gap-3 align-items-start">
                                <div class="shoutbox-message-meta">
                                    <?php if (!empty($message['user_id'])): ?>
                                        <strong><a class="text-decoration-none" href="<?= user_profile_url((int)$message['user_id']) ?>"><?= e($message['username'] ?? __('member.none')) ?></a></strong>
                                    <?php else: ?>
                                        <strong><?= e($message['username'] ?? __('member.guest')) ?></strong>
                                    <?php endif; ?>
                                    <div class="text-secondary small"><?= e(format_dt($message['created_at'])) ?></div>
                                </div>
                            </div>
                            <div class="mt-2 shoutbox-message-body"><?= shoutbox_escape_and_format($message['message']) ?></div>
                        </article>
                    <?php endforeach; ?>

                    <?php $pagination = render_pagination(public_path('shoutbox.php'), $pager); ?>
                    <?php if ($pagination !== ''): ?>
                        <div class="mt-3"><?= $pagination ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php
    include THEMES . setting('current_theme', CURRENT_THEME) . '/footer.php';
}
