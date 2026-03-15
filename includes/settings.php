<?php
function setting($name, $default = null)
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            foreach ($GLOBALS['pdo']->query("SELECT setting_key, setting_value FROM site_settings")->fetchAll() as $row) {
                $cache[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Throwable $e) { $cache = []; }
    }
    return $cache[$name] ?? $default;
}
function save_setting($key, $value)
{
    $stmt = $GLOBALS['pdo']->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (:k,:v) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
    return $stmt->execute([':k'=>$key, ':v'=>(string)$value]);
}
function available_themes()
{
    $themes = [];
    foreach (glob(THEMES . '*', GLOB_ONLYDIR) as $dir) $themes[] = basename($dir);
    sort($themes);
    return $themes;
}
