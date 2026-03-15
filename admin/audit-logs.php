<?php
require_once __DIR__ . '/_guard.php';
require_permission($pdo, 'audit.view');

$where = [];
$params = [];

if (!empty($_GET['user_id'])) {
    $where[] = 'al.user_id = :user_id';
    $params[':user_id'] = (int)$_GET['user_id'];
}
if (!empty($_GET['action'])) {
    $where[] = 'al.action = :action';
    $params[':action'] = trim($_GET['action']);
}
if (!empty($_GET['entity_type'])) {
    $where[] = 'al.entity_type = :entity_type';
    $params[':entity_type'] = trim($_GET['entity_type']);
}
if (!empty($_GET['ip'])) {
    $where[] = 'INET6_NTOA(al.ip_address) = :ip';
    $params[':ip'] = trim($_GET['ip']);
}

$sql = "SELECT al.*, u.username, INET6_NTOA(al.ip_address) AS ip_text
        FROM audit_logs al
        LEFT JOIN users u ON u.id = al.user_id";
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY al.id DESC LIMIT 200";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

include dirname(__DIR__) . '/theme/header.php';
?>
<h1>Audit log</h1>
<form method="get" class="row g-2 mb-3">
    <div class="col-md-3"><input class="form-control" name="user_id" placeholder="User ID" value="<?= e($_GET['user_id'] ?? '') ?>"></div>
    <div class="col-md-3"><input class="form-control" name="action" placeholder="Veiksmas" value="<?= e($_GET['action'] ?? '') ?>"></div>
    <div class="col-md-3"><input class="form-control" name="entity_type" placeholder="Objektas" value="<?= e($_GET['entity_type'] ?? '') ?>"></div>
    <div class="col-md-3"><input class="form-control" name="ip" placeholder="IP" value="<?= e($_GET['ip'] ?? '') ?>"></div>
</form>
<table class="table table-striped small">
    <tr><th>ID</th><th>Data</th><th>User</th><th>Action</th><th>Entity</th><th>IP</th><th>Details</th></tr>
    <?php foreach ($logs as $log): ?>
    <tr>
        <td><?= (int)$log['id'] ?></td>
        <td><?= e(format_dt($log['created_at'])) ?></td>
        <td><?= e($log['username'] ?? '—') ?></td>
        <td><?= e($log['action']) ?></td>
        <td><?= e(($log['entity_type'] ?? '') . '#' . ($log['entity_id'] ?? '')) ?></td>
        <td><?= e($log['ip_text'] ?? '') ?></td>
        <td><code><?= e($log['details']) ?></code></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php include dirname(__DIR__) . '/theme/footer.php'; ?>
