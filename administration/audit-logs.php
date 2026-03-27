<?php
require_once __DIR__ . '/_guard.php';
require_permission('audit.view');

function audit_pretty_details($value)
{
    $decoded = json_decode((string)$value, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    return (string)$value;
}

$filters = [
    'user_id' => trim((string)($_GET['user_id'] ?? '')),
    'action' => trim((string)($_GET['action'] ?? '')),
    'entity_type' => trim((string)($_GET['entity_type'] ?? '')),
    'ip' => trim((string)($_GET['ip'] ?? '')),
];
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 100;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];

if ($filters['user_id'] !== '') {
    $where[] = 'al.user_id = :user_id';
    $params[':user_id'] = (int)$filters['user_id'];
}
if ($filters['action'] !== '') {
    $where[] = 'al.action LIKE :action';
    $params[':action'] = '%' . $filters['action'] . '%';
}
if ($filters['entity_type'] !== '') {
    $where[] = 'al.entity_type LIKE :entity_type';
    $params[':entity_type'] = '%' . $filters['entity_type'] . '%';
}
if ($filters['ip'] !== '') {
    $where[] = "COALESCE(NULLIF(INET6_NTOA(al.ip_address), ''), NULLIF(CAST(al.ip_address AS CHAR(45)), ''), '') LIKE :ip";
    $params[':ip'] = '%' . $filters['ip'] . '%';
}

$fromSql = 'FROM audit_logs al LEFT JOIN users u ON u.id = al.user_id';
if ($where) {
    $fromSql .= ' WHERE ' . implode(' AND ', $where);
}

$countStmt = $GLOBALS['pdo']->prepare("SELECT COUNT(*) {$fromSql}");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$sql = "SELECT al.*, u.username,
        COALESCE(NULLIF(INET6_NTOA(al.ip_address), ''), NULLIF(CAST(al.ip_address AS CHAR(45)), ''), '') AS ip_text
        {$fromSql}
        ORDER BY al.id DESC
        LIMIT {$perPage} OFFSET {$offset}";
$stmt = $GLOBALS['pdo']->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();
$pages = max(1, (int)ceil($total / $perPage));

include THEMES . 'default/admin_header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Audit log</h1>
    <span class="badge text-bg-secondary"><?= (int)$total ?> irasai</span>
</div>

<form method="get" class="card card-body mb-3">
    <div class="row g-2">
        <div class="col-md-3"><input class="form-control" name="user_id" placeholder="User ID" value="<?= e($filters['user_id']) ?>"></div>
        <div class="col-md-3"><input class="form-control" name="action" placeholder="Veiksmas" value="<?= e($filters['action']) ?>"></div>
        <div class="col-md-3"><input class="form-control" name="entity_type" placeholder="Objektas" value="<?= e($filters['entity_type']) ?>"></div>
        <div class="col-md-3"><input class="form-control" name="ip" placeholder="IP" value="<?= e($filters['ip']) ?>"></div>
        <div class="col-12 d-flex gap-2">
            <button class="btn btn-primary">Filtruoti</button>
            <a class="btn btn-outline-secondary" href="<?= public_path('administration/audit-logs.php') ?>">Isvalyti</a>
        </div>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-striped align-middle mb-0 small admin-log-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Data</th>
                    <th>Vartotojas</th>
                    <th>Veiksmas</th>
                    <th>Objektas</th>
                    <th>IP</th>
                    <th>URL</th>
                    <th>Detales</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= (int)$log['id'] ?></td>
                    <td><?= e(format_dt($log['created_at'])) ?></td>
                    <td><?= e($log['username'] ?? 'Svecias') ?></td>
                    <td><code><?= e($log['action']) ?></code></td>
                    <td><?= e(trim(($log['entity_type'] ?? '') . ' #' . ($log['entity_id'] ?? ''), ' #')) ?></td>
                    <td><?= e($log['ip_text']) ?></td>
                    <td class="text-break"><?= e($log['url'] ?? '') ?></td>
                    <td class="min-width-260">
                        <?php if (!empty($log['details'])): ?>
                            <details class="audit-details">
                                <summary>Perziureti</summary>
                                <pre class="small mt-2 mb-0 admin-log-entry"><?= e(audit_pretty_details($log['details'])) ?></pre>
                            </details>
                        <?php else: ?>
                            <span class="text-secondary">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$logs): ?>
                <tr><td colspan="8" class="text-secondary">Audit irasu nerasta.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($pages > 1): ?>
<nav class="mt-3">
    <ul class="pagination mb-0">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
            <?php $query = http_build_query(array_merge($filters, ['page' => $i])); ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="?<?= e($query) ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>
<?php include THEMES . 'default/admin_footer.php'; ?>
