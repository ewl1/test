<?php
require_once __DIR__ . '/includes/bootstrap.php';

$token = trim((string)($_GET['token'] ?? ''));
$tokenState = password_reset_token_state($pdo, $token);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    [$ok, $message] = reset_password_by_token($pdo, $token, (string)($_POST['password'] ?? ''));
    flash($ok ? 'success' : 'error', $message);
    if ($ok) {
        redirect(public_path('login.php'));
    }

    $tokenState = password_reset_token_state($pdo, $token);
}

include THEMES . setting('current_theme', CURRENT_THEME) . '/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <h1 class="h4 mb-3"><?= e(__('password_reset.reset.title')) ?></h1>
                <?php if ($msg = flash('error')): ?>
                    <div class="alert alert-danger"><?= e($msg) ?></div>
                <?php endif; ?>

                <?php if (!$tokenState['valid']): ?>
                    <div class="alert alert-warning mb-0">
                        <?= e($tokenState['message']) ?>
                        <div class="mt-3">
                            <a class="btn btn-outline-primary" href="<?= public_path('forgot-password.php') ?>"><?= e(__('password_reset.reset.request_new')) ?></a>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="text-secondary"><?= e(__('password_reset.reset.help')) ?></p>
                    <form method="post">
                        <?= csrf_input() ?>
                        <div class="mb-3">
                            <label class="form-label"><?= e(__('profile.password.new')) ?></label>
                            <input class="form-control js-toggle-password" type="password" name="password" autocomplete="new-password" required>
                            <div class="form-check mt-2">
                                <input class="form-check-input" id="reset-show-password" type="checkbox" data-password-toggle data-password-target=".js-toggle-password">
                                <label class="form-check-label" for="reset-show-password"><?= e(__('auth.password.show')) ?></label>
                            </div>
                        </div>
                        <button class="btn btn-primary"><?= e(__('password_reset.reset.submit')) ?></button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php include THEMES . setting('current_theme', CURRENT_THEME) . '/footer.php'; ?>
