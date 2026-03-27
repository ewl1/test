<?php
require_once __DIR__ . '/includes/bootstrap.php';
$token = $_GET['token'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    [$ok, $message] = reset_password_by_token($pdo, $token, $_POST['password'] ?? '');
    flash($ok ? 'success' : 'error', $message);
    if ($ok) {
        redirect('login.php');
    }
}
include __DIR__ . '/themes/default/header.php';
?>
<h1>Naujas slaptažodis</h1>
<form method="post" class="card card-body">
    <?= csrf_input() ?>
    <div class="mb-3"><label class="form-label">Naujas slaptažodis</label><input class="form-control" type="password" name="password" required></div>
    <button class="btn btn-primary">Išsaugoti</button>
</form>
<?php include __DIR__ . '/themes/default/footer.php'; ?>
