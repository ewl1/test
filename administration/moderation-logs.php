<?php
require_once __DIR__ . '/_guard.php';
require_permission('audit.view');

ensure_moderation_log_schema();

$filters = [
    'moderator_user_id' => trim((string)($_GET['moderator_user_id'] ?? '')),
    'action' => trim((string)($_GET['action'] ?? '')),
    'target_type' => trim((string)($_GET['target_type'] ?? '')),
    'ip' => trim((string)($_GET['ip'] ?? '')),
];

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 100;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];

if ($filters['moderator_user_id'] !== '') {
    $where[] = 'ml.moderator_user_id = :moderator_user_id';
    $params[':moderator_user_id'] = (int)$filters['moderator_user_id'];
}
if ($filters['action'] !== '') {
    $where[] = 'ml.action LIKE :action';
    $params[':action'] = '%' . $filters['action'] . '%';
}
if ($filters['target_type'] !== '') {
    $where[] = 'ml.target_type LIKE :target_type';
    $params[':target_type'] = '%' . $filters['target_type'] . '%';
}
if ($filters['ip'] !== '') {
    $where[] = "COALESCE(NULLIF(INET6_NTOA(ml.ip_address), ''), NULLIF(CAST(ml.ip_address AS CHAR(45)), ''), '') LIKE :ip";
    $params[':ip'] = '%' . $filters['ip'] . '%';
}

$fromSql = 'FROM ' . moderation_log_table_name() . ' ml LEFT JOIN users u ON u.id = ml.moderator_user_id';
if ($where) {
    $fromSql .= ' WHERE ' . implode(' AND ', $where);
}

$countStmt = $GLOBALS['pdo']->prepare("SELECT COUNT(*) {$fromSql}");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));

$sql = "SELECT ml.*, u.username,
        COALESCE(NULLIF(INET6_NTOA(ml.ip_address), ''), NULLIF(CAST(ml.ip_address AS CHAR(45)), ''), '') AS ip_text
        {$fromSql}
        ORDER BY ml.id DESC
        LIMIT {$perPage} OFFSET {$offset}";
$stmt = $GLOBALS['pdo']->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

include THEMES . 'default/admin_header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Moderavimo žurnalas</h1>
    <span class="badge text-bg-secondary"><?= (int)$total ?> įrašai</span>
</div>

<form method="get" class="card card-body mb-3">
    <div class="row g-2">
        <div class="col-md-3"><input class="form-control" name="moderator_user_id" placeholder="Moderatorius ID" value="<?= e($filters['moderator_user_id']) ?>"></div>
        <div class="col-md-3"><input class="form-control" name="action" placeholder="Veiksmo kodas" value="<?= e($filters['action']) ?>"></div>
        <div class="col-md-3"><input class="form-control" name="target_type" placeholder="Objekto tipas" value="<?= e($filters['target_type']) ?>"></div>
        <div class="col-md-3"><input class="form-control" name="ip" placeholder="IP" value="<?= e($filters['ip']) ?>"></div>
        <div class="col-12 d-flex gap-2">
            <button class="btn btn-primary">Filtruoti</button>
            <a class="btn btn-outline-secondary" href="<?= public_path('administration/moderation-logs.php') ?>">Išvalyti</a>
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
                <th>Moderatorius</th>
                <th>Veiksmas</th>
                <th>Objektas</th>
                <th>Kontekstas</th>
                <th>Priežastis</th>
                <th>IP</th>
                <th>Detalės</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
                <?php
                $targetSummary = trim((string)($log['target_type'] ?? ''));
                if (!empty($log['target_label'])) {
                    $targetSummary .= ': ' . (string)$log['target_label'];
                } elseif (!empty($log['target_id'])) {
                    $targetSummary .= ' #' . (int)$log['target_id'];
                }

                $contextSummary = trim((string)($log['context_type'] ?? ''));
                if (!empty($log['context_id'])) {
                    $contextSummary .= ' #' . (int)$log['context_id'];
                }
                ?>
                <tr>
                    <td><?= (int)$log['id'] ?></td>
                    <td><?= e(format_dt($log['created_at'])) ?></td>
                    <td><?= e($log['username'] ?? __('member.guest')) ?></td>
                    <td>
                        <div class="fw-semibold admin-strong-cell"><?= e(moderation_action_label($log['action'])) ?></div>
                        <code class="admin-mono-pill"><?= e($log['action']) ?></code>
                    </td>
                    <td class="min-width-260">
                        <div class="admin-strong-cell"><?= e($targetSummary !== '' ? $targetSummary : '-') ?></div>
                    </td>
                    <td class="admin-table-note"><?= e($contextSummary !== '' ? $contextSummary : '-') ?></td>
                    <td class="admin-table-note"><?= e($log['reason'] ?: '-') ?></td>
                    <td><?= e($log['ip_text']) ?></td>
                    <td class="min-width-260">
                        <?php if (!empty($log['details'])): ?>
                            <details class="audit-details">
                                <summary>Peržiūrėti</summary>
                                <pre class="small mt-2 mb-0 admin-log-entry"><?= e(moderation_pretty_details($log['details'])) ?></pre>
                            </details>
                        <?php else: ?>
                            <span class="text-secondary">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$logs): ?>
                <tr><td colspan="9" class="text-secondary">Moderavimo įrašų nerasta.</td></tr>
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
