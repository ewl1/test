<?php
require_once __DIR__ . '/includes/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    [$ok, $message] = create_password_reset($pdo, trim($_POST['email'] ?? ''));
    flash($ok ? 'success' : 'error', $message);
    redirect('login.php');
}
include __DIR__ . '/themes/default/header.php';
?>
<h1>Slaptažodžio atstatymas</h1>
<form method="post" class="card card-body">
    <?= csrf_input() ?>
    <div class="mb-3"><label class="form-label">El. paštas</label><input class="form-control" type="email" name="email" required></div>
    <button class="btn btn-primary">Siųsti nuorodą</button>
</form>
<?php include __DIR__ . '/themes/default/footer.php'; ?>
