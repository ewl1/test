<?php
require_once __DIR__ . '/_guard.php';
require_permission($pdo, 'permissions.manage');

$roles = $pdo->query("SELECT * FROM roles ORDER BY level DESC")->fetchAll();
$permissions = $pdo->query("SELECT * FROM permissions ORDER BY slug ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $roleId = (int)($_POST['role_id'] ?? 0);
    $pdo->prepare("DELETE FROM role_permissions WHERE role_id = :role_id")->execute([':role_id' => $roleId]);

    foreach ($_POST['permissions'] ?? [] as $permissionId) {
        $stmt = $pdo->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)");
        $stmt->execute([':role_id' => $roleId, ':permission_id' => (int)$permissionId]);
    }

    audit_log($pdo, $_SESSION['user']['id'], 'permissions_update', 'roles', $roleId);
    flash('success', 'Leidimai išsaugoti.');
    redirect('permissions.php?role_id=' . $roleId);
}

$currentRoleId = (int)($_GET['role_id'] ?? ($roles[0]['id'] ?? 0));
$currentPermissions = $pdo->prepare("SELECT permission_id FROM role_permissions WHERE role_id = :role_id");
$currentPermissions->execute([':role_id' => $currentRoleId]);
$map = array_flip(array_map('intval', $currentPermissions->fetchAll(PDO::FETCH_COLUMN)));

include dirname(__DIR__) . '/theme/header.php';
?>
<h1>Leidimai</h1>
<form method="get" class="mb-3">
    <label class="form-label">Rolė</label>
    <select class="form-select" name="role_id" onchange="this.form.submit()">
        <?php foreach ($roles as $role): ?>
            <option value="<?= (int)$role['id'] ?>" <?= $currentRoleId === (int)$role['id'] ? 'selected' : '' ?>><?= e($role['name']) ?></option>
        <?php endforeach; ?>
    </select>
</form>

<form method="post" class="card card-body">
    <?= csrf_input() ?>
    <input type="hidden" name="role_id" value="<?= $currentRoleId ?>">
    <?php foreach ($permissions as $perm): ?>
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="permissions[]" value="<?= (int)$perm['id'] ?>" id="p<?= (int)$perm['id'] ?>" <?= isset($map[(int)$perm['id']]) ? 'checked' : '' ?>>
            <label class="form-check-label" for="p<?= (int)$perm['id'] ?>">
                <strong><?= e($perm['slug']) ?></strong>
                <span class="text-muted"><?= e($perm['description']) ?></span>
            </label>
        </div>
    <?php endforeach; ?>
    <button class="btn btn-primary mt-3">Išsaugoti</button>
</form>
<?php include dirname(__DIR__) . '/theme/footer.php'; ?>
