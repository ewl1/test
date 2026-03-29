<?php

function news_handle_admin_request()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    verify_csrf();
    if (news_create_item($_POST['title'] ?? '', $_POST['summary'] ?? '')) {
        flash('success', __('news.admin.created'));
    } else {
        flash('error', __('news.admin.title_required'));
    }

    redirect('infusion-admin.php?folder=news');
}

function news_render_admin_page()
{
    $success = flash('success');
    $error = flash('error');
    $items = news_recent_items(20);
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
            <form method="post" class="row g-3">
                <?= csrf_field() ?>
                <div class="col-md-6">
                    <label class="form-label"><?= e(__('news.field.title')) ?></label>
                    <input class="form-control" name="title">
                </div>
                <div class="col-md-6">
                    <label class="form-label"><?= e(__('news.field.summary')) ?></label>
                    <input class="form-control" name="summary">
                </div>
                <div class="col-12">
                    <button class="btn btn-primary"><?= e(__('news.action.create')) ?></button>
                </div>
            </form>
            <hr>
            <?php foreach ($items as $row): ?>
                <div class="border-bottom py-2">
                    <div class="fw-semibold"><?= e($row['title']) ?></div>
                    <div class="small text-secondary"><?= e($row['summary']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}
