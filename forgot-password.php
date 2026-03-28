<?php
require_once __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    [$ok, $responseMessage] = create_password_reset($pdo, trim((string)($_POST['email'] ?? '')));
    flash($ok ? 'success' : 'error', $responseMessage);
    redirect(public_path($ok ? 'login.php' : 'forgot-password.php'));
}

include THEMES . setting('current_theme', CURRENT_THEME) . '/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <h1 class="h4 mb-3"><?= e(__('password_reset.request.title')) ?></h1>
                <p class="text-secondary"><?= e(__('password_reset.request.help')) ?></p>
                <?php if ($msg = flash('error')): ?>
                    <div class="alert alert-danger"><?= e($msg) ?></div>
                <?php endif; ?>
                <form method="post">
                    <?= csrf_input() ?>
                    <div class="mb-3">
                        <label class="form-label"><?= e(__('auth.login.email')) ?></label>
                        <input class="form-control" type="email" name="email" autocomplete="email" required>
                    </div>
                    <button class="btn btn-primary"><?= e(__('password_reset.request.submit')) ?></button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include THEMES . setting('current_theme', CURRENT_THEME) . '/footer.php'; ?>
