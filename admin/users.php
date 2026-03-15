<?php
require_once __DIR__ . '/_guard.php';
require_permission($pdo, 'users.manage');
$users = get_all_users($pdo);
include dirname(__DIR__) . '/theme/header.php';
?>
<h1>Nariai</h1>
<table class="table table-striped">
    <tr><th>ID</th><th>Vardas</th><th>Email</th><th>Rolė</th><th>Statusas</th><th></th></tr>
    <?php foreach ($users as $user): ?>
    <tr>
        <td><?= (int)$user['id'] ?></td>
        <td><?= e($user['username']) ?></td>
        <td><?= e($user['email']) ?></td>
        <td><?= e($user['role_name'] ?? '—') ?></td>
        <td><?= e($user['status']) ?></td>
        <td>
            <a class="btn btn-sm btn-outline-primary" href="user-edit.php?id=<?= (int)$user['id'] ?>">Redaguoti</a>
            <a class="btn btn-sm btn-outline-secondary" href="user-status.php?id=<?= (int)$user['id'] ?>&status=active">Aktyvuoti</a>
            <a class="btn btn-sm btn-outline-warning" href="user-status.php?id=<?= (int)$user['id'] ?>&status=inactive">Deaktyvuoti</a>
            <a class="btn btn-sm btn-outline-danger" href="user-status.php?id=<?= (int)$user['id'] ?>&status=blocked">Blokuoti</a>
            <a class="btn btn-sm btn-danger confirm-delete" href="user-delete.php?id=<?= (int)$user['id'] ?>">Trinti</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php include dirname(__DIR__) . '/theme/footer.php'; ?>
