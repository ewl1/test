<?php
require_once __DIR__ . '/_guard.php';
require_any_permission(['users.manage', 'users.view']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $userId = (int)($_POST['id'] ?? 0);

    if ($action === 'create') {
        require_permission('users.create');
        $payload = [
            'username' => $_POST['username'] ?? '',
            'email' => $_POST['email'] ?? '',
            'password' => $_POST['password'] ?? '',
            'role_id' => (int)($_POST['role_id'] ?? 4),
            'status' => $_POST['status'] ?? 'inactive',
        ];
        $errors = validate_user_payload($payload, 'create');
        if (!can_manage_role_id($payload['role_id'])) {
            $errors[] = 'Negalite priskirti aukštesnės rolės už savo.';
        }
        if ($errors) {
            flash('error', implode(' ', $errors));
            redirect('users.php');
        }

        $stmt = $GLOBALS['pdo']->prepare("
            INSERT INTO users (username, email, password, role_id, is_active, status, created_at)
            VALUES (:u,:e,:p,:r,:a,:s,NOW())
        ");
        $stmt->execute([
            ':u' => trim((string)$payload['username']),
            ':e' => normalize_email($payload['email']),
            ':p' => password_hash((string)$payload['password'], PASSWORD_DEFAULT),
            ':r' => (int)$payload['role_id'],
            ':a' => $payload['status'] === 'active' ? 1 : 0,
            ':s' => $payload['status'],
        ]);
        audit_log(current_user()['id'], 'user_create', 'users', $GLOBALS['pdo']->lastInsertId());
        flash('success', 'Vartotojas sukurtas.');
        redirect('users.php');
    }

    if ($action === 'update') {
        require_permission('users.edit');
        $currentStmt = $GLOBALS['pdo']->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $currentStmt->execute([':id' => $userId]);
        $existing = $currentStmt->fetch();
        if (!$existing) {
            flash('error', 'Vartotojas nerastas.');
            redirect('users.php');
        }

        $payload = [
            'username' => $_POST['username'] ?? '',
            'email' => $_POST['email'] ?? '',
            'password' => $_POST['password'] ?? '',
            'role_id' => (int)($_POST['role_id'] ?? 4),
            'status' => $_POST['status'] ?? 'inactive',
        ];
        $errors = validate_user_payload($payload, 'update', $userId);
        if (!can_manage_role_id($payload['role_id']) || !can_manage_role_id((int)$existing['role_id'])) {
            $errors[] = 'Negalite valdyti šio vartotojo rolės.';
        }
        if ($errors) {
            flash('error', implode(' ', $errors));
            redirect('users.php');
        }

        $sql = "UPDATE users SET username=:u, email=:e, role_id=:r, is_active=:a, status=:s";
        $params = [
            ':u' => trim((string)$payload['username']),
            ':e' => normalize_email($payload['email']),
            ':r' => (int)$payload['role_id'],
            ':a' => (int)($_POST['is_active'] ?? 0),
            ':s' => $payload['status'],
            ':id' => $userId,
        ];
        if (trim((string)$payload['password']) !== '') {
            $sql .= ", password=:p";
            $params[':p'] = password_hash((string)$payload['password'], PASSWORD_DEFAULT);
        }
        $sql .= " WHERE id=:id";
        $stmt = $GLOBALS['pdo']->prepare($sql);
        $stmt->execute($params);

        audit_log(current_user()['id'], 'user_update', 'users', $userId);
        flash('success', 'Vartotojas atnaujintas.');
        redirect('users.php');
    }

    if ($action === 'delete') {
        require_permission('users.delete');
        if ($userId === (int)current_user()['id']) {
            flash('error', 'Negalite ištrinti savęs.');
            redirect('users.php');
        }

        $roleStmt = $GLOBALS['pdo']->prepare("SELECT role_id FROM users WHERE id = :id");
        $roleStmt->execute([':id' => $userId]);
        $targetRoleId = (int)$roleStmt->fetchColumn();
        if (!can_manage_role_id($targetRoleId)) {
            flash('error', 'Negalite ištrinti šio vartotojo.');
            redirect('users.php');
        }

        $GLOBALS['pdo']->prepare("DELETE FROM users WHERE id = :id")->execute([':id' => $userId]);
        audit_log(current_user()['id'], 'user_delete', 'users', $userId);
        flash('success', 'Vartotojas ištrintas.');
        redirect('users.php');
    }

    if (in_array($action, ['activate', 'deactivate', 'block'], true)) {
        require_permission('users.status');
        $roleStmt = $GLOBALS['pdo']->prepare("SELECT role_id FROM users WHERE id = :id");
        $roleStmt->execute([':id' => $userId]);
        $targetRoleId = (int)$roleStmt->fetchColumn();
        if (!can_manage_role_id($targetRoleId)) {
            flash('error', 'Negalite keisti šio vartotojo būsenos.');
            redirect('users.php');
        }

        $status = $action === 'activate' ? 'active' : ($action === 'deactivate' ? 'inactive' : 'blocked');
        $active = $action === 'activate' ? 1 : 0;
        $stmt = $GLOBALS['pdo']->prepare("UPDATE users SET status=:s, is_active=:a WHERE id=:id");
        $stmt->execute([':s' => $status, ':a' => $active, ':id' => $userId]);
        audit_log(current_user()['id'], 'user_' . $action, 'users', $userId);
        flash('success', 'Vartotojo būsena pakeista.');
        redirect('users.php');
    }
}

$roles = $GLOBALS['pdo']->query("SELECT * FROM roles ORDER BY level DESC")->fetchAll();
$users = $GLOBALS['pdo']->query("SELECT u.*, r.name AS role_name FROM users u LEFT JOIN roles r ON r.id = u.role_id ORDER BY u.id DESC")->fetchAll();
$statusLabels = [
    'active' => 'Aktyvus',
    'inactive' => 'Neaktyvus',
    'blocked' => 'Blokuotas',
    'deleted' => 'Ištrintas',
];

include THEMES . 'default/admin_header.php';
?>
<h1 class="h3 mb-3">Narių valdymas</h1>
<?php if ($msg = flash('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
<?php if ($msg = flash('error')): ?><div class="alert alert-danger"><?= e($msg) ?></div><?php endif; ?>

<div class="card mb-4">
  <div class="card-header">Naujas vartotojas</div>
  <div class="card-body">
    <form method="post" class="row g-3">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="create">
      <div class="col-md-3"><label class="form-label">Vardas</label><input class="form-control" name="username"></div>
      <div class="col-md-3"><label class="form-label">El. paštas</label><input class="form-control" type="email" name="email"></div>
      <div class="col-md-2"><label class="form-label">Slaptažodis</label><input class="form-control" type="password" name="password"></div>
      <div class="col-md-2"><label class="form-label">Rolė</label><select class="form-select" name="role_id"><?php foreach ($roles as $role): ?><option value="<?= (int)$role['id'] ?>"><?= e($role['name']) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-2"><label class="form-label">Būsena</label><select class="form-select" name="status"><?php foreach (['active', 'inactive', 'blocked'] as $status): ?><option value="<?= $status ?>"><?= e($statusLabels[$status] ?? $status) ?></option><?php endforeach; ?></select></div>
      <div class="col-12"><button class="btn btn-primary">Sukurti</button></div>
    </form>
  </div>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead><tr><th>ID</th><th>Vartotojas</th><th>El. paštas</th><th>Rolė</th><th>Statusas</th><th>Aktyvus</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($users as $user): ?>
        <tr>
          <td class="admin-strong-cell"><?= (int)$user['id'] ?></td>
          <td><span class="fw-semibold admin-strong-cell"><?= e($user['username']) ?></span></td>
          <td><span class="admin-table-note"><?= e($user['email']) ?></span></td>
          <td><span class="fw-semibold admin-strong-cell"><?= e($user['role_name'] ?? '-') ?></span></td>
          <td><span class="badge text-bg-secondary"><?= e($statusLabels[$user['status']] ?? $user['status']) ?></span></td>
          <td><span class="admin-table-note"><?= (int)$user['is_active'] ? 'Taip' : 'Ne' ?></span></td>
          <td><button class="btn btn-sm btn-outline-primary admin-action-button" data-bs-toggle="collapse" data-bs-target="#user-<?= (int)$user['id'] ?>">Valdyti</button></td>
        </tr>
        <tr class="collapse" id="user-<?= (int)$user['id'] ?>">
          <td colspan="7">
            <form method="post" class="row g-2 align-items-end mb-2">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="update">
              <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
              <div class="col-md-2"><label class="form-label">Vardas</label><input class="form-control" name="username" value="<?= e($user['username']) ?>"></div>
              <div class="col-md-3"><label class="form-label">El. paštas</label><input class="form-control" name="email" value="<?= e($user['email']) ?>"></div>
              <div class="col-md-2"><label class="form-label">Naujas slaptažodis</label><input class="form-control" type="password" name="password"></div>
              <div class="col-md-2"><label class="form-label">Rolė</label>
                <select class="form-select" name="role_id">
                  <?php foreach ($roles as $role): ?>
                    <option value="<?= (int)$role['id'] ?>" <?= (int)$user['role_id'] === (int)$role['id'] ? 'selected' : '' ?>><?= e($role['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-1"><label class="form-label">Statusas</label>
                <select class="form-select" name="status">
                  <?php foreach (['active', 'inactive', 'blocked', 'deleted'] as $status): ?>
                    <option value="<?= $status ?>" <?= $user['status'] === $status ? 'selected' : '' ?>><?= e($statusLabels[$status] ?? $status) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-1"><label class="form-label">Aktyvus</label>
                <select class="form-select" name="is_active">
                  <option value="1" <?= (int)$user['is_active'] === 1 ? 'selected' : '' ?>>1</option>
                  <option value="0" <?= (int)$user['is_active'] === 0 ? 'selected' : '' ?>>0</option>
                </select>
              </div>
              <div class="col-md-1 d-flex gap-2"><button class="btn btn-primary">Išsaugoti</button></div>
            </form>

            <form method="post" class="d-flex flex-wrap gap-2">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
              <button class="btn btn-sm btn-outline-success" name="action" value="activate">Aktyvuoti</button>
              <button class="btn btn-sm btn-outline-warning" name="action" value="deactivate">Deaktyvuoti</button>
              <button class="btn btn-sm btn-outline-dark" name="action" value="block">Blokuoti</button>
              <?php if ((int)$user['id'] !== (int)current_user()['id']): ?>
              <button class="btn btn-sm btn-outline-danger" name="action" value="delete" data-confirm-message="Tikrai ištrinti vartotoją?">Ištrinti</button>
              <?php endif; ?>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include THEMES . 'default/admin_footer.php'; ?>
