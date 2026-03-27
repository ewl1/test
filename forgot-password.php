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
                <h1 class="h4 mb-3">Slaptažodžio atstatymas</h1>
                <p class="text-secondary">Įveskite savo paskyros el. paštą. Jei paskyra egzistuoja ir yra aktyvi, atsiųsime atstatymo nuorodą.</p>
                <?php if ($msg = flash('error')): ?>
                    <div class="alert alert-danger"><?= e($msg) ?></div>
                <?php endif; ?>
                <form method="post">
                    <?= csrf_input() ?>
                    <div class="mb-3">
                        <label class="form-label">El. paštas</label>
                        <input class="form-control" type="email" name="email" autocomplete="email" required>
                    </div>
                    <button class="btn btn-primary">Siųsti nuorodą</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include THEMES . setting('current_theme', CURRENT_THEME) . '/footer.php'; ?>
