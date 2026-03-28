<?php
require_once __DIR__ . '/_guard.php';
require_permission('roles.manage');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $name = trim((string)($_POST['name'] ?? ''));
    $slug = normalize_slug($_POST['slug'] ?? '');
    $level = (int)($_POST['level'] ?? 0);

    if ($action === 'create') {
        $errors = validate_role_payload($name, $slug, $level);
        if (role_slug_exists($slug)) {
            $errors[] = 'Toks rolės slug jau egzistuoja.';
        }
        if ($errors) {
            flash('error', implode(' ', $errors));
            redirect('roles.php');
        }

        $stmt = $GLOBALS['pdo']->prepare("INSERT INTO roles (name, slug, level) VALUES (:name, :slug, :level)");
        $stmt->execute([
            ':name' => $name,
            ':slug' => $slug,
            ':level' => $level,
        ]);
        audit_log(current_user()['id'], 'role_create', 'roles', $GLOBALS['pdo']->lastInsertId());
        flash('success', 'Rolė sukurta.');
        redirect('roles.php');
    }

    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $errors = validate_role_payload($name, $slug, $level);
        if (role_slug_exists($slug, $id)) {
            $errors[] = 'Toks rolės slug jau egzistuoja.';
        }
        if ($id <= 0) {
            $errors[] = 'Neteisingas rolės ID.';
        }
        if ($errors) {
            flash('error', implode(' ', $errors));
            redirect('roles.php');
        }

        $stmt = $GLOBALS['pdo']->prepare("UPDATE roles SET name=:name, slug=:slug, level=:level WHERE id=:id");
        $stmt->execute([
            ':id' => $id,
            ':name' => $name,
            ':slug' => $slug,
            ':level' => $level,
        ]);
        audit_log(current_user()['id'], 'role_update', 'roles', $id);
        flash('success', 'Rolė atnaujinta.');
        redirect('roles.php');
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 5) {
            flash('error', 'Sisteminių rolių trynimas užblokuotas.');
            redirect('roles.php');
        }

        $check = $GLOBALS['pdo']->prepare("SELECT COUNT(*) FROM users WHERE role_id = :id");
        $check->execute([':id' => $id]);
        if ((int)$check->fetchColumn() > 0) {
            flash('error', 'Rolė naudojama vartotojų.');
            redirect('roles.php');
        }

        $GLOBALS['pdo']->prepare("DELETE FROM role_permissions WHERE role_id = :id")->execute([':id' => $id]);
        $GLOBALS['pdo']->prepare("DELETE FROM roles WHERE id = :id")->execute([':id' => $id]);
        audit_log(current_user()['id'], 'role_delete', 'roles', $id);
        flash('success', 'Rolė ištrinta.');
        redirect('roles.php');
    }
}

$roles = $GLOBALS['pdo']->query("SELECT r.*, (SELECT COUNT(*) FROM users u WHERE u.role_id = r.id) AS users_count FROM roles r ORDER BY level DESC, id ASC")->fetchAll();
include THEMES . 'default/admin_header.php';
?>
<h1 class="h3 mb-3">Rolės</h1>
<?php if ($msg = flash('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
<?php if ($msg = flash('error')): ?><div class="alert alert-danger"><?= e($msg) ?></div><?php endif; ?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">Nauja rolė</div>
            <div class="card-body">
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3"><label class="form-label">Pavadinimas</label><input class="form-control" name="name" required></div>
                    <div class="mb-3"><label class="form-label">Slug</label><input class="form-control" name="slug" required></div>
                    <div class="mb-3"><label class="form-label">Lygis</label><input class="form-control" type="number" name="level" value="10" required></div>
                    <button class="btn btn-primary">Sukurti</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">Esamos rolės</div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>ID</th><th>Pavadinimas</th><th>Slug</th><th>Lygis</th><th>Nariai</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($roles as $role): ?>
                        <tr>
                            <td class="admin-strong-cell"><?= (int)$role['id'] ?></td>
                            <td><span class="fw-semibold admin-strong-cell"><?= e($role['name']) ?></span></td>
                            <td><code class="admin-mono-pill admin-folder-label"><?= e($role['slug']) ?></code></td>
                            <td><span class="admin-table-note"><?= (int)$role['level'] ?></span></td>
                            <td><span class="admin-table-note"><?= (int)$role['users_count'] ?></span></td>
                            <td><button class="btn btn-sm btn-outline-primary admin-action-button" data-bs-toggle="collapse" data-bs-target="#role-<?= (int)$role['id'] ?>">Redaguoti</button></td>
                        </tr>
                        <tr class="collapse" id="role-<?= (int)$role['id'] ?>">
                            <td colspan="6">
                                <form method="post" class="row g-2 align-items-end">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="id" value="<?= (int)$role['id'] ?>">
                                    <div class="col-md-3"><label class="form-label">Pavadinimas</label><input class="form-control" name="name" value="<?= e($role['name']) ?>"></div>
                                    <div class="col-md-3"><label class="form-label">Slug</label><input class="form-control" name="slug" value="<?= e($role['slug']) ?>"></div>
                                    <div class="col-md-2"><label class="form-label">Lygis</label><input class="form-control" type="number" name="level" value="<?= (int)$role['level'] ?>"></div>
                                    <div class="col-md-4 d-flex gap-2">
                                        <button class="btn btn-primary">Išsaugoti</button>
                                        <a class="btn btn-outline-primary admin-action-button" href="permissions.php?role_id=<?= (int)$role['id'] ?>">Leidimai</a>
                                        <?php if ((int)$role['id'] > 5): ?>
                                            <button class="btn btn-outline-danger" type="submit" name="action" value="delete" data-confirm-message="Tikrai trinti rolę?">Trinti</button>
                                        <?php endif; ?>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include THEMES . 'default/admin_footer.php'; ?>
