<?php
function infusion_manifest_path($folder) { return INFUSIONS . trim((string)$folder) . '/manifest.json'; }
function infusion_admin_path($folder) { return INFUSIONS . trim((string)$folder) . '/admin.php'; }
function infusion_schema_path($folder) { return INFUSIONS . trim((string)$folder) . '/schema.php'; }
function infusion_upgrade_path($folder) { return INFUSIONS . trim((string)$folder) . '/upgrade.php'; }
function infusion_migrations_dir($folder) { return INFUSIONS . trim((string)$folder) . '/migrations'; }

function infusion_hook_registry()
{
    return \App\MiniCMS\Infusions\InfusionSdk::hooks();
}

function infusion_add_hook($name, callable $listener, $priority = 10)
{
    infusion_hook_registry()->add((string)$name, $listener, (int)$priority);
}

function infusion_do_hook($name, $payload = null, array $context = [])
{
    return infusion_hook_registry()->dispatch((string)$name, $payload, $context);
}

function infusion_apply_filters($name, $value, array $context = [])
{
    return infusion_hook_registry()->filter((string)$name, $value, $context);
}

function infusion_fire_lifecycle_hooks($stage, array $payload = [])
{
    $stage = trim((string)$stage);
    if ($stage === '') {
        return $payload;
    }

    $folder = trim((string)($payload['folder'] ?? ''));
    $context = [
        'stage' => $stage,
        'folder' => $folder,
        'infusion_id' => (int)($payload['infusion_id'] ?? 0),
        'manifest' => $payload['manifest'] ?? null,
        'installed_version' => $payload['installed_version'] ?? null,
        'target_version' => $payload['target_version'] ?? null,
        'operation_result' => $payload['result'] ?? null,
    ];

    $result = infusion_do_hook($stage, $payload, $context);
    if (is_array($result)) {
        $payload = $result;
        $context['manifest'] = $payload['manifest'] ?? $context['manifest'];
        $context['installed_version'] = $payload['installed_version'] ?? $context['installed_version'];
        $context['target_version'] = $payload['target_version'] ?? $context['target_version'];
        $context['operation_result'] = $payload['result'] ?? $context['operation_result'];
    }

    if ($folder !== '') {
        $result = infusion_do_hook($stage . '.' . $folder, $payload, $context);
        if (is_array($result)) {
            $payload = $result;
        }
    }

    return $payload;
}

function infusion_context($folder, $infusionId = 0, ?array $manifest = null)
{
    return \App\MiniCMS\Infusions\InfusionSdk::context((string)$folder, (int)$infusionId, $manifest);
}

function infusion_sdk_module($folder, $infusionId = 0, ?array $manifest = null)
{
    return \App\MiniCMS\Infusions\InfusionSdk::module((string)$folder, (int)$infusionId, $manifest);
}

function read_infusion_manifest($folder)
{
    return \App\MiniCMS\Infusions\InfusionSdk::manifest((string)$folder)->toArray();
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
                'conflicts' => [],
                'permissions' => [],
                'admin_menu' => [],
                'min_core_version' => '1.0.0',
                'min_php_version' => '8.0.0',
                'required_extensions' => [],
                'provides' => [],
                'changelog' => [],
                'upgrade_notes' => [],
                'rollback_notes' => [],
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

function read_infusion_manifest_raw($folder)
{
    $path = infusion_manifest_path($folder);
    if (!is_file($path)) {
        return null;
    }

    $data = json_decode((string)file_get_contents($path), true);
    return is_array($data) ? $data : null;
}

function infusion_developer_normalize_path($path)
{
    return str_replace('\\', '/', strtolower((string)$path));
}

function describe_infusion_hook_listener(callable $listener)
{
    $info = [
        'label' => 'Closure',
        'class' => null,
        'scope_class' => null,
        'method' => null,
        'file' => null,
        'line' => null,
        'type' => 'callable',
    ];

    try {
        if (is_array($listener) && isset($listener[0], $listener[1])) {
            $target = $listener[0];
            $method = (string)$listener[1];
            $class = is_object($target) ? get_class($target) : trim((string)$target);
            $info['class'] = $class !== '' ? $class : null;
            $info['method'] = $method !== '' ? $method : null;
            $info['label'] = $class !== ''
                ? (is_object($target) ? $class . '->' . $method : $class . '::' . $method)
                : $method;
            $info['type'] = is_object($target) ? 'object_method' : 'static_method';

            $reflection = new ReflectionMethod($target, $method);
            $info['file'] = $reflection->getFileName() ?: null;
            $info['line'] = $reflection->getStartLine() ?: null;
            return $info;
        }

        if ($listener instanceof Closure) {
            $reflection = new ReflectionFunction($listener);
            $scopeClass = $reflection->getClosureScopeClass();
            $closureThis = $reflection->getClosureThis();

            $info['type'] = 'closure';
            $info['scope_class'] = $scopeClass ? $scopeClass->getName() : null;
            $info['class'] = is_object($closureThis) ? get_class($closureThis) : $info['scope_class'];
            $info['label'] = $info['class'] ? $info['class'] . '::{closure}' : 'Closure';
            $info['file'] = $reflection->getFileName() ?: null;
            $info['line'] = $reflection->getStartLine() ?: null;
            return $info;
        }

        if (is_string($listener) && strpos($listener, '::') !== false) {
            [$class, $method] = explode('::', $listener, 2);
            $info['class'] = $class !== '' ? $class : null;
            $info['method'] = $method !== '' ? $method : null;
            $info['label'] = $listener;
            $info['type'] = 'static_string';

            $reflection = new ReflectionMethod($class, $method);
            $info['file'] = $reflection->getFileName() ?: null;
            $info['line'] = $reflection->getStartLine() ?: null;
            return $info;
        }

        if (is_object($listener) && is_callable($listener)) {
            $class = get_class($listener);
            $info['class'] = $class;
            $info['method'] = '__invoke';
            $info['label'] = $class . '::__invoke';
            $info['type'] = 'invokable_object';

            $reflection = new ReflectionMethod($listener, '__invoke');
            $info['file'] = $reflection->getFileName() ?: null;
            $info['line'] = $reflection->getStartLine() ?: null;
            return $info;
        }

        if (is_string($listener)) {
            $info['label'] = $listener;
            $info['type'] = 'function';

            $reflection = new ReflectionFunction($listener);
            $info['file'] = $reflection->getFileName() ?: null;
            $info['line'] = $reflection->getStartLine() ?: null;
            return $info;
        }
    } catch (Throwable $e) {
    }

    return $info;
}

function infusion_hook_listener_matches_module(array $listenerInfo, $folder, $moduleClass, $moduleNamespace)
{
    $folderRoot = infusion_developer_normalize_path(INFUSIONS . trim((string)$folder) . '/');
    $classCandidates = array_filter([
        $listenerInfo['class'] ?? null,
        $listenerInfo['scope_class'] ?? null,
    ]);

    foreach ($classCandidates as $candidate) {
        if ($moduleClass !== '' && $candidate === $moduleClass) {
            return true;
        }
        if ($moduleNamespace !== '' && str_starts_with($candidate, $moduleNamespace)) {
            return true;
        }
    }

    $listenerFile = trim((string)($listenerInfo['file'] ?? ''));
    if ($listenerFile !== '') {
        return str_starts_with(infusion_developer_normalize_path($listenerFile), $folderRoot);
    }

    return false;
}

function get_infusion_registered_hooks($folder, $infusionId = 0, ?array $manifest = null)
{
    $context = infusion_context($folder, (int)$infusionId, $manifest);
    $moduleClass = trim((string)$context->moduleClass());
    $moduleNamespace = trim((string)$context->moduleNamespace());
    $registered = [];

    foreach (infusion_hook_registry()->all() as $hookName => $listenersByPriority) {
        foreach ($listenersByPriority as $priority => $listeners) {
            foreach ($listeners as $listener) {
                $info = describe_infusion_hook_listener($listener);
                if (!infusion_hook_listener_matches_module($info, $folder, $moduleClass, $moduleNamespace)) {
                    continue;
                }

                $registered[] = [
                    'hook' => (string)$hookName,
                    'priority' => (int)$priority,
                    'listener' => $info['label'],
                    'file' => $info['file'],
                    'line' => $info['line'],
                    'type' => $info['type'],
                ];
            }
        }
    }

    usort($registered, static function ($left, $right) {
        return [$left['hook'], $left['priority'], $left['listener']] <=> [$right['hook'], $right['priority'], $right['listener']];
    });

    return $registered;
}

function count_infusion_settings_schema_fields(array $schema)
{
    $count = 0;

    foreach ($schema as $field) {
        if (is_array($field) && isset($field['fields']) && is_array($field['fields'])) {
            $count += count_infusion_settings_schema_fields($field['fields']);
            continue;
        }

        if (is_array($field) && !empty($field)) {
            $count++;
            continue;
        }

        if (!is_array($field) && trim((string)$field) !== '') {
            $count++;
        }
    }

    return $count;
}

function get_infusion_settings_contract_summary($folder, $infusionId = 0, ?array $manifest = null)
{
    $summary = [
        'implements' => false,
        'interface' => \App\MiniCMS\Infusions\ModuleSettingsContract::class,
        'section_count' => 0,
        'field_count' => 0,
        'rule_count' => 0,
        'sections' => [],
        'schema' => [],
        'rules' => [],
        'error' => null,
    ];

    try {
        $module = infusion_sdk_module($folder, (int)$infusionId, $manifest);
    } catch (Throwable $e) {
        $summary['error'] = $e->getMessage();
        return $summary;
    }

    if (!$module instanceof \App\MiniCMS\Infusions\ModuleSettingsContract) {
        return $summary;
    }

    $sections = (array)$module->settingsSections();
    $schema = (array)$module->settingsFormSchema();
    $rules = (array)$module->settingsValidationRules();

    $summary['implements'] = true;
    $summary['sections'] = $sections;
    $summary['schema'] = $schema;
    $summary['rules'] = $rules;
    $summary['section_count'] = count($sections);
    $summary['field_count'] = count_infusion_settings_schema_fields($schema);
    $summary['rule_count'] = count($rules);

    return $summary;
}

function count_infusion_diagnostics_issues(array $items)
{
    $issueCount = 0;

    foreach ($items as $item) {
        $status = strtolower(trim((string)($item['status'] ?? $item['state'] ?? 'ok')));
        if ($status === '' || in_array($status, ['ok', 'pass', 'passed', 'success', 'healthy', 'info'], true)) {
            continue;
        }

        $issueCount++;
    }

    return $issueCount;
}

function get_infusion_diagnostics_contract_summary($folder, $infusionId = 0, ?array $manifest = null)
{
    $summary = [
        'implements' => false,
        'interface' => \App\MiniCMS\Infusions\ModuleDiagnosticsContract::class,
        'health_checks' => [],
        'missing_files' => [],
        'missing_tables' => [],
        'configuration_states' => [],
        'health_check_count' => 0,
        'health_issue_count' => 0,
        'missing_file_count' => 0,
        'missing_table_count' => 0,
        'configuration_state_count' => 0,
        'configuration_issue_count' => 0,
        'error' => null,
    ];

    try {
        $module = infusion_sdk_module($folder, (int)$infusionId, $manifest);
    } catch (Throwable $e) {
        $summary['error'] = $e->getMessage();
        return $summary;
    }

    if (!$module instanceof \App\MiniCMS\Infusions\ModuleDiagnosticsContract) {
        return $summary;
    }

    $healthChecks = (array)$module->diagnosticsHealthChecks();
    $missingFiles = (array)$module->diagnosticsMissingFiles();
    $missingTables = (array)$module->diagnosticsMissingTables();
    $configurationStates = (array)$module->diagnosticsConfigurationStates();

    $summary['implements'] = true;
    $summary['health_checks'] = $healthChecks;
    $summary['missing_files'] = $missingFiles;
    $summary['missing_tables'] = $missingTables;
    $summary['configuration_states'] = $configurationStates;
    $summary['health_check_count'] = count($healthChecks);
    $summary['health_issue_count'] = count_infusion_diagnostics_issues($healthChecks);
    $summary['missing_file_count'] = count($missingFiles);
    $summary['missing_table_count'] = count($missingTables);
    $summary['configuration_state_count'] = count($configurationStates);
    $summary['configuration_issue_count'] = count_infusion_diagnostics_issues($configurationStates);

    return $summary;
}

function get_infusion_developer_snapshot($folder, $infusionId = 0, ?array $manifest = null)
{
    $folder = trim((string)$folder);
    $manifest = $manifest ?: read_infusion_manifest($folder);
    $context = infusion_context($folder, (int)$infusionId, $manifest);
    $rawManifest = read_infusion_manifest_raw($folder);
    $directory = rtrim($context->path(), '/\\');
    $installed = $infusionId > 0 ? get_installed_infusion((int)$infusionId) : get_installed_infusion_by_folder($folder);
    $migrationFiles = array_map('basename', glob($context->migrationsPath('*.php')) ?: []);
    sort($migrationFiles);

    $rollbackFiles = array_values(array_filter($migrationFiles, static function ($file) {
        return str_ends_with($file, '.rollback.php');
    }));
    $migrationSteps = array_values(array_filter($migrationFiles, static function ($file) {
        return !str_ends_with($file, '.rollback.php');
    }));

    $assetCss = array_map('basename', glob($context->path('assets/css/*.css')) ?: []);
    $assetJs = array_map('basename', glob($context->path('assets/js/*.js')) ?: []);
    $localeFiles = array_map('basename', glob($context->path('locale/*.php')) ?: []);
    sort($assetCss);
    sort($assetJs);
    sort($localeFiles);

    $moduleClass = $context->moduleClass();
    $moduleNamespace = $context->moduleNamespace();
    $classExists = class_exists($moduleClass);
    $isSdkModule = $classExists && is_subclass_of($moduleClass, \App\MiniCMS\Infusions\InfusionModuleInterface::class);

    $permissions = [];
    foreach ((array)($manifest['permissions'] ?? []) as $permission) {
        $slug = trim((string)($permission['slug'] ?? ''));
        if ($slug !== '') {
            $permissions[] = $slug;
        }
    }

    $adminMenu = [];
    foreach ((array)($manifest['admin_menu'] ?? []) as $menuItem) {
        $slug = trim((string)($menuItem['slug'] ?? ''));
        if ($slug !== '') {
            $adminMenu[] = $slug;
        }
    }

    $dependencies = [];
    foreach ((array)($manifest['dependencies'] ?? []) as $dependency) {
        if (is_array($dependency)) {
            $folderName = trim((string)($dependency['folder'] ?? ''));
            $version = trim((string)($dependency['version'] ?? ''));
            if ($folderName !== '') {
                $dependencies[] = $version !== '' ? ($folderName . ' >= ' . $version) : $folderName;
            }
            continue;
        }

        $dependency = trim((string)$dependency);
        if ($dependency !== '') {
            $dependencies[] = $dependency;
        }
    }

    $conflicts = [];
    foreach ((array)($manifest['conflicts'] ?? []) as $conflict) {
        if (is_array($conflict)) {
            $folderName = trim((string)($conflict['folder'] ?? ''));
            $version = trim((string)($conflict['version'] ?? ''));
            if ($folderName !== '') {
                $conflicts[] = $version !== '' ? ($folderName . ' @ ' . $version) : $folderName;
            }
            continue;
        }

        $conflict = trim((string)$conflict);
        if ($conflict !== '') {
            $conflicts[] = $conflict;
        }
    }

    $declaredHooks = [];
    foreach ((array)($rawManifest['hooks'] ?? $manifest['hooks'] ?? []) as $key => $value) {
        if (is_int($key)) {
            $hookName = trim((string)$value);
            if ($hookName !== '') {
                $declaredHooks[] = $hookName;
            }
            continue;
        }

        $hookName = trim((string)$key);
        if ($hookName !== '') {
            $declaredHooks[] = $hookName;
        }
    }
    sort($declaredHooks);

    $provides = [];
    foreach ((array)($rawManifest['provides'] ?? $manifest['provides'] ?? []) as $key => $value) {
        if (is_int($key)) {
            $value = trim((string)$value);
            if ($value !== '') {
                $provides[] = $value;
            }
            continue;
        }

        if (is_array($value)) {
            foreach ($value as $nestedValue) {
                $nestedValue = trim((string)$nestedValue);
                if ($nestedValue !== '') {
                    $provides[] = $key . ':' . $nestedValue;
                }
            }
            continue;
        }

        $value = trim((string)$value);
        if ($value !== '') {
            $provides[] = $key . ':' . $value;
        } else {
            $provides[] = (string)$key;
        }
    }
    sort($provides);

    return [
        'folder' => $folder,
        'directory' => $directory,
        'installed' => $installed ?: null,
        'manifest' => $manifest,
        'raw_manifest' => $rawManifest,
        'raw_manifest_json' => json_encode($rawManifest ?: $manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        'module_class' => $moduleClass,
        'module_namespace' => $moduleNamespace,
        'module_class_exists' => $classExists,
        'is_sdk_module' => $isSdkModule,
        'files' => [
            'manifest.json' => is_file($context->path('manifest.json')),
            'bootstrap.php' => $context->has('bootstrap.php'),
            'admin.php' => $context->has('admin.php'),
            'panel.php' => $context->has('panel.php'),
            'schema.php' => $context->has('schema.php'),
            'upgrade.php' => $context->has('upgrade.php'),
            'uninstall.php' => $context->has('uninstall.php'),
        ],
        'directories' => [
            'assets/' => $context->hasDirectory('assets'),
            'locale/' => $context->hasDirectory('locale'),
            'classes/' => $context->hasDirectory('classes'),
            'migrations/' => $context->hasDirectory('migrations'),
        ],
        'migration_steps' => $migrationSteps,
        'rollback_files' => $rollbackFiles,
        'asset_css' => $assetCss,
        'asset_js' => $assetJs,
        'locale_files' => $localeFiles,
        'permissions' => $permissions,
        'admin_menu' => $adminMenu,
        'dependencies' => $dependencies,
        'conflicts' => $conflicts,
        'required_extensions' => array_values((array)($manifest['required_extensions'] ?? [])),
        'declared_hooks' => $declaredHooks,
        'registered_hooks' => get_infusion_registered_hooks($folder, (int)($installed['id'] ?? $infusionId), $manifest),
        'provides' => $provides,
        'min_core_version' => trim((string)($manifest['min_core_version'] ?? '1.0.0')),
        'min_php_version' => trim((string)($manifest['min_php_version'] ?? '8.0.0')),
        'changelog' => (array)($manifest['changelog'] ?? []),
        'upgrade_notes' => (array)($manifest['upgrade_notes'] ?? []),
        'rollback_notes' => (array)($manifest['rollback_notes'] ?? []),
        'settings_page' => trim((string)($rawManifest['settings_page'] ?? $manifest['settings_page'] ?? '')),
        'diagnostics_page' => trim((string)($rawManifest['diagnostics_page'] ?? '')),
        'settings_contract' => get_infusion_settings_contract_summary($folder, (int)($installed['id'] ?? $infusionId), $manifest),
        'diagnostics_contract' => get_infusion_diagnostics_contract_summary($folder, (int)($installed['id'] ?? $infusionId), $manifest),
    ];
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
    $GLOBALS['pdo']->exec("
        CREATE TABLE IF NOT EXISTS infusion_migration_state (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            lock_name VARCHAR(191) NOT NULL,
            operation VARCHAR(32) NOT NULL,
            resource VARCHAR(191) DEFAULT NULL,
            infusion_id INT UNSIGNED DEFAULT NULL,
            folder VARCHAR(120) DEFAULT NULL,
            admin_user_id INT UNSIGNED DEFAULT NULL,
            owner_connection_id BIGINT UNSIGNED DEFAULT NULL,
            started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            heartbeat_at DATETIME DEFAULT NULL,
            details TEXT DEFAULT NULL,
            UNIQUE KEY uniq_infusion_migration_state_lock (lock_name),
            KEY idx_infusion_migration_state_owner (owner_connection_id),
            KEY idx_infusion_migration_state_started (started_at)
        )
    ");
}

function infusion_migration_lock_name()
{
    static $lockName = null;
    if ($lockName !== null) {
        return $lockName;
    }

    $databaseName = 'default';
    try {
        $resolved = (string)$GLOBALS['pdo']->query('SELECT DATABASE()')->fetchColumn();
        if ($resolved !== '') {
            $databaseName = $resolved;
        }
    } catch (Throwable $e) {
    }

    $databaseName = preg_replace('/[^a-z0-9_:-]+/i', '_', $databaseName);
    $lockName = 'minicms:' . $databaseName . ':infusion-migrations';
    return $lockName;
}

function current_infusion_db_connection_id()
{
    try {
        return (int)$GLOBALS['pdo']->query('SELECT CONNECTION_ID()')->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

function resolve_infusion_migration_resource_context($resource)
{
    $resource = trim((string)$resource);
    $context = [
        'infusion_id' => null,
        'folder' => null,
        'details' => [],
    ];

    if ($resource === '') {
        return $context;
    }

    if (preg_match('/^infusion:(\d+)$/', $resource, $matches)) {
        $infusionId = (int)$matches[1];
        $context['infusion_id'] = $infusionId;
        $infusion = get_installed_infusion($infusionId);
        if ($infusion) {
            $context['folder'] = (string)($infusion['folder'] ?? '');
            $context['details']['infusion_name'] = (string)($infusion['name'] ?? '');
        }
        return $context;
    }

    if (preg_match('/^folder:(.+)$/', $resource, $matches)) {
        $folder = trim((string)$matches[1]);
        $context['folder'] = $folder !== '' ? $folder : null;
        $infusion = $folder !== '' ? get_installed_infusion_by_folder($folder) : null;
        if ($infusion) {
            $context['infusion_id'] = (int)$infusion['id'];
            $context['details']['infusion_name'] = (string)($infusion['name'] ?? '');
        }
        return $context;
    }

    $context['details']['resource'] = $resource;
    return $context;
}

function save_infusion_migration_lock_state($lockName, $operation, $resource = '')
{
    ensure_infusion_tables();

    $context = resolve_infusion_migration_resource_context($resource);
    $details = $context['details'] ?? [];
    if ($resource !== '') {
        $details['resource'] = $resource;
    }

    try {
        $stmt = $GLOBALS['pdo']->prepare("
            INSERT INTO infusion_migration_state
                (lock_name, operation, resource, infusion_id, folder, admin_user_id, owner_connection_id, started_at, heartbeat_at, details)
            VALUES
                (:lock_name, :operation, :resource, :infusion_id, :folder, :admin_user_id, :owner_connection_id, NOW(), NOW(), :details)
            ON DUPLICATE KEY UPDATE
                operation = VALUES(operation),
                resource = VALUES(resource),
                infusion_id = VALUES(infusion_id),
                folder = VALUES(folder),
                admin_user_id = VALUES(admin_user_id),
                owner_connection_id = VALUES(owner_connection_id),
                started_at = VALUES(started_at),
                heartbeat_at = VALUES(heartbeat_at),
                details = VALUES(details)
        ");
        $stmt->execute([
            ':lock_name' => (string)$lockName,
            ':operation' => trim((string)$operation),
            ':resource' => $resource !== '' ? $resource : null,
            ':infusion_id' => $context['infusion_id'] !== null ? (int)$context['infusion_id'] : null,
            ':folder' => $context['folder'] !== null ? (string)$context['folder'] : null,
            ':admin_user_id' => current_user()['id'] ?? null,
            ':owner_connection_id' => current_infusion_db_connection_id() ?: null,
            ':details' => $details ? json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
        ]);
    } catch (Throwable $e) {
    }
}

function clear_infusion_migration_lock_state($lockName)
{
    ensure_infusion_tables();

    try {
        $stmt = $GLOBALS['pdo']->prepare('DELETE FROM infusion_migration_state WHERE lock_name = :lock_name');
        $stmt->execute([':lock_name' => (string)$lockName]);
    } catch (Throwable $e) {
    }
}

function get_infusion_migration_lock_status()
{
    ensure_infusion_tables();

    $lockName = infusion_migration_lock_name();
    $ownerConnectionId = null;
    try {
        $stmt = $GLOBALS['pdo']->prepare('SELECT IS_USED_LOCK(:lock_name)');
        $stmt->execute([':lock_name' => $lockName]);
        $ownerConnectionId = $stmt->fetchColumn();
        $ownerConnectionId = $ownerConnectionId !== null ? (int)$ownerConnectionId : null;
    } catch (Throwable $e) {
        $ownerConnectionId = null;
    }

    $state = null;
    try {
        $stmt = $GLOBALS['pdo']->prepare("
            SELECT ims.*, u.username AS admin_username, i.name AS infusion_name
            FROM infusion_migration_state ims
            LEFT JOIN users u ON u.id = ims.admin_user_id
            LEFT JOIN infusions i ON i.id = ims.infusion_id
            WHERE ims.lock_name = :lock_name
            LIMIT 1
        ");
        $stmt->execute([':lock_name' => $lockName]);
        $state = $stmt->fetch() ?: null;
    } catch (Throwable $e) {
        $state = null;
    }

    $details = [];
    if (!empty($state['details'])) {
        $decoded = json_decode((string)$state['details'], true);
        if (is_array($decoded)) {
            $details = $decoded;
        }
    }

    return [
        'lock_name' => $lockName,
        'active' => $ownerConnectionId !== null,
        'owner_connection_id' => $ownerConnectionId,
        'state' => $state,
        'details' => $details,
    ];
}

function get_recent_infusion_migration_activity($limit = 12)
{
    ensure_infusion_tables();

    $stmt = $GLOBALS['pdo']->prepare("
        SELECT iml.*, i.folder, i.name AS infusion_name
        FROM infusion_migration_log iml
        LEFT JOIN infusions i ON i.id = iml.infusion_id
        ORDER BY COALESCE(iml.finished_at, iml.started_at) DESC, iml.id DESC
        LIMIT :limit
    ");
    $stmt->bindValue(':limit', max(1, (int)$limit), PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function get_recent_infusion_rollback_activity($limit = 8)
{
    ensure_infusion_tables();

    $stmt = $GLOBALS['pdo']->prepare("
        SELECT irl.*, i.folder, i.name AS infusion_name
        FROM infusion_rollback_log irl
        LEFT JOIN infusions i ON i.id = irl.infusion_id
        ORDER BY irl.created_at DESC, irl.id DESC
        LIMIT :limit
    ");
    $stmt->bindValue(':limit', max(1, (int)$limit), PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function acquire_infusion_migration_lock($operation = 'upgrade', $resource = '', $timeoutSeconds = 0)
{
    $lockName = infusion_migration_lock_name();

    try {
        $stmt = $GLOBALS['pdo']->prepare('SELECT GET_LOCK(:lock_name, :timeout_seconds)');
        $stmt->bindValue(':lock_name', $lockName, PDO::PARAM_STR);
        $stmt->bindValue(':timeout_seconds', max(0, (int)$timeoutSeconds), PDO::PARAM_INT);
        $stmt->execute();
        $status = (int)$stmt->fetchColumn();
    } catch (Throwable $e) {
        $status = 0;
    }

    if ($status !== 1) {
        audit_log(current_user()['id'] ?? null, 'infusion_lock_blocked', 'infusions', null, [
            'operation' => (string)$operation,
            'resource' => (string)$resource,
            'lock_name' => $lockName,
        ]);
        throw new RuntimeException('Šiuo metu kitas administratorius jau vykdo modulio diegimą, atnaujinimą arba pašalinimą. Palaukite kelias sekundes ir bandykite dar kartą.');
    }

    save_infusion_migration_lock_state($lockName, $operation, $resource);
    return $lockName;
}

function release_infusion_migration_lock($lockName = null)
{
    $lockName = trim((string)($lockName ?: infusion_migration_lock_name()));
    if ($lockName === '') {
        return;
    }

    try {
        $stmt = $GLOBALS['pdo']->prepare('SELECT RELEASE_LOCK(:lock_name)');
        $stmt->execute([':lock_name' => $lockName]);
    } catch (Throwable $e) {
    }

    clear_infusion_migration_lock_state($lockName);
}

function with_infusion_migration_lock(callable $callback, $operation = 'upgrade', $resource = '', $timeoutSeconds = 0)
{
    $lockName = acquire_infusion_migration_lock($operation, $resource, $timeoutSeconds);

    try {
        return $callback();
    } finally {
        release_infusion_migration_lock($lockName);
    }
}

function get_infusion_core_version()
{
    return app_version();
}

function validate_infusion_dependencies(array $manifest)
{
    if (version_compare(get_infusion_core_version(), $manifest['min_core_version'], '<')) {
        throw new RuntimeException('Per sena branduolio versija siai infusion. Reikia bent ' . $manifest['min_core_version'] . '.');
    }

    if (version_compare(PHP_VERSION, (string)($manifest['min_php_version'] ?? '8.0.0'), '<')) {
        throw new RuntimeException('Per sena PHP versija siai infusion. Reikia bent ' . $manifest['min_php_version'] . '.');
    }

    foreach ((array)($manifest['required_extensions'] ?? []) as $extension) {
        $extension = trim((string)$extension);
        if ($extension !== '' && !extension_loaded($extension)) {
            throw new RuntimeException('Truksta reikalingo PHP pletinio: ' . $extension);
        }
    }

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

    foreach ((array)($manifest['conflicts'] ?? []) as $conflict) {
        $folder = trim((string)($conflict['folder'] ?? ''));
        if ($folder === '') {
            continue;
        }

        $installed = get_installed_infusion_by_folder($folder);
        if ($installed && (int)$installed['is_installed'] === 1 && (int)$installed['is_enabled'] === 1) {
            throw new RuntimeException('Infusion konfliktuoja su aktyviu moduliu: ' . $folder);
        }
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

function get_infusion_version_summary($folder, ?array $manifest = null, $installed = null)
{
    $folder = trim((string)$folder);
    $manifestData = is_array($manifest) ? $manifest : null;

    if ($manifestData === null && $folder !== '') {
        try {
            $manifestData = read_infusion_manifest($folder);
        } catch (Throwable $e) {
            $manifestData = null;
        }
    }

    if ($installed === null && $folder !== '') {
        $installed = get_installed_infusion_by_folder($folder);
    } elseif (is_numeric($installed)) {
        $installed = get_installed_infusion((int)$installed);
    }

    $isInstalled = is_array($installed) && !empty($installed['id']);
    $installedVersion = $isInstalled ? (get_installed_infusion_version((int)$installed['id']) ?: '0.0.0') : null;
    $manifestVersion = null;

    if (is_array($manifestData)) {
        $candidate = trim((string)($manifestData['version'] ?? ''));
        if ($candidate !== '') {
            $manifestVersion = $candidate;
        }
    }

    if (!$isInstalled && $manifestVersion === null) {
        $manifestVersion = '0.0.0';
    }

    $availableUpgrade = null;
    $status = 'not_installed';
    $statusLabel = 'Neidiegtas';
    $statusBadgeClass = 'text-bg-secondary';

    if ($isInstalled) {
        if ($manifestVersion === null) {
            $status = 'manifest_missing';
            $statusLabel = 'Manifest truksta';
            $statusBadgeClass = 'text-bg-danger';
        } elseif (version_compare($manifestVersion, $installedVersion, '>')) {
            $status = 'upgrade_available';
            $statusLabel = 'Yra atnaujinimas';
            $statusBadgeClass = 'text-bg-warning';
            $availableUpgrade = $manifestVersion;
        } elseif (version_compare($manifestVersion, $installedVersion, '<')) {
            $status = 'manifest_older';
            $statusLabel = 'Manifest senesnis';
            $statusBadgeClass = 'text-bg-danger';
        } else {
            $status = 'up_to_date';
            $statusLabel = 'Aktualu';
            $statusBadgeClass = 'text-bg-success';
        }
    }

    return [
        'is_installed' => $isInstalled,
        'installed_version' => $installedVersion,
        'installed_display' => $installedVersion ?? '-',
        'manifest_version' => $manifestVersion,
        'manifest_display' => $manifestVersion ?? '-',
        'available_upgrade' => $availableUpgrade,
        'available_upgrade_display' => $availableUpgrade ?? '-',
        'status' => $status,
        'status_label' => $statusLabel,
        'status_badge_class' => $statusBadgeClass,
    ];
}

function get_installed_infusions_map()
{
    $rows = $GLOBALS['pdo']->query("SELECT * FROM infusions ORDER BY id DESC")->fetchAll();
    $map = [];
    foreach ($rows as $row) {
        $folder = trim((string)($row['folder'] ?? ''));
        if ($folder !== '') {
            $map[$folder] = $row;
        }
    }

    return $map;
}

function format_infusion_module_reference(array $reference, $mode = 'dependency')
{
    $folder = trim((string)($reference['folder'] ?? ''));
    $version = trim((string)($reference['version'] ?? ''));
    if ($folder === '') {
        return '';
    }

    if ($version === '') {
        return $folder;
    }

    return $mode === 'conflict'
        ? ($folder . ' @ ' . $version)
        : ($folder . ' >= ' . $version);
}

function get_infusion_compatibility_summary($folder, ?array $manifest = null, ?array $installedFolders = null, ?array $scanned = null)
{
    $folder = trim((string)$folder);
    if ($folder === '') {
        return [
            'status' => 'unknown',
            'label' => 'Nera duomenu',
            'badge_class' => 'text-bg-secondary',
            'environment_summary' => '-',
            'dependencies_summary' => '-',
            'conflicts_summary' => '-',
            'required_extensions' => [],
            'missing_extensions' => [],
            'dependency_statuses' => [],
            'conflict_statuses' => [],
            'issues' => [],
        ];
    }

    if (!is_array($manifest)) {
        try {
            $manifest = read_infusion_manifest($folder);
        } catch (Throwable $e) {
            $manifest = null;
        }
    }

    $manifest = is_array($manifest) ? $manifest : [];
    $installedFolders = is_array($installedFolders) ? $installedFolders : get_installed_infusions_map();
    $scanned = is_array($scanned) ? $scanned : scan_infusions();

    $coreVersion = get_infusion_core_version();
    $phpVersion = PHP_VERSION;
    $minCoreVersion = trim((string)($manifest['min_core_version'] ?? '1.0.0'));
    $minPhpVersion = trim((string)($manifest['min_php_version'] ?? '8.0.0'));

    $coreOk = version_compare($coreVersion, $minCoreVersion, '>=');
    $phpOk = version_compare($phpVersion, $minPhpVersion, '>=');

    $requiredExtensions = array_values((array)($manifest['required_extensions'] ?? []));
    $missingExtensions = [];
    foreach ($requiredExtensions as $extension) {
        $extension = trim((string)$extension);
        if ($extension !== '' && !extension_loaded($extension)) {
            $missingExtensions[] = $extension;
        }
    }

    $dependencyStatuses = [];
    foreach ((array)($manifest['dependencies'] ?? []) as $dependency) {
        $label = format_infusion_module_reference((array)$dependency, 'dependency');
        if ($label === '') {
            continue;
        }

        $dependencyFolder = trim((string)($dependency['folder'] ?? ''));
        $requiredVersion = trim((string)($dependency['version'] ?? ''));
        $installedDependency = $installedFolders[$dependencyFolder] ?? null;

        if (!$installedDependency || (int)($installedDependency['is_installed'] ?? 0) !== 1 || (int)($installedDependency['is_enabled'] ?? 0) !== 1) {
            $dependencyStatuses[] = [
                'label' => $label,
                'status' => 'missing',
                'message' => 'Truksta arba isjungta',
            ];
            continue;
        }

        $installedDependencyVersion = get_installed_infusion_version((int)$installedDependency['id']) ?: ($scanned[$dependencyFolder]['version'] ?? '0.0.0');
        if ($requiredVersion !== '' && version_compare($installedDependencyVersion, $requiredVersion, '<')) {
            $dependencyStatuses[] = [
                'label' => $label,
                'status' => 'version_mismatch',
                'message' => 'Idiegta ' . $installedDependencyVersion,
            ];
            continue;
        }

        $dependencyStatuses[] = [
            'label' => $label,
            'status' => 'ok',
            'message' => 'OK',
        ];
    }

    $conflictStatuses = [];
    foreach ((array)($manifest['conflicts'] ?? []) as $conflict) {
        $label = format_infusion_module_reference((array)$conflict, 'conflict');
        if ($label === '') {
            continue;
        }

        $conflictFolder = trim((string)($conflict['folder'] ?? ''));
        $installedConflict = $installedFolders[$conflictFolder] ?? null;
        $conflictActive = $installedConflict && (int)($installedConflict['is_installed'] ?? 0) === 1 && (int)($installedConflict['is_enabled'] ?? 0) === 1;

        $conflictStatuses[] = [
            'label' => $label,
            'status' => $conflictActive ? 'conflict' : 'ok',
            'message' => $conflictActive ? 'Aktyvus konfliktas' : 'Nera aktyvaus konflikto',
        ];
    }

    $issues = [];
    if (!$coreOk) {
        $issues[] = 'Core reikia >= ' . $minCoreVersion;
    }
    if (!$phpOk) {
        $issues[] = 'PHP reikia >= ' . $minPhpVersion;
    }
    if ($missingExtensions) {
        $issues[] = 'Truksta plėtinių: ' . implode(', ', $missingExtensions);
    }

    $dependencyIssues = array_values(array_filter($dependencyStatuses, static fn ($item) => $item['status'] !== 'ok'));
    if ($dependencyIssues) {
        $issues[] = 'Priklausomybiu problemos: ' . implode(', ', array_map(static fn ($item) => $item['label'], $dependencyIssues));
    }

    $conflictIssues = array_values(array_filter($conflictStatuses, static fn ($item) => $item['status'] === 'conflict'));
    if ($conflictIssues) {
        $issues[] = 'Aktyvus konfliktas: ' . implode(', ', array_map(static fn ($item) => $item['label'], $conflictIssues));
    }

    return [
        'status' => $issues ? 'incompatible' : 'compatible',
        'label' => $issues ? 'Nesuderinamas' : 'Suderinamas',
        'badge_class' => $issues ? 'text-bg-danger' : 'text-bg-success',
        'environment_summary' => 'Core ' . ($coreOk ? 'OK' : ('>=' . $minCoreVersion)) . ' | PHP ' . ($phpOk ? 'OK' : ('>=' . $minPhpVersion)) . ' | Pletiniai ' . ($missingExtensions ? ('truksta: ' . implode(', ', $missingExtensions)) : 'OK'),
        'dependencies_summary' => $dependencyIssues ? implode(', ', array_map(static fn ($item) => $item['label'], $dependencyIssues)) : ($dependencyStatuses ? 'Visos tenkinamos' : 'Nera'),
        'conflicts_summary' => $conflictIssues ? implode(', ', array_map(static fn ($item) => $item['label'], $conflictIssues)) : ($conflictStatuses ? 'Aktyviu nera' : 'Nera'),
        'required_extensions' => $requiredExtensions,
        'missing_extensions' => $missingExtensions,
        'dependency_statuses' => $dependencyStatuses,
        'conflict_statuses' => $conflictStatuses,
        'issues' => $issues,
    ];
}

function get_infusion_health_summary($folder, ?array $manifest = null)
{
    $folder = trim((string)$folder);
    $manifest = is_array($manifest) ? $manifest : [];
    $rawManifest = read_infusion_manifest_raw($folder);
    $moduleClass = trim((string)($manifest['module_class'] ?? ''));
    $moduleClassExists = $moduleClass === '' ? true : class_exists($moduleClass);

    $errors = [];
    $warnings = [];

    if ($rawManifest === null) {
        $errors[] = 'Manifest nerastas arba klaidingas';
    }

    if (!empty($manifest['bootstrap']) && !file_exists(INFUSIONS . $folder . '/bootstrap.php')) {
        $errors[] = 'Truksta bootstrap.php';
    }

    if (!empty($manifest['admin']) && !file_exists(infusion_admin_path($folder))) {
        $errors[] = 'Truksta admin.php';
    }

    if (!empty($manifest['panel']) && !file_exists(INFUSIONS . $folder . '/panel.php')) {
        $errors[] = 'Truksta panel.php';
    }

    if ($moduleClass !== '' && !$moduleClassExists) {
        $errors[] = 'Truksta modulio klases';
    }

    if (!empty($manifest['schema']) && !file_exists(infusion_schema_path($folder)) && $moduleClass === '') {
        $errors[] = 'Truksta schema.php';
    }

    if (!is_dir(INFUSIONS . $folder . '/locale')) {
        $warnings[] = 'Nera locale/';
    }

    if (!is_dir(INFUSIONS . $folder . '/assets')) {
        $warnings[] = 'Nera assets/';
    }

    if (!empty($manifest['upgrade']) && !file_exists(infusion_upgrade_path($folder)) && !is_dir(infusion_migrations_dir($folder))) {
        $warnings[] = 'Nera upgrade.php arba migrations/';
    }

    if ($errors) {
        $status = 'error';
        $label = 'Sveikata: klaidos';
        $badgeClass = 'text-bg-danger';
        $summary = implode('; ', $errors);
    } elseif ($warnings) {
        $status = 'warning';
        $label = 'Sveikata: demesio';
        $badgeClass = 'text-bg-warning';
        $summary = implode('; ', $warnings);
    } else {
        $status = 'ok';
        $label = 'Sveikata: gera';
        $badgeClass = 'text-bg-success';
        $summary = 'Manifest, failai ir katalogai atrodo tvarkingi';
    }

    return [
        'status' => $status,
        'label' => $label,
        'badge_class' => $badgeClass,
        'summary' => $summary,
        'errors' => $errors,
        'warnings' => $warnings,
    ];
}

function get_infusion_module_actions($folder, ?array $manifest = null, $installed = null, ?array $versionSummary = null)
{
    $folder = trim((string)$folder);
    if ($folder === '') {
        return [];
    }

    if (!is_array($manifest)) {
        try {
            $manifest = read_infusion_manifest($folder);
        } catch (Throwable $e) {
            $manifest = [];
        }
    }

    if ($installed === null) {
        $installed = get_installed_infusion_by_folder($folder);
    } elseif (is_numeric($installed)) {
        $installed = get_installed_infusion((int)$installed);
    }

    $versionSummary = is_array($versionSummary)
        ? $versionSummary
        : get_infusion_version_summary($folder, $manifest, $installed);

    $isInstalled = !empty($versionSummary['is_installed']);
    $actions = [];

    $hasAdminFile = !empty($manifest['has_admin_file']) || file_exists(infusion_admin_path($folder));

    if ($isInstalled && !empty($manifest['admin']) && $hasAdminFile) {
        $actions[] = [
            'key' => 'admin',
            'label' => 'Admin',
            'kind' => 'link',
            'href' => 'infusion-admin.php?folder=' . rawurlencode($folder),
            'class' => 'btn btn-sm btn-outline-primary admin-action-button',
        ];
    }

    $settingsPage = trim((string)($manifest['settings_page'] ?? ''));
    if ($isInstalled && $settingsPage !== '') {
        $actions[] = [
            'key' => 'settings',
            'label' => 'Settings',
            'kind' => 'link',
            'href' => $settingsPage,
            'class' => 'btn btn-sm btn-outline-secondary admin-action-button',
        ];
    }

    $healthPage = trim((string)($manifest['diagnostics_page'] ?? ''));
    if ($healthPage === '') {
        $healthPage = 'infusions.php?developer=1#infusion-dev-' . rawurlencode($folder);
    }
    if ($healthPage !== '') {
        $actions[] = [
            'key' => 'health',
            'label' => 'Health',
            'kind' => 'link',
            'href' => $healthPage,
            'class' => 'btn btn-sm btn-outline-info admin-action-button',
        ];
    }

    if ($isInstalled && ($versionSummary['status'] ?? '') === 'upgrade_available') {
        $actions[] = [
            'key' => 'upgrade',
            'label' => 'Atnaujinti',
            'kind' => 'post',
            'value' => 'upgrade',
            'class' => 'btn btn-sm btn-outline-primary admin-action-button',
        ];
    }

    return $actions;
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
    $module = infusion_sdk_module($folder, (int)$infusionId, $manifest);
    if ($module) {
        $module->install();
        return;
    }

    if ($manifest['schema'] && file_exists(infusion_schema_path($folder))) {
        $INFUSION = ['id' => $infusionId, 'folder' => $folder, 'manifest' => $manifest];
        include infusion_schema_path($folder);
    }
}

function log_migration_step($infusionId, $version, $direction, $status, $message = null)
{
    $isStarted = (string)$status === 'started';
    $stmt = $GLOBALS['pdo']->prepare("
        INSERT INTO infusion_migration_log (infusion_id, step_version, direction, status, started_at, finished_at, message)
        VALUES (:iid,:v,:d,:s,NOW(),:finished_at,:m)
        ON DUPLICATE KEY UPDATE
            status = VALUES(status),
            started_at = CASE WHEN VALUES(status) = 'started' THEN VALUES(started_at) ELSE started_at END,
            finished_at = VALUES(finished_at),
            message = VALUES(message)
    ");
    $stmt->execute([
        ':iid' => (int)$infusionId,
        ':v' => (string)$version,
        ':d' => (string)$direction,
        ':s' => (string)$status,
        ':finished_at' => $isStarted ? null : date('Y-m-d H:i:s'),
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

    return with_infusion_migration_lock(function () use ($folder) {
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

            $lifecyclePayload = infusion_fire_lifecycle_hooks('before_install', [
                'folder' => $folder,
                'infusion_id' => $infusionId,
                'manifest' => $manifest,
                'operation' => 'install',
            ]);
            if (isset($lifecyclePayload['manifest']) && is_array($lifecyclePayload['manifest'])) {
                $manifest = $lifecyclePayload['manifest'];
            }

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

            infusion_fire_lifecycle_hooks('after_install', [
                'folder' => $folder,
                'infusion_id' => $infusionId,
                'manifest' => $manifest,
                'operation' => 'install',
                'result' => [
                    'installed' => true,
                    'version' => $manifest['version'],
                    'panel' => !empty($manifest['panel']),
                ],
            ]);

            if ($GLOBALS['pdo']->inTransaction()) {
                $GLOBALS['pdo']->commit();
            }
            return $infusionId;
        } catch (Throwable $e) {
            if ($GLOBALS['pdo']->inTransaction()) $GLOBALS['pdo']->rollBack();
            throw $e;
        }
    }, 'install', 'folder:' . $folder);
}

function upgrade_infusion_by_id($id)
{
    $id = (int)$id;

    return with_infusion_migration_lock(function () use ($id) {
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
            $lifecyclePayload = infusion_fire_lifecycle_hooks('before_upgrade', [
                'folder' => $infusion['folder'],
                'infusion_id' => (int)$id,
                'manifest' => $manifest,
                'operation' => 'upgrade',
                'installed_version' => $installedVersion,
                'target_version' => $targetVersion,
            ]);
            if (isset($lifecyclePayload['manifest']) && is_array($lifecyclePayload['manifest'])) {
                $manifest = $lifecyclePayload['manifest'];
            }

            $migrationDir = infusion_migrations_dir($infusion['folder']);
            if (is_dir($migrationDir)) {
                $executedSteps = run_migration_steps($infusion['folder'], (int)$id, $installedVersion, $targetVersion);
            } else {
                $module = infusion_sdk_module($infusion['folder'], (int)$id, $manifest);
                if ($module) {
                    $module->upgrade($installedVersion, $targetVersion);
                } else {
                    $upgradeFile = infusion_upgrade_path($infusion['folder']);
                    if (!file_exists($upgradeFile)) {
                        throw new RuntimeException('Atnaujinimo failas nerastas.');
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
            }

            register_infusion_permissions((int)$id, $manifest['permissions']);
            register_infusion_admin_menu((int)$id, $manifest['admin_menu']);

            $stmt = $GLOBALS['pdo']->prepare("INSERT INTO infusion_versions (infusion_id, version) VALUES (:iid,:v)");
            $stmt->execute([':iid' => (int)$id, ':v' => $targetVersion]);

            $upgradeResult = [
                'upgraded' => true,
                'from' => $installedVersion,
                'to' => $targetVersion,
                'steps' => array_map(fn($s) => $s['version'], $executedSteps),
            ];
            infusion_fire_lifecycle_hooks('after_upgrade', [
                'folder' => $infusion['folder'],
                'infusion_id' => (int)$id,
                'manifest' => $manifest,
                'operation' => 'upgrade',
                'installed_version' => $installedVersion,
                'target_version' => $targetVersion,
                'result' => $upgradeResult,
            ]);

            if ($GLOBALS['pdo']->inTransaction()) {
                $GLOBALS['pdo']->commit();
            }
            return $upgradeResult;
        } catch (Throwable $e) {
            if ($GLOBALS['pdo']->inTransaction()) $GLOBALS['pdo']->rollBack();
            try {
                rollback_migration_steps($infusion['folder'], (int)$id, $executedSteps);
            } catch (Throwable $re) {}
            throw $e;
        }
    }, 'upgrade', 'infusion:' . $id);
}

function uninstall_infusion_by_id($id)
{
    $id = (int)$id;

    return with_infusion_migration_lock(function () use ($id) {
        ensure_infusion_tables();
        $infusion = get_installed_infusion($id);
        if (!$infusion) throw new RuntimeException('Infusion nerasta.');
        $path = INFUSIONS . $infusion['folder'];

        $GLOBALS['pdo']->beginTransaction();
        try {
            $manifest = [];
            try {
                $manifest = read_infusion_manifest($infusion['folder']);
            } catch (Throwable $e) {
                $manifest = [];
            }

            $installedVersion = get_installed_infusion_version((int)$id);
            $lifecyclePayload = infusion_fire_lifecycle_hooks('before_uninstall', [
                'folder' => $infusion['folder'],
                'infusion_id' => (int)$id,
                'manifest' => $manifest,
                'operation' => 'uninstall',
                'installed_version' => $installedVersion,
            ]);
            if (isset($lifecyclePayload['manifest']) && is_array($lifecyclePayload['manifest'])) {
                $manifest = $lifecyclePayload['manifest'];
            }

            $module = infusion_sdk_module($infusion['folder'], (int)$id, $manifest ?: null);
            if ($module) {
                $module->uninstall();
            } elseif (file_exists($path . '/uninstall.php')) {
                $INFUSION = ['id' => (int)$id, 'folder' => $infusion['folder']];
                include $path . '/uninstall.php';
            }
            $GLOBALS['pdo']->prepare("DELETE FROM infusion_admin_menu WHERE infusion_id = :id")->execute([':id' => (int)$id]);
            $GLOBALS['pdo']->prepare("DELETE FROM infusion_panels WHERE infusion_id = :id")->execute([':id' => (int)$id]);
            $GLOBALS['pdo']->prepare("DELETE FROM infusion_versions WHERE infusion_id = :id")->execute([':id' => (int)$id]);
            $GLOBALS['pdo']->prepare("DELETE FROM infusion_migration_log WHERE infusion_id = :id")->execute([':id' => (int)$id]);
            $GLOBALS['pdo']->prepare("DELETE FROM infusion_rollback_log WHERE infusion_id = :id")->execute([':id' => (int)$id]);
            $GLOBALS['pdo']->prepare("DELETE FROM infusions WHERE id = :id")->execute([':id' => (int)$id]);

            infusion_fire_lifecycle_hooks('after_uninstall', [
                'folder' => $infusion['folder'],
                'infusion_id' => (int)$id,
                'manifest' => $manifest,
                'operation' => 'uninstall',
                'installed_version' => $installedVersion,
                'result' => [
                    'uninstalled' => true,
                    'folder' => $infusion['folder'],
                ],
            ]);

            if ($GLOBALS['pdo']->inTransaction()) {
                $GLOBALS['pdo']->commit();
            }
        } catch (Throwable $e) {
            if ($GLOBALS['pdo']->inTransaction()) $GLOBALS['pdo']->rollBack();
            throw $e;
        }
    }, 'uninstall', 'infusion:' . $id);
}

function load_enabled_infusions()
{
    $stmt = $GLOBALS['pdo']->query("SELECT * FROM infusions WHERE is_installed = 1 AND is_enabled = 1 ORDER BY id ASC");
    foreach ($stmt->fetchAll() as $infusion) {
        $manifest = [];
        try {
            $manifest = read_infusion_manifest($infusion['folder']);
        } catch (Throwable $e) {
            $manifest = [];
        }

        $bootstrap = INFUSIONS . $infusion['folder'] . '/bootstrap.php';
        if (file_exists($bootstrap)) {
            include_once $bootstrap;
        }

        $module = infusion_sdk_module($infusion['folder'], (int)$infusion['id'], $manifest ?: null);
        if ($module) {
            $module->registerHooks(infusion_hook_registry());
            $module->boot();
        }
    }
}

function render_infusion_panel($folder, array $panelData = [])
{
    $installed = get_installed_infusion_by_folder($folder);
    $infusionId = $installed ? (int)$installed['id'] : (int)($panelData['infusion_id'] ?? 0);

    $manifest = [];
    try {
        $manifest = read_infusion_manifest($folder);
    } catch (Throwable $e) {
        $manifest = [];
    }

    panel_render_begin($panelData);

    try {
        $module = infusion_sdk_module($folder, $infusionId, $manifest ?: null);
        $html = '';

        if ($module) {
            ob_start();
            $result = $module->renderPanel($panelData);
            $html = (string)ob_get_clean() . (string)$result;
        } else {
            $panelFile = INFUSIONS . $folder . '/panel.php';
            if (!file_exists($panelFile)) {
                return ['html' => '', 'custom_shell' => false];
            }

            ob_start();
            include $panelFile;
            $html = (string)ob_get_clean();
        }

        $context = [
            'folder' => $folder,
            'panel' => $panelData,
            'infusion_id' => $infusionId,
            'manifest' => $manifest,
        ];

        $html = (string)infusion_apply_filters('infusion.panel.output', $html, $context);
        $html = (string)infusion_apply_filters('infusion.panel.output.' . $folder, $html, $context);

        return [
            'html' => $html,
            'custom_shell' => panel_render_uses_custom_shell(),
        ];
    } finally {
        panel_render_end();
    }
}

function render_infusion_admin($folder)
{
    $installed = get_installed_infusion_by_folder($folder);
    $manifest = read_infusion_manifest($folder);
    $module = infusion_sdk_module($folder, $installed ? (int)$installed['id'] : 0, $manifest);

    if ($module) {
        echo $module->renderAdmin();
        return;
    }

    $path = infusion_admin_path($folder);
    if (!file_exists($path)) throw new RuntimeException('Infusion admin failas nerastas.');
    include $path;
}
