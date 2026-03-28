<?php
require_once __DIR__ . '/_guard.php';
require_permission('audit.view');

ensure_auth_security_log_schema();

$filters = [
    'user_id' => trim((string)($_GET['user_id'] ?? '')),
    'event' => trim((string)($_GET['event'] ?? '')),
    'status' => trim((string)($_GET['status'] ?? '')),
    'email' => trim((string)($_GET['email'] ?? '')),
    'ip' => trim((string)($_GET['ip'] ?? '')),
];

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 100;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];

if ($filters['user_id'] !== '') {
    $where[] = 'sl.user_id = :user_id';
    $params[':user_id'] = (int)$filters['user_id'];
}
if ($filters['event'] !== '') {
    $where[] = 'sl.event LIKE :event';
    $params[':event'] = '%' . $filters['event'] . '%';
}
if ($filters['status'] !== '') {
    $where[] = 'sl.status = :status';
    $params[':status'] = $filters['status'];
}
if ($filters['email'] !== '') {
    $where[] = 'sl.email LIKE :email';
    $params[':email'] = '%' . $filters['email'] . '%';
}
if ($filters['ip'] !== '') {
    $where[] = "COALESCE(NULLIF(INET6_NTOA(sl.ip_address), ''), NULLIF(CAST(sl.ip_address AS CHAR(45)), ''), '') LIKE :ip";
    $params[':ip'] = '%' . $filters['ip'] . '%';
}

$fromSql = 'FROM ' . auth_security_log_table_name() . ' sl LEFT JOIN users u ON u.id = sl.user_id';
if ($where) {
    $fromSql .= ' WHERE ' . implode(' AND ', $where);
}

$countStmt = $GLOBALS['pdo']->prepare("SELECT COUNT(*) {$fromSql}");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));

$sql = "SELECT sl.*, u.username,
        COALESCE(NULLIF(INET6_NTOA(sl.ip_address), ''), NULLIF(CAST(sl.ip_address AS CHAR(45)), ''), '') AS ip_text
        {$fromSql}
        ORDER BY sl.id DESC
        LIMIT {$perPage} OFFSET {$offset}";
$stmt = $GLOBALS['pdo']->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

include THEMES . 'default/admin_header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Saugumo &#382;urnalas</h1>
    <span class="badge text-bg-secondary"><?= (int)$total ?> &#303;ra&#353;ai</span>
</div>

<form method="get" class="card card-body mb-3">
    <div class="row g-2">
        <div class="col-md-2"><input class="form-control" name="user_id" placeholder="User ID" value="<?= e($filters['user_id']) ?>"></div>
        <div class="col-md-2"><input class="form-control" name="event" placeholder="Event" value="<?= e($filters['event']) ?>"></div>
        <div class="col-md-2">
            <select class="form-select" name="status">
                <option value="">B&#363;sena</option>
                <?php foreach (['success' => 'S&#279;km&#279;', 'failed' => 'Klaida', 'blocked' => 'Blokuota', 'warning' => '&#302;sp&#279;jimas', 'info' => 'Info'] as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= $filters['status'] === $value ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3"><input class="form-control" name="email" placeholder="El. pa&#353;tas" value="<?= e($filters['email']) ?>"></div>
        <div class="col-md-3"><input class="form-control" name="ip" placeholder="IP" value="<?= e($filters['ip']) ?>"></div>
        <div class="col-12 d-flex gap-2">
            <button class="btn btn-primary">Filtruoti</button>
            <a class="btn btn-outline-secondary" href="<?= public_path('administration/security-logs.php') ?>">I&#353;valyti</a>
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
                <th>Naudotojas</th>
                <th>&#302;vykis</th>
                <th>B&#363;sena</th>
                <th>Objektas</th>
                <th>El. pa&#353;tas</th>
                <th>Prie&#382;astis</th>
                <th>IP</th>
                <th>Detal&#279;s</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
                <?php
                $subjectSummary = trim((string)($log['subject_type'] ?? ''));
                if (!empty($log['subject_label'])) {
                    $subjectSummary .= ': ' . (string)$log['subject_label'];
                } elseif (!empty($log['subject_id'])) {
                    $subjectSummary .= ' #' . (int)$log['subject_id'];
                }

                $statusClass = match ((string)$log['status']) {
                    'success' => 'text-bg-success',
                    'failed' => 'text-bg-danger',
                    'blocked' => 'text-bg-warning',
                    'warning' => 'text-bg-secondary',
                    default => 'text-bg-dark',
                };
                $statusLabel = match ((string)$log['status']) {
                    'success' => 'Sėkmė',
                    'failed' => 'Klaida',
                    'blocked' => 'Blokuota',
                    'warning' => 'Įspėjimas',
                    default => 'Info',
                };
                ?>
                <tr>
                    <td><?= (int)$log['id'] ?></td>
                    <td><?= e(format_dt($log['created_at'])) ?></td>
                    <td>
                        <div class="admin-strong-cell"><?= e($log['username'] ?? __('member.guest')) ?></div>
                    </td>
                    <td>
                        <div class="fw-semibold admin-strong-cell"><?= e(auth_security_event_meta($log['event'])['label']) ?></div>
                        <code class="admin-mono-pill"><?= e($log['event']) ?></code>
                    </td>
                    <td><span class="badge <?= $statusClass ?>"><?= e($statusLabel) ?></span></td>
                    <td class="admin-table-note min-width-260"><?= e($subjectSummary !== '' ? $subjectSummary : '-') ?></td>
                    <td class="admin-table-note"><?= e($log['email'] ?: '-') ?></td>
                    <td class="admin-table-note"><?= e($log['reason'] ?: '-') ?></td>
                    <td><?= e($log['ip_text']) ?></td>
                    <td class="min-width-260">
                        <?php if (!empty($log['details'])): ?>
                            <details class="audit-details">
                                <summary>Peržiūrėti</summary>
                                <pre class="small mt-2 mb-0 admin-log-entry"><?= e(auth_security_pretty_details($log['details'])) ?></pre>
                            </details>
                        <?php else: ?>
                            <span class="text-secondary">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$logs): ?>
                <tr><td colspan="10" class="text-secondary">Saugumo įrašų nerasta.</td></tr>
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
