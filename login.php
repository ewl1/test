<?php
require_once __DIR__ . '/include/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    [$ok, $message] = login_user($pdo, trim($_POST['email'] ?? ''), $_POST['password'] ?? '');
    if ($ok) {
        flash('success', $message);
        redirect('index.php');
    }
    flash('error', $message);
}

include __DIR__ . '/theme/header.php';
?>
<h1>Prisijungimas</h1>
<form method="post" class="card card-body">
    <?= csrf_input() ?>
    <div class="mb-3">
        <label class="form-label">El. paštas</label>
        <input type="email" name="email" class="form-control" value="<?= e(old('email')) ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Slaptažodis</label>
        <input type="password" name="password" class="form-control" required>
    </div>
    <button class="btn btn-primary">Prisijungti</button>
    <a href="forgot-password.php" class="btn btn-link">Pamiršau slaptažodį</a>
</form>
<?php include __DIR__ . '/theme/footer.php'; ?>
