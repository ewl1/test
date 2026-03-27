<?php
define('IN_ADMIN', true);
require_once dirname(__DIR__) . '/includes/bootstrap.php';

$user = current_user();
if ($user) {
    if (has_permission($GLOBALS['pdo'], $user['id'], 'admin.access')) {
        redirect(public_path('administration/index.php'));
    }
    redirect(public_path('index.php'));
}

$error = auth_error();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (login(trim($_POST['email'] ?? ''), (string)($_POST['password'] ?? ''))) {
        $freshUser = current_user() ?: sync_session_user();
        if ($freshUser && has_permission($GLOBALS['pdo'], $freshUser['id'], 'admin.access')) {
            redirect(public_path('administration/index.php'));
        }

        flash('error', 'Ši paskyra neturi administratoriaus teisių.');
        $_SESSION = [];
        session_destroy();
        redirect(public_path('login.php'));
    }

    $error = auth_error() ?? 'Prisijungti nepavyko.';
}

include THEMES . 'default/admin_header.php';
?>
<div class="row justify-content-center"><div class="col-lg-5"><div class="card"><div class="card-body">
<h1 class="h4 mb-3">Admin prisijungimas</h1>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<form method="post"><?= csrf_field() ?>
<div class="mb-3"><label class="form-label">El. paštas</label><input class="form-control" type="email" name="email"></div>
<div class="mb-3"><label class="form-label">Slaptažodis</label><input class="form-control" type="password" name="password"></div>
<button class="btn btn-primary">Prisijungti</button></form></div></div></div></div>
<?php include THEMES . 'default/admin_footer.php'; ?>
