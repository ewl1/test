<?php
require_once __DIR__ . '/_guard.php';
require_permission('infusions.manage');

$error = '';
$scanned = scan_infusions();
$developerModeParam = $_GET['developer'] ?? null;
if ($developerModeParam !== null) {
    $_SESSION['infusions_developer_mode'] = $developerModeParam === '1';
}
$developerMode = !empty($_SESSION['infusions_developer_mode']);
$developerToggleUrl = 'infusions.php?developer=' . ($developerMode ? '0' : '1');
$redirectTarget = 'infusions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'install_folder') {
            $folder = trim((string)($_POST['folder'] ?? ''));
            $id = install_infusion_from_folder($folder);
            audit_log(current_user()['id'], 'infusion_install', 'infusions', $id, ['folder' => $folder]);
            flash('success', 'Infusion modulis įdiegtas iš failų sistemos.');
            redirect($redirectTarget);
        }

        if (in_array($action, ['enable', 'disable'], true)) {
            $id = (int)($_POST['id'] ?? 0);
            $enabled = $action === 'enable' ? 1 : 0;
            $GLOBALS['pdo']->prepare("UPDATE infusions SET is_enabled = :e WHERE id = :id")->execute([':e' => $enabled, ':id' => $id]);
            audit_log(current_user()['id'], 'infusion_' . $action, 'infusions', $id);
            flash('success', 'Infusion modulio būsena pakeista.');
            redirect($redirectTarget);
        }

        if ($action === 'upgrade') {
            $id = (int)($_POST['id'] ?? 0);
            $result = upgrade_infusion_by_id($id);
            $msg = !empty($result['upgraded'])
                ? 'Infusion modulis atnaujintas iš ' . $result['from'] . ' į ' . $result['to'] . (!empty($result['steps']) ? ' | žingsniai: ' . implode(', ', $result['steps']) : '')
                : 'Atnaujinimas nereikalingas. Versija ' . $result['to'];
            flash('success', $msg . '.');
            audit_log(current_user()['id'], 'infusion_upgrade', 'infusions', $id, $result);
            redirect($redirectTarget);
        }

        if ($action === 'uninstall') {
            $id = (int)($_POST['id'] ?? 0);
            $result = uninstall_infusion_by_id($id, (string)($_POST['confirm_folder'] ?? ''));
            audit_log(current_user()['id'], 'infusion_uninstall', 'infusions', $id, $result);
            flash('success', 'Infusion modulis pašalintas.');
            redirect($redirectTarget);
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
$versionSummaries = [];
$compatibilitySummaries = [];
$healthSummaries = [];
$uninstallSummaries = [];
foreach ($scanned as $folder => $meta) {
    $versionSummaries[$folder] = get_infusion_version_summary($folder, $meta, $installedFolders[$folder] ?? null);
    $compatibilitySummaries[$folder] = get_infusion_compatibility_summary($folder, $meta, $installedFolders, $scanned);
    $healthSummaries[$folder] = get_infusion_health_summary($folder, $meta);
    if (isset($installedFolders[$folder])) {
        $uninstallSummaries[$folder] = get_infusion_uninstall_summary($folder, $installedFolders[$folder], $meta, $installedFolders, $scanned);
    }
}
foreach ($installed as $inf) {
    if (!isset($versionSummaries[$inf['folder']])) {
        $versionSummaries[$inf['folder']] = get_infusion_version_summary($inf['folder'], $scanned[$inf['folder']] ?? null, $inf);
    }
    if (!isset($compatibilitySummaries[$inf['folder']])) {
        $compatibilitySummaries[$inf['folder']] = get_infusion_compatibility_summary($inf['folder'], $scanned[$inf['folder']] ?? null, $installedFolders, $scanned);
    }
    if (!isset($healthSummaries[$inf['folder']])) {
        $healthSummaries[$inf['folder']] = get_infusion_health_summary($inf['folder'], $scanned[$inf['folder']] ?? null);
    }
    if (!isset($uninstallSummaries[$inf['folder']])) {
        $uninstallSummaries[$inf['folder']] = get_infusion_uninstall_summary($inf['folder'], $inf, $scanned[$inf['folder']] ?? null, $installedFolders, $scanned);
    }
}
$migrationLockStatus = get_infusion_migration_lock_status();
$recentMigrationActivity = get_recent_infusion_migration_activity(10);
$recentRollbackActivity = get_recent_infusion_rollback_activity(6);
$developerSnapshots = [];
if ($developerMode) {
    foreach ($scanned as $folder => $meta) {
        $developerSnapshots[$folder] = get_infusion_developer_snapshot(
            $folder,
            (int)($installedFolders[$folder]['id'] ?? 0),
            $meta
        );
    }
}

include THEMES . 'default/admin_header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
    <div>
        <h1 class="h3 mb-1"><?= e(__('infusions.title')) ?></h1>
        <div class="admin-page-subtitle">
            <?= $developerMode ? 'Developer mode aktyvus: rodoma modulio klase, failai, migracijos, hook&apos;ai ir manifest duomenys.' : 'Ijunkite developer mode, jei norite matyti modulio klases, hook&apos;us, migracijas ir manifest detales.' ?>
        </div>
    </div>
    <a class="btn btn-sm <?= $developerMode ? 'btn-outline-warning' : 'btn-outline-primary' ?> admin-action-button" href="<?= e($developerToggleUrl) ?>">
        <?= $developerMode ? 'Isjungti developer mode' : 'Ijungti developer mode' ?>
    </a>
</div>
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
                    <thead><tr><th>Folder</th><th>Pavadinimas</th><th>&#302;diegta</th><th>Manifest</th><th>Atnaujinimas</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($scanned as $folder => $meta): ?>
                        <?php $developerMeta = $developerSnapshots[$folder] ?? null; ?>
                        <?php $versionSummary = $versionSummaries[$folder] ?? get_infusion_version_summary($folder, $meta, $installedFolders[$folder] ?? null); ?>
                        <?php $compatibilitySummary = $compatibilitySummaries[$folder] ?? get_infusion_compatibility_summary($folder, $meta, $installedFolders, $scanned); ?>
                        <?php $healthSummary = $healthSummaries[$folder] ?? get_infusion_health_summary($folder, $meta); ?>
                        <?php $uninstallSummary = $uninstallSummaries[$folder] ?? null; ?>
                        <?php $moduleActions = get_infusion_module_actions($folder, $meta, $installedFolders[$folder] ?? null, $versionSummary); ?>
                        <tr>
                            <td><code class="admin-mono-pill admin-folder-label"><?= e($folder) ?></code></td>
                            <td>
                                <div class="fw-semibold admin-strong-cell"><?= e($meta['name']) ?></div>
                                <div class="small admin-table-description admin-description-strong"><?= e($meta['description'] ?? '') ?></div>
                                <div class="admin-version-summary mt-2">
                                    <span class="admin-version-pill"><strong>Idiegta:</strong> <?= e($versionSummary['installed_display']) ?></span>
                                    <span class="admin-version-pill"><strong>Manifest:</strong> <?= e($versionSummary['manifest_display']) ?></span>
                                    <span class="admin-version-pill"><strong>Atnaujinimas:</strong> <?= e($versionSummary['available_upgrade_display']) ?></span>
                                    <span class="badge <?= e($versionSummary['status_badge_class']) ?> admin-version-state-badge"><?= e($versionSummary['status_label']) ?></span>
                                    <span class="badge <?= e($compatibilitySummary['badge_class']) ?> admin-version-state-badge"><?= e($compatibilitySummary['label']) ?></span>
                                    <span class="badge <?= e($healthSummary['badge_class']) ?> admin-version-state-badge"><?= e($healthSummary['label']) ?></span>
                                </div>
                                <div class="small admin-module-meta mt-2">Suderinamumas: <?= e($compatibilitySummary['environment_summary']) ?></div>
                                <div class="small admin-module-meta">Priklausomybes: <?= e($compatibilitySummary['dependencies_summary']) ?> | Konfliktai: <?= e($compatibilitySummary['conflicts_summary']) ?></div>
                                <div class="small admin-module-meta">Sveikata: <?= e($healthSummary['summary']) ?></div>
                                <?php if ($uninstallSummary): ?>
                                    <div class="small admin-module-meta">Salinimas: <?= e($uninstallSummary['summary']) ?></div>
                                <?php endif; ?>
                                <?php if ($developerMode && $developerMeta): ?>
                                    <div class="d-flex flex-wrap gap-1 mt-2">
                                        <span class="badge text-bg-dark admin-dev-badge"><?= e($developerMeta['is_sdk_module'] ? 'SDK' : 'Legacy') ?></span>
                                        <?php if (!empty($developerMeta['directories']['migrations/'])): ?>
                                            <span class="badge text-bg-info admin-dev-badge">Migrations</span>
                                        <?php endif; ?>
                                        <?php if (!empty($developerMeta['files']['admin.php'])): ?>
                                            <span class="badge text-bg-secondary admin-dev-badge">Admin</span>
                                        <?php endif; ?>
                                        <?php if (!empty($developerMeta['module_class_exists'])): ?>
                                            <span class="badge text-bg-success admin-dev-badge">Class OK</span>
                                        <?php else: ?>
                                            <span class="badge text-bg-warning admin-dev-badge">Class missing</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><span class="admin-table-note admin-version-chip"><?= e($versionSummary['installed_display']) ?></span></td>
                            <td><span class="admin-table-note admin-version-chip"><?= e($versionSummary['manifest_display']) ?></span></td>
                            <td>
                                <span class="admin-table-note admin-version-chip"><?= e($versionSummary['available_upgrade_display']) ?></span>
                                <div class="mt-1">
                                    <span class="badge <?= e($versionSummary['status_badge_class']) ?> admin-version-state-badge"><?= e($versionSummary['status_label']) ?></span>
                                </div>
                            </td>
                            <td>
                                <?php if (isset($installedFolders[$folder])): ?>
                                    <div class="d-flex flex-wrap gap-2 admin-module-actions">
                                        <?php foreach ($moduleActions as $action): ?>
                                            <?php if ($action['kind'] === 'link'): ?>
                                                <a class="<?= e($action['class']) ?>" href="<?= e($action['href']) ?>"><?= e($action['label']) ?></a>
                                            <?php elseif ($action['kind'] === 'post'): ?>
                                                <form method="post" class="d-inline">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="id" value="<?= (int)($installedFolders[$folder]['id'] ?? 0) ?>">
                                                    <?php if (($action['key'] ?? '') === 'uninstall' && !empty($action['requires_confirmation'])): ?>
                                                        <input class="form-control form-control-sm admin-confirm-input" type="text" name="confirm_folder" placeholder="Iveskite <?= e($action['confirmation_value'] ?? '') ?>">
                                                    <?php endif; ?>
                                                    <button class="<?= e($action['class']) ?>" name="action" value="<?= e($action['value']) ?>" <?= (($action['key'] ?? '') === 'uninstall' && empty($action['can_uninstall'])) ? 'disabled' : '' ?>><?= e($action['label']) ?></button>
                                                </form>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                        <span class="badge text-bg-success"><?= e(__('infusions.installed_badge')) ?></span>
                                    </div>
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
                        $developerMeta = $developerSnapshots[$inf['folder']] ?? null;
                        $displayName = $manifest['name'] ?? $inf['name'];
                        $versionSummary = $versionSummaries[$inf['folder']] ?? get_infusion_version_summary($inf['folder'], $manifest, $inf);
                        $compatibilitySummary = $compatibilitySummaries[$inf['folder']] ?? get_infusion_compatibility_summary($inf['folder'], $manifest, $installedFolders, $scanned);
                        $healthSummary = $healthSummaries[$inf['folder']] ?? get_infusion_health_summary($inf['folder'], $manifest);
                        $uninstallSummary = $uninstallSummaries[$inf['folder']] ?? get_infusion_uninstall_summary($inf['folder'], $inf, $manifest, $installedFolders, $scanned);
                        $moduleActions = get_infusion_module_actions($inf['folder'], $manifest, $inf, $versionSummary);
                    ?>
                        <tr>
                            <td class="admin-strong-cell"><?= (int)$inf['id'] ?></td>
                            <td>
                                <span class="fw-semibold admin-strong-cell"><?= e($displayName) ?></span>
                                <div class="admin-version-summary mt-2">
                                    <span class="admin-version-pill"><strong>Idiegta:</strong> <?= e($versionSummary['installed_display']) ?></span>
                                    <span class="admin-version-pill"><strong>Manifest:</strong> <?= e($versionSummary['manifest_display']) ?></span>
                                    <span class="admin-version-pill"><strong>Atnaujinimas:</strong> <?= e($versionSummary['available_upgrade_display']) ?></span>
                                    <span class="badge <?= e($versionSummary['status_badge_class']) ?> admin-version-state-badge"><?= e($versionSummary['status_label']) ?></span>
                                    <span class="badge <?= e($compatibilitySummary['badge_class']) ?> admin-version-state-badge"><?= e($compatibilitySummary['label']) ?></span>
                                    <span class="badge <?= e($healthSummary['badge_class']) ?> admin-version-state-badge"><?= e($healthSummary['label']) ?></span>
                                </div>
                                <div class="small admin-module-meta mt-2">Suderinamumas: <?= e($compatibilitySummary['environment_summary']) ?></div>
                                <div class="small admin-module-meta">Priklausomybes: <?= e($compatibilitySummary['dependencies_summary']) ?> | Konfliktai: <?= e($compatibilitySummary['conflicts_summary']) ?></div>
                                <div class="small admin-module-meta">Sveikata: <?= e($healthSummary['summary']) ?></div>
                                <div class="small admin-module-meta">Salinimas: <?= e($uninstallSummary['summary']) ?></div>
                                <?php if ($developerMode && $developerMeta): ?>
                                    <div class="d-flex flex-wrap gap-1 mt-2">
                                        <span class="badge text-bg-dark admin-dev-badge"><?= e($developerMeta['is_sdk_module'] ? 'SDK' : 'Legacy') ?></span>
                                        <?php if (!empty($developerMeta['directories']['migrations/'])): ?>
                                            <span class="badge text-bg-info admin-dev-badge"><?= e(count($developerMeta['migration_steps'])) ?> migration</span>
                                        <?php endif; ?>
                                        <?php if (!empty($developerMeta['registered_hooks'])): ?>
                                            <span class="badge text-bg-secondary admin-dev-badge"><?= e(count($developerMeta['registered_hooks'])) ?> hook</span>
                                        <?php endif; ?>
                                        <?php if ((int)$inf['is_enabled'] === 1): ?>
                                            <span class="badge text-bg-success admin-dev-badge">Enabled</span>
                                        <?php else: ?>
                                            <span class="badge text-bg-warning admin-dev-badge">Disabled</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><code class="admin-mono-pill admin-folder-label"><?= e($inf['folder']) ?></code></td>
                            <td><span class="admin-table-note admin-version-chip"><?= e($versionSummary['installed_display']) ?></span></td>
                            <td><span class="admin-table-note admin-version-chip"><?= e($versionSummary['manifest_display']) ?></span></td>
                            <td>
                                <div class="d-flex flex-wrap gap-2 admin-module-actions">
                                    <?php foreach ($moduleActions as $action): ?>
                                        <?php if ($action['kind'] === 'link'): ?>
                                            <a class="<?= e($action['class']) ?>" href="<?= e($action['href']) ?>"><?= e($action['label']) ?></a>
                                        <?php elseif ($action['kind'] === 'post'): ?>
                                            <form method="post" class="d-inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= (int)$inf['id'] ?>">
                                                <?php if (($action['key'] ?? '') === 'uninstall' && !empty($action['requires_confirmation'])): ?>
                                                    <input class="form-control form-control-sm admin-confirm-input" type="text" name="confirm_folder" placeholder="Iveskite <?= e($action['confirmation_value'] ?? '') ?>">
                                                <?php endif; ?>
                                                <button class="<?= e($action['class']) ?>" name="action" value="<?= e($action['value']) ?>" <?= (($action['key'] ?? '') === 'uninstall' && empty($action['can_uninstall'])) ? 'disabled' : '' ?>><?= e($action['label']) ?></button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    <form method="post" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int)$inf['id'] ?>">
                                        <?php if ((int)$inf['is_enabled']): ?>
                                            <button class="btn btn-sm btn-outline-warning admin-action-button" name="action" value="disable"><?= e(__('infusions.disable')) ?></button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-success admin-action-button" name="action" value="enable"><?= e(__('infusions.enable')) ?></button>
                                        <?php endif; ?>
                                    </form>
                                    <form method="post" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int)$inf['id'] ?>">
                                        <?php if (!empty($uninstallSummary['requires_confirmation'])): ?>
                                            <input class="form-control form-control-sm admin-confirm-input" type="text" name="confirm_folder" placeholder="Iveskite <?= e($uninstallSummary['confirmation_value'] ?? '') ?>">
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-outline-danger admin-danger-button" name="action" value="uninstall" data-confirm-message="Tikrai pašalinti infusion modulį?" <?= empty($uninstallSummary['can_uninstall']) ? 'disabled' : '' ?>><?= e(__('infusions.uninstall')) ?></button>
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

<?php if ($developerMode): ?>
<div class="card mb-4 mt-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span>Developer mode</span>
        <span class="badge text-bg-info"><?= e(count($developerSnapshots)) ?> moduliai</span>
    </div>
    <div class="card-body">
        <p class="mb-4 text-secondary">Si perziura skirta greitam modulio SDK, manifest, migraciju ir runtime hook&apos;u auditui. Jei modulis isjungtas, hook&apos;ai gali buti nerodomi, nes jie registruojami tik `boot` metu.</p>

        <div class="admin-dev-grid">
            <?php foreach ($developerSnapshots as $folder => $snapshot): ?>
                <?php
                $moduleLabel = trim((string)($snapshot['manifest']['name'] ?? $folder));
                $installedInfo = $snapshot['installed'];
                $statusLabel = !$installedInfo ? 'Neidiegtas' : ((int)($installedInfo['is_enabled'] ?? 0) === 1 ? 'Ijungtas' : 'Isjungtas');
                $statusClass = !$installedInfo ? 'text-bg-secondary' : ((int)($installedInfo['is_enabled'] ?? 0) === 1 ? 'text-bg-success' : 'text-bg-warning');
                $compatibilitySummary = $compatibilitySummaries[$folder] ?? get_infusion_compatibility_summary($folder, $snapshot['manifest'], $installedFolders, $scanned);
                $healthSummary = $healthSummaries[$folder] ?? get_infusion_health_summary($folder, $snapshot['manifest']);
                ?>
                <details class="admin-dev-card" id="infusion-dev-<?= e($folder) ?>">
                    <summary class="admin-dev-summary">
                        <div>
                            <div class="admin-dev-title"><?= e($moduleLabel) ?></div>
                            <div class="small admin-page-subtitle"><?= e($folder) ?></div>
                        </div>
                        <div class="d-flex flex-wrap gap-1 justify-content-end">
                            <span class="badge <?= $statusClass ?> admin-dev-badge"><?= e($statusLabel) ?></span>
                            <span class="badge text-bg-dark admin-dev-badge"><?= e($snapshot['is_sdk_module'] ? 'SDK' : 'Legacy') ?></span>
                            <?php if (!empty($snapshot['directories']['migrations/'])): ?>
                                <span class="badge text-bg-info admin-dev-badge">migrations</span>
                            <?php endif; ?>
                            <?php if (!empty($snapshot['module_class_exists'])): ?>
                                <span class="badge text-bg-success admin-dev-badge">class ok</span>
                            <?php else: ?>
                                <span class="badge text-bg-warning admin-dev-badge">class missing</span>
                            <?php endif; ?>
                        </div>
                    </summary>

                    <div class="admin-dev-body">
                        <div class="admin-dev-columns">
                            <div>
                                <div class="admin-dev-section-title">Pagrindas</div>
                                <ul class="list-unstyled admin-dev-list mb-0">
                                    <li><strong>Modulio klase:</strong> <code class="admin-mono-pill"><?= e($snapshot['module_class']) ?></code></li>
                                    <li><strong>Namespace:</strong> <code class="admin-mono-pill"><?= e($snapshot['module_namespace']) ?></code></li>
                                    <li><strong>Katalogas:</strong> <code class="admin-path-code admin-path-code-strong"><?= e($snapshot['directory']) ?></code></li>
                                    <?php if ($snapshot['settings_page'] !== ''): ?>
                                        <li><strong>Settings:</strong> <code class="admin-mono-pill"><?= e($snapshot['settings_page']) ?></code></li>
                                    <?php endif; ?>
                                    <?php if ($snapshot['diagnostics_page'] !== ''): ?>
                                        <li><strong>Diagnostics:</strong> <code class="admin-mono-pill"><?= e($snapshot['diagnostics_page']) ?></code></li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                            <div>
                                <div class="admin-dev-section-title">Failai ir katalogai</div>
                                <div class="d-flex flex-wrap gap-1 mb-2">
                                    <?php foreach ($snapshot['files'] as $label => $present): ?>
                                        <span class="badge <?= $present ? 'text-bg-success' : 'text-bg-secondary' ?> admin-dev-badge"><?= e($label) ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <div class="d-flex flex-wrap gap-1">
                                    <?php foreach ($snapshot['directories'] as $label => $present): ?>
                                        <span class="badge <?= $present ? 'text-bg-primary' : 'text-bg-secondary' ?> admin-dev-badge"><?= e($label) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div>
                                <div class="admin-dev-section-title">Sutartys ir galimybes</div>
                                <div class="d-flex flex-wrap gap-1 mb-2">
                                    <?php foreach ($snapshot['provides'] as $item): ?>
                                        <code class="admin-mono-pill"><?= e($item) ?></code>
                                    <?php endforeach; ?>
                                    <?php if (!$snapshot['provides']): ?>
                                        <span class="text-secondary">Nenurodyta</span>
                                    <?php endif; ?>
                                </div>
                                <div class="small admin-page-subtitle">
                                    Core / PHP: <?= e($snapshot['min_core_version']) ?> / <?= e($snapshot['min_php_version']) ?>
                                </div>
                                <div class="small admin-page-subtitle">
                                    Suderinamumas: <?= e($compatibilitySummary['environment_summary']) ?>
                                </div>
                                <div class="small admin-page-subtitle">
                                    Sveikata: <?= e($healthSummary['summary']) ?>
                                </div>
                                <div class="small admin-page-subtitle">
                                    Leidimai: <?= e($snapshot['permissions'] ? implode(', ', $snapshot['permissions']) : '-') ?>
                                </div>
                                <div class="small admin-page-subtitle">
                                    Admin meniu: <?= e($snapshot['admin_menu'] ? implode(', ', $snapshot['admin_menu']) : '-') ?>
                                </div>
                                <div class="small admin-page-subtitle">
                                    Priklausomybes: <?= e($snapshot['dependencies'] ? implode(', ', $snapshot['dependencies']) : '-') ?>
                                </div>
                                <div class="small admin-page-subtitle">
                                    Konfliktai: <?= e($snapshot['conflicts'] ? implode(', ', $snapshot['conflicts']) : '-') ?>
                                </div>
                                <div class="small admin-page-subtitle">
                                    PHP pletiniai: <?= e($snapshot['required_extensions'] ? implode(', ', $snapshot['required_extensions']) : '-') ?>
                                </div>
                                <div class="small admin-page-subtitle">
                                    Changelog / upgrade / rollback: <?= e((string)count($snapshot['changelog'])) ?> / <?= e((string)count($snapshot['upgrade_notes'])) ?> / <?= e((string)count($snapshot['rollback_notes'])) ?>
                                </div>
                                <div class="small admin-page-subtitle">
                                    Settings contract:
                                    <?php if (!empty($snapshot['settings_contract']['implements'])): ?>
                                        taip | sekcijos <?= (int)$snapshot['settings_contract']['section_count'] ?> | laukai <?= (int)$snapshot['settings_contract']['field_count'] ?> | taisykles <?= (int)$snapshot['settings_contract']['rule_count'] ?>
                                    <?php else: ?>
                                        <?= !empty($snapshot['settings_contract']['error']) ? e('klaida: ' . $snapshot['settings_contract']['error']) : 'neigyvendintas' ?>
                                    <?php endif; ?>
                                </div>
                                <div class="small admin-page-subtitle">
                                    Diagnostics contract:
                                    <?php if (!empty($snapshot['diagnostics_contract']['implements'])): ?>
                                        taip | check'ai <?= (int)$snapshot['diagnostics_contract']['health_check_count'] ?> | problemos <?= (int)$snapshot['diagnostics_contract']['health_issue_count'] ?> | missing files <?= (int)$snapshot['diagnostics_contract']['missing_file_count'] ?> | missing tables <?= (int)$snapshot['diagnostics_contract']['missing_table_count'] ?> | config busenos <?= (int)$snapshot['diagnostics_contract']['configuration_state_count'] ?>
                                    <?php else: ?>
                                        <?= !empty($snapshot['diagnostics_contract']['error']) ? e('klaida: ' . $snapshot['diagnostics_contract']['error']) : 'neigyvendintas' ?>
                                    <?php endif; ?>
                                </div>
                                <div class="small admin-page-subtitle">
                                    Events contract:
                                    <?php if (!empty($snapshot['event_contract']['implements'])): ?>
                                        taip | ivykiai <?= (int)$snapshot['event_contract']['event_count'] ?> | notifications <?= (int)$snapshot['event_contract']['notification_event_count'] ?> | activity feed <?= (int)$snapshot['event_contract']['activity_feed_event_count'] ?> | abu <?= (int)$snapshot['event_contract']['dual_channel_event_count'] ?>
                                    <?php else: ?>
                                        <?= !empty($snapshot['event_contract']['error']) ? e('klaida: ' . $snapshot['event_contract']['error']) : 'neigyvendintas' ?>
                                    <?php endif; ?>
                                </div>
                                <div class="small admin-page-subtitle">
                                    Search contract:
                                    <?php if (!empty($snapshot['search_contract']['implements'])): ?>
                                        taip | saltiniai <?= (int)$snapshot['search_contract']['source_count'] ?> | indeksuojami laukai <?= (int)$snapshot['search_contract']['indexed_field_count'] ?> | URL <?= (int)$snapshot['search_contract']['result_url_count'] ?> | title/summary <?= (int)$snapshot['search_contract']['title_summary_count'] ?> | kategorija/tipas <?= (int)$snapshot['search_contract']['category_type_count'] ?> | permission filter <?= (int)$snapshot['search_contract']['permission_filtered_count'] ?> | svoris <?= (int)$snapshot['search_contract']['weighted_source_count'] ?>
                                    <?php else: ?>
                                        <?= !empty($snapshot['search_contract']['error']) ? e('klaida: ' . $snapshot['search_contract']['error']) : 'neigyvendintas' ?>
                                    <?php endif; ?>
                                </div>
                                <div class="small admin-page-subtitle">
                                    Presentation contract:
                                    <?php if (!empty($snapshot['presentation_contract']['implements'])): ?>
                                        taip | korteles badge <?= (int)$snapshot['presentation_contract']['card_badge_count'] ?> | korteles meta <?= (int)$snapshot['presentation_contract']['card_meta_count'] ?> | korteles santraukos <?= (int)$snapshot['presentation_contract']['card_summary_count'] ?> | detales sekcijos <?= (int)$snapshot['presentation_contract']['detail_section_count'] ?>
                                    <?php else: ?>
                                        <?= !empty($snapshot['presentation_contract']['error']) ? e('klaida: ' . $snapshot['presentation_contract']['error']) : 'neigyvendintas' ?>
                                    <?php endif; ?>
                                </div>
                                <div class="small admin-page-subtitle">
                                    Safe uninstall:
                                    rizika <?= e($snapshot['uninstall_summary']['risk_label'] ?? '-') ?> | priklausomi moduliai <?= (int)($snapshot['uninstall_summary']['dependent_count'] ?? 0) ?> | palies irasus <?= (int)($snapshot['uninstall_summary']['affected_record_count'] ?? 0) ?> | papildomas patvirtinimas <?= !empty($snapshot['uninstall_summary']['requires_confirmation']) ? 'taip' : 'ne' ?>
                                </div>
                            </div>
                        </div>

                        <div class="admin-dev-columns mt-3">
                            <div>
                                <div class="admin-dev-section-title">Migracijos ir asset&apos;ai</div>
                                <div class="small admin-page-subtitle mb-1">Migration step failai</div>
                                <div class="d-flex flex-wrap gap-1 mb-2">
                                    <?php foreach ($snapshot['migration_steps'] as $step): ?>
                                        <code class="admin-mono-pill"><?= e($step) ?></code>
                                    <?php endforeach; ?>
                                    <?php if (!$snapshot['migration_steps']): ?>
                                        <span class="text-secondary">Nera</span>
                                    <?php endif; ?>
                                </div>
                                <div class="small admin-page-subtitle mb-1">Rollback failai</div>
                                <div class="d-flex flex-wrap gap-1 mb-2">
                                    <?php foreach ($snapshot['rollback_files'] as $step): ?>
                                        <code class="admin-mono-pill"><?= e($step) ?></code>
                                    <?php endforeach; ?>
                                    <?php if (!$snapshot['rollback_files']): ?>
                                        <span class="text-secondary">Nera</span>
                                    <?php endif; ?>
                                </div>
                                <div class="small admin-page-subtitle mb-1">CSS / JS / locale</div>
                                <div class="d-flex flex-wrap gap-1">
                                    <?php foreach (array_merge($snapshot['asset_css'], $snapshot['asset_js'], $snapshot['locale_files']) as $assetFile): ?>
                                        <code class="admin-mono-pill"><?= e($assetFile) ?></code>
                                    <?php endforeach; ?>
                                    <?php if (!$snapshot['asset_css'] && !$snapshot['asset_js'] && !$snapshot['locale_files']): ?>
                                        <span class="text-secondary">Nera papildomu asset&#039;u ar locale failu</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div>
                                <div class="admin-dev-section-title">Hook&apos;ai</div>
                                <div class="small admin-page-subtitle mb-1">Deklaruoti manifest</div>
                                <div class="d-flex flex-wrap gap-1 mb-3">
                                    <?php foreach ($snapshot['declared_hooks'] as $hookName): ?>
                                        <code class="admin-mono-pill"><?= e($hookName) ?></code>
                                    <?php endforeach; ?>
                                    <?php if (!$snapshot['declared_hooks']): ?>
                                        <span class="text-secondary">Nera</span>
                                    <?php endif; ?>
                                </div>

                                <div class="small admin-page-subtitle mb-1">Runtime registruoti</div>
                                <?php if ($snapshot['registered_hooks']): ?>
                                    <div class="table-responsive">
                                        <table class="table align-middle mb-0 admin-table-strong admin-dev-hook-table">
                                            <thead><tr><th>Hook</th><th>Priority</th><th>Listener</th></tr></thead>
                                            <tbody>
                                            <?php foreach ($snapshot['registered_hooks'] as $hookInfo): ?>
                                                <tr>
                                                    <td><code class="admin-mono-pill"><?= e($hookInfo['hook']) ?></code></td>
                                                    <td class="admin-table-note"><?= (int)$hookInfo['priority'] ?></td>
                                                    <td>
                                                        <div class="admin-strong-cell"><?= e($hookInfo['listener']) ?></div>
                                                        <?php if (!empty($hookInfo['file'])): ?>
                                                            <div class="small admin-page-subtitle"><?= e($hookInfo['file']) ?><?php if (!empty($hookInfo['line'])): ?>:<?= (int)$hookInfo['line'] ?><?php endif; ?></div>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="text-secondary">Runtime hook&apos;u nerasta arba modulis nebuvo `boot` metu aktyvus.</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mt-3">
                            <div class="admin-dev-section-title">Manifest JSON</div>
                            <pre class="admin-dev-pre"><?= e((string)$snapshot['raw_manifest_json']) ?></pre>
                        </div>
                    </div>
                </details>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

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
