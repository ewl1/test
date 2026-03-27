<?php
function app_version()
{
    return defined('APP_VERSION') ? (string)APP_VERSION : '1.0.0';
}

function ini_size_to_bytes($value)
{
    $value = trim((string)$value);
    if ($value === '') {
        return 0;
    }

    $unit = strtolower(substr($value, -1));
    $number = (float)$value;

    return match ($unit) {
        'g' => (int)($number * 1024 * 1024 * 1024),
        'm' => (int)($number * 1024 * 1024),
        'k' => (int)($number * 1024),
        default => (int)$number,
    };
}

function format_bytes_human($bytes)
{
    $bytes = max(0, (int)$bytes);
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $power = 0;

    while ($bytes >= 1024 && $power < count($units) - 1) {
        $bytes /= 1024;
        $power++;
    }

    return number_format($bytes, $power === 0 ? 0 : 2) . ' ' . $units[$power];
}

function is_opcache_available()
{
    return extension_loaded('Zend OPcache') || extension_loaded('opcache');
}

function is_opcache_enabled()
{
    return is_opcache_available() && (bool)ini_get('opcache.enable');
}

function opcache_summary()
{
    if (!is_opcache_enabled() || !function_exists('opcache_get_status')) {
        return null;
    }

    $status = @opcache_get_status(false);
    if (!is_array($status)) {
        return null;
    }

    $memory = $status['memory_usage'] ?? [];
    $stats = $status['opcache_statistics'] ?? [];

    return [
        'enabled' => true,
        'cached_scripts' => (int)($stats['num_cached_scripts'] ?? 0),
        'hits' => (int)($stats['hits'] ?? 0),
        'misses' => (int)($stats['misses'] ?? 0),
        'hit_rate' => isset($stats['opcache_hit_rate']) ? round((float)$stats['opcache_hit_rate'], 2) : null,
        'used_memory' => (int)($memory['used_memory'] ?? 0),
        'free_memory' => (int)($memory['free_memory'] ?? 0),
        'wasted_memory' => (int)($memory['wasted_memory'] ?? 0),
    ];
}

function path_status($path)
{
    $exists = file_exists($path);
    $writableTarget = $exists ? $path : dirname($path);

    return [
        'path' => $path,
        'exists' => $exists,
        'writable' => is_writable($writableTarget),
        'is_dir' => is_dir($path),
    ];
}

function app_runtime_diagnostics()
{
    $logDir = BASEDIR . 'logs';
    $uploadDir = BASEDIR . 'uploads';
    $avatarDir = $uploadDir . '/avatars';

    return [
        'application' => [
            'name' => defined('APP_NAME') ? APP_NAME : 'Mini CMS',
            'version' => app_version(),
            'site_url' => defined('SITE_URL') ? SITE_URL : '',
            'basedir' => BASEDIR,
            'maintenance' => setting('site_maintenance', MAINTENANCE_MODE ? '1' : '0') === '1',
        ],
        'php' => [
            'version' => PHP_VERSION,
            'sapi' => PHP_SAPI,
            'loaded_ini' => php_ini_loaded_file() ?: 'Nerasta',
            'memory_limit_raw' => ini_get('memory_limit'),
            'memory_limit' => format_bytes_human(ini_size_to_bytes(ini_get('memory_limit'))),
            'upload_max_filesize_raw' => ini_get('upload_max_filesize'),
            'upload_max_filesize' => format_bytes_human(ini_size_to_bytes(ini_get('upload_max_filesize'))),
            'post_max_size_raw' => ini_get('post_max_size'),
            'post_max_size' => format_bytes_human(ini_size_to_bytes(ini_get('post_max_size'))),
            'max_execution_time' => (int)ini_get('max_execution_time'),
            'timezone' => date_default_timezone_get(),
        ],
        'server' => [
            'software' => (string)($_SERVER['SERVER_SOFTWARE'] ?? 'CLI'),
            'document_root' => (string)($_SERVER['DOCUMENT_ROOT'] ?? ''),
            'https' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'opcache' => opcache_summary(),
        ],
        'paths' => [
            'config' => path_status(BASEDIR . 'config.php'),
            'logs' => path_status($logDir),
            'uploads' => path_status($uploadDir),
            'avatars' => path_status($avatarDir),
        ],
        'extensions' => [
            'pdo_mysql' => extension_loaded('pdo_mysql'),
            'mbstring' => extension_loaded('mbstring'),
            'curl' => extension_loaded('curl'),
            'gd' => extension_loaded('gd'),
            'intl' => extension_loaded('intl'),
            'openssl' => extension_loaded('openssl'),
        ],
    ];
}
