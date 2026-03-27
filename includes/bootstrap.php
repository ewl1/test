<?php
function request_is_secure()
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }

    $forwardedProto = strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
    return $forwardedProto === 'https';
}

function configure_session_security()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $isSecure = request_is_secure();
    $cookieParams = session_get_cookie_params();

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', $isSecure ? '1' : '0');
    ini_set('session.cookie_samesite', 'Lax');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $cookieParams['path'] ?? '/',
        'domain' => $cookieParams['domain'] ?? '',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

configure_session_security();
session_start();

function send_security_headers()
{
    if (headers_sent()) {
        return;
    }

    header_remove('X-Powered-By');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Cross-Origin-Opener-Policy: same-origin');
    header('Cross-Origin-Resource-Policy: same-site');

    $csp = implode('; ', [
        "default-src 'self'",
        "base-uri 'self'",
        "form-action 'self'",
        "frame-ancestors 'self'",
        "img-src 'self' data: https://www.gravatar.com",
        "style-src 'self'",
        "style-src-elem 'self'",
        "style-src-attr 'none'",
        "script-src 'self'",
        "script-src-elem 'self'",
        "script-src-attr 'none'",
        "font-src 'self' data:",
        "connect-src 'self'",
        "frame-src 'self'",
        "manifest-src 'self'",
        "media-src 'self'",
        "object-src 'none'",
    ]);
    header('Content-Security-Policy: ' . $csp);

    if (request_is_secure()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

send_security_headers();

require_once dirname(__DIR__) . '/maincore.php';
require_once INCLUDES . 'http.php';
register_http_error_handlers();

$logDir = BASEDIR . 'logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}
error_reporting(E_ALL);
ini_set('log_errors', '1');
ini_set('error_log', $logDir . '/php-error.log');
if (PHP_SAPI !== 'cli') {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
}

require_once INCLUDES . 'db.php';
require_once INCLUDES . 'system.php';
require_once INCLUDES . 'formatting.php';
require_once INCLUDES . 'security.php';
require_once INCLUDES . 'settings.php';
require_once INCLUDES . 'audit.php';
require_once INCLUDES . 'ratelimit.php';
require_once INCLUDES . 'auth.php';
require_once INCLUDES . 'validation.php';
require_once INCLUDES . 'mail.php';
require_once INCLUDES . 'password_resets.php';
require_once INCLUDES . 'bbcode.php';
require_once INCLUDES . 'permissions.php';
require_once INCLUDES . 'panels.php';
require_once INCLUDES . 'user_profiles.php';
require_once INCLUDES . 'functions/pagination.php';
require_once INCLUDES . 'functions/output.php';
require_once INCLUDES . 'functions/posts.php';
require_once INCLUDES . 'functions/shouts.php';
require_once INCLUDES . 'functions/users.php';

if ((setting('site_maintenance', MAINTENANCE_MODE ? '1' : '0') === '1') && !defined('IN_ADMIN')) {
    require BASEDIR . 'maintenance.php';
    exit;
}

require_once INCLUDES . 'infusions.php';
load_enabled_infusions();
