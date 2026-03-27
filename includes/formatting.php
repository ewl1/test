<?php
function escape_html($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function escape_attr($value)
{
    return escape_html($value);
}

function is_safe_output_url($url, array $allowedSchemes = ['http', 'https', 'mailto', 'tel'], $allowRelative = true)
{
    $url = trim((string)$url);
    if ($url === '') {
        return false;
    }

    if (preg_match('/[\x00-\x1F\x7F]/', $url)) {
        return false;
    }

    if (preg_match('/^\s*(javascript|data|vbscript):/i', $url)) {
        return false;
    }

    $parts = parse_url($url);
    if ($parts === false) {
        return false;
    }

    if (!empty($parts['scheme'])) {
        return in_array(mb_strtolower((string)$parts['scheme']), $allowedSchemes, true);
    }

    return $allowRelative;
}

function escape_url($url, $fallback = '#')
{
    $url = trim((string)$url);
    $fallback = (string)$fallback;

    if (!is_safe_output_url($url)) {
        return escape_html($fallback);
    }

    return escape_html($url);
}

function normalize_local_path($path, $fallback = 'index.php')
{
    $path = trim((string)$path);
    $fallback = ltrim((string)$fallback, '/');
    if ($fallback === '') {
        $fallback = 'index.php';
    }

    if ($path === '') {
        return $fallback;
    }

    if (preg_match('/[\x00-\x1F\x7F]/', $path) || strpos($path, '..') !== false) {
        return $fallback;
    }

    $siteUrl = defined('SITE_URL') ? (string)SITE_URL : '';
    $siteHost = (string)(parse_url($siteUrl, PHP_URL_HOST) ?? '');
    $sitePort = parse_url($siteUrl, PHP_URL_PORT);
    $siteBasePath = trim((string)(parse_url($siteUrl, PHP_URL_PATH) ?? ''), '/');

    if (preg_match('#^(?:https?:)?//#i', $path)) {
        $parts = parse_url($path);
        if ($parts === false) {
            return $fallback;
        }

        $host = (string)($parts['host'] ?? '');
        if ($siteHost !== '' && strcasecmp($host, $siteHost) !== 0) {
            return $fallback;
        }

        if ($sitePort !== false && $sitePort !== null && isset($parts['port']) && (int)$parts['port'] !== (int)$sitePort) {
            return $fallback;
        }

        $path = (string)($parts['path'] ?? '');
        if (!empty($parts['query'])) {
            $path .= '?' . $parts['query'];
        }
        if (!empty($parts['fragment'])) {
            $path .= '#' . $parts['fragment'];
        }
    }

    $path = str_replace('\\', '/', $path);
    if (preg_match('/^[a-z][a-z0-9+\-.]*:/i', $path)) {
        return $fallback;
    }

    $path = ltrim($path, '/');
    if ($siteBasePath !== '') {
        if ($path === $siteBasePath) {
            return 'index.php';
        }

        $prefix = $siteBasePath . '/';
        if (strpos($path, $prefix) === 0) {
            $path = substr($path, strlen($prefix));
        }
    }

    return $path !== '' ? $path : $fallback;
}

function redirect_target_url($path, $fallback = 'index.php')
{
    return public_path(normalize_local_path($path, $fallback));
}

function asset_path($path = '')
{
    $relativePath = ltrim((string)$path, '/');
    $url = public_path($relativePath);
    if ($relativePath === '') {
        return $url;
    }

    $absolutePath = rtrim(BASEDIR, '/\\') . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
    if (!is_file($absolutePath)) {
        return $url;
    }

    $version = @filemtime($absolutePath);
    if (!$version) {
        return $url;
    }

    return $url . (str_contains($url, '?') ? '&' : '?') . 'v=' . rawurlencode((string)$version);
}

function user_avatar_url($user)
{
    if (!empty($user['avatar'])) {
        return public_path('uploads/avatars/' . rawurlencode((string)$user['avatar']));
    }

    return 'https://www.gravatar.com/avatar/' . md5(strtolower(trim((string)($user['email'] ?? '')))) . '?d=mp&s=80';
}

function format_dt($value, $fallback = '')
{
    if (!$value) {
        return $fallback;
    }

    $timestamp = strtotime((string)$value);
    if ($timestamp === false) {
        return $fallback;
    }

    return date('Y-m-d H:i', $timestamp);
}
