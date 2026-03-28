<?php
define('IN_ADMIN', true);
require_once dirname(__DIR__) . '/includes/bootstrap.php';

$user = current_user();
if ($user) {
    if (has_permission($GLOBALS['pdo'], (int)$user['id'], 'admin.access') && is_admin_session_verified()) {
        redirect(public_path('administration/index.php'));
    }
    if (!has_permission($GLOBALS['pdo'], (int)$user['id'], 'admin.access')) {
        redirect(public_path('index.php'));
    }
}

$error = auth_error();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (login_admin(trim((string)($_POST['email'] ?? '')), (string)($_POST['password'] ?? ''))) {
        redirect(public_path('administration/index.php'));
    }

    $error = auth_error() ?? __('auth.admin_login.failed');
}

include THEMES . 'default/admin_header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-body">
                <h1 class="h4 mb-3"><?= e(__('auth.admin_login.title')) ?></h1>
                <p class="text-secondary"><?= e(__('auth.admin_login.description')) ?></p>
                <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
                <form method="post">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label"><?= e(__('auth.login.email')) ?></label>
                        <input class="form-control" type="email" name="email" autocomplete="username">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= e(__('auth.password')) ?></label>
                        <input class="form-control js-toggle-password" type="password" name="password" autocomplete="current-password">
                        <div class="form-check mt-2">
                            <input class="form-check-input" id="admin-login-show-password" type="checkbox" data-password-toggle data-password-target=".js-toggle-password">
                            <label class="form-check-label" for="admin-login-show-password"><?= e(__('auth.password.show')) ?></label>
                        </div>
                    </div>
                    <button class="btn btn-primary"><?= e(__('auth.admin_login.submit')) ?></button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include THEMES . 'default/admin_footer.php'; ?>
