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

    $message = auth_error() ?? 'Neteisingi duomenys.';
    $messageType = 'danger';
}

include THEMES . setting('current_theme', CURRENT_THEME) . '/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h1 class="h4 mb-0">Prisijungimas</h1>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= public_path('administration/login.php') ?>">Admin</a>
                </div>
                <?php if ($message): ?><div class="alert alert-<?= e($messageType) ?>"><?= e($message) ?></div><?php endif; ?>
                <form method="post">
                    <?= csrf_field() ?>
                    <div class="mb-3"><label class="form-label">El. paštas</label><input class="form-control" type="email" name="email" autocomplete="email"></div>
                    <div class="mb-3"><label class="form-label">Slaptažodis</label><input class="form-control" type="password" name="password" autocomplete="current-password"></div>
                    <button class="btn btn-primary">Prisijungti</button>
                </form>
                <div class="mt-3 text-end">
                    <a class="small" href="<?= public_path('forgot-password.php') ?>">Pamiršote slaptažodį?</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include THEMES . setting('current_theme', CURRENT_THEME) . '/footer.php'; ?>
