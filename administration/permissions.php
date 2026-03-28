<?php
require_once __DIR__ . '/_guard.php';
require_permission('permissions.manage');

$roles = $GLOBALS['pdo']->query('SELECT * FROM roles ORDER BY level DESC, id ASC')->fetchAll();
$permissions = $GLOBALS['pdo']->query('SELECT * FROM permissions ORDER BY slug ASC')->fetchAll();
$currentRoleId = (int)($_GET['role_id'] ?? ($roles[0]['id'] ?? 1));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $roleId = (int)($_POST['role_id'] ?? 0);
    if ($roleId <= 0) {
        flash('error', 'Neteisinga rolė.');
        redirect('permissions.php');
    }

    $GLOBALS['pdo']->prepare('DELETE FROM role_permissions WHERE role_id = :id')->execute([':id' => $roleId]);
    foreach ($_POST['permissions'] ?? [] as $permissionId) {
        $stmt = $GLOBALS['pdo']->prepare('INSERT INTO role_permissions (role_id, permission_id) VALUES (:r, :p)');
        $stmt->execute([':r' => $roleId, ':p' => (int)$permissionId]);
    }
    audit_log(current_user()['id'], 'role_permissions_update', 'roles', $roleId);
    flash('success', 'Leidimai išsaugoti.');
    redirect('permissions.php?role_id=' . $roleId);
}

$stmt = $GLOBALS['pdo']->prepare('SELECT permission_id FROM role_permissions WHERE role_id = :id');
$stmt->execute([':id' => $currentRoleId]);
$current = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
$currentMap = array_flip($current);

include THEMES . 'default/admin_header.php';
?>
<h1 class="h3 mb-3">Leidimų matrica</h1>
<?php if ($msg = flash('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
<?php if ($msg = flash('error')): ?><div class="alert alert-danger"><?= e($msg) ?></div><?php endif; ?>

<div class="row g-4">
  <div class="col-lg-3">
    <div class="list-group">
      <?php foreach ($roles as $role): ?>
        <a class="list-group-item list-group-item-action <?= $currentRoleId === (int)$role['id'] ? 'active' : '' ?>" href="permissions.php?role_id=<?= (int)$role['id'] ?>">
          <div class="fw-semibold"><?= e($role['name']) ?></div>
          <div class="small admin-role-meta"><code class="admin-mono-pill admin-folder-label"><?= e($role['slug']) ?></code> · lygis <?= (int)$role['level'] ?></div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="col-lg-9">
    <div class="card">
      <div class="card-header">Pasirinkite teises rolei</div>
      <div class="card-body">
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="role_id" value="<?= $currentRoleId ?>">
          <div class="row g-3">
            <?php foreach ($permissions as $permission): ?>
              <div class="col-md-6">
                <label class="form-check admin-permission-card p-3 d-block h-100">
                  <input class="form-check-input me-2" type="checkbox" name="permissions[]" value="<?= (int)$permission['id'] ?>" <?= isset($currentMap[(int)$permission['id']]) ? 'checked' : '' ?>>
                  <span class="fw-semibold"><?= e($permission['name']) ?></span><br>
                  <small class="text-secondary"><code class="admin-mono-pill admin-folder-label"><?= e($permission['slug']) ?></code></small><br>
                  <small class="text-secondary"><?= e($permission['description'] ?? '') ?></small>
                </label>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="mt-3"><button class="btn btn-primary">Išsaugoti</button></div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php include THEMES . 'default/admin_footer.php'; ?>
