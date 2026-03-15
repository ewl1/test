<?php
require_once __DIR__ . '/_guard.php';
require_permission($pdo, 'users.manage');
$id = (int)($_GET['id'] ?? 0);
$user = get_user($pdo, $id);
$roles = $pdo->query("SELECT * FROM roles ORDER BY level DESC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    [$ok, $message] = update_user_profile($pdo, $id, $_POST);
    if ($ok) {
        $up = $pdo->prepare("UPDATE users SET role_id = :role_id WHERE id = :id");
        $up->execute([':role_id' => (int)($_POST['role_id'] ?? 4), ':id' => $id]);
    }
    flash($ok ? 'success' : 'error', $message);
    redirect('users.php');
}
include dirname(__DIR__) . '/theme/header.php';
?>
<h1>Redaguoti narį</h1>
<form method="post" class="card card-body">
    <?= csrf_input() ?>
    <div class="mb-3"><label class="form-label">Vartotojo vardas</label><input class="form-control" name="username" value="<?= e($user['username'] ?? '') ?>"></div>
    <div class="mb-3"><label class="form-label">El. paštas</label><input class="form-control" name="email" value="<?= e($user['email'] ?? '') ?>"></div>
    <div class="mb-3">
        <label class="form-label">Rolė</label>
        <select class="form-select" name="role_id">
            <?php foreach ($roles as $role): ?>
                <option value="<?= (int)$role['id'] ?>" <?= ((int)($user['role_id'] ?? 0) === (int)$role['id']) ? 'selected' : '' ?>><?= e($role['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button class="btn btn-primary">Išsaugoti</button>
</form>
<?php include dirname(__DIR__) . '/theme/footer.php'; ?>
