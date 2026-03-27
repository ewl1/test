<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login_page();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    [$ok, $message] = change_password($pdo, $_SESSION['user']['id'], $_POST['current_password'] ?? '', $_POST['new_password'] ?? '');
    flash($ok ? 'success' : 'error', $message);
    if ($ok) {
        redirect('profile.php');
    }
}
include __DIR__ . '/themes/default/header.php';
?>
<h1>Keisti slaptažodį</h1>
<form method="post" class="card card-body">
    <?= csrf_input() ?>
    <div class="mb-3"><label class="form-label">Dabartinis slaptažodis</label><input class="form-control" type="password" name="current_password" required></div>
    <div class="mb-3"><label class="form-label">Naujas slaptažodis</label><input class="form-control" type="password" name="new_password" required></div>
    <button class="btn btn-primary">Pakeisti</button>
</form>
<?php include __DIR__ . '/themes/default/footer.php'; ?>
