<?php
require_once dirname(__DIR__) . '/include/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    [$ok, $message] = login_user($pdo, trim($_POST['email'] ?? ''), $_POST['password'] ?? '');
    if ($ok && has_permission($pdo, 'admin.access')) {
        redirect('index.php');
    }
    flash('error', $message ?: 'Prisijungti nepavyko.');
}
include dirname(__DIR__) . '/theme/header.php';
?>
<h1>Admin prisijungimas</h1>
<form method="post" class="card card-body">
    <?= csrf_input() ?>
    <div class="mb-3"><label class="form-label">El. paštas</label><input class="form-control" type="email" name="email"></div>
    <div class="mb-3"><label class="form-label">Slaptažodis</label><input class="form-control" type="password" name="password"></div>
    <button class="btn btn-primary">Prisijungti</button>
</form>
<?php include dirname(__DIR__) . '/theme/footer.php'; ?>
