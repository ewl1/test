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
            flash('success', 'Infusion įdiegta iš failų sistemos.');
            redirect('infusions.php');
        }

        if (in_array($action, ['enable', 'disable'], true)) {
            $id = (int)($_POST['id'] ?? 0);
            $enabled = $action === 'enable' ? 1 : 0;
            $GLOBALS['pdo']->prepare("UPDATE infusions SET is_enabled = :e WHERE id = :id")->execute([':e' => $enabled, ':id' => $id]);
            audit_log(current_user()['id'], 'infusion_' . $action, 'infusions', $id);
            flash('success', 'Infusion būsena pakeista.');
            redirect('infusions.php');
        }

        if ($action === 'upgrade') {
            $id = (int)($_POST['id'] ?? 0);
            $result = upgrade_infusion_by_id($id);
            $msg = !empty($result['upgraded'])
                ? 'Infusion atnaujinta iš ' . $result['from'] . ' į ' . $result['to'] . (!empty($result['steps']) ? ' | steps: ' . implode(', ', $result['steps']) : '')
                : 'Atnaujinimas nereikalingas. Versija ' . $result['to'];
            flash('success', $msg . '.');
            audit_log(current_user()['id'], 'infusion_upgrade', 'infusions', $id, $result);
            redirect('infusions.php');
        }

        if ($action === 'uninstall') {
            $id = (int)($_POST['id'] ?? 0);
            uninstall_infusion_by_id($id);
            audit_log(current_user()['id'], 'infusion_uninstall', 'infusions', $id);
            flash('success', 'Infusion pašalinta.');
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

include THEMES . 'default/admin_header.php';
?>
<h1 class="h3 mb-3">Infusions lifecycle</h1>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<?php if ($msg = flash('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">Failų sistemoje rastos infusions</div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Folder</th><th>Pavadinimas</th><th>Versija</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($scanned as $folder => $meta): ?>
                        <tr>
                            <td><code class="admin-mono-pill"><?= e($folder) ?></code></td>
                            <td>
                                <div class="fw-semibold"><?= e($meta['name']) ?></div>
                                <div class="small admin-table-description"><?= e($meta['description'] ?? '') ?></div>
                            </td>
                            <td><span class="admin-table-note"><?= e($meta['version'] ?? '0.0.0') ?></span></td>
                            <td>
                                <?php if (isset($installedFolders[$folder])): ?>
                                    <span class="badge text-bg-success">Installed</span>
                                <?php else: ?>
                                    <form method="post">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="install_folder">
                                        <input type="hidden" name="folder" value="<?= e($folder) ?>">
                                        <button class="btn btn-sm btn-primary">Install</button>
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
            <div class="card-header">Įdiegtos infusions</div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>ID</th><th>Pavadinimas</th><th>Folder</th><th>Įdiegta</th><th>Manifest</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($installed as $inf):
                        $manifest = $scanned[$inf['folder']] ?? null;
                        $installedVersion = get_installed_infusion_version((int)$inf['id']) ?: '0.0.0';
                        $manifestVersion = $manifest['version'] ?? $installedVersion;
                    ?>
                        <tr>
                            <td><?= (int)$inf['id'] ?></td>
                            <td><span class="fw-semibold"><?= e($inf['name']) ?></span></td>
                            <td><code class="admin-mono-pill"><?= e($inf['folder']) ?></code></td>
                            <td><span class="admin-table-note"><?= e($installedVersion) ?></span></td>
                            <td><span class="admin-table-note"><?= e($manifestVersion) ?></span></td>
                            <td>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php if (!empty($manifest['admin']) && !empty($manifest['has_admin_file'])): ?>
                                        <a class="btn btn-sm btn-outline-primary" href="infusion-admin.php?folder=<?= urlencode($inf['folder']) ?>">Admin</a>
                                    <?php endif; ?>
                                    <form method="post" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int)$inf['id'] ?>">
                                        <?php if ((int)$inf['is_enabled']): ?>
                                            <button class="btn btn-sm btn-outline-warning" name="action" value="disable">Disable</button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-success" name="action" value="enable">Enable</button>
                                        <?php endif; ?>
                                    </form>
                                    <?php if (version_compare($manifestVersion, $installedVersion, '>')): ?>
                                        <form method="post" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int)$inf['id'] ?>">
                                            <button class="btn btn-sm btn-outline-primary" name="action" value="upgrade">Upgrade</button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="post" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int)$inf['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger" name="action" value="uninstall" data-confirm-message="Tikrai pašalinti infusion?">Uninstall</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$installed): ?>
                        <tr><td colspan="6" class="text-secondary">Kol kas nėra įdiegtų infusions.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include THEMES . 'default/admin_footer.php'; ?>
