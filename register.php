<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (current_user()) {
    redirect(public_path('index.php'));
}

$errors = [];
$old = [
    'username' => '',
    'email' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $old['username'] = trim((string)($_POST['username'] ?? ''));
    $old['email'] = mb_strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    $passwordConfirm = (string)($_POST['password_confirmation'] ?? '');

    if ($password !== $passwordConfirm) {
        $errors[] = __('auth.register.password_mismatch');
    } else {
        try {
            $errors = register_user($old['username'], $old['email'], $password);
            if (!$errors) {
                flash('success', __('auth.register.success'));
                redirect(public_path('login.php'));
            }
        } catch (Throwable $e) {
            $errors[] = __('auth.register.save_failed');
        }
    }
}

include THEMES . setting('current_theme', CURRENT_THEME) . '/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <h1 class="h4 mb-3"><?= e(__('auth.register.title')) ?></h1>
                <?php if ($errors): ?>
                    <div class="alert alert-danger"><?= e(implode(' ', $errors)) ?></div>
                <?php endif; ?>
                <form method="post">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label"><?= e(__('auth.register.username')) ?></label>
                        <input class="form-control" name="username" value="<?= e($old['username']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= e(__('auth.register.email')) ?></label>
                        <input class="form-control" type="email" name="email" value="<?= e($old['email']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= e(__('auth.register.password')) ?></label>
                        <input class="form-control js-toggle-password" type="password" name="password" autocomplete="new-password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= e(__('auth.register.password_confirmation')) ?></label>
                        <input class="form-control js-toggle-password" type="password" name="password_confirmation" autocomplete="new-password" required>
                        <div class="form-check mt-2">
                            <input class="form-check-input" id="register-show-password" type="checkbox" data-password-toggle data-password-target=".js-toggle-password">
                            <label class="form-check-label" for="register-show-password"><?= e(__('auth.password.show')) ?></label>
                        </div>
                    </div>
                    <button class="btn btn-primary"><?= e(__('auth.register.submit')) ?></button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include THEMES . setting('current_theme', CURRENT_THEME) . '/footer.php'; ?>
