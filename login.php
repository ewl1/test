<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (current_user()) {
    redirect(public_path('index.php'));
}

$errorMessage = auth_error();
$successMessage = flash('success');
$message = $errorMessage ?: $successMessage;
$messageType = $errorMessage ? 'danger' : 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (login(trim((string)($_POST['email'] ?? '')), (string)($_POST['password'] ?? ''))) {
        redirect(public_path('index.php'));
    }

    $message = auth_error() ?? __('auth.login.failed');
    $messageType = 'danger';
}

include THEMES . setting('current_theme', CURRENT_THEME) . '/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h1 class="h4 mb-0"><?= e(__('auth.login.title')) ?></h1>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= public_path('administration/login.php') ?>"><?= e(__('nav.admin_login')) ?></a>
                </div>
                <?php if ($message): ?><div class="alert alert-<?= e($messageType) ?>"><?= e($message) ?></div><?php endif; ?>
                <form method="post">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label"><?= e(__('auth.login.email')) ?></label>
                        <input class="form-control" type="email" name="email" autocomplete="email">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= e(__('auth.password')) ?></label>
                        <input class="form-control js-toggle-password" type="password" name="password" autocomplete="current-password">
                        <div class="form-check mt-2">
                            <input class="form-check-input" id="login-show-password" type="checkbox" data-password-toggle data-password-target=".js-toggle-password">
                            <label class="form-check-label" for="login-show-password"><?= e(__('auth.password.show')) ?></label>
                        </div>
                    </div>
                    <button class="btn btn-primary"><?= e(__('auth.login.submit')) ?></button>
                </form>
                <div class="mt-3 text-end">
                    <a class="small" href="<?= public_path('forgot-password.php') ?>"><?= e(__('auth.login.forgot')) ?></a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include THEMES . setting('current_theme', CURRENT_THEME) . '/footer.php'; ?>
