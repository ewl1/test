<?php
function settings_table_name()
{
    static $table = null;
    if ($table !== null) {
        return $table;
    }

    foreach (['settings', 'site_settings'] as $candidate) {
        try {
            $stmt = $GLOBALS['pdo']->prepare("SHOW TABLES LIKE :table_name");
            $stmt->execute([':table_name' => $candidate]);
            if ($stmt->fetchColumn()) {
                $table = $candidate;
                return $table;
            }
        } catch (Throwable $e) {
        }
    }

    $table = 'settings';
    return $table;
}

function load_settings_cache()
{
    $cache = [];

    try {
        $table = settings_table_name();
        foreach ($GLOBALS['pdo']->query("SELECT setting_key, setting_value FROM {$table}")->fetchAll() as $row) {
            $cache[$row['setting_key']] = $row['setting_value'];
        }
    } catch (Throwable $e) {
    }

    return $cache;
}

function setting($name, $default = null)
{
    if (!array_key_exists('_settings_cache', $GLOBALS)) {
        $GLOBALS['_settings_cache'] = load_settings_cache();
    }

    return $GLOBALS['_settings_cache'][$name] ?? $default;
}

function save_setting($key, $value)
{
    $table = settings_table_name();
    $stmt = $GLOBALS['pdo']->prepare("INSERT INTO {$table} (setting_key, setting_value) VALUES (:k,:v) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
    $result = $stmt->execute([':k' => $key, ':v' => (string)$value]);
    unset($GLOBALS['_settings_cache']);
    return $result;
}

function available_themes()
{
    $themes = [];
    foreach (glob(THEMES . '*', GLOB_ONLYDIR) as $dir) $themes[] = basename($dir);
    sort($themes);
    return $themes;
}
