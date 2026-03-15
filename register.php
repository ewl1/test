<?php
require_once __DIR__ . '/include/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    [$ok, $message] = register_user($pdo, $_POST);
    flash($ok ? 'success' : 'error', $message);
    if ($ok) {
        redirect('login.php');
    }
}
include __DIR__ . '/theme/header.php';
?>
<h1>Registracija</h1>
<form method="post" class="card card-body">
    <?= csrf_input() ?>
    <div class="mb-3"><label class="form-label">Vartotojo vardas</label><input class="form-control" name="username" required></div>
    <div class="mb-3"><label class="form-label">El. paštas</label><input class="form-control" type="email" name="email" required></div>
    <div class="mb-3"><label class="form-label">Slaptažodis</label><input class="form-control" type="password" name="password" required></div>
    <button class="btn btn-primary">Registruotis</button>
</form>
<?php include __DIR__ . '/theme/footer.php'; ?>
