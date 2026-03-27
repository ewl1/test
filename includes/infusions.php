<?php
function infusion_manifest_path($folder) { return INFUSIONS . trim((string)$folder) . '/manifest.json'; }
function infusion_admin_path($folder) { return INFUSIONS . trim((string)$folder) . '/admin.php'; }
function infusion_schema_path($folder) { return INFUSIONS . trim((string)$folder) . '/schema.php'; }
function infusion_upgrade_path($folder) { return INFUSIONS . trim((string)$folder) . '/upgrade.php'; }
function infusion_migrations_dir($folder) { return INFUSIONS . trim((string)$folder) . '/migrations'; }

function read_infusion_manifest($folder)
{
    $path = infusion_manifest_path($folder);
    if (!file_exists($path)) throw new RuntimeException('Manifest nerastas: ' . $folder);

    $data = json_decode(file_get_contents($path), true);
    if (!is_array($data)) throw new RuntimeException('Manifest JSON klaidingas: ' . $folder);

    return [
        'folder' => $folder,
        'name' => $data['name'] ?? ucwords(str_replace(['-','_'], ' ', $folder)),
        'description' => $data['description'] ?? '',
        'version' => $data['version'] ?? '1.0.0',
        'author' => $data['author'] ?? '',
        'website' => $data['website'] ?? '',
        'default_position' => $data['default_position'] ?? 'left',
        'default_panel_name' => $data['default_panel_name'] ?? (($data['name'] ?? $folder) . ' Panel'),
        'admin' => !empty($data['admin']),
        'bootstrap' => !empty($data['bootstrap']),
        'panel' => !empty($data['panel']),
        'schema' => !empty($data['schema']),
        'upgrade' => !empty($data['upgrade']),
        'dependencies' => is_array($data['dependencies'] ?? null) ? $data['dependencies'] : [],
        'permissions' => is_array($data['permissions'] ?? null) ? $data['permissions'] : [],
        'admin_menu' => is_array($data['admin_menu'] ?? null) ? $data['admin_menu'] : [],
        'min_core_version' => $data['min_core_version'] ?? '1.0.0',
    ];
}

function scan_infusions()
{
    $items = [];
    foreach (glob(INFUSIONS . '*', GLOB_ONLYDIR) as $dir) {
        $folder = basename($dir);
        try {
            $manifest = read_infusion_manifest($folder);
        } catch (Throwable $e) {
            $manifest = [
                'folder' => $folder,
                'name' => ucwords(str_replace(['-','_'], ' ', $folder)),
                'description' => 'Manifest nerastas arba klaidingas',
                'version' => '0.0.0',
                'default_position' => 'left',
                'default_panel_name' => ucwords($folder) . ' Panel',
                'admin' => file_exists($dir . '/admin.php'),
                'bootstrap' => file_exists($dir . '/bootstrap.php'),
                'panel' => file_exists($dir . '/panel.php'),
                'schema' => file_exists($dir . '/schema.php'),
                'upgrade' => file_exists($dir . '/upgrade.php'),
                'dependencies' => [],
                'permissions' => [],
                'admin_menu' => [],
                'min_core_version' => '1.0.0',
            ];
        }
        $manifest['directory'] = $dir;
        $manifest['has_admin_file'] = file_exists($dir . '/admin.php');
        $manifest['has_bootstrap_file'] = file_exists($dir . '/bootstrap.php');
        $manifest['has_panel_file'] = file_exists($dir . '/panel.php');
        $manifest['has_schema_file'] = file_exists($dir . '/schema.php');
        $manifest['has_upgrade_file'] = file_exists($dir . '/upgrade.php');
        $manifest['has_migrations_dir'] = is_dir($dir . '/migrations');
        $items[$folder] = $manifest;
    }
    ksort($items);
    return $items;
}

function ensure_infusion_tables()
{
    $GLOBALS['pdo']->exec("
        CREATE TABLE IF NOT EXISTS infusion_versions (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            infusion_id INT UNSIGNED NOT NULL,
            version VARCHAR(50) NOT NULL,
            installed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_infusion_version (infusion_id, version)
        )
    ");
    $GLOBALS['pdo']->exec("
        CREATE TABLE IF NOT EXISTS infusion_admin_menu (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            infusion_id INT UNSIGNED NOT NULL,
            title VARCHAR(120) NOT NULL,
            slug VARCHAR(120) NOT NULL,
            permission_slug VARCHAR(120) DEFAULT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            UNIQUE KEY uniq_infusion_slug (infusion_id, slug)
        )
    ");
    $GLOBALS['pdo']->exec("
        CREATE TABLE IF NOT EXISTS infusion_migration_log (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            infusion_id INT UNSIGNED NOT NULL,
            step_version VARCHAR(50) NOT NULL,
            direction ENUM('up','down') NOT NULL DEFAULT 'up',
            status ENUM('started','done','failed','skipped') NOT NULL DEFAULT 'started',
            started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            finished_at DATETIME DEFAULT NULL,
            message TEXT DEFAULT NULL,
            UNIQUE KEY uniq_infusion_step_direction (infusion_id, step_version, direction)
        )
    ");
    $GLOBALS['pdo']->exec("
        CREATE TABLE IF NOT EXISTS infusion_rollback_log (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            infusion_id INT UNSIGNED NOT NULL,
            failed_step VARCHAR(50) NOT NULL,
            rollback_step VARCHAR(50) DEFAULT NULL,
            status ENUM('started','done','failed','skipped') NOT NULL DEFAULT 'started',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            message TEXT DEFAULT NULL
        )
    ");
}

function get_infusion_core_version()
{
    return app_version();
}

function validate_infusion_dependencies(array $manifest)
{
    foreach ($manifest['dependencies'] as $dep) {
        $folder = trim((string)($dep['folder'] ?? ''));
        $version = trim((string)($dep['version'] ?? ''));
        if ($folder === '') continue;

        $installed = get_installed_infusion_by_folder($folder);
        if (!$installed || (int)$installed['is_installed'] !== 1 || (int)$installed['is_enabled'] !== 1) {
            throw new RuntimeException('Trūksta infusion priklausomybės: ' . $folder);
        }

        if ($version !== '') {
            $installedVersion = get_installed_infusion_version((int)$installed['id']) ?: '0.0.0';
            if (version_compare($installedVersion, $version, '<')) {
                throw new RuntimeException('Priklausomybė ' . $folder . ' turi per seną versiją. Reikia bent ' . $version . '.');
            }
        }
    }

    if (version_compare(get_infusion_core_version(), $manifest['min_core_version'], '<')) {
        throw new RuntimeException('Per sena branduolio versija šiai infusion. Reikia bent ' . $manifest['min_core_version'] . '.');
    }
}

function register_infusion_permissions($infusionId, array $permissions)
{
    foreach ($permissions as $perm) {
        $slug = trim((string)($perm['slug'] ?? ''));
        $name = trim((string)($perm['name'] ?? $slug));
        $description = trim((string)($perm['description'] ?? ''));
        if ($slug === '') continue;

        $stmt = $GLOBALS['pdo']->prepare("
            INSERT INTO permissions (name, slug, description)
            VALUES (:name,:slug,:description)
            ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description)
        ");
        $stmt->execute([':name' => $name, ':slug' => $slug, ':description' => $description]);

        $lookup = $GLOBALS['pdo']->prepare("SELECT id FROM permissions WHERE slug = :slug");
        $lookup->execute([':slug' => $slug]);
        $permissionId = (int)$lookup->fetchColumn();

        if ($permissionId > 0) {
            $grant = $GLOBALS['pdo']->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (1, :pid)");
            $grant->execute([':pid' => $permissionId]);
        }
    }
}

function register_infusion_admin_menu($infusionId, array $items)
{
    foreach ($items as $item) {
        $title = trim((string)($item['title'] ?? ''));
        $slug = trim((string)($item['slug'] ?? ''));
        $permission = trim((string)($item['permission'] ?? ''));
        $sort = (int)($item['sort_order'] ?? 0);
        if ($title === '' || $slug === '') continue;

        $stmt = $GLOBALS['pdo']->prepare("
            INSERT INTO infusion_admin_menu (infusion_id, title, slug, permission_slug, sort_order, is_active)
            VALUES (:iid,:title,:slug,:permission,:sort,1)
            ON DUPLICATE KEY UPDATE title = VALUES(title), permission_slug = VALUES(permission_slug), sort_order = VALUES(sort_order), is_active = 1
        ");
        $stmt->execute([
            ':iid' => (int)$infusionId,
            ':title' => $title,
            ':slug' => $slug,
            ':permission' => $permission !== '' ? $permission : null,
            ':sort' => $sort,
        ]);
    }
}

function get_infusion_admin_menu_items()
{
    $stmt = $GLOBALS['pdo']->query("
        SELECT iam.*, i.folder, i.name AS infusion_name
        FROM infusion_admin_menu iam
        INNER JOIN infusions i ON i.id = iam.infusion_id
        WHERE iam.is_active = 1 AND i.is_installed = 1 AND i.is_enabled = 1
        ORDER BY iam.sort_order ASC, iam.id ASC
    ");
    return $stmt->fetchAll();
}

function get_installed_infusion($id)
{
    $stmt = $GLOBALS['pdo']->prepare("SELECT * FROM infusions WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => (int)$id]);
    return $stmt->fetch();
}

function get_installed_infusion_by_folder($folder)
{
    $stmt = $GLOBALS['pdo']->prepare("SELECT * FROM infusions WHERE folder = :f LIMIT 1");
    $stmt->execute([':f' => trim((string)$folder)]);
    return $stmt->fetch();
}

function get_installed_infusion_version($infusionId)
{
    ensure_infusion_tables();
    $stmt = $GLOBALS['pdo']->prepare("SELECT version FROM infusion_versions WHERE infusion_id = :iid ORDER BY id DESC LIMIT 1");
    $stmt->execute([':iid' => (int)$infusionId]);
    return $stmt->fetchColumn() ?: null;
}

function list_migration_steps($folder)
{
    $dir = infusion_migrations_dir($folder);
    if (!is_dir($dir)) return [];

    $steps = [];
    foreach (glob($dir . '/*.php') as $file) {
        $base = basename($file, '.php');
        if (preg_match('/^(\d+_)?(\d+\.\d+\.\d+)$/', $base, $m)) {
            $version = $m[2];
            $steps[$version] = $file;
        }
    }
    uksort($steps, 'version_compare');
    return $steps;
}

function execute_schema_install($folder, $infusionId, array $manifest)
{
    if ($manifest['schema'] && file_exists(infusion_schema_path($folder))) {
        $INFUSION = ['id' => $infusionId, 'folder' => $folder, 'manifest' => $manifest];
        include infusion_schema_path($folder);
    }
}

function log_migration_step($infusionId, $version, $direction, $status, $message = null)
{
    $stmt = $GLOBALS['pdo']->prepare("
        INSERT INTO infusion_migration_log (infusion_id, step_version, direction, status, started_at, finished_at, message)
        VALUES (:iid,:v,:d,:s,NOW(),NOW(),:m)
        ON DUPLICATE KEY UPDATE status = VALUES(status), finished_at = VALUES(finished_at), message = VALUES(message)
    ");
    $stmt->execute([
        ':iid' => (int)$infusionId,
        ':v' => (string)$version,
        ':d' => (string)$direction,
        ':s' => (string)$status,
        ':m' => $message,
    ]);
}

function log_rollback($infusionId, $failedStep, $rollbackStep, $status, $message = null)
{
    $stmt = $GLOBALS['pdo']->prepare("
        INSERT INTO infusion_rollback_log (infusion_id, failed_step, rollback_step, status, created_at, message)
        VALUES (:iid,:failed,:rollback,:status,NOW(),:message)
    ");
    $stmt->execute([
        ':iid' => (int)$infusionId,
        ':failed' => (string)$failedStep,
        ':rollback' => $rollbackStep !== null ? (string)$rollbackStep : null,
        ':status' => (string)$status,
        ':message' => $message,
    ]);
}

function run_migration_steps($folder, $infusionId, $installedVersion, $targetVersion)
{
    $steps = list_migration_steps($folder);
    $executed = [];

    foreach ($steps as $version => $file) {
        if (version_compare($version, $installedVersion, '>') && version_compare($version, $targetVersion, '<=')) {
            log_migration_step($infusionId, $version, 'up', 'started');
            try {
                $INFUSION = [
                    'id' => (int)$infusionId,
                    'folder' => $folder,
                    'installed_version' => $installedVersion,
                    'target_version' => $targetVersion,
                    'step_version' => $version,
                ];
                include $file;
                log_migration_step($infusionId, $version, 'up', 'done');
                $executed[] = ['version' => $version, 'file' => $file];
            } catch (Throwable $e) {
                log_migration_step($infusionId, $version, 'up', 'failed', $e->getMessage());
                throw new RuntimeException('Migration step nepavyko: ' . $version . ' - ' . $e->getMessage());
            }
        }
    }

    return $executed;
}

function rollback_migration_steps($folder, $infusionId, array $executedSteps)
{
    $dir = infusion_migrations_dir($folder);
    $executedSteps = array_reverse($executedSteps);

    foreach ($executedSteps as $step) {
        $version = $step['version'];
        $rollbackFile = $dir . '/' . $version . '.rollback.php';
        if (!file_exists($rollbackFile)) {
            log_rollback($infusionId, $version, null, 'skipped', 'Rollback failas nerastas');
            continue;
        }

        log_rollback($infusionId, $version, $version, 'started');
        try {
            $INFUSION = [
                'id' => (int)$infusionId,
                'folder' => $folder,
                'step_version' => $version,
            ];
            include $rollbackFile;
            log_migration_step($infusionId, $version, 'down', 'done');
            log_rollback($infusionId, $version, $version, 'done');
        } catch (Throwable $e) {
            log_migration_step($infusionId, $version, 'down', 'failed', $e->getMessage());
            log_rollback($infusionId, $version, $version, 'failed', $e->getMessage());
        }
    }
}

function install_infusion_from_folder($folder)
{
    $folder = trim((string)$folder);
    $path = INFUSIONS . $folder;
    if (!is_dir($path)) throw new RuntimeException('Infusion katalogas nerastas.');

    ensure_infusion_tables();
    $manifest = read_infusion_manifest($folder);
    validate_infusion_dependencies($manifest);

    $GLOBALS['pdo']->beginTransaction();
    try {
        $stmt = $GLOBALS['pdo']->prepare("
            INSERT INTO infusions (name, folder, is_installed, is_enabled, created_at)
            VALUES (:n,:f,1,1,NOW())
            ON DUPLICATE KEY UPDATE name=VALUES(name), is_installed=1, is_enabled=1
        ");
        $stmt->execute([':n' => $manifest['name'], ':f' => $folder]);

        $idStmt = $GLOBALS['pdo']->prepare("SELECT id FROM infusions WHERE folder = :f LIMIT 1");
        $idStmt->execute([':f' => $folder]);
        $infusionId = (int)$idStmt->fetchColumn();

        execute_schema_install($folder, $infusionId, $manifest);
        register_infusion_permissions($infusionId, $manifest['permissions']);
        register_infusion_admin_menu($infusionId, $manifest['admin_menu']);

        $versionStmt = $GLOBALS['pdo']->prepare("INSERT IGNORE INTO infusion_versions (infusion_id, version) VALUES (:iid,:v)");
        $versionStmt->execute([':iid' => $infusionId, ':v' => $manifest['version']]);

        if ($manifest['panel'] && file_exists($path . '/panel.php')) {
            $exists = $GLOBALS['pdo']->prepare("SELECT COUNT(*) FROM infusion_panels WHERE infusion_id = :iid");
            $exists->execute([':iid' => $infusionId]);
            if ((int)$exists->fetchColumn() === 0) {
                $panelStmt = $GLOBALS['pdo']->prepare("
                    INSERT INTO infusion_panels (infusion_id, panel_name, position, sort_order, is_enabled)
                    VALUES (:iid,:n,:p,999,1)
                ");
                $panelStmt->execute([
                    ':iid' => $infusionId,
                    ':n' => $manifest['default_panel_name'],
                    ':p' => $manifest['default_position'],
                ]);
            }
        }

        if ($GLOBALS['pdo']->inTransaction()) {
            $GLOBALS['pdo']->commit();
        }
        return $infusionId;
    } catch (Throwable $e) {
        if ($GLOBALS['pdo']->inTransaction()) $GLOBALS['pdo']->rollBack();
        throw $e;
    }
}

function upgrade_infusion_by_id($id)
{
    ensure_infusion_tables();
    $infusion = get_installed_infusion($id);
    if (!$infusion) throw new RuntimeException('Infusion nerasta.');

    $manifest = read_infusion_manifest($infusion['folder']);
    validate_infusion_dependencies($manifest);

    $installedVersion = get_installed_infusion_version($id) ?: '0.0.0';
    $targetVersion = $manifest['version'];

    if (version_compare($targetVersion, $installedVersion, '<=')) {
        register_infusion_permissions((int)$id, $manifest['permissions']);
        register_infusion_admin_menu((int)$id, $manifest['admin_menu']);
        return ['upgraded' => false, 'from' => $installedVersion, 'to' => $targetVersion];
    }

    $executedSteps = [];
    $GLOBALS['pdo']->beginTransaction();
    try {
        $migrationDir = infusion_migrations_dir($infusion['folder']);
        if (is_dir($migrationDir)) {
            $executedSteps = run_migration_steps($infusion['folder'], (int)$id, $installedVersion, $targetVersion);
        } else {
            $upgradeFile = infusion_upgrade_path($infusion['folder']);
            if (!file_exists($upgradeFile)) {
                throw new RuntimeException('Upgrade failas nerastas.');
            }
            $INFUSION = [
                'id' => (int)$id,
                'folder' => $infusion['folder'],
                'manifest' => $manifest,
                'installed_version' => $installedVersion,
                'target_version' => $targetVersion,
            ];
            include $upgradeFile;
        }

        register_infusion_permissions((int)$id, $manifest['permissions']);
        register_infusion_admin_menu((int)$id, $manifest['admin_menu']);

        $stmt = $GLOBALS['pdo']->prepare("INSERT INTO infusion_versions (infusion_id, version) VALUES (:iid,:v)");
        $stmt->execute([':iid' => (int)$id, ':v' => $targetVersion]);

        if ($GLOBALS['pdo']->inTransaction()) {
            $GLOBALS['pdo']->commit();
        }
        return ['upgraded' => true, 'from' => $installedVersion, 'to' => $targetVersion, 'steps' => array_map(fn($s) => $s['version'], $executedSteps)];
    } catch (Throwable $e) {
        if ($GLOBALS['pdo']->inTransaction()) $GLOBALS['pdo']->rollBack();
        try {
            rollback_migration_steps($infusion['folder'], (int)$id, $executedSteps);
        } catch (Throwable $re) {}
        throw $e;
    }
}

function uninstall_infusion_by_id($id)
{
    ensure_infusion_tables();
    $infusion = get_installed_infusion($id);
    if (!$infusion) throw new RuntimeException('Infusion nerasta.');
    $path = INFUSIONS . $infusion['folder'];

    $GLOBALS['pdo']->beginTransaction();
    try {
        if (file_exists($path . '/uninstall.php')) {
            $INFUSION = ['id' => (int)$id, 'folder' => $infusion['folder']];
            include $path . '/uninstall.php';
        }
        $GLOBALS['pdo']->prepare("DELETE FROM infusion_admin_menu WHERE infusion_id = :id")->execute([':id' => (int)$id]);
        $GLOBALS['pdo']->prepare("DELETE FROM infusion_panels WHERE infusion_id = :id")->execute([':id' => (int)$id]);
        $GLOBALS['pdo']->prepare("DELETE FROM infusion_versions WHERE infusion_id = :id")->execute([':id' => (int)$id]);
        $GLOBALS['pdo']->prepare("DELETE FROM infusion_migration_log WHERE infusion_id = :id")->execute([':id' => (int)$id]);
        $GLOBALS['pdo']->prepare("DELETE FROM infusion_rollback_log WHERE infusion_id = :id")->execute([':id' => (int)$id]);
        $GLOBALS['pdo']->prepare("DELETE FROM infusions WHERE id = :id")->execute([':id' => (int)$id]);
        if ($GLOBALS['pdo']->inTransaction()) {
            $GLOBALS['pdo']->commit();
        }
    } catch (Throwable $e) {
        if ($GLOBALS['pdo']->inTransaction()) $GLOBALS['pdo']->rollBack();
        throw $e;
    }
}

function load_enabled_infusions()
{
    $stmt = $GLOBALS['pdo']->query("SELECT * FROM infusions WHERE is_installed = 1 AND is_enabled = 1 ORDER BY id ASC");
    foreach ($stmt->fetchAll() as $infusion) {
        $bootstrap = INFUSIONS . $infusion['folder'] . '/bootstrap.php';
        if (file_exists($bootstrap)) include_once $bootstrap;
    }
}

function render_infusion_admin($folder)
{
    $path = infusion_admin_path($folder);
    if (!file_exists($path)) throw new RuntimeException('Infusion admin failas nerastas.');
    include $path;
}
