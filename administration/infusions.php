<?php
require_once __DIR__ . '/_guard.php';
require_permission('infusions.manage');

$error = '';
$scanned = scan_infusions();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'install_folder') {
            $folder = trim((string)($_POST['folder'] ?? ''));
            $id = install_infusion_from_folder($folder);
            audit_log(current_user()['id'], 'infusion_install', 'infusions', $id, ['folder' => $folder]);
            flash('success', 'Infusion modulis įdiegtas iš failų sistemos.');
            redirect('infusions.php');
        }

        if (in_array($action, ['enable', 'disable'], true)) {
            $id = (int)($_POST['id'] ?? 0);
            $enabled = $action === 'enable' ? 1 : 0;
            $GLOBALS['pdo']->prepare("UPDATE infusions SET is_enabled = :e WHERE id = :id")->execute([':e' => $enabled, ':id' => $id]);
            audit_log(current_user()['id'], 'infusion_' . $action, 'infusions', $id);
            flash('success', 'Infusion modulio būsena pakeista.');
            redirect('infusions.php');
        }

        if ($action === 'upgrade') {
            $id = (int)($_POST['id'] ?? 0);
            $result = upgrade_infusion_by_id($id);
            $msg = !empty($result['upgraded'])
                ? 'Infusion modulis atnaujintas iš ' . $result['from'] . ' į ' . $result['to'] . (!empty($result['steps']) ? ' | žingsniai: ' . implode(', ', $result['steps']) : '')
                : 'Atnaujinimas nereikalingas. Versija ' . $result['to'];
            flash('success', $msg . '.');
            audit_log(current_user()['id'], 'infusion_upgrade', 'infusions', $id, $result);
            redirect('infusions.php');
        }

        if ($action === 'uninstall') {
            $id = (int)($_POST['id'] ?? 0);
            uninstall_infusion_by_id($id);
            audit_log(current_user()['id'], 'infusion_uninstall', 'infusions', $id);
            flash('success', 'Infusion modulis pašalintas.');
            redirect('infusions.php');
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$installed = $GLOBALS['pdo']->query("SELECT * FROM infusions ORDER BY id DESC")->fetchAll();
$installedFolders = [];
foreach ($installed as $i) {
    $installedFolders[$i['folder']] = $i;
}
$migrationLockStatus = get_infusion_migration_lock_status();
$recentMigrationActivity = get_recent_infusion_migration_activity(10);
$recentRollbackActivity = get_recent_infusion_rollback_activity(6);

include THEMES . 'default/admin_header.php';
?>
<h1 class="h3 mb-3"><?= e(__('infusions.title')) ?></h1>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<?php if ($msg = flash('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span>Migracij&#371; b&#363;sena</span>
        <?php if (!empty($migrationLockStatus['active'])): ?>
            <span class="badge text-bg-warning">Vykdoma dabar</span>
        <?php else: ?>
            <span class="badge text-bg-success">Laisva</span>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if (!empty($migrationLockStatus['active'])): ?>
            <?php
            $lockState = $migrationLockStatus['state'] ?? null;
            $lockDetails = $migrationLockStatus['details'] ?? [];
            $operationLabel = match ((string)($lockState['operation'] ?? '')) {
                'install' => '&#302;diegimas',
                'upgrade' => 'Atnaujinimas',
                'uninstall' => 'Pa&#353;alinimas',
                default => 'Migracija',
            };
            $lockModuleLabel = trim((string)($lockState['infusion_name'] ?? ''));
            if ($lockModuleLabel === '') {
                $lockModuleLabel = trim((string)($lockDetails['infusion_name'] ?? ''));
            }
            $lockFolder = trim((string)($lockState['folder'] ?? ''));
            $lockResource = trim((string)($lockState['resource'] ?? ''));
            ?>
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="text-secondary small">Veiksmas</div>
                    <div class="fw-semibold admin-strong-cell"><?= $operationLabel ?></div>
                </div>
                <div class="col-md-3">
                    <div class="text-secondary small">Modulis</div>
                    <div class="fw-semibold admin-strong-cell">
                        <?= e($lockModuleLabel !== '' ? $lockModuleLabel : ($lockFolder !== '' ? $lockFolder : '-')) ?>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-secondary small">Prad&#279;ta</div>
                    <div class="admin-table-note"><?= e(!empty($lockState['started_at']) ? format_dt($lockState['started_at']) : '-') ?></div>
                </div>
                <div class="col-md-3">
                    <div class="text-secondary small">Admin</div>
                    <div class="admin-table-note"><?= e($lockState['admin_username'] ?? '-') ?></div>
                </div>
                <div class="col-md-6">
                    <div class="text-secondary small">Lock pavadinimas</div>
                    <code class="admin-mono-pill"><?= e($migrationLockStatus['lock_name']) ?></code>
                </div>
                <div class="col-md-3">
                    <div class="text-secondary small">DB ry&#353;io ID</div>
                    <div class="admin-table-note"><?= e((string)($migrationLockStatus['owner_connection_id'] ?? '-')) ?></div>
                </div>
                <div class="col-md-3">
                    <div class="text-secondary small">Resursas</div>
                    <div class="admin-table-note"><?= e($lockResource !== '' ? $lockResource : '-') ?></div>
                </div>
            </div>
        <?php else: ?>
            <p class="mb-0 text-secondary">Aktyvi&#371; modulio migracij&#371; dabar n&#279;ra. Jei vienas administratorius paleis install arba upgrade, &#269;ia i&#353;kart matysis aktyvus lock ir kontekstas.</p>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><?= e(__('infusions.available')) ?></div>
            <div class="table-responsive">
                <table class="table align-middle mb-0 admin-table-strong">
                    <thead><tr><th>Folder</th><th>Pavadinimas</th><th>Versija</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($scanned as $folder => $meta): ?>
                        <tr>
                            <td><code class="admin-mono-pill admin-folder-label"><?= e($folder) ?></code></td>
                            <td>
                                <div class="fw-semibold admin-strong-cell"><?= e($meta['name']) ?></div>
                                <div class="small admin-table-description admin-description-strong"><?= e($meta['description'] ?? '') ?></div>
                            </td>
                            <td><span class="admin-table-note admin-version-chip"><?= e($meta['version'] ?? '0.0.0') ?></span></td>
                            <td>
                                <?php if (isset($installedFolders[$folder])): ?>
                                    <span class="badge text-bg-success"><?= e(__('infusions.installed_badge')) ?></span>
                                <?php else: ?>
                                    <form method="post">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="install_folder">
                                        <input type="hidden" name="folder" value="<?= e($folder) ?>">
                                        <button class="btn btn-sm btn-primary admin-action-button"><?= e(__('infusions.install')) ?></button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><?= e(__('infusions.installed')) ?></div>
            <div class="table-responsive">
                <table class="table align-middle mb-0 admin-table-strong">
                    <thead><tr><th>ID</th><th>Pavadinimas</th><th>Folder</th><th>Įdiegta</th><th>Manifest</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($installed as $inf):
                        $manifest = $scanned[$inf['folder']] ?? null;
                        $displayName = $manifest['name'] ?? $inf['name'];
                        $installedVersion = get_installed_infusion_version((int)$inf['id']) ?: '0.0.0';
                        $manifestVersion = $manifest['version'] ?? $installedVersion;
                    ?>
                        <tr>
                            <td class="admin-strong-cell"><?= (int)$inf['id'] ?></td>
                            <td><span class="fw-semibold admin-strong-cell"><?= e($displayName) ?></span></td>
                            <td><code class="admin-mono-pill admin-folder-label"><?= e($inf['folder']) ?></code></td>
                            <td><span class="admin-table-note admin-version-chip"><?= e($installedVersion) ?></span></td>
                            <td><span class="admin-table-note admin-version-chip"><?= e($manifestVersion) ?></span></td>
                            <td>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php if (!empty($manifest['admin']) && !empty($manifest['has_admin_file'])): ?>
                                        <a class="btn btn-sm btn-outline-primary admin-action-button" href="infusion-admin.php?folder=<?= urlencode($inf['folder']) ?>">Admin</a>
                                    <?php endif; ?>
                                    <form method="post" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int)$inf['id'] ?>">
                                        <?php if ((int)$inf['is_enabled']): ?>
                                            <button class="btn btn-sm btn-outline-warning admin-action-button" name="action" value="disable"><?= e(__('infusions.disable')) ?></button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-success admin-action-button" name="action" value="enable"><?= e(__('infusions.enable')) ?></button>
                                        <?php endif; ?>
                                    </form>
                                    <?php if (version_compare($manifestVersion, $installedVersion, '>')): ?>
                                        <form method="post" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int)$inf['id'] ?>">
                                            <button class="btn btn-sm btn-outline-primary admin-action-button" name="action" value="upgrade"><?= e(__('infusions.upgrade')) ?></button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="post" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int)$inf['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger admin-danger-button" name="action" value="uninstall" data-confirm-message="Tikrai pašalinti infusion modulį?"><?= e(__('infusions.uninstall')) ?></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$installed): ?>
                        <tr><td colspan="6" class="text-secondary">Kol kas nėra įdiegtų infusion modulių.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header">Paskutiniai migracij&#371; &#382;ingsniai</div>
            <div class="table-responsive">
                <table class="table align-middle mb-0 admin-table-strong">
                    <thead><tr><th>Data</th><th>Modulis</th><th>&#381;ingsnis</th><th>Kryptis</th><th>B&#363;sena</th><th>Pastaba</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentMigrationActivity as $row): ?>
                        <?php
                        $statusClass = match ((string)$row['status']) {
                            'done' => 'text-bg-success',
                            'failed' => 'text-bg-danger',
                            'started' => 'text-bg-warning',
                            'skipped' => 'text-bg-secondary',
                            default => 'text-bg-dark',
                        };
                        $directionLabel = (string)$row['direction'] === 'down' ? 'Down' : 'Up';
                        $moduleLabel = trim((string)($row['infusion_name'] ?? '')) !== ''
                            ? (string)$row['infusion_name']
                            : (string)($row['folder'] ?? ('#' . (int)$row['infusion_id']));
                        $activityTime = !empty($row['finished_at']) ? $row['finished_at'] : $row['started_at'];
                        ?>
                        <tr>
                            <td class="admin-table-note"><?= e($activityTime ? format_dt($activityTime) : '-') ?></td>
                            <td class="admin-strong-cell"><?= e($moduleLabel) ?></td>
                            <td><code class="admin-mono-pill"><?= e($row['step_version']) ?></code></td>
                            <td class="admin-table-note"><?= e($directionLabel) ?></td>
                            <td><span class="badge <?= $statusClass ?>"><?= e($row['status']) ?></span></td>
                            <td class="admin-table-note"><?= e($row['message'] ?: '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$recentMigrationActivity): ?>
                        <tr><td colspan="6" class="text-secondary">Migracij&#371; &#382;ingsni&#371; istorija dar tu&#353;&#269;ia.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header">Rollback istorija</div>
            <div class="table-responsive">
                <table class="table align-middle mb-0 admin-table-strong">
                    <thead><tr><th>Data</th><th>Modulis</th><th>Step</th><th>B&#363;sena</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentRollbackActivity as $row): ?>
                        <?php
                        $rollbackClass = match ((string)$row['status']) {
                            'done' => 'text-bg-success',
                            'failed' => 'text-bg-danger',
                            'started' => 'text-bg-warning',
                            'skipped' => 'text-bg-secondary',
                            default => 'text-bg-dark',
                        };
                        $rollbackModuleLabel = trim((string)($row['infusion_name'] ?? '')) !== ''
                            ? (string)$row['infusion_name']
                            : (string)($row['folder'] ?? ('#' . (int)$row['infusion_id']));
                        ?>
                        <tr>
                            <td class="admin-table-note"><?= e(format_dt($row['created_at'])) ?></td>
                            <td class="admin-strong-cell"><?= e($rollbackModuleLabel) ?></td>
                            <td>
                                <div><code class="admin-mono-pill"><?= e($row['failed_step']) ?></code></div>
                                <?php if (!empty($row['rollback_step'])): ?>
                                    <div class="small text-secondary mt-1">rollback: <?= e($row['rollback_step']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge <?= $rollbackClass ?>"><?= e($row['status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$recentRollbackActivity): ?>
                        <tr><td colspan="4" class="text-secondary">Rollback istorijos dar n&#279;ra.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include THEMES . 'default/admin_footer.php'; ?>
