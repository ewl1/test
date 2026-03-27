<?php
if (!defined('BASEDIR')) define('BASEDIR', __DIR__ . '/');

define("ADMIN", BASEDIR."administration/");
define("CLASSES", BASEDIR."includes/classes/");
define("INFUSIONS", BASEDIR."infusions/");
define("IMAGES", BASEDIR."images/");
define("INCLUDES", BASEDIR."includes/");
define("THEMES", BASEDIR."themes/");

function detect_site_url()
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
    $baseDir = realpath(rtrim(BASEDIR, '/\\'));
    $basePath = '';

    if ($documentRoot && $baseDir) {
        $documentRoot = str_replace('\\', '/', rtrim($documentRoot, '/\\'));
        $baseDir = str_replace('\\', '/', rtrim($baseDir, '/\\'));
        if (strpos($baseDir, $documentRoot) === 0) {
            $basePath = substr($baseDir, strlen($documentRoot));
        }
    }

    if ($basePath === '' && isset($_SERVER['SCRIPT_NAME'])) {
        $scriptPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        $basePath = $scriptPath === '/' ? '' : rtrim($scriptPath, '/');
    }

    return $scheme . '://' . $host . $basePath;
}

$configPath = BASEDIR . 'config.php';
if (is_file($configPath)) {
    require_once $configPath;
}

if (!defined('APP_NAME')) define('APP_NAME', 'Mini CMS Pro');
if (!defined('APP_VERSION')) define('APP_VERSION', '1.0.0');
if (!defined('SITE_URL')) define('SITE_URL', detect_site_url());
if (!defined('CURRENT_THEME')) define('CURRENT_THEME', 'default');
if (!defined('ADMIN_THEME')) define('ADMIN_THEME', CURRENT_THEME);
if (!defined('TIMEZONE')) define('TIMEZONE', 'Europe/Vilnius');
if (!defined('MAINTENANCE_MODE')) define('MAINTENANCE_MODE', false);
date_default_timezone_set(TIMEZONE);

function public_path($path = '') { return SITE_URL . '/' . ltrim($path, '/'); }
function redirect($path) { header('Location: ' . $path); exit; }
