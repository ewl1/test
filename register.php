<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (current_user()) {
    redirect(public_path('index.php'));
}

$errors = [];
$old = [
    'username' => '',
    'email' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $old['username'] = trim((string)($_POST['username'] ?? ''));
    $old['email'] = mb_strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    $passwordConfirm = (string)($_POST['password_confirmation'] ?? '');

    if ($password !== $passwordConfirm) {
        $errors[] = 'Slaptažodžiai nesutampa.';
    } else {
        try {
            $errors = register_user($old['username'], $old['email'], $password);
            if (!$errors) {
                flash('success', 'Sėkmingai užsiregistravote. Dabar galite prisijungti.');
                redirect(public_path('login.php'));
            }
        } catch (Throwable $e) {
            $errors[] = 'Registracijos išsaugoti nepavyko.';
        }
    }
}

include THEMES . setting('current_theme', CURRENT_THEME) . '/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <h1 class="h4 mb-3">Registracija</h1>
                <?php if ($errors): ?>
                    <div class="alert alert-danger"><?= e(implode(' ', $errors)) ?></div>
                <?php endif; ?>
                <form method="post">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Vartotojo vardas</label>
                        <input class="form-control" name="username" value="<?= e($old['username']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">El. paštas</label>
                        <input class="form-control" type="email" name="email" value="<?= e($old['email']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Slaptažodis</label>
                        <input class="form-control" type="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Pakartokite slaptažodį</label>
                        <input class="form-control" type="password" name="password_confirmation" required>
                    </div>
                    <button class="btn btn-primary">Sukurti paskyrą</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include THEMES . setting('current_theme', CURRENT_THEME) . '/footer.php'; ?>
